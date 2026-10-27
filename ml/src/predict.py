"""Tahmin: bir ürün için önümüzdeki 7 günün satış tahmini.

Yöntem her istekte otomatik seçilir:
  ml                 model bu ürünü tanıyor ve en az 35 günlük geçmiş var
  seasonal_average   en az 28 gün geçmiş var: son 4 haftada aynı günün ortalaması
  history_average    daha az geçmiş var (yeni ürün): eldeki günlerin ortalaması
  no_data            hiç hareket yok: tahmin yapılamaz

Kampanyalar: ML modeli kampanyayı (on_promo, price_ratio) kendisi hesaba katar.
Basit yöntemler ortalamayı kampanyasız günlerden alır, sonra kampanya günlerinin tahminini
bu ürünün geçmiş kampanyalarında ölçülen artışla çarpar (geçmiş kampanya azsa %30 artış varsayılır).

Özellikler eğitimdeki kodla (features.build_features) üretilir;
böylece eğitim ve tahmin birbirinden farklılaşamaz.
"""
from __future__ import annotations

import joblib
import numpy as np
import pandas as pd

from . import decision
from .data import load_daily_sales, load_delivery_every, load_products, load_promotions
from .features import FEATURES, HORIZON, MIN_HISTORY_DAYS, add_promotions, build_features
from .train import predict_units


DEFAULT_PROMO_UPLIFT = 1.3  # geçmiş kampanya azsa: kampanya günü %30 fazla satış
MIN_PROMO_DAYS = 3          # artışı ölçmek için gereken en az geçmiş kampanya günü
MAX_PROMO_UPLIFT = 3.0      # ölçülen artış en fazla 3 kat kabul edilir


def load_bundle(path) -> dict:
    return joblib.load(path)


def _forecast_ml(bundle, sales, products, promotions, future_days) -> np.ndarray:
    future = pd.DataFrame({"date": future_days, "product_id": sales["product_id"].iloc[0], "units_sold": np.nan})
    frame = pd.concat([sales, future], ignore_index=True)
    feats, _ = build_features(frame, products, product_ids=bundle["product_ids"], promotions=promotions)
    feats = feats[feats["date"].isin(future_days)]
    return predict_units(bundle["model"], feats, bundle.get("features", FEATURES))


def _forecast_seasonal(history: pd.Series, future_days) -> np.ndarray:
    """history: sadece kampanyasız günlerin satışları."""
    out = []
    for day in future_days:
        same_weekday = [history.get(day - pd.Timedelta(weeks=w)) for w in range(1, 5)]
        same_weekday = [v for v in same_weekday if v is not None]
        out.append(np.mean(same_weekday) if same_weekday else history.mean())
    return np.array(out)


def promo_uplift(sales: pd.DataFrame) -> tuple[float, bool]:
    """Kampanya günü kaç kat fazla satılıyor? (çarpan, bu üründe ölçüldü mü?)"""
    promo = sales["on_promo"] == 1
    base = sales.loc[~promo, "units_sold"].mean() if (~promo).any() else 0.0
    if promo.sum() >= MIN_PROMO_DAYS and base > 0:
        ratio = sales.loc[promo, "units_sold"].mean() / base
        return float(np.clip(ratio, 1.0, MAX_PROMO_UPLIFT)), True
    return DEFAULT_PROMO_UPLIFT, False


def _apply_promotions(sales, future_promo: np.ndarray, values: np.ndarray, warnings: list[str]) -> np.ndarray:
    """Sadece basit yöntemler için: kampanya günlerinde tahmini artırır."""
    if not future_promo.any():
        return values
    uplift, measured = promo_uplift(sales)
    source = "bu ürünün geçmiş kampanyalarından ölçüldü" if measured else "varsayılan, geçmiş kampanya az"
    warnings.append(f"kampanya günlerinde tahmin x{uplift:.2f} artırıldı ({source})")
    return np.where(future_promo, values * uplift, values)


def forecast(bundle: dict | None, product_id: int) -> dict:
    products = load_products(product_id)
    if products.empty:
        raise LookupError(f"ürün bulunamadı: {product_id}")

    sales = load_daily_sales(product_id)
    if sales.empty:
        return {"product_id": product_id, "method": "no_data", "forecast": [], "total_next_7_days": None,
                "warnings": ["bu ürünün hiç hareketi yok, tahmin yapılamıyor"]}

    last_day = sales["date"].max()
    future_days = pd.date_range(last_day + pd.Timedelta(days=1), periods=HORIZON, freq="D")
    n_days = len(sales)

    # Geçmiş ve gelecek günlerin kampanya bilgisi
    promotions = load_promotions(product_id)
    sales = add_promotions(sales, promotions)
    future = add_promotions(pd.DataFrame({"date": future_days, "product_id": product_id}), promotions)
    future_promo = future["on_promo"].to_numpy() == 1
    known = bundle is not None and product_id in bundle["product_ids"]
    warnings = []

    if known and n_days >= MIN_HISTORY_DAYS:
        method, values = "ml", _forecast_ml(bundle, sales[["date", "product_id", "units_sold"]], products,
                                            promotions, future_days)
    else:
        if bundle is None:
            warnings.append("model henüz eğitilmedi (python -m src.train)")
        elif not known and n_days >= MIN_HISTORY_DAYS:
            warnings.append("ürün eğitimden sonra eklendi: model yeniden eğitilene kadar basit ortalama kullanılıyor")
        # Ortalama kampanyasız günlerden alınır (hepsi kampanyalıysa tüm günler)
        regular = sales[sales["on_promo"] == 0]
        if regular.empty:
            regular = sales
        history = regular.set_index("date")["units_sold"]
        if n_days >= 28:
            method, values = "seasonal_average", _forecast_seasonal(history, future_days)
        else:
            warnings.append(f"sadece {n_days} günlük geçmiş var, tahmin bu günlerin ortalaması")
            method, values = "history_average", np.full(HORIZON, history.mean())
        values = _apply_promotions(sales, future_promo, values, warnings)

    return {
        "product_id": product_id,
        "method": method,
        "forecast": [{"date": str(d.date()), "units": round(float(v), 2), "on_promo": bool(p)}
                     for d, v, p in zip(future_days, values, future_promo)],
        "total_next_7_days": round(float(np.sum(values)), 2),
        "warnings": warnings,
    }


def order_suggestion(bundle: dict | None, product_id: int, current_stock: float) -> dict:
    """Kaç tane sipariş vermeli? = teslimata kadar beklenen satış + yedek stok - elimdeki stok

    Hesabın ayrıntısı decision.py'de: yedek stok tahmin hatasına ve ürünün bozulup
    bozulmadığına göre değişir, sipariş raf ömründen uzun bir süreyi karşılamaz.
    """
    fc = forecast(bundle, product_id)
    if fc["total_next_7_days"] is None:
        return {"product_id": product_id, "method": fc["method"], "suggested_order": None,
                "warnings": fc["warnings"]}

    product = load_products(product_id).iloc[0]
    shelf = None if pd.isna(product["shelf_life_days"]) else int(product["shelf_life_days"])
    price = float(product["price"])
    purchase = product["purchase_price"]
    cost = decision.unit_cost(price, purchase)
    warnings = list(fc["warnings"])
    if not decision.has_purchase_price(purchase):
        warnings.append("alış fiyatı girilmemiş, satış fiyatının %60'ı varsayıldı")

    every = load_delivery_every(product_id).get(product_id)
    if every is None:
        warnings.append(f"teslimat aralığı bilinmiyor (son 90 günde az stok girişi var), "
                        f"{decision.DEFAULT_COVER_DAYS} gün varsayıldı")
    days = decision.cover_days(every, shelf)
    daily = decision.extend_forecast([d["units"] for d in fc["forecast"]], days)
    sigma = decision.forecast_error_std(load_daily_sales(product_id)["units_sold"])
    service = decision.service_level(price, shelf, days, cost)
    order = decision.order_quantity(daily, current_stock, sigma, service)

    return {
        "product_id": product_id,
        "method": fc["method"],
        "delivery_every_days": every,
        "cover_days": days,
        "service_level": round(service, 2),
        "unit_cost": round(cost, 2),
        "cost_is_estimated": not decision.has_purchase_price(purchase),
        "expected_sales": order["expected_sales"],
        "safety_stock": order["safety_stock"],
        "current_stock": current_stock,
        "suggested_order": order["suggested_order"],
        "warnings": warnings,
    }


def waste_risk(bundle: dict | None, product_id: int, current_stock: float, shelf_life_days: int) -> dict:
    """Ne kadarı fire olur? = elimdeki stok - bozulmadan önce satılabilecek miktar"""
    fc = forecast(bundle, product_id)
    if fc["total_next_7_days"] is None:
        return {"product_id": product_id, "method": fc["method"], "units_at_risk": None,
                "risk_level": None, "warnings": fc["warnings"]}

    daily = [d["units"] for d in fc["forecast"]]
    if shelf_life_days <= len(daily):
        sellable = sum(daily[:shelf_life_days])
    else:
        sellable = sum(daily) / len(daily) * shelf_life_days

    at_risk = max(0.0, current_stock - sellable)
    ratio = at_risk / current_stock if current_stock > 0 else 0
    if ratio == 0:
        level = "none"
    elif ratio < 0.1:
        level = "low"
    elif ratio < 0.3:
        level = "medium"
    else:
        level = "high"

    return {
        "product_id": product_id,
        "method": fc["method"],
        "current_stock": current_stock,
        "shelf_life_days": shelf_life_days,
        "sellable_before_expiry": round(sellable, 2),
        "units_at_risk": round(at_risk, 2),
        "risk_level": level,
        "warnings": fc["warnings"],
    }
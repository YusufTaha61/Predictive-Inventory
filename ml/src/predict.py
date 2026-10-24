"""Tahmin: bir ürün için önümüzdeki 7 günün satış tahmini.

Yöntem her istekte otomatik seçilir:
  ml                 model bu ürünü tanıyor ve en az 35 günlük geçmiş var
  seasonal_average   en az 28 gün geçmiş var: son 4 haftada aynı günün ortalaması
  history_average    daha az geçmiş var (yeni ürün): eldeki günlerin ortalaması
  no_data            hiç hareket yok: tahmin yapılamaz

Özellikler eğitimdeki kodla (features.build_features) üretilir;
böylece eğitim ve tahmin birbirinden farklılaşamaz.
"""
from __future__ import annotations

import math
import joblib
import numpy as np
import pandas as pd

from .data import load_daily_sales, load_products
from .features import HORIZON, MIN_HISTORY_DAYS, build_features
from .train import predict_units


def load_bundle(path) -> dict:
    return joblib.load(path)


def _forecast_ml(bundle, sales, products, future_days) -> np.ndarray:
    future = pd.DataFrame({"date": future_days, "product_id": sales["product_id"].iloc[0], "units_sold": np.nan})
    frame = pd.concat([sales, future], ignore_index=True)
    feats, _ = build_features(frame, products, product_ids=bundle["product_ids"])
    feats = feats[feats["date"].isin(future_days)]
    return predict_units(bundle["model"], feats)


def _forecast_seasonal(history: pd.Series, future_days) -> np.ndarray:
    out = []
    for day in future_days:
        same_weekday = [history.get(day - pd.Timedelta(weeks=w)) for w in range(1, 5)]
        same_weekday = [v for v in same_weekday if v is not None]
        out.append(np.mean(same_weekday) if same_weekday else history.mean())
    return np.array(out)


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
    known = bundle is not None and product_id in bundle["product_ids"]
    warnings = []

    if known and n_days >= MIN_HISTORY_DAYS:
        method, values = "ml", _forecast_ml(bundle, sales, products, future_days)
    else:
        if bundle is None:
            warnings.append("model henüz eğitilmedi (python -m src.train)")
        elif not known and n_days >= MIN_HISTORY_DAYS:
            warnings.append("ürün eğitimden sonra eklendi: model yeniden eğitilene kadar basit ortalama kullanılıyor")
        history = sales.set_index("date")["units_sold"]
        if n_days >= 28:
            method, values = "seasonal_average", _forecast_seasonal(history, future_days)
        else:
            warnings.append(f"sadece {n_days} günlük geçmiş var, tahmin bu günlerin ortalaması")
            method, values = "history_average", np.full(HORIZON, history.mean())

    return {
        "product_id": product_id,
        "method": method,
        "forecast": [{"date": str(d.date()), "units": round(float(v), 2)} for d, v in zip(future_days, values)],
        "total_next_7_days": round(float(np.sum(values)), 2),
        "warnings": warnings,
    }


SAFETY_RATIO = 0.2  # %20 yedek stok


def order_suggestion(bundle: dict | None, product_id: int, current_stock: float) -> dict:
    """Kaç tane sipariş vermeli? = 7 günlük satış + %20 yedek - elimdeki stok"""
    fc = forecast(bundle, product_id)
    total = fc["total_next_7_days"]
    if total is None:
        return {"product_id": product_id, "method": fc["method"], "suggested_order": None,
                "warnings": fc["warnings"]}

    safety = total * SAFETY_RATIO
    need = total + safety - current_stock
    return {
        "product_id": product_id,
        "method": fc["method"],
        "expected_sales_7_days": total,
        "safety_stock": round(safety, 2),
        "current_stock": current_stock,
        "suggested_order": max(0, math.ceil(need)),
        "warnings": fc["warnings"],
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
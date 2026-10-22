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

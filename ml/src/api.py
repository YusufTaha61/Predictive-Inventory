"""Tahmin API'si (FastAPI). ml klasöründe başlat:

    uvicorn src.api:app --reload --port 8001

Adresler:
    GET  /health                  servis çalışıyor mu, hangi model yüklü
    GET  /forecast/{product_id}   ürünün önümüzdeki 7 günlük satış tahmini
    POST /reload                  model yeniden eğitildikten sonra yeni modeli yükler
    GET  /order-suggestion/{id}   kaç tane sipariş verilmeli (?current_stock=)
    GET  /waste-risk/{id}         ne kadarı fire olur (?current_stock=&shelf_life_days=)

Otomatik dokümantasyon: http://127.0.0.1:8001/docs

Not: Bu servis sadece kendi bilgisayarında (127.0.0.1) çalışır ve Laravel tarafından çağrılır.
Hangi ürünü kimin görebileceğini Laravel kontrol eder.
"""
from __future__ import annotations

from fastapi import FastAPI, HTTPException, Query

from . import predict
from .train import MODEL_PATH

app = FastAPI(title="Predictive Inventory ML API", version="0.1.0")
_bundle: dict | None = None


def get_bundle() -> dict | None:
    """Model dosyası ilk istekte yüklenir; dosya yoksa None (basit yöntemler kullanılır)."""
    global _bundle
    if _bundle is None and MODEL_PATH.exists():
        _bundle = predict.load_bundle(MODEL_PATH)
    return _bundle


@app.get("/health")
def health():
    b = get_bundle()
    if b is None:
        return {"status": "ok", "model": None, "trained_until": None}
    return {"status": "ok", "model": b["model_name"], "trained_until": b["trained_until"], "metrics": b["metrics"]}


@app.get("/forecast/{product_id}")
def forecast(product_id: int):
    try:
        return predict.forecast(get_bundle(), product_id)
    except LookupError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc


@app.post("/reload")
def reload():
    global _bundle
    if not MODEL_PATH.exists():
        raise HTTPException(status_code=404, detail="model dosyası yok, önce python -m src.train çalıştır")
    _bundle = predict.load_bundle(MODEL_PATH)
    return {"status": "reloaded", "model": _bundle["model_name"], "trained_until": _bundle["trained_until"]}



@app.get("/order-suggestion/{product_id}")
def get_order_suggestion(product_id: int, current_stock: float = Query(..., ge=0)):
    try:
        return predict.order_suggestion(get_bundle(), product_id, current_stock)
    except LookupError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc


@app.get("/waste-risk/{product_id}")
def get_waste_risk(product_id: int, current_stock: float = Query(..., ge=0),
                   shelf_life_days: int = Query(..., ge=1)):
    try:
        return predict.waste_risk(get_bundle(), product_id, current_stock, shelf_life_days)
    except LookupError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
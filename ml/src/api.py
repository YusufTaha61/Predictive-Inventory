"""Tahmin API'si (FastAPI). ml klasöründe başlat:

    uvicorn src.api:app --reload --port 8001

Adresler:
    GET  /health                  servis çalışıyor mu, hangi model yüklü
    GET  /forecast/{product_id}   ürünün önümüzdeki 7 günlük satış tahmini
    POST /reload                  model yeniden eğitildikten sonra yeni modeli yükler

Otomatik dokümantasyon: http://127.0.0.1:8001/docs

Not: Bu servis sadece kendi bilgisayarında (127.0.0.1) çalışır ve Laravel tarafından çağrılır.
Hangi ürünü kimin görebileceğini Laravel kontrol eder.
"""
from __future__ import annotations

from fastapi import FastAPI, HTTPException

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

"""Model eğitimi: basit yöntemleri ve ML modellerini karşılaştırır, en iyisini kaydeder.

ml klasöründe çalıştır:   python -m src.train

Değerlendirme zamana göre yapılır (rolling-origin): model hep geçmişle eğitilir,
hemen sonraki 60 günde test edilir. Bu 3 kez tekrarlanır ve ortalama alınır.
Rastgele bölme yapılmaz, çünkü o zaman model "geleceği görerek" öğrenmiş olurdu.

Ölçütler (7 gün sonrası için yapılan tahminlerde, adet olarak):
  MAE   ortalama mutlak hata (günde kaç adet yanılıyor)
  WAPE  toplam hata / toplam satış (% olarak; ne kadar düşükse o kadar iyi)
  bias  toplam (tahmin - gerçek) / toplam satış; artı = fazla tahmin, eksi = az tahmin
"""
from __future__ import annotations

from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import HistGradientBoostingRegressor, RandomForestRegressor

from .data import ML_DIR, load_daily_sales, load_products
from .features import BASELINES, FEATURES, HORIZON, MIN_HISTORY_DAYS, build_features

MODEL_PATH = ML_DIR / "models" / "forecast_model.joblib"
SEED = 42


def metrics(actual, predicted) -> dict:
    y = np.asarray(actual, float)
    p = np.asarray(predicted, float)
    err = p - y
    total = y.sum() or 1.0
    return {"mae": np.abs(err).mean(), "wape": np.abs(err).sum() / total, "bias": err.sum() / total}


def get_models() -> dict:
    return {
        "Random Forest": RandomForestRegressor(
            n_estimators=300, min_samples_leaf=20, max_features=0.5, n_jobs=-1, random_state=SEED,
        ),
        "Gradient Boosting": HistGradientBoostingRegressor(
            learning_rate=0.03, max_iter=300, min_samples_leaf=40, random_state=SEED,
        ),
    }


def predict_units(model, rows: pd.DataFrame) -> np.ndarray:
    """Model oran tahmin eder; seviyeyle çarpılınca adet olur. Satış eksi olamaz."""
    return np.clip(model.predict(rows[FEATURES]), 0, None) * rows["level"].to_numpy()


def time_folds(df: pd.DataFrame, n_folds: int = 3, fold_days: int = 60):
    """Her turda: test bloğundan önceki her şeyle eğit, test bloğunda ölç."""
    end = df["date"].max()
    for k in range(n_folds, 0, -1):
        test_start = end - pd.Timedelta(days=fold_days * k - 1)
        test_end = end - pd.Timedelta(days=fold_days * (k - 1))
        train = df["date"] < test_start
        test = (df["date"] >= test_start) & (df["date"] <= test_end)
        yield n_folds - k + 1, train, test


def main() -> None:
    sales = load_daily_sales()
    products = load_products()
    df, product_ids = build_features(sales, products)
    # İlk 35 günün özellikleri eksik (yeterli geçmiş yok), onlar eğitimde kullanılmaz
    df = df.dropna(subset=["lag_28", "roll_std_28"]).reset_index(drop=True)
    if df.empty:
        raise SystemExit(f"Eğitim için yeterli veri yok: en az {MIN_HISTORY_DAYS} günlük satış gerekli.")

    print(f"Eğitim tablosu: {len(df):,} satır, {len(product_ids)} ürün, "
          f"{df['date'].min().date()} - {df['date'].max().date()}")

    rows = []
    for fold, train, test in time_folds(df):
        y_test = df.loc[test, "units_sold"]
        for name, column in BASELINES.items():
            rows.append({"model": name, "kind": "basit", "fold": fold, **metrics(y_test, df.loc[test, column])})
        for name, model in get_models().items():
            model.fit(df.loc[train, FEATURES], df.loc[train, "target_ratio"])
            pred = predict_units(model, df.loc[test])
            rows.append({"model": name, "kind": "ml", "fold": fold, **metrics(y_test, pred)})
        print(f"  tur {fold}: eğitim {train.sum():,} satır, test {test.sum():,} satır")

    summary = (
        pd.DataFrame(rows)
        .groupby(["model", "kind"], as_index=False)[["mae", "wape", "bias"]].mean()
        .sort_values("wape")
    )
    show = summary.assign(wape=lambda s: (100 * s.wape).round(1), bias=lambda s: (100 * s.bias).round(1),
                          mae=lambda s: s.mae.round(2))
    print(f"\n{HORIZON} gün sonrası tahmin hatası (3 turun ortalaması, wape ve bias % olarak):")
    print(show.to_string(index=False))

    # En iyi ML modelini tüm veriyle yeniden eğit ve kaydet
    best = summary[summary.kind == "ml"].iloc[0]["model"]
    model = get_models()[best]
    model.fit(df[FEATURES], df["target_ratio"])

    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump({
        "model": model,
        "model_name": best,
        "features": FEATURES,
        "product_ids": product_ids,
        "trained_until": str(df["date"].max().date()),
        "metrics": show.to_dict(orient="records"),
    }, MODEL_PATH)
    print(f"\nEn iyi model ({best}) kaydedildi: {MODEL_PATH}")


if __name__ == "__main__":
    main()

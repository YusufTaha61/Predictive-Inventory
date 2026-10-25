"""Grafikler ve tablolar: model karşılaştırması ve özellik önemi (tez ve sunum için).

ml klasöründe çalıştır (önce model eğitilmiş olmalı):   python -m src.report

Üretilen dosyalar (ml/reports/):
  model_comparison.csv / .png     her yöntemin 7 gün sonrası tahmin hatası (WAPE, düşük = iyi)
  feature_importance.csv / .png   model en çok hangi bilgilere bakıyor?

Özellik önemi nasıl ölçülür (permutation importance): model son 60 gün hariç tüm veriyle
eğitilir, son 60 günde test edilir. Sonra bir özelliğin değerleri karıştırılır ve hatanın ne
kadar arttığına bakılır. Hata çok artıyorsa model o özelliğe çok güveniyordur.
"""
from __future__ import annotations

import matplotlib

matplotlib.use("Agg")  # pencere açmadan dosyaya çizer
import joblib
import matplotlib.pyplot as plt
import pandas as pd
from sklearn.inspection import permutation_importance

from .data import ML_DIR, load_daily_sales, load_products, load_promotions
from .features import FEATURES, build_features
from .train import MODEL_PATH, SEED, get_models

REPORTS_DIR = ML_DIR / "reports"
TEST_DAYS = 60

# Grafiklerde özellik adlarının Türkçesi
FEATURE_NAMES = {
    "lag_7_ratio": "Geçen hafta aynı gün",
    "lag_14_ratio": "2 hafta önce aynı gün",
    "lag_21_ratio": "3 hafta önce aynı gün",
    "lag_28_ratio": "4 hafta önce aynı gün",
    "dow_mean_4_ratio": "Son 4 haftada aynı günün ort.",
    "roll_mean_7_ratio": "Son 7 günün ortalaması",
    "roll_std_28_ratio": "Satış dalgalanması",
    "roll_mean_28": "Ürünün normal satış seviyesi",
    "dow": "Haftanın günü",
    "month": "Ay",
    "day_of_year": "Yılın günü (mevsim)",
    "is_weekend": "Hafta sonu",
    "is_holiday": "Bayram",
    "on_promo": "Kampanya var mı",
    "price_ratio": "İndirim oranı",
    "shelf_life_days": "Raf ömrü",
    "price": "Fiyat",
    "product_code": "Ürün",
}


def model_comparison(bundle: dict) -> None:
    """Eğitimde ölçülen hataları (bundle["metrics"]) tablo ve grafik olarak kaydeder."""
    table = pd.DataFrame(bundle["metrics"]).sort_values("wape")
    table.to_csv(REPORTS_DIR / "model_comparison.csv", index=False)

    colors = ["#2E7D32" if kind == "ml" else "#9E9E9E" for kind in table["kind"]]
    fig, ax = plt.subplots(figsize=(8, 4.2))
    ax.barh(table["model"], table["wape"], color=colors)
    for y, value in enumerate(table["wape"]):
        ax.text(value, y, f" %{value:.1f}", va="center", fontsize=9)
    ax.set_xlim(0, table["wape"].max() * 1.15)  # sağdaki yüzde yazısına yer kalsın
    ax.invert_yaxis()  # en iyi model en üstte
    ax.set_xlabel("WAPE: toplam hata / toplam satış (%, düşük = iyi)")
    ax.set_title("7 gün sonrası tahmin hatası\nyeşil: ML modelleri, gri: basit yöntemler", fontsize=11)
    fig.tight_layout()
    fig.savefig(REPORTS_DIR / "model_comparison.png", dpi=150)
    plt.close(fig)
    print("Model karşılaştırması:")
    print(table.to_string(index=False))


def feature_importance(bundle: dict) -> None:
    df, _ = build_features(load_daily_sales(), load_products(), promotions=load_promotions())
    df = df.dropna(subset=["lag_28", "roll_std_28"])
    test_start = df["date"].max() - pd.Timedelta(days=TEST_DAYS - 1)
    train, test = df[df["date"] < test_start], df[df["date"] >= test_start]

    model = get_models()[bundle["model_name"]]
    model.fit(train[FEATURES], train["target_ratio"])
    result = permutation_importance(model, test[FEATURES], test["target_ratio"], n_repeats=5,
                                    random_state=SEED, scoring="neg_mean_absolute_error", n_jobs=-1)
    table = pd.DataFrame({
        "feature": FEATURES,
        "name": [FEATURE_NAMES.get(f, f) for f in FEATURES],
        "importance": result.importances_mean,
    }).sort_values("importance", ascending=False)
    table["share_pct"] = 100 * table["importance"].clip(lower=0) / table["importance"].clip(lower=0).sum()
    table.round(4).to_csv(REPORTS_DIR / "feature_importance.csv", index=False)

    top = table.head(12).iloc[::-1]  # en önemli en üstte görünsün
    fig, ax = plt.subplots(figsize=(8, 5))
    ax.barh(top["name"], top["share_pct"], color="#2E7D32")
    ax.set_xlabel("Önem payı (%)")
    ax.set_title(f"{bundle['model_name']}: model en çok hangi bilgilere bakıyor?")
    fig.tight_layout()
    fig.savefig(REPORTS_DIR / "feature_importance.png", dpi=150)
    plt.close(fig)
    print("\nEn önemli 5 özellik:")
    print(table.head(5)[["name", "share_pct"]].round(1).to_string(index=False))


def main() -> None:
    if not MODEL_PATH.exists():
        raise SystemExit("Model dosyası yok. Önce modeli eğit: python -m src.train (veya php artisan ml:train)")
    bundle = joblib.load(MODEL_PATH)
    REPORTS_DIR.mkdir(exist_ok=True)
    model_comparison(bundle)
    feature_importance(bundle)
    print(f"\nGrafikler kaydedildi: {REPORTS_DIR}")


if __name__ == "__main__":
    main()

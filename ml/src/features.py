"""Ön işleme: günlük satış tablosundan modelin kullanacağı özellikleri (feature) üretir.

Tahmin ufku 7 gün. Bu yüzden geçmiş satışa dayanan her özellik en az 7 gün geriden gelir:
d gününün tahmini, d-7 gününe kadar bilinen satışlarla yapılır. Böylece model bugün,
önümüzdeki 7 günün hepsini tahmin edebilir.
Takvim bilgileri (haftanın günü, ay, bayram) önceden bilindiği için serbestçe kullanılır.
"""
from __future__ import annotations

import pandas as pd

HORIZON = 7  # kaç gün ilerisi tahmin ediliyor

# Bayram günleri ve hemen öncesi. Aynı liste DemoDataSeeder.php içinde de var.
HOLIDAYS = pd.to_datetime([
    # Ramazan Bayramı 2026
    "2026-03-17", "2026-03-18", "2026-03-19", "2026-03-20",
    # Kurban Bayramı 2026
    "2026-05-24", "2026-05-25", "2026-05-26", "2026-05-27",
])

# Ürünler çok farklı miktarlarda satılır (günde 60 ekmek, 2 lokum). Model bu yüzden adet yerine
# "oran" öğrenir: o gün, ürünün normal seviyesinin (son 28 günün ortalaması) kaç katı satılacak.
# Tahminden sonra oran tekrar seviyeyle çarpılıp adete çevrilir.
RATIO_OF = ["lag_7", "lag_14", "lag_21", "lag_28", "dow_mean_4", "roll_mean_7", "roll_std_28"]

FEATURES = [
    # Geçmiş satışlar (seviyeye oranla): 1-4 hafta önceki aynı gün, son 4 haftada aynı günün
    # ortalaması, son 7 günün ortalaması ve dalgalanma
    *[f"{c}_ratio" for c in RATIO_OF],
    # Ürünün normal seviyesi (son 28 günün ortalaması, adet)
    "roll_mean_28",
    # Takvim
    "dow", "month", "day_of_year", "is_weekend", "is_holiday",
    # Ürün bilgisi
    "shelf_life_days", "price", "product_code",
]

# Modelle karşılaştırılan basit yöntemler (modelin gerçekten işe yarayıp yaramadığını görmek için)
BASELINES = {
    "Geçen hafta aynı gün": "lag_7",
    "Son 4 haftada aynı günün ortalaması": "dow_mean_4",
    "Son 28 günün ortalaması": "roll_mean_28",
}

# Bir satırın özelliklerinin tam olması için gereken en az geçmiş gün sayısı
MIN_HISTORY_DAYS = 28 + HORIZON


def build_features(sales: pd.DataFrame, products: pd.DataFrame, product_ids: list[int] | None = None):
    """Özellik tablosunu ve modelin tanıdığı ürün listesini döndürür.

    sales     : date, product_id, units_sold (gelecek günlerde units_sold boş olabilir)
    products  : product_id, shelf_life_days, price
    product_ids: tahmin sırasında eğitimdeki ürün listesi verilir; eğitimde boş bırakılır.
    """
    df = sales.merge(products[["product_id", "shelf_life_days", "price"]], on="product_id", how="left")
    df = df.sort_values(["product_id", "date"]).reset_index(drop=True)
    g = df.groupby("product_id", sort=False)["units_sold"]

    for lag in (7, 14, 21, 28):
        df[f"lag_{lag}"] = g.shift(lag)
    df["dow_mean_4"] = df[["lag_7", "lag_14", "lag_21", "lag_28"]].mean(axis=1)

    # Hareketli ortalamalar da 7 gün geriden başlar
    shifted = g.shift(HORIZON)
    by_product = shifted.groupby(df["product_id"], sort=False)
    df["roll_mean_7"] = by_product.transform(lambda s: s.rolling(7, min_periods=7).mean())
    df["roll_mean_28"] = by_product.transform(lambda s: s.rolling(28, min_periods=28).mean())
    df["roll_std_28"] = by_product.transform(lambda s: s.rolling(28, min_periods=28).std())

    # Seviye ve oranlar (+0.5: hiç satılmayan üründe sıfıra bölmeyi önler)
    df["level"] = df["roll_mean_28"] + 0.5
    for c in RATIO_OF:
        df[f"{c}_ratio"] = df[c] / df["level"]
    df["target_ratio"] = df["units_sold"] / df["level"]  # modelin öğrendiği değer

    d = df["date"].dt
    df["dow"] = d.dayofweek  # 0 = Pazartesi
    df["month"] = d.month
    df["day_of_year"] = d.dayofyear
    df["is_weekend"] = (d.dayofweek >= 5).astype(int)
    df["is_holiday"] = df["date"].isin(HOLIDAYS).astype(int)

    # Raf ömrü girilmemişse 0 (bilinmiyor / bozulmaz)
    df["shelf_life_days"] = df["shelf_life_days"].fillna(0)
    df["price"] = df["price"].astype(float)

    # Ürün kimliği modele sayı kodu olarak verilir; model her ürünün kendi seviyesini öğrenir.
    if product_ids is None:
        product_ids = sorted(df["product_id"].unique().tolist())
    df["product_code"] = pd.Categorical(df["product_id"], categories=product_ids).codes

    return df, product_ids

"""Akıllı sipariş hesabı: tahmini bir sipariş miktarına çevirir.

Eski kural "7 günlük satış + %20 yedek - stok" idi. İki sorunu vardı:
  1. Her ürüne aynı %20 yedek veriliyordu. Ekmek gibi 1 günde bozulan bir ürün için
     fazla yedek = fire; makarna gibi bozulmayan bir ürün için az yedek = rafın boş kalması.
  2. Her zaman 7 günlük sipariş öneriliyordu. Raf ömrü 1 gün olan ürünün 6 günlüğü çöpe gider.

Yeni hesap:
  sipariş = bir sonraki teslimata kadar beklenen satış + yedek stok - elimdeki stok

  - Kaç günlük sipariş: bir sonraki teslimata kadar (teslimat aralığı), ama ürünün raf
    ömründen uzun değil. Tahmin 7 gün için var; daha uzun sürelerde 7 günlük tahmin tekrarlanır.
  - Yedek stok = z x tahmin hatası x karekök(gün sayısı). Tahmin ne kadar çok yanılıyorsa
    yedek o kadar fazla olur.
  - z, "talebin yüzde kaçını stoktan karşılamak istiyoruz?" sorusundan gelir (hizmet düzeyi):
      bozulmayan ürün : %95. Fazla kalan ürün bozulmaz, rafta bekler; az sipariş ise satış kaybıdır.
      bozulabilen ürün: fire maliyeti ile kaçan satışın kârı dengelenir (gazeteci satıcısı
                        problemi / newsvendor). Ayrıntı service_level() içinde.

Alış fiyatı ürün kartından gelir. Girilmemişse satış fiyatının COST_RATIO katı kabul edilir.
"""
from __future__ import annotations

from math import ceil, sqrt
from statistics import NormalDist

import numpy as np

COST_RATIO = 0.60          # alış fiyatı girilmemişse: satış fiyatı x 0.60 (yani %40 kâr)
PERISHABLE_DAYS = 7        # raf ömrü 7 gün veya daha az olan ürün "bozulabilir" sayılır
NON_PERISHABLE_SERVICE = 0.95
MIN_SERVICE = 0.50         # bozulabilir üründe bile beklenen satışın altına inilmez
MAX_SERVICE = 0.95
DEFAULT_COVER_DAYS = 7     # teslimat aralığı bilinmiyorsa
MAX_COVER_DAYS = 28        # en fazla 4 haftalık sipariş


def unit_cost(price: float, purchase_price=None) -> float:
    """Alış fiyatı. Ürün kartında girilmişse o kullanılır, yoksa satış fiyatının %60'ı varsayılır."""
    if has_purchase_price(purchase_price):
        return float(purchase_price)
    return float(price) * COST_RATIO


def has_purchase_price(purchase_price) -> bool:
    """Alış fiyatı girilmiş mi? (boş veya NaN değilse)"""
    return purchase_price is not None and purchase_price == purchase_price and float(purchase_price) >= 0


def is_perishable(shelf_life_days) -> bool:
    return shelf_life_days is not None and 0 < shelf_life_days <= PERISHABLE_DAYS


def service_level(price: float, shelf_life_days, cover: int, cost: float | None = None) -> float:
    """Talebin yüzde kaçı stoktan karşılansın? (0.95 = %95)

    Bozulabilir ürün (gazeteci satıcısı problemi):
      bir ürün eksik alınırsa  -> satış kaçar, kayıp = kâr
      bir ürün fazla alınırsa  -> fire olabilir, kayıp = alış fiyatı x fire olma payı
      en iyi oran = kâr / (kâr + alış fiyatı x fire olma payı)
    Fire olma payı: fazla kalan ürün bir sonraki teslimat döneminde de satılabilir.
    Raf ömrü 1 gün olan ekmekte artan her şey çöpe gider (pay = 1); raf ömrü 4 gün olan
    ve 2 günde bir gelen sütte artanın yarısı bir sonraki dönemde satılır (pay = 2/4).

    cost: alış fiyatı. Verilmezse satış fiyatının %60'ı kabul edilir.
    """
    if not is_perishable(shelf_life_days):
        return NON_PERISHABLE_SERVICE
    if price <= 0:
        return MIN_SERVICE
    if cost is None:
        cost = unit_cost(price)
    margin = price - cost
    if margin <= 0:
        # Zararına satılan ürün: fazla almanın hiçbir faydası yok
        return MIN_SERVICE
    waste_share = min(1.0, cover / shelf_life_days)
    return float(np.clip(margin / (margin + cost * waste_share), MIN_SERVICE, MAX_SERVICE))


def z_score(service: float) -> float:
    """Hizmet düzeyini normal dağılım katsayısına çevirir (0.95 -> 1.645, 0.50 -> 0)."""
    return NormalDist().inv_cdf(min(max(service, 0.001), 0.999))


def forecast_error_std(daily_sales) -> float:
    """Günlük tahmin hatasının standart sapması (adet).

    Son 8 haftada her gün için "son 4 haftada aynı günün ortalaması" tahmini yapılır
    ve gerçek satışla farkına bakılır. Geçmiş kısaysa satışların karekökü kullanılır
    (rastgele satışlarda ortalaması m olan bir günün sapması yaklaşık karekök m'dir).
    """
    y = np.asarray(daily_sales, float)
    if len(y) < 35:
        return float(sqrt(y.mean())) if len(y) else 0.0
    errors = []
    for t in range(max(28, len(y) - 56), len(y)):
        guess = np.mean([y[t - 7 * w] for w in range(1, 5)])
        errors.append(y[t] - guess)
    return float(np.std(errors))


def cover_days(delivery_every, shelf_life_days) -> int:
    """Siparişin kaç günlük satışı karşılaması gerektiği."""
    days = int(delivery_every) if delivery_every else DEFAULT_COVER_DAYS
    if shelf_life_days:
        days = min(days, int(shelf_life_days))
    return max(1, min(days, MAX_COVER_DAYS))


def extend_forecast(daily_forecast, days: int) -> np.ndarray:
    """Tahmini istenen gün sayısına uzatır: 7 günlük tahmin hafta hafta tekrarlanır."""
    f = np.asarray(daily_forecast, float)
    return np.resize(f, days) if f.size else np.zeros(days)


def order_quantity(daily_forecast, current_stock: float, sigma: float, service: float) -> dict:
    """Sipariş miktarı = beklenen satış + yedek stok - elimdeki stok (eksi olamaz)."""
    f = np.asarray(daily_forecast, float)
    expected = float(f.sum())
    safety = max(0.0, z_score(service) * sigma * sqrt(len(f)))
    target = expected + safety
    return {
        "expected_sales": round(expected, 2),
        "safety_stock": round(safety, 2),
        "target_stock": round(target, 2),
        "suggested_order": max(0, ceil(target - current_stock - 1e-9)),
    }

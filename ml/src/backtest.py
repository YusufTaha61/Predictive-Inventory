"""Sipariş simülasyonu: "alışkanlıkla sipariş mi, tahminle sipariş mi daha az maliyetli?"

ml klasöründe çalıştır:   python -m src.backtest

Son 180 gün yeniden oynatılır. Her ürün için aynı satışlar, aynı raf ömrü, aynı teslimat
günleri ve aynı başlangıç stoğu kullanılır; sadece SİPARİŞ MİKTARI değişir:

  Alışkanlık          marketin şu anki yöntemi: son 2 haftanın ortalamasına bakıp biraz fazla
                      sipariş vermek (demo verideki market de böyle sipariş veriyor)
  Eski öneri          uygulamanın ilk kuralı: 7 günlük tahmin + %20 yedek - stok
  Akıllı (basit)      decision.py hesabı, tahmin = son 4 haftada aynı günün ortalaması
  Akıllı (ML)         decision.py hesabı, tahmin = Random Forest modeli

Adil olması için:
  - model sadece simülasyon döneminden ÖNCEKİ verilerle eğitilir (geleceği görmez)
  - d gününün tahmini d-7 gününe kadar bilinen satışlarla yapılır (features.py)

Maliyetler (₺):
  fire maliyeti    = bozulan ürün x alış fiyatı
  kaçan kâr        = stok yetmediği için satılamayan ürün x (satış fiyatı - alış fiyatı)
  toplam maliyet   = fire maliyeti + kaçan kâr

Sınırlama: Gerçek talep bilinmiyor, kayıtlı satışlar talep kabul edilir. Geçmişte stok bittiği
günlerde gerçek talep daha yüksekti; bu yüzden kaçan satışlar tüm yöntemlerde biraz az görünür.
"""
from __future__ import annotations

import matplotlib

matplotlib.use("Agg")  # pencere açmadan dosyaya çizer
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

from . import decision
from .data import ML_DIR, load_daily_sales, load_delivery_every, load_products, load_promotions
from .features import FEATURES, build_features
from .train import get_models, predict_units

REPORTS_DIR = ML_DIR / "reports"
REPLAY_DAYS = 180   # simüle edilen son gün sayısı
VAL_DAYS = 60       # tahmin hatasını ölçmek için simülasyondan önceki gün sayısı

HABIT = "Alışkanlık"
OLD_RULE = "Eski öneri (7 gün + %20)"
SMART_SIMPLE = "Akıllı sipariş (basit tahmin)"
SMART_ML = "Akıllı sipariş (ML)"
POLICIES = [HABIT, OLD_RULE, SMART_SIMPLE, SMART_ML]


def simulate(demand, shelf_life, delivery_every: int, order_fn, initial_stock: int):
    """Stoğu gün gün izler. Önce eski parti satılır, raf ömrü dolan parti fire olur.

    order_fn(t, stock) -> t gününün akşamı verilecek sipariş; ertesi sabah gelir.
                          stock: yarın sabah hâlâ satılabilecek stok (yarın bozulacak olan sayılmaz).
    Sipariş sadece teslimat günlerinde verilir (her delivery_every günde bir).
    shelf_life None ise ürün bozulmaz.
    Dönüş: her gün için (satılan, fire, sabahki stok).
    """
    n = len(demand)
    life = shelf_life or 10_000
    batches = [[int(initial_stock), life]]   # [miktar, son satılabileceği gün + 1]
    arriving = 0
    sold, waste, stock_start = np.zeros(n, int), np.zeros(n, int), np.zeros(n, int)
    for t in range(n):
        if arriving > 0:
            batches.append([arriving, t + life])
            arriving = 0
        waste[t] = sum(q for q, end in batches if end <= t)
        batches = [b for b in batches if b[1] > t]
        stock = sum(q for q, _ in batches)
        stock_start[t] = stock
        sold[t] = left = min(int(demand[t]), stock)
        for b in batches:
            take = min(b[0], left)
            b[0] -= take
            left -= take
        batches = [b for b in batches if b[0] > 0]
        if t % delivery_every == 0:
            usable = sum(q for q, end in batches if end > t + 1)
            arriving = max(0, int(order_fn(t, usable)))
    return sold, waste, stock_start


def upcoming(F, t: int, days: int) -> np.ndarray:
    """t gününden sonraki `days` günün tahmini (t+1, t+2, ...).

    Sadece 7 gün ilerisi gerçek tahmin; daha sonrası için bir hafta önceki aynı günün tahmini kullanılır.
    """
    out = []
    for k in range(1, days + 1):
        idx = t + k
        while idx > t + 7 or idx >= len(F):
            idx -= 7
        out.append(F[max(idx, 0)])
    return np.array(out)


def habit_rule(sales_before, demand, expires_soon: bool, delivery_every: int):
    """Marketin alışkanlığı (DemoDataSeeder.php'deki market sahibi gibi).

    Raf ömrü kısa (90 günden az): son 14 günün ortalaması x teslimat aralığı x 1.15
                                  - stoğun yarısı (rafı tam saymaz)
    Raf ömrü uzun               : stok 5 günlük satışın altına düşünce 3 haftalık sipariş
    """
    history = list(sales_before[-14:])

    def rule(t, stock):
        recent = (history + list(demand[: t + 1]))[-14:]
        avg = float(np.mean(recent)) if recent else 0.0
        if expires_soon:
            return round(avg * delivery_every * 1.15 - stock * 0.5)
        return round(avg * 21 - stock) if stock < avg * 5 else 0

    return rule


def main() -> None:
    sales = load_daily_sales()
    products = load_products().set_index("product_id")
    promotions = load_promotions()
    delivery = load_delivery_every()
    df, _ = build_features(sales, products.reset_index(), promotions=promotions)
    df = df.dropna(subset=["lag_28", "roll_std_28"])

    end = df["date"].max()
    t0 = end - pd.Timedelta(days=REPLAY_DAYS - 1)
    v0 = t0 - pd.Timedelta(days=VAL_DAYS)
    print(f"Simülasyon: {t0.date()} - {end.date()} ({REPLAY_DAYS} gün)")

    # 1) Tahmin hatası (yedek stok için): doğrulama döneminden önceki veriyle eğitilen model
    model = get_models()["Random Forest"]
    before_val = df[df["date"] < v0]
    model.fit(before_val[FEATURES], before_val["target_ratio"])
    val = df[(df["date"] >= v0) & (df["date"] < t0)].copy()
    val["pred"] = predict_units(model, val)
    sigma_ml = (val["units_sold"] - val["pred"]).groupby(val["product_id"]).std().fillna(0)
    sigma_simple = (val["units_sold"] - val["dow_mean_4"]).groupby(val["product_id"]).std().fillna(0)

    # 2) Simülasyon tahminleri: simülasyondan önceki tüm veriyle eğitilen model
    model = get_models()["Random Forest"]
    before = df[df["date"] < t0]
    model.fit(before[FEATURES], before["target_ratio"])
    window = df[df["date"] >= t0].copy()
    window["pred"] = predict_units(model, window)

    rows = []
    for pid, g in window.groupby("product_id"):
        g = g.sort_values("date")
        p = products.loc[pid]
        shelf = None if pd.isna(p["shelf_life_days"]) else int(p["shelf_life_days"])
        price = float(p["price"])
        cost = decision.unit_cost(price, p["purchase_price"])
        perishable = decision.is_perishable(shelf)
        every = delivery.get(int(pid), decision.DEFAULT_COVER_DAYS)
        days = decision.cover_days(every, shelf)
        service = decision.service_level(price, shelf, days, cost)
        demand = g["units_sold"].to_numpy()
        F_ml, F_simple = g["pred"].to_numpy(), g["dow_mean_4"].to_numpy()
        past = sales[(sales["product_id"] == pid) & (sales["date"] < t0)]["units_sold"].to_numpy()
        start_stock = int(round(np.mean(past[-14:]) * every)) if len(past) else 0

        def smart(F, sigma):
            return lambda t, stock: decision.order_quantity(upcoming(F, t, days), stock, sigma, service)["suggested_order"]

        rules = {
            HABIT: habit_rule(past, demand, shelf is not None and shelf < 90, every),
            OLD_RULE: lambda t, stock: int(np.ceil(upcoming(F_ml, t, 7).sum() * 1.2 - stock)),
            SMART_SIMPLE: smart(F_simple, sigma_simple.get(pid, 0.0)),
            SMART_ML: smart(F_ml, sigma_ml.get(pid, 0.0)),
        }
        for name, rule in rules.items():
            sold, waste, stock_start = simulate(demand, shelf, every, rule, start_stock)
            rows.append({
                "policy": name, "product_id": int(pid), "product": p["name"], "perishable": perishable,
                "demand": demand.sum(), "sold": sold.sum(), "waste": waste.sum(),
                "waste_cost": waste.sum() * cost, "lost_margin": (demand.sum() - sold.sum()) * (price - cost),
                "avg_stock_value": stock_start.mean() * cost,
            })

    res = pd.DataFrame(rows)
    res["total_cost"] = res["waste_cost"] + res["lost_margin"]

    def summarize(frame: pd.DataFrame) -> pd.DataFrame:
        s = frame.groupby("policy").agg(
            demand=("demand", "sum"), sold=("sold", "sum"), waste=("waste", "sum"),
            waste_cost=("waste_cost", "sum"), lost_margin=("lost_margin", "sum"),
            total_cost=("total_cost", "sum"), avg_stock_value=("avg_stock_value", "sum"),
        ).loc[POLICIES]
        s["waste_pct"] = 100 * s["waste"] / (s["sold"] + s["waste"])
        s["lost_sales_pct"] = 100 * (s["demand"] - s["sold"]) / s["demand"]
        s["saving_vs_habit_pct"] = 100 * (1 - s["total_cost"] / s.loc[HABIT, "total_cost"])
        return s.reset_index()

    overall = summarize(res)
    REPORTS_DIR.mkdir(exist_ok=True)
    overall.round(2).to_csv(REPORTS_DIR / "policy_comparison.csv", index=False)
    res.round(2).to_csv(REPORTS_DIR / "policy_by_product.csv", index=False)

    cols = ["policy", "waste_pct", "lost_sales_pct", "waste_cost", "lost_margin", "total_cost", "saving_vs_habit_pct"]
    pd.set_option("display.width", 200)
    print("\nTüm ürünler (fire ve kaçan satış %, maliyetler ₺):")
    print(overall[cols].round(1).to_string(index=False))
    print("\nSadece bozulabilir ürünler (raf ömrü 7 gün veya daha az):")
    print(summarize(res[res["perishable"]])[cols].round(1).to_string(index=False))

    # Grafik: her yöntemde paranın nereye gittiği.
    # Eski öneri grafiğe konmaz: maliyeti diğerlerinin 10 katından fazla, çubukları okunmaz hale getirir
    # (sonucu yukarıdaki tabloda ve CSV dosyasında var).
    chart = overall.set_index("policy").loc[[HABIT, SMART_SIMPLE, SMART_ML]]
    labels = ["Alışkanlık", "Akıllı sipariş\n(basit tahmin)", "Akıllı sipariş\n(ML)"]
    fig, ax = plt.subplots(figsize=(8, 4.8))
    ax.bar(labels, chart["waste_cost"], color="#C77D0A", label="Fire maliyeti")
    ax.bar(labels, chart["lost_margin"], bottom=chart["waste_cost"], color="#1F3A5F", label="Kaçan kâr (stok bitti)")
    for i, total in enumerate(chart["total_cost"]):
        ax.text(i, total, f"{total:,.0f} ₺".replace(",", "."), ha="center", va="bottom", fontsize=9)
    ax.set_ylabel(f"Son {REPLAY_DAYS} günde maliyet (₺)")
    ax.set_title("Aynı satışlar üzerinde sipariş yöntemlerinin karşılaştırılması")
    ax.legend()
    fig.tight_layout()
    fig.savefig(REPORTS_DIR / "policy_comparison.png", dpi=150)
    plt.close(fig)
    print(f"\nSonuçlar kaydedildi: {REPORTS_DIR}")


if __name__ == "__main__":
    main()

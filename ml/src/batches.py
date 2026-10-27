"""Parti (lot) bazlı fire riski.

Eski hesap tüm stoğu aynı gün gelmiş gibi düşünüyordu: "elimdeki stok - raf ömrü boyunca satılabilecek".
Ama stok farklı günlerde gelen partilerden oluşur. 3 gün önce gelen süt, dün gelen sütten önce bozulur.

Yeni hesap:
  1. Stok girişleri (in) birer parti sayılır: geliş günü + miktar.
     Bozulma günü = geliş günü + raf ömrü (o gün artık satılamaz).
  2. Geçmiş satışlar ve fireler partilerden "ilk giren ilk çıkar" (FIFO) mantığıyla düşülür:
     önce en eski parti tükenir. Böylece bugün elde hangi partiden ne kadar kaldığı bulunur.
  3. Gelecek günler tek tek ilerletilir: her gün önce bozulan partiler ayrılır (fire riski),
     sonra o günün tahmini satışı en eski partiden düşülür.
  4. Bozulma gününe kadar satılamayan miktar = o partinin fire riski.
"""
from __future__ import annotations

from datetime import timedelta

import pandas as pd

MAX_SIM_DAYS = 400  # en fazla bu kadar gün ileriye bakılır


def current_batches(movements: pd.DataFrame, shelf_life_days: int) -> list[dict]:
    """Bugün elde kalan partiler (en eskiden yeniye).

    movements: date, type ('in' / 'sale' / 'waste'), quantity
    Aynı gün içinde önce girişler, sonra çıkışlar işlenir.
    """
    if movements.empty:
        return []
    m = movements.copy()
    m["date"] = pd.to_datetime(m["date"])
    m["order"] = (m["type"] != "in").astype(int)  # girişler önce
    m = m.sort_values(["date", "order"], kind="stable")

    batches: list[dict] = []
    for row in m.itertuples():
        qty = float(row.quantity)
        if row.type == "in":
            batches.append({"arrived_on": row.date, "expires_on": row.date + timedelta(days=int(shelf_life_days)),
                            "quantity": qty})
        else:
            _take(batches, qty)
    return [b for b in batches if b["quantity"] > 1e-9]


def _take(batches: list[dict], qty: float) -> float:
    """En eski partiden başlayarak qty kadar düşer; karşılanabilen miktarı döndürür."""
    taken = 0.0
    for b in batches:
        if qty - taken <= 1e-9:
            break
        use = min(b["quantity"], qty - taken)
        b["quantity"] -= use
        taken += use
    batches[:] = [b for b in batches if b["quantity"] > 1e-9]
    return taken


def simulate(batches: list[dict], daily_forecast, first_day) -> list[dict]:
    """Partileri gelecek günlerde satışla eritir; her parti için fire riskini hesaplar.

    daily_forecast: first_day'den başlayarak günlük tahmini satış (liste uzatılabilir: tekrarlanır).
    Dönen liste her parti için: arrived_on, expires_on, quantity (bugün elde), units_at_risk.
    """
    first_day = pd.Timestamp(first_day)
    result = [{**b, "start_quantity": b["quantity"], "units_at_risk": 0.0} for b in batches]
    open_batches = list(result)
    forecast = [float(x) for x in daily_forecast] or [0.0]

    last_expiry = max((b["expires_on"] for b in result), default=first_day)
    days = min(MAX_SIM_DAYS, max(1, (last_expiry - first_day).days + 1))
    for i in range(days):
        day = first_day + timedelta(days=i)
        # 1) Bugün bozulan (veya zaten bozulmuş) partiler satılamaz
        for b in open_batches:
            if b["expires_on"] <= day:
                b["units_at_risk"] = b["quantity"]
                b["quantity"] = 0.0
        open_batches = [b for b in open_batches if b["quantity"] > 1e-9]
        if not open_batches:
            break
        # 2) Günün tahmini satışı en eski partiden düşülür
        _take(open_batches, forecast[i % len(forecast)])

    return [{
        "arrived_on": str(b["arrived_on"].date()),
        "expires_on": str(b["expires_on"].date()),
        "quantity": round(b["start_quantity"], 2),
        "units_at_risk": round(b["units_at_risk"], 2),
    } for b in result]

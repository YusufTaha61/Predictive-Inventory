"""Veri yükleme: Laravel'in SQLite veritabanından ürünleri ve günlük satışları okur.

Python servisi veriyi kopyalamaz, doğrudan Laravel'in veritabanını okur:
    backend/database/database.sqlite

Başka bir veritabanı dosyası kullanmak için DB_PATH ortam değişkeni verilebilir.
"""
from __future__ import annotations

import os
import sqlite3
from contextlib import closing
from pathlib import Path

import pandas as pd

ML_DIR = Path(__file__).resolve().parents[1]
DEFAULT_DB = ML_DIR.parent / "backend" / "database" / "database.sqlite"


def db_path() -> Path:
    return Path(os.getenv("DB_PATH") or DEFAULT_DB)


def _connect():
    path = db_path()
    if not path.exists():
        raise FileNotFoundError(f"veritabanı bulunamadı: {path}")
    # Sadece okuma modunda açılır; Python tarafı veritabanına hiçbir şey yazmaz.
    # closing(): "with" bloğu bitince bağlantı kapanır (Windows'ta açık dosya kilitli kalmasın).
    return closing(sqlite3.connect(f"file:{path.as_posix()}?mode=ro", uri=True))


def load_products(product_id: int | None = None) -> pd.DataFrame:
    """Ürünler: product_id, company_id, name, shelf_life_days, price, purchase_price.

    purchase_price (alış fiyatı) boş olabilir. Sütun henüz yoksa (migrate yapılmamışsa) boş gelir.
    """
    params: tuple = ()
    where = ""
    if product_id is not None:
        where = " WHERE id = ?"
        params = (product_id,)
    with _connect() as con:
        columns = {row[1] for row in con.execute("PRAGMA table_info(products)")}
        purchase = "purchase_price" if "purchase_price" in columns else "NULL AS purchase_price"
        sql = f"SELECT id AS product_id, company_id, name, shelf_life_days, price, {purchase} FROM products{where}"
        return pd.read_sql_query(sql, con, params=params)


def load_daily_sales(product_id: int | None = None) -> pd.DataFrame:
    """Günlük satış tablosu: date, product_id, units_sold.

    - Her ürün için ilk hareket gününden veritabanındaki son güne kadar her gün bir satır vardır.
    - Satış olmayan günler 0 olarak doldurulur (model "o gün hiç satılmadı" bilgisini de görmeli).
    - Sadece "sale" (satış) hareketleri sayılır; giriş ve fire satış değildir.
    """
    only_one = "" if product_id is None else "AND product_id = ?"
    params: tuple = () if product_id is None else (product_id,)
    with _connect() as con:
        # date(): tarih "2026-10-16" ya da "2026-10-16 00:00:00" olarak kayıtlı olabilir
        sales = pd.read_sql_query(
            f"""SELECT product_id, date(moved_on) AS date, SUM(quantity) AS units_sold
                FROM stock_movements
                WHERE type = 'sale' {only_one}
                GROUP BY product_id, date(moved_on)""",
            con, params=params,
        )
        first = pd.read_sql_query(
            f"""SELECT product_id, MIN(date(moved_on)) AS first_day
                FROM stock_movements
                WHERE 1 = 1 {only_one}
                GROUP BY product_id""",
            con, params=params,
        )
        # Son gün tüm şirketler için aynıdır: veritabanındaki en son hareket günü
        last_day = con.execute("SELECT MAX(date(moved_on)) FROM stock_movements").fetchone()[0]

    if first.empty or last_day is None:
        return pd.DataFrame(columns=["date", "product_id", "units_sold"])

    sales["date"] = pd.to_datetime(sales["date"])
    last_day = pd.Timestamp(last_day)

    # Her ürün için boşluksuz bir gün listesi oluştur, satışları üzerine yerleştir
    frames = []
    for row in first.itertuples():
        days = pd.date_range(pd.Timestamp(row.first_day), last_day, freq="D")
        frames.append(pd.DataFrame({"date": days, "product_id": row.product_id}))
    full = pd.concat(frames, ignore_index=True)

    full = full.merge(sales, on=["date", "product_id"], how="left")
    full["units_sold"] = full["units_sold"].fillna(0).astype(float)
    return full.sort_values(["product_id", "date"]).reset_index(drop=True)


def load_stock_movements(product_id: int) -> pd.DataFrame:
    """Bir ürünün tüm stok hareketleri: date, type ('in' / 'sale' / 'waste'), quantity (eskiden yeniye)."""
    with _connect() as con:
        return pd.read_sql_query(
            """SELECT date(moved_on) AS date, type, quantity
               FROM stock_movements
               WHERE product_id = ?
               ORDER BY date(moved_on), id""",
            con, params=(product_id,),
        )


def load_promotions(product_id: int | None = None) -> pd.DataFrame:
    """Kampanyalar: product_id, starts_on, ends_on, discount_percent.

    Laravel'deki promotions tablosundan okunur. Tablo henüz yoksa (migrate yapılmamışsa) boş döner.
    """
    columns = ["product_id", "starts_on", "ends_on", "discount_percent"]
    only_one = "" if product_id is None else "WHERE product_id = ?"
    params: tuple = () if product_id is None else (product_id,)
    with _connect() as con:
        exists = con.execute("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'promotions'").fetchone()
        if not exists:
            return pd.DataFrame(columns=columns)
        promos = pd.read_sql_query(
            f"""SELECT product_id, date(starts_on) AS starts_on, date(ends_on) AS ends_on, discount_percent
                FROM promotions {only_one}""",
            con, params=params,
        )
    promos["starts_on"] = pd.to_datetime(promos["starts_on"])
    promos["ends_on"] = pd.to_datetime(promos["ends_on"])
    return promos


def load_delivery_every(product_id: int | None = None) -> dict[int, int]:
    """Her ürün için tedarikçinin kaç günde bir mal getirdiği (son 90 gündeki stok girişlerinden).

    Ardışık iki giriş arasındaki gün sayılarının ortancası (median) alınır.
    En az 3 girişi olmayan ürün listede yer almaz (teslimat aralığı bilinmiyor).
    """
    only_one = "" if product_id is None else "AND product_id = ?"
    params: tuple = () if product_id is None else (product_id,)
    with _connect() as con:
        ins = pd.read_sql_query(
            f"""SELECT DISTINCT product_id, date(moved_on) AS date
                FROM stock_movements
                WHERE type = 'in' {only_one}
                  AND date(moved_on) >= date((SELECT MAX(moved_on) FROM stock_movements), '-90 days')
                ORDER BY product_id, date""",
            con, params=params,
        )
    ins["date"] = pd.to_datetime(ins["date"])
    result = {}
    for pid, g in ins.groupby("product_id"):
        if len(g) >= 3:
            result[int(pid)] = max(1, int(round(g["date"].diff().dt.days.median())))
    return result


def promo_days(promos: pd.DataFrame) -> pd.DataFrame:
    """Kampanyaları gün gün açar: date, product_id, on_promo (1), price_ratio (indirimli fiyat / normal fiyat)."""
    rows = []
    for p in promos.itertuples():
        discount = 0.0 if pd.isna(p.discount_percent) else float(p.discount_percent)
        for day in pd.date_range(p.starts_on, p.ends_on, freq="D"):
            rows.append({"date": day, "product_id": p.product_id, "on_promo": 1, "price_ratio": 1 - discount / 100})
    if not rows:
        return pd.DataFrame(columns=["date", "product_id", "on_promo", "price_ratio"])
    # Aynı güne iki kampanya düşerse (Laravel bunu engelliyor ama) en büyük indirim geçerli
    return pd.DataFrame(rows).groupby(["date", "product_id"], as_index=False).agg(
        on_promo=("on_promo", "max"), price_ratio=("price_ratio", "min"))

"""Tahmin ve sipariş önerisi testleri (src/predict.py).

Laravel'in gerçek veritabanı kullanılmaz: her test çalışmasında geçici, küçük bir SQLite
veritabanı oluşturulur ve DB_PATH ile Python servisine gösterilir. Model olmadan çalışır
(basit yöntemler test edilir).
"""
import os
import sqlite3
import tempfile
import unittest
from datetime import date, timedelta

from src import predict

LAST_DAY = date(2026, 10, 24)


def create_db(path):
    con = sqlite3.connect(path)
    con.executescript("""
        CREATE TABLE products (id INTEGER PRIMARY KEY, company_id INT, name TEXT, price REAL, shelf_life_days INT);
        CREATE TABLE stock_movements (id INTEGER PRIMARY KEY, company_id INT, product_id INT,
                                      type TEXT, quantity REAL, moved_on TEXT);
        CREATE TABLE promotions (id INTEGER PRIMARY KEY, company_id INT, product_id INT,
                                 starts_on TEXT, ends_on TEXT, discount_percent REAL);
        INSERT INTO products VALUES (1, 1, 'Ekmek', 15, 1), (2, 1, 'Makarna', 35, 730),
                                    (3, 1, 'Yeni ürün', 50, 3), (4, 1, 'Hareketsiz', 10, NULL);
    """)
    rows = []
    for i in range(60):  # Ekmek ve makarna: 60 gün, her gün 10 satış, iki günde bir giriş
        day = (LAST_DAY - timedelta(days=59 - i)).isoformat()
        for pid in (1, 2):
            rows.append((1, pid, "sale", 10, day))
            if i % 2 == 0:
                rows.append((1, pid, "in", 20, day))
    for i in range(10):  # Yeni ürün: sadece 10 günlük geçmiş
        rows.append((1, 3, "sale", 4, (LAST_DAY - timedelta(days=9 - i)).isoformat()))
    con.executemany("INSERT INTO stock_movements (company_id, product_id, type, quantity, moved_on) "
                    "VALUES (?, ?, ?, ?, ?)", rows)
    con.commit()
    con.close()


class PredictTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.tmp = tempfile.TemporaryDirectory(ignore_cleanup_errors=True)
        path = os.path.join(cls.tmp.name, "test.sqlite")
        create_db(path)
        cls.old_db = os.environ.get("DB_PATH")
        os.environ["DB_PATH"] = path

    @classmethod
    def tearDownClass(cls):
        if cls.old_db is None:
            os.environ.pop("DB_PATH", None)
        else:
            os.environ["DB_PATH"] = cls.old_db
        cls.tmp.cleanup()

    def test_unknown_product(self):
        with self.assertRaises(LookupError):
            predict.forecast(None, 999)

    def test_product_without_movements(self):
        self.assertEqual(predict.forecast(None, 4)["method"], "no_data")

    def test_long_history_uses_seasonal_average(self):
        fc = predict.forecast(None, 2)
        self.assertEqual(fc["method"], "seasonal_average")
        self.assertEqual(len(fc["forecast"]), 7)
        self.assertAlmostEqual(fc["total_next_7_days"], 70)

    def test_short_history_uses_average(self):
        fc = predict.forecast(None, 3)
        self.assertEqual(fc["method"], "history_average")
        self.assertAlmostEqual(fc["total_next_7_days"], 28)

    def test_one_day_product_orders_only_one_day(self):
        order = predict.order_suggestion(None, 1, current_stock=0)
        self.assertEqual(order["cover_days"], 1)
        self.assertEqual(order["suggested_order"], 10)

    def test_long_life_product_covers_until_next_delivery(self):
        order = predict.order_suggestion(None, 2, current_stock=5)
        self.assertEqual(order["delivery_every_days"], 2)
        self.assertEqual(order["cover_days"], 2)
        self.assertEqual(order["suggested_order"], 15)

    def test_order_never_negative(self):
        self.assertEqual(predict.order_suggestion(None, 2, current_stock=1000)["suggested_order"], 0)

    def test_waste_risk(self):
        risk = predict.waste_risk(None, 1, current_stock=30, shelf_life_days=1)
        self.assertAlmostEqual(risk["units_at_risk"], 20)
        self.assertEqual(risk["risk_level"], "high")


if __name__ == "__main__":
    unittest.main()

"""Parti bazlı fire riski testleri (src/batches.py)."""
import unittest

import pandas as pd

from src import batches


def moves(rows):
    return pd.DataFrame(rows, columns=["date", "type", "quantity"])


class CurrentBatchesTest(unittest.TestCase):
    def test_sales_use_oldest_batch_first(self):
        lots = batches.current_batches(moves([
            ("2026-10-01", "in", 10),
            ("2026-10-02", "in", 10),
            ("2026-10-02", "sale", 12),
        ]), shelf_life_days=4)
        self.assertEqual(len(lots), 1)
        self.assertEqual(lots[0]["quantity"], 8)
        self.assertEqual(str(lots[0]["arrived_on"].date()), "2026-10-02")

    def test_empty_when_everything_sold(self):
        self.assertEqual(batches.current_batches(moves([
            ("2026-10-01", "in", 5), ("2026-10-01", "sale", 5)]), 3), [])


class SimulateTest(unittest.TestCase):
    def test_old_batch_spoils_new_batch_is_sold(self):
        # Raf ömrü 4 gün. Eski parti (10 adet) 5 Ekim'de, yeni parti (6 adet) 7 Ekim'de bozulur.
        # Günde 3 satış: eski partiden 2 günde 6 satılır, 4'ü bozulur. Yeni parti zamanında biter.
        lots = batches.current_batches(moves([
            ("2026-10-01", "in", 10),
            ("2026-10-03", "in", 6),
        ]), shelf_life_days=4)
        result = batches.simulate(lots, [3] * 7, "2026-10-03")
        self.assertEqual(result[0]["units_at_risk"], 4)
        self.assertEqual(result[1]["units_at_risk"], 0)

    def test_already_expired_batch_is_fully_at_risk(self):
        lots = batches.current_batches(moves([("2026-10-01", "in", 6)]), shelf_life_days=1)
        result = batches.simulate(lots, [10] * 7, "2026-10-05")
        self.assertEqual(result[0]["units_at_risk"], 6)


if __name__ == "__main__":
    unittest.main()

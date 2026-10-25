"""Sipariş simülasyonunun testleri (src/backtest.py)."""
import unittest

import numpy as np

from src.backtest import simulate, upcoming


class SimulateTest(unittest.TestCase):
    def test_unsold_one_day_product_is_wasted(self):
        # Her gün 10 ekmek gelir, 6 tanesi satılır: kalan 4'ü ertesi gün fire olur
        sold, waste, _ = simulate([6] * 5, shelf_life=1, delivery_every=1,
                                  order_fn=lambda t, stock: 10, initial_stock=10)
        self.assertEqual(list(sold), [6] * 5)
        self.assertEqual(list(waste), [0, 4, 4, 4, 4])

    def test_long_life_product_is_not_wasted(self):
        sold, waste, _ = simulate([5] * 10, shelf_life=None, delivery_every=1,
                                  order_fn=lambda t, stock: 5, initial_stock=5)
        self.assertEqual(waste.sum(), 0)
        self.assertEqual(sold.sum(), 50)

    def test_cannot_sell_more_than_stock(self):
        sold, _, _ = simulate([10, 10], shelf_life=None, delivery_every=1,
                              order_fn=lambda t, stock: 0, initial_stock=12)
        self.assertEqual(list(sold), [10, 2])

    def test_orders_only_on_delivery_days(self):
        calls = []
        simulate([1] * 6, shelf_life=None, delivery_every=3,
                 order_fn=lambda t, stock: calls.append(t) or 0, initial_stock=10)
        self.assertEqual(calls, [0, 3])

    def test_stock_expiring_tomorrow_is_not_counted(self):
        seen = []
        simulate([0, 0], shelf_life=1, delivery_every=1,
                 order_fn=lambda t, stock: seen.append(stock) or 0, initial_stock=10)
        self.assertEqual(seen[0], 0)


class UpcomingTest(unittest.TestCase):
    def test_after_7_days_repeats_last_week(self):
        F = np.arange(20)
        self.assertEqual(list(upcoming(F, 2, 9)), [3, 4, 5, 6, 7, 8, 9, 3, 4])


if __name__ == "__main__":
    unittest.main()

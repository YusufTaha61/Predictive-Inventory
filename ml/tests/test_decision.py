"""Akıllı sipariş hesabının testleri (src/decision.py)."""
import unittest

from src import decision


class ServiceLevelTest(unittest.TestCase):
    def test_non_perishable_gets_95_percent(self):
        self.assertEqual(decision.service_level(35, 730, 7), 0.95)
        self.assertEqual(decision.service_level(35, None, 7), 0.95)

    def test_one_day_product_never_below_expected_sales(self):
        # Ekmek: artan her şey çöpe gider, ama hedef beklenen satışın altına inmez
        self.assertEqual(decision.service_level(15, 1, 1), decision.MIN_SERVICE)

    def test_leftover_sold_next_period_raises_target(self):
        # Süt: 4 gün dayanıyor, 2 günde bir geliyor -> ekmekten daha yüksek hedef
        milk = decision.service_level(45, 4, 2)
        self.assertGreater(milk, decision.MIN_SERVICE)
        self.assertLess(milk, decision.MAX_SERVICE)


class PurchasePriceTest(unittest.TestCase):
    def test_uses_purchase_price_when_given(self):
        self.assertEqual(decision.unit_cost(15, 10), 10)

    def test_falls_back_to_60_percent(self):
        self.assertAlmostEqual(decision.unit_cost(15, None), 9)
        self.assertAlmostEqual(decision.unit_cost(15, float("nan")), 9)

    def test_higher_margin_raises_target(self):
        # Süt: kâr yüksekse eksik kalmak daha pahalı -> daha yüksek hedef
        cheap = decision.service_level(45, 4, 2, cost=20)
        expensive = decision.service_level(45, 4, 2, cost=40)
        self.assertGreater(cheap, expensive)

    def test_no_margin_gives_minimum(self):
        self.assertEqual(decision.service_level(45, 4, 2, cost=50), decision.MIN_SERVICE)


class CoverDaysTest(unittest.TestCase):
    def test_not_longer_than_shelf_life(self):
        self.assertEqual(decision.cover_days(7, 1), 1)

    def test_uses_delivery_interval(self):
        self.assertEqual(decision.cover_days(3, 10), 3)

    def test_default_when_unknown(self):
        self.assertEqual(decision.cover_days(None, None), decision.DEFAULT_COVER_DAYS)

    def test_maximum(self):
        self.assertEqual(decision.cover_days(60, None), decision.MAX_COVER_DAYS)


class OrderQuantityTest(unittest.TestCase):
    def test_no_safety_stock_at_50_percent(self):
        result = decision.order_quantity([10, 10], current_stock=5, sigma=3, service=0.5)
        self.assertEqual(result["safety_stock"], 0)
        self.assertEqual(result["suggested_order"], 15)

    def test_more_safety_when_forecast_is_less_reliable(self):
        calm = decision.order_quantity([10] * 7, 0, sigma=1, service=0.95)
        noisy = decision.order_quantity([10] * 7, 0, sigma=5, service=0.95)
        self.assertGreater(noisy["safety_stock"], calm["safety_stock"])

    def test_never_negative(self):
        result = decision.order_quantity([1, 1], current_stock=100, sigma=1, service=0.95)
        self.assertEqual(result["suggested_order"], 0)


class HelpersTest(unittest.TestCase):
    def test_extend_forecast_repeats_week(self):
        week = [1, 2, 3, 4, 5, 6, 7]
        self.assertEqual(list(decision.extend_forecast(week, 9)), week + [1, 2])

    def test_error_std_of_constant_sales_is_zero(self):
        self.assertEqual(decision.forecast_error_std([10] * 60), 0)

    def test_error_std_short_history(self):
        self.assertAlmostEqual(decision.forecast_error_std([4] * 10), 2.0)


if __name__ == "__main__":
    unittest.main()

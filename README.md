# Predictive Inventory

**SaaS-based smart stock and waste forecasting for SMEs** — graduation project.

A web app that helps small shops order the right amount of stock. Machine learning models forecast how much each product will sell in the next 7 days, how much to order now and how much of the current stock may expire unsold.

This system only gives suggestions. The final order decision always belongs to the shop owner. The interface is in Turkish.

## Features

- **Products and stock movements:** stock in, sale and waste records, plus bulk upload with a CSV file
- **7-day forecast** for each product with Random Forest / Gradient Boosting models
- **Order suggestion:** expected sales until the next delivery + safety stock − current stock; for perishable products, waste cost is balanced against lost profit using the purchase price
- **Waste risk per batch:** every stock-in is a batch, sales use the oldest batch first, and what is left on the expiry day is shown as at risk
- **Promotions:** discounts and campaign days raise the forecast
- **Dashboard:** sales, revenue, waste rate and low-stock products
- **Multi-company (SaaS):** several companies use one installation and each only sees its own data
- **Demo data:** one year of sales for "Demo Market" (17 products); a demo login button is shown only on a local setup
- **Security:** every query is limited to the user's company, the ML service only runs on 127.0.0.1, and the demo account can't be deleted or have its password changed

## Tech stack

PHP 8.4 · Laravel 13 · Tailwind · SQLite · Python · pandas · scikit-learn · FastAPI

## Results

Measured on the demo data by replaying the last 180 days (details in [`ml/reports/`](ml/reports)).

| Ordering method | Waste | Waste + lost profit |
|---|---|---|
| By habit (what a shop does today) | 5.8% | baseline |
| Smart order with a simple forecast | 2.1% | 36% lower |
| Smart order with the ML forecast | 1.9% | 53% lower |

The ML models also forecast better than simple rules: about 19% error (WAPE) against 22% for the best simple rule.

## Setup

```bash
# Laravel (backend/)
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve                     # http://127.0.0.1:8000

# ML service (ml/, second terminal)
python -m venv .venv && .venv\Scripts\activate
pip install -r requirements.txt
uvicorn src.api:app --port 8001

# Train the model (backend/)
php artisan ml:train
```

Tests: `php artisan test` (backend) and `python -m unittest` (ml).

## Project structure

```
backend/      Laravel app: pages, ForecastService, CSV import, demo seeder, tests
ml/src/       api.py, predict.py, decision.py (order), batches.py (waste), train.py, backtest.py
ml/reports/   results and charts
```

## Notes

- The models are trained on synthetic data, so the results show that the system works, not how it performs in a real shop. It must be tested with real sales data before real use.
- New shops have little sales history (cold start), so the first forecasts are less reliable.
- The ML service has no login: keep it on 127.0.0.1. Before going online set `APP_ENV=production`, `APP_DEBUG=false` and use HTTPS.

## Author

Yusuf Taha Çebi — graduation project, supervised by Dr. Sibel Boran.

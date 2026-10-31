# Predictive Inventory

[![Tests](https://github.com/YusufTaha61/Predictive-Inventory/actions/workflows/tests.yml/badge.svg)](https://github.com/YusufTaha61/Predictive-Inventory/actions/workflows/tests.yml)

**SaaS-based smart stock and waste forecasting for SMEs** — graduation project.

A web app that helps small shops order the right amount of stock. ML models forecast sales for the next 7 days, suggest how much to order and show which stock may expire unsold. The final decision always belongs to the shop owner. The interface is in Turkish.

## Features

- **Stock tracking:** products, stock in / sale / waste records, bulk CSV upload
- **7-day forecast** per product (Random Forest / Gradient Boosting), raised on promotion days
- **Order suggestion** that balances waste cost against lost profit for perishable products
- **Waste risk per batch:** oldest stock is sold first, what is left on the expiry day is flagged
- **Dashboard:** sales, revenue, waste rate, low-stock products
- **Multi-company (SaaS):** each company only sees its own data
- **Demo data:** one year of sales for "Demo Market" with a local-only demo login

## Tech stack

PHP 8.4 · Laravel 13 · Tailwind · SQLite · Python · pandas · scikit-learn · FastAPI · Docker · GitHub Actions

## Results

Replaying the last 180 days of demo data (details in [`ml/reports/`](ml/reports)):

| Ordering method | Waste | Waste + lost profit |
|---|---|---|
| By habit | 5.8% | baseline |
| Smart order, simple forecast | 2.1% | 36% lower |
| Smart order, ML forecast | 1.9% | 53% lower |

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

**ML service with Docker** (instead of the venv steps above, from the project root):

```bash
docker build -t predictive-inventory-ml ml
docker run --rm -p 127.0.0.1:8001:8001 -v "${PWD}\backend\database:/data" -v "${PWD}\ml\models:/app/models" predictive-inventory-ml
```

## Tests and CI

Tests: `php artisan test` (backend) and `python -m unittest` (ml).
GitHub Actions runs both on every push to `main` ([`tests.yml`](.github/workflows/tests.yml)).

## Notes

- The models are trained on synthetic data; real sales data is needed before real use.
- The ML service has no login: keep it on 127.0.0.1. For production set `APP_DEBUG=false` and use HTTPS.

## Author

Yusuf Taha Çebi — graduation project, supervised by Dr. Sibel Boran.

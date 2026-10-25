# Predictive Inventory

**SaaS-based smart stock and waste forecasting for SMEs** — graduation project.

Small shops (markets, bakeries, delis) usually order stock by habit. For perishable products this
means either throwing food away or running out of stock. Predictive Inventory lets a shop record its
stock movements and then tells it, per product:

- how much it will probably sell in the next 7 days,
- how many units to order now,
- how much of the current stock is likely to expire unsold (waste risk).

Several companies can use the same installation; each company only sees its own data.

## Features

| Area | What it does |
|---|---|
| Accounts | Register with a company name, log in, profile (Laravel Breeze) |
| Multi-tenancy | Every product, stock movement and promotion belongs to a company; other companies' records are never shown |
| Products | Create / edit / delete products with SKU, unit (piece, kg, litre…), price and shelf life |
| Stock movements | Record stock in, sales and waste (decimal quantities allowed); current stock is calculated from the movements |
| CSV import | Upload many movements at once from an Excel CSV file, with row-by-row error messages |
| Promotions | Enter campaign days and discount % per product; forecasts raise sales on those days |
| Dashboard | Last 30 days: sales, revenue, waste rate, waste cost, daily chart, top waste products, low stock |
| Forecast page | 7-day demand forecast, order suggestion and waste risk for a product; campaign days are marked |
| ML service | Python FastAPI service with a trained forecasting model and simple fallbacks for new products |
| Demo data | One year of realistic data for "Demo Market" (17 products, weekends, seasons, holidays, campaigns, waste) |

## How it works

```
 Browser ──> Laravel app (backend/) ──HTTP/JSON──> Python ML service (ml/)
              SQLite database  <──── read-only ────  FastAPI + trained model
              products, stock movements, promotions   /forecast, /order-suggestion, /waste-risk

 php artisan ml:train  =  python -m src.train ──> ml/models/forecast_model.joblib
                          then POST /reload, so the running service uses the new model
```

- The Python service reads the Laravel SQLite database directly in **read-only** mode
  (`backend/database/database.sqlite`, or `DB_PATH`). It never writes to it. Laravel decides who
  may see which product; the service only runs on `127.0.0.1`.
- **Model:** the training script compares simple baselines with Random Forest and Gradient Boosting
  using time-based (rolling-origin) validation and saves the best ML model. Features: sales 1–4 weeks
  ago, weekday averages, rolling means, calendar (weekday, month, holidays), promotion day and price
  ratio, shelf life and price. The model learns sales relative to the product's normal level, so
  products selling 60 a day and 2 a day can share one model.
- **Fallbacks (cold start):** when the model cannot be used (new product, short history, model not
  trained yet) the forecast falls back to the average of the last 4 same weekdays, or to the average
  of the available days. These averages leave out past campaign days and multiply coming campaign
  days by the uplift measured on this product's past campaigns (1.3 if it has fewer than 3 campaign days).
- **Order suggestion:** 7-day forecast + 20 % safety stock − current stock.
- **Waste risk:** current stock − what can be sold before the shelf life ends.

## Tech stack

- **Backend:** PHP 8.4, Laravel 13, Laravel Breeze, Blade, Tailwind CSS, Alpine.js, SQLite
- **ML:** Python 3, pandas, scikit-learn (Random Forest, Gradient Boosting), joblib, FastAPI, Uvicorn

## Project structure

```
predictive-inventory/
├── backend/                       Laravel web app
│   ├── app/Models/                Company, User, Product, StockMovement, Promotion
│   ├── app/Http/Controllers/      dashboard, products, forecast, stock movements, CSV import, promotions
│   ├── app/Services/              ForecastService (calls the ML API), MovementCsvImporter
│   ├── app/Console/Commands/      TrainModel (php artisan ml:train)
│   └── database/seeders/          DemoDataSeeder (Demo Market)
└── ml/                            Python forecasting service
    ├── src/data.py                reads products, daily sales and promotions from the SQLite database
    ├── src/features.py            7-day-horizon feature engineering (no leakage)
    ├── src/train.py               baselines vs Random Forest vs Gradient Boosting, saves the best model
    ├── src/predict.py             forecast, order suggestion, waste risk, cold-start fallbacks
    └── src/api.py                 FastAPI app
```

## Getting started

Requirements: PHP 8.4, Composer, Node.js, Python 3.10+.

### 1. Laravel app

```bash
cd backend
composer install
cp .env.example .env              # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed        # creates the SQLite database and loads the demo data
npm install
npm run build
php artisan serve                 # http://127.0.0.1:8000
```

If `migrate` asks to create `database/database.sqlite`, answer yes.

**Demo login:** `demo@market.test` / `password` (the login page has a button that fills these in).

To reload only the demo data later: `php artisan db:seed`

### 2. ML service

In a second terminal:

```bash
cd ml
python -m venv .venv              # Windows: py -m venv .venv
source .venv/bin/activate         # Windows: .venv\Scripts\activate
pip install -r requirements.txt
uvicorn src.api:app --port 8001   # API docs at http://127.0.0.1:8001/docs
```

Train the model on the app's data (in `backend/`, takes a few minutes):

```bash
php artisan ml:train
```

`ml:train` runs the training with the Python in `ml/.venv` and then tells the running service to
load the new model, so no restart is needed. If the service is not running, the model is loaded
when it starts. Retrain after adding a lot of new data or new products.

After changing Python files, restart the service (`Ctrl + C`, then `uvicorn` again).

The Laravel app finds the service through `ML_SERVICE_URL` in `backend/.env`
(default `http://127.0.0.1:8001`). If the service is not running, the forecast page shows a
clear error message; the rest of the app keeps working.

## CSV import format

Stock movements can be uploaded from **Stok hareketleri → Dosyadan yükle**. A template file filled
with the company's own products can be downloaded there. The first row is the header; `;` and `,`
separators and Excel's Turkish encoding are both accepted. At most 5,000 rows per file.

```
Tarih;Ürün;Tür;Miktar;Not
16.10.2026;FRN-001;Satış;45;
16.10.2026;Süt 1 L;Giriş;40;sabah teslimatı
16.10.2026;MNV-001;Fire;1,5;ezilmiş
```

| Column | Meaning |
|---|---|
| `Tarih` | date, `16.10.2026` or `2026-10-16`, not in the future |
| `Ürün` | product SKU or product name |
| `Tür` | `Giriş` (stock in), `Satış` (sale) or `Fire` (waste); `in` / `sale` / `waste` also work |
| `Miktar` | positive number, decimals allowed (`1,5` or `1.5`) |
| `Not` | optional note |

If any row has an error, or a product's stock would go below zero, nothing is saved and the
problems are listed with their line numbers.

## ML API

| Endpoint | Returns |
|---|---|
| `GET /health` | model name, last training day and validation metrics |
| `POST /reload` | load the model file again after retraining |
| `GET /forecast/{product_id}` | demand for the next 7 days (campaign days marked) |
| `GET /order-suggestion/{product_id}?current_stock=` | units to order now and safety stock |
| `GET /waste-risk/{product_id}?current_stock=&shelf_life_days=` | units likely to expire unsold, risk level (none / low / medium / high) |

The forecast method is chosen automatically:

| Situation | Method |
|---|---|
| product known to the model, 35+ days of history | `ml` (trained model) |
| 28+ days of history | `seasonal_average` (last 4 same weekdays) |
| fewer than 28 days | `history_average` |
| no history | `no_data` |

Every response says which method was used and adds plain-language warnings.

## Important limitations

- The demo data is synthetic. Weekly, seasonal, promotion and holiday patterns were put in on
  purpose, so the results show that the pipeline works, not how well it performs in a real shop.
  It should be validated on real sales data.
- Daily random noise puts a floor under the forecast error, especially for slow-selling products.
- The model is retrained only when `php artisan ml:train` is run.

## Next steps

1. Validation on real sales data or a public retail dataset (M5, Favorita, Rossmann).
2. Automated tests for products, stock movements, CSV import, promotions and the forecast page.

## Author

Yusuf Taha Çebi — graduation project, supervised by Dr. Sibel Boran.

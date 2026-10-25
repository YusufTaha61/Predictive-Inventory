# Predictive Inventory

**SaaS-based smart stock and waste forecasting for SMEs** — graduation project.

Small shops usually order stock by habit, so perishable products are either thrown away or run out.
Predictive Inventory records a shop's stock movements and tells it, for each product:

- how much it will probably sell in the next 7 days,
- how many units to order now,
- how much of the current stock may expire unsold (waste risk).

Several companies can use the same installation; each company only sees its own data.

## Features

- **Products and stock movements:** stock in, sales and waste; current stock is calculated from the movements
- **CSV import:** upload many movements at once from Excel, with row-by-row error messages
- **Promotions:** campaign days and discounts per product; forecasts raise sales on those days
- **Dashboard:** last 30 days of sales, revenue, waste rate and cost, low-stock products
- **Forecast page:** 7-day forecast, order suggestion and waste risk for each product
- **Demo data:** one year of realistic data for "Demo Market" (17 products)

## How it works

```
Browser ──> Laravel app (backend/) ──HTTP──> Python ML service (ml/)
             SQLite database  <── read-only ──  FastAPI + trained model
```

- The Python service reads the Laravel database (read-only) and predicts daily sales with a
  **Random Forest / Gradient Boosting** model, chosen by time-based validation against simple baselines.
- Features: past sales, weekday averages, calendar and holidays, promotion days and discount, shelf life, price.
- New products with little history get a simple average instead of the model.
- **Order suggestion** = expected sales until the next delivery + safety stock − current stock.
  The safety stock grows with the forecast error. For perishable products it balances the cost of
  waste against the lost profit of an empty shelf (newsvendor model), and an order never covers
  more days than the product's shelf life.
- **Waste risk** = current stock − what can be sold before the shelf life ends.

**Tech stack:** PHP 8.4, Laravel 13, Breeze, Tailwind CSS, SQLite · Python, pandas, scikit-learn, FastAPI

## Getting started

Requirements: PHP 8.4, Composer, Node.js, Python 3.10+.

**1. Laravel app**

```bash
cd backend
composer install
cp .env.example .env              # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed        # creates the database and loads the demo data
npm install && npm run build
php artisan serve                 # http://127.0.0.1:8000
```

Demo login: `demo@market.test` / `password` (the login page has a button that fills these in).

**2. ML service** (second terminal)

```bash
cd ml
python -m venv .venv              # Windows: py -m venv .venv
source .venv/bin/activate         # Windows: .venv\Scripts\activate
pip install -r requirements.txt
uvicorn src.api:app --port 8001
```

**3. Train the model** (in `backend/`)

```bash
php artisan ml:train
```

This trains the model on the app's data and loads it into the running service.

## Results

Measured on the demo data with `ml/src/backtest.py` and `ml/src/report.py`
(numbers in [`ml/reports/`](ml/reports)).

**Forecast accuracy.** Error of 7-day-ahead forecasts, time-based validation (lower is better).
The ML models beat the simple rules a shop would use.

![Model comparison](ml/reports/model_comparison.png)

**Ordering simulation.** The last 180 days are replayed with the same sales, shelf lives and
delivery days; only the order quantity changes. Ordering with the forecast lowers the total cost
(waste + lost profit) compared with ordering by habit, and the ML forecast does better than a
simple average. The first rule of the app (7-day forecast + 20 %) is also in the table: it was far
too much for products that spoil in a few days, which is why it was replaced.

![Ordering policies](ml/reports/policy_comparison.png)

**What the model looks at.** Promotions, recent sales level and volatility matter most.

![Feature importance](ml/reports/feature_importance.png)

To reproduce (in `ml/`, after `php artisan ml:train`):

```bash
python -m src.backtest           # ordering simulation
python -m src.report             # model comparison and feature importance charts
python -m unittest               # automated tests
```

## Limitations

The demo data is synthetic, so results show that the system works, not how well it performs in a
real shop. The purchase price is not stored, so it is assumed to be 60 % of the sales price.
Recorded sales are used as demand, so lost sales are underestimated on days when stock ran out.
Next step: validation on real sales data.

## Author

Yusuf Taha Çebi — graduation project, supervised by Dr. Sibel Boran.

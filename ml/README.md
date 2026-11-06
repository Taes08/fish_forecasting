# FishCast ML forecasting

Uses your existing `price_records`, `forecasts`, `forecast_runs`, and `model_evaluations` tables. No database migration needed for the uploaded SQL schema.

## Install on Windows (XAMPP)

```powershell
cd C:\xampp\htdocs\fish_forecasting
py -m venv .venv
.venv\Scripts\activate
pip install -r ml\requirements.txt
python ml\train_forecast.py --horizon 7
```

Set `FISHCAST_DB_HOST`, `FISHCAST_DB_USER`, `FISHCAST_DB_PASSWORD`, `FISHCAST_DB_NAME` environment variables if defaults differ.

Open `http://localhost/fish_forecasting/admin/forecasts.php` after signing in as admin. Repeat the CLI training when new verified price observations are available.

## Model behavior

* At least 45 **distinct dates** are required per fish species and market. Your uploaded SQL dump has only one price observation, so it cannot train a credible ML model yet.
* Multiple vendor prices on the same date are averaged. The model uses the seven most recent observed prices and calendar features. Missing dates are not filled with fake data.
* Chronological holdout evaluation compares Random Forest against the last-price baseline. If Random Forest does not beat the baseline on MAE, baseline forecasts are stored instead.
* Forecasts are recursive for the next 1–30 calendar days. Longer horizons and irregular observation schedules can reduce reliability.
* `model_evaluations` stores MAE and RMSE. Forecasts are illustrative model estimates and do not yet include prediction intervals or full decision-support rules.
* For production, improve evaluation using rolling-origin backtests and direct horizon-specific validation before using 7-day predictions for operational decisions.

Do not expose the training script through a public web endpoint. Run it via a scheduled Windows Task or a trusted administrator terminal.

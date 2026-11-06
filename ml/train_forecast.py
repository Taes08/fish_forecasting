# Train per-market/species next-observation regression and publish 7 calendar-day forecasts.
# Run from CLI: python ml/train_forecast.py --horizon 7
# Only observed dates are used in training; missing dates are never fabricated.
import argparse
import json
import math
import os
from datetime import timedelta

import numpy as np
import pandas as pd
import pymysql
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error

LAGS = 7
MIN_OBSERVATIONS = 45

def features(history, target_date):
    # Known prices only; calendar features for next observation.
    if len(history) < LAGS:
        raise ValueError('Insufficient history')
    a = np.array(history[-LAGS:], dtype=float)
    return [*a[::-1], float(a.mean()), float(a.std()),
            target_date.weekday(), target_date.month, target_date.dayofyear]

def train_series(rows, horizon):
    # Aggregate multiple vendor observations for same date to daily mean.
    frame = pd.DataFrame(rows, columns=['day','price'])
    frame['day'] = pd.to_datetime(frame['day'])
    frame['price'] = pd.to_numeric(frame['price'])
    frame = frame.groupby('day', as_index=False)['price'].mean().sort_values('day')
    if len(frame) < MIN_OBSERVATIONS:
        return None, f'Only {len(frame)} distinct dates (need {MIN_OBSERVATIONS})'
    days = [d.date() for d in frame['day']]
    prices = frame['price'].to_numpy(dtype=float)
    X = np.array([features(prices[:i], days[i]) for i in range(LAGS,len(prices))])
    y = prices[LAGS:]
    # Chronological holdout, no shuffled train/test split.
    split = max(20, int(len(X)*.8))
    if len(X)-split < 7:
        split = len(X)-7
    if split < 15:
        return None, 'Not enough train/test samples'
    model = RandomForestRegressor(n_estimators=160, min_samples_leaf=2, random_state=42, n_jobs=-1)
    model.fit(X[:split], y[:split])
    predicted = model.predict(X[split:])
    baseline = X[split:,0]  # most recent known price
    mae = float(mean_absolute_error(y[split:], predicted))
    baseline_mae = float(mean_absolute_error(y[split:], baseline))
    rmse = float(np.sqrt(mean_squared_error(y[split:], predicted)))
    # Only deploy ML if it beats simple last-price baseline.
    use_ml = mae < baseline_mae
    if use_ml:
        model.fit(X,y)
    history = list(prices)
    last_date = days[-1]
    future=[]
    for j in range(1,horizon+1):
        target = last_date + timedelta(days=j)
        val = float(model.predict([features(history,target)])[0]) if use_ml else float(history[-1])
        val = round(max(0,val),2)
        future.append((target,val))
        history.append(val)
    return {'model':'RandomForest' if use_ml else 'LastObservedBaseline',
            'mae':mae if use_ml else baseline_mae,
            'rmse':rmse if use_ml else float(np.sqrt(np.mean((y[split:]-baseline)**2))),
            'ml_mae':mae,'baseline_mae':baseline_mae,
            'test_n':len(y[split:]), 'start':days[0], 'end':last_date,
            'test_start':days[LAGS+split], 'test_end':days[-1], 'future':future},None

def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--horizon',type=int,default=7)
    args=parser.parse_args()
    if not 1 <= args.horizon <= 30:
        parser.error('horizon must be between 1 and 30')
    conn=pymysql.connect(host=os.getenv('FISHCAST_DB_HOST','127.0.0.1'),
        user=os.getenv('FISHCAST_DB_USER','root'), password=os.getenv('FISHCAST_DB_PASSWORD',''),
        database=os.getenv('FISHCAST_DB_NAME','fish_price_forecasting_db'),
        charset='utf8mb4', autocommit=False)
    try:
        with conn.cursor() as c:
            c.execute('''SELECT market_id, fish_species_id, observed_on, AVG(price_per_kg)
                         FROM price_records WHERE price_per_kg > 0
                         GROUP BY market_id,fish_species_id,observed_on
                         ORDER BY market_id,fish_species_id,observed_on''')
            raw=c.fetchall()
        series={}
        for mid,fid,day,price in raw:
            series.setdefault((mid,fid),[]).append((day,float(price)))
        if not series:
            print('No price records. No forecasts generated.')
            return
        for (mid,fid),observations in series.items():
            result,reason=train_series(observations,args.horizon)
            if result is None:
                print(f'SKIP market={mid} fish={fid}: {reason}')
                continue
            with conn.cursor() as c:
                c.execute('''INSERT INTO forecast_runs
                    (model_name,model_version,training_start,training_end,test_start,test_end,horizon_days,parameters_json)
                    VALUES (%s,%s,%s,%s,%s,%s,%s,%s)''',
                    (result['model'],'1.0',result['start'],result['end'],result['test_start'],result['test_end'],args.horizon,
                     json.dumps({'lags':LAGS,'test_n':result['test_n'],'ml_mae':result['ml_mae'],
                                 'baseline_mae':result['baseline_mae'],'strategy':'recursive; calendar days; last observed fallback'})))
                run_id=c.lastrowid
                c.execute('''INSERT INTO model_evaluations
                    (forecast_run_id,market_id,fish_species_id,mae,rmse,test_observations)
                    VALUES (%s,%s,%s,%s,%s,%s)''',
                    (run_id,mid,fid,result['mae'],result['rmse'],result['test_n']))
                c.executemany('''INSERT INTO forecasts
                    (forecast_run_id,market_id,fish_species_id,target_date,predicted_price_per_kg)
                    VALUES (%s,%s,%s,%s,%s)''',
                    [(run_id,mid,fid,d,v) for d,v in result['future']])
            conn.commit()
            print(f'OK market={mid} fish={fid}: {result["model"]}, MAE={result["mae"]:.2f}, run={run_id}')
        print('Completed. Forecasts are model estimates, not guaranteed prices.')
    finally:
        conn.close()

if __name__=='__main__':
    main()

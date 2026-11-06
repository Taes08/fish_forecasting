<?php
// Public aggregated analytics only; no vendor identifiers or personal information.
$analyticsError = null;
$analytics = ['species'=>0,'markets'=>0,'records'=>0,'average'=>null,'speciesOptions'=>[], 'marketOptions'=>[], 'trend'=>[], 'speciesPrices'=>[], 'marketCounts'=>[], 'recent'=>[]];
try {
    $host = getenv('FISHCAST_DB_HOST') ?: '127.0.0.1';
    $db = getenv('FISHCAST_DB_NAME') ?: 'fish_price_forecasting_db';
    $user = getenv('FISHCAST_DB_USER') ?: 'root';
    $pass = getenv('FISHCAST_DB_PASS') !== false ? getenv('FISHCAST_DB_PASS') : '';
    $pdoAnalytics = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    $analytics['species'] = (int)$pdoAnalytics->query('SELECT COUNT(*) FROM fish_species WHERE is_active=1')->fetchColumn();
    $analytics['markets'] = (int)$pdoAnalytics->query('SELECT COUNT(*) FROM markets WHERE is_active=1')->fetchColumn();
    $summary = $pdoAnalytics->query('SELECT COUNT(*) AS records, ROUND(AVG(price_per_kg),2) AS average_price FROM price_records WHERE price_per_kg > 0')->fetch();
    $analytics['records'] = (int)$summary['records'];
    $analytics['average'] = $summary['average_price'] === null ? null : (float)$summary['average_price'];
    $analytics['speciesOptions'] = $pdoAnalytics->query('SELECT id, common_name FROM fish_species WHERE is_active=1 ORDER BY common_name')->fetchAll();
    $analytics['marketOptions'] = $pdoAnalytics->query('SELECT id, name FROM markets WHERE is_active=1 ORDER BY name')->fetchAll();

    // Latest 30 observed calendar dates for each fish/market; aggregated across vendors.
    $analytics['trend'] = $pdoAnalytics->query("SELECT p.fish_species_id AS fish_id, p.market_id, DATE_FORMAT(p.observed_on,'%Y-%m-%d') AS observed_on, ROUND(AVG(p.price_per_kg),2) AS avg_price
        FROM price_records p WHERE p.price_per_kg > 0 AND p.observed_on >= CURDATE() - INTERVAL 90 DAY
        GROUP BY p.fish_species_id,p.market_id,p.observed_on ORDER BY p.observed_on ASC")->fetchAll();
    $analytics['speciesPrices'] = $pdoAnalytics->query("SELECT f.common_name AS label, ROUND(AVG(p.price_per_kg),2) AS value
        FROM price_records p JOIN fish_species f ON f.id=p.fish_species_id
        WHERE p.price_per_kg>0 GROUP BY f.id,f.common_name ORDER BY value DESC LIMIT 12")->fetchAll();
    $analytics['marketCounts'] = $pdoAnalytics->query("SELECT m.name AS label, COUNT(*) AS value FROM price_records p JOIN markets m ON m.id=p.market_id
        GROUP BY m.id,m.name ORDER BY value DESC LIMIT 12")->fetchAll();
    $analytics['recent'] = $pdoAnalytics->query("SELECT DATE_FORMAT(p.observed_on,'%Y-%m-%d') AS observed_on, f.common_name AS fish, m.name AS market,
        ROUND(AVG(p.price_per_kg),2) AS avg_price, COUNT(*) AS samples
        FROM price_records p JOIN fish_species f ON f.id=p.fish_species_id JOIN markets m ON m.id=p.market_id
        WHERE p.price_per_kg>0 GROUP BY p.observed_on,f.id,f.common_name,m.id,m.name
        ORDER BY p.observed_on DESC, f.common_name LIMIT 8")->fetchAll();
} catch (Throwable $e) {
    error_log('FishCast public analytics error: '.$e->getMessage());
    $analyticsError = 'Market analytics are temporarily unavailable. Check the database connection and tables.';
}

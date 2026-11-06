<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';
function fe($x) { return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$fish = $pdo->query('SELECT id,common_name FROM fish_species ORDER BY common_name')->fetchAll(PDO::FETCH_ASSOC);
$markets = $pdo->query('SELECT id,name FROM markets ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$fishId = filter_input(INPUT_GET,'fish_id',FILTER_VALIDATE_INT) ?: 0;
$marketId = filter_input(INPUT_GET,'market_id',FILTER_VALIDATE_INT) ?: 0;
$sql = 'SELECT f.target_date,f.predicted_price_per_kg, f.market_id, f.fish_species_id,
               s.common_name, m.name AS market_name, r.model_name, r.created_at,
               e.mae,e.rmse,e.test_observations
        FROM forecasts f JOIN forecast_runs r ON r.id=f.forecast_run_id
        JOIN fish_species s ON s.id=f.fish_species_id
        JOIN markets m ON m.id=f.market_id
        LEFT JOIN model_evaluations e ON e.forecast_run_id=r.id
          AND e.market_id=f.market_id AND e.fish_species_id=f.fish_species_id
        WHERE r.id = (SELECT MAX(r2.id) FROM forecast_runs r2
          JOIN forecasts f2 ON f2.forecast_run_id=r2.id
          WHERE f2.market_id=f.market_id AND f2.fish_species_id=f.fish_species_id)';
$args=[];
if ($fishId) { $sql.=' AND f.fish_species_id=?'; $args[]=$fishId; }
if ($marketId) { $sql.=' AND f.market_id=?'; $args[]=$marketId; }
$sql.=' ORDER BY s.common_name,m.name,f.target_date LIMIT 300';
$stmt=$pdo->prepare($sql); $stmt->execute($args); $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
$labels=[];$values=[];
foreach ($rows as $r) { $labels[]=$r['common_name'].' · '.$r['market_name'].' · '.$r['target_date']; $values[]=(float)$r['predicted_price_per_kg']; }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ML Forecasts | FishCast</title><script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="https://unpkg.com/lucide@latest"></script></head>
<body class="bg-slate-50 text-[#0B3954]">
<?php include __DIR__.'/../includes/sidebar.php'; ?>
<div class="lg:ml-64 min-h-screen"><?php include __DIR__.'/../includes/header.php'; ?>
<main class="max-w-7xl mx-auto p-6 space-y-6">
<div><h1 class="text-3xl font-bold">Machine Learning Price Forecasts</h1><p class="text-slate-600 mt-2">Forecasts from your verified historical records. Model estimates are not guaranteed prices.</p></div>
<form method="get" class="bg-white p-5 rounded-2xl border flex flex-wrap gap-3 items-end">
<label class="text-sm">Fish species<select name="fish_id" class="block border rounded-lg p-2 mt-1"><option value="0">All species</option><?php foreach($fish as $f): ?><option value="<?= (int)$f['id'] ?>" <?= $fishId==(int)$f['id']?'selected':'' ?>><?= fe($f['common_name']) ?></option><?php endforeach; ?></select></label>
<label class="text-sm">Market<select name="market_id" class="block border rounded-lg p-2 mt-1"><option value="0">All markets</option><?php foreach($markets as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $marketId==(int)$m['id']?'selected':'' ?>><?= fe($m['name']) ?></option><?php endforeach; ?></select></label>
<button class="bg-[#087E8B] text-white px-5 py-2 rounded-lg">Filter</button></form>
<section class="bg-white rounded-2xl border p-5"><h2 class="font-semibold mb-3">Forecast prices (₱ per kg)</h2>
<?php if ($rows): ?><div style="height:320px"><canvas id="forecastChart"></canvas></div><?php else: ?><p class="text-slate-500">No forecasts available yet. Collect at least 45 distinct observation dates per market and fish species, then run the Python trainer.</p><?php endif; ?></section>
<section class="bg-white rounded-2xl border p-5 overflow-x-auto"><h2 class="font-semibold mb-3">Latest forecast run by species and market</h2><table class="min-w-full text-sm"><thead><tr class="border-b text-left"><th class="p-2">Fish</th><th class="p-2">Market</th><th class="p-2">Target date</th><th class="p-2">Predicted ₱/kg</th><th class="p-2">Model</th><th class="p-2">MAE</th><th class="p-2">Generated</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr class="border-b"><td class="p-2"><?= fe($r['common_name']) ?></td><td class="p-2"><?= fe($r['market_name']) ?></td><td class="p-2"><?= fe($r['target_date']) ?></td><td class="p-2">₱<?= number_format((float)$r['predicted_price_per_kg'],2) ?></td><td class="p-2"><?= fe($r['model_name']) ?></td><td class="p-2"><?= $r['mae']===null?'—':number_format((float)$r['mae'],2) ?></td><td class="p-2"><?= fe($r['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></section>
</main></div><script>
if(window.lucide) lucide.createIcons();
<?php if($rows): ?>
new Chart(document.getElementById('forecastChart'),{type:'line',data:{labels:<?= json_encode($labels,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,datasets:[{label:'Predicted price (₱/kg)',data:<?= json_encode($values) ?>,borderColor:'#087E8B',backgroundColor:'rgba(46,196,182,.15)',fill:true,tension:.25}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:false}}}});
<?php endif; ?>
</script></body></html>

<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';
function esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['import_csrf'])) $_SESSION['import_csrf'] = bin2hex(random_bytes(32));
$error = ''; $notice = $_SESSION['import_notice'] ?? ''; unset($_SESSION['import_notice']);
$columns = ['observed_on','market','fish_species','price_per_kg','vendor_code','source_type','source_reference','collection_time','product_condition','remarks'];
$types = ['vendor_interview','market_record','government_record','other'];
$markets = $pdo->query('SELECT id,name FROM markets WHERE is_active=1')->fetchAll();
$species = $pdo->query('SELECT id,common_name FROM fish_species WHERE is_active=1')->fetchAll();
$marketMap=[]; $speciesMap=[];
foreach ($markets as $r) $marketMap[mb_strtolower(trim($r['name']))] = (int)$r['id'];
foreach ($species as $r) $speciesMap[mb_strtolower(trim($r['common_name']))] = (int)$r['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['import_csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request token'); }
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'preview') {
            unset($_SESSION['price_import_preview']);
            if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Select a CSV file.');
            if ($_FILES['csv']['size'] > 2*1024*1024) throw new RuntimeException('CSV must be 2 MB or smaller.');
            if (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') throw new RuntimeException('File must have .csv extension.');
            $fp = fopen($_FILES['csv']['tmp_name'], 'r');
            if (!$fp) throw new RuntimeException('Unable to read CSV.');
            $header = fgetcsv($fp);
            if (!$header) throw new RuntimeException('CSV is empty.');
            $header = array_map(fn($x)=>trim((string)$x), $header);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if ($header !== $columns) throw new RuntimeException('CSV headers must exactly match the template, in the same order.');
            $rows=[]; $seen=[]; $line=1;
            $exists=$pdo->prepare('SELECT 1 FROM price_records WHERE market_id=? AND fish_species_id=? AND observed_on=? AND vendor_code=? LIMIT 1');
            while (($values=fgetcsv($fp)) !== false) {
                $line++;
                if (count($values) === 1 && trim((string)$values[0]) === '') continue;
                if (count($rows)>=1000) throw new RuntimeException('Maximum 1,000 data rows per upload.');
                $errors=[];
                if (count($values)!==count($columns)) { $rows[]=['line'=>$line,'errors'=>['Incorrect number of columns'],'data'=>null]; continue; }
                $v=array_combine($columns,array_map('trim',$values));
                $m=$marketMap[mb_strtolower($v['market'])] ?? null;
                $s=$speciesMap[mb_strtolower($v['fish_species'])] ?? null;
                if (!$m) $errors[]='Unknown/inactive market';
                if (!$s) $errors[]='Unknown/inactive fish species';
                $date=DateTimeImmutable::createFromFormat('!Y-m-d',$v['observed_on']);
                if (!$date || $date->format('Y-m-d')!==$v['observed_on'] || $v['observed_on']>date('Y-m-d')) $errors[]='Invalid/future date';
                if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D',$v['price_per_kg']) || (float)$v['price_per_kg']<=0) $errors[]='Invalid positive price';
                if ($v['vendor_code']==='' || mb_strlen($v['vendor_code'])>50) $errors[]='Vendor code required (max 50)';
                if (!in_array($v['source_type'],$types,true)) $errors[]='Invalid source type';
                if (mb_strlen($v['source_reference'])>255 || mb_strlen($v['product_condition'])>100) $errors[]='Text field too long';
                if ($v['collection_time']!=='' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D',$v['collection_time'])) $errors[]='Invalid collection time';
                if ($m && $s && $date && $date->format('Y-m-d')===$v['observed_on'] && $v['vendor_code']!=='') {
                    $key=implode('|',[$m,$s,$v['observed_on'],$v['vendor_code']]);
                    if (isset($seen[$key])) $errors[]='Duplicate in uploaded CSV';
                    $seen[$key]=true;
                    $exists->execute([$m,$s,$v['observed_on'],$v['vendor_code']]);
                    if ($exists->fetchColumn()) $errors[]='Already exists in database';
                }
                $rows[]=['line'=>$line,'errors'=>$errors,'data'=>['market_id'=>$m,'fish_species_id'=>$s]+$v];
            }
            fclose($fp);
            if (!$rows) throw new RuntimeException('No data rows found.');
            $_SESSION['price_import_preview']=$rows;
        } elseif ($action === 'import') {
            $rows=$_SESSION['price_import_preview'] ?? null;
            if (!$rows) throw new RuntimeException('Preview a CSV file first.');
            $count=0;
            $pdo->beginTransaction();
            try {
                $check=$pdo->prepare('SELECT 1 FROM price_records WHERE market_id=? AND fish_species_id=? AND observed_on=? AND vendor_code=? LIMIT 1');
                $insert=$pdo->prepare('INSERT INTO price_records (market_id,fish_species_id,observed_on,price_per_kg,vendor_code,source_type,source_reference,collection_time,product_condition,remarks,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                foreach ($rows as $row) {
                    if ($row['errors']) continue;
                    $v=$row['data'];
                    $check->execute([$v['market_id'],$v['fish_species_id'],$v['observed_on'],$v['vendor_code']]);
                    if ($check->fetchColumn()) throw new RuntimeException('Records changed since preview. Please upload and preview again.');
                    $insert->execute([$v['market_id'],$v['fish_species_id'],$v['observed_on'],$v['price_per_kg'],$v['vendor_code'],$v['source_type'],$v['source_reference']?:null,$v['collection_time']?:null,$v['product_condition']?:null,$v['remarks']?:null,$_SESSION['user_id']]);
                    $count++;
                }
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            unset($_SESSION['price_import_preview']);
            $_SESSION['import_notice']="Imported $count valid records. Invalid rows were skipped.";
            header('Location: import_prices.php'); exit;
        } elseif ($action === 'cancel') { unset($_SESSION['price_import_preview']); header('Location: import_prices.php'); exit; }
        else throw new RuntimeException('Unknown action');
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $error=$e instanceof PDOException ? 'Database import error. Check server logs.' : $e->getMessage(); if ($e instanceof PDOException) error_log($e->getMessage()); }
}
$preview=$_SESSION['price_import_preview'] ?? [];
$valid=count(array_filter($preview,fn($r)=>!$r['errors']));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CSV Import | FishForecast</title><script src="https://cdn.tailwindcss.com"></script><script src="https://unpkg.com/lucide@latest"></script></head><body class="bg-slate-50 min-h-screen">
<?php require_once __DIR__.'/../includes/sidebar.php'; ?>
<div class="lg:ml-64 min-h-screen"><?php require_once __DIR__.'/../includes/header.php'; ?>
<main class="p-5 lg:p-8 space-y-6"><div><h2 class="text-2xl font-bold text-slate-800">Import Historical Fish Prices</h2><p class="text-slate-500">Preview and validate CSV observations before adding them to the research dataset.</p></div>
<?php if ($error): ?><div class="bg-red-50 text-red-700 p-4 rounded-xl"><?=esc($error)?></div><?php endif; ?>
<?php if ($notice): ?><div class="bg-green-50 text-green-700 p-4 rounded-xl"><?=esc($notice)?></div><?php endif; ?>
<section class="bg-white rounded-2xl shadow-sm p-6 space-y-4"><h3 class="font-semibold text-lg">Upload CSV</h3><p class="text-sm text-slate-600">Maximum 2 MB and 1,000 rows. Register fish species and markets first. CSV must use the exact headers in the template.</p><form method="post" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-center"><input type="hidden" name="csrf" value="<?=esc($_SESSION['import_csrf'])?>"><input type="hidden" name="action" value="preview"><input type="file" name="csv" accept=".csv,text/csv" required class="border rounded-lg p-2"><button class="bg-teal-700 text-white px-5 py-2 rounded-lg">Preview CSV</button></form><a class="text-teal-700 underline" href="../assets/templates/fish_prices_template.csv" download>Download CSV template</a><p class="text-xs text-slate-500">Source types: vendor_interview, market_record, government_record, other. Use a distinct vendor code per vendor; use MARKET_AVERAGE only for documented averages.</p></section>
<?php if ($preview): ?><section class="bg-white rounded-2xl shadow-sm p-6 space-y-4"><h3 class="font-semibold text-lg">Preview: <?=count($preview)?> rows, <?=$valid?> valid, <?=count($preview)-$valid?> rejected</h3><div class="overflow-x-auto"><table class="min-w-full text-sm text-left"><thead class="bg-slate-100"><tr><th class="p-3">CSV line</th><th class="p-3">Date</th><th class="p-3">Market</th><th class="p-3">Fish</th><th class="p-3">Price/kg</th><th class="p-3">Status / reason</th></tr></thead><tbody><?php foreach ($preview as $r): $v=$r['data'] ?? []; ?><tr class="border-b"><td class="p-3"><?=esc($r['line'])?></td><td class="p-3"><?=esc($v['observed_on']??'—')?></td><td class="p-3"><?=esc($v['market']??'—')?></td><td class="p-3"><?=esc($v['fish_species']??'—')?></td><td class="p-3"><?=esc($v['price_per_kg']??'—')?></td><td class="p-3 <?= $r['errors']?'text-red-700':'text-green-700' ?>"><?=esc($r['errors']?implode('; ',$r['errors']):'Valid')?></td></tr><?php endforeach; ?></tbody></table></div><div class="flex gap-3"><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['import_csrf'])?>"><input type="hidden" name="action" value="import"><button class="bg-teal-700 text-white px-5 py-2 rounded-lg" <?= $valid?'':'disabled' ?>>Import <?=$valid?> valid rows</button></form><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['import_csrf'])?>"><input type="hidden" name="action" value="cancel"><button class="bg-slate-200 px-5 py-2 rounded-lg">Cancel</button></form></div></section><?php endif; ?>
</main></div><script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('sidebarOverlay').classList.toggle('hidden')}lucide.createIcons();</script></body></html>

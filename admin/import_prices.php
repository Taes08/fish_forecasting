<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function normalizedName(string $value): string {
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return mb_strtolower($value, 'UTF-8');
}
function parseCsvDate(string $value): ?string {
    $value = trim($value);
    foreach (['!Y-m-d' => 'Y-m-d', '!d/m/Y' => 'd/m/Y'] as $input => $output) {
        $date = DateTimeImmutable::createFromFormat($input, $value);
        if ($date && $date->format($output) === $value) return $date->format('Y-m-d');
    }
    return null;
}
function loadNameMap(PDO $pdo, string $table, string $column): array {
    // These identifiers are fixed in the code, never supplied by users.
    $map = [];
    foreach ($pdo->query("SELECT id, `$column` AS label FROM `$table`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = normalizedName((string)$row['label']);
        if (!isset($map[$key])) $map[$key] = (int)$row['id'];
    }
    return $map;
}
function fishKey(string $name): string { return normalizedName($name); }
function findFishId(array $map, string $name): ?int {
    $key = fishKey($name);
    if (isset($map[$key])) return $map[$key];
    // Only strip a final parenthetical English/common-name translation.
    $short = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($name));
    $key = fishKey($short);
    return $map[$key] ?? null;
}
function getTableColumns(PDO $pdo, string $table): array {
    $result = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $result[$column['Field']] = $column;
    }
    return $result;
}

if (empty($_SESSION['import_csrf'])) $_SESSION['import_csrf'] = bin2hex(random_bytes(32));
$error = '';
$notice = $_SESSION['import_notice'] ?? '';
unset($_SESSION['import_notice']);
$columns = ['observed_on','market','fish_species','price_per_kg','vendor_code','source_type','source_reference','collection_time','product_condition','remarks'];
$types = ['vendor_interview','market_record','government_record','other'];
$preview = $_SESSION['price_import_preview'] ?? [];

try {
    $marketMap = loadNameMap($pdo, 'markets', 'name');
    $speciesMap = loadNameMap($pdo, 'fish_species', 'common_name');
    $marketCols = getTableColumns($pdo, 'markets');
    $fishCols = getTableColumns($pdo, 'fish_species');
    $priceCols = getTableColumns($pdo, 'price_records');
    // Restrict inactive markets. Existing inactive fish species are not automatically reactivated.
    if (isset($marketCols['is_active'])) {
        $marketMap = [];
        foreach ($pdo->query('SELECT id,name FROM markets WHERE is_active=1')->fetchAll(PDO::FETCH_ASSOC) as $market) {
            $marketMap[normalizedName((string)$market['name'])] = (int)$market['id'];
        }
    }
    $inactiveFishKeys = [];
    if (isset($fishCols['is_active'])) {
        foreach ($pdo->query('SELECT common_name FROM fish_species WHERE is_active=0')->fetchAll(PDO::FETCH_ASSOC) as $fish) {
            $inactiveFishKeys[fishKey((string)$fish['common_name'])] = true;
        }
    }
} catch (Throwable $e) {
    $error = 'Unable to read database tables: ' . $e->getMessage();
    $marketMap = $speciesMap = $inactiveFishKeys = [];
    $fishCols = $priceCols = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['import_csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Invalid request token');
    }
    try {
        if ($error !== '') throw new RuntimeException($error);
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'preview') {
            unset($_SESSION['price_import_preview']);
            if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Please select a valid CSV file.');
            if ($_FILES['csv']['size'] > 2 * 1024 * 1024) throw new RuntimeException('CSV must be 2 MB or smaller.');
            if (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') throw new RuntimeException('Only .csv files are accepted.');
            $fp = fopen($_FILES['csv']['tmp_name'], 'rb');
            if (!$fp) throw new RuntimeException('Unable to open CSV.');
            try {
                $header = fgetcsv($fp);
                if (!$header) throw new RuntimeException('CSV is empty.');
                $header = array_map(static fn($v) => trim((string)$v), $header);
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
                if ($header !== $columns) throw new RuntimeException('CSV headers must exactly match the template in the correct order.');
                $rows = [];
                $seen = [];
                $line = 1;
                $exists = $pdo->prepare('SELECT 1 FROM price_records WHERE market_id=? AND fish_species_id=? AND observed_on=? AND vendor_code=? LIMIT 1');
                foreach (range(1, 1001) as $_) {
                    $values = fgetcsv($fp);
                    if ($values === false) break;
                    $line++;
                    if (count($values) === 1 && trim((string)$values[0]) === '') continue;
                    if (count($rows) >= 1000) throw new RuntimeException('Maximum 1,000 data rows per upload.');
                    if (count($values) !== count($columns)) {
                        $rows[] = ['line'=>$line,'data'=>null,'errors'=>['Incorrect number of CSV columns'],'duplicate'=>false,'new_species'=>false];
                        continue;
                    }
                    $v = array_combine($columns, array_map(static fn($x) => trim((string)$x), $values));
                    $errors = [];
                    $m = $marketMap[normalizedName($v['market'])] ?? null;
                    if (!$m) $errors[] = 'Unknown or inactive market: ' . $v['market'];
                    $name = $v['fish_species'];
                    if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Fish species name is required (max 150 characters)';
                    $fishId = $name !== '' ? findFishId($speciesMap, $name) : null;
                    $shortName = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name);
                    if (isset($inactiveFishKeys[fishKey($name)]) || isset($inactiveFishKeys[fishKey($shortName)])) {
                        $errors[] = 'Fish species is inactive; activate it before importing';
                    }
                    $date = parseCsvDate($v['observed_on']);
                    if (!$date || $date > date('Y-m-d')) $errors[] = 'Invalid or future date';
                    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $v['price_per_kg']) || (float)$v['price_per_kg'] <= 0) $errors[] = 'Invalid positive price';
                    if ($v['vendor_code'] === '' || mb_strlen($v['vendor_code']) > 50) $errors[] = 'Vendor code required (max 50 characters)';
                    if (!in_array($v['source_type'], $types, true)) $errors[] = 'Invalid source type';
                    if (mb_strlen($v['source_reference']) > 255 || mb_strlen($v['product_condition']) > 100) $errors[] = 'Text field too long';
                    if ($v['collection_time'] !== '' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D', $v['collection_time'])) $errors[] = 'Invalid collection time';
                    $duplicate = false;
                    if ($m && $date && $v['vendor_code'] !== '' && $name !== '') {
                        $key = implode('|', [$m, $fishId ? 'id:'.$fishId : 'name:'.fishKey($name), $date, $v['vendor_code']]);
                        if (isset($seen[$key])) { $duplicate = true; $errors[] = 'Duplicate in uploaded CSV'; }
                        $seen[$key] = true;
                        if ($fishId) {
                            $exists->execute([$m,$fishId,$date,$v['vendor_code']]);
                            if ($exists->fetchColumn()) { $duplicate = true; $errors[] = 'Already exists in database'; }
                            $exists->closeCursor();
                        }
                    }
                    $v['observed_on'] = $date ?? $v['observed_on'];
                    $rows[] = ['line'=>$line,'data'=>['market_id'=>$m,'fish_species_id'=>$fishId] + $v,
                        'errors'=>$errors,'duplicate'=>$duplicate,'new_species'=>$fishId === null];
                }
                if (!$rows) throw new RuntimeException('No data rows found.');
                $_SESSION['price_import_preview'] = $rows;
                $preview = $rows;
            } finally { fclose($fp); }
        } elseif ($action === 'import') {
            $rows = $_SESSION['price_import_preview'] ?? [];
            if (!$rows) throw new RuntimeException('Preview a CSV file first.');
            // The table must allow insertion of a species with only common_name and optional is_active.
            foreach ($fishCols as $field => $column) {
                if (in_array($field, ['id','common_name','is_active'], true)) continue;
                if ($column['Null'] === 'NO' && $column['Default'] === null && stripos((string)$column['Extra'], 'auto_increment') === false) {
                    throw new RuntimeException("fish_species requires '$field'. Add a database default or update the importer to supply it.");
                }
            }
            $inserted = $speciesAdded = $duplicates = $invalid = 0;
            $pdo->beginTransaction();
            try {
                $speciesMap = loadNameMap($pdo, 'fish_species', 'common_name');
                $speciesInsertSql = isset($fishCols['is_active'])
                    ? 'INSERT INTO fish_species (common_name,is_active) VALUES (?,1)'
                    : 'INSERT INTO fish_species (common_name) VALUES (?)';
                $insertSpecies = $pdo->prepare($speciesInsertSql);
                $check = $pdo->prepare('SELECT 1 FROM price_records WHERE market_id=? AND fish_species_id=? AND observed_on=? AND vendor_code=? LIMIT 1');
                $insert = $pdo->prepare('INSERT INTO price_records (market_id,fish_species_id,observed_on,price_per_kg,vendor_code,source_type,source_reference,collection_time,product_condition,remarks,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                foreach ($rows as $row) {
                    if ($row['errors']) { if ($row['duplicate']) $duplicates++; else $invalid++; continue; }
                    $v = $row['data'];
                    $id = findFishId($speciesMap, $v['fish_species']);
                    if (!$id) {
                        // A unique index on fish_species.common_name is recommended for concurrent imports.
                        $insertSpecies->execute([$v['fish_species']]);
                        $id = (int)$pdo->lastInsertId();
                        $speciesMap[fishKey($v['fish_species'])] = $id;
                        $speciesAdded++;
                    }
                    $check->execute([$v['market_id'],$id,$v['observed_on'],$v['vendor_code']]);
                    $already = (bool)$check->fetchColumn();
                    $check->closeCursor();
                    if ($already) { $duplicates++; continue; }
                    $insert->execute([
                        $v['market_id'],$id,$v['observed_on'],$v['price_per_kg'],$v['vendor_code'],$v['source_type'],
                        $v['source_reference'] !== '' ? $v['source_reference'] : null,
                        $v['collection_time'] !== '' ? $v['collection_time'] : null,
                        $v['product_condition'] !== '' ? $v['product_condition'] : null,
                        $v['remarks'] !== '' ? $v['remarks'] : null,
                        $_SESSION['user_id'] ?? null
                    ]);
                    $inserted++;
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            unset($_SESSION['price_import_preview']);
            $_SESSION['import_notice'] = "Imported $inserted price records; registered $speciesAdded new fish species; skipped $duplicates duplicates and $invalid invalid rows.";
            header('Location: import_prices.php', true, 303);
            exit;
        } elseif ($action === 'cancel') {
            unset($_SESSION['price_import_preview']);
            header('Location: import_prices.php', true, 303);
            exit;
        } else throw new RuntimeException('Unknown action.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Fish price CSV import failed: ' . $e->getMessage());
        $error = $e->getMessage(); // For admin debugging; replace with generic message in production.
    }
}
$preview = $_SESSION['price_import_preview'] ?? [];
$valid = count(array_filter($preview, static fn($r) => empty($r['errors'])));
$newSpecies = [];
foreach ($preview as $r) if (empty($r['errors']) && $r['new_species'] && $r['data']) $newSpecies[fishKey($r['data']['fish_species'])] = true;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CSV Import | FishForecast</title>
<script src="https://cdn.tailwindcss.com"></script><script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 min-h-screen">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="lg:ml-64 min-h-screen">
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<main class="p-5 lg:p-8 space-y-6">
<div><h2 class="text-2xl font-bold text-slate-800">Import Historical Fish Prices</h2>
<p class="text-slate-500">Preview CSV records, automatically register new fish species, and skip duplicate prices.</p></div>
<?php if ($error): ?><div class="bg-red-50 text-red-700 p-4 rounded-xl"><?=esc($error)?></div><?php endif; ?>
<?php if ($notice): ?><div class="bg-green-50 text-green-700 p-4 rounded-xl"><?=esc($notice)?></div><?php endif; ?>
<section class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
<h3 class="font-semibold text-lg">Upload CSV</h3>
<p class="text-sm text-slate-600">Maximum 2 MB and 1,000 data rows. Markets must already be registered and active. Missing fish species are added automatically. Dates may be YYYY-MM-DD or DD/MM/YYYY.</p>
<form method="post" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-center">
<input type="hidden" name="csrf" value="<?=esc($_SESSION['import_csrf'])?>"><input type="hidden" name="action" value="preview">
<input type="file" name="csv" accept=".csv,text/csv" required class="border rounded-lg p-2">
<button class="bg-teal-700 text-white px-5 py-2 rounded-lg">Preview CSV</button>
</form>
<a class="text-teal-700 underline" href="../assets/templates/fish_prices_template.csv" download>Download CSV template</a>
<p class="text-xs text-slate-500">Source types: vendor_interview, market_record, government_record, other.</p>
</section>
<?php if ($preview): ?>
<section class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
<h3 class="font-semibold text-lg">Preview: <?=count($preview)?> rows, <?=$valid?> valid, <?=count($preview)-$valid?> skipped, <?=count($newSpecies)?> new fish species</h3>
<div class="overflow-x-auto"><table class="min-w-full text-sm text-left">
<thead class="bg-slate-100"><tr><th class="p-3">CSV line</th><th class="p-3">Date</th><th class="p-3">Market</th><th class="p-3">Fish species</th><th class="p-3">Price/kg</th><th class="p-3">Status / reason</th></tr></thead>
<tbody>
<?php foreach ($preview as $r): $v = $r['data'] ?? []; ?>
<tr class="border-b"><td class="p-3"><?=esc($r['line'])?></td><td class="p-3"><?=esc($v['observed_on'] ?? '—')?></td>
<td class="p-3"><?=esc($v['market'] ?? '—')?></td><td class="p-3"><?=esc($v['fish_species'] ?? '—')?></td>
<td class="p-3"><?=esc($v['price_per_kg'] ?? '—')?></td>
<td class="p-3 <?= $r['errors'] ? 'text-red-700' : 'text-green-700' ?>">
<?=esc($r['errors'] ? implode('; ', $r['errors']) : ($r['new_species'] ? 'Valid — new species' : 'Valid'))?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="flex flex-wrap gap-3">
<form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['import_csrf'])?>"><input type="hidden" name="action" value="import">
<button class="bg-teal-700 text-white px-5 py-2 rounded-lg disabled:opacity-50" <?=$valid ? '' : 'disabled'?>>Import <?=$valid?> valid rows</button></form>
<form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['import_csrf'])?>"><input type="hidden" name="action" value="cancel"><button class="bg-slate-200 px-5 py-2 rounded-lg">Cancel</button></form>
</div></section>
<?php endif; ?>
</main></div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('sidebarOverlay').classList.toggle('hidden')}lucide.createIcons();</script>
</body></html>


<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['price_csrf'])) {
    $_SESSION['price_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$notice = $_SESSION['price_notice'] ?? '';
unset($_SESSION['price_notice']);

$oldInput = null;

$sourceTypes = [
    'vendor_interview',
    'market_record',
    'government_record',
    'other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['price_csrf'], $token)) {
        http_response_code(403);
        $error = 'Invalid security token. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($action === 'delete') {
                if (!$id || $id < 1) {
                    throw new RuntimeException('Invalid record ID.');
                }

                $q = $pdo->prepare(
                    'DELETE FROM price_records WHERE id = ?'
                );

                $q->execute([$id]);

                if ($q->rowCount() === 0) {
                    throw new RuntimeException('The price record no longer exists.');
                }

                $_SESSION['price_notice'] = 'Price record deleted successfully.';

            } elseif ($action === 'add' || $action === 'edit') {

                $market = filter_var(
                    $_POST['market_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                $species = filter_var(
                    $_POST['fish_species_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                $date = trim($_POST['observed_on'] ?? '');
                $price = trim($_POST['price_per_kg'] ?? '');
                $vendor = trim($_POST['vendor_code'] ?? '');
                $source = $_POST['source_type'] ?? '';
                $reference = trim($_POST['source_reference'] ?? '');
                $time = trim($_POST['collection_time'] ?? '');
                $condition = trim($_POST['product_condition'] ?? '');
                $remarks = trim($_POST['remarks'] ?? '');

                $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

                if (
                    !$market ||
                    !$species ||
                    !$d ||
                    $d->format('Y-m-d') !== $date ||
                    $date > date('Y-m-d')
                ) {
                    throw new RuntimeException(
                        'Select a valid market, fish species, and observation date that is not in the future.'
                    );
                }

                if (
                    !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price) ||
                    (float)$price <= 0
                ) {
                    throw new RuntimeException(
                        'Price must be positive and have at most two decimal places.'
                    );
                }

                if ($vendor === '' || strlen($vendor) > 50) {
                    throw new RuntimeException(
                        'Vendor code is required and must not exceed 50 characters.'
                    );
                }

                if (!in_array($source, $sourceTypes, true)) {
                    throw new RuntimeException('Invalid source type.');
                }

                if (
                    mb_strlen($reference) > 255 ||
                    mb_strlen($condition) > 100
                ) {
                    throw new RuntimeException(
                        'Source reference or product condition is too long.'
                    );
                }

                if (
                    $time !== '' &&
                    !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)
                ) {
                    throw new RuntimeException('Invalid collection time.');
                }

                $values = [
                    $market,
                    $species,
                    $date,
                    $price,
                    $vendor,
                    $source,
                    $reference !== '' ? $reference : null,
                    $time !== '' ? $time : null,
                    $condition !== '' ? $condition : null,
                    $remarks !== '' ? $remarks : null
                ];

                if ($action === 'add') {
                    $q = $pdo->prepare("
                        INSERT INTO price_records
                        (
                            market_id,
                            fish_species_id,
                            observed_on,
                            price_per_kg,
                            vendor_code,
                            source_type,
                            source_reference,
                            collection_time,
                            product_condition,
                            remarks,
                            recorded_by
                        )
                        VALUES (?,?,?,?,?,?,?,?,?,?,?)
                    ");

                    $q->execute([
                        ...$values,
                        $_SESSION['user_id']
                    ]);

                    $_SESSION['price_notice'] = 'Price record added successfully.';

                } else {
                    if (!$id || $id < 1) {
                        throw new RuntimeException('Invalid record ID.');
                    }

                    $check = $pdo->prepare(
                        'SELECT id FROM price_records WHERE id = ?'
                    );
                    $check->execute([$id]);

                    if (!$check->fetchColumn()) {
                        throw new RuntimeException(
                            'The selected price record no longer exists.'
                        );
                    }

                    $q = $pdo->prepare("
                        UPDATE price_records
                        SET market_id = ?,
                            fish_species_id = ?,
                            observed_on = ?,
                            price_per_kg = ?,
                            vendor_code = ?,
                            source_type = ?,
                            source_reference = ?,
                            collection_time = ?,
                            product_condition = ?,
                            remarks = ?
                        WHERE id = ?
                    ");

                    $q->execute([
                        ...$values,
                        $id
                    ]);

                    $_SESSION['price_notice'] = 'Price record updated successfully.';
                }

            } else {
                throw new RuntimeException('Unknown action.');
            }

            header('Location: price_records.php');
            exit;

        } catch (PDOException $exception) {
            error_log('FishCast price record error: ' . $exception->getMessage());

            $error = $exception->getCode() === '23000'
                ? 'Duplicate observation or invalid market/species. Check the market, fish, date, and vendor code.'
                : 'Database error. Please check the server logs.';

        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        if ($action === 'add' || $action === 'edit') {
            $oldInput = [
                'action' => $action,
                'id' => $id ?: '',
                'market_id' => $_POST['market_id'] ?? '',
                'fish_species_id' => $_POST['fish_species_id'] ?? '',
                'observed_on' => $_POST['observed_on'] ?? '',
                'price_per_kg' => $_POST['price_per_kg'] ?? '',
                'vendor_code' => $_POST['vendor_code'] ?? '',
                'source_type' => $_POST['source_type'] ?? '',
                'source_reference' => $_POST['source_reference'] ?? '',
                'collection_time' => $_POST['collection_time'] ?? '',
                'product_condition' => $_POST['product_condition'] ?? '',
                'remarks' => $_POST['remarks'] ?? ''
            ];
        }
    }
}

$markets = $pdo->query("
    SELECT id, name
    FROM markets
    WHERE is_active = 1
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$speciesList = $pdo->query("
    SELECT id, common_name
    FROM fish_species
    WHERE is_active = 1
    ORDER BY common_name
")->fetchAll(PDO::FETCH_ASSOC);

$filterMarket = filter_var(
    $_GET['market'] ?? null,
    FILTER_VALIDATE_INT
);

$filterSpecies = filter_var(
    $_GET['species'] ?? null,
    FILTER_VALIDATE_INT
);

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
$params = [];

if ($filterMarket) {
    $where[] = 'p.market_id = ?';
    $params[] = $filterMarket;
}

if ($filterSpecies) {
    $where[] = 'p.fish_species_id = ?';
    $params[] = $filterSpecies;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = 'p.observed_on >= ?';
    $params[] = $from;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = 'p.observed_on <= ?';
    $params[] = $to;
}

$sql = "
    SELECT
        p.*,
        m.name AS market_name,
        f.common_name
    FROM price_records p
    JOIN markets m ON m.id = p.market_id
    JOIN fish_species f ON f.id = p.fish_species_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY p.observed_on DESC, p.id DESC';

if (($_GET['export'] ?? '') === 'csv') {
    $q = $pdo->prepare($sql);
    $q->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fish_price_records.csv"');

    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'id',
        'date',
        'market',
        'fish',
        'price_per_kg',
        'vendor_code',
        'source_type',
        'source_reference',
        'collection_time',
        'product_condition',
        'remarks'
    ]);

    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
        $values = [
            $r['id'],
            $r['observed_on'],
            $r['market_name'],
            $r['common_name'],
            $r['price_per_kg'],
            $r['vendor_code'],
            $r['source_type'],
            $r['source_reference'],
            $r['collection_time'],
            $r['product_condition'],
            $r['remarks']
        ];

        $safeValues = array_map(function($value) {
            $value = (string)($value ?? '');

            if (preg_match('/^\s*[=+\-@]/u', $value)) {
                return "'" . $value;
            }

            return $value;
        }, $values);

        fputcsv($out, $safeValues);
    }

    fclose($out);
    exit;
}

$sql .= ' LIMIT 200';

$q = $pdo->prepare($sql);
$q->execute($params);

$records = $q->fetchAll(PDO::FETCH_ASSOC);

$totalShown = count($records);
$averagePrice = $totalShown > 0
    ? array_sum(array_column($records, 'price_per_kg')) / $totalShown
    : 0;

$uniqueMarkets = count(array_unique(array_column($records, 'market_id')));
$uniqueSpecies = count(array_unique(array_column($records, 'fish_species_id')));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historical Price Records | FishCast</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ocean: '#0B3954',
                        marine: '#087E8B',
                        aqua: '#2EC4B6',
                        seamist: '#E8F7F6'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif']
                    }
                }
            }
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F5FAFB;
            color: #0B3954;
        }

        h1, h2, h3 {
            font-family: 'Outfit', sans-serif;
        }

        .content-card {
            background: #FFFFFF;
            border: 1px solid #E1ECEE;
            border-radius: 20px;
            box-shadow: 0 4px 18px rgba(11,57,84,.035);
        }

        .form-control {
            width: 100%;
            border: 1px solid #CBD5E1;
            border-radius: 12px;
            padding: 12px 15px;
            background: #FFFFFF;
            color: #0B3954;
            outline: none;
            transition: all .2s;
        }

        .form-control:focus {
            border-color: #087E8B;
            box-shadow: 0 0 0 4px rgba(8,126,139,.12);
        }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 20px;
            border-radius: 12px;
            background: #087E8B;
            color: white;
            font-weight: 700;
            transition: .2s;
        }

        .primary-btn:hover {
            background: #0B3954;
        }

        .primary-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .table-row:hover {
            background: #F2FAFA;
        }

        .modal-panel {
            animation: modalIn .2s ease-out;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .swal2-popup {
            border-radius: 20px !important;
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>

<body class="min-h-screen">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="lg:ml-64 min-h-screen">

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="p-5 lg:p-8 xl:p-10 space-y-7 max-w-[1800px] mx-auto">

        <!-- PAGE HEADER -->
        <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">

            <div>
                <p class="text-xs uppercase tracking-[.16em] font-bold text-marine flex items-center gap-2 mb-3">
                    <i data-lucide="database" class="w-4 h-4"></i>
                    FishCast / Data Management
                </p>

                <h1 class="font-display text-3xl lg:text-4xl font-extrabold text-ocean">
                    Historical Price Records
                </h1>

                <p class="text-slate-500 mt-2 text-sm lg:text-base">
                    Manage verified market observations for future fish price forecasting.
                </p>
            </div>

            <button
                type="button"
                onclick="openAdd()"
                class="primary-btn shrink-0 shadow-sm">

                <i data-lucide="plus" class="w-5 h-5"></i>
                Add Price Record
            </button>

        </section>

        <!-- STATISTICS -->
        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

            <div class="content-card p-5">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Displayed Records</p>
                        <h2 class="font-display text-3xl font-bold text-ocean mt-3">
                            <?= number_format($totalShown) ?>
                        </h2>
                    </div>

                    <div class="p-3 bg-seamist rounded-xl">
                        <i data-lucide="database" class="w-6 h-6 text-marine"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-4">Up to 200 matching records</p>
            </div>

            <div class="content-card p-5">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Average Displayed Price</p>
                        <h2 class="font-display text-3xl font-bold text-ocean mt-3">
                            ₱<?= number_format($averagePrice, 2) ?>
                        </h2>
                    </div>

                    <div class="p-3 bg-seamist rounded-xl">
                        <i data-lucide="chart-line" class="w-6 h-6 text-marine"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-4">Per kilogram, across displayed records</p>
            </div>

            <div class="content-card p-5">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Markets Represented</p>
                        <h2 class="font-display text-3xl font-bold text-ocean mt-3">
                            <?= number_format($uniqueMarkets) ?>
                        </h2>
                    </div>

                    <div class="p-3 bg-seamist rounded-xl">
                        <i data-lucide="store" class="w-6 h-6 text-marine"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-4">In the displayed observations</p>
            </div>

            <div class="content-card p-5">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <p class="text-sm text-slate-500">Fish Species Represented</p>
                        <h2 class="font-display text-3xl font-bold text-ocean mt-3">
                            <?= number_format($uniqueSpecies) ?>
                        </h2>
                    </div>

                    <div class="p-3 bg-seamist rounded-xl">
                        <i data-lucide="fish" class="w-6 h-6 text-marine"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mt-4">In the displayed observations</p>
            </div>

        </section>

        <!-- FILTERS -->
        <section class="content-card p-6">

            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-seamist rounded-xl">
                    <i data-lucide="sliders-horizontal" class="w-5 h-5 text-marine"></i>
                </div>

                <div>
                    <h2 class="font-display text-xl font-bold text-ocean">
                        Filter Price Records
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Narrow down observations by market, species, or date.
                    </p>
                </div>
            </div>

            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

                <div>
                    <label for="filterMarket" class="block text-sm font-semibold text-ocean mb-2">
                        Market
                    </label>

                    <select name="market" id="filterMarket" class="form-control">
                        <option value="">All Markets</option>

                        <?php foreach ($markets as $m): ?>
                            <option
                                value="<?= (int)$m['id'] ?>"
                                <?= $filterMarket == $m['id'] ? 'selected' : '' ?>>
                                <?= h($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filterSpecies" class="block text-sm font-semibold text-ocean mb-2">
                        Fish Species
                    </label>

                    <select name="species" id="filterSpecies" class="form-control">
                        <option value="">All Fish Species</option>

                        <?php foreach ($speciesList as $f): ?>
                            <option
                                value="<?= (int)$f['id'] ?>"
                                <?= $filterSpecies == $f['id'] ? 'selected' : '' ?>>
                                <?= h($f['common_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filterFrom" class="block text-sm font-semibold text-ocean mb-2">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from"
                        id="filterFrom"
                        value="<?= h($from) ?>"
                        class="form-control">
                </div>

                <div>
                    <label for="filterTo" class="block text-sm font-semibold text-ocean mb-2">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to"
                        id="filterTo"
                        value="<?= h($to) ?>"
                        class="form-control">
                </div>

                <div class="sm:col-span-2 xl:col-span-4 flex flex-wrap gap-3 pt-2">

                    <button type="submit" class="primary-btn text-sm">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        Apply Filters
                    </button>

                    <button
                        type="submit"
                        name="export"
                        value="csv"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-ocean text-white rounded-xl text-sm font-bold hover:bg-marine transition">

                        <i data-lucide="download" class="w-4 h-4"></i>
                        Export CSV
                    </button>

                    <a href="price_records.php"
                       class="inline-flex items-center gap-2 px-5 py-3 bg-slate-100 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-200 transition">

                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        Reset
                    </a>

                </div>

            </form>

        </section>

        <!-- RECORDS TABLE -->
        <section class="content-card overflow-hidden">

            <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100">

                <div>
                    <h2 class="font-display text-xl font-bold text-ocean">
                        Price Observation Directory
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Latest recorded observations matching your filters.
                    </p>
                </div>

                <span class="inline-flex items-center gap-2 px-3 py-2 bg-seamist text-marine rounded-xl text-xs font-bold w-fit">
                    <i data-lucide="list" class="w-4 h-4"></i>
                    <?= number_format($totalShown) ?> Displayed
                </span>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-seamist/60 text-ocean">
                        <tr>
                            <th class="px-5 py-4 font-bold whitespace-nowrap">Date</th>
                            <th class="px-5 py-4 font-bold whitespace-nowrap">Market</th>
                            <th class="px-5 py-4 font-bold whitespace-nowrap">Fish Species</th>
                            <th class="px-5 py-4 font-bold whitespace-nowrap">Price/kg</th>
                            <th class="px-5 py-4 font-bold whitespace-nowrap">Vendor</th>
                            <th class="px-5 py-4 font-bold whitespace-nowrap">Source</th>
                            <th class="px-5 py-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($records)): ?>

                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">

                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-seamist flex items-center justify-center mb-4">
                                            <i data-lucide="database" class="w-8 h-8 text-marine"></i>
                                        </div>

                                        <h3 class="font-display text-lg font-bold text-ocean">
                                            No Price Records Found
                                        </h3>

                                        <p class="text-sm text-slate-500 mt-2">
                                            Add a verified observation or adjust your filters.
                                        </p>
                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($records as $r): ?>

                                <tr class="table-row transition">

                                    <td class="px-5 py-4 whitespace-nowrap text-slate-600">
                                        <?= h($r['observed_on']) ?>
                                    </td>

                                    <td class="px-5 py-4 font-semibold text-ocean">
                                        <?= h($r['market_name']) ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="fish" class="w-4 h-4 text-marine shrink-0"></i>
                                            <span class="text-slate-700">
                                                <?= h($r['common_name']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1.5 rounded-lg bg-seamist text-marine font-bold">
                                            ₱<?= number_format((float)$r['price_per_kg'], 2) ?>
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <?= h($r['vendor_code']) ?>
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        <?= h(ucwords(str_replace('_', ' ', $r['source_type']))) ?>
                                    </td>

                                    <td class="px-5 py-4">

                                        <div class="flex items-center justify-end gap-2">

                                            <button
                                                type="button"
                                                data-record="<?= h(json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>"
                                                onclick="openEdit(JSON.parse(this.dataset.record))"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-seamist text-marine hover:bg-teal-100 text-xs font-bold transition">

                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                                Edit
                                            </button>

                                            <form
                                                method="POST"
                                                class="delete-form"
                                                data-record-label="<?= h($r['common_name'] . ' — ' . $r['observed_on']) ?>">

                                                <input type="hidden" name="csrf" value="<?= h($_SESSION['price_csrf']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-xs font-bold transition">

                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>

            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                <p class="text-xs text-slate-500">
                    Showing up to 200 recent matching records.
                    CSV export includes all matching records.
                </p>
            </div>

        </section>

    </main>

</div>

<!-- ADD / EDIT MODAL -->
<div
    id="modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#061F30]/60 backdrop-blur-sm p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modalTitle"
    onclick="if(event.target === this) closeModal()">

    <div class="modal-panel bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between gap-4 p-6 border-b border-slate-100">

            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-seamist flex items-center justify-center">
                    <i data-lucide="database" class="w-6 h-6 text-marine"></i>
                </div>

                <div>
                    <h3 id="modalTitle" class="font-display text-xl font-bold text-ocean">
                        Add Price Record
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Enter a verified fish price observation.
                    </p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeModal()"
                class="p-2 rounded-xl text-slate-500 hover:bg-slate-100"
                aria-label="Close dialog">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

        </div>

        <form id="priceForm" method="POST" class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

            <input type="hidden" name="csrf" value="<?= h($_SESSION['price_csrf']) ?>">
            <input type="hidden" name="action" id="action" value="add">
            <input type="hidden" name="id" id="recordId">

            <div>
                <label for="market_id" class="block text-sm font-semibold text-ocean mb-2">
                    Market <span class="text-red-500">*</span>
                </label>

                <select name="market_id" id="market_id" required class="form-control">
                    <option value="">Select Market</option>

                    <?php foreach ($markets as $m): ?>
                        <option value="<?= (int)$m['id'] ?>">
                            <?= h($m['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="fish_species_id" class="block text-sm font-semibold text-ocean mb-2">
                    Fish Species <span class="text-red-500">*</span>
                </label>

                <select name="fish_species_id" id="fish_species_id" required class="form-control">
                    <option value="">Select Fish Species</option>

                    <?php foreach ($speciesList as $f): ?>
                        <option value="<?= (int)$f['id'] ?>">
                            <?= h($f['common_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="observed_on" class="block text-sm font-semibold text-ocean mb-2">
                    Observation Date <span class="text-red-500">*</span>
                </label>

                <input
                    type="date"
                    name="observed_on"
                    id="observed_on"
                    max="<?= date('Y-m-d') ?>"
                    required
                    class="form-control">
            </div>

            <div>
                <label for="price_per_kg" class="block text-sm font-semibold text-ocean mb-2">
                    Price per Kilogram (PHP) <span class="text-red-500">*</span>
                </label>

                <input
                    type="number"
                    name="price_per_kg"
                    id="price_per_kg"
                    min="0.01"
                    max="99999999.99"
                    step="0.01"
                    required
                    placeholder="e.g. 180.00"
                    class="form-control">
            </div>

            <div>
                <label for="vendor_code" class="block text-sm font-semibold text-ocean mb-2">
                    Vendor Code <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="vendor_code"
                    id="vendor_code"
                    maxlength="50"
                    required
                    placeholder="e.g. V001"
                    class="form-control">
            </div>

            <div>
                <label for="source_type" class="block text-sm font-semibold text-ocean mb-2">
                    Source Type <span class="text-red-500">*</span>
                </label>

                <select name="source_type" id="source_type" required class="form-control">

                    <?php foreach ($sourceTypes as $source): ?>
                        <option value="<?= h($source) ?>">
                            <?= h(ucwords(str_replace('_', ' ', $source))) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label for="source_reference" class="block text-sm font-semibold text-ocean mb-2">
                    Source Reference
                </label>

                <input
                    type="text"
                    name="source_reference"
                    id="source_reference"
                    maxlength="255"
                    placeholder="Reference or document details"
                    class="form-control">
            </div>

            <div>
                <label for="collection_time" class="block text-sm font-semibold text-ocean mb-2">
                    Collection Time
                </label>

                <input
                    type="time"
                    name="collection_time"
                    id="collection_time"
                    class="form-control">
            </div>

            <div class="sm:col-span-2">
                <label for="product_condition" class="block text-sm font-semibold text-ocean mb-2">
                    Product Condition
                </label>

                <input
                    type="text"
                    name="product_condition"
                    id="product_condition"
                    maxlength="100"
                    placeholder="e.g. Fresh, whole"
                    class="form-control">
            </div>

            <div class="sm:col-span-2">
                <label for="remarks" class="block text-sm font-semibold text-ocean mb-2">
                    Remarks
                </label>

                <textarea
                    name="remarks"
                    id="remarks"
                    rows="3"
                    placeholder="Additional notes about the observation..."
                    class="form-control resize-y"></textarea>
            </div>

            <div class="sm:col-span-2 flex justify-end gap-3 pt-4 border-t border-slate-100">

                <button
                    type="button"
                    onclick="closeModal()"
                    class="px-5 py-3 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-semibold">
                    Cancel
                </button>

                <button
                    type="submit"
                    id="saveButton"
                    class="primary-btn text-sm">

                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span id="saveButtonText">Save Record</span>

                </button>

            </div>

        </form>

    </div>

</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !overlay) return;

    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

const modal = document.getElementById('modal');
const priceForm = document.getElementById('priceForm');

function showModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        document.getElementById('market_id').focus();
    }, 100);
}

function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function openAdd() {
    priceForm.reset();

    document.getElementById('action').value = 'add';
    document.getElementById('recordId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Price Record';
    document.getElementById('saveButtonText').textContent = 'Save Record';

    showModal();
}

function openEdit(record) {
    priceForm.reset();

    document.getElementById('action').value = 'edit';
    document.getElementById('recordId').value = record.id;

    const fields = [
        'market_id',
        'fish_species_id',
        'observed_on',
        'price_per_kg',
        'vendor_code',
        'source_type',
        'source_reference',
        'collection_time',
        'product_condition',
        'remarks'
    ];

    fields.forEach(function(key) {
        const element = document.getElementById(key);

        if (element) {
            element.value = record[key] ?? '';
        }
    });

    if (record.collection_time) {
        document.getElementById('collection_time').value =
            String(record.collection_time).substring(0, 5);
    }

    document.getElementById('modalTitle').textContent = 'Edit Price Record';
    document.getElementById('saveButtonText').textContent = 'Update Record';

    showModal();
}

document.querySelectorAll('.delete-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const recordLabel = form.dataset.recordLabel || 'this price record';

        if (typeof Swal === 'undefined') {
            if (confirm('Permanently delete ' + recordLabel + '?')) {
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Delete Price Record?',
            text: 'You are about to permanently delete ' + recordLabel + '. This action cannot be undone.',
            icon: 'warning',
            iconColor: '#DC2626',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#64748B',
            reverseButtons: true,
            background: '#FFFFFF',
            color: '#0B3954'
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

priceForm.addEventListener('submit', function(event) {
    if (!priceForm.checkValidity()) {
        return;
    }

    const vendor = document.getElementById('vendor_code').value.trim();
    const price = Number(document.getElementById('price_per_kg').value);

    if (!vendor || !Number.isFinite(price) || price <= 0) {
        event.preventDefault();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Invalid Price Record',
                text: 'Please provide a valid positive price and vendor code.',
                icon: 'warning',
                confirmButtonColor: '#087E8B'
            });
        } else {
            alert('Please provide a valid price and vendor code.');
        }

        return;
    }

    document.getElementById('saveButton').disabled = true;
    document.getElementById('saveButtonText').textContent = 'Saving...';
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeModal();
    }
});

const successMessage = <?= json_encode(
    $notice,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

const errorMessage = <?= json_encode(
    $error,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

const oldInput = <?= json_encode(
    $oldInput,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    if (successMessage) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Success!',
                text: successMessage,
                icon: 'success',
                iconColor: '#087E8B',
                confirmButtonColor: '#087E8B',
                timer: 2500,
                timerProgressBar: true
            });
        } else {
            alert(successMessage);
        }
    }

    if (errorMessage) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Unable to Complete Action',
                text: errorMessage,
                icon: 'error',
                confirmButtonColor: '#087E8B'
            });
        } else {
            alert(errorMessage);
        }

        if (oldInput && (oldInput.action === 'add' || oldInput.action === 'edit')) {
            document.getElementById('action').value = oldInput.action;
            document.getElementById('recordId').value = oldInput.id || '';

            [
                'market_id',
                'fish_species_id',
                'observed_on',
                'price_per_kg',
                'vendor_code',
                'source_type',
                'source_reference',
                'collection_time',
                'product_condition',
                'remarks'
            ].forEach(function(key) {
                const element = document.getElementById(key);

                if (element) {
                    element.value = oldInput[key] ?? '';
                }
            });

            document.getElementById('modalTitle').textContent =
                oldInput.action === 'edit'
                    ? 'Edit Price Record'
                    : 'Add Price Record';

            document.getElementById('saveButtonText').textContent =
                oldInput.action === 'edit'
                    ? 'Update Record'
                    : 'Save Record';

            showModal();
        }
    }
});
</script>

</body>
</html>

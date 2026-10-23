
<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';

function dashboardE($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function countRecords($pdo, $table) {
    $allowed = [
        'fish_species',
        'markets',
        'price_records',
        'forecasts'
    ];

    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid table');
    }

    return (int)$pdo->query(
        "SELECT COUNT(*) FROM `$table`"
    )->fetchColumn();
}

$totalSpecies = countRecords($pdo, 'fish_species');
$totalMarkets = countRecords($pdo, 'markets');
$totalPrices = countRecords($pdo, 'price_records');
$totalForecasts = countRecords($pdo, 'forecasts');

$stmt = $pdo->query("
    SELECT
        p.observed_on,
        p.price_per_kg,
        f.common_name,
        m.name AS market_name
    FROM price_records p
    INNER JOIN fish_species f
        ON p.fish_species_id = f.id
    INNER JOIN markets m
        ON p.market_id = m.id
    ORDER BY p.observed_on DESC, p.id DESC
    LIMIT 8
");

$recentPrices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        observed_on,
        ROUND(AVG(price_per_kg), 2) AS avg_price
    FROM price_records
    GROUP BY observed_on
    ORDER BY observed_on DESC
    LIMIT 30
");

$chartData = array_reverse(
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);

$chartLabels = array_column($chartData, 'observed_on');

$chartPrices = array_map(
    'floatval',
    array_column($chartData, 'avg_price')
);

$cards = [
    [
        'title' => 'Fish Species',
        'value' => $totalSpecies,
        'icon' => 'fish',
        'description' => 'Registered species'
    ],
    [
        'title' => 'Markets',
        'value' => $totalMarkets,
        'icon' => 'store',
        'description' => 'Registered markets'
    ],
    [
        'title' => 'Price Records',
        'value' => $totalPrices,
        'icon' => 'database',
        'description' => 'Historical observations'
    ],
    [
        'title' => 'Forecasts',
        'value' => $totalForecasts,
        'icon' => 'chart-no-axes-combined',
        'description' => 'Saved forecasts'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard | FishCast</title>

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F6F9FA;
            color: #0B3954;
        }

        h1, h2, h3 {
            font-family: 'Outfit', sans-serif;
        }

        .surface {
            background: #FFFFFF;
            border: 1px solid #E4EBEF;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(11,57,84,.025);
        }

        .action-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 17px;
            border-radius: 10px;
            background: #087E8B;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 700;
            transition: background .2s;
        }

        .action-primary:hover {
            background: #0B3954;
        }

        .action-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 17px;
            border-radius: 10px;
            background: #FFFFFF;
            border: 1px solid #D7E3E8;
            color: #0B3954;
            font-size: 13px;
            font-weight: 700;
            transition: all .2s;
        }

        .action-secondary:hover {
            border-color: #087E8B;
            color: #087E8B;
        }

        .records-row:hover {
            background: #F7FBFB;
        }

        .swal2-popup {
            border-radius: 16px !important;
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>

<body class="min-h-screen">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="lg:ml-64 min-h-screen">

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-[1700px] mx-auto
                 px-5 py-7 lg:px-8 lg:py-9 space-y-7">

        <!-- PAGE HEADING -->
        <section class="flex flex-col md:flex-row
                        md:items-center md:justify-between
                        gap-5">

            <div>
                <h1 class="font-display text-3xl
                           font-bold tracking-tight text-ocean">
                    Dashboard
                </h1>

                <p class="text-sm text-slate-500 mt-2">
                    Monitor fish price data, markets, and historical trends.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">

                <a href="price_records.php"
                   class="action-primary">

                    <i data-lucide="plus"
                       class="w-4 h-4"></i>

                    Add Price Record
                </a>

                <a href="import_prices.php"
                   class="action-secondary">

                    <i data-lucide="upload"
                       class="w-4 h-4"></i>

                    Import Dataset
                </a>

            </div>
        </section>

        <!-- SUMMARY CARDS -->
        <section class="grid grid-cols-1 sm:grid-cols-2
                        xl:grid-cols-4 gap-4">

            <?php foreach ($cards as $card): ?>

                <div class="surface p-5">

                    <div class="flex items-start
                                justify-between gap-4">

                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                <?= dashboardE($card['title']) ?>
                            </p>

                            <h2 class="font-display text-3xl
                                       font-bold text-ocean mt-3">
                                <?= number_format($card['value']) ?>
                            </h2>
                        </div>

                        <div class="w-11 h-11 rounded-xl
                                    bg-seamist flex items-center
                                    justify-center shrink-0">

                            <i data-lucide="<?= dashboardE($card['icon']) ?>"
                               class="w-5 h-5 text-marine"></i>

                        </div>

                    </div>

                    <p class="text-xs text-slate-400 mt-4">
                        <?= dashboardE($card['description']) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </section>

        <!-- HISTORICAL PRICE CHART -->
        <section class="surface p-5 lg:p-7">

            <div class="flex flex-col sm:flex-row
                        sm:items-start sm:justify-between
                        gap-4 mb-7">

                <div>
                    <h2 class="font-display text-xl
                               font-bold text-ocean">
                        Historical Price Trends
                    </h2>

                    <p class="text-sm text-slate-500 mt-2">
                        Daily average fish prices based on recorded observations.
                    </p>
                </div>

                <div class="inline-flex items-center
                            gap-2 px-3 py-2
                            bg-seamist rounded-lg
                            text-xs font-semibold
                            text-marine w-fit">

                    <i data-lucide="calendar-days"
                       class="w-4 h-4"></i>

                    Last 30 Recorded Dates
                </div>

            </div>

            <?php if (empty($chartData)): ?>

                <div class="h-72 flex flex-col
                            items-center justify-center
                            text-center">

                    <div class="w-14 h-14 rounded-2xl
                                bg-seamist flex items-center
                                justify-center mb-4">

                        <i data-lucide="chart-line"
                           class="w-7 h-7 text-marine"></i>
                    </div>

                    <h3 class="text-lg font-bold text-ocean">
                        No Historical Data Available
                    </h3>

                    <p class="text-sm text-slate-500 mt-2">
                        Add fish price observations to display trends.
                    </p>

                    <a href="price_records.php"
                       class="mt-4 text-sm font-semibold
                              text-marine hover:text-ocean">
                        Manage Price Records
                    </a>

                </div>

            <?php else: ?>

                <div class="relative h-72 sm:h-80 lg:h-96">
                    <canvas id="priceChart"></canvas>
                </div>

                <div class="flex items-start gap-2
                            mt-6 pt-4 border-t
                            border-slate-100">

                    <i data-lucide="info"
                       class="w-4 h-4 text-slate-400
                              shrink-0 mt-0.5"></i>

                    <p class="text-xs text-slate-500 leading-5">
                        Values represent daily averages across recorded
                        markets and fish species. They are historical
                        observations, not price forecasts.
                    </p>

                </div>

            <?php endif; ?>

        </section>

        <!-- RECENT PRICE RECORDS -->
        <section class="surface overflow-hidden">

            <div class="px-5 py-5 lg:px-7
                        flex flex-col sm:flex-row
                        sm:items-center sm:justify-between
                        gap-4">

                <div>
                    <h2 class="font-display text-xl
                               font-bold text-ocean">
                        Recent Price Records
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Latest fish price observations entered into the system.
                    </p>
                </div>

                <a href="price_records.php"
                   class="inline-flex items-center gap-2
                          text-sm font-semibold text-marine
                          hover:text-ocean transition">

                    View All Records

                    <i data-lucide="arrow-up-right"
                       class="w-4 h-4"></i>
                </a>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-[#F5FAFA]
                                  border-y border-[#E4EBEF]">

                        <tr class="text-xs uppercase
                                   tracking-wide text-slate-500">

                            <th class="px-5 lg:px-7 py-4 font-semibold">
                                Date
                            </th>

                            <th class="px-5 py-4 font-semibold">
                                Fish Species
                            </th>

                            <th class="px-5 py-4 font-semibold">
                                Market
                            </th>

                            <th class="px-5 lg:px-7 py-4
                                       font-semibold text-right">
                                Price / kg
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($recentPrices)): ?>

                            <tr>
                                <td colspan="4"
                                    class="px-6 py-14 text-center">

                                    <i data-lucide="inbox"
                                       class="w-9 h-9 text-slate-300 mx-auto mb-3"></i>

                                    <p class="text-sm font-semibold text-ocean">
                                        No price records found
                                    </p>

                                    <p class="text-xs text-slate-500 mt-2">
                                        New observations will appear here.
                                    </p>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($recentPrices as $record): ?>

                                <tr class="records-row transition">

                                    <td class="px-5 lg:px-7 py-4
                                               whitespace-nowrap
                                               text-slate-500">

                                        <?= dashboardE($record['observed_on']) ?>

                                    </td>

                                    <td class="px-5 py-4
                                               font-semibold text-ocean">

                                        <?= dashboardE($record['common_name']) ?>

                                    </td>

                                    <td class="px-5 py-4 text-slate-600">

                                        <?= dashboardE($record['market_name']) ?>

                                    </td>

                                    <td class="px-5 lg:px-7 py-4
                                               text-right whitespace-nowrap">

                                        <span class="font-bold text-marine">
                                            ₱<?= number_format(
                                                (float)$record['price_per_kg'],
                                                2
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="px-5 lg:px-7 py-4
                        bg-slate-50/60 border-t
                        border-slate-100">

                <p class="text-xs text-slate-400">
                    Showing up to 8 recent observations.
                </p>

            </div>

        </section>

    </main>

</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !overlay) {
        return;
    }

    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

const chartLabels = <?= json_encode(
    $chartLabels,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

const chartPrices = <?= json_encode($chartPrices) ?>;

const canvas = document.getElementById('priceChart');

if (canvas && typeof Chart !== 'undefined') {
    new Chart(canvas, {
        type: 'line',

        data: {
            labels: chartLabels,

            datasets: [{
                label: 'Average Recorded Price (₱/kg)',
                data: chartPrices,
                borderColor: '#087E8B',
                backgroundColor: 'rgba(46,196,182,0.10)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#087E8B',
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                intersect: false,
                mode: 'index'
            },

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    backgroundColor: '#0B3954',
                    titleColor: '#FFFFFF',
                    bodyColor: '#FFFFFF',
                    padding: 12,
                    cornerRadius: 8,

                    callbacks: {
                        label: function(context) {
                            return 'Average: ₱' +
                                Number(context.parsed.y).toLocaleString(
                                    'en-PH',
                                    {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    }
                                ) + '/kg';
                        }
                    }
                }
            },

            scales: {
                x: {
                    grid: {
                        display: false
                    },

                    ticks: {
                        color: '#64748B',
                        maxTicksLimit: 9,
                        maxRotation: 0
                    }
                },

                y: {
                    grid: {
                        color: '#EDF2F4'
                    },

                    ticks: {
                        color: '#64748B',

                        callback: function(value) {
                            return '₱' + value;
                        }
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>

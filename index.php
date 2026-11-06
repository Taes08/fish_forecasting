
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);

$accessUrl = $isLoggedIn
    ? 'admin/dashboard.php'
    : 'auth/login.php';

$accessLabel = $isLoggedIn ? 'Dashboard' : 'Sign In';
require_once __DIR__ . '/includes/public_analytics.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FishCast: Machine Learning-Based Fish Price Forecasting System with Decision Support for selected markets in Madridejos, Cebu.">

    <title>FishCast | Fish Price Forecasting & Decision Support</title>

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --ocean: #0B3954;
            --marine: #087E8B;
            --aqua: #2EC4B6;
            --seamist: #E8F7F6;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--ocean);
            background: #ffffff;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }

        .hero-bg {
            background:
                radial-gradient(circle at 90% 15%, rgba(46,196,182,.22), transparent 32%),
                radial-gradient(circle at 10% 90%, rgba(8,126,139,.10), transparent 32%),
                linear-gradient(135deg, #f4fcfc 0%, #e8f7f6 55%, #ffffff 100%);
        }

        .glass-header {
            background: rgba(255,255,255,.94);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid #dceceb;
        }

        .nav-link {
            color: #476579;
            transition: color .2s ease;
        }

        .nav-link:hover {
            color: var(--marine);
        }

        .primary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--marine);
            color: white;
            border-radius: 12px;
            padding: 14px 24px;
            font-weight: 700;
            transition: all .2s ease;
            cursor: pointer;
        }

        .primary-button:hover {
            background: var(--ocean);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(8,126,139,.17);
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid #a5d9d7;
            background: white;
            color: var(--ocean);
            border-radius: 12px;
            padding: 14px 24px;
            font-weight: 700;
            transition: all .2s ease;
        }

        .secondary-button:hover {
            border-color: var(--marine);
            color: var(--marine);
            transform: translateY(-2px);
        }

        .feature-card {
            background: white;
            border: 1px solid #e1ecee;
            border-radius: 20px;
            padding: 28px;
            transition: all .25s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #7ad5ce;
            box-shadow: 0 20px 40px rgba(11,57,84,.08);
        }

        .feature-icon {
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            background: var(--seamist);
            color: var(--marine);
            margin-bottom: 20px;
        }

        .section-label {
            color: var(--marine);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .chart-grid {
            background-image: linear-gradient(to bottom, #e8f0f1 1px, transparent 1px);
            background-size: 100% 25%;
        }

        .step-number {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            background: var(--marine);
        }

        .mobile-menu {
            display: none;
        }

        .mobile-menu.open {
            display: flex;
        }

        .swal2-popup {
            border-radius: 20px !important;
            font-family: 'Inter', sans-serif !important;
        }

        @media (max-width: 640px) {
            .hero-title {
                font-size: 42px !important;
                line-height: 1.12 !important;
            }
        }
    </style>
</head>

<body>

<header class="glass-header sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="h-20 flex items-center justify-between">

            <a href="index.php" class="flex items-center gap-3">
                <div class="w-12 h-12 bg-ocean rounded-2xl flex items-center justify-center shadow-sm">
                    <i data-lucide="fish" class="w-7 h-7 text-aqua"></i>
                </div>

                <div>
                    <div class="font-display font-extrabold text-2xl text-ocean leading-tight">
                        Fish<span class="text-marine">Cast</span>
                    </div>
                    <div class="text-[10px] uppercase tracking-[.18em] text-slate-500 font-bold">
                        Fish Price Intelligence
                    </div>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-9 text-sm font-semibold">
                <a href="#home" class="nav-link">Home</a>
                <a href="#market-analytics" class="nav-link">Market Analytics</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#process" class="nav-link">How It Works</a>
                <a href="#about" class="nav-link">About</a>
            </nav>

            <button type="button"
                    onclick="confirmAccess()"
                    class="primary-button hidden md:inline-flex text-sm">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <?php echo htmlspecialchars($accessLabel, ENT_QUOTES, 'UTF-8'); ?>
            </button>

            <button type="button"
                    id="menuToggle"
                    class="md:hidden p-2 rounded-xl border border-slate-200 text-ocean"
                    aria-label="Toggle navigation"
                    aria-expanded="false">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>

        </div>

        <div id="mobileMenu" class="mobile-menu md:hidden flex-col gap-5 pb-6 text-sm font-semibold">
            <a href="#home" class="nav-link">Home</a>
            <a href="#market-analytics" class="nav-link">Market Analytics</a>
                <a href="#features" class="nav-link">Features</a>
            <a href="#process" class="nav-link">How It Works</a>
            <a href="#about" class="nav-link">About</a>

            <button type="button"
                    onclick="confirmAccess()"
                    class="primary-button w-full">
                <?php echo htmlspecialchars($accessLabel, ENT_QUOTES, 'UTF-8'); ?>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</header>

<main>

    <section id="home" class="hero-bg overflow-hidden">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-20 lg:py-28">

            <div class="grid lg:grid-cols-2 gap-14 items-center">

                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full border border-teal-200 bg-white/80 text-marine text-xs font-bold mb-8">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        Research Project · Madridejos, Cebu
                    </div>

                    <h1 class="hero-title font-display text-5xl md:text-6xl xl:text-7xl font-extrabold text-ocean leading-[1.08] tracking-tight mb-7">
                        Smarter Fish Prices.
                        <span class="text-marine">Better Market Decisions.</span>
                    </h1>

                    <p class="text-slate-600 text-lg leading-8 max-w-xl mb-9">
                        Organize historical fish prices, explore market trends,
                        and support the development of machine learning-based
                        forecasting and transparent decision support.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="button"
                                onclick="confirmAccess()"
                                class="primary-button">
                            <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
                            Access FishCast
                        </button>

                        <a href="#features" class="secondary-button">
                            Explore Features
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </a>
                    </div>

                    <div class="flex items-start gap-3 mt-9 text-sm text-slate-600">
                        <i data-lucide="shield-check" class="w-5 h-5 text-marine shrink-0"></i>
                        <span>
                            Built around organized data collection, traceable sources,
                            and responsible research practices.
                        </span>
                    </div>
                </div>

                <div class="relative">

                    <div class="absolute -top-8 -right-8 w-40 h-40 bg-aqua/20 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-8 -left-8 w-44 h-44 bg-marine/10 rounded-full blur-3xl"></div>

                    <div class="relative bg-white rounded-3xl border border-teal-100 shadow-2xl shadow-ocean/10 p-6 sm:p-8">

                        <div class="flex items-start justify-between gap-4 mb-7">
                            <div>
                                <p class="section-label mb-2">Market Intelligence</p>
                                <h2 class="font-display text-2xl font-bold text-ocean">
                                    Price Trend Preview
                                </h2>
                                <p class="text-sm text-slate-500 mt-1">
                                    Illustrative visualization
                                </p>
                            </div>

                            <div class="w-12 h-12 rounded-2xl bg-seamist flex items-center justify-center">
                                <i data-lucide="chart-no-axes-combined" class="w-6 h-6 text-marine"></i>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <span class="text-sm font-bold text-ocean">Observed Price Trends (Last 90 Days)</span>
                                <span class="px-3 py-1 rounded-full bg-teal-50 text-marine text-xs font-bold">MySQL Live Data</span>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-3 mb-4">
                                <select id="heroFish" aria-label="Select fish species" class="rounded-xl border border-slate-200 p-3 bg-white text-sm">
                                    <?php foreach ($analytics['speciesOptions'] as $fish): ?>
                                        <option value="<?= (int)$fish['id'] ?>"><?= htmlspecialchars($fish['common_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="heroMarket" aria-label="Select market" class="rounded-xl border border-slate-200 p-3 bg-white text-sm">
                                    <?php foreach ($analytics['marketOptions'] as $market): ?>
                                        <option value="<?= (int)$market['id'] ?>"><?= htmlspecialchars($market['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="relative h-64"><canvas id="heroTrendChart" aria-label="Observed fish prices" role="img"></canvas></div>
                            <p id="trendEmpty" class="hidden text-sm text-slate-500 mt-3">No price observations for this selection in the last 90 days.</p>
                            <p class="text-xs text-slate-500 mt-4">Daily average of recorded observations, not machine learning forecasts.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-5">

                            <div class="rounded-2xl border border-slate-100 p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <i data-lucide="database" class="w-5 h-5 text-marine"></i>
                                    <span class="text-xs font-bold text-slate-500">DATA</span>
                                </div>
                                <p class="font-display text-lg font-bold text-ocean">
                                    Price Records
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Historical observations
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-100 p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <i data-lucide="brain-circuit" class="w-5 h-5 text-marine"></i>
                                    <span class="text-xs font-bold text-slate-500">PLANNED</span>
                                </div>
                                <p class="font-display text-lg font-bold text-ocean">
                                    ML Forecasts
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Future capability
                                </p>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="market-analytics" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <p class="section-label mb-3">Live Market Monitoring</p>
            <h2 class="font-display text-3xl md:text-4xl font-bold mb-3">Database-Connected Market Analytics</h2>
            <p class="text-slate-600 mb-8">Aggregated historical records from participating markets. These are observed prices, not predictions.</p>
            <?php if ($analyticsError): ?>
                <p class="rounded-xl bg-amber-50 text-amber-900 p-4 mb-6" role="alert"><?= htmlspecialchars($analyticsError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <?php foreach ([
                    ['Fish Species',number_format($analytics['species'])],
                    ['Active Markets',number_format($analytics['markets'])],
                    ['Price Observations',number_format($analytics['records'])],
                    ['Overall Mean Price',$analytics['average'] === null ? 'No data' : '₱'.number_format($analytics['average'],2).'/kg']
                ] as $metric): ?>
                    <div class="rounded-2xl border border-teal-100 bg-seamist p-5">
                        <p class="text-sm text-slate-600 mb-2"><?= htmlspecialchars($metric[0], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="font-display text-2xl font-bold text-ocean"><?= htmlspecialchars($metric[1], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="grid lg:grid-cols-2 gap-6 mb-8">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h3 class="font-display text-xl font-bold mb-4">Average Price by Fish (₱/kg)</h3>
                    <div class="h-72"><canvas id="speciesChart"></canvas></div>
                </div>
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h3 class="font-display text-xl font-bold mb-4">Price Records by Market</h3>
                    <div class="h-72"><canvas id="marketChart"></canvas></div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-6 overflow-x-auto">
                <h3 class="font-display text-xl font-bold mb-4">Recent Daily Average Prices</h3>
                <table class="w-full text-sm text-left min-w-[560px]">
                    <thead class="bg-seamist text-ocean"><tr><th class="p-3">Date</th><th class="p-3">Fish</th><th class="p-3">Market</th><th class="p-3">Average Price</th><th class="p-3">Observations</th></tr></thead>
                    <tbody>
                    <?php foreach ($analytics['recent'] as $row): ?>
                        <tr class="border-b border-slate-100">
                            <td class="p-3"><?= htmlspecialchars($row['observed_on'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['fish'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['market'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="p-3">₱<?= number_format((float)$row['avg_price'],2) ?></td>
                            <td class="p-3"><?= (int)$row['samples'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$analytics['recent']): ?><tr><td colspan="5" class="p-5 text-center text-slate-500">No historical price observations available yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">

            <div class="max-w-3xl mx-auto text-center mb-14">
                <p class="section-label mb-4">System Capabilities</p>

                <h2 class="font-display text-4xl md:text-5xl font-bold text-ocean mb-5">
                    One platform for fish price intelligence
                </h2>

                <p class="text-slate-600 text-lg leading-8">
                    A structured foundation for monitoring fish prices,
                    analyzing trends, and developing reliable forecasts.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="database" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-3 text-ocean">
                        Historical Price Records
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Store fish price observations with dates, markets,
                        fish species, vendor identifiers, and source references.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="file-up" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-3 text-ocean">
                        Dataset Import
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Upload CSV datasets and review validation results
                        before importing observations into the database.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="chart-line" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-3 text-ocean">
                        Trend Visualization
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Planned charts to illustrate historical changes
                        in fish prices across selected markets.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="brain-circuit" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-3 text-ocean">
                        Machine Learning Forecasting
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Planned models to estimate future fish prices
                        using verified historical observations.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="target" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-3 text-ocean">
                        Model Evaluation
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Compare forecasting models against a baseline
                        using MAE and RMSE on chronological test data.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i data-lucide="lightbulb" class="w-7 h-7"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-3 text-ocean">
                        Decision Support
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Planned transparent, rule-based explanations
                        to help users interpret price forecasts.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section id="process" class="py-24 bg-seamist">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">

            <div class="text-center mb-16">
                <p class="section-label mb-4">Our Process</p>
                <h2 class="font-display text-4xl md:text-5xl font-bold text-ocean">
                    From market data to informed decisions
                </h2>
            </div>

            <div class="grid md:grid-cols-4 gap-6">

                <div class="bg-white rounded-2xl p-7 border border-teal-100">
                    <div class="step-number mb-6">01</div>
                    <h3 class="font-display text-xl font-bold text-ocean mb-3">
                        Collect
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Gather fish price observations from selected markets
                        and documented sources.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-7 border border-teal-100">
                    <div class="step-number mb-6">02</div>
                    <h3 class="font-display text-xl font-bold text-ocean mb-3">
                        Validate
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Check prices, dates, duplicates, and source information
                        to prepare a reliable dataset.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-7 border border-teal-100">
                    <div class="step-number mb-6">03</div>
                    <h3 class="font-display text-xl font-bold text-ocean mb-3">
                        Forecast
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Train and evaluate machine learning models
                        to estimate future fish prices.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-7 border border-teal-100">
                    <div class="step-number mb-6">04</div>
                    <h3 class="font-display text-xl font-bold text-ocean mb-3">
                        Interpret
                    </h3>
                    <p class="text-slate-600 text-sm leading-7">
                        Present understandable price trends, forecasts,
                        and decision-support insights.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section id="about" class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-5 sm:px-8 text-center">

            <div class="w-20 h-20 rounded-3xl bg-seamist flex items-center justify-center mx-auto mb-7">
                <i data-lucide="graduation-cap" class="w-10 h-10 text-marine"></i>
            </div>

            <p class="section-label mb-4">About FishCast</p>

            <h2 class="font-display text-3xl md:text-5xl font-bold text-ocean leading-tight mb-7">
                Machine Learning-Based Fish Price Forecasting System
                with Decision Support
            </h2>

            <p class="text-slate-600 text-lg leading-9 max-w-3xl mx-auto">
                FishCast is a master's capstone research project focused on
                selected fresh fish products and public markets in Madridejos, Cebu.
                It aims to support systematic price data management,
                forecasting model evaluation, and transparent decision support.
            </p>

            <div class="mt-10">
                <button type="button" onclick="confirmAccess()" class="primary-button">
                    <i data-lucide="arrow-right-circle" class="w-5 h-5"></i>
                    Enter FishCast
                </button>
            </div>

        </div>
    </section>

    <section class="py-16 bg-ocean">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 text-center">

            <div class="inline-flex items-center gap-2 text-aqua text-sm font-bold mb-4">
                <i data-lucide="fish" class="w-5 h-5"></i>
                FISHCAST
            </div>

            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-5">
                Explore the Fish Price Forecasting System
            </h2>

            <p class="text-slate-200 max-w-2xl mx-auto mb-8 leading-8">
                Access the platform to manage fish price records
                and explore available research tools.
            </p>

            <button type="button"
                    onclick="confirmAccess()"
                    class="inline-flex items-center justify-center gap-2 bg-aqua hover:bg-teal-300 text-ocean font-bold px-7 py-4 rounded-xl transition">
                <?php echo $isLoggedIn ? 'Open Dashboard' : 'Sign In to FishCast'; ?>
                <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
            </button>

        </div>
    </section>

</main>

<footer class="bg-white border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 py-8">

        <div class="flex flex-col md:flex-row items-center justify-between gap-5">

            <div class="flex items-center gap-2">
                <i data-lucide="fish" class="w-6 h-6 text-marine"></i>
                <span class="font-display font-extrabold text-xl text-ocean">
                    Fish<span class="text-marine">Cast</span>
                </span>
            </div>

            <p class="text-slate-500 text-sm text-center">
                &copy; <?php echo date('Y'); ?> FishCast.
                Research Project | Madridejos, Cebu
            </p>

            <a href="#home" class="text-marine hover:text-ocean text-sm font-bold inline-flex items-center gap-2">
                Back to Top
                <i data-lucide="arrow-up" class="w-4 h-4"></i>
            </a>

        </div>

    </div>
</footer>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    menuToggle.addEventListener('click', function () {
        const isOpen = mobileMenu.classList.toggle('open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.querySelectorAll('#mobileMenu a').forEach(function (link) {
        link.addEventListener('click', function () {
            mobileMenu.classList.remove('open');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
    });

    const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    const accessUrl = <?php echo json_encode($accessUrl); ?>;

    function confirmAccess() {
        if (typeof Swal === 'undefined') {
            window.location.href = accessUrl;
            return;
        }

        Swal.fire({
            title: isLoggedIn ? 'Open Your Dashboard?' : 'Welcome to FishCast!',
            text: isLoggedIn
                ? 'Continue to your FishCast administration dashboard.'
                : 'Sign in to access the Fish Price Forecasting System.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isLoggedIn ? 'Open Dashboard' : 'Continue to Sign In',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#087E8B',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = accessUrl;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') {
            return;
        }

        try {
            if (!sessionStorage.getItem('fishcast_ocean_welcome')) {
                Swal.fire({
                    title: 'Welcome to FishCast!',
                    text: 'Discover a research-based platform for fish price monitoring and forecasting in Madridejos, Cebu.',
                    icon: 'info',
                    confirmButtonText: 'Explore FishCast',
                    confirmButtonColor: '#087E8B',
                    timer: 5000,
                    timerProgressBar: true
                });

                sessionStorage.setItem('fishcast_ocean_welcome', '1');
            }
        } catch (error) {
            // The homepage remains usable if session storage is unavailable.
        }
    });
</script>


<script>
const fishcastAnalytics = <?= json_encode([
    'trend'=>$analytics['trend'], 'speciesPrices'=>$analytics['speciesPrices'], 'marketCounts'=>$analytics['marketCounts']
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
(function () {
    if (typeof Chart === 'undefined') return;
    const palette = ['#087E8B','#2EC4B6','#0B3954','#60A5FA','#F59E0B','#A78BFA','#F472B6','#84CC16'];
    const base = {responsive:true, maintainAspectRatio:false};
    const fish = document.getElementById('heroFish');
    const market = document.getElementById('heroMarket');
    let trendChart;
    function updateTrend() {
        const filtered = fishcastAnalytics.trend.filter(r => String(r.fish_id) === fish.value && String(r.market_id) === market.value);
        document.getElementById('trendEmpty').classList.toggle('hidden', filtered.length > 0);
        if (trendChart) trendChart.destroy();
        trendChart = new Chart(document.getElementById('heroTrendChart'), {
            type:'line',
            data:{labels:filtered.map(r=>r.observed_on),datasets:[{label:'Average observed price (₱/kg)',data:filtered.map(r=>Number(r.avg_price)),borderColor:'#087E8B',backgroundColor:'rgba(46,196,182,.13)',fill:true,tension:.3,pointRadius:3}]},
            options:{...base,plugins:{legend:{display:false}},scales:{y:{beginAtZero:false,ticks:{callback:v=>'₱'+v}}}}
        });
    }
    if (fish && market) {fish.addEventListener('change',updateTrend);market.addEventListener('change',updateTrend);updateTrend();}
    const species = fishcastAnalytics.speciesPrices;
    new Chart(document.getElementById('speciesChart'),{
        type:'bar',data:{labels:species.map(r=>r.label),datasets:[{label:'Mean ₱/kg',data:species.map(r=>Number(r.value)),backgroundColor:'#087E8B',borderRadius:6}]},
        options:{...base,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}}
    });
    const markets = fishcastAnalytics.marketCounts;
    new Chart(document.getElementById('marketChart'),{
        type:'doughnut',data:{labels:markets.map(r=>r.label),datasets:[{data:markets.map(r=>Number(r.value)),backgroundColor:markets.map((_,i)=>palette[i%palette.length])}]},
        options:{...base,plugins:{legend:{position:'bottom'}}}
    });
})();
</script>
</body>
</html>


<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = $_SESSION['market_success'] ?? '';
unset($_SESSION['market_success']);

$oldInput = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        $error = 'Invalid request token. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($action === 'add' || $action === 'edit') {
                $name = trim($_POST['name'] ?? '');
                $municipality = trim($_POST['municipality'] ?? '');
                $province = trim($_POST['province'] ?? '');
                $active = ($_POST['is_active'] ?? '1') === '1' ? 1 : 0;

                if ($name === '' || $municipality === '' || $province === '') {
                    throw new Exception('All market information fields are required.');
                }

                if (
                    mb_strlen($name) > 150 ||
                    mb_strlen($municipality) > 100 ||
                    mb_strlen($province) > 100
                ) {
                    throw new Exception('One or more fields exceed the allowed length.');
                }

                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO markets
                        (name, municipality, province, is_active)
                        VALUES (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $name,
                        $municipality,
                        $province,
                        $active
                    ]);

                    $success = 'Market added successfully.';
                } else {
                    if (!$id || $id < 1) {
                        throw new Exception('Invalid market ID.');
                    }

                    $check = $pdo->prepare("SELECT id FROM markets WHERE id = ?");
                    $check->execute([$id]);

                    if (!$check->fetchColumn()) {
                        throw new Exception('The selected market no longer exists.');
                    }

                    $stmt = $pdo->prepare("
                        UPDATE markets
                        SET name = ?,
                            municipality = ?,
                            province = ?,
                            is_active = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $name,
                        $municipality,
                        $province,
                        $active,
                        $id
                    ]);

                    $success = 'Market updated successfully.';
                }
            } elseif ($action === 'deactivate') {
                if (!$id || $id < 1) {
                    throw new Exception('Invalid market ID.');
                }

                $stmt = $pdo->prepare("
                    UPDATE markets
                    SET is_active = 0
                    WHERE id = ? AND is_active = 1
                ");

                $stmt->execute([$id]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('The market is already inactive or does not exist.');
                }

                $success = 'Market deactivated successfully.';
            } else {
                throw new Exception('Invalid action.');
            }

            $_SESSION['market_success'] = $success;
            header('Location: markets.php');
            exit;

        } catch (Throwable $exception) {
            if ($exception instanceof PDOException) {
                error_log('FishCast market error: ' . $exception->getMessage());
                $error = 'Unable to save the market. Please check the information and try again.';
            } else {
                $error = $exception->getMessage();
            }

            if ($action === 'add' || $action === 'edit') {
                $oldInput = [
                    'action' => $action,
                    'id' => $id ?: '',
                    'name' => $_POST['name'] ?? '',
                    'municipality' => $_POST['municipality'] ?? '',
                    'province' => $_POST['province'] ?? '',
                    'is_active' => $_POST['is_active'] ?? '1'
                ];
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT *
        FROM markets
        WHERE name LIKE ?
           OR municipality LIKE ?
           OR province LIKE ?
        ORDER BY name
    ");

    $term = '%' . $search . '%';
    $stmt->execute([$term, $term, $term]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM markets
        ORDER BY name
    ");
}

$markets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalMarkets = count($markets);
$activeMarkets = 0;
$inactiveMarkets = 0;

foreach ($markets as $market) {
    if ((int)$market['is_active'] === 1) {
        $activeMarkets++;
    } else {
        $inactiveMarkets++;
    }
}

$statsLabel = $search !== '' ? 'Matching' : 'Total';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Market Management | FishCast</title>

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
            background: white;
            border: 1px solid #E1ECEE;
            border-radius: 20px;
            box-shadow: 0 4px 18px rgba(11,57,84,.035);
        }

        .form-control {
            width: 100%;
            border: 1px solid #CBD5E1;
            border-radius: 12px;
            padding: 12px 15px;
            background: white;
            color: #0B3954;
            outline: none;
            transition: .2s;
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
        <section class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">

            <div>
                <p class="text-xs uppercase tracking-[.16em] font-bold text-marine flex items-center gap-2 mb-3">
                    <i data-lucide="store" class="w-4 h-4"></i>
                    FishCast / Data Management
                </p>

                <h1 class="font-display text-3xl lg:text-4xl font-extrabold text-ocean">
                    Market Management
                </h1>

                <p class="text-slate-500 mt-2 text-sm lg:text-base">
                    Manage selected public markets for fish price monitoring.
                </p>
            </div>

            <button
                type="button"
                onclick="openAddModal()"
                class="primary-btn shadow-sm shrink-0">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Add Market
            </button>

        </section>

        <!-- STATISTICS -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div class="content-card p-5 flex items-center gap-4">
                <div class="p-3 rounded-2xl bg-seamist">
                    <i data-lucide="store" class="w-7 h-7 text-marine"></i>
                </div>

                <div>
                    <p class="text-sm text-slate-500">
                        <?= e($statsLabel) ?> Markets
                    </p>
                    <h2 class="font-display text-3xl font-bold text-ocean mt-1">
                        <?= number_format($totalMarkets) ?>
                    </h2>
                </div>
            </div>

            <div class="content-card p-5 flex items-center gap-4">
                <div class="p-3 rounded-2xl bg-emerald-50">
                    <i data-lucide="check-circle-2" class="w-7 h-7 text-emerald-700"></i>
                </div>

                <div>
                    <p class="text-sm text-slate-500">
                        <?= e($statsLabel) ?> Active
                    </p>
                    <h2 class="font-display text-3xl font-bold text-ocean mt-1">
                        <?= number_format($activeMarkets) ?>
                    </h2>
                </div>
            </div>

            <div class="content-card p-5 flex items-center gap-4">
                <div class="p-3 rounded-2xl bg-slate-100">
                    <i data-lucide="circle-pause" class="w-7 h-7 text-slate-600"></i>
                </div>

                <div>
                    <p class="text-sm text-slate-500">
                        <?= e($statsLabel) ?> Inactive
                    </p>
                    <h2 class="font-display text-3xl font-bold text-ocean mt-1">
                        <?= number_format($inactiveMarkets) ?>
                    </h2>
                </div>
            </div>

        </section>

        <!-- MARKET DIRECTORY -->
        <section class="content-card overflow-hidden">

            <div class="p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">

                <div>
                    <h2 class="font-display text-xl font-bold text-ocean">
                        Market Directory
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Search, update, and manage registered markets.
                    </p>
                </div>

                <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">

                    <div class="relative flex-1 lg:w-72">
                        <i data-lucide="search"
                           class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none"></i>

                        <input
                            type="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search markets..."
                            class="form-control pl-12">
                    </div>

                    <button type="submit" class="primary-btn">
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="markets.php"
                           class="inline-flex items-center justify-center gap-2 border border-slate-200 px-4 py-3 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            <i data-lucide="x" class="w-4 h-4"></i>
                            Clear
                        </a>
                    <?php endif; ?>

                </form>
            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-seamist/60 text-ocean">
                        <tr>
                            <th class="px-6 py-4 font-bold">Market Name</th>
                            <th class="px-6 py-4 font-bold">Municipality</th>
                            <th class="px-6 py-4 font-bold">Province</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($markets)): ?>

                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="flex flex-col items-center">

                                        <div class="w-16 h-16 rounded-2xl bg-seamist flex items-center justify-center mb-4">
                                            <i data-lucide="store" class="w-8 h-8 text-marine"></i>
                                        </div>

                                        <h3 class="font-display text-lg font-bold text-ocean">
                                            No Markets Found
                                        </h3>

                                        <p class="text-slate-500 text-sm mt-2">
                                            <?= $search !== ''
                                                ? 'Try another search term.'
                                                : 'Add your first market to get started.' ?>
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($markets as $market): ?>

                                <tr class="table-row transition">

                                    <td class="px-6 py-4">

                                        <div class="flex items-center gap-3">

                                            <div class="w-10 h-10 rounded-xl bg-seamist flex items-center justify-center shrink-0">
                                                <i data-lucide="store" class="w-5 h-5 text-marine"></i>
                                            </div>

                                            <span class="font-bold text-ocean">
                                                <?= e($market['name']) ?>
                                            </span>

                                        </div>

                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= e($market['municipality']) ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= e($market['province']) ?>
                                    </td>

                                    <td class="px-6 py-4">

                                        <?php if ((int)$market['is_active'] === 1): ?>

                                            <span class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full text-xs font-bold">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="inline-flex items-center gap-2 bg-slate-100 text-slate-600 px-3 py-1.5 rounded-full text-xs font-bold">
                                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td class="px-6 py-4">

                                        <div class="flex items-center justify-end gap-2">

                                            <button
                                                type="button"
                                                data-market="<?= e(json_encode($market, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>"
                                                onclick="openEditModal(JSON.parse(this.dataset.market))"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-seamist text-marine hover:bg-teal-100 text-xs font-bold transition">

                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                                Edit
                                            </button>

                                            <?php if ((int)$market['is_active'] === 1): ?>

                                                <form
                                                    method="POST"
                                                    class="deactivate-form"
                                                    data-market-name="<?= e($market['name']) ?>">

                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="id" value="<?= (int)$market['id'] ?>">

                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-xs font-bold transition">

                                                        <i data-lucide="circle-pause" class="w-4 h-4"></i>
                                                        Deactivate
                                                    </button>

                                                </form>

                                            <?php endif; ?>

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
                    Showing <?= number_format($totalMarkets) ?> markets
                    <?= $search !== '' ? 'matching your search.' : 'in the directory.' ?>
                </p>
            </div>

        </section>

    </main>

</div>

<!-- ADD / EDIT MARKET MODAL -->
<div
    id="marketModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#061F30]/60 backdrop-blur-sm p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modalTitle"
    onclick="if (event.target === this) closeModal()">

    <div class="modal-panel bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between gap-4 p-6 border-b border-slate-100">

            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-xl bg-seamist flex items-center justify-center">
                    <i data-lucide="store" class="w-6 h-6 text-marine"></i>
                </div>

                <div>
                    <h3 id="modalTitle" class="font-display text-xl font-bold text-ocean">
                        Add Market
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Enter market information below.
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

        <form id="marketForm" method="POST" class="p-6 space-y-5">

            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="marketId">

            <div>
                <label for="marketName" class="block text-sm font-semibold text-ocean mb-2">
                    Market Name <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="marketName"
                    required
                    maxlength="150"
                    placeholder="Enter market name"
                    class="form-control">
            </div>

            <div>
                <label for="municipality" class="block text-sm font-semibold text-ocean mb-2">
                    Municipality <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="municipality"
                    id="municipality"
                    value="Madridejos"
                    required
                    maxlength="100"
                    placeholder="Enter municipality"
                    class="form-control">
            </div>

            <div>
                <label for="province" class="block text-sm font-semibold text-ocean mb-2">
                    Province <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="province"
                    id="province"
                    value="Cebu"
                    required
                    maxlength="100"
                    placeholder="Enter province"
                    class="form-control">
            </div>

            <div>
                <label for="marketStatus" class="block text-sm font-semibold text-ocean mb-2">
                    Status
                </label>

                <select
                    name="is_active"
                    id="marketStatus"
                    class="form-control">

                    <option value="1">Active</option>
                    <option value="0">Inactive</option>

                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">

                <button
                    type="button"
                    onclick="closeModal()"
                    class="px-5 py-3 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold text-sm">
                    Cancel
                </button>

                <button
                    type="submit"
                    id="saveButton"
                    class="primary-btn text-sm">

                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span id="saveButtonText">Save Market</span>

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

const marketModal = document.getElementById('marketModal');
const marketForm = document.getElementById('marketForm');

function showModal() {
    marketModal.classList.remove('hidden');
    marketModal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        document.getElementById('marketName').focus();
    }, 100);
}

function closeModal() {
    marketModal.classList.add('hidden');
    marketModal.classList.remove('flex');
    document.body.style.overflow = '';
}

function openAddModal() {
    marketForm.reset();

    document.getElementById('formAction').value = 'add';
    document.getElementById('marketId').value = '';
    document.getElementById('marketName').value = '';
    document.getElementById('municipality').value = 'Madridejos';
    document.getElementById('province').value = 'Cebu';
    document.getElementById('marketStatus').value = '1';
    document.getElementById('modalTitle').textContent = 'Add Market';
    document.getElementById('saveButtonText').textContent = 'Save Market';

    showModal();
}

function openEditModal(market) {
    marketForm.reset();

    document.getElementById('formAction').value = 'edit';
    document.getElementById('marketId').value = market.id;
    document.getElementById('marketName').value = market.name || '';
    document.getElementById('municipality').value = market.municipality || '';
    document.getElementById('province').value = market.province || '';
    document.getElementById('marketStatus').value = String(market.is_active);
    document.getElementById('modalTitle').textContent = 'Edit Market';
    document.getElementById('saveButtonText').textContent = 'Update Market';

    showModal();
}

document.querySelectorAll('.deactivate-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const marketName = form.dataset.marketName || 'this market';

        if (typeof Swal === 'undefined') {
            if (confirm('Deactivate ' + marketName + '?')) {
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Deactivate Market?',
            text: marketName + ' will be marked as inactive. Existing price records will be preserved.',
            icon: 'warning',
            iconColor: '#D97706',
            showCancelButton: true,
            confirmButtonText: 'Yes, Deactivate',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#087E8B',
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

marketForm.addEventListener('submit', function(event) {
    if (!marketForm.checkValidity()) {
        return;
    }

    const name = document.getElementById('marketName').value.trim();
    const municipality = document.getElementById('municipality').value.trim();
    const province = document.getElementById('province').value.trim();

    if (!name || !municipality || !province) {
        event.preventDefault();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Required Fields',
                text: 'Please complete the market name, municipality, and province.',
                icon: 'warning',
                confirmButtonColor: '#087E8B'
            });
        } else {
            alert('Please complete all required fields.');
        }

        return;
    }

    document.getElementById('saveButton').disabled = true;
    document.getElementById('saveButtonText').textContent = 'Saving...';
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !marketModal.classList.contains('hidden')) {
        closeModal();
    }
});

const successMessage = <?= json_encode(
    $success,
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
            document.getElementById('formAction').value = oldInput.action;
            document.getElementById('marketId').value = oldInput.id || '';
            document.getElementById('marketName').value = oldInput.name || '';
            document.getElementById('municipality').value = oldInput.municipality || '';
            document.getElementById('province').value = oldInput.province || '';
            document.getElementById('marketStatus').value = String(oldInput.is_active);

            document.getElementById('modalTitle').textContent =
                oldInput.action === 'edit' ? 'Edit Market' : 'Add Market';

            document.getElementById('saveButtonText').textContent =
                oldInput.action === 'edit' ? 'Update Market' : 'Save Market';

            showModal();
        }
    }
});
</script>

</body>
</html>

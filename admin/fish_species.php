
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
$success = '';
$oldInput = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        $error = 'Your session token is invalid. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        try {
            if ($action === 'add' || $action === 'edit') {
                $common = trim($_POST['common_name'] ?? '');
                $scientific = trim($_POST['scientific_name'] ?? '');
                $local = trim($_POST['local_name'] ?? '');
                $active = ($_POST['is_active'] ?? '1') === '1' ? 1 : 0;

                if ($common === '') {
                    throw new Exception('Common name is required.');
                }

                if (
                    mb_strlen($common) > 120 ||
                    mb_strlen($scientific) > 180 ||
                    mb_strlen($local) > 120
                ) {
                    throw new Exception('One or more fields exceed the allowed length.');
                }

                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO fish_species
                        (common_name, scientific_name, local_name, is_active)
                        VALUES (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $common,
                        $scientific !== '' ? $scientific : null,
                        $local !== '' ? $local : null,
                        $active
                    ]);

                    $success = 'Fish species added successfully.';
                } else {
                    if (!$id || $id < 1) {
                        throw new Exception('Invalid species ID.');
                    }

                    $check = $pdo->prepare("SELECT id FROM fish_species WHERE id = ?");
                    $check->execute([$id]);

                    if (!$check->fetchColumn()) {
                        throw new Exception('The selected fish species no longer exists.');
                    }

                    $stmt = $pdo->prepare("
                        UPDATE fish_species
                        SET common_name = ?,
                            scientific_name = ?,
                            local_name = ?,
                            is_active = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $common,
                        $scientific !== '' ? $scientific : null,
                        $local !== '' ? $local : null,
                        $active,
                        $id
                    ]);

                    $success = 'Fish species updated successfully.';
                }
            } elseif ($action === 'deactivate') {
                if (!$id || $id < 1) {
                    throw new Exception('Invalid species ID.');
                }

                $stmt = $pdo->prepare("
                    UPDATE fish_species
                    SET is_active = 0
                    WHERE id = ? AND is_active = 1
                ");

                $stmt->execute([$id]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('This fish species is already inactive or does not exist.');
                }

                $success = 'Fish species deactivated successfully.';
            } else {
                throw new Exception('Invalid action.');
            }
        } catch (Throwable $exception) {
            if ($exception instanceof PDOException) {
                error_log('FishCast fish_species database error: ' . $exception->getMessage());
                $error = 'Unable to save the record. Please check the information and try again.';
            } else {
                $error = $exception->getMessage();
            }

            if ($action === 'add' || $action === 'edit') {
                $oldInput = [
                    'action' => $action,
                    'id' => $id ?: '',
                    'common_name' => $_POST['common_name'] ?? '',
                    'scientific_name' => $_POST['scientific_name'] ?? '',
                    'local_name' => $_POST['local_name'] ?? '',
                    'is_active' => $_POST['is_active'] ?? '1'
                ];
            }
        }

        if ($success !== '') {
            $_SESSION['flash_success'] = $success;
            header('Location: fish_species.php');
            exit;
        }
    }
}

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT *
        FROM fish_species
        WHERE common_name LIKE ?
           OR scientific_name LIKE ?
           OR local_name LIKE ?
        ORDER BY common_name
    ");

    $term = '%' . $search . '%';
    $stmt->execute([$term, $term, $term]);
} else {
    $stmt = $pdo->query("
        SELECT *
        FROM fish_species
        ORDER BY common_name
    ");
}

$species = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSpecies = count($species);
$activeSpecies = 0;
$inactiveSpecies = 0;

foreach ($species as $fish) {
    if ((int)$fish['is_active'] === 1) {
        $activeSpecies++;
    } else {
        $inactiveSpecies++;
    }
}

$statsLabel = $search !== '' ? 'Matching' : 'Total';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fish Species | FishCast</title>

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
            background: #087E8B;
            color: white;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 20px;
            transition: .2s;
        }

        .primary-btn:hover {
            background: #0B3954;
        }

        .table-row:hover {
            background: #F2FAFA;
        }

        .swal2-popup {
            border-radius: 20px !important;
            font-family: 'Inter', sans-serif !important;
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
                    <i data-lucide="fish" class="w-4 h-4"></i>
                    FishCast / Data Management
                </p>

                <h1 class="font-display text-3xl lg:text-4xl font-extrabold text-ocean">
                    Fish Species Management
                </h1>

                <p class="text-slate-500 mt-2 text-sm lg:text-base">
                    Register and manage fish species used in market price monitoring.
                </p>
            </div>

            <button
                type="button"
                onclick="openAddModal()"
                class="primary-btn shadow-sm shrink-0">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Add Fish Species
            </button>

        </section>

        <!-- STATISTICS -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div class="content-card p-5 flex items-center gap-4">
                <div class="w-13 h-13 p-3 rounded-2xl bg-seamist">
                    <i data-lucide="fish" class="w-7 h-7 text-marine"></i>
                </div>

                <div>
                    <p class="text-sm text-slate-500">
                        <?= e($statsLabel) ?> Species
                    </p>
                    <h2 class="font-display text-3xl font-bold text-ocean mt-1">
                        <?= number_format($totalSpecies) ?>
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
                        <?= number_format($activeSpecies) ?>
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
                        <?= number_format($inactiveSpecies) ?>
                    </h2>
                </div>
            </div>

        </section>

        <!-- SPECIES TABLE -->
        <section class="content-card overflow-hidden">

            <div class="p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">

                <div>
                    <h2 class="font-display text-xl font-bold text-ocean">
                        Fish Species Directory
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Search, update, and manage registered species.
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
                            placeholder="Search fish species..."
                            class="form-control pl-12">
                    </div>

                    <button type="submit" class="primary-btn">
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="fish_species.php"
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
                            <th class="px-6 py-4 font-bold">Common Name</th>
                            <th class="px-6 py-4 font-bold">Scientific Name</th>
                            <th class="px-6 py-4 font-bold">Local Name</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php if (empty($species)): ?>

                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-seamist flex items-center justify-center mb-4">
                                            <i data-lucide="fish" class="w-8 h-8 text-marine"></i>
                                        </div>

                                        <h3 class="font-display text-lg font-bold text-ocean">
                                            No Fish Species Found
                                        </h3>

                                        <p class="text-slate-500 text-sm mt-2">
                                            <?= $search !== ''
                                                ? 'Try another search term.'
                                                : 'Add your first fish species to get started.' ?>
                                        </p>
                                    </div>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($species as $fish): ?>

                                <tr class="table-row transition">

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">

                                            <div class="w-10 h-10 rounded-xl bg-seamist flex items-center justify-center shrink-0">
                                                <i data-lucide="fish" class="w-5 h-5 text-marine"></i>
                                            </div>

                                            <span class="font-bold text-ocean">
                                                <?= e($fish['common_name']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600 italic">
                                        <?= e($fish['scientific_name'] ?: '—') ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= e($fish['local_name'] ?: '—') ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?php if ((int)$fish['is_active'] === 1): ?>

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
                                                data-fish="<?= e(json_encode($fish, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>"
                                                onclick="openEditModal(JSON.parse(this.dataset.fish))"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-seamist text-marine hover:bg-teal-100 text-xs font-bold transition">

                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                                Edit
                                            </button>

                                            <?php if ((int)$fish['is_active'] === 1): ?>

                                                <form
                                                    method="POST"
                                                    class="deactivate-form"
                                                    data-species-name="<?= e($fish['common_name']) ?>">

                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="id" value="<?= (int)$fish['id'] ?>">

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
                    Showing <?= number_format($totalSpecies) ?>
                    <?= $totalSpecies === 1 ? 'species' : 'species' ?>
                    <?= $search !== '' ? 'matching your search.' : 'in the directory.' ?>
                </p>
            </div>

        </section>

    </main>

</div>

<!-- ADD / EDIT MODAL -->
<div
    id="fishModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#061F30]/60 backdrop-blur-sm p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modalTitle"
    onclick="if (event.target === this) closeModal()">

    <div class="modal-panel bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between gap-4 p-6 border-b border-slate-100">

            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-xl bg-seamist flex items-center justify-center">
                    <i data-lucide="fish" class="w-6 h-6 text-marine"></i>
                </div>

                <div>
                    <h3 id="modalTitle" class="font-display text-xl font-bold text-ocean">
                        Add Fish Species
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Enter fish species information.
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

        <form id="fishForm" method="POST" class="p-6 space-y-5">

            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="fishId">

            <div>
                <label for="commonName" class="block text-sm font-semibold text-ocean mb-2">
                    Common Name <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="common_name"
                    id="commonName"
                    required
                    maxlength="120"
                    placeholder="e.g. Milkfish"
                    class="form-control">
            </div>

            <div>
                <label for="scientificName" class="block text-sm font-semibold text-ocean mb-2">
                    Scientific Name
                </label>

                <input
                    type="text"
                    name="scientific_name"
                    id="scientificName"
                    maxlength="180"
                    placeholder="e.g. Chanos chanos"
                    class="form-control">
            </div>

            <div>
                <label for="localName" class="block text-sm font-semibold text-ocean mb-2">
                    Local Name
                </label>

                <input
                    type="text"
                    name="local_name"
                    id="localName"
                    maxlength="120"
                    placeholder="e.g. Bangus"
                    class="form-control">
            </div>

            <div>
                <label for="fishStatus" class="block text-sm font-semibold text-ocean mb-2">
                    Status
                </label>

                <select name="is_active" id="fishStatus" class="form-control">
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
                    <span id="saveButtonText">Save Species</span>
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

const fishModal = document.getElementById('fishModal');
const fishForm = document.getElementById('fishForm');

function showModal() {
    fishModal.classList.remove('hidden');
    fishModal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    setTimeout(function() {
        document.getElementById('commonName').focus();
    }, 100);
}

function closeModal() {
    fishModal.classList.add('hidden');
    fishModal.classList.remove('flex');
    document.body.style.overflow = '';
}

function openAddModal() {
    fishForm.reset();

    document.getElementById('formAction').value = 'add';
    document.getElementById('fishId').value = '';
    document.getElementById('fishStatus').value = '1';
    document.getElementById('modalTitle').textContent = 'Add Fish Species';
    document.getElementById('saveButtonText').textContent = 'Save Species';

    showModal();
}

function openEditModal(fish) {
    fishForm.reset();

    document.getElementById('formAction').value = 'edit';
    document.getElementById('fishId').value = fish.id;
    document.getElementById('commonName').value = fish.common_name || '';
    document.getElementById('scientificName').value = fish.scientific_name || '';
    document.getElementById('localName').value = fish.local_name || '';
    document.getElementById('fishStatus').value = String(fish.is_active);
    document.getElementById('modalTitle').textContent = 'Edit Fish Species';
    document.getElementById('saveButtonText').textContent = 'Update Species';

    showModal();
}

document.querySelectorAll('.deactivate-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const speciesName = form.dataset.speciesName || 'this fish species';

        if (typeof Swal === 'undefined') {
            if (confirm('Deactivate ' + speciesName + '?')) {
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Deactivate Fish Species?',
            text: speciesName + ' will be marked as inactive. Existing records will be preserved.',
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

fishForm.addEventListener('submit', function(event) {
    if (!fishForm.checkValidity()) {
        return;
    }

    const commonName = document.getElementById('commonName').value.trim();

    if (!commonName) {
        event.preventDefault();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Required Field',
                text: 'Please enter the common name of the fish species.',
                icon: 'warning',
                confirmButtonColor: '#087E8B'
            });
        } else {
            alert('Please enter the common name.');
        }

        return;
    }

    const button = document.getElementById('saveButton');
    button.disabled = true;
    document.getElementById('saveButtonText').textContent = 'Saving...';
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !fishModal.classList.contains('hidden')) {
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
            document.getElementById('fishId').value = oldInput.id || '';
            document.getElementById('commonName').value = oldInput.common_name || '';
            document.getElementById('scientificName').value = oldInput.scientific_name || '';
            document.getElementById('localName').value = oldInput.local_name || '';
            document.getElementById('fishStatus').value = String(oldInput.is_active);

            document.getElementById('modalTitle').textContent =
                oldInput.action === 'edit' ? 'Edit Fish Species' : 'Add Fish Species';

            document.getElementById('saveButtonText').textContent =
                oldInput.action === 'edit' ? 'Update Species' : 'Save Species';

            showModal();
        }
    }
});
</script>

</body>
</html>

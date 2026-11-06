
<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$menu = [
    ['dashboard.php', 'layout-dashboard', 'Dashboard'],
    ['fish_species.php', 'fish', 'Fish Species'],
    ['markets.php', 'store', 'Markets'],
    ['price_records.php', 'database', 'Price Records'],
    ['import_prices.php', 'file-up', 'Import Dataset'],
    ['forecasts.php', 'chart-line', 'ML Forecasts']
];

function sidebarE($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$sidebarName = $_SESSION['full_name'] ?? 'FishCast User';
$sidebarRole = $_SESSION['role'] ?? 'User';
$sidebarPicture = '';

if (isset($pdo) && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT full_name, role, profile_picture
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([(int)$_SESSION['user_id']]);
    $sidebarUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sidebarUser) {
        $sidebarName = $sidebarUser['full_name'];
        $sidebarRole = $sidebarUser['role'];

        $storedPicture = $sidebarUser['profile_picture'] ?? '';

        if (
            $storedPicture &&
            preg_match(
                '~^uploads/profile_pictures/[a-zA-Z0-9_.-]+$~',
                $storedPicture
            ) &&
            is_file(__DIR__ . '/../' . $storedPicture)
        ) {
            $sidebarPicture = '../' . $storedPicture;
        }
    }
}

$nameParts = preg_split('/\s+/', trim($sidebarName));
$initials = '';

foreach (array_slice($nameParts, 0, 2) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}

$initials = $initials ?: 'FC';
?>

<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 w-64
           bg-[#0B3954] text-white
           -translate-x-full lg:translate-x-0
           transition-transform duration-300 ease-in-out
           flex flex-col shadow-2xl">

    <!-- BRAND -->
    <div class="h-20 flex items-center gap-3 px-5
                border-b border-white/10 shrink-0">

        <div class="w-11 h-11 rounded-xl bg-[#087E8B]
                    flex items-center justify-center shadow-sm">
            <i data-lucide="fish" class="w-6 h-6 text-[#2EC4B6]"></i>
        </div>

        <div>
            <h2 class="text-xl font-extrabold tracking-tight leading-tight">
                Fish<span class="text-[#2EC4B6]">Cast</span>
            </h2>

            <p class="text-[10px] uppercase tracking-[0.14em] text-[#A9D9DA] mt-1">
                Price Intelligence
            </p>
        </div>
    </div>

    <!-- NAVIGATION -->
    <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-6">

        <p class="px-4 mb-3 text-[10px] uppercase
                  tracking-[0.18em] font-bold text-[#8ECACB]">
            Main Navigation
        </p>

        <div class="space-y-1">

            <?php foreach ($menu as $item): ?>
                <?php $active = $currentPage === $item[0]; ?>

                <a
                    href="<?= sidebarE($item[0]) ?>"
                    <?= $active ? 'aria-current="page"' : '' ?>
                    class="group flex items-center gap-3
                           px-4 py-3 rounded-xl text-sm
                           font-medium transition-all duration-200
                           <?= $active
                               ? 'bg-[#087E8B] text-white shadow-sm font-semibold'
                               : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">

                    <i
                        data-lucide="<?= sidebarE($item[1]) ?>"
                        class="w-5 h-5 shrink-0
                               <?= $active
                                   ? 'text-[#2EC4B6]'
                                   : 'text-[#8ECACB] group-hover:text-[#2EC4B6]' ?>">
                    </i>

                    <span class="flex-1">
                        <?= sidebarE($item[2]) ?>
                    </span>

                    <?php if ($active): ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#2EC4B6]"></span>
                    <?php endif; ?>

                </a>

            <?php endforeach; ?>

        </div>

        <!-- ACCOUNT -->
        <div class="mt-8 pt-6 border-t border-white/10">

            <p class="px-4 mb-3 text-[10px] uppercase
                      tracking-[0.18em] font-bold text-[#8ECACB]">
                Account
            </p>

            <div class="space-y-1">

                <a
                    href="profile.php"
                    <?= $currentPage === 'profile.php' ? 'aria-current="page"' : '' ?>
                    class="group flex items-center gap-3
                           px-4 py-3 rounded-xl text-sm
                           font-medium transition-all duration-200
                           <?= $currentPage === 'profile.php'
                               ? 'bg-[#087E8B] text-white font-semibold'
                               : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">

                    <i data-lucide="user-round"
                       class="w-5 h-5 text-[#8ECACB] group-hover:text-[#2EC4B6]"></i>

                    My Profile
                </a>

                <a
                    href="../index.php"
                    class="group flex items-center gap-3
                           px-4 py-3 rounded-xl text-sm
                           font-medium text-slate-300
                           hover:bg-white/10 hover:text-white transition-all duration-200">

                    <i data-lucide="house"
                       class="w-5 h-5 text-[#8ECACB] group-hover:text-[#2EC4B6]"></i>

                    Homepage
                </a>

            </div>

        </div>

    </nav>

    <!-- USER & LOGOUT -->
    <div class="shrink-0 p-3 border-t border-white/10 bg-[#092F46]">

        <a
            href="profile.php"
            class="group flex items-center gap-3
                   p-3 rounded-xl hover:bg-white/10 transition">

            <div class="w-11 h-11 rounded-full overflow-hidden
                        bg-[#087E8B] shrink-0 flex items-center justify-center
                        border-2 border-[#2EC4B6]/40">

                <?php if ($sidebarPicture): ?>

                    <img
                        src="<?= sidebarE($sidebarPicture) ?>"
                        alt="Profile picture"
                        class="w-full h-full object-cover">

                <?php else: ?>

                    <span class="text-sm font-bold text-white">
                        <?= sidebarE($initials) ?>
                    </span>

                <?php endif; ?>

            </div>

            <div class="min-w-0 flex-1">

                <p class="text-sm font-bold text-white truncate"
                   title="<?= sidebarE($sidebarName) ?>">
                    <?= sidebarE($sidebarName) ?>
                </p>

                <p class="text-xs text-[#8ECACB] mt-1">
                    <?= sidebarE(ucwords(str_replace('_', ' ', $sidebarRole))) ?>
                </p>

            </div>

            <i data-lucide="chevron-right"
               class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0"></i>

        </a>

        <button
            type="button"
            onclick="confirmLogout()"
            class="w-full flex items-center justify-center gap-2
                   px-4 py-3 mt-2 rounded-xl
                   bg-white/5 border border-white/10
                   text-sm font-semibold text-red-200
                   hover:bg-red-500/15 hover:text-red-100
                   transition-all duration-200">

            <i data-lucide="log-out" class="w-4 h-4"></i>
            Sign Out
        </button>

    </div>

</aside>

<!-- MOBILE OVERLAY -->
<div
    id="sidebarOverlay"
    class="fixed inset-0 bg-[#061F30]/60 z-30 hidden lg:hidden"
    onclick="toggleSidebar()">
</div>

<script>
function confirmLogout() {
    const logoutUrl = '../auth/logout.php';

    if (typeof Swal === 'undefined') {
        if (window.confirm('Are you sure you want to sign out?')) {
            window.location.href = logoutUrl;
        }
        return;
    }

    Swal.fire({
        title: 'Sign Out?',
        text: 'Are you sure you want to leave your FishCast account?',
        icon: 'question',
        iconColor: '#087E8B',
        showCancelButton: true,
        confirmButtonText: 'Yes, Sign Out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#087E8B',
        cancelButtonColor: '#64748B',
        reverseButtons: true,
        background: '#FFFFFF',
        color: '#0B3954',
        customClass: {
            popup: 'rounded-2xl'
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = logoutUrl;
        }
    });
}
</script>

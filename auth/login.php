
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email address and password.';
    } else {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password_hash, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            header('Location: ../admin/dashboard.php');
            exit;
        }

        $error = 'Invalid email address or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | FishCast</title>

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
        }

        h1, h2, h3 {
            font-family: 'Outfit', sans-serif;
        }

        .login-background {
            background:
                radial-gradient(
                    circle at 15% 15%,
                    rgba(46,196,182,0.16),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #f4fcfc 0%,
                    #e8f7f6 100%
                );
        }

        .form-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 14px 16px 14px 46px;
            outline: none;
            transition: all .2s ease;
            color: #0B3954;
            background: #fff;
        }

        .form-input:focus {
            border-color: #087E8B;
            box-shadow: 0 0 0 4px rgba(8,126,139,0.12);
        }

        .login-button {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #087E8B;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            transition: all .2s ease;
        }

        .login-button:hover {
            background: #0B3954;
        }

        .login-button:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        .swal2-popup {
            border-radius: 20px !important;
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>

<body class="min-h-screen login-background">

<div class="min-h-screen flex flex-col lg:flex-row">

    <!-- LEFT PANEL -->
    <section class="hidden lg:flex lg:w-1/2 bg-ocean text-white relative overflow-hidden">

        <div class="absolute top-[-120px] right-[-120px] w-96 h-96 rounded-full bg-aqua/10"></div>
        <div class="absolute bottom-[-160px] left-[-100px] w-[450px] h-[450px] rounded-full bg-marine/30"></div>

        <div class="relative z-10 flex flex-col justify-between w-full p-12 xl:p-20">

            <a href="../index.php" class="flex items-center gap-3 w-fit">

                <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center">
                    <i data-lucide="fish" class="w-8 h-8 text-aqua"></i>
                </div>

                <div>
                    <h2 class="font-display text-3xl font-extrabold">
                        Fish<span class="text-aqua">Cast</span>
                    </h2>

                    <p class="text-xs tracking-[0.15em] text-slate-300 uppercase">
                        Fish Price Intelligence
                    </p>
                </div>

            </a>

            <div class="max-w-xl">

                <div class="inline-flex items-center gap-2 border border-aqua/30 bg-white/5 rounded-full px-4 py-2 text-sm text-aqua mb-8">
                    <i data-lucide="waves" class="w-4 h-4"></i>
                    Madridejos, Cebu
                </div>

                <h1 class="font-display text-5xl xl:text-6xl font-extrabold leading-tight mb-7">
                    Turning Fish Price Data Into
                    <span class="text-aqua">
                        Smarter Decisions.
                    </span>
                </h1>

                <p class="text-slate-300 text-lg leading-9">
                    A research-based platform for historical fish price
                    monitoring, machine learning forecasting, and
                    decision support for selected local markets.
                </p>

                <div class="grid grid-cols-3 gap-4 mt-12">

                    <div class="bg-white/5 border border-white/10 rounded-2xl p-5">
                        <i data-lucide="database" class="w-7 h-7 text-aqua mb-4"></i>
                        <p class="text-sm font-semibold">
                            Price Records
                        </p>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-2xl p-5">
                        <i data-lucide="chart-line" class="w-7 h-7 text-aqua mb-4"></i>
                        <p class="text-sm font-semibold">
                            Trend Analysis
                        </p>
                    </div>

                    <div class="bg-white/5 border border-white/10 rounded-2xl p-5">
                        <i data-lucide="brain-circuit" class="w-7 h-7 text-aqua mb-4"></i>
                        <p class="text-sm font-semibold">
                            ML Forecasting
                        </p>
                    </div>

                </div>

            </div>

            <p class="text-sm text-slate-400">
                Machine Learning-Based Fish Price Forecasting System
                with Decision Support
            </p>

        </div>
    </section>

    <!-- RIGHT PANEL -->
    <section class="w-full lg:w-1/2 flex items-center justify-center px-5 py-12 sm:px-10">

        <div class="w-full max-w-md">

            <!-- MOBILE BRAND -->
            <div class="lg:hidden flex items-center justify-center gap-3 mb-10">

                <div class="w-12 h-12 rounded-xl bg-ocean flex items-center justify-center">
                    <i data-lucide="fish" class="w-7 h-7 text-aqua"></i>
                </div>

                <div>
                    <h2 class="font-display text-2xl font-extrabold text-ocean">
                        Fish<span class="text-marine">Cast</span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Fish Price Intelligence
                    </p>
                </div>

            </div>

            <div class="bg-white rounded-3xl shadow-xl shadow-ocean/10 border border-teal-100 p-7 sm:p-10">

                <div class="mb-9">

                    <div class="w-14 h-14 bg-seamist rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="lock-keyhole" class="w-7 h-7 text-marine"></i>
                    </div>

                    <h2 class="font-display text-3xl font-bold text-ocean">
                        Welcome Back
                    </h2>

                    <p class="text-slate-500 mt-3 leading-7">
                        Sign in to access your FishCast dashboard.
                    </p>

                </div>

                <form
                    id="loginForm"
                    method="POST"
                    action=""
                    class="space-y-6">

                    <div>
                        <label for="email" class="block text-sm font-semibold text-ocean mb-2">
                            Email Address
                        </label>

                        <div class="relative">

                            <i data-lucide="mail"
                               class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none">
                            </i>

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                required
                                maxlength="190"
                                autocomplete="username"
                                placeholder="Enter your email address"
                                class="form-input">

                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-semibold text-ocean">
                                Password
                            </label>
                        </div>

                        <div class="relative">

                            <i data-lucide="lock"
                               class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none">
                            </i>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                class="form-input pr-12">

                            <button
                                type="button"
                                id="togglePassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-marine"
                                aria-label="Show password"
                                aria-pressed="false">

                                <i id="passwordIcon" data-lucide="eye" class="w-5 h-5"></i>

                            </button>

                        </div>
                    </div>

                    <button
                        type="submit"
                        id="loginButton"
                        class="login-button">

                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        <span id="loginButtonText">Sign In</span>

                    </button>

                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 text-center">

                    <a
                        href="../index.php"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-marine hover:text-ocean transition">

                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Back to Homepage

                    </a>

                </div>

            </div>

            <p class="text-center text-xs text-slate-500 mt-8 leading-6">
                FishCast | Machine Learning-Based Fish Price Forecasting
                <br>
                Madridejos, Cebu
            </p>

        </div>

    </section>

</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');

    togglePassword.addEventListener('click', function () {

        const isHidden = passwordInput.type === 'password';

        passwordInput.type = isHidden ? 'text' : 'password';

        passwordIcon.setAttribute(
            'data-lucide',
            isHidden ? 'eye-off' : 'eye'
        );

        togglePassword.setAttribute(
            'aria-label',
            isHidden ? 'Hide password' : 'Show password'
        );

        togglePassword.setAttribute(
            'aria-pressed',
            String(isHidden)
        );

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    const loginButtonText = document.getElementById('loginButtonText');

    loginForm.addEventListener('submit', function () {

        if (!loginForm.checkValidity()) {
            return;
        }

        loginButton.disabled = true;
        loginButtonText.textContent = 'Signing In...';
    });
</script>

<?php if ($error !== ''): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const errorMessage = <?= json_encode(
            $error,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Sign In Failed',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#087E8B'
            });
        } else {
            alert(errorMessage);
        }

    });
</script>
<?php endif; ?>

</body>
</html>


<?php
session_start();
require_once __DIR__ . '/config/database.php';

$message = '';
$success = false;

$stmt = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
);

$adminExists = (int) $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 12
    ) {
        $message = 'Enter a valid name, email, and password of at least 12 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users
                 (full_name, email, password_hash, role)
                 VALUES (?, ?, ?, 'admin')"
            );

            $stmt->execute([$name, $email, $hash]);

            $success = true;
            $message = 'Administrator created successfully.';
        } catch (PDOException $e) {
            $message = 'Unable to create account. Check if the email already exists.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

<div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">
        Administrator Setup
    </h1>

    <p class="text-slate-500 mb-6">
        Fish Price Forecasting System
    </p>

    <?php if ($message): ?>
        <p class="mb-4 text-sm <?= $success ? 'text-green-600' : 'text-red-600' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($success || $adminExists): ?>
        <p class="mb-4">Administrator setup is complete.</p>
        <a href="auth/login.php" class="text-blue-600 font-semibold">
            Go to Login
        </a>
    <?php else: ?>
        <form method="POST" class="space-y-4">
            <input name="full_name" required maxlength="150"
                   placeholder="Full Name"
                   class="w-full border rounded-lg p-3">

            <input type="email" name="email" required maxlength="190"
                   placeholder="Email Address"
                   class="w-full border rounded-lg p-3">

            <input type="password" name="password" required minlength="12"
                   placeholder="Password (12 characters minimum)"
                   class="w-full border rounded-lg p-3">

            <button class="w-full bg-emerald-700 text-white p-3 rounded-lg">
                Create Administrator
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

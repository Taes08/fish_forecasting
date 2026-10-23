
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /fish_forecasting/auth/login.php');
    exit;
}

function requireRole($allowedRoles) {
    if (!in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

<?php
// Frank Restaurant - Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'frank_restaurant');

define('SITE_NAME', 'Frank Restaurant');
define('SITE_VERSION', '2.0');

// Currency Settings
define('CURRENCY_SYMBOL', '₱');
define('CURRENCY_CODE', 'PHP');

define('ROLES', [
    'admin'    => ['dashboard', 'reservations', 'tables', 'customers', 'orders', 'reports', 'profile'],
    'manager'  => ['dashboard', 'reservations', 'tables', 'customers', 'orders', 'reports', 'profile'],
    'staff'    => ['dashboard', 'reservations', 'tables', 'orders', 'profile'],
    'customer' => ['dashboard', 'reservations', 'orders', 'profile'],
]);

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function hasAccess($page) {
    $role = getUserRole();
    if (!$role) return false;
    return in_array($page, ROLES[$role] ?? []);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $msg = '', $type = 'success') {
    if ($msg) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
?>

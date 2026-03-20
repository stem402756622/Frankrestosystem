<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
requireLogin();

ob_start(); // Start output buffering

$flash   = getFlash();
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$name    = $_SESSION['full_name'] ?? 'User';
$initials = strtoupper(substr($name,0,1)) . (strpos($name,' ')!==false ? strtoupper(substr(strrchr($name,' '),1,1)) : '');

// Pending reservations badge (for staff/admin)
$pendingCount = 0;
if (in_array($role, ['admin','manager','staff'])) {
    $r = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE status='pending'");
    $pendingCount = $r['cnt'] ?? 0;
}

// Current page detection
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

function navItem($href, $icon, $label, $current, $badge = 0) {
    $page = basename($href, '.php');
    $active = ($page === $current) ? 'active' : '';
    $badgeHtml = $badge > 0 ? "<span class='nav-badge'>$badge</span>" : '';
    echo "<a href='$href' class='nav-item $active'>
        <span class='nav-icon'>$icon</span>
        <span>$label</span>
        $badgeHtml
    </a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Frank Restaurant' ?> — Frank</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/frank-restaurant/assets/css/style.css">
</head>
<body>

<?php if($flash): ?>
<div id="flashData"
     data-msg="<?= htmlspecialchars($flash['msg']) ?>"
     data-type="<?= htmlspecialchars($flash['type']) ?>"
     data-confetti="<?= ($flash['type']==='success' && isset($flash['confetti'])) ? '1' : '0' ?>">
</div>
<?php endif; ?>

<div class="layout">
    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">🍽️</div>
            <span class="logo-text">Frank</span>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section-title">Main</div>
            <?php navItem('dashboard.php',         '📊', 'Dashboard',    $currentPage); ?>
            <?php navItem('reservations.php',  '📅', 'Reservations', $currentPage, $pendingCount); ?>
            <?php navItem('tables.php',        '🪑', 'Tables',       $currentPage); ?>

            <?php if(hasAccess('orders')): ?>
            <div class="nav-section-title">Operations</div>
            <?php navItem('orders.php',   '🧾', 'Orders',   $currentPage); ?>
            
            <?php if($role === 'customer'): ?>
                <?php navItem('index.php', '🍽️', 'Menu', $currentPage); ?>
                <?php navItem('my_reservations.php', '📋', 'My Reservations', $currentPage); ?>
                <?php navItem('queue_status.php', '⏳', 'Queue Status', $currentPage); ?>
                <?php navItem('favorites.php', '❤️', 'Favorites', $currentPage); ?>
                <?php navItem('feedback.php', '⭐', 'Feedback', $currentPage); ?>
            <?php else: ?>
                <?php if(in_array($role, ['admin','manager'])): ?>
                    <?php navItem('admin_menu.php', '📋', 'Menu', $currentPage); ?>
                    <?php navItem('inventory.php', '📦', 'Inventory', $currentPage); ?>
                <?php endif; ?>
                <?php navItem('queue.php', '⏳', 'Queue', $currentPage); ?>
                <?php navItem('waitlist.php', '📝', 'Waitlist', $currentPage); ?>
            <?php endif; ?>
            <?php endif; ?>

            <?php if(hasAccess('customers')): ?>
            <?php navItem('customers.php','👥', 'Customers', $currentPage); ?>
            <?php if(in_array($role, ['admin','manager'])): ?>
                <?php navItem('promo_codes.php', '🎟️', 'Promo Codes', $currentPage); ?>
            <?php endif; ?>
            <?php endif; ?>

            <?php if(hasAccess('reports')): ?>
            <div class="nav-section-title">Analytics</div>
            <?php navItem('reports.php', '📈', 'Reports', $currentPage); ?>
            <?php if(in_array($role, ['admin','manager'])): ?>
                <?php navItem('peak_hours.php', '⏰', 'Peak Hours', $currentPage); ?>
                <?php navItem('feedback_analysis.php', '💬', 'Feedback Analysis', $currentPage); ?>
            <?php endif; ?>
            <?php endif; ?>

            <div class="nav-section-title">Account</div>
            <?php navItem('logout.php',  '🚪', 'Logout',   ''); ?>
        </div>

        <div class="sidebar-footer">
            <a href="profile.php" class="user-mini" style="text-decoration:none; color:inherit;">
                <div class="user-avatar"><?= $initials ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($name) ?></div>
                    <div class="user-role"><?= $role ?></div>
                </div>
            </a>
        </div>
    </nav>

    <!-- MAIN -->
    <div class="main-content">
        <header class="topbar">
            <div>
                <button class="btn btn-icon btn-secondary" id="sidebarToggle" style="display:none" title="Toggle Menu">☰</button>
                <div class="topbar-title"><?= $pageTitle ?? 'Frank Restaurant' ?></div>
                <div class="topbar-subtitle"><?= $pageSubtitle ?? date('l, F j, Y') ?></div>
            </div>
            <div class="topbar-actions">
                <?php if($role==='customer'): ?>
                <a href="create_reservation.php" class="btn btn-primary btn-sm glow">+ New Reservation</a>
                <?php elseif(in_array($role,['admin','manager','staff'])): ?>
                <a href="index.php" class="btn btn-secondary btn-sm" target="_blank">🌐 View Website</a>
                <a href="create_reservation.php" class="btn btn-primary btn-sm">+ Reservation</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="page-content">

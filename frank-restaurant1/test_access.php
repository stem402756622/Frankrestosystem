<?php
require_once 'includes/config.php';

// Test access control for different roles
echo "<h2>Access Control Test</h2>";

$testRoles = ['admin', 'manager', 'staff', 'customer'];
$testPages = ['dashboard', 'reservations', 'tables', 'customers', 'orders', 'reports', 'inventory', 'promo_codes', 'admin_menu', 'peak_hours', 'waitlist', 'queue', 'favorites'];

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Role/Page</th>";
foreach ($testPages as $page) {
    echo "<th>$page</th>";
}
echo "</tr>";

foreach ($testRoles as $role) {
    echo "<tr><td><strong>$role</strong></td>";
    foreach ($testPages as $page) {
        $hasAccess = in_array($page, ROLES[$role] ?? []);
        $color = $hasAccess ? 'green' : 'red';
        $symbol = $hasAccess ? '✓' : '✗';
        echo "<td style='color: $color; text-align: center; font-weight: bold;'>$symbol</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h3>Key Restrictions:</h3>";
echo "<ul>";
echo "<li><strong>Staff</strong> can only access: dashboard, reservations, tables, orders, profile, waitlist, queue</li>";
echo "<li><strong>Admin/Manager</strong> can access all features including: customers, reports, inventory, promo_codes, admin_menu, peak_hours</li>";
echo "<li><strong>Customer</strong> can only access: dashboard, reservations, orders, profile, waitlist, favorites</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> Pages like invoice.php and receipt.php are accessible to all logged-in users for order processing.</p>";
?>
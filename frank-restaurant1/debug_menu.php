<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Menu Items Debug</h2>";

// Test database connection
echo "<h3>Database Connection Test</h3>";
try {
    $categories = db()->fetchAll("SELECT * FROM menu_categories ORDER BY sort_order");
    echo "<p>✓ Database connection successful</p>";
    echo "<p>Found " . count($categories) . " categories</p>";
    
    // Test menu items
    $menu_items = db()->fetchAll("SELECT mi.*, mc.name as category_name FROM menu_items mi LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id ORDER BY mc.sort_order, mi.name");
    echo "<p>Found " . count($menu_items) . " menu items</p>";
    
    // Display items
    echo "<h3>Menu Items:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Available</th></tr>";
    
    foreach($menu_items as $item) {
        echo "<tr>";
        echo "<td>" . $item['item_id'] . "</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>" . htmlspecialchars($item['category_name'] ?? 'Uncategorized') . "</td>";
        echo "<td>₱" . number_format($item['price'], 2) . "</td>";
        echo "<td>" . ($item['is_available'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test allergens
    $allergens = db()->fetchAll("SELECT * FROM allergens ORDER BY name");
    echo "<h3>Allergens:</h3>";
    echo "<p>Found " . count($allergens) . " allergens</p>";
    
    if (!empty($allergens)) {
        echo "<ul>";
        foreach($allergens as $allergen) {
            echo "<li>" . htmlspecialchars($allergen['name']) . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>Session Info:</h3>";
echo "<p>User logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "</p>";
if (isLoggedIn()) {
    echo "<p>User role: " . ($_SESSION['role'] ?? 'Unknown') . "</p>";
}
?>

<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>🔍 Menu System Diagnostic</h2>";
echo "<p>Checking why the menu is not working...</p>";

$issues_found = [];

// Check 1: Required tables
echo "<h3>📋 Checking Required Tables</h3>";

$required_tables = ['menu_items', 'menu_categories', 'menu_item_allergens', 'allergens'];
$existing_tables = [];

try {
    $tables = db()->fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
        $existing_tables[] = array_values($table)[0];
    }
} catch (Exception $e) {
    $issues_found[] = "Database connection failed: " . $e->getMessage();
}

foreach ($required_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "<p>✅ $table table exists</p>";
    } else {
        echo "<p>❌ $table table missing</p>";
        $issues_found[] = "Missing table: $table";
    }
}

// Check 2: Menu items data
echo "<h3>🍽️ Checking Menu Items</h3>";

try {
    $menu_items = db()->fetchAll("SELECT COUNT(*) as cnt FROM menu_items WHERE is_available = 1");
    echo "<p>✅ Found " . $menu_items[0]['cnt'] . " available menu items</p>";
    
    if ($menu_items[0]['cnt'] == 0) {
        $issues_found[] = "No available menu items found";
        echo "<p>❌ No available menu items</p>";
    }
} catch (Exception $e) {
    $issues_found[] = "Error querying menu_items: " . $e->getMessage();
    echo "<p>❌ Error querying menu_items: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check 3: Menu categories
echo "<h3>📂 Checking Menu Categories</h3>";

try {
    $categories = db()->fetchAll("SELECT COUNT(*) as cnt FROM menu_categories");
    echo "<p>✅ Found " . $categories[0]['cnt'] . " menu categories</p>";
    
    if ($categories[0]['cnt'] == 0) {
        $issues_found[] = "No menu categories found";
        echo "<p>❌ No menu categories</p>";
    }
} catch (Exception $e) {
    $issues_found[] = "Error querying menu_categories: " . $e->getMessage();
    echo "<p>❌ Error querying menu_categories: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check 4: Test the exact query from menu.php
echo "<h3>🔍 Testing Menu Query</h3>";

try {
    $items = db()->fetchAll(
        "SELECT mi.*, mc.name as category_name, 
         (SELECT GROUP_CONCAT(a.name) FROM menu_item_allergens mia JOIN allergens a ON mia.allergen_id=a.id WHERE mia.menu_item_id=mi.item_id) as allergens
         FROM menu_items mi 
         LEFT JOIN menu_categories mc ON mi.category_id=mc.category_id 
         WHERE mi.is_available = 1 
         ORDER BY mc.sort_order, mi.name"
    );
    echo "<p>✅ Main query successful - found " . count($items) . " items</p>";
    
    if (count($items) > 0) {
        echo "<p>Sample item: " . htmlspecialchars($items[0]['name']) . " (Category: " . htmlspecialchars($items[0]['category_name']) . ")</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Main query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Try fallback query
    try {
        $items = db()->fetchAll(
            "SELECT mi.*, mc.name as category_name, NULL as allergens
             FROM menu_items mi 
             LEFT JOIN menu_categories mc ON mi.category_id=mc.category_id 
             WHERE mi.is_available = 1 
             ORDER BY mc.sort_order, mi.name"
        );
        echo "<p>✅ Fallback query successful - found " . count($items) . " items</p>";
    } catch (Exception $e2) {
        $issues_found[] = "Both menu queries failed: " . $e2->getMessage();
        echo "<p>❌ Fallback query also failed: " . htmlspecialchars($e2->getMessage()) . "</p>";
    }
}

// Check 5: Test menu.php file access
echo "<h3>📄 Checking menu.php File</h3>";

if (file_exists('menu.php')) {
    echo "<p>✅ menu.php file exists</p>";
    
    // Check for syntax errors
    $output = [];
    $return_code = 0;
    exec("php -l menu.php 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "<p>✅ menu.php syntax is valid</p>";
    } else {
        echo "<p>❌ menu.php has syntax errors: " . htmlspecialchars(implode(' ', $output)) . "</p>";
        $issues_found[] = "menu.php syntax errors";
    }
} else {
    echo "<p>❌ menu.php file missing</p>";
    $issues_found[] = "menu.php file missing";
}

// Summary and solutions
echo "<h3>📊 Diagnostic Results</h3>";

if (empty($issues_found)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Menu System Appears Healthy</h3>";
    echo "<p>All required components are working. If you're still experiencing issues, try:</p>";
    echo "<ul>";
    echo "<li>1. Clear browser cache and reload</li>";
    echo "<li>2. Check browser console for JavaScript errors</li>";
    echo "<li>3. Verify you're logged in (if required)</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Issues Found: " . count($issues_found) . "</h3>";
    echo "<h4>Problems:</h4><ul>";
    foreach ($issues_found as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Provide solutions
    echo "<h3>🔧 Suggested Solutions:</h3>";
    
    if (in_array('Missing table: menu_items', $issues_found) || in_array('Missing table: menu_categories', $issues_found)) {
        echo "<p><strong>Create missing tables:</strong></p>";
        echo "<p>Run the table creation script to create missing menu tables.</p>";
    }
    
    if (in_array('No available menu items', $issues_found)) {
        echo "<p><strong>Add menu items:</strong></p>";
        echo "<p>Add some menu items to the database through the admin menu.</p>";
    }
}

echo "<p><a href='menu.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🍽️ Test Menu</a></p>";
echo "<p><a href='admin_menu.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Admin Menu</a></p>";

?>

<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>🛡️ Setting Up Menu Favorites System</h2>";

try {
    // Create saved_favorites table if it doesn't exist
    echo "<h3>Creating saved_favorites table...</h3>";
    
    db()->execute("
        CREATE TABLE IF NOT EXISTS saved_favorites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_menu (user_id, menu_item_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_menu_item_id (menu_item_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ saved_favorites table created successfully</p>";
    
    // Test the favorites functionality
    echo "<h3>Testing favorites system...</h3>";
    
    // Get a test user and menu item
    $test_user = db()->fetchOne("SELECT user_id FROM users LIMIT 1");
    $test_item = db()->fetchOne("SELECT item_id FROM menu_items LIMIT 1");
    
    if ($test_user && $test_item) {
        // Test adding a favorite
        try {
            db()->execute("INSERT IGNORE INTO saved_favorites (user_id, menu_item_id) VALUES (?,?)", [$test_user['user_id'], $test_item['item_id']]);
            echo "<p>✅ Successfully added test favorite</p>";
            
            // Test retrieving favorites
            $favorites = db()->fetchAll(
                "SELECT sf.*, mi.name, mi.price, mi.description, mi.image_url 
                 FROM saved_favorites sf 
                 JOIN menu_items mi ON sf.menu_item_id = mi.item_id 
                 WHERE sf.user_id = ? 
                 ORDER BY sf.created_at DESC",
                [$test_user['user_id']]
            );
            echo "<p>✅ Successfully retrieved " . count($favorites) . " favorites</p>";
            
            // Clean up test data
            db()->execute("DELETE FROM saved_favorites WHERE user_id=? AND menu_item_id=?", [$test_user['user_id'], $test_item['item_id']]);
            echo "<p>✅ Cleaned up test data</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Test failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>⚠️ No users or menu items found for testing</p>";
    }
    
    echo "<h3>✅ Menu Favorites System Ready!</h3>";
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ Features Available:</strong><br>";
    echo "• Menu accessible from customer navigation<br>";
    echo "• Products aligned with order system<br>";
    echo "• Add to favorites functionality<br>";
    echo "• Favorites stored in database<br>";
    echo "• Toggle favorites on/off<br>";
    echo "• View favorites in dedicated page";
    echo "</div>";
    
    echo "<p><strong>How it works:</strong></p>";
    echo "<ul>";
    echo "<li>📋 Customers can browse menu from navigation</li>";
    echo "<li>🛒 Products have quantity selectors for ordering</li>";
    echo "<li>❤️ Click heart icon to add/remove favorites</li>";
    echo "<li>📊 View all favorites in Favorites page</li>";
    echo "<li>🧾 Order directly from menu page</li>";
    echo "</ul>";
    
    echo "<p><a href='menu.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🍽️ Test Menu Now</a></p>";
    echo "<p><a href='favorites.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>❤️ View Favorites</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

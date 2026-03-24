<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Creating Missing saved_favorites Table</h2>";

try {
    // Create saved_favorites table
    $sql = "CREATE TABLE IF NOT EXISTS saved_favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_menu (user_id, menu_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    db()->execute($sql);
    echo "<p style='color: green;'>✅ saved_favorites table created successfully!</p>";
    
    // Add some sample favorites for testing
    $sample_favorites = [
        [1, 1], // user_id 1, menu_item_id 1
        [1, 2], // user_id 1, menu_item_id 2
        [2, 1], // user_id 2, menu_item_id 1
    ];
    
    foreach ($sample_favorites as $favorite) {
        db()->execute("INSERT IGNORE INTO saved_favorites (user_id, menu_item_id) VALUES (?,?)", $favorite);
    }
    
    echo "<p style='color: blue;'>📝 Sample favorites added for testing</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creating table: " . $e->getMessage() . "</p>";
}

echo "<br><a href='favorites.php' class='btn btn-primary'>📋 Go to Favorites</a>";
echo "<br><a href='loyalty.php' class='btn btn-secondary'>🎯 Go to Loyalty</a>";
?>

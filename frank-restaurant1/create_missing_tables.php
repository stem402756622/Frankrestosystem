<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Creating Missing Database Tables</h2>";

try {
    // Check if allergens table exists
    $allergens_check = db()->fetchOne("SHOW TABLES LIKE 'allergens'");
    $menu_item_allergens_check = db()->fetchOne("SHOW TABLES LIKE 'menu_item_allergens'");
    $queue_check = db()->fetchOne("SHOW TABLES LIKE 'queue'");
    $waitlist_check = db()->fetchOne("SHOW TABLES LIKE 'waitlist'");
    
    if (!$allergens_check) {
        echo "<h3>Creating allergens table...</h3>";
        db()->execute("
            CREATE TABLE allergens (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL UNIQUE
            )
        ");
        echo "<p>✓ allergens table created</p>";
    } else {
        echo "<p>✓ allergens table already exists</p>";
    }
    
    if (!$menu_item_allergens_check) {
        echo "<h3>Creating menu_item_allergens table...</h3>";
        db()->execute("
            CREATE TABLE menu_item_allergens (
                menu_item_id INT NOT NULL,
                allergen_id INT NOT NULL,
                PRIMARY KEY (menu_item_id, allergen_id),
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
                FOREIGN KEY (allergen_id) REFERENCES allergens(id) ON DELETE CASCADE
            )
        ");
        echo "<p>✓ menu_item_allergens table created</p>";
    } else {
        echo "<p>✓ menu_item_allergens table already exists</p>";
    }
    
    if (!$queue_check) {
        echo "<h3>Creating queue table...</h3>";
        db()->execute("
            CREATE TABLE queue (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                party_size INT NOT NULL,
                status ENUM('waiting','seated','left') DEFAULT 'waiting',
                queue_number INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                seated_at TIMESTAMP NULL
            )
        ");
        echo "<p>✓ queue table created</p>";
    } else {
        echo "<p>✓ queue table already exists</p>";
    }
    
    if (!$waitlist_check) {
        echo "<h3>Creating waitlist table...</h3>";
        db()->execute("
            CREATE TABLE waitlist (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                party_size INT NOT NULL,
                requested_date DATE NOT NULL,
                requested_time TIME NOT NULL,
                status ENUM('waiting','notified','converted','cancelled') DEFAULT 'waiting',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✓ waitlist table created</p>";
    } else {
        echo "<p>✓ waitlist table already exists</p>";
    }
    
    // Insert allergen data if table is empty
    $allergen_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM allergens")['cnt'];
    if ($allergen_count == 0) {
        echo "<h3>Inserting allergen data...</h3>";
        $allergens = [
            'Nuts', 'Gluten', 'Dairy', 'Shellfish', 'Soy', 'Eggs', 'Fish', 'Wheat', 
            'Peanuts', 'Almonds', 'Tree Nuts', 'Milk', 'Lactose', 'Sesame', 'Mustard'
        ];
        
        foreach ($allergens as $allergen) {
            db()->execute("INSERT IGNORE INTO allergens (name) VALUES (?)", [$allergen]);
        }
        echo "<p>✓ Allergen data inserted</p>";
    } else {
        echo "<p>✓ Allergen data already exists ($allergen_count records)</p>";
    }
    
    // Verify tables exist
    echo "<h3>Verifying tables...</h3>";
    $allergen_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM allergens")['cnt'];
    $junction_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM menu_item_allergens")['cnt'];
    $queue_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM queue")['cnt'];
    $waitlist_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM waitlist")['cnt'];
    
    echo "<p>Allergens table has $allergen_count records</p>";
    echo "<p>Menu_item_allergens table has $junction_count records</p>";
    echo "<p>Queue table has $queue_count records</p>";
    echo "<p>Waitlist table has $waitlist_count records</p>";
    
    echo "<h3>✅ Success! All missing tables have been created.</h3>";
    echo "<p><a href='orders.php'>Go to Orders page</a></p>";
    echo "<p><a href='admin_menu.php'>Go to Admin Menu page</a></p>";
    echo "<p><a href='menu.php'>Go to Menu page</a></p>";
    echo "<p><a href='queue.php'>Go to Queue page</a></p>";
    echo "<p><a href='waitlist.php'>Go to Waitlist page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>

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
    $inventory_items_check = db()->fetchOne("SHOW TABLES LIKE 'inventory_items'");
    $inventory_transactions_check = db()->fetchOne("SHOW TABLES LIKE 'inventory_transactions'");
    $menu_item_ingredients_check = db()->fetchOne("SHOW TABLES LIKE 'menu_item_ingredients'");
    $promo_codes_check = db()->fetchOne("SHOW TABLES LIKE 'promo_codes'");
    $peak_hours_check = db()->fetchOne("SHOW TABLES LIKE 'peak_hours'");
    
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
    
    if (!$inventory_items_check) {
        echo "<h3>Creating inventory_items table...</h3>";
        db()->execute("
            CREATE TABLE inventory_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                unit VARCHAR(20) NOT NULL,
                quantity DECIMAL(10,2) DEFAULT 0,
                low_stock_threshold DECIMAL(10,2) DEFAULT 10,
                cost_per_unit DECIMAL(10,2) DEFAULT 0,
                supplier VARCHAR(100),
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✓ inventory_items table created</p>";
    } else {
        echo "<p>✓ inventory_items table already exists</p>";
    }
    
    if (!$inventory_transactions_check) {
        echo "<h3>Creating inventory_transactions table...</h3>";
        db()->execute("
            CREATE TABLE inventory_transactions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                item_id INT NOT NULL,
                type ENUM('restock','used','waste','adjustment') NOT NULL,
                quantity DECIMAL(10,2) NOT NULL,
                notes TEXT,
                user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            )
        ");
        echo "<p>✓ inventory_transactions table created</p>";
    } else {
        echo "<p>✓ inventory_transactions table already exists</p>";
    }
    
    if (!$menu_item_ingredients_check) {
        echo "<h3>Creating menu_item_ingredients table...</h3>";
        db()->execute("
            CREATE TABLE menu_item_ingredients (
                menu_item_id INT NOT NULL,
                inventory_item_id INT NOT NULL,
                quantity_needed DECIMAL(10,2) NOT NULL,
                PRIMARY KEY (menu_item_id, inventory_item_id),
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
                FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
            )
        ");
        echo "<p>✓ menu_item_ingredients table created</p>";
    } else {
        echo "<p>✓ menu_item_ingredients table already exists</p>";
    }
    
    if (!$promo_codes_check) {
        echo "<h3>Creating promo_codes table...</h3>";
        db()->execute("
            CREATE TABLE promo_codes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                code VARCHAR(50) UNIQUE NOT NULL,
                discount_type ENUM('percentage','fixed') NOT NULL,
                discount_value DECIMAL(10,2) NOT NULL,
                min_order_amount DECIMAL(10,2) DEFAULT 0,
                max_uses INT DEFAULT 0,
                used_count INT DEFAULT 0,
                valid_from DATE DEFAULT NULL,
                valid_until DATE DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✓ promo_codes table created</p>";
    } else {
        echo "<p>✓ promo_codes table already exists</p>";
    }
    
    if (!$peak_hours_check) {
        echo "<h3>Creating peak_hours table...</h3>";
        db()->execute("
            CREATE TABLE peak_hours (
                id INT PRIMARY KEY AUTO_INCREMENT,
                day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                max_bookings_per_slot INT DEFAULT 5,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✓ peak_hours table created</p>";
    } else {
        echo "<p>✓ peak_hours table already exists</p>";
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
    
    // Insert sample inventory data if table is empty
    $inventory_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM inventory_items")['cnt'];
    if ($inventory_count == 0) {
        echo "<h3>Inserting sample inventory data...</h3>";
        $sample_items = [
            ['Tomatoes', 'kg', 50, 10, 120, 'Local Farm'],
            ['Flour', 'kg', 100, 20, 45, 'Bakery Supply Co'],
            ['Olive Oil', 'L', 25, 5, 180, 'Italian Imports'],
            ['Chicken Breast', 'kg', 30, 10, 250, 'Premium Meats'],
            ['Lettuce', 'kg', 15, 5, 80, 'Green Valley Farms']
        ];
        
        foreach ($sample_items as $item) {
            db()->execute("INSERT INTO inventory_items (name, unit, quantity, low_stock_threshold, cost_per_unit, supplier) VALUES (?,?,?,?,?,?)", $item);
        }
        echo "<p>✓ Sample inventory data inserted</p>";
    } else {
        echo "<p>✓ Inventory data already exists ($inventory_count records)</p>";
    }
    
    // Insert sample promo code if table is empty
    $promo_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM promo_codes")['cnt'];
    if ($promo_count == 0) {
        echo "<h3>Inserting sample promo code...</h3>";
        db()->execute("INSERT INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_uses, valid_from, valid_until) VALUES (?,?,?,?,?,?,?)", 
            ['WELCOME2025', 'percentage', 15.00, 100.00, 50, date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]);
        echo "<p>✓ Sample promo code inserted</p>";
    } else {
        echo "<p>✓ Promo codes already exists ($promo_count records)</p>";
    }
    
    // Insert sample peak hours if table is empty
    $peak_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM peak_hours")['cnt'];
    if ($peak_count == 0) {
        echo "<h3>Inserting sample peak hours...</h3>";
        $sample_peak_hours = [
            [5, '18:00:00', '22:00:00', 10], // Friday 6-10pm
            [6, '18:00:00', '22:00:00', 8],  // Saturday 6-10pm
        ];
        
        foreach ($sample_peak_hours as $peak) {
            db()->execute("INSERT INTO peak_hours (day_of_week, start_time, end_time, max_bookings_per_slot) VALUES (?,?,?,?)", $peak);
        }
        echo "<p>✓ Sample peak hours inserted</p>";
    } else {
        echo "<p>✓ Peak hours already exists ($peak_count records)</p>";
    }
    
    // Verify tables exist
    echo "<h3>Verifying tables...</h3>";
    $allergen_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM allergens")['cnt'];
    $junction_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM menu_item_allergens")['cnt'];
    $queue_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM queue")['cnt'];
    $waitlist_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM waitlist")['cnt'];
    $inventory_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM inventory_items")['cnt'];
    $transactions_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM inventory_transactions")['cnt'];
    $promo_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM promo_codes")['cnt'];
    $peak_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM peak_hours")['cnt'];
    
    echo "<p>Allergens table has $allergen_count records</p>";
    echo "<p>Menu_item_allergens table has $junction_count records</p>";
    echo "<p>Queue table has $queue_count records</p>";
    echo "<p>Waitlist table has $waitlist_count records</p>";
    echo "<p>Inventory_items table has $inventory_count records</p>";
    echo "<p>Inventory_transactions table has $transactions_count records</p>";
    echo "<p>Promo_codes table has $promo_count records</p>";
    echo "<p>Peak_hours table has $peak_count records</p>";
    
    echo "<h3>✅ Success! All missing tables have been created.</h3>";
    echo "<p><a href='orders.php'>Go to Orders page</a></p>";
    echo "<p><a href='admin_menu.php'>Go to Admin Menu page</a></p>";
    echo "<p><a href='menu.php'>Go to Menu page</a></p>";
    echo "<p><a href='queue.php'>Go to Queue page</a></p>";
    echo "<p><a href='waitlist.php'>Go to Waitlist page</a></p>";
    echo "<p><a href='inventory.php'>Go to Inventory page</a></p>";
    echo "<p><a href='promo_codes.php'>Go to Promo Codes page</a></p>";
    echo "<p><a href='peak_hours.php'>Go to Peak Hours page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>

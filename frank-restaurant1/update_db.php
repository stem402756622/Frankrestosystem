<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $pdo = db()->getPdo();
    
    // Feature 1: Add reschedule_count to reservations table
    $stmt = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'reschedule_count'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN reschedule_count INT DEFAULT 0 AFTER status");
        echo "Added reschedule_count to reservations table.\n";
    } else {
        echo "reschedule_count already exists in reservations table.\n";
    }

    // Feature 2: Add is_reminded to reservations table
    $stmt = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'is_reminded'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN is_reminded TINYINT(1) DEFAULT 0");
        echo "Added is_reminded to reservations table.\n";
    } else {
        echo "is_reminded already exists in reservations table.\n";
    }

    // Feature 3: Create waitlist table
    $pdo->exec("CREATE TABLE IF NOT EXISTS waitlist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        party_size INT NOT NULL,
        requested_date DATE NOT NULL,
        requested_time TIME NOT NULL,
        status ENUM('waiting','notified','converted','cancelled') DEFAULT 'waiting',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created waitlist table.\n";

    // Feature 5: Peak Hours
    $pdo->exec("CREATE TABLE IF NOT EXISTS peak_hours (
        id INT PRIMARY KEY AUTO_INCREMENT,
        day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        max_bookings_per_slot INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created peak_hours table.\n";

    // Feature 6: Allergies
    $pdo->exec("CREATE TABLE IF NOT EXISTS allergens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL UNIQUE
    )");
    
    // Insert common allergens
    $pdo->exec("INSERT IGNORE INTO allergens (name) VALUES 
        ('Nuts'), ('Gluten'), ('Dairy'), ('Shellfish'), ('Soy'), ('Eggs'), ('Fish'), ('Wheat')");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_item_allergens (
        menu_item_id INT NOT NULL,
        allergen_id INT NOT NULL,
        PRIMARY KEY (menu_item_id, allergen_id),
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
        FOREIGN KEY (allergen_id) REFERENCES allergens(id) ON DELETE CASCADE
    )");
    
    // Add dietary tags column
    $stmt = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'dietary_tags'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN dietary_tags SET('Vegetarian','Vegan','Gluten-Free','Halal','Spicy') DEFAULT NULL");
        echo "Added dietary_tags to menu_items.\n";
    }
    
    echo "Created allergens tables.\n";

    // Feature 7: Reviews
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        order_id INT NOT NULL,
        menu_item_id INT DEFAULT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        is_approved TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    echo "Created reviews table.\n";

    // Feature 8: Favorites
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_favorites (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_item (user_id, menu_item_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
    )");
    echo "Created favorites table.\n";

    // Feature 9: Promo Codes
    $pdo->exec("CREATE TABLE IF NOT EXISTS promo_codes (
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
    )");
    
    // Add discount columns to orders
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount_amount'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER tax");
        $pdo->exec("ALTER TABLE orders ADD COLUMN promo_code_id INT DEFAULT NULL AFTER discount_amount");
        echo "Added discount columns to orders.\n";
    }

    // Feature 12: Invoices
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        invoice_number VARCHAR(50) UNIQUE NOT NULL,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        total_amount DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) NOT NULL,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        status ENUM('unpaid','paid','voided') DEFAULT 'unpaid',
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
    )");
    echo "Created invoices table.\n";

    // Feature 13 & 14: Inventory
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        quantity DECIMAL(10,2) DEFAULT 0,
        low_stock_threshold DECIMAL(10,2) DEFAULT 10,
        cost_per_unit DECIMAL(10,2) DEFAULT 0,
        supplier VARCHAR(100),
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        item_id INT NOT NULL,
        type ENUM('restock','used','waste','adjustment') NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        notes TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_item_ingredients (
        menu_item_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        quantity_needed DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (menu_item_id, inventory_item_id),
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
    )");
    echo "Created inventory tables.\n";

    // Feature 16: Queue
    $pdo->exec("CREATE TABLE IF NOT EXISTS queue (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        party_size INT NOT NULL,
        status ENUM('waiting','seated','left') DEFAULT 'waiting',
        queue_number INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        seated_at TIMESTAMP NULL
    )");
    echo "Created queue table.\n";
    
    // Feature 17: Feedback
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT DEFAULT NULL,
        name VARCHAR(100),
        email VARCHAR(100),
        overall_rating INT NOT NULL CHECK (overall_rating >= 1 AND overall_rating <= 5),
        food_rating INT,
        service_rating INT,
        ambiance_rating INT,
        comment TEXT,
        is_published TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created feedback table.\n";

    echo "Database update completed successfully.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>

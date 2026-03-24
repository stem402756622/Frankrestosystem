<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$results = [];
$errors = [];

try {
    // Feature 1: Reschedule Limits
    $stmt = db()->execute("ALTER TABLE reservations ADD COLUMN IF NOT EXISTS reschedule_count INT DEFAULT 0 AFTER status");
    $results[] = "✅ Added reschedule_count to reservations table";
    
    // Feature 2: Email Notifications
    $stmt = db()->execute("ALTER TABLE reservations ADD COLUMN IF NOT EXISTS is_reminded TINYINT(1) DEFAULT 0");
    $results[] = "✅ Added is_reminded to reservations table";
    
    // Feature 3: Waitlist
    db()->execute("
        CREATE TABLE IF NOT EXISTS waitlist (
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
    $results[] = "✅ Created waitlist table";
    
    // Feature 5: Peak Hours
    db()->execute("
        CREATE TABLE IF NOT EXISTS peak_hours (
            id INT PRIMARY KEY AUTO_INCREMENT,
            day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            max_bookings_per_slot INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $results[] = "✅ Created peak_hours table";
    
    // Feature 6: Allergies & Dietary
    db()->execute("
        CREATE TABLE IF NOT EXISTS allergens (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE
        )
    ");
    
    // Insert common allergens
    db()->execute("
        INSERT IGNORE INTO allergens (name) VALUES 
        ('Nuts'), ('Gluten'), ('Dairy'), ('Shellfish'), ('Soy'), ('Eggs'), ('Fish'), ('Wheat')
    ");
    
    db()->execute("
        CREATE TABLE IF NOT EXISTS menu_item_allergens (
            menu_item_id INT NOT NULL,
            allergen_id INT NOT NULL,
            PRIMARY KEY (menu_item_id, allergen_id),
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
            FOREIGN KEY (allergen_id) REFERENCES allergens(id) ON DELETE CASCADE
        )
    ");
    
    db()->execute("ALTER TABLE menu_items ADD COLUMN IF NOT EXISTS dietary_tags SET('Vegetarian','Vegan','Gluten-Free','Halal','Spicy') DEFAULT NULL");
    $results[] = "✅ Created allergens tables and added dietary_tags";
    
    // Feature 7: Reviews
    db()->execute("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            order_id INT NOT NULL,
            menu_item_id INT DEFAULT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            is_approved TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    $results[] = "✅ Created reviews table";
    
    // Feature 8: Favorites
    db()->execute("
        CREATE TABLE IF NOT EXISTS saved_favorites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_item (user_id, menu_item_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE
        )
    ");
    $results[] = "✅ Created saved_favorites table";
    
    // Feature 9: Promo Codes
    db()->execute("
        CREATE TABLE IF NOT EXISTS promo_codes (
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
    
    db()->execute("ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER tax");
    db()->execute("ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_code_id INT DEFAULT NULL AFTER discount_amount");
    $results[] = "✅ Created promo_codes table and added discount columns";
    
    // Feature 12: Invoices
    db()->execute("
        CREATE TABLE IF NOT EXISTS invoices (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            invoice_number VARCHAR(50) UNIQUE NOT NULL,
            issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            total_amount DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) NOT NULL,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            status ENUM('unpaid','paid','voided') DEFAULT 'unpaid',
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
        )
    ");
    $results[] = "✅ Created invoices table";
    
    // Feature 13 & 14: Inventory Management
    db()->execute("
        CREATE TABLE IF NOT EXISTS inventory_items (
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
    
    db()->execute("
        CREATE TABLE IF NOT EXISTS inventory_transactions (
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
    
    db()->execute("
        CREATE TABLE IF NOT EXISTS menu_item_ingredients (
            menu_item_id INT NOT NULL,
            inventory_item_id INT NOT NULL,
            quantity_needed DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (menu_item_id, inventory_item_id),
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
            FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        )
    ");
    $results[] = "✅ Created inventory tables";
    
    // Feature 16: Queue
    db()->execute("
        CREATE TABLE IF NOT EXISTS queue (
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
    $results[] = "✅ Created queue table";
    
    // Feature 17: Feedback
    db()->execute("
        CREATE TABLE IF NOT EXISTS feedback (
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
        )
    ");
    $results[] = "✅ Created feedback table";
    
    // Add sample data for testing
    db()->execute("
        INSERT IGNORE INTO peak_hours (day_of_week, start_time, end_time, max_bookings_per_slot) VALUES 
        (5, '18:00:00', '21:00:00', 3), -- Friday 6-9 PM
        (6, '19:00:00', '22:00:00', 4)  -- Saturday 7-10 PM
    ");
    
    db()->execute("
        INSERT IGNORE INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_uses) VALUES 
        ('WELCOME10', 'percentage', 10.00, 0, 100),
        ('SAVE5', 'fixed', 5.00, 50.00, 50)
    ");
    
    db()->execute("
        INSERT IGNORE INTO inventory_items (name, unit, quantity, low_stock_threshold, cost_per_unit, supplier) VALUES 
        ('Chicken Breast', 'kg', 50.00, 10.00, 8.50, 'Premium Meats'),
        ('Beef Tenderloin', 'kg', 25.00, 5.00, 25.00, 'Prime Cuts'),
        ('Salmon Fillet', 'kg', 20.00, 5.00, 18.00, 'Fresh Seafood Co'),
        ('Olive Oil', 'L', 10.00, 2.00, 12.00, 'Mediterranean Imports'),
        ('Tomatoes', 'kg', 30.00, 5.00, 2.50, 'Local Farms')
    ");
    
    $results[] = "✅ Added sample data for testing";
    
} catch (Exception $e) {
    $errors[] = "❌ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Frank Restaurant - Database Update</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .log { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🍽️ Frank Restaurant Database Update</h1>
    
    <?php if (empty($errors)): ?>
        <div class="log">
            <h3>✅ Database Update Completed Successfully!</h3>
            <ul>
                <?php foreach ($results as $result): ?>
                    <li><?= htmlspecialchars($result) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div style="text-align: center;">
            <a href="dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    <?php else: ?>
        <div class="log">
            <h3>❌ Database Update Failed</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p>Please check your database connection and try again.</p>
    <?php endif; ?>
    
    <hr style="margin: 30px 0;">
    
    <h3>📋 What was updated:</h3>
    <ul>
        <li>✅ Reschedule limits tracking</li>
        <li>✅ Email notification flags</li>
        <li>✅ Waitlist management system</li>
        <li>✅ Peak hour restrictions</li>
        <li>✅ Allergen and dietary tracking</li>
        <li>✅ Review and rating system</li>
        <li>✅ Saved favorites functionality</li>
        <li>✅ Promo codes and discounts</li>
        <li>✅ Invoice generation system</li>
        <li>✅ Inventory management</li>
        <li>✅ Queue management system</li>
        <li>✅ Customer feedback system</li>
    </ul>
    
    <p><strong>Sample data has been added for testing:</strong></p>
    <ul>
        <li>Peak hours: Friday 6-9 PM, Saturday 7-10 PM</li>
        <li>Promo codes: WELCOME10 (10% off), SAVE5 (₱5 off)</li>
        <li>Inventory items: Chicken, Beef, Salmon, Olive Oil, Tomatoes</li>
    </ul>
</body>
</html>
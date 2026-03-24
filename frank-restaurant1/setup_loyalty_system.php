<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>🎯 Setting Up Loyalty Points System</h2>";

try {
    // Check if loyalty_points column exists in users table
    echo "<h3>Checking users table structure...</h3>";
    $columns = db()->fetchAll("SHOW COLUMNS FROM users");
    $has_loyalty_points = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'loyalty_points') {
            $has_loyalty_points = true;
            break;
        }
    }
    
    if (!$has_loyalty_points) {
        echo "<p>Adding loyalty_points column to users table...</p>";
        db()->execute("ALTER TABLE users ADD COLUMN loyalty_points INT DEFAULT 0 AFTER vip_status");
        echo "<p>✅ loyalty_points column added</p>";
    } else {
        echo "<p>✅ loyalty_points column already exists</p>";
    }
    
    // Create loyalty_points_transactions table
    echo "<h3>Creating loyalty transactions table...</h3>";
    db()->execute("
        CREATE TABLE IF NOT EXISTS loyalty_points_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            points INT NOT NULL,
            transaction_type ENUM('earned', 'redeemed') NOT NULL,
            reference_type ENUM('reservation', 'order', 'manual_adjustment') NOT NULL,
            reference_id INT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_transaction_type (transaction_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ loyalty_points_transactions table created</p>";
    
    // Create loyalty_rewards table for point redemption
    echo "<h3>Creating loyalty rewards table...</h3>";
    db()->execute("
        CREATE TABLE IF NOT EXISTS loyalty_rewards (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            points_required INT NOT NULL,
            reward_type ENUM('discount_percentage', 'discount_fixed', 'free_item', 'free_delivery') NOT NULL,
            reward_value DECIMAL(10,2) DEFAULT NULL,
            reward_item_id INT DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            max_uses_per_customer INT DEFAULT NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reward_item_id) REFERENCES menu_items(item_id) ON DELETE SET NULL,
            INDEX idx_is_active (is_active),
            INDEX idx_points_required (points_required)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ loyalty_rewards table created</p>";
    
    // Add sample loyalty rewards
    $reward_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM loyalty_rewards WHERE is_active = 1")['cnt'];
    if ($reward_count == 0) {
        echo "<h3>Adding sample loyalty rewards...</h3>";
        
        $sample_rewards = [
            [
                'name' => '5% Off Your Next Order',
                'description' => 'Get 5% discount on your next order',
                'points_required' => 100,
                'reward_type' => 'discount_percentage',
                'reward_value' => 5
            ],
            [
                'name' => '₱50 Off Your Order',
                'description' => 'Get ₱50 discount on your order',
                'points_required' => 200,
                'reward_type' => 'discount_fixed',
                'reward_value' => 50
            ],
            [
                'name' => 'Free Drink',
                'description' => 'Get any drink for free',
                'points_required' => 150,
                'reward_type' => 'free_item',
                'reward_value' => null
            ],
            [
                'name' => '10% Off Your Next Order',
                'description' => 'Get 10% discount on your next order',
                'points_required' => 300,
                'reward_type' => 'discount_percentage',
                'reward_value' => 10
            ]
        ];
        
        foreach ($sample_rewards as $reward) {
            db()->insert(
                "INSERT INTO loyalty_rewards (name, description, points_required, reward_type, reward_value) VALUES (?,?,?,?,?)",
                [
                    $reward['name'],
                    $reward['description'],
                    $reward['points_required'],
                    $reward['reward_type'],
                    $reward['reward_value']
                ]
            );
        }
        echo "<p>✅ Added 4 sample loyalty rewards</p>";
    }
    
    // Test the system
    echo "<h3>Testing loyalty system...</h3>";
    
    // Get a test user
    $test_user = db()->fetchOne("SELECT user_id, loyalty_points FROM users LIMIT 1");
    if ($test_user) {
        echo "<p>Test user: {$test_user['user_id']} - Current points: {$test_user['loyalty_points']}</p>";
        
        // Test earning points
        $points_to_earn = 10;
        db()->execute("UPDATE users SET loyalty_points = loyalty_points + ? WHERE user_id = ?", [$points_to_earn, $test_user['user_id']]);
        
        // Record transaction
        db()->insert(
            "INSERT INTO loyalty_points_transactions (user_id, points, transaction_type, reference_type, description) VALUES (?,?,?,?,?)",
            [$test_user['user_id'], $points_to_earn, 'earned', 'manual_adjustment', 'Test points addition']
        );
        
        echo "<p>✅ Added {$points_to_earn} test points</p>";
        
        // Check available rewards
        $available_rewards = db()->fetchAll(
            "SELECT * FROM loyalty_rewards WHERE is_active = 1 AND points_required <= ? ORDER BY points_required ASC",
            [$test_user['loyalty_points'] + $points_to_earn]
        );
        
        echo "<p>✅ Found " . count($available_rewards) . " rewards available for redemption</p>";
    }
    
    echo "<h3>✅ Loyalty Points System Ready!</h3>";
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ Features Implemented:</strong><br>";
    echo "• Loyalty points tracking for customers<br>";
    echo "• Points earned for reservations and orders<br>";
    echo "• Points redemption system with rewards<br>";
    echo "• Transaction history tracking<br>";
    echo "• Multiple reward types (discounts, free items)<br>";
    echo "• Sample rewards for testing";
    echo "</div>";
    
    echo "<p><strong>Points System:</strong></p>";
    echo "<ul>";
    echo "<li>📅 Reservations: 10 points</li>";
    echo "<li>🍽️ Orders: 1 point per ₱10 spent</li>";
    echo "<li>🎁 Redemption: Various rewards available</li>";
    echo "<li>📊 Tracking: Full transaction history</li>";
    echo "</ul>";
    
    echo "<p><a href='create_reservation.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🎯 Test Loyalty System</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

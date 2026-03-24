<?php
$pageTitle = 'Loyalty Rewards';
$pageSubtitle = 'Earn points and redeem rewards';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// Get user's current points and transaction history
try {
    $user = db()->fetchOne("SELECT loyalty_points FROM users WHERE user_id = ?", [$user_id]);
    $current_points = $user['loyalty_points'] ?? 0;
} catch (Exception $e) {
    // Handle missing loyalty_points column
    $current_points = 0;
    // Try to add the column
    try {
        db()->execute("ALTER TABLE users ADD COLUMN loyalty_points INT DEFAULT 0");
        $user = db()->fetchOne("SELECT loyalty_points FROM users WHERE user_id = ?", [$user_id]);
        $current_points = $user['loyalty_points'] ?? 0;
    } catch (Exception $e2) {
        // Column already exists or other error
    }
}

// Get recent transactions
try {
    $transactions = db()->fetchAll(
        "SELECT lpt.*, 
         CASE 
            WHEN lpt.reference_type = 'reservation' THEN CONCAT('Reservation #', lpt.reference_id)
            WHEN lpt.reference_type = 'order' THEN CONCAT('Order #', lpt.reference_id)
            ELSE lpt.description
         END as reference_display,
         lpt.created_at
         FROM loyalty_points_transactions lpt
         WHERE lpt.user_id = ?
         ORDER BY lpt.created_at DESC
         LIMIT 10",
        [$user_id]
    );
} catch (Exception $e) {
    $transactions = [];
    // Create the table if it doesn't exist
    try {
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
    } catch (Exception $e2) {
        // Table creation failed
    }
}

// Get available rewards
try {
    $available_rewards = db()->fetchAll(
        "SELECT * FROM loyalty_rewards 
         WHERE is_active = 1 
         AND (expires_at IS NULL OR expires_at > NOW())
         ORDER BY points_required ASC"
    );
} catch (Exception $e) {
    $available_rewards = [];
    // Create the table if it doesn't exist
    try {
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
        
        // Add sample rewards if table was just created
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
        
        // Try to get rewards again
        $available_rewards = db()->fetchAll(
            "SELECT * FROM loyalty_rewards 
             WHERE is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY points_required ASC"
        );
    } catch (Exception $e2) {
        // Table creation failed
    }
}

// Get menu items with points pricing
try {
    $menu_items_with_points = db()->fetchAll(
        "SELECT mi.*, mc.name as category_name,
         CASE 
            WHEN mi.price <= 50 THEN 5
            WHEN mi.price <= 100 THEN 10
            WHEN mi.price <= 200 THEN 20
            WHEN mi.price <= 500 THEN 50
            ELSE 100
         END as points_required
         FROM menu_items mi 
         LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id 
         WHERE mi.is_available = 1 
         ORDER BY mc.sort_order, mi.name
         LIMIT 20"
    );
} catch (Exception $e) {
    $menu_items_with_points = [];
}

// Get user's redemption history
try {
    $redemption_history = db()->fetchAll(
        "SELECT lpt.*, lr.name as reward_name
         FROM loyalty_points_transactions lpt
         LEFT JOIN loyalty_rewards lr ON lpt.description LIKE CONCAT('%Redeemed reward: ', lr.name, '%')
         WHERE lpt.user_id = ? AND lpt.transaction_type = 'redeemed'
         ORDER BY lpt.created_at DESC
         LIMIT 5",
        [$user_id]
    );
} catch (Exception $e) {
    $redemption_history = [];
}

// Get points statistics for summary
try {
    $points_earned = db()->fetchOne(
        "SELECT COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as earned,
             COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as redeemed
             FROM loyalty_points_transactions WHERE user_id = ?",
        [$user_id]
    );
} catch (Exception $e) {
    $points_earned = ['earned' => 0, 'redeemed' => 0];
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">🎯 Your Points</h3>
            </div>
            <div class="card-body text-center">
                <div class="points-display">
                    <div class="points-number"><?= number_format($current_points) ?></div>
                    <div class="points-label">Available Points</div>
                </div>
                
                <div class="points-info mt-3">
                    <div class="info-item">
                        <span class="info-label">📅 Reservations:</span>
                        <span class="info-value">+10 points each</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">🍽️ Orders:</span>
                        <span class="info-value">+1 point per ₱10</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card animate-in mt-3">
            <div class="card-header">
                <h4 class="card-title">📊 Quick Stats</h4>
            </div>
            <div class="card-body">
                <?php
                $points_earned = db()->fetchOne(
                    "SELECT COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as earned,
                     COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as redeemed
                     FROM loyalty_points_transactions WHERE user_id = ?",
                    [$user_id]
                );
                ?>
                <div class="stat-item">
                    <span class="stat-label">Total Earned:</span>
                    <span class="stat-value text-success"><?= number_format($points_earned['earned']) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Redeemed:</span>
                    <span class="stat-value text-danger"><?= number_format($points_earned['redeemed']) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Net Points:</span>
                    <span class="stat-value text-primary"><?= number_format($current_points) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Available Rewards -->
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">🎁 Available Rewards</h3>
            </div>
            <div class="card-body">
                <div class="rewards-grid">
                    <?php foreach($available_rewards as $reward): ?>
                    <div class="reward-card <?= $current_points >= $reward['points_required'] ? 'available' : 'locked' ?>">
                        <div class="reward-header">
                            <div class="reward-name"><?= htmlspecialchars($reward['name']) ?></div>
                            <div class="reward-points"><?= $reward['points_required'] ?> points</div>
                        </div>
                        <div class="reward-body">
                            <div class="reward-description"><?= htmlspecialchars($reward['description']) ?></div>
                            <div class="reward-type">
                                <?php
                                $type_labels = [
                                    'discount_percentage' => '📊 Percentage Discount',
                                    'discount_fixed' => '💰 Fixed Discount',
                                    'free_item' => '🎁 Free Item',
                                    'free_delivery' => '🚚 Free Delivery'
                                ];
                                echo $type_labels[$reward['reward_type']] ?? '🎁 Reward';
                                ?>
                            </div>
                            <?php if ($reward['reward_value']): ?>
                            <div class="reward-value">
                                <?php
                                if ($reward['reward_type'] === 'discount_percentage') {
                                    echo $reward['reward_value'] . '% off';
                                } elseif ($reward['reward_type'] === 'discount_fixed') {
                                    echo '₱' . number_format($reward['reward_value']) . ' off';
                                } elseif ($reward['reward_type'] === 'free_item') {
                                    echo 'Free item';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="reward-footer">
                            <?php if ($current_points >= $reward['points_required']): ?>
                                <button class="btn btn-primary btn-sm" onclick="redeemReward(<?= $reward['id'] ?>)">
                                    🎁 Redeem Now
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    🔒 Need <?= $reward['points_required'] - $current_points ?> more points
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Menu Items with Points -->
        <div class="card animate-in mt-3">
            <div class="card-header">
                <h3 class="card-title">🍽️ Menu Items (Points Pricing)</h3>
            </div>
            <div class="card-body">
                <div class="menu-items-grid">
                    <?php foreach($menu_items_with_points as $item): ?>
                    <div class="menu-item-card">
                        <div class="menu-item-header">
                            <div class="menu-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="menu-item-price">₱<?= number_format($item['price'], 2) ?></div>
                        </div>
                        <div class="menu-item-body">
                            <div class="menu-item-description"><?= htmlspecialchars($item['description']) ?></div>
                            <div class="menu-item-category"><?= htmlspecialchars($item['category_name']) ?></div>
                        </div>
                        <div class="menu-item-footer">
                            <div class="points-gained">
                                🎯 +<?= intval($item['price'] / 10) ?> points earned
                            </div>
                            <div class="points-required">
                                🎁 <?= $item['points_required'] ?> points to redeem
                            </div>
                            <a href="favorites.php?action=add&item_id=<?= $item['item_id'] ?>" class="btn btn-outline-primary btn-sm">
                                ❤️ Add to Favorites
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Products with Points Gained Details -->
        <div class="card animate-in mt-3">
            <div class="card-header">
                <h3 class="card-title">📊 Products & Points Analysis</h3>
            </div>
            <div class="card-body">
                <div class="points-analysis">
                    <div class="analysis-header">
                        <h4>💰 How Points Are Calculated</h4>
                        <p class="text-muted">Earn 1 point for every ₱10 spent on menu items</p>
                    </div>
                    
                    <div class="products-table-container">
                        <table class="products-points-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Points Earned</th>
                                    <th>Points to Redeem</th>
                                    <th>Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($menu_items_with_points as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-name">
                                            <span class="product-icon"><?= $food_icons[$item['name']] ?? '🍽️' ?></span>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?= htmlspecialchars($item['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="price-tag">₱<?= number_format($item['price'], 2) ?></span>
                                    </td>
                                    <td>
                                        <span class="points-earned">+<?= intval($item['price'] / 10) ?></span>
                                    </td>
                                    <td>
                                        <span class="points-cost"><?= $item['points_required'] ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $value_ratio = $item['points_required'] > 0 ? ($item['price'] / $item['points_required']) * 100 : 0;
                                        $value_class = $value_ratio >= 2 ? 'excellent' : ($value_ratio >= 1.5 ? 'good' : 'fair');
                                        ?>
                                        <span class="value-badge <?= $value_class ?>">
                                            <?= number_format($value_ratio, 1) ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="orderItem(<?= $item['item_id'] ?>)">
                                            🛒 Order Now
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <!-- Recent Transactions -->
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">📊 Recent Transactions</h3>
            </div>
            <div class="card-body">
                <?php if (empty($transactions)): ?>
                <p class="text-muted">No transactions yet. Start earning points by making reservations and orders!</p>
                <?php else: ?>
                <div class="transactions-list">
                    <?php foreach($transactions as $trans): ?>
                    <div class="transaction-item">
                        <div class="transaction-details">
                            <div class="transaction-reference"><?= htmlspecialchars($trans['reference_display']) ?></div>
                            <div class="transaction-description"><?= htmlspecialchars($trans['description']) ?></div>
                            <div class="transaction-date"><?= date('M j, Y h:i A', strtotime($trans['created_at'])) ?></div>
                        </div>
                        <div class="transaction-points <?= $trans['points'] > 0 ? 'positive' : 'negative' ?>">
                            <?= $trans['points'] > 0 ? '+' : '' ?><?= $trans['points'] ?> points
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Redemption History -->
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">🎁 Redemption History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($redemption_history)): ?>
                <p class="text-muted">No rewards redeemed yet. Start earning points to unlock rewards!</p>
                <?php else: ?>
                <div class="redemption-list">
                    <?php foreach($redemption_history as $redemption): ?>
                    <div class="redemption-item">
                        <div class="redemption-details">
                            <div class="redemption-name"><?= htmlspecialchars($redemption['reward_name']) ?></div>
                            <div class="redemption-date"><?= date('M j, Y h:i A', strtotime($redemption['created_at'])) ?></div>
                        </div>
                        <div class="redemption-points negative">
                            -<?= abs($redemption['points']) ?> points
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.points-display {
    background: var(--gradient-hero);
    color: var(--text-primary);
    padding: 2rem;
    border-radius: var(--radius-lg);
    margin: 1rem 0;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.points-display::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%); }
    100% { transform: translateX(100%) translateY(100%); }
}

.points-number {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
    color: var(--text-primary);
}

.points-label {
    font-size: 1.2rem;
    opacity: 0.95;
    margin-top: 0.5rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-secondary);
}

.points-info .info-item {
    display: flex;
    justify-content: space-between;
    margin: 0.75rem 0;
    font-size: 0.95rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-sm);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    margin: 1rem 0;
    padding: 1rem;
    border-radius: var(--radius);
    background: var(--bg-card);
    border-left: 4px solid var(--accent-primary);
    transition: var(--transition);
    border: 1px solid var(--border-color);
}

.favorite-actions .btn:disabled:hover {
    transform: none;
    box-shadow: var(--shadow);
}

.stat-label {
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.stat-value {
    font-weight: 700;
    color: var(--accent-primary);
    font-size: 1.1rem;
    text-shadow: 0 0 2px rgba(99, 102, 241, 0.3);
}

.rewards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.reward-card {
    border: 2px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    background: var(--bg-card);
    box-shadow: var(--shadow);
}

.reward-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
    transition: left 0.5s ease;
}

.reward-card:hover::before {
    left: 100%;
}

.reward-card.available {
    border-color: var(--success);
    background: var(--bg-card);
    box-shadow: var(--shadow-glow);
}

.reward-card.locked {
    opacity: 0.7;
    border-color: var(--text-muted);
    background: var(--bg-card);
}

.reward-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.reward-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.reward-name {
    font-weight: 700;
    font-size: 1.2rem;
    color: var(--text-primary);
    line-height: 1.2;
}

.reward-points {
    background: var(--gradient-primary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 700;
    box-shadow: var(--shadow);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid var(--border-accent);
}

.reward-description {
    color: var(--text-secondary);
    margin-bottom: 1rem;
    font-size: 0.95rem;
    line-height: 1.5;
}

.reward-type {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.reward-value {
    font-weight: 700;
    color: var(--success);
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
    padding: 0.5rem;
    background: rgba(16, 185, 129, 0.1);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--success);
}

.menu-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.menu-item-card {
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1.5rem;
    transition: var(--transition);
    background: var(--bg-card);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.menu-item-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.05), transparent);
    transition: left 0.6s ease;
}

.menu-item-card:hover::before {
    left: 100%;
}

.menu-item-card:hover {
    border-color: var(--accent-primary);
    box-shadow: var(--shadow-glow);
    transform: translateY(-4px);
}

.menu-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.menu-item-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--text-primary);
    line-height: 1.2;
}

.menu-item-price {
    color: var(--accent-primary);
    font-weight: 800;
    font-size: 1.2rem;
    text-shadow: 0 1px 2px rgba(99, 102, 241, 0.4);
}

.points-gained {
    background: var(--success);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    display: inline-block;
    box-shadow: var(--shadow);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border: 1px solid var(--border-accent);
}

.points-required {
    background: var(--warning);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    display: inline-block;
    box-shadow: var(--shadow);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border: 1px solid var(--border-accent);
}

.products-points-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.products-points-table th {
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    box-shadow: var(--shadow);
}

.products-points-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-card);
    transition: var(--transition);
}

.products-points-table tr:hover td {
    background: var(--bg-tertiary);
    transform: scale(1.01);
    box-shadow: var(--shadow);
}

.product-name {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    font-size: 1rem;
    color: var(--text-primary);
}

.product-icon {
    font-size: 1.5rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.category-badge {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    padding: 0.4rem 0.8rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid var(--border-color);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.price-tag {
    font-weight: 700;
    color: var(--accent-primary);
    font-size: 1.1rem;
    text-shadow: 0 1px 2px rgba(99, 102, 241, 0.4);
}

.points-earned {
    color: var(--success);
    font-weight: 700;
    font-size: 1rem;
    padding: 0.3rem 0.6rem;
    background: rgba(16, 185, 129, 0.1);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--success);
}

.points-cost {
    color: var(--warning);
    font-weight: 700;
    font-size: 1rem;
    padding: 0.3rem 0.6rem;
    background: rgba(245, 158, 11, 0.1);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--warning);
}

.value-badge {
    padding: 0.4rem 0.8rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border: none;
    box-shadow: var(--shadow);
}

.value-badge.excellent {
    background: var(--success);
    color: white;
    box-shadow: var(--shadow);
}

.value-badge.good {
    background: var(--info);
    color: white;
    box-shadow: var(--shadow);
}

.value-badge.fair {
    background: var(--warning);
    color: white;
    box-shadow: var(--shadow);
}

.points-analysis {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow);
}

.analysis-header h4 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-weight: 700;
    font-size: 1.3rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-shadow: 0 0 2px rgba(0, 0, 0, 0.3);
}

.analysis-header p {
    color: var(--text-secondary);
    font-size: 1rem;
    margin: 0;
}

.transaction-item, .redemption-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-card);
    transition: var(--transition);
    border-radius: var(--radius-sm);
    margin-bottom: 0.5rem;
    box-shadow: var(--shadow);
}

.transaction-item:hover, .redemption-item:hover {
    background: var(--bg-tertiary);
    transform: translateX(5px);
    box-shadow: var(--shadow-glow);
}

.transaction-points.positive {
    color: var(--success);
    font-weight: 700;
    font-size: 1.1rem;
    text-shadow: 0 0 2px rgba(16, 185, 129, 0.4);
}

.transaction-points.negative, .redemption-points.negative {
    color: var(--warning);
    font-weight: 700;
    font-size: 1.1rem;
    text-shadow: 0 0 2px rgba(245, 158, 11, 0.4);
}

.transaction-reference {
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-size: 1rem;
}

.transaction-description, .redemption-name {
    font-size: 0.95rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.transaction-date, .redemption-date {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.card {
    position: relative;
    overflow: hidden;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow);
}

.card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
    animation: float 8s ease-in-out infinite;
    pointer-events: none;
}

@keyframes float {
    0%, 100% { transform: translate(-50%, -50%) scale(0.8); }
    50% { transform: translate(-30%, -30%) scale(1); }
}

.btn {
    border-radius: var(--radius-sm);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow);
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: var(--gradient-primary);
    border: none;
    color: white;
    box-shadow: var(--shadow);
    text-shadow: 0 0 2px rgba(0, 0, 0, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-outline-primary {
    background: transparent;
    border: 2px solid var(--accent-primary);
    color: var(--accent-primary);
}

.favorite-actions .btn:disabled:hover {
    transform: none;
    box-shadow: var(--shadow);
}

.favorite-indicator {
    color: var(--success);
    font-size: 1rem;
    margin-left: 0.5rem;
    text-shadow: 0 0 2px rgba(16, 185, 129, 0.4);
}

.favorited-text {
    color: var(--success);
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.25rem 0.5rem;
    background: rgba(16, 185, 129, 0.1);
    border-radius: var(--radius-sm);
    border: 1px solid var(--success);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: inline-block;
    margin-right: 0.5rem;
}

.btn-outline-primary:hover {
    background: var(--gradient-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    text-shadow: 0 0 2px rgba(0, 0, 0, 0.3);
}
</style>

<script>
function redeemReward(rewardId) {
    if (!confirm('Are you sure you want to redeem this reward? Points will be deducted immediately.')) {
        return;
    }
    
    fetch('ajax_loyalty.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=redeem_reward&reward_id=' + rewardId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Reward redeemed successfully! You can use this reward on your next order.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error redeeming reward. Please try again.');
        console.error('Redemption error:', error);
    });
}

function addToFavorites(itemId) {
    fetch('ajax_favorite.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'item_id=' + itemId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item added to favorites!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error adding to favorites. Please try again.');
        console.error('Favorite error:', error);
    });
}

function orderItem(itemId) {
    // Add item to cart and redirect to menu page
    fetch('menu.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'add_to_cart=' + itemId + '&quantity=1'
    })
    .then(response => response.text())
    .then(data => {
        // Redirect to menu page to complete order
        window.location.href = 'menu.php';
    })
    .catch(error => {
        alert('Error adding item to cart. Please try again.');
        console.error('Order error:', error);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>

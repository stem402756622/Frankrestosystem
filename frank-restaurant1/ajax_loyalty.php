<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_points':
        // Get user's current loyalty points
        $user = db()->fetchOne("SELECT loyalty_points FROM users WHERE user_id = ?", [$user_id]);
        echo json_encode([
            'success' => true,
            'points' => $user['loyalty_points'] ?? 0
        ]);
        break;
        
    case 'get_rewards':
        // Get available rewards for user's points
        $user_points = intval($_GET['points'] ?? 0);
        $rewards = db()->fetchAll(
            "SELECT * FROM loyalty_rewards 
             WHERE is_active = 1 
             AND points_required <= ? 
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY points_required ASC",
            [$user_points]
        );
        
        echo json_encode([
            'success' => true,
            'rewards' => $rewards
        ]);
        break;
        
    case 'get_reward_details':
        // Get details for a specific reward
        $reward_id = intval($_GET['id'] ?? 0);
        $reward = db()->fetchOne(
            "SELECT * FROM loyalty_rewards 
             WHERE id = ? AND is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())",
            [$reward_id]
        );
        
        if ($reward) {
            echo json_encode([
                'success' => true,
                'reward' => $reward
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Reward not found or expired'
            ]);
        }
        break;
        
    case 'redeem_reward':
        // Redeem a reward (called from order processing)
        $reward_id = intval($_POST['reward_id'] ?? 0);
        
        if (!$reward_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid reward']);
            exit;
        }
        
        // Get user points and reward details
        $user = db()->fetchOne("SELECT loyalty_points FROM users WHERE user_id = ?", [$user_id]);
        $reward = db()->fetchOne(
            "SELECT * FROM loyalty_rewards 
             WHERE id = ? AND is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())",
            [$reward_id]
        );
        
        if (!$user || !$reward) {
            echo json_encode(['success' => false, 'message' => 'Invalid reward or user']);
            exit;
        }
        
        if ($user['loyalty_points'] < $reward['points_required']) {
            echo json_encode(['success' => false, 'message' => 'Insufficient points']);
            exit;
        }
        
        // Check if user has already used this reward the maximum times
        if ($reward['max_uses_per_customer']) {
            $usage_count = db()->fetchOne(
                "SELECT COUNT(*) as cnt FROM loyalty_points_transactions lpt
                 JOIN orders o ON lpt.reference_id = o.order_id
                 WHERE lpt.user_id = ? AND lpt.reference_type = 'order' 
                 AND o.promo_code = ?",
                [$user_id, 'LOYALTY_' . $reward_id]
            )['cnt'];
            
            if ($usage_count >= $reward['max_uses_per_customer']) {
                echo json_encode(['success' => false, 'message' => 'Maximum uses reached for this reward']);
                exit;
            }
        }
        
        // Deduct points and record transaction
        db()->execute("UPDATE users SET loyalty_points = loyalty_points - ? WHERE user_id = ?", [$reward['points_required'], $user_id]);
        
        db()->insert(
            "INSERT INTO loyalty_points_transactions (user_id, points, transaction_type, reference_type, description) VALUES (?,?,?,?,?)",
            [$user_id, -$reward['points_required'], 'redeemed', 'manual_adjustment', "Redeemed reward: {$reward['name']}"]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Reward redeemed successfully',
            'reward' => $reward,
            'points_deducted' => $reward['points_required']
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>

<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

// Check if feedback table exists, create if not
try {
    $table_check = db()->fetchOne("SHOW TABLES LIKE 'customer_feedback'");
    if (!$table_check) {
        db()->execute("CREATE TABLE customer_feedback (
            feedback_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_id INT NULL,
            reservation_id INT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            service_rating INT NULL CHECK (service_rating >= 1 AND service_rating <= 5),
            food_rating INT NULL CHECK (food_rating >= 1 AND food_rating <= 5),
            atmosphere_rating INT NULL CHECK (atmosphere_rating >= 1 AND atmosphere_rating <= 5),
            feedback_text TEXT NULL,
            would_recommend BOOLEAN NULL,
            visit_again BOOLEAN NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (order_id) REFERENCES orders(order_id),
            FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
} catch (Exception $e) {
    error_log("Feedback table creation failed: " . $e->getMessage());
}

$pageTitle = 'Customer Feedback';
$pageSubtitle = 'Share your experience with us';
require_once 'includes/header.php';

$user = db()->fetchOne("SELECT * FROM users WHERE user_id=?", [$user_id]);
$error = $success = '';

// Get user's recent orders and reservations for context
$recent_orders = db()->fetchAll("SELECT order_id, created_at, total FROM orders WHERE user_id=? AND status='paid' ORDER BY created_at DESC LIMIT 5", [$user_id]);
$recent_reservations = db()->fetchAll("SELECT reservation_id, reservation_date, reservation_time, status FROM reservations WHERE user_id=? AND status='completed' ORDER BY reservation_date DESC LIMIT 5", [$user_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'submit_feedback') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $reservation_id = intval($_POST['reservation_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $service_rating = intval($_POST['service_rating'] ?? 0);
        $food_rating = intval($_POST['food_rating'] ?? 0);
        $atmosphere_rating = intval($_POST['atmosphere_rating'] ?? 0);
        $feedback_text = sanitize($_POST['feedback_text'] ?? '');
        $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;
        $visit_again = isset($_POST['visit_again']) ? 1 : 0;
        
        if ($rating < 1 || $rating > 5) {
            $error = 'Please provide an overall rating between 1 and 5 stars.';
        } elseif (empty($feedback_text)) {
            $error = 'Please provide your feedback comments.';
        } else {
            try {
                db()->execute(
                    "INSERT INTO customer_feedback (user_id, order_id, reservation_id, rating, service_rating, food_rating, atmosphere_rating, feedback_text, would_recommend, visit_again) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$user_id, $order_id ?: null, $reservation_id ?: null, $rating, $service_rating, $food_rating, $atmosphere_rating, $feedback_text, $would_recommend, $visit_again]
                );
                $success = 'Thank you for your feedback! We appreciate your input.';
            } catch (Exception $e) {
                $error = 'Failed to submit feedback. Please try again.';
            }
        }
    }
}

// Get user's feedback history
$feedback_history = db()->fetchAll(
    "SELECT cf.*, o.order_id, o.total as order_total, r.reservation_id, r.reservation_date 
     FROM customer_feedback cf 
     LEFT JOIN orders o ON cf.order_id = o.order_id 
     LEFT JOIN reservations r ON cf.reservation_id = r.reservation_id 
     WHERE cf.user_id = ? 
     ORDER BY cf.created_at DESC 
     LIMIT 10",
    [$user_id]
);
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Customer Feedback</h2>
        <p class="section-subtitle">Share your dining experience with us</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" data-dismiss="5000">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" data-dismiss="5000">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="content-grid">
    <!-- Feedback Form -->
    <div>
        <div class="card animate-in mb-3">
            <div class="card-header">
                <h3 class="card-title">📝 Submit Your Feedback</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="submit_feedback">
                
                <!-- Context Selection -->
                <div class="form-group">
                    <label>Related To (Optional)</label>
                    <select name="context" class="form-control" onchange="updateContextFields(this.value)">
                        <option value="">General Feedback</option>
                        <option value="order">Recent Order</option>
                        <option value="reservation">Recent Reservation</option>
                    </select>
                </div>
                
                <div id="order_context" style="display: none;" class="form-group">
                    <label>Select Order</label>
                    <select name="order_id" class="form-control">
                        <option value="">Choose an order...</option>
                        <?php foreach ($recent_orders as $order): ?>
                        <option value="<?= $order['order_id'] ?>">
                            Order #<?= $order['order_id'] ?> - <?= date('M j, Y', strtotime($order['created_at'])) ?> - ₱<?= number_format($order['total'], 2) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="reservation_context" style="display: none;" class="form-group">
                    <label>Select Reservation</label>
                    <select name="reservation_id" class="form-control">
                        <option value="">Choose a reservation...</option>
                        <?php foreach ($recent_reservations as $reservation): ?>
                        <option value="<?= $reservation['reservation_id'] ?>">
                            Reservation #<?= $reservation['reservation_id'] ?> - <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Overall Rating -->
                <div class="form-group">
                    <label>Overall Rating *</label>
                    <div class="star-rating" id="overall_rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?= $i ?>" onclick="setRating('overall', <?= $i ?>)">⭐</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="overall_rating_value" value="0" required>
                    <div class="text-xs text-muted mt-1">Click to rate your overall experience</div>
                </div>
                
                <!-- Category Ratings -->
                <div class="form-group">
                    <label>Service Quality</label>
                    <div class="star-rating" id="service_rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?= $i ?>" onclick="setRating('service', <?= $i ?>)">⭐</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="service_rating" id="service_rating_value" value="0">
                </div>
                
                <div class="form-group">
                    <label>Food Quality</label>
                    <div class="star-rating" id="food_rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?= $i ?>" onclick="setRating('food', <?= $i ?>)">⭐</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="food_rating" id="food_rating_value" value="0">
                </div>
                
                <div class="form-group">
                    <label>Atmosphere</label>
                    <div class="star-rating" id="atmosphere_rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?= $i ?>" onclick="setRating('atmosphere', <?= $i ?>)">⭐</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="atmosphere_rating" id="atmosphere_rating_value" value="0">
                </div>
                
                <!-- Feedback Text -->
                <div class="form-group">
                    <label>Your Feedback *</label>
                    <textarea name="feedback_text" class="form-control" rows="4" required 
                              placeholder="Tell us about your experience... What did you like? What could we improve?"></textarea>
                </div>
                
                <!-- Yes/No Questions -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="would_recommend" value="1">
                            <span class="checkmark"></span>
                            I would recommend Frank Restaurant to others
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="visit_again" value="1">
                            <span class="checkmark"></span>
                            I plan to visit again
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 glow" style="justify-content:center;">
                    📝 Submit Feedback
                </button>
            </form>
        </div>
    </div>
    
    <!-- Feedback History -->
    <div>
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">📊 Your Feedback History</h3>
            </div>
            
            <?php if ($feedback_history): ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($feedback_history as $feedback): ?>
                <div class="feedback-item" style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-sm);">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="star-display">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="<?= $i <= $feedback['rating'] ? 'star-filled' : 'star-empty' ?>">⭐</span>
                                <?php endfor; ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?>
                            </div>
                        </div>
                        <?php if ($feedback['order_id']): ?>
                        <span class="badge badge-info">Order #<?= $feedback['order_id'] ?></span>
                        <?php elseif ($feedback['reservation_id']): ?>
                        <span class="badge badge-success">Reservation #<?= $feedback['reservation_id'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($feedback['service_rating'] || $feedback['food_rating'] || $feedback['atmosphere_rating']): ?>
                    <div class="category-ratings" style="margin: 0.5rem 0;">
                        <?php if ($feedback['service_rating']): ?>
                        <div class="text-xs">Service: <?= str_repeat('⭐', $feedback['service_rating']) ?></div>
                        <?php endif; ?>
                        <?php if ($feedback['food_rating']): ?>
                        <div class="text-xs">Food: <?= str_repeat('⭐', $feedback['food_rating']) ?></div>
                        <?php endif; ?>
                        <?php if ($feedback['atmosphere_rating']): ?>
                        <div class="text-xs">Atmosphere: <?= str_repeat('⭐', $feedback['atmosphere_rating']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="feedback-text" style="margin: 0.5rem 0; font-size: 0.9rem;">
                        <?= htmlspecialchars($feedback['feedback_text']) ?>
                    </div>
                    
                    <?php if ($feedback['would_recommend'] || $feedback['visit_again']): ?>
                    <div class="feedback-indicators" style="margin-top: 0.5rem;">
                        <?php if ($feedback['would_recommend']): ?>
                        <span class="badge badge-success" style="font-size: 0.7rem;">👍 Would Recommend</span>
                        <?php endif; ?>
                        <?php if ($feedback['visit_again']): ?>
                        <span class="badge badge-primary" style="font-size: 0.7rem;">🔄 Will Visit Again</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-text">No feedback submitted yet.</div>
                <p class="text-muted">Share your first experience with us!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.star-rating {
    display: flex;
    gap: 5px;
    font-size: 1.5rem;
    margin: 0.5rem 0;
}

.star {
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 0.3;
}

.star:hover {
    opacity: 1;
    transform: scale(1.1);
}

.star.filled {
    opacity: 1;
}

.star-display {
    display: flex;
    gap: 2px;
    font-size: 1rem;
}

.star-filled {
    opacity: 1;
}

.star-empty {
    opacity: 0.3;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.checkbox-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.feedback-item {
    border-left: 3px solid var(--accent-primary);
}

.category-ratings {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.feedback-indicators {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .star-rating {
        font-size: 1.2rem;
    }
    
    .category-ratings {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
function setRating(type, rating) {
    const container = document.getElementById(type + '_rating');
    const stars = container.querySelectorAll('.star');
    const input = document.getElementById(type + '_rating_value');
    
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('filled');
        } else {
            star.classList.remove('filled');
        }
    });
    
    input.value = rating;
}

function updateContextFields(context) {
    document.getElementById('order_context').style.display = context === 'order' ? 'block' : 'none';
    document.getElementById('reservation_context').style.display = context === 'reservation' ? 'block' : 'none';
    
    // Clear selections when context changes
    if (context !== 'order') {
        document.querySelector('select[name="order_id"]').value = '';
    }
    if (context !== 'reservation') {
        document.querySelector('select[name="reservation_id"]').value = '';
    }
}

// Initialize ratings
document.addEventListener('DOMContentLoaded', function() {
    // Set initial rating states
    ['overall', 'service', 'food', 'atmosphere'].forEach(type => {
        setRating(type, 0);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

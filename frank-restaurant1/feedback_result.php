<?php
// Try to load dependencies with fallbacks
try {
    require_once 'includes/config.php';
} catch (Exception $e) {
    // Fallback config
    define('BASE_URL', '/');
    session_start();
}

try {
    require_once 'includes/database.php';
} catch (Exception $e) {
    // Fallback database connection
    die('Database connection failed. Please check configuration.');
}

try {
    requireLogin();
} catch (Exception $e) {
    // Fallback login check
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Only allow admin access
if (!in_array($role, ['admin'])) {
    redirect('dashboard.php', 'Access denied. Admin only.', 'error');
}

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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
} catch (Exception $e) {
    error_log("Feedback table creation failed: " . $e->getMessage());
    // Continue even if table creation fails
}

// Set page variables with fallbacks
$pageTitle = 'Feedback Results';
$pageSubtitle = 'View all customer feedback and ratings';

try {
    require_once 'includes/header.php';
} catch (Exception $e) {
    // Fallback header
    echo '<!DOCTYPE html><html><head><title>' . $pageTitle . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;margin:20px;}';
    echo '.card{border:1px solid #ddd;padding:20px;margin:20px 0;border-radius:8px;}';
    echo '.btn{padding:10px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;}';
    echo '</style></head><body>';
}

// Get feedback statistics
try {
    $total_feedback = db()->fetchOne("SELECT COUNT(*) as count FROM customer_feedback")['count'] ?? 0;
} catch (Exception $e) {
    $total_feedback = 0;
}

// Average ratings
try {
    $avg_overall = db()->fetchOne("SELECT AVG(rating) as avg FROM customer_feedback WHERE rating > 0")['avg'] ?? 0;
} catch (Exception $e) {
    $avg_overall = 0;
}

try {
    $avg_service = db()->fetchOne("SELECT AVG(service_rating) as avg FROM customer_feedback WHERE service_rating > 0")['avg'] ?? 0;
} catch (Exception $e) {
    $avg_service = 0;
}

try {
    $avg_food = db()->fetchOne("SELECT AVG(food_rating) as avg FROM customer_feedback WHERE food_rating > 0")['avg'] ?? 0;
} catch (Exception $e) {
    $avg_food = 0;
}

try {
    $avg_atmosphere = db()->fetchOne("SELECT AVG(atmosphere_rating) as avg FROM customer_feedback WHERE atmosphere_rating > 0")['avg'] ?? 0;
} catch (Exception $e) {
    $avg_atmosphere = 0;
}

// Recommendation and visit again rates
try {
    $recommend_rate = db()->fetchOne("SELECT AVG(would_recommend) * 100 as rate FROM customer_feedback WHERE would_recommend IS NOT NULL")['rate'] ?? 0;
} catch (Exception $e) {
    $recommend_rate = 0;
}

try {
    $visit_again_rate = db()->fetchOne("SELECT AVG(visit_again) * 100 as rate FROM customer_feedback WHERE visit_again IS NOT NULL")['rate'] ?? 0;
} catch (Exception $e) {
    $visit_again_rate = 0;
}

// Get all feedback with user details
try {
    $all_feedback = db()->fetchAll(
        "SELECT cf.*, u.full_name, u.username, u.email, u.phone, o.order_id, o.total as order_total, o.created_at as order_date, r.reservation_id, r.reservation_date, r.reservation_time 
         FROM customer_feedback cf 
         LEFT JOIN users u ON cf.user_id = u.user_id 
         LEFT JOIN orders o ON cf.order_id = o.order_id 
         LEFT JOIN reservations r ON cf.reservation_id = r.reservation_id 
         ORDER BY cf.created_at DESC"
    );
} catch (Exception $e) {
    $all_feedback = [];
}

// Filter options
$filter_period = sanitize($_GET['period'] ?? 'all');
$filter_rating = sanitize($_GET['rating'] ?? 'all');

$where_conditions = [];
$params = [];

if ($filter_period !== 'all') {
    switch ($filter_period) {
        case 'today':
            $where_conditions[] = "DATE(cf.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "cf.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "cf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if ($filter_rating !== 'all') {
    $where_conditions[] = "cf.rating = ?";
    $params[] = intval($filter_rating);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get filtered feedback
try {
    $filtered_feedback = db()->fetchAll(
        "SELECT cf.*, u.full_name, u.username, u.email, u.phone, o.order_id, o.total as order_total, o.created_at as order_date, r.reservation_id, r.reservation_date, r.reservation_time 
         FROM customer_feedback cf 
         LEFT JOIN users u ON cf.user_id = u.user_id 
         LEFT JOIN orders o ON cf.order_id = o.order_id 
         LEFT JOIN reservations r ON cf.reservation_id = r.reservation_id 
         $where_clause
         ORDER BY cf.created_at DESC",
        $params
    );
} catch (Exception $e) {
    $filtered_feedback = [];
}
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">💬 Customer Feedback</h2>
        <p class="section-subtitle">All customer feedback and ratings in one place</p>
    </div>
</div>

<!-- Statistics Overview -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value"><?= $total_feedback ?></div>
        <div class="stat-label">Total Feedback</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($avg_overall, 1) ?> ⭐</div>
        <div class="stat-label">Average Rating</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($recommend_rate, 1) ?>%</div>
        <div class="stat-label">Would Recommend</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($visit_again_rate, 1) ?>%</div>
        <div class="stat-label">Would Visit Again</div>
    </div>
</div>

<!-- Category Performance -->
<div class="card animate-in mb-4">
    <div class="card-header">
        <h3 class="card-title">📊 Performance by Category</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
        <div class="rating-category">
            <div class="flex justify-between items-center">
                <span>🍽️ Food Quality</span>
                <span class="rating-score"><?= number_format($avg_food, 1) ?> ⭐</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($avg_food / 5) * 100 ?>%;"></div>
            </div>
        </div>
        
        <div class="rating-category">
            <div class="flex justify-between items-center">
                <span>👥 Service</span>
                <span class="rating-score"><?= number_format($avg_service, 1) ?> ⭐</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($avg_service / 5) * 100 ?>%;"></div>
            </div>
        </div>
        
        <div class="rating-category">
            <div class="flex justify-between items-center">
                <span>🎨 Atmosphere</span>
                <span class="rating-score"><?= number_format($avg_atmosphere, 1) ?> ⭐</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($avg_atmosphere / 5) * 100 ?>%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card animate-in mb-4">
    <div class="card-header">
        <h3 class="card-title">🔍 Filter Feedback</h3>
    </div>
    <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label>Time Period</label>
            <select name="period" class="form-control">
                <option value="all" <?= $filter_period === 'all' ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= $filter_period === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week" <?= $filter_period === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="month" <?= $filter_period === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
            </select>
        </div>
        
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label>Rating</label>
            <select name="rating" class="form-control">
                <option value="all" <?= $filter_rating === 'all' ? 'selected' : '' ?>>All Ratings</option>
                <option value="5" <?= $filter_rating === '5' ? 'selected' : '' ?>>5 Stars</option>
                <option value="4" <?= $filter_rating === '4' ? 'selected' : '' ?>>4 Stars</option>
                <option value="3" <?= $filter_rating === '3' ? 'selected' : '' ?>>3 Stars</option>
                <option value="2" <?= $filter_rating === '2' ? 'selected' : '' ?>>2 Stars</option>
                <option value="1" <?= $filter_rating === '1' ? 'selected' : '' ?>>1 Star</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Apply Filters</button>
        <a href="feedback_result.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<!-- All Feedback -->
<div class="card animate-in">
    <div class="card-header">
        <h3 class="card-title">📝 Customer Feedback Results</h3>
        <span class="badge badge-info"><?= count($filtered_feedback) ?> entries</span>
    </div>
    
    <?php if ($filtered_feedback): ?>
    <div style="display: grid; gap: 1rem;">
        <?php foreach ($filtered_feedback as $feedback): ?>
        <div class="feedback-item" style="padding: 1.5rem; background: var(--bg-tertiary, #f8f9fa); border-radius: var(--radius-sm, 8px); border-left: 4px solid var(--accent-primary, #007bff);">
            <!-- Date and Context -->
            <div class="flex justify-between items-center mb-3">
                <div>
                    <div class="text-xs text-muted">
                        📅 <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?>
                    </div>
                </div>
                <div class="text-right">
                    <?php if ($feedback['order_id']): ?>
                    <span class="badge badge-info">Order #<?= $feedback['order_id'] ?></span>
                    <?php elseif ($feedback['reservation_id']): ?>
                    <span class="badge badge-success">Reservation #<?= $feedback['reservation_id'] ?></span>
                    <?php else: ?>
                    <span class="badge badge-secondary">General Feedback</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Overall Rating -->
            <div class="mb-3">
                <div class="flex items-center gap-2 mb-2">
                    <span class="fw-600 text-lg">Overall Rating:</span>
                    <div class="star-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?= $i <= $feedback['rating'] ? 'star-filled' : 'star-empty' ?>" style="font-size: 1.5rem;">⭐</span>
                        <?php endfor; ?>
                    </div>
                    <span class="badge badge-primary"><?= $feedback['rating'] ?>/5</span>
                </div>
            </div>
            
            <!-- Category Ratings -->
            <?php if ($feedback['service_rating'] || $feedback['food_rating'] || $feedback['atmosphere_rating']): ?>
            <div class="category-ratings mb-3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php if ($feedback['service_rating']): ?>
                <div class="rating-item">
                    <div class="flex items-center gap-2">
                        <span class="fw-600">👥 Service:</span>
                        <div class="star-display">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?= $i <= $feedback['service_rating'] ? 'star-filled' : 'star-empty' ?>">⭐</span>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-muted">(<?= $feedback['service_rating'] ?>/5)</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($feedback['food_rating']): ?>
                <div class="rating-item">
                    <div class="flex items-center gap-2">
                        <span class="fw-600">🍽️ Food:</span>
                        <div class="star-display">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?= $i <= $feedback['food_rating'] ? 'star-filled' : 'star-empty' ?>">⭐</span>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-muted">(<?= $feedback['food_rating'] ?>/5)</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($feedback['atmosphere_rating']): ?>
                <div class="rating-item">
                    <div class="flex items-center gap-2">
                        <span class="fw-600">🎨 Atmosphere:</span>
                        <div class="star-display">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?= $i <= $feedback['atmosphere_rating'] ? 'star-filled' : 'star-empty' ?>">⭐</span>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-muted">(<?= $feedback['atmosphere_rating'] ?>/5)</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Feedback Text (Suggestions) -->
            <div class="feedback-suggestion mb-3" style="padding: 1rem; background: var(--bg-secondary, #f8f9fa); border-radius: var(--radius-sm, 6px); border-left: 4px solid var(--accent-primary, #007bff); line-height: 1.6;">
                <div class="fw-600 mb-2" style="color: var(--accent-primary, #007bff);">💬 Customer Feedback:</div>
                <div style="font-style: italic; color: var(--text-primary, #333);">
                    <?= htmlspecialchars($feedback['feedback_text']) ?>
                </div>
            </div>
            
            <!-- Recommendations -->
            <?php if ($feedback['would_recommend'] !== null || $feedback['visit_again'] !== null): ?>
            <div class="recommendations mb-2" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <?php if ($feedback['would_recommend'] !== null): ?>
                <?php if ($feedback['would_recommend']): ?>
                <span class="badge badge-success" style="font-size: 0.8rem;">👍 Would Recommend</span>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size: 0.8rem;">👎 Would Not Recommend</span>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($feedback['visit_again'] !== null): ?>
                <?php if ($feedback['visit_again']): ?>
                <span class="badge badge-primary" style="font-size: 0.8rem;">🔄 Will Visit Again</span>
                <?php else: ?>
                <span class="badge badge-secondary" style="font-size: 0.8rem;">🚫 Will Not Visit Again</span>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Related Order/Reservation Info (simplified) -->
            <?php if ($feedback['order_id'] && $feedback['order_date']): ?>
            <div class="related-info text-xs text-muted" style="background: var(--bg-tertiary, #f8f9fa); padding: 0.5rem; border-radius: 4px;">
                <strong>Order #<?= $feedback['order_id'] ?></strong> - <?= date('M j, Y', strtotime($feedback['order_date'])) ?> - ₱<?= number_format($feedback['order_total'], 2) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($feedback['reservation_id'] && $feedback['reservation_date']): ?>
            <div class="related-info text-xs text-muted" style="background: var(--bg-tertiary, #f8f9fa); padding: 0.5rem; border-radius: 4px;">
                <strong>Reservation #<?= $feedback['reservation_id'] ?></strong> - <?= date('M j, Y', strtotime($feedback['reservation_date'])) ?> at <?= date('g:i A', strtotime($feedback['reservation_time'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-text">No feedback found with current filters.</div>
        <p class="text-muted">Try adjusting the filters or wait for customers to submit feedback.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.rating-category {
    padding: 1rem;
    background: var(--bg-secondary, #f8f9fa);
    border-radius: var(--radius-sm, 4px);
    border: 1px solid var(--border-color, #ddd);
}

.rating-score {
    font-weight: 700;
    color: var(--accent-primary, #007bff);
}

.progress-bar {
    height: 8px;
    background: var(--border-color, #ddd);
    border-radius: 4px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent-primary, #007bff), var(--accent-secondary, #0056b3));
    transition: width 0.3s ease;
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

.feedback-item {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background: var(--bg-tertiary, #f8f9fa);
    border: 1px solid var(--border-color, #ddd);
}

.feedback-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.category-ratings {
    font-size: 0.8rem;
}

.feedback-text {
    color: var(--text-primary, #333);
    border-left: 3px solid var(--accent-primary, #007bff);
    background: var(--bg-secondary, #f8f9fa);
}

.feedback-indicators {
    font-size: 0.7rem;
}

.customer-details {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.related-info {
    background: var(--bg-tertiary, #f8f9fa);
    padding: 0.5rem;
    border-radius: var(--radius-sm, 4px);
    border-left: 3px solid var(--border-color, #ddd);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius, 8px);
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-primary, #007bff);
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary, #666);
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius, 8px);
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color, #ddd);
    background: var(--bg-secondary, #f8f9fa);
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: var(--text-primary, #333);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary, #333);
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius-sm, 4px);
    font-size: 0.875rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: var(--radius-sm, 4px);
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: var(--accent-primary, #007bff);
    color: white;
}

.btn-primary:hover {
    background: var(--accent-secondary, #0056b3);
}

.btn-secondary {
    background: var(--text-secondary, #6c757d);
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: var(--radius-sm, 4px);
}

.badge-info {
    background: #17a2b8;
    color: white;
}

.badge-success {
    background: #28a745;
    color: white;
}

.badge-primary {
    background: #007bff;
    color: white;
}

.badge-secondary {
    background: #6c757d;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary, #666);
}

.empty-text {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .customer-details {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .category-ratings {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php
try {
    require_once 'includes/footer.php';
} catch (Exception $e) {
    // Fallback footer
    echo '</body></html>';
}
?>

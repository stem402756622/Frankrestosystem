<?php
$pageTitle    = 'Reports';
$pageSubtitle = 'Business analytics & insights';
require_once 'includes/header.php';

if (!hasAccess('reports')) redirect('index.php');

$period = sanitize($_GET['period'] ?? 'week');
$dateCondition = match($period) {
    'today' => "DATE(created_at) = CURDATE()",
    'week'  => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    default => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
};

// Stats
$totalRevenue  = db()->fetchOne("SELECT COALESCE(SUM(total),0) as r FROM orders WHERE status='completed' AND $dateCondition")['r'] ?? 0;
$totalOrders   = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE $dateCondition")['cnt'] ?? 0;
$totalRes      = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE $dateCondition")['cnt'] ?? 0;
$newCustomers  = db()->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE role='customer' AND $dateCondition")['cnt'] ?? 0;
$completedRes  = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE status='completed' AND $dateCondition")['cnt'] ?? 0;
$cancelledRes  = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE status='cancelled' AND $dateCondition")['cnt'] ?? 0;
$noShowRes     = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE status='no_show' AND $dateCondition")['cnt'] ?? 0;
$avgParty      = db()->fetchOne("SELECT COALESCE(AVG(party_size),0) as avg FROM reservations WHERE $dateCondition")['avg'] ?? 0;
$avgOrder      = db()->fetchOne("SELECT COALESCE(AVG(total),0) as avg FROM orders WHERE status='completed' AND $dateCondition")['avg'] ?? 0;

// Daily revenue last 7 days
$dailyRevenue = db()->fetchAll(
    "SELECT DATE(created_at) as day, COALESCE(SUM(total),0) as revenue, COUNT(*) as orders
     FROM orders WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY day ASC"
);

// Occasions breakdown
$occasions = db()->fetchAll(
    "SELECT occasion, COUNT(*) as cnt FROM reservations WHERE $dateCondition GROUP BY occasion ORDER BY cnt DESC"
);

// Table utilization
$tableUtil = db()->fetchAll(
    "SELECT t.table_number, t.location, t.table_type, t.status,
            COUNT(r.reservation_id) as reservations
     FROM restaurant_tables t
     LEFT JOIN reservations r ON t.table_id=r.table_id
     GROUP BY t.table_id ORDER BY reservations DESC LIMIT 10"
);

// Top customers
$topCustomers = db()->fetchAll(
    "SELECT u.full_name, u.email, u.loyalty_points, u.vip_status, COUNT(r.reservation_id) as visits
     FROM users u LEFT JOIN reservations r ON u.user_id=r.user_id
     WHERE u.role='customer' GROUP BY u.user_id ORDER BY visits DESC LIMIT 8"
);

// Customer Feedback Analytics
try {
    // Check if feedback table exists
    $feedbackTableCheck = db()->fetchOne("SHOW TABLES LIKE 'customer_feedback'");
    
    if ($feedbackTableCheck) {
        // Feedback statistics
        $totalFeedback = db()->fetchOne("SELECT COUNT(*) as cnt FROM customer_feedback WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['cnt'] ?? 0;
        $avgRating = db()->fetchOne("SELECT AVG(rating) as avg FROM customer_feedback WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['avg'] ?? 0;
        $recommendRate = db()->fetchOne("SELECT AVG(would_recommend) * 100 as rate FROM customer_feedback WHERE would_recommend IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['rate'] ?? 0;
        
        // Recent feedback with customer details
        $recentFeedback = db()->fetchAll(
            "SELECT cf.*, u.full_name, u.email, u.phone, o.order_id, o.total as order_total, o.created_at as order_date, r.reservation_id, r.reservation_date, r.reservation_time 
             FROM customer_feedback cf 
             LEFT JOIN users u ON cf.user_id = u.user_id 
             LEFT JOIN orders o ON cf.order_id = o.order_id 
             LEFT JOIN reservations r ON cf.reservation_id = r.reservation_id 
             WHERE cf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             ORDER BY cf.created_at DESC 
             LIMIT 10"
        );
        
        // Rating distribution
        $ratingDistribution = db()->fetchAll("SELECT rating, COUNT(*) as count FROM customer_feedback WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY rating ORDER BY rating");
        
        // Category averages
        $avgService = db()->fetchOne("SELECT AVG(service_rating) as avg FROM customer_feedback WHERE service_rating > 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['avg'] ?? 0;
        $avgFood = db()->fetchOne("SELECT AVG(food_rating) as avg FROM customer_feedback WHERE food_rating > 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['avg'] ?? 0;
        $avgAtmosphere = db()->fetchOne("SELECT AVG(atmosphere_rating) as avg FROM customer_feedback WHERE atmosphere_rating > 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['avg'] ?? 0;
    } else {
        $totalFeedback = 0;
        $avgRating = 0;
        $recommendRate = 0;
        $recentFeedback = [];
        $ratingDistribution = [];
        $avgService = 0;
        $avgFood = 0;
        $avgAtmosphere = 0;
    }
} catch (Exception $e) {
    $totalFeedback = 0;
    $avgRating = 0;
    $recommendRate = 0;
    $recentFeedback = [];
    $ratingDistribution = [];
    $avgService = 0;
    $avgFood = 0;
    $avgAtmosphere = 0;
}

$noShowRate = $totalRes > 0 ? round($noShowRes / $totalRes * 100) : 0;
$cancelRate = $totalRes > 0 ? round($cancelledRes / $totalRes * 100) : 0;
$completionRate = $totalRes > 0 ? round($completedRes / $totalRes * 100) : 0;
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Reports & Analytics</h2>
        <p class="section-subtitle">Business performance overview</p>
    </div>
    <div class="flex gap-1">
        <?php foreach(['today'=>'Today','week'=>'7 Days','month'=>'30 Days'] as $k=>$v): ?>
        <a href="?period=<?= $k ?>" class="btn <?= $period===$k?'btn-primary':'btn-secondary' ?> btn-sm"><?= $v ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Key Metrics -->
<div class="stats-grid mb-4">
    <div class="stat-card animate-in">
        <div class="stat-icon">💰</div>
        <div class="stat-value">₱<?= number_format($totalRevenue, 0) ?></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-change">Avg ₱<?= number_format($avgOrder, 2) ?>/order</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">🧾</div>
        <div class="stat-value" data-counter="<?= $totalOrders ?>"><?= $totalOrders ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">📅</div>
        <div class="stat-value" data-counter="<?= $totalRes ?>"><?= $totalRes ?></div>
        <div class="stat-label">Reservations</div>
        <div class="stat-change up">✅ <?= $completionRate ?>% completed</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">🆕</div>
        <div class="stat-value" data-counter="<?= $newCustomers ?>"><?= $newCustomers ?></div>
        <div class="stat-label">New Customers</div>
    </div>
</div>

<div class="content-grid mb-4">
    <!-- Customer Feedback Analytics -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">💬 Customer Feedback Analytics</h3>
            <span class="badge badge-info">Last 30 Days</span>
        </div>
        
        <?php if ($totalFeedback > 0): ?>
        <!-- Feedback Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                <div class="stat-value" style="font-size: 1.5rem;"><?= $totalFeedback ?></div>
                <div class="stat-label" style="font-size: 0.8rem;">Total Feedback</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                <div class="stat-value" style="font-size: 1.5rem;"><?= number_format($avgRating, 1) ?> ⭐</div>
                <div class="stat-label" style="font-size: 0.8rem;">Avg Rating</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                <div class="stat-value" style="font-size: 1.5rem;"><?= number_format($recommendRate, 1) ?>%</div>
                <div class="stat-label" style="font-size: 0.8rem;">Would Recommend</div>
            </div>
        </div>
        
        <!-- Category Performance -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem; color: var(--text-primary);">Category Performance</h4>
            <div style="display: grid; gap: 0.5rem;">
                <div class="flex justify-between items-center">
                    <span style="font-size: 0.8rem;">🍽️ Food Quality</span>
                    <span style="font-size: 0.8rem; font-weight: 600;"><?= number_format($avgFood, 1) ?> ⭐</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: <?= ($avgFood / 5) * 100 ?>%; background: linear-gradient(90deg, #28a745, #20c997);"></div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span style="font-size: 0.8rem;">👥 Service</span>
                    <span style="font-size: 0.8rem; font-weight: 600;"><?= number_format($avgService, 1) ?> ⭐</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: <?= ($avgService / 5) * 100 ?>%; background: linear-gradient(90deg, #007bff, #0056b3);"></div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span style="font-size: 0.8rem;">🎨 Atmosphere</span>
                    <span style="font-size: 0.8rem; font-weight: 600;"><?= number_format($avgAtmosphere, 1) ?> ⭐</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: <?= ($avgAtmosphere / 5) * 100 ?>%; background: linear-gradient(90deg, #6f42c1, #5a32a3);"></div>
                </div>
            </div>
        </div>
        
        <!-- Recent Feedback -->
        <div>
            <h4 style="font-size: 0.9rem; margin-bottom: 0.75rem; color: var(--text-primary);">Recent Customer Feedback</h4>
            <div style="display: grid; gap: 0.75rem; max-height: 400px; overflow-y: auto;">
                <?php foreach ($recentFeedback as $feedback): ?>
                <div style="padding: 0.75rem; background: var(--bg-tertiary); border-radius: var(--radius-sm); border-left: 3px solid var(--accent-primary);">
                    <!-- Customer Info & Rating -->
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;"><?= htmlspecialchars($feedback['full_name'] ?? 'Anonymous') ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                                <?php if ($feedback['email']): ?>📧 <?= htmlspecialchars($feedback['email']) ?><?php endif; ?>
                                <?php if ($feedback['phone']): ?> • 📱 <?= htmlspecialchars($feedback['phone']) ?><?php endif; ?>
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-secondary);">
                                📅 <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.8rem; margin-bottom: 0.25rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="<?= $i <= $feedback['rating'] ? '' : 'text-muted' ?>" style="font-size: 0.8rem;">⭐</span>
                                <?php endfor; ?>
                            </div>
                            <span class="badge badge-primary" style="font-size: 0.6rem;"><?= $feedback['rating'] ?>/5</span>
                        </div>
                    </div>
                    
                    <!-- Feedback Text -->
                    <div style="font-style: italic; font-size: 0.8rem; color: var(--text-primary); margin-bottom: 0.5rem; line-height: 1.4;">
                        <?= htmlspecialchars($feedback['feedback_text']) ?>
                    </div>
                    
                    <!-- Recommendations -->
                    <?php if ($feedback['would_recommend'] !== null || $feedback['visit_again'] !== null): ?>
                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                        <?php if ($feedback['would_recommend'] !== null): ?>
                        <span class="badge <?= $feedback['would_recommend'] ? 'badge-success' : 'badge-secondary' ?>" style="font-size: 0.6rem;">
                            <?= $feedback['would_recommend'] ? '👍 Recommend' : '👎 No Recommend' ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($feedback['visit_again'] !== null): ?>
                        <span class="badge <?= $feedback['visit_again'] ? 'badge-primary' : 'badge-secondary' ?>" style="font-size: 0.6rem;">
                            <?= $feedback['visit_again'] ? '🔄 Return' : '🚫 No Return' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="empty-state" style="padding: 2rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">💬</div>
            <div class="empty-text">No customer feedback yet</div>
            <p class="text-muted">Customer feedback will appear here once customers start submitting reviews.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
    <!-- Reservation Analytics -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">📅 Reservation Analytics</h3>
        </div>
        <div style="display:grid;gap:0.75rem;">
            <?php
            $metrics = [
                ['Completed', $completedRes, $completionRate, 'success'],
                ['Cancelled', $cancelledRes, $cancelRate, 'danger'],
                ['No-Show', $noShowRes, $noShowRate, 'warning'],
            ];
            foreach ($metrics as [$label, $cnt, $pct, $color]):
            ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm fw-600"><?= $label ?></span>
                    <span class="text-sm"><?= $cnt ?> <span class="text-muted">(<?= $pct ?>%)</span></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width:<?= $pct ?>%;<?= $color==='danger'?'background:var(--danger);':($color==='warning'?'background:var(--warning);':'') ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="divider"></div>
            <div class="flex justify-between">
                <span class="text-sm text-muted">Avg Party Size</span>
                <span class="fw-600">👥 <?= round($avgParty, 1) ?> guests</span>
            </div>
        </div>
    </div>

    <!-- Occasions Breakdown -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">🎉 Occasion Breakdown</h3>
        </div>
        <?php if($occasions): ?>
        <div style="display:grid;gap:0.5rem;">
            <?php
            $occ_total = array_sum(array_column($occasions,'cnt'));
            $occ_icons = ['dining'=>'🍽️','birthday'=>'🎂','anniversary'=>'💑','business'=>'💼','date'=>'❤️','celebration'=>'🎉','other'=>'✨'];
            foreach($occasions as $o):
                $pct = $occ_total > 0 ? round($o['cnt']/$occ_total*100) : 0;
                $icon = $occ_icons[$o['occasion']] ?? '✨';
            ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm"><?= $icon ?> <?= ucfirst($o['occasion']) ?></span>
                    <span class="text-sm fw-600"><?= $o['cnt'] ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:2rem;"><span class="empty-icon">📊</span><div class="empty-text">No data</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- Revenue Chart (HTML/CSS) -->
<?php if($dailyRevenue): ?>
<div class="card animate-in mb-4">
    <div class="card-header">
        <h3 class="card-title">📈 Daily Revenue (Last 7 Days)</h3>
    </div>
    <?php
    $maxRev = max(array_column($dailyRevenue,'revenue')) ?: 1;
    ?>
    <div style="display:flex;align-items:flex-end;gap:0.75rem;height:160px;padding:0 0.5rem;">
        <?php foreach($dailyRevenue as $d):
            $pct = ($d['revenue'] / $maxRev) * 100;
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.3rem;height:100%;justify-content:flex-end;">
            <div class="text-xs fw-600" style="color:var(--accent-primary);">₱<?= number_format($d['revenue'],0) ?></div>
            <div style="width:100%;background:var(--gradient-primary);border-radius:4px 4px 0 0;transition:height 0.8s ease;height:<?= $pct ?>%;min-height:4px;box-shadow:var(--shadow-glow);opacity:0.85;"></div>
            <div class="text-xs text-muted"><?= date('M j', strtotime($d['day'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="content-grid">
    <!-- Table Utilization -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">🪑 Table Utilization</h3>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>Table</th><th>Location</th><th>Reservations</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach($tableUtil as $t):
                        $sc = ['available'=>'success','occupied'=>'danger','reserved'=>'warning','maintenance'=>'muted','cleaning'=>'info'][$t['status']] ?? 'muted';
                    ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($t['table_number']) ?></td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($t['location']) ?></td>
                        <td><span class="badge badge-primary"><?= $t['reservations'] ?></span></td>
                        <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($t['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">⭐ Top Customers</h3>
        </div>
        <div style="display:grid;gap:0.5rem;">
            <?php foreach($topCustomers as $i => $c): ?>
            <div class="flex items-center gap-2" style="padding:0.6rem;border-radius:var(--radius-sm);background:var(--bg-tertiary);">
                <div style="width:24px;height:24px;border-radius:50%;background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:800;color:white;flex-shrink:0;"><?= $i+1 ?></div>
                <div class="flex-1">
                    <div class="fw-600 text-sm"><?= htmlspecialchars($c['full_name']) ?> <?= $c['vip_status'] ? '👑' : '' ?></div>
                    <div class="text-xs text-muted"><?= $c['visits'] ?> visits · <?= number_format($c['loyalty_points']) ?> pts</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

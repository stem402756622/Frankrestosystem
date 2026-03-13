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

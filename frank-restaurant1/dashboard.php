<?php
$pageTitle    = 'Dashboard';
$pageSubtitle = 'Restaurant Overview';
require_once 'includes/header.php';

// Stats based on role
$stats = [];
if (in_array($role, ['admin','manager','staff'])) {
    $stats['reservations'] = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE DATE(reservation_date)=CURDATE()")['cnt'] ?? 0;
    $stats['tables_occ']   = db()->fetchOne("SELECT COUNT(*) as cnt FROM restaurant_tables WHERE status='occupied'")['cnt'] ?? 0;
    $stats['tables_avail'] = db()->fetchOne("SELECT COUNT(*) as cnt FROM restaurant_tables WHERE status='available'")['cnt'] ?? 0;
    $stats['customers']    = db()->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE role='customer'")['cnt'] ?? 0;
    $stats['pending']      = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE status='pending'")['cnt'] ?? 0;
    $stats['orders_today'] = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at)=CURDATE()")['cnt'] ?? 0;
    $stats['revenue']      = db()->fetchOne("SELECT COALESCE(SUM(total),0) as total FROM orders WHERE DATE(created_at)=CURDATE() AND status='completed'")['total'] ?? 0;
    $stats['vip']          = db()->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE vip_status=1")['cnt'] ?? 0;

    $recent_reservations = db()->fetchAll(
        "SELECT r.*, u.full_name, u.email, t.table_number
         FROM reservations r
         JOIN users u ON r.user_id=u.user_id
         LEFT JOIN restaurant_tables t ON r.table_id=t.table_id
         ORDER BY r.created_at DESC LIMIT 8"
    );
    $table_summary = db()->fetchAll("SELECT status, COUNT(*) as cnt FROM restaurant_tables GROUP BY status");
} else {
    // Customer view
    $my_reservations = db()->fetchAll(
        "SELECT r.*, t.table_number, t.location FROM reservations r
         LEFT JOIN restaurant_tables t ON r.table_id=t.table_id
         WHERE r.user_id=? ORDER BY r.reservation_date DESC LIMIT 5",
        [$user_id]
    );
    $my_points = db()->fetchOne("SELECT loyalty_points, vip_status FROM users WHERE user_id=?", [$user_id]);
    
    // Feature 15: Follow-Up Notice
    $unreviewed = db()->fetchOne(
        "SELECT o.order_id, o.total, o.created_at FROM orders o 
         LEFT JOIN reviews r ON o.order_id = r.order_id 
         WHERE o.user_id = ? AND o.status = 'completed' AND r.id IS NULL 
         ORDER BY o.created_at DESC LIMIT 1",
        [$user_id]
    );
}
?>

<?php if(isset($unreviewed) && $unreviewed): ?>
<div id="reviewModal" class="modal" style="display:block; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
    <div class="modal-content bg-white p-6 m-auto mt-20 rounded shadow-lg max-w-md" style="background:white; margin:10% auto; padding:20px; width:400px; border-radius:8px;">
        <h3 class="text-xl font-bold mb-4">How was your recent order?</h3>
        <p class="mb-4 text-muted">Order #<?= $unreviewed['order_id'] ?> on <?= date('M j', strtotime($unreviewed['created_at'])) ?></p>
        
        <form method="POST" action="submit_review.php">
            <input type="hidden" name="order_id" value="<?= $unreviewed['order_id'] ?>">
            <div class="form-group mb-3">
                <label>Rating</label>
                <div class="rating-stars text-2xl cursor-pointer" style="font-size: 2rem;">
                    <span onclick="setRating(1)">★</span>
                    <span onclick="setRating(2)">★</span>
                    <span onclick="setRating(3)">★</span>
                    <span onclick="setRating(4)">★</span>
                    <span onclick="setRating(5)">★</span>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required>
            </div>
            <div class="form-group mb-4">
                <label>Comment</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('reviewModal').style.display='none'">Maybe Later</button>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>
<script>
function setRating(n) {
    document.getElementById('ratingInput').value = n;
    let stars = document.querySelectorAll('.rating-stars span');
    stars.forEach((s, i) => {
        s.style.color = i < n ? '#FFD700' : '#ccc';
    });
}
</script>
<?php endif; ?>

<?php if(in_array($role, ['admin','manager','staff'])): ?>
<!-- STAFF / ADMIN DASHBOARD -->
<div class="stats-grid stagger-container">
    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">📅</div>
        <div class="stat-value" data-counter="<?= $stats['reservations'] ?>"><?= $stats['reservations'] ?></div>
        <div class="stat-label">Today's Reservations</div>
        <?php if($stats['pending'] > 0): ?>
        <div class="stat-change up">⚡ <?= $stats['pending'] ?> pending</div>
        <?php endif; ?>
    </div>

    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">🪑</div>
        <div class="stat-value" data-counter="<?= $stats['tables_occ'] ?>"><?= $stats['tables_occ'] ?></div>
        <div class="stat-label">Tables Occupied</div>
        <div class="stat-change"><?= $stats['tables_avail'] ?> available</div>
    </div>

    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">👥</div>
        <div class="stat-value" data-counter="<?= $stats['customers'] ?>"><?= $stats['customers'] ?></div>
        <div class="stat-label">Total Customers</div>
        <div class="stat-change up">⭐ <?= $stats['vip'] ?> VIP</div>
    </div>

    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">💰</div>
        <div class="stat-value" data-prefix="₱" data-counter="<?= intval($stats['revenue']) ?>">₱<?= number_format($stats['revenue'], 0) ?></div>
        <div class="stat-label">Today's Revenue</div>
        <div class="stat-change"><?= $stats['orders_today'] ?> orders completed</div>
    </div>
</div>

<!-- Table Status Summary -->
<div class="content-grid mb-4">
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">🪑 Table Status</h3>
            <a href="tables.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php
        $statusColors = ['available'=>'success','occupied'=>'danger','reserved'=>'warning','maintenance'=>'muted','cleaning'=>'info'];
        $statusMap = [];
        foreach ($table_summary as $s) $statusMap[$s['status']] = $s['cnt'];
        $total = array_sum($statusMap);
        ?>
        <div style="display:grid;gap:0.75rem;">
            <?php foreach(['available','occupied','reserved','cleaning','maintenance'] as $st): ?>
            <?php $cnt = $statusMap[$st] ?? 0; $pct = $total > 0 ? round($cnt/$total*100) : 0; ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm" style="text-transform:capitalize;"><?= $st ?></span>
                    <span class="text-sm fw-600"><?= $cnt ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width:<?= $pct ?>%;<?= $st==='occupied'?'background:var(--danger);':($st==='reserved'?'background:var(--warning);':($st==='available'?'':'background:var(--text-muted);')) ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">📅 Recent Reservations</h3>
            <a href="reservations.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if($recent_reservations): ?>
        <div style="display:grid;gap:0.6rem;">
            <?php foreach(array_slice($recent_reservations,0,5) as $r): ?>
            <?php
            $statusBadge = [
                'pending'   => 'warning',
                'confirmed' => 'info',
                'seated'    => 'success',
                'completed' => 'muted',
                'cancelled' => 'danger',
                'no_show'   => 'danger',
            ][$r['status']] ?? 'muted';
            ?>
            <div class="flex items-center gap-2" style="padding:0.6rem;border-radius:var(--radius-sm);background:var(--bg-tertiary);">
                <div style="font-size:1.4rem;">👤</div>
                <div class="flex-1">
                    <div class="fw-600 text-sm"><?= htmlspecialchars($r['full_name']) ?></div>
                    <div class="text-xs text-muted"><?= date('M j', strtotime($r['reservation_date'])) ?> at <?= date('g:i A', strtotime($r['reservation_time'])) ?> · <?= $r['party_size'] ?> guests</div>
                </div>
                <span class="badge badge-<?= $statusBadge ?>"><?= ucfirst($r['status']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:2rem;">
            <span class="empty-icon">📋</span>
            <div class="empty-text">No reservations yet</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="card animate-in">
    <div class="card-header">
        <h3 class="card-title">⚡ Quick Actions</h3>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
        <a href="create_reservation.php" class="btn btn-primary">📅 New Reservation</a>
        <a href="tables.php" class="btn btn-secondary">🪑 Manage Tables</a>
        <a href="customers.php" class="btn btn-secondary">👥 View Customers</a>
        <a href="orders.php" class="btn btn-secondary">🧾 View Orders</a>
        <?php if(hasAccess('reports')): ?>
        <a href="reports.php" class="btn btn-secondary">📈 Reports</a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- CUSTOMER DASHBOARD -->
<div class="stats-grid stagger-container">
    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">⭐</div>
        <div class="stat-value" data-counter="<?= $my_points['loyalty_points'] ?? 0 ?>"><?= $my_points['loyalty_points'] ?? 0 ?></div>
        <div class="stat-label">Loyalty Points</div>
        <?php if($my_points['vip_status']): ?>
        <div class="stat-change up">👑 VIP Member</div>
        <?php endif; ?>
    </div>

    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">📅</div>
        <?php $total_res = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE user_id=?", [$user_id])['cnt'] ?? 0; ?>
        <div class="stat-value" data-counter="<?= $total_res ?>"><?= $total_res ?></div>
        <div class="stat-label">Total Reservations</div>
    </div>

    <div class="stat-card stagger-item animate-in">
        <div class="stat-icon">✅</div>
        <?php $comp = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE user_id=? AND status='completed'", [$user_id])['cnt'] ?? 0; ?>
        <div class="stat-value" data-counter="<?= $comp ?>"><?= $comp ?></div>
        <div class="stat-label">Completed Visits</div>
    </div>
</div>

<div class="content-grid">
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">📅 My Reservations</h3>
            <a href="reservations.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if($my_reservations): ?>
        <div style="display:grid;gap:0.6rem;">
            <?php foreach($my_reservations as $r):
                $sb = ['pending'=>'warning','confirmed'=>'info','seated'=>'success','completed'=>'muted','cancelled'=>'danger','no_show'=>'danger'][$r['status']] ?? 'muted';
            ?>
            <div style="padding:0.8rem;border-radius:var(--radius-sm);background:var(--bg-tertiary);border:1px solid var(--border-color);">
                <div class="flex justify-between items-center mb-1">
                    <div class="fw-600 text-sm"><?= date('M j, Y', strtotime($r['reservation_date'])) ?></div>
                    <span class="badge badge-<?= $sb ?>"><?= ucfirst($r['status']) ?></span>
                </div>
                <div class="text-xs text-muted">
                    🕐 <?= date('g:i A', strtotime($r['reservation_time'])) ?> ·
                    👥 <?= $r['party_size'] ?> guests
                    <?= $r['table_number'] ? ' · Table '.$r['table_number'] : '' ?>
                </div>
                <?php if($r['occasion'] !== 'dining'): ?>
                <div class="text-xs mt-1" style="color:var(--accent-primary);">🎉 <?= ucfirst($r['occasion']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:2rem;">
            <span class="empty-icon">📋</span>
            <div class="empty-title">No reservations yet</div>
            <div class="empty-text">Make your first reservation!</div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">🚀 Get Started</h3>
        </div>
        <div style="display:grid;gap:0.75rem;">
            <a href="create_reservation.php" class="btn btn-primary glow" style="justify-content:center;padding:1rem;">
                📅 Make a Reservation
            </a>
            <a href="reservations.php" class="btn btn-secondary" style="justify-content:center;">
                📋 My Reservations
            </a>
            <a href="profile.php" class="btn btn-secondary" style="justify-content:center;">
                👤 Edit My Profile
            </a>
        </div>
        <div class="divider"></div>
        <div style="background:var(--bg-tertiary);border-radius:var(--radius-sm);padding:1rem;text-align:center;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">🍽️</div>
            <div class="fw-600 mb-1">Welcome to Frank!</div>
            <div class="text-sm text-muted">Experience fine dining at its finest</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

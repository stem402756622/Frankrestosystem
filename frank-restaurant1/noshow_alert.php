<?php
$pageTitle    = 'No-Show Alert';
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$role = $_SESSION['role'];

if (!in_array($role, ['admin','manager'])) {
    redirect('index.php', 'Access denied.', 'error');
}

require_once 'includes/header.php';

// Get current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Find no-show reservations (past reservation time, not cancelled, not completed)
$no_shows = db()->fetchAll(
    "SELECT r.*, u.full_name, u.email, u.phone 
     FROM reservations r 
     LEFT JOIN users u ON r.user_id = u.user_id 
     WHERE r.reservation_date < ? 
     AND r.status = 'confirmed' 
     ORDER BY r.reservation_date DESC, r.reservation_time DESC",
    [$current_date]
);

// Get statistics
$total_confirmed = db()->fetchOne(
    "SELECT COUNT(*) as count FROM reservations 
     WHERE status = 'confirmed' AND reservation_date >= ?",
    [date('Y-m-d', strtotime('-30 days'))]
)['count'];

$total_no_shows = db()->fetchOne(
    "SELECT COUNT(*) as count FROM reservations 
     WHERE status = 'confirmed' 
     AND reservation_date < ? 
     AND reservation_date >= ?",
    [$current_date, date('Y-m-d', strtotime('-30 days'))]
)['count'];

$no_show_rate = $total_confirmed > 0 ? round(($total_no_shows / $total_confirmed) * 100, 1) : 0;

// Get today's reservations that might become no-shows
$at_risk = db()->fetchAll(
    "SELECT r.*, u.full_name, u.email 
     FROM reservations r 
     LEFT JOIN users u ON r.user_id = u.user_id 
     WHERE r.reservation_date = ? 
     AND r.status = 'confirmed' 
     AND r.reservation_time < ? 
     ORDER BY r.reservation_time ASC",
    [$current_date, date('H:i:s', strtotime('-30 minutes'))]
);
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1.25rem;
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.alert-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1rem;
    margin-bottom: 1rem;
}

.alert-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.alert-title {
    font-weight: 600;
    color: var(--text-primary);
}

.alert-time {
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.alert-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
    font-size: 0.85rem;
}

.alert-detail {
    color: var(--text-secondary);
}

.alert-actions {
    margin-top: 0.75rem;
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.825rem;
}

.at-risk {
    border-left: 4px solid var(--warning);
}

.no-show {
    border-left: 4px solid var(--danger);
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}
</style>

<div class="page-header">
    <h1>🚨 No-Show Alert</h1>
    <p class="text-muted">Monitor customers who missed their reservations</p>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= count($no_shows) ?></div>
        <div class="stat-label">Total No-Shows</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $no_show_rate ?>%</div>
        <div class="stat-label">No-Show Rate (30 days)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($at_risk) ?></div>
        <div class="stat-label">At Risk Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $total_confirmed ?></div>
        <div class="stat-label">Confirmed (30 days)</div>
    </div>
</div>

<!-- At Risk Reservations (Today) -->
<?php if (!empty($at_risk)): ?>
<div class="mb-4">
    <h3 style="color: var(--warning); margin-bottom: 1rem;">⚠️ At Risk Today</h3>
    <?php foreach ($at_risk as $reservation): ?>
    <div class="alert-card at-risk">
        <div class="alert-header">
            <div>
                <div class="alert-title"><?= htmlspecialchars($reservation['full_name'] ?? 'Guest') ?></div>
                <div class="alert-time">Reservation: <?= date('g:i A', strtotime($reservation['reservation_time'])) ?></div>
            </div>
        </div>
        <div class="alert-details">
            <div class="alert-detail">📧 <?= htmlspecialchars($reservation['email'] ?? 'N/A') ?></div>
            <div class="alert-detail">👥 <?= $reservation['guests'] ?> guests</div>
            <div class="alert-detail">📅 <?= date('M j', strtotime($reservation['reservation_date'])) ?></div>
            <div class="alert-detail">🪑 Table: <?= htmlspecialchars($reservation['table_preference'] ?? 'Any') ?></div>
        </div>
        <div class="alert-actions">
            <button class="btn btn-sm btn-secondary" onclick="contactCustomer(<?= $reservation['reservation_id'] ?>)">Contact</button>
            <button class="btn btn-sm btn-primary" onclick="markNoShow(<?= $reservation['reservation_id'] ?>)">Mark No-Show</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- No-Shows -->
<div>
    <h3 style="color: var(--danger); margin-bottom: 1rem;">❌ Confirmed No-Shows</h3>
    <?php if (!empty($no_shows)): ?>
        <?php foreach ($no_shows as $reservation): ?>
        <div class="alert-card no-show">
            <div class="alert-header">
                <div>
                    <div class="alert-title"><?= htmlspecialchars($reservation['full_name'] ?? 'Guest') ?></div>
                    <div class="alert-time">Missed: <?= date('M j, g:i A', strtotime($reservation['reservation_date'] . ' ' . $reservation['reservation_time'])) ?></div>
                </div>
            </div>
            <div class="alert-details">
                <div class="alert-detail">📧 <?= htmlspecialchars($reservation['email'] ?? 'N/A') ?></div>
                <div class="alert-detail">📞 <?= htmlspecialchars($reservation['phone'] ?? 'N/A') ?></div>
                <div class="alert-detail">👥 <?= $reservation['guests'] ?> guests</div>
                <div class="alert-detail">🪑 Table: <?= htmlspecialchars($reservation['table_preference'] ?? 'Any') ?></div>
                <div class="alert-detail">📝 <?= htmlspecialchars($reservation['special_requests'] ?? 'None') ?></div>
            </div>
            <div class="alert-actions">
                <button class="btn btn-sm btn-secondary" onclick="contactCustomer(<?= $reservation['reservation_id'] ?>)">Contact Customer</button>
                <button class="btn btn-sm btn-warning" onclick="cancelReservation(<?= $reservation['reservation_id'] ?>)">Cancel Reservation</button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
            <h3>No No-Shows Found</h3>
            <p>All customers have shown up for their reservations!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function markNoShow(reservationId) {
    if (confirm('Mark this reservation as a no-show?')) {
        fetch('reservations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=no_show&reservation_id=${reservationId}`
        }).then(() => location.reload());
    }
}

function cancelReservation(reservationId) {
    if (confirm('Cancel this reservation?')) {
        fetch('reservations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=cancel&reservation_id=${reservationId}`
        }).then(() => location.reload());
    }
}

function contactCustomer(reservationId) {
    alert('Contact feature would open email/phone interface');
}
</script>

<?php require_once 'includes/footer.php'; ?>

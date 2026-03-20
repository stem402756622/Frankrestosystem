<?php
$pageTitle    = 'My Reservations';
$pageSubtitle = 'View your reservation history';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php', 'Please login to view your reservations.', 'error');
}

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $rid = intval($_POST['reservation_id']);
    
    // Verify ownership and status
    $res = db()->fetchOne(
        "SELECT * FROM reservations WHERE reservation_id=? AND user_id=? AND status IN ('pending','confirmed')",
        [$rid, $user_id]
    );
    
    if ($res) {
        // Free table if assigned
        if ($res['table_id']) {
            db()->execute("UPDATE restaurant_tables SET status='available' WHERE table_id=?", [$res['table_id']]);
        }
        
        db()->execute("UPDATE reservations SET status='cancelled' WHERE reservation_id=?", [$rid]);
        
        // Send cancellation email
        require_once 'includes/mailer.php';
        $user = db()->fetchOne("SELECT email, full_name FROM users WHERE user_id=?", [$user_id]);
        if ($user) {
            $subject = "Reservation Cancelled - Frank Restaurant";
            $message = "Dear " . htmlspecialchars($user['full_name']) . ",<br><br>";
            $message .= "Your reservation (#" . $rid . ") has been cancelled as requested.<br>";
            sendEmail($user['email'], $subject, $message);
        }
        
        redirect('my_reservations.php', 'Reservation cancelled successfully.', 'success');
    } else {
        redirect('my_reservations.php', 'Unable to cancel reservation.', 'error');
    }
}

// Fetch user's reservations with details
$reservations = db()->fetchAll(
    "SELECT r.*, rt.table_number, rt.location, rt.capacity as table_capacity,
     (SELECT SUM(oi.quantity * oi.unit_price) FROM orders o JOIN order_items oi ON o.order_id=oi.order_id WHERE o.reservation_id=r.reservation_id) as order_total
     FROM reservations r 
     LEFT JOIN restaurant_tables rt ON r.table_id=rt.table_id
     WHERE r.user_id=? 
     ORDER BY r.reservation_date DESC, r.reservation_time DESC",
    [$user_id]
);

$statusColors = [
    'pending'   => 'warning',
    'confirmed' => 'success',
    'seated'    => 'info',
    'completed' => 'primary',
    'cancelled' => 'danger',
    'no_show'   => 'muted'
];

$statusIcons = [
    'pending'   => '⏳',
    'confirmed' => '✅',
    'seated'    => '🍽️',
    'completed' => '✔️',
    'cancelled' => '❌',
    'no_show'   => '⚠️'
];

// Calculate stats
$totalReservations = count($reservations);
$completedCount = 0;
$upcomingCount = 0;
$cancelledCount = 0;

foreach ($reservations as $r) {
    if ($r['status'] === 'completed') $completedCount++;
    if ($r['status'] === 'cancelled') $cancelledCount++;
    if (in_array($r['status'], ['pending', 'confirmed']) && strtotime($r['reservation_date']) >= strtotime('today')) {
        $upcomingCount++;
    }
}
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">My Reservations</h2>
        <p class="section-subtitle">View and manage your dining history</p>
    </div>
    <a href="create_reservation.php" class="btn btn-primary">+ New Reservation</a>
</div>

<!-- Stats Cards -->
<div class="stats-grid mb-4">
    <div class="stat-card animate-in">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?= $totalReservations ?></div>
        <div class="stat-label">Total Reservations</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">🍽️</div>
        <div class="stat-value"><?= $completedCount ?></div>
        <div class="stat-label">Completed Visits</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">⏳</div>
        <div class="stat-value"><?= $upcomingCount ?></div>
        <div class="stat-label">Upcoming</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">⭐</div>
        <div class="stat-value"><?= number_format($user['loyalty_points'] ?? 0) ?></div>
        <div class="stat-label">Loyalty Points</div>
    </div>
</div>

<!-- Reservations List -->
<div class="card animate-in">
    <?php if($reservations): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date & Time</th>
                    <th>Party Size</th>
                    <th>Table</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reservations as $r): 
                    $isUpcoming = in_array($r['status'], ['pending', 'confirmed']) && strtotime($r['reservation_date'] . ' ' . $r['reservation_time']) > time();
                    $canCancel = in_array($r['status'], ['pending', 'confirmed']) && strtotime($r['reservation_date']) >= strtotime('today');
                    $canReschedule = in_array($r['status'], ['pending', 'confirmed']) && ($r['reschedule_count'] ?? 0) < 2;
                ?>
                <tr>
                    <td>
                        <span class="text-muted text-xs">#<?= $r['reservation_id'] ?></span>
                        <?php if($r['occasion'] !== 'dining'): ?>
                        <div class="text-xs" style="text-transform:capitalize;"><?= htmlspecialchars($r['occasion']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-600"><?= date('M j, Y', strtotime($r['reservation_date'])) ?></div>
                        <div class="text-xs text-muted"><?= date('g:i A', strtotime($r['reservation_time'])) ?></div>
                        <?php if($r['reschedule_count'] > 0): ?>
                        <div class="text-xs text-warning">Rescheduled <?= $r['reschedule_count'] ?>x</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-primary">👥 <?= $r['party_size'] ?></span>
                    </td>
                    <td>
                        <?php if($r['table_number']): ?>
                        <div class="fw-600">Table <?= htmlspecialchars($r['table_number']) ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($r['location']) ?></div>
                        <?php else: ?>
                        <span class="text-xs text-muted">Auto-assign</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $statusColors[$r['status']] ?? 'muted' ?>">
                            <?= $statusIcons[$r['status']] ?? '' ?> <?= ucfirst(str_replace('_',' ',$r['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($r['order_total']): ?>
                        <div class="fw-600">₱<?= number_format($r['order_total'], 2) ?></div>
                        <?php else: ?>
                        <span class="text-xs text-muted">No order</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <?php if($canReschedule): ?>
                            <a href="reservations.php?reschedule=<?= $r['reservation_id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Reschedule (<?= 2 - ($r['reschedule_count'] ?? 0) ?> left)">📅</a>
                            <?php endif; ?>
                            
                            <?php if($canCancel): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this reservation?')">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Cancel">✕</button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if($r['order_total'] && $r['status'] === 'completed'): ?>
                            <a href="invoice.php?id=<?= $r['reservation_id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Invoice">🧾</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:3rem;">
        <span class="empty-icon">📋</span>
        <div class="empty-title">No reservations yet</div>
        <div class="empty-text">Make your first reservation to get started.</div>
        <a href="create_reservation.php" class="btn btn-primary mt-3">Book a Table</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle    = 'Reservations';
$pageSubtitle = 'Manage all reservations';
require_once 'includes/header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $rid = intval($_POST['reservation_id']);
    if ($_POST['action'] === 'update_status' && in_array($role, ['admin','manager','staff'])) {
        $status = sanitize($_POST['status']);
        $allowed = ['pending','confirmed','seated','completed','cancelled','no_show'];
        if (in_array($status, $allowed)) {
            // Auto-assign table when confirming reservation
            if ($status === 'confirmed') {
                $reservation = db()->fetchOne("SELECT * FROM reservations WHERE reservation_id=?", [$rid]);
                if ($reservation && !$reservation['table_id']) {
                    // Find best available table for party size
                    $table = db()->fetchOne(
                        "SELECT table_id FROM restaurant_tables 
                         WHERE status='available' AND capacity >= ? AND min_party_size <= ?
                         ORDER BY ABS(capacity - ?) ASC LIMIT 1",
                        [$reservation['party_size'], $reservation['party_size'], $reservation['party_size']]
                    );
                    
                    if ($table) {
                        db()->execute("UPDATE reservations SET table_id=? WHERE reservation_id=?", [$table['table_id'], $rid]);
                        db()->execute("UPDATE restaurant_tables SET status='reserved' WHERE table_id=?", [$table['table_id']]);
                    }
                }
            }
            // Mark table as occupied when seated
            elseif ($status === 'seated') {
                $reservation = db()->fetchOne("SELECT table_id FROM reservations WHERE reservation_id=?", [$rid]);
                if ($reservation && $reservation['table_id']) {
                    db()->execute("UPDATE restaurant_tables SET status='occupied' WHERE table_id=?", [$reservation['table_id']]);
                }
            }
            // Free table when completed or cancelled
            elseif (in_array($status, ['completed','cancelled','no_show'])) {
                $reservation = db()->fetchOne("SELECT table_id FROM reservations WHERE reservation_id=?", [$rid]);
                if ($reservation && $reservation['table_id']) {
                    db()->execute("UPDATE restaurant_tables SET status='available' WHERE table_id=?", [$reservation['table_id']]);
                }
            }
            
            db()->execute("UPDATE reservations SET status=? WHERE reservation_id=?", [$status, $rid]);
            redirect('reservations.php', 'Reservation status updated.', 'success');
        }
    }
    if ($_POST['action'] === 'cancel') {
        $where = $role === 'customer' ? "reservation_id=? AND user_id=?" : "reservation_id=?";
        $params = $role === 'customer' ? [$rid, $user_id] : [$rid];
        
        // Free table if assigned
        $reservation = db()->fetchOne("SELECT table_id FROM reservations WHERE $where", $params);
        if ($reservation && $reservation['table_id']) {
            db()->execute("UPDATE restaurant_tables SET status='available' WHERE table_id=?", [$reservation['table_id']]);
        }
        
        db()->execute("UPDATE reservations SET status='cancelled' WHERE $where", $params);
        redirect('reservations.php', 'Reservation cancelled.', 'success');
    }
}

// Filters
$filter_status = sanitize($_GET['status'] ?? '');
$filter_date   = sanitize($_GET['date'] ?? '');
$search        = sanitize($_GET['q'] ?? '');

// Build query
if (in_array($role, ['admin','manager','staff'])) {
    $where = '1=1';
    $params = [];
    if ($filter_status) { $where .= " AND r.status=?"; $params[] = $filter_status; }
    if ($filter_date)   { $where .= " AND r.reservation_date=?"; $params[] = $filter_date; }
    if ($search)        { $where .= " AND (u.full_name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

    $reservations = db()->fetchAll(
        "SELECT r.*, u.full_name, u.email, u.phone, u.vip_status, u.loyalty_points, 
                t.table_number, t.location, t.capacity as table_capacity,
                oi.order_id, oi.total as order_total
         FROM reservations r
         JOIN users u ON r.user_id=u.user_id
         LEFT JOIN restaurant_tables t ON r.table_id=t.table_id
         LEFT JOIN orders oi ON r.reservation_id=oi.reservation_id AND oi.status!='cancelled'
         WHERE $where
         ORDER BY r.reservation_date DESC, r.reservation_time DESC",
        $params
    );
} else {
    $reservations = db()->fetchAll(
        "SELECT r.*, t.table_number, t.location, t.capacity as table_capacity,
                oi.order_id, oi.total as order_total FROM reservations r
         LEFT JOIN restaurant_tables t ON r.table_id=t.table_id
         LEFT JOIN orders oi ON r.reservation_id=oi.reservation_id AND oi.status!='cancelled'
         WHERE r.user_id=? ORDER BY r.reservation_date DESC",
        [$user_id]
    );
}

$statusColors = ['pending'=>'warning','confirmed'=>'info','seated'=>'success','completed'=>'muted','cancelled'=>'danger','no_show'=>'danger'];
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Reservations</h2>
        <p class="section-subtitle"><?= count($reservations) ?> reservation(s) found</p>
    </div>
    <div class="flex gap-1">
        <a href="create_reservation.php" class="btn btn-primary">+ New Reservation</a>
        <?php if(in_array($role, ['admin','manager','staff'])): ?>
        <a href="tables.php" class="btn btn-secondary">🪑 Manage Tables</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <form method="GET" class="flex gap-2" style="flex-wrap:wrap;align-items:flex-end;">
        <?php if(in_array($role, ['admin','manager','staff'])): ?>
        <div class="flex-1" style="min-width:200px;">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" class="form-control" placeholder="Search guests..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <?php endif; ?>
        <div>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <?php foreach(['pending','confirmed','seated','completed','cancelled','no_show'] as $s): ?>
                <option value="<?= $s ?>" <?= $filter_status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="reservations.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<!-- Table -->
<div class="card animate-in">
    <?php if($reservations): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <?php if(in_array($role,['admin','manager','staff'])): ?>
                    <th>Guest Details</th>
                    <?php endif; ?>
                    <th>Date & Time</th>
                    <th>Party</th>
                    <th>Table</th>
                    <th>Occasion</th>
                    <th>Billing</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reservations as $r): ?>
                <tr>
                    <td><span class="text-muted text-xs">#<?= $r['reservation_id'] ?></span></td>

                    <?php if(in_array($role,['admin','manager','staff'])): ?>
                    <td>
                        <div class="guest-details">
                            <div class="fw-600"><?= htmlspecialchars($r['full_name']) ?></div>
                            <div class="text-xs text-muted"><?= htmlspecialchars($r['email']) ?></div>
                            <div class="text-xs text-muted"><?= htmlspecialchars($r['phone']) ?></div>
                            <div class="flex gap-1 mt-1">
                                <?php if($r['vip_status']): ?><span class="badge badge-vip">👑 VIP</span><?php endif; ?>
                                <span class="badge badge-muted">⭐ <?= $r['loyalty_points'] ?> pts</span>
                            </div>
                        </div>
                    </td>
                    <?php endif; ?>

                    <td>
                        <div class="fw-600"><?= date('M j, Y', strtotime($r['reservation_date'])) ?></div>
                        <div class="text-xs text-muted"><?= date('g:i A', strtotime($r['reservation_time'])) ?></div>
                    </td>

                    <td>
                        <span class="badge badge-primary">👥 <?= $r['party_size'] ?></span>
                        <?php if(!empty($r['table_capacity'])): ?>
                        <div class="text-xs text-muted">Cap: <?= $r['table_capacity'] ?></div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if($r['table_number']): ?>
                        <div>
                            <span class="fw-600">T<?= $r['table_number'] ?></span><br>
                            <span class="text-xs text-muted"><?= htmlspecialchars($r['location']) ?></span>
                        </div>
                        <?php else: ?>
                        <span class="text-muted text-xs">Auto-assign on confirm</span>
                        <?php endif; ?>
                    </td>

                    <td><span class="text-sm" style="text-transform:capitalize;"><?= htmlspecialchars($r['occasion']) ?></span></td>

                    <td>
                        <?php if(!empty($r['order_total'])): ?>
                        <div class="billing-info">
                            <div class="fw-600">₱<?= number_format($r['order_total'], 2) ?></div>
                            <div class="text-xs text-muted">Order #<?= $r['order_id'] ?></div>
                        </div>
                        <?php else: ?>
                        <span class="text-muted text-xs">No order</span>
                        <?php endif; ?>
                    </td>

                    <td><span class="badge badge-<?= $statusColors[$r['status']] ?? 'muted' ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>

                    <td>
                        <div class="flex gap-1">
                            <?php if(in_array($role,['admin','manager','staff'])): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <select name="status" class="form-control" style="width:auto;padding:0.3rem 0.6rem;font-size:0.8rem;" onchange="this.form.submit()">
                                    <?php foreach(['pending','confirmed','seated','completed','cancelled','no_show'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <?php endif; ?>

                            <?php if($r['status'] !== 'cancelled' && $r['status'] !== 'completed'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this reservation?')">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                                <button class="btn btn-danger btn-sm btn-icon" type="submit" data-tooltip="Cancel">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <span class="empty-icon">📋</span>
        <div class="empty-title">No reservations found</div>
        <div class="empty-text">Try adjusting your filters or create a new reservation.</div>
        <a href="create_reservation.php" class="btn btn-primary mt-2">+ New Reservation</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

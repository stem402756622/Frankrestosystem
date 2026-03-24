<?php
$pageTitle = 'No-Show History';
$pageSubtitle = 'View customer no-show records';
require_once 'includes/header.php';

if (!in_array($role, ['admin','manager'])) {
    redirect('dashboard.php', 'Access denied.', 'error');
}

// Fetch no-show records
try {
    $no_shows = db()->fetchAll(
        "SELECT ns.*, u.full_name as reported_by_name 
         FROM no_shows ns 
         LEFT JOIN users u ON ns.reported_by = u.user_id 
         ORDER BY ns.reported_at DESC"
    );
} catch (Exception $e) {
    $no_shows = [];
    error_log("No-shows table not found: " . $e->getMessage());
}

// Smart Real-time No-Show Detection
$live_noshow_alerts = [];
try {
    // Check for confirmed reservations that are now late (15+ minutes)
    $late_reservations = db()->fetchAll(
        "SELECT r.*, u.full_name, u.email, u.phone 
         FROM reservations r 
         JOIN users u ON r.user_id = u.user_id 
         WHERE r.status = 'confirmed' 
         AND r.reservation_date <= CURDATE() 
         AND r.reservation_time < ADDTIME(CURTIME(), '-00:15:00')
         AND r.reservation_id NOT IN (
             SELECT reservation_id FROM no_shows WHERE reservation_id IS NOT NULL
         )
         ORDER BY r.reservation_date ASC, r.reservation_time ASC"
    );
    
    foreach ($late_reservations as $res) {
        $reservation_datetime = new DateTime($res['reservation_date'] . ' ' . $res['reservation_time']);
        $current_datetime = new DateTime();
        $minutes_late = ($current_datetime->getTimestamp() - $reservation_datetime->getTimestamp()) / 60;
        
        $live_noshow_alerts[] = [
            'reservation_id' => $res['reservation_id'],
            'customer_name' => $res['full_name'],
            'customer_email' => $res['email'],
            'customer_phone' => $res['phone'],
            'party_size' => $res['party_size'],
            'reservation_date' => $res['reservation_date'],
            'reservation_time' => $res['reservation_time'],
            'minutes_late' => round($minutes_late),
            'severity' => $minutes_late > 30 ? 'critical' : ($minutes_late > 60 ? 'high' : 'medium'),
            'auto_detected' => true,
            'alert_time' => date('Y-m-d H:i:s')
        ];
    }
} catch (Exception $e) {
    $live_noshow_alerts = [];
}

// Handle delete if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_noshow'])) {
    $noshow_id = intval($_POST['noshow_id']);
    
    try {
        db()->execute("DELETE FROM no_shows WHERE id = ?", [$noshow_id]);
        redirect('no_shows.php', 'No-show record deleted.', 'success');
    } catch (Exception $e) {
        redirect('no_shows.php', 'Error deleting no-show record.', 'error');
    }
}
?>

<style>
.noshow-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1.5rem;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.noshow-table {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow: hidden;
}

.noshow-table table {
    width: 100%;
    border-collapse: collapse;
}

.noshow-table th {
    background: var(--bg-tertiary);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
}

.noshow-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}

.noshow-table tr:hover {
    background: var(--bg-tertiary);
}

.reason-badge {
    background: var(--danger);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
    font-weight: 600;
}

.action-badge {
    background: var(--warning);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
    font-weight: 600;
}

.btn-danger {
    background: var(--danger);
    color: white;
    border: 1px solid var(--danger);
}

.btn-danger:hover {
    background: #dc2626;
}
</style>

<div class="noshow-stats">
    <div class="stat-card">
        <div class="stat-number"><?= count($no_shows) ?></div>
        <div class="stat-label">Total No-Shows</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count(array_filter($no_shows, function($ns) { return date('Y-m-d', strtotime($ns['reported_at'])) == date('Y-m-d'); })) ?></div>
        <div class="stat-label">Today's No-Shows</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count($no_shows) > 0 ? round(count(array_filter($no_shows, function($ns) { return date('Y-m', strtotime($ns['reported_at'])) == date('Y-m'); })) / count($no_shows) * 100, 1) : 0 ?>%</div>
        <div class="stat-label">This Month</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count($live_noshow_alerts) ?></div>
        <div class="stat-label">Live Alerts</div>
    </div>
</div>

<!-- Smart No-Show Alerts -->
<?php if(!empty($live_noshow_alerts)): ?>
<div class="noshow-form">
    <h3 style="margin-bottom: 1rem; color: var(--text-primary);">🚨 Live No-Show Alerts</h3>
    <div style="background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%); color: white; padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
        <strong>⚠️ Real-time No-Show Detection Active</strong>
        <p style="margin: 0.5rem 0;">The following reservations are currently late and may be no-shows:</p>
    </div>
    
    <div style="max-height: 400px; overflow-y: auto;">
        <?php foreach($live_noshow_alerts as $alert): ?>
        <div class="noshow-alert-item" style="
            background: var(--bg-card); 
            border-left: 4px solid <?= $alert['severity'] === 'critical' ? 'var(--danger)' : ($alert['severity'] === 'high' ? '#ff9800' : 'var(--warning)') ?>; 
            border-radius: var(--radius); 
            padding: 1rem; 
            margin-bottom: 1rem;
            position: relative;
        ">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($alert['customer_name']) ?>
                        <span style="font-size: 0.8rem; color: var(--text-secondary);"> (Party of <?= $alert['party_size'] ?>)</span>
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                        📅 <?= date('M j, Y', strtotime($alert['reservation_date'])) ?> at <?= date('g:i A', strtotime($alert['reservation_time'])) ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="background: <?= $alert['severity'] === 'critical' ? 'var(--danger)' : ($alert['severity'] === 'high' ? '#ff9800' : 'var(--warning)') ?>; color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.7rem; font-weight: 600;">
                            <?= $alert['minutes_late'] ?> min late
                        </span>
                        <span style="background: var(--danger); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.7rem; font-weight: 600;">
                            <?= $alert['severity'] === 'critical' ? 'CRITICAL' : ($alert['severity'] === 'high' ? 'HIGH' : 'MEDIUM') ?>
                        </span>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($alert['customer_email']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($alert['customer_phone']) ?></div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Detected: <?= date('g:i A', strtotime($alert['alert_time'])) ?>
                    </div>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Automatically record no-show for <?= htmlspecialchars($alert['customer_name']) ?>?')">
                        <input type="hidden" name="reservation_id" value="<?= $alert['reservation_id'] ?>">
                        <input type="hidden" name="reason" value="Automatic Detection">
                        <input type="hidden" name="action_taken" value="No Action">
                        <input type="hidden" name="notes" value="Real-time detection - <?= $alert['minutes_late'] ?> minutes late">
                        <button type="submit" name="add_noshow" class="btn btn-danger" style="background: var(--danger); border-color: var(--danger); font-size: 0.8rem;">
                            🚨 Record No-Show
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- No-Shows History -->
<div class="noshow-table">
    <h3 style="padding: 1rem; margin: 0; color: var(--text-primary);">No-Show History</h3>
    <?php if(empty($no_shows)): ?>
    <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
        No no-show records found.
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>Customer</th>
                <th>Party Size</th>
                <th>Reservation Details</th>
                <th>Reason</th>
                <th>Action</th>
                <th>Reported By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($no_shows as $noshow): ?>
            <tr>
                <td><?= date('M j, Y g:i A', strtotime($noshow['reported_at'])) ?></td>
                <td>
                    <div><?= htmlspecialchars($noshow['customer_name']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($noshow['customer_email']) ?></div>
                </td>
                <td><?= $noshow['party_size'] ?></td>
                <td>
                    <div><?= date('M j, Y', strtotime($noshow['reservation_date'])) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= date('g:i A', strtotime($noshow['reservation_time'])) ?></div>
                </td>
                <td><span class="reason-badge"><?= htmlspecialchars($noshow['reason']) ?></span></td>
                <td><span class="action-badge"><?= htmlspecialchars($noshow['action_taken']) ?></span></td>
                <td><?= htmlspecialchars($noshow['reported_by_name']) ?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this no-show record?')">
                        <input type="hidden" name="noshow_id" value="<?= $noshow['id'] ?>">
                        <button type="submit" name="delete_noshow" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

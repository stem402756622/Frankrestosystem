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
</div>

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

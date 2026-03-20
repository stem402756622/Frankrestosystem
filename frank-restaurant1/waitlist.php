<?php
$pageTitle    = 'Waitlist Management';
$pageSubtitle = 'Manage customer queue';
require_once 'includes/header.php';

if (!in_array($role, ['admin','manager','staff'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wid = intval($_POST['waitlist_id']);
    $action = $_POST['action'];
    
    if ($action === 'notify') {
        try {
            $w = db()->fetchOne("SELECT * FROM waitlist WHERE id=?", [$wid]);
            if ($w) {
                require_once 'includes/mailer.php';
                $subject = "Table Available - Frank Restaurant";
                $msg = "Dear " . htmlspecialchars($w['customer_name']) . ",<br><br>Good news! A table for " . $w['party_size'] . " people is now available for your requested time (" . $w['requested_date'] . " " . $w['requested_time'] . ").<br><br>Please call us or book online immediately to secure it.";
                sendEmail($w['email'], $subject, $msg);
                
                db()->execute("UPDATE waitlist SET status='notified' WHERE id=?", [$wid]);
                redirect('waitlist.php', 'Customer notified.', 'success');
            }
        } catch (Exception $e) {
            redirect('waitlist.php', 'Waitlist table not available. Please contact administrator.', 'error');
        }
    }
    
    if ($action === 'convert') {
        try {
            $w = db()->fetchOne("SELECT * FROM waitlist WHERE id=?", [$wid]);
            if ($w) {
                $u = db()->fetchOne("SELECT user_id FROM users WHERE email=?", [$w['email']]);
                if ($u) {
                    $uid = $u['user_id'];
                    $rid = db()->insert(
                        "INSERT INTO reservations (user_id, reservation_date, reservation_time, party_size, status) VALUES (?,?,?,?,'confirmed')",
                        [$uid, $w['requested_date'], $w['requested_time'], $w['party_size']]
                    );
                    
                    db()->execute("UPDATE waitlist SET status='converted' WHERE id=?", [$wid]);
                    redirect('waitlist.php', 'Converted to reservation #' . $rid, 'success');
                } else {
                    redirect('waitlist.php', 'User not found. Please register customer first.', 'error');
                }
            }
        } catch (Exception $e) {
            redirect('waitlist.php', 'Waitlist table not available. Please contact administrator.', 'error');
        }
    }
    
    if ($action === 'remove') {
        try {
            db()->execute("DELETE FROM waitlist WHERE id=?", [$wid]);
            redirect('waitlist.php', 'Removed from waitlist.', 'success');
        } catch (Exception $e) {
            redirect('waitlist.php', 'Waitlist table not available. Please contact administrator.', 'error');
        }
    }
}

try {
    $waitlist = db()->fetchAll("SELECT * FROM waitlist WHERE status IN ('waiting','notified') ORDER BY created_at ASC");
} catch (Exception $e) {
    $waitlist = []; // Empty array if table doesn't exist
    error_log("Waitlist table not found: " . $e->getMessage());
}
?>

<div class="card">
    <h3 class="card-title mb-4">Current Waitlist</h3>
    <?php if($waitlist): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Request</th>
                    <th>Status</th>
                    <th>Joined At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($waitlist as $w): ?>
                <tr>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($w['customer_name']) ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($w['email']) ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($w['phone']) ?></div>
                    </td>
                    <td>
                        <div><?= date('M j, Y', strtotime($w['requested_date'])) ?></div>
                        <div class="text-xs text-muted"><?= date('g:i A', strtotime($w['requested_time'])) ?> • <?= $w['party_size'] ?> ppl</div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $w['status']==='notified'?'info':'warning' ?>"><?= ucfirst($w['status']) ?></span>
                    </td>
                    <td><span class="text-xs text-muted"><?= date('M j g:i A', strtotime($w['created_at'])) ?></span></td>
                    <td>
                        <div class="flex gap-1">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="waitlist_id" value="<?= $w['id'] ?>">
                                <input type="hidden" name="action" value="notify">
                                <button type="submit" class="btn btn-sm btn-primary" title="Notify Table Available">🔔</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="waitlist_id" value="<?= $w['id'] ?>">
                                <input type="hidden" name="action" value="convert">
                                <button type="submit" class="btn btn-sm btn-success" title="Convert to Reservation">✅</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remove from waitlist?')">
                                <input type="hidden" name="waitlist_id" value="<?= $w['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn btn-sm btn-danger" title="Remove">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-text">Waitlist is empty.</div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

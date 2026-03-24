<?php
$pageTitle    = 'Waitlist Management';
$pageSubtitle = 'Manage customer queue';
require_once 'includes/header.php';

if (!in_array($role, ['admin','manager','staff'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wid = intval($_POST['waitlist_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    // Add new waitlist entry
    if ($action === 'add') {
        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $party_size = intval($_POST['party_size'] ?? 0);
        $requested_date = sanitize($_POST['requested_date'] ?? '');
        $requested_time = sanitize($_POST['requested_time'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'normal');
        $notes = sanitize($_POST['notes'] ?? '');
        
        if (!$customer_name || !$email || !$party_size || !$requested_date || !$requested_time) {
            redirect('waitlist.php', 'Please fill in all required fields.', 'error');
        } elseif ($party_size < 1 || $party_size > 20) {
            redirect('waitlist.php', 'Party size must be between 1 and 20.', 'error');
        } else {
            try {
                // Check if customer is already on waitlist for same date/time
                $existing = db()->fetchOne(
                    "SELECT id FROM waitlist WHERE email=? AND requested_date=? AND requested_time=? AND status IN ('waiting','notified')",
                    [$email, $requested_date, $requested_time]
                );
                
                if ($existing) {
                    redirect('waitlist.php', 'Customer is already on waitlist for this time.', 'error');
                }
                
                // Calculate estimated wait time based on current queue
                $wait_time = calculateWaitTime($party_size, $priority);
                
                db()->execute(
                    "INSERT INTO waitlist (customer_name, email, phone, party_size, requested_date, requested_time, priority, notes, estimated_wait_time) VALUES (?,?,?,?,?,?,?,?,?)",
                    [$customer_name, $email, $phone, $party_size, $requested_date, $requested_time, $priority, $notes, $wait_time]
                );
                
                redirect('waitlist.php', 'Added to waitlist successfully.', 'success');
            } catch (Exception $e) {
                redirect('waitlist.php', 'Error adding to waitlist. Please contact administrator.', 'error');
            }
        }
    }
    
    if ($action === 'notify') {
        try {
            $w = db()->fetchOne("SELECT * FROM waitlist WHERE id=?", [$wid]);
            if ($w) {
                require_once 'includes/mailer.php';
                $subject = "Table Available - Frank Restaurant";
                $msg = "Dear " . htmlspecialchars($w['customer_name']) . ",<br><br>Good news! A table for " . $w['party_size'] . " people is now available for your requested time (" . $w['requested_date'] . " " . $w['requested_time'] . ").<br><br>Please call us or book online immediately to secure it.<br><br>Estimated wait time: " . $w['estimated_wait_time'] . " minutes";
                sendEmail($w['email'], $subject, $msg);
                
                db()->execute("UPDATE waitlist SET status='notified', notified_at=NOW() WHERE id=?", [$wid]);
                redirect('waitlist.php', 'Customer notified successfully.', 'success');
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
                    
                    db()->execute("UPDATE waitlist SET status='converted', converted_at=NOW() WHERE id=?", [$wid]);
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
    
    if ($action === 'update_priority') {
        $new_priority = sanitize($_POST['new_priority'] ?? 'normal');
        try {
            db()->execute("UPDATE waitlist SET priority=? WHERE id=?", [$new_priority, $wid]);
            redirect('waitlist.php', 'Priority updated successfully.', 'success');
        } catch (Exception $e) {
            redirect('waitlist.php', 'Error updating priority.', 'error');
        }
    }
}

// Calculate estimated wait time
function calculateWaitTime($party_size, $priority) {
    try {
        $current_queue = db()->fetchOne("SELECT COUNT(*) as count FROM waitlist WHERE status IN ('waiting','notified')");
        $base_wait = $current_queue['count'] * 15; // 15 minutes per party
        
        // Adjust based on party size
        $size_multiplier = $party_size > 4 ? 1.5 : 1.0;
        
        // Adjust based on priority
        $priority_multiplier = match($priority) {
            'urgent' => 0.5,
            'high' => 0.7,
            'normal' => 1.0,
            'low' => 1.3,
            default => 1.0
        };
        
        return round($base_wait * $size_multiplier * $priority_multiplier);
    } catch (Exception $e) {
        return 30; // Default 30 minutes
    }
}

// Get filter parameters
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? 'all');
$priority_filter = sanitize($_GET['priority'] ?? 'all');
$date_filter = sanitize($_GET['date'] ?? '');

// Build query with filters
$where_conditions = ["status IN ('waiting','notified')"];
$params = [];

if ($search) {
    $where_conditions[] = "(customer_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "priority = ?";
    $params[] = $priority_filter;
}

if ($date_filter) {
    $where_conditions[] = "requested_date = ?";
    $params[] = $date_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);
$order_by = "ORDER BY 
    CASE priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'normal' THEN 3 
        WHEN 'low' THEN 4 
        ELSE 5 
    END, created_at ASC";

try {
    $waitlist = db()->fetchAll("SELECT * FROM waitlist $where_clause $order_by", $params);
    
    // Get statistics
    $stats = [
        'total' => db()->fetchOne("SELECT COUNT(*) as count FROM waitlist WHERE status IN ('waiting','notified')")['count'],
        'waiting' => db()->fetchOne("SELECT COUNT(*) as count FROM waitlist WHERE status='waiting'")['count'],
        'notified' => db()->fetchOne("SELECT COUNT(*) as count FROM waitlist WHERE status='notified'")['count'],
        'urgent' => db()->fetchOne("SELECT COUNT(*) as count FROM waitlist WHERE priority='urgent' AND status IN ('waiting','notified')")['count'],
        'avg_wait_time' => db()->fetchOne("SELECT AVG(estimated_wait_time) as avg FROM waitlist WHERE status IN ('waiting','notified')")['avg'] ?? 0
    ];
    
} catch (Exception $e) {
    $waitlist = []; // Empty array if table doesn't exist
    $stats = ['total' => 0, 'waiting' => 0, 'notified' => 0, 'urgent' => 0, 'avg_wait_time' => 0];
    error_log("Waitlist table not found: " . $e->getMessage());
}
?>

<!-- Statistics Cards -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Waiting</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['waiting'] ?></div>
        <div class="stat-label">Waiting</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['notified'] ?></div>
        <div class="stat-label">Notified</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $stats['urgent'] ?></div>
        <div class="stat-label">Urgent</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= round($stats['avg_wait_time']) ?>m</div>
        <div class="stat-label">Avg Wait</div>
    </div>
</div>

<!-- Add to Waitlist Form -->
<div class="card mb-4">
    <h3 class="card-title mb-4">Add to Waitlist</h3>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label class="form-label">Customer Name *</label>
            <input type="text" name="customer_name" class="form-control" required 
                   placeholder="John Doe" maxlength="100">
        </div>
        
        <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required 
                   placeholder="john@example.com" maxlength="100">
        </div>
        
        <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control" 
                   placeholder="+1234567890" maxlength="20">
        </div>
        
        <div class="form-group">
            <label class="form-label">Party Size *</label>
            <input type="number" name="party_size" class="form-control" required 
                   min="1" max="20" placeholder="4">
        </div>
        
        <div class="form-group">
            <label class="form-label">Requested Date *</label>
            <input type="date" name="requested_date" class="form-control" required 
                   min="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Requested Time *</label>
            <input type="time" name="requested_time" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
                <option value="low">Low</option>
                <option value="normal" selected>Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>
        
        <div class="form-group md:col-span-2">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" 
                      placeholder="Special requests or notes..." maxlength="500"></textarea>
        </div>
        
        <div class="form-group md:col-span-2 lg:col-span-3">
            <button type="submit" class="btn btn-primary">
                ➕ Add to Waitlist
            </button>
        </div>
    </form>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Current Waitlist</h3>
        <form method="GET" class="flex gap-2 items-end">
            <div class="form-group">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search customers..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="waiting" <?= $status_filter === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                    <option value="notified" <?= $status_filter === 'notified' ? 'selected' : '' ?>>Notified</option>
                </select>
            </div>
            <div class="form-group">
                <select name="priority" class="form-control">
                    <option value="all" <?= $priority_filter === 'all' ? 'selected' : '' ?>>All Priority</option>
                    <option value="urgent" <?= $priority_filter === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="high" <?= $priority_filter === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="normal" <?= $priority_filter === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="low" <?= $priority_filter === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="date" class="form-control" 
                       value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <button type="submit" class="btn btn-secondary">🔍 Filter</button>
            <a href="waitlist.php" class="btn btn-outline-secondary">🔄 Clear</a>
        </form>
    </div>
    
    <?php if($waitlist): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Request</th>
                    <th>Priority</th>
                    <th>Est. Wait</th>
                    <th>Status</th>
                    <th>Joined At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($waitlist as $w): ?>
                <tr class="<?= $w['priority'] === 'urgent' ? 'urgent-row' : '' ?>">
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($w['customer_name']) ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($w['email']) ?></div>
                        <?php if($w['phone']): ?>
                            <div class="text-xs text-muted"><?= htmlspecialchars($w['phone']) ?></div>
                        <?php endif; ?>
                        <?php if($w['notes']): ?>
                            <div class="text-xs text-primary mt-1"><?= htmlspecialchars(substr($w['notes'], 0, 50)) ?>...</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= date('M j, Y', strtotime($w['requested_date'])) ?></div>
                        <div class="text-xs text-muted"><?= date('g:i A', strtotime($w['requested_time'])) ?> • <?= $w['party_size'] ?> ppl</div>
                    </td>
                    <td>
                        <span class="badge badge-<?= match($w['priority']) {
                            'urgent' => 'danger',
                            'high' => 'warning', 
                            'normal' => 'info',
                            'low' => 'secondary',
                            default => 'secondary'
                        } ?>"><?= ucfirst($w['priority']) ?></span>
                    </td>
                    <td>
                        <div class="fw-600"><?= $w['estimated_wait_time'] ?>m</div>
                        <?php if($w['notified_at']): ?>
                            <div class="text-xs text-muted">Notified <?= date('g:i A', strtotime($w['notified_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $w['status']==='notified'?'info':'warning' ?>"><?= ucfirst($w['status']) ?></span>
                    </td>
                    <td><span class="text-xs text-muted"><?= date('M j g:i A', strtotime($w['created_at'])) ?></span></td>
                    <td>
                        <div class="flex gap-1 flex-wrap">
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
                            <form method="POST" style="display:inline;" class="inline-form">
                                <input type="hidden" name="waitlist_id" value="<?= $w['id'] ?>">
                                <input type="hidden" name="action" value="update_priority">
                                <select name="new_priority" class="form-control form-control-sm" onchange="this.form.submit()">
                                    <option value="low" <?= $w['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                    <option value="normal" <?= $w['priority'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="high" <?= $w['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                    <option value="urgent" <?= $w['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                </select>
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
        <div class="empty-text">No waitlist entries found.</div>
        <p class="text-muted">Try adjusting your filters or add customers to the waitlist.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1rem;
    text-align: center;
    box-shadow: var(--shadow);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-primary);
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.urgent-row {
    background: rgba(239, 68, 68, 0.1) !important;
    border-left: 4px solid var(--danger);
}

.inline-form {
    display: inline;
}

.form-control-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .flex.gap-1.flex-wrap {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>

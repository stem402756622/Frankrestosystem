<?php
$pageTitle    = 'Customers';
$pageSubtitle = 'Customer directory';
require_once 'includes/header.php';

if (!hasAccess('customers')) redirect('index.php');

// Handle VIP toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'toggle_vip') {
    $uid = intval($_POST['user_id']);
    db()->execute("UPDATE users SET vip_status = NOT vip_status WHERE user_id=?", [$uid]);
    redirect('customers.php', 'Customer VIP status updated.', 'success');
}

$search = sanitize($_GET['q'] ?? '');
$filter = sanitize($_GET['filter'] ?? '');
$where = "role='customer'";
$params = [];

if ($search) { $where .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $params = ["%$search%","%$search%","%$search%"]; }
if ($filter === 'vip') { $where .= " AND vip_status=1"; }

$customers = db()->fetchAll("SELECT * FROM users WHERE $where ORDER BY created_at DESC", $params);

// Stats
$total   = db()->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE role='customer'")['cnt'] ?? 0;
$vipCount= db()->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE role='customer' AND vip_status=1")['cnt'] ?? 0;
$newThis = db()->fetchOne("SELECT COUNT(*) as cnt FROM users WHERE role='customer' AND MONTH(created_at)=MONTH(NOW())")['cnt'] ?? 0;
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Customers</h2>
        <p class="section-subtitle"><?= $total ?> registered customers</p>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-4">
    <div class="stat-card animate-in">
        <div class="stat-icon">👥</div>
        <div class="stat-value" data-counter="<?= $total ?>"><?= $total ?></div>
        <div class="stat-label">Total Customers</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">👑</div>
        <div class="stat-value" data-counter="<?= $vipCount ?>"><?= $vipCount ?></div>
        <div class="stat-label">VIP Members</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">🆕</div>
        <div class="stat-value" data-counter="<?= $newThis ?>"><?= $newThis ?></div>
        <div class="stat-label">New This Month</div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <form method="GET" class="flex gap-2">
        <div class="flex-1">
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" class="form-control" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <select name="filter" class="form-control" style="width:160px;">
            <option value="">All Customers</option>
            <option value="vip" <?= $filter==='vip'?'selected':'' ?>>👑 VIP Only</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="customers.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<!-- Customer Table -->
<div class="card animate-in">
    <?php if($customers): ?>
    <div class="table-container">
        <table id="customersTable">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Loyalty Points</th>
                    <th>Status</th>
                    <th>Reservations</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($customers as $c):
                    $resCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE user_id=?", [$c['user_id']])['cnt'] ?? 0;
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="user-avatar" style="width:36px;height:36px;font-size:0.85rem;flex-shrink:0;">
                                <?= strtoupper(substr($c['full_name'],0,1)) ?>
                            </div>
                            <div>
                                <div class="fw-600"><?= htmlspecialchars($c['full_name']) ?></div>
                                <div class="text-xs text-muted">@<?= htmlspecialchars($c['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm"><?= htmlspecialchars($c['email']) ?></div>
                        <?php if($c['phone']): ?><div class="text-xs text-muted"><?= htmlspecialchars($c['phone']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-primary">⭐ <?= number_format($c['loyalty_points']) ?></span>
                    </td>
                    <td>
                        <?php if($c['vip_status']): ?>
                        <span class="badge badge-vip">👑 VIP</span>
                        <?php else: ?>
                        <span class="badge badge-muted">Regular</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="text-sm fw-600"><?= $resCount ?></span></td>
                    <td class="text-xs text-muted"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                    <td>
                        <div class="flex gap-1">
                            <a href="create_reservation.php?user=<?= $c['user_id'] ?>" class="btn btn-primary btn-sm" data-tooltip="New Reservation">📅</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_vip">
                                <input type="hidden" name="user_id" value="<?= $c['user_id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $c['vip_status'] ? 'btn-warning' : 'btn-secondary' ?>" data-tooltip="<?= $c['vip_status'] ? 'Remove VIP' : 'Make VIP' ?>">
                                    <?= $c['vip_status'] ? '👑' : '⭐' ?>
                                </button>
                            </form>
                            <button class="btn btn-secondary btn-sm" onclick="viewCustomer(<?= htmlspecialchars(json_encode($c)) ?>)" data-tooltip="View Details">👁</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <span class="empty-icon">👥</span>
        <div class="empty-title">No customers found</div>
        <div class="empty-text">Try adjusting your search criteria.</div>
    </div>
    <?php endif; ?>
</div>

<!-- Customer Detail Modal -->
<div id="customerModal" class="modal-overlay" style="display:none;">
    <div class="modal" style="max-width:500px;">
        <div class="modal-header">
            <h3 class="card-title" id="customerModalTitle">Customer Details</h3>
            <button class="btn btn-secondary btn-sm btn-icon" onclick="Modal.close('customerModal')">✕</button>
        </div>
        <div class="modal-body" id="customerModalBody"></div>
    </div>
</div>

<script>
function viewCustomer(c) {
    document.getElementById('customerModalTitle').textContent = c.full_name;
    document.getElementById('customerModalBody').innerHTML = `
        <div style="display:grid;gap:1rem;">
            <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:var(--bg-tertiary);border-radius:var(--radius-sm);">
                <div class="user-avatar" style="width:56px;height:56px;font-size:1.25rem;">${c.full_name.charAt(0).toUpperCase()}</div>
                <div>
                    <div style="font-weight:700;font-size:1.1rem;">${c.full_name}</div>
                    <div style="color:var(--text-secondary);font-size:0.85rem;">@${c.username}</div>
                    ${c.vip_status=='1' ? '<span class="badge badge-vip">👑 VIP Member</span>' : ''}
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;font-size:0.9rem;">
                <div><span style="color:var(--text-muted);font-size:0.78rem;">EMAIL</span><br>${c.email}</div>
                <div><span style="color:var(--text-muted);font-size:0.78rem;">PHONE</span><br>${c.phone || '—'}</div>
                <div><span style="color:var(--text-muted);font-size:0.78rem;">LOYALTY POINTS</span><br><strong>⭐ ${parseInt(c.loyalty_points).toLocaleString()}</strong></div>
                <div><span style="color:var(--text-muted);font-size:0.78rem;">MEMBER SINCE</span><br>${new Date(c.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</div>
            </div>
        </div>`;
    Modal.open('customerModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>

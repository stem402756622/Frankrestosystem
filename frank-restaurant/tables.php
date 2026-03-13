<?php
$pageTitle    = 'Tables';
$pageSubtitle = 'Restaurant floor plan';
require_once 'includes/header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['admin','manager','staff'])) {
    $tid    = intval($_POST['table_id']);
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_status') {
        $status = sanitize($_POST['status']);
        $allowed = ['available','occupied','reserved','maintenance','cleaning'];
        if (in_array($status, $allowed)) {
            if ($status === 'cleaning') {
                db()->execute("UPDATE restaurant_tables SET status=?, last_cleaned=NOW() WHERE table_id=?", [$status, $tid]);
            } else {
                db()->execute("UPDATE restaurant_tables SET status=? WHERE table_id=?", [$status, $tid]);
            }
            redirect('tables.php', 'Table status updated.', 'success');
        }
    }
}

$tables = db()->fetchAll(
    "SELECT t.*, 
            r.reservation_id, r.reservation_date, r.reservation_time, r.party_size, r.occasion, r.status as reservation_status,
            u.full_name, u.email, u.phone, u.vip_status,
            o.order_id, o.total as order_total, o.status as order_status
     FROM restaurant_tables t
     LEFT JOIN reservations r ON t.table_id=r.table_id AND r.status IN ('confirmed', 'seated')
     LEFT JOIN users u ON r.user_id=u.user_id
     LEFT JOIN orders o ON r.reservation_id=o.reservation_id AND o.status!='cancelled'
     ORDER BY t.table_number"
);

// Group table data
$tableData = [];
foreach ($tables as $row) {
    if (!isset($tableData[$row['table_id']])) {
        $tableData[$row['table_id']] = [
            'table_id' => $row['table_id'],
            'table_number' => $row['table_number'],
            'location' => $row['location'],
            'capacity' => $row['capacity'],
            'table_type' => $row['table_type'],
            'status' => $row['status'],
            'last_cleaned' => $row['last_cleaned'],
            'reservations' => [],
            'orders' => []
        ];
    }
    
    if ($row['reservation_id']) {
        $tableData[$row['table_id']]['reservations'][] = [
            'reservation_id' => $row['reservation_id'],
            'reservation_date' => $row['reservation_date'],
            'reservation_time' => $row['reservation_time'],
            'party_size' => $row['party_size'],
            'occasion' => $row['occasion'],
            'status' => $row['reservation_status'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'vip_status' => $row['vip_status']
        ];
    }
    
    if ($row['order_id']) {
        $tableData[$row['table_id']]['orders'][] = [
            'order_id' => $row['order_id'],
            'total' => $row['order_total'],
            'status' => $row['order_status']
        ];
    }
}

// Convert back to array for display
$tables = array_values($tableData);

// Stats
$statusCounts = [];
foreach ($tables as $t) {
    $statusCounts[$t['status']] = ($statusCounts[$t['status']] ?? 0) + 1;
}

$viewMode = sanitize($_GET['view'] ?? 'grid');
$filterStatus = sanitize($_GET['status'] ?? '');
$displayTables = $filterStatus ? array_filter($tables, fn($t) => $t['status'] === $filterStatus) : $tables;

$statusColors = ['available'=>'success','occupied'=>'danger','reserved'=>'warning','maintenance'=>'muted','cleaning'=>'info'];
$statusIcons  = ['available'=>'✅','occupied'=>'🔴','reserved'=>'🟡','maintenance'=>'🔧','cleaning'=>'🧹'];
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Tables</h2>
        <p class="section-subtitle"><?= count($tables) ?> tables total</p>
    </div>
    <div class="flex gap-1">
        <a href="?view=grid" class="btn <?= $viewMode==='grid'?'btn-primary':'btn-secondary' ?> btn-sm">⊞ Grid</a>
        <a href="?view=list" class="btn <?= $viewMode==='list'?'btn-primary':'btn-secondary' ?> btn-sm">☰ List</a>
    </div>
</div>

<!-- Status Summary Cards -->
<div class="stats-grid mb-4">
    <?php foreach(['available','occupied','reserved','cleaning','maintenance'] as $st): ?>
    <?php $cnt = $statusCounts[$st] ?? 0; ?>
    <a href="?status=<?= $filterStatus===$st?'':$st ?>&view=<?= $viewMode ?>" class="stat-card animate-in" style="text-decoration:none;<?= $filterStatus===$st ? 'border-color:var(--accent-primary);' : '' ?>">
        <div class="stat-icon" style="font-size:1.5rem;"><?= $statusIcons[$st] ?></div>
        <div class="stat-value" style="font-size:1.8rem;" data-counter="<?= $cnt ?>"><?= $cnt ?></div>
        <div class="stat-label" style="text-transform:capitalize;"><?= $st ?></div>
    </a>
    <?php endforeach; ?>
</div>

<?php if($filterStatus): ?>
<div class="alert alert-info mb-3">
    Filtered by: <strong><?= ucfirst($filterStatus) ?></strong> tables
    <a href="tables.php?view=<?= $viewMode ?>" style="margin-left:0.5rem;color:inherit;font-weight:700;">✕ Clear</a>
</div>
<?php endif; ?>

<?php if($viewMode === 'grid'): ?>
<!-- GRID VIEW -->
<div class="card animate-in">
    <div class="card-header">
        <h3 class="card-title">Floor Plan</h3>
        <span class="text-xs text-muted">Click a table to manage</span>
    </div>
    <div class="table-grid">
        <?php foreach($displayTables as $t): ?>
        <div class="table-card <?= $t['status'] ?>" onclick="openTableModal(<?= htmlspecialchars(json_encode($t)) ?>)" style="cursor:pointer;">
            <div style="display:flex;align-items:center;justify-content:center;margin-bottom:0.4rem;">
                <span class="table-status-dot dot-<?= $t['status'] ?>"></span>
            </div>
            <div class="table-number"><?= htmlspecialchars($t['table_number']) ?></div>
            <div class="table-capacity">👥 <?= $t['capacity'] ?> seats</div>
            <div class="text-xs text-muted mt-1"><?= htmlspecialchars($t['location']) ?></div>
            
            <?php if(!empty($t['reservations'])): ?>
            <div class="reservation-info" style="margin-top:0.5rem;padding:0.3rem;background:var(--bg-tertiary);border-radius:var(--radius-sm);font-size:0.7rem;">
                <div style="font-weight:600;color:var(--accent-primary);"><?= htmlspecialchars($t['reservations'][0]['full_name']) ?></div>
                <div style="color:var(--text-secondary);"><?= date('M j, g:i A', strtotime($t['reservations'][0]['reservation_date'] . ' ' . $t['reservations'][0]['reservation_time'])) ?></div>
                <div style="color:var(--text-secondary);">👥 <?= $t['reservations'][0]['party_size'] ?> guests</div>
                <?php if($t['reservations'][0]['vip_status']): ?>
                <div style="color:var(--warning);">👑 VIP</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($t['orders'])): ?>
            <div class="order-info" style="margin-top:0.3rem;font-size:0.65rem;color:var(--success);">
                <div>💰 ₱<?= number_format($t['orders'][0]['total'], 2) ?></div>
                <div>Order #<?= $t['orders'][0]['order_id'] ?></div>
            </div>
            <?php endif; ?>
            
            <div class="badge badge-<?= $statusColors[$t['status']] ?> mt-1" style="font-size:0.65rem;"><?= ucfirst($t['status']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<!-- LIST VIEW -->
<div class="card animate-in">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Table #</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Last Cleaned</th>
                    <?php if(in_array($role,['admin','manager','staff'])): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($displayTables as $t): ?>
                <tr>
                    <td><span class="fw-600"><?= htmlspecialchars($t['table_number']) ?></span></td>
                    <td><?= htmlspecialchars($t['location']) ?></td>
                    <td style="text-transform:capitalize;"><?= $t['table_type'] ?></td>
                    <td>👥 <?= $t['capacity'] ?></td>
                    <td><span class="badge badge-<?= $statusColors[$t['status']] ?>"><?= $statusIcons[$t['status']] ?> <?= ucfirst($t['status']) ?></span></td>
                    <td class="text-muted text-xs"><?= $t['last_cleaned'] ? date('M j, g:i A', strtotime($t['last_cleaned'])) : '—' ?></td>
                    <?php if(in_array($role,['admin','manager','staff'])): ?>
                    <td>
                        <form method="POST" class="flex gap-1">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="table_id" value="<?= $t['table_id'] ?>">
                            <select name="status" class="form-control" style="width:auto;padding:0.3rem 0.6rem;font-size:0.8rem;">
                                <?php foreach(['available','occupied','reserved','cleaning','maintenance'] as $s): ?>
                                <option value="<?= $s ?>" <?= $t['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Table Detail Modal -->
<div id="tableModal" class="modal-overlay" style="display:none;">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3 class="card-title" id="modalTableTitle">Table Details</h3>
            <button class="btn btn-secondary btn-sm btn-icon" onclick="Modal.close('tableModal')">✕</button>
        </div>
        <div class="modal-body" id="modalTableBody"></div>
        <?php if(in_array($role, ['admin','manager','staff'])): ?>
        <div class="modal-footer">
            <form method="POST" class="flex gap-1 w-100">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="table_id" id="modalTableId">
                <select name="status" class="form-control flex-1" id="modalTableStatus">
                    <?php foreach(['available','occupied','reserved','cleaning','maintenance'] as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openTableModal(table) {
    document.getElementById('modalTableTitle').textContent = 'Table ' + table.table_number;
    document.getElementById('modalTableId').value = table.table_id;
    document.getElementById('modalTableStatus').value = table.status;

    const statusColors = {available:'#10b981',occupied:'#ef4444',reserved:'#f59e0b',maintenance:'#6b7280',cleaning:'#3b82f6'};
    const statusIcons  = {available:'✅',occupied:'🔴',reserved:'🟡',maintenance:'🔧',cleaning:'🧹'};

    document.getElementById('modalTableBody').innerHTML = `
        <div style="display:grid;gap:0.75rem;">
            <div style="text-align:center;padding:1rem;background:var(--bg-tertiary);border-radius:var(--radius-sm);">
                <div style="font-size:3rem;font-weight:900;font-family:'Playfair Display',serif;color:${statusColors[table.status]}">${table.table_number}</div>
                <div style="color:var(--text-secondary);font-size:0.85rem;">${statusIcons[table.status]} ${table.status.charAt(0).toUpperCase()+table.status.slice(1)}</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.88rem;">
                <div><span style="color:var(--text-muted)">Capacity</span><br><strong>👥 ${table.capacity} seats</strong></div>
                <div><span style="color:var(--text-muted)">Type</span><br><strong style="text-transform:capitalize">${table.table_type}</strong></div>
                <div><span style="color:var(--text-muted)">Location</span><br><strong>${table.location}</strong></div>
                <div><span style="color:var(--text-muted)">Party Size</span><br><strong>${table.min_party_size}–${table.max_party_size}</strong></div>
            </div>
            ${table.features ? `<div style="font-size:0.85rem;color:var(--text-secondary);">✨ ${table.features}</div>` : ''}
        </div>`;
    Modal.open('tableModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>

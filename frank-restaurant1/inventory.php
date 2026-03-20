<?php
$pageTitle    = 'Inventory Management';

require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$role = $_SESSION['role'];

if (!in_array($role, ['admin','manager'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        try {
            $name = sanitize($_POST['name']);
            $unit = sanitize($_POST['unit']);
            $qty  = floatval($_POST['quantity']);
            $cost = floatval($_POST['cost']);
            $min  = floatval($_POST['threshold']);
            $supp = sanitize($_POST['supplier']);
            
            db()->insert(
                "INSERT INTO inventory_items (name, unit, quantity, cost_per_unit, low_stock_threshold, supplier) VALUES (?,?,?,?,?,?)",
                [$name, $unit, $qty, $cost, $min, $supp]
            );
            redirect('inventory.php', 'Item added.', 'success');
        } catch (Exception $e) {
            redirect('inventory.php', 'Inventory tables not available. Please contact administrator.', 'error');
        }
    }
    
    if (isset($_POST['transaction'])) {
        try {
            $id   = intval($_POST['item_id']);
            $type = sanitize($_POST['type']);
            $qty  = floatval($_POST['quantity']);
            $notes= sanitize($_POST['notes']);
            
            // Update stock
            $current = db()->fetchOne("SELECT quantity FROM inventory_items WHERE id=?", [$id]);
            $new_qty = $current['quantity'];
            
            if ($type === 'restock') {
                $new_qty += $qty;
            } else {
                $new_qty -= $qty;
            }
            
            db()->execute("UPDATE inventory_items SET quantity=? WHERE id=?", [$new_qty, $id]);
            
            // Log transaction
            db()->insert(
                "INSERT INTO inventory_transactions (item_id, type, quantity, notes, user_id) VALUES (?,?,?,?,?)",
                [$id, $type, $qty, $notes, $_SESSION['user_id']]
            );
            
            redirect('inventory.php', 'Stock updated.', 'success');
        } catch (Exception $e) {
            redirect('inventory.php', 'Inventory tables not available. Please contact administrator.', 'error');
        }
    }
}

require_once 'includes/header.php';

try {
    $items = db()->fetchAll("SELECT * FROM inventory_items ORDER BY name");
    $low_stock = array_filter($items, function($i) { return $i['quantity'] <= $i['low_stock_threshold']; });
} catch (Exception $e) {
    $items = []; // Empty array if table doesn't exist
    $low_stock = []; // Empty array if table doesn't exist
    error_log("Inventory tables not found: " . $e->getMessage());
}
?>

<?php if($low_stock): ?>
<div class="alert alert-danger mb-4" style="background:var(--danger); color:white; border:1px solid var(--danger); padding:12px; border-radius:var(--radius);">
    <h4 style="font-weight:700; margin-bottom:8px; color:white;">⚠️ Low Stock Alert</h4>
    <ul style="margin:0; padding-left:20px; list-style-type:disc;">
        <?php foreach($low_stock as $i): ?>
        <li style="margin-bottom:4px; color:white;"><?= htmlspecialchars($i['name']) ?> (Current: <?= $i['quantity'] ?> <?= $i['unit'] ?>, Min: <?= $i['low_stock_threshold'] ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius);">
            <h3 class="card-title mb-3" style="color:var(--text-primary);">Add Inventory Item</h3>
            <form method="POST">
                <input type="hidden" name="add_item" value="1">
                <div class="form-group mb-3">
                    <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Item Name</label>
                    <input type="text" name="name" class="form-control" required style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                </div>
                <div class="row">
                    <div class="col-6 form-group mb-3">
                        <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Unit</label>
                        <input type="text" name="unit" class="form-control" required placeholder="kg, pcs, L" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                    </div>
                    <div class="col-6 form-group mb-3">
                        <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Initial Qty</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" required style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 form-group mb-3">
                        <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Cost / Unit</label>
                        <input type="number" step="0.01" name="cost" class="form-control" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                    </div>
                    <div class="col-6 form-group mb-3">
                        <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Low Stock Alert</label>
                        <input type="number" step="0.01" name="threshold" class="form-control" value="10" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Supplier</label>
                    <input type="text" name="supplier" class="form-control" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                </div>
                <button type="submit" class="btn btn-primary w-100" style="background:var(--accent-primary); border-color:var(--accent-primary);">Add Item</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius);">
            <h3 class="card-title mb-3" style="color:var(--text-primary);">Inventory List</h3>
            <table class="table" style="background:var(--bg-card);">
                <thead>
                    <tr style="background:var(--bg-tertiary);">
                        <th style="padding:12px; text-align:left; color:var(--text-primary); border-bottom:1px solid var(--border-color);">Item</th>
                        <th style="padding:12px; text-align:left; color:var(--text-primary); border-bottom:1px solid var(--border-color);">Stock</th>
                        <th style="padding:12px; text-align:left; color:var(--text-primary); border-bottom:1px solid var(--border-color);">Cost</th>
                        <th style="padding:12px; text-align:left; color:var(--text-primary); border-bottom:1px solid var(--border-color);">Supplier</th>
                        <th style="padding:12px; text-align:left; color:var(--text-primary); border-bottom:1px solid var(--border-color);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $i): ?>
                    <tr style="<?= $i['quantity'] <= $i['low_stock_threshold'] ? 'background:rgba(239,68,68,0.1);' : '' ?> border-bottom:1px solid var(--border-color);">
                        <td style="padding:12px;">
                            <div style="font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($i['name']) ?></div>
                            <div style="font-size:0.75rem; color:var(--text-secondary);">Alert at <?= $i['low_stock_threshold'] ?> <?= $i['unit'] ?></div>
                        </td>
                        <td style="padding:12px;">
                            <span class="badge" style="padding:4px 8px; border-radius:var(--radius-sm); font-size:0.75rem; font-weight:600; <?= $i['quantity'] <= $i['low_stock_threshold'] ? 'background:var(--danger); color:white;' : 'background:var(--success); color:white;' ?>">
                                <?= $i['quantity'] ?> <?= $i['unit'] ?>
                            </span>
                        </td>
                        <td style="padding:12px; color:var(--text-primary);">₱<?= number_format($i['cost_per_unit'], 2) ?></td>
                        <td style="padding:12px; color:var(--text-primary);"><?= htmlspecialchars($i['supplier']) ?></td>
                        <td style="padding:12px;">
                            <button class="btn btn-sm btn-secondary" onclick="openTransaction(<?= $i['id'] ?>, '<?= htmlspecialchars($i['name']) ?>')" style="background:var(--accent-secondary); border-color:var(--accent-secondary);">Manage Stock</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transaction Modal -->
<div id="transModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000;">
    <div class="modal-content" style="background:var(--bg-card); color:var(--text-primary); margin:10% auto; padding:20px; width:90%; max-width:450px; border-radius:var(--radius); border:1px solid var(--border-color);">
        <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border-color);">Manage Stock: <span id="itemName"></span></h3>
        <form method="POST">
            <input type="hidden" name="transaction" value="1">
            <input type="hidden" name="item_id" id="itemId">
            
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Action</label>
                <select name="type" class="form-control" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
                    <option value="restock">Restock (+)</option>
                    <option value="used">Used (-)</option>
                    <option value="waste">Waste (-)</option>
                    <option value="adjustment">Adjustment (-)</option>
                </select>
            </div>
            
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Quantity</label>
                <input type="number" step="0.01" name="quantity" class="form-control" required min="0.01" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
            </div>
            
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.85rem;">Notes</label>
                <input type="text" name="notes" class="form-control" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary);">
            </div>
            
            <div style="display:flex; gap:8px; justify-content:flex-end; padding-top:12px; border-top:1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" style="padding:8px 16px; font-size:0.85rem;" onclick="document.getElementById('transModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 16px; font-size:0.85rem;">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTransaction(id, name) {
    document.getElementById('itemId').value = id;
    document.getElementById('itemName').innerText = name;
    document.getElementById('transModal').style.display = 'block';
}
</script>

<?php require_once 'includes/footer.php'; ?>

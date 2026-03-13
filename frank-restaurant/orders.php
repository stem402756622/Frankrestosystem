<?php
$pageTitle    = 'Orders';
$pageSubtitle = 'Order management';
require_once 'includes/header.php';

if (!hasAccess('orders')) redirect('index.php');

// Handle new order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_order') {
    $table_id = intval($_POST['table_id'] ?? 0);
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!empty($items)) {
        // Create order
        $order_id = db()->insert(
            "INSERT INTO orders (user_id, table_id, status, notes) VALUES (?, ?, 'pending', ?)",
            [$role === 'customer' ? $user_id : null, $table_id ?: null, $notes]
        );
        
        // Add order items
        $subtotal = 0;
        foreach ($items as $item_id) {
            $qty = intval($quantities[$item_id] ?? 1);
            if ($qty > 0) {
                $menu_item = db()->fetchOne("SELECT price FROM menu_items WHERE item_id = ? AND is_available = 1", [$item_id]);
                if ($menu_item) {
                    db()->execute(
                        "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)",
                        [$order_id, $item_id, $qty, $menu_item['price']]
                    );
                    $subtotal += $menu_item['price'] * $qty;
                }
            }
        }
        
        // Update order total
        $tax = $subtotal * 0.08; // 8% tax
        $total = $subtotal + $tax;
        db()->execute("UPDATE orders SET subtotal = ?, tax = ?, total = ? WHERE order_id = ?", [$subtotal, $tax, $total, $order_id]);
        
        redirect('orders.php', 'Order created successfully!', 'success');
    } else {
        $error = 'Please select at least one item.';
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_status') {
    $oid    = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    $allowed = ['pending','preorder','preparing','ready','served','completed','cancelled'];
    if (in_array($status, $allowed)) {
        db()->execute("UPDATE orders SET status=? WHERE order_id=?", [$status, $oid]);
        redirect('orders.php', 'Order updated.', 'success');
    }
}

$filterStatus = sanitize($_GET['status'] ?? '');
$where = $role === 'customer' ? "o.user_id=$user_id" : '1=1';
$params = [];
if ($filterStatus) { $where .= " AND o.status=?"; $params[] = $filterStatus; }

$orders = db()->fetchAll(
    "SELECT o.*, u.full_name, t.table_number FROM orders o
     LEFT JOIN users u ON o.user_id=u.user_id
     LEFT JOIN restaurant_tables t ON o.table_id=t.table_id
     WHERE $where ORDER BY o.created_at DESC LIMIT 100",
    $params
);

// Get menu items for ordering
$menu_categories = db()->fetchAll(
    "SELECT mc.*, mi.* FROM menu_categories mc
     LEFT JOIN menu_items mi ON mc.category_id = mi.category_id AND mi.is_available = 1
     ORDER BY mc.sort_order, mi.name"
);

// Group by category
$menu_by_category = [];
foreach ($menu_categories as $item) {
    if ($item['item_id']) {
        $menu_by_category[$item['category_id']]['name'] = $item['name'];
        $menu_by_category[$item['category_id']]['items'][] = $item;
    }
}

// Define food icons
$food_icons = [
    'Appetizers' => '🥗',
    'Main Course' => '🍽️', 
    'Desserts' => '🍰',
    'Beverages' => '🍷',
    'Bruschetta al Pomodoro' => '🍅',
    'Calamari Fritti' => '🦑',
    'Charcuterie Board' => '🧀',
    'Garlic Bread' => '🍞',
    'Frank Signature Steak' => '🥩',
    'Sea Bass Piccata' => '🐟',
    'Grilled Salmon' => '🐠',
    'Mushroom Risotto' => '🍄',
    'Chicken Marsala' => '🍗',
    'Tiramisu' => '🍮',
    'Chocolate Lava Cake' => '🍫',
    'Crème Brûlée' => '🍯',
    'House Wine (Glass)' => '🍷',
    'Craft Cocktails' => '🍸',
    'Fresh Lemonade' => '🍋',
    'Sparkling Water' => '💧'
];

$statusColors = ['pending'=>'warning','preorder'=>'primary','preparing'=>'info','ready'=>'primary','served'=>'success','completed'=>'muted','cancelled'=>'danger'];
$statusIcons  = ['pending'=>'⏳','preorder'=>'🛒','preparing'=>'👨‍🍳','ready'=>'🔔','served'=>'🍽️','completed'=>'✅','cancelled'=>'❌'];

// Today's revenue
$todayRevenue = db()->fetchOne("SELECT COALESCE(SUM(total),0) as r FROM orders WHERE DATE(created_at)=CURDATE() AND status='completed'")['r'] ?? 0;
$todayOrders  = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at)=CURDATE()")['cnt'] ?? 0;
$activeOrders = db()->fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE status IN ('pending','preparing','ready')")['cnt'] ?? 0;

// Get tables for order creation
$tables = db()->fetchAll("SELECT table_id, table_number, status FROM restaurant_tables WHERE status IN ('available', 'occupied') ORDER BY table_number");
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Orders</h2>
        <p class="section-subtitle"><?= count($orders) ?> orders</p>
    </div>
    <?php if(in_array($role, ['admin','manager','staff'])): ?>
    <button class="btn btn-primary" onclick="toggleOrderForm()">+ Create New Order</button>
    <?php endif; ?>
</div>

<?php if(in_array($role, ['admin','manager','staff'])): ?>
<!-- New Order Form -->
<div id="orderForm" class="card mb-4" style="display:none;">
    <div class="card-header">
        <h3 class="card-title">🛒 Create New Order</h3>
        <button class="btn btn-icon btn-secondary" onclick="toggleOrderForm()">✕</button>
    </div>
    
    <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" class="order-form">
        <input type="hidden" name="action" value="create_order">
        
        <!-- Table Selection -->
        <div class="form-group">
            <label class="form-label">Table (Optional)</label>
            <select name="table_id" class="form-control">
                <option value="">Walk-in / No Table</option>
                <?php foreach($tables as $table): ?>
                <option value="<?= $table['table_id'] ?>">
                    Table <?= $table['table_number'] ?> (<?= $table['status'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Menu Categories -->
        <?php foreach($menu_by_category as $category_id => $category): ?>
        <div class="menu-category">
            <h4 class="category-title">
                <?= $food_icons[$category['name']] ?? '🍽️' ?> <?= $category['name'] ?>
            </h4>
            <div class="menu-items-grid">
                <?php foreach($category['items'] as $item): ?>
                <div class="menu-item-card">
                    <div class="menu-item-header">
                        <span class="menu-item-icon"><?= $food_icons[$item['name']] ?? '🍽️' ?></span>
                        <div class="menu-item-info">
                            <div class="menu-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="menu-item-price">₱<?= number_format($item['price'], 2) ?></div>
                        </div>
                        <?php if($item['is_featured']): ?>
                        <span class="featured-badge">⭐</span>
                        <?php endif; ?>
                    </div>
                    <div class="menu-item-description"><?= htmlspecialchars($item['description']) ?></div>
                    <div class="menu-item-actions">
                        <div class="quantity-control">
                            <button type="button" class="btn btn-icon btn-sm" onclick="decreaseQty(<?= $item['item_id'] ?>)">−</button>
                            <input type="number" name="quantities[<?= $item['item_id'] ?>]" id="qty_<?= $item['item_id'] ?>" value="0" min="0" max="20" class="qty-input">
                            <button type="button" class="btn btn-icon btn-sm" onclick="increaseQty(<?= $item['item_id'] ?>)">+</button>
                        </div>
                        <label class="checkbox-label">
                            <input type="checkbox" name="items[]" value="<?= $item['item_id'] ?>" onchange="updateQty(<?= $item['item_id'] ?>)">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Order Notes -->
        <div class="form-group">
            <label class="form-label">Order Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Special requests or dietary restrictions..."></textarea>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">₱0.00</span>
            </div>
            <div class="summary-row">
                <span>Tax (8%):</span>
                <span id="tax">₱0.00</span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span id="total">₱0.00</span>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">🛒 Create Order</button>
            <button type="button" class="btn btn-secondary" onclick="toggleOrderForm()">Cancel</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if(in_array($role, ['admin','manager','staff'])): ?>
<div class="stats-grid mb-4">
    <div class="stat-card animate-in">
        <div class="stat-icon">💰</div>
        <div class="stat-value">₱<?= number_format($todayRevenue, 0) ?></div>
        <div class="stat-label">Today's Revenue</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">🧾</div>
        <div class="stat-value" data-counter="<?= $todayOrders ?>"><?= $todayOrders ?></div>
        <div class="stat-label">Orders Today</div>
    </div>
    <div class="stat-card animate-in">
        <div class="stat-icon">⚡</div>
        <div class="stat-value" data-counter="<?= $activeOrders ?>"><?= $activeOrders ?></div>
        <div class="stat-label">Active Orders</div>
    </div>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="card mb-3">
    <div class="flex gap-2" style="flex-wrap:wrap;">
        <a href="orders.php" class="btn <?= !$filterStatus?'btn-primary':'btn-secondary' ?> btn-sm">All</a>
        <?php foreach(['pending','preorder','preparing','ready','served','completed','cancelled'] as $s): ?>
        <a href="?status=<?= $s ?>" class="btn <?= $filterStatus===$s?'btn-primary':'btn-secondary' ?> btn-sm">
            <?= $statusIcons[$s] ?> <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card animate-in">
    <?php if($orders): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <?php if($role!=='customer'): ?><th>Customer</th><?php endif; ?>
                    <th>Table</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Time</th>
                    <?php if($role!=='customer'): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): ?>
                <tr>
                    <td><span class="fw-600">#<?= $o['order_id'] ?></span></td>
                    <?php if($role!=='customer'): ?>
                    <td><?= htmlspecialchars($o['full_name'] ?? 'Walk-in') ?></td>
                    <?php endif; ?>
                    <td><?= $o['table_number'] ? 'T'.$o['table_number'] : '<span class="text-muted">—</span>' ?></td>
                    <td class="fw-600">₱<?= number_format($o['total'], 2) ?></td>
                    <?php 
                    $status = !empty($o['status']) ? $o['status'] : 'pending';
                    ?>
                    <td><span class="badge badge-<?= $statusColors[$status] ?>"><?= $statusIcons[$status] ?> <?= ucfirst($status) ?></span></td>
                    <td class="text-xs text-muted"><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
                    <?php if($role!=='customer'): ?>
                    <td>
                        <form method="POST" class="flex gap-1">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="status" class="form-control" style="width:auto;padding:0.3rem 0.6rem;font-size:0.8rem;" onchange="this.form.submit()">
                                <?php foreach(['pending','preorder','preparing','ready','served','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($o['status'] ?? 'pending') === $s ? 'selected' : '' ?>><?= $statusIcons[$s] ?> <?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <span class="empty-icon">🧾</span>
        <div class="empty-title">No orders found</div>
        <div class="empty-text">Orders will appear here once placed.</div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

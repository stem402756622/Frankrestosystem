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
    "SELECT o.*, u.full_name, u.user_id as customer_id, t.table_number FROM orders o
     LEFT JOIN users u ON o.user_id=u.user_id
     LEFT JOIN restaurant_tables t ON o.table_id=t.table_id
     WHERE $where ORDER BY o.created_at DESC LIMIT 100",
    $params
);

// Fetch order items with allergens for each order
$orderItems = [];
$orderAllergens = [];
foreach ($orders as $order) {
    // Try to get items with allergens, fallback to basic query if table doesn't exist
    try {
        $items = db()->fetchAll(
            "SELECT oi.*, mi.name, mi.description,
             (SELECT GROUP_CONCAT(a.name) FROM menu_item_allergens mia JOIN allergens a ON mia.allergen_id=a.id WHERE mia.menu_item_id=mi.item_id) as allergens
             FROM order_items oi
             JOIN menu_items mi ON oi.menu_item_id=mi.item_id
             WHERE oi.order_id=?",
            [$order['order_id']]
        );
    } catch (Exception $e) {
        // Fallback query without allergens if table doesn't exist
        $items = db()->fetchAll(
            "SELECT oi.*, mi.name, mi.description, NULL as allergens
             FROM order_items oi
             JOIN menu_items mi ON oi.menu_item_id=mi.item_id
             WHERE oi.order_id=?",
            [$order['order_id']]
        );
    }
    $orderItems[$order['order_id']] = $items;
    
    // Collect all allergens for this order
    $allergenList = [];
    foreach ($items as $item) {
        if ($item['allergens']) {
            $allergenList = array_merge($allergenList, explode(',', $item['allergens']));
        }
    }
    $orderAllergens[$order['order_id']] = array_unique(array_filter($allergenList));
}

// Get menu items with allergens for ordering
try {
    $menu_items = db()->fetchAll(
        "SELECT mi.item_id, mi.name, mi.description, mi.price, mi.is_available, mi.is_featured,
         mi.category_id, mc.name as category_name, mc.sort_order,
         (SELECT GROUP_CONCAT(a.name) FROM menu_item_allergens mia JOIN allergens a ON mia.allergen_id=a.id WHERE mia.menu_item_id=mi.item_id) as allergens
         FROM menu_items mi
         JOIN menu_categories mc ON mi.category_id = mc.category_id
         WHERE mi.is_available = 1
         ORDER BY mc.sort_order, mi.name"
    );
} catch (Exception $e) {
    // Fallback query without allergens if table doesn't exist
    $menu_items = db()->fetchAll(
        "SELECT mi.item_id, mi.name, mi.description, mi.price, mi.is_available, mi.is_featured,
         mi.category_id, mc.name as category_name, mc.sort_order, NULL as allergens
         FROM menu_items mi
         JOIN menu_categories mc ON mi.category_id = mc.category_id
         WHERE mi.is_available = 1
         ORDER BY mc.sort_order, mi.name"
    );
}

// Group by category
$menu_by_category = [];
foreach ($menu_items as $item) {
    $menu_by_category[$item['category_id']]['name'] = $item['category_name'];
    $menu_by_category[$item['category_id']]['items'][] = $item;
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
    'Sparkling Water' => '💧',
    // Additional items with allergens
    'Peanut Butter Brownie' => '🥜',
    'Shrimp Scampi' => '🦐',
    'Nutella Crepes' => '🌰',
    'Thai Peanut Salad' => '🥗',
    'Almond Crusted Fish' => '🐟',
];

// Add sample items with allergens if not exists
$hasPeanutItem = db()->fetchOne("SELECT COUNT(*) as cnt FROM menu_items WHERE name LIKE '%Peanut%' OR name LIKE '%Nut%'")['cnt'] ?? 0;
if ($hasPeanutItem == 0) {
    try {
        // Add sample allergen items for demonstration
        $dessertCat = db()->fetchOne("SELECT category_id FROM menu_categories WHERE name='Desserts'")['category_id'] ?? 3;
        $appCat = db()->fetchOne("SELECT category_id FROM menu_categories WHERE name='Appetizers'")['category_id'] ?? 1;
        
        // Add Peanut Butter Brownie
        db()->execute(
            "INSERT IGNORE INTO menu_items (category_id, name, description, price, is_available, is_featured) VALUES (?, 'Peanut Butter Brownie', 'Rich chocolate brownie with peanut butter swirl', 14.00, 1, 1)",
            [$dessertCat]
        );
        $newItemId = db()->fetchOne("SELECT item_id FROM menu_items WHERE name='Peanut Butter Brownie'")['item_id'] ?? 0;
        if ($newItemId) {
            $nutId = db()->fetchOne("SELECT id FROM allergens WHERE name='Nuts'")['id'] ?? 1;
            db()->execute("INSERT IGNORE INTO menu_item_allergens (menu_item_id, allergen_id) VALUES (?, ?)", [$newItemId, $nutId]);
        }
    } catch (Exception $e) {
        // Skip allergen data creation if tables don't exist
        error_log("Could not create allergen sample data: " . $e->getMessage());
    }
}

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
        
        <!-- Menu Products with Allergen Info -->
        <style>
            .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
            .product-card { 
                background: var(--bg-card); 
                border: 2px solid var(--border-color); 
                border-radius: var(--radius); 
                padding: 1rem; 
                transition: var(--transition);
                box-shadow: var(--shadow);
            }
            .product-card:hover { 
                border-color: var(--accent-primary); 
                box-shadow: var(--shadow-glow);
                transform: translateY(-2px);
            }
            .product-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
            .product-icon { font-size: 2rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }
            .product-info { flex: 1; }
            .product-name { font-weight: 700; font-size: 1.05rem; margin-bottom: 0.25rem; color: var(--text-primary); }
            .product-price { color: var(--accent-primary); font-weight: 700; font-size: 1.1rem; }
            .product-desc { color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.75rem; line-height: 1.4; }
            .allergen-tags { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.75rem; }
            .allergen-tag { 
                background: var(--danger); 
                color: white; 
                padding: 0.2rem 0.5rem; 
                border-radius: var(--radius-sm); 
                font-size: 0.7rem; 
                font-weight: 600;
                box-shadow: 0 2px 4px rgba(239,68,68,0.3);
            }
            .allergen-safe { 
                background: var(--success);
                box-shadow: 0 2px 4px rgba(16,185,129,0.3);
            }
            .product-actions { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
            .qty-control { display: flex; align-items: center; gap: 0.5rem; }
            .qty-control button { 
                width: 32px; 
                height: 32px; 
                border-radius: var(--radius-sm); 
                border: 1px solid var(--border-color); 
                background: var(--bg-tertiary); 
                color: var(--text-primary);
                cursor: pointer; 
                font-weight: 700;
                transition: var(--transition);
            }
            .qty-control button:hover {
                background: var(--accent-primary);
                border-color: var(--accent-primary);
                color: white;
            }
            .qty-control input { 
                width: 50px; 
                text-align: center; 
                padding: 0.4rem; 
                border: 1px solid var(--border-color); 
                border-radius: var(--radius-sm); 
                background: var(--bg-primary);
                color: var(--text-primary);
            }
            .category-header { 
                font-size: 1.3rem; 
                font-weight: 700; 
                margin: 1.5rem 0 1rem; 
                padding-bottom: 0.5rem; 
                border-bottom: 2px solid var(--accent-primary); 
                color: var(--text-primary);
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            /* Theme-specific glow effects */
            [data-theme="dark"] .product-card {
                background: linear-gradient(145deg, var(--bg-card) 0%, var(--bg-tertiary) 100%);
            }
            [data-theme="ocean"] .product-card {
                border-color: rgba(79,172,254,0.2);
            }
            [data-theme="ocean"] .product-card:hover {
                border-color: #4facfe;
                box-shadow: 0 0 30px rgba(79,172,254,0.3);
            }
        </style>

        <!-- APPETIZERS -->
        <div class="category-header">🥗 Appetizers</div>
        <div class="product-grid">
            <!-- Product 1 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍅</span>
                    <div class="product-info">
                        <div class="product-name">Bruschetta al Pomodoro</div>
                        <div class="product-price">₱180.00</div>
                    </div>
                </div>
                <div class="product-desc">Fresh tomatoes, basil, garlic on toasted artisan bread with extra virgin olive oil</div>
                <div class="allergen-tags">
                    <span class="allergen-tag allergen-safe">✓ Gluten-Free Option</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[1]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="1" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 2 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🦐</span>
                    <div class="product-info">
                        <div class="product-name">Shrimp Cocktail</div>
                        <div class="product-price">₱320.00</div>
                    </div>
                </div>
                <div class="product-desc">Chilled jumbo shrimp with house-made cocktail sauce and lemon</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Shellfish</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[2]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="2" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 3 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🧀</span>
                    <div class="product-info">
                        <div class="product-name">Loaded Nachos</div>
                        <div class="product-price">₱250.00</div>
                    </div>
                </div>
                <div class="product-desc">Tortilla chips topped with cheese, jalapeños, salsa, and sour cream</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Dairy</span>
                    <span class="allergen-tag">⚠️ Gluten</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[3]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="3" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 4 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🥜</span>
                    <div class="product-info">
                        <div class="product-name">Thai Peanut Spring Rolls</div>
                        <div class="product-price">₱220.00</div>
                    </div>
                </div>
                <div class="product-desc">Crispy rolls with peanut sauce, vegetables, and sweet chili dip</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Nuts (Peanuts)</span>
                    <span class="allergen-tag">⚠️ Gluten</span>
                    <span class="allergen-tag">⚠️ Soy</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[4]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="4" style="width:20px;height:20px;"></label>
                </div>
            </div>
        </div>

        <!-- MAIN COURSE -->
        <div class="category-header">🍽️ Main Course</div>
        <div class="product-grid">
            <!-- Product 5 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🥩</span>
                    <div class="product-info">
                        <div class="product-name">Grilled Ribeye Steak</div>
                        <div class="product-price">₱650.00</div>
                    </div>
                </div>
                <div class="product-desc">12oz premium ribeye with herb butter, roasted vegetables, and mashed potatoes</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Dairy (Butter)</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[5]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="5" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 6 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🐟</span>
                    <div class="product-info">
                        <div class="product-name">Almond Crusted Salmon</div>
                        <div class="product-price">₱480.00</div>
                    </div>
                </div>
                <div class="product-desc">Fresh Atlantic salmon with almond crust, lemon butter sauce, and asparagus</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Nuts (Almonds)</span>
                    <span class="allergen-tag">⚠️ Fish</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[6]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="6" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 7 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍝</span>
                    <div class="product-info">
                        <div class="product-name">Creamy Alfredo Pasta</div>
                        <div class="product-price">₱380.00</div>
                    </div>
                </div>
                <div class="product-desc">Fettuccine in rich parmesan cream sauce with grilled chicken</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Gluten (Wheat)</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                    <span class="allergen-tag">⚠️ Eggs</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[7]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="7" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 8 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍗</span>
                    <div class="product-info">
                        <div class="product-name">Classic Fried Chicken</div>
                        <div class="product-price">₱320.00</div>
                    </div>
                </div>
                <div class="product-desc">Crispy buttermilk fried chicken with coleslaw and biscuits</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Gluten</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                    <span class="allergen-tag">⚠️ Eggs</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[8]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="8" style="width:20px;height:20px;"></label>
                </div>
            </div>
        </div>

        <!-- DESSERTS -->
        <div class="category-header">🍰 Desserts</div>
        <div class="product-grid">
            <!-- Product 9 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍰</span>
                    <div class="product-info">
                        <div class="product-name">Chocolate Lava Cake</div>
                        <div class="product-price">₱220.00</div>
                    </div>
                </div>
                <div class="product-desc">Warm chocolate cake with molten center, served with vanilla ice cream</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Gluten</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                    <span class="allergen-tag">⚠️ Eggs</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[9]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="9" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 10 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🥜</span>
                    <div class="product-info">
                        <div class="product-name">Peanut Butter Cheesecake</div>
                        <div class="product-price">₱240.00</div>
                    </div>
                </div>
                <div class="product-desc">Creamy cheesecake with peanut butter swirl and chocolate ganache</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Nuts (Peanuts)</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                    <span class="allergen-tag">⚠️ Gluten</span>
                    <span class="allergen-tag">⚠️ Eggs</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[10]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="10" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 11 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍨</span>
                    <div class="product-info">
                        <div class="product-name">Mango Sorbet</div>
                        <div class="product-price">₱150.00</div>
                    </div>
                </div>
                <div class="product-desc">Refreshing dairy-free mango sorbet with fresh fruit</div>
                <div class="allergen-tags">
                    <span class="allergen-tag allergen-safe">✓ Vegan</span>
                    <span class="allergen-tag allergen-safe">✓ Dairy-Free</span>
                    <span class="allergen-tag allergen-safe">✓ Gluten-Free</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[11]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="11" style="width:20px;height:20px;"></label>
                </div>
            </div>
        </div>

        <!-- BEVERAGES -->
        <div class="category-header">🍷 Beverages</div>
        <div class="product-grid">
            <!-- Product 12 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍹</span>
                    <div class="product-info">
                        <div class="product-name">Tropical Smoothie</div>
                        <div class="product-price">₱180.00</div>
                    </div>
                </div>
                <div class="product-desc">Mango, pineapple, banana with coconut milk</div>
                <div class="allergen-tags">
                    <span class="allergen-tag allergen-safe">✓ Allergen-Free</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[12]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="12" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 13 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">☕</span>
                    <div class="product-info">
                        <div class="product-name">Caramel Macchiato</div>
                        <div class="product-price">₱160.00</div>
                    </div>
                </div>
                <div class="product-desc">Espresso with steamed milk and caramel drizzle</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Dairy</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[13]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="13" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 14 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🥤</span>
                    <div class="product-info">
                        <div class="product-name">House Lemonade</div>
                        <div class="product-price">₱120.00</div>
                    </div>
                </div>
                <div class="product-desc">Fresh-squeezed lemons with mint and honey</div>
                <div class="allergen-tags">
                    <span class="allergen-tag allergen-safe">✓ Allergen-Free</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[14]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="14" style="width:20px;height:20px;"></label>
                </div>
            </div>
        </div>

        <!-- KIDS MENU -->
        <div class="category-header">🧒 Kids Menu</div>
        <div class="product-grid">
            <!-- Product 15 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🍕</span>
                    <div class="product-info">
                        <div class="product-name">Mini Cheese Pizza</div>
                        <div class="product-price">₱180.00</div>
                    </div>
                </div>
                <div class="product-desc">Personal cheese pizza with tomato sauce</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Gluten</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[15]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="15" style="width:20px;height:20px;"></label>
                </div>
            </div>

            <!-- Product 16 -->
            <div class="product-card">
                <div class="product-header">
                    <span class="product-icon">🐟</span>
                    <div class="product-info">
                        <div class="product-name">Fish & Chips</div>
                        <div class="product-price">₱220.00</div>
                    </div>
                </div>
                <div class="product-desc">Battered fish fillets with fries and tartar sauce</div>
                <div class="allergen-tags">
                    <span class="allergen-tag">⚠️ Fish</span>
                    <span class="allergen-tag">⚠️ Gluten</span>
                    <span class="allergen-tag">⚠️ Eggs</span>
                    <span class="allergen-tag">⚠️ Dairy</span>
                </div>
                <div class="product-actions">
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.value=Math.max(0,parseInt(this.nextElementSibling.value)-1)">−</button>
                        <input type="number" name="quantities[16]" value="0" min="0" max="20">
                        <button type="button" onclick="this.previousElementSibling.value=Math.min(20,parseInt(this.previousElementSibling.value)+1)">+</button>
                    </div>
                    <label><input type="checkbox" name="items[]" value="16" style="width:20px;height:20px;"></label>
                </div>
            </div>
        </div>
        
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
    <div class="orders-list" style="display:grid;gap:1rem;">
        <?php foreach($orders as $o): 
            $items = $orderItems[$o['order_id']] ?? [];
            $allergens = $orderAllergens[$o['order_id']] ?? [];
            $status = !empty($o['status']) ? $o['status'] : 'pending';
        ?>
        <div class="order-card" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;">
            <!-- Order Header -->
            <div class="flex justify-between items-start mb-3" style="flex-wrap:wrap;gap:0.5rem;">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="fw-700 text-lg">Order #<?= $o['order_id'] ?></span>
                        <span class="badge badge-<?= $statusColors[$status] ?>"><?= $statusIcons[$status] ?> <?= ucfirst($status) ?></span>
                    </div>
                    <div class="text-sm text-muted">
                        <?= date('M j, Y g:i A', strtotime($o['created_at'])) ?>
                        <?php if($o['table_number']): ?> · Table T<?= $o['table_number'] ?><?php endif; ?>
                        <?php if($o['full_name']): ?> · <?= htmlspecialchars($o['full_name']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="text-right">
                    <div class="fw-700 text-xl text-primary">₱<?= number_format($o['total'], 2) ?></div>
                </div>
            </div>
            
            <!-- Allergen Warning -->
            <?php if(!empty($allergens)): ?>
            <div class="alert alert-danger mb-3" style="padding:0.75rem;border-left:4px solid var(--danger);">
                <div class="flex items-center gap-2">
                    <span style="font-size:1.2rem;">⚠️</span>
                    <div>
                        <div class="fw-600 text-sm">ALLERGEN WARNING</div>
                        <div class="text-sm">This order contains: <strong><?= implode(', ', $allergens) ?></strong></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Order Items -->
            <div class="order-items mb-3" style="background:var(--bg-tertiary);border-radius:var(--radius-sm);padding:0.75rem;">
                <div class="text-xs fw-600 text-muted mb-2" style="text-transform:uppercase;letter-spacing:0.5px;">Order Items</div>
                <?php foreach($items as $item): ?>
                <div class="flex justify-between items-center py-2" style="border-bottom:1px solid var(--border-color);">
                    <div class="flex items-center gap-2">
                        <span style="font-size:1.1rem;"><?= $food_icons[$item['name']] ?? '🍽️' ?></span>
                        <div>
                            <div class="fw-500"><?= htmlspecialchars($item['name']) ?></div>
                            <?php if($item['allergens']): ?>
                            <div class="text-xs text-danger">⚠️ Contains: <?= htmlspecialchars($item['allergens']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="fw-600"><?= $item['quantity'] ?>× ₱<?= number_format($item['unit_price'], 2) ?></div>
                        <div class="text-sm text-muted">₱<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Order Actions -->
            <div class="flex justify-between items-center" style="flex-wrap:wrap;gap:0.5rem;">
                <?php if($o['notes']): ?>
                <div class="text-sm text-muted" style="flex:1;min-width:200px;">
                    <span class="fw-500">Notes:</span> <?= htmlspecialchars($o['notes']) ?>
                </div>
                <?php endif; ?>
                
                <div class="flex gap-2">
                    <!-- Add More Items Button -->
                    <?php if(in_array($role, ['admin','manager','staff']) && !in_array($status, ['completed','cancelled'])): ?>
                    <a href="menu.php?add_to_order=<?= $o['order_id'] ?>" class="btn btn-secondary btn-sm" title="Add more items">
                        ➕ Add Items
                    </a>
                    <?php endif; ?>
                    
                    <!-- Receipt -->
                    <a href="receipt.php?order_id=<?= $o['order_id'] ?>" class="btn btn-secondary btn-sm" title="View Receipt">
                        🧾 Receipt
                    </a>
                    
                    <!-- Invoice (if completed) -->
                    <?php if($status === 'completed'): ?>
                    <a href="invoice.php?id=<?= $o['order_id'] ?>" class="btn btn-primary btn-sm" title="View Invoice">
                        📄 Invoice
                    </a>
                    <?php endif; ?>
                    
                    <!-- Status Update (staff only) -->
                    <?php if($role!=='customer' && !in_array($status, ['completed','cancelled'])): ?>
                    <form method="POST" class="flex gap-1" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                        <select name="status" class="form-control" style="width:auto;padding:0.3rem 0.6rem;font-size:0.8rem;" onchange="this.form.submit()">
                            <?php foreach(['pending','preorder','preparing','ready','served','completed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $statusIcons[$s] ?> <?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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

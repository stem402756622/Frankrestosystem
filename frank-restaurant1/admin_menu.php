<?php
$pageTitle    = 'Menu Management';

require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$role = $_SESSION['role'];

if (!in_array($role, ['admin','manager'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        db()->execute("DELETE FROM menu_items WHERE item_id=?", [intval($_POST['delete_id'])]);
        redirect('admin_menu.php', 'Item deleted.', 'success');
    } else {
        $id = intval($_POST['item_id'] ?? 0);
        $name = sanitize($_POST['name']);
        $desc = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $cat_id = intval($_POST['category_id']);
        $avail = isset($_POST['is_available']) ? 1 : 0;
        $selected_allergens = $_POST['allergens'] ?? [];
        
        if ($id) {
            // Update
            db()->execute(
                "UPDATE menu_items SET name=?, description=?, price=?, category_id=?, is_available=? WHERE item_id=?",
                [$name, $desc, $price, $cat_id, $avail, $id]
            );
            $msg = 'Item updated.';
        } else {
            // Insert
            $id = db()->insert(
                "INSERT INTO menu_items (name, description, price, category_id, is_available) VALUES (?,?,?,?,?)",
                [$name, $desc, $price, $cat_id, $avail]
            );
            $msg = 'Item added.';
        }
        
        // Update allergens
        db()->execute("DELETE FROM menu_item_allergens WHERE menu_item_id=?", [$id], 'i');
        if (!empty($selected_allergens)) {
            foreach ($selected_allergens as $aid) {
                db()->execute("INSERT INTO menu_item_allergens (menu_item_id, allergen_id) VALUES (?,?)", [$id, $aid], 'ii');
            }
        }
        
        redirect('admin_menu.php', $msg, 'success');
    }
}

require_once 'includes/header.php';

$categories = db()->fetchAll("SELECT * FROM menu_categories ORDER BY sort_order");
try {
    $allergens = db()->fetchAll("SELECT * FROM allergens ORDER BY name");
} catch (Exception $e) {
    $allergens = []; // Empty array if table doesn't exist
}

// Fetch menu items from database instead of hardcoded
$menu_items = db()->fetchAll("SELECT mi.*, mc.name as category_name FROM menu_items mi LEFT JOIN menu_categories mc ON mi.category_id = mc.category_id ORDER BY mc.sort_order, mi.name");

// Group by category
$itemsByCategory = [];
foreach($menu_items as $item) {
    $category_name = $item['category_name'] ?? 'Uncategorized';
    $itemsByCategory[$category_name][] = $item;
}

// Map for database operations
$all_items = $menu_items;
?>

<div class="flex justify-between items-center mb-4">
    <h2 class="section-title">Menu Management</h2>
    <button onclick="openModal()" class="btn btn-primary">+ New Item</button>
</div>

<!-- Menu Products List -->
<style>
    .product-list { display: flex; flex-direction: column; gap: 0.75rem; }
    .product-list-item { 
        background: var(--bg-card); 
        border: 1px solid var(--border-color); 
        border-radius: var(--radius); 
        padding: 0.75rem 1rem; 
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .product-list-item:hover { 
        border-color: var(--accent-primary); 
        background: var(--bg-tertiary);
    }
    .product-list-item.unavailable {
        opacity: 0.6;
        border-color: var(--text-muted);
    }
    .product-icon { font-size: 2rem; width: 48px; text-align: center; }
    .product-info { flex: 1; min-width: 0; }
    .product-name { font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 0.15rem; }
    .product-meta { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .product-price { color: var(--accent-primary); font-weight: 700; }
    .product-desc { color: var(--text-secondary); font-size: 0.85rem; }
    .allergen-tags { display: flex; flex-wrap: wrap; gap: 0.3rem; }
    .allergen-tag { 
        background: var(--danger); 
        color: white; 
        padding: 0.15rem 0.4rem; 
        border-radius: var(--radius-sm); 
        font-size: 0.65rem; 
        font-weight: 600;
    }
    .allergen-safe { background: var(--success); }
    .dietary-tag {
        background: var(--accent-primary);
        color: white;
        padding: 0.15rem 0.4rem;
        border-radius: var(--radius-sm);
        font-size: 0.65rem;
        font-weight: 600;
    }
    .product-actions { display: flex; align-items: center; gap: 0.5rem; }
    .status-badge {
        padding: 0.2rem 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.7rem;
        font-weight: 600;
        background: var(--text-muted);
        color: white;
    }
    .category-header { 
        font-size: 1.2rem; 
        font-weight: 700; 
        margin: 1.25rem 0 0.75rem; 
        padding-bottom: 0.4rem; 
        border-bottom: 2px solid var(--accent-primary); 
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>

<?php
$categoryIcons = ['Appetizers' => '🥗', 'Main Course' => '🍽️', 'Desserts' => '🍰', 'Beverages' => '🍷', 'Kids Menu' => '🧒'];
$itemIcons = [
    'Bruschetta al Pomodoro' => '🍅', 'Shrimp Cocktail' => '🦐', 'Loaded Nachos' => '🧀', 'Thai Peanut Spring Rolls' => '🥜',
    'Grilled Ribeye Steak' => '🥩', 'Almond Crusted Salmon' => '🐟', 'Creamy Alfredo Pasta' => '🍝', 'Classic Fried Chicken' => '🍗',
    'Chocolate Lava Cake' => '🍰', 'Peanut Butter Cheesecake' => '🥜', 'Mango Sorbet' => '🍨',
    'Tropical Smoothie' => '🍹', 'Caramel Macchiato' => '☕', 'House Lemonade' => '🥤',
    'Mini Cheese Pizza' => '🍕', 'Fish & Chips' => '🐟'
];
?>

<?php foreach($itemsByCategory as $catName => $catItems): ?>
<div class="category-header"><?= $categoryIcons[$catName] ?? '🍽️' ?> <?= htmlspecialchars($catName) ?></div>
<div class="product-list">
    <?php foreach($catItems as $i): 
        $isAvailable = $i['is_available'];
        
        // Fetch allergens for this item
        try {
            $item_allergens = db()->fetchAll("SELECT a.name FROM allergens a JOIN menu_item_allergens mia ON a.id = mia.allergen_id WHERE mia.menu_item_id = ?", [$i['item_id']]);
            $allergenList = array_column($item_allergens, 'name');
        } catch (Exception $e) {
            $allergenList = []; // Empty array if table doesn't exist
        }
        
        // For now, dietary tags are not stored in database, so we'll use empty array
        $dietaryList = [];
    ?>
    <div class="product-list-item <?= $isAvailable ? '' : 'unavailable' ?>">
        <span class="product-icon"><?= $itemIcons[$i['name']] ?? '🍽️' ?></span>
        <div class="product-info">
            <div class="product-name"><?= htmlspecialchars($i['name']) ?></div>
            <div class="product-desc"><?= htmlspecialchars($i['description']) ?></div>
            <div class="product-meta">
                <span class="product-price">₱<?= number_format($i['price'], 2) ?></span>
                <?php if(!empty($dietaryList)): ?>
                    <?php foreach($dietaryList as $tag): ?>
                    <span class="dietary-tag"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if(!empty($allergenList)): ?>
                    <?php foreach($allergenList as $allergen): ?>
                    <span class="allergen-tag">⚠️ <?= htmlspecialchars($allergen) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="allergen-tag allergen-safe">✓ No Allergens</span>
                <?php endif; ?>
                <?php if(!$isAvailable): ?>
                <span class="status-badge">Unavailable</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="product-actions">
            <button class="btn btn-sm btn-secondary" onclick='editItem(<?= json_encode($i) ?>)'>Edit</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?')">
                <input type="hidden" name="delete_id" value="<?= $i['item_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">✕</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<!-- Modal -->
<div id="itemModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; overflow-y:auto;">
    <div class="modal-content" style="background:var(--bg-card); color:var(--text-primary); margin:3% auto; padding:20px; width:90%; max-width:500px; border-radius:var(--radius); border:1px solid var(--border-color);">
        <h3 id="modalTitle" style="font-size:1.1rem; font-weight:600; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border-color);">Add Menu Item</h3>
        
        <form method="POST">
            <input type="hidden" name="item_id" id="item_id">
            
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.8rem; font-weight:500;">Name</label>
                <input type="text" name="name" id="item_name" required style="width:100%; padding:8px 12px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary); font-size:0.9rem;">
            </div>
            
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.8rem; font-weight:500;">Description</label>
                <textarea name="description" id="item_desc" rows="2" style="width:100%; padding:8px 12px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary); font-size:0.9rem; resize:vertical;"></textarea>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                <div>
                    <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.8rem; font-weight:500;">Category</label>
                    <select name="category_id" id="item_cat" required style="width:100%; padding:8px 12px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary); font-size:0.9rem;">
                        <?php foreach($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.8rem; font-weight:500;">Price (₱)</label>
                    <input type="number" step="0.01" name="price" id="item_price" required style="width:100%; padding:8px 12px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--bg-primary); color:var(--text-primary); font-size:0.9rem;">
                </div>
            </div>
            
            <div style="margin-bottom:12px;">
                <label style="display:block; margin-bottom:6px; color:var(--text-secondary); font-size:0.8rem; font-weight:500;">Dietary Tags</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach(['Vegetarian','Vegan','Gluten-Free','Halal','Spicy'] as $tag): ?>
                    <label style="display:flex; align-items:center; gap:4px; padding:4px 10px; background:var(--bg-tertiary); border-radius:var(--radius-sm); cursor:pointer; font-size:0.8rem;">
                        <input type="checkbox" name="dietary[]" value="<?= $tag ?>" class="item_dietary_<?= $tag ?>" style="width:14px; height:14px;">
                        <span style="color:var(--text-primary);"><?= $tag ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; color:var(--text-secondary); font-size:0.8rem; font-weight:500;">Allergens</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <?php foreach($allergens as $a): ?>
                    <label style="display:flex; align-items:center; gap:4px; padding:4px 10px; background:var(--bg-tertiary); border-radius:var(--radius-sm); cursor:pointer; font-size:0.8rem; border:1px solid var(--border-color);">
                        <input type="checkbox" name="allergens[]" value="<?= $a['id'] ?>" class="item_allergen_<?= $a['id'] ?>" style="width:14px; height:14px;">
                        <span style="color:var(--text-primary);"><?= htmlspecialchars($a['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <label style="display:flex; align-items:center; gap:8px; margin-bottom:16px; cursor:pointer; font-size:0.9rem;">
                <input type="checkbox" name="is_available" id="item_avail" value="1" checked style="width:16px; height:16px;">
                <span style="color:var(--text-primary);">Available for ordering</span>
            </label>
            
            <div style="display:flex; gap:8px; justify-content:flex-end; padding-top:12px; border-top:1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" style="padding:8px 16px; font-size:0.85rem;" onclick="document.getElementById('itemModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 16px; font-size:0.85rem;">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').innerText = 'Add Menu Item';
    document.getElementById('item_id').value = '';
    document.getElementById('item_name').value = '';
    document.getElementById('item_desc').value = '';
    document.getElementById('item_price').value = '';
    document.getElementById('item_cat').value = '';
    document.getElementById('item_avail').checked = true;
    
    // Clear checkboxes
    document.querySelectorAll('input[name="allergens[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="dietary[]"]').forEach(cb => cb.checked = false);
    
    document.getElementById('itemModal').style.display = 'block';
}

function editItem(item) {
    document.getElementById('modalTitle').innerText = 'Edit Menu Item';
    document.getElementById('item_id').value = item.item_id;
    document.getElementById('item_name').value = item.name;
    document.getElementById('item_desc').value = item.description;
    document.getElementById('item_price').value = item.price;
    document.getElementById('item_cat').value = item.category_id;
    document.getElementById('item_avail').checked = item.is_available == 1;
    
    // Clear checkboxes first
    document.querySelectorAll('input[name="allergens[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="dietary[]"]').forEach(cb => cb.checked = false);
    
    // Set allergen checkboxes if data is available
    if (item.allergen_ids) {
        item.allergen_ids.split(',').forEach(id => {
            let cb = document.querySelector('.item_allergen_' + id);
            if(cb) cb.checked = true;
        });
    }
    
    // Set dietary checkboxes if data is available
    if (item.dietary_tags) {
        item.dietary_tags.split(',').forEach(tag => {
            let cb = document.querySelector('.item_dietary_' + tag);
            if(cb) cb.checked = true;
        });
    }
    
    document.getElementById('itemModal').style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('itemModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>

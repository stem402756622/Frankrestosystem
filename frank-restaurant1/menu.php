<?php
$pageTitle    = 'Menu & Ordering';
require_once 'includes/header.php';

// Icon mapping for menu items
$menu_icons = [
    'Bruschetta al Pomodoro' => '🍅',
    'Calamari Fritti' => '🦑',
    'Charcuterie Board' => '🧀',
    'Frank Signature Steak' => '🥩',
    'Sea Bass Piccata' => '🐟',
    'Mushroom Risotto' => '🍄',
    'Chicken Marsala' => '🍗',
    'Tiramisu' => '🍰',
    'Crème Brûlée' => '🍮',
    'House Wine (Glass)' => '🍷',
    'Craft Cocktails' => '🍸',
    'Sparkling Water' => '💧',
    'Salmon Teriyaki' => '🍣',
    'Margherita Pizza' => '🍕',
    'Caesar Salad' => '🥗',
    'Fish and Chips' => '🍟',
    'Pasta Carbonara' => '🍝',
    'Grilled Vegetables' => '🥦',
    'Beef Burger' => '🍔',
    'Fish Tacos' => '🌮',
    'Vegetable Soup' => '🍲',
    'Chocolate Cake' => '🎂',
    'Ice Cream Sundae' => '🍨',
    'Coffee' => '☕',
    'Fresh Juice' => '🧃',
    'Sushi Platter' => '🍱',
    'Ramen Bowl' => '🍜',
    'BBQ Ribs' => '🍖',
    'Garden Salad' => '🥬',
    'Fruit Smoothie' => '🥤',
    'Cheese Platter' => '🧈',
    'Shrimp Scampi' => '🍤',
    'Lobster Bisque' => '🦞',
    'Apple Pie' => '🥧',
    'Brownie Sundae' => '🍫',
    'Iced Tea' => '🧊',
    'Lemonade' => '🍋',
    'Club Sandwich' => '🥪',
    'French Onion Soup' => '🧅',
    'Stuffed Mushrooms' => '🍄',
    'Chocolate Mousse' => '🍫',
    'Panna Cotta' => '🍮',
    'Hot Chocolate' => '☕',
    'Orange Juice' => '🍊',
    'Tomato Soup' => '🍅',
    'Garlic Bread' => '🥖',
    'Caprese Salad' => '🥗',
    'Beef Tacos' => '🌮',
    'Chicken Wings' => '🍗',
    'Onion Rings' => '🧅',
    'Cheesecake' => '🍰',
    'Cobb Salad' => '🥗',
    'Vegetable Stir Fry' => '🥦',
    'Fruit Tart' => '🥧',
    'Mint Tea' => '🍵',
    'Berry Smoothie' => '🥤'
];

// Function to get icon for menu item
function getMenuIcon($itemName) {
    global $menu_icons;
    return $menu_icons[$itemName] ?? '🍽'; // Default icon if not found
}

// Fetch items with allergens
try {
    $items = db()->fetchAll(
        "SELECT mi.*, mc.name as category_name, 
         (SELECT GROUP_CONCAT(a.name) FROM menu_item_allergens mia JOIN allergens a ON mia.allergen_id=a.id WHERE mia.menu_item_id=mi.item_id) as allergens
         FROM menu_items mi 
         LEFT JOIN menu_categories mc ON mi.category_id=mc.category_id 
         WHERE mi.is_available = 1 
         ORDER BY mc.sort_order, mi.name"
    );
} catch (Exception $e) {
    // Fallback query without allergens if table doesn't exist
    $items = db()->fetchAll(
        "SELECT mi.*, mc.name as category_name, NULL as allergens
         FROM menu_items mi 
         LEFT JOIN menu_categories mc ON mi.category_id=mc.category_id 
         WHERE mi.is_available = 1 
         ORDER BY mc.sort_order, mi.name"
    );
}

// Group by category
$menu = [];
foreach ($items as $i) {
    $menu[$i['category_name']][] = $i;
}

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!isLoggedIn()) {
        redirect('login.php', 'Please login to place an order.', 'error');
    }
    
    $cart = $_POST['cart'] ?? []; // format: item_id => quantity
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!empty($cart)) {
        // Create order
        $order_id = db()->insert(
            "INSERT INTO orders (user_id, status, notes) VALUES (?, 'pending', ?)",
            [$user_id, $notes]
        );
        
        $subtotal = 0;
        foreach ($cart as $iid => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $item = db()->fetchOne("SELECT price FROM menu_items WHERE item_id=?", [$iid]);
                if ($item) {
                    db()->execute(
                        "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?,?,?,?)",
                        [$order_id, $iid, $qty, $item['price']]
                    );
                    $subtotal += $item['price'] * $qty;
                }
            }
        }
        
        $tax = $subtotal * 0.08;
        $total = $subtotal + $tax;
        
        // Handle promo code
        $discount = 0;
        if (!empty($_POST['promo_code'])) {
            $code = strtoupper(sanitize($_POST['promo_code']));
            $promo = db()->fetchOne("SELECT * FROM promo_codes WHERE code=? AND is_active=1", [$code]);
            if ($promo) {
                // ... validate again ...
                // Calculate discount
                if ($promo['discount_type'] === 'percentage') {
                    $discount = $subtotal * ($promo['discount_value'] / 100);
                } else {
                    $discount = $promo['discount_value'];
                }
                if ($discount > $total) $discount = $total;
                
                // Update promo usage
                db()->execute("UPDATE promo_codes SET used_count=used_count+1 WHERE id=?", [$promo['id']]);
                // Link promo to order
                db()->execute("UPDATE orders SET promo_code_id=? WHERE order_id=?", [$promo['id'], $order_id]);
            }
        }
        
        $total -= $discount;
        db()->execute("UPDATE orders SET subtotal=?, tax=?, discount_amount=?, total=? WHERE order_id=?", [$subtotal, $tax, $discount, $total, $order_id]);
        
        redirect('payment.php?order_id='.$order_id, 'Order placed successfully! Please proceed to payment.', 'success');
    }
}

$user_favorites = [];
if (isLoggedIn()) {
    $favs = db()->fetchAll("SELECT menu_item_id FROM saved_favorites WHERE user_id=?", [$user_id]);
    $user_favorites = array_column($favs, 'menu_item_id');
}

// Recommendations (Feature 18)
$recommendations = [];
if (isLoggedIn()) {
    $recommendations = db()->fetchAll(
        "SELECT mi.*, COUNT(oi.menu_item_id) as popularity 
         FROM menu_items mi
         JOIN order_items oi ON mi.item_id = oi.menu_item_id
         JOIN orders o ON oi.order_id = o.order_id
         WHERE o.user_id = ? AND mi.is_available = 1
         GROUP BY mi.item_id
         ORDER BY popularity DESC LIMIT 3",
        [$user_id]
    );
}

if (empty($recommendations)) {
    $recommendations = db()->fetchAll(
        "SELECT mi.*, COUNT(oi.id) as popularity 
         FROM menu_items mi
         JOIN order_items oi ON mi.item_id = oi.menu_item_id
         WHERE mi.is_available = 1
         GROUP BY mi.item_id
         ORDER BY popularity DESC LIMIT 3"
    );
}
?>

<div class="flex justify-between items-center mb-4">
    <h2 class="section-title" style="color:var(--text-primary);">Our Menu</h2>
    <div class="flex gap-2">
        <?php if(isLoggedIn()): ?>
        <a href="favorites.php" class="btn btn-outline-primary" style="color:var(--accent-primary); border-color:var(--accent-primary);">❤️ My Favorites</a>
        <?php endif; ?>
        <select id="dietaryFilter" class="form-control" style="background:var(--bg-primary); color:var(--text-primary); border-color:var(--border-color);" onchange="filterMenu()">
            <option value="">All Diets</option>
            <option value="Vegetarian">Vegetarian</option>
            <option value="Vegan">Vegan</option>
            <option value="Gluten-Free">Gluten-Free</option>
            <option value="Halal">Halal</option>
            <option value="Spicy">Spicy</option>
        </select>
        <button type="button" class="btn btn-warning" style="background:var(--warning); border-color:var(--warning);" onclick="document.getElementById('allergySelectModal').style.display='block'">⚠️ Allergies</button>
    </div>
</div>

<!-- Allergy Selection Modal -->
<div id="allergySelectModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1500;">
    <div class="modal-content" style="background:var(--bg-card); color:var(--text-primary); margin:10% auto; padding:20px; width:90%; max-width:450px; border-radius:var(--radius); border:1px solid var(--border-color);">
        <h3 style="font-size:1.1rem; font-weight:600; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border-color);">Select Your Allergies</h3>
        <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
            <?php 
            $all_allergens = db()->fetchAll("SELECT * FROM allergens ORDER BY name");
            foreach($all_allergens as $a): 
            ?>
            <label style="display:flex; align-items:center; gap:8px; color:var(--text-primary);">
                <input type="checkbox" class="user-allergy" value="<?= htmlspecialchars($a['name']) ?>" style="accent-color:var(--accent-primary);">
                <?= htmlspecialchars($a['name']) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-primary w-100" style="background:var(--accent-primary); border-color:var(--accent-primary);" onclick="document.getElementById('allergySelectModal').style.display='none'">Save Preferences</button>
    </div>
</div>

<form method="POST" id="orderForm" onsubmit="return validateAllergies()">
    <input type="hidden" name="action" value="place_order">
    
    <?php if($recommendations): ?>
    <div class="mb-5">
        <h3 style="font-size:1.2rem; font-weight:600; margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--border-color); color:var(--text-primary);">✨ Recommended for You</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach($recommendations as $item): ?>
            <div class="card" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius);">
                <div style="font-weight:600; color:var(--text-primary); margin-bottom:8px;"><?= htmlspecialchars($item['name']) ?></div>
                <div style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:12px;"><?= htmlspecialchars($item['description']) ?></div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:var(--accent-primary); font-weight:600;">₱<?= number_format($item['price'], 2) ?></span>
                    <button type="button" class="btn btn-sm btn-primary" style="background:var(--accent-primary); border-color:var(--accent-primary);" onclick="updateCart(<?= $item['item_id'] ?>, 1)">Add +</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php foreach($menu as $cat => $list): ?>
    <div class="mb-5">
        <h3 style="font-size:1.2rem; font-weight:600; margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--border-color); color:var(--text-primary);"><?= htmlspecialchars($cat) ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach($list as $item): ?>
            <div class="card menu-item-row" data-dietary="<?= htmlspecialchars($item['dietary_tags'] ?? '') ?>" data-allergens="<?= htmlspecialchars($item['allergens'] ?? '') ?>" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius);">
                <div style="display:flex; justify-content:space-between;">
                    <div>
                        <div style="font-weight:600; font-size:1.1rem; display:flex; align-items:center; gap:8px; color:var(--text-primary);">
                            <span style="font-size: 1.2rem;"><?= getMenuIcon(htmlspecialchars($item['name'])) ?></span>
                            <?= htmlspecialchars($item['name']) ?>
                            <span style="color:var(--accent-primary);">₱<?= number_format($item['price'], 2) ?></span>
                        </div>
                        <div style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:8px;"><?= htmlspecialchars($item['description']) ?></div>
                        
                        <div style="display:flex; gap:4px; flex-wrap:wrap; margin-bottom:8px;">
                            <?php if(!empty($item['dietary_tags'])): ?>
                                <?php foreach(explode(',', $item['dietary_tags']) as $tag): ?>
                                <span style="background:var(--success); color:white; padding:2px 6px; border-radius:var(--radius-sm); font-size:0.75rem; font-weight:600;"><?= $tag ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if($item['allergens']): ?>
                                <?php foreach(explode(',', $item['allergens']) as $alg): ?>
                                <span style="background:var(--danger); color:white; padding:2px 6px; border-radius:var(--radius-sm); font-size:0.75rem; font-weight:600;">⚠️ <?= $alg ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                        <button type="button" class="btn btn-sm btn-primary" style="background:var(--accent-primary); border-color:var(--accent-primary);" onclick="updateCart(<?= $item['item_id'] ?>, 1)">Add +</button>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" class="btn btn-sm btn-secondary" style="background:var(--bg-tertiary); color:var(--text-primary); border-color:var(--border-color);" onclick="updateCart(<?= $item['item_id'] ?>, -1)">-</button>
                            <input type="number" name="cart[<?= $item['item_id'] ?>]" value="0" min="0" class="cart-qty" style="width:50px; text-align:center; background:var(--bg-primary); color:var(--text-primary); border:1px solid var(--border-color); border-radius:var(--radius-sm);">
                            <button type="button" class="btn btn-sm btn-secondary" style="background:var(--bg-tertiary); color:var(--text-primary); border-color:var(--border-color);" onclick="updateCart(<?= $item['item_id'] ?>, 1)">+</button>
                        </div>
                        <?php if(isLoggedIn()): ?>
                        <button type="button" class="btn btn-sm" style="background:transparent; color:var(--danger); border:1px solid var(--danger); padding:4px 8px;" onclick="toggleFavorite(<?= $item['item_id'] ?>)">
                            <span class="fav-icon" data-id="<?= $item['item_id'] ?>">♡</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if(isLoggedIn()): ?>
    <div class="fixed-bottom-bar card flex justify-between items-center" style="position:fixed; bottom:20px; left:50%; transform:translateX(-50%); width:90%; max-width:600px; z-index:100; box-shadow: 0 5px 20px rgba(0,0,0,0.2);">
        <div>
            <div class="text-sm text-muted">Total Items: <span id="totalItems">0</span></div>
            <div class="font-bold text-lg">Total: <span id="totalPrice">₱0.00</span></div>
        </div>
        <button type="button" class="btn btn-primary" id="checkoutBtn" disabled onclick="openCheckout()">Checkout</button>
    </div>
    <?php endif; ?>
</form>

<!-- Checkout Modal -->
<div id="checkoutModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1600;">
    <div class="modal-content bg-white p-6 m-auto mt-20 rounded shadow-lg max-w-md" style="background:white; margin:10% auto; padding:20px; width:400px; border-radius:8px;">
        <h3 class="text-xl font-bold mb-4">Checkout</h3>
        
        <div class="mb-4">
            <div class="flex justify-between mb-2">
                <span>Subtotal:</span>
                <span id="checkoutSubtotal">₱0.00</span>
            </div>
            <div class="flex justify-between mb-2 text-success" id="discountRow" style="display:none;">
                <span>Discount:</span>
                <span id="checkoutDiscount">-₱0.00</span>
            </div>
            <div class="flex justify-between font-bold text-lg border-t pt-2">
                <span>Total:</span>
                <span id="checkoutTotal">₱0.00</span>
            </div>
        </div>
        
        <div class="form-group mb-4">
            <label>Promo Code</label>
            <div class="flex gap-2">
                <input type="text" id="promoCode" class="form-control" placeholder="Enter code">
                <button type="button" class="btn btn-secondary" onclick="applyPromo()">Apply</button>
            </div>
            <small id="promoMessage" class="text-muted"></small>
            <input type="hidden" name="promo_code" id="appliedPromoCode">
        </div>
        
        <div class="form-group mb-4">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Special requests..."></textarea>
        </div>
        
        <div class="flex gap-2">
            <button type="button" class="btn btn-secondary flex-1" onclick="document.getElementById('checkoutModal').style.display='none'">Cancel</button>
            <button type="button" class="btn btn-primary flex-1" onclick="submitOrder()">Confirm Order</button>
        </div>
    </div>
</div>

<!-- Allergy Warning Modal -->
<div id="allergyModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
    <div class="modal-content bg-white p-6 m-auto mt-20 rounded shadow-lg max-w-md">
        <h3 class="text-xl font-bold text-danger mb-4">⚠️ Allergy Warning</h3>
        <p class="mb-4">The following items in your order contain allergens you selected:</p>
        <ul id="allergyList" class="list-disc pl-5 mb-4 text-danger"></ul>
        <p class="mb-4 text-sm">Do you want to proceed?</p>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-secondary" onclick="closeAllergyModal()">Go Back</button>
            <button type="button" class="btn btn-danger" onclick="submitOrder()">Proceed Anyway</button>
        </div>
    </div>
</div>

<script>
let prices = {};
<?php foreach($items as $i): ?>
prices[<?= $i['item_id'] ?>] = <?= $i['price'] ?>;
<?php endforeach; ?>

function updateCart(id, change) {
    let el = document.getElementById('qty_' + id);
    if (!el) return;
    let val = parseInt(el.value) || 0;
    val += change;
    if (val < 0) val = 0;
    el.value = val;
    calcTotal();
}

function calcTotal() {
    let total = 0;
    let count = 0;
    document.querySelectorAll('input[name^="cart"]').forEach(inp => {
        let qty = parseInt(inp.value) || 0;
        if (qty > 0) {
            let id = inp.id.replace('qty_', '');
            total += qty * (prices[id] || 0);
            count += qty;
        }
    });
    
    document.getElementById('totalItems').innerText = count;
    document.getElementById('totalPrice').innerText = '₱' + total.toFixed(2);
    let btn = document.getElementById('checkoutBtn');
    if(btn) btn.disabled = count === 0;
}

function filterMenu() {
    let filter = document.getElementById('dietaryFilter').value;
    document.querySelectorAll('.menu-item-row').forEach(row => {
        let tags = row.dataset.dietary || '';
        if (!filter || tags.includes(filter)) {
            row.style.display = 'block';
        } else {
            row.style.display = 'none';
        }
    });
}

function validateAllergies() {
    // Get selected allergies
    let selectedAllergies = [];
    document.querySelectorAll('.user-allergy:checked').forEach(cb => selectedAllergies.push(cb.value));
    
    if (selectedAllergies.length === 0) return true;
    
    let conflicts = [];
    
    document.querySelectorAll('input[name^="cart"]').forEach(inp => {
        if (parseInt(inp.value) > 0) {
            let itemRow = inp.closest('.menu-item-row');
            let itemAllergens = itemRow.dataset.allergens ? itemRow.dataset.allergens.split(',') : [];
            let itemName = itemRow.querySelector('.font-bold').innerText;
            
            // Check intersection
            let hasAllergy = selectedAllergies.some(alg => itemAllergens.includes(alg));
            if (hasAllergy) {
                conflicts.push(itemName);
            }
        }
    });
    
    if (conflicts.length > 0) {
        document.getElementById('allergyList').innerHTML = conflicts.map(c => `<li>${c}</li>`).join('');
        document.getElementById('allergyModal').style.display = 'block';
        return false; // Prevent submission
    }
    
    return true;
}

function submitOrder() {
    // Bypass validation check by removing onsubmit handler temporarily or manually submitting
    document.getElementById('orderForm').removeAttribute('onsubmit');
    document.getElementById('orderForm').submit();
}

function closeAllergyModal() {
    document.getElementById('allergyModal').style.display = 'none';
}

let currentSubtotal = 0;
let currentDiscount = 0;

function openCheckout() {
    // Validate allergies first
    if (!validateAllergies()) return;
    
    // Calculate subtotal
    currentSubtotal = 0;
    document.querySelectorAll('input[name^="cart"]').forEach(inp => {
        let qty = parseInt(inp.value) || 0;
        if (qty > 0) {
            let id = inp.id.replace('qty_', '');
            currentSubtotal += qty * (prices[id] || 0);
        }
    });
    
    document.getElementById('checkoutSubtotal').innerText = '₱' + currentSubtotal.toFixed(2);
    document.getElementById('checkoutTotal').innerText = '₱' + currentSubtotal.toFixed(2);
    document.getElementById('checkoutModal').style.display = 'block';
}

function applyPromo() {
    let code = document.getElementById('promoCode').value;
    if (!code) return;
    
    fetch('ajax_promo.php?code=' + encodeURIComponent(code) + '&amount=' + currentSubtotal)
    .then(res => res.json())
    .then(data => {
        let msg = document.getElementById('promoMessage');
        if (data.valid) {
            currentDiscount = data.discount;
            document.getElementById('appliedPromoCode').value = data.code;
            document.getElementById('discountRow').style.display = 'flex';
            document.getElementById('checkoutDiscount').innerText = '-₱' + currentDiscount.toFixed(2);
            document.getElementById('checkoutTotal').innerText = '₱' + (currentSubtotal - currentDiscount).toFixed(2);
            msg.className = 'text-success';
            msg.innerText = data.message;
        } else {
            currentDiscount = 0;
            document.getElementById('appliedPromoCode').value = '';
            document.getElementById('discountRow').style.display = 'none';
            document.getElementById('checkoutTotal').innerText = '₱' + currentSubtotal.toFixed(2);
            msg.className = 'text-danger';
            msg.innerText = data.message;
        }
    });
}

function toggleFavorite(itemId, btn) {
    fetch('ajax_favorite.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'item_id=' + itemId
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            btn.innerText = data.is_favorite ? '❤️' : '♡';
        } else {
            alert(data.message || 'Error updating favorite');
        }
    });
}

window.onload = function() {
    let urlParams = new URLSearchParams(window.location.search);
    let addId = urlParams.get('add');
    if (addId) {
        updateCart(parseInt(addId), 1);
        let el = document.getElementById('qty_' + addId);
        if (el) el.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
};
</script>

<?php require_once 'includes/footer.php'; ?>

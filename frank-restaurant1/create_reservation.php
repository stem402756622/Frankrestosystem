<?php
$pageTitle    = 'New Reservation';
$pageSubtitle = 'Book a table';
require_once 'includes/header.php';

$tables = db()->fetchAll("SELECT * FROM restaurant_tables ORDER BY table_number");
$customers = [];
if (in_array($role, ['admin','manager','staff'])) {
    $customers = db()->fetchAll("SELECT user_id, full_name, email, phone, preferred_table_id, vip_status FROM users WHERE role='customer' ORDER BY full_name");
}

// Get menu items for pre-ordering
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

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res_user_id   = $role === 'customer' ? $user_id : intval($_POST['user_id'] ?? 0);
    $res_date      = sanitize($_POST['reservation_date'] ?? '');
    $res_time      = sanitize($_POST['reservation_time'] ?? '');
    $party_size    = intval($_POST['party_size'] ?? 0);
    $occasion      = sanitize($_POST['occasion'] ?? 'dining');
    $special_req   = sanitize($_POST['special_requests'] ?? '');
    $priority      = in_array($role, ['admin','manager']) ? sanitize($_POST['priority'] ?? 'normal') : 'normal';
    $table_id      = intval($_POST['table_id'] ?? 0);
    $duration      = intval($_POST['estimated_duration'] ?? 120);
    
    // Food ordering data
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $preorder_notes = sanitize($_POST['preorder_notes'] ?? '');

    if (!$res_date || !$res_time || !$party_size) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($res_date) < strtotime('today')) {
        $error = 'Reservation date cannot be in the past.';
    } else {
        // Peak Hour Check
        $day_of_week = date('w', strtotime($res_date));
        $peak_rule = db()->fetchOne(
            "SELECT * FROM peak_hours 
             WHERE day_of_week = ? 
             AND start_time <= ? 
             AND end_time > ? 
             LIMIT 1",
            [$day_of_week, $res_time, $res_time]
        );
        
        if ($peak_rule) {
            $current_bookings = db()->fetchOne(
                "SELECT COUNT(*) as cnt FROM reservations 
                 WHERE reservation_date = ? 
                 AND reservation_time = ? 
                 AND status IN ('confirmed','pending','seated')",
                [$res_date, $res_time]
            );
            
            if (($current_bookings['cnt'] ?? 0) >= $peak_rule['max_bookings_per_slot']) {
                $error = "Sorry, this time slot is fully booked due to peak hours (Max " . $peak_rule['max_bookings_per_slot'] . " bookings). Please choose another time.";
            }
        }

        if (!$error) {
            // Smart table assignment and Availability Check
        $req_start_ts = strtotime("$res_date $res_time");
        $req_end_ts   = $req_start_ts + ($duration * 60);
        $req_start_sql = date('Y-m-d H:i:s', $req_start_ts);
        $req_end_sql   = date('Y-m-d H:i:s', $req_end_ts);

        $assigned_table_id = null;

        // Check availability logic
        if (!$table_id) {
            // Get all suitable tables
            $candidate_tables = db()->fetchAll("SELECT table_id FROM restaurant_tables WHERE capacity >= ? AND status != 'maintenance' ORDER BY capacity ASC", [$party_size]);
            
            // 1. Try preferred table
            if ($role === 'customer') {
                $pref = db()->fetchOne("SELECT preferred_table_id FROM users WHERE user_id=?", [$user_id]);
                if ($pref && $pref['preferred_table_id']) {
                    foreach ($candidate_tables as $ct) {
                        if ($ct['table_id'] == $pref['preferred_table_id']) {
                            $overlap = db()->fetchOne(
                                "SELECT COUNT(*) as cnt FROM reservations 
                                 WHERE table_id = ? 
                                 AND status IN ('confirmed', 'seated', 'pending')
                                 AND (
                                    TIMESTAMP(reservation_date, reservation_time) < ? 
                                    AND 
                                    DATE_ADD(TIMESTAMP(reservation_date, reservation_time), INTERVAL estimated_duration MINUTE) > ?
                                 )",
                                [$pref['preferred_table_id'], $req_end_sql, $req_start_sql]
                            );
                            if (($overlap['cnt'] ?? 0) == 0) {
                                $assigned_table_id = $pref['preferred_table_id'];
                            }
                            break;
                        }
                    }
                }
            }
            
            // 2. Auto-assign best available
            if (!$assigned_table_id) {
                foreach ($candidate_tables as $t) {
                    $tid = $t['table_id'];
                    $overlap = db()->fetchOne(
                        "SELECT COUNT(*) as cnt FROM reservations 
                         WHERE table_id = ? 
                         AND status IN ('confirmed', 'seated', 'pending')
                         AND (
                            TIMESTAMP(reservation_date, reservation_time) < ? 
                            AND 
                            DATE_ADD(TIMESTAMP(reservation_date, reservation_time), INTERVAL estimated_duration MINUTE) > ?
                         )",
                        [$tid, $req_end_sql, $req_start_sql]
                    );
                    
                    if (($overlap['cnt'] ?? 0) == 0) {
                        $assigned_table_id = $tid;
                        break;
                    }
                }
            }
        } else {
            $assigned_table_id = $table_id; // Admin override
        }

        if (!$assigned_table_id && !$table_id) {
             $error = "No tables available for this time. <a href='join_waitlist.php?date=$res_date&time=$res_time&size=$party_size' class='text-primary underline'>Join Waitlist</a>";
        } else {
            $table_id = $assigned_table_id;

            // Process coupon code if provided
            $coupon_code = sanitize($_POST['coupon_code'] ?? '');
            $discount_amount = 0;
            $coupon_message = '';
            
            if ($coupon_code) {
                $promo = db()->fetchOne("SELECT * FROM promo_codes WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at > NOW())", [$coupon_code]);
                if ($promo) {
                    // For reservations, we'll store the coupon but apply it to pre-orders only
                    $coupon_message = "Coupon applied: " . $promo['description'];
                } else {
                    $coupon_code = null; // Invalid coupon, don't store
                }
            }
            
            // Process loyalty reward redemption
            $loyalty_reward_id = intval($_POST['loyalty_reward_id'] ?? 0);
            $loyalty_discount = 0;
            $loyalty_reward = null;
            
            if ($loyalty_reward_id && $res_user_id) {
                $reward = db()->fetchOne(
                    "SELECT * FROM loyalty_rewards 
                     WHERE id = ? AND is_active = 1 
                     AND (expires_at IS NULL OR expires_at > NOW())",
                    [$loyalty_reward_id]
                );
                
                if ($reward) {
                    $user_points = db()->fetchOne("SELECT loyalty_points FROM users WHERE user_id = ?", [$res_user_id])['loyalty_points'] ?? 0;
                    
                    if ($user_points >= $reward['points_required']) {
                        // Check usage limits
                        if ($reward['max_uses_per_customer']) {
                            $usage_count = db()->fetchOne(
                                "SELECT COUNT(*) as cnt FROM loyalty_points_transactions lpt
                                 JOIN orders o ON lpt.reference_id = o.order_id
                                 WHERE lpt.user_id = ? AND lpt.reference_type = 'order' 
                                 AND o.promo_code = ?",
                                [$res_user_id, 'LOYALTY_' . $reward['id']]
                            )['cnt'];
                            
                            if ($usage_count >= $reward['max_uses_per_customer']) {
                                $loyalty_reward_id = null; // Can't use again
                            }
                        }
                        
                        if ($loyalty_reward_id) {
                            $loyalty_reward = $reward;
                            // Points will be deducted when pre-order is created
                        }
                    } else {
                        $loyalty_reward_id = null; // Not enough points
                    }
                } else {
                    $loyalty_reward_id = null; // Invalid reward
                }
            }

            // Create reservation
            $rid = db()->insert(
                "INSERT INTO reservations (user_id, table_id, reservation_date, reservation_time, party_size, occasion, special_requests, status, priority, estimated_duration, coupon_code) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [$res_user_id, $table_id ?: null, $res_date, $res_time, $party_size, $occasion, $special_req, 'pending', $priority, $duration, $coupon_code]
            );

        if ($rid) {
            // Award loyalty points for reservation
            if ($res_user_id) {
                $reservation_points = 10; // 10 points for making a reservation
                
                // Update user's loyalty points
                db()->execute("UPDATE users SET loyalty_points = loyalty_points + ? WHERE user_id = ?", [$reservation_points, $res_user_id]);
                
                // Record loyalty transaction
                db()->insert(
                    "INSERT INTO loyalty_points_transactions (user_id, points, transaction_type, reference_type, reference_id, description) VALUES (?,?,?,?,?,?)",
                    [$res_user_id, $reservation_points, 'earned', 'reservation', $rid, "Points earned for reservation #{$rid}"]
                );
            }
            // Auto-update table status to reserved if a table was assigned
            if ($table_id) {
                db()->execute("UPDATE restaurant_tables SET status='reserved' WHERE table_id=?", [$table_id]);
            }
            
            // Send confirmation email
            require_once 'includes/mailer.php';
            $customer = db()->fetchOne("SELECT email, full_name FROM users WHERE user_id=?", [$res_user_id]);
            if ($customer) {
                $subject = "Reservation Confirmation - Frank Restaurant";
                $message = "Dear " . htmlspecialchars($customer['full_name']) . ",<br><br>";
                $message .= "Your reservation has been successfully created.<br>";
                $message .= "<strong>Date:</strong> " . date('F j, Y', strtotime($res_date)) . "<br>";
                $message .= "<strong>Time:</strong> " . date('g:i A', strtotime($res_time)) . "<br>";
                $message .= "<strong>Party Size:</strong> " . $party_size . " people<br>";
                $message .= "<strong>Reference ID:</strong> #" . $rid . "<br><br>";
                $message .= "We look forward to serving you!";
                
                sendEmail($customer['email'], $subject, $message);
            }
            
            // Create preorder if items were selected
            if (!empty($items)) {
                $subtotal = 0;
                foreach ($items as $item_id) {
                    $qty = intval($quantities[$item_id] ?? 1);
                    if ($qty > 0) {
                        $menu_item = db()->fetchOne("SELECT price FROM menu_items WHERE item_id = ? AND is_available = 1", [$item_id]);
                        if ($menu_item) {
                            $subtotal += $menu_item['price'] * $qty;
                        }
                    }
                }
                
                if ($subtotal > 0) {
                    // Apply coupon discount to pre-order
                    $discount = 0;
                    if ($coupon_code) {
                        $promo = db()->fetchOne("SELECT * FROM promo_codes WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at > NOW())", [$coupon_code]);
                        if ($promo) {
                            if ($promo['discount_type'] === 'percentage') {
                                $discount = $subtotal * ($promo['discount_value'] / 100);
                            } else {
                                $discount = $promo['discount_value'];
                            }
                            if ($discount > $subtotal) $discount = $subtotal;
                            
                            // Update promo usage
                            db()->execute("UPDATE promo_codes SET used_count=used_count+1 WHERE id=?", [$promo['id']]);
                        }
                    }
                    
                    // Apply loyalty reward discount
                    if ($loyalty_reward && $loyalty_reward_id) {
                        $loyalty_discount = 0;
                        if ($loyalty_reward['reward_type'] === 'discount_percentage') {
                            $loyalty_discount = $subtotal * ($loyalty_reward['reward_value'] / 100);
                        } elseif ($loyalty_reward['reward_type'] === 'discount_fixed') {
                            $loyalty_discount = $loyalty_reward['reward_value'];
                        }
                        
                        if ($loyalty_discount > 0) {
                            $discount += $loyalty_discount;
                            
                            // Deduct loyalty points
                            db()->execute("UPDATE users SET loyalty_points = loyalty_points - ? WHERE user_id = ?", [$loyalty_reward['points_required'], $res_user_id]);
                            
                            // Record loyalty transaction
                            db()->insert(
                                "INSERT INTO loyalty_points_transactions (user_id, points, transaction_type, reference_type, reference_id, description) VALUES (?,?,?,?,?,?)",
                                [$res_user_id, -$loyalty_reward['points_required'], 'redeemed', 'order', $order_id, "Redeemed reward: {$loyalty_reward['name']}"]
                            );
                        }
                    }
                    
                    $tax = ($subtotal - $discount) * 0.08;
                    $total = $subtotal - $discount + $tax;
                    
                    // Create preorder order
                    $order_id = db()->insert(
                        "INSERT INTO orders (user_id, table_id, reservation_id, status, subtotal, discount_amount, tax, total, notes, promo_code) VALUES (?, ?, ?, 'preorder', ?, ?, ?, ?, ?, ?)",
                        [$res_user_id, $table_id ?: null, $rid, $subtotal, $discount, $tax, $total, $preorder_notes, $coupon_code]
                    );
                    
                    // Add order items
                    foreach ($items as $item_id) {
                        $qty = intval($quantities[$item_id] ?? 1);
                        if ($qty > 0) {
                            $menu_item = db()->fetchOne("SELECT price FROM menu_items WHERE item_id = ? AND is_available = 1", [$item_id]);
                            if ($menu_item) {
                                db()->execute(
                                    "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)",
                                    [$order_id, $item_id, $qty, $menu_item['price']]
                                );
                            }
                        }
                    }
                    
                    // Award loyalty points for pre-order
                    if ($res_user_id) {
                        $order_points = intval($subtotal / 10); // 1 point per ₱10 spent
                        if ($order_points > 0) {
                            // Update user's loyalty points
                            db()->execute("UPDATE users SET loyalty_points = loyalty_points + ? WHERE user_id = ?", [$order_points, $res_user_id]);
                            
                            // Record loyalty transaction
                            db()->insert(
                                "INSERT INTO loyalty_points_transactions (user_id, points, transaction_type, reference_type, reference_id, description) VALUES (?,?,?,?,?,?)",
                                [$res_user_id, $order_points, 'earned', 'order', $order_id, "Points earned for pre-order #{$order_id}"]
                            );
                        }
                    }
                }
            }
            
            $_SESSION['flash'] = ['msg' => 'Reservation #'.$rid.' created successfully!'.(!empty($items) ? ' Pre-order placed.' : ''), 'type' => 'success', 'confetti' => true];
            header('Location: reservations.php');
            exit;
        } else {
            $error = 'Failed to create reservation. Please try again.';
        }
      }
     }
    }
}
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">New Reservation</h2>
        <p class="section-subtitle">Complete the form below to book a table</p>
    </div>
    <a href="reservations.php" class="btn btn-secondary">← Back</a>
</div>

<?php if($error): ?>
<div class="alert alert-danger" data-dismiss="6000">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="content-grid">
    <!-- Form -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">Reservation Details</h3>
        </div>
        <form method="POST" action="" id="reservationForm">

            <?php if(in_array($role, ['admin','manager','staff']) && $customers): ?>
            <div class="form-group">
                <label>Guest *</label>
                <select name="user_id" class="form-control" required onchange="loadGuestPref(this)">
                    <option value="">Select a customer...</option>
                    <?php foreach($customers as $c): ?>
                    <option value="<?= $c['user_id'] ?>" data-pref="<?= $c['preferred_table_id'] ?>"
                        <?= ($c['vip_status'] ? '⭐ ' : '') . htmlspecialchars($c['full_name']) . ' (' . htmlspecialchars($c['email']) . ')' ?>>
                        <?= ($c['vip_status'] ? '👑 ' : '') . htmlspecialchars($c['full_name']) . ' — ' . htmlspecialchars($c['email']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="reservation_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['reservation_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Time *</label>
                    <input type="time" name="reservation_time" class="form-control" required value="<?= htmlspecialchars($_POST['reservation_time'] ?? '19:00') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Party Size *</label>
                    <input type="number" name="party_size" class="form-control" min="1" max="20" required value="<?= htmlspecialchars($_POST['party_size'] ?? 2) ?>">
                </div>
                <div class="form-group">
                    <label>Occasion</label>
                    <select name="occasion" class="form-control">
                        <?php foreach(['dining','birthday','anniversary','business','date','celebration','other'] as $occ): ?>
                        <option value="<?= $occ ?>" <?= ($_POST['occasion'] ?? 'dining') === $occ ? 'selected' : '' ?>><?= ucfirst($occ) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if(in_array($role, ['admin','manager'])): ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="vip">VIP</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimated Duration (min)</label>
                    <input type="number" name="estimated_duration" class="form-control" value="120" min="30" max="360" step="30">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Special Requests</label>
                <textarea name="special_requests" class="form-control" placeholder="Dietary requirements, accessibility needs, special arrangements..."><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>🎟️ Coupon Code (Optional)</label>
                <div class="input-group">
                    <input type="text" name="coupon_code" class="form-control" placeholder="Enter coupon code for discount" value="<?= htmlspecialchars($_POST['coupon_code'] ?? '') ?>">
                    <button type="button" class="btn btn-secondary" onclick="validateCoupon()">Apply</button>
                </div>
                <div id="couponMessage" class="form-text mt-1"></div>
            </div>

            <?php if($role === 'customer' && isLoggedIn()): ?>
            <div class="form-group">
                <label>🎁 Loyalty Points Redemption (Optional)</label>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Your Available Points:</span>
                    <span class="badge bg-primary" id="userPoints">Loading...</span>
                </div>
                <select name="loyalty_reward_id" class="form-control" id="loyaltyRewardSelect">
                    <option value="">Select a reward to redeem...</option>
                </select>
                <div id="loyaltyMessage" class="form-text mt-1"></div>
            </div>
            <?php endif; ?>

            <!-- Food Pre-order Section -->
            <div class="card mb-3" style="background:var(--bg-tertiary);">
                <div class="card-header" style="background:transparent;border:none;padding:0;margin-bottom:1rem;">
                    <h4 class="card-title" style="font-size:1.1rem;">🍽️ Pre-order Food (Optional)</h4>
                    <span class="text-xs text-muted">Order now and pay when you arrive</span>
                </div>
                
                <?php foreach($menu_by_category as $category_id => $category): ?>
                <div class="menu-category mb-3">
                    <h5 class="category-title" style="font-size:1rem;margin-bottom:0.75rem;">
                        <?= $food_icons[$category['name']] ?? '🍽️' ?> <?= $category['name'] ?>
                    </h5>
                    <div class="menu-items-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:0.75rem;">
                        <?php foreach($category['items'] as $item): ?>
                        <div class="menu-item-card" style="padding:0.75rem;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-sm);">
                            <div class="menu-item-header" style="display:flex;align-items:flex-start;gap:0.5rem;margin-bottom:0.5rem;">
                                <span class="menu-item-icon"><?= $food_icons[$item['name']] ?? '🍽️' ?></span>
                                <div class="menu-item-info" style="flex:1;">
                                    <div class="menu-item-name" style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="menu-item-price" style="color:var(--accent-primary);font-weight:600;">₱<?= number_format($item['price'], 2) ?></div>
                                </div>
                                <?php if($item['is_featured']): ?>
                                <span class="featured-badge" style="background:var(--gradient-primary);color:white;padding:0.1rem 0.3rem;border-radius:var(--radius-sm);font-size:0.6rem;font-weight:600;">⭐</span>
                                <?php endif; ?>
                            </div>
                            <div class="menu-item-description" style="color:var(--text-secondary);font-size:0.8rem;line-height:1.3;margin-bottom:0.75rem;"><?= htmlspecialchars($item['description']) ?></div>
                            <div class="menu-item-actions" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                                <div class="quantity-control" style="display:flex;align-items:center;gap:0.3rem;">
                                    <button type="button" class="btn btn-icon btn-sm" onclick="decreasePreorderQty(<?= $item['item_id'] ?>)">−</button>
                                    <input type="number" name="quantities[<?= $item['item_id'] ?>]" id="preorder_qty_<?= $item['item_id'] ?>" value="0" min="0" max="20" class="qty-input" style="width:50px;text-align:center;padding:0.2rem;border:1px solid var(--border-color);border-radius:var(--radius-sm);background:var(--bg-tertiary);">
                                    <button type="button" class="btn btn-icon btn-sm" onclick="increasePreorderQty(<?= $item['item_id'] ?>)">+</button>
                                </div>
                                <label class="checkbox-label" style="display:flex;align-items:center;cursor:pointer;">
                                    <input type="checkbox" name="items[]" value="<?= $item['item_id'] ?>" onchange="updatePreorderQty(<?= $item['item_id'] ?>)">
                                    <span class="checkmark" style="width:16px;height:16px;border:2px solid var(--border-color);border-radius:var(--radius-sm);margin-right:0.3rem;display:flex;align-items:center;justify-content:center;transition:var(--transition);"></span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Pre-order Summary -->
                <div class="order-summary" style="background:var(--bg-card);border-radius:var(--radius);padding:1rem;margin-top:1rem;border:1px solid var(--border-color);">
                    <div class="summary-row" style="display:flex;justify-content:space-between;align-items:center;padding:0.3rem 0;color:var(--text-secondary);">
                        <span>Subtotal:</span>
                        <span id="preorder_subtotal">₱0.00</span>
                    </div>
                    <div class="summary-row" style="display:flex;justify-content:space-between;align-items:center;padding:0.3rem 0;color:var(--text-secondary);">
                        <span>Tax (8%):</span>
                        <span id="preorder_tax">₱0.00</span>
                    </div>
                    <div class="summary-row total" style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-top:1px solid var(--border-color);margin-top:0.3rem;font-weight:700;font-size:1.1rem;color:var(--text-primary);">
                        <span>Total:</span>
                        <span id="preorder_total">₱0.00</span>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:1rem;">
                    <label>Pre-order Notes</label>
                    <textarea name="preorder_notes" class="form-control" rows="2" placeholder="Special instructions for your pre-order..."><?= htmlspecialchars($_POST['preorder_notes'] ?? '') ?></textarea>
                </div>
            </div>

            <input type="hidden" name="table_id" id="selectedTableId" value="">

            <button type="submit" class="btn btn-primary w-100 glow" style="justify-content:center;padding:0.85rem;">
                📅 Confirm Reservation
            </button>
        </form>
    </div>

    <!-- Table Selection -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">🪑 Floor Plan - Select Table</h3>
            <span class="text-xs text-muted">Choose your table or let us auto-assign</span>
        </div>

        <!-- Legend -->
        <div class="flex gap-2 mb-3" style="flex-wrap:wrap;">
            <?php foreach(['available'=>'success','occupied'=>'danger','reserved'=>'warning','cleaning'=>'info','maintenance'=>'muted'] as $st => $cl): ?>
            <span style="display:flex;align-items:center;gap:0.3rem;font-size:0.78rem;">
                <span class="table-status-dot dot-<?= $st ?>"></span><?= ucfirst($st) ?>
            </span>
            <?php endforeach; ?>
        </div>

        <!-- Floor Plan Grid -->
        <div class="floor-plan-grid" id="floorPlan">
            <?php 
            $table_groups = [];
            foreach($tables as $table) {
                $table_groups[$table['location']][] = $table;
            }
            
            foreach($table_groups as $location => $location_tables): ?>
            <div class="floor-section">
                <h4 class="section-title"><?= htmlspecialchars($location) ?></h4>
                <div class="tables-grid">
                    <?php foreach($location_tables as $table): 
                        $is_available = $table['status'] === 'available';
                        $can_fit = $table['capacity'] >= ($_POST['party_size'] ?? 2);
                        ?>
                    <div class="table-card <?= $is_available && $can_fit ? 'selectable' : 'disabled' ?>" 
                         data-table-id="<?= $table['table_id'] ?>" 
                         data-table-number="<?= $table['table_number'] ?>"
                         data-capacity="<?= $table['capacity'] ?>"
                         data-location="<?= htmlspecialchars($table['location']) ?>"
                         data-type="<?= $table['table_type'] ?>"
                         onclick="selectTable(<?= $table['table_id'] ?>, <?= $is_available && $can_fit ? 'true' : 'false' ?>)">
                        
                        <div class="table-header">
                            <span class="table-number">T<?= $table['table_number'] ?></span>
                            <span class="table-status-dot dot-<?= $table['status'] ?>"></span>
                        </div>
                        
                        <div class="table-details">
                            <div class="capacity">👥 <?= $table['capacity'] ?></div>
                            <div class="type"><?= ucfirst($table['table_type']) ?></div>
                        </div>
                        
                        <?php if(!$is_available): ?>
                        <div class="table-overlay">
                            <span><?= ucfirst($table['status']) ?></span>
                        </div>
                        <?php elseif(!$can_fit): ?>
                        <div class="table-overlay">
                            <span>Too small</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Selected Table Display -->
        <div id="selectedTableInfo" class="selected-table-info" style="display:none;">
            <div class="flex justify-between items-center">
                <div>
                    <strong>Selected: Table <span id="selectedTableNumber"></span></strong>
                    <div class="text-xs text-muted">
                        <span id="selectedTableLocation"></span> • <span id="selectedTableCapacity"></span> guests • <span id="selectedTableType"></span>
                    </div>
                </div>
                <button type="button" class="btn btn-icon btn-sm" onclick="clearTableSelection()">✕</button>
            </div>
        </div>
    </div>
</div>

<script>
// Table selection functions
function selectTable(tableId, isSelectable) {
    if (!isSelectable) return;
    
    // Clear previous selection
    document.querySelectorAll('.table-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Select new table
    const tableCard = document.querySelector(`[data-table-id="${tableId}"]`);
    if (tableCard) {
        tableCard.classList.add('selected');
        
        // Update hidden input
        document.getElementById('selectedTableId').value = tableId;
        
        // Show selected table info
        const info = document.getElementById('selectedTableInfo');
        const number = tableCard.dataset.tableNumber;
        const location = tableCard.dataset.location;
        const capacity = tableCard.dataset.capacity;
        const type = tableCard.dataset.type;
        
        document.getElementById('selectedTableNumber').textContent = number;
        document.getElementById('selectedTableLocation').textContent = location;
        document.getElementById('selectedTableCapacity').textContent = capacity;
        document.getElementById('selectedTableType').textContent = type;
        
        info.style.display = 'block';
    }
}

function clearTableSelection() {
    document.querySelectorAll('.table-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById('selectedTableId').value = '';
    document.getElementById('selectedTableInfo').style.display = 'none';
}

// Pre-order functions
const PreorderForm = {
    menuItems: {},
    
    init() {
        // Store menu item prices
        document.querySelectorAll('.menu-item-card').forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            const priceText = card.querySelector('.menu-item-price').textContent;
            const price = parseFloat(priceText.replace('₱', '').replace(',', ''));
            
            if (checkbox) {
                this.menuItems[checkbox.value] = { price, element: card };
            }
        });
    },
    
    updateQty(itemId) {
        const checkbox = document.querySelector(`input[value="${itemId}"]`);
        const qtyInput = document.getElementById(`preorder_qty_${itemId}`);
        
        if (checkbox.checked && qtyInput.value === '0') {
            qtyInput.value = 1;
        } else if (!checkbox.checked) {
            qtyInput.value = 0;
        }
        
        this.updateSummary();
    },
    
    increaseQty(itemId) {
        const qtyInput = document.getElementById(`preorder_qty_${itemId}`);
        const checkbox = document.querySelector(`input[value="${itemId}"]`);
        const currentValue = parseInt(qtyInput.value) || 0;
        
        if (currentValue < 20) {
            qtyInput.value = currentValue + 1;
            checkbox.checked = true;
            this.updateSummary();
        }
    },
    
    decreaseQty(itemId) {
        const qtyInput = document.getElementById(`preorder_qty_${itemId}`);
        const checkbox = document.querySelector(`input[value="${itemId}"]`);
        const currentValue = parseInt(qtyInput.value) || 0;
        
        if (currentValue > 0) {
            qtyInput.value = currentValue - 1;
            if (currentValue - 1 === 0) {
                checkbox.checked = false;
            }
            this.updateSummary();
        }
    },
    
    updateSummary() {
        let subtotal = 0;
        
        document.querySelectorAll('input[name="items[]"]:checked').forEach(checkbox => {
            const itemId = checkbox.value;
            const qtyInput = document.getElementById(`preorder_qty_${itemId}`);
            const qty = parseInt(qtyInput.value) || 0;
            const item = this.menuItems[itemId];
            
            if (item && qty > 0) {
                subtotal += item.price * qty;
            }
        });
        
        const tax = subtotal * 0.08;
        const total = subtotal + tax;
        
        document.getElementById('preorder_subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('preorder_tax').textContent = `₱${tax.toFixed(2)}`;
        document.getElementById('preorder_total').textContent = `₱${total.toFixed(2)}`;
    }
};

// Global functions for onclick handlers
function updatePreorderQty(itemId) {
    PreorderForm.updateQty(itemId);
}

function increasePreorderQty(itemId) {
    PreorderForm.increaseQty(itemId);
}

function decreasePreorderQty(itemId) {
    PreorderForm.decreaseQty(itemId);
}

// Coupon validation function
function validateCoupon() {
    const couponCode = document.querySelector('input[name="coupon_code"]').value.trim();
    const messageDiv = document.getElementById('couponMessage');
    
    if (!couponCode) {
        messageDiv.innerHTML = '<span style="color: #6c757d;">Please enter a coupon code</span>';
        return;
    }
    
    messageDiv.innerHTML = '<span style="color: #007bff;">Validating...</span>';
    
    fetch('ajax_promo.php?code=' + encodeURIComponent(couponCode))
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            messageDiv.innerHTML = `<span style="color: #28a745;">✅ ${data.message} - Save ₱${data.discount.toFixed(2)}</span>`;
        } else {
            messageDiv.innerHTML = `<span style="color: #dc3545;">❌ ${data.message}</span>`;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<span style="color: #dc3545;">Error validating coupon</span>';
        console.error('Coupon validation error:', error);
    });
}

// Load user loyalty points and available rewards
function loadLoyaltyData() {
    // Load user points
    fetch('ajax_loyalty.php?action=get_points')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('userPoints').textContent = data.points + ' points';
            loadAvailableRewards(data.points);
        }
    })
    .catch(error => {
        console.error('Error loading loyalty data:', error);
        document.getElementById('userPoints').textContent = 'Error';
    });
}

// Load available rewards based on user points
function loadAvailableRewards(userPoints) {
    fetch('ajax_loyalty.php?action=get_rewards&points=' + userPoints)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('loyaltyRewardSelect');
            select.innerHTML = '<option value="">Select a reward to redeem...</option>';
            
            data.rewards.forEach(reward => {
                const option = document.createElement('option');
                option.value = reward.id;
                option.textContent = `${reward.name} (${reward.points_required} points)`;
                select.appendChild(option);
            });
            
            if (data.rewards.length === 0) {
                select.innerHTML = '<option value="">No rewards available</option>';
            }
        }
    })
    .catch(error => {
        console.error('Error loading rewards:', error);
    });
}

// Handle reward selection change
document.getElementById('loyaltyRewardSelect')?.addEventListener('change', function() {
    const rewardId = this.value;
    const messageDiv = document.getElementById('loyaltyMessage');
    
    if (!rewardId) {
        messageDiv.innerHTML = '';
        return;
    }
    
    messageDiv.innerHTML = '<span style="color: #007bff;">Loading reward details...</span>';
    
    fetch('ajax_loyalty.php?action=get_reward_details&id=' + rewardId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = `<span style="color: #28a745;">✅ ${data.reward.description} - This will be applied to your pre-order</span>`;
        } else {
            messageDiv.innerHTML = `<span style="color: #dc3545;">❌ ${data.message}</span>`;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<span style="color: #dc3545;">Error loading reward details</span>';
        console.error('Error loading reward details:', error);
    });
});

// Initialize loyalty data when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('userPoints')) {
        loadLoyaltyData();
    }
});

// Update table availability when party size changes
document.querySelector('input[name="party_size"]')?.addEventListener('change', function() {
    const partySize = parseInt(this.value);
    document.querySelectorAll('.table-card').forEach(card => {
        const capacity = parseInt(card.dataset.capacity);
        const isAvailable = card.querySelector('.dot-available') !== null;
        const canFit = capacity >= partySize;
        
        if (isAvailable && canFit) {
            card.classList.add('selectable');
            card.classList.remove('disabled');
        } else {
            card.classList.remove('selectable');
            card.classList.add('disabled');
        }
    });
});

// Initialize preorder form when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.menu-item-card')) {
        PreorderForm.init();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

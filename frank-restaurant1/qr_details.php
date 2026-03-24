<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Get order ID from QR code or URL parameter
$order_id = intval($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$reservation_id = intval($_GET['reservation_id'] ?? $_POST['reservation_id'] ?? 0);

if (!$order_id && !$reservation_id) {
    die('Invalid QR code: No order or reservation ID found');
}

$order = null;
$reservation = null;
$title = '';
$items = [];

// Handle order display
if ($order_id > 0) {
    try {
        $order = db()->fetchOne(
            "SELECT o.*, u.full_name, u.email, u.phone 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.user_id 
             WHERE o.order_id = ?",
            [$order_id]
        );
        
        if ($order) {
            $title = "Order Details";
            $items = db()->fetchAll(
                "SELECT oi.*, mi.name, mi.description 
                 FROM order_items oi 
                 LEFT JOIN menu_items mi ON oi.menu_item_id = mi.item_id 
                 WHERE oi.order_id = ?",
                [$order_id]
            );
        }
    } catch (Exception $e) {
        die('Error loading order details');
    }
}

// Handle reservation display
if ($reservation_id > 0) {
    try {
        $reservation = db()->fetchOne(
            "SELECT r.*, u.full_name, u.email, u.phone 
             FROM reservations r 
             LEFT JOIN users u ON r.user_id = u.user_id 
             WHERE r.reservation_id = ?",
            [$reservation_id]
        );
        
        if ($reservation) {
            $title = "Reservation Details";
        }
    } catch (Exception $e) {
        die('Error loading reservation details');
    }
}

if (!$order && !$reservation) {
    die('Order or reservation not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Frank Restaurant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .header .restaurant-name {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .customer-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .customer-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
        }
        
        .items-section {
            margin-bottom: 25px;
        }
        
        .items-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
        }
        
        .item-price {
            color: #667eea;
            font-weight: 700;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9rem;
        }
        
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .total-row.grand-total {
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 15px;
            margin-top: 15px;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #ffc107; color: #333; }
        .status-confirmed { background: #28a745; color: white; }
        .status-delivered { background: #17a2b8; color: white; }
        .status-paid { background: #6c757d; color: white; }
        .status-seated { background: #20c997; color: white; }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 0.9rem;
        }
        
        .qr-info {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .qr-info h4 {
            color: #1976d2;
            margin-bottom: 8px;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ <?= $title ?></h1>
            <div class="restaurant-name">Frank Restaurant</div>
        </div>
        
        <div class="content">
            <div class="qr-info">
                <h4>📱 QR Code Scanned Successfully!</h4>
                <p><?= $title ?> information displayed below</p>
            </div>
            
            <?php if ($order): ?>
                <!-- Order Details -->
                <div class="customer-info">
                    <h3>👤 Customer Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= htmlspecialchars($order['full_name'] ?? 'Guest') ?></span>
                    </div>
                    <?php if ($order['email']): ?>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= htmlspecialchars($order['email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['phone']): ?>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= htmlspecialchars($order['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Order #:</span>
                        <span class="info-value"><?= $order['order_id'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date:</span>
                        <span class="info-value"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                        </span>
                    </div>
                </div>
                
                <?php if ($items): ?>
                <div class="items-section">
                    <h3>🛒 Order Items</h3>
                    <?php foreach ($items as $item): ?>
                    <div class="item">
                        <div class="item-header">
                            <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                            <span class="item-price">₱<?= number_format($item['unit_price'], 2) ?></span>
                        </div>
                        <div class="item-details">
                            <span>Quantity: <?= $item['quantity'] ?></span>
                            <span>Subtotal: ₱<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></span>
                        </div>
                        <?php if ($item['description']): ?>
                        <div style="margin-top: 8px; color: #666; font-size: 0.9rem;">
                            <?= htmlspecialchars($item['description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="total-section">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>₱<?= number_format($order['subtotal'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                    <div class="total-row">
                        <span>Discount:</span>
                        <span>-₱<?= number_format($order['discount_amount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span>Tax (8%):</span>
                        <span>₱<?= number_format($order['tax'] ?? 0, 2) ?></span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>₱<?= number_format($order['total'] ?? 0, 2) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($reservation): ?>
                <!-- Reservation Details -->
                <div class="customer-info">
                    <h3>👤 Customer Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= htmlspecialchars($reservation['full_name'] ?? 'Guest') ?></span>
                    </div>
                    <?php if ($reservation['email']): ?>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= htmlspecialchars($reservation['email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($reservation['phone']): ?>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= htmlspecialchars($reservation['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Reservation #:</span>
                        <span class="info-value"><?= $reservation['reservation_id'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date:</span>
                        <span class="info-value"><?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Time:</span>
                        <span class="info-value"><?= date('g:i A', strtotime($reservation['reservation_time'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Party Size:</span>
                        <span class="info-value"><?= $reservation['party_size'] ?> people</span>
                    </div>
                    <?php if ($reservation['table_id']): ?>
                    <div class="info-row">
                        <span class="info-label">Table:</span>
                        <span class="info-value"><?= $reservation['table_id'] ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?= $reservation['status'] ?>"><?= ucfirst($reservation['status']) ?></span>
                        </span>
                    </div>
                </div>
                
                <?php if ($reservation['special_requests']): ?>
                <div class="items-section">
                    <h3>📝 Special Requests</h3>
                    <div class="item">
                        <p><?= htmlspecialchars($reservation['special_requests']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($reservation['deposit_amount']) && $reservation['deposit_amount'] > 0): ?>
                <div class="total-section">
                    <div class="total-row">
                        <span>Deposit Paid:</span>
                        <span>₱<?= number_format($reservation['deposit_amount'], 2) ?></span>
                    </div>
                    <?php if (isset($reservation['total_amount']) && $reservation['total_amount'] > 0): ?>
                    <div class="total-row">
                        <span>Total Amount:</span>
                        <span>₱<?= number_format($reservation['total_amount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>🍽️ Frank Restaurant | Thank you for your business!</p>
            <p>Scan QR code for instant order/reservation details</p>
        </div>
    </div>
</body>
</html>

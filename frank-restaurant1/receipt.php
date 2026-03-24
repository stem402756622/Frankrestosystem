<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$oid = intval($_GET['order_id'] ?? $_GET['id'] ?? 0);
if (!$oid) die('Order ID not specified');

$order = db()->fetchOne(
    "SELECT o.*, u.full_name, u.email, u.phone, t.table_number 
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.user_id 
     LEFT JOIN restaurant_tables t ON o.table_id = t.table_id 
     WHERE o.order_id=?", 
    [$oid]
);

if (!$order) die('Order not found');

$items = db()->fetchAll(
    "SELECT oi.*, mi.name, mi.description 
     FROM order_items oi 
     JOIN menu_items mi ON oi.menu_item_id=mi.item_id 
     WHERE oi.order_id=?", 
    [$oid]
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?= $oid ?> - Frank Restaurant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #2c3e50;
            background: #fff;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .receipt-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .receipt-header .tagline {
            color: #7f8c8d;
            font-size: 12px;
            font-style: italic;
        }
        
        .receipt-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #3498db;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .item-name {
            font-weight: 500;
        }
        
        .item-desc {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        .quantity-price {
            text-align: right;
            white-space: nowrap;
        }
        
        .summary-section {
            background: #fff;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .summary-row.total {
            border-top: 2px solid #3498db;
            padding-top: 8px;
            font-weight: 700;
            font-size: 16px;
            color: #2c3e50;
        }
        
        .discount-row {
            color: #27ae60;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #bdc3c7;
        }
        
        .thank-you {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .contact-info {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .payment-method {
            background: #e8f5e8;
            color: #2d5016;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .no-print {
            margin-top: 20px;
        }
        
        .print-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .receipt-header { page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <h1>🍽️ Frank Restaurant</h1>
        <p class="tagline">Fine Dining Experience</p>
    </div>
    
    <div class="receipt-info">
        <div class="info-row">
            <span class="info-label">Receipt #:</span>
            <span class="info-value"><?= str_pad($oid, 6, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value"><?= date('M j, Y h:i A', strtotime($order['created_at'])) ?></span>
        </div>
        <?php if ($order['full_name']): ?>
        <div class="info-row">
            <span class="info-label">Customer:</span>
            <span class="info-value"><?= htmlspecialchars($order['full_name']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($order['table_number']): ?>
        <div class="info-row">
            <span class="info-label">Table:</span>
            <span class="info-value"><?= htmlspecialchars($order['table_number']) ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="status-badge status-<?= $order['status'] ?>"><?= $order['status'] ?></span>
        </div>
        <?php if ($order['payment_method'] ?? null): ?>
        <div class="info-row">
            <span class="info-label">Payment:</span>
            <span class="payment-method"><?= ucfirst($order['payment_method']) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align: right; width: 80px;">Qty</th>
                <th style="text-align: right; width: 100px;">Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td>
                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                    <?php if ($item['description']): ?>
                    <div class="item-desc"><?= htmlspecialchars(substr($item['description'], 0, 60)) ?></div>
                    <?php endif; ?>
                </td>
                <td class="quantity-price"><?= $item['quantity'] ?></td>
                <td class="quantity-price">₱<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="summary-section">
        <div class="summary-row">
            <span>Subtotal:</span>
            <span>₱<?= number_format($order['subtotal'] ?? 0, 2) ?></span>
        </div>
        <?php if (($order['discount_amount'] ?? 0) > 0): ?>
        <div class="summary-row discount-row">
            <span>Discount:</span>
            <span>-₱<?= number_format($order['discount_amount'], 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="summary-row">
            <span>Tax (8%):</span>
            <span>₱<?= number_format($order['tax'] ?? 0, 2) ?></span>
        </div>
        <?php if (($order['service_charge'] ?? 0) > 0): ?>
        <div class="summary-row">
            <span>Service Charge:</span>
            <span>₱<?= number_format($order['service_charge'], 2) ?></span>
        </div>
        <?php endif; ?>
        <div class="summary-row total">
            <span>TOTAL:</span>
            <span>₱<?= number_format($order['total'] ?? 0, 2) ?></span>
        </div>
    </div>
    
    <?php if ($order['notes']): ?>
    <div style="background: #fff9e6; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
        <strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?>
    </div>
    <?php endif; ?>
    
    <div class="receipt-footer">
        <div class="thank-you">Thank you for dining with us! 🎉</div>
        <p class="contact-info">Your satisfaction is our priority</p>
        <p class="contact-info">Visit us again soon!</p>
        <p class="contact-info">www.frankrestaurant.com | (123) 456-7890</p>
    </div>
    
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>
    </div>
</body>
</html>

<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$oid = intval($_GET['id'] ?? 0);
$order = db()->fetchOne("SELECT * FROM orders WHERE order_id=?", [$oid]);
if (!$order) die('Order not found');

$items = db()->fetchAll("SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id=mi.item_id WHERE oi.order_id=?", [$oid]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?= $oid ?></title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 300px; margin: 0 auto; }
        .center { text-align: center; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 0; border-bottom: 1px dashed #000; }
        .total { font-weight: bold; font-size: 14px; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center">
        <h2>FRANK RESTAURANT</h2>
        <p>123 Main Street, Cityville<br>Tel: (123) 456-7890</p>
        <p>Receipt #<?= $oid ?><br><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="text-align:left">Item</th>
                <th style="text-align:right">Qty</th>
                <th style="text-align:right">Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?= substr($item['name'], 0, 20) ?></td>
                <td class="right"><?= $item['quantity'] ?></td>
                <td class="right"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="right">
        <p>Subtotal: <?= number_format($order['subtotal'], 2) ?></p>
        <?php if($order['discount_amount'] > 0): ?>
        <p>Discount: -<?= number_format($order['discount_amount'], 2) ?></p>
        <?php endif; ?>
        <p>Tax (8%): <?= number_format($order['tax'], 2) ?></p>
        <p class="total">TOTAL: <?= number_format($order['total'], 2) ?></p>
    </div>
    
    <div class="center">
        <p>Thank you for dining with us!</p>
        <p>www.frankrestaurant.com</p>
    </div>
    
    <button class="no-print" onclick="window.print()" style="width:100%; padding:10px; margin-top:20px;">Print Receipt</button>
</body>
</html>

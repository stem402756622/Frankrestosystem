<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$oid = intval($_GET['id'] ?? 0);
$inv = db()->fetchOne("SELECT * FROM invoices WHERE order_id=?", [$oid]);
$order = db()->fetchOne("SELECT o.*, u.full_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id=u.user_id WHERE o.order_id=?", [$oid]);

if (!$inv && $order && $order['status'] === 'completed') {
    // Generate invoice if not exists but paid
    $inv_num = 'INV-' . date('Ymd') . '-' . str_pad($oid, 4, '0', STR_PAD_LEFT);
    db()->execute("INSERT IGNORE INTO invoices (order_id, invoice_number, total_amount, tax_amount, discount_amount, status) VALUES (?,?,?,?,?,'paid')", [$oid, $inv_num, $order['total'], $order['tax'], $order['discount_amount'] ?? 0]);
    $inv = db()->fetchOne("SELECT * FROM invoices WHERE order_id=?", [$oid]);
}

if (!$inv) die('Invoice not found or order not completed.');

$items = db()->fetchAll("SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id=mi.item_id WHERE oi.order_id=?", [$oid]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice <?= $inv['invoice_number'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; color: #333; max-width: 800px; margin: 0 auto; border: 1px solid #ddd; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: bold; color: #667eea; }
        .invoice-details { text-align: right; }
        .bill-to { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .totals { text-align: right; }
        .totals p { margin: 5px 0; }
        .grand-total { font-size: 18px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; }
        .footer { margin-top: 50px; text-align: center; color: #777; font-size: 12px; }
        @media print { 
            body { border: none; padding: 0; margin: 0; } 
            .no-print { display: none; } 
            .header { margin-top: 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="logo">FRANK RESTAURANT</div>
            <p>123 Main Street, Cityville</p>
            <p>Email: contact@frankrestaurant.com</p>
            <p>Phone: (123) 456-7890</p>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> <?= $inv['invoice_number'] ?></p>
            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($inv['issued_at'])) ?></p>
            <p><strong>Status:</strong> <span style="color:green; font-weight:bold; text-transform:uppercase;"><?= $inv['status'] ?></span></p>
        </div>
    </div>

    <div class="bill-to">
        <h3>Bill To:</h3>
        <p><strong><?= htmlspecialchars($order['full_name'] ?? 'Guest') ?></strong></p>
        <?php if($order['email']): ?><p><?= htmlspecialchars($order['email']) ?></p><?php endif; ?>
        <?php if($order['phone']): ?><p><?= htmlspecialchars($order['phone']) ?></p><?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:center">Quantity</th>
                <th style="text-align:right">Unit Price</th>
                <th style="text-align:right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td style="text-align:center"><?= $item['quantity'] ?></td>
                <td style="text-align:right">₱<?= number_format($item['unit_price'], 2) ?></td>
                <td style="text-align:right">₱<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <p>Subtotal: ₱<?= number_format($inv['total_amount'] - $inv['tax_amount'] + $inv['discount_amount'], 2) ?></p>
        <?php if($inv['discount_amount'] > 0): ?>
        <p style="color:green">Discount: -₱<?= number_format($inv['discount_amount'], 2) ?></p>
        <?php endif; ?>
        <p>Tax (8%): ₱<?= number_format($inv['tax_amount'], 2) ?></p>
        <p class="grand-total">Total: ₱<?= number_format($inv['total_amount'], 2) ?></p>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Terms & Conditions apply.</p>
    </div>
    
    <div class="no-print" style="position:fixed; bottom:20px; right:20px;">
        <button onclick="window.print()" style="padding: 15px 30px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size:16px; box-shadow:0 4px 10px rgba(0,0,0,0.2);">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>

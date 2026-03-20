<?php
$pageTitle    = 'Payment';
require_once 'includes/header.php';

$oid = intval($_GET['order_id'] ?? 0);
$order = db()->fetchOne(
    "SELECT o.*, u.full_name, u.email FROM orders o 
     LEFT JOIN users u ON o.user_id=u.user_id 
     WHERE o.order_id=?", 
    [$oid]
);

if (!$order) redirect('index.php', 'Order not found.', 'error');

// Verify user access
if ($role === 'customer' && $order['user_id'] != $user_id) {
    redirect('index.php', 'Access denied.', 'error');
}

// Fetch items
$items = db()->fetchAll(
    "SELECT oi.*, mi.name FROM order_items oi 
     JOIN menu_items mi ON oi.menu_item_id=mi.item_id 
     WHERE oi.order_id=?", 
    [$oid]
);

// Payment confirmation (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['admin','manager','staff'])) {
    if ($_POST['action'] === 'confirm_payment') {
        db()->execute("UPDATE orders SET status='completed' WHERE order_id=?", [$oid]);
        
        // Feature 12: Invoicing - auto-generate invoice record
        $inv_num = 'INV-' . date('Ymd') . '-' . str_pad($oid, 4, '0', STR_PAD_LEFT);
        db()->execute(
            "INSERT IGNORE INTO invoices (order_id, invoice_number, total_amount, tax_amount, discount_amount, status) VALUES (?,?,?,?,?,'paid')",
            [$oid, $inv_num, $order['total'], $order['tax'], $order['discount_amount']]
        );
        
        // Feature 15: Follow-up notice logic would be triggered here or on next login
        
        redirect('orders.php', 'Payment confirmed.', 'success');
    }
}
?>

<div class="row justify-center">
    <div class="col-md-6">
        <div class="card animate-in">
            <h2 class="card-title text-center mb-4">Order Payment</h2>
            
            <div class="text-center mb-4">
                <div id="qrcode" class="mb-2" style="display:inline-block;"></div>
                <p class="text-muted text-sm">Scan to Pay</p>
                <div class="font-bold text-2xl text-primary mt-2">₱<?= number_format($order['total'], 2) ?></div>
                <div class="text-sm text-muted">Order #<?= $oid ?></div>
            </div>
            
            <div class="border-t border-b py-3 my-3">
                <h4 class="font-bold mb-2">Order Summary</h4>
                <?php foreach($items as $item): ?>
                <div class="flex justify-between text-sm mb-1">
                    <span><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                    <span>₱<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="flex justify-between font-bold mt-2 pt-2 border-t">
                    <span>Subtotal</span>
                    <span>₱<?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <?php if($order['discount_amount'] > 0): ?>
                <div class="flex justify-between text-success">
                    <span>Discount</span>
                    <span>-₱<?= number_format($order['discount_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-muted text-sm">
                    <span>Tax</span>
                    <span>₱<?= number_format($order['tax'], 2) ?></span>
                </div>
                <div class="flex justify-between font-bold text-lg mt-2">
                    <span>Total</span>
                    <span>₱<?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
            
            <div class="text-center">
                <?php if($order['status'] === 'completed'): ?>
                <div class="alert alert-success">Payment Completed ✅</div>
                <a href="invoice.php?id=<?= $oid ?>" class="btn btn-secondary w-100 mb-2">Download Invoice</a>
                <a href="orders.php" class="btn btn-outline-primary w-100">Back to Orders</a>
                <?php else: ?>
                    <?php if(in_array($role, ['admin','manager','staff'])): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="confirm_payment">
                        <button type="submit" class="btn btn-success w-100 mb-2">Confirm Payment Received</button>
                    </form>
                    <?php else: ?>
                    <p class="text-muted mb-4">Please show this QR code to the cashier or scan with your payment app.</p>
                    <a href="orders.php" class="btn btn-outline-primary w-100">I Have Paid</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: "PAYMENT:<?= $oid ?>:<?= $order['total'] ?>",
    width: 200,
    height: 200
});
</script>

<?php require_once 'includes/footer.php'; ?>

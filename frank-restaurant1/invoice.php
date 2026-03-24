<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

$oid = intval($_GET['id'] ?? 0);

// Check if invoices table exists, create if not
try {
    $inv = db()->fetchOne("SELECT * FROM invoices WHERE order_id=?", [$oid]);
} catch (Exception $e) {
    // Create invoices table
    $sql = "CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        invoice_number VARCHAR(50) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id),
        UNIQUE KEY unique_invoice_number (invoice_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    db()->execute($sql);
    $inv = null; // Reset to try again
}

$order = db()->fetchOne("SELECT o.*, u.full_name, u.email, u.phone FROM orders o LEFT JOIN users u ON o.user_id=u.user_id WHERE o.order_id=?", [$oid]);

if (!$inv && $order && $order['status'] === 'completed') {
    // Generate invoice if not exists but paid
    $inv_num = 'INV-' . date('Ymd') . '-' . str_pad($oid, 4, '0', STR_PAD_LEFT);
    try {
        db()->execute("INSERT IGNORE INTO invoices (order_id, invoice_number, total_amount, tax_amount, discount_amount, status) VALUES (?,?,?,?,?,'paid')", [$oid, $inv_num, $order['total'], $order['tax'], $order['discount_amount'] ?? 0]);
        $inv = db()->fetchOne("SELECT * FROM invoices WHERE order_id=?", [$oid]);
    } catch (Exception $e) {
        // If insert fails, create a mock invoice
        $inv = [
            'id' => 1,
            'order_id' => $oid,
            'invoice_number' => $inv_num,
            'total_amount' => $order['total'],
            'tax_amount' => $order['tax'] ?? 0,
            'discount_amount' => $order['discount_amount'] ?? 0,
            'status' => 'paid',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
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
            <p>Email: contact@frankrestaurant.com</p>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> <?= $inv['invoice_number'] ?></p>
            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($inv['created_at'] ?? 'now')) ?></p>
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
        <div class="action-buttons" style="display: flex; gap: 10px; margin-bottom: 10px; justify-content: flex-end;">
            <button class="action-btn receipt-btn" onclick="window.open('receipt.php?id=<?= $oid ?>', '_blank')">🧾 Receipt</button>
            <button class="action-btn qr-btn" onclick="showQRCode()">📱 QR Details</button>
            <button class="action-btn invoice-btn" onclick="window.print()">📄 Invoice</button>
        </div>
        <button onclick="window.print()" style="padding: 15px 30px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size:16px; box-shadow:0 4px 10px rgba(0,0,0,0.2);">🖨️ Print / Save as PDF</button>
    </div>
    
    <!-- QR Code Modal -->
    <div id="qrModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h4>📱 Scan QR Code</h4>
                <button onclick="closeQRModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body text-center">
                <div class="qr-info">
                    <h5>Invoice Details</h5>
                    <p>Scan to view complete invoice information</p>
                </div>
                <div class="qr-code-container">
                    <img id="qrImage" src="" alt="Invoice QR Code" style="width: 200px; height: 200px; border: 2px solid #ddd; border-radius: 10px;">
                </div>
                <div class="qr-actions">
                    <button onclick="simulateQRScan()" class="btn btn-info">📱 Test QR Scan</button>
                    <button onclick="closeQRModal()" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    
    .action-btn {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .receipt-btn {
        background: #28a745;
        color: white;
    }
    
    .receipt-btn:hover {
        background: #218838;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .qr-btn {
        background: #17a2b8;
        color: white;
    }
    
    .qr-btn:hover {
        background: #138496;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .invoice-btn {
        background: #667eea;
        color: white;
    }
    
    .invoice-btn:hover {
        background: #5a6fd6;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-content {
        background: white;
        border-radius: 15px;
        padding: 0;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
        background: linear-gradient(135deg, #667eea 0%, #5a6fd6 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    
    .modal-header h4 {
        margin: 0;
        font-size: 18px;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: white;
        opacity: 0.8;
    }
    
    .close-btn:hover {
        opacity: 1;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .qr-info {
        margin-bottom: 20px;
    }
    
    .qr-info h5 {
        color: #667eea;
        margin-bottom: 5px;
    }
    
    .qr-info p {
        color: #666;
        margin: 0;
        font-size: 14px;
    }
    
    .qr-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-info {
        background: #17a2b8;
        color: white;
    }
    
    .btn-info:hover {
        background: #138496;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .action-btn {
            font-size: 12px;
            padding: 8px 12px;
        }
    }
    </style>
    
    <script>
    function showQRCode() {
        // Generate QR code for invoice details
        const orderId = '<?= $oid ?>';
        const qrData = `frank_restaurant_invoice:${orderId}:${Date.now()}`;
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`;
        
        document.getElementById('qrImage').src = qrUrl;
        document.getElementById('qrModal').style.display = 'flex';
        
        // Store the URL for QR scanning
        window.currentQRUrl = `qr_details.php?order_id=${orderId}`;
    }
    
    function closeQRModal() {
        document.getElementById('qrModal').style.display = 'none';
    }
    
    function simulateQRScan() {
        if (window.currentQRUrl) {
            window.open(window.currentQRUrl, '_blank');
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('qrModal');
        if (event.target == modal) {
            closeQRModal();
        }
    }
    </script>
</body>
</html>

<?php
$pageTitle    = 'Promo Codes';
require_once 'includes/header.php';

if (!in_array($role, ['admin','manager'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        try {
            db()->execute("DELETE FROM promo_codes WHERE id=?", [intval($_POST['delete_id'])]);
            redirect('promo_codes.php', 'Promo code deleted.', 'success');
        } catch (Exception $e) {
            redirect('promo_codes.php', 'Promo codes table not available. Please contact administrator.', 'error');
        }
    } else {
        try {
            $code = strtoupper(sanitize($_POST['code']));
            $type = sanitize($_POST['discount_type']);
            $val  = floatval($_POST['discount_value']);
            $min  = floatval($_POST['min_order']);
            $max  = intval($_POST['max_uses']);
            $from = sanitize($_POST['valid_from']) ?: null;
            $until= sanitize($_POST['valid_until']) ?: null;
            
            // Check if code already exists
            $existing = db()->fetchOne("SELECT id FROM promo_codes WHERE code = ?", [$code]);
            if ($existing) {
                redirect('promo_codes.php', 'Promo code already exists.', 'error');
            }
            
            db()->insert(
                "INSERT INTO promo_codes (code, discount_type, discount_value, min_order_amount, max_uses, valid_from, valid_until) VALUES (?,?,?,?,?,?,?)",
                [$code, $type, $val, $min, $max, $from, $until]
            );
            redirect('promo_codes.php', 'Promo code created.', 'success');
        } catch (Exception $e) {
            redirect('promo_codes.php', 'Promo codes table not available. Please contact administrator.', 'error');
        }
    }
}

try {
    $codes = db()->fetchAll("SELECT * FROM promo_codes ORDER BY created_at DESC");
} catch (Exception $e) {
    $codes = []; // Empty array if table doesn't exist
    error_log("Promo codes table not found: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <h3 class="card-title mb-3">Create Promo Code</h3>
            <form method="POST">
                <div class="form-group mb-3">
                    <label>Code</label>
                    <input type="text" name="code" class="form-control" required placeholder="SUMMER2025" style="text-transform:uppercase;">
                </div>
                <div class="row">
                    <div class="col-6 form-group mb-3">
                        <label>Type</label>
                        <select name="discount_type" class="form-control">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (₱)</option>
                        </select>
                    </div>
                    <div class="col-6 form-group mb-3">
                        <label>Value</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" required>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label>Min Order Amount</label>
                    <input type="number" step="0.01" name="min_order" class="form-control" value="0">
                </div>
                <div class="form-group mb-3">
                    <label>Max Uses (0 = unlimited)</label>
                    <input type="number" name="max_uses" class="form-control" value="0">
                </div>
                <div class="row">
                    <div class="col-6 form-group mb-3">
                        <label>Valid From</label>
                        <input type="date" name="valid_from" class="form-control">
                    </div>
                    <div class="col-6 form-group mb-3">
                        <label>Valid Until</label>
                        <input type="date" name="valid_until" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create Code</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <h3 class="card-title mb-3">Active Codes</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Order</th>
                        <th>Usage</th>
                        <th>Validity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($codes as $c): ?>
                    <tr>
                        <td class="font-bold"><?= htmlspecialchars($c['code']) ?></td>
                        <td>
                            <?= $c['discount_type']=='percentage' ? $c['discount_value'].'%' : '₱'.$c['discount_value'] ?>
                        </td>
                        <td>₱<?= number_format($c['min_order_amount'], 2) ?></td>
                        <td><?= $c['used_count'] ?> / <?= $c['max_uses']>0 ? $c['max_uses'] : '∞' ?></td>
                        <td class="text-sm text-muted">
                            <?php 
                            if ($c['valid_from']) echo date('M j', strtotime($c['valid_from']));
                            if ($c['valid_until']) echo ' - ' . date('M j', strtotime($c['valid_until']));
                            if (!$c['valid_from'] && !$c['valid_until']) echo 'Always';
                            ?>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this code?')">
                                <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

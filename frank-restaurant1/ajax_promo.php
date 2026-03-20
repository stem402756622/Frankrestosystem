<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
header('Content-Type: application/json');

$code = strtoupper(sanitize($_GET['code'] ?? ''));
$amount = floatval($_GET['amount'] ?? 0);

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'Enter a code.']);
    exit;
}

$promo = db()->fetchOne("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1", [$code]);

if (!$promo) {
    echo json_encode(['valid' => false, 'message' => 'Invalid code.']);
    exit;
}

// Check validity
$now = date('Y-m-d');
if ($promo['valid_from'] && $promo['valid_from'] > $now) {
    echo json_encode(['valid' => false, 'message' => 'Code not active yet.']);
    exit;
}
if ($promo['valid_until'] && $promo['valid_until'] < $now) {
    echo json_encode(['valid' => false, 'message' => 'Code expired.']);
    exit;
}
if ($promo['max_uses'] > 0 && $promo['used_count'] >= $promo['max_uses']) {
    echo json_encode(['valid' => false, 'message' => 'Code usage limit reached.']);
    exit;
}
if ($promo['min_order_amount'] > 0 && $amount < $promo['min_order_amount']) {
    echo json_encode(['valid' => false, 'message' => 'Min order amount is ₱' . $promo['min_order_amount']]);
    exit;
}

// Calculate discount
$discount = 0;
if ($promo['discount_type'] === 'percentage') {
    $discount = $amount * ($promo['discount_value'] / 100);
} else {
    $discount = $promo['discount_value'];
}

// Cap discount at total amount
if ($discount > $amount) $discount = $amount;

echo json_encode([
    'valid' => true,
    'discount' => round($discount, 2),
    'code' => $promo['code'],
    'message' => 'Code applied!'
]);
?>

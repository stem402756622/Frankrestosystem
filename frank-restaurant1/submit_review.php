<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = intval($_POST['order_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    
    // Validate order ownership
    $order = db()->fetchOne("SELECT user_id FROM orders WHERE order_id=?", [$oid]);
    if (!$order || $order['user_id'] != $user_id) {
        redirect('dashboard.php', 'Invalid order.', 'error');
    }
    
    // Check if already reviewed
    $exists = db()->fetchOne("SELECT id FROM reviews WHERE order_id=?", [$oid]);
    if ($exists) {
        redirect('dashboard.php', 'You have already reviewed this order.', 'error');
    }
    
    db()->insert(
        "INSERT INTO reviews (user_id, order_id, rating, comment) VALUES (?,?,?,?)",
        [$user_id, $oid, $rating, $comment]
    );
    
    redirect('dashboard.php', 'Thank you for your feedback!', 'success');
}
redirect('dashboard.php');
?>

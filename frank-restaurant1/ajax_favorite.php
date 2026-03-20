<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $action = $_POST['action'] ?? 'toggle';
    
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid item.']);
        exit;
    }
    
    try {
        if ($action === 'add') {
            db()->execute("INSERT IGNORE INTO saved_favorites (user_id, menu_item_id) VALUES (?,?)", [$user_id, $item_id]);
            $is_fav = true;
        } elseif ($action === 'remove') {
            db()->execute("DELETE FROM saved_favorites WHERE user_id=? AND menu_item_id=?", [$user_id, $item_id]);
            $is_fav = false;
        } else {
            // Toggle
            $exists = db()->fetchOne("SELECT id FROM saved_favorites WHERE user_id=? AND menu_item_id=?", [$user_id, $item_id]);
            if ($exists) {
                db()->execute("DELETE FROM saved_favorites WHERE user_id=? AND menu_item_id=?", [$user_id, $item_id]);
                $is_fav = false;
            } else {
                db()->execute("INSERT INTO saved_favorites (user_id, menu_item_id) VALUES (?,?)", [$user_id, $item_id]);
                $is_fav = true;
            }
        }
        
        echo json_encode(['success' => true, 'is_favorite' => $is_fav]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>

<?php
$pageTitle    = 'My Favorites';
require_once 'includes/header.php';

requireLogin();

// Handle add action from loyalty page
$action = $_GET['action'] ?? '';
$item_id = $_GET['item_id'] ?? 0;

if ($action === 'add' && $item_id > 0) {
    $item_id = intval($item_id);
    if ($item_id > 0) {
        try {
            db()->execute("INSERT IGNORE INTO saved_favorites (user_id, menu_item_id) VALUES (?,?)", [$user_id, $item_id]);
            $_SESSION['success'] = 'Item added to favorites!';
        } catch (Exception $e) {
            // Table doesn't exist, create it first
            try {
                $sql = "CREATE TABLE IF NOT EXISTS saved_favorites (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    menu_item_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_menu (user_id, menu_item_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                db()->execute($sql);
                
                // Now try to add the favorite
                db()->execute("INSERT IGNORE INTO saved_favorites (user_id, menu_item_id) VALUES (?,?)", [$user_id, $item_id]);
                $_SESSION['success'] = 'Item added to favorites!';
            } catch (Exception $e2) {
                $_SESSION['error'] = 'Error creating favorites table: ' . $e2->getMessage();
            }
        }
    }
    // Redirect back to loyalty page
    header('Location: loyalty.php');
    exit;
}

// Handle remove action
if ($action === 'remove' && $item_id > 0) {
    $item_id = intval($item_id);
    if ($item_id > 0) {
        try {
            db()->execute("DELETE FROM saved_favorites WHERE user_id = ? AND menu_item_id = ?", [$user_id, $item_id]);
            $_SESSION['success'] = 'Item removed from favorites!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error removing from favorites: ' . $e->getMessage();
        }
    }
}

// Get user's favorites
$favorites = [];
try {
    $favorites = db()->fetchAll(
        "SELECT sf.*, mi.name, mi.price, mi.description, mi.image_url 
         FROM saved_favorites sf 
         LEFT JOIN menu_items mi ON sf.menu_item_id = mi.item_id 
         WHERE sf.user_id = ? 
         ORDER BY sf.created_at DESC",
        [$user_id]
    );
} catch (Exception $e) {
    // Table doesn't exist, create it
    try {
        // Create saved_favorites table
        $sql = "CREATE TABLE IF NOT EXISTS saved_favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_menu (user_id, menu_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        db()->execute($sql);
        
        // Try to get favorites again
        $favorites = db()->fetchAll(
            "SELECT sf.*, mi.name, mi.price, mi.description, mi.image_url 
             FROM saved_favorites sf 
             LEFT JOIN menu_items mi ON sf.menu_item_id = mi.item_id 
             WHERE sf.user_id = ? 
             ORDER BY sf.created_at DESC",
            [$user_id]
        );
    } catch (Exception $e2) {
        $_SESSION['error'] = 'Error creating favorites table: ' . $e2->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">❤️ My Favorites</h3>
                    <a href="loyalty.php" class="btn btn-secondary btn-sm">
                        🎯 Back to Loyalty
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success'] ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($favorites)): ?>
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <h4 class="text-muted">No favorites yet</h4>
                                <p class="text-muted">Start adding your favorite menu items!</p>
                                <a href="loyalty.php" class="btn btn-primary">
                                    🎯 Browse Menu Items
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach($favorites as $favorite): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <?php if ($favorite['image_url']): ?>
                                            <img src="<?= htmlspecialchars($favorite['image_url']) ?>" 
                                                 class="card-img-top" 
                                                 alt="<?= htmlspecialchars($favorite['name']) ?>"
                                                 style="height: 200px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($favorite['name']) ?></h5>
                                            <p class="card-text text-muted"><?= htmlspecialchars($favorite['description']) ?></p>
                                            <p class="card-text">
                                                <strong>₱<?= number_format($favorite['price'], 2) ?></strong>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <a href="favorites.php?action=remove&item_id=<?= $favorite['menu_item_id'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Remove from favorites?')">
                                                🗑️ Remove
                                            </a>
                                            <a href="loyalty.php" class="btn btn-primary btn-sm">
                                                🎯 Back to Loyalty
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

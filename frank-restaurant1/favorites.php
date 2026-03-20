<?php
$pageTitle    = 'My Favorites';
require_once 'includes/header.php';

requireLogin();

$favorites = db()->fetchAll(
    "SELECT sf.*, mi.name, mi.price, mi.description, mi.image_url 
     FROM saved_favorites sf
     JOIN menu_items mi ON sf.menu_item_id = mi.item_id
     WHERE sf.user_id = ?
     ORDER BY sf.created_at DESC",
    [$user_id]
);
?>

<div class="flex justify-between items-center mb-4">
    <h2 class="section-title">My Favorites</h2>
    <a href="menu.php" class="btn btn-secondary">Browse Menu</a>
</div>

<?php if($favorites): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach($favorites as $fav): ?>
    <div class="card animate-in">
        <div class="flex justify-between items-start mb-2">
            <h3 class="font-bold text-lg"><?= htmlspecialchars($fav['name']) ?></h3>
            <span class="text-primary font-bold">₱<?= number_format($fav['price'], 2) ?></span>
        </div>
        <p class="text-muted text-sm mb-4"><?= htmlspecialchars($fav['description']) ?></p>
        
        <div class="flex gap-2 mt-auto">
            <a href="menu.php?add=<?= $fav['menu_item_id'] ?>" class="btn btn-primary btn-sm flex-1 text-center">Add to Order</a>
            <button class="btn btn-danger btn-sm btn-icon" onclick="removeFavorite(<?= $fav['menu_item_id'] ?>, this)">💔</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">❤️</div>
    <div class="empty-title">No favorites yet</div>
    <div class="empty-text">Start adding items from the menu!</div>
    <a href="menu.php" class="btn btn-primary mt-3">Go to Menu</a>
</div>
<?php endif; ?>

<script>
function removeFavorite(itemId, btn) {
    if (!confirm('Remove from favorites?')) return;
    
    fetch('ajax_favorite.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove&item_id=' + itemId
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            btn.closest('.card').remove();
            if (document.querySelectorAll('.card').length === 0) location.reload();
        } else {
            alert(data.message || 'Error removing favorite');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>

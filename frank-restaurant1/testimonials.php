<?php
$pageTitle    = 'Guest Reviews';
require_once 'includes/header.php';

// Aggregated ratings
$stats = db()->fetchOne("SELECT AVG(overall_rating) as overall, AVG(food_rating) as food, AVG(service_rating) as service, AVG(ambiance_rating) as ambiance, COUNT(*) as total FROM feedback WHERE is_published=1");

// Recent reviews
$reviews = db()->fetchAll("SELECT * FROM feedback WHERE is_published=1 ORDER BY created_at DESC LIMIT 20");
?>

<div class="row mb-5">
    <div class="col-md-4">
        <div class="card text-center h-100 flex flex-col justify-center">
            <h2 class="text-6xl font-bold text-primary mb-2" style="font-size:4rem;"><?= number_format($stats['overall'] ?? 0, 1) ?></h2>
            <div class="text-2xl text-yellow-400 mb-2">★★★★★</div>
            <p class="text-muted"><?= $stats['total'] ?? 0 ?> verified reviews</p>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card h-100">
            <h3 class="card-title mb-3">Rating Breakdown</h3>
            <div class="space-y-4">
                <?php foreach(['Food'=>$stats['food']??0, 'Service'=>$stats['service']??0, 'Ambiance'=>$stats['ambiance']??0] as $label => $val): ?>
                <div class="mb-3">
                    <div class="flex justify-between mb-1">
                        <span><?= $label ?></span>
                        <span class="font-bold"><?= number_format($val, 1) ?></span>
                    </div>
                    <div class="progress h-2" style="height:8px; background:#eee; border-radius:4px; overflow:hidden;">
                        <div class="progress-bar bg-primary" style="width: <?= ($val/5)*100 ?>%; height:100%; background:var(--accent-primary);"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-between items-center mb-4">
    <h2 class="section-title">What Our Guests Say</h2>
    <a href="feedback.php" class="btn btn-primary">Leave a Review</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach($reviews as $r): ?>
    <div class="card animate-in">
        <div class="flex justify-between mb-2">
            <div class="font-bold"><?= htmlspecialchars($r['name']) ?></div>
            <div class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
        </div>
        <div class="flex items-center gap-1 mb-2 text-yellow-400">
            <?php for($i=0; $i<$r['overall_rating']; $i++) echo '★'; ?>
            <?php for($i=$r['overall_rating']; $i<5; $i++) echo '<span class="text-gray-300" style="color:#ddd;">★</span>'; ?>
        </div>
        <p class="text-gray-700"><?= htmlspecialchars($r['comment']) ?></p>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

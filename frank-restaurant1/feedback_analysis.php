<?php
$pageTitle    = 'Feedback Analysis';
$pageSubtitle = 'Customer satisfaction insights';
require_once 'includes/header.php';

if (!in_array($role, ['admin', 'manager'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle publish/unpublish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $fid = intval($_POST['feedback_id']);
    
    if ($_POST['action'] === 'publish') {
        db()->execute("UPDATE feedback SET is_published=1 WHERE id=?", [$fid]);
        redirect('feedback_analysis.php', 'Feedback published to testimonials.', 'success');
    }
    if ($_POST['action'] === 'unpublish') {
        db()->execute("UPDATE feedback SET is_published=0 WHERE id=?", [$fid]);
        redirect('feedback_analysis.php', 'Feedback unpublished.', 'success');
    }
    if ($_POST['action'] === 'delete') {
        db()->execute("DELETE FROM feedback WHERE id=?", [$fid]);
        redirect('feedback_analysis.php', 'Feedback deleted.', 'success');
    }
}

// Fetch analytics
$totalFeedback = db()->fetchOne("SELECT COUNT(*) as cnt FROM feedback")['cnt'] ?? 0;
$publishedCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM feedback WHERE is_published=1")['cnt'] ?? 0;

// Average ratings
$avgRatings = db()->fetchOne(
    "SELECT 
     COALESCE(AVG(overall_rating),0) as overall,
     COALESCE(AVG(food_rating),0) as food,
     COALESCE(AVG(service_rating),0) as service,
     COALESCE(AVG(ambiance_rating),0) as ambiance
     FROM feedback"
);

// Rating distribution
$ratingDist = db()->fetchAll(
    "SELECT overall_rating as rating, COUNT(*) as cnt 
     FROM feedback 
     GROUP BY overall_rating 
     ORDER BY overall_rating DESC"
);

// Recent feedback with user info
$recentFeedback = db()->fetchAll(
    "SELECT f.*, u.full_name, u.email as user_email, u.vip_status
     FROM feedback f
     LEFT JOIN users u ON f.user_id=u.user_id
     ORDER BY f.created_at DESC
     LIMIT 50"
);

// Feedback by date (last 30 days)
$feedbackTrend = db()->fetchAll(
    "SELECT DATE(created_at) as day, COUNT(*) as cnt, AVG(overall_rating) as avg_rating
     FROM feedback
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
);

// Common themes from comments (simple keyword count)
$commonWords = ['excellent', 'great', 'good', 'amazing', 'delicious', 'fantastic', 'love', 'perfect',
                'bad', 'poor', 'terrible', 'disappointing', 'slow', 'cold', 'rude', 'overpriced'];

$wordCounts = [];
foreach ($commonWords as $word) {
    $count = db()->fetchOne(
        "SELECT COUNT(*) as cnt FROM feedback WHERE comment LIKE ?",
        ["%$word%"]
    )['cnt'] ?? 0;
    if ($count > 0) {
        $wordCounts[$word] = $count;
    }
}
arsort($wordCounts);

// Category scores
$categoryScores = [
    'Overall' => round($avgRatings['overall'], 1),
    'Food' => round($avgRatings['food'], 1),
    'Service' => round($avgRatings['service'], 1),
    'Ambiance' => round($avgRatings['ambiance'], 1)
];

$ratingColors = [
    5 => 'success',
    4 => 'success',
    3 => 'warning',
    2 => 'danger',
    1 => 'danger'
];
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">Feedback Analysis</h2>
        <p class="section-subtitle">Customer satisfaction metrics and insights</p>
    </div>
    <a href="feedback.php" class="btn btn-outline-primary">Submit Test Feedback</a>
</div>

<!-- Overall Score Cards -->
<div class="stats-grid mb-4">
    <?php foreach($categoryScores as $category => $score): 
        $colorClass = $score >= 4 ? 'success' : ($score >= 3 ? 'warning' : 'danger');
        $emoji = $score >= 4 ? '😊' : ($score >= 3 ? '😐' : '😞');
    ?>
    <div class="stat-card animate-in">
        <div class="stat-icon"><?= $emoji ?></div>
        <div class="stat-value" style="color:var(--<?= $colorClass ?>);"><?= number_format($score, 1) ?>/5</div>
        <div class="stat-label"><?= $category ?> Rating</div>
    </div>
    <?php endforeach; ?>
    
    <div class="stat-card animate-in">
        <div class="stat-icon">💬</div>
        <div class="stat-value"><?= $totalFeedback ?></div>
        <div class="stat-label">Total Feedback</div>
        <div class="stat-change"><?= $publishedCount ?> published</div>
    </div>
</div>

<div class="content-grid mb-4">
    <!-- Rating Distribution -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">⭐ Rating Distribution</h3>
        </div>
        <?php if($ratingDist): ?>
        <div style="display:grid;gap:0.75rem;">
            <?php 
            $totalRatings = array_sum(array_column($ratingDist,'cnt'));
            foreach([5,4,3,2,1] as $star): 
                $count = 0;
                foreach($ratingDist as $r) {
                    if ($r['rating'] == $star) {
                        $count = $r['cnt'];
                        break;
                    }
                }
                $pct = $totalRatings > 0 ? round($count/$totalRatings*100) : 0;
            ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm"><?= str_repeat('★', $star) ?></span>
                    <span class="text-sm fw-600"><?= $count ?> <span class="text-muted">(<?= $pct ?>%)</span></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width:<?= $pct ?>%;<?= $star >= 4 ? 'background:var(--success);' : ($star == 3 ? 'background:var(--warning);' : 'background:var(--danger);') ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><span class="empty-icon">📊</span><div class="empty-text">No ratings yet</div></div>
        <?php endif; ?>
    </div>
    
    <!-- Common Themes -->
    <div class="card animate-in">
        <div class="card-header">
            <h3 class="card-title">📝 Common Words in Comments</h3>
        </div>
        <?php if($wordCounts): ?>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <?php foreach(array_slice($wordCounts, 0, 15, true) as $word => $count): 
                $isPositive = in_array($word, ['excellent', 'great', 'good', 'amazing', 'delicious', 'fantastic', 'love', 'perfect']);
                $badgeClass = $isPositive ? 'success' : 'danger';
            ?>
            <span class="badge badge-<?= $badgeClass ?>" style="font-size:0.9rem;padding:0.4rem 0.8rem;">
                <?= htmlspecialchars($word) ?> (<?= $count ?>)
            </span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><span class="empty-icon">📝</span><div class="empty-text">Not enough comment data</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- Feedback Trend Chart -->
<?php if($feedbackTrend): ?>
<div class="card animate-in mb-4">
    <div class="card-header">
        <h3 class="card-title">📈 Feedback Trend (Last 30 Days)</h3>
    </div>
    <div style="display:flex;align-items:flex-end;gap:0.5rem;height:150px;padding:1rem;">
        <?php
        $maxCnt = max(array_column($feedbackTrend,'cnt')) ?: 1;
        foreach($feedbackTrend as $d):
            $height = ($d['cnt'] / $maxCnt) * 100;
            $color = $d['avg_rating'] >= 4 ? 'var(--success)' : ($d['avg_rating'] >= 3 ? 'var(--warning)' : 'var(--danger)');
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.3rem;justify-content:flex-end;height:100%;">
            <div class="text-xs fw-600" style="color:<?= $color ?>;"><?= number_format($d['avg_rating'], 1) ?></div>
            <div style="width:100%;background:<?= $color ?>;border-radius:4px 4px 0 0;height:<?= max($height, 5) ?>%;min-height:4px;opacity:0.7;"></div>
            <div class="text-xs text-muted"><?= date('M j', strtotime($d['day'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Feedback Table -->
<div class="card animate-in">
    <div class="card-header">
        <h3 class="card-title">💬 Recent Feedback</h3>
    </div>
    <?php if($recentFeedback): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Ratings</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentFeedback as $f): ?>
                <tr>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($f['name']) ?></div>
                        <?php if($f['full_name']): ?>
                        <div class="text-xs text-muted"><?= htmlspecialchars($f['full_name']) ?> <?= $f['vip_status'] ? '👑' : '' ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex flex-col gap-1 text-xs">
                            <span>Overall: <?= $f['overall_rating'] ?>/5</span>
                            <span class="text-muted">Food: <?= $f['food_rating'] ?? '-' ?> · Service: <?= $f['service_rating'] ?? '-' ?> · Ambiance: <?= $f['ambiance_rating'] ?? '-' ?></span>
                        </div>
                    </td>
                    <td style="max-width:300px;">
                        <div class="text-sm"><?= nl2br(htmlspecialchars($f['comment'] ?? '-')) ?></div>
                    </td>
                    <td>
                        <div class="text-sm"><?= date('M j, Y', strtotime($f['created_at'])) ?></div>
                        <div class="text-xs text-muted"><?= date('g:i A', strtotime($f['created_at'])) ?></div>
                    </td>
                    <td>
                        <?php if($f['is_published']): ?>
                        <span class="badge badge-success">Published</span>
                        <?php else: ?>
                        <span class="badge badge-muted">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <?php if($f['is_published']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="unpublish">
                                <input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" title="Unpublish">👁️‍🗨️</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="publish">
                                <input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm" title="Publish">✓</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this feedback?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:2rem;">
        <span class="empty-icon">💬</span>
        <div class="empty-title">No feedback yet</div>
        <div class="empty-text">Customer feedback will appear here.</div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

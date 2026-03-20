<?php
$pageTitle    = 'Live Queue';
require_once 'includes/header.php';

$qid = $_COOKIE['queue_id'] ?? 0;
$my_q = null;

if ($qid) {
    $my_q = db()->fetchOne("SELECT * FROM queue WHERE id=?", [$qid]);
    if (!$my_q || $my_q['status'] !== 'waiting') {
        $my_q = null;
        setcookie('queue_id', '', time() - 3600);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$my_q) {
    $name = sanitize($_POST['name']);
    $phone= sanitize($_POST['phone']);
    $size = intval($_POST['party_size']);
    
    $last = db()->fetchOne("SELECT MAX(queue_number) as num FROM queue WHERE DATE(joined_at)=CURDATE()");
    $num = ($last && isset($last['num'])) ? $last['num'] + 1 : 1;
    
    $id = db()->insert(
        "INSERT INTO queue (customer_name, phone, party_size, queue_number, status) VALUES (?,?,?,?, 'waiting')",
        [$name, $phone, $size, $num]
    );
    
    setcookie('queue_id', $id, time() + 86400); // 24 hours
    redirect('queue_status.php');
}

// Calculate wait time
$waiting_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM queue WHERE status='waiting'")['cnt'];
$est_wait = $waiting_count * 15; // 15 mins per party roughly

?>

<div class="row justify-center">
    <div class="col-md-6">
        <div class="card text-center animate-in">
            <h2 class="card-title mb-4">Frank's Live Queue</h2>
            
            <?php if($my_q): ?>
                <div class="mb-4">
                    <div class="text-muted text-sm uppercase tracking-wide">Your Queue Number</div>
                    <div class="font-bold text-6xl text-primary my-2">Q<?= $my_q['queue_number'] ?></div>
                    <div class="text-muted">Party of <?= $my_q['party_size'] ?></div>
                </div>
                
                <?php 
                // Position in queue
                $ahead = db()->fetchOne("SELECT COUNT(*) as cnt FROM queue WHERE status='waiting' AND id < ?", [$my_q['id']])['cnt'];
                $my_wait = ($ahead + 1) * 15;
                ?>
                
                <div class="bg-gray-100 p-4 rounded mb-4" style="background:#f8f9fa;">
                    <div class="row">
                        <div class="col-6" style="border-right:1px solid #ddd;">
                            <div class="font-bold text-xl"><?= $ahead ?></div>
                            <div class="text-xs text-muted">Parties Ahead</div>
                        </div>
                        <div class="col-6">
                            <div class="font-bold text-xl">~<?= $my_wait ?> min</div>
                            <div class="text-xs text-muted">Est. Wait</div>
                        </div>
                    </div>
                </div>
                
                <p class="text-muted text-sm mb-4">Please stay nearby. We will call your number soon.</p>
                <a href="menu.php" class="btn btn-secondary w-100">Browse Menu While Waiting</a>
                
            <?php else: ?>
                <div class="mb-4">
                    <div class="text-muted text-sm">Current Wait Time</div>
                    <div class="font-bold text-4xl text-primary my-2">~<?= $est_wait ?> min</div>
                    <div class="text-muted"><?= $waiting_count ?> parties waiting</div>
                </div>
                
                <hr class="my-4">
                
                <h3 class="font-bold mb-3">Join the Queue</h3>
                <form method="POST">
                    <div class="form-group mb-3 text-left" style="text-align:left;">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3 text-left" style="text-align:left;">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group mb-3 text-left" style="text-align:left;">
                        <label>Party Size</label>
                        <input type="number" name="party_size" class="form-control" required min="1" value="2">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Get in Line</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

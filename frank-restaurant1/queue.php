<?php
$pageTitle    = 'Queue Management';
require_once 'includes/header.php';

if (!in_array($role, ['admin','manager','staff'])) {
    redirect('index.php', 'Access denied.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_customer'])) {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $size = intval($_POST['party_size']);
        
        try {
            // Get next number
            $last = db()->fetchOne("SELECT MAX(queue_number) as num FROM queue WHERE DATE(joined_at)=CURDATE()");
            $num = ($last && isset($last['num'])) ? $last['num'] + 1 : 1;
            
            db()->insert(
                "INSERT INTO queue (customer_name, phone, party_size, queue_number, status) VALUES (?,?,?,?, 'waiting')",
                [$name, $phone, $size, $num]
            );
            redirect('queue.php', 'Added to queue.', 'success');
        } catch (Exception $e) {
            redirect('queue.php', 'Queue table not available. Please contact administrator.', 'error');
        }
    }
    
    if (isset($_POST['update_status'])) {
        $id = intval($_POST['queue_id']);
        $status = sanitize($_POST['status']);
        
        try {
            if ($status === 'seated') {
                db()->execute("UPDATE queue SET status='seated', seated_at=NOW() WHERE id=?", [$id]);
            } else {
                db()->execute("UPDATE queue SET status=? WHERE id=?", [$status, $id]);
            }
            redirect('queue.php', 'Status updated.', 'success');
        } catch (Exception $e) {
            redirect('queue.php', 'Queue table not available. Please contact administrator.', 'error');
        }
    }
}

try {
    $waiting = db()->fetchAll("SELECT * FROM queue WHERE status='waiting' ORDER BY joined_at ASC");
} catch (Exception $e) {
    $waiting = []; // Empty array if table doesn't exist
    error_log("Queue table not found: " . $e->getMessage());
}
$avg_wait = 15; // est minutes per party
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <h3 class="card-title mb-3">Add Walk-in</h3>
            <form method="POST">
                <input type="hidden" name="add_customer" value="1">
                <div class="form-group mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>Party Size</label>
                    <input type="number" name="party_size" class="form-control" required min="1">
                </div>
                <button type="submit" class="btn btn-primary w-100">Add to Queue</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <h3 class="card-title mb-3">Current Queue</h3>
            <?php if($waiting): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Wait Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($waiting as $idx => $q): ?>
                        <tr>
                            <td><span class="badge badge-primary rounded-circle text-lg px-2">Q<?= $q['queue_number'] ?></span></td>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($q['customer_name']) ?></div>
                                <div class="text-sm text-muted"><?= $q['party_size'] ?> ppl • <?= htmlspecialchars($q['phone']) ?></div>
                            </td>
                            <td>
                                <?php 
                                $waited = round((time() - strtotime($q['joined_at'])) / 60);
                                echo $waited . ' mins';
                                ?>
                            </td>
                            <td>
                                <form method="POST" class="flex gap-1">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="queue_id" value="<?= $q['id'] ?>">
                                    <button type="submit" name="status" value="seated" class="btn btn-sm btn-success">Seat</button>
                                    <button type="submit" name="status" value="left" class="btn btn-sm btn-danger">Left</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">No customers in queue.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

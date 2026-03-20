<?php
$pageTitle    = 'Peak Hours Configuration';
require_once 'includes/header.php';

if (!in_array($role, ['admin','manager'])) {
    redirect('index.php', 'Access denied.', 'error');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        try {
            db()->execute("DELETE FROM peak_hours WHERE id=?", [intval($_POST['delete_id'])]);
            redirect('peak_hours.php', 'Peak hour rule deleted.', 'success');
        } catch (Exception $e) {
            redirect('peak_hours.php', 'Peak hours table not available. Please contact administrator.', 'error');
        }
    } else {
        try {
            $day   = intval($_POST['day_of_week']);
            $start = sanitize($_POST['start_time']);
            $end   = sanitize($_POST['end_time']);
            $max   = intval($_POST['max_bookings']);
            
            if ($start >= $end) {
                $error = "Start time must be before end time.";
            } else {
                db()->insert(
                    "INSERT INTO peak_hours (day_of_week, start_time, end_time, max_bookings_per_slot) VALUES (?,?,?,?)",
                    [$day, $start, $end, $max]
                );
                redirect('peak_hours.php', 'Peak hour rule added.', 'success');
            }
        } catch (Exception $e) {
            redirect('peak_hours.php', 'Peak hours table not available. Please contact administrator.', 'error');
        }
    }
}

try {
    $rules = db()->fetchAll("SELECT * FROM peak_hours ORDER BY day_of_week, start_time");
} catch (Exception $e) {
    $rules = []; // Empty array if table doesn't exist
    error_log("Peak hours table not found: " . $e->getMessage());
}
$days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <h3 class="card-title mb-3">Add Peak Hour Rule</h3>
            <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group mb-3">
                    <label>Day of Week</label>
                    <select name="day_of_week" class="form-control" required>
                        <?php foreach($days as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>Max Bookings per Slot</label>
                    <input type="number" name="max_bookings" class="form-control" min="1" value="5" required>
                    <small class="text-muted">Maximum concurrent reservations allowed.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Add Rule</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <h3 class="card-title mb-3">Current Rules</h3>
            <?php if($rules): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time Range</th>
                        <th>Limit</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rules as $r): ?>
                    <tr>
                        <td><?= $days[$r['day_of_week']] ?></td>
                        <td><?= date('g:i A', strtotime($r['start_time'])) ?> - <?= date('g:i A', strtotime($r['end_time'])) ?></td>
                        <td><?= $r['max_bookings_per_slot'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this rule?')">
                                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted">No peak hour rules defined.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

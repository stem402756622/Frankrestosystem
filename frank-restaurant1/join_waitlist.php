<?php
$pageTitle    = 'Join Waitlist';
$pageSubtitle = 'Get notified when a table opens up';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $size  = intval($_POST['party_size']);
    $date  = sanitize($_POST['date']);
    $time  = sanitize($_POST['time']);

    if (!$name || !$email || !$phone || !$size || !$date || !$time) {
        $error = 'Please fill in all fields.';
    } else {
        db()->insert(
            "INSERT INTO waitlist (customer_name, email, phone, party_size, requested_date, requested_time, status) VALUES (?,?,?,?,?,?, 'waiting')",
            [$name, $email, $phone, $size, $date, $time]
        );
        $success = 'You have been added to the waitlist. We will notify you if a table becomes available.';
    }
}
?>

<div class="row justify-center">
    <div class="col-md-6">
        <div class="card">
            <?php if($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <a href="index.php" class="btn btn-primary">Return Home</a>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user_id ? ($user['full_name']??'') : '') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user_id ? ($user['email']??'') : '') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control" required value="<?= htmlspecialchars($user_id ? ($user['phone']??'') : '') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label>Party Size</label>
                        <input type="number" name="party_size" class="form-control" required min="1" value="<?= htmlspecialchars($_GET['size'] ?? 2) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label>Time</label>
                            <input type="time" name="time" class="form-control" required value="<?= htmlspecialchars($_GET['time'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Join Waitlist</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

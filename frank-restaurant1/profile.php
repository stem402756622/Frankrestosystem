<?php
$pageTitle    = 'My Profile';
$pageSubtitle = 'Account settings';
require_once 'includes/header.php';

$user = db()->fetchOne("SELECT * FROM users WHERE user_id=?", [$user_id]);
$prefs = db()->fetchAll("SELECT * FROM customer_preferences WHERE user_id=?", [$user_id]);

$error = $success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $phone     = sanitize($_POST['phone'] ?? '');
        $email     = sanitize($_POST['email'] ?? '');

        if (!$full_name || !$email) {
            $error = 'Name and email are required.';
        } else {
            db()->execute("UPDATE users SET full_name=?, phone=?, email=? WHERE user_id=?", [$full_name, $phone, $email, $user_id]);
            $_SESSION['full_name'] = $full_name;
            redirect('profile.php', 'Profile updated successfully!', 'success');
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_pw !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_pw, PASSWORD_BCRYPT);
            db()->execute("UPDATE users SET password=? WHERE user_id=?", [$hash, $user_id]);
            redirect('profile.php', 'Password changed successfully!', 'success');
        }
    }
}

// Stats
$resCount  = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE user_id=?", [$user_id])['cnt'] ?? 0;
$doneCount = db()->fetchOne("SELECT COUNT(*) as cnt FROM reservations WHERE user_id=? AND status='completed'", [$user_id])['cnt'] ?? 0;
?>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="section-title">My Profile</h2>
        <p class="section-subtitle">Manage your account settings</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" data-dismiss="5000">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="content-grid">
    <!-- Profile Card -->
    <div>
        <div class="card animate-in mb-3">
            <div style="text-align:center;padding:1.5rem 0 1rem;">
                <div class="user-avatar float" style="width:80px;height:80px;font-size:2rem;margin:0 auto 1rem;border-radius:50%;border:3px solid var(--accent-primary);">
                    <?= $initials ?>
                </div>
                <h3 style="font-size:1.3rem;margin-bottom:0.25rem;"><?= htmlspecialchars($user['full_name']) ?></h3>
                <div class="text-muted text-sm">@<?= htmlspecialchars($user['username']) ?></div>
                <div class="mt-2">
                    <span class="badge badge-primary"><?= ucfirst($user['role']) ?></span>
                    <?php if($user['vip_status']): ?><span class="badge badge-vip ml-1">👑 VIP</span><?php endif; ?>
                </div>
            </div>
            <div class="divider"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center;gap:0.5rem;">
                <div>
                    <div class="fw-700" style="font-size:1.3rem;color:var(--accent-primary);"><?= $resCount ?></div>
                    <div class="text-xs text-muted">Reservations</div>
                </div>
                <div>
                    <div class="fw-700" style="font-size:1.3rem;color:var(--success);"><?= $doneCount ?></div>
                    <div class="text-xs text-muted">Visits</div>
                </div>
                <div>
                    <div class="fw-700" style="font-size:1.3rem;color:var(--warning);">⭐ <?= $user['loyalty_points'] ?></div>
                    <div class="text-xs text-muted">Points</div>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">🔒 Change Password</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger w-100" style="justify-content:center;">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Edit Profile -->
    <div>
        <div class="card animate-in mb-3">
            <div class="card-header">
                <h3 class="card-title">✏️ Edit Profile</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:0.6;">
                    <div class="text-xs text-muted mt-1">Username cannot be changed.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100 glow" style="justify-content:center;">Save Changes</button>
            </form>
        </div>

        <!-- Preferences -->
        <?php if($prefs): ?>
        <div class="card animate-in">
            <div class="card-header">
                <h3 class="card-title">🎯 My Preferences</h3>
            </div>
            <div style="display:grid;gap:0.5rem;">
                <?php foreach($prefs as $p): ?>
                <div class="flex justify-between" style="padding:0.5rem;background:var(--bg-tertiary);border-radius:var(--radius-sm);">
                    <span class="text-sm text-muted" style="text-transform:capitalize;"><?= str_replace('_',' ',$p['preference_type']) ?></span>
                    <span class="text-sm fw-600" style="text-transform:capitalize;"><?= str_replace('_',' ',$p['preference_value']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

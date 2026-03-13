<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

if (isLoggedIn()) { redirect('index.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = db()->fetchOne(
            "SELECT * FROM users WHERE username=? OR email=?",
            [$username, $username]
        );
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            redirect('dashboard.php', 'Welcome back, ' . $user['full_name'] . '!');
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Frank Restaurant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-bg-orb auth-bg-orb-1"></div>
    <div class="auth-bg-orb auth-bg-orb-2"></div>

    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon float" style="width:64px;height:64px;font-size:1.8rem;margin:0 auto 1rem;border-radius:16px;display:flex;align-items:center;justify-content:center;background:var(--gradient-primary);box-shadow:var(--shadow-glow);">🍽️</div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to Frank Restaurant</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger" data-dismiss="5000">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <div class="floating-label">
                    <input type="text" name="username" id="username" class="form-control" placeholder=" " value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                    <label for="username">Username or Email</label>
                </div>
            </div>

            <div class="form-group">
                <div class="floating-label">
                    <input type="password" name="password" id="password" class="form-control" placeholder=" " required autocomplete="current-password">
                    <label for="password">Password</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 glow" style="margin-top:0.5rem;justify-content:center;padding:0.85rem;">
                Sign In ➜
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Create one</a>
        </div>

        <!-- Demo credentials - Hidden for production -->
        <!--
        <div class="divider"></div>
        <div style="background:var(--bg-tertiary);border-radius:var(--radius-sm);padding:1rem;font-size:0.8rem;">
            <div style="font-weight:700;margin-bottom:0.5rem;color:var(--text-secondary);">🔑 Demo Accounts</div>
            <div style="display:grid;gap:0.25rem;color:var(--text-muted);">
                <span>Admin: <strong style="color:var(--text-primary)">admin / password</strong></span>
                <span>Manager: <strong style="color:var(--text-primary)">manager / password</strong></span>
                <span>Staff: <strong style="color:var(--text-primary)">staff1 / password</strong></span>
                <span>Customer: <strong style="color:var(--text-primary)">john_doe / password</strong></span>
            </div>
        </div>
        -->
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>

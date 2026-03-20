<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

if (isLoggedIn()) { redirect('index.php'); }

$error   = '';
$success = false;
$tables  = db()->fetchAll("SELECT * FROM restaurant_tables WHERE status='available' ORDER BY table_number");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $pref_table= intval($_POST['preferred_table_id'] ?? 0);

    if (!$full_name || !$username || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check duplicates
        $existing = db()->fetchOne("SELECT user_id FROM users WHERE username=? OR email=?", [$username, $email]);
        if ($existing) {
            $error = 'Username or email already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $table_id = $pref_table > 0 ? $pref_table : null;
            $user_id = db()->insert(
                "INSERT INTO users (username, email, password, full_name, phone, preferred_table_id) VALUES (?,?,?,?,?,?)",
                [$username, $email, $hash, $full_name, $phone, $table_id]
            );

            // Save preferences
            if (!empty($_POST['preferences'])) {
                foreach ($_POST['preferences'] as $type => $val) {
                    db()->execute(
                        "INSERT INTO customer_preferences (user_id, preference_type, preference_value) VALUES (?,?,?)",
                        [$user_id, sanitize($type), sanitize($val)]
                    );
                }
            }

            $_SESSION['user_id']   = $user_id;
            $_SESSION['username']  = $username;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role']      = 'customer';

            $flash = ['msg' => "Welcome to Frank, $full_name! Your account is ready.", 'type' => 'success', 'confetti' => true];
            $_SESSION['flash'] = $flash;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Frank Restaurant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="theme-switcher">
    <div class="theme-btn" data-theme-target="dark"  title="Dark"></div>
    <div class="theme-btn" data-theme-target="light" title="Light"></div>
    <div class="theme-btn" data-theme-target="ocean" title="Ocean"></div>
</div>

<div class="auth-page" style="padding:3rem 2rem;align-items:flex-start;">
    <div class="auth-bg-orb auth-bg-orb-1"></div>
    <div class="auth-bg-orb auth-bg-orb-2"></div>

    <div class="auth-card" style="max-width:560px;margin:0 auto;">
        <div class="auth-logo">
            <div class="logo-icon float" style="width:56px;height:56px;font-size:1.5rem;margin:0 auto 0.75rem;border-radius:14px;display:flex;align-items:center;justify-content:center;background:var(--gradient-primary);box-shadow:var(--shadow-glow);">🍽️</div>
            <h1 class="auth-title" style="font-size:1.5rem;">Create Account</h1>
            <p class="auth-subtitle">Join the Frank Restaurant family</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger" data-dismiss="5000">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="@username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+1 555 0100" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>

            <!-- Preferred Table -->
            <?php if ($tables): ?>
            <div class="form-group">
                <label>Preferred Table (optional)</label>
                <select name="preferred_table_id" class="form-control">
                    <option value="">No preference</option>
                    <?php foreach ($tables as $t): ?>
                    <option value="<?= $t['table_id'] ?>"><?= htmlspecialchars("Table {$t['table_number']} — {$t['table_type']} ({$t['capacity']} seats, {$t['location']})") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Preferences -->
            <div style="background:var(--bg-tertiary);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.25rem;">
                <div style="font-weight:700;font-size:0.85rem;margin-bottom:0.75rem;color:var(--text-secondary);">🎯 Dining Preferences</div>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Seating</label>
                        <select name="preferences[seating]" class="form-control">
                            <option value="no_preference">No preference</option>
                            <option value="window">Window seat</option>
                            <option value="booth">Booth</option>
                            <option value="bar">Bar area</option>
                            <option value="outdoor">Outdoor/Patio</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Ambiance</label>
                        <select name="preferences[ambiance]" class="form-control">
                            <option value="no_preference">No preference</option>
                            <option value="quiet">Quiet</option>
                            <option value="lively">Lively</option>
                            <option value="romantic">Romantic</option>
                            <option value="family">Family-friendly</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 glow" style="justify-content:center;padding:0.85rem;">
                🎉 Create My Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>

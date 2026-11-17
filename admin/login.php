<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = $_POST['password'] ?? '';

    // Basic brute-force throttling per session
    $_SESSION['admin_attempts'] = $_SESSION['admin_attempts'] ?? 0;

    if (!verify_csrf()) {
        $error = 'Your session has expired. Please try again.';
    } elseif ($_SESSION['admin_attempts'] >= 5 && time() - ($_SESSION['admin_last_attempt'] ?? 0) < 300) {
        $error = 'Too many failed attempts. Please wait 5 minutes and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM admin WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            unset($_SESSION['admin_attempts'], $_SESSION['admin_last_attempt']);
            db()->prepare('UPDATE admin SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
            flash('success', 'Welcome back, ' . $admin['full_name'] . '.');
            redirect('admin/index.php');
        }
        $_SESSION['admin_attempts']++;
        $_SESSION['admin_last_attempt'] = time();
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | CarePoint</title>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@400..800&family=Space+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
</head>
<body class="grid-login-body">
<section class="grid-login">
    <?= str_repeat('<span></span>', 260) ?>

    <div class="grid-card">
        <div class="grid-card-inner">
            <a href="<?= url('index.php') ?>" class="logo logo-light">
                <span class="logo-mark"><i class="fa-solid fa-staff-snake"></i></span>
                <span class="logo-text">Care<strong>Point</strong></span>
            </a>
            <h1>Admin Sign In</h1>
            <p class="grid-sub"><i class="fa-solid fa-shield-halved"></i> Restricted area for clinic staff</p>

            <?= render_flashes() ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form method="post" action="<?= url('admin/login.php') ?>" novalidate class="js-validate grid-form" id="adminLoginForm">
                <?= csrf_field() ?>
                <div class="form-group grid-field">
                    <input type="text" id="username" name="username" value="<?= e($username) ?>" placeholder=" " required autocomplete="username" autofocus>
                    <label for="username">Username or email</label>
                </div>
                <div class="form-group grid-field">
                    <input type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
                    <label for="password">Password</label>
                    <button type="button" class="toggle-password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                </div>
                <div class="grid-links">
                    <a href="<?= url('index.php') ?>"><i class="fa-solid fa-arrow-left"></i> Back to website</a>
                    <a href="<?= url('contact.php') ?>">Need help?</a>
                </div>
                <button type="submit" class="grid-submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
            </form>

            <div class="quick-access quick-dark" aria-label="Quick demo access">
                <p class="quick-title"><i class="fa-solid fa-bolt"></i> One-click demo login (for the supervisor)</p>
                <div class="quick-btns">
                    <button type="button" class="quick-btn quick-admin" data-quick="admin" data-form="#adminLoginForm" data-user-field="username" data-user="admin" data-pass="Admin@123"><i class="fa-solid fa-user-shield"></i> Admin</button>
                    <a href="<?= url('login.php?quick=patient') ?>" class="quick-btn quick-patient"><i class="fa-solid fa-hospital-user"></i> Patient</a>
                </div>
                <p class="quick-creds">Admin: <strong>admin</strong> / <strong>Admin@123</strong><br>Patient: <strong>patient@carepoint.com</strong> / <strong>Patient@123</strong></p>
            </div>
        </div>
    </div>
</section>
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>

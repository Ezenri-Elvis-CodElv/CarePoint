<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$mode        = 'login';
$loginErrors = [];
$loginEmail  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginEmail = post('email');
    $password   = $_POST['password'] ?? '';

    if (!verify_csrf()) {
        $loginErrors['form'] = 'Your session has expired. Please try again.';
    } elseif (!filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
        $loginErrors['email'] = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $loginErrors['password'] = 'Please enter your password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$loginEmail]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            $target = $_SESSION['redirect_after_login'] ?? '';
            unset($_SESSION['redirect_after_login']);
            if ($target && str_starts_with($target, BASE_URL)) {
                header('Location: ' . $target);
                exit;
            }
            redirect('dashboard.php');
        }
        $loginErrors['form'] = 'Incorrect email address or password.';
    }
}

require __DIR__ . '/includes/auth_view.php';

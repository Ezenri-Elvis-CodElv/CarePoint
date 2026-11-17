<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$mode      = 'register';
$regErrors = [];
$regValues = ['full_name' => '', 'email' => (string)($_GET['email'] ?? ''), 'phone' => '', 'age' => '', 'gender' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($regValues as $k => $_) {
        $regValues[$k] = post($k);
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $v = $regValues;

    if (!verify_csrf()) {
        $regErrors['form'] = 'Your session has expired. Please try again.';
    }
    if (mb_strlen($v['full_name']) < 3 || !preg_match("/^[\p{L}\s.'-]+$/u", $v['full_name'])) {
        $regErrors['full_name'] = 'Please enter your full name (letters only, at least 3 characters).';
    }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $regErrors['email'] = 'Please enter a valid email address.';
    } else {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$v['email']]);
        if ($stmt->fetchColumn()) {
            $regErrors['email'] = 'An account with this email already exists. Please log in instead.';
        }
    }
    if (!valid_phone($v['phone'])) {
        $regErrors['phone'] = 'Please enter a valid phone number.';
    }
    if ($v['age'] !== '' && (!ctype_digit($v['age']) || (int)$v['age'] > 120)) {
        $regErrors['age'] = 'Please enter a valid age.';
    }
    if ($v['gender'] !== '' && !in_array($v['gender'], ['Male', 'Female', 'Other'], true)) {
        $regErrors['gender'] = 'Please select a valid gender.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $regErrors['password'] = 'Password must be at least 8 characters and include letters and numbers.';
    } elseif ($password !== $confirm) {
        $regErrors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$regErrors) {
        $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, age, gender, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $v['full_name'],
            strtolower($v['email']),
            $v['phone'],
            $v['age'] === '' ? null : (int)$v['age'],
            $v['gender'] === '' ? null : $v['gender'],
            password_hash($password, PASSWORD_DEFAULT),
        ]);
        login_user(['id' => db()->lastInsertId(), 'full_name' => $v['full_name'], 'email' => strtolower($v['email'])]);
        flash('success', 'Your account has been created. Welcome to CarePoint, ' . $v['full_name'] . '!');
        redirect('dashboard.php');
    }
}

require __DIR__ . '/includes/auth_view.php';

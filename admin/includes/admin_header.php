<?php
/**
 * Admin layout header. Expects $pageTitle and $adminPage.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pendingCount = (int)db()->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();
$unreadCount  = (int)db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
$menu = [
    'dashboard'    => ['Dashboard', 'admin/index.php', 'fa-gauge-high', 0],
    'appointments' => ['Appointments', 'admin/appointments.php', 'fa-calendar-check', $pendingCount],
    'doctors'      => ['Doctors', 'admin/doctors.php', 'fa-user-doctor', 0],
    'patients'     => ['Patients', 'admin/patients.php', 'fa-hospital-user', 0],
    'messages'     => ['Messages', 'admin/messages.php', 'fa-envelope', $unreadCount],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> | CarePoint Admin</title>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@400..800&family=Space+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">
<aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= url('admin/index.php') ?>" class="logo logo-light">
        <span class="logo-mark"><i class="fa-solid fa-staff-snake"></i></span>
        <span class="logo-text">Care<strong>Point</strong></span>
    </a>
    <span class="sidebar-label">Administration</span>
    <nav class="admin-menu">
        <?php foreach ($menu as $key => [$label, $href, $icon, $badge]): ?>
            <a href="<?= url($href) ?>" class="<?= ($adminPage ?? '') === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= $icon ?>"></i><span><?= e($label) ?></span>
                <?php if ($badge): ?><em class="menu-badge"><?= $badge ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <a href="<?= url('index.php') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>View website</span></a>
        <a href="<?= url('admin/logout.php') ?>"><i class="fa-solid fa-right-from-bracket"></i><span>Log out</span></a>
    </div>
</aside>
<div class="admin-overlay" id="adminOverlay"></div>

<div class="admin-main">
    <header class="admin-topbar">
        <button class="admin-toggle" id="adminToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
        <h1><?= e($pageTitle ?? 'Admin') ?></h1>
        <div class="admin-user">
            <span class="avatar-sm"><?= e(initials($_SESSION['admin_name'] ?? 'Admin')) ?></span>
            <span class="hide-sm"><?= e($_SESSION['admin_name'] ?? 'Administrator') ?></span>
        </div>
    </header>
    <div class="admin-content">
        <?= render_flashes() ?>

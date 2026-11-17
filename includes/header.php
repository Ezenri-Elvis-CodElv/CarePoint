<?php
/**
 * Public site header.
 * Expects: $pageTitle (string), $activePage (string)
 */
require_once __DIR__ . '/functions.php';
$pageTitle  = $pageTitle  ?? 'Clinic Appointment Booking';
$activePage = $activePage ?? '';
$navItems = [
    'home'     => ['Home', 'index.php'],
    'about'    => ['About Us', 'about.php'],
    'services' => ['Services', 'services.php'],
    'doctors'  => ['Our Doctors', 'doctors.php'],
    'contact'  => ['Contact', 'contact.php'],
    'track'    => ['Track Booking', 'track.php'],
];
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | CarePoint Clinic</title>
    <meta name="description" content="CarePoint Clinic – book appointments online with experienced doctors in general medicine, dental care, cardiology, paediatrics, dermatology, eye care and laboratory services.">
    <script>document.documentElement.classList.add('js');</script>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@400..800&family=Space+Mono:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/auth.css') ?>">
    <?php foreach (($pageStyles ?? []) as $__style): ?>
    <link rel="stylesheet" href="<?= asset('css/' . $__style) ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-info">
            <span><i class="fa-solid fa-phone"></i> <?= e(CLINIC_PHONE) ?></span>
            <span class="hide-sm"><i class="fa-solid fa-envelope"></i> <?= e(CLINIC_EMAIL) ?></span>
            <span class="hide-md"><i class="fa-regular fa-clock"></i> Mon–Fri 8:00 AM – 6:00 PM · Sat 9:00 AM – 2:00 PM</span>
        </div>
        <div class="topbar-emergency"><i class="fa-solid fa-truck-medical"></i> Emergency: <?= e(CLINIC_EMERGENCY) ?></div>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="container nav-wrap">
        <a href="<?= url('index.php') ?>" class="logo" aria-label="CarePoint home">
            <span class="logo-mark"><i class="fa-solid fa-staff-snake"></i></span>
            <span class="logo-text">Care<strong>Point</strong></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mainNav">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav" aria-label="Main navigation">
            <ul>
                <?php foreach ($navItems as $key => [$label, $href]): ?>
                    <li><a href="<?= url($href) ?>" class="<?= $activePage === $key ? 'active' : '' ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <a href="<?= url('dashboard.php') ?>" class="nav-user <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                        <span class="avatar-sm"><?= e(initials($user['full_name'])) ?></span>
                        <span>My Dashboard</span>
                    </a>
                    <a href="<?= url('logout.php') ?>" class="btn btn-ghost btn-sm" title="Log out"><i class="fa-solid fa-right-from-bracket"></i><span class="show-mobile-inline"> Log out</span></a>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="btn btn-ghost btn-sm <?= $activePage === 'login' ? 'active' : '' ?>">Log in</a>
                <?php endif; ?>
                <a href="<?= url('book.php') ?>" class="btn btn-primary btn-sm"><i class="fa-regular fa-calendar-check"></i> Book Now</a>
            </div>
        </nav>
    </div>
</header>
<div class="nav-overlay" id="navOverlay"></div>

<main id="main">
<?php $__flashes = render_flashes(); if ($__flashes): ?>
    <div class="container flash-container"><?= $__flashes ?></div>
<?php endif; ?>

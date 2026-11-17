<?php
require_once __DIR__ . '/includes/functions.php';

$ref = strtoupper(trim($_GET['ref'] ?? ''));
// Only show full details to the browser that just made the booking
if ($ref === '' || !in_array($ref, $_SESSION['recent_bookings'] ?? [], true)) {
    redirect('track.php');
}

$stmt = db()->prepare(
    'SELECT a.*, dp.name AS department_name, d.photo
     FROM appointments a
     JOIN departments dp ON dp.id = a.department_id
     LEFT JOIN doctors d ON d.id = a.doctor_id
     WHERE a.reference = ?'
);
$stmt->execute([$ref]);
$appt = $stmt->fetch();
if (!$appt) {
    redirect('track.php');
}

$pageTitle  = 'Booking Confirmed';
$activePage = 'book';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <div class="card confirm-card reveal">
            <div class="confirm-icon"><i class="fa-solid fa-check"></i></div>
            <h1>Appointment booked successfully!</h1>
            <p class="lead">Thank you, <?= e($appt['patient_name']) ?>. Your appointment request has been received and is awaiting confirmation from our team.</p>

            <div class="ref-box">
                <span>Your booking reference</span>
                <strong id="bookingRef"><?= e($appt['reference']) ?></strong>
                <button type="button" class="btn btn-ghost btn-sm" data-copy="<?= e($appt['reference']) ?>"><i class="fa-regular fa-copy"></i> Copy</button>
            </div>
            <p class="muted-sm">Please keep this reference safe – you'll need it (with your email address) to track or cancel your appointment.</p>

            <div class="summary-grid">
                <div class="summary-doctor">
                    <img src="<?= doctor_photo($appt['photo']) ?>" alt="<?= e($appt['doctor_name']) ?>">
                    <div><strong><?= e($appt['doctor_name']) ?></strong><span><?= e($appt['department_name']) ?></span></div>
                </div>
                <dl class="details-list">
                    <div><dt><i class="fa-regular fa-calendar"></i> Date</dt><dd><?= e(format_date($appt['appointment_date'], 'l, j F Y')) ?></dd></div>
                    <div><dt><i class="fa-regular fa-clock"></i> Time</dt><dd><?= e(format_time($appt['appointment_time'])) ?></dd></div>
                    <div><dt><i class="fa-solid fa-signal"></i> Status</dt><dd><?= status_badge($appt['status']) ?></dd></div>
                    <div><dt><i class="fa-solid fa-envelope"></i> Email</dt><dd><?= e($appt['email']) ?></dd></div>
                </dl>
            </div>

            <div class="confirm-actions">
                <?php if (is_logged_in()): ?>
                    <a href="<?= url('dashboard.php') ?>" class="btn btn-primary"><i class="fa-solid fa-gauge"></i> Go to my dashboard</a>
                <?php else: ?>
                    <a href="<?= url('register.php?email=' . urlencode($appt['email'])) ?>" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Create an account to manage bookings</a>
                <?php endif; ?>
                <a href="<?= url('track.php?ref=' . urlencode($appt['reference'])) ?>" class="btn btn-outline"><i class="fa-solid fa-magnifying-glass"></i> Track this booking</a>
                <button type="button" class="btn btn-ghost" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

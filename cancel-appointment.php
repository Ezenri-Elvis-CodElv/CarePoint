<?php
/**
 * POST handler – lets a patient cancel their own appointment.
 * Allowed when the patient is logged in and owns the booking, or has
 * verified the booking (reference + email) in this browser session.
 */
require_once __DIR__ . '/includes/functions.php';

$return = ($_POST['return'] ?? '') === 'dashboard' ? 'dashboard.php' : 'track.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($return);
}
if (!verify_csrf()) {
    flash('error', 'Your session has expired. Please try again.');
    redirect($return);
}

$ref  = strtoupper(post('reference'));
$stmt = db()->prepare('SELECT * FROM appointments WHERE reference = ?');
$stmt->execute([$ref]);
$appt = $stmt->fetch();

$owns = $appt && (
    (is_logged_in() && (int)$appt['user_id'] === (int)$_SESSION['user_id'])
    || !empty($_SESSION['tracked'][$ref])
    || in_array($ref, $_SESSION['recent_bookings'] ?? [], true)
);

if (!$owns) {
    flash('error', 'We could not verify that appointment. Please track it with your reference and email first.');
    redirect($return);
}
if ($return === 'track.php') {
    $return .= '?ref=' . urlencode($ref);
}
if (!can_cancel($appt)) {
    flash('error', 'This appointment can no longer be cancelled.');
    redirect($return);
}

update_appointment_status((int)$appt['id'], 'Cancelled', 'Cancelled by patient.');
flash('success', "Appointment {$ref} has been cancelled. The time slot has been released.");
redirect($return);

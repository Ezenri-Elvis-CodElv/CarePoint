<?php
/**
 * POST handler – change an appointment's status (approve, reject, complete...).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_helpers.php';
require_admin();

$return = admin_return_url($_POST['return'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    flash('error', 'Invalid request. Please try again.');
    redirect($return);
}

$id     = (int)($_POST['id'] ?? 0);
$status = post('status');
$note   = isset($_POST['admin_note']) ? mb_substr(post('admin_note'), 0, 255) : null;

$stmt = db()->prepare('SELECT reference, status FROM appointments WHERE id = ?');
$stmt->execute([$id]);
$appt = $stmt->fetch();
if (!$appt) {
    flash('error', 'Appointment not found.');
    redirect($return);
}

$result = update_appointment_status($id, $status, $note);
if ($result === true) {
    $verb = ['Confirmed' => 'approved', 'Rejected' => 'rejected', 'Cancelled' => 'cancelled', 'Completed' => 'marked as completed', 'Pending' => 'set back to pending'][$status];
    flash('success', "Appointment {$appt['reference']} has been {$verb}.");
} else {
    flash('error', $result);
}
redirect($return);

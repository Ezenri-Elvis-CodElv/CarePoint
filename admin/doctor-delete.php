<?php
/**
 * POST handler – delete a doctor.
 * Doctors with upcoming (pending/confirmed) appointments cannot be deleted;
 * historical appointments keep the doctor's name via appointments.doctor_name.
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    flash('error', 'Invalid request. Please try again.');
    redirect('admin/doctors.php');
}

$id = (int)($_POST['id'] ?? 0);
$doctor = get_doctor($id, false);
if (!$doctor) {
    flash('error', 'Doctor not found.');
    redirect('admin/doctors.php');
}

$stmt = db()->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status IN ('Pending','Confirmed') AND appointment_date >= CURDATE()");
$stmt->execute([$id]);
$upcoming = (int)$stmt->fetchColumn();
if ($upcoming > 0) {
    flash('error', "{$doctor['full_name']} has {$upcoming} upcoming appointment(s). Please reassign, cancel or complete them first, or set the doctor to Inactive instead.");
    redirect('admin/doctors.php');
}

db()->prepare('DELETE FROM doctors WHERE id = ?')->execute([$id]);

// Remove uploaded photo (keep bundled demo photos only if still referenced elsewhere)
if ($doctor['photo'] && str_starts_with($doctor['photo'], 'upload_') && is_file(DOCTOR_UPLOAD_DIR . $doctor['photo'])) {
    @unlink(DOCTOR_UPLOAD_DIR . $doctor['photo']);
}

flash('success', "{$doctor['full_name']} has been removed from the doctors list.");
redirect('admin/doctors.php');

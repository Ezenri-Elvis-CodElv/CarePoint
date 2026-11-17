<?php
/**
 * GET api/slots.php?doctor_id=1&date=2026-01-15
 * Returns the doctor's time slots for a date, flagging reserved/past ones.
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$doctorId = filter_input(INPUT_GET, 'doctor_id', FILTER_VALIDATE_INT);
$date     = (string)($_GET['date'] ?? '');

if (!$doctorId || !valid_date($date)) {
    respond(['success' => false, 'message' => 'Please choose a doctor and a valid date.'], 400);
}

$doctor = get_doctor($doctorId);
if (!$doctor) {
    respond(['success' => false, 'message' => 'The selected doctor is not available.'], 404);
}

$today = date('Y-m-d');
$max   = date('Y-m-d', strtotime('+' . BOOKING_WINDOW_DAYS . ' days'));
if ($date < $today) {
    respond(['success' => true, 'works' => false, 'slots' => [], 'message' => 'You cannot book an appointment in the past.']);
}
if ($date > $max) {
    respond(['success' => true, 'works' => false, 'slots' => [], 'message' => 'Appointments can be booked up to ' . BOOKING_WINDOW_DAYS . ' days in advance.']);
}
if (!doctor_works_on($doctor, $date)) {
    respond([
        'success' => true, 'works' => false, 'slots' => [],
        'message' => $doctor['full_name'] . ' does not consult on ' . date('l', strtotime($date)) . 's. Available days: ' . format_days($doctor) . '.',
    ]);
}

$slots = slot_availability($doctor, $date);
$free  = count(array_filter($slots, fn($s) => $s['available']));

respond([
    'success' => true,
    'works'   => true,
    'slots'   => $slots,
    'free'    => $free,
    'message' => $free ? "$free slot" . ($free === 1 ? '' : 's') . ' available on ' . format_date($date, 'l, j F Y') . '.'
                       : 'All slots are fully booked on this date. Please choose another day.',
]);

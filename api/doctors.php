<?php
/**
 * GET api/doctors.php?department_id=1
 * Returns active doctors belonging to a department.
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$deptId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
if (!$deptId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid department is required.']);
    exit;
}

$stmt = db()->prepare(
    "SELECT id, full_name, specialisation, photo, available_days, start_time, end_time, consultation_fee, experience_years
     FROM doctors WHERE department_id = ? AND status = 'Active' ORDER BY full_name"
);
$stmt->execute([$deptId]);

$doctors = array_map(function (array $d) {
    return [
        'id'             => (int)$d['id'],
        'name'           => $d['full_name'],
        'specialisation' => $d['specialisation'],
        'photo'          => doctor_photo($d['photo']),
        'days'           => doctor_days($d),
        'days_label'     => format_days($d),
        'hours'          => format_hours($d),
        'fee'            => number_format((float)$d['consultation_fee'], 2),
        'experience'     => (int)$d['experience_years'],
    ];
}, $stmt->fetchAll());

echo json_encode(['success' => true, 'doctors' => $doctors]);

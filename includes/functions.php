<?php
/**
 * Shared helper functions.
 */
require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------
// Output & URLs
// ---------------------------------------------------------------------
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

// ---------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flashes(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $icons = ['success' => 'fa-circle-check', 'error' => 'fa-circle-exclamation', 'info' => 'fa-circle-info', 'warning' => 'fa-triangle-exclamation'];
    $html = '<div class="flash-stack">';
    foreach ($_SESSION['flash'] as $f) {
        $type = isset($icons[$f['type']]) ? $f['type'] : 'info';
        $html .= '<div class="alert alert-' . $type . '" role="alert"><i class="fa-solid ' . $icons[$type] . '"></i><span>'
            . e($f['message']) . '</span><button type="button" class="alert-close" aria-label="Dismiss">&times;</button></div>';
    }
    $html .= '</div>';
    unset($_SESSION['flash']);
    return $html;
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals(csrf_token(), $token);
}

// ---------------------------------------------------------------------
// Form helpers
// ---------------------------------------------------------------------
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function old(string $key, $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="field-error">' . e($errors[$key]) . '</small>' : '';
}

function valid_phone(string $phone): bool
{
    return (bool)preg_match('/^\+?[0-9\s\-().]{7,20}$/', $phone);
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ---------------------------------------------------------------------
// Authentication – patients
// ---------------------------------------------------------------------
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (is_logged_in()) {
            $stmt = db()->prepare('SELECT id, full_name, email, phone, age, gender, created_at FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function require_login(): void
{
    if (!is_logged_in() || !current_user()) {
        flash('info', 'Please log in to access your dashboard.');
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    // Link any guest bookings made with the same email to this account
    $stmt = db()->prepare('UPDATE appointments SET user_id = ? WHERE user_id IS NULL AND email = ?');
    $stmt->execute([$user['id'], $user['email']]);
}

// ---------------------------------------------------------------------
// Authentication – administrators
// ---------------------------------------------------------------------
function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('error', 'Please sign in as an administrator to continue.');
        redirect('admin/login.php');
    }
}

// ---------------------------------------------------------------------
// Departments & doctors
// ---------------------------------------------------------------------
function get_departments(): array
{
    return db()->query('SELECT * FROM departments ORDER BY id')->fetchAll();
}

function get_doctor(int $id, bool $activeOnly = true): ?array
{
    $sql = 'SELECT d.*, dp.name AS department_name, dp.slug AS department_slug
            FROM doctors d JOIN departments dp ON dp.id = d.department_id WHERE d.id = ?';
    if ($activeOnly) {
        $sql .= " AND d.status = 'Active'";
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function doctor_photo(?string $photo): string
{
    if ($photo && is_file(DOCTOR_UPLOAD_DIR . $photo)) {
        return asset('images/doctors/' . rawurlencode($photo));
    }
    return asset('images/doctor-placeholder.svg');
}

const WEEK_DAYS = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];

function doctor_days(array $doctor): array
{
    return array_values(array_filter(array_map('trim', explode(',', $doctor['available_days']))));
}

function format_days(array $doctor): string
{
    $days = doctor_days($doctor);
    return $days ? implode(', ', $days) : 'Not scheduled';
}

function format_hours(array $doctor): string
{
    return date('g:i A', strtotime($doctor['start_time'])) . ' – ' . date('g:i A', strtotime($doctor['end_time']));
}

function doctor_works_on(array $doctor, string $date): bool
{
    return in_array(date('D', strtotime($date)), doctor_days($doctor), true);
}

/** All slot start times (H:i) for a doctor's working day. */
function generate_slots(array $doctor): array
{
    $slots = [];
    $step  = max(10, (int)$doctor['slot_duration']) * 60;
    $start = strtotime('2000-01-01 ' . $doctor['start_time']);
    $end   = strtotime('2000-01-01 ' . $doctor['end_time']);
    for ($t = $start; $t + $step <= $end; $t += $step) {
        $slots[] = date('H:i', $t);
    }
    return $slots;
}

/** Times (H:i) already reserved for a doctor on a date. */
function booked_slots(int $doctorId, string $date, ?int $excludeAppointmentId = null): array
{
    $sql = "SELECT TIME_FORMAT(appointment_time, '%H:%i') AS t FROM appointments
            WHERE doctor_id = ? AND appointment_date = ? AND slot_lock = 1";
    $params = [$doctorId, $date];
    if ($excludeAppointmentId) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeAppointmentId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/** Slot list with availability flags for the booking UI. */
function slot_availability(array $doctor, string $date): array
{
    $booked = booked_slots((int)$doctor['id'], $date);
    $now    = time();
    $result = [];
    foreach (generate_slots($doctor) as $time) {
        $status = 'available';
        if (in_array($time, $booked, true)) {
            $status = 'booked';
        } elseif (strtotime("$date $time") <= $now + 30 * 60) {
            $status = 'past';
        }
        $result[] = [
            'time'      => $time,
            'label'     => date('g:i A', strtotime("2000-01-01 $time")),
            'available' => $status === 'available',
            'status'    => $status,
        ];
    }
    return $result;
}

// ---------------------------------------------------------------------
// Appointments
// ---------------------------------------------------------------------
function generate_reference(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $stmt = db()->prepare('SELECT 1 FROM appointments WHERE reference = ?');
    do {
        $ref = 'CP-';
        for ($i = 0; $i < 6; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt->execute([$ref]);
    } while ($stmt->fetchColumn());
    return $ref;
}

const APPOINTMENT_STATUSES = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'];

function status_badge(string $status): string
{
    $icons = [
        'Pending' => 'fa-hourglass-half', 'Confirmed' => 'fa-circle-check', 'Completed' => 'fa-flag-checkered',
        'Cancelled' => 'fa-ban', 'Rejected' => 'fa-circle-xmark',
    ];
    $icon = $icons[$status] ?? 'fa-circle';
    return '<span class="badge badge-' . strtolower(e($status)) . '"><i class="fa-solid ' . $icon . '"></i> ' . e($status) . '</span>';
}

/** Whether a patient may still cancel an appointment. */
function can_cancel(array $appt): bool
{
    return in_array($appt['status'], ['Pending', 'Confirmed'], true)
        && strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) > time();
}

/**
 * Change an appointment's status, keeping the slot lock consistent.
 * Returns true on success or an error message string.
 */
function update_appointment_status(int $id, string $status, ?string $note = null)
{
    if (!in_array($status, APPOINTMENT_STATUSES, true)) {
        return 'Invalid status selected.';
    }
    $lock = in_array($status, ['Cancelled', 'Rejected'], true) ? null : 1;
    try {
        if ($note === null) {
            $stmt = db()->prepare('UPDATE appointments SET status = ?, slot_lock = ? WHERE id = ?');
            $stmt->execute([$status, $lock, $id]);
        } else {
            $stmt = db()->prepare('UPDATE appointments SET status = ?, slot_lock = ?, admin_note = ? WHERE id = ?');
            $stmt->execute([$status, $lock, $note === '' ? null : $note, $id]);
        }
        return true;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return 'This time slot has since been booked by another patient, so the appointment cannot be re-activated.';
        }
        throw $e;
    }
}

function format_date(string $date, string $format = 'D, j M Y'): string
{
    return date($format, strtotime($date));
}

function format_time(string $time): string
{
    return date('g:i A', strtotime('2000-01-01 ' . $time));
}

// ---------------------------------------------------------------------
// Patient profile
// ---------------------------------------------------------------------
const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

/** Human-facing patient number, e.g. CPP-00042. */
function patient_code(int $id): string
{
    return 'CPP-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
}

/** Full patient row without the password hash. Works before and after the profile-fields migration. */
function get_patient_profile(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    unset($row['password_hash']);
    return $row;
}

function age_from_dob(?string $dob): ?int
{
    if (!$dob || !valid_date($dob)) {
        return null;
    }
    return (new DateTime($dob))->diff(new DateTime('today'))->y;
}

function initials(string $name): string
{
    $name = preg_replace('/^(Dr\.?\s+)/i', '', $name);
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out;
}

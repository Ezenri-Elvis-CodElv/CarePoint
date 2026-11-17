<?php
/**
 * CarePoint – global configuration.
 * Adjust the database credentials below if your MySQL setup differs
 * from the XAMPP defaults (user "root", empty password).
 */

// ---------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------
define('DB_HOST', getenv('CP_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('CP_DB_PORT') ?: '3306');
define('DB_NAME', getenv('CP_DB_NAME') ?: 'carepoint_db');
define('DB_USER', getenv('CP_DB_USER') ?: 'root');
define('DB_PASS', getenv('CP_DB_PASS') !== false ? getenv('CP_DB_PASS') : '');

// ---------------------------------------------------------------------
// Clinic details (used across the site)
// ---------------------------------------------------------------------
define('SITE_NAME', 'CarePoint');
define('CLINIC_NAME', 'CarePoint Clinic');
define('CLINIC_ADDRESS', '128 Harbour Road, Westbridge, WB 10024');
define('CLINIC_PHONE', '+1 (555) 240-7788');
define('CLINIC_EMERGENCY', '+1 (555) 240-9111');
define('CLINIC_EMAIL', 'info@carepointclinic.com');

// How far ahead patients may book (days)
define('BOOKING_WINDOW_DAYS', 60);

// Timezone used for appointment dates/times
date_default_timezone_set('UTC');

// ---------------------------------------------------------------------
// Errors – show everything during development
// ---------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------------
// Base URL detection – works whether the project lives at
// http://localhost/CarePoint/ or in any other sub-folder.
// ---------------------------------------------------------------------
$__appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$__docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$__base = '/';
if ($__docRoot !== '' && stripos($__appRoot, $__docRoot) === 0) {
    $__base = '/' . trim(substr($__appRoot, strlen($__docRoot)), '/') . '/';
    $__base = str_replace('//', '/', $__base);
}
define('BASE_URL', $__base);
define('ROOT_PATH', $__appRoot . '/');
define('DOCTOR_UPLOAD_DIR', ROOT_PATH . 'assets/images/doctors/');
unset($__appRoot, $__docRoot, $__base);

// ---------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name('CAREPOINTSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

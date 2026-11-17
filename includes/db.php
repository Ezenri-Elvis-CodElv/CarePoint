<?php
/**
 * PDO database connection (singleton).
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
                exit;
            }
            echo <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Database error – CarePoint</title>
<style>body{font-family:system-ui,sans-serif;background:#f1f6fc;color:#0b2545;display:grid;place-items:center;min-height:100vh;margin:0;padding:16px}
.box{background:#fff;max-width:560px;padding:32px;border-radius:14px;box-shadow:0 10px 30px rgba(11,37,69,.1)}h1{margin-top:0;font-size:1.4rem}
code{background:#eef4fb;padding:2px 6px;border-radius:4px}</style></head><body><div class="box">
<h1>Unable to connect to the database</h1>
<p>Please make sure MySQL is running in XAMPP and that the <code>carepoint_db</code> database has been imported from <code>database/carepoint_db.sql</code>.</p>
<p>Check the credentials in <code>includes/config.php</code>.</p>
<p style="color:#b91c1c;font-size:.9rem">Details: {$msg}</p></div></body></html>
HTML;
            exit;
        }
    }
    return $pdo;
}

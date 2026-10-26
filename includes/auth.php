<?php

declare(strict_types=1);
require_once __DIR__ . '/functions.php';
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('UTC');
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('CUYOTECH_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => config()['base_url'] . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' https: data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    set_exception_handler(function (Throwable $error): void {
        error_log('SSIS failure: ' . get_class($error) . ' ' . $error->getCode());
        if ($error instanceof DomainException) {
            abort_page(400, $error->getMessage());
        }
        abort_page(503, 'The service is temporarily unavailable. Check the database configuration or contact your administrator.');
    });
}
function current_user(): ?array
{
    if (empty($_SESSION['account_id'])) {
        return null;
    }
    $user = one('SELECT a.account_id, a.email, a.account_role, a.is_active,
        COALESCE(s.first_name,f.first_name) AS first_name,COALESCE(s.last_name,f.last_name) AS last_name,
        s.student_id, s.department_id AS student_department_id, TRIM(CONCAT(s.first_name,CHAR(32),s.last_name)) AS student_name,
        f.staff_id, TRIM(CONCAT(f.first_name,CHAR(32),f.last_name)) AS staff_name, f.staff_role, f.access_scope, f.department_id
        FROM accounts a LEFT JOIN students s ON s.account_id=a.account_id
        LEFT JOIN staff_users f ON f.account_id=a.account_id WHERE a.account_id=?', [$_SESSION['account_id']]);
    if (!$user || !$user['is_active']) {
        $_SESSION = [];
        return null;
    }
    $user['role'] = $user['account_role'] === 'STUDENT' ? 'STUDENT' : $user['staff_role'];
    $user['full_name'] = $user['student_name'] ?: $user['staff_name'];
    if (
        !in_array($user['role'], ['STUDENT', 'ADMIN', 'REGISTRAR', 'CASHIER', 'DEPARTMENT'], true)
        || ($user['role'] === 'STUDENT' && !$user['student_id']) || ($user['role'] !== 'STUDENT' && !$user['staff_id'])
    ) {
        $_SESSION = [];
        return null;
    }
    return $user;
}
function require_role(string ...$allowed): array
{
    $user = current_user();
    if (!$user) {
        redirect('auth/login.php');
    }
    if (!in_array($user['role'], $allowed, true)) {
        abort_page(403, 'Your account is not authorized for this portal.');
    }
    if ($user["role"] === "ADMIN" && $user["access_scope"] !== "UNIVERSITY") {
        abort_page(403, "Administrator access requires university-wide scope.");
    }
    if ($user["role"] === "DEPARTMENT" && ($user["access_scope"] !== "DEPARTMENT" || !$user["department_id"])) {
        abort_page(403, "A valid department assignment is required.");
    }
    return $user;
}
function portal(array $user): string
{
    return strtolower($user['role']);
}
function scope_sql(array $user, string $alias = 's'): array
{
    if ($user['role'] === 'STUDENT') {
        return ["$alias.student_id = ?", [$user['student_id']]];
    }
    if ($user['access_scope'] === 'DEPARTMENT') {
        return ["$alias.department_id = ?", [$user['department_id']]];
    }
    return ['1=1', []];
}
function require_student_scope(array $user, int $studentId, bool $lock = false): array
{
    $student = one('SELECT * FROM students WHERE student_id=?' . ($lock ? ' FOR UPDATE' : ''), [$studentId]);
    if (
        !$student || ($user['role'] === 'STUDENT' && (int) $user['student_id'] !== $studentId)
        || ($user['role'] !== 'STUDENT' && $user['access_scope'] === 'DEPARTMENT'
            && (int) $user['department_id'] !== (int) $student['department_id'])
    ) {
        throw new DomainException('This student record is unavailable to your account.');
    }
    return $student;
}
// All workflow writes lock the student first, then child rows. Clearance writers
// use the same student lock, so verification cannot race clearance revocation.
function owned_request(array $user, int $requestId, bool $lock = false): array
{
    $request = one('SELECT * FROM document_requests WHERE request_id=?', [$requestId]);
    if (!$request) {
        throw new DomainException('Request not found.');
    }
    require_student_scope($user, (int) $request['student_id'], $lock);
    return $lock ? one('SELECT * FROM document_requests WHERE request_id=? FOR UPDATE', [$requestId]) : $request;
}

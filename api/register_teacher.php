<?php
/**
 * POST /api/register_teacher.php
 * Body (JSON): name, email, phone, tsc?, role, grade?, subject?
 * Only an HOI / Deputy HOI can register a teacher.
 * Returns { ok:true, teacherId, name } or { ok:false, error, message }.
 */
mysqli_report(MYSQLI_REPORT_OFF);
require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

function respondOk(array $data = [], int $status = 200): void {
    http_response_code($status);
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}
function respondError(string $message, int $status = 400, string $error = 'error'): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $error, 'message' => $message]);
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'fatal', 'message' => 'Server error: ' . $e['message']]);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Method not allowed', 405, 'method_not_allowed');
}
if (!$conn) {
    respondError('DB connection failed', 500, 'db_error');
}

/* ---- Auth (same helper as the other endpoints) ---- */
$session = require_auth();

// ASSUMPTION: $session['role'] holds the logged-in user's role.
// If your auth_check.php names it differently, change it here.
$myRole = strtolower(trim((string) ($session['role'] ?? '')));
$managerRoles = ['hoi', 'dhoi', 'head of instituion', 'head of institution'];
if (!in_array($myRole, $managerRoles, true)) {
    respondError('Not authorized to register teachers', 403, 'forbidden');
}

/* ---- Body ---- */
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$name    = trim((string) ($input['name'] ?? ''));
$email   = trim((string) ($input['email'] ?? ''));
$phone   = trim((string) ($input['phone'] ?? ''));
$tsc     = trim((string) ($input['tsc'] ?? ''));
$role    = trim((string) ($input['role'] ?? ''));
$grade   = trim((string) ($input['grade'] ?? ''));
$subject = trim((string) ($input['subject'] ?? ''));

/* ---- Normalise ---- */
// "0712 345 678", "0712-345-678", "+254712345678" -> "0712345678"
$phone = preg_replace('/[\s\-()]/', '', $phone);
if (preg_match('/^\+?254(\d{9})$/', $phone, $pm)) {
    $phone = '0' . $pm[1];
}
// "grade5" / "Grade 5" -> 5 ; empty -> 0 (classTeacher is an INT column)
$gradeNum = (int) preg_replace('/\D/', '', $grade);
// Missing TSC is stored as 0, matching your existing rows
$tscValue = $tsc !== '' ? $tsc : '0';

/* ---- Validation ---- */
$allowedRoles = ['hoi', 'Dhoi', 'Senior', 'teacher'];

if ($name === '' || $email === '' || $phone === '' || $role === '') {
    respondError('name, email, phone and role are required', 422, 'validation_error');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondError('Invalid email address', 422, 'validation_error');
}
if (!preg_match('/^0\d{9}$/', $phone)) {
    respondError('Phone must be a 10-digit number starting with 0', 422, 'validation_error');
}
if (!in_array($role, $allowedRoles, true)) {
    respondError('Invalid role', 422, 'validation_error');
}
if ($tsc !== '' && !ctype_digit($tsc)) {
    respondError('TSC number must be numeric', 422, 'validation_error');
}
if ($gradeNum < 0 || $gradeNum > 9) {
    respondError('Grade must be between 1 and 9', 422, 'validation_error');
}

/* ---- Duplicate email ---- */
$check = $conn->prepare("SELECT id FROM Teachers WHERE email = ? LIMIT 1");
if (!$check) {
    error_log('register_teacher.php check prepare failed: ' . $conn->error);
    respondError('Server error', 500, 'server_error');
}
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    respondError('A teacher with this email already exists', 409, 'duplicate_email');
}
$check->close();

/* ---- Insert ---- */
$stmt = $conn->prepare(
    "INSERT INTO Teachers (name, email, phoneNo, tscNo, role, classTeacher, subject)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    error_log('register_teacher.php prepare failed: ' . $conn->error);
    respondError('Server error', 500, 'server_error');
}

// types: s s s s s i s
$stmt->bind_param('sssssis', $name, $email, $phone, $tscValue, $role, $gradeNum, $subject);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    respondOk(['teacherId' => $newId, 'name' => $name], 201);
}

error_log('register_teacher.php insert failed: ' . $stmt->error);
$detail = $stmt->error;
$stmt->close();
// "detail" is handy while debugging; remove it once everything works.
http_response_code(500);
echo json_encode([
    'ok' => false, 'error' => 'insert_failed',
    'message' => 'Insert failed', 'detail' => $detail,
]);

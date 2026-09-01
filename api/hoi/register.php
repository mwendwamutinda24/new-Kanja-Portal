<?php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../conn.php';   // $conn (mysqli)
require __DIR__ . '/../auth.php';   // must expose require_auth(): returns authenticated user or exits with 401 JSON

mysqli_report(MYSQLI_REPORT_OFF); // handle query failures ourselves instead of throwing

function respond(int $httpCode, array $payload): void {
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

// ── Auth ──
// Adjust this call to match whatever your other /results/*.php endpoints use
// to validate the Authorization: Bearer <token> header sent by apiRequest().
require_auth();

// ── Read JSON body (apiRequest() sends JSON.stringify'd body) ──
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$name    = trim((string)($body['name'] ?? ''));
$email   = trim((string)($body['email'] ?? ''));
$phone   = trim((string)($body['phone'] ?? ''));
$tsc     = trim((string)($body['tsc'] ?? ''));
$role    = trim((string)($body['role'] ?? ''));
$grade   = trim((string)($body['grade'] ?? ''));   // "classTeacher" column — optional grade string e.g. "grade4"
$subject = trim((string)($body['subject'] ?? ''));

// ── Validation ──
$errors = [];
if ($name === '') {
    $errors[] = 'Full name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if ($phone === '') {
    $errors[] = 'Phone number is required.';
}
$validRoles = ['hoi', 'Dhoi', 'Senior', 'teacher'];
if ($role === '' || !in_array($role, $validRoles, true)) {
    $errors[] = 'A valid role is required.';
}

if ($errors) {
    respond(422, ['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
}

// Normalize optional fields to null rather than empty strings
$tscParam     = $tsc !== '' ? $tsc : null;
$gradeParam   = $grade !== '' ? $grade : null;
$subjectParam = $subject !== '' ? $subject : null;

// ── Duplicate email guard ──
$dupStmt = $conn->prepare('SELECT id FROM Teachers WHERE email = ? LIMIT 1');
$dupStmt->bind_param('s', $email);
$dupStmt->execute();
$dupStmt->store_result();
if ($dupStmt->num_rows > 0) {
    $dupStmt->close();
    respond(409, ['success' => false, 'message' => 'A teacher with this email is already registered.']);
}
$dupStmt->close();

// ── Insert ──
$stmt = $conn->prepare(
    'INSERT INTO Teachers (name, email, phoneNo, tscNo, role, classTeacher, subject)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    error_log('hoi/register.php prepare failed: ' . $conn->error);
    respond(500, ['success' => false, 'message' => 'Could not register teacher.']);
}

$stmt->bind_param(
    'sssssss',
    $name, $email, $phone, $tscParam, $role, $gradeParam, $subjectParam
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    respond(200, [
        'success' => true,
        'message' => 'Teacher registered successfully.',
        'id'      => $newId,
    ]);
} else {
    error_log('hoi/register.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    respond(500, ['success' => false, 'message' => 'Could not register teacher.']);
}

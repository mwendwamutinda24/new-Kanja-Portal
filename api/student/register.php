<?php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../conn.php';   // $conn (mysqli)
require __DIR__ . '/../auth.php';   // must expose require_auth(): returns authenticated user or exits with 401 JSON

mysqli_report(MYSQLI_REPORT_OFF);

function respond(int $httpCode, array $payload): void {
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

require_auth();

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$upi        = trim((string)($body['upi'] ?? ''));
$assessment = trim((string)($body['assessment'] ?? ''));
$firstName  = trim((string)($body['firstName'] ?? ''));
$middleName = trim((string)($body['middleName'] ?? ''));
$surname    = trim((string)($body['surname'] ?? ''));
$birthNo    = trim((string)($body['birthNo'] ?? ''));
$dobRaw     = trim((string)($body['dob'] ?? ''));
$gradeRaw   = trim((string)($body['grade'] ?? ''));

// ── Validation — only firstName is required, matches the RN form's note
//    "Only First Name is required — everything else can be added later." ──
if ($firstName === '') {
    respond(422, ['success' => false, 'message' => 'First name is required.']);
}

// Basic dd/mm/yyyy sanity check for DOB if provided — adjust if your
// SelectField/date picker sends a different format.
$dob = null;
if ($dobRaw !== '') {
    $d = DateTime::createFromFormat('d/m/Y', $dobRaw);
    if ($d && $d->format('d/m/Y') === $dobRaw) {
        $dob = $d->format('Y-m-d'); // store as proper DATE
    } else {
        respond(422, ['success' => false, 'message' => 'Date of birth must be in dd/mm/yyyy format.']);
    }
}

$grade = null;
if ($gradeRaw !== '') {
    if (!ctype_digit($gradeRaw) || (int)$gradeRaw < 1 || (int)$gradeRaw > 9) {
        respond(422, ['success' => false, 'message' => 'Grade must be between 1 and 9.']);
    }
    $grade = (int)$gradeRaw;
}

$upiParam        = $upi !== '' ? $upi : null;
$assessmentParam = $assessment !== '' ? $assessment : null;
$middleNameParam = $middleName !== '' ? $middleName : null;
$surnameParam    = $surname !== '' ? $surname : null;
$birthNoParam    = $birthNo !== '' ? $birthNo : null;

$stmt = $conn->prepare(
    "INSERT INTO Student (UPI, Assesment, firstName, middleName, surname, DOB, Grade, birthNo, role)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')"
);
if (!$stmt) {
    error_log('students/register.php prepare failed: ' . $conn->error);
    respond(500, ['success' => false, 'message' => 'Could not register student.']);
}

$stmt->bind_param(
    'ssssssis',
    $upiParam, $assessmentParam, $firstName, $middleNameParam,
    $surnameParam, $dob, $grade, $birthNoParam
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    respond(200, [
        'success' => true,
        'message' => 'Student registered successfully.',
        'id'      => $newId,
    ]);
} else {
    error_log('students/register.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    respond(500, ['success' => false, 'message' => 'Could not register student.']);
}

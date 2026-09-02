<?php
/**
 * POST /api/register_teacher.php
 *
 * Mobile (Kanja Portal) endpoint for the "Register Teacher" screen.
 * Only an HOI / Deputy HOI can register a teacher.
 * Auth'd via Bearer token, returns the standard { ok, ... } / { ok:false, error, message }
 * envelope used by every api/*.php endpoint (see api/helpers/response.php).
 */

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

include 'conn.php';
include 'auth.php'; // <-- adjust to wherever your existing verifyToken()/Bearer-auth helper lives

// Swap for api/helpers/response.php's respondOk()/respondError() if that's where
// the shared envelope actually lives — same note as in register_student.php.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Method not allowed', 405, 'method_not_allowed');
}

/* ---- Auth ---- */
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
    respondError('Missing bearer token', 401, 'no_token');
}
$token = $m[1];

// TODO: replace with your real helper — assumes a `tokens` table
// (token, user_id, role, expires_at) shared with subjects.php / students.php.
$user = verifyToken($conn, $token);
if (!$user) {
    respondError('Invalid or expired token', 401, 'invalid_token');
}
// Only Head of Institution / Deputy can register teachers.
if (!in_array($user['role'], ['hoi', 'Dhoi'], true)) {
    respondError('Not authorized to register teachers', 403, 'forbidden');
}

/* ---- Body: apiRequest() always sends JSON when auth=true ---- */
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST; // fallback for form-urlencoded callers
}

$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$phone   = trim($input['phone'] ?? '');
$tsc     = trim($input['tsc'] ?? '');
$role    = trim($input['role'] ?? '');
$grade   = trim($input['grade'] ?? '');   // "Class Teacher For" — optional
$subject = trim($input['subject'] ?? ''); // optional

/* ---- Validation ----
 * Matches the ✦-marked fields on the mobile form: name, email, phone, role.
 */
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

/* ---- Duplicate email check ---- */
$check = $conn->prepare("SELECT id FROM Teachers WHERE email = ? LIMIT 1");
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    respondError('A teacher with this email already exists', 409, 'duplicate_email');
}
$check->close();

/* ---- Insert (prepared statement — no raw concatenation) ---- */
$stmt = $conn->prepare(
    "INSERT INTO Teachers (name, email, phoneNo, tscNo, role, classTeacher, subject)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    error_log('register_teacher.php prepare failed: ' . $conn->error);
    respondError('Server error', 500, 'server_error');
}

$tscValue   = $tsc !== '' ? $tsc : null;
$gradeValue = $grade !== '' ? $grade : null;
$subjValue  = $subject !== '' ? $subject : null;

$stmt->bind_param(
    'sssssss',
    $name, $email, $phone, $tscValue, $role, $gradeValue, $subjValue
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    respondOk(['teacherId' => $newId, 'name' => $name], 201);
} else {
    error_log('register_teacher.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    respondError('Insert failed', 500, 'insert_failed');
}

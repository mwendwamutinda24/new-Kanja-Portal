<?php
/**
 * POST /api/register_student.php
 *
 * Mobile (Kanja Portal) endpoint for the "Admit New Learner" screen.
 * Accepts JSON body, auth'd via Bearer token, returns the standard
 * { ok: true, ... } / { ok: false, error, message } envelope used by
 * every api/*.php endpoint (see api/helpers/response.php).
 *
 * Matches the same rule as bulk_register.php: only firstName is required,
 * everything else can be filled in later on the Students page.
 */

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF); // don't let a failed query throw; we handle errors ourselves

include 'conn.php';
include 'auth.php'; // <-- adjust to wherever your existing verifyToken()/Bearer-auth helper lives

// If you already have api/helpers/response.php exporting respondOk()/respondError(),
// `include` that instead of these two local functions — keeping every endpoint
// on the exact same envelope shape matters more than where the functions live.
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

// TODO: replace with your real helper — this assumes a `tokens` table
// (token, user_id, role, expires_at) shared with subjects.php / students.php.
$user = verifyToken($conn, $token);
if (!$user) {
    respondError('Invalid or expired token', 401, 'invalid_token');
}
if (!in_array($user['role'], ['hoi', 'Dhoi', 'teacher'], true)) {
    respondError('Not authorized to register students', 403, 'forbidden');
}

/* ---- Body: apiRequest() always sends JSON when auth=true ---- */
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST; // fallback for form-urlencoded callers
}

$upi        = trim($input['UPI'] ?? '');
$assessment = trim($input['Assesment'] ?? '');
$firstName  = trim($input['firstName'] ?? '');
$middleName = trim($input['middleName'] ?? '');
$surname    = trim($input['surname'] ?? '');
$birthNo    = trim($input['birthNo'] ?? '');
$dobRaw     = trim($input['DOB'] ?? '');
$gradeRaw   = trim($input['Grade'] ?? '');

/* ---- Validation: only firstName is required ---- */
if ($firstName === '') {
    respondError('firstName is required', 422, 'validation_error');
}

$dob = null;
if ($dobRaw !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $dobRaw) ?: DateTime::createFromFormat('d/m/Y', $dobRaw);
    if (!$d) {
        respondError('DOB must be YYYY-MM-DD or DD/MM/YYYY', 422, 'validation_error');
    }
    $dob = $d->format('Y-m-d');
}

$grade = null;
if ($gradeRaw !== '') {
    if (!ctype_digit($gradeRaw) || (int)$gradeRaw < 1 || (int)$gradeRaw > 9) {
        respondError('Grade must be between 1 and 9', 422, 'validation_error');
    }
    $grade = (int)$gradeRaw;
}

/* ---- Insert ----
 * Column order: UPI, Assesment, firstName, middleName, surname, DOB, Grade, birthNo
 * Type string:    s     s          s           s          s      s    i      s
 */
$stmt = $conn->prepare(
    "INSERT INTO Student (UPI, Assesment, firstName, middleName, surname, DOB, Grade, birthNo, role)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')"
);
if (!$stmt) {
    error_log('register_student.php prepare failed: ' . $conn->error);
    respondError('Server error', 500, 'server_error');
}

$stmt->bind_param(
    'ssssssis',
    $upi, $assessment, $firstName, $middleName, $surname, $dob, $grade, $birthNo
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    respondOk(['studentId' => $newId, 'firstName' => $firstName], 201);
} else {
    error_log('register_student.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    respondError('Insert failed', 500, 'insert_failed');
}

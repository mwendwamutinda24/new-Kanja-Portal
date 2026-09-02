<?php
/**
 * POST /api/register_student.php
 *
 * Mobile (Kanja Portal) endpoint for the "Admit New Learner" screen.
 * Accepts JSON body OR form-urlencoded, auth'd via Bearer token, returns JSON.
 *
 * Matches the same rule as bulk_register.php: only firstName is required,
 * everything else can be filled in later on the Students page.
 */

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF); // don't let a failed query throw; we handle errors ourselves

include 'conn.php';
include 'auth.php'; // <-- adjust to wherever your existing verifyToken()/Bearer-auth helper lives

function respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'error' => 'Method not allowed']);
}

/* ---- Auth ---- */
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
    respond(401, ['success' => false, 'error' => 'Missing bearer token']);
}
$token = $m[1];

// TODO: replace with your real helper — this assumes a `tokens` table
// (token, user_id, role, expires_at) shared with subjects.php / students.php.
$user = verifyToken($conn, $token);
if (!$user) {
    respond(401, ['success' => false, 'error' => 'Invalid or expired token']);
}
if (!in_array($user['role'], ['hoi', 'Dhoi', 'teacher'], true)) {
    respond(403, ['success' => false, 'error' => 'Not authorized to register students']);
}

/* ---- Accept JSON or form-urlencoded body ---- */
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
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
    respond(422, ['success' => false, 'error' => 'firstName is required']);
}

$dob = null;
if ($dobRaw !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $dobRaw) ?: DateTime::createFromFormat('d/m/Y', $dobRaw);
    if (!$d) {
        respond(422, ['success' => false, 'error' => 'DOB must be YYYY-MM-DD or DD/MM/YYYY']);
    }
    $dob = $d->format('Y-m-d');
}

$grade = null;
if ($gradeRaw !== '') {
    if (!ctype_digit($gradeRaw) || (int)$gradeRaw < 1 || (int)$gradeRaw > 9) {
        respond(422, ['success' => false, 'error' => 'Grade must be between 1 and 9']);
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
    respond(500, ['success' => false, 'error' => 'Server error']);
}

$stmt->bind_param(
    'ssssssis',
    $upi, $assessment, $firstName, $middleName, $surname, $dob, $grade, $birthNo
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    respond(201, [
        'success'   => true,
        'studentId' => $newId,
        'firstName' => $firstName,
    ]);
} else {
    error_log('register_student.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    respond(500, ['success' => false, 'error' => 'Insert failed']);
}

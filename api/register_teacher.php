<?php
/**
 * POST /api/register_teacher.php
 *
 * Mobile (Kanja Portal) endpoint for the "Register Teacher" screen.
 * Only an HOI / Deputy HOI can register a teacher.
 * Accepts JSON body OR form-urlencoded, auth'd via Bearer token, returns JSON.
 */

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

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

// TODO: replace with your real helper — assumes a `tokens` table
// (token, user_id, role, expires_at) shared with subjects.php / students.php.
$user = verifyToken($conn, $token);
if (!$user) {
    respond(401, ['success' => false, 'error' => 'Invalid or expired token']);
}
// Only Head of Institution / Deputy can register teachers.
if (!in_array($user['role'], ['hoi', 'Dhoi'], true)) {
    respond(403, ['success' => false, 'error' => 'Not authorized to register teachers']);
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
    respond(422, ['success' => false, 'error' => 'name, email, phone and role are required']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['success' => false, 'error' => 'Invalid email address']);
}
if (!preg_match('/^0\d{9}$/', $phone)) {
    respond(422, ['success' => false, 'error' => 'Phone must be a 10-digit number starting with 0']);
}
if (!in_array($role, $allowedRoles, true)) {
    respond(422, ['success' => false, 'error' => 'Invalid role']);
}
if ($tsc !== '' && !ctype_digit($tsc)) {
    respond(422, ['success' => false, 'error' => 'TSC number must be numeric']);
}

/* ---- Duplicate email check ---- */
$check = $conn->prepare("SELECT id FROM Teachers WHERE email = ? LIMIT 1");
$check->bind_param('s', $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    respond(409, ['success' => false, 'error' => 'A teacher with this email already exists']);
}
$check->close();

/* ---- Insert (prepared statement — no raw concatenation) ---- */
$stmt = $conn->prepare(
    "INSERT INTO Teachers (name, email, phoneNo, tscNo, role, classTeacher, subject)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    error_log('register_teacher.php prepare failed: ' . $conn->error);
    respond(500, ['success' => false, 'error' => 'Server error']);
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
    respond(201, [
        'success'   => true,
        'teacherId' => $newId,
        'name'      => $name,
    ]);
} else {
    error_log('register_teacher.php insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    respond(500, ['success' => false, 'error' => 'Insert failed']);
}

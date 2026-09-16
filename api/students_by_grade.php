<?php
// students_by_grade.php
// GET /students_by_grade.php?grade=<1-9>
// Auth: Authorization: Bearer <token>
// Returns: { "success": true, "students": [ { assessmentNo, upi, fullName, dob, birthNo }, ... ] }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/auth_check.php';

// --- Auth: expect "Authorization: Bearer <token>" ---
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing or malformed Authorization header']);
    exit();
}

$token = $matches[1];

// Reuse whatever token-validation function the rest of the API already
// uses (e.g. validateToken($token) -> user row or false). Adjust the
// function name below to match your existing auth_check.php.
$user = validateToken($token);
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit();
}

// --- Validate grade param ---
$grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';

if ($grade === '' || !ctype_digit($grade) || (int)$grade < 1 || (int)$grade > 9) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing grade parameter']);
    exit();
}

$gradeEscaped = mysqli_real_escape_string($conn, $grade);

// Adjust table/column names below to match your actual students table schema.
$sql = "SELECT
            assessment_no AS assessmentNo,
            upi,
            full_name AS fullName,
            dob,
            birth_cert_no AS birthNo
        FROM students
        WHERE grade = '$gradeEscaped'
        ORDER BY full_name ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed']);
    exit();
}

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

http_response_code(200);
echo json_encode(['success' => true, 'students' => $students]);

mysqli_close($conn);

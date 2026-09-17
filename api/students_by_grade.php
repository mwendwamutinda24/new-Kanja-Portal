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

// require_auth() already validates the Bearer token against api_sessions
// and sends 401/exits on failure — no need to parse the header or call a
// validateToken() function ourselves.
$session = require_auth();

// --- Validate grade param ---
$grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';

if ($grade === '' || !ctype_digit($grade) || (int)$grade < 1 || (int)$grade > 9) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing grade parameter']);
    exit();
}

$gradeEscaped = mysqli_real_escape_string($conn, $grade);



$sql = "SELECT
            Assesment AS assessmentNo,
            UPI AS upi,
            CONCAT_WS(' ', firstName, middleName, surname) AS fullName,
            DOB AS dob,
            birthNo AS birthNo
        FROM Student
        WHERE Grade = '$gradeEscaped'
        ORDER BY firstName ASC";

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

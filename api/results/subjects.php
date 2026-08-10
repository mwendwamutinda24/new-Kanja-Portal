<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// FIX: this used to require conn.php as '../conn.php', which from
// api/results/ only resolves up to api/ - but conn.php actually lives at
// the project root, two levels up from here. auth_check.php's own
// require of conn.php was fine (its __DIR__ is api/, one level from root),
// but this file's own direct require needed the extra level.
require __DIR__ . '/../../conn.php';
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';
require __DIR__ . '/_input.php';

mysqli_report(MYSQLI_REPORT_OFF);

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$session = require_auth();
if (!in_array($session['role'], ['teacher', 'hoi'], true)) {
    respond(['success' => false, 'message' => 'Not authorized.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'POST required'], 405);
}

$input    = skp_body();
$grade    = trim((string) ($input['grade'] ?? ''));
$term     = trim((string) ($input['term'] ?? ''));
$examType = trim((string) ($input['examType'] ?? ''));
$year     = trim((string) ($input['year'] ?? ''));

if ($grade === '') {
    respond(['success' => false, 'message' => 'grade is required'], 400);
}

$gradeSafe = mysqli_real_escape_string($conn, $grade);
$subjects  = skp_subjects_for_grade($grade);
$codes     = array_column($subjects, 'code');

$query = "SELECT id, Assesment, firstName, surname
          FROM Student
          WHERE Grade = '$gradeSafe'
          ORDER BY firstName, surname";
$result = mysqli_query($conn, $query);

if ($result === false) {
    respond(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)], 500);
}

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[$row['id']] = [
        'id'        => (string) $row['id'],
        'assesment' => $row['Assesment'],
        'firstName' => $row['firstName'],
        'surname'   => $row['surname'],
        'marks'     => array_fill_keys($codes, null),
        'hasRecord' => false,
    ];
}

// Prefill existing marks for this term/examType/year, if all three were given.
// ASSUMPTION: exam2 is keyed on studentId/grade/term/examType/year with one
// column per subject code. See _save.php for the same assumption.
$marksWarning = null;
if ($term !== '' && $examType !== '' && $year !== '' && count($students) > 0) {
    $termSafe     = mysqli_real_escape_string($conn, $term);
    $examTypeSafe = mysqli_real_escape_string($conn, $examType);
    $yearSafe     = mysqli_real_escape_string($conn, $year);

    $colList = implode(', ', array_map(fn($c) => "`$c`", $codes));
    $mq = "SELECT studentId, $colList
           FROM exam2
           WHERE grade = '$gradeSafe' AND term = '$termSafe'
             AND examType = '$examTypeSafe' AND year = '$yearSafe'";
    $mres = mysqli_query($conn, $mq);

    if ($mres === false) {
        $marksWarning = 'Could not load existing marks: ' . mysqli_error($conn);
    } else {
        while ($mrow = mysqli_fetch_assoc($mres)) {
            $sid = $mrow['studentId'];
            if (isset($students[$sid])) {
                foreach ($codes as $c) {
                    $students[$sid]['marks'][$c] = $mrow[$c] !== null ? (int) $mrow[$c] : null;
                }
                $students[$sid]['hasRecord'] = true;
            }
        }
    }
}

$response = [
    'success'  => true,
    'grade'    => $grade,
    'subjects' => $subjects,
    'students' => array_values($students),
];
if ($marksWarning) {
    $response['warning'] = $marksWarning;
}

respond($response);

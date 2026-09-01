<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../../conn.php';
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';
require __DIR__ . '/_input.php';

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

$input = skp_body();

$grade    = trim((string) ($input['grade'] ?? ''));
$term     = trim((string) ($input['term'] ?? ''));
$examType = trim((string) ($input['examType'] ?? ''));
$year     = trim((string) ($input['year'] ?? ''));

if ($grade === '') {
    respond(['success' => false, 'message' => 'grade is required'], 400);
}

$subjects = skp_subjects_for_grade($grade);
$subjectCodes = array_column($subjects, 'code');

$gradeSafe = mysqli_real_escape_string($conn, $grade);

$res = mysqli_query($conn, "
    SELECT id, Assesment, firstName, surname
    FROM Student
    WHERE Grade = '$gradeSafe'
    ORDER BY firstName, surname
");

if ($res === false) {
    respond(['success' => false, 'message' => 'Database error loading students: ' . mysqli_error($conn)], 500);
}

$students = [];
$idsInOrder = [];
while ($row = mysqli_fetch_assoc($res)) {
    $students[$row['id']] = [
        'id'        => $row['id'],
        'assesment' => $row['Assesment'],
        'firstName' => $row['firstName'],
        'surname'   => $row['surname'],
        'marks'     => array_fill_keys($subjectCodes, null),
    ];
    $idsInOrder[] = $row['id'];
}

$warning = null;

// Only look up existing marks once term/examType/year are all present —
// otherwise every student's marks legitimately stay null (no exam picked
// yet). Matches upload.php's requirement that all four fields be set to
// save, so pre-fill only kicks in once that same combination is complete.
if ($term !== '' && $examType !== '' && $year !== '' && count($idsInOrder) > 0) {
    $termSafe     = mysqli_real_escape_string($conn, $term);
    $examTypeSafe = mysqli_real_escape_string($conn, $examType);
    $yearSafe     = mysqli_real_escape_string($conn, $year);

    // CONFIRM: column names below (studentId, examTerm, examType,
    // examYear) — adjust to match the real exam2 schema once shared.
    // Given the documented "inconsistent term string formats" quirk in
    // exam2, examTerm may need to also match alternate formats (e.g.
    // 'Term 1' vs '1').
    $subjCols = implode(', ', array_map(fn($c) => "`$c`", $subjectCodes));
    $idsSafe = implode(',', array_map(fn($id) => "'" . mysqli_real_escape_string($conn, $id) . "'", $idsInOrder));

    $markRes = mysqli_query($conn, "
        SELECT studentId, $subjCols
        FROM exam2
        WHERE studentId IN ($idsSafe)
          AND examTerm = '$termSafe'
          AND examType = '$examTypeSafe'
          AND examYear = '$yearSafe'
    ");

    if ($markRes === false) {
        // Don't fail the whole roster load over this — surface it as a
        // warning so the app can still show the (blank) roster.
        $warning = 'Could not load existing marks: ' . mysqli_error($conn);
    } else {
        while ($mrow = mysqli_fetch_assoc($markRes)) {
            $sid = $mrow['studentId'];
            if (!isset($students[$sid])) continue;
            foreach ($subjectCodes as $code) {
                $val = $mrow[$code] ?? null;
                $students[$sid]['marks'][$code] = ($val === null || $val === '') ? null : $val;
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
if ($warning) $response['warning'] = $warning;

respond($response);

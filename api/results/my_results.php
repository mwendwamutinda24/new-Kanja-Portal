<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../../conn.php';   // must come before auth_check.php (see subjects.php fix)
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';
require __DIR__ . '/_input.php';

mysqli_report(MYSQLI_REPORT_OFF);

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'POST required'], 405);
}

$session = require_auth();
$role    = $session['role'] ?? '';

if (!in_array($role, ['student', 'teacher', 'Head of Instituion'], true)) {
    respond(['success' => false, 'message' => 'Not authorized.'], 403);
}

$input    = skp_body();
$term     = trim((string) ($input['term'] ?? ''));      // expects "1"/"2"/"3"
$examType = trim((string) ($input['examType'] ?? ''));  // expects opener/midterm/endterm
$year     = trim((string) ($input['year'] ?? ''));

if ($term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'term, examType and year are all required'], 400);
}

$termSafe     = mysqli_real_escape_string($conn, $term);
$examTypeSafe = mysqli_real_escape_string($conn, $examType);
$yearSafe     = mysqli_real_escape_string($conn, $year);

// WORKAROUND: exam2.term has been written in at least two formats — bare
// digits ("1") from the mobile upload endpoints, and "Term 1" style
// strings from the older web upload flow (see the note in results.php).
// Match either, so results show up regardless of which flow saved them.
$termCandidates = [$termSafe];
if (ctype_digit($term)) {
    $termCandidates[] = 'Term ' . $termSafe;
}
$termInClause = "'" . implode("','", $termCandidates) . "'";

/**
 * Build one student's { student, subjects } block from an exam2 row
 * (or an empty one if $row is null), using this file's own banding
 * helper (skp_band_info_for_grade, in _config.php) — NOT the
 * differently-named bandInfoForGrade() that lives locally inside
 * results.php (the web app); the two are unrelated functions.
 *
 * $studentRow uses the Student table's own column names (surname, not
 * lastName) — normalized here so callers always get {id, name, ...}.
 */
function skp_build_student_block(array $studentRow, ?array $row, array $subjectMap, int $gradeInt): array {
    $subjectCount = count($subjectMap);
    $subjects     = [];
    $total        = 0;

    foreach ($subjectMap as $s) {
        $score  = $row !== null ? (int) ($row[$s['code']] ?? 0) : 0;
        $total += $score;
        $band   = skp_band_info_for_grade($score, $gradeInt);
        $subjects[] = [
            'subject' => $s['label'],
            'score'   => $score,
            'grade'   => $band['code'],
            'remarks' => $band['label'],
        ];
    }

    $lastName = $studentRow['surname'] ?? ($studentRow['lastName'] ?? '');

    return [
        'student' => [
            'id'         => (string) $studentRow['id'],
            'name'       => trim($studentRow['firstName'] . ' ' . $lastName),
            'average'    => $row !== null ? round($total / $subjectCount, 1) : null,
            'hasResults' => $row !== null,
        ],
        'subjects' => $subjects,
    ];
}

if ($role === 'student') {
    // Students only ever see their own record.
    if (empty($session['user_id'])) {
        respond(['success' => false, 'message' => 'Not authorized.'], 403);
    }
    $studentId     = (string) $session['user_id'];
    $studentIdSafe = mysqli_real_escape_string($conn, $studentId);

    $sRes = mysqli_query($conn, "SELECT id, firstName, surname, Grade FROM Student WHERE id = '$studentIdSafe' LIMIT 1");
    if ($sRes === false || mysqli_num_rows($sRes) === 0) {
        respond(['success' => false, 'message' => 'Student record not found'], 404);
    }
    $studentRow = mysqli_fetch_assoc($sRes);
    $grade      = (string) $studentRow['Grade'];
    $gradeInt   = (int) $grade;
    $gradeSafe  = mysqli_real_escape_string($conn, $grade);

    $subjectMap = skp_subjects_for_grade($grade);
    if (count($subjectMap) === 0) {
        respond(['success' => false, 'message' => 'No subjects configured for this grade'], 500);
    }

    // exam2 uses student_id / exam_type (snake_case), not studentId / examType.
    $q = "SELECT * FROM exam2
          WHERE student_id = '$studentIdSafe' AND grade = '$gradeSafe'
            AND term IN ($termInClause) AND exam_type = '$examTypeSafe' AND year = '$yearSafe'
          LIMIT 1";
    $res = mysqli_query($conn, $q);
    $row = ($res !== false && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;

    $block = skp_build_student_block($studentRow, $row, $subjectMap, $gradeInt);
    respond([
        'success'  => true,
        'student'  => $block['student'],
        'subjects' => $block['subjects'],
    ]);
}

// Staff (teacher / Head of Instituion): return every learner in the
// selected grade, each with their own subjects/average — this is the
// "whole class" transcript view, not a single-student lookup.
$grade = trim((string) ($input['grade'] ?? ''));
if ($grade === '') {
    respond(['success' => false, 'message' => 'grade is required for staff lookups.'], 400);
}
$gradeInt  = (int) $grade;
$gradeSafe = mysqli_real_escape_string($conn, $grade);

$subjectMap = skp_subjects_for_grade($grade);
if (count($subjectMap) === 0) {
    respond(['success' => false, 'message' => 'No subjects configured for this grade'], 500);
}

// Student.lastName doesn't exist — the column is `surname`.
$stuRes = mysqli_query($conn, "SELECT id, firstName, surname, Grade FROM Student WHERE Grade = '$gradeSafe' ORDER BY firstName, surname");
if ($stuRes === false) {
    respond(['success' => false, 'message' => 'Failed to load students for this grade', 'debug' => mysqli_error($conn)], 500);
}

$students = [];
while ($studentRow = mysqli_fetch_assoc($stuRes)) {
    $studentIdSafe = mysqli_real_escape_string($conn, (string) $studentRow['id']);

    // exam2 uses student_id / exam_type (snake_case), not studentId / examType.
    $q = "SELECT * FROM exam2
          WHERE student_id = '$studentIdSafe' AND grade = '$gradeSafe'
            AND term IN ($termInClause) AND exam_type = '$examTypeSafe' AND year = '$yearSafe'
          LIMIT 1";
    $res = mysqli_query($conn, $q);
    $row = ($res !== false && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;

    $students[] = skp_build_student_block($studentRow, $row, $subjectMap, $gradeInt);
}

respond([
    'success'  => true,
    'grade'    => $grade,
    'students' => $students,
]);

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

$session = require_auth();
$role    = $session['role'] ?? '';

// ── Role-based studentId resolution ──
// Students can only ever see their own record — studentId comes from their
// own session, never from client input (so a student can't pass someone
// else's id and view a classmate's marks). Teachers/HOI look up a specific
// learner by id, since they need to pull results for any student in their
// classes.
$input = skp_body();

if ($role === 'student') {
    // ASSUMPTION: a student's own session carries their Student.id as
    // $session['studentId'] (parallel to how teacher/hoi sessions carry
    // $session['role']). If your real session shape uses a different key,
    // this is the one line to change.
    if (empty($session['studentId'])) {
        respond(['success' => false, 'message' => 'Not authorized.'], 403);
    }
    $studentId = (string) $session['studentId'];
} elseif (in_array($role, ['teacher', 'hoi'], true)) {
    $studentId = trim((string) ($input['studentId'] ?? ''));
    if ($studentId === '') {
        respond(['success' => false, 'message' => 'studentId is required'], 400);
    }
} else {
    respond(['success' => false, 'message' => 'Not authorized.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'POST required'], 405);
}

$term     = trim((string) ($input['term'] ?? ''));      // expects "1"/"2"/"3"
$examType = trim((string) ($input['examType'] ?? ''));  // expects opener/midterm/endterm
$year     = trim((string) ($input['year'] ?? ''));

if ($term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'term, examType and year are all required'], 400);
}

// Pull grade + name from the Student row itself rather than trusting a
// client-supplied grade — this also doubles as existence validation for
// the teacher/hoi lookup path (a bogus studentId just 404s here).
$studentIdSafe = mysqli_real_escape_string($conn, $studentId);
$sRes = mysqli_query($conn, "SELECT id, firstName, lastName, Grade FROM Student WHERE id = '$studentIdSafe' LIMIT 1");
if ($sRes === false || mysqli_num_rows($sRes) === 0) {
    respond(['success' => false, 'message' => 'Student record not found'], 404);
}
$studentRow = mysqli_fetch_assoc($sRes);
$grade      = (string) $studentRow['Grade'];
$gradeInt   = (int) $grade;

$subjectMap = skp_subjects_for_grade($grade); // [{code,label}, ...]
if (count($subjectMap) === 0) {
    respond(['success' => false, 'message' => 'No subjects configured for this grade'], 500);
}
$subjectCount = count($subjectMap);

$termSafe     = mysqli_real_escape_string($conn, $term);
$examTypeSafe = mysqli_real_escape_string($conn, $examType);
$yearSafe     = mysqli_real_escape_string($conn, $year);
$gradeSafe    = mysqli_real_escape_string($conn, $grade);

// WORKAROUND: exam2.term has been written in at least two formats — bare
// digits ("1") from the mobile upload endpoints, and "Term 1" style
// strings from the older web upload flow (see the note in results.php).
// Match either, so results show up regardless of which flow saved them.
$termCandidates = [$termSafe];
if (ctype_digit($term)) {
    $termCandidates[] = 'Term ' . $termSafe;
}
$termInClause = "'" . implode("','", $termCandidates) . "'";

$q = "SELECT * FROM exam2
      WHERE studentId = '$studentIdSafe' AND grade = '$gradeSafe'
        AND term IN ($termInClause) AND examType = '$examTypeSafe' AND year = '$yearSafe'
      LIMIT 1";
$res = mysqli_query($conn, $q);
if ($res === false || mysqli_num_rows($res) === 0) {
    respond(['success' => true, 'student' => null, 'subjects' => []]);
}
$row = mysqli_fetch_assoc($res);

$subjects = [];
$total    = 0;
foreach ($subjectMap as $s) {
    $score  = (int) ($row[$s['code']] ?? 0);
    $total += $score;
    $band   = bandInfoForGrade($score, $gradeInt); // same helper results.php uses for its award pills
    $subjects[] = [
        'subject' => $s['label'],
        'score'   => $score,
        'grade'   => $band['code'],   // e.g. "E.E" / "EE1"
        'remarks' => $band['label'],  // e.g. "Exceeding Expectation"
    ];
}
$average = round($total / $subjectCount, 1);

respond([
    'success' => true,
    'student' => [
        'name'    => trim($studentRow['firstName'] . ' ' . $studentRow['lastName']),
        'average' => $average,
        'grade'   => $grade, // returned so the teacher/hoi UI can show it back
    ],
    'subjects' => $subjects,
]);

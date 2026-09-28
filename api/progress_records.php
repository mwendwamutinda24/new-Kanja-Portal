<?php
/**
 * GET /api/progress_records.php
 *
 * Returns:
 *   - records:   one row per learner / exam / subject mark
 *   - learners:  every learner, for the Learner dropdown
 *   - years:     every distinct exam year, for the Year dropdown
 *   - examTypes: every distinct exam type found in exam2 (opener, midterm,
 *                endterm AND any others that have been added), as
 *                [{ value, label }], for the Exam dropdown
 *
 * Query params (all optional): grade, term, exam_type, year, subject, student_id
 *   exam_type is the `value` from examTypes (lowercase, no spaces/hyphens),
 *   e.g. opener | midterm | endterm | <any other added exam>
 *
 * Schema:
 *  - `Student`: id, UPI, Assesment, firstName, middleName, surname,
 *    parentName, parentPhone, birthNo, DOB, Grade, password, role
 *  - `exam2`: id, student_id, Assesment, firstName, lastName, math, eng,
 *    kisw, sst, scie, ca, agri, re, pretec, grade, term, exam_type, year
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'GET required'], 405);
}

$session = require_auth();
if (!in_array($session['role'], ['hoi', 'Dhoi', 'teacher'], true)) {
    respond(['error' => 'Not authorized.'], 403);
}

$subjectCodes = ['math', 'eng', 'kisw', 'sst', 'scie', 'ca', 'agri', 're', 'pretec'];

/* Exam type helpers: "Mid-Term", "midterm", "Mid Term" all become "midterm" */
$knownExamLabels = ['opener' => 'Opener', 'midterm' => 'Mid-Term', 'endterm' => 'End Term'];

function exam_key($s) {
    return strtolower(str_replace(['-', ' '], '', trim((string) $s)));
}
function exam_label($raw) {
    global $knownExamLabels;
    $k = exam_key($raw);
    return $knownExamLabels[$k] ?? trim((string) $raw);
}

/* ---- Filters ---- */
$gradeIn   = preg_replace('/\D/', '', (string) ($_GET['grade'] ?? ''));
$termIn    = preg_replace('/\D/', '', (string) ($_GET['term'] ?? ''));
$yearIn    = (int) ($_GET['year'] ?? 0);
$studentIn = (int) ($_GET['student_id'] ?? 0);
$examIn    = exam_key($_GET['exam_type'] ?? '');

$subjectIn = trim((string) ($_GET['subject'] ?? ''));
if (!in_array($subjectIn, $subjectCodes, true)) $subjectIn = '';

$conditions = ['1=1'];

if ($gradeIn !== '') {
    $g = (int) $gradeIn;
    $conditions[] = "(e.grade = '$g' OR e.grade = 'Grade $g')";
}
if ($termIn !== '') {
    $t = (int) $termIn;
    $conditions[] = "(e.term = '$t' OR e.term = 'Term $t')";
}
if ($examIn !== '') {
    $conditions[] = "LOWER(REPLACE(REPLACE(e.exam_type, '-', ''), ' ', '')) = '"
                  . mysqli_real_escape_string($conn, $examIn) . "'";
}
if ($yearIn > 0)    $conditions[] = "e.year = $yearIn";
if ($studentIn > 0) $conditions[] = "s.id = $studentIn";

$subjectsToUse = $subjectIn !== '' ? [$subjectIn] : $subjectCodes;
$subjectSelect = implode(', ', array_map(fn($c) => "e.`$c`", $subjectsToUse));

$sql = "SELECT s.id AS student_id, s.Assesment, s.firstName, s.surname,
               e.id AS exam_id, e.grade, e.term, e.exam_type, e.year,
               $subjectSelect
        FROM exam2 e
        JOIN Student s ON s.id = e.student_id
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY s.firstName, s.surname, e.year DESC, e.term DESC, e.id";

$res = mysqli_query($conn, $sql);
if ($res === false) {
    respond(['error' => 'Database error loading marks: ' . mysqli_error($conn)], 500);
}

/* ---- Unpivot: one record per subject mark ---- */
$records = [];
while ($row = mysqli_fetch_assoc($res)) {
    $gradeOut = preg_replace('/\D/', '', (string) $row['grade']);
    $termOut  = preg_replace('/\D/', '', (string) $row['term']);

    foreach ($subjectsToUse as $code) {
        $val = $row[$code] ?? null;
        if ($val === null || $val === '') continue;

        $records[] = [
            'studentId' => (int) $row['student_id'],
            'admNo'     => $row['Assesment'],
            'firstName' => $row['firstName'],
            'surname'   => $row['surname'],
            'grade'     => $gradeOut !== '' ? $gradeOut : $row['grade'],
            'term'      => $termOut !== '' ? $termOut : $row['term'],
            'examType'  => exam_key($row['exam_type']),
            'examLabel' => exam_label($row['exam_type']),
            'year'      => $row['year'],
            'subject'   => $code,
            'score'     => (float) $val,
        ];
    }
}

/* ---- Learner list ---- */
$learners = [];
$lr = mysqli_query($conn, "SELECT id, firstName, surname, Grade FROM Student ORDER BY firstName, surname");
if ($lr) {
    while ($r = mysqli_fetch_assoc($lr)) {
        $learners[] = [
            'id'    => (int) $r['id'],
            'name'  => trim($r['firstName'] . ' ' . $r['surname']),
            'grade' => $r['Grade'],
        ];
    }
}

/* ---- Distinct years ---- */
$years = [];
$yr = mysqli_query($conn, "SELECT DISTINCT year FROM exam2 WHERE year IS NOT NULL AND year <> '' ORDER BY year DESC");
if ($yr) {
    while ($r = mysqli_fetch_assoc($yr)) {
        $years[] = (int) $r['year'];
    }
}

/* ---- Distinct exam types (all of them, including any added later) ---- */
$examMap = [];
$er = mysqli_query($conn, "SELECT DISTINCT exam_type FROM exam2 WHERE exam_type IS NOT NULL AND exam_type <> ''");
if ($er) {
    while ($r = mysqli_fetch_assoc($er)) {
        $k = exam_key($r['exam_type']);
        if ($k === '' || isset($examMap[$k])) continue;
        $examMap[$k] = ['value' => $k, 'label' => exam_label($r['exam_type'])];
    }
}
// Opener, Mid-Term, End Term first, then any others alphabetically
$order = array_keys($knownExamLabels);
uasort($examMap, function ($a, $b) use ($order) {
    $ia = array_search($a['value'], $order, true);
    $ib = array_search($b['value'], $order, true);
    $ia = $ia === false ? 99 : $ia;
    $ib = $ib === false ? 99 : $ib;
    return $ia === $ib ? strcasecmp($a['label'], $b['label']) : $ia <=> $ib;
});
$examTypes = array_values($examMap);

respond([
    'records'   => $records,
    'learners'  => $learners,
    'years'     => $years,
    'examTypes' => $examTypes,
]);

<?php
/**
 * GET /api/progress_records.php
 *
 * Backs the Progress Records screen. Returns:
 *   - records:  one row per learner / exam / subject mark
 *   - learners: every learner, for the Learner dropdown
 *   - years:    every distinct exam year, for the Year dropdown
 *
 * Query params (all optional): grade, term, exam_type, year, subject, student_id
 *   grade      e.g. "6"            (also matches "Grade 6" if that's what's stored)
 *   term       e.g. "2"            (also matches "Term 2")
 *   exam_type  opener | midterm | endterm  (matches "Mid-Term", "End Term", etc.)
 *   subject    math | eng | kisw | sst | scie | ca | agri | re | pretec
 *
 * Schema (confirmed against the real tables):
 *  - `Student`: id, UPI, Assesment, firstName, middleName, surname,
 *    parentName, parentPhone, birthNo, DOB, Grade, password, role
 *  - `exam2`: id, student_id, Assesment, firstName, lastName, math, eng,
 *    kisw, sst, scie, ca, agri, re, pretec, grade, term, exam_type, year
 *    — subject marks are one column per subject code on the same row.
 *
 * Stats (learners, mean, passing, at risk) are calculated in the app from
 * the returned records, so they always match the rows on screen.
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

/* Subject columns on exam2, in display order */
$subjectCodes = ['math', 'eng', 'kisw', 'sst', 'scie', 'ca', 'agri', 're', 'pretec'];
$examCodes    = ['opener', 'midterm', 'endterm'];

/* ---- Filters ---- */
$gradeIn   = preg_replace('/\D/', '', (string) ($_GET['grade'] ?? ''));
$termIn    = preg_replace('/\D/', '', (string) ($_GET['term'] ?? ''));
$yearIn    = (int) ($_GET['year'] ?? 0);
$studentIn = (int) ($_GET['student_id'] ?? 0);

$examIn = strtolower(trim((string) ($_GET['exam_type'] ?? '')));
if (!in_array($examIn, $examCodes, true)) $examIn = '';

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
    // Normalise "Mid-Term" / "End Term" / "endterm" etc. before comparing
    $conditions[] = "LOWER(REPLACE(REPLACE(e.exam_type, '-', ''), ' ', '')) = '"
                  . mysqli_real_escape_string($conn, $examIn) . "'";
}
if ($yearIn > 0) {
    $conditions[] = "e.year = $yearIn";
}
if ($studentIn > 0) {
    $conditions[] = "s.id = $studentIn";
}

/* Only pull the subject column(s) we need */
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
    // Store "Grade 6" / "Term 2" as plain numbers; the app adds the words.
    $gradeOut = preg_replace('/\D/', '', (string) $row['grade']);
    $termOut  = preg_replace('/\D/', '', (string) $row['term']);
    // Normalise exam type to opener | midterm | endterm when it matches
    $examNorm = strtolower(str_replace(['-', ' '], '', (string) $row['exam_type']));
    $examOut  = in_array($examNorm, $examCodes, true) ? $examNorm : (string) $row['exam_type'];

    foreach ($subjectsToUse as $code) {
        $val = $row[$code] ?? null;
        if ($val === null || $val === '') continue; // no mark entered

        $records[] = [
            'studentId' => (int) $row['student_id'],
            'admNo'     => $row['Assesment'],
            'firstName' => $row['firstName'],
            'surname'   => $row['surname'],
            'grade'     => $gradeOut !== '' ? $gradeOut : $row['grade'],
            'term'      => $termOut !== '' ? $termOut : $row['term'],
            'examType'  => $examOut,
            'year'      => $row['year'],
            'subject'   => $code,
            'score'     => (float) $val,
        ];
    }
}

/* ---- Learner list for the Learner dropdown ---- */
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

/* ---- Distinct exam years for the Year dropdown ---- */
$years = [];
$yr = mysqli_query($conn, "SELECT DISTINCT year FROM exam2 WHERE year IS NOT NULL AND year <> '' ORDER BY year DESC");
if ($yr) {
    while ($r = mysqli_fetch_assoc($yr)) {
        $years[] = (int) $r['year'];
    }
}

respond([
    'records'  => $records,
    'learners' => $learners,
    'years'    => $years,
]);

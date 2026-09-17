<?php
/**
 * GET /api/progress_records.php
 *
 * Backs the Progress Records screen: returns summary stats plus a
 * per-student list of averaged marks, optionally filtered by grade, term,
 * exam type, and subject.
 *
 * Schema (confirmed against the real tables):
 *  - `Student`: id, UPI, Assesment, firstName, middleName, surname,
 *    parentName, parentPhone, birthNo, DOB, Grade, password, role
 *  - `exam2`: id, student_id, Assesment, firstName, lastName, math, eng,
 *    kisw, sst, scie, ca, agri, re, pretec, grade, term, exam_type, year
 *    — subject marks are one column per subject code on the same row.
 *  - "Passing" = average across a student's matched subject marks >= 50.
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

$grade    = trim((string) ($_GET['grade'] ?? ''));
$term     = trim((string) ($_GET['term'] ?? ''));
$examType = trim((string) ($_GET['examType'] ?? ''));
$subject  = trim((string) ($_GET['subject'] ?? ''));

/* ---- Students (optionally filtered by grade) ---- */
$studentWhere = '1=1';
if ($grade !== '') {
    $gradeSafe = mysqli_real_escape_string($conn, $grade);
    $studentWhere = "Grade = '$gradeSafe'";
}

$studRes = mysqli_query($conn, "SELECT id, Assesment, firstName, surname, Grade FROM Student WHERE $studentWhere");
if ($studRes === false) {
    respond(['error' => 'Database error loading students: ' . mysqli_error($conn)], 500);
}

$students = [];
$idsInOrder = [];
while ($row = mysqli_fetch_assoc($studRes)) {
    $students[$row['id']] = $row;
    $idsInOrder[] = $row['id'];
}

$totalLearners = count($idsInOrder);
$recordsLogged = 0;
$passing = 0;
$atRisk = 0;
$records = [];

if ($totalLearners > 0) {
    $idsSafe = implode(',', array_map(
        fn($id) => "'" . mysqli_real_escape_string($conn, $id) . "'",
        $idsInOrder
    ));

    $conditions = ["student_id IN ($idsSafe)"];
    if ($term !== '') {
        $conditions[] = "term = '" . mysqli_real_escape_string($conn, $term) . "'";
    }
    if ($examType !== '') {
        $conditions[] = "exam_type = '" . mysqli_real_escape_string($conn, $examType) . "'";
    }
    $where = implode(' AND ', $conditions);

    if ($subject !== '') {
        // $subject is expected to be the exact subject column name
        // (e.g. "math", "eng") — same convention as subjects_config.php's
        // subject codes.
        $subjectCol = mysqli_real_escape_string($conn, $subject);
        $markRes = mysqli_query($conn, "SELECT student_id, term, `$subjectCol` AS score FROM exam2 WHERE $where");
    } else {
        $markRes = mysqli_query($conn, "SELECT * FROM exam2 WHERE $where");
    }

    if ($markRes === false) {
        respond(['error' => 'Database error loading marks: ' . mysqli_error($conn)], 500);
    }

    // Non-subject columns on exam2 — must be excluded from the
    // "average every remaining column" fallback below, or firstName/
    // lastName/Assesment/grade get cast to floats and pollute the average.
    $reservedCols = ['id', 'student_id', 'Assesment', 'firstName', 'lastName', 'grade', 'term', 'exam_type', 'year'];

    while ($mrow = mysqli_fetch_assoc($markRes)) {
        $sid = $mrow['student_id'] ?? null;
        if ($sid === null || !isset($students[$sid])) continue;

        if (array_key_exists('score', $mrow)) {
            $scores = ($mrow['score'] === null || $mrow['score'] === '') ? [] : [(float) $mrow['score']];
        } else {
            $scores = [];
            foreach ($mrow as $col => $val) {
                if (in_array($col, $reservedCols, true)) continue;
                if ($val === null || $val === '') continue;
                $scores[] = (float) $val;
            }
        }

        if (empty($scores)) continue;

        $avg = array_sum($scores) / count($scores);
        $recordsLogged++;
        if ($avg >= 50) {
            $passing++;
        } else {
            $atRisk++;
        }

        $s = $students[$sid];
        $records[] = [
            'id'          => $sid,
            'learnerName' => trim($s['firstName'] . ' ' . $s['surname']),
            'initials'    => strtoupper(substr($s['firstName'], 0, 1) . substr($s['surname'], 0, 1)),
            'grade'       => $s['Grade'],
            'term'        => $term !== '' ? $term : ($mrow['term'] ?? ''),
            'average'     => round($avg, 1),
        ];
    }
}

respond([
    'stats' => [
        'totalLearners' => $totalLearners,
        'recordsLogged' => $recordsLogged,
        'passing'       => $passing,
        'atRisk'        => $atRisk,
    ],
    'records' => $records,
]);

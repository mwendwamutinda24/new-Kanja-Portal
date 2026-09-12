<?php
/**
 * GET /api/progress_records.php
 *
 * Backs the Progress Records screen: returns summary stats plus a
 * per-student list of averaged marks, optionally filtered by grade, term,
 * exam type, and subject.
 *
 * SCHEMA ASSUMPTIONS — please confirm against the real `exam2` table:
 *  1. `exam2` has columns `studentId`, `examTerm`, `examType`, `examYear`,
 *     matching the WHERE clause used in the roster-loading endpoint.
 *  2. Subject marks are stored as one column per subject code on the same
 *     row (e.g. `eng`, `math` — as seen in the roster endpoint), NOT as a
 *     separate `subject`/`score` pair. If that's wrong, the per-subject
 *     branch below (`$subjectCol`) needs to change to whatever the real
 *     shape is.
 *  3. "Passing" is defined here as an average across a student's subject
 *     marks for the matched exam combo being >= 50 — this wasn't specified
 *     anywhere, so confirm it matches how the web app defines pass/at-risk.
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

    $conditions = ["studentId IN ($idsSafe)"];
    if ($term !== '') {
        $conditions[] = "examTerm = '" . mysqli_real_escape_string($conn, $term) . "'";
    }
    if ($examType !== '') {
        $conditions[] = "examType = '" . mysqli_real_escape_string($conn, $examType) . "'";
    }
    $where = implode(' AND ', $conditions);

    if ($subject !== '') {
        // Assumes `$subject` is passed as the exact subject column name
        // (e.g. "math", "eng") — same convention as subjects_config.php's
        // subject codes.
        $subjectCol = mysqli_real_escape_string($conn, $subject);
        $markRes = mysqli_query($conn, "SELECT studentId, examTerm, `$subjectCol` AS score FROM exam2 WHERE $where");
    } else {
        $markRes = mysqli_query($conn, "SELECT * FROM exam2 WHERE $where");
    }

    if ($markRes === false) {
        respond(['error' => 'Database error loading marks: ' . mysqli_error($conn)], 500);
    }

    $reservedCols = ['id', 'studentId', 'examTerm', 'examType', 'examYear'];

    while ($mrow = mysqli_fetch_assoc($markRes)) {
        $sid = $mrow['studentId'] ?? null;
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
            'term'        => $term !== '' ? $term : ($mrow['examTerm'] ?? ''),
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

<?php
/**
 * GET /api/progress_records.php
 *
 * Modes:
 *   ?meta=1            -> { learners, years, examTypes }  (dropdown data, cacheable)
 *   (default)          -> { records, total, stats, subjectMeans, offset, limit }
 *                         records is ONE PAGE (default 50) of the filtered rows;
 *                         stats / subjectMeans / total cover ALL filtered rows.
 *   ?export=1          -> same as default but returns every record (for the PDF).
 *
 * Query params (all optional): grade, term, exam_type, year, subject, student_id,
 *                              offset, limit
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

// Compress the JSON response (much smaller over mobile data)
if (function_exists('ob_gzhandler') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

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

/* =====================================================================
   META MODE: dropdown data only. Fetched once by the app and cached.
   ===================================================================== */
if (($_GET['meta'] ?? '') === '1') {
    header('Cache-Control: private, max-age=300');

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

    $years = [];
    $yr = mysqli_query($conn, "SELECT DISTINCT year FROM exam2 WHERE year IS NOT NULL AND year <> '' ORDER BY year DESC");
    if ($yr) {
        while ($r = mysqli_fetch_assoc($yr)) {
            $years[] = (int) $r['year'];
        }
    }

    $examMap = [];
    $er = mysqli_query($conn, "SELECT DISTINCT exam_type FROM exam2 WHERE exam_type IS NOT NULL AND exam_type <> ''");
    if ($er) {
        while ($r = mysqli_fetch_assoc($er)) {
            $k = exam_key($r['exam_type']);
            if ($k === '' || isset($examMap[$k])) continue;
            $examMap[$k] = ['value' => $k, 'label' => exam_label($r['exam_type'])];
        }
    }
    $order = array_keys($knownExamLabels);
    uasort($examMap, function ($a, $b) use ($order) {
        $ia = array_search($a['value'], $order, true);
        $ib = array_search($b['value'], $order, true);
        $ia = $ia === false ? 99 : $ia;
        $ib = $ib === false ? 99 : $ib;
        return $ia === $ib ? strcasecmp($a['label'], $b['label']) : $ia <=> $ib;
    });

    respond([
        'learners'  => $learners,
        'years'     => $years,
        'examTypes' => array_values($examMap),
    ]);
}

/* =====================================================================
   RECORDS MODE
   ===================================================================== */
$gradeIn   = preg_replace('/\D/', '', (string) ($_GET['grade'] ?? ''));
$termIn    = preg_replace('/\D/', '', (string) ($_GET['term'] ?? ''));
$yearIn    = (int) ($_GET['year'] ?? 0);
$studentIn = (int) ($_GET['student_id'] ?? 0);
$examIn    = exam_key($_GET['exam_type'] ?? '');

$subjectIn = trim((string) ($_GET['subject'] ?? ''));
if (!in_array($subjectIn, $subjectCodes, true)) $subjectIn = '';

/* Paging: default 50 per page; export=1 returns everything */
$export = ($_GET['export'] ?? '') === '1';
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$limit  = $export ? 0 : (isset($_GET['limit']) ? min(200, max(1, (int) $_GET['limit'])) : 50);

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
if ($studentIn > 0) $conditions[] = "e.student_id = $studentIn";

$subjectsToUse = $subjectIn !== '' ? [$subjectIn] : $subjectCodes;
$subjectSelect = implode(', ', array_map(fn($c) => "e.`$c`", $subjectsToUse));

$sql = "SELECT e.student_id, s.Assesment, s.firstName, s.surname,
               e.grade, e.term, e.exam_type, e.year,
               $subjectSelect
        FROM exam2 e
        JOIN Student s ON s.id = e.student_id
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY s.firstName, s.surname, e.year DESC, e.term DESC, e.id";

$res = mysqli_query($conn, $sql, MYSQLI_USE_RESULT);
if ($res === false) {
    respond(['error' => 'Database error loading marks: ' . mysqli_error($conn)], 500);
}

/* One pass: unpivot, compute stats over ALL rows, keep only the requested page */
$records     = [];
$total       = 0;
$sum         = 0.0;
$passing     = 0;
$learnerSeen = [];
$bySubject   = [];

while ($row = mysqli_fetch_assoc($res)) {
    $gradeOut = preg_replace('/\D/', '', (string) $row['grade']);
    $termOut  = preg_replace('/\D/', '', (string) $row['term']);
    $examKey  = exam_key($row['exam_type']);
    $examLbl  = exam_label($row['exam_type']);

    foreach ($subjectsToUse as $code) {
        $val = $row[$code] ?? null;
        if ($val === null || $val === '') continue;
        $score = (float) $val;

        // stats over everything that matches the filters
        $learnerSeen[$row['student_id']] = true;
        $sum += $score;
        if ($score >= 50) $passing++;
        if (!isset($bySubject[$code])) $bySubject[$code] = ['sum' => 0.0, 'count' => 0];
        $bySubject[$code]['sum'] += $score;
        $bySubject[$code]['count']++;

        // only build row objects for the page being returned
        $inPage = $limit === 0 || ($total >= $offset && $total < $offset + $limit);
        if ($inPage) {
            $records[] = [
                'studentId' => (int) $row['student_id'],
                'admNo'     => $row['Assesment'],
                'firstName' => $row['firstName'],
                'surname'   => $row['surname'],
                'grade'     => $gradeOut !== '' ? $gradeOut : $row['grade'],
                'term'      => $termOut !== '' ? $termOut : $row['term'],
                'examType'  => $examKey,
                'examLabel' => $examLbl,
                'year'      => $row['year'],
                'subject'   => $code,
                'score'     => $score,
            ];
        }
        $total++;
    }
}
mysqli_free_result($res);

$subjectMeans = [];
foreach ($bySubject as $code => $v) {
    $subjectMeans[] = ['subject' => $code, 'mean' => round($v['sum'] / $v['count'], 2), 'count' => $v['count']];
}

respond([
    'records'      => $records,
    'total'        => $total,
    'offset'       => $offset,
    'limit'        => $limit,
    'stats'        => [
        'totalLearners' => count($learnerSeen),
        'recordsLogged' => $total,
        'mean'          => $total ? round($sum / $total, 4) : 0,
        'passing'       => $passing,
        'atRisk'        => $total - $passing,
    ],
    'subjectMeans' => $subjectMeans,
]);

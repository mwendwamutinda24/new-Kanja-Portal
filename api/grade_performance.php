<?php
// ============================================================
// api/grade_performance.php
// Cohort stats per subject for a given grade/term/exam/year.
//
// GET params:
//   grade      -> "Grade 4" or "4"
//   term       -> "Term 3" or "3"
//   exam_type  -> "End Term" or "endterm"
//   year       -> "2026"
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

include __DIR__ . '/../conn.php';
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

/* ── Normalise inputs ────────────────────────────────────── */
$rawGrade = isset($_GET['grade'])     ? trim($_GET['grade'])     : '';
$rawTerm  = isset($_GET['term'])      ? trim($_GET['term'])      : '';
$rawExam  = isset($_GET['exam_type']) ? trim($_GET['exam_type']) : '';
$rawYear  = isset($_GET['year'])      ? trim($_GET['year'])      : '';

$gradeInt = 0;
if (preg_match('/(\d+)/', $rawGrade, $m)) $gradeInt = (int)$m[1];

$termNum = '';
if (preg_match('/(\d+)/', $rawTerm, $m)) $termNum = $m[1];

$yearInt = (int)$rawYear;

// Map mobile-facing labels to DB values
$examMap = [
    'opener'  => 'opener',
    'midterm' => 'midterm',
    'mid term'=> 'midterm',
    'endterm' => 'endterm',
    'end term'=> 'endterm',
];
$examKey = strtolower(str_replace('_', ' ', $rawExam));
$examDb  = $examMap[$examKey] ?? null;

if (!$gradeInt || $termNum === '' || !$examDb || !$yearInt) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid grade/term/exam_type/year',
                      'got'   => ['grade'=>$rawGrade,'term'=>$rawTerm,'exam_type'=>$rawExam,'year'=>$rawYear]]);
    exit;
}

$termLabel = "Term $termNum";

/* ── Subject map (label + column) ────────────────────────── */
$subjectCols = [
    'math'   => 'Mathematics',
    'eng'    => 'English',
    'kisw'   => 'Kiswahili',
    'sst'    => 'Social Studies',
    'scie'   => 'Science',
    'ca'     => 'CA',
    'agri'   => 'Agriculture',
    're'     => 'RE',
    'pretec' => 'Pre-Technical',
];

/* ── Pull all exam rows for this cohort in one query ─────── */
$cols = implode(', ', array_keys($subjectCols));
$stmt = $conn->prepare("
    SELECT $cols
    FROM exam2
    WHERE grade = ? AND term = ? AND year = ? AND exam_type = ?
");
$stmt->bind_param('isis', $gradeInt, $termLabel, $yearInt, $examDb);
$stmt->execute();
$res = $stmt->get_result();

/* ── Accumulate per-subject stats ────────────────────────── */
$stats = [];
foreach ($subjectCols as $col => $label) {
    $stats[$col] = [
        'subject' => $label,
        'sum'     => 0.0,
        'count'   => 0,
        'highest' => null,
        'lowest'  => null,
    ];
}

while ($row = $res->fetch_assoc()) {
    foreach ($subjectCols as $col => $label) {
        $v = $row[$col];
        if ($v === null || $v === '') continue;
        $v = (float)$v;
        $stats[$col]['sum'] += $v;
        $stats[$col]['count']++;
        if ($stats[$col]['highest'] === null || $v > $stats[$col]['highest']) $stats[$col]['highest'] = $v;
        if ($stats[$col]['lowest']  === null || $v < $stats[$col]['lowest'])  $stats[$col]['lowest']  = $v;
    }
}
$stmt->close();

/* ── Format response ─────────────────────────────────────── */
$subjects = [];
foreach ($stats as $s) {
    if ($s['count'] === 0) continue; // skip subjects with no data at this exam
    $subjects[] = [
        'subject' => $s['subject'],
        'average' => round($s['sum'] / $s['count'], 1),
        'highest' => (int)$s['highest'],
        'lowest'  => (int)$s['lowest'],
    ];
}

if (empty($subjects)) {
    echo json_encode(['subjects' => []]);
    exit;
}

echo json_encode(['subjects' => $subjects]);

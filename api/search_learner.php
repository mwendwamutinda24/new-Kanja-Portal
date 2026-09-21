<?php
// ============================================================
// api/search_learner.php
// Search learners by UPI / name / grade. Returns each student with
// their most recent exam average and a trend vs. the previous exam.
//
// GET params:
//   assessment_number  -> matches Student.UPI (fuzzy)
//   name               -> matches firstName or surname (fuzzy)
//   grade              -> "Grade 4" or "4" (optional)
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

/* ── Read + normalise inputs ─────────────────────────────── */
$rawAssess = isset($_GET['assessment_number']) ? trim($_GET['assessment_number']) : '';
$rawName   = isset($_GET['name'])              ? trim($_GET['name'])              : '';
$rawGrade  = isset($_GET['grade'])             ? trim($_GET['grade'])             : '';

// "Grade 4" or "4" -> 4 ; "" -> null
$gradeInt = null;
if ($rawGrade !== '') {
    if (preg_match('/(\d+)/', $rawGrade, $m)) $gradeInt = (int)$m[1];
}

if ($rawAssess === '' && $rawName === '') {
    echo json_encode(['error' => 'Enter an assessment number or a learner name.']);
    exit;
}

/* ── Build the student query ─────────────────────────────── */
$subjects = [
    'math', 'eng', 'kisw', 'sst', 'scie', 'ca', 'agri', 're', 'pretec',
];

$where  = [];
$params = [];
$types  = '';

if ($rawAssess !== '') {
    $where[]  = 's.UPI LIKE ?';
    $params[] = '%' . $rawAssess . '%';
    $types   .= 's';
}
if ($rawName !== '') {
    $where[]  = '(s.firstName LIKE ? OR s.surname LIKE ?)';
    $params[] = '%' . $rawName . '%';
    $params[] = '%' . $rawName . '%';
    $types   .= 'ss';
}
if ($gradeInt !== null) {
    $where[]  = 's.`Grade` = ?';
    $params[] = $gradeInt;
    $types   .= 'i';
}

$sql = "SELECT s.id, s.UPI, s.firstName, s.surname, s.`Grade` AS grade
        FROM Student s
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.firstName, s.surname
        LIMIT 50";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$students = [];
while ($row = $res->fetch_assoc()) {
    $students[] = [
        'id'     => (int)$row['id'],
        'upi'    => $row['UPI'],
        'name'   => trim($row['firstName'] . ' ' . $row['surname']),
        'grade'  => 'Grade ' . $row['grade'],
        'gradeInt' => (int)$row['grade'],
    ];
}
$stmt->close();

if (empty($students)) {
    echo json_encode(['results' => []]);
    exit;
}

/* ── For each student, fetch last two exams + compute mean ─ */
function compute_student_exam_mean(mysqli $conn, int $studentId, array $subjects): array {
    // Pull all exam2 rows for the student, newest first
    $stmt = $conn->prepare("
        SELECT id, grade, term, year, exam_type,
               math, eng, kisw, sst, scie, ca, agri, re, pretec
        FROM exam2
        WHERE student_id = ?
        ORDER BY year DESC,
                 CASE term WHEN 'Term 3' THEN 3 WHEN 'Term 2' THEN 2 ELSE 1 END DESC,
                 CASE exam_type WHEN 'endterm' THEN 3 WHEN 'midterm' THEN 2 ELSE 1 END DESC
        LIMIT 4
    ");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $r = $stmt->get_result();

    $exams = [];
    while ($row = $r->fetch_assoc()) {
        $scores = [];
        foreach ($subjects as $s) {
            if ($row[$s] !== null && $row[$s] !== '') $scores[] = (float)$row[$s];
        }
        if (empty($scores)) continue;
        $mean = array_sum($scores) / count($scores);
        $exams[] = [
            'exam_id'  => (int)$row['id'],
            'term'     => $row['term'],
            'year'     => (int)$row['year'],
            'exam_type'=> $row['exam_type'],
            'mean'     => round($mean, 1),
        ];
    }
    $stmt->close();

    // Skip duplicate (same term/year/exam_type) — keep the newest
    $seen = [];
    $unique = [];
    foreach ($exams as $e) {
        $key = $e['term'] . '|' . $e['year'] . '|' . $e['exam_type'];
        if (!isset($seen[$key])) { $seen[$key] = true; $unique[] = $e; }
    }

    return $unique;
}

$results = [];
foreach ($students as $s) {
    $exams = compute_student_exam_mean($conn, $s['id'], $subjects);
    $avg   = count($exams) > 0 ? $exams[0]['mean'] : 0.0;

    $trend = 'flat';
    if (count($exams) >= 2) {
        $delta = $exams[0]['mean'] - $exams[1]['mean'];
        if ($delta > 1.0)      $trend = 'up';
        else if ($delta < -1.0) $trend = 'down';
    }

    $results[] = [
        'assessmentNumber' => $s['upi'] ?: ('STU-' . str_pad((string)$s['id'], 4, '0', STR_PAD_LEFT)),
        'name'             => $s['name'],
        'grade'            => $s['grade'],
        'average'          => $avg,
        'trend'            => $trend,
    ];
}

echo json_encode(['results' => $results]);

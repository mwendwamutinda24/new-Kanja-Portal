<?php
/**
 * /api/results/class.php
 *
 * JSON endpoint for HOI / Teacher viewing a FULL CLASS marklist —
 * mirrors ViewResults.php's core computation (ranking, subject means,
 * achievement bands) without the HTML/Chart.js/export machinery, which
 * the mobile app doesn't need (it can build its own charts from this
 * JSON, the same way HoiDashboard.tsx already does).
 *
 * Query params: grade, term, examType, year   (all required)
 *   grade    — plain number, e.g. "4"  (NOT "Grade 4")
 *   term     — "Term 1" / "Term 2" / "Term 3"
 *   examType — "Opener" / "Mid Term" / "End Term"
 *   year     — e.g. "2026"
 *
 * AUTH — uses the existing session scheme from login.php/me.php via
 * auth_check.php's require_auth(); restricted to hoi/teacher roles.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/../../conn.php';
require __DIR__ . '/../../subjects_config.php';
require __DIR__ . '/../../auth_check.php';

function fail($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

if (!isset($conn) || !$conn) {
    fail('Database connection unavailable.');
}

$session = require_auth(); // 401s + exits internally if token is invalid/expired

if (!in_array($session['role'], ['hoi', 'teacher'], true)) {
    fail('This endpoint is for HOI/teacher accounts only.', 403);
}

$grade     = $_GET['grade'] ?? '';
$termLabel = $_GET['term'] ?? '';
$examLabel = $_GET['examType'] ?? '';
$year      = $_GET['year'] ?? '';

if (!$grade || !$termLabel || !$examLabel || !$year) {
    fail('grade, term, examType, and year are all required.', 400);
}

$gradeInt = (int) $grade;

$examTypeMap = [
    'Opener'   => 'opener',
    'Mid Term' => 'midterm',
    'End Term' => 'endterm',
];
$examType    = $examTypeMap[$examLabel] ?? strtolower(str_replace(' ', '', $examLabel));
$examDisplay = $examLabel;

$subjectMapForGrade = getSubjectsForGrade($gradeInt);
$subjects      = array_keys($subjectMapForGrade);
$subjectLabels = array_values($subjectMapForGrade);
$subjectCount  = count($subjects);
$totalOutOf    = $subjectCount * 100;

if ($subjectCount === 0) {
    fail('Could not determine subjects for this grade.', 400);
}

$stmt = $conn->prepare(
    "SELECT DISTINCT * FROM exam2 WHERE grade = ? AND term = ? AND exam_type = ? AND year = ?"
);
$gradeLabel = "Grade $gradeInt";
$stmt->bind_param('ssss', $gradeLabel, $termLabel, $examType, $year);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode([
        'meta'     => [
            'grade' => $gradeInt, 'term' => $termLabel, 'exam' => $examDisplay, 'year' => (int) $year,
            'studentCount' => 0,
        ],
        'students'         => [],
        'subjectMeans'     => [],
        'achievementBands' => [],
    ]);
    exit;
}

$subjectTotals = array_fill_keys($subjects, 0);
$classBands    = ['ee' => 0, 'me' => 0, 'ae' => 0, 'be' => 0];
$totalSum      = 0;
$students      = [];

while ($row = mysqli_fetch_assoc($result)) {
    $subs = [];
    foreach ($subjects as $s) {
        $subs[$s] = (int) ($row[$s] ?? 0);
    }
    $total = array_sum($subs);

    $avgPerSubject = $total / $subjectCount;
    $band = bandInfoForGrade($avgPerSubject, $gradeInt);
    $tier = strtolower(explode('.', str_replace(['1', '2'], '', $band['code']))[0]); // 'EE1'/'E.E' -> 'ee'
    if (!isset($classBands[$tier])) $tier = 'ae'; // safety fallback
    $classBands[$tier]++;

    foreach ($subs as $s => $score) {
        $subjectTotals[$s] += $score;
    }

    $students[] = [
        'assessmentNo' => $row['Assesment'] ?? null,
        'firstName'    => $row['firstName'] ?? '',
        'lastName'     => $row['lastName'] ?? '',
        'subjects'     => $subs,
        'total'        => $total,
        'band'         => $band['code'],
    ];
    $totalSum += $total;
}
$stmt->close();

$studentCount = count($students);
$classMeanOfTotal   = $studentCount ? round($totalSum / $studentCount, 1) : 0;
$classMeanPerSubject = $studentCount ? round($classMeanOfTotal / $subjectCount, 1) : 0;

// Rank by total, descending, ties share a rank (same logic as ViewResults.php)
usort($students, fn($a, $b) => $b['total'] <=> $a['total']);
$rank = 1; $prevTotal = null; $repeatCount = 0;
foreach ($students as $i => $s) {
    if ($s['total'] === $prevTotal) {
        $students[$i]['rank'] = $rank;
        $repeatCount++;
    } else {
        $rank += $repeatCount;
        $students[$i]['rank'] = $rank;
        $prevTotal = $s['total'];
        $repeatCount = 1;
    }
}

// Expand each student's subject scores into labelled, band-tagged rows for easy rendering
foreach ($students as $i => $s) {
    $expanded = [];
    foreach ($subjectMapForGrade as $key => $label) {
        $score = $s['subjects'][$key];
        $band  = bandInfoForGrade($score, $gradeInt);
        $expanded[] = ['subject' => $label, 'score' => $score, 'grade' => $band['code']];
    }
    $students[$i]['subjects'] = $expanded;
}

$subjectMeans = [];
foreach ($subjectMapForGrade as $key => $label) {
    $mean = $studentCount ? round($subjectTotals[$key] / $studentCount, 1) : 0;
    $subjectMeans[] = ['subject' => $label, 'mean' => $mean, 'band' => bandInfoForGrade($mean, $gradeInt)['code']];
}

$achievementBands = [
    ['band' => 'Exceeding',   'count' => $classBands['ee']],
    ['band' => 'Meeting',     'count' => $classBands['me']],
    ['band' => 'Approaching', 'count' => $classBands['ae']],
    ['band' => 'Below',       'count' => $classBands['be']],
];

echo json_encode([
    'meta' => [
        'grade'            => $gradeInt,
        'term'             => $termLabel,
        'exam'             => $examDisplay,
        'year'             => (int) $year,
        'studentCount'     => $studentCount,
        'totalOutOf'       => $totalOutOf,
        'classMeanTotal'   => $classMeanOfTotal,
        'classMeanSubject' => $classMeanPerSubject,
    ],
    'students'         => $students,
    'subjectMeans'     => $subjectMeans,
    'achievementBands' => $achievementBands,
], JSON_NUMERIC_CHECK);
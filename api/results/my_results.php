<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../../conn.php';
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
$term     = trim((string) ($input['term'] ?? ''));
$examType = trim((string) ($input['examType'] ?? ''));
$year     = trim((string) ($input['year'] ?? ''));

if ($term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'term, examType and year are all required'], 400);
}

$termSafe     = mysqli_real_escape_string($conn, $term);
$examTypeSafe = mysqli_real_escape_string($conn, $examType);
$yearSafe     = mysqli_real_escape_string($conn, $year);

// WORKAROUND: exam2.term has been written in at least two formats — bare
// digits ("1") and "Term 1" style strings. Match either.
$termCandidates = [$termSafe];
if (ctype_digit($term)) {
    $termCandidates[] = 'Term ' . $termSafe;
}
$termInClause = "'" . implode("','", $termCandidates) . "'";

$examLabelMap = ['opener' => 'Opener', 'midterm' => 'Mid Term', 'endterm' => 'End Term'];
$examLabel    = $examLabelMap[$examType] ?? $examType;

/* Simple 4-tier band, independent of grade — mirrors bandInfo() in the
   web app's results.php. Used for the KPI strip and the "Overall"
   achievement-band section, which are always 4-tier regardless of grade. */
function skp_simple_tier(float $score): array {
    if ($score >= 75) return ['tier' => 'ee', 'label' => 'Exceeding Expectation'];
    if ($score >= 50) return ['tier' => 'me', 'label' => 'Meeting Expectation'];
    if ($score >= 26) return ['tier' => 'ae', 'label' => 'Approaching Expectation'];
    return ['tier' => 'be', 'label' => 'Below Expectation'];
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

    $q = "SELECT * FROM exam2
          WHERE student_id = '$studentIdSafe' AND grade = '$gradeSafe'
            AND term IN ($termInClause) AND exam_type = '$examTypeSafe' AND year = '$yearSafe'
          LIMIT 1";
    $res = mysqli_query($conn, $q);
    $row = ($res !== false && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;

    $subjects = [];
    $total    = 0;
    foreach ($subjectMap as $s) {
        $score  = $row !== null ? (int) ($row[$s['code']] ?? 0) : 0;
        $total += $score;
        $band   = skp_band_info_for_grade($score, $gradeInt);
        $subjects[] = ['subject' => $s['label'], 'score' => $score, 'grade' => $band['code'], 'remarks' => $band['label']];
    }
    $lastName = $studentRow['surname'] ?? '';

    respond([
        'success'  => true,
        'student'  => [
            'id'         => (string) $studentRow['id'],
            'name'       => trim($studentRow['firstName'] . ' ' . $lastName),
            'average'    => $row !== null ? round($total / count($subjectMap), 1) : null,
            'hasResults' => $row !== null,
        ],
        'subjects' => $subjects,
    ]);
}

// ══════════════════════════════════════════════════════════════
// Staff (teacher / Head of Instituion): full class dashboard —
// KPIs, achievement bands, subject means, previous-exam comparison,
// and the ranked marklist. Reads directly from exam2 (which already
// carries firstName/lastName/Assesment denormalized on every row),
// the same source the web report (results.php) uses — so a learner
// only appears here once they have a recorded exam2 row for this
// grade/term/exam/year, matching the web dashboard's behavior.
// ══════════════════════════════════════════════════════════════
$grade = trim((string) ($input['grade'] ?? ''));
if ($grade === '') {
    respond(['success' => false, 'message' => 'grade is required for staff lookups.'], 400);
}
$gradeInt     = (int) $grade;
$gradeSafe    = mysqli_real_escape_string($conn, $grade);
$isLowerGrade = $gradeInt >= 1 && $gradeInt <= 6;

$subjectMap = skp_subjects_for_grade($grade);
if (count($subjectMap) === 0) {
    respond(['success' => false, 'message' => 'No subjects configured for this grade'], 500);
}
$subjectCodes  = array_column($subjectMap, 'code');
$subjectLabels = array_column($subjectMap, 'label');
$subjectCount  = count($subjectMap);
$totalOutOf    = $subjectCount * 100;

$curRes = mysqli_query($conn, "SELECT * FROM exam2
    WHERE grade = '$gradeSafe' AND term IN ($termInClause)
      AND exam_type = '$examTypeSafe' AND year = '$yearSafe'");
if ($curRes === false) {
    respond(['success' => false, 'message' => 'Failed to load results for this grade', 'debug' => mysqli_error($conn)], 500);
}

$currentRows = [];
while ($r = mysqli_fetch_assoc($curRes)) $currentRows[] = $r;
$studentCount = count($currentRows);

if ($studentCount === 0) {
    respond(['success' => true, 'grade' => $grade, 'studentCount' => 0, 'students' => [], 'message' => 'No results found for the selected filters.']);
}

$subjectTotals      = array_fill_keys($subjectCodes, 0);
$subjectBandsSimple = array_fill_keys($subjectCodes, ['ee' => 0, 'me' => 0, 'ae' => 0, 'be' => 0]);
$classBandsSimple   = ['ee' => 0, 'me' => 0, 'ae' => 0, 'be' => 0];
$classBandsGraded   = [];
$totalSum           = 0;
$students           = [];

foreach ($currentRows as $row) {
    $subs = [];
    $rowTotal = 0;
    foreach ($subjectCodes as $c) {
        $score = (int) ($row[$c] ?? 0);
        $subs[$c] = $score;
        $rowTotal += $score;
        $subjectTotals[$c] += $score;
        $t = skp_simple_tier($score);
        $subjectBandsSimple[$c][$t['tier']]++;
    }
    $avgPerSubject = $subjectCount ? $rowTotal / $subjectCount : 0;

    $simple = skp_simple_tier($avgPerSubject);
    $classBandsSimple[$simple['tier']]++;

    $graded = skp_band_info_for_grade((int) round($avgPerSubject), $gradeInt);
    $classBandsGraded[$graded['code']] = ($classBandsGraded[$graded['code']] ?? 0) + 1;

    $students[] = [
        'assesment' => $row['Assesment'] ?? '',
        'firstName' => $row['firstName'] ?? '',
        'lastName'  => $row['lastName'] ?? '',
        'subjects'  => $subs,
        'total'     => $rowTotal,
        'bandCode'  => $graded['code'],
    ];
    $totalSum += $rowTotal;
}

usort($students, fn($a, $b) => $b['total'] <=> $a['total']);
$rank = 1; $prevTotal = null; $repeat = 0;
foreach ($students as $i => $s) {
    if ($s['total'] === $prevTotal) {
        $students[$i]['rank'] = $rank;
        $repeat++;
    } else {
        $rank += $repeat;
        $students[$i]['rank'] = $rank;
        $prevTotal = $s['total'];
        $repeat = 1;
    }
}

$classMeanOfTotal    = round($totalSum / $studentCount, 1);
$classMeanPerSubject = round($classMeanOfTotal / $subjectCount, 1);

$subjectMeans = [];
foreach ($subjectCodes as $c) $subjectMeans[$c] = round($subjectTotals[$c] / $studentCount, 1);

$eeP = round($classBandsSimple['ee'] / $studentCount * 100);
$meP = round($classBandsSimple['me'] / $studentCount * 100);
$aeP = round($classBandsSimple['ae'] / $studentCount * 100);
$beP = round($classBandsSimple['be'] / $studentCount * 100);

/* Grade-aware breakdown rows (4-level for Grade 1-6, 8-level for 7-9),
   built directly from the counts collected above — no external
   allBandCodesForGrade()/representativeScoreForGrade() helpers needed. */
$breakdownCodes = $isLowerGrade
    ? ['E.E', 'M.E', 'A.E', 'B.E']
    : ['EE2', 'EE1', 'ME2', 'ME1', 'AE2', 'AE1', 'BE2', 'BE1'];
$breakdownLabelFor = [
    'E.E' => 'Exceeding Expectation', 'M.E' => 'Meeting Expectation',
    'A.E' => 'Approaching Expectation', 'B.E' => 'Below Expectation',
    'EE2' => 'Exceeding Expectation 2', 'EE1' => 'Exceeding Expectation 1',
    'ME2' => 'Meeting Expectation 2', 'ME1' => 'Meeting Expectation 1',
    'AE2' => 'Approaching Expectation 2', 'AE1' => 'Approaching Expectation 1',
    'BE2' => 'Below Expectation 2', 'BE1' => 'Below Expectation 1',
];
$breakdown = [];
foreach ($breakdownCodes as $code) {
    $count = $classBandsGraded[$code] ?? 0;
    $breakdown[] = [
        'code'  => $code,
        'label' => $breakdownLabelFor[$code],
        'count' => $count,
        'pct'   => round($count / $studentCount * 100),
    ];
}

/* ════ PREVIOUS EXAM (for comparison) ════ */
$hasPrev = false;
$prevLabel = '';
$prevMeans = [];
$prevStudentCount = 0;
$prevClassMeanTotal = 0;

$prevExamMap  = ['opener' => null, 'midterm' => 'opener', 'endterm' => 'midterm'];
$prevExamType = $prevExamMap[$examType] ?? null;

function skp_fetch_prev_stats($conn, string $gradeSafe, string $termInClause, string $examTypeVal, string $yearSafe, array $subjectCodes) {
    $examTypeSafe = mysqli_real_escape_string($conn, $examTypeVal);
    $res = mysqli_query($conn, "SELECT * FROM exam2
        WHERE grade = '$gradeSafe' AND term IN ($termInClause)
          AND exam_type = '$examTypeSafe' AND year = '$yearSafe'");
    if ($res === false) return null;
    $totals = array_fill_keys($subjectCodes, 0);
    $count = 0;
    while ($r = mysqli_fetch_assoc($res)) {
        foreach ($subjectCodes as $c) $totals[$c] += (int) ($r[$c] ?? 0);
        $count++;
    }
    return $count > 0 ? [$totals, $count] : null;
}

if ($prevExamType) {
    $prevLabel = $examLabelMap[$prevExamType] ?? ucfirst($prevExamType);
    $stats = skp_fetch_prev_stats($conn, $gradeSafe, $termInClause, $prevExamType, $yearSafe, $subjectCodes);
    if ($stats !== null) {
        [$prevTotals, $prevStudentCount] = $stats;
        foreach ($subjectCodes as $c) $prevMeans[$c] = round($prevTotals[$c] / $prevStudentCount, 1);
        $prevClassMeanTotal = round(array_sum($prevTotals) / $prevStudentCount, 1);
        $hasPrev = true;
    }
}
if (!$hasPrev && (int) $year > 2026) {
    $prevYear = (string) ((int) $year - 1);
    $prevYearSafe = mysqli_real_escape_string($conn, $prevYear);
    $stats = skp_fetch_prev_stats($conn, $gradeSafe, $termInClause, $examType, $prevYearSafe, $subjectCodes);
    if ($stats !== null) {
        [$prevTotals, $prevStudentCount] = $stats;
        foreach ($subjectCodes as $c) $prevMeans[$c] = round($prevTotals[$c] / $prevStudentCount, 1);
        $prevClassMeanTotal = round(array_sum($prevTotals) / $prevStudentCount, 1);
        $prevLabel = "Year $prevYear ($examLabel)";
        $hasPrev = true;
    }
}

/* ════ ASSEMBLE OUTPUT ════ */
$subjectsOut = [];
foreach ($subjectCodes as $i => $c) {
    $mean = $subjectMeans[$c];
    $subjectsOut[] = [
        'code'  => $c,
        'label' => $subjectLabels[$i],
        'mean'  => $mean,
        'tier'  => skp_simple_tier($mean)['tier'],
        'bands' => $subjectBandsSimple[$c],
    ];
}

$comparisonSubjects = [];
if ($hasPrev) {
    foreach ($subjectCodes as $i => $c) {
        $cur  = $subjectMeans[$c];
        $prev = $prevMeans[$c] ?? 0;
        $comparisonSubjects[] = [
            'code'    => $c,
            'label'   => $subjectLabels[$i],
            'current' => $cur,
            'previous'=> $prev,
            'change'  => round($cur - $prev, 1),
        ];
    }
}

$studentsOut = [];
foreach ($students as $s) {
    $subjOut = [];
    foreach ($subjectCodes as $i => $c) {
        $score = $s['subjects'][$c];
        $band  = skp_band_info_for_grade($score, $gradeInt);
        $subjOut[] = ['code' => $c, 'label' => $subjectLabels[$i], 'score' => $score, 'bandCode' => $band['code']];
    }
    $studentsOut[] = [
        'rank'      => $s['rank'],
        'assesment' => $s['assesment'],
        'firstName' => $s['firstName'],
        'lastName'  => $s['lastName'],
        'subjects'  => $subjOut,
        'total'     => $s['total'],
        'bandCode'  => $s['bandCode'],
    ];
}

$classMeanRow = ['subjects' => [], 'total' => $classMeanOfTotal, 'bandCode' => skp_band_info_for_grade((int) round($classMeanPerSubject), $gradeInt)['code']];
foreach ($subjectCodes as $i => $c) {
    $classMeanRow['subjects'][] = ['code' => $c, 'label' => $subjectLabels[$i], 'score' => $subjectMeans[$c]];
}

respond([
    'success' => true,
    'grade'   => $grade,
    'meta'    => [
        'gradeInt'      => $gradeInt,
        'isLowerGrade'  => $isLowerGrade,
        'term'          => $term,
        'examType'      => $examType,
        'examLabel'     => $examLabel,
        'year'          => $year,
        'studentCount'  => $studentCount,
        'subjectCount'  => $subjectCount,
        'totalOutOf'    => $totalOutOf,
        'classMeanTotal'=> $classMeanOfTotal,
        'classMeanSubject' => $classMeanPerSubject,
        'generatedAt'   => date('jS F Y, g:i A'),
    ],
    'kpis' => [
        'totalStudents'   => $studentCount,
        'exceedingCount'  => $classBandsSimple['ee'],
        'exceedingPct'    => $eeP,
        'belowCount'      => $classBandsSimple['be'],
        'belowPct'        => $beP,
    ],
    'bands' => [
        'overall' => [
            'ee' => $classBandsSimple['ee'], 'eeP' => $eeP,
            'me' => $classBandsSimple['me'], 'meP' => $meP,
            'ae' => $classBandsSimple['ae'], 'aeP' => $aeP,
            'be' => $classBandsSimple['be'], 'beP' => $beP,
        ],
        'breakdown' => $breakdown,
    ],
    'subjects' => $subjectsOut,
    'comparison' => [
        'hasPrevious'        => $hasPrev,
        'previousLabel'      => $prevLabel,
        'subjects'           => $comparisonSubjects,
        'classMeanCurrent'   => $classMeanOfTotal,
        'classMeanPrevious'  => $prevClassMeanTotal,
        'studentCountPrevious' => $prevStudentCount,
    ],
    'students'     => $studentsOut,
    'classMeanRow' => $classMeanRow,
]);

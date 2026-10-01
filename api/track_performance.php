<?php
/* ════════════════════════════════════════════════════════════════════
   track_performance.php  —  Track Performance backend
   ────────────────────────────────────────────────────────────────────
   GET ?action=meta
       -> { success, examTypes:[{label,value}] }
   GET ?action=search_learner&assessment_number=&name=&grade=
       -> { success, results:[{ id, assessmentNumber, name, grade, stream,
            average, trend, latestExam, subjects:[{label,score}],
            history:[{label,average}] }] }
   GET ?action=grade&grade=&term=&exam_type=&year=
       -> { success, subjects:[{subject,average,highest,lowest}],
            summary:{...}, top:[{name,assessmentNumber,total,average}] }
   ════════════════════════════════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

// Same includes as students_by_grade.php — adjust the paths if yours differ.
require_once __DIR__ . '/conn.php';              // defines $conn (mysqli)
require_once __DIR__ . '/subjects_config.php';   // getSubjectsForGrade()
// require_once __DIR__ . '/auth.php';           // <- add the SAME Bearer-token check your other endpoints use

/* ── helpers ─────────────────────────────────────────────────────── */
function out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}
function digitsOnly($v)  { return preg_replace('/[^0-9]/', '', (string)$v); }
function normExam($v)    { return preg_replace('/[^a-z0-9]/', '', strtolower((string)$v)); }
function normTerm($v) {
    $d = digitsOnly($v);
    return $d !== '' ? $d : strtolower(trim((string)$v));
}
/* 4-band cut-offs, same as the Results page: 75 / 50 / 26 */
function bandTier($avg) {
    if ($avg >= 75) return 'ee';
    if ($avg >= 50) return 'me';
    if ($avg >= 26) return 'ae';
    return 'be';
}

/* exam code (normalised) => display name. Built-ins + the exam_types table. */
function loadExamLabels($conn) {
    $labels = ['opener' => 'Opener', 'midterm' => 'Mid Term', 'endterm' => 'End Term'];
    try {
        $res = mysqli_query($conn, "SELECT code, name FROM exam_types ORDER BY id ASC");
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $k = normExam($r['code']);
                if ($k !== '') $labels[$k] = $r['name'] !== '' ? $r['name'] : $r['code'];
            }
        }
    } catch (\Throwable $e) { /* table missing: built-ins only */ }
    return $labels;
}

/* chronological position of an exam: year, then term, then exam order */
function examRank($year, $term, $examNorm, array $seq) {
    $ei = array_search($examNorm, $seq, true);
    if ($ei === false) $ei = count($seq);
    return ((int)$year) * 10000 + ((int)$term) * 100 + $ei;
}

function rowSubjectScores(array $row, array $subjectMap) {
    $scores = [];
    foreach ($subjectMap as $code => $label) $scores[$code] = (int)($row[$code] ?? 0);
    return $scores;
}

/* exam2 has split/duplicate rows: merge them so zeros get filled in */
function mergeRows(array $existing, array $new, array $subjectCodes) {
    foreach ($subjectCodes as $c) {
        if ((int)($existing[$c] ?? 0) === 0 && (int)($new[$c] ?? 0) !== 0) $existing[$c] = $new[$c];
    }
    return $existing;
}

$action = $_GET['action'] ?? '';
$labels = loadExamLabels($conn);
$seq    = array_merge(['opener', 'midterm', 'endterm'], array_values(array_diff(array_keys($labels), ['opener', 'midterm', 'endterm'])));

/* ════════════════════ ACTION: meta ════════════════════ */
if ($action === 'meta') {
    $types = [];
    foreach ($labels as $code => $name) $types[] = ['label' => $name, 'value' => $code];
    out(['success' => true, 'examTypes' => $types]);
}

/* ════════════════════ ACTION: search_learner ════════════════════ */
if ($action === 'search_learner') {
    $assess = trim($_GET['assessment_number'] ?? '');
    $name   = trim($_GET['name'] ?? '');
    $grade  = digitsOnly($_GET['grade'] ?? '');

    if ($assess === '' && $name === '') {
        out(['success' => false, 'message' => 'Enter an assessment number or a learner name.'], 400);
    }

    $where = ["student_id IS NOT NULL", "student_id <> ''"];
    if ($assess !== '') {
        $a = mysqli_real_escape_string($conn, $assess);
        $where[] = "Assesment LIKE '%$a%'";
    }
    if ($name !== '') {
        // every word must appear in the first or last name ("jane mwangi" works)
        foreach (preg_split('/\s+/', $name) as $w) {
            if ($w === '') continue;
            $w = mysqli_real_escape_string($conn, $w);
            $where[] = "(firstName LIKE '%$w%' OR lastName LIKE '%$w%')";
        }
    }
    if ($grade !== '') {
        $g = mysqli_real_escape_string($conn, $grade);
        $where[] = "grade = '$g'";
    }

    $idRes = mysqli_query($conn, "SELECT DISTINCT student_id FROM exam2 WHERE " . implode(' AND ', $where) . " LIMIT 25");
    $ids = [];
    while ($idRes && ($r = mysqli_fetch_assoc($idRes))) $ids[] = (string)$r['student_id'];

    if (!$ids) out(['success' => true, 'results' => []]);

    $in = "'" . implode("','", array_map(function ($i) use ($conn) { return mysqli_real_escape_string($conn, $i); }, $ids)) . "'";

    /* stream from the Student table (optional: blank if the column differs) */
    $streams = [];
    try {
        $sRes = mysqli_query($conn, "SELECT id, stream FROM Student WHERE id IN ($in)");
        while ($sRes && ($s = mysqli_fetch_assoc($sRes))) $streams[(string)$s['id']] = $s['stream'] ?? '';
    } catch (\Throwable $e) { /* leave blank */ }

    $res = mysqli_query($conn, "SELECT * FROM exam2 WHERE student_id IN ($in)");

    // student_id => examKey => row   (duplicates merged)
    $byStudent = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $sid  = (string)$row['student_id'];
        $term = (int)normTerm($row['term'] ?? '');
        $exam = normExam($row['exam_type'] ?? '');
        $year = (int)($row['year'] ?? 0);
        if ($term === 0 || $exam === '' || $year === 0) continue;

        $subjectMap = getSubjectsForGrade((int)digitsOnly($row['grade'] ?? ''));
        if (!$subjectMap) continue;

        $key = examRank($year, $term, $exam, $seq);
        $row['_exam'] = $exam; $row['_term'] = $term; $row['_year'] = $year; $row['_key'] = $key;

        if (isset($byStudent[$sid][$key])) {
            $row = mergeRows($byStudent[$sid][$key], $row, array_keys($subjectMap)) + $row;
        }
        $byStudent[$sid][$key] = $row;
    }

    $results = [];
    foreach ($byStudent as $sid => $exams) {
        ksort($exams);                       // oldest -> newest
        $history = [];
        $latest  = null;
        foreach ($exams as $row) {
            $subjectMap = getSubjectsForGrade((int)digitsOnly($row['grade'] ?? ''));
            $scores = rowSubjectScores($row, $subjectMap);
            $avg = count($scores) ? array_sum($scores) / count($scores) : 0;
            $history[] = [
                'label'   => ($labels[$row['_exam']] ?? ucfirst($row['_exam'])) . ", Term {$row['_term']} {$row['_year']}",
                'average' => round($avg, 1),
            ];
            $latest = ['row' => $row, 'scores' => $scores, 'avg' => $avg, 'map' => $subjectMap];
        }
        if (!$latest) continue;

        $n = count($history);
        $trend = 'flat';
        if ($n >= 2) {
            $diff = $history[$n - 1]['average'] - $history[$n - 2]['average'];
            $trend = $diff > 0.5 ? 'up' : ($diff < -0.5 ? 'down' : 'flat');
        }

        $subjects = [];
        foreach ($latest['scores'] as $code => $score) {
            $subjects[] = ['label' => $latest['map'][$code], 'score' => $score];
        }

        $r = $latest['row'];
        $results[] = [
            'id'               => (string)$sid,
            'assessmentNumber' => (string)($r['Assesment'] ?? ''),
            'name'             => trim(($r['firstName'] ?? '') . ' ' . ($r['lastName'] ?? '')),
            'grade'            => digitsOnly($r['grade'] ?? ''),
            'stream'           => $streams[(string)$sid] ?? '',
            'average'          => round($latest['avg'], 1),
            'trend'            => $trend,
            'latestExam'       => $history[$n - 1]['label'],
            'subjects'         => $subjects,
            'history'          => array_reverse(array_slice($history, -6)),   // newest first
        ];
    }

    usort($results, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    out(['success' => true, 'results' => $results]);
}

/* ════════════════════ ACTION: grade (class details) ════════════════════ */
if ($action === 'grade') {
    $grade = digitsOnly($_GET['grade'] ?? '');
    $term  = digitsOnly($_GET['term'] ?? '');
    $exam  = normExam($_GET['exam_type'] ?? '');
    $year  = digitsOnly($_GET['year'] ?? '');

    if ($grade === '' || $term === '' || $exam === '' || $year === '') {
        out(['success' => false, 'message' => 'Select grade, term, exam type and year.'], 400);
    }

    $subjectMap = getSubjectsForGrade((int)$grade);
    if (!$subjectMap) out(['success' => false, 'message' => 'No subjects are configured for this grade.'], 404);
    $subjectCodes = array_keys($subjectMap);

    $g = mysqli_real_escape_string($conn, $grade);
    $y = mysqli_real_escape_string($conn, $year);
    $res = mysqli_query($conn, "SELECT * FROM exam2 WHERE grade = '$g' AND year = '$y'");

    // Term/exam strings are inconsistent in exam2, so they are matched after normalising.
    $rows = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        if (normTerm($row['term'] ?? '') !== $term) continue;
        if (normExam($row['exam_type'] ?? '') !== $exam) continue;
        $sid = (string)($row['student_id'] ?? '');
        $k = $sid !== '' ? $sid : 'row' . count($rows);
        $rows[$k] = isset($rows[$k]) ? (mergeRows($rows[$k], $row, $subjectCodes) + $row) : $row;
    }

    if (!$rows) out(['success' => true, 'subjects' => [], 'summary' => null, 'top' => []]);

    $count = count($rows);
    $stat  = [];
    foreach ($subjectCodes as $c) $stat[$c] = ['sum' => 0, 'high' => 0, 'low' => PHP_INT_MAX];
    $bands = ['ee' => 0, 'me' => 0, 'ae' => 0, 'be' => 0];
    $totalSum = 0;
    $learners = [];

    foreach ($rows as $row) {
        $total = 0;
        foreach ($subjectCodes as $c) {
            $s = (int)($row[$c] ?? 0);
            $stat[$c]['sum'] += $s;
            if ($s > $stat[$c]['high']) $stat[$c]['high'] = $s;
            if ($s < $stat[$c]['low'])  $stat[$c]['low']  = $s;
            $total += $s;
        }
        $totalSum += $total;
        $bands[bandTier($total / count($subjectCodes))]++;
        $learners[] = [
            'name'             => trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')),
            'assessmentNumber' => (string)($row['Assesment'] ?? ''),
            'total'            => $total,
            'average'          => round($total / count($subjectCodes), 1),
        ];
    }

    $subjectsOut = [];
    foreach ($subjectCodes as $c) {
        $subjectsOut[] = [
            'subject' => $subjectMap[$c],
            'average' => round($stat[$c]['sum'] / $count, 1),
            'highest' => $stat[$c]['high'],
            'lowest'  => $stat[$c]['low'] === PHP_INT_MAX ? 0 : $stat[$c]['low'],
        ];
    }

    usort($learners, function ($a, $b) { return $b['total'] <=> $a['total']; });

    $classMean = round($totalSum / $count, 1);
    out([
        'success'  => true,
        'subjects' => $subjectsOut,
        'summary'  => [
            'studentCount'        => $count,
            'classMean'           => $classMean,
            'classMeanPerSubject' => round($classMean / count($subjectCodes), 1),
            'totalOutOf'          => count($subjectCodes) * 100,
            'bands'               => $bands,
            'examLabel'           => ($labels[$exam] ?? ucfirst($exam)) . ", Term $term $year",
        ],
        'top' => array_slice($learners, 0, 5),
    ]);
}

out(['success' => false, 'message' => 'Unknown action.'], 400);

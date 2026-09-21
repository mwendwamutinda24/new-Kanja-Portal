<?php
/**
 * GET /api/download_progress_csv.php
 *
 * CSV export mirroring progress_records.php's filtering (grade, term,
 * examType, subject) and its average/level calculation. One row per
 * student with a matched exam2 record.
 *
 * AUTH NOTE: the mobile app's "Download CSV" button opens this URL via
 * Linking.openURL() (OS browser / share sheet) rather than fetch(), so
 * it cannot attach an Authorization header the way progress_records.php
 * is called. To keep require_auth() in auth_check.php working
 * unmodified, a `token` query param is accepted as a fallback and
 * folded into $_SERVER['HTTP_AUTHORIZATION'] before auth_check.php
 * runs, so require_auth() sees it exactly as if it arrived as a header.
 */

if (!isset($_SERVER['HTTP_AUTHORIZATION']) && isset($_GET['token']) && trim((string) $_GET['token']) !== '') {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . trim((string) $_GET['token']);
}

mysqli_report(MYSQLI_REPORT_OFF);

require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

$session = require_auth();
if (!in_array($session['role'], ['hoi', 'Dhoi', 'teacher'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not authorized.';
    exit;
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

$studRes = mysqli_query($conn, "SELECT id, firstName, surname, Grade FROM Student WHERE $studentWhere");
if ($studRes === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Database error loading students: ' . mysqli_error($conn);
    exit;
}

$students = [];
$idsInOrder = [];
while ($row = mysqli_fetch_assoc($studRes)) {
    $students[$row['id']] = $row;
    $idsInOrder[] = $row['id'];
}

$rows = []; // ['learnerName', 'grade', 'term', 'average']

if (!empty($idsInOrder)) {
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
        $subjectCol = mysqli_real_escape_string($conn, $subject);
        $markRes = mysqli_query($conn, "SELECT student_id, term, `$subjectCol` AS score FROM exam2 WHERE $where");
    } else {
        $markRes = mysqli_query($conn, "SELECT * FROM exam2 WHERE $where");
    }

    if ($markRes === false) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Database error loading marks: ' . mysqli_error($conn);
        exit;
    }

    // Same reserved-column exclusion as progress_records.php, so
    // firstName/lastName/Assesment/grade never get averaged in as marks.
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
        $s = $students[$sid];

        $rows[] = [
            'learnerName' => trim($s['firstName'] . ' ' . $s['surname']),
            'grade'       => $s['Grade'],
            'term'        => $term !== '' ? $term : ($mrow['term'] ?? ''),
            'average'     => round($avg, 1),
        ];
    }
}

// Same thresholds as ClassRankings.php's bandFor() and the mobile app's
// bandFor(), so the level in the CSV matches what's shown on screen.
function levelCodeFor($avg) {
    if ($avg >= 75) return 'EE'; // Exceeding Expectation
    if ($avg >= 50) return 'ME'; // Meeting Expectation
    if ($avg >= 30) return 'AE'; // Approaching Expectation
    return 'BE';                 // Below Expectation
}

$filenameParts = array_filter([
    'progress',
    $grade !== '' ? 'grade' . $grade : null,
    $term !== '' ? preg_replace('/\s+/', '', $term) : null,
    $examType !== '' ? $examType : null,
    $subject !== '' ? $subject : null,
]);
$filename = implode('_', $filenameParts) . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$out = fopen('php://output', 'w');
// UTF-8 BOM so learner names with non-ASCII characters open correctly
// in Excel, which otherwise guesses the wrong encoding for CSVs.
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Learner', 'Grade', 'Term', 'Average', 'Level']);
foreach ($rows as $r) {
    fputcsv($out, [$r['learnerName'], $r['grade'], $r['term'], $r['average'], levelCodeFor($r['average'])]);
}
fclose($out);
exit;

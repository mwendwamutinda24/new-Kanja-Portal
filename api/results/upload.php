<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// FIX: conn.php path corrected to '../../conn.php' (see students.php).
require __DIR__ . '/../../conn.php';
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';
require __DIR__ . '/_save.php';
require __DIR__ . '/_input.php';

mysqli_report(MYSQLI_REPORT_OFF);

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$session = require_auth();
if (!in_array($session['role'], ['teacher', 'hoi'], true)) {
    respond(['success' => false, 'message' => 'Not authorized.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'POST required'], 405);
}

// Sent as JSON now (apiRequest's default). Expected shape:
// {
//   "grade": "4", "term": "1", "examType": "opener", "year": "2026",
//   "students": [
//     { "id": "17", "marks": { "MATH": "78", "ENG": "64" } },
//     { "id": "18", "marks": { "MATH": "55" } }
//   ]
// }
$input    = skp_body();
$grade    = trim((string) ($input['grade'] ?? ''));
$term     = trim((string) ($input['term'] ?? ''));
$examType = trim((string) ($input['examType'] ?? ''));
$year     = trim((string) ($input['year'] ?? ''));
$students = $input['students'] ?? null;

if ($grade === '' || $term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'grade, term, examType and year are all required'], 400);
}
if (!is_array($students) || count($students) === 0) {
    respond(['success' => false, 'message' => 'No student marks were submitted'], 400);
}

$validCodes = array_column(skp_subjects_for_grade($grade), 'code');

$saved   = 0;
$skipped = 0;
$errors  = [];

foreach ($students as $entry) {
    $studentId = trim((string) ($entry['id'] ?? ''));
    if ($studentId === '') continue;

    $marksIn = is_array($entry['marks'] ?? null) ? $entry['marks'] : [];

    // Keep only marks for subjects valid at this grade band, and only
    // fields that were actually filled in - blank means "leave alone",
    // not "clear this subject".
    $marks = [];
    foreach ($marksIn as $code => $val) {
        if (!in_array($code, $validCodes, true)) continue;
        $val = trim((string) $val);
        if ($val === '') continue;
        if (!is_numeric($val)) {
            $errors[] = "Student $studentId: invalid mark '$val' for $code";
            continue;
        }
        $marks[$code] = (int) $val;
    }

    if (count($marks) === 0) {
        // Nothing entered for this student this round - skip, matching
        // the web app's manual entry behaviour.
        $skipped++;
        continue;
    }

    $err = skp_save_marks($conn, $studentId, $grade, $term, $examType, $year, $marks);
    if ($err) {
        $errors[] = "Student $studentId: $err";
        continue;
    }
    $saved++;
}

// success reflects "the request was processed", not "every row was
// perfect" - per-row problems surface via saved/skipped/errors so the
// client can show a partial-success message instead of a hard failure.
respond([
    'success' => true,
    'saved'   => $saved,
    'skipped' => $skipped,
    'errors'  => $errors,
]);

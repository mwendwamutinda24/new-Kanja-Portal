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

$input = skp_body();

$grade    = trim((string) ($input['grade'] ?? ''));
$term     = trim((string) ($input['term'] ?? ''));
$examType = trim((string) ($input['examType'] ?? ''));
$year     = trim((string) ($input['year'] ?? ''));
$students = $input['students'] ?? null;

if ($grade === '' || $term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'grade, term, examType and year are all required'], 400);
}

if (!is_array($students) || count($students) === 0) {
    respond(['success' => false, 'message' => 'No students provided'], 422);
}

$subjects = skp_subjects_for_grade($grade);
$validCodes = array_column($subjects, 'code');

$gradeSafe    = mysqli_real_escape_string($conn, $grade);
$termSafe     = mysqli_real_escape_string($conn, $term);
$examTypeSafe = mysqli_real_escape_string($conn, $examType);
$yearSafe     = mysqli_real_escape_string($conn, $year);

$saved = 0;
$skipped = 0;
$errors = [];

foreach ($students as $entry) {
    $studentId = isset($entry['id']) ? (string) $entry['id'] : '';
    $marksIn   = is_array($entry['marks'] ?? null) ? $entry['marks'] : [];

    if ($studentId === '') {
        $errors[] = 'Skipped a row with no student id';
        $skipped++;
        continue;
    }

    // Only keep known subject codes with a genuinely non-blank value — a
    // student with all-blank marks is skipped entirely, not saved as a
    // zeroed-out row, same as the web app's manual-entry behaviour.
    $marks = [];
    foreach ($marksIn as $code => $val) {
        if (!in_array($code, $validCodes, true)) continue;
        if ($val === null || $val === '') continue;
        if (!is_numeric($val) || $val < 0 || $val > 100) {
            $errors[] = "Invalid mark for student $studentId, subject $code: $val";
            continue;
        }
        $marks[$code] = (int) $val;
    }

    if (count($marks) === 0) {
        $skipped++;
        continue;
    }

    $studentIdSafe = mysqli_real_escape_string($conn, $studentId);

    // Confirm the student actually exists (and belongs to this grade)
    // before writing anything.
    $chk = mysqli_query($conn, "SELECT id FROM Student WHERE id = '$studentIdSafe' AND Grade = '$gradeSafe' LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) === 0) {
        $errors[] = "Student $studentId not found in Grade $grade";
        $skipped++;
        continue;
    }

    // CONFIRM: column names (studentId, examTerm, examType, examYear) —
    // adjust to match the real exam2 schema once shared.
    $existing = mysqli_query($conn, "
        SELECT id FROM exam2
        WHERE studentId = '$studentIdSafe'
          AND examTerm = '$termSafe'
          AND examType = '$examTypeSafe'
          AND examYear = '$yearSafe'
        LIMIT 1
    ");

    if ($existing === false) {
        $errors[] = "Database error checking existing marks for student $studentId: " . mysqli_error($conn);
        $skipped++;
        continue;
    }

    if (mysqli_num_rows($existing) > 0) {
        // UPDATE only the subject columns actually submitted, so subjects
        // not in this payload keep whatever was there before.
        $setParts = [];
        foreach ($marks as $code => $val) {
            $setParts[] = "`$code` = $val";
        }
        $setSql = implode(', ', $setParts);
        $row = mysqli_fetch_assoc($existing);
        $examId = (int) $row['id'];

        $ok = mysqli_query($conn, "UPDATE exam2 SET $setSql WHERE id = $examId");
    } else {
        $cols = array_merge(['studentId', 'examTerm', 'examType', 'examYear'], array_keys($marks));
        $vals = array_merge(
            ["'$studentIdSafe'", "'$termSafe'", "'$examTypeSafe'", "'$yearSafe'"],
            array_values($marks)
        );
        $colsSql = implode(', ', array_map(fn($c) => "`$c`", $cols));
        $valsSql = implode(', ', $vals);

        $ok = mysqli_query($conn, "INSERT INTO exam2 ($colsSql) VALUES ($valsSql)");
    }

    if ($ok) {
        $saved++;
    } else {
        $errors[] = "Failed to save marks for student $studentId: " . mysqli_error($conn);
        $skipped++;
    }
}

respond([
    'success' => true,
    'saved'   => $saved,
    'skipped' => $skipped,
    'errors'  => $errors,
]);

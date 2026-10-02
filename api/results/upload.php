<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

ini_set('display_errors', '0');

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Any uncaught error or fatal comes back as JSON, so the phone shows the
// real reason instead of an empty / HTML 500 page.
set_exception_handler(function ($e) {
    respond(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'message' => 'Fatal: ' . $err['message']]);
    }
});

require __DIR__ . '/../../conn.php';
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';
require __DIR__ . '/_input.php';

mysqli_report(MYSQLI_REPORT_OFF);

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

$subjects   = skp_subjects_for_grade($grade);
$validCodes = array_column($subjects, 'code');

/*
 * exam2 schema (confirmed via phpMyAdmin):
 *   id, student_id, Assesment, firstName, lastName,
 *   math, eng, kisw, sst, scie, ca, agri, re, pretec,
 *   grade, term, exam_type, year
 *
 * exam2.term is stored as "Term 1" / "Term 2" / "Term 3" (same as the web
 * upload flow and ViewResults.php). The app sends a bare digit, so we
 * normalise to "Term N" for writes, and match BOTH formats when looking
 * for an existing row, so older rows saved either way are still found
 * and updated instead of duplicated.
 */
$termDigits = preg_replace('/[^0-9]/', '', $term);
$termLabel  = $termDigits !== '' ? "Term $termDigits" : $term;

$gradeSafe     = mysqli_real_escape_string($conn, $grade);
$termLabelSafe = mysqli_real_escape_string($conn, $termLabel);
$examTypeSafe  = mysqli_real_escape_string($conn, $examType);
$yearSafe      = mysqli_real_escape_string($conn, $year);

$termCandidates = ["'$termLabelSafe'"];
if ($termDigits !== '') {
    $termCandidates[] = "'" . mysqli_real_escape_string($conn, $termDigits) . "'";
}
$termInClause = implode(',', array_unique($termCandidates));

/*
 * Look the student up. MySQL on Linux (Render / Railway) treats table names
 * as case-sensitive while Windows does not, so try "Student" and then
 * "student". Returns the result set, or false with the real DB error in $err.
 */
function skp_find_student($conn, $idInt, $gradeSafe, &$err) {
    $err = '';
    foreach (['Student', 'student'] as $table) {
        $res = mysqli_query(
            $conn,
            "SELECT id, Assesment, firstName, surname FROM `$table`
             WHERE id = $idInt AND Grade = '$gradeSafe' LIMIT 1"
        );
        if ($res !== false) return $res;
        $err = mysqli_error($conn);
    }
    return false;
}

$saved = 0;
$skipped = 0;
$errors = [];

foreach ($students as $entry) {
    $studentId = isset($entry['id']) ? (string) $entry['id'] : '';
    $marksIn   = is_array($entry['marks'] ?? null) ? $entry['marks'] : [];

    if ($studentId === '' || !ctype_digit($studentId)) {
        $errors[] = 'Skipped a row with no valid student id';
        $skipped++;
        continue;
    }
    $studentIdInt = (int) $studentId;

    // Only keep known subject codes with a genuinely non-blank value. A
    // student with all-blank marks is skipped entirely, not saved as a
    // zeroed-out row.
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

    // Confirm the student exists in this grade, and grab the identity
    // fields exam2 also stores (Assesment, firstName, lastName).
    $lookupErr = '';
    $chk = skp_find_student($conn, $studentIdInt, $gradeSafe, $lookupErr);
    if ($chk === false) {
        $errors[] = "Student lookup failed for $studentId: $lookupErr";
        $skipped++;
        continue;
    }
    if (mysqli_num_rows($chk) === 0) {
        $errors[] = "Student $studentId not found in Grade $grade";
        $skipped++;
        continue;
    }
    $stu = mysqli_fetch_assoc($chk);

    $existing = mysqli_query($conn, "
        SELECT id FROM exam2
        WHERE student_id = $studentIdInt
          AND grade = '$gradeSafe'
          AND term IN ($termInClause)
          AND exam_type = '$examTypeSafe'
          AND year = '$yearSafe'
        LIMIT 1
    ");

    if ($existing === false) {
        $errors[] = "Database error checking existing marks for student $studentId: " . mysqli_error($conn);
        $skipped++;
        continue;
    }

    if (mysqli_num_rows($existing) > 0) {
        // UPDATE only the subject columns actually submitted, so subjects
        // not in this payload keep whatever was there before. Term is also
        // normalised to "Term N" in case the old row used the bare digit.
        $setParts = ["term = '$termLabelSafe'"];
        foreach ($marks as $code => $val) {
            $setParts[] = "`$code` = $val";
        }
        $setSql = implode(', ', $setParts);
        $row    = mysqli_fetch_assoc($existing);
        $examId = (int) $row['id'];

        $ok = mysqli_query($conn, "UPDATE exam2 SET $setSql WHERE id = $examId");
    } else {
        $assesSafe = mysqli_real_escape_string($conn, (string) ($stu['Assesment'] ?? ''));
        $firstSafe = mysqli_real_escape_string($conn, (string) ($stu['firstName'] ?? ''));
        $lastSafe  = mysqli_real_escape_string($conn, (string) ($stu['surname'] ?? ''));

        $cols = array_merge(
            ['student_id', 'Assesment', 'firstName', 'lastName', 'grade', 'term', 'exam_type', 'year'],
            array_keys($marks)
        );
        $vals = array_merge(
            [
                $studentIdInt,
                "'$assesSafe'",
                "'$firstSafe'",
                "'$lastSafe'",
                "'$gradeSafe'",
                "'$termLabelSafe'",
                "'$examTypeSafe'",
                "'$yearSafe'",
            ],
            array_values($marks)
        );
        $colsSql = implode(', ', array_map(function ($c) { return "`$c`"; }, $cols));
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

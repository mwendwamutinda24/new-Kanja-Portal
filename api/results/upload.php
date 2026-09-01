<?php
/**
 * POST /results/upload.php
 * Body: {
 *   grade: "5", term: "1", examType: "opener", year: "2026",
 *   students: [ { id: "123", marks: { MATH: "78", ENG: "65", ... } }, ... ]
 * }
 *
 * Mirrors the web app's manual-entry save behaviour:
 *   - a student with NO marks in the payload (all blank) is skipped
 *     entirely, not saved as a zeroed-out row
 *   - one exam2 row per student/grade/term/examType/year — UPDATE if
 *     it already exists, INSERT if not (never a blind duplicate INSERT)
 *
 * The client already filters out deselected learners and blank marks
 * before sending (see submitManual() in UploadResults.tsx), but this
 * endpoint re-validates rather than trusting that, since it's a
 * public API surface.
 *
 * Response: { success, saved, skipped, errors: string[] }
 */

require_once __DIR__ . '/_bootstrap.php';

$grade    = requireField($BODY, 'grade');
$term     = requireField($BODY, 'term');
$examType = requireField($BODY, 'examType');
$year     = requireField($BODY, 'year');
$students = $BODY['students'] ?? null;

if (!is_array($students) || count($students) === 0) {
    json_error('No students provided', 422);
}

if (!ctype_digit($grade) || (int)$grade < 1 || (int)$grade > 9) {
    json_error('Invalid grade', 422);
}

$subjects = getSubjectsForGrade((int)$grade); // CONFIRM: see subjects.php note
$validCodes = array_map(fn($s) => $s['code'], $subjects);

$gradeSafe    = mysqli_real_escape_string($conn, $grade);
$termSafe     = mysqli_real_escape_string($conn, $term);
$examTypeSafe = mysqli_real_escape_string($conn, $examType);
$yearSafe     = mysqli_real_escape_string($conn, $year);

$saved = 0;
$skipped = 0;
$errors = [];

foreach ($students as $entry) {
    $studentId = isset($entry['id']) ? (string)$entry['id'] : '';
    $marksIn   = is_array($entry['marks'] ?? null) ? $entry['marks'] : [];

    if ($studentId === '') {
        $errors[] = 'Skipped a row with no student id';
        $skipped++;
        continue;
    }

    // Only keep known subject codes with a genuinely non-blank value —
    // same "skip if all blank" rule as the web app.
    $marks = [];
    foreach ($marksIn as $code => $val) {
        if (!in_array($code, $validCodes, true)) continue;
        if ($val === null || $val === '') continue;
        if (!is_numeric($val) || $val < 0 || $val > 100) {
            $errors[] = "Invalid mark for student $studentId, subject $code: $val";
            continue;
        }
        $marks[$code] = (int)$val;
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

    // CONFIRM: column names (studentId, examTerm, examType, examYear)
    // — adjust to match the real exam2 schema, same as students.php.
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
        // UPDATE only the subject columns that were actually submitted,
        // so subjects not in this payload keep whatever was there before.
        $setParts = [];
        foreach ($marks as $code => $val) {
            $setParts[] = "`$code` = $val";
        }
        $setSql = implode(', ', $setParts);
        $row = mysqli_fetch_assoc($existing);
        $examId = (int)$row['id'];

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

json_ok([
    'saved'   => $saved,
    'skipped' => $skipped,
    'errors'  => $errors,
]);

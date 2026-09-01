<?php
/**
 * POST /results/students.php
 * Body: { grade: "5", term: "1", examType: "opener", year: "2026" }
 *
 * Returns the grade's roster (same fields as the web app's AJAX row
 * builder in UploadResults.php: id, Assesment, firstName, surname)
 * plus each student's already-saved marks for the given
 * term/examType/year, if any exist in exam2 — so the mobile form
 * pre-fills exactly like the web page does when you switch term/exam/
 * year for a grade that already has marks.
 *
 * term/examType/year are optional here (roster still loads without
 * them) but marks will all come back null until all three are given,
 * matching what upload.php requires to save.
 *
 * Response: { success, grade, subjects, students: [{id, assesment,
 *             firstName, surname, marks: {code: number|null}}],
 *             warning? }
 */

require_once __DIR__ . '/_bootstrap.php';

$grade    = requireField($BODY, 'grade');
$term     = $BODY['term'] ?? '';
$examType = $BODY['examType'] ?? '';
$year     = $BODY['year'] ?? '';

if (!ctype_digit($grade) || (int)$grade < 1 || (int)$grade > 9) {
    json_error('Invalid grade', 422);
}

$subjects = getSubjectsForGrade((int)$grade); // CONFIRM: see subjects.php note
$subjectCodes = array_map(fn($s) => $s['code'], $subjects);

$gradeSafe = mysqli_real_escape_string($conn, $grade);

$res = mysqli_query($conn, "
    SELECT id, Assesment, firstName, surname
    FROM Student
    WHERE Grade = '$gradeSafe'
    ORDER BY firstName, surname
");

if ($res === false) {
    json_error('Database error loading students: ' . mysqli_error($conn), 500);
}

$students = [];
$idsInOrder = [];
while ($row = mysqli_fetch_assoc($res)) {
    $students[$row['id']] = [
        'id'        => $row['id'],
        'assesment' => $row['Assesment'],
        'firstName' => $row['firstName'],
        'surname'   => $row['surname'],
        'marks'     => array_fill_keys($subjectCodes, null),
    ];
    $idsInOrder[] = $row['id'];
}

$warning = null;

// Only look up existing marks once we actually have enough to
// identify a unique exam row — otherwise every student's marks
// legitimately stay null (no exam picked yet).
if ($term !== '' && $examType !== '' && $year !== '' && count($idsInOrder) > 0) {
    $termSafe     = mysqli_real_escape_string($conn, $term);
    $examTypeSafe = mysqli_real_escape_string($conn, $examType);
    $yearSafe     = mysqli_real_escape_string($conn, $year);

    // CONFIRM: column names below (studentId, examTerm, examType,
    // examYear) — adjust to match the real exam2 schema. Given the
    // documented "inconsistent term string formats" quirk in exam2,
    // you may need to relax examTerm to also match e.g. 'Term 1'.
    $subjCols = implode(', ', array_map(fn($c) => "`$c`", $subjectCodes));
    $idsSafe = implode(',', array_map(fn($id) => "'" . mysqli_real_escape_string($conn, $id) . "'", $idsInOrder));

    $markRes = mysqli_query($conn, "
        SELECT studentId, $subjCols
        FROM exam2
        WHERE studentId IN ($idsSafe)
          AND examTerm = '$termSafe'
          AND examType = '$examTypeSafe'
          AND examYear = '$yearSafe'
    ");

    if ($markRes === false) {
        // Don't fail the whole roster load over this — surface it as
        // a warning so the app can still show the (blank) roster.
        $warning = 'Could not load existing marks: ' . mysqli_error($conn);
    } else {
        while ($mrow = mysqli_fetch_assoc($markRes)) {
            $sid = $mrow['studentId'];
            if (!isset($students[$sid])) continue;
            foreach ($subjectCodes as $code) {
                $val = $mrow[$code] ?? null;
                $students[$sid]['marks'][$code] = ($val === null || $val === '') ? null : $val;
            }
        }
    }
}

$response = [
    'grade'    => $grade,
    'subjects' => array_values($subjects),
    'students' => array_values($students),
];
if ($warning) $response['warning'] = $warning;

json_ok($response);

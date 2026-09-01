<?php
/**
 * POST /results/upload_bulk.php
 * multipart/form-data:
 *   grade, term, examType, year   (text fields)
 *   marksFile                     (the completed .csv, from
 *                                  generate_template.php's CSV output)
 *
 * Note: the mobile app only offers CSV (see openTemplateDownload() in
 * UploadResults.tsx, which links straight to generate_template.php on
 * the web host and the "helperText" telling users to keep it as .csv).
 * The web app additionally supports .xlsx uploads via PhpSpreadsheet —
 * if you want that here too, reuse the same PhpSpreadsheet autoloader
 * mentioned in your notes rather than duplicating parsing logic.
 *
 * Matching rule (same as the web app's bulk upload): match each row to
 * a student PRIMARILY by First Name + Surname within the selected
 * grade; Assessment No is only used to break a tie between two
 * same-named learners in that grade. Existing exam2 rows for that
 * student/term/year/examType are updated in place; otherwise a new row
 * is created. Never a blind duplicate INSERT.
 *
 * Response: { success, saved, skipped, errors: string[] }
 */

require_once __DIR__ . '/_bootstrap.php';

// This endpoint receives multipart/form-data, so text fields land in
// $_POST directly (readBody() in _bootstrap.php already falls back to
// $_POST when the body isn't JSON, but be explicit here since a file
// is involved).
$grade    = requireField($_POST, 'grade');
$term     = requireField($_POST, 'term');
$examType = requireField($_POST, 'examType');
$year     = requireField($_POST, 'year');

if (!ctype_digit($grade) || (int)$grade < 1 || (int)$grade > 9) {
    json_error('Invalid grade', 422);
}

if (!isset($_FILES['marksFile']) || $_FILES['marksFile']['error'] !== UPLOAD_ERR_OK) {
    json_error('No file was uploaded', 422);
}

$tmpPath = $_FILES['marksFile']['tmp_name'];
$origName = $_FILES['marksFile']['name'];

if (!preg_match('/\.csv$/i', $origName)) {
    json_error('Only .csv files are supported by this endpoint', 422);
}

$handle = fopen($tmpPath, 'r');
if ($handle === false) {
    json_error('Could not read uploaded file', 500);
}

$header = fgetcsv($handle);
if ($header === false) {
    fclose($handle);
    json_error('CSV file is empty', 422);
}

// Normalize header names for matching: trim + lowercase.
$normHeader = array_map(fn($h) => strtolower(trim((string)$h)), $header);

$colIndex = function (array $candidates) use ($normHeader): ?int {
    foreach ($candidates as $cand) {
        $idx = array_search($cand, $normHeader, true);
        if ($idx !== false) return $idx;
    }
    return null;
};

$firstNameIdx = $colIndex(['first name', 'firstname']);
$surnameIdx   = $colIndex(['surname', 'last name', 'lastname']);
$assessIdx    = $colIndex(['assess. no', 'assessment no', 'assesment', 'assess no', 'assessment']);

if ($firstNameIdx === null || $surnameIdx === null) {
    fclose($handle);
    json_error('CSV must include First Name and Surname columns', 422);
}

$subjects = getSubjectsForGrade((int)$grade); // CONFIRM: see subjects.php note
$validCodes = array_map(fn($s) => $s['code'], $subjects);

// Map each subject code to whichever CSV column matches it (by code
// or by label, case-insensitively) — the template's header naming may
// use either depending on how generate_template.php labels columns.
$subjectColIdx = [];
foreach ($subjects as $subj) {
    $idx = $colIndex([strtolower($subj['code']), strtolower($subj['label'])]);
    if ($idx !== null) $subjectColIdx[$subj['code']] = $idx;
}

if (count($subjectColIdx) === 0) {
    fclose($handle);
    json_error('CSV has no recognizable subject columns for this grade', 422);
}

// ── Load this grade's roster, grouped by "firstname|surname" so we
// can detect same-name collisions and fall back to Assessment No. ──
$gradeSafe = mysqli_real_escape_string($conn, $grade);
$rosterRes = mysqli_query($conn, "
    SELECT id, Assesment, firstName, surname
    FROM Student
    WHERE Grade = '$gradeSafe'
");
if ($rosterRes === false) {
    fclose($handle);
    json_error('Database error loading roster: ' . mysqli_error($conn), 500);
}

$byName = []; // 'firstname|surname' (lowercase, trimmed) => [ student rows ]
while ($row = mysqli_fetch_assoc($rosterRes)) {
    $key = strtolower(trim($row['firstName'])) . '|' . strtolower(trim($row['surname']));
    $byName[$key][] = $row;
}

$termSafe     = mysqli_real_escape_string($conn, $term);
$examTypeSafe = mysqli_real_escape_string($conn, $examType);
$yearSafe     = mysqli_real_escape_string($conn, $year);

$saved = 0;
$skipped = 0;
$errors = [];
$rowNum = 1; // header was row 0

while (($line = fgetcsv($handle)) !== false) {
    $rowNum++;

    // Skip fully blank trailing lines some spreadsheet apps leave in.
    if (count(array_filter($line, fn($c) => trim((string)$c) !== '')) === 0) continue;

    $firstName = trim((string)($line[$firstNameIdx] ?? ''));
    $surname   = trim((string)($line[$surnameIdx] ?? ''));
    $assess    = $assessIdx !== null ? trim((string)($line[$assessIdx] ?? '')) : '';

    if ($firstName === '' || $surname === '') {
        $errors[] = "Row $rowNum: missing First Name or Surname, skipped";
        $skipped++;
        continue;
    }

    $key = strtolower($firstName) . '|' . strtolower($surname);
    $candidates = $byName[$key] ?? [];

    $student = null;
    if (count($candidates) === 1) {
        $student = $candidates[0];
    } elseif (count($candidates) > 1) {
        // Tie-break by Assessment No.
        foreach ($candidates as $cand) {
            if ($assess !== '' && (string)$cand['Assesment'] === $assess) {
                $student = $cand;
                break;
            }
        }
        if ($student === null) {
            $errors[] = "Row $rowNum: multiple learners named \"$firstName $surname\" in Grade $grade and Assessment No didn't disambiguate — skipped";
            $skipped++;
            continue;
        }
    } else {
        $errors[] = "Row $rowNum: no learner named \"$firstName $surname\" found in Grade $grade — skipped";
        $skipped++;
        continue;
    }

    $marks = [];
    foreach ($subjectColIdx as $code => $idx) {
        $val = trim((string)($line[$idx] ?? ''));
        if ($val === '') continue;
        if (!is_numeric($val) || $val < 0 || $val > 100) {
            $errors[] = "Row $rowNum ($firstName $surname): invalid mark \"$val\" for $code, skipped that subject";
            continue;
        }
        $marks[$code] = (int)$val;
    }

    if (count($marks) === 0) {
        $skipped++;
        continue;
    }

    $studentIdSafe = mysqli_real_escape_string($conn, $student['id']);

    // CONFIRM: column names (studentId, examTerm, examType, examYear)
    // — same schema assumption as upload.php / students.php.
    $existing = mysqli_query($conn, "
        SELECT id FROM exam2
        WHERE studentId = '$studentIdSafe'
          AND examTerm = '$termSafe'
          AND examType = '$examTypeSafe'
          AND examYear = '$yearSafe'
        LIMIT 1
    ");

    if ($existing === false) {
        $errors[] = "Row $rowNum ($firstName $surname): database error checking existing marks: " . mysqli_error($conn);
        $skipped++;
        continue;
    }

    if (mysqli_num_rows($existing) > 0) {
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
        $errors[] = "Row $rowNum ($firstName $surname): failed to save — " . mysqli_error($conn);
        $skipped++;
    }
}

fclose($handle);

json_ok([
    'saved'   => $saved,
    'skipped' => $skipped,
    'errors'  => $errors,
]);

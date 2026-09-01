<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/../../conn.php';
require __DIR__ . '/../auth_check.php';
require __DIR__ . '/_config.php';
// NOTE: this endpoint is multipart/form-data (it carries a file), so text
// fields are read straight from $_POST rather than through skp_body() —
// _input.php isn't required here for that reason. If skp_body() already
// falls back to $_POST for non-JSON requests, requiring it and calling it
// would work too; left out to avoid assuming that behaviour.

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

$grade    = trim((string) ($_POST['grade'] ?? ''));
$term     = trim((string) ($_POST['term'] ?? ''));
$examType = trim((string) ($_POST['examType'] ?? ''));
$year     = trim((string) ($_POST['year'] ?? ''));

if ($grade === '' || $term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'grade, term, examType and year are all required'], 400);
}

if (!isset($_FILES['marksFile']) || $_FILES['marksFile']['error'] !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'message' => 'No file was uploaded'], 422);
}

$tmpPath  = $_FILES['marksFile']['tmp_name'];
$origName = $_FILES['marksFile']['name'];

if (!preg_match('/\.csv$/i', $origName)) {
    respond(['success' => false, 'message' => 'Only .csv files are supported by this endpoint'], 422);
}

$handle = fopen($tmpPath, 'r');
if ($handle === false) {
    respond(['success' => false, 'message' => 'Could not read uploaded file'], 500);
}

$header = fgetcsv($handle);
if ($header === false) {
    fclose($handle);
    respond(['success' => false, 'message' => 'CSV file is empty'], 422);
}

$normHeader = array_map(fn($h) => strtolower(trim((string) $h)), $header);

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
    respond(['success' => false, 'message' => 'CSV must include First Name and Surname columns'], 422);
}

$subjects = skp_subjects_for_grade($grade);

// Map each subject code to whichever CSV column matches it (by code or by
// label, case-insensitively) — the template's header naming may use
// either depending on how generate_template.php labels columns.
$subjectColIdx = [];
foreach ($subjects as $subj) {
    $idx = $colIndex([strtolower($subj['code']), strtolower($subj['label'])]);
    if ($idx !== null) $subjectColIdx[$subj['code']] = $idx;
}

if (count($subjectColIdx) === 0) {
    fclose($handle);
    respond(['success' => false, 'message' => 'CSV has no recognizable subject columns for this grade'], 422);
}

// Load this grade's roster, grouped by "firstname|surname" so we can
// detect same-name collisions and fall back to Assessment No.
$gradeSafe = mysqli_real_escape_string($conn, $grade);
$rosterRes = mysqli_query($conn, "
    SELECT id, Assesment, firstName, surname
    FROM Student
    WHERE Grade = '$gradeSafe'
");
if ($rosterRes === false) {
    fclose($handle);
    respond(['success' => false, 'message' => 'Database error loading roster: ' . mysqli_error($conn)], 500);
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

    if (count(array_filter($line, fn($c) => trim((string) $c) !== '')) === 0) continue;

    $firstName = trim((string) ($line[$firstNameIdx] ?? ''));
    $surname   = trim((string) ($line[$surnameIdx] ?? ''));
    $assess    = $assessIdx !== null ? trim((string) ($line[$assessIdx] ?? '')) : '';

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
        foreach ($candidates as $cand) {
            if ($assess !== '' && (string) $cand['Assesment'] === $assess) {
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
        $val = trim((string) ($line[$idx] ?? ''));
        if ($val === '') continue;
        if (!is_numeric($val) || $val < 0 || $val > 100) {
            $errors[] = "Row $rowNum ($firstName $surname): invalid mark \"$val\" for $code, skipped that subject";
            continue;
        }
        $marks[$code] = (int) $val;
    }

    if (count($marks) === 0) {
        $skipped++;
        continue;
    }

    $studentIdSafe = mysqli_real_escape_string($conn, $student['id']);

    // CONFIRM: column names (studentId, examTerm, examType, examYear) —
    // same schema assumption as upload.php / students.php.
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
        $errors[] = "Row $rowNum ($firstName $surname): failed to save — " . mysqli_error($conn);
        $skipped++;
    }
}

fclose($handle);

respond([
    'success' => true,
    'saved'   => $saved,
    'skipped' => $skipped,
    'errors'  => $errors,
]);

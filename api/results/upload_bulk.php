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

// NOTE: this endpoint is multipart/form-data (file upload), so it's sent
// via a plain fetch() with FormData on the client rather than through
// apiRequest() (which always JSON.stringifies). Fields land in $_POST as
// usual for multipart requests, and the file lands in $_FILES.
$grade    = trim($_POST['grade'] ?? '');
$term     = trim($_POST['term'] ?? '');
$examType = trim($_POST['examType'] ?? '');
$year     = trim($_POST['year'] ?? '');

if ($grade === '' || $term === '' || $examType === '' || $year === '') {
    respond(['success' => false, 'message' => 'grade, term, examType and year are all required'], 400);
}
if (!isset($_FILES['marksFile']) || $_FILES['marksFile']['error'] !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'message' => 'No file uploaded, or upload failed'], 400);
}

$validSubjects = skp_subjects_for_grade($grade);
$codeByLabel   = [];
foreach ($validSubjects as $s) {
    $codeByLabel[strtolower(trim($s['label']))] = $s['code'];
}

$handle = fopen($_FILES['marksFile']['tmp_name'], 'r');
if ($handle === false) {
    respond(['success' => false, 'message' => 'Could not read uploaded file'], 500);
}

$header = fgetcsv($handle);
if ($header === false) {
    fclose($handle);
    respond(['success' => false, 'message' => 'CSV file is empty'], 400);
}

// ASSUMPTION: header row has an "Assessment No" style column plus one
// column per subject using the subject's display label (matching whatever
// generate_template.php currently writes as headers). If your template's
// headers differ, tell me the exact header text and I'll adjust the match.
$assessCol   = null;
$subjectCols = []; // colIndex => subjectCode

foreach ($header as $i => $h) {
    $hNorm = strtolower(trim($h));
    if (in_array($hNorm, ['assessment no', 'assessment number', 'assesment', 'assess. no'], true)) {
        $assessCol = $i;
    } elseif (isset($codeByLabel[$hNorm])) {
        $subjectCols[$i] = $codeByLabel[$hNorm];
    }
}

if ($assessCol === null) {
    fclose($handle);
    respond(['success' => false, 'message' => 'Could not find an "Assessment No" column in the file'], 400);
}
if (count($subjectCols) === 0) {
    fclose($handle);
    respond(['success' => false, 'message' => 'No recognized subject columns found in the file'], 400);
}

$gradeSafe = mysqli_real_escape_string($conn, $grade);

$saved   = 0;
$skipped = 0;
$errors  = [];
$rowNum  = 1; // header was row 1

while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;
    $assessment = trim($row[$assessCol] ?? '');
    if ($assessment === '') continue;

    $assessSafe = mysqli_real_escape_string($conn, $assessment);
    $sq = "SELECT id FROM Student WHERE Assesment = '$assessSafe' AND Grade = '$gradeSafe' LIMIT 1";
    $sres = mysqli_query($conn, $sq);
    if ($sres === false || mysqli_num_rows($sres) === 0) {
        $errors[] = "Row $rowNum: no student found with Assessment No '$assessment' in Grade $grade";
        continue;
    }
    $studentId = (string) mysqli_fetch_assoc($sres)['id'];

    $marks = [];
    foreach ($subjectCols as $colIdx => $code) {
        $val = trim($row[$colIdx] ?? '');
        if ($val === '') continue;
        if (!is_numeric($val)) {
            $errors[] = "Row $rowNum: invalid mark '$val' for $code";
            continue;
        }
        $marks[$code] = (int) $val;
    }

    if (count($marks) === 0) {
        $skipped++;
        continue;
    }

    $err = skp_save_marks($conn, $studentId, $grade, $term, $examType, $year, $marks);
    if ($err) {
        $errors[] = "Row $rowNum ($assessment): $err";
        continue;
    }
    $saved++;
}

fclose($handle);

respond([
    'success' => true,
    'saved'   => $saved,
    'skipped' => $skipped,
    'errors'  => $errors,
]);

<?php
// ============================================================
// upload_excel_marks.php — Stephen Kanja School Management System
//
// Receives a completed marks CSV, matches its subject columns by
// header name against the same canonical subject list used across
// the whole system, and saves each mark into exam2.
//
// CSV-only, pure PHP — no Composer / PhpSpreadsheet dependency.
// This system runs on InfinityFree's free tier, which has no
// shell/Composer access, so vendor/ can never be installed there.
// An earlier PhpSpreadsheet-based version of this script failed
// with an HTTP 500 the moment it hit require vendor/autoload.php.
// str_getcsv() is built into core PHP, so this needs nothing extra
// to run — the trade-off is that it only accepts .csv, not native
// .xlsx/.xls (the paired generate_template.php only ever produces
// .csv for exactly this reason, so the round trip stays consistent).
//
// Insert/update logic (the important part):
//   For every (student, subject) pair found in the file, first
//   check whether a row already exists in exam2 for that
//   Assesment + grade + term + year + exam_type combination.
//     - If it exists  → UPDATE just that one subject column.
//     - If it doesn't → INSERT a new row with just that subject
//                        filled in (other subjects default to 0,
//                        to be filled by a later upload/entry).
//   This is deliberately NOT a single blind INSERT per row, because
//   that would create duplicate exam2 rows per upload (the same
//   problem already seen with the term-format inconsistency this
//   system has elsewhere) — checking first keeps one logical row
//   per student/term/year/exam type no matter how many separate
//   subject uploads are done for it over time.
// ============================================================
session_start();
include 'conn.php';

function redirect_with_message($msg, $type = 'success') {
    header('Location: UploadResults.php?excel_msg=' . urlencode($msg) . '&excel_status=' . $type);
    exit;
}

// ── Canonical subject list — must match generate_template.php ──
$subject_map = [
    'math'   => 'Mathematics',
    'eng'    => 'English',
    'kisw'   => 'Kiswahili',
    'sst'    => 'Social Studies',
    'scie'   => 'Science',
    'ca'     => 'CA',
    'agri'   => 'Agriculture',
    're'     => 'RE',
    'pretec' => 'Pre-Technical',
];
// Header text (lowercased) => subject code. Accepts either the full
// display label ("Mathematics") or the short code itself ("math"),
// so a teacher who retypes headers slightly differently isn't blocked.
$label_to_code = [];
foreach ($subject_map as $code => $label) {
    $label_to_code[strtolower($label)] = $code;
    $label_to_code[strtolower($code)] = $code;
}
$assess_header_aliases = ['assessment no', 'assessment number', 'assesment', 'assess. no', 'assess no'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['marksFile'])) {
    redirect_with_message('No file was uploaded.', 'error');
}

// ── Read + validate the shared context fields ──
$grade    = isset($_POST['grade']) ? (int)$_POST['grade'] : 0;
$termNum  = isset($_POST['term']) ? preg_replace('/[^0-9]/', '', $_POST['term']) : '';
$year     = isset($_POST['year']) ? (int)$_POST['year'] : 0;
$examType = isset($_POST['examType']) ? trim($_POST['examType']) : '';
$allowedExamTypes = ['opener', 'midterm', 'endterm'];

if (!$grade || $termNum === '' || !$year || !in_array($examType, $allowedExamTypes, true)) {
    redirect_with_message('Grade, term, exam type, and year are all required before uploading.', 'error');
}
// Write new rows in one canonical format going forward, rather than
// perpetuating the "Term 1" vs "1" inconsistency documented elsewhere.
$termLabel = "Term $termNum";

// ── Validate the uploaded file ──
$file = $_FILES['marksFile'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_with_message('The upload failed — please try again.', 'error');
}
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    redirect_with_message(
        'Please upload a .csv file. This server can\'t parse native .xlsx/.xls files — ' .
        'in Excel use File → Save As → CSV (Comma delimited), then upload that file instead.',
        'error'
    );
}

// ── Read the file ourselves (no library) ──
$content = file_get_contents($file['tmp_name']);
if ($content === false || trim($content) === '') {
    redirect_with_message('The uploaded file is empty or unreadable.', 'error');
}

// Strip a UTF-8 BOM if present (Excel adds one on Windows)
$content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
// Normalize Windows/Mac line endings to \n
$content = str_replace(["\r\n", "\r"], "\n", $content);

$lines = array_values(array_filter(explode("\n", $content), function ($l) {
    return trim($l) !== '';
}));

if (count($lines) < 2) {
    redirect_with_message('The uploaded file has no data rows.', 'error');
}

// Sniff the delimiter from the header row (comma vs semicolon —
// some regional Excel installs export CSV with semicolons).
$firstLine = $lines[0];
$delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

$headerFields = str_getcsv(array_shift($lines), $delimiter);

$assessColIdx = null;
$subjectCols = []; // subject code => column index
foreach ($headerFields as $idx => $headerText) {
    $h = strtolower(trim((string)$headerText));
    if ($h === '') continue;
    if (in_array($h, $assess_header_aliases, true)) {
        $assessColIdx = $idx;
    } elseif (isset($label_to_code[$h])) {
        $subjectCols[$label_to_code[$h]] = $idx;
    }
}

if ($assessColIdx === null) {
    redirect_with_message('Could not find an "Assessment No" column in the uploaded file.', 'error');
}
if (empty($subjectCols)) {
    redirect_with_message('No recognized subject columns were found. Expected headers like "Mathematics", "Science", "Kiswahili", etc.', 'error');
}

// ── Process each data row ──
$updatedCount  = 0;
$insertedCount = 0;
$skippedRows   = 0;
$skippedCells  = 0;

foreach ($lines as $line) {
    if (trim($line) === '') continue;
    $fields = str_getcsv($line, $delimiter);

    $assess = trim((string)($fields[$assessColIdx] ?? ''));
    if ($assess === '') {
        $skippedRows++;
        continue;
    }

    // Confirm the assessment number belongs to a real, currently
    // enrolled student before writing anything for it.
    $stuStmt = $conn->prepare("SELECT firstName, surname FROM Student WHERE Assesment = ? LIMIT 1");
    $stuStmt->bind_param('s', $assess);
    $stuStmt->execute();
    $student = $stuStmt->get_result()->fetch_assoc();
    $stuStmt->close();

    if (!$student) {
        $skippedRows++;
        continue;
    }

    foreach ($subjectCols as $code => $idx) {
        $raw = trim((string)($fields[$idx] ?? ''));
        if ($raw === '') continue; // blank cell = "not entered", leave it alone
        if (!is_numeric($raw)) { $skippedCells++; continue; }

        $score = (float)$raw;
        $score = max(0, min(100, $score)); // clamp to a sane 0–100 range

        // Does a row already exist for this student/grade/term/year/exam type?
        $check = $conn->prepare(
            "SELECT id FROM exam2 WHERE Assesment = ? AND grade = ? AND term = ? AND year = ? AND exam_type = ? LIMIT 1"
        );
        $check->bind_param('sisis', $assess, $grade, $termLabel, $year, $examType);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            // $code is only ever one of the whitelisted subject_map keys
            // (matched from the header row above), so it's safe to
            // interpolate directly as a column name here.
            $update = $conn->prepare("UPDATE exam2 SET `$code` = ? WHERE id = ?");
            $update->bind_param('di', $score, $existing['id']);
            $update->execute();
            $update->close();
            $updatedCount++;
        } else {
            $insert = $conn->prepare(
                "INSERT INTO exam2 (Assesment, firstName, lastName, `$code`, grade, term, year, exam_type)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insert->bind_param(
                'sssdisis',
                $assess,
                $student['firstName'],
                $student['surname'],
                $score,
                $grade,
                $termLabel,
                $year,
                $examType
            );
            $insert->execute();
            $insert->close();
            $insertedCount++;
        }
    }
}

mysqli_close($conn);

$parts = [];
$parts[] = "$updatedCount mark(s) updated";
$parts[] = "$insertedCount new record(s) created";
if ($skippedRows > 0)  $parts[] = "$skippedRows row(s) skipped (unrecognized assessment number)";
if ($skippedCells > 0) $parts[] = "$skippedCells cell(s) skipped (non-numeric value)";

redirect_with_message('Upload complete — ' . implode(', ', $parts) . '.', 'success');
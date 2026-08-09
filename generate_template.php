<?php
// ============================================================
// generate_template.php — Stephen Kanja School Management System
//
// Streams a ready-to-fill .csv template for a chosen grade and
// subject list: Assessment No / First Name / Surname are
// pre-filled from the Student table, one blank column is added
// per selected subject for the teacher to fill in offline.
//
// CSV-only, pure PHP — no Composer / PhpSpreadsheet dependency.
// This matters because this system runs on InfinityFree's free
// tier, which has no shell/Composer access, so a vendor/ folder
// can never be installed there — a PhpSpreadsheet-based version
// would fail with a fatal error (HTTP 500) the moment it tried to
// require vendor/autoload.php. fputcsv() is built into core PHP,
// so this needs nothing extra to run.
//
// The .csv opens fine in Excel, Google Sheets, or Numbers — the
// teacher fills it in and saves it back as .csv (not .xlsx) for
// upload_excel_marks.php to read.
// ============================================================
include 'conn.php';

// ── Canonical subject list — MUST match the codes used in
// student_dashboard.php and upload_excel_marks.php exactly, since
// the uploader matches these same display labels back to DB columns. ──
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

$grade = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$requestedSubjects = isset($_GET['subjects']) && is_array($_GET['subjects']) ? $_GET['subjects'] : [];

// Whitelist: only accept subject codes we actually know about
$selectedSubjects = array_values(array_filter($requestedSubjects, function ($s) use ($subject_map) {
    return array_key_exists($s, $subject_map);
}));

if ($grade < 1 || $grade > 9) {
    http_response_code(400);
    die('Invalid or missing grade.');
}
if (empty($selectedSubjects)) {
    http_response_code(400);
    die('Please select at least one subject.');
}

// ── Pull the grade's roster ──
$stmt = $conn->prepare(
    "SELECT Assesment, firstName, surname FROM Student WHERE Grade = ? AND role = 'user' ORDER BY surname, firstName"
);
$stmt->bind_param('i', $grade);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
mysqli_close($conn);

// ── Stream the CSV ──
$filename = "Grade{$grade}_Marks_Template.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel on Windows detects the encoding correctly
// instead of mangling names with accented characters.
fwrite($out, "\xEF\xBB\xBF");

$headers = ['Assessment No', 'First Name', 'Surname'];
foreach ($selectedSubjects as $code) {
    $headers[] = $subject_map[$code];
}
fputcsv($out, $headers);

foreach ($students as $s) {
    $row = [$s['Assesment'], $s['firstName'], $s['surname']];
    foreach ($selectedSubjects as $code) {
        $row[] = ''; // blank — the teacher fills this in
    }
    fputcsv($out, $row);
}

fclose($out);
exit;
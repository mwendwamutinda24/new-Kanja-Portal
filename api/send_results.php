<?php
// ============================================================
// api/send_results.php
// JSON API for the mobile app.
// Actions: filters | preview | send
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include __DIR__ . '/../conn.php';
require_once __DIR__ . '/../africa_talking_sms.php';

function jexit($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!$conn) jexit(['success' => false, 'error' => 'DB connection failed'], 500);

$subject_map = array(
    'math' => 'Mathematics', 'eng' => 'English', 'kisw' => 'Kiswahili',
    'sst' => 'Social Studies', 'scie' => 'Science', 'ca' => 'CA',
    'agri' => 'Agriculture', 're' => 'RE', 'pretec' => 'Pre-Technical',
);

function build_subject_summary($row, $subject_map) {
    $parts = array(); $scores = array();
    foreach ($subject_map as $code => $label) {
        if (isset($row[$code]) && $row[$code] !== null && $row[$code] !== '') {
            $parts[]  = $label . ': ' . $row[$code];
            $scores[] = (float)$row[$code];
        }
    }
    $total   = array_sum($scores);
    $average = count($scores) > 0 ? round($total / count($scores), 1) : 0;
    return array('summary' => implode(', ', $parts), 'total' => $total, 'average' => $average);
}

function fill_template($template, $tokens) {
    foreach ($tokens as $k => $v) $template = str_replace('{' . $k . '}', $v, $template);
    return $template;
}

$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

// ─────────────────────────────────────────────────────────────
// ACTION: filters — populate dropdowns (grades / terms / years / exam types)
// ─────────────────────────────────────────────────────────────
if ($action === 'filters') {
    $grades = []; $terms = []; $years = []; $examTypes = [];
    $q = $conn->query("SELECT DISTINCT grade FROM exam2 WHERE grade IS NOT NULL ORDER BY grade");
    while ($r = $q->fetch_assoc()) $grades[] = (int)$r['grade'];

    $q = $conn->query("SELECT DISTINCT term FROM exam2 WHERE term IS NOT NULL ORDER BY term");
    while ($r = $q->fetch_assoc()) $terms[] = $r['term'];

    $q = $conn->query("SELECT DISTINCT year FROM exam2 WHERE year IS NOT NULL ORDER BY year DESC");
    while ($r = $q->fetch_assoc()) $years[] = (int)$r['year'];

    $q = $conn->query("SELECT DISTINCT exam_type FROM exam2 WHERE exam_type IS NOT NULL ORDER BY exam_type");
    while ($r = $q->fetch_assoc()) $examTypes[] = $r['exam_type'];

    jexit(['success' => true, 'grades' => $grades, 'terms' => $terms, 'years' => $years, 'examTypes' => $examTypes]);
}

// ─────────────────────────────────────────────────────────────
// ACTION: preview — return matched students + their marks + phone status
// ─────────────────────────────────────────────────────────────
if ($action === 'preview') {
    $grade    = (int)($input['grade'] ?? 0);
    $termNum  = preg_replace('/[^0-9]/', '', $input['term'] ?? '');
    $year     = (int)($input['year'] ?? 0);
    $examType = trim($input['examType'] ?? '');
    $termLabel = $termNum !== '' ? "Term $termNum" : '';

    if (!$grade || !$termLabel || !$year || !$examType) {
        jexit(['success' => false, 'error' => 'Missing filter parameters'], 400);
    }

    $stmt = $conn->prepare("
        SELECT e.id AS exam_id, s.id AS student_id, s.firstName, s.surname,
               s.parentName, s.parentPhone,
               e.math, e.eng, e.kisw, e.sst, e.scie, e.ca, e.agri, e.re, e.pretec
        FROM exam2 e
        JOIN Student s ON s.id = e.student_id
        WHERE e.grade = ? AND e.term = ? AND e.year = ? AND e.exam_type = ?
        ORDER BY s.firstName, s.surname
    ");
    $stmt->bind_param('isis', $grade, $termLabel, $year, $examType);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $summary = build_subject_summary($row, $subject_map);
        $phoneOk = function_exists('normalize_kenyan_phone')
                   ? normalize_kenyan_phone($row['parentPhone'])
                   : $row['parentPhone'];
        $rows[] = array(
            'student_id'  => (int)$row['student_id'],
            'name'        => trim($row['firstName'] . ' ' . $row['surname']),
            'parentName'  => $row['parentName'],
            'rawPhone'    => $row['parentPhone'],
            'phone'       => $phoneOk ?: null,
            'hasPhone'    => !empty($phoneOk),
            'marks'       => $summary['summary'] ?: '—',
            'total'       => $summary['total'],
            'average'     => $summary['average'],
        );
    }
    $stmt->close();

    jexit(['success' => true, 'count' => count($rows), 'students' => $rows]);
}

// ─────────────────────────────────────────────────────────────
// ACTION: send — send SMS to selected student IDs
// ─────────────────────────────────────────────────────────────
if ($action === 'send') {
    $grade    = (int)($input['grade'] ?? 0);
    $termNum  = preg_replace('/[^0-9]/', '', $input['term'] ?? '');
    $year     = (int)($input['year'] ?? 0);
    $examType = trim($input['examType'] ?? '');
    $termLabel = $termNum !== '' ? "Term $termNum" : '';
    $studentIds = isset($input['studentIds']) && is_array($input['studentIds'])
                  ? array_map('intval', $input['studentIds']) : [];

    $template = trim($input['messageTemplate'] ?? '');
    if ($template === '') {
        $template = "Dear Parent/Guardian, {student_name}'s {exam_type} results for {term} {year}, {grade_label}: {subjects}. Total: {total} Average: {average}. - Stephen Kanja School";
    }

    if (!$grade || !$termLabel || !$year || !$examType || empty($studentIds)) {
        jexit(['success' => false, 'error' => 'Missing filter or recipient parameters'], 400);
    }

    $stmt = $conn->prepare("
        SELECT e.id AS exam_id, s.id AS student_id, s.firstName, s.surname,
               s.parentPhone, e.math, e.eng, e.kisw, e.sst, e.scie, e.ca, e.agri, e.re, e.pretec
        FROM exam2 e
        JOIN Student s ON s.id = e.student_id
        WHERE e.grade = ? AND e.term = ? AND e.year = ? AND e.exam_type = ?
    ");
    $stmt->bind_param('isis', $grade, $termLabel, $year, $examType);
    $stmt->execute();
    $result = $stmt->get_result();

    $sent = 0; $failed = 0; $skipped = 0; $details = [];

    while ($row = $result->fetch_assoc()) {
        $studentId = (int)$row['student_id'];
        if (!in_array($studentId, $studentIds, true)) continue;

        $phone = function_exists('normalize_kenyan_phone')
                 ? normalize_kenyan_phone($row['parentPhone'])
                 : $row['parentPhone'];
        $name  = trim($row['firstName'] . ' ' . $row['surname']);

        if (!$phone) {
            $skipped++;
            $details[] = ['name' => $name, 'phone' => null, 'status' => 'skipped', 'note' => 'No valid phone on file'];
            continue;
        }

        $summary = build_subject_summary($row, $subject_map);
        $message = fill_template($template, array(
            'student_name' => $name,
            'exam_type'    => ucfirst($examType),
            'term'         => $termLabel,
            'year'         => $year,
            'grade_label'  => "Grade $grade",
            'subjects'     => $summary['summary'],
            'total'        => $summary['total'],
            'average'      => $summary['average'],
        ));

        $res = send_africas_talking_sms($phone, $message);
        $ok  = !empty($res['success']);

        $log = $conn->prepare("INSERT INTO sms_log (student_id, phone, grade, term, year, exam_type, message, status, provider_response) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $status = $ok ? 'sent' : 'failed';
        $log->bind_param('isisssss',
            $studentId, $phone, $grade, $termLabel, $year, $examType, $message, $status, $res['response']);
        $log->execute();
        $log->close();

        if ($ok) { $sent++; $details[] = ['name' => $name, 'phone' => $phone, 'status' => 'sent', 'note' => '']; }
        else     { $failed++; $details[] = ['name' => $name, 'phone' => $phone, 'status' => 'failed', 'note' => $res['response']]; }
    }
    $stmt->close();

    jexit([
        'success' => true,
        'sent' => $sent,
        'failed' => $failed,
        'skipped' => $skipped,
        'details' => $details,
    ]);
}

jexit(['success' => false, 'error' => 'Unknown action'], 400);

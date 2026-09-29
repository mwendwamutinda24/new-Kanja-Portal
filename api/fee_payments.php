<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'POST required'], 405);
}

$session = require_auth();
if (!in_array($session['role'], ['hoi', 'Dhoi', 'teacher'], true)) {
    respond(['success' => false, 'error' => 'Not authorized.'], 403);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond(['success' => false, 'error' => 'Invalid JSON body.'], 400);
}

$grade    = (int) ($body['grade'] ?? 0);
$term     = (int) ($body['term'] ?? 0);
$year     = (int) ($body['year'] ?? 0);
$payments = $body['payments'] ?? [];

if ($grade <= 0 || $term <= 0 || $year <= 0 || !is_array($payments) || count($payments) === 0) {
    respond(['success' => false, 'error' => 'grade, term, year and payments are required.'], 400);
}

function make_receipt_no($conn) {
    do {
        $candidate = 'RCPT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $stmt = $conn->prepare("SELECT id FROM Fees WHERE receipt_no = ?");
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
    } while ($exists);
    return $candidate;
}

$saved = 0;
$receipts = [];
mysqli_begin_transaction($conn);
try {
    $studentStmt = $conn->prepare("SELECT Assesment, firstName, surname FROM Student WHERE id = ?");
    $insert = $conn->prepare(
        "INSERT INTO Fees (Assesment, StudentID, firstName, surname, Fee, AssesmentFee, Activity, other, Grade, Term, Year, payment_date, receipt_no)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
    );

    foreach ($payments as $studentId => $amounts) {
        $sid  = (int) $studentId;
        $fee        = (float) ($amounts['fee'] ?? 0);
        $assessment = (float) ($amounts['assessment'] ?? 0);
        $activity   = (float) ($amounts['activity'] ?? 0);
        $other      = (float) ($amounts['other'] ?? 0);
        $total = $fee + $assessment + $activity + $other;

        if ($sid <= 0 || $total <= 0) continue;

        $studentStmt->bind_param('i', $sid);
        $studentStmt->execute();
        $s = $studentStmt->get_result()->fetch_assoc();
        if (!$s) continue; // unknown student id — skip rather than fail the whole batch

        $receiptNo = make_receipt_no($conn);
        $insert->bind_param(
            'sisssdddiis',
            $s['Assesment'], $sid, $s['firstName'], $s['surname'],
            $fee, $assessment, $activity, $other,
            $grade, $term, $year, $receiptNo
        );
        $insert->execute();

        $saved++;
        $receipts[] = ['student_id' => $sid, 'receipt_no' => $receiptNo, 'total' => $total];
    }

    if ($saved === 0) {
        mysqli_rollback($conn);
        respond(['success' => false, 'error' => 'No valid fee amounts to save.'], 400);
    }

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    respond(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}

respond(['success' => true, 'saved' => $saved, 'receipts' => $receipts]);

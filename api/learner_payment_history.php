<?php
// ============================================================
// api/learner_payment_history.php
//   GET ?id=520&year=2026&term=3     (year and term are optional)
//
// Returns every payment row for one learner, newest first:
//   { success, transactions:[{id, date, amount, term, receiptNo}] }
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

function fail($msg, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e['message']]);
    }
});

if (!$conn) fail('DB connection failed');

// ── Inputs ────────────────────────────────────────────────
$id   = (int) ($_GET['id'] ?? 0);
$year = (int) ($_GET['year'] ?? 0);
$term = (int) preg_replace('/[^0-9]/', '', (string) ($_GET['term'] ?? ''));

if ($id <= 0) fail('Missing learner id', 400);

// Fees.receipt_no only exists after the first payment is saved with the
// updated save_fee_payments.php. Select it only when the column is there.
$hasReceipt = false;
$col = $conn->query("SHOW COLUMNS FROM Fees LIKE 'receipt_no'");
if ($col && $col->num_rows > 0) $hasReceipt = true;

$sql = "SELECT id, Fee, AssesmentFee, Activity, other, Term, Year, payment_date"
     . ($hasReceipt ? ", receipt_no" : "")
     . " FROM Fees WHERE StudentID = ?";
$types  = 'i';
$params = [$id];

if ($year > 0) { $sql .= " AND Year = ?"; $types .= 'i'; $params[] = $year; }
if ($term > 0) { $sql .= " AND Term = ?"; $types .= 'i'; $params[] = $term; }
$sql .= " ORDER BY payment_date DESC, id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) fail('Prepare failed: ' . $conn->error);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$transactions = [];
while ($r = $res->fetch_assoc()) {
    $amount = (float) $r['Fee'] + (float) $r['AssesmentFee']
            + (float) $r['Activity'] + (float) $r['other'];
    if ($amount <= 0) continue;

    $ts = strtotime((string) $r['payment_date']);
    $transactions[] = [
        'id'        => (int) $r['id'],
        'date'      => $ts ? date('d M Y', $ts) : '',
        'amount'    => $amount,
        'term'      => 'Term ' . (int) $r['Term'] . ' ' . (int) $r['Year'],
        'receiptNo' => $hasReceipt ? (string) ($r['receipt_no'] ?? '') : '',
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'transactions' => $transactions]);

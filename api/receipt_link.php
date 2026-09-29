<?php
// GET ?receipt_no=REC-...  ->  { success, url }
// Returns a 10-minute signed link to download_receipt.php for that receipt.
mysqli_report(MYSQLI_REPORT_OFF);
require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function fail($msg, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$session = require_auth();

$secret = getenv('RECEIPT_LINK_SECRET');
if (!$secret) fail('RECEIPT_LINK_SECRET is not set on the server.');

$receiptNo = trim((string) ($_GET['receipt_no'] ?? ''));
if ($receiptNo === '') fail('Missing receipt_no', 400);

$stmt = $conn->prepare("SELECT id FROM Fees WHERE receipt_no = ? LIMIT 1");
$stmt->bind_param('s', $receiptNo);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) fail('Receipt not found', 404);
$stmt->close();

$exp = time() + 600;
$sig = hash_hmac('sha256', $receiptNo . '|' . $exp, $secret);

$base = 'https://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/download_receipt.php';
$url  = $base . '?receipt_no=' . urlencode($receiptNo) . '&exp=' . $exp . '&sig=' . $sig;

echo json_encode(['success' => true, 'url' => $url]);

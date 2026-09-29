<?php
// ============================================================
// api/download_receipt.php
//   ?receipt_no=REC-...                    (needs a login session)
//   ?receipt_no=REC-...&exp=..&sig=..      (signed link from receipt_link.php)
//   &format=pdf   -> real PDF if Dompdf is installed,
//                    otherwise the page opens the print dialog
// ============================================================
mysqli_report(MYSQLI_REPORT_OFF);
require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

$receiptNo = trim((string) ($_GET['receipt_no'] ?? ''));
if ($receiptNo === '') {
    http_response_code(400);
    echo 'Missing receipt_no';
    exit;
}

// ── Access: valid signed link OR a normal logged-in session ──
$exp      = (int) ($_GET['exp'] ?? 0);
$sig      = (string) ($_GET['sig'] ?? '');
$secret   = getenv('RECEIPT_LINK_SECRET');
$signedOk = $secret && $sig !== '' && $exp >= time()
    && hash_equals(hash_hmac('sha256', $receiptNo . '|' . $exp, $secret), $sig);

if (!$signedOk) {
    $session = require_auth();
}

// ── Look up the payment ──────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT receipt_no, Fee, AssesmentFee, Activity, other, Grade, Term, Year, payment_date,
            Assesment, firstName, surname
     FROM Fees
     WHERE receipt_no = ?
     LIMIT 1"
);
$stmt->bind_param('s', $receiptNo);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo 'Receipt not found';
    exit;
}

// Who recorded it (from the audit log; optional)
$servedBy = 'Staff';
$lg = $conn->prepare("SELECT recorded_by FROM fee_payments_log WHERE receipt_no = ? LIMIT 1");
if ($lg) {
    $lg->bind_param('s', $receiptNo);
    $lg->execute();
    $lr = $lg->get_result()->fetch_assoc();
    if ($lr && $lr['recorded_by']) $servedBy = $lr['recorded_by'];
    $lg->close();
}

$format      = strtolower((string) ($_GET['format'] ?? 'html'));
$learnerName = trim($row['firstName'] . ' ' . $row['surname']);
$total       = (float) $row['Fee'] + (float) $row['AssesmentFee'] + (float) $row['Activity'] + (float) $row['other'];
$paidOn      = date('d M Y, H:i', strtotime($row['payment_date']));
$e           = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$money       = fn($v) => number_format((float) $v, 2);

function item_row($label, $amount, $money, $e) {
    if ($amount <= 0) return '';
    return '<tr><td>' . $e($label) . '</td><td class="amt">' . $money($amount) . '</td></tr>';
}

// Table-based layout so it renders the same in browsers and in Dompdf
$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt ' . $e($row['receipt_no']) . '</title>
<style>
  body { font-family: Helvetica, Arial, sans-serif; color: #1a1a18; margin: 0; padding: 24px; background: #f4f4f2; }
  .receipt { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e0e0e0; }
  .head { background: #111; border-bottom: 3px solid #f0c040; padding: 20px 26px; }
  .head h1 { color: #fff; font-size: 20px; margin: 0; letter-spacing: 1px; }
  .head h1 span { color: #f0c040; }
  .head p { color: #aaa; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; margin: 4px 0 0; }
  .body { padding: 22px 26px; }
  table { width: 100%; border-collapse: collapse; }
  .info td { padding: 8px 0; border-bottom: 1px dashed #ddd; font-size: 13px; }
  .info td.k { color: #666; }
  .info td.v { text-align: right; font-weight: bold; }
  .items { margin-top: 18px; }
  .items th { text-align: left; color: #888; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; padding: 8px 0; border-bottom: 1px solid #ddd; }
  .items th.amt, .items td.amt { text-align: right; }
  .items td { padding: 9px 0; border-bottom: 1px solid #eee; font-size: 13px; }
  .total { margin-top: 16px; border-top: 2px solid #111; }
  .total td { padding-top: 14px; }
  .total .lbl { font-size: 12px; letter-spacing: 1px; text-transform: uppercase; color: #666; }
  .total .sum { text-align: right; font-size: 22px; font-weight: bold; color: #16a34a; }
  .foot { background: #fafafa; border-top: 1px solid #eee; padding: 14px 26px; text-align: center; font-size: 11px; color: #888; }
  .btn { display: block; margin: 18px auto 0; padding: 10px 22px; background: #f0c040; border: 0; font-weight: bold; cursor: pointer; }
  @media print { body { background: #fff; padding: 0; } .btn { display: none; } .receipt { border: 0; } }
</style>
</head>
<body>
  <div class="receipt">
    <div class="head">
      <h1>STEPHEN KANJA <span>SCHOOL</span></h1>
      <p>Aim Higher &middot; Official Fee Receipt</p>
    </div>
    <div class="body">
      <table class="info">
        <tr><td class="k">Receipt No.</td><td class="v">' . $e($row['receipt_no']) . '</td></tr>
        <tr><td class="k">Date</td><td class="v">' . $e($paidOn) . '</td></tr>
        <tr><td class="k">Learner</td><td class="v">' . $e($learnerName) . '</td></tr>
        <tr><td class="k">Assessment No.</td><td class="v">' . $e($row['Assesment']) . '</td></tr>
        <tr><td class="k">Grade</td><td class="v">Grade ' . $e($row['Grade']) . '</td></tr>
        <tr><td class="k">Term / Year</td><td class="v">Term ' . (int) $row['Term'] . ', ' . (int) $row['Year'] . '</td></tr>
      </table>

      <table class="items">
        <tr><th>Description</th><th class="amt">Amount (KES)</th></tr>'
        . item_row('School Fee',     (float) $row['Fee'],          $money, $e)
        . item_row('Assessment Fee', (float) $row['AssesmentFee'], $money, $e)
        . item_row('Activity Fee',   (float) $row['Activity'],     $money, $e)
        . item_row('Other Fee',      (float) $row['other'],        $money, $e) . '
      </table>

      <table class="total">
        <tr><td class="lbl">Total Paid</td><td class="sum">KES ' . $money($total) . '</td></tr>
      </table>
    </div>
    <div class="foot">
      Served by: ' . $e($servedBy) . ' &middot; Thank you for your payment.<br>
      This is a computer-generated receipt.
    </div>
  </div>';

// ── PDF mode: real PDF when Dompdf is installed ──────────────
if ($format === 'pdf') {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html . '</body></html>');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Receipt-' . $row['receipt_no'] . '.pdf"');
            echo $dompdf->output();
            exit;
        }
    }
    // No Dompdf: open the print dialog so the user can "Save as PDF"
    $html .= '<script>window.onload = function () { window.print(); };</script>';
} else {
    $html .= '<button class="btn" onclick="window.print()">Print / Save as PDF</button>';
}

header('Content-Type: text/html; charset=utf-8');
echo $html . '</body></html>';

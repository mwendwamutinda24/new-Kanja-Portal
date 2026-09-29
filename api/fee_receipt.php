<?php
mysqli_report(MYSQLI_REPORT_OFF);
require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

$session = require_auth();

$receiptNo = trim((string) ($_GET['receipt_no'] ?? ''));
if ($receiptNo === '') {
    http_response_code(400);
    echo 'Missing receipt_no';
    exit;
}

$stmt = $conn->prepare(
    "SELECT f.receipt_no, f.Fee, f.AssesmentFee, f.Activity, f.other, f.Grade, f.Term, f.Year, f.payment_date,
            f.Assesment, f.firstName, f.surname
     FROM Fees f
     WHERE f.receipt_no = ?"
);
$stmt->bind_param('s', $receiptNo);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo 'Receipt not found';
    exit;
}

$isPdf = ($_GET['format'] ?? '') === 'pdf';
$learnerName = trim($row['firstName'] . ' ' . $row['surname']);
$total = (float) $row['Fee'] + (float) $row['AssesmentFee'] + (float) $row['Activity'] + (float) $row['other'];

function line($label, $amount) {
    if ($amount <= 0) return '';
    return '<div class="row"><span><b>' . htmlspecialchars($label) . '</b></span><span>KES ' . number_format($amount, 2) . '</span></div>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= htmlspecialchars($row['receipt_no']) ?></title>
<style>
  body { font-family: Helvetica, Arial, sans-serif; color: #1a1a18; padding: 30px; }
  .band { background: #111; border-bottom: 3px solid #f0c040; padding: 16px; text-align: center; margin: -30px -30px 24px; }
  .band h1 { color: #f0c040; font-size: 18px; margin: 0 0 4px; letter-spacing: 1px; }
  .band p { color: #fff; margin: 0; font-size: 12px; }
  .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 13px; }
  .row b { color: #555; }
  .total { margin-top: 18px; padding: 14px; background: #fdf6e3; border-top: 2px solid #f0c040; font-size: 16px; font-weight: 700; display: flex; justify-content: space-between; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>
  <div class="band">
    <h1>Stephen Kanja Primary &amp; Junior School</h1>
    <p>Official Fee Receipt</p>
  </div>

  <div class="row"><span><b>Receipt No.</b></span><span><?= htmlspecialchars($row['receipt_no']) ?></span></div>
  <div class="row"><span><b>Learner</b></span><span><?= htmlspecialchars($learnerName) ?></span></div>
  <div class="row"><span><b>Admission No.</b></span><span><?= htmlspecialchars($row['Assesment']) ?></span></div>
  <div class="row"><span><b>Grade</b></span><span>Grade <?= htmlspecialchars($row['Grade']) ?></span></div>
  <div class="row"><span><b>Term / Year</b></span><span>Term <?= (int) $row['Term'] ?>, <?= (int) $row['Year'] ?></span></div>
  <div class="row"><span><b>Date</b></span><span><?= htmlspecialchars(date('d M Y, H:i', strtotime($row['payment_date']))) ?></span></div>

  <?= line('School Fee', (float) $row['Fee']) ?>
  <?= line('Assessment Fee', (float) $row['AssesmentFee']) ?>
  <?= line('Activity Fee', (float) $row['Activity']) ?>
  <?= line('Other Fee', (float) $row['other']) ?>

  <div class="total"><span>Total Paid</span><span>KES <?= number_format($total, 2) ?></span></div>

<?php if ($isPdf): ?>
  <p class="no-print" style="margin-top:24px;font-size:12px;color:#888;">Use your browser's Print dialog and choose "Save as PDF".</p>
  <script>window.onload = function () { window.print(); };</script>
<?php endif; ?>
</body>
</html>

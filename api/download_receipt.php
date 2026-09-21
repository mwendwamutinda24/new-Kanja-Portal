<?php
// ============================================================
// download_receipt.php
// Generate a printable receipt for a single payment.
//   ?receipt_no=REC-YYYYMMDD-XXXX     -> preferred
//   ?student_id=..&term=..&year=..    -> fallback (latest receipt)
//   &format=pdf                       -> stream PDF via Dompdf
// ============================================================

session_start();
include 'conn.php';

if (!$conn) { http_response_code(500); exit('DB connection failed'); }

$receiptNo = isset($_GET['receipt_no']) ? trim($_GET['receipt_no']) : '';
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$termNum   = isset($_GET['term']) ? preg_replace('/[^0-9]/', '', $_GET['term']) : '';
$year      = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$format    = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

$termLabel = $termNum !== '' ? "Term $termNum" : '';

if ($receiptNo === '' && (!$studentId || $termLabel === '' || !$year)) {
    http_response_code(400);
    exit('Provide receipt_no, or student_id + term + year.');
}

// ── Look up the payment (prefer exact receipt, else latest for student/term) ──
if ($receiptNo !== '') {
    $stmt = $conn->prepare("
        SELECT p.*, s.firstName, s.surname, s.assessmentNo, s.grade AS student_grade
        FROM fee_payments_log p
        JOIN Student s ON s.id = p.student_id
        WHERE p.receipt_no = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $receiptNo);
} else {
    $stmt = $conn->prepare("
        SELECT p.*, s.firstName, s.surname, s.assessmentNo, s.grade AS student_grade
        FROM fee_payments_log p
        JOIN Student s ON s.id = p.student_id
        WHERE p.student_id = ? AND p.term = ? AND p.year = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('isi', $studentId, $termLabel, $year);
}
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    exit('Receipt not found.');
}

$studentName = trim($row['firstName'] . ' ' . $row['surname']);
$total = (float)$row['total_paid'];
$paidOn = date('d M Y, H:i', strtotime($row['created_at']));
$schoolName = 'Stephen Kanja Primary & Junior School';
$schoolMotto = 'Aim Higher';

// Build the HTML (used for both modes)
$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt {$row['receipt_no']}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Helvetica', Arial, sans-serif; background: #f4f4f2; padding: 24px; color: #1a1a18; }
  .receipt { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; }
  .rhead { background: #111; color: #fff; padding: 20px 26px; border-bottom: 3px solid #f0c040; }
  .rhead h1 { font-size: 20px; letter-spacing: 1px; }
  .rhead h1 span { color: #f0c040; }
  .rhead .motto { font-size: 11px; letter-spacing: 3px; color: #888; margin-top: 4px; text-transform: uppercase; }
  .rbody { padding: 26px; }
  .rrow { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 13.5px; }
  .rrow .k { color: #666; }
  .rrow .v { font-weight: 600; color: #1a1a18; text-align: right; }
  .items { margin-top: 18px; }
  .items table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  .items th, .items td { padding: 9px 8px; text-align: left; border-bottom: 1px solid #eee; }
  .items th { color: #888; font-weight: 600; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.08em; }
  .items td.amt { text-align: right; font-family: 'Courier New', monospace; }
  .total { display: flex; justify-content: space-between; margin-top: 18px; padding-top: 14px; border-top: 2px solid #111; }
  .total .lbl { font-size: 13px; letter-spacing: 0.1em; text-transform: uppercase; color: #666; }
  .total .amt { font-size: 22px; font-weight: 800; color: #16a34a; }
  .rfoot { padding: 16px 26px 24px; font-size: 11px; color: #888; text-align: center; background: #fafafa; border-top: 1px solid #eee; }
  .btn-print { display:block; margin: 18px auto 0; padding: 10px 22px; background:#f0c040; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
  @media print {
    body { background: #fff; padding: 0; }
    .btn-print { display: none; }
    .receipt { border: none; }
  }
</style>
</head>
<body>
  <div class="receipt">
    <div class="rhead">
      <h1>STEPHEN KANJA <span>SCHOOL</span></h1>
      <div class="motto">{$schoolMotto}</div>
    </div>
    <div class="rbody">
      <div class="rrow"><span class="k">Receipt No.</span><span class="v">{$row['receipt_no']}</span></div>
      <div class="rrow"><span class="k">Date</span><span class="v">{$paidOn}</span></div>
      <div class="rrow"><span class="k">Student</span><span class="v">{$studentName}</span></div>
      <div class="rrow"><span class="k">Assessment No.</span><span class="v">{$row['assessmentNo']}</span></div>
      <div class="rrow"><span class="k">Grade</span><span class="v">Grade {$row['student_grade']}</span></div>
      <div class="rrow"><span class="k">Term / Year</span><span class="v">{$row['term']} · {$row['year']}</span></div>

      <div class="items">
        <table>
          <tr><th>Description</th><th style="text-align:right;">Amount (KES)</th></tr>
          <tr><td>School Fees</td><td class="amt">{$row['school_fee']}</td></tr>
          <tr><td>Assessment Fee</td><td class="amt">{$row['assessment_fee']}</td></tr>
          <tr><td>Activity Fees</td><td class="amt">{$row['activity_fee']}</td></tr>
          <tr><td>Other Fees</td><td class="amt">{$row['other_fee']}</td></tr>
        </table>
      </div>

      <div class="total">
        <span class="lbl">Total Paid</span>
        <span class="amt">KES {$total}</span>
      </div>
    </div>
    <div class="rfoot">
      Served by: {$row['recorded_by']} &nbsp;·&nbsp; Thank you for your payment.<br>
      This is a computer-generated receipt.
    </div>
  </div>
  <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
</body>
</html>
HTML;

// ── PDF mode ─────────────────────────────────────────────────
if ($format === 'pdf') {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Receipt-' . $row['receipt_no'] . '.pdf"');
            echo $dompdf->output();
            exit;
        }
    }
    // No Dompdf — tell the browser to print instead
    header('Location: download_receipt.php?receipt_no=' . urlencode($row['receipt_no']));
    exit;
}

// ── HTML mode ────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
echo $html;

<?php
// ============================================================
// save_fee_payments.php
// Persist fee entries for all students in one grade/term/year
// submission, generating a receipt number per student.
// ============================================================

session_start();
include 'conn.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// Accept both form-encoded (web) and JSON (mobile) bodies
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = $_POST;

$grade    = isset($body['grade']) ? (int)$body['grade'] : 0;
$termNum  = isset($body['term'])  ? preg_replace('/[^0-9]/', '', $body['term']) : '';
$year     = isset($body['year'])  ? (int)$body['year'] : 0;
$termLabel = $termNum !== '' ? "Term $termNum" : '';

// Expected columns sent either as a flat dict "school_fee[ID]" or as nested
// {"school_fee": {"ID": value}}
$fields = ['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'];

$columnsByField = [];
foreach ($fields as $f) {
    if (isset($body[$f]) && is_array($body[$f])) {
        $columnsByField[$f] = $body[$f];
    } else {
        // Rebuild from PHP's bracket parsing: $body["school_fee"][id]
        $columnsByField[$f] = [];
        foreach ($body as $k => $v) {
            if (preg_match('/^' . preg_quote($f, '/') . '\[(\d+)\]$/', $k, $m)) {
                $columnsByField[$f][$m[1]] = $v;
            }
        }
    }
}

// Union of all student IDs present in any field
$studentIds = [];
foreach ($columnsByField as $col) {
    foreach (array_keys($col) as $sid) $studentIds[$sid] = true;
}
$studentIds = array_keys($studentIds);

if (!$grade || !$termLabel || !$year || empty($studentIds)) {
    echo json_encode(['success' => false, 'error' => 'Nothing to save (missing grade/term/year or no rows)']);
    exit;
}

$recordedBy = $_SESSION['teacher_name'] ?? ($_SESSION['username'] ?? 'Staff');

$conn->begin_transaction();
try {
    $savedCount = 0;
    $receipts   = [];

    // Prep statements once
    $selStmt = $conn->prepare("
        SELECT id, paid_amount, school_fee, assessment_fee, activity_fee, other_fee
        FROM fee_records WHERE student_id = ? AND term = ? AND year = ?
    ");
    $insStmt = $conn->prepare("
        INSERT INTO fee_records
            (student_id, grade, term, year,
             expected_amount, paid_amount,
             school_fee, assessment_fee, activity_fee, other_fee,
             receipt_no, payment_date, recorded_by)
        VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $updStmt = $conn->prepare("
        UPDATE fee_records
           SET grade = ?,
               paid_amount    = paid_amount    + ?,
               school_fee     = school_fee     + ?,
               assessment_fee = assessment_fee + ?,
               activity_fee   = activity_fee   + ?,
               other_fee      = other_fee      + ?,
               receipt_no     = ?,
               payment_date   = NOW(),
               recorded_by    = ?
         WHERE student_id = ? AND term = ? AND year = ?
    ");
    $logStmt = $conn->prepare("
        INSERT INTO fee_payments_log
            (receipt_no, student_id, grade, term, year,
             school_fee, assessment_fee, activity_fee, other_fee,
             total_paid, recorded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($studentIds as $sid) {
        $sid = (int)$sid;
        $school     = (float)($columnsByField['school_fee'][$sid]     ?? 0);
        $assessment = (float)($columnsByField['assessment_fee'][$sid] ?? 0);
        $activity   = (float)($columnsByField['activity_fee'][$sid]   ?? 0);
        $other      = (float)($columnsByField['other_fee'][$sid]      ?? 0);
        $total      = $school + $assessment + $activity + $other;

        if ($total <= 0) continue; // skip students the teacher left blank

        // Generate a unique receipt number: REC-YYYYMMDD-XXXX (sequence per day)
        $receiptNo = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Does a row exist for this student/term/year?
        $selStmt->bind_param('isi', $sid, $termLabel, $year);
        $selStmt->execute();
        $existing = $selStmt->get_result()->fetch_assoc();
        $selStmt->free_result();

        if ($existing) {
            $updStmt->bind_param(
                'iddddd' . 'ss' . 'isi',
                $grade,
                $total, $school, $assessment, $activity, $other,
                $receiptNo, $recordedBy,
                $sid, $termLabel, $year
            );
            // Fix param string: 1 int + 5 doubles + 2 strings + 1 int + 1 string + 1 int
            // Rebuild it correctly below
            $updStmt->close();
            $updStmt = $conn->prepare("
                UPDATE fee_records
                   SET grade = ?,
                       paid_amount    = paid_amount    + ?,
                       school_fee     = school_fee     + ?,
                       assessment_fee = assessment_fee + ?,
                       activity_fee   = activity_fee   + ?,
                       other_fee      = other_fee      + ?,
                       receipt_no     = ?,
                       payment_date   = NOW(),
                       recorded_by    = ?
                 WHERE student_id = ? AND term = ? AND year = ?
            ");
            $updStmt->bind_param(
                'idddddssisi',
                $grade,
                $total, $school, $assessment, $activity, $other,
                $receiptNo, $recordedBy,
                $sid, $termLabel, $year
            );
            $updStmt->execute();
        } else {
            $insStmt->bind_param(
                'iisiddddss',
                $sid, $grade, $termLabel, $year,
                $total, $school, $assessment, $activity, $other,
                $receiptNo, $recordedBy
            );
            $insStmt->execute();
        }

        // Audit log
        $logStmt->bind_param(
            'siisssssd s',
            $receiptNo, $sid, $grade, $termLabel, $year,
            $school, $assessment, $activity, $other,
            $total, $recordedBy
        );
        $logStmt->execute();

        $receipts[] = ['student_id' => $sid, 'receipt_no' => $receiptNo, 'total' => $total];
        $savedCount++;
    }

    $conn->commit();
    echo json_encode([
        'success'      => true,
        'saved'        => $savedCount,
        'receipts'     => $receipts,
        'message'      => "$savedCount payment(s) recorded.",
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

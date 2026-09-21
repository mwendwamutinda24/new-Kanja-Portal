<?php
// ============================================================
// api/save_fee_payments.php
//
// Persist fee entries for all students in one grade/term/year
// submission. Auto-creates the fee tables on first run.
//
// Accepts JSON (mobile) or form-encoded (web) bodies:
//   {
//     "grade": 6,
//     "term":  "3",
//     "year":  2026,
//     "school_fee":     { "284": 100, "285": 250, ... },
//     "assessment_fee": { ... },   // optional
//     "activity_fee":   { ... },   // optional
//     "other_fee":      { ... }    // optional
//   }
//
// Returns:
//   { success, saved, receipts:[{student_id, receipt_no, total}], message }
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

// ── CORS + JSON ────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Never leak PHP warnings into the JSON body
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// Convert fatal errors into a JSON reply instead of a blank 500
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Server error: ' . $e['message'],
            'file'    => basename($e['file']),
            'line'    => $e['line'],
        ]);
    }
});

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// ============================================================
// STEP 1 — AUTO-CREATE TABLES (idempotent: safe to run every call)
// ============================================================
function ensure_fee_tables(mysqli $conn) {
    $sqlFeeRecords = "
        CREATE TABLE IF NOT EXISTS `fee_records` (
            `id`              INT AUTO_INCREMENT PRIMARY KEY,
            `student_id`      INT NOT NULL,
            `grade`           INT NOT NULL,
            `term`            VARCHAR(20) NOT NULL,
            `year`            INT NOT NULL,

            `expected_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `paid_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,

            `school_fee`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `assessment_fee`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `activity_fee`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `other_fee`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,

            `receipt_no`      VARCHAR(40) DEFAULT NULL,
            `payment_date`    DATETIME DEFAULT CURRENT_TIMESTAMP,
            `recorded_by`     VARCHAR(80) DEFAULT NULL,

            UNIQUE KEY `uniq_student_term` (`student_id`, `term`, `year`),
            KEY `idx_grade_term_year` (`grade`, `term`, `year`),
            KEY `idx_receipt` (`receipt_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $sqlLog = "
        CREATE TABLE IF NOT EXISTS `fee_payments_log` (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `receipt_no`     VARCHAR(40) NOT NULL,
            `student_id`     INT NOT NULL,
            `grade`          INT NOT NULL,
            `term`           VARCHAR(20) NOT NULL,
            `year`           INT NOT NULL,

            `school_fee`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `assessment_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `activity_fee`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `other_fee`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_paid`     DECIMAL(10,2) NOT NULL,

            `recorded_by`    VARCHAR(80) DEFAULT NULL,
            `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,

            KEY `idx_receipt` (`receipt_no`),
            KEY `idx_student` (`student_id`, `term`, `year`),
            KEY `idx_grade_term_year` (`grade`, `term`, `year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $errors = [];
    if (!$conn->query($sqlFeeRecords)) $errors[] = 'fee_records: ' . $conn->error;
    if (!$conn->query($sqlLog))        $errors[] = 'fee_payments_log: ' . $conn->error;

    return $errors;
}

$tableErrors = ensure_fee_tables($conn);
if ($tableErrors) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Could not prepare fee tables: ' . implode(' | ', $tableErrors),
        'hint'    => 'Check DB user has CREATE TABLE privilege.',
    ]);
    exit;
}

// ============================================================
// STEP 2 — PARSE INPUT (JSON body or form POST)
// ============================================================
$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true);
if (!is_array($body)) $body = $_POST;

$grade    = isset($body['grade']) ? (int)$body['grade'] : 0;
$termNum  = isset($body['term'])  ? preg_replace('/[^0-9]/', '', (string)$body['term']) : '';
$year     = isset($body['year'])  ? (int)$body['year'] : 0;
$termLabel = $termNum !== '' ? "Term $termNum" : '';

$fields = ['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'];

// Normalise the payload: accept either nested arrays or "field[ID]" keys
$columnsByField = [];
foreach ($fields as $f) {
    if (isset($body[$f]) && is_array($body[$f])) {
        $columnsByField[$f] = $body[$f];
    } else {
        $columnsByField[$f] = [];
        foreach ($body as $k => $v) {
            if (preg_match('/^' . preg_quote($f, '/') . '\[(\d+)\]$/', $k, $m)) {
                $columnsByField[$f][$m[1]] = $v;
            }
        }
    }
}

// Union of student IDs found in any field
$studentIds = [];
foreach ($columnsByField as $col) {
    foreach (array_keys($col) as $sid) $studentIds[(int)$sid] = true;
}
$studentIds = array_keys($studentIds);

if (!$grade || $termLabel === '' || !$year) {
    echo json_encode(['success' => false, 'error' => 'Missing grade/term/year']);
    exit;
}
if (empty($studentIds)) {
    echo json_encode(['success' => false, 'error' => 'No student rows in payload']);
    exit;
}

$recordedBy = $_SESSION['teacher_name']
           ?? $_SESSION['username']
           ?? 'Staff';

// ============================================================
// STEP 3 — WRITE TRANSACTIONS
// ============================================================
$conn->begin_transaction();
try {
    $selStmt = $conn->prepare("
        SELECT id FROM fee_records
        WHERE student_id = ? AND term = ? AND year = ?
        LIMIT 1
    ");
    $insStmt = $conn->prepare("
        INSERT INTO fee_records
            (student_id, grade, term, year,
             expected_amount, paid_amount,
             school_fee, assessment_fee, activity_fee, other_fee,
             receipt_no, payment_date, recorded_by)
        VALUES (?, ?, ?, ?,
                0, ?,
                ?, ?, ?, ?,
                ?, NOW(), ?)
    ");
    $updStmt = $conn->prepare("
        UPDATE fee_records
           SET grade          = ?,
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

    $savedCount = 0;
    $receipts   = [];

    foreach ($studentIds as $sid) {
        $sid        = (int)$sid;
        $school     = (float)($columnsByField['school_fee'][$sid]     ?? 0);
        $assessment = (float)($columnsByField['assessment_fee'][$sid] ?? 0);
        $activity   = (float)($columnsByField['activity_fee'][$sid]   ?? 0);
        $other      = (float)($columnsByField['other_fee'][$sid]      ?? 0);
        $total      = $school + $assessment + $activity + $other;

        if ($total <= 0) continue; // teacher left this row blank

        $receiptNo = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Does a row already exist?
        $selStmt->bind_param('isi', $sid, $termLabel, $year);
        $selStmt->execute();
        $existing = $selStmt->get_result()->fetch_assoc();
        $selStmt->free_result();

        if ($existing) {
            // UPDATE — types: i d d d d d s s i s i
            $updStmt->bind_param(
                'idddddssisi',
                $grade,
                $total, $school, $assessment, $activity, $other,
                $receiptNo, $recordedBy,
                $sid, $termLabel, $year
            );
            $updStmt->execute();
        } else {
            // INSERT — types: i i s i d d d d d s s
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
            'siisssssds',
            $receiptNo, $sid, $grade, $termLabel, $year,
            $school, $assessment, $activity, $other,
            $total, $recordedBy
        );
        $logStmt->execute();

        $receipts[] = [
            'student_id' => $sid,
            'receipt_no' => $receiptNo,
            'total'      => $total,
        ];
        $savedCount++;
    }

    $conn->commit();

    echo json_encode([
        'success'  => true,
        'saved'    => $savedCount,
        'receipts' => $receipts,
        'message'  => $savedCount . ' payment' . ($savedCount === 1 ? '' : 's') . ' recorded.',
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Save failed: ' . $e->getMessage(),
    ]);
}

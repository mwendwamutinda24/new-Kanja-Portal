<?php
// ============================================================
// api/save_fee_payments.php
//
// Persist fee entries for all students in one grade/term/year
// submission. Each payment is inserted into `Fees` (one row per
// payment, with its receipt_no) and mirrored into
// `fee_payments_log`.
//
// Accepts JSON (mobile) or form-encoded (web) bodies:
//   { "grade": 6, "term": "3", "year": 2026,
//     "school_fee":     { "284": 100, "285": 250 },
//     "assessment_fee": { ... },   // optional
//     "activity_fee":   { ... },   // optional
//     "other_fee":      { ... } }  // optional
//
// Returns:
//   { success, saved, receipts:[{student_id, receipt_no, total}], message }
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ============================================================
// STEP 1 — SCHEMA PREPARATION (idempotent)
// Must run BEFORE begin_transaction(): ALTER/CREATE commit implicitly.
// ============================================================
function prepare_schema(mysqli $conn) {
    $errors = [];

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
    if (!$conn->query($sqlLog)) $errors[] = 'fee_payments_log: ' . $conn->error;

    // Fees must carry the receipt number (download_receipt.php reads it from Fees)
    $col = $conn->query("SHOW COLUMNS FROM Fees LIKE 'receipt_no'");
    if ($col && $col->num_rows === 0) {
        if (!$conn->query("ALTER TABLE Fees ADD COLUMN receipt_no VARCHAR(40) NULL, ADD INDEX idx_receipt (receipt_no)")) {
            $errors[] = 'Fees.receipt_no: ' . $conn->error;
        }
    }

    return $errors;
}

$schemaErrors = prepare_schema($conn);
if ($schemaErrors) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Could not prepare tables: ' . implode(' | ', $schemaErrors),
        'hint'    => 'Check DB user has CREATE/ALTER TABLE privilege.',
    ]);
    exit;
}

// ============================================================
// STEP 2 — PARSE INPUT (JSON body or form POST)
// ============================================================
$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true);
if (!is_array($body)) $body = $_POST;

$grade   = isset($body['grade']) ? (int)$body['grade'] : 0;
$termNum = isset($body['term'])  ? preg_replace('/[^0-9]/', '', (string)$body['term']) : '';
$year    = isset($body['year'])  ? (int)$body['year'] : 0;

if (!$grade || $termNum === '' || !$year) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing grade/term/year']);
    exit;
}
$termInt = (int)$termNum;

$fields = ['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'];

// Accept either nested arrays or "field[ID]" keys
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

$studentIds = [];
foreach ($columnsByField as $col) {
    foreach (array_keys($col) as $sid) $studentIds[(int)$sid] = true;
}
$studentIds = array_keys($studentIds);

if (empty($studentIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No student rows in payload']);
    exit;
}

$recordedBy = $_SESSION['teacher_name']
           ?? $_SESSION['username']
           ?? 'Staff';

// ============================================================
// STEP 3 — WRITE (single transaction: all rows or none)
// ============================================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn->begin_transaction();

    $stuStmt = $conn->prepare("
        SELECT Assesment, firstName, surname
        FROM Student
        WHERE id = ?
        LIMIT 1
    ");

    $feeStmt = $conn->prepare("
        INSERT INTO Fees
            (Assesment, StudentID, firstName, surname,
             Fee, AssesmentFee, Activity, other,
             Grade, Term, Year, receipt_no, payment_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
        $school     = max(0, (float)($columnsByField['school_fee'][$sid]     ?? 0));
        $assessment = max(0, (float)($columnsByField['assessment_fee'][$sid] ?? 0));
        $activity   = max(0, (float)($columnsByField['activity_fee'][$sid]   ?? 0));
        $other      = max(0, (float)($columnsByField['other_fee'][$sid]      ?? 0));
        $total      = $school + $assessment + $activity + $other;

        if ($total <= 0) continue; // blank row

        $stuStmt->bind_param('i', $sid);
        $stuStmt->execute();
        $stu = $stuStmt->get_result()->fetch_assoc();
        if (!$stu) continue; // unknown student id

        $receiptNo = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Fees row — types: s i s s d d d d i i i s  (12)
        $feeStmt->bind_param(
            'sissddddiiis',
            $stu['Assesment'], $sid, $stu['firstName'], $stu['surname'],
            $school, $assessment, $activity, $other,
            $grade, $termInt, $year, $receiptNo
        );
        $feeStmt->execute();

        // Audit log — types: s i i s i d d d d d s  (11)
        $logStmt->bind_param(
            'siisiddddds',
            $receiptNo, $sid, $grade, $termNum, $year,
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

    if ($savedCount === 0) {
        $conn->rollback();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No valid fee amounts to save.']);
        exit;
    }

    $conn->commit();

    echo json_encode([
        'success'  => true,
        'saved'    => $savedCount,
        'receipts' => $receipts,
        'message'  => $savedCount . ' payment' . ($savedCount === 1 ? '' : 's') . ' recorded successfully.',
    ]);

} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignore) {}
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Save failed: ' . $e->getMessage(),
    ]);
}

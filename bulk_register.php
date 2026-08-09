<?php
/*
 * bulk_register.php
 * =================
 * Place this file in the SAME folder as RegisterStudent.php.
 *
 * Accepts:  POST  Content-Type: application/json
 *           Body: { "students": [ { upi, assessment, firstName, middleName,
 *                                   surname, dob, grade, birthCert }, ... ] }
 *
 * Returns:  { "success": true,  "imported": N, "skipped": M, "errors": [...] }
 *        or { "success": false, "message": "reason" }
 *
 * FIXED vs previous version:
 *   1. error_reporting(0) + display_errors(0) hid every real PHP error,
 *      so failures came back as a useless generic JSON blob. We now log
 *      real errors to the PHP error log (visible to you, invisible to
 *      the client) instead of hiding them completely.
 *   2. Grade is safely handled whether or not Student.Grade allows NULL.
 *      Ideally run:  ALTER TABLE Student MODIFY Grade INT(11) NULL DEFAULT NULL;
 *      — but if that hasn't been run yet, a blank grade no longer crashes
 *      the whole row silently; it just gets flagged with a clear message.
 *   3. Any mysqli/prepare failure is now caught per-row and reported in
 *      the "errors" array with the real DB message, instead of vanishing.
 *
 * VALIDATION RULE (must match the client-side rule in RegisterStudent.php):
 *   Only firstName is required. Every other field may be blank:
 *     - UPI / Assessment / MiddleName / Surname / BirthCertNo -> stored as ''
 *     - DOB                                                    -> stored as NULL
 *     - Grade                                                  -> NULL if the
 *       column allows it, otherwise flagged as an error for that row so it's
 *       obvious in the response instead of failing silently.
 *   Because a row can arrive with no UPI and no Assessment, the duplicate
 *   check below only runs against whichever of those two fields was actually
 *   supplied — otherwise every blank-UPI/blank-Assessment row would look
 *   like a duplicate of every other one.
 */

/* ── Catch real errors quietly instead of hiding them ──
   display_errors stays OFF (never leak PHP errors into JSON output to
   the browser), but error_reporting stays ON and we log to the PHP
   error log so you can actually diagnose failures server-side. */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start(); // catch any accidental stray output before we're ready to respond

/* ── Headers ── */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

/* ── Only accept POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

/* ── Parse JSON body ── */
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['students']) || !is_array($body['students'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request body. Expected JSON with "students" array.'
    ]);
    exit;
}

$students = $body['students'];

if (count($students) === 0) {
    ob_end_clean();
    echo json_encode(['success' => true, 'imported' => 0, 'skipped' => 0, 'errors' => []]);
    exit;
}

/* ── Database connection ── */
include 'conn.php'; // must define $conn as a mysqli object

mysqli_report(MYSQLI_REPORT_OFF); // don't let mysqli throw exceptions and break JSON output

if (!isset($conn) || $conn->connect_error) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'conn.php did not set $conn')
    ]);
    exit;
}

/* ── Detect whether Student.Grade currently allows NULL ──
   Lets us give a clear per-row error instead of a silent DB failure
   if the ALTER TABLE hasn't been run yet. Checked once, not per row. */
$gradeAllowsNull = true;
$colCheck = $conn->query("SHOW COLUMNS FROM Student LIKE 'Grade'");
if ($colCheck && ($col = $colCheck->fetch_assoc())) {
    $gradeAllowsNull = (strtoupper($col['Null']) === 'YES');
}

/* ── Prepare insert statement ──
 * DOB and Grade are bound as nullable-capable columns. Passing PHP null
 * through bind_param sends SQL NULL regardless of the declared type
 * letter — that's fine for DOB (date, nullable) and for Grade as long
 * as Grade actually allows NULL in the schema (checked above). */
$insertStmt = $conn->prepare(
    "INSERT INTO Student
       (UPI, Assesment, firstName, middleName, surname, DOB, Grade, birthNo, role)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')"
);

if (!$insertStmt) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'DB prepare failed: ' . $conn->error]);
    exit;
}

$checkStmtCache = []; // reuse prepared duplicate-check statements per condition shape

/* ── Process rows ── */
$imported = 0;
$skipped  = 0;
$errors   = [];

foreach ($students as $idx => $s) {
    $rowLabel = 'Row ' . ($idx + 2); // +2 to match spreadsheet row numbers (header = row 1)

    $upi        = trim((string)($s['upi']        ?? ''));
    $assessment = trim((string)($s['assessment'] ?? ''));
    $firstName  = trim((string)($s['firstName']  ?? ''));
    $middleName = trim((string)($s['middleName'] ?? ''));
    $surname    = trim((string)($s['surname']    ?? ''));
    $dobRaw     = trim((string)($s['dob']        ?? ''));
    $gradeRaw   = trim((string)($s['grade']      ?? ''));
    $birthCert  = trim((string)($s['birthCert']  ?? ''));

    /* ── Server-side validation ──
     * Only firstName is required. Grade/DOB are only flagged if a value
     * was actually supplied but doesn't look valid — a blank value is
     * fine because both fields are optional (schema permitting). */
    $rowErrors = [];
    if ($firstName === '') {
        $rowErrors[] = 'First name missing';
    }
    if ($dobRaw !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobRaw)) {
        $rowErrors[] = 'DOB format invalid (expected YYYY-MM-DD)';
    }
    if ($gradeRaw !== '' && (!is_numeric($gradeRaw) || (int)$gradeRaw < 1 || (int)$gradeRaw > 9)) {
        $rowErrors[] = 'Grade out of range (1-9)';
    }
    if ($gradeRaw === '' && !$gradeAllowsNull) {
        // Grade column is still NOT NULL in the live schema — surface this
        // clearly instead of letting the INSERT fail with a cryptic error.
        $rowErrors[] = 'Grade required until Student.Grade is altered to allow NULL';
    }

    if (!empty($rowErrors)) {
        $skipped++;
        $errors[] = "$rowLabel: " . implode(', ', $rowErrors);
        continue;
    }

    $dob   = $dobRaw !== '' ? $dobRaw : null;
    $grade = $gradeRaw !== '' ? (int)$gradeRaw : null;

    /* ── Duplicate check ──
     * Only run this when at least one of UPI / Assessment was actually
     * supplied. Otherwise every blank-UPI, blank-Assessment row would
     * falsely "duplicate" every other blank one. */
    if ($upi !== '' || $assessment !== '') {
        $conditions = [];
        $types      = '';
        $params     = [];

        if ($upi !== '')        { $conditions[] = 'UPI = ?';       $types .= 's'; $params[] = $upi; }
        if ($assessment !== '') { $conditions[] = 'Assesment = ?'; $types .= 's'; $params[] = $assessment; }

        $cacheKey = $types; // 's' or 'ss' or 's' depending which fields present — shape is what matters
        if (!isset($checkStmtCache[$cacheKey])) {
            $checkSql = 'SELECT id FROM Student WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1';
            $stmt     = $conn->prepare($checkSql);
            if (!$stmt) {
                $skipped++;
                $errors[] = "$rowLabel: DB prepare failed for duplicate check — " . $conn->error;
                continue;
            }
            $checkStmtCache[$cacheKey] = $stmt;
        }
        $checkStmt = $checkStmtCache[$cacheKey];

        $checkStmt->bind_param($types, ...$params);
        $checkStmt->execute();
        $checkStmt->store_result();
        $isDuplicate = $checkStmt->num_rows > 0;
        $checkStmt->free_result();

        if ($isDuplicate) {
            $skipped++;
            $label = $upi !== '' ? "UPI ($upi)" : "Assessment ($assessment)";
            $errors[] = "$rowLabel: Duplicate $label — skipped";
            continue;
        }
    }

    /* Insert */
    $insertStmt->bind_param(
        'ssssssis',
        $upi, $assessment, $firstName, $middleName,
        $surname, $dob, $grade, $birthCert
    );

    if ($insertStmt->execute()) {
        $imported++;
    } else {
        $skipped++;
        $errors[] = "$rowLabel: Insert failed — " . $insertStmt->error;
        error_log("bulk_register.php insert failed for $rowLabel: " . $insertStmt->error);
    }
}

$insertStmt->close();
foreach ($checkStmtCache as $stmt) {
    $stmt->close();
}
$conn->close();

/* ── Discard any stray output and send clean JSON ── */
ob_end_clean();

echo json_encode([
    'success'  => true,
    'imported' => $imported,
    'skipped'  => $skipped,
    'errors'   => $errors
]);
exit;
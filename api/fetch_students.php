<?php
// ============================================================
// api/fetch_students.php
//
// Loads students for a given grade/term/year plus their fee status.
//
// Called by:
//   - Fee.php (web)          -> default HTML <tr> rows
//   - mobile app             -> ?format=json returns JSON
//
// Column mapping (matches the live Student table):
//   Student.id           -> id
//   Student.UPI          -> assessmentNo (falls back to STU-xxxx)
//   Student.firstName    -> firstName
//   Student.middleName   -> middleName
//   Student.surname      -> lastName
//   Student.Grade        -> filter (int, capital G)
//
// fee_records is OPTIONAL. If the table does not exist the query
// still returns all students (expected_amount and paid_amount = 0).
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

// ── CORS + JSON headers (harmless for the web caller) ───────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Never leak PHP warnings into the response body
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// ── Wrapper: exit cleanly with either JSON or HTML ──────────
function respond($format, $payload, $httpCode = 200) {
    http_response_code($httpCode);
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    } else {
        // HTML mode always returns a <tr> so the caller can inject it
        $msg = is_array($payload) && isset($payload['error'])
             ? $payload['error']
             : 'No students found.';
        echo '<tr><td colspan="11">'
           . '<div class="empty-state">'
           . '<div class="empty-icon"><i class="fa-solid fa-coins"></i></div>'
           . '<p>' . htmlspecialchars($msg) . '</p>'
           . '</div></td></tr>';
    }
    exit;
}

// ── Validate connection ────────────────────────────────────
if (!$conn) {
    respond('json', ['success' => false, 'error' => 'DB connection failed'], 500);
}

// ── Read + validate input ──────────────────────────────────
$format  = isset($_GET['format']) && strtolower($_GET['format']) === 'json' ? 'json' : 'html';
$grade   = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$termNum = isset($_GET['term'])  ? preg_replace('/[^0-9]/', '', (string)$_GET['term']) : '';
$year    = isset($_GET['year'])  ? (int)$_GET['year'] : 0;

if ($grade <= 0 || $termNum === '' || $year <= 0) {
    respond($format, [
        'success' => false,
        'error'   => 'Missing or invalid grade/term/year (got grade=' . $grade
                   . ', term=' . htmlspecialchars($termNum)
                   . ', year=' . $year . ')',
    ], 400);
}

$termLabel = "Term $termNum";  // matches how the web app / sms module store it

// ── Detect whether fee_records exists (avoids fatal JOIN error) ──
$hasFeeTable = false;
$check = $conn->query("SHOW TABLES LIKE 'fee_records'");
if ($check && $check->num_rows > 0) {
    $hasFeeTable = true;
}
if ($check) $check->free();

// ── Build the query: join fee_records only if the table exists ──
if ($hasFeeTable) {
    $sql = "
        SELECT s.id,
               s.UPI,
               s.firstName,
               s.middleName,
               s.surname,
               COALESCE(f.expected_amount, 0) AS expected_amount,
               COALESCE(f.paid_amount,     0) AS paid_amount
        FROM Student s
        LEFT JOIN fee_records f
               ON f.student_id = s.id
              AND f.term = ?
              AND f.year = ?
        WHERE s.`Grade` = ?
        ORDER BY s.firstName, s.surname
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond($format, ['success' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
    }
    // s=term, i=year, i=grade
    $stmt->bind_param('sii', $termLabel, $year, $grade);
} else {
    // No fee_records table yet — return students with zero balances
    $sql = "
        SELECT s.id,
               s.UPI,
               s.firstName,
               s.middleName,
               s.surname,
               0 AS expected_amount,
               0 AS paid_amount
        FROM Student s
        WHERE s.`Grade` = ?
        ORDER BY s.firstName, s.surname
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond($format, ['success' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
    }
    // i=grade
    $stmt->bind_param('i', $grade);
}

// ── Execute ────────────────────────────────────────────────
if (!$stmt->execute()) {
    respond($format, [
        'success' => false,
        'error'   => 'Query failed: ' . $stmt->error,
        'sql'     => $sql,
        'params'  => ['grade' => $grade, 'term' => $termLabel, 'year' => $year],
    ], 500);
}

$result = $stmt->get_result();
if (!$result) {
    respond($format, ['success' => false, 'error' => 'get_result failed: ' . $stmt->error], 500);
}

// ── Collect rows ───────────────────────────────────────────
$students = [];
while ($row = $result->fetch_assoc()) {
    // Prefer UPI as the assessment number; fall back to a padded student id
    $assessmentNo = !empty($row['UPI'])
        ? $row['UPI']
        : ('STU-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT));

    $students[] = [
        'id'              => (int)$row['id'],
        'assessmentNo'    => $assessmentNo,
        'firstName'       => $row['firstName'],
        'middleName'      => $row['middleName'],
        'lastName'        => $row['surname'],
        'expected_amount' => (float)$row['expected_amount'],
        'paid_amount'     => (float)$row['paid_amount'],
    ];
}
$stmt->close();

// ── Return ─────────────────────────────────────────────────
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'  => true,
        'count'    => count($students),
        'grade'    => $grade,
        'term'     => $termLabel,
        'year'     => $year,
        'students' => $students,
    ]);
    exit;
}

// HTML mode (for Fee.php). If empty, print the empty-state row.
if (empty($students)) {
    respond('html', [
        'error' => "No students found for Grade $grade — $termLabel $year.",
    ]);
}

foreach ($students as $s) {
    $expectedAttr = $s['expected_amount'] > 0 ? (string)$s['expected_amount'] : '';

    echo '<tr'
       . ' data-expected="'  . htmlspecialchars($expectedAttr) . '"'
       . ' data-paid="'      . htmlspecialchars((string)$s['paid_amount']) . '"'
       . ' data-student-id="' . (int)$s['id'] . '">';

    echo '<td class="cell-assess">' . htmlspecialchars($s['assessmentNo']) . '</td>';
    echo '<td class="cell-name">'   . htmlspecialchars($s['firstName'])   . '</td>';
    echo '<td class="cell-name">'   . htmlspecialchars($s['lastName'])    . '</td>';

    $expectedTxt = $expectedAttr !== ''
        ? 'KES ' . number_format($s['expected_amount'])
        : '—';
    $paidTxt = $s['paid_amount'] > 0
        ? 'KES ' . number_format($s['paid_amount'])
        : '—';

    echo '<td class="cell-expected">' . $expectedTxt . '</td>';
    echo '<td class="cell-paid">'     . $paidTxt     . '</td>';

    // Four fee-input cells per student row (school / assessment / activity / other)
    foreach (['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'] as $field) {
        echo '<td><input type="number" step="0.01" min="0"'
           . ' name="' . $field . '[' . (int)$s['id'] . ']"'
           . ' placeholder="0"></td>';
    }

    echo '</tr>';
}

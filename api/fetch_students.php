<?php
// ============================================================
// fetch_students.php
// Load students for a given grade/term/year along with their
// expected fee and amount already paid this term, so the front-end
// can compute balances live as the teacher types.
//
// Called by:
//   - Fee.php (web)          -> returns HTML <tr> rows
//   - api/fee_students.php   -> passes ?format=json
// ============================================================

session_start();
include 'conn.php';

if (!$conn) {
    http_response_code(500);
    exit('DB connection failed');
}

$grade = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$termNum = isset($_GET['term']) ? preg_replace('/[^0-9]/', '', $_GET['term']) : '';
$year  = isset($_GET['year'])  ? (int)$_GET['year'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if (!$grade || $termNum === '' || !$year) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Missing grade/term/year']);
    } else {
        echo '<tr><td colspan="11"><div class="empty-state"><p>Missing grade, term or year.</p></div></td></tr>';
    }
    exit;
}

$termLabel = "Term $termNum";

// LEFT JOIN fee_records so students who have never been billed still show up
$stmt = $conn->prepare("
    SELECT s.id,
           s.assessmentNo,
           s.firstName,
           s.surname,
           COALESCE(f.expected_amount, 0) AS expected_amount,
           COALESCE(f.paid_amount,     0) AS paid_amount
    FROM Student s
    LEFT JOIN fee_records f
           ON f.student_id = s.id
          AND f.term = ?
          AND f.year = ?
    WHERE s.grade = ?
    ORDER BY s.firstName, s.surname
");
$stmt->bind_param('sii', $termLabel, $year, $grade);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'id'              => (int)$row['id'],
        'assessmentNo'    => $row['assessmentNo'] ?: ('STU-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT)),
        'firstName'       => $row['firstName'],
        'lastName'        => $row['surname'],
        'expected_amount' => (float)$row['expected_amount'],
        'paid_amount'     => (float)$row['paid_amount'],
    ];
}
$stmt->close();

// ─── JSON mode (mobile app) ─────────────────────────────────
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}

// ─── HTML mode (web Fee.php) ────────────────────────────────
if (empty($students)) {
    echo '<tr><td colspan="11">
        <div class="empty-state">
          <div class="empty-icon"><i class="fa-solid fa-coins"></i></div>
          <p>No students found for Grade ' . $grade . ' — ' . htmlspecialchars($termLabel) . ' ' . $year . '.</p>
        </div>
    </td></tr>';
    exit;
}

foreach ($students as $s) {
    $expectedAttr = $s['expected_amount'] > 0 ? (string)$s['expected_amount'] : '';
    echo '<tr data-expected="' . htmlspecialchars($expectedAttr) . '"'
       . ' data-paid="' . htmlspecialchars((string)$s['paid_amount']) . '"'
       . ' data-student-id="' . (int)$s['id'] . '">';

    echo '<td class="cell-assess">' . htmlspecialchars($s['assessmentNo']) . '</td>';
    echo '<td class="cell-name">'   . htmlspecialchars($s['firstName'])   . '</td>';
    echo '<td class="cell-name">'   . htmlspecialchars($s['lastName'])    . '</td>';

    $expectedTxt = $expectedAttr !== '' ? 'KES ' . number_format($s['expected_amount']) : '—';
    $paidTxt     = $s['paid_amount'] > 0 ? 'KES ' . number_format($s['paid_amount'])     : '—';

    echo '<td class="cell-expected">' . $expectedTxt . '</td>';
    echo '<td class="cell-paid">'     . $paidTxt     . '</td>';

    // The four input cells the teacher fills in
    foreach (['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'] as $field) {
        echo '<td><input type="number" step="0.01" min="0" name="' . $field . '[' . $s['id'] . ']" placeholder="0"></td>';
    }
    // Row Total + Balance cells are appended by JS after insertion
    echo '</tr>';
}

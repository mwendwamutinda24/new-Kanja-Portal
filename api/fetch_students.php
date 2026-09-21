<?php
// ============================================================
// api/fetch_students.php
// Loads students for a given grade/term/year with their fee status.
// Returns HTML <tr> rows for Fee.php, or JSON with ?format=json
// for the mobile app.
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

if (!$conn) {
    http_response_code(500);
    exit('DB connection failed');
}

$grade   = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$termNum = isset($_GET['term'])  ? preg_replace('/[^0-9]/', '', $_GET['term']) : '';
$year    = isset($_GET['year'])  ? (int)$_GET['year'] : 0;
$format  = isset($_GET['format']) ? $_GET['format'] : 'html';

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

// ── Grade is stored as an INT (6 in your table), so compare as int ──
// ── Student primary name columns: firstName, middleName, surname    ──
// ── UPI is the closest match to an "assessment number" — use it    ──
$stmt = $conn->prepare("
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
    WHERE s.Grade = ?
    ORDER BY s.firstName, s.surname
");
if (!$stmt) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'SQL error: ' . $conn->error]);
    } else {
        echo '<tr><td colspan="11"><p>SQL error: ' . htmlspecialchars($conn->error) . '</p></td></tr>';
    }
    exit;
}
$stmt->bind_param('sii', $termLabel, $year, $grade);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'id'              => (int)$row['id'],
        'assessmentNo'    => $row['UPI'] ?: ('STU-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT)),
        'firstName'       => $row['firstName'],
        'middleName'      => $row['middleName'],
        'lastName'        => $row['surname'],
        'expected_amount' => (float)$row['expected_amount'],
        'paid_amount'     => (float)$row['paid_amount'],
    ];
}
$stmt->close();

// ── JSON mode (mobile) ──────────────────────────────────────
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => count($students), 'students' => $students]);
    exit;
}

// ── HTML mode (web Fee.php) ─────────────────────────────────
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

    foreach (['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'] as $field) {
        echo '<td><input type="number" step="0.01" min="0" name="' . $field . '[' . $s['id'] . ']" placeholder="0"></td>';
    }
    echo '</tr>';
}

<?php
// ============================================================
// api/fetch_students.php
//
// Loads students for a given grade/term/year plus their fee status,
// from the real fee tables:
//
//   FeeStructure: id, Grade (comma-list, e.g. "1,2,3,4,5"), Term, Year,
//                 ExpectedFee, ExpectedAssesmentFee, ExpectedActivity, ExpectedOther
//   Fees:         id, Assesment, StudentID, firstName, surname,
//                 Fee, AssesmentFee, Activity, other, Grade, Term, Year, payment_date
//                 (payments are cumulative — a learner can have several rows)
//
// Called by:
//   - Fee.php (web)  -> default HTML <tr> rows
//   - mobile app      -> ?format=json returns JSON
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

function respond($format, $payload, $httpCode = 200) {
    http_response_code($httpCode);
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    } else {
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

if (!$conn) {
    respond('json', ['success' => false, 'error' => 'DB connection failed'], 500);
}

$format = isset($_GET['format']) && strtolower($_GET['format']) === 'json' ? 'json' : 'html';
$grade  = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$term   = isset($_GET['term'])  ? (int) preg_replace('/[^0-9]/', '', (string) $_GET['term']) : 0;
$year   = isset($_GET['year'])  ? (int) $_GET['year'] : 0;

if ($grade <= 0 || $term <= 0 || $year <= 0) {
    respond($format, [
        'success' => false,
        'error'   => "Missing or invalid grade/term/year (got grade=$grade, term=$term, year=$year)",
    ], 400);
}

/* ── Expected fees for this grade/term/year.
   FeeStructure.Grade is a comma-list (e.g. "1,2,3,4,5"), so
   FIND_IN_SET matches this grade against that list. ── */
$expectedFee = $expectedAssess = $expectedActivity = $expectedOther = 0.0;

$stmt = $conn->prepare(
    "SELECT ExpectedFee, ExpectedAssesmentFee, ExpectedActivity, ExpectedOther
     FROM FeeStructure
     WHERE FIND_IN_SET(?, Grade) AND Term = ? AND Year = ?
     LIMIT 1"
);
if (!$stmt) {
    respond($format, ['success' => false, 'error' => 'Prepare failed (structure): ' . $conn->error], 500);
}
$stmt->bind_param('iii', $grade, $term, $year);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $expectedFee      = (float) $row['ExpectedFee'];
    $expectedAssess   = (float) $row['ExpectedAssesmentFee'];
    $expectedActivity = (float) $row['ExpectedActivity'];
    $expectedOther    = (float) $row['ExpectedOther'];
}
$stmt->close();
$expectedTotal = $expectedFee + $expectedAssess + $expectedActivity + $expectedOther;

/* ── Learners in this grade ── */
$stmt = $conn->prepare("SELECT id, Assesment, firstName, surname FROM Student WHERE Grade = ? ORDER BY firstName, surname");
if (!$stmt) {
    respond($format, ['success' => false, 'error' => 'Prepare failed (students): ' . $conn->error], 500);
}
$stmt->bind_param('i', $grade);
$stmt->execute();
$result = $stmt->get_result();

$byId = [];
while ($row = $result->fetch_assoc()) {
    $byId[(int) $row['id']] = [
        'id'                 => (int) $row['id'],
        'assessmentNo'       => $row['Assesment'],
        'firstName'          => $row['firstName'],
        'lastName'           => $row['surname'],
        'expected_fee'       => $expectedFee,
        'expected_assessment'=> $expectedAssess,
        'expected_activity'  => $expectedActivity,
        'expected_other'     => $expectedOther,
        'expected_amount'    => $expectedTotal,
        'paid_fee'           => 0.0,
        'paid_assessment'    => 0.0,
        'paid_activity'      => 0.0,
        'paid_other'         => 0.0,
        'paid_amount'        => 0.0,
    ];
}
$stmt->close();

/* ── Sum every payment row already recorded this term/year, per learner
   (Fees rows are cumulative — a learner can have several) ── */
if ($byId) {
    $ids = implode(',', array_map('intval', array_keys($byId)));
    $sql = "SELECT StudentID,
                   SUM(Fee) AS fee, SUM(AssesmentFee) AS assess,
                   SUM(Activity) AS activity, SUM(other) AS other
            FROM Fees
            WHERE Term = $term AND Year = $year AND StudentID IN ($ids)
            GROUP BY StudentID";
    $pr = mysqli_query($conn, $sql);
    if ($pr) {
        while ($p = mysqli_fetch_assoc($pr)) {
            $sid = (int) $p['StudentID'];
            if (!isset($byId[$sid])) continue;
            $fee = (float) $p['fee']; $assess = (float) $p['assess'];
            $activity = (float) $p['activity']; $other = (float) $p['other'];
            $byId[$sid]['paid_fee']        = $fee;
            $byId[$sid]['paid_assessment'] = $assess;
            $byId[$sid]['paid_activity']   = $activity;
            $byId[$sid]['paid_other']      = $other;
            $byId[$sid]['paid_amount']     = $fee + $assess + $activity + $other;
        }
    }
}

$students = array_values($byId);

/* ── JSON mode (app) ── */
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'  => true,
        'count'    => count($students),
        'grade'    => $grade,
        'term'     => $term,
        'year'     => $year,
        'students' => $students,
    ]);
    exit;
}

/* ── HTML mode (Fee.php) ── */
if (empty($students)) {
    respond('html', ['error' => "No students found for Grade $grade — Term $term $year."]);
}

foreach ($students as $s) {
    $expectedAttr = $s['expected_amount'] > 0 ? (string) $s['expected_amount'] : '';

    echo '<tr'
       . ' data-expected="'   . htmlspecialchars($expectedAttr) . '"'
       . ' data-paid="'       . htmlspecialchars((string) $s['paid_amount']) . '"'
       . ' data-student-id="' . (int) $s['id'] . '">';

    echo '<td class="cell-assess">' . htmlspecialchars($s['assessmentNo']) . '</td>';
    echo '<td class="cell-name">'   . htmlspecialchars($s['firstName'])    . '</td>';
    echo '<td class="cell-name">'   . htmlspecialchars($s['lastName'])     . '</td>';

    $expectedTxt = $expectedAttr !== '' ? 'KES ' . number_format($s['expected_amount']) : '—';
    $paidTxt     = $s['paid_amount'] > 0 ? 'KES ' . number_format($s['paid_amount']) : '—';

    echo '<td class="cell-expected">' . $expectedTxt . '</td>';
    echo '<td class="cell-paid">'     . $paidTxt     . '</td>';

    // Four fee-input cells: names map directly onto the Fees table columns
    foreach (['school_fee', 'assessment_fee', 'activity_fee', 'other_fee'] as $field) {
        echo '<td><input type="number" step="0.01" min="0"'
           . ' name="' . $field . '[' . (int) $s['id'] . ']"'
           . ' placeholder="0"></td>';
    }

    echo '</tr>';
}

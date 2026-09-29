<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

require __DIR__ . '/../conn.php';
require __DIR__ . '/auth_check.php';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'error' => 'GET required'], 405);
}

$session = require_auth();
if (!in_array($session['role'], ['hoi', 'Dhoi', 'teacher'], true)) {
    respond(['success' => false, 'error' => 'Not authorized.'], 403);
}

$grade = (int) ($_GET['grade'] ?? 0);
$term  = (int) ($_GET['term'] ?? 0);
$year  = (int) ($_GET['year'] ?? 0);

if ($grade <= 0 || $term <= 0 || $year <= 0) {
    respond(['success' => false, 'error' => 'grade, term and year are required.'], 400);
}

/* FeeStructure.Grade is a comma-separated list, e.g. "1,2,3,4,5" —
   FIND_IN_SET matches this grade against that list. */
$expected = ['fee' => 0.0, 'assessment' => 0.0, 'activity' => 0.0, 'other' => 0.0];
$stmt = $conn->prepare(
    "SELECT ExpectedFee, ExpectedAssesmentFee, ExpectedActivity, ExpectedOther
     FROM FeeStructure
     WHERE FIND_IN_SET(?, Grade) AND Term = ? AND Year = ?
     LIMIT 1"
);
$stmt->bind_param('iii', $grade, $term, $year);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $expected = [
        'fee'        => (float) $row['ExpectedFee'],
        'assessment' => (float) $row['ExpectedAssesmentFee'],
        'activity'   => (float) $row['ExpectedActivity'],
        'other'      => (float) $row['ExpectedOther'],
    ];
}

/* Learners in this grade */
$byId = [];
$stmt = $conn->prepare("SELECT id, Assesment, firstName, surname FROM Student WHERE Grade = ? ORDER BY firstName, surname");
$stmt->bind_param('i', $grade);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $byId[(int) $r['id']] = [
        'id'                => (int) $r['id'],
        'assessmentNo'      => $r['Assesment'],
        'firstName'         => $r['firstName'],
        'lastName'          => $r['surname'],
        'expected_fee'          => $expected['fee'],
        'expected_assessment'   => $expected['assessment'],
        'expected_activity'     => $expected['activity'],
        'expected_other'        => $expected['other'],
        'paid_fee'          => 0.0,
        'paid_assessment'   => 0.0,
        'paid_activity'     => 0.0,
        'paid_other'        => 0.0,
    ];
}

/* Sum every payment row already recorded this term/year, per learner
   (payments are cumulative — a learner can have several rows) */
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
            $byId[$sid]['paid_fee']        = (float) $p['fee'];
            $byId[$sid]['paid_assessment'] = (float) $p['assess'];
            $byId[$sid]['paid_activity']   = (float) $p['activity'];
            $byId[$sid]['paid_other']      = (float) $p['other'];
        }
    }
}

respond(['success' => true, 'students' => array_values($byId)]);

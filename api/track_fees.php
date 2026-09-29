<?php
// ============================================================
// api/track_fees.php
//
// Fee tracking summary for the Track Fees screen.
//   GET ?grade=6&term=3&year=2026&search=amina
//   (grade / term omitted or non-numeric = all)
//
// Returns:
//   { success, stats:{totalCollected,totalOutstanding,learnersWithBalance},
//     learners:[{id,initials,name,grade,schoolFee,paid,balance,status}] }
//
// schoolFee = expected total from FeeStructure (summed over the
// selected terms), paid = SUM of Fees rows for the same filter.
// ============================================================

session_start();
include __DIR__ . '/../conn.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

function fail($msg, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

if (!$conn) fail('DB connection failed');

// ── Inputs ────────────────────────────────────────────────
$grade  = (int) preg_replace('/[^0-9]/', '', (string) ($_GET['grade'] ?? ''));
$term   = (int) preg_replace('/[^0-9]/', '', (string) ($_GET['term']  ?? ''));
$year   = (int) ($_GET['year'] ?? 0);
if ($year <= 0) $year = (int) date('Y');
$search = trim((string) ($_GET['search'] ?? ''));

// ── Expected fee per grade (FeeStructure.Grade is a comma-list) ──
$sql    = "SELECT Grade, Term, ExpectedFee, ExpectedAssesmentFee, ExpectedActivity, ExpectedOther
           FROM FeeStructure WHERE Year = ?";
$types  = 'i';
$params = [$year];
if ($term > 0) {
    $sql    .= " AND Term = ?";
    $types  .= 'i';
    $params[] = $term;
}
$stmt = $conn->prepare($sql);
if (!$stmt) fail('Prepare failed (structure): ' . $conn->error);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$expectedByGrade = [];   // grade => total expected over the selected terms
$seen = [];              // "grade|term" => true, avoids double counting duplicates
while ($r = $res->fetch_assoc()) {
    $rowTotal = (float) $r['ExpectedFee'] + (float) $r['ExpectedAssesmentFee']
              + (float) $r['ExpectedActivity'] + (float) $r['ExpectedOther'];
    foreach (explode(',', (string) $r['Grade']) as $g) {
        $g = (int) trim($g);
        if ($g <= 0) continue;
        $key = $g . '|' . (int) $r['Term'];
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $expectedByGrade[$g] = ($expectedByGrade[$g] ?? 0) + $rowTotal;
    }
}
$stmt->close();

// ── Learners (grade + search filters) ─────────────────────
$where  = [];
$types  = '';
$params = [];
if ($grade > 0) {
    $where[]  = 'Grade = ?';
    $types   .= 'i';
    $params[] = $grade;
}
if ($search !== '') {
    $where[]  = '(firstName LIKE ? OR surname LIKE ? OR Assesment LIKE ? OR CONCAT(firstName, " ", surname) LIKE ?)';
    $like     = '%' . $search . '%';
    $types   .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
$sql = "SELECT id, Assesment, firstName, surname, Grade FROM Student"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY Grade, firstName, surname";

$stmt = $conn->prepare($sql);
if (!$stmt) fail('Prepare failed (students): ' . $conn->error);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$byId = [];
while ($r = $res->fetch_assoc()) {
    $sid = (int) $r['id'];
    $g   = (int) $r['Grade'];
    $first = trim((string) $r['firstName']);
    $last  = trim((string) $r['surname']);
    $byId[$sid] = [
        'id'        => $sid,
        'initials'  => strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1)),
        'name'      => trim($first . ' ' . $last),
        'grade'     => 'Grade ' . $g,
        'schoolFee' => (float) ($expectedByGrade[$g] ?? 0),
        'paid'      => 0.0,
        'balance'   => 0.0,
        'status'    => 'Unpaid',
    ];
}
$stmt->close();

// ── Payments per learner (same year / optional term) ──────
if ($byId) {
    $ids = implode(',', array_map('intval', array_keys($byId)));
    $sql = "SELECT StudentID, SUM(Fee + AssesmentFee + Activity + other) AS paid
            FROM Fees
            WHERE Year = " . (int) $year
         . ($term > 0 ? " AND Term = " . (int) $term : '')
         . " AND StudentID IN ($ids)
            GROUP BY StudentID";
    $pr = $conn->query($sql);
    if ($pr) {
        while ($p = $pr->fetch_assoc()) {
            $sid = (int) $p['StudentID'];
            if (isset($byId[$sid])) $byId[$sid]['paid'] = (float) $p['paid'];
        }
    }
}

// ── Balance, status, stats ────────────────────────────────
$totalCollected = 0.0;
$totalOutstanding = 0.0;
$withBalance = 0;

foreach ($byId as &$l) {
    $l['balance'] = max(0.0, $l['schoolFee'] - $l['paid']);

    if ($l['paid'] > 0 && $l['balance'] <= 0.004) {
        $l['status'] = 'Paid';
    } elseif ($l['paid'] > 0) {
        $l['status'] = 'Partial';
    } else {
        $l['status'] = 'Unpaid';
    }

    $totalCollected   += $l['paid'];
    $totalOutstanding += $l['balance'];
    if ($l['balance'] > 0.004) $withBalance++;
}
unset($l);

echo json_encode([
    'success' => true,
    'stats'   => [
        'totalCollected'      => $totalCollected,
        'totalOutstanding'    => $totalOutstanding,
        'learnersWithBalance' => $withBalance,
    ],
    'learners' => array_values($byId),
]);

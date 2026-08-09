<?php
/**
 * /api/hoi/dashboard.php
 *
 * JSON data endpoint for the mobile Head-of-Institution dashboard
 * (HoiDashboard.tsx). Mirrors the exact query logic already used in
 * Hoi.php, but returns a single JSON payload matching the DashboardData
 * shape the app expects instead of rendering HTML.
 *
 * Expected by the app:
 *   const res = await apiRequest<DashboardData>('/hoi/dashboard.php');
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/../../conn.php'; // adjust path if conn.php lives elsewhere relative to /api/hoi

function fail($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

if (!isset($conn) || !$conn) {
    fail('Database connection unavailable.');
}

try {

    // ---------- Top stat cards ----------

    $totalStudents = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM Student"))['c'] ?? 0);
    $totalTeachers = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM Teachers"))['c'] ?? 0);
    $totalClasses = 9;

    // ---------- Term finance totals ----------

    function termFinance($conn, $term) {
        $row = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COALESCE(SUM(Fee),0) AS fee,
                    COALESCE(SUM(Assesment),0) AS assess,
                    COALESCE(SUM(Activity),0) AS activity,
                    COALESCE(SUM(Other),0) AS other
             FROM Fees WHERE Term=" . (int) $term
        ));
        return [
            'schoolFee'  => (float) ($row['fee'] ?? 0),
            'assessment' => (float) ($row['assess'] ?? 0),
            'activity'   => (float) ($row['activity'] ?? 0),
            'other'      => (float) ($row['other'] ?? 0),
        ];
    }

    $t1 = termFinance($conn, 1);
    $t2 = termFinance($conn, 2);
    $t3 = termFinance($conn, 3);

    $termSum = fn($t) => $t['schoolFee'] + $t['assessment'] + $t['activity'] + $t['other'];
    $grandTotal = $termSum($t1) + $termSum($t2) + $termSum($t3);

    // ---------- Fee data per grade ----------

    $gradeFeeRes = mysqli_query(
        $conn,
        "SELECT Grade,
                COALESCE(SUM(Fee),0) AS fee, COALESCE(SUM(Assesment),0) AS assess,
                COALESCE(SUM(Activity),0) AS activity, COALESCE(SUM(Other),0) AS other
         FROM Fees GROUP BY Grade ORDER BY Grade+0"
    );
    $gradeFeeByGrade = [];
    while ($row = mysqli_fetch_assoc($gradeFeeRes)) {
        $gradeFeeByGrade[(int) $row['Grade']] = $row;
    }

    $gradeStudentRes = mysqli_query($conn, "SELECT Grade, COUNT(*) AS cnt FROM Student GROUP BY Grade ORDER BY Grade+0");
    $gradeStudents = [];
    while ($r = mysqli_fetch_assoc($gradeStudentRes)) {
        $gradeStudents[(int) $r['Grade']] = (int) $r['cnt'];
    }

    $gradeFeePaidRes = mysqli_query(
        $conn,
        "SELECT Grade, COUNT(DISTINCT Assesment) AS paid_students,
                COALESCE(SUM(Fee+Assesment+Activity+Other),0) AS total_paid
         FROM Fees GROUP BY Grade ORDER BY Grade+0"
    );
    $gradeFeePaid = [];
    while ($r = mysqli_fetch_assoc($gradeFeePaidRes)) {
        $gradeFeePaid[(int) $r['Grade']] = $r;
    }

    $feeByGrade = [];
    for ($g = 1; $g <= 9; $g++) {
        $enrolled = $gradeStudents[$g] ?? 0;
        $paid     = (int) ($gradeFeePaid[$g]['paid_students'] ?? 0);
        $fee      = (float) ($gradeFeeByGrade[$g]['fee'] ?? 0);
        $assess   = (float) ($gradeFeeByGrade[$g]['assess'] ?? 0);
        $act      = (float) ($gradeFeeByGrade[$g]['activity'] ?? 0);
        $other    = (float) ($gradeFeeByGrade[$g]['other'] ?? 0);
        $coverage = $enrolled > 0 ? round($paid / $enrolled * 100) : 0;
        $missing  = max(0, $enrolled - $paid);

        $feeByGrade[] = [
            'grade'           => "Grade $g",
            'enrolled'        => $enrolled,
            'withRecords'     => $paid,
            'schoolFee'       => $fee,
            'assessment'      => $assess,
            'activity'        => $act,
            'other'           => $other,
            'coveragePercent' => (int) $coverage,
            'withoutRecords'  => $missing,
        ];
    }

    // ---------- Academic performance ----------

    $perfRes = mysqli_query(
        $conn,
        "SELECT grade, term, exam_type, year,
                AVG(math+eng+kisw+sst+scie+ca+agri+re+pretec) AS total_mean,
                (AVG(math)+AVG(eng)+AVG(kisw)+AVG(sst)+AVG(scie)+AVG(ca)+AVG(agri)+AVG(`re`)+AVG(pretec))/9 AS subject_mean
         FROM exam2
         GROUP BY grade, term, exam_type, year
         ORDER BY year, grade+0, term, exam_type"
    );
    $perfRows = [];
    while ($r = mysqli_fetch_assoc($perfRes)) {
        $perfRows[] = $r;
    }

    // Last row encountered per grade, in (year, grade, term, exam_type) order — matches Hoi.php's logic.
    $latestGradePerf = [];
    foreach ($perfRows as $p) {
        $latestGradePerf[(int) $p['grade']] = $p;
    }

    function examBand($mean) {
        if ($mean === null) return null;
        if ($mean > 74) return 'E.E';
        if ($mean > 49) return 'M.E';
        if ($mean > 25) return 'A.E';
        return 'B.E';
    }

    $academicByGrade = [];
    $gradeExamMeans  = [];
    for ($g = 1; $g <= 9; $g++) {
        $mean = isset($latestGradePerf[$g]) ? round((float) $latestGradePerf[$g]['subject_mean'], 1) : null;
        $academicByGrade[] = [
            'grade'   => "Grade $g",
            'average' => $mean ?? 0,
        ];
        $gradeExamMeans[] = [
            'grade' => "Grade $g",
            'mean'  => $mean ?? 0,
            'band'  => examBand($mean),
        ];
    }

    // ---------- Achievement bands (all exams) ----------

    $bandCounts = ['Exceeding' => 0, 'Meeting' => 0, 'Approaching' => 0, 'Below' => 0];
    $bandRes = mysqli_query($conn, "SELECT (math+eng+kisw+sst+scie+ca+agri+re+pretec)/9 AS avg_score FROM exam2");
    while ($b = mysqli_fetch_assoc($bandRes)) {
        $s = (float) $b['avg_score'];
        if ($s > 74) $bandCounts['Exceeding']++;
        elseif ($s > 49) $bandCounts['Meeting']++;
        elseif ($s > 25) $bandCounts['Approaching']++;
        else $bandCounts['Below']++;
    }
    $achievementBands = [];
    foreach ($bandCounts as $band => $count) {
        $achievementBands[] = ['band' => $band, 'count' => $count];
    }

    // ---------- Performance timeline (per-grade series across all exams) ----------

    $allLabels = [];
    foreach ($perfRows as $p) {
        $lbl = "T{$p['term']} " . ucfirst($p['exam_type']) . " {$p['year']}";
        if (!in_array($lbl, $allLabels, true)) {
            $allLabels[] = $lbl;
        }
    }

    $colors9 = ['#6C5CE7', '#26A65B', '#E0A63C', '#F0645F', '#3AB0D8', '#A78BFA', '#FB923C', '#34D399', '#F472B6'];
    $series = [];
    for ($g = 1; $g <= 9; $g++) {
        $pts = [];
        foreach ($allLabels as $lbl) {
            $found = 0; // chart-kit (mobile) can't render nulls, so gaps default to 0
            foreach ($perfRows as $p) {
                $plbl = "T{$p['term']} " . ucfirst($p['exam_type']) . " {$p['year']}";
                if ((int) $p['grade'] === $g && $plbl === $lbl) {
                    $found = round((float) $p['subject_mean'], 1);
                    break;
                }
            }
            $pts[] = $found;
        }
        if (array_sum($pts) > 0) {
            $series[] = [
                'grade' => "Grade $g",
                'color' => $colors9[$g - 1],
                'data'  => $pts,
            ];
        }
    }

    // ---------- Full exam results summary table ----------

    $examTypeLabels = ['opener' => 'Opener', 'midterm' => 'Mid-Term', 'endterm' => 'End Term'];
    $examResultsSummary = [];
    foreach ($perfRows as $p) {
        $examResultsSummary[] = [
            'grade'    => "Grade {$p['grade']}",
            'term'     => "Term {$p['term']}",
            'examType' => $examTypeLabels[$p['exam_type']] ?? ucfirst($p['exam_type']),
            'year'     => (int) $p['year'],
        ];
    }

    // ---------- Teaching staff ----------

    $teachersRes = mysqli_query($conn, "SELECT name, email FROM Teachers");
    $teachingStaffList = [];
    while ($row = mysqli_fetch_assoc($teachersRes)) {
        $teachingStaffList[] = [
            'name'  => $row['name'],
            'email' => $row['email'],
        ];
    }

    // ---------- Assemble payload ----------

    $payload = [
        'totalLearners'        => $totalStudents,
        'teachingStaff'        => $totalTeachers,
        'classes'               => $totalClasses,
        'totalRevenue'          => $grandTotal,
        'terms'                 => [
            'term1' => $t1,
            'term2' => $t2,
            'term3' => $t3,
        ],
        'feeByGrade'            => $feeByGrade,
        'academicByGrade'       => $academicByGrade,
        'achievementBands'      => $achievementBands,
        'gradeExamMeans'        => $gradeExamMeans,
        'performanceTimeline'   => [
            'examLabels' => $allLabels,
            'series'     => $series,
        ],
        'examResultsSummary'    => $examResultsSummary,
        'teachingStaffList'     => $teachingStaffList,
    ];

    echo json_encode($payload, JSON_NUMERIC_CHECK);

} catch (Throwable $e) {
    fail('Failed to build dashboard data: ' . $e->getMessage());
}

<?php
// ============================================================
// student_dashboard.php — Student Portal
// Stephen Kanja School Management System
// ============================================================
session_start();
include 'conn.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ── Require login ──────────────────────────────────────────
// index.php stores the Assessment Number in $_SESSION['username']
// (not 'student_assess' — that was from an earlier draft login page).
if (empty($_SESSION['loggedin']) || empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}
$assess = mysqli_real_escape_string($conn, $_SESSION['username']);

// ── Student info (always from session-matched record, never a guess) ──
$stu_res = mysqli_query($conn, "SELECT * FROM Student WHERE Assesment='$assess' LIMIT 1");
$student = $stu_res ? mysqli_fetch_assoc($stu_res) : null;

// If the session points at a student that no longer exists, force re-login
if (!$student) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$grade = $student['Grade'];
$name  = htmlspecialchars($student['firstName'] . ' ' . $student['surname']);

// ── Active section ────────────────────────────────────────────
$section = isset($_GET['section']) ? $_GET['section'] : 'home';

// ── exam2 is stored WIDE (one column per subject) ─────────────
// Map of DB column => display name + color theme key
$subject_map = [
    'math'   => 'Mathematics',
    'eng'    => 'English',
    'kisw'   => 'Kiswahili',
    'sst'    => 'Social Studies',
    'scie'   => 'Science',
    'ca'     => 'CA',
    'agri'   => 'Agriculture',
    're'     => 'RE',
    'pretec' => 'Pre-Technical',
];
// Assume scores are out of 100 unless a row says otherwise (no max_score column exists in exam2)
$default_max = 100;

$sel_term = isset($_GET['term']) ? $_GET['term'] : 'Term 1';
$sel_year = isset($_GET['year']) ? (int)$_GET['year'] : 2026;

// ── FIX: exam2.term is stored inconsistently — the SAME student/year can
// have one row with term = "Term 1" and another row with term = "1", and
// a student's subjects are sometimes split across those two rows (each row
// has 0 for whatever subject wasn't entered on it). The old query did an
// exact match on term='Term 1' with LIMIT 1, so it silently grabbed only
// one of the rows and lost whatever subjects lived in the other one.
//
// Fix: build every spelling of the selected term ("Term 1", "term 1", "1")
// and match any of them, then GROUP BY Assesment and take MAX() of every
// subject column so a 0 in one row never hides a real score sitting in a
// sibling row for the same student/term/year.
$term_num = preg_replace('/[^0-9]/', '', $sel_term);
if ($term_num === '') { $term_num = '1'; }
$term_variants = array_unique(array("Term $term_num", "term $term_num", $term_num));
$term_variants_esc = array();
foreach ($term_variants as $tv) {
    $term_variants_esc[] = "'" . mysqli_real_escape_string($conn, $tv) . "'";
}
$term_in = implode(',', $term_variants_esc);

$subj_cols = implode(', ', array_keys($subject_map));
$subj_cols_max_parts = array();
foreach (array_keys($subject_map) as $c) {
    $subj_cols_max_parts[] = "MAX($c) AS $c";
}
$subj_cols_max = implode(', ', $subj_cols_max_parts);

// ── My results: unpivot + combine my own exam2 row(s) for the selected term/year ──
$my_exam_res = mysqli_query($conn,
    "SELECT $subj_cols_max FROM exam2
     WHERE Assesment='$assess' AND term IN ($term_in) AND year=$sel_year
     GROUP BY Assesment
     LIMIT 1"
);
$my_results = [];
$total_score = 0; $total_max = 0;
if ($my_exam_res && ($row = mysqli_fetch_assoc($my_exam_res))) {
    foreach ($subject_map as $col => $label) {
        $score = isset($row[$col]) ? (float)$row[$col] : 0;
        // Treat a 0 in a subject column as "not taken" so blank subjects don't drag the average down
        if ($score <= 0) continue;
        $my_results[] = ['subject' => $label, 'score' => $score, 'max_score' => $default_max];
        $total_score += $score;
        $total_max   += $default_max;
    }
}
$my_avg = $total_max > 0 ? round(($total_score / $total_max) * 100, 1) : 0;

// ── Grade-wide performance: unpivot + combine every exam2 row for this grade/term/year ──
$grade_perf = []; // subject => ['scores'=>[], 'count_students'=>n]
$grade_rows_res = mysqli_query($conn,
    "SELECT Assesment, $subj_cols_max FROM exam2
     WHERE grade='$grade' AND term IN ($term_in) AND year=$sel_year
     GROUP BY Assesment"
);
$grade_assess_pct = []; // Assesment => overall pct, for ranking
if ($grade_rows_res) {
    while ($gr = mysqli_fetch_assoc($grade_rows_res)) {
        $stu_total = 0; $stu_max = 0;
        foreach ($subject_map as $col => $label) {
            $score = isset($gr[$col]) ? (float)$gr[$col] : 0;
            if ($score <= 0) continue;
            $grade_perf[$label]['scores'][] = $score;
            $stu_total += $score;
            $stu_max   += $default_max;
        }
        if ($stu_max > 0) {
            $grade_assess_pct[$gr['Assesment']] = ($stu_total / $stu_max) * 100;
        }
    }
}
// Reduce raw score arrays into avg/top/low percentages
foreach ($grade_perf as $label => &$gp) {
    $scores = $gp['scores'];
    $gp['avg_pct'] = round((array_sum($scores) / count($scores)), 1);
    $gp['top_pct'] = round(max($scores), 1);
    $gp['low_pct'] = round(min($scores), 1);
    $gp['student_count'] = count($scores);
}
unset($gp);

// Grade overall average (mean of each student's overall %)
$grade_avg = count($grade_assess_pct) > 0
    ? round(array_sum($grade_assess_pct) / count($grade_assess_pct), 1)
    : 0;

// My rank in grade
arsort($grade_assess_pct); // highest first, preserves Assesment keys
$my_rank = 0; $rank_pos = 0;
foreach ($grade_assess_pct as $a => $pct) {
    $rank_pos++;
    if ($a === $assess) { $my_rank = $rank_pos; break; }
}
$total_students_in_grade = count($grade_assess_pct);

// ── Attendance summary (table exists but has no data yet) ─────
$att_month_start = date('Y-m-01');
$att_month_end   = date('Y-m-t');
$att = ['Present'=>0,'Absent'=>0,'Late'=>0];
$att_res = mysqli_query($conn, "SHOW TABLES LIKE 'attendance'");
if ($att_res && mysqli_num_rows($att_res) > 0) {
    $a_res = mysqli_query($conn,
        "SELECT status, COUNT(*) as cnt FROM attendance
         WHERE Assesment='$assess' AND attendance_date BETWEEN '$att_month_start' AND '$att_month_end'
         GROUP BY status"
    );
    if ($a_res) while ($a = mysqli_fetch_assoc($a_res)) $att[$a['status']] = (int)$a['cnt'];
}
$att_total = array_sum($att);
$att_pct   = $att_total > 0 ? round(($att['Present'] / $att_total) * 100) : null;

// ── Fees: Fee + AssesmentFee + Activity + other = amounts PAID per record ──
// There is no "amount due" column anywhere in this data, so we show total
// paid and a per-record breakdown, and we are explicit in the UI that no
// balance can be calculated until a fee structure / amount-due source exists.
$fees_res = mysqli_query($conn,
    "SELECT Fee, AssesmentFee, Activity, other, Grade, Term, Year, payment_date
     FROM Fees WHERE Assesment='$assess' ORDER BY Year DESC, Term DESC, payment_date DESC"
);
$fees_list = [];
$total_paid = 0;
if ($fees_res) {
    while ($f = mysqli_fetch_assoc($fees_res)) {
        $row_total = (float)$f['Fee'] + (float)$f['AssesmentFee'] + (float)$f['Activity'] + (float)$f['other'];
        $f['row_total'] = $row_total;
        $fees_list[] = $f;
        $total_paid += $row_total;
    }
}

// ── Grade-wide fee totals (for context only — payments, not charges) ──
$grade_fees_res = mysqli_query($conn,
    "SELECT Term, Year, AVG(Fee+AssesmentFee+Activity+other) as avg_paid, COUNT(*) as record_count
     FROM Fees WHERE Grade='$grade' GROUP BY Term, Year ORDER BY Year DESC, Term DESC LIMIT 10"
);
$grade_fees = [];
if ($grade_fees_res) while ($gf = mysqli_fetch_assoc($grade_fees_res)) $grade_fees[] = $gf;

// ── Notices (table exists, may be empty) ───────────────────────
$notices = [];          // latest 5, for the Home preview
$all_notices = [];      // full list, for the Notices page (optionally filtered by type)
$nfilter = isset($_GET['ntype']) ? $_GET['ntype'] : '';

$not_check = mysqli_query($conn, "SHOW TABLES LIKE 'notices'");
if ($not_check && mysqli_num_rows($not_check) > 0) {
    $notices_res = mysqli_query($conn, "SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
    if ($notices_res) while ($n = mysqli_fetch_assoc($notices_res)) $notices[] = $n;

    $nwhere = '';
    if (in_array($nfilter, ['info','urgent','event'], true)) {
        $nfilter_esc = mysqli_real_escape_string($conn, $nfilter);
        $nwhere = "WHERE type='$nfilter_esc'";
    }
    $all_notices_res = mysqli_query($conn, "SELECT * FROM notices $nwhere ORDER BY created_at DESC");
    if ($all_notices_res) while ($n = mysqli_fetch_assoc($all_notices_res)) $all_notices[] = $n;
}

// ── Timetable for my grade (table exists, may be empty) ───────
$days    = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$periods = ['7:30 - 8:30','8:30 - 9:30','9:30 - 10:00','10:00 - 11:00','11:00 - 12:00','12:00 - 1:00','1:00 - 2:00'];
$tt_grid = [];
$tt_check = mysqli_query($conn, "SHOW TABLES LIKE 'timetable'");
if ($tt_check && mysqli_num_rows($tt_check) > 0 && !empty($grade)) {
    $grade_esc = mysqli_real_escape_string($conn, $grade);
    // Timetable also mixes "Grade 4" / "4" style strings, so match both.
    $tt_res = mysqli_query($conn, "SELECT * FROM timetable WHERE grade='Grade $grade_esc' OR grade='$grade_esc'");
    if ($tt_res) while ($tt = mysqli_fetch_assoc($tt_res)) $tt_grid[$tt['day']][$tt['period']] = $tt;
}

// ── Helper: grade letter ──────────────────────────────────────
function gradeLabel($pct) {
    if ($pct >= 80) return ['A', '#166534', '#dcfce7'];
    if ($pct >= 70) return ['B', '#1e40af', '#dbeafe'];
    if ($pct >= 60) return ['C', '#854d0e', '#fef9c3'];
    if ($pct >= 50) return ['D', '#9a3412', '#ffedd5'];
    return ['E', '#b91c1c', '#fde8e8'];
}

$subject_colors = [
    'Mathematics'=>['#dbeafe','#1e40af'],'English'=>['#dcfce7','#166534'],
    'Kiswahili'=>['#fde8e8','#b91c1c'],'Science'=>['#fef9c3','#854d0e'],
    'Social Studies'=>['#ede9fe','#6d28d9'],'CRE'=>['#fce7f3','#9d174d'],
    'Art & Craft'=>['#ffedd5','#9a3412'],'PE'=>['#cffafe','#155e75'],
    'Agriculture'=>['#d1fae5','#065f46'],'RE'=>['#fce7f3','#9d174d'],
    'Pre-Technical'=>['#e0e7ff','#3730a3'],'CA'=>['#f3f4f6','#374151'],
    'Break'=>['#f3f4f6','#4b5563'],'Lunch Break'=>['#f3f4f6','#4b5563'],
];

$url_base = '?';
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $name ?> — Student Portal · Stephen Kanja School</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --gold:#f0c040;--gold-dim:#c9a030;--gold-pale:#fdf3c0;
    --black:#111111;--dark:#1a1a1a;--mid:#2a2a2a;
    --bg:#f4f4f2;--bg-card:#ffffff;--bg-input:#f8f8f6;
    --text-primary:#1a1a18;--text-secondary:#5f5e5a;--text-tertiary:#888780;
    --border:rgba(0,0,0,0.09);--radius-md:8px;--radius-lg:14px;
    --sidebar-w:248px;
    --green:#16a34a;--red:#dc2626;--blue:#2563eb;--amber:#d97706;--purple:#7c3aed;
    --shadow-sm:0 1px 3px rgba(0,0,0,.06);
    --shadow-md:0 6px 20px rgba(0,0,0,.07);
    --shadow-lg:0 18px 44px rgba(0,0,0,.12);
  }
  html{scroll-behavior:smooth}
  body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text-primary);min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}

  /* ── Overlay ── */
  .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;backdrop-filter:blur(2px)}
  .overlay.active{display:block}

  /* ── Header ── */
  .site-header{position:sticky;top:0;z-index:300;background:var(--black);border-bottom:3px solid var(--gold);display:flex;align-items:center;gap:1rem;padding:0 1.4rem;height:62px;flex-shrink:0;box-shadow:0 2px 10px rgba(0,0,0,.18)}
  .hamburger{display:none;background:var(--mid);border:none;cursor:pointer;color:#fff;font-size:14px;width:34px;height:34px;border-radius:7px;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s}
  .hamburger:hover{background:var(--gold);color:var(--black)}
  .school-logo{width:36px;height:36px;border-radius:9px;background:var(--gold);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 10px rgba(240,192,64,.25)}
  .school-logo i{color:var(--black);font-size:14px}
  .school-name-wrap{flex:1;min-width:0}
  .school-name{font-family:'Bebas Neue',sans-serif;font-size:1.35rem;letter-spacing:.12em;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1}
  .school-name span{color:var(--gold)}
  .school-motto{font-size:9.5px;color:#555;font-weight:600;letter-spacing:.1em;text-transform:uppercase;margin-top:2px}
  .header-user{display:flex;align-items:center;gap:9px;flex-shrink:0}
  .user-avatar{width:32px;height:32px;border-radius:50%;background:var(--mid);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--gold);font-weight:700}
  .user-meta{display:flex;flex-direction:column;line-height:1.2}
  .user-label{font-size:12px;color:#eee;font-weight:600;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .user-sub{font-size:9.5px;color:#666}
  .btn-logout{display:flex;align-items:center;gap:5px;font-size:12px;font-weight:500;color:#888;background:transparent;border:1px solid var(--mid);border-radius:var(--radius-md);padding:6px 11px;cursor:pointer;text-decoration:none;transition:all .18s}
  .btn-logout:hover{border-color:var(--gold);color:var(--gold);background:rgba(240,192,64,.06)}

  /* ── Layout ── */
  .layout{display:flex;flex:1}

  /* ── Sidebar ── */
  .sidebar{width:var(--sidebar-w);background:var(--black);border-right:1px solid var(--mid);display:flex;flex-direction:column;flex-shrink:0;transition:transform .3s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden}
  .sidebar::before{content:'';position:absolute;top:0;left:0;width:3px;height:100%;background:linear-gradient(to bottom,var(--gold) 0%,transparent 100%)}
  .sidebar-inner{flex:1;overflow-y:auto;padding:1.1rem 0;scrollbar-width:thin;scrollbar-color:var(--mid) transparent}
  .nav-label{font-size:9.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#3a3a3a;padding:.7rem 1.3rem .4rem}
  .sidebar nav{display:flex;flex-direction:column;gap:2px;padding:0 .6rem}
  .sidebar nav a{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:var(--radius-md);font-size:13px;font-weight:400;color:#aaa;text-decoration:none;border-left:3px solid transparent;transition:all .18s}
  .sidebar nav a i{width:16px;text-align:center;font-size:12px;color:#555;flex-shrink:0;transition:color .15s}
  .sidebar nav a:hover{background:rgba(255,255,255,.04);color:#fff;border-left-color:var(--gold)}
  .sidebar nav a:hover i{color:var(--gold)}
  .sidebar nav a.active{background:rgba(240,192,64,.09);color:#fff;font-weight:500;border-left-color:var(--gold)}
  .sidebar nav a.active i{color:var(--gold)}
  .sidebar-footer{padding:.9rem 1.3rem;border-top:1px solid var(--mid);font-size:10px;color:#3a3a3a;letter-spacing:.04em;line-height:1.6}
  .sidebar-footer b{color:#777}

  /* ── Main ── */
  .main{flex:1;min-width:0;padding:1.9rem;display:flex;flex-direction:column;gap:1.6rem}

  /* ── Section title ── */
  .section-title{font-family:'Bebas Neue',sans-serif;font-size:1.45rem;letter-spacing:.1em;color:var(--text-primary);display:flex;align-items:center;gap:9px;margin-bottom:.2rem}
  .section-title::before{content:'◆';color:var(--gold);font-size:.7rem}
  .section-desc{font-size:12.5px;color:var(--text-tertiary);margin-bottom:.9rem;margin-top:-.6rem}

  /* ── Cards ── */
  .card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm)}
  .card-head{background:var(--black);border-bottom:2px solid var(--gold);padding:.7rem 1.2rem;display:flex;align-items:center;justify-content:space-between;gap:8px}
  .card-head-left{display:flex;align-items:center;gap:8px}
  .card-head span{font-family:'Bebas Neue',sans-serif;font-size:.92rem;letter-spacing:.1em;color:#fff}
  .card-head i{color:var(--gold);font-size:12px}
  .card-body{padding:1.25rem 1.4rem}

  /* ── Data note (honest disclaimers) ── */
  .data-note{display:flex;align-items:flex-start;gap:9px;background:rgba(240,192,64,.08);border:1px solid rgba(240,192,64,.25);border-radius:var(--radius-md);padding:10px 13px;font-size:12px;color:#7a6420;line-height:1.5}
  .data-note i{color:var(--gold-dim);margin-top:1px;flex-shrink:0}

  /* ── Welcome banner ── */
  .welcome-banner{background:var(--black);border-radius:var(--radius-lg);padding:1.7rem 1.9rem;display:flex;align-items:center;justify-content:space-between;gap:1.2rem;flex-wrap:wrap;position:relative;overflow:hidden;animation:fadeUp .4s ease both;box-shadow:var(--shadow-md)}
  .welcome-banner::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(240,192,64,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(240,192,64,.04) 1px,transparent 1px);background-size:32px 32px;pointer-events:none}
  .welcome-banner::after{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--gold);border-radius:0 4px 4px 0}
  .welcome-text{position:relative}
  .welcome-eyebrow{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-dim);margin-bottom:5px}
  .welcome-title{font-family:'Bebas Neue',sans-serif;font-size:1.9rem;letter-spacing:.1em;color:#fff;line-height:1;margin-bottom:5px}
  .welcome-title span{color:var(--gold)}
  .welcome-sub{font-size:12px;color:#666}
  .welcome-pills{display:flex;gap:.6rem;flex-wrap:wrap;position:relative}
  .pill{display:flex;flex-direction:column;align-items:center;gap:2px;background:rgba(255,255,255,.04);border:1px solid var(--mid);border-radius:10px;padding:.7rem 1.05rem;min-width:76px}
  .pill .pill-val{font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:var(--gold);line-height:1}
  .pill .pill-lbl{font-size:9px;color:#555;text-transform:uppercase;letter-spacing:.08em;font-weight:600;text-align:center}

  /* ── Stats row ── */
  .stats-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:13px}
  .stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.05rem 1.25rem;position:relative;overflow:hidden;animation:fadeUp .4s ease both;box-shadow:var(--shadow-sm);transition:box-shadow .2s,transform .2s}
  .stat-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
  .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent,var(--gold))}
  .stat-val{font-size:1.85rem;font-weight:700;color:var(--accent,var(--text-primary));line-height:1.1;margin-bottom:3px;letter-spacing:-.01em}
  .stat-lbl{font-size:10.5px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.07em;font-weight:600}
  .stat-sub{font-size:10.5px;color:var(--text-tertiary);margin-top:4px}

  /* ── Tables ── */
  .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
  table.data{width:100%;border-collapse:collapse;font-size:12.5px}
  table.data th{background:#fafafa;color:var(--text-tertiary);font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:10px 12px;border-bottom:1px solid rgba(0,0,0,.07);text-align:left;white-space:nowrap}
  table.data td{padding:11px 12px;border-bottom:1px solid rgba(0,0,0,.05);vertical-align:middle}
  table.data tr:hover td{background:var(--bg-input)}
  table.data tr:last-child td{border-bottom:none}

  /* ── Progress bar ── */
  .bar-wrap{height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;width:100%;min-width:60px}
  .bar-fill{height:100%;border-radius:3px;transition:width .4s ease}

  /* ── Badge ── */
  .badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:10.5px;font-weight:700;letter-spacing:.04em}

  /* ── Notice items ── */
  .notice-item{border-left:4px solid var(--border);padding:11px 14px;background:var(--bg-input);border-radius:0 var(--radius-md) var(--radius-md) 0;margin-bottom:9px}
  .notice-item.urgent{border-left-color:#dc2626;background:#fff5f5}
  .notice-item.info{border-left-color:#2563eb;background:#f0f4ff}
  .notice-item.event{border-left-color:#16a34a;background:#f0fff4}
  .notice-title{font-size:13px;font-weight:600;margin-bottom:2px}
  .notice-meta{font-size:10px;color:var(--text-tertiary)}
  .nb-badge-s{display:inline-block;padding:1px 8px;border-radius:20px;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
  .badge-urgent{background:#fde8e8;color:#b91c1c}.badge-info{background:#dbeafe;color:#1e40af}.badge-event{background:#dcfce7;color:#166534}

  /* ── Timetable ── */
  table.tt{width:100%;border-collapse:collapse;font-size:11.5px;min-width:600px}
  table.tt th{background:var(--black);color:var(--gold);padding:9px 6px;text-align:center;font-family:'Bebas Neue',sans-serif;font-size:12px;letter-spacing:.08em;white-space:nowrap}
  table.tt td{border:1px solid rgba(0,0,0,.06);padding:8px 5px;text-align:center;vertical-align:middle}
  table.tt tr:hover td{background:var(--bg-input)}
  .tt-period{font-weight:600;font-size:10px;color:var(--text-secondary);white-space:nowrap;text-align:left!important;padding-left:8px!important}
  .subj-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:600}
  .tt-teacher{font-size:9px;color:var(--text-tertiary);margin-top:1px}
  .tt-empty{color:#ccc;font-size:11px}

  /* ── Fee items ── */
  .fee-row{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:6px}
  .fee-row:last-child{border-bottom:none}
  .fee-label{font-size:13px;font-weight:500}
  .fee-meta{font-size:10px;color:var(--text-tertiary)}
  .fee-amounts{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
  .fee-col{text-align:right}
  .fee-col .amt{font-size:13px;font-weight:600}
  .fee-col .lbl{font-size:9px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:.06em}
  .balance-banner{background:var(--black);color:#fff;border-radius:var(--radius-lg);padding:1.25rem 1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:var(--shadow-md)}
  .balance-val{font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--gold)}
  .balance-label{font-size:11px;color:#555;text-transform:uppercase;letter-spacing:.08em;margin-bottom:2px}

  /* ── Comparison row ── */
  .compare-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);flex-wrap:wrap}
  .compare-row:last-child{border-bottom:none}
  .compare-subject{font-size:12.5px;font-weight:500;min-width:110px}
  .compare-bars{flex:1;min-width:140px;display:flex;flex-direction:column;gap:4px}
  .compare-bar-row{display:flex;align-items:center;gap:6px}
  .compare-bar-lbl{font-size:9.5px;color:var(--text-tertiary);width:36px;text-align:right}
  .compare-bar-wrap{flex:1;height:5px;background:#e5e7eb;border-radius:3px;overflow:hidden}
  .compare-bar-fill{height:100%;border-radius:3px}
  .compare-score{font-size:12px;font-weight:600;min-width:38px;text-align:right}

  /* ── Quick nav cards ── */
  .nav-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem}
  .nav-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.35rem 1.15rem;display:flex;flex-direction:column;align-items:flex-start;gap:.85rem;text-decoration:none;color:var(--text-primary);position:relative;overflow:hidden;transition:transform .22s,box-shadow .22s,border-color .22s;box-shadow:var(--shadow-sm)}
  .nav-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:var(--card-accent,var(--gold))}
  .nav-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--card-accent,var(--black))}
  .nav-card::after{content:'';position:absolute;bottom:-24px;right:-24px;width:80px;height:80px;border-radius:50%;background:var(--card-accent,transparent);opacity:.06;transition:opacity .22s}
  .nav-card:hover::after{opacity:.12}
  .card-icon{width:44px;height:44px;border-radius:11px;background:var(--card-accent,var(--black));display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .card-icon i{font-size:17px;color:#fff}
  .card-ttl{font-size:13.5px;font-weight:600;line-height:1.2}
  .card-dsc{font-size:11.5px;color:var(--text-tertiary);line-height:1.4}
  .card-arrow{position:absolute;bottom:.9rem;right:1rem;font-size:10px;color:var(--card-accent,var(--text-tertiary));opacity:0;transform:translateX(-5px);transition:opacity .2s,transform .2s}
  .nav-card:hover .card-arrow{opacity:1;transform:translateX(0)}
  .c-results{--card-accent:#16a34a}.c-grade{--card-accent:#7c3aed}.c-att{--card-accent:#0891b2}.c-fees{--card-accent:#c9a030}.c-notice{--card-accent:#dc2626}.c-tt{--card-accent:#2563eb}

  /* ── Alerts ── */
  .alert{padding:11px 14px;border-radius:var(--radius-md);font-size:13px;margin-bottom:1rem}
  .alert-info{background:#f0f4ff;color:#1e40af;border:1px solid #93c5fd}

  /* ── Empty state ── */
  .empty{text-align:center;color:var(--text-tertiary);padding:2.6rem 0;font-size:13.5px}
  .empty i{font-size:30px;color:var(--gold);display:block;margin-bottom:9px}

  /* ── Section tabs ── */
  .section-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1rem}
  .stab{padding:6px 15px;border-radius:20px;border:1px solid var(--border);background:var(--bg-input);cursor:pointer;font-size:12px;font-weight:500;text-decoration:none;color:var(--text-secondary);transition:all .15s}
  .stab:hover,.stab.active{background:var(--black);color:var(--gold);border-color:transparent}

  /* ── Filter form ── */
  .filter-form{display:flex;gap:9px;flex-wrap:wrap;align-items:flex-end;margin-bottom:.6rem}
  .filter-field label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-tertiary);display:block;margin-bottom:5px}
  .filter-field select{padding:8px 13px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--bg-input);font-size:13px;outline:none;font-family:inherit;cursor:pointer}
  .filter-field select:focus{border-color:var(--gold)}
  .btn-filter{padding:8px 18px;background:var(--black);color:var(--gold);border:none;border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
  .btn-filter:hover{background:#000}

  /* ── Footer ── */
  .site-footer{background:var(--black);border-top:2px solid var(--gold);padding:1rem 1.5rem;text-align:center;font-size:10.5px;color:#444;letter-spacing:.04em}

  /* ── Animations ── */
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .fade-in{animation:fadeUp .35s ease both}

  /* ── Responsive ── */
  @media(max-width:900px){
    .sidebar{position:fixed;top:62px;left:0;bottom:0;z-index:250;transform:translateX(-100%);box-shadow:6px 0 30px rgba(0,0,0,.4)}
    .sidebar.open{transform:translateX(0)}
    .hamburger{display:flex}
    .user-meta{display:none}
    .main{padding:1.2rem 1rem 2.5rem}
  }
  @media(max-width:520px){
    .nav-cards{grid-template-columns:repeat(2,1fr)}
    .stats-row{grid-template-columns:repeat(2,1fr)}
    .welcome-pills{gap:.4rem}
    .btn-logout span{display:none}
  }
</style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ═══ HEADER ═══ -->
<header class="site-header">
  <button class="hamburger" id="hamburger" aria-label="Open menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="school-logo"><i class="fa-solid fa-graduation-cap"></i></div>
  <div class="school-name-wrap">
    <div class="school-name">Stephen Kanja <span>School</span></div>
    <div class="school-motto">Aim Higher</div>
  </div>
  <div class="header-user">
    <div class="user-avatar"><?= strtoupper(substr($student['firstName'],0,1) . substr($student['surname'],0,1)) ?></div>
    <div class="user-meta">
      <span class="user-label"><?= $name ?></span>
      <span class="user-sub">Grade <?= htmlspecialchars($grade) ?> &middot; <?= htmlspecialchars($assess) ?></span>
    </div>
    <a href="index.php?logout=1" class="btn-logout" onclick="return confirmLogout()">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Logout</span>
    </a>
  </div>
</header>

<div class="layout">

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-inner">
    <p class="nav-label">My Portal</p>
    <nav>
      <a href="<?= $url_base ?>section=home"        class="<?= $section==='home'        ? 'active':'' ?>"><i class="fa-solid fa-house"></i> Home</a>
      <a href="<?= $url_base ?>section=results"     class="<?= $section==='results'     ? 'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> My Results</a>
      <a href="<?= $url_base ?>section=grade_perf"  class="<?= $section==='grade_perf'  ? 'active':'' ?>"><i class="fa-solid fa-users"></i> Grade Performance</a>
      <a href="<?= $url_base ?>section=attendance"  class="<?= $section==='attendance'  ? 'active':'' ?>"><i class="fa-solid fa-clipboard-check"></i> Attendance</a>
      <a href="<?= $url_base ?>section=fees"        class="<?= $section==='fees'        ? 'active':'' ?>"><i class="fa-solid fa-coins"></i> Fees</a>
      <a href="<?= $url_base ?>section=notices"     class="<?= $section==='notices'     ? 'active':'' ?>"><i class="fa-solid fa-bullhorn"></i> Notices</a>
      <a href="<?= $url_base ?>section=timetable"   class="<?= $section==='timetable'   ? 'active':'' ?>"><i class="fa-solid fa-calendar-days"></i> Timetable</a>
    </nav>
  </div>
  <div class="sidebar-footer">Grade <b><?= htmlspecialchars($grade) ?></b><br>ID: <b><?= htmlspecialchars($assess) ?></b><br>&copy; 2026 Kelvin Mutinda</div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="main">

<?php if ($section === 'home'): ?>
  <!-- ══ HOME ══ -->
  <div class="welcome-banner">
    <div class="welcome-text">
      <p class="welcome-eyebrow">Student Dashboard</p>
      <h1 class="welcome-title">Welcome, <span><?= htmlspecialchars($student['firstName']) ?></span></h1>
      <p class="welcome-sub">Grade <?= htmlspecialchars($grade) ?> &middot; ID: <?= htmlspecialchars($assess) ?> &middot; Stephen Kanja School</p>
    </div>
    <div class="welcome-pills">
      <div class="pill"><span class="pill-val"><?= $my_avg ?>%</span><span class="pill-lbl">My Avg</span></div>
      <div class="pill"><span class="pill-val"><?= $att_pct !== null ? $att_pct.'%' : '—' ?></span><span class="pill-lbl">Attendance</span></div>
      <div class="pill"><span class="pill-val">KES <?= number_format($total_paid) ?></span><span class="pill-lbl">Total Paid</span></div>
    </div>
  </div>

  <!-- Quick stats -->
  <div class="stats-row">
    <div class="stat-card" style="--accent:var(--green)">
      <div class="stat-val"><?= $my_avg ?>%</div>
      <div class="stat-lbl">Average Score</div>
      <div class="stat-sub"><?= count($my_results) ?> subjects · <?= htmlspecialchars($sel_term) ?> <?= $sel_year ?></div>
    </div>
    <?php if ($my_rank > 0): ?>
    <div class="stat-card" style="--accent:var(--purple)">
      <div class="stat-val"><?= $my_rank ?><?= $my_rank===1?'st':($my_rank===2?'nd':($my_rank===3?'rd':'th')) ?></div>
      <div class="stat-lbl">Grade Rank</div>
      <div class="stat-sub">Out of <?= $total_students_in_grade ?> students</div>
    </div>
    <?php endif; ?>
    <div class="stat-card" style="--accent:var(--blue)">
      <div class="stat-val"><?= $att_pct !== null ? $att_pct.'%' : '—' ?></div>
      <div class="stat-lbl">Attendance</div>
      <div class="stat-sub"><?= $att_pct !== null ? $att['Present'].' present · '.$att['Absent'].' absent' : 'No records yet' ?></div>
    </div>
    <div class="stat-card" style="--accent:var(--amber)">
      <div class="stat-val">KES <?= number_format($total_paid) ?></div>
      <div class="stat-lbl">Total Paid</div>
      <div class="stat-sub"><?= count($fees_list) ?> payment record<?= count($fees_list)===1?'':'s' ?></div>
    </div>
  </div>

  <h2 class="section-title">Quick Access</h2>
  <div class="nav-cards">
    <a href="<?= $url_base ?>section=results" class="nav-card c-results">
      <div class="card-icon"><i class="fa-solid fa-chart-pie"></i></div>
      <div><div class="card-ttl">My Results</div><div class="card-dsc">View scores & grades by subject</div></div>
      <i class="fa-solid fa-arrow-right card-arrow"></i>
    </a>
    <a href="<?= $url_base ?>section=grade_perf" class="nav-card c-grade">
      <div class="card-icon"><i class="fa-solid fa-users"></i></div>
      <div><div class="card-ttl">Grade Performance</div><div class="card-dsc">See how your grade is doing</div></div>
      <i class="fa-solid fa-arrow-right card-arrow"></i>
    </a>
    <a href="<?= $url_base ?>section=attendance" class="nav-card c-att">
      <div class="card-icon"><i class="fa-solid fa-clipboard-check"></i></div>
      <div><div class="card-ttl">Attendance</div><div class="card-dsc">Monthly attendance record</div></div>
      <i class="fa-solid fa-arrow-right card-arrow"></i>
    </a>
    <a href="<?= $url_base ?>section=fees" class="nav-card c-fees">
      <div class="card-icon"><i class="fa-solid fa-coins"></i></div>
      <div><div class="card-ttl">Fees</div><div class="card-dsc">Payment history & records</div></div>
      <i class="fa-solid fa-arrow-right card-arrow"></i>
    </a>
    <a href="<?= $url_base ?>section=notices" class="nav-card c-notice">
      <div class="card-icon"><i class="fa-solid fa-bullhorn"></i></div>
      <div><div class="card-ttl">Notices</div><div class="card-dsc">Latest school announcements</div></div>
      <i class="fa-solid fa-arrow-right card-arrow"></i>
    </a>
    <a href="<?= $url_base ?>section=timetable" class="nav-card c-tt">
      <div class="card-icon"><i class="fa-solid fa-calendar-days"></i></div>
      <div><div class="card-ttl">Timetable</div><div class="card-dsc">Weekly class schedule</div></div>
      <i class="fa-solid fa-arrow-right card-arrow"></i>
    </a>
  </div>

  <!-- Latest notices preview -->
  <?php if (!empty($notices)): ?>
  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-bell"></i><span>Latest Notices</span></div></div>
    <div class="card-body">
      <?php foreach (array_slice($notices, 0, 3) as $n): ?>
        <div class="notice-item <?= htmlspecialchars($n['type']) ?>">
          <div class="nb-badge-s badge-<?= $n['type'] ?>"><?= ucfirst($n['type']) ?></div>
          <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="notice-meta"><?= htmlspecialchars($n['posted_by']) ?> · <?= date('d M Y', strtotime($n['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
      <a href="<?= $url_base ?>section=notices" style="font-size:12px;color:var(--gold-dim);font-weight:600;text-decoration:none;display:block;margin-top:8px">View all notices →</a>
    </div>
  </div>
  <?php endif; ?>


<?php elseif ($section === 'results'): ?>
  <!-- ══ MY RESULTS ══ -->
  <h2 class="section-title">My Results</h2>
  <p class="section-desc">Subject scores are read from this term's exam record.</p>

  <!-- Term/Year filter -->
  <form method="GET" class="filter-form">
    <input type="hidden" name="section" value="results">
    <div class="filter-field">
      <label>Term</label>
      <select name="term">
        <?php foreach(['Term 1','Term 2','Term 3'] as $t): ?>
          <option <?= $t===$sel_term?'selected':'' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-field">
      <label>Year</label>
      <select name="year">
        <?php foreach([2026,2025,2024] as $y): ?>
          <option <?= $y==$sel_year?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-filter">Load</button>
  </form>

  <!-- Summary stats -->
  <div class="stats-row">
    <div class="stat-card" style="--accent:var(--green)">
      <div class="stat-val"><?= $my_avg ?>%</div>
      <div class="stat-lbl">My Average</div>
    </div>
    <?php $gl = gradeLabel($my_avg); ?>
    <div class="stat-card" style="--accent:<?= $gl[1] ?>">
      <div class="stat-val"><?= $gl[0] ?></div>
      <div class="stat-lbl">Overall Grade</div>
    </div>
    <?php if ($my_rank > 0): ?>
    <div class="stat-card" style="--accent:var(--purple)">
      <div class="stat-val"><?= $my_rank ?>/<?= $total_students_in_grade ?></div>
      <div class="stat-lbl">Grade Rank</div>
    </div>
    <?php endif; ?>
    <div class="stat-card" style="--accent:var(--blue)">
      <div class="stat-val"><?= count($my_results) ?></div>
      <div class="stat-lbl">Subjects</div>
    </div>
  </div>

  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-table"></i><span>Subject Scores — <?= htmlspecialchars($sel_term) ?> <?= $sel_year ?></span></div></div>
    <div class="table-wrap">
      <?php if (!empty($my_results)): ?>
      <table class="data">
        <thead><tr><th>Subject</th><th>Score</th><th>Out of</th><th>%</th><th>Grade</th><th>Progress</th><th>Vs Grade Avg</th></tr></thead>
        <tbody>
          <?php foreach ($my_results as $r):
            $pct = $r['max_score'] > 0 ? round(($r['score']/$r['max_score'])*100,1) : 0;
            $gl  = gradeLabel($pct);
            $g_avg = isset($grade_perf[$r['subject']]) ? round((float)($grade_perf[$r['subject']]['avg_pct'] ?? 0),1) : null;
            $diff  = $g_avg !== null ? round($pct - $g_avg, 1) : null;
          ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($r['subject']) ?></td>
            <td style="font-weight:600;font-size:14px"><?= $r['score'] ?></td>
            <td style="color:var(--text-tertiary)"><?= $r['max_score'] ?></td>
            <td style="font-weight:600"><?= $pct ?>%</td>
            <td>
              <span class="badge" style="background:<?= $gl[2] ?>;color:<?= $gl[1] ?>"><?= $gl[0] ?></span>
            </td>
            <td style="min-width:80px">
              <div class="bar-wrap">
                <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $pct>=60?'#16a34a':($pct>=40?'#d97706':'#dc2626') ?>"></div>
              </div>
            </td>
            <td>
              <?php if ($diff !== null): ?>
                <span style="font-weight:600;color:<?= $diff>=0?'#16a34a':'#dc2626' ?>">
                  <?= $diff>=0?'+':'' ?><?= $diff ?>%
                </span>
              <?php else: ?>
                <span style="color:var(--text-tertiary)">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty"><i class="fa-solid fa-inbox"></i>No results found for <?= htmlspecialchars($sel_term) ?> <?= $sel_year ?>.</div>
      <?php endif; ?>
    </div>
  </div>


<?php elseif ($section === 'grade_perf'): ?>
  <!-- ══ GRADE PERFORMANCE ══ -->
  <h2 class="section-title">Grade <?= htmlspecialchars($grade) ?> Performance</h2>

  <form method="GET" class="filter-form">
    <input type="hidden" name="section" value="grade_perf">
    <div class="filter-field">
      <label>Term</label>
      <select name="term">
        <?php foreach(['Term 1','Term 2','Term 3'] as $t): ?>
          <option <?= $t===$sel_term?'selected':'' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-field">
      <label>Year</label>
      <select name="year">
        <?php foreach([2026,2025,2024] as $y): ?>
          <option <?= $y==$sel_year?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-filter">Load</button>
  </form>

  <div class="stats-row">
    <div class="stat-card" style="--accent:var(--blue)">
      <div class="stat-val"><?= $grade_avg ?>%</div>
      <div class="stat-lbl">Grade Average</div>
    </div>
    <div class="stat-card" style="--accent:var(--green)">
      <div class="stat-val"><?= $my_avg ?>%</div>
      <div class="stat-lbl">My Average</div>
    </div>
    <div class="stat-card" style="--accent:var(--purple)">
      <div class="stat-val"><?= $total_students_in_grade ?></div>
      <div class="stat-lbl">Students Assessed</div>
    </div>
  </div>

  <?php if (!empty($grade_perf)): ?>
  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-chart-bar"></i><span>Subject Comparison — Me vs Grade</span></div></div>
    <div class="card-body">
      <?php foreach ($grade_perf as $subj => $gp):
        $my_subj_score = 0;
        foreach ($my_results as $mr) { if ($mr['subject']===$subj) { $my_subj_score = $mr['max_score']>0?round($mr['score']/$mr['max_score']*100,1):0; break; } }
        $g_avg_pct = round((float)($gp['avg_pct'] ?? 0),1);
        $g_top_pct = round((float)($gp['top_pct'] ?? 0),1);
      ?>
      <div class="compare-row">
        <div class="compare-subject"><?= htmlspecialchars($subj) ?></div>
        <div class="compare-bars">
          <div class="compare-bar-row">
            <span class="compare-bar-lbl">Me</span>
            <div class="compare-bar-wrap"><div class="compare-bar-fill" style="width:<?= $my_subj_score ?>%;background:#f0c040"></div></div>
            <span class="compare-score" style="color:var(--gold-dim)"><?= $my_subj_score ?>%</span>
          </div>
          <div class="compare-bar-row">
            <span class="compare-bar-lbl">Avg</span>
            <div class="compare-bar-wrap"><div class="compare-bar-fill" style="width:<?= $g_avg_pct ?>%;background:#2563eb"></div></div>
            <span class="compare-score" style="color:#2563eb"><?= $g_avg_pct ?>%</span>
          </div>
          <div class="compare-bar-row">
            <span class="compare-bar-lbl">Top</span>
            <div class="compare-bar-wrap"><div class="compare-bar-fill" style="width:<?= $g_top_pct ?>%;background:#16a34a"></div></div>
            <span class="compare-score" style="color:#16a34a"><?= $g_top_pct ?>%</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:14px;display:flex;gap:16px;flex-wrap:wrap;font-size:11px;color:var(--text-tertiary)">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:var(--gold);margin-right:4px"></span>Me</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#2563eb;margin-right:4px"></span>Grade Average</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#16a34a;margin-right:4px"></span>Top Score</span>
      </div>
    </div>
  </div>

  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-table"></i><span>Grade Subject Summary</span></div></div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Subject</th><th>Grade Avg</th><th>Top Score</th><th>Lowest</th><th>Students</th><th>My Score</th></tr></thead>
        <tbody>
          <?php foreach ($grade_perf as $subj => $gp):
            $my_subj_score = '—';
            foreach ($my_results as $mr) {
              if ($mr['subject']===$subj) {
                $ms = $mr['max_score']>0?round($mr['score']/$mr['max_score']*100,1):0;
                $my_subj_score = $ms.'%';
                break;
              }
            }
          ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($subj) ?></td>
            <td><?= round((float)($gp['avg_pct'] ?? 0),1) ?>%</td>
            <td style="color:var(--green);font-weight:600"><?= round((float)($gp['top_pct'] ?? 0),1) ?>%</td>
            <td style="color:var(--red)"><?= round((float)($gp['low_pct'] ?? 0),1) ?>%</td>
            <td><?= $gp['student_count'] ?></td>
            <td style="font-weight:600;color:var(--gold-dim)"><?= $my_subj_score ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
    <div class="empty"><i class="fa-solid fa-chart-bar"></i>No grade performance data yet for <?= htmlspecialchars($sel_term) ?> <?= $sel_year ?>.</div>
  <?php endif; ?>


<?php elseif ($section === 'attendance'): ?>
  <!-- ══ ATTENDANCE ══ -->
  <h2 class="section-title">My Attendance</h2>

  <?php if ($att_total === 0): ?>
    <div class="data-note"><i class="fa-solid fa-circle-info"></i>No attendance records have been entered yet. Once your class teacher starts logging attendance, it will appear here automatically.</div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat-card" style="--accent:var(--green)">
      <div class="stat-val"><?= $att['Present'] ?></div>
      <div class="stat-lbl">Present</div>
    </div>
    <div class="stat-card" style="--accent:var(--red)">
      <div class="stat-val"><?= $att['Absent'] ?></div>
      <div class="stat-lbl">Absent</div>
    </div>
    <div class="stat-card" style="--accent:var(--amber)">
      <div class="stat-val"><?= $att['Late'] ?></div>
      <div class="stat-lbl">Late</div>
    </div>
    <div class="stat-card" style="--accent:var(--blue)">
      <div class="stat-val"><?= $att_pct !== null ? $att_pct.'%' : '—' ?></div>
      <div class="stat-lbl">This Month</div>
    </div>
  </div>

  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-calendar-check"></i><span>Attendance Record — <?= date('F Y') ?></span></div></div>
    <div class="card-body">
      <?php if ($att_pct !== null): ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px">
          <span>Attendance rate</span>
          <span style="font-weight:600;color:<?= $att_pct>=80?'var(--green)':($att_pct>=60?'var(--amber)':'var(--red)') ?>"><?= $att_pct ?>%</span>
        </div>
        <div class="bar-wrap" style="height:10px;border-radius:5px">
          <div class="bar-fill" style="width:<?= $att_pct ?>%;background:<?= $att_pct>=80?'#16a34a':($att_pct>=60?'#d97706':'#dc2626') ?>"></div>
        </div>
        <?php if ($att_pct < 80): ?>
          <p style="font-size:11px;color:var(--red);margin-top:6px"><i class="fa-solid fa-triangle-exclamation"></i> Attendance below 80% — please discuss with your class teacher.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($att_total > 0): ?>
        <p style="font-size:12px;color:var(--text-tertiary)">Daily attendance log will appear here once more records are added.</p>
      <?php else: ?>
        <div class="empty" style="padding:1.5rem 0"><i class="fa-solid fa-inbox"></i>No attendance records found.</div>
      <?php endif; ?>
    </div>
  </div>


<?php elseif ($section === 'fees'): ?>
  <!-- ══ FEES ══ -->
  <h2 class="section-title">Fees</h2>

  <div class="data-note">
    <i class="fa-solid fa-circle-info"></i>
    This page shows payments on record. There is currently no "amount due" figure stored in the system, so an outstanding balance cannot be calculated yet — talk to the school office if you'd like that added.
  </div>

  <!-- Total paid banner -->
  <div class="balance-banner">
    <div>
      <div class="balance-label">Total Paid (All Records)</div>
      <div class="balance-val">KES <?= number_format($total_paid, 2) ?></div>
      <div style="font-size:11px;color:#555"><?= count($fees_list) ?> payment record<?= count($fees_list)===1?'':'s' ?> on file</div>
    </div>
    <div style="background:var(--gold);color:var(--black);padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600"><i class="fa-solid fa-receipt"></i> Payment History</div>
  </div>

  <!-- Personal fee breakdown -->
  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-user"></i><span>My Payment Records</span></div></div>
    <div class="card-body">
      <?php if (!empty($fees_list)): ?>
        <?php foreach ($fees_list as $f): ?>
        <div class="fee-row">
          <div>
            <div class="fee-label"><?= htmlspecialchars($f['Term']) ?> <?= htmlspecialchars($f['Year']) ?></div>
            <div class="fee-meta"><?= $f['payment_date'] && $f['payment_date'] !== '0000-00-00 00:00:00' ? 'Recorded: '.date('d M Y', strtotime($f['payment_date'])) : 'No date on file' ?></div>
          </div>
          <div class="fee-amounts">
            <div class="fee-col"><div class="amt">KES <?= number_format($f['Fee'],2) ?></div><div class="lbl">Fee</div></div>
            <div class="fee-col"><div class="amt">KES <?= number_format($f['AssesmentFee'],2) ?></div><div class="lbl">Assessment</div></div>
            <div class="fee-col"><div class="amt">KES <?= number_format($f['Activity'],2) ?></div><div class="lbl">Activity</div></div>
            <div class="fee-col"><div class="amt">KES <?= number_format($f['other'],2) ?></div><div class="lbl">Other</div></div>
            <div class="fee-col"><div class="amt" style="color:var(--green)">KES <?= number_format($f['row_total'],2) ?></div><div class="lbl">Total</div></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty"><i class="fa-solid fa-receipt"></i>No payment records found.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- General grade fees -->
  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-users"></i><span>Average Payments — Grade <?= htmlspecialchars($grade) ?></span></div></div>
    <div class="card-body">
      <?php if (!empty($grade_fees)): ?>
        <?php foreach ($grade_fees as $gf): ?>
        <div class="fee-row">
          <div>
            <div class="fee-label"><?= htmlspecialchars($gf['Term']) ?> <?= htmlspecialchars($gf['Year']) ?></div>
            <div class="fee-meta"><?= $gf['record_count'] ?> record<?= $gf['record_count']==1?'':'s' ?></div>
          </div>
          <div class="fee-col">
            <div class="amt">KES <?= number_format($gf['avg_paid'],2) ?></div>
            <div class="lbl">Avg Per Student</div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty"><i class="fa-solid fa-receipt"></i>No fee records found for Grade <?= htmlspecialchars($grade) ?>.</div>
      <?php endif; ?>
    </div>
  </div>


<?php elseif ($section === 'notices'): ?>
  <!-- ══ NOTICES ══ -->
  <h2 class="section-title">School Notices</h2>

  <?php /* $nfilter and $all_notices were already prepared above, before the DB connection closed */ ?>

  <div class="section-tabs">
    <a href="<?= $url_base ?>section=notices" class="stab <?= $nfilter===''?'active':'' ?>">All</a>
    <a href="<?= $url_base ?>section=notices&ntype=urgent" class="stab <?= $nfilter==='urgent'?'active':'' ?>"><i class="fa-solid fa-circle-exclamation"></i> Urgent</a>
    <a href="<?= $url_base ?>section=notices&ntype=info"   class="stab <?= $nfilter==='info'?'active':'' ?>"><i class="fa-solid fa-circle-info"></i> Info</a>
    <a href="<?= $url_base ?>section=notices&ntype=event"  class="stab <?= $nfilter==='event'?'active':'' ?>"><i class="fa-solid fa-calendar-star"></i> Events</a>
  </div>

  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-list"></i><span>Notices Board</span></div></div>
    <div class="card-body">
      <?php if (!empty($all_notices)): ?>
        <?php foreach ($all_notices as $n): ?>
          <div class="notice-item <?= htmlspecialchars($n['type']) ?>">
            <div class="nb-badge-s badge-<?= $n['type'] ?>"><?= ucfirst($n['type']) ?></div>
            <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notice-meta">
              <i class="fa-solid fa-user" style="font-size:9px"></i> <?= htmlspecialchars($n['posted_by']) ?>
              &middot; <?= date('d M Y, g:i A', strtotime($n['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty"><i class="fa-solid fa-inbox"></i>No notices found.</div>
      <?php endif; ?>
    </div>
  </div>


<?php elseif ($section === 'timetable'): ?>
  <!-- ══ TIMETABLE ══ -->
  <h2 class="section-title">My Timetable — Grade <?= htmlspecialchars($grade) ?></h2>

  <div class="card fade-in">
    <div class="card-head"><div class="card-head-left"><i class="fa-solid fa-calendar-days"></i><span>Weekly Schedule</span></div></div>
    <div class="table-wrap">
      <?php if (!empty($tt_grid)): ?>
      <table class="tt">
        <thead>
          <tr>
            <th style="text-align:left;padding-left:8px">Time</th>
            <?php foreach ($days as $d): ?><th><?= $d ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($periods as $period): ?>
          <tr>
            <td class="tt-period"><?= htmlspecialchars($period) ?></td>
            <?php foreach ($days as $day): ?>
            <td>
              <?php if (isset($tt_grid[$day][$period])):
                $entry = $tt_grid[$day][$period];
                $subj  = $entry['subject'];
                $colors = $subject_colors[$subj] ?? ['#f3f4f6','#374151'];
              ?>
                <span class="subj-pill" style="background:<?= $colors[0] ?>;color:<?= $colors[1] ?>"><?= htmlspecialchars($subj) ?></span>
                <?php if (!empty($entry['teacher_name'])): ?><div class="tt-teacher"><?= htmlspecialchars($entry['teacher_name']) ?></div><?php endif; ?>
              <?php else: ?>
                <span class="tt-empty">—</span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty" style="padding:2rem"><i class="fa-solid fa-calendar-xmark"></i>No timetable set for Grade <?= htmlspecialchars($grade) ?> yet.</div>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

</main>
</div>

<footer class="site-footer">&copy; Designed by Kelvin Mutinda 2026. All rights reserved.</footer>

<script>
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');
function openSidebar(){sidebar.classList.add('open');overlay.classList.add('active');hamburger.innerHTML='<i class="fa-solid fa-xmark"></i>';document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('active');hamburger.innerHTML='<i class="fa-solid fa-bars"></i>';document.body.style.overflow=''}
hamburger.addEventListener('click',()=>sidebar.classList.contains('open')?closeSidebar():openSidebar());
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSidebar()});
function confirmLogout(){return confirm('Are you sure you want to log out?')}
</script>
</body>
</html>
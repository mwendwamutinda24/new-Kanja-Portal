<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Progress Records — Kanja School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php
/* ══════════════════════════════════════════════════════════
   ALL DATA LOGIC LIVES AT THE TOP so CSV export can exit
   cleanly before any HTML is sent.
   ══════════════════════════════════════════════════════════ */
include 'conn.php';

/* Map of DB column => display name for every subject column in exam2 */
$subjectColumns = [
  'math'   => 'Mathematics',
  'eng'    => 'English',
  'kisw'   => 'Kiswahili',
  'sst'    => 'S.S.T',
  'scie'   => 'Science',
  'ca'     => 'C.A',
  'agri'   => 'Agriculture',
  're'     => 'R.E',
  'pretec' => 'Pre-Technical',
];

/* ── Filters ── */
$where  = [];
$params = [];
$types  = '';

if (!empty($_GET['grade'])) {
  $where[]  = 'grade = ?';
  $params[] = intval($_GET['grade']);
  $types   .= 'i';
}

if (!empty($_GET['term'])) {
  // term is stored inconsistently ("Term 1" vs "1"), so match loosely
  $where[]  = 'term LIKE ?';
  $params[] = '%' . intval($_GET['term']) . '%';
  $types   .= 's';
}

$subjectFilter = trim($_GET['subject'] ?? '');

$sql = "SELECT * FROM exam2"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY firstName, lastName, term";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$dbRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* helpers */
function scoreClass($s) {
  if ($s >= 75) return 'fill-green';
  if ($s >= 50) return 'fill-blue';
  if ($s >= 35) return 'fill-amber';
  return 'fill-red';
}

function getGrade($score) {
  if ($score >= 75) return 'E..E';
  if ($score >= 50) return 'M.E';
  if ($score >= 25) return 'A.E';
  if ($score < 25) return 'B.E';
  return 'E';
}

/* ── Unpivot: one row per subject-with-a-score, per DB row ── */
$rows = [];
foreach ($dbRows as $r) {
  foreach ($subjectColumns as $col => $label) {
    $score = isset($r[$col]) ? (int)$r[$col] : 0;
    if ($score <= 0) continue; // subject wasn't entered for this submission

    if ($subjectFilter !== '' && stripos($label, $subjectFilter) === false) {
      continue; // doesn't match the subject text filter
    }

    $rows[] = [
      'admNo'     => $r['Assesment'] ?? '—',
      'firstName' => $r['firstName'] ?? '',
      'lastName'  => $r['lastName'] ?? '',
      'classGrade'=> $r['grade'] ?? '—',
      'term'      => $r['term'] ?? '—',
      'subject'   => $label,
      'score'     => $score,
      'letter'    => getGrade($score),
    ];
  }
}
$count = count($rows);

/* ── CSV DOWNLOAD (of the currently filtered/unpivoted result set) ── */
if (($_GET['export'] ?? '') === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="progress_records_' . date('Ymd_His') . '.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Adm No.', 'First Name', 'Last Name', 'Class', 'Term', 'Subject', 'Score', 'Grade']);
  foreach ($rows as $row) {
    fputcsv($out, [
      $row['admNo'], $row['firstName'], $row['lastName'],
      $row['classGrade'], $row['term'], $row['subject'],
      $row['score'], $row['letter'],
    ]);
  }
  fclose($out);
  exit;
}

/* ── summary chip counts (based on the same unpivoted subject entries) ── */
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COUNT(DISTINCT firstName, lastName) AS c FROM exam2"))['c'] ?? 0;
$totalGradesLogged = 0;
$passingCount = 0;
$failingCount = 0;
foreach ($dbRows as $r) {
  foreach ($subjectColumns as $col => $label) {
    $score = isset($r[$col]) ? (int)$r[$col] : 0;
    if ($score <= 0) continue;
    $totalGradesLogged++;
    if ($score >= 50) $passingCount++; else $failingCount++;
  }
}

/* Build a query string that preserves current filters, for the download link */
$exportQuery = http_build_query(array_merge($_GET, ['export' => 'csv']));
?>

  <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:        #f0c040;
      --gold-dim:    #c9a030;
      --black:       #111111;
      --dark:        #1a1a1a;
      --mid:         #2a2a2a;
      --bg:          #f4f4f2;
      --bg-card:     #ffffff;
      --bg-input:    #f8f8f6;
      --text-primary:   #1a1a18;
      --text-secondary: #5f5e5a;
      --text-tertiary:  #888780;
      --border:         rgba(0,0,0,0.09);
      --radius-md:  8px;
      --radius-lg:  12px;
      --sidebar-w:  260px;

      --grade-a: #16a34a;
      --grade-b: #2563eb;
      --grade-c: #d97706;
      --grade-d: #dc2626;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 200;
      backdrop-filter: blur(2px);
    }
    .overlay.active { display: block; }

    .site-header {
      position: sticky;
      top: 0;
      z-index: 300;
      background: var(--black);
      border-bottom: 3px solid var(--gold);
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0 1.6rem;
      height: 62px;
      flex-shrink: 0;
    }

    .hamburger {
      display: none;
      background: var(--mid);
      border: none;
      cursor: pointer;
      color: #fff;
      font-size: 14px;
      width: 34px; height: 34px;
      border-radius: 7px;
      align-items: center; justify-content: center;
      flex-shrink: 0;
      transition: background 0.2s;
    }
    .hamburger:hover { background: var(--gold); color: var(--black); }

    .school-logo {
      width: 36px; height: 36px;
      border-radius: 8px;
      background: var(--gold);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .school-logo i { color: var(--black); font-size: 16px; }

    .school-name-wrap { flex: 1; min-width: 0; }
    .school-name {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.4rem;
      letter-spacing: 0.12em;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      line-height: 1;
    }
    .school-name span { color: var(--gold); }
    .school-motto {
      font-size: 10px;
      color: #666;
      font-weight: 500;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-top: 2px;
    }

    .header-tag {
      font-size: 11px;
      color: #555;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .header-tag i { color: var(--gold); font-size: 10px; }

    .layout { display: flex; flex: 1; }

    .sidebar {
      width: var(--sidebar-w);
      background: var(--black);
      border-right: 1px solid var(--mid);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
      position: relative;
      overflow: hidden;
    }

    .sidebar::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 3px; height: 100%;
      background: linear-gradient(to bottom, var(--gold) 0%, transparent 100%);
    }

    .sidebar-inner {
      flex: 1;
      overflow-y: auto;
      padding: 1.2rem 0;
      scrollbar-width: thin;
      scrollbar-color: var(--mid) transparent;
    }

    .nav-label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #444;
      padding: 0.8rem 1.4rem 0.4rem;
    }

    .sidebar nav {
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding: 0 0.75rem;
    }

    .sidebar nav a {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 9px 13px;
      border-radius: var(--radius-md);
      font-size: 13.5px;
      font-weight: 400;
      color: #aaa;
      text-decoration: none;
      border-left: 3px solid transparent;
      transition: all 0.18s;
    }

    .sidebar nav a i {
      width: 17px;
      text-align: center;
      font-size: 13px;
      color: #555;
      flex-shrink: 0;
      transition: color 0.15s;
    }

    .sidebar nav a:hover { background: rgba(255,255,255,0.04); color: #fff; border-left-color: var(--gold); }
    .sidebar nav a:hover i { color: var(--gold); }
    .sidebar nav a.active { background: rgba(240,192,64,0.08); color: #fff; font-weight: 500; border-left-color: var(--gold); }
    .sidebar nav a.active i { color: var(--gold); }

    .sidebar-footer {
      padding: 1rem 1.4rem;
      border-top: 1px solid var(--mid);
      font-size: 11px;
      color: #444;
      letter-spacing: 0.04em;
    }

    .main {
      flex: 1;
      min-width: 0;
      padding: 2rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .page-title-bar {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .page-eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--gold-dim);
      margin-bottom: 4px;
    }

    .page-title {
      font-family: 'DM Serif Display', serif;
      font-size: 24px;
      font-weight: 400;
      color: var(--text-primary);
      line-height: 1.2;
    }

    .stats-row {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .stat-chip {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1rem 1.4rem;
      display: flex;
      flex-direction: column;
      gap: 4px;
      flex: 1;
      min-width: 130px;
      animation: fadeUp 0.4s ease both;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-chip:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }

    .stat-chip .chip-val {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      color: var(--black);
      line-height: 1;
    }

    .stat-chip .chip-lbl {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-tertiary);
    }

    .stat-chip.gold { border-top: 3px solid var(--gold); }
    .stat-chip.green { border-top: 3px solid var(--grade-a); }
    .stat-chip.blue  { border-top: 3px solid var(--grade-b); }
    .stat-chip.red   { border-top: 3px solid var(--grade-d); }

    .filter-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: flex-end;
      gap: 1rem;
      flex-wrap: wrap;
      animation: fadeUp 0.4s 0.05s ease both;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
      flex: 1;
      min-width: 150px;
    }

    .field-label {
      font-size: 12px;
      font-weight: 500;
      color: var(--text-secondary);
    }

    .field-select, .field-input {
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      color: var(--text-primary);
      background: var(--bg-input);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: var(--radius-md);
      padding: 9px 13px;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      width: 100%;
    }

    .field-select {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888780' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 32px;
      cursor: pointer;
    }

    .field-select:focus, .field-input:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.12);
    }

    .btn-filter, .btn-download {
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 500;
      color: var(--black);
      background: var(--gold);
      border: none;
      border-radius: var(--radius-md);
      padding: 9px 20px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      white-space: nowrap;
      transition: background 0.15s, transform 0.1s;
      height: 40px;
      text-decoration: none;
    }
    .btn-filter:hover, .btn-download:hover { background: var(--gold-dim); }
    .btn-filter:active, .btn-download:active { transform: scale(0.98); }

    .btn-download {
      background: var(--black);
      color: var(--gold);
      border: 1px solid var(--gold);
    }
    .btn-download:hover { background: var(--mid); }

    .table-section { display: flex; flex-direction: column; gap: 0.75rem; animation: fadeUp 0.4s 0.1s ease both; }

    .table-header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .results-title {
      font-size: 14px;
      font-weight: 500;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .count-badge {
      font-size: 11px;
      font-weight: 600;
      background: var(--black);
      color: var(--gold);
      border-radius: 100px;
      padding: 2px 10px;
      letter-spacing: 0.04em;
    }

    .table-wrap {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    .table-scroll {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table.results-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13.5px;
      min-width: 700px;
    }

    table.results-table thead tr.banner-row th {
      background: var(--black);
      color: var(--gold);
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.1rem;
      letter-spacing: 0.14em;
      text-align: center;
      padding: 0.9rem;
      border-bottom: 2px solid var(--gold);
    }

    table.results-table thead tr.col-row th {
      background: #fafafa;
      padding: 10px 16px;
      text-align: left;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      color: var(--text-tertiary);
      white-space: nowrap;
      border-bottom: 1px solid rgba(0,0,0,0.07);
    }

    table.results-table thead tr.col-row th:first-child { width: 48px; text-align: center; }

    table.results-table td {
      padding: 12px 16px;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      vertical-align: middle;
      color: var(--text-primary);
    }

    table.results-table td:first-child { text-align: center; color: var(--text-tertiary); font-size: 12px; font-weight: 500; }
    table.results-table tbody tr:last-child td { border-bottom: none; }
    table.results-table tbody tr:hover { background: var(--bg-input); }

    .mono {
      font-family: 'Courier New', monospace;
      font-size: 12.5px;
      color: var(--text-secondary);
    }

    .avatar {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: var(--black);
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 11px;
      font-weight: 600;
      color: var(--gold);
      flex-shrink: 0;
    }

    .name-with-avatar { display: flex; align-items: center; gap: 9px; }

    .grade-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.04em;
      padding: 3px 10px;
      border-radius: 100px;
    }

    .grade-pill.A  { background: #dcfce7; color: var(--grade-a); }
    .grade-pill.B  { background: #dbeafe; color: var(--grade-b); }
    .grade-pill.C  { background: #fef3c7; color: var(--grade-c); }
    .grade-pill.D, .grade-pill.E  { background: #fee2e2; color: var(--grade-d); }

    .score-bar-wrap { display: flex; align-items: center; gap: 8px; }
    .score-bar {
      flex: 1;
      height: 5px;
      background: #eee;
      border-radius: 99px;
      overflow: hidden;
      min-width: 60px;
    }
    .score-bar-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
    .fill-green { background: var(--grade-a); }
    .fill-blue  { background: var(--grade-b); }
    .fill-amber { background: var(--grade-c); }
    .fill-red   { background: var(--grade-d); }

    .score-num { font-size: 13px; font-weight: 600; min-width: 32px; text-align: right; }

    .empty-state {
      padding: 3.5rem 1.5rem;
      text-align: center;
    }
    .empty-icon {
      width: 64px; height: 64px;
      background: var(--black);
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
    }
    .empty-icon i { color: var(--gold); font-size: 26px; }
    .empty-state h3 { font-size: 15px; font-weight: 500; margin-bottom: 6px; }
    .empty-state p { font-size: 13px; color: var(--text-tertiary); }

    .site-footer {
      background: var(--black);
      border-top: 2px solid var(--gold);
      padding: 1rem 1.5rem;
      text-align: center;
      font-size: 11px;
      color: #444;
      letter-spacing: 0.04em;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .stat-chip:nth-child(1) { animation-delay: 0.04s; }
    .stat-chip:nth-child(2) { animation-delay: 0.10s; }
    .stat-chip:nth-child(3) { animation-delay: 0.16s; }
    .stat-chip:nth-child(4) { animation-delay: 0.22s; }

    @media (max-width: 900px) {
      .sidebar {
        position: fixed;
        top: 62px; left: 0; bottom: 0;
        z-index: 250;
        transform: translateX(-100%);
        box-shadow: 6px 0 30px rgba(0,0,0,0.4);
      }
      .sidebar.open { transform: translateX(0); }
      .hamburger { display: flex; }
      .header-tag { display: none; }
      .main { padding: 1.25rem 1rem 3rem; }
      .page-title { font-size: 20px; }
      .filter-card { padding: 1rem; }
      .btn-filter { width: 100%; justify-content: center; }
      .stats-row { gap: 0.75rem; }
    }

    @media (max-width: 480px) {
      .page-title-bar { flex-direction: column; }
      .school-name { font-size: 1.1rem; }
      .stat-chip { min-width: calc(50% - 0.375rem); }
    }
  
  </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<header class="site-header">
  <button class="hamburger" id="hamburger" aria-label="Open menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="school-logo"><i class="fa-solid fa-graduation-cap"></i></div>
  <div class="school-name-wrap">
    <div class="school-name">Stephen Kanja <span>School</span></div>
    <div class="school-motto">Aim Higher</div>
  </div>
  <div class="header-tag"><i class="fa-solid fa-circle-dot"></i> Progress Records</div>
</header>

<div class="layout">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <p class="nav-label">Main</p>
      <nav>
        <a href="Hoi.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
        <a href="Progress.php" class="active"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
      </nav>
      <p class="nav-label">Administration</p>
      <nav>
        <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
        <a href="RegisterTeacher.php"><i class="fa-solid fa-user-plus"></i> Register Teachers</a>
      </nav>
      <p class="nav-label">Academics</p>
      <nav>
        <a href="ViewResults.php"><i class="fa-solid fa-chart-pie"></i> Results</a>
        <a href="UploadResults.php"><i class="fa-solid fa-upload"></i> Upload Results</a>
        <a href="track.php"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
      </nav>
      <p class="nav-label">Finance</p>
      <nav>
        <a href="Fee.php"><i class="fa-solid fa-coins"></i> Finances</a>
      </nav>
    </div>
    <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
  </aside>

  <main class="main">

    <div class="page-title-bar">
      <div>
        <p class="page-eyebrow">Academic Tracking</p>
        <h1 class="page-title">Progress Records</h1>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-chip gold">
        <span class="chip-val"><?= $totalStudents ?></span>
        <span class="chip-lbl">Total Learners</span>
      </div>
      <div class="stat-chip blue">
        <span class="chip-val"><?= $totalGradesLogged ?></span>
        <span class="chip-lbl">Records Logged</span>
      </div>
      <div class="stat-chip green">
        <span class="chip-val"><?= $passingCount ?></span>
        <span class="chip-lbl">Passing (&ge;50)</span>
      </div>
      <div class="stat-chip red">
        <span class="chip-val"><?= $failingCount ?></span>
        <span class="chip-lbl">At Risk (&lt;50)</span>
      </div>
    </div>

    <div class="filter-card">
      <form method="GET" action="" style="display:contents;">
        <div class="field-group">
          <label class="field-label" for="grade">Class / Grade</label>
          <select class="field-select" name="grade" id="grade">
            <option value="">All Grades</option>
            <?php for ($g = 1; $g <= 9; $g++): ?>
              <option value="<?= $g ?>" <?= (isset($_GET['grade']) && intval($_GET['grade']) === $g) ? 'selected' : '' ?>>
                Grade <?= $g ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="field-group">
          <label class="field-label" for="term">Term</label>
          <select class="field-select" name="term" id="term">
            <option value="">All Terms</option>
            <option value="1" <?= (isset($_GET['term']) && $_GET['term']==='1') ? 'selected' : '' ?>>Term 1</option>
            <option value="2" <?= (isset($_GET['term']) && $_GET['term']==='2') ? 'selected' : '' ?>>Term 2</option>
            <option value="3" <?= (isset($_GET['term']) && $_GET['term']==='3') ? 'selected' : '' ?>>Term 3</option>
          </select>
        </div>
        <div class="field-group">
          <label class="field-label" for="subject">Subject</label>
          <input class="field-input" type="text" name="subject" id="subject"
                 placeholder="e.g. Mathematics"
                 value="<?= htmlspecialchars($_GET['subject'] ?? '') ?>">
        </div>
        <button class="btn-filter" type="submit">
          <i class="fa-solid fa-magnifying-glass"></i> Filter
        </button>
      </form>
    </div>

    <div class="table-section">
      <div class="table-header-row">
        <p class="results-title">
          Progress Entries
          <span class="count-badge"><?= $count ?> records</span>
        </p>
        <?php if ($count > 0): ?>
          <a class="btn-download" href="?<?= htmlspecialchars($exportQuery) ?>">
            <i class="fa-solid fa-download"></i> Download CSV
          </a>
        <?php endif; ?>
      </div>

      <div class="table-wrap">
        <?php if ($count === 0): ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-chart-line"></i></div>
            <h3>No records found</h3>
            <p>Try adjusting your filters, or upload results first.</p>
          </div>
        <?php else: ?>
        <div class="table-scroll">
          <table class="results-table">
            <thead>
              <tr class="banner-row">
                <th colspan="7">Student Progress Records</th>
              </tr>
              <tr class="col-row">
                <th>#</th>
                <th>Learner</th>
                <th>Class</th>
                <th>Term</th>
                <th>Subject</th>
                <th>Score</th>
                <th>Grade</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $i => $row):
                $initials = strtoupper(substr($row['firstName'] ?: '?', 0, 1) . substr($row['lastName'] ?: '?', 0, 1));
                $fullName = trim($row['firstName'] . ' ' . $row['lastName']);
                $pct = min(100, $row['score']);
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <div class="name-with-avatar">
                    <div class="avatar"><?= $initials ?></div>
                    <strong><?= htmlspecialchars($fullName) ?></strong>
                  </div>
                </td>
                <td><span class="mono">Grade <?= htmlspecialchars($row['classGrade']) ?></span></td>
                <td>Term <?= htmlspecialchars($row['term']) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td>
                  <div class="score-bar-wrap">
                    <div class="score-bar">
                      <div class="score-bar-fill <?= scoreClass($pct) ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="score-num"><?= $row['score'] ?></span>
                  </div>
                </td>
                <td>
                  <span class="grade-pill <?= $row['letter'] ?>">
                    <?= $row['letter'] ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<footer class="site-footer">
  &copy; Designed by Kelvin Mutinda 2026. All rights reserved.
</footer>

<script>
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('overlay');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    hamburger.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    hamburger.innerHTML = '<i class="fa-solid fa-bars"></i>';
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', () =>
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar()
  );
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
</script>
</body>
</html>
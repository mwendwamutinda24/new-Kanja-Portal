<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Students — Kanja School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:        #f0c040;
      --gold-dim:    #c9a030;
      --black:       #111111;
      --dark:        #1a1a1a;
      --mid:         #2a2a2a;
      --teal:        #1D9E75;
      --teal-dark:   #0F6E56;
      --teal-light:  #E1F5EE;
      --teal-mid:    #5DCAA5;
      --bg:          #f4f4f2;
      --bg-card:     #ffffff;
      --bg-input:    #f8f8f6;
      --text-primary:   #1a1a18;
      --text-secondary: #5f5e5a;
      --text-tertiary:  #888780;
      --border:         rgba(0,0,0,0.09);
      --border-hover:   rgba(0,0,0,0.18);
      --radius-md:  8px;
      --radius-lg:  12px;
      --sidebar-w:  260px;
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

    /* ══════════════════════════════
       OVERLAY (mobile)
    ══════════════════════════════ */
    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 200;
      backdrop-filter: blur(2px);
    }
    .overlay.active { display: block; }

    /* ══════════════════════════════
       TOP HEADER — DARK
    ══════════════════════════════ */
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

    /* ══════════════════════════════
       LAYOUT
    ══════════════════════════════ */
    .layout {
      display: flex;
      flex: 1;
    }

    /* ══════════════════════════════
       SIDEBAR — DARK
    ══════════════════════════════ */
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

    /* gold left stripe */
    .sidebar::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 3px;
      height: 100%;
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
      transition: color 0.15s;
      flex-shrink: 0;
    }

    .sidebar nav a:hover {
      background: rgba(255,255,255,0.04);
      color: #fff;
      border-left-color: var(--gold);
    }
    .sidebar nav a:hover i { color: var(--gold); }

    .sidebar nav a.active {
      background: rgba(240,192,64,0.08);
      color: #fff;
      font-weight: 500;
      border-left-color: var(--gold);
    }
    .sidebar nav a.active i { color: var(--gold); }

    .sidebar-footer {
      padding: 1rem 1.4rem;
      border-top: 1px solid var(--mid);
      font-size: 11px;
      color: #444;
      letter-spacing: 0.04em;
    }

    /* ══════════════════════════════
       MAIN CONTENT
    ══════════════════════════════ */
    .main {
      flex: 1;
      min-width: 0;
      padding: 2rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    /* ── Page title bar ── */
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
      color: var(--teal);
      margin-bottom: 4px;
    }

    .page-title {
      font-family: 'DM Serif Display', serif;
      font-size: 24px;
      font-weight: 400;
      color: var(--text-primary);
      line-height: 1.2;
    }

    /* ── Register button ── */
    .btn-register {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--black);
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 500;
      border: none;
      border-radius: var(--radius-md);
      padding: 9px 18px;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.15s, transform 0.1s;
      white-space: nowrap;
      border: 2px solid var(--gold);
    }
    .btn-register:hover { background: var(--gold); color: var(--black); }
    .btn-register:active { transform: scale(0.98); }
    .btn-register i { color: var(--gold); transition: color 0.15s; }
    .btn-register:hover i { color: var(--black); }

    /* ── Filter card ── */
    .filter-card {
      background: var(--bg-card);
      border: 1px solid rgba(0,0,0,0.08);
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: flex-end;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
      flex: 1;
      min-width: 160px;
    }

    .field-label {
      font-size: 12px;
      font-weight: 500;
      color: var(--text-secondary);
    }

    .field-select {
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      color: var(--text-primary);
      background: var(--bg-input);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: var(--radius-md);
      padding: 9px 32px 9px 13px;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888780' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      cursor: pointer;
      width: 100%;
    }

    .field-select:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(29,158,117,0.10);
    }

    .btn-filter {
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 500;
      color: var(--black);
      background: var(--gold);
      border: none;
      border-radius: var(--radius-md);
      padding: 9px 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
      white-space: nowrap;
      transition: background 0.15s, transform 0.1s;
      height: 40px;
    }
    .btn-filter:hover { background: var(--gold-dim); }
    .btn-filter:active { transform: scale(0.98); }

    /* ── Results section ── */
    .results-section {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .results-header {
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

    /* ══════════════════════════════════
       LEARNER SEARCH BAR (new)
       Client-side filter over the rows
       already rendered for the selected
       grade — instant, no extra request.
    ══════════════════════════════════ */
    .learner-search-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0.85rem 1.4rem;
      background: var(--bg-input);
      border-bottom: 1px solid var(--border);
      flex-wrap: wrap;
    }

    .learner-search-wrap {
      flex: 1;
      min-width: 220px;
      position: relative;
      display: flex;
      align-items: center;
    }

    .learner-search-wrap > i.fa-magnifying-glass {
      position: absolute;
      left: 13px;
      font-size: 12px;
      color: var(--text-tertiary);
      pointer-events: none;
    }

    .learner-search-wrap input {
      width: 100%;
      padding: 9px 36px 9px 34px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      color: var(--text-primary);
      background: var(--bg-card);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: var(--radius-md);
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .learner-search-wrap input:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.12);
    }

    .learner-search-wrap input::placeholder { color: var(--text-tertiary); }

    #learnerSearchClear {
      position: absolute;
      right: 8px;
      width: 22px; height: 22px;
      border: none;
      background: transparent;
      color: var(--text-tertiary);
      cursor: pointer;
      border-radius: 50%;
      display: none;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      transition: background 0.15s, color 0.15s;
    }
    #learnerSearchClear.visible { display: flex; }
    #learnerSearchClear:hover { background: rgba(0,0,0,0.06); color: var(--text-primary); }

    .learner-search-count {
      font-size: 11.5px;
      color: var(--text-tertiary);
      white-space: nowrap;
      flex-shrink: 0;
    }

    .results-table tbody tr.row-no-match { display: none; }

    @media (max-width: 520px) {
      .learner-search-bar { padding: 0.7rem 0.9rem; }
      .learner-search-count { width: 100%; }
    }

    /* ── Table ── */
    .table-wrap {
      background: var(--bg-card);
      border: 1px solid rgba(0,0,0,0.08);
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
      min-width: 640px;
    }

    table.results-table thead {
      background: var(--black);
      position: sticky;
      top: 0;
      z-index: 5;
    }

    table.results-table thead tr.banner-row th {
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

    table.results-table thead tr.col-row th:first-child {
      width: 48px;
      text-align: center;
    }

    table.results-table td {
      padding: 12px 16px;
      color: var(--text-primary);
      border-bottom: 1px solid rgba(0,0,0,0.05);
      vertical-align: middle;
    }

    table.results-table td:first-child {
      text-align: center;
      color: var(--text-tertiary);
      font-size: 12px;
      font-weight: 500;
    }

    table.results-table tbody tr:nth-child(even) { background: rgba(0,0,0,0.015); }
    table.results-table tbody tr:last-child td { border-bottom: none; }
    table.results-table tbody tr:hover { background: var(--teal-light); }

    /* Highlight the row that matches the search term */
    table.results-table tbody tr.row-highlight td:first-child { color: var(--gold-dim); font-weight: 700; }

    .mono {
      font-family: 'DM Mono', 'Courier New', monospace;
      font-size: 12.5px;
      color: var(--text-secondary);
    }

    .name-cell strong {
      font-weight: 500;
      color: var(--text-primary);
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
      letter-spacing: 0.04em;
    }

    .name-with-avatar {
      display: flex;
      align-items: center;
      gap: 9px;
    }

    /* ── Empty state ── */
    .empty-state {
      padding: 3rem 1.5rem;
      text-align: center;
    }

    .empty-state i {
      font-size: 32px;
      margin-bottom: 0.75rem;
      color: var(--text-tertiary);
      opacity: 0.4;
    }

    .empty-state p {
      font-size: 14px;
      color: var(--text-secondary);
      margin-bottom: 4px;
    }

    .empty-state small {
      font-size: 12px;
      color: var(--text-tertiary);
      font-weight: 300;
    }

    /* ── Footer ── */
    .site-footer {
      background: var(--black);
      border-top: 2px solid var(--gold);
      padding: 1rem 1.5rem;
      text-align: center;
      font-size: 11px;
      color: #444;
      letter-spacing: 0.04em;
    }

    /* ══════════════════════════════
       ANIMATIONS
    ══════════════════════════════ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .filter-card  { animation: fadeUp 0.4s 0.05s ease both; }
    .table-wrap   { animation: fadeUp 0.4s 0.15s ease both; }

    /* ══════════════════════════════
       RESPONSIVE
    ══════════════════════════════ */
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
    }

    @media (max-width: 480px) {
      .page-title-bar { flex-direction: column; align-items: flex-start; }
      .school-name { font-size: 1.1rem; }
    }
  </style>
</head>
<body>

<!-- Mobile overlay -->
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ════════════ HEADER — DARK ════════════ -->
<header class="site-header">
  <button class="hamburger" id="hamburger" aria-label="Open menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="school-logo">
    <i class="fa-solid fa-graduation-cap"></i>
  </div>
  <div class="school-name-wrap">
    <div class="school-name">Stephen Kanja <span>School</span></div>
    <div class="school-motto">Aim Higher</div>
  </div>
  <div class="header-tag">
    <i class="fa-solid fa-circle-dot"></i> Student Portal
  </div>
</header>

<!-- ════════════ LAYOUT ════════════ -->
<div class="layout">

  <!-- ── SIDEBAR — DARK ── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <p class="nav-label">Main</p>
      <nav>
        <a href="Hoi.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="Students.php" class="active"><i class="fa-solid fa-user-graduate"></i> Students</a>
        <a href="Progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
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

    <div class="sidebar-footer">
      © 2026 Kelvin Mutinda
    </div>
  </aside>

  <!-- ── MAIN ── -->
  <main class="main">

    <!-- Page title -->
    <div class="page-title-bar">
      <div>
        <p class="page-eyebrow">Enrollment Portal</p>
        <h1 class="page-title">Student Learners</h1>
      </div>
      <a href="RegisterStudent.php" class="btn-register">
        <i class="fa-solid fa-user-plus"></i> Register New Learner
      </a>
    </div>

    <!-- Filter card -->
    <div class="filter-card">
      <form method="GET" action="" style="display:contents;">
        <div class="field-group">
          <label class="field-label" for="grade">Filter by Grade</label>
          <select class="field-select" name="grade" id="grade" required>
            <option value="" disabled <?= !isset($_GET['grade']) ? 'selected' : '' ?>>— Select a grade —</option>
            <?php for ($g = 1; $g <= 9; $g++): ?>
              <option value="<?= $g ?>" <?= (isset($_GET['grade']) && intval($_GET['grade']) === $g) ? 'selected' : '' ?>>
                Grade <?= $g ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <button class="btn-filter" type="submit">
          <i class="fa-solid fa-magnifying-glass"></i> View Learners
        </button>
      </form>
    </div>

    <!-- Results -->
    <?php
    include 'conn.php';

    if (isset($_GET['grade'])) {
      $grade = intval($_GET['grade']);

      $sql = "SELECT Assesment, UPI, firstName, middleName, surname, DOB, birthNo
              FROM Student WHERE Grade = ? AND role='user'";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $grade);
      $stmt->execute();
      $result = $stmt->get_result();
      $rows = $result->fetch_all(MYSQLI_ASSOC);

      $sql2 = "SELECT COUNT(*) AS total FROM Student WHERE Grade = ? AND role='user'";
      $stmt2 = $conn->prepare($sql2);
      $stmt2->bind_param("i", $grade);
      $stmt2->execute();
      $total = $stmt2->get_result()->fetch_assoc()['total'];
      $stmt2->close();
    ?>

    <div class="results-section">
      <div class="results-header">
        <p class="results-title">
          Grade <?= $grade ?> Learners
          <span class="count-badge"><?= $total ?> students</span>
        </p>
      </div>

      <div class="table-wrap">

        <!-- ══ LEARNER SEARCH ══
             Filters the already-loaded rows by name, assessment
             number, or UPI, so staff can jump straight to one
             learner instead of scanning the whole grade list.
             Purely client-side — the rows are already rendered
             server-side above, this just toggles visibility. -->
        <div class="learner-search-bar" id="learnerSearchBar">
          <div class="learner-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="learnerSearch" placeholder="Search by name, assessment no, or UPI…" autocomplete="off">
            <button type="button" id="learnerSearchClear" title="Clear search" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <span class="learner-search-count" id="learnerSearchCount"></span>
        </div>
        <!-- ══ END LEARNER SEARCH ══ -->

        <div class="table-scroll">
          <table class="results-table">
            <thead>
              <tr class="banner-row">
                <th colspan="6">Grade <?= $grade ?> — Enrolled Learners</th>
              </tr>
              <tr class="col-row">
                <th>#</th>
                <th>Assessment No</th>
                <th>UPI No</th>
                <th>Full Name</th>
                <th>Date of Birth</th>
                <th>Birth Cert No</th>
              </tr>
            </thead>
            <tbody id="studentTableBody">
              <?php if (count($rows) === 0): ?>
              <tr>
                <td colspan="6">
                  <div class="empty-state">
                    <i class="fa-solid fa-user-slash"></i>
                    <p>No learners found for Grade <?= $grade ?></p>
                    <small>Try a different grade or register a new learner.</small>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($rows as $i => $row):
                $initials = strtoupper(substr($row['firstName'], 0, 1) . substr($row['surname'], 0, 1));
                $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['surname']);
              ?>
              <tr data-search="<?= htmlspecialchars(strtolower($fullName . ' ' . $row['Assesment'] . ' ' . $row['UPI'])) ?>">
                <td><?= $i + 1 ?></td>
                <td><span class="mono"><?= htmlspecialchars($row['Assesment']) ?></span></td>
                <td><span class="mono"><?= htmlspecialchars($row['UPI']) ?></span></td>
                <td>
                  <div class="name-with-avatar">
                    <div class="avatar"><?= $initials ?></div>
                    <div class="name-cell"><strong><?= htmlspecialchars($fullName) ?></strong></div>
                  </div>
                </td>
                <td><?= htmlspecialchars($row['DOB']) ?></td>
                <td><span class="mono"><?= htmlspecialchars($row['birthNo'] ?: '—') ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php } else { ?>
    <div class="table-wrap">
      <div class="empty-state">
        <i class="fa-solid fa-filter"></i>
        <p>Select a grade above to view learners</p>
        <small>Choose a grade from the dropdown and click "View Learners".</small>
      </div>
    </div>
    <?php } ?>

  </main>
</div>

<!-- ════════════ FOOTER ════════════ -->
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

  hamburger.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
  });

  /* ══════════════════════════════════════════
     LEARNER SEARCH
     Filters rows already rendered server-side
     by name, assessment number, or UPI. Every
     row carries a pre-lowercased data-search
     attribute so this is a simple substring
     match — no server round-trip needed.
  ══════════════════════════════════════════ */
  const learnerSearchInput = document.getElementById('learnerSearch');
  const learnerSearchClear = document.getElementById('learnerSearchClear');
  const learnerSearchCount = document.getElementById('learnerSearchCount');
  const studentTableBody   = document.getElementById('studentTableBody');

  function filterLearnerRows() {
    if (!studentTableBody) return;
    const term = learnerSearchInput.value.trim().toLowerCase();
    const rows = Array.from(studentTableBody.querySelectorAll('tr[data-search]'));

    learnerSearchClear.classList.toggle('visible', term.length > 0);

    if (!term) {
      rows.forEach(r => { r.classList.remove('row-no-match', 'row-highlight'); });
      learnerSearchCount.textContent = '';
      return;
    }

    let visibleCount = 0;
    rows.forEach(row => {
      const haystack = row.getAttribute('data-search') || '';
      const isMatch = haystack.includes(term);
      row.classList.toggle('row-no-match', !isMatch);
      row.classList.toggle('row-highlight', isMatch);
      if (isMatch) visibleCount++;
    });

    learnerSearchCount.textContent = visibleCount + ' of ' + rows.length + ' shown';
  }

  if (learnerSearchInput) {
    learnerSearchInput.addEventListener('input', filterLearnerRows);
    learnerSearchClear.addEventListener('click', () => {
      learnerSearchInput.value = '';
      filterLearnerRows();
      learnerSearchInput.focus();
    });
  }
</script>

</body>
</html>
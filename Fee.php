<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fee Payment — Stephen Kanja School</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:      #f0c040;
      --gold-dim:  #c9a030;
      --black:     #111111;
      --dark:      #1a1a1a;
      --mid:       #2a2a2a;
      --bg:        #f4f4f2;
      --bg-card:   #ffffff;
      --bg-input:  #f8f8f6;
      --text-primary:   #1a1a18;
      --text-secondary: #5f5e5a;
      --text-tertiary:  #888780;
      --border:    rgba(0,0,0,0.09);
      --green:     #16a34a;
      --green-bg:  #dcfce7;
      --radius-md: 8px;
      --radius-lg: 12px;
      --sidebar-w: 260px;
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

    /* ══════════ OVERLAY ══════════ */
    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 200;
      backdrop-filter: blur(2px);
    }
    .overlay.active { display: block; }

    /* ══════════ HEADER ══════════ */
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
      flex-shrink: 0;
    }
    .header-tag i { color: var(--gold); font-size: 10px; }

    /* ══════════ LAYOUT ══════════ */
    .layout { display: flex; flex: 1; }

    /* ══════════ SIDEBAR ══════════ */
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

    /* ══════════ MAIN ══════════ */
    .main {
      flex: 1;
      min-width: 0;
      padding: 2rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    /* ── Page title ── */
    .page-eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--gold-dim);
      margin-bottom: 4px;
    }

    .page-title {
      font-family: 'DM Sans', sans-serif;
      font-size: 24px;
      font-weight: 400;
      color: var(--text-primary);
      line-height: 1.2;
    }

    .page-sub {
      font-size: 13px;
      color: var(--text-tertiary);
      margin-top: 4px;
    }

    /* ── Stat chips ── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
    }

    .stat-chip {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.1rem 1.3rem;
      animation: fadeUp 0.4s ease both;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-chip:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }
    .stat-chip:nth-child(1) { border-top: 3px solid var(--black);  animation-delay: 0.04s; }
    .stat-chip:nth-child(2) { border-top: 3px solid var(--gold);   animation-delay: 0.10s; }
    .stat-chip:nth-child(3) { border-top: 3px solid var(--green);  animation-delay: 0.16s; }

    .chip-label {
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-tertiary);
      margin-bottom: 6px;
    }

    .chip-val {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      color: var(--text-primary);
      line-height: 1;
    }

    .chip-sub {
      font-size: 11px;
      color: var(--text-tertiary);
      margin-top: 4px;
    }

    /* ══════════════════════════════════
       FILTER CARD — ALIGNMENT FIX
       Same pattern as UploadResults:
       each .filter-field is flex-column
       so label + select are always paired.
    ══════════════════════════════════ */
    .filter-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      animation: fadeUp 0.4s 0.08s ease both;
    }

    .filter-card-head {
      background: var(--black);
      border-bottom: 2px solid var(--gold);
      padding: 0.8rem 1.4rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-card-head span {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1rem;
      letter-spacing: 0.1em;
      color: #fff;
    }

    .filter-card-head i { color: var(--gold); font-size: 13px; }

    .filter-body {
      padding: 1.4rem 1.6rem;
    }

    /* 4-column grid, each cell self-contained */
    .filter-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem 1.2rem;
      align-items: end;
    }

    .filter-field {
      display: flex;
      flex-direction: column;   /* label above select — always */
      gap: 6px;
      width: 100%;
    }

    .filter-field label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-secondary);
      white-space: nowrap;
      display: block;
    }

    .filter-field select {
      width: 100%;
      padding: 9px 32px 9px 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      color: var(--text-primary);
      background-color: var(--bg-input);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888780' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: var(--radius-md);
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      cursor: pointer;
      display: block;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .filter-field select:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.12);
    }

    /* Pay button aligned to bottom of its cell */
    .filter-field .btn-pay {
      width: 100%;
      padding: 10px 16px;
      background: var(--gold);
      color: var(--black);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      border: none;
      border-radius: var(--radius-md);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      transition: background 0.15s, transform 0.1s;
      height: 40px;           /* matches select height */
    }

    .filter-field .btn-pay:hover { background: var(--gold-dim); }
    .filter-field .btn-pay:active { transform: scale(0.98); }

    /* ── Table card ── */
    .table-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      animation: fadeUp 0.4s 0.16s ease both;
    }

    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.9rem 1.4rem;
      background: var(--black);
      border-bottom: 2px solid var(--gold);
    }

    .table-card-header .label {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1rem;
      letter-spacing: 0.1em;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .table-card-header .label i { color: var(--gold); }

    .count-badge {
      background: var(--gold);
      color: var(--black);
      font-size: 11px;
      font-weight: 700;
      padding: 2px 10px;
      border-radius: 20px;
      letter-spacing: 0.04em;
    }

    .table-scroll {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table.fees-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      min-width: 780px;
    }

    .fees-table thead tr.col-row th {
      background: #fafafa;
      color: var(--text-tertiary);
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 10px 12px;
      border-bottom: 1px solid rgba(0,0,0,0.07);
      text-align: left;
      white-space: nowrap;
    }

    .fees-table tbody tr {
      border-bottom: 1px solid rgba(0,0,0,0.05);
      transition: background 0.12s;
    }

    .fees-table tbody tr:hover { background: var(--bg-input); }
    .fees-table tbody tr:last-child { border-bottom: none; }

    .fees-table td {
      padding: 8px 12px;
      vertical-align: middle;
    }

    .cell-assess {
      font-size: 12px;
      color: var(--text-secondary);
      font-weight: 600;
      letter-spacing: 0.04em;
      font-family: 'Courier New', monospace;
    }

    .cell-name {
      font-size: 13px;
      font-weight: 500;
      color: var(--text-primary);
    }

    /* Fee inputs */
    .fees-table input[type="number"],
    .fees-table input[type="text"] {
      width: 100px;
      padding: 7px 10px;
      background: var(--bg-input);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 7px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      color: var(--text-primary);
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
      -moz-appearance: textfield;
    }

    .fees-table input[type="number"]::-webkit-inner-spin-button,
    .fees-table input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }

    .fees-table input[type="number"]:focus,
    .fees-table input[type="text"]:focus {
      border-color: var(--gold);
      background: #fff;
      box-shadow: 0 0 0 2px rgba(240,192,64,0.12);
    }

    /* Row total */
    .total-cell {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1rem;
      color: var(--green);
      letter-spacing: 0.04em;
      min-width: 90px;
      white-space: nowrap;
    }

    /* ── Empty / spinner ── */
    .empty-state {
      text-align: center;
      padding: 3.5rem 2rem;
    }

    .empty-icon {
      width: 56px; height: 56px;
      background: var(--black);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
    }

    .empty-icon i { color: var(--gold); font-size: 22px; }
    .empty-state p { font-size: 14px; color: var(--text-tertiary); }

    .spinner {
      display: none;
      text-align: center;
      padding: 2.5rem;
      color: var(--text-tertiary);
      font-size: 13px;
    }

    .spinner i { color: var(--gold); margin-right: 6px; }

    /* ── Submit bar ── */
    .submit-bar {
      display: none;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 1rem 1.4rem;
      border-top: 1px solid var(--border);
      background: var(--bg-input);
      flex-wrap: wrap;
    }

    .submit-bar.visible { display: flex; }

    .total-summary {
      font-size: 13px;
      color: var(--text-secondary);
      display: flex;
      align-items: baseline;
      gap: 6px;
    }

    .total-summary strong {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.4rem;
      color: var(--text-primary);
      letter-spacing: 0.04em;
    }

    .submit-actions { display: flex; gap: 10px; flex-wrap: wrap; }

    .btn-clear {
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 500;
      color: var(--text-secondary);
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 9px 16px;
      cursor: pointer;
      display: flex; align-items: center; gap: 6px;
      transition: all 0.15s;
    }
    .btn-clear:hover { border-color: rgba(0,0,0,0.2); color: var(--text-primary); }

    .btn-save {
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      color: var(--black);
      background: var(--gold);
      border: none;
      border-radius: var(--radius-md);
      padding: 10px 24px;
      cursor: pointer;
      display: flex; align-items: center; gap: 8px;
      transition: background 0.15s, transform 0.1s;
    }
    .btn-save:hover { background: var(--gold-dim); }
    .btn-save:active { transform: scale(0.98); }

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

    /* ══════════ ANIMATIONS ══════════ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ══════════ RESPONSIVE ══════════ */

    /* Tablet: 2×2 grid */
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

      .stats-row { grid-template-columns: 1fr 1fr; }

      /* 2 columns on tablet */
      .filter-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* Mobile: single column */
    @media (max-width: 520px) {
      .school-name { font-size: 1.1rem; }
      .filter-body { padding: 1rem; }
      .stats-row { grid-template-columns: 1fr; }

      /* stack everything */
      .filter-grid { grid-template-columns: 1fr; gap: 0.85rem; }

      .filter-field .btn-pay { font-size: 14px; }

      .submit-bar { flex-direction: column; align-items: stretch; }
      .submit-actions { flex-direction: column; }
      .btn-save, .btn-clear { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ════════════ HEADER ════════════ -->
<header class="site-header">
  <button class="hamburger" id="hamburger" aria-label="Open menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="school-logo"><i class="fa-solid fa-graduation-cap"></i></div>
  <div class="school-name-wrap">
    <div class="school-name">Stephen Kanja <span>School</span></div>
    <div class="school-motto">Aim Higher</div>
  </div>
  <div class="header-tag"><i class="fa-solid fa-circle-dot"></i> Fee Payment</div>
</header>

<div class="layout">

  <!-- ════════════ SIDEBAR ════════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <p class="nav-label">Main</p>
      <nav>
        <a href="Hoi.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
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
        <a href="Fee.php" class="active"><i class="fa-solid fa-coins"></i> Finances</a>
      </nav>
    </div>
    <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
  </aside>

  <!-- ════════════ MAIN ════════════ -->
  <main class="main">

    <!-- Page title -->
    <div>
      <p class="page-eyebrow">Finance Module</p>
      <h1 class="page-title">Fee Payment</h1>
      <p class="page-sub">Select a grade to load students and enter fee amounts.</p>
    </div>

    <!-- Summary stat chips -->
    <div class="stats-row">
      <div class="stat-chip">
        <div class="chip-label">Students Loaded</div>
        <div class="chip-val" id="statsCount">—</div>
        <div class="chip-sub">in selected grade</div>
      </div>
      <div class="stat-chip">
        <div class="chip-label">Total Fees Entered</div>
        <div class="chip-val" id="statsTotal">KES 0</div>
        <div class="chip-sub">across all students</div>
      </div>
      <div class="stat-chip">
        <div class="chip-label">Selected Term</div>
        <div class="chip-val" id="statsTerm">—</div>
        <div class="chip-sub">academic term</div>
      </div>
    </div>

    <form method="POST" action="feespayment.php" id="feeForm">

      <!-- ── Filter Card ── -->
      <div class="filter-card">
        <div class="filter-card-head">
          <i class="fa-solid fa-sliders"></i>
          <span>Filter Options</span>
        </div>
        <div class="filter-body">
          <!--
            FIX: Each .filter-field wraps its own label + select
            as flex-column children. The grid distributes cells
            evenly — labels and selects can never drift apart.
          -->
          <div class="filter-grid">

            <div class="filter-field">
              <label for="gradeSelect">Grade</label>
              <select id="gradeSelect" name="grade" required>
                <option value="">— Select Grade —</option>
                <?php for ($i = 1; $i <= 9; $i++) echo "<option value='$i'>Grade $i</option>"; ?>
              </select>
            </div>

            <div class="filter-field">
              <label for="termSelect">Term</label>
              <select id="termSelect" name="term" required>
                <option value="">— Select Term —</option>
                <option value="1">Term 1</option>
                <option value="2">Term 2</option>
                <option value="3">Term 3</option>
              </select>
            </div>

            <div class="filter-field">
              <label for="yearSelect">Year</label>
              <select id="yearSelect" name="year" required>
                <option value="">— Select Year —</option>
                <?php for ($y = 2026; $y <= 2030; $y++) echo "<option value='$y'>$y</option>"; ?>
              </select>
            </div>

            <!-- Button cell: empty label keeps it grid-aligned to bottom -->
            <div class="filter-field">
              <label aria-hidden="true">&nbsp;</label>
              <button type="submit" class="btn-pay">
                <i class="fa-solid fa-money-bill-wave"></i> Pay Fees
              </button>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Table card ── -->
      <div class="table-card">
        <div class="table-card-header">
          <span class="label"><i class="fa-solid fa-coins"></i> Fee Entries</span>
          <span class="count-badge" id="countBadge">0 students</span>
        </div>

        <div class="table-scroll">
          <div class="spinner" id="spinner">
            <i class="fa-solid fa-circle-notch fa-spin"></i> Loading students…
          </div>

          <table class="fees-table">
            <thead>
              <tr class="col-row">
                <th>Assessment No</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>School Fees (KES)</th>
                <th>Assessment Fee</th>
                <th>Activity Fees</th>
                <th>Other Fees</th>
                <th>Row Total</th>
              </tr>
            </thead>
            <tbody id="studentTableBody">
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-coins"></i></div>
                    <p>Select a grade to load students and enter fees</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="submit-bar" id="submitBar">
          <div class="total-summary">
            Grand Total: <strong id="grandTotal">KES 0</strong>
          </div>
          <div class="submit-actions">
            <button type="button" class="btn-clear" onclick="clearFees()">
              <i class="fa-solid fa-rotate-left"></i> Clear
            </button>
            <button type="submit" class="btn-save">
              <i class="fa-solid fa-check"></i> Save Fee Records
            </button>
          </div>
        </div>
      </div>

    </form>

  </main>
</div>

<!-- ════════════ FOOTER ════════════ -->
<footer class="site-footer">
  &copy; Designed by Kelvin Mutinda 2026. All rights reserved.
</footer>

<script>
  /* ── Sidebar ── */
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

  /* ── Grade change → load students ── */
  const gradeSelect = document.getElementById('gradeSelect');
  const termSelect  = document.getElementById('termSelect');

  gradeSelect.addEventListener('change', function () {
    const grade      = this.value;
    const spinner    = document.getElementById('spinner');
    const tbody      = document.getElementById('studentTableBody');
    const countBadge = document.getElementById('countBadge');
    const submitBar  = document.getElementById('submitBar');
    const statsCount = document.getElementById('statsCount');

    if (!grade) {
      tbody.innerHTML = emptyState('Select a grade to load students and enter fees');
      countBadge.textContent = '0 students';
      statsCount.textContent = '—';
      submitBar.classList.remove('visible');
      return;
    }

    spinner.style.display = 'block';
    tbody.innerHTML = '';
    submitBar.classList.remove('visible');

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'fetch_students.php?grade=' + grade, true);
    xhr.onload = function () {
      spinner.style.display = 'none';
      if (xhr.status === 200) {
        tbody.innerHTML = xhr.responseText;

        // Append live row-total cell to each row
        tbody.querySelectorAll('tr').forEach(row => {
          const td = document.createElement('td');
          td.className = 'total-cell';
          td.textContent = 'KES 0';
          row.appendChild(td);

          row.querySelectorAll('input[type="number"], input[type="text"]').forEach(inp => {
            inp.addEventListener('input', () => updateRowTotal(row, td));
          });
        });

        const count = tbody.querySelectorAll('tr').length;
        countBadge.textContent = count + ' student' + (count !== 1 ? 's' : '');
        statsCount.textContent = count;
        submitBar.classList.add('visible');
        updateGrandTotal();
      } else {
        tbody.innerHTML = emptyState('Error loading students. Please try again.');
      }
    };
    xhr.send();
  });

  termSelect.addEventListener('change', function () {
    const map = { '1': 'Term 1', '2': 'Term 2', '3': 'Term 3' };
    document.getElementById('statsTerm').textContent = map[this.value] || '—';
  });

  function updateRowTotal(row, totalCell) {
    let sum = 0;
    row.querySelectorAll('input[type="number"], input[type="text"]').forEach(inp => {
      const v = parseFloat(inp.value);
      if (!isNaN(v)) sum += v;
    });
    totalCell.textContent = 'KES ' + sum.toLocaleString();
    updateGrandTotal();
  }

  function updateGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.total-cell').forEach(cell => {
      const v = parseFloat(cell.textContent.replace('KES ', '').replace(/,/g, ''));
      if (!isNaN(v)) grand += v;
    });
    const fmt = 'KES ' + grand.toLocaleString();
    document.getElementById('grandTotal').textContent = fmt;
    document.getElementById('statsTotal').textContent = fmt;
  }

  function clearFees() {
    document.querySelectorAll('#studentTableBody input').forEach(inp => inp.value = '');
    document.querySelectorAll('.total-cell').forEach(cell => cell.textContent = 'KES 0');
    document.getElementById('grandTotal').textContent = 'KES 0';
    document.getElementById('statsTotal').textContent = 'KES 0';
  }

  function emptyState(msg) {
    return `<tr><td colspan="8">
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-coins"></i></div>
        <p>${msg}</p>
      </div>
    </td></tr>`;
  }
</script>

</body>
</html>
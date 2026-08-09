<?php
/*
 * RegisterStudent.php
 * ====================
 * Manual + bulk (Excel/CSV) student registration page.
 * Bulk imports are POSTed as JSON to bulk_register.php (same folder) —
 * see bulk_register.php for the matching server-side validation rule:
 * only FirstName is required, everything else is optional.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Registration — Kanja School</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- SheetJS for client-side Excel parsing -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
      --border-focus:   rgba(240,192,64,0.5);
      --green:       #16a34a;
      --green-bg:    #dcfce7;
      --red:         #dc2626;
      --red-bg:      #fee2e2;
      --radius-md:   8px;
      --radius-lg:   14px;
      --sidebar-w:   260px;
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
      flex-shrink: 0;
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
      gap: 2rem;
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
      font-size: 26px;
      font-weight: 400;
      color: var(--text-primary);
      line-height: 1.2;
    }

    .page-sub {
      font-size: 13px;
      color: var(--text-tertiary);
      margin-top: 4px;
    }

    .form-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      animation: fadeUp 0.4s ease both;
    }

    .card-head {
      background: var(--black);
      border-bottom: 2px solid var(--gold);
      padding: 1rem 1.6rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .head-icon {
      width: 36px; height: 36px;
      border-radius: 8px;
      background: var(--gold);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .head-icon i { color: var(--black); font-size: 15px; }

    .card-head-text h2 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.2rem;
      letter-spacing: 0.12em;
      color: #fff;
      line-height: 1;
    }

    .card-head-text p {
      font-size: 12px;
      color: #555;
      margin-top: 2px;
    }

    .card-body {
      padding: 1.8rem 1.6rem;
    }

    .section-label {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.13em;
      text-transform: uppercase;
      color: var(--text-tertiary);
      padding-bottom: 0.5rem;
      border-bottom: 1px solid var(--border);
      margin-bottom: 1.2rem;
      margin-top: 1.6rem;
    }
    .section-label:first-child { margin-top: 0; }

    .field-grid {
      display: grid;
      gap: 1.1rem 1.3rem;
      margin-bottom: 1.4rem;
    }
    .cols-1 { grid-template-columns: 1fr; }
    .cols-2 { grid-template-columns: 1fr 1fr; }
    .cols-3 { grid-template-columns: 1fr 1fr 1fr; }

    .field {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .field label {
      font-size: 12px;
      font-weight: 500;
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .req { color: var(--gold-dim); font-size: 14px; line-height: 1; }

    .opt-tag {
      font-size: 10px;
      color: var(--text-tertiary);
      background: var(--bg-input);
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 1px 6px;
      font-weight: 400;
    }

    .field input,
    .field select {
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      color: var(--text-primary);
      background: var(--bg-input);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: var(--radius-md);
      padding: 10px 13px;
      width: 100%;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }

    .field input::placeholder { color: var(--text-tertiary); }

    .field input:focus,
    .field select:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.12);
      background: #fff;
    }

    .field input.error {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
    }

    .field select {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888780' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 32px;
      cursor: pointer;
    }

    .field-hint {
      font-size: 11px;
      color: var(--text-tertiary);
    }

    .form-footer {
      padding: 1.2rem 1.6rem;
      background: var(--bg-input);
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .footer-note {
      font-size: 12px;
      color: var(--text-tertiary);
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .footer-note i { color: var(--gold-dim); }

    .btn-submit {
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 600;
      color: var(--black);
      background: var(--gold);
      border: none;
      border-radius: var(--radius-md);
      padding: 10px 28px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.15s, transform 0.1s;
    }
    .btn-submit:hover { background: var(--gold-dim); }
    .btn-submit:active { transform: scale(0.98); }

    .flash {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      border-radius: var(--radius-md);
      padding: 12px 16px;
      font-size: 13.5px;
      margin-bottom: 1.4rem;
      animation: fadeUp 0.3s ease both;
    }
    .flash.success { background: var(--green-bg); border: 1px solid #86efac; color: #166534; }
    .flash.error   { background: var(--red-bg);   border: 1px solid #fca5a5; color: #991b1b; }
    .flash i { margin-top: 1px; flex-shrink: 0; }

    .excel-card {
      animation-delay: 0.1s;
    }

    .drop-zone {
      border: 2px dashed rgba(0,0,0,0.12);
      border-radius: var(--radius-lg);
      padding: 2.5rem 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      position: relative;
      background: var(--bg-input);
    }

    .drop-zone:hover,
    .drop-zone.drag-over {
      border-color: var(--gold);
      background: rgba(240,192,64,0.04);
    }

    .drop-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }

    .drop-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      background: var(--black);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
    }
    .drop-icon i { color: var(--gold); font-size: 20px; }

    .drop-zone h3 {
      font-size: 15px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 4px;
    }

    .drop-zone p {
      font-size: 12.5px;
      color: var(--text-tertiary);
    }

    .drop-zone .file-types {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 10px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.06em;
      color: var(--text-tertiary);
    }

    .file-types span {
      background: var(--black);
      color: var(--gold);
      border-radius: 4px;
      padding: 2px 7px;
      font-family: 'Courier New', monospace;
    }

    .btn-template {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12.5px;
      font-weight: 600;
      color: var(--text-secondary);
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 7px 14px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.18s;
    }
    .btn-template:hover { border-color: var(--gold); color: var(--gold-dim); }
    .btn-template i { color: var(--green); }

    .preview-wrap {
      display: none;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1.4rem;
    }

    .preview-wrap.visible { display: flex; }

    .preview-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .preview-title {
      font-size: 13.5px;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .row-count {
      font-size: 11px;
      font-weight: 700;
      background: var(--black);
      color: var(--gold);
      border-radius: 100px;
      padding: 2px 10px;
      letter-spacing: 0.04em;
    }

    .btn-clear-preview {
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      font-weight: 500;
      color: var(--text-secondary);
      background: transparent;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 5px 12px;
      cursor: pointer;
      display: flex; align-items: center; gap: 5px;
      transition: all 0.15s;
    }
    .btn-clear-preview:hover { border-color: var(--red); color: var(--red); }

    .validation-summary {
      display: none;
      border-radius: var(--radius-md);
      padding: 10px 14px;
      font-size: 13px;
    }
    .validation-summary.has-errors {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      background: var(--red-bg);
      border: 1px solid #fca5a5;
      color: #991b1b;
    }
    .validation-summary.all-good {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      background: var(--green-bg);
      border: 1px solid #86efac;
      color: #166534;
    }

    .preview-table-wrap {
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    .preview-scroll { overflow-x: auto; }

    table.preview-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12.5px;
      min-width: 640px;
    }

    .preview-table thead tr.head-banner th {
      background: var(--black);
      color: var(--gold);
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1rem;
      letter-spacing: 0.12em;
      text-align: center;
      padding: 0.7rem;
      border-bottom: 2px solid var(--gold);
    }

    .preview-table thead tr.col-row th {
      background: #fafafa;
      color: var(--text-tertiary);
      font-size: 10.5px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 9px 12px;
      border-bottom: 1px solid rgba(0,0,0,0.07);
      text-align: left;
      white-space: nowrap;
    }

    .preview-table td {
      padding: 9px 12px;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      color: var(--text-primary);
      vertical-align: middle;
    }

    .preview-table tbody tr:last-child td { border-bottom: none; }
    .preview-table tbody tr:hover { background: var(--bg-input); }
    .preview-table tbody tr.row-error { background: var(--red-bg); }
    .preview-table tbody tr.row-error td { color: #991b1b; }

    .row-num {
      color: var(--text-tertiary);
      font-size: 11px;
      font-weight: 600;
      text-align: center;
      width: 32px;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 100px;
    }
    .status-pill.ok  { background: var(--green-bg); color: var(--green); }
    .status-pill.err { background: var(--red-bg);   color: var(--red); }

    .upload-progress {
      display: none;
      flex-direction: column;
      gap: 8px;
      margin-top: 1rem;
    }
    .upload-progress.visible { display: flex; }

    .progress-label {
      font-size: 12.5px;
      font-weight: 600;
      color: var(--text-secondary);
      display: flex;
      justify-content: space-between;
    }

    .progress-bar-wrap {
      height: 6px;
      background: rgba(0,0,0,0.07);
      border-radius: 99px;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      background: var(--gold);
      border-radius: 99px;
      width: 0%;
      transition: width 0.3s ease;
    }

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
      .cols-2, .cols-3 { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
      .school-name { font-size: 1.1rem; }
      .form-footer { flex-direction: column; align-items: stretch; }
      .btn-submit { width: 100%; justify-content: center; }
      .card-body { padding: 1.2rem; }
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
  <div class="header-tag"><i class="fa-solid fa-circle-dot"></i> Student Registration</div>
</header>

<div class="layout">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <p class="nav-label">Main</p>
      <nav>
        <a href="Hoi.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
        <a href="progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
      </nav>
      <p class="nav-label">Administration</p>
      <nav>
        <a href="RegisterStudent.php" class="active"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
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

    <div>
      <p class="page-eyebrow">Administration</p>
      <h1 class="page-title">Register New Learner</h1>
      <p class="page-sub">Fill the form below, or bulk-upload students via an Excel spreadsheet. Only First Name is required — everything else can be added later.</p>
    </div>

    <?php
    if (isset($_GET['success'])):
    ?>
    <div class="flash success">
      <i class="fa-solid fa-circle-check"></i>
      Student registered successfully. You may add another below.
    </div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="flash error">
      <i class="fa-solid fa-circle-exclamation"></i>
      Something went wrong. Please check your details and try again.
    </div>
    <?php endif; ?>

    <?php
    if (isset($_GET['bulk_success'])):
      $imported = intval($_GET['bulk_success']);
      $skipped  = intval($_GET['skipped'] ?? 0);
    ?>
    <div class="flash success">
      <i class="fa-solid fa-circle-check"></i>
      Bulk import complete — <strong><?= $imported ?></strong> student<?= $imported !== 1 ? 's' : '' ?> registered<?= $skipped > 0 ? ", <strong>$skipped</strong> row" . ($skipped !== 1 ? 's' : '') . " skipped (duplicates or missing first name)" : '' ?>.
    </div>
    <?php elseif (isset($_GET['bulk_error'])): ?>
    <div class="flash error">
      <i class="fa-solid fa-circle-exclamation"></i>
      Bulk import failed — <?= htmlspecialchars($_GET['bulk_error']) ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
      <div class="card-head">
        <div class="head-icon"><i class="fa-solid fa-user-pen"></i></div>
        <div class="card-head-text">
          <h2>Manual Registration</h2>
          <p>Register one student at a time</p>
        </div>
      </div>

      <form method="POST" action="/reg" id="regForm">
        <div class="card-body">

          <p class="section-label">Identification</p>
          <div class="field-grid cols-2">
            <div class="field">
              <label for="upi">UPI Number <span class="opt-tag">Optional</span></label>
              <input type="text" id="upi" name="UPI" placeholder="e.g. 12345678">
            </div>
            <div class="field">
              <label for="assessment">Assessment Number <span class="opt-tag">Optional</span></label>
              <input type="text" id="assessment" name="Assesment" placeholder="e.g. A0000001">
            </div>
          </div>
          <div class="field-grid cols-1">
            <div class="field">
              <label for="birthNo">Birth Certificate No <span class="opt-tag">Optional</span></label>
              <input type="text" id="birthNo" name="birthNo" placeholder="Enter birth certificate number">
              <span class="field-hint">Leave blank if not yet available</span>
            </div>
          </div>

          <p class="section-label">Personal Details</p>
          <div class="field-grid cols-3">
            <div class="field">
              <label for="firstName"><span class="req">✦</span> First Name</label>
              <input type="text" id="firstName" name="firstName" placeholder="First name" required>
            </div>
            <div class="field">
              <label for="middleName">Middle Name <span class="opt-tag">Optional</span></label>
              <input type="text" id="middleName" name="middleName" placeholder="Middle name">
            </div>
            <div class="field">
              <label for="surname">Surname <span class="opt-tag">Optional</span></label>
              <input type="text" id="surname" name="surname" placeholder="Surname">
            </div>
          </div>
          <div class="field-grid cols-2">
            <div class="field">
              <label for="dob">Date of Birth <span class="opt-tag">Optional</span></label>
              <input type="date" id="dob" name="DOB">
            </div>
            <div class="field">
              <label for="grade">Grade <span class="opt-tag">Optional</span></label>
              <select id="grade" name="Grade">
                <option value="">— Select grade —</option>
                <?php for ($g = 1; $g <= 9; $g++) echo "<option value='$g'>Grade $g</option>"; ?>
              </select>
            </div>
          </div>

        </div>

        <div class="form-footer">
          <span class="footer-note">
            <i class="fa-solid fa-circle-info"></i>
            Only <span class="req" style="font-size:13px;">✦</span> <strong>First Name</strong> is required — everything else can be added later
          </span>
          <button type="submit" class="btn-submit">
            <i class="fa-solid fa-user-plus"></i> Register Student
          </button>
        </div>
      </form>
    </div>

    <div class="form-card excel-card">
      <div class="card-head">
        <div class="head-icon" style="background:#16a34a;">
          <i class="fa-solid fa-file-excel" style="color:#fff;"></i>
        </div>
        <div class="card-head-text">
          <h2>Bulk Import via Excel</h2>
          <p>Upload an .xlsx or .csv file to register multiple students at once</p>
        </div>
      </div>

      <div class="card-body">

        <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);padding:1rem 1.2rem;margin-bottom:1.4rem;display:flex;align-items:flex-start;gap:12px;">
          <i class="fa-solid fa-circle-info" style="color:var(--gold-dim);margin-top:2px;flex-shrink:0;"></i>
          <div>
            <p style="font-size:13px;font-weight:600;margin-bottom:4px;">How bulk import works</p>
            <p style="font-size:12.5px;color:var(--text-tertiary);line-height:1.6;">
              Download the template below, fill in the student details (one row per student), then upload it here.
              The system will preview your data before submitting.
              Only <strong>FirstName</strong> is required — a row with a first name and nothing else will still import.
              Everything else (<em>UPI, Assessment, MiddleName, Surname, DOB, Grade, BirthCertNo</em>) is optional and
              can be filled in on the Students page later.
            </p>
          </div>
        </div>

        <div style="margin-bottom:1.4rem;">
          <button class="btn-template" onclick="downloadTemplate()">
            <i class="fa-solid fa-download"></i> Download Excel Template
          </button>
        </div>

        <div class="drop-zone" id="dropZone">
          <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" onchange="handleFile(this.files[0])">
          <div class="drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
          <h3>Drop your file here</h3>
          <p>or click to browse</p>
          <div class="file-types">
            Accepted: <span>.xlsx</span> <span>.xls</span> <span>.csv</span>
          </div>
        </div>

        <div class="preview-wrap" id="previewWrap">

          <div class="preview-header">
            <span class="preview-title">
              Preview
              <span class="row-count" id="rowCount">0 rows</span>
            </span>
            <button class="btn-clear-preview" onclick="clearPreview()">
              <i class="fa-solid fa-xmark"></i> Clear
            </button>
          </div>

          <div class="validation-summary" id="validationSummary"></div>

          <div class="preview-table-wrap">
            <div class="preview-scroll">
              <table class="preview-table">
                <thead>
                  <tr class="head-banner">
                    <th colspan="9">Student Import Preview</th>
                  </tr>
                  <tr class="col-row">
                    <th class="row-num">#</th>
                    <th>UPI</th>
                    <th>Assessment No</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Surname</th>
                    <th>Date of Birth</th>
                    <th>Grade</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="previewBody"></tbody>
              </table>
            </div>
          </div>

          <div class="upload-progress" id="uploadProgress">
            <div class="progress-label">
              <span>Importing students…</span>
              <span id="progressPct">0%</span>
            </div>
            <div class="progress-bar-wrap">
              <div class="progress-bar-fill" id="progressFill"></div>
            </div>
          </div>

          <div class="form-footer" style="border-radius:var(--radius-md);border:1px solid var(--border);">
            <span class="footer-note">
              <i class="fa-solid fa-circle-info" style="color:var(--gold-dim);"></i>
              Only rows missing a First Name will be skipped
            </span>
            <button type="button" class="btn-submit" id="bulkSubmitBtn" onclick="submitBulk()" disabled>
              <i class="fa-solid fa-upload"></i> Import All Valid Rows
            </button>
          </div>

        </div>

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

const requiredIds = ['firstName'];
const regForm = document.getElementById('regForm');

regForm.addEventListener('submit', function(e) {
  let valid = true;
  requiredIds.forEach(id => {
    const el = document.getElementById(id);
    if (!el.value.trim()) { el.classList.add('error'); valid = false; }
  });
  if (!valid) {
    e.preventDefault();
    regForm.querySelector('.error')?.focus();
  }
});

requiredIds.forEach(id => {
  document.getElementById(id)?.addEventListener('input', function() {
    this.classList.remove('error');
  });
});

const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', e => {
  e.preventDefault();
  dropZone.classList.add('drag-over');
});

['dragleave','drop'].forEach(evt => {
  dropZone.addEventListener(evt, () => dropZone.classList.remove('drag-over'));
});

dropZone.addEventListener('drop', e => {
  e.preventDefault();
  const file = e.dataTransfer.files[0];
  if (file) handleFile(file);
});

function downloadTemplate() {
  const headers = [
    ['UPI','Assessment','FirstName','MiddleName','Surname','DOB','Grade','BirthCertNo']
  ];
  const ws = XLSX.utils.aoa_to_sheet(headers);
  ws['!cols'] = [14,16,14,14,14,14,8,16].map(w => ({ wch: w }));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Students');
  XLSX.writeFile(wb, 'Kanja_Student_Import_Template.xlsx');
}

let parsedRows = [];

function handleFile(file) {
  if (!file) return;

  const ext = file.name.split('.').pop().toLowerCase();
  if (!['xlsx','xls','csv'].includes(ext)) {
    alert('Please upload a .xlsx, .xls, or .csv file.');
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const data  = new Uint8Array(e.target.result);
    const wb    = XLSX.read(data, { type: 'array', cellDates: true });
    const ws    = wb.Sheets[wb.SheetNames[0]];
    const rows  = XLSX.utils.sheet_to_json(ws, { defval: '' });

    parsedRows = rows.map((row, i) => {
      const get = (...keys) => {
        for (const k of keys) {
          const found = Object.keys(row).find(rk => rk.trim().toLowerCase() === k.toLowerCase());
          if (found !== undefined) return String(row[found]).trim();
        }
        return '';
      };

      const upi        = get('upi');
      const assessment = get('assessment','assessmentno','assess');
      const firstName  = get('firstname','first name','first');
      const middleName = get('middlename','middle name','middle','other names');
      const surname    = get('surname','last name','lastname');
      const dob        = get('dob','date of birth','dateofbirth');
      const grade      = get('grade');
      const birthCert  = get('birthcertno','birthcert','birth cert no','birth certificate');

      const errors = [];
      if (!firstName) errors.push('First name missing');
      if (grade && (isNaN(grade) || grade < 1 || grade > 9)) errors.push('Grade invalid (1–9)');

      return {
        rowNum: i + 2,
        upi, assessment, firstName, middleName, surname,
        dob: formatDate(dob), grade, birthCert,
        valid: errors.length === 0,
        errors
      };
    });

    renderPreview();
  };
  reader.readAsArrayBuffer(file);
}

function formatDate(raw) {
  if (!raw) return '';
  if (raw instanceof Date) {
    return raw.toISOString().split('T')[0];
  }
  const d = new Date(raw);
  if (!isNaN(d.getTime())) return d.toISOString().split('T')[0];
  return raw;
}

function renderPreview() {
  const wrap    = document.getElementById('previewWrap');
  const body    = document.getElementById('previewBody');
  const countEl = document.getElementById('rowCount');
  const summary = document.getElementById('validationSummary');
  const btn     = document.getElementById('bulkSubmitBtn');

  const validRows   = parsedRows.filter(r => r.valid);
  const invalidRows = parsedRows.filter(r => !r.valid);

  countEl.textContent = parsedRows.length + ' row' + (parsedRows.length !== 1 ? 's' : '');

  if (invalidRows.length > 0) {
    summary.className = 'validation-summary has-errors';
    summary.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;flex-shrink:0;"></i>
      <span><strong>${invalidRows.length}</strong> row${invalidRows.length !== 1 ? 's have' : ' has'} errors and will be skipped.
      ${validRows.length} valid row${validRows.length !== 1 ? 's' : ''} will be imported.</span>`;
  } else {
    summary.className = 'validation-summary all-good';
    summary.innerHTML = `<i class="fa-solid fa-circle-check" style="margin-top:2px;flex-shrink:0;"></i>
      <span>All <strong>${validRows.length}</strong> rows are valid and ready to import.</span>`;
  }

  body.innerHTML = parsedRows.map(r => `
    <tr class="${r.valid ? '' : 'row-error'}">
      <td class="row-num">${r.rowNum}</td>
      <td>${esc(r.upi) || '<span style="color:var(--text-tertiary)">—</span>'}</td>
      <td>${esc(r.assessment) || '<span style="color:var(--text-tertiary)">—</span>'}</td>
      <td>${esc(r.firstName)}</td>
      <td>${esc(r.middleName) || '<span style="color:var(--text-tertiary)">—</span>'}</td>
      <td>${esc(r.surname) || '<span style="color:var(--text-tertiary)">—</span>'}</td>
      <td>${esc(r.dob) || '<span style="color:var(--text-tertiary)">—</span>'}</td>
      <td>${esc(r.grade) || '<span style="color:var(--text-tertiary)">—</span>'}</td>
      <td>
        ${r.valid
          ? '<span class="status-pill ok"><i class="fa-solid fa-check"></i> OK</span>'
          : `<span class="status-pill err" title="${r.errors.join(', ')}">
               <i class="fa-solid fa-xmark"></i> ${r.errors[0]}${r.errors.length > 1 ? ' +' + (r.errors.length - 1) : ''}
             </span>`
        }
      </td>
    </tr>
  `).join('');

  btn.disabled = validRows.length === 0;
  wrap.classList.add('visible');
}

function esc(str) {
  return String(str || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function clearPreview() {
  parsedRows = [];
  document.getElementById('previewWrap').classList.remove('visible');
  document.getElementById('excelFile').value = '';
  document.getElementById('validationSummary').className = 'validation-summary';
  document.getElementById('uploadProgress').classList.remove('visible');
}

async function submitBulk() {
  const validRows = parsedRows.filter(r => r.valid);
  if (validRows.length === 0) return;

  const btn      = document.getElementById('bulkSubmitBtn');
  const progress = document.getElementById('uploadProgress');
  const fill     = document.getElementById('progressFill');
  const pctLabel = document.getElementById('progressPct');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importing…';
  progress.classList.add('visible');

  let pct = 0;
  const ticker = setInterval(() => {
    pct = Math.min(pct + Math.random() * 12, 88);
    fill.style.width = pct + '%';
    pctLabel.textContent = Math.round(pct) + '%';
  }, 200);

  try {
    const response = await fetch('/bulk_register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ students: validRows })
    });

    clearInterval(ticker);
    fill.style.width = '100%';
    pctLabel.textContent = '100%';

    const result = await response.json();

    if (result.success) {
      const skipped = (result.skipped ?? 0);
      window.location.href =
        'RegisterStudent.php?bulk_success=' + result.imported +
        '&skipped=' + skipped;
    } else {
      window.location.href =
        'RegisterStudent.php?bulk_error=' + encodeURIComponent(result.message ?? 'Unknown error');
    }

  } catch (err) {
    clearInterval(ticker);
    progress.classList.remove('visible');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-upload"></i> Import All Valid Rows';
    alert('Network error — please check your connection and try again.');
  }
}

document.querySelectorAll('.flash').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity 0.4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 7000);
});
</script>

</body>
</html>
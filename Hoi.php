<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kanja Home — Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:wght@600;700&family=DM+Mono:wght@400;500&display=swap">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      /* Core palette */
      --navy:        #0f172a;
      --navy-mid:    #1e293b;
      --navy-light:  #334155;
      --indigo:      #6366f1;
      --indigo-dim:  #4f46e5;
      --indigo-glow: rgba(99,102,241,0.18);
      --emerald:     #10b981;
      --emerald-bg:  rgba(16,185,129,0.12);
      --amber:       #f59e0b;
      --amber-bg:    rgba(245,158,11,0.12);
      --red:         #ef4444;
      --red-bg:      rgba(239,68,68,0.12);
      --sky:         #38bdf8;
      --violet:      #a78bfa;

      /* Surfaces */
      --bg:          #f1f5f9;
      --card:        #ffffff;
      --border:      #e2e8f0;
      --border-mid:  #cbd5e1;

      /* Text */
      --text-primary:   #0f172a;
      --text-secondary: #475569;
      --text-muted:     #94a3b8;

      /* Sidebar */
      --sidebar-w: 268px;

      /* Fonts */
      --font-display: 'Playfair Display', Georgia, serif;
      --font-body:    'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-mono:    'DM Mono', 'Fira Code', monospace;

      /* Shadows */
      --shadow-sm:  0 1px 3px rgba(15,23,42,0.08), 0 1px 2px rgba(15,23,42,0.06);
      --shadow-md:  0 4px 16px rgba(15,23,42,0.10), 0 2px 6px rgba(15,23,42,0.06);
      --shadow-lg:  0 12px 36px rgba(15,23,42,0.14), 0 4px 12px rgba(15,23,42,0.08);
      --shadow-card:0 2px 8px rgba(15,23,42,0.06);
    }

    html { scroll-behavior: smooth; }
    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--text-primary);
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
      font-feature-settings: 'tnum' 1;
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-mid); border-radius: 99px; }

    /* ── Overlay ── */
    .overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:200; backdrop-filter:blur(3px); }
    .overlay.active { display:block; }

    /* ════════════════════════════════
       SIDEBAR
    ════════════════════════════════ */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: var(--sidebar-w); height: 100vh;
      background: var(--navy);
      display: flex; flex-direction: column; z-index: 300;
      transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
      overflow: hidden;
    }
    /* Indigo accent stripe */
    .sidebar::after {
      content: ''; position: absolute; top: 0; right: 0;
      width: 1px; height: 100%;
      background: linear-gradient(to bottom, var(--indigo) 0%, transparent 70%);
      opacity: 0.4;
    }

    /* Brand */
    .sidebar-brand {
      padding: 1.5rem 1.4rem 1.25rem;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex; align-items: center; gap: 12px;
    }
    .brand-icon {
      width: 40px; height: 40px; flex-shrink: 0;
      background: linear-gradient(135deg, var(--indigo) 0%, var(--violet) 100%);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 0 16px rgba(99,102,241,0.4);
    }
    .brand-icon i { color: #fff; font-size: 17px; }
    .brand-text { display: flex; flex-direction: column; line-height: 1.2; }
    .brand-text .brand-name { font-family: var(--font-display); font-weight: 700; font-size: 1.08rem; color: #fff; letter-spacing: 0.01em; }
    .brand-text .brand-sub  { font-size: 10px; color: var(--text-muted); letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; margin-top: 2px; }

    /* Close btn (mobile) */
    .sidebar-close {
      display: none; position: absolute; top: 1rem; right: 1rem;
      background: rgba(255,255,255,0.07); border: none; color: #fff;
      width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
      align-items: center; justify-content: center; font-size: 12px; transition: background 0.2s;
    }
    .sidebar-close:hover { background: var(--indigo); }

    /* Nav */
    .sidebar-nav { flex: 1; overflow-y: auto; padding: 1rem 0; }
    .nav-section-label {
      font-size: 9.5px; letter-spacing: 0.12em; text-transform: uppercase;
      color: #3f5168; padding: 1rem 1.4rem 0.4rem; font-weight: 700;
    }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 10px;
      padding: 0.62rem 1.4rem; margin: 1px 10px;
      border-radius: 8px;
      color: #7a92ad; text-decoration: none;
      font-size: 13.5px; font-weight: 500; letter-spacing: 0.01em;
      transition: all 0.18s;
    }
    .sidebar-nav a:hover { color: #e2e8f0; background: rgba(255,255,255,0.05); }
    .sidebar-nav a.active {
      color: #fff; background: var(--indigo-glow);
      box-shadow: inset 3px 0 0 var(--indigo);
    }
    .sidebar-nav a i { width: 17px; text-align: center; font-size: 13px; flex-shrink: 0; }
    .sidebar-nav a:hover i, .sidebar-nav a.active i { color: var(--indigo); }

    .sidebar-footer {
      padding: 1rem 1.4rem; border-top: 1px solid rgba(255,255,255,0.05);
      font-size: 10.5px; color: #2d4459; letter-spacing: 0.04em;
    }

    /* ════════════════════════════════
       MAIN
    ════════════════════════════════ */
    .main {
      margin-left: var(--sidebar-w); min-height: 100vh;
      display: flex; flex-direction: column;
      transition: margin-left 0.3s cubic-bezier(0.4,0,0.2,1);
    }

    /* Topbar */
    .topbar {
      background: var(--navy);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      padding: 0 2rem;
      height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 1px 0 rgba(99,102,241,0.3);
    }
    .topbar-left { display: flex; align-items: center; gap: 14px; }
    .menu-toggle {
      display: none; background: rgba(255,255,255,0.06); border: none;
      color: #fff; width: 36px; height: 36px; border-radius: 8px;
      cursor: pointer; align-items: center; justify-content: center;
      font-size: 14px; transition: background 0.2s; flex-shrink: 0;
    }
    .menu-toggle:hover { background: var(--indigo); }

    .topbar-title { font-family: var(--font-display); font-weight: 700; font-size: 1.35rem; color: #fff; letter-spacing: 0.01em; }
    .topbar-title em { color: var(--indigo); font-style: normal; }

    .topbar-right { display: flex; align-items: center; gap: 16px; }
    .topbar-badge {
      display: flex; align-items: center; gap: 7px;
      font-size: 12px; font-weight: 600; color: var(--text-muted);
      letter-spacing: 0.04em; text-transform: uppercase;
    }
    .live-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--emerald); box-shadow: 0 0 0 3px rgba(16,185,129,0.25); animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{box-shadow:0 0 0 3px rgba(16,185,129,0.25)} 50%{box-shadow:0 0 0 6px rgba(16,185,129,0.1)} }

    /* Content area */
    .content { flex: 1; padding: 2rem 2.2rem; max-width: 1400px; width: 100%; }

    /* ── Section headers ── */
    .section-head {
      display: flex; align-items: center; gap: 10px;
      font-family: var(--font-display); font-weight: 700; font-size: 1.55rem;
      color: var(--text-primary); margin: 0 0 1.4rem; letter-spacing: 0.01em;
    }
    .section-head .sh-icon {
      width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 13px;
    }
    .section-head .sh-icon.indigo { background: var(--indigo-glow); color: var(--indigo); }
    .section-head .sh-icon.emerald { background: var(--emerald-bg); color: var(--emerald); }
    .section-head .sh-icon.amber  { background: var(--amber-bg);   color: var(--amber); }
    .section-head .sh-icon.violet { background: rgba(167,139,250,0.14); color: var(--violet); }
    .section-head .sh-rule { flex: 1; height: 1px; background: var(--border); margin-left: 8px; }
    .section-head.mt { margin-top: 2.8rem; }

    /* ════════════════════════════════
       KPI STAT CARDS
    ════════════════════════════════ */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.2rem; margin-bottom: 2.4rem; }

    .stat-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 16px; padding: 1.5rem 1.6rem;
      position: relative; overflow: hidden;
      box-shadow: var(--shadow-card);
      transition: transform 0.22s ease, box-shadow 0.22s ease;
      animation: fadeUp 0.5s ease both;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

    /* Colored top bar */
    .stat-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0;
      height: 3px; border-radius: 16px 16px 0 0;
    }
    .stat-card.indigo::before { background: linear-gradient(90deg, var(--indigo), var(--violet)); }
    .stat-card.emerald::before{ background: linear-gradient(90deg, var(--emerald), var(--sky)); }
    .stat-card.amber::before  { background: linear-gradient(90deg, var(--amber), #fbbf24); }
    .stat-card.navy::before   { background: linear-gradient(90deg, var(--navy-light), var(--indigo)); }

    /* Watermark icon */
    .stat-card .wm-icon {
      position: absolute; right: -8px; bottom: -12px;
      font-size: 72px; opacity: 0.04; pointer-events: none;
      color: var(--text-primary);
    }

    .stat-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
    .stat-icon {
      width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 17px;
    }
    .stat-icon.indigo { background: var(--indigo-glow); color: var(--indigo); }
    .stat-icon.emerald{ background: var(--emerald-bg);  color: var(--emerald); }
    .stat-icon.amber  { background: var(--amber-bg);    color: var(--amber); }
    .stat-icon.navy   { background: rgba(99,102,241,0.1); color: var(--indigo); }

    .stat-num { font-family: var(--font-mono); font-size: 2.6rem; font-weight: 500; color: var(--text-primary); line-height: 1; letter-spacing: -0.03em; }
    .stat-num.sm { font-size: 1.75rem; }
    .stat-label { font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 3px; }
    .stat-desc { font-size: 12px; color: var(--text-muted); }

    /* ════════════════════════════════
       TERM FINANCE CARDS
    ════════════════════════════════ */
    .finance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.2rem; margin-bottom: 2rem; }

    .finance-card {
      background: var(--card); border: 1px solid var(--border); border-radius: 16px;
      padding: 1.4rem 1.6rem; box-shadow: var(--shadow-card);
      transition: transform 0.22s ease, box-shadow 0.22s ease;
      animation: fadeUp 0.5s ease both;
    }
    .finance-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }

    .finance-card-head {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 1.1rem; padding-bottom: 0.9rem;
      border-bottom: 1px solid var(--border);
    }
    .finance-card-head .fc-title { font-family: var(--font-display); font-weight: 600; font-size: 1rem; color: var(--text-primary); }
    .term-badge {
      font-size: 10px; font-weight: 700; letter-spacing: 0.06em; padding: 3px 9px;
      border-radius: 99px; background: var(--indigo-glow); color: var(--indigo);
    }

    .fin-rows { display: flex; flex-direction: column; gap: 0; }
    .fin-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; }
    .fin-row + .fin-row { border-top: 1px solid #f8fafc; }
    .fin-row .lbl { font-size: 12px; color: var(--text-muted); font-weight: 500; }
    .fin-row .val { font-family: var(--font-mono); font-size: 13.5px; color: var(--text-primary); font-weight: 500; }
    .fin-row.total-row { margin-top: 6px; padding-top: 10px; border-top: 1.5px solid var(--border) !important; }
    .fin-row.total-row .lbl { font-weight: 700; color: var(--text-secondary); font-size: 11.5px; letter-spacing: 0.04em; text-transform: uppercase; }
    .fin-row.total-row .val { color: var(--emerald); font-size: 15px; font-weight: 600; }

    /* ════════════════════════════════
       CHART CARDS
    ════════════════════════════════ */
    .chart-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 16px; overflow: hidden; margin-bottom: 1.6rem;
      box-shadow: var(--shadow-card); animation: fadeUp 0.5s ease both;
    }
    .chart-card-header {
      padding: 1.1rem 1.6rem; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .chart-card-header h3 { font-family: var(--font-body); font-size: 14px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .chart-card-header h3 .ch-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .chart-card-header h3 .ch-dot.indigo  { background: var(--indigo); box-shadow: 0 0 8px var(--indigo); }
    .chart-card-header h3 .ch-dot.emerald { background: var(--emerald); box-shadow: 0 0 8px var(--emerald); }
    .chart-card-header h3 .ch-dot.amber   { background: var(--amber);   box-shadow: 0 0 8px var(--amber); }
    .chart-card-header h3 .ch-dot.violet  { background: var(--violet);  box-shadow: 0 0 8px var(--violet); }
    .chart-badge { font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 6px; letter-spacing: 0.04em; }
    .chart-badge.live { background: var(--emerald-bg); color: var(--emerald); }
    .chart-card-body { padding: 1.4rem 1.6rem; }
    .chart-inner { position: relative; }
    .chart-inner.h300 { height: 300px; }
    .chart-inner.h360 { height: 360px; }
    .chart-inner.h280 { height: 280px; }

    /* 2-column chart grid */
    .charts-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.4rem; margin-bottom: 1.6rem; }
    .charts-2col .chart-card { margin-bottom: 0; }

    /* ════════════════════════════════
       TABLE CARDS
    ════════════════════════════════ */
    .table-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 16px; overflow: hidden; margin-bottom: 1.8rem;
      box-shadow: var(--shadow-card); animation: fadeUp 0.5s ease both;
    }
    .table-card-header {
      padding: 1rem 1.6rem; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      background: #fafbfc;
    }
    .table-card-title { font-size: 14px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
    .table-card-title i { color: var(--indigo); }

    .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.data-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 680px; }
    table.data-table thead tr.col-row th {
      background: #fafbfc; color: var(--text-muted); font-size: 10.5px; font-weight: 700;
      letter-spacing: 0.07em; text-transform: uppercase; padding: 10px 16px;
      border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap;
    }
    table.data-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
    table.data-table tbody tr:hover { background: #f8fafc; }
    table.data-table tbody tr:last-child { border-bottom: none; }
    table.data-table tbody td { padding: 11px 16px; color: var(--text-secondary); font-size: 13px; font-weight: 500; vertical-align: middle; font-variant-numeric: tabular-nums; }
    table.data-table tbody td strong { color: var(--text-primary); }
    table.data-table tfoot tr td { background: #f8fafc; font-weight: 700; padding: 11px 16px; font-size: 13px; border-top: 2px solid var(--border); color: var(--text-primary); }

    /* Pills & Badges */
    .pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 99px; letter-spacing: 0.02em; }
    .pill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
    .pill.success { background: var(--emerald-bg); color: var(--emerald); }
    .pill.success::before { background: var(--emerald); }
    .pill.warn    { background: var(--amber-bg);   color: #b45309; }
    .pill.warn::before    { background: var(--amber); }
    .pill.danger  { background: var(--red-bg);     color: #dc2626; }
    .pill.danger::before  { background: var(--red); }

    /* Band labels */
    .band-lbl { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.03em; }
    .band-ee { background: var(--emerald-bg); color: var(--emerald); }
    .band-me { background: var(--indigo-glow);  color: var(--indigo); }
    .band-ae { background: var(--amber-bg);    color: #b45309; }
    .band-be { background: var(--red-bg);      color: #dc2626; }

    /* Mini progress bar */
    .bar-wrap { background: #e2e8f0; border-radius: 99px; height: 6px; width: 88px; display: inline-block; vertical-align: middle; margin-left: 8px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 99px; }
    .bar-fill.g { background: var(--emerald); }
    .bar-fill.a { background: var(--amber); }
    .bar-fill.r { background: var(--red); }

    /* ── Teachers table ── */
    .table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; margin-bottom: 2rem; box-shadow: var(--shadow-card); }
    table.teachers-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 640px; }
    table.teachers-table thead tr.banner-row th {
      background: var(--navy); color: #fff;
      font-family: var(--font-display); font-weight: 700; font-size: 1.2rem;
      text-align: center; padding: 1.1rem 1rem;
      border-bottom: 2px solid var(--indigo);
    }
    table.teachers-table thead tr.col-row th {
      background: #fafbfc; color: var(--text-muted); font-size: 10.5px; font-weight: 700;
      letter-spacing: 0.07em; text-transform: uppercase; padding: 10px 16px;
      border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap;
    }
    table.teachers-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
    table.teachers-table tbody tr:hover { background: #f8fafc; }
    table.teachers-table tbody td { padding: 12px 16px; color: var(--text-secondary); font-size: 13px; font-weight: 500; }

    /* Avatar initials */
    .avatar { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; font-size: 12px; font-weight: 700; margin-right: 8px; flex-shrink: 0; }

    /* ── Footer ── */
    .footer { text-align: center; padding: 1.4rem 2rem; border-top: 1px solid var(--border); font-size: 11.5px; color: var(--text-muted); letter-spacing: 0.04em; }

    /* ── Animations ── */
    @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
    .stat-card:nth-child(1){animation-delay:0.05s}
    .stat-card:nth-child(2){animation-delay:0.12s}
    .stat-card:nth-child(3){animation-delay:0.19s}
    .stat-card:nth-child(4){animation-delay:0.26s}
    .finance-card:nth-child(1){animation-delay:0.08s}
    .finance-card:nth-child(2){animation-delay:0.16s}
    .finance-card:nth-child(3){animation-delay:0.24s}

    /* ── Responsive ── */
    @media(max-width:960px) {
      .sidebar{ transform: translateX(-100%); }
      .sidebar.open{ transform: translateX(0); box-shadow: 12px 0 48px rgba(15,23,42,0.4); }
      .sidebar-close{ display: flex; }
      .main{ margin-left: 0; }
      .menu-toggle{ display: flex; }
      .topbar-right{ display: none; }
      .content{ padding: 1.2rem 1.4rem; }
      .charts-2col{ grid-template-columns: 1fr; }
    }
    @media(max-width:520px) {
      .topbar{ padding: 0 1rem; }
      .topbar-title{ font-size: 1.1rem; }
      .content{ padding: 1rem; }
      .stat-num{ font-size: 2.1rem; }
    }
  </style>
</head>
<body>

<?php
include('conn.php');

/* ══════════════════════════════════════
   DATA COLLECTION (unchanged logic)
══════════════════════════════════════ */

$totalStudents = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM Student"))['c'] ?? 0);
$totalTeachers = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM Teachers"))['c'] ?? 0);

function termFinance($conn, $term) {
    return mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(Fee),0) AS fee,
                COALESCE(SUM(Assesment),0) AS assess,
                COALESCE(SUM(Activity),0) AS activity,
                COALESCE(SUM(Other),0) AS other
         FROM Fees WHERE Term=$term"));
}
$t1 = termFinance($conn, 1);
$t2 = termFinance($conn, 2);
$t3 = termFinance($conn, 3);
$grandTotal = ($t1['fee']+$t1['assess']+$t1['activity']+$t1['other'])
            + ($t2['fee']+$t2['assess']+$t2['activity']+$t2['other'])
            + ($t3['fee']+$t3['assess']+$t3['activity']+$t3['other']);

$gradeFeeSql = "SELECT Grade,
    COALESCE(SUM(Fee),0) AS fee, COALESCE(SUM(Assesment),0) AS assess,
    COALESCE(SUM(Activity),0) AS activity, COALESCE(SUM(Other),0) AS other,
    COUNT(*) AS records
FROM Fees GROUP BY Grade ORDER BY Grade+0";
$gradeFeeRes = mysqli_query($conn, $gradeFeeSql);
$gradeData = [];
while ($row = mysqli_fetch_assoc($gradeFeeRes)) $gradeData[] = $row;

$gradeStudentRes = mysqli_query($conn, "SELECT Grade, COUNT(*) AS cnt FROM Student GROUP BY Grade ORDER BY Grade+0");
$gradeStudents = [];
while ($r = mysqli_fetch_assoc($gradeStudentRes)) $gradeStudents[$r['Grade']] = (int)$r['cnt'];

$gradeFeePaidRes = mysqli_query($conn,
    "SELECT Grade, COUNT(DISTINCT Assesment) AS paid_students,
            COALESCE(SUM(Fee+Assesment+Activity+Other),0) AS total_paid
     FROM Fees GROUP BY Grade ORDER BY Grade+0");
$gradeFeePaid = [];
while ($r = mysqli_fetch_assoc($gradeFeePaidRes)) $gradeFeePaid[$r['Grade']] = $r;

$perfRes = mysqli_query($conn,
    "SELECT grade, term, exam_type, year,
            AVG(math+eng+kisw+sst+scie+ca+agri+re+pretec) AS total_mean,
            (AVG(math)+AVG(eng)+AVG(kisw)+AVG(sst)+AVG(scie)+AVG(ca)+AVG(agri)+AVG(`re`)+AVG(pretec))/9 AS subject_mean
     FROM exam2
     GROUP BY grade, term, exam_type, year
     ORDER BY year, grade+0, term, exam_type");
$perfRows = [];
while ($r = mysqli_fetch_assoc($perfRes)) $perfRows[] = $r;

$latestGradePerf = [];
foreach ($perfRows as $p) $latestGradePerf[$p['grade']] = $p;

$allLabels = [];
foreach ($perfRows as $p) {
    $lbl = "T{$p['term']} ".ucfirst($p['exam_type'])." {$p['year']}";
    if (!in_array($lbl,$allLabels)) $allLabels[] = $lbl;
}

$gradePerfLabels = []; $gradePerfMeans = [];
for ($g=1;$g<=9;$g++) {
    $gradePerfLabels[] = "Grade $g";
    $gradePerfMeans[]  = isset($latestGradePerf[$g]) ? round((float)$latestGradePerf[$g]['subject_mean'],1) : 0;
}

$chartGrades=[]; $chartFees=[]; $chartAssess=[]; $chartAct=[]; $chartOther=[];
foreach ($gradeData as $gd) {
    $chartGrades[] = "G{$gd['Grade']}";
    $chartFees[]   = (float)$gd['fee'];
    $chartAssess[] = (float)$gd['assess'];
    $chartAct[]    = (float)$gd['activity'];
    $chartOther[]  = (float)$gd['other'];
}

$bandCounts = ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0];
$bandRes = mysqli_query($conn, "SELECT (math+eng+kisw+sst+scie+ca+agri+re+pretec)/9 AS avg_score FROM exam2");
while ($b = mysqli_fetch_assoc($bandRes)) {
    $s = (float)$b['avg_score'];
    if ($s>74) $bandCounts['ee']++;
    elseif ($s>49) $bandCounts['me']++;
    elseif ($s>25) $bandCounts['ae']++;
    else $bandCounts['be']++;
}

$colors7 = ['#6366f1','#10b981','#f59e0b','#ef4444','#38bdf8','#a78bfa','#fb923c','#34d399','#f472b6'];
$gradeDatasets = [];
for ($g=1;$g<=9;$g++) {
    $pts = [];
    foreach ($allLabels as $lbl) {
        $found = null;
        foreach ($perfRows as $p) {
            $plbl = "T{$p['term']} ".ucfirst($p['exam_type'])." {$p['year']}";
            if ((int)$p['grade']===$g && $plbl===$lbl) { $found = round((float)$p['subject_mean'],1); break; }
        }
        $pts[] = $found;
    }
    if (array_filter($pts,fn($v)=>$v!==null)) {
        $c = $colors7[$g-1];
        $gradeDatasets[] = ['label'=>"Grade $g",'data'=>$pts,'borderColor'=>$c,
            'backgroundColor'=>'transparent','borderWidth'=>2,'pointRadius'=>4,'tension'=>0.35,'spanGaps'=>true];
    }
}

$covData = []; $debtData = [];
for ($g=1;$g<=9;$g++) {
    $enrolled = $gradeStudents[$g] ?? 0;
    $paid     = $gradeFeePaid[$g]['paid_students'] ?? 0;
    $covData[]  = $enrolled > 0 ? round($paid/$enrolled*100) : 0;
    $debtData[] = max(0, $enrolled-$paid);
}

$teachersRes = mysqli_query($conn,"SELECT * FROM Teachers");
$avatarColors = ['#6366f1','#10b981','#f59e0b','#38bdf8','#a78bfa','#ef4444','#fb923c'];
?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ░░░ SIDEBAR ░░░ -->
<aside class="sidebar" id="sidebar">
  <button class="sidebar-close" onclick="closeSidebar()"><i class="fa-solid fa-xmark"></i></button>
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
    <div class="brand-text">
      <span class="brand-name">Kanja School</span>
      <span class="brand-sub">Management System</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a href="Hoi.php" class="active"><i class="fa-solid fa-house-chimney"></i> Dashboard</a>
    <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
    <a href="Progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>

    <div class="nav-section-label">Administration</div>
    <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
    <a href="attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
    <a href="RegisterTeacher.php"><i class="fa-solid fa-chalkboard-user"></i> Register Teachers</a>
    <a href="notice.php"><i class="fa-solid fa-bell"></i> Notices</a>

    <div class="nav-section-label">Academics</div>
    <a href="ViewResults.php"><i class="fa-solid fa-chart-pie"></i> Results</a>
    <a href="Timetable.php"><i class="fa-solid fa-table-cells"></i> Timetable</a>
    <a href="UploadResults.php"><i class="fa-solid fa-upload"></i> Upload Results</a>
    <a href="LearningMaterials.php"><i class="fa-solid fa-book-open"></i> Learning Materials</a>
    <a href="track.php"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>

    <div class="nav-section-label">Finance</div>
    <a href="Fee.php"><i class="fa-solid fa-coins"></i> Finances (Pay)</a>
    <a href="viewFees.php"><i class="fa-solid fa-receipt"></i> Track Fees</a>
  </nav>

  <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
</aside>

<!-- ░░░ MAIN ░░░ -->
<div class="main" id="main">

  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="openSidebar()"><i class="fa-solid fa-bars"></i></button>
      <span class="topbar-title">Stephen Kanja <em>School</em></span>
    </div>
    <div class="topbar-right">
      <div class="topbar-badge">
        <span class="live-dot"></span>
        Dashboard Live
      </div>
    </div>
  </header>

  <div class="content">

    <!-- ═══════════════════════════════════
         SECTION 1 — OVERVIEW KPIs
    ═══════════════════════════════════ -->
    <div class="section-head">
      <span class="sh-icon indigo"><i class="fa-solid fa-gauge-high"></i></span>
      School Overview
      <span class="sh-rule"></span>
    </div>

    <div class="stat-grid">
      <div class="stat-card indigo">
        <i class="fa-solid fa-user-graduate wm-icon"></i>
        <div class="stat-card-top">
          <div>
            <div class="stat-label">Total Learners</div>
            <div class="stat-num"><?= $totalStudents ?></div>
          </div>
          <div class="stat-icon indigo"><i class="fa-solid fa-user-graduate"></i></div>
        </div>
        <div class="stat-desc">Active enrolled learners</div>
      </div>

      <div class="stat-card navy">
        <i class="fa-solid fa-chalkboard-user wm-icon"></i>
        <div class="stat-card-top">
          <div>
            <div class="stat-label">Teaching Staff</div>
            <div class="stat-num"><?= $totalTeachers ?></div>
          </div>
          <div class="stat-icon navy"><i class="fa-solid fa-chalkboard-user"></i></div>
        </div>
        <div class="stat-desc">Available teachers</div>
      </div>

      <div class="stat-card amber">
        <i class="fa-solid fa-door-open wm-icon"></i>
        <div class="stat-card-top">
          <div>
            <div class="stat-label">Classes</div>
            <div class="stat-num">9</div>
          </div>
          <div class="stat-icon amber"><i class="fa-solid fa-door-open"></i></div>
        </div>
        <div class="stat-desc">Grades 1 through 9</div>
      </div>

      <div class="stat-card emerald">
        <i class="fa-solid fa-coins wm-icon"></i>
        <div class="stat-card-top">
          <div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-num sm">KES <?= number_format($grandTotal) ?></div>
          </div>
          <div class="stat-icon emerald"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="stat-desc">All terms combined</div>
      </div>
    </div>

    <!-- ═══════════════════════════════════
         SECTION 2 — FINANCES
    ═══════════════════════════════════ -->
    <div class="section-head mt">
      <span class="sh-icon emerald"><i class="fa-solid fa-coins"></i></span>
      Finances — Term Summaries
      <span class="sh-rule"></span>
    </div>

    <div class="finance-grid">
      <?php
      function renderTermCard($term, $data, $label) {
          $total = $data['fee']+$data['assess']+$data['activity']+$data['other'];
          echo "<div class='finance-card'>
            <div class='finance-card-head'>
              <span class='fc-title'>$label</span>
              <span class='term-badge'>Term $term</span>
            </div>
            <div class='fin-rows'>
              <div class='fin-row'><span class='lbl'>School Fee</span><span class='val'>".number_format((float)$data['fee'],2)."</span></div>
              <div class='fin-row'><span class='lbl'>Assessment</span><span class='val'>".number_format((float)$data['assess'],2)."</span></div>
              <div class='fin-row'><span class='lbl'>Activity</span><span class='val'>".number_format((float)$data['activity'],2)."</span></div>
              <div class='fin-row'><span class='lbl'>Other</span><span class='val'>".number_format((float)$data['other'],2)."</span></div>
              <div class='fin-row total-row'><span class='lbl'>Total</span><span class='val'>".number_format($total,2)."</span></div>
            </div>
          </div>";
      }
      renderTermCard(1,$t1,'Term 1 Totals');
      renderTermCard(2,$t2,'Term 2 Totals');
      renderTermCard(3,$t3,'Term 3 Totals');
      ?>
    </div>

    <!-- Fee chart -->
    <div class="chart-card">
      <div class="chart-card-header">
        <h3><span class="ch-dot indigo"></span>Fee Collection by Grade — All Categories</h3>
        <span class="chart-badge live">Live Data</span>
      </div>
      <div class="chart-card-body"><div class="chart-inner h360"><canvas id="gradeFeesChart"></canvas></div></div>
    </div>

    <!-- ═══════════════════════════════════
         SECTION 3 — GRADE BREAKDOWN
    ═══════════════════════════════════ -->
    <div class="section-head mt">
      <span class="sh-icon amber"><i class="fa-solid fa-table"></i></span>
      Grade-by-Grade Fee Breakdown &amp; Debt Analysis
      <span class="sh-rule"></span>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title"><i class="fa-solid fa-table-list"></i> Fee Collection Per Grade</span>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr class="col-row">
              <th>Grade</th><th>Enrolled</th><th>With Records</th>
              <th>School Fee (KES)</th><th>Assessment (KES)</th>
              <th>Activity (KES)</th><th>Other (KES)</th>
              <th>Grade Total (KES)</th><th>Coverage</th><th>Debt Status</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $grandFee=0; $grandAssess=0; $grandAct=0; $grandOther=0; $grandGTotal=0;
          for ($g=1;$g<=9;$g++) {
              $enrolled = $gradeStudents[$g] ?? 0;
              $paid     = $gradeFeePaid[$g]['paid_students'] ?? 0;
              $gTotal=0; $fee=$assess=$act=$other=0;
              foreach ($gradeData as $gd) {
                  if ((int)$gd['Grade']===$g) {
                      $fee=$gd['fee']; $assess=$gd['assess']; $act=$gd['activity']; $other=$gd['other'];
                      $gTotal=$fee+$assess+$act+$other;
                  }
              }
              $grandFee+=$fee; $grandAssess+=$assess; $grandAct+=$act; $grandOther+=$other; $grandGTotal+=$gTotal;
              $coverage = $enrolled>0 ? round($paid/$enrolled*100) : 0;
              $missing  = max(0,$enrolled-$paid);
              if ($coverage>=90) { $pillClass='success'; $pillLabel='Low Risk'; $barClass='g'; }
              elseif ($coverage>=60) { $pillClass='warn'; $pillLabel='Moderate'; $barClass='a'; }
              else { $pillClass='danger'; $pillLabel='High Risk'; $barClass='r'; }
              echo "<tr>
                <td><strong>Grade $g</strong></td>
                <td>$enrolled</td><td>$paid</td>
                <td>".number_format((float)$fee)."</td>
                <td>".number_format((float)$assess)."</td>
                <td>".number_format((float)$act)."</td>
                <td>".number_format((float)$other)."</td>
                <td><strong>".number_format($gTotal)."</strong></td>
                <td>
                  <span style='font-size:12px;font-weight:600;color:var(--text-primary)'>$coverage%</span>
                  <span class='bar-wrap'><span class='bar-fill $barClass' style='width:{$coverage}%'></span></span>
                  <br><small style='color:var(--text-muted);font-size:10.5px'>$missing unpaid</small>
                </td>
                <td><span class='pill $pillClass'>$pillLabel</span></td>
              </tr>";
          }
          ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3"><strong>Totals</strong></td>
              <td><?= number_format($grandFee) ?></td>
              <td><?= number_format($grandAssess) ?></td>
              <td><?= number_format($grandAct) ?></td>
              <td><?= number_format($grandOther) ?></td>
              <td><strong><?= number_format($grandGTotal) ?></strong></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Coverage & debt charts -->
    <div class="charts-2col">
      <div class="chart-card">
        <div class="chart-card-header">
          <h3><span class="ch-dot emerald"></span>Fee Coverage % by Grade</h3>
        </div>
        <div class="chart-card-body"><div class="chart-inner h280"><canvas id="coverageChart"></canvas></div></div>
      </div>
      <div class="chart-card">
        <div class="chart-card-header">
          <h3><span class="ch-dot amber"></span>Students Without Fee Records</h3>
        </div>
        <div class="chart-card-body"><div class="chart-inner h280"><canvas id="debtChart"></canvas></div></div>
      </div>
    </div>

    <!-- ═══════════════════════════════════
         SECTION 4 — ACADEMIC PERFORMANCE
    ═══════════════════════════════════ -->
    <div class="section-head mt">
      <span class="sh-icon violet"><i class="fa-solid fa-chart-line"></i></span>
      Academic Performance Analysis
      <span class="sh-rule"></span>
    </div>

    <div class="chart-card">
      <div class="chart-card-header">
        <h3><span class="ch-dot indigo"></span>Latest Exam — Average Subject Score by Grade</h3>
        <span class="chart-badge live">Live</span>
      </div>
      <div class="chart-card-body"><div class="chart-inner h300"><canvas id="gradePerfChart"></canvas></div></div>
    </div>

    <div class="charts-2col">
      <div class="chart-card">
        <div class="chart-card-header">
          <h3><span class="ch-dot violet"></span>Student Achievement Bands (All Exams)</h3>
        </div>
        <div class="chart-card-body"><div class="chart-inner h280"><canvas id="bandChart"></canvas></div></div>
      </div>
      <div class="chart-card">
        <div class="chart-card-header">
          <h3><span class="ch-dot emerald"></span>Latest Exam Performance per Grade</h3>
        </div>
        <div class="chart-card-body" style="overflow-x:auto">
          <table class="data-table" style="min-width:unset;font-size:12.5px">
            <thead>
              <tr class="col-row"><th>Grade</th><th>Mean /100</th><th>Band</th></tr>
            </thead>
            <tbody>
              <?php
              for ($g=1;$g<=9;$g++) {
                  $mean = isset($latestGradePerf[$g]) ? round((float)$latestGradePerf[$g]['subject_mean'],1) : null;
                  if ($mean===null) { echo "<tr><td>Grade $g</td><td style='color:var(--text-muted)'>—</td><td>—</td></tr>"; continue; }
                  if ($mean>74) { $bc='band-ee'; $bl='E.E — Exceeding'; }
                  elseif ($mean>49) { $bc='band-me'; $bl='M.E — Meeting'; }
                  elseif ($mean>25) { $bc='band-ae'; $bl='A.E — Approaching'; }
                  else { $bc='band-be'; $bl='B.E — Below'; }
                  echo "<tr><td><strong>Grade $g</strong></td><td style='font-family:var(--font-mono)'>$mean</td><td><span class='band-lbl $bc'>$bl</span></td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if (count($perfRows)>0): ?>
    <div class="chart-card">
      <div class="chart-card-header">
        <h3><span class="ch-dot violet"></span>Grade Performance Timeline — All Exams</h3>
      </div>
      <div class="chart-card-body"><div class="chart-inner h360"><canvas id="timelineChart"></canvas></div></div>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <span class="table-card-title"><i class="fa-solid fa-list-check"></i> All Exam Results Summary</span>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr class="col-row">
              <th>Grade</th><th>Term</th><th>Exam Type</th><th>Year</th>
              <th>Avg Total /900</th><th>Avg /100</th><th>Band</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($perfRows as $p) {
                $mean = round((float)$p['subject_mean'],1);
                $tot  = round((float)$p['total_mean'],1);
                if ($mean>74) { $bc='band-ee'; $bl='E.E'; }
                elseif ($mean>49) { $bc='band-me'; $bl='M.E'; }
                elseif ($mean>25) { $bc='band-ae'; $bl='A.E'; }
                else { $bc='band-be'; $bl='B.E'; }
                $examDisp = ['opener'=>'Opener','midterm'=>'Mid-Term','endterm'=>'End Term'][$p['exam_type']] ?? $p['exam_type'];
                echo "<tr>
                  <td><strong>Grade {$p['grade']}</strong></td>
                  <td>Term {$p['term']}</td>
                  <td>$examDisp</td>
                  <td>{$p['year']}</td>
                  <td style='font-family:var(--font-mono)'>$tot</td>
                  <td style='font-family:var(--font-mono)'>$mean</td>
                  <td><span class='band-lbl $bc'>$bl</span></td>
                </tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════
         SECTION 5 — TEACHING STAFF
    ═══════════════════════════════════ -->
    <div class="section-head mt">
      <span class="sh-icon indigo"><i class="fa-solid fa-chalkboard-user"></i></span>
      Teaching Staff
      <span class="sh-rule"></span>
    </div>

    <div class="table-wrap">
      <div class="table-scroll">
        <table class="teachers-table">
          <thead>
            <tr class="banner-row"><th colspan="7">Our Esteemed Teaching Staff</th></tr>
            <tr class="col-row">
              <th>Name</th><th>Email</th><th>Phone</th>
              <th>TSC No</th><th>Role</th><th>Class Teacher</th><th>Subject</th>
            </tr>
          </thead>
          <tbody>
            <?php $ai=0; while ($row = mysqli_fetch_assoc($teachersRes)):
              $initials = strtoupper(substr($row['name'],0,1));
              $col = $avatarColors[$ai % count($avatarColors)]; $ai++;
            ?>
            <tr>
              <td style="display:flex;align-items:center;gap:0">
                <span class="avatar" style="background:<?= $col ?>22;color:<?= $col ?>"><?= $initials ?></span>
                <?= htmlspecialchars($row['name']) ?>
              </td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['phoneNo']) ?></td>
              <td><code style="font-family:var(--font-mono);font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($row['TscNo']) ?></code></td>
              <td><?= htmlspecialchars($row['role']) ?></td>
              <td><?= htmlspecialchars($row['classTeacher']) ?></td>
              <td><span class="pill success" style="gap:0;padding:3px 8px"><?= htmlspecialchars($row['subject']) ?></span></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- .content -->
  <div class="footer">Designed by <strong>Kelvin Mutinda</strong> · Stephen Kanja School Management System · 2026</div>
</div><!-- .main -->

<!-- ═══════════════════════════════════
     CHARTS JAVASCRIPT
═══════════════════════════════════ -->
<script>
(function(){
  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = '#94a3b8';

  const C = {
    indigo:  '#6366f1', violet:  '#a78bfa',
    emerald: '#10b981', sky:     '#38bdf8',
    amber:   '#f59e0b', red:     '#ef4444',
    slate:   '#475569', border:  '#e2e8f0',
    grid:    'rgba(15,23,42,0.05)',
  };

  const gradeLabels    = <?= json_encode($chartGrades) ?>;
  const fees           = <?= json_encode($chartFees, JSON_NUMERIC_CHECK) ?>;
  const assess         = <?= json_encode($chartAssess, JSON_NUMERIC_CHECK) ?>;
  const activity       = <?= json_encode($chartAct, JSON_NUMERIC_CHECK) ?>;
  const other          = <?= json_encode($chartOther, JSON_NUMERIC_CHECK) ?>;
  const gradeAllLabels = ['G1','G2','G3','G4','G5','G6','G7','G8','G9'];
  const coverageData   = <?= json_encode($covData) ?>;
  const debtData       = <?= json_encode($debtData) ?>;
  const gradePerfMeans = <?= json_encode($gradePerfMeans, JSON_NUMERIC_CHECK) ?>;
  const bandData       = <?= json_encode(array_values($bandCounts)) ?>;

  /* ─ 1. Fee by Grade — Grouped Bar ─ */
  new Chart(document.getElementById('gradeFeesChart'), {
    type: 'bar',
    data: {
      labels: gradeLabels,
      datasets: [
        { label:'School Fee',  data:fees,     backgroundColor: C.indigo+'cc',  borderRadius:4, borderSkipped:false },
        { label:'Assessment',  data:assess,   backgroundColor: C.emerald+'cc', borderRadius:4, borderSkipped:false },
        { label:'Activity',    data:activity, backgroundColor: C.amber+'cc',   borderRadius:4, borderSkipped:false },
        { label:'Other',       data:other,    backgroundColor: C.sky+'99',     borderRadius:4, borderSkipped:false },
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{position:'bottom',labels:{padding:18,boxWidth:10,borderRadius:5,usePointStyle:true,pointStyle:'circle'}},
        tooltip:{
          backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#94a3b8',
          borderColor:'rgba(99,102,241,0.3)', borderWidth:1, padding:12, cornerRadius:8,
          callbacks:{label:ctx=>' KES '+ctx.raw.toLocaleString()}
        }
      },
      scales:{
        x:{grid:{display:false}, ticks:{color:'#64748b'}},
        y:{grid:{color:C.grid, drawBorder:false},
           ticks:{color:'#64748b',callback:v=>'KES '+v.toLocaleString()}}
      }
    }
  });

  /* ─ 2. Coverage % ─ */
  new Chart(document.getElementById('coverageChart'), {
    type:'bar',
    data:{
      labels:gradeAllLabels,
      datasets:[{
        label:'Coverage %',
        data:coverageData,
        backgroundColor:coverageData.map(v=>v>=90?C.emerald+'bb':v>=60?C.amber+'bb':C.red+'bb'),
        borderRadius:6, borderSkipped:false,
        borderWidth:0
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#94a3b8',
          borderColor:'rgba(99,102,241,0.2)', borderWidth:1, padding:10, cornerRadius:8,
          callbacks:{label:ctx=>`  ${ctx.raw}% coverage`}
        }
      },
      scales:{
        x:{grid:{display:false},ticks:{color:'#64748b'}},
        y:{min:0,max:100,grid:{color:C.grid},ticks:{color:'#64748b',callback:v=>v+'%'},
           title:{display:true,text:'Coverage %',color:'#94a3b8',font:{size:11}}}
      }
    }
  });

  /* ─ 3. Unpaid records ─ */
  new Chart(document.getElementById('debtChart'), {
    type:'bar',
    data:{
      labels:gradeAllLabels,
      datasets:[{
        label:'Unpaid Students',
        data:debtData,
        backgroundColor:debtData.map(v=>v===0?C.emerald+'bb':v<5?C.amber+'bb':C.red+'bb'),
        borderRadius:6, borderSkipped:false, borderWidth:0
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#94a3b8',
          borderColor:'rgba(239,68,68,0.2)', borderWidth:1, padding:10, cornerRadius:8,
          callbacks:{label:ctx=>`  ${ctx.raw} students without records`}
        }
      },
      scales:{
        x:{grid:{display:false},ticks:{color:'#64748b'}},
        y:{grid:{color:C.grid},ticks:{color:'#64748b',stepSize:1},
           title:{display:true,text:'Students',color:'#94a3b8',font:{size:11}}}
      }
    }
  });

  /* ─ 4. Grade performance bar ─ */
  new Chart(document.getElementById('gradePerfChart'), {
    type:'bar',
    data:{
      labels:['G1','G2','G3','G4','G5','G6','G7','G8','G9'],
      datasets:[{
        label:'Avg /100',
        data:gradePerfMeans,
        backgroundColor:gradePerfMeans.map(v=>
          v>74?C.emerald+'bb':v>49?C.indigo+'bb':v>25?C.amber+'bb':C.red+'bb'
        ),
        borderRadius:6, borderSkipped:false, borderWidth:0
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#94a3b8',
          borderColor:'rgba(99,102,241,0.2)', borderWidth:1, padding:10, cornerRadius:8,
          callbacks:{
            label:ctx=>`  ${ctx.raw}/100`,
            afterLabel:ctx=>{const v=ctx.raw; return v>74?'Exceeding Expectations':v>49?'Meeting Expectations':v>25?'Approaching Expectations':'Below Expectations';}
          }
        }
      },
      scales:{
        x:{grid:{display:false},ticks:{color:'#64748b'}},
        y:{min:0,max:100,grid:{color:C.grid},
           ticks:{color:'#64748b',callback:v=>v+'/100'},
           title:{display:true,text:'Average Score',color:'#94a3b8',font:{size:11}}}
      }
    }
  });

  /* ─ 5. Band doughnut ─ */
  new Chart(document.getElementById('bandChart'), {
    type:'doughnut',
    data:{
      labels:['Exceeding (E.E)','Meeting (M.E)','Approaching (A.E)','Below (B.E)'],
      datasets:[{
        data:bandData,
        backgroundColor:[C.emerald+'cc',C.indigo+'cc',C.amber+'cc',C.red+'cc'],
        borderColor:['#fff','#fff','#fff','#fff'],
        borderWidth:3, hoverOffset:10
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false, cutout:'65%',
      plugins:{
        legend:{position:'bottom',labels:{padding:16,boxWidth:10,borderRadius:5,usePointStyle:true,pointStyle:'circle'}},
        tooltip:{
          backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#94a3b8',
          borderColor:'rgba(167,139,250,0.25)', borderWidth:1, padding:10, cornerRadius:8
        }
      }
    }
  });

  /* ─ 6. Timeline line chart ─ */
  <?php if(count($perfRows)>0): ?>
  new Chart(document.getElementById('timelineChart'), {
    type:'line',
    data:{
      labels: <?= json_encode($allLabels) ?>,
      datasets: <?= json_encode($gradeDatasets) ?>
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{mode:'index',intersect:false},
      plugins:{
        legend:{position:'bottom',labels:{padding:16,boxWidth:10,borderRadius:5,usePointStyle:true,pointStyle:'circle'}},
        tooltip:{
          backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#94a3b8',
          borderColor:'rgba(99,102,241,0.25)', borderWidth:1, padding:12, cornerRadius:8
        }
      },
      scales:{
        x:{grid:{color:C.grid},ticks:{maxRotation:40,minRotation:30,color:'#64748b'}},
        y:{min:0,max:100,grid:{color:C.grid},
           ticks:{color:'#64748b',callback:v=>v+'/100'},
           title:{display:true,text:'Avg Subject Score',color:'#94a3b8',font:{size:11}}}
      }
    }
  });
  <?php endif; ?>

})();
</script>

<script>
  function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('active');
    document.body.style.overflow = '';
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
</script>

</body>
</html>
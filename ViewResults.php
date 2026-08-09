<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Results — Stephen Kanja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink:       #0f1923;
            --ink-soft:  #3d4f5c;
            --ink-mute:  #7a909e;
            --paper:     #f5f2ee;
            --cream:     #faf8f5;
            --white:     #ffffff;
            --gold:      #c8992a;
            --gold-pale: #f5e9c8;
            --blue:      #1a4a7a;
            --blue-pale: #dce8f5;
            --border:    rgba(15,25,35,0.1);
            --shadow:    0 4px 24px rgba(15,25,35,0.08);
            --shadow-lg: 0 12px 48px rgba(15,25,35,0.14);
            --ee: #16a34a; --ee-bg: #dcfce7;
            --me: #1a4a7a; --me-bg: #dce8f5;
            --ae: #92400e; --ae-bg: #fef3c7;
            --be: #991b1b; --be-bg: #fee2e2;

            /* 8-level performance bands */
            --ee2: #15803d; --ee2-bg: #bbf7d0;
            --ee1: #16a34a; --ee1-bg: #dcfce7;
            --me2: #123657; --me2-bg: #cfe0f3;
            --me1: #1a4a7a; --me1-bg: #dce8f5;
            --ae2: #92400e; --ae2-bg: #fde68a;
            --ae1: #b45309; --ae1-bg: #fef3c7;
            --be2: #7f1d1d; --be2-bg: #fecaca;
            --be1: #991b1b; --be1-bg: #fee2e2;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            font-size: 15px;
            line-height: 1.6;
        }

        /* ══════ HEADER ══════ */
        .page-header {
            background: var(--ink);
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 80% at 10% 50%, rgba(200,153,42,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 40% 60% at 90% 20%, rgba(26,74,122,0.25) 0%, transparent 60%);
            pointer-events: none;
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 48px 32px 40px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .header-crest {
            width: 68px; height: 68px;
            background: var(--gold);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 28px;
            box-shadow: 0 0 0 4px rgba(200,153,42,0.25);
        }
        .header-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 900;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .header-text .subtitle {
            margin-top: 6px;
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }
        .header-rule {
            height: 3px;
            background: linear-gradient(90deg, var(--gold) 0%, transparent 100%);
        }

        /* ══════ MAIN WRAPPER ══════ */
        .results {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }

        /* ══════ FILTER FORM ══════ */
        .filter-form {
            background: var(--white);
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 36px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px 16px;
            align-items: end;
        }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--ink-mute);
        }
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            background: var(--cream) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237a909e' stroke-width='1.8' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 11px 38px 11px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--ink);
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .form-group select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px var(--blue-pale);
        }
        .btn-view {
            background: var(--ink);
            color: var(--white);
            border: none;
            border-radius: 10px;
            padding: 12px 28px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            height: 44px;
        }
        .btn-view:hover { background: var(--blue); box-shadow: 0 6px 20px rgba(26,74,122,0.3); transform: translateY(-1px); }
        .btn-view:active { transform: translateY(0); }

        /* ══════ SECTION LABEL ══════ */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 36px 0 20px;
        }
        .section-divider h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.1rem, 2.2vw, 1.45rem);
            font-weight: 700;
            white-space: nowrap;
            color: var(--ink);
        }
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .section-badge {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            background: var(--ink);
            color: var(--gold);
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        /* ══════ EXPORT BUTTON ══════ */
        .export-btns {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #1a4a7a 0%, #123657 100%);
            color: var(--white);
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            white-space: nowrap;
            transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
            box-shadow: 0 4px 14px rgba(26,74,122,0.28);
        }
        .btn-export:hover  { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(26,74,122,0.38); }
        .btn-export:active { transform: translateY(0); }
        .btn-export:disabled { opacity: 0.6; cursor: wait; transform: none; }
        .btn-export svg { flex-shrink: 0; }
        .btn-export-gold {
            background: linear-gradient(135deg, #c8992a 0%, #a67c1e 100%);
            box-shadow: 0 4px 14px rgba(200,153,42,0.3);
        }
        .btn-export-gold:hover { box-shadow: 0 8px 22px rgba(200,153,42,0.4); }
        @media (max-width: 700px) {
            .section-divider { flex-wrap: wrap; row-gap: 10px; }
            .btn-export { font-size: 0.78rem; padding: 9px 14px; }
        }

        /* ══════ KPI STRIP ══════ */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }
        .kpi-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 18px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            animation: fadeUp 0.4s ease both;
        }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .kpi-card.kpi-total::before { background: var(--ink); }
        .kpi-card.kpi-ee::before    { background: var(--ee); }
        .kpi-card.kpi-me::before    { background: var(--me); }
        .kpi-card.kpi-below::before { background: var(--be); }
        .kpi-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--ink-mute);
            margin-bottom: 8px;
        }
        .kpi-val {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            font-weight: 900;
            line-height: 1;
            color: var(--ink);
        }
        .kpi-sub { font-size: 0.78rem; color: var(--ink-mute); margin-top: 6px; }

        /* ══════ PERFORMANCE BANDS ══════ */
        .bands-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .band-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            animation: fadeUp 0.4s 0.1s ease both;
        }
        .band-card h3 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-mute);
            margin-bottom: 18px;
        }
        .band-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .band-label {
            font-size: 0.75rem;
            font-weight: 600;
            width: 110px;
            flex-shrink: 0;
        }
        .band-bar-wrap {
            flex: 1;
            background: var(--paper);
            border-radius: 99px;
            height: 10px;
            overflow: hidden;
        }
        .band-bar {
            height: 100%;
            border-radius: 99px;
            transition: width 1s cubic-bezier(0.4,0,0.2,1);
        }
        .band-count {
            font-size: 0.78rem;
            font-weight: 700;
            width: 70px;
            text-align: right;
            color: var(--ink-soft);
            flex-shrink: 0;
        }
        .band-ee { background: var(--ee); }
        .band-me { background: var(--me); }
        .band-ae { background: #d97706; }
        .band-be { background: var(--be); }

        /* ══════ CHART GRID ══════ */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .chart-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            animation: fadeUp 0.4s 0.15s ease both;
        }
        .chart-card.full-width { grid-column: 1 / -1; }
        .chart-card h3 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-mute);
            margin-bottom: 18px;
        }
        .chart-wrap { position: relative; height: 260px; }
        .chart-wrap.tall { height: 320px; }

        /* ══════ COMPARISON CARD ══════ */
        .compare-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            margin-bottom: 28px;
            animation: fadeUp 0.4s 0.2s ease both;
        }
        .compare-card h3 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-mute);
            margin-bottom: 18px;
        }
        .compare-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .compare-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--ink-soft);
        }
        .compare-pill-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .no-prev {
            text-align: center;
            padding: 28px;
            color: var(--ink-mute);
            font-size: 0.88rem;
            background: var(--paper);
            border-radius: 10px;
        }

        /* ══════ SUBJECT MEANS / BREAKDOWN TABLES ══════ */
        .means-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            margin-bottom: 28px;
            overflow-x: auto;
            animation: fadeUp 0.4s 0.22s ease both;
        }
        .means-card h3 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-mute);
            margin-bottom: 18px;
        }
        .means-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 700px;
        }
        .means-table th {
            background: var(--ink);
            color: rgba(255,255,255,0.7);
            padding: 10px 14px;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .means-table td {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(15,25,35,0.06);
            color: var(--ink-soft);
        }
        .means-table tr:last-child td { border-bottom: none; }
        .means-table tr:hover td { background: #f0f6ff; }
        .means-table .mean-val { font-weight: 700; color: var(--ink); }
        .means-table .trend-up   { color: var(--ee); font-weight: 700; }
        .means-table .trend-down { color: var(--be); font-weight: 700; }
        .means-table .trend-same { color: var(--ink-mute); }

        /* ══════ RESULTS TABLE ══════ */
        .table-scroll {
            overflow-x: auto;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            -webkit-overflow-scrolling: touch;
            animation: fadeUp 0.4s 0.25s ease both;
        }
        .results-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            background: var(--white);
            font-size: 0.875rem;
        }
        .results-table thead tr { background: var(--ink); color: var(--white); }
        .results-table thead th {
            padding: 14px;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .results-table thead th:first-child { padding-left: 20px; }
        .results-table thead th:last-child  { padding-right: 20px; }
        .results-table tbody tr {
            border-bottom: 1px solid rgba(15,25,35,0.06);
            transition: background 0.15s;
        }
        .results-table tbody tr:hover { background: #f0f6ff; }
        .results-table tbody tr:last-child { border-bottom: none; }
        .results-table td {
            padding: 11px 14px;
            color: var(--ink-soft);
            white-space: nowrap;
        }
        .results-table td:first-child { padding-left: 20px; }
        .results-table td:last-child  { padding-right: 20px; font-weight: 700; color: var(--ink); }

        .rank-badge {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 30px; height: 30px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.8rem;
            background: var(--blue-pale);
            color: var(--blue);
        }
        .rank-badge.gold   { background: var(--gold-pale); color: #7a5a10; }
        .rank-badge.silver { background: #e8e8f0; color: #4a4a6a; }
        .rank-badge.bronze { background: #f0e6d8; color: #7a4a2a; }

        .mean-row { background: var(--ink) !important; }
        .mean-row td { color: rgba(255,255,255,0.85) !important; padding-top: 14px !important; padding-bottom: 14px !important; font-weight: 600; }
        .mean-row td:first-child { color: var(--white) !important; font-size: 0.72rem; letter-spacing: 1.5px; text-transform: uppercase; }

        .award, .mean-award {
            display: inline-block;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 4px;
            letter-spacing: 0.4px;
            vertical-align: middle;
            margin-left: 3px;
        }
        /* 8-level award pill colors (Grades 7-9) */
        .award.ee2 { background: var(--ee2-bg); color: var(--ee2); }
        .award.ee1 { background: var(--ee1-bg); color: var(--ee1); }
        .award.me2 { background: var(--me2-bg); color: var(--me2); }
        .award.me1 { background: var(--me1-bg); color: var(--me1); }
        .award.ae2 { background: var(--ae2-bg); color: var(--ae2); }
        .award.ae1 { background: var(--ae1-bg); color: var(--ae1); }
        .award.be2 { background: var(--be2-bg); color: var(--be2); }
        .award.be1 { background: var(--be1-bg); color: var(--be1); }
        /* simple 4-level award pill colors (Grades 1-6) */
        .award.ee { background: var(--ee1-bg); color: var(--ee1); }
        .award.me { background: var(--me1-bg); color: var(--me1); }
        .award.ae { background: var(--ae1-bg); color: var(--ae1); }
        .award.be { background: var(--be1-bg); color: var(--be1); }

        .mean-row .award.ee2, .mean-row .award.ee1 { background: rgba(22,163,74,0.35); color: #86efac; }
        .mean-row .award.me2, .mean-row .award.me1 { background: rgba(26,74,122,0.4);  color: #a0ccf5; }
        .mean-row .award.ae2, .mean-row .award.ae1 { background: rgba(200,153,42,0.35);color: #f5d88a; }
        .mean-row .award.be2, .mean-row .award.be1 { background: rgba(153,27,27,0.35); color: #fca5a5; }
        .mean-row .award.ee { background: rgba(22,163,74,0.35); color: #86efac; }
        .mean-row .award.me { background: rgba(26,74,122,0.4);  color: #a0ccf5; }
        .mean-row .award.ae { background: rgba(200,153,42,0.35);color: #f5d88a; }
        .mean-row .award.be { background: rgba(153,27,27,0.35); color: #fca5a5; }

        /* ══════ MESSAGES ══════ */
        .no-results {
            background: var(--white);
            border: 1.5px dashed var(--border);
            border-radius: 14px;
            padding: 48px 32px;
            text-align: center;
            color: var(--ink-mute);
            font-size: 0.95rem;
            line-height: 2;
        }
        .no-results::before { content: '📋'; display: block; font-size: 2rem; margin-bottom: 12px; }

        .scroll-hint { display: none; font-size: 0.76rem; color: var(--ink-mute); text-align: right; margin-bottom: 8px; }

        /* ══════ ANIMATION ══════ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ══════ RESPONSIVE ══════ */
        @media (max-width: 900px) {
            .kpi-strip      { grid-template-columns: repeat(2, 1fr); }
            .bands-grid     { grid-template-columns: 1fr; }
            .charts-grid    { grid-template-columns: 1fr; }
            .charts-grid .chart-card.full-width { grid-column: 1; }
        }
        @media (max-width: 700px) {
            .header-inner  { padding: 28px 18px 24px; gap: 14px; }
            .header-crest  { width: 48px; height: 48px; font-size: 20px; }
            .filter-form   { grid-template-columns: 1fr 1fr; padding: 20px 16px; gap: 14px 10px; }
            .btn-view      { grid-column: 1 / -1; }
            .results       { padding: 20px 14px 60px; }
            .scroll-hint   { display: block; }
            .kpi-strip     { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 420px) {
            .filter-form { grid-template-columns: 1fr; }
            .kpi-strip   { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header class="page-header">
    <div class="header-inner">
        <div class="header-crest">🏫</div>
        <div class="header-text">
            <h1>Stephen Kanja Primary &amp; Junior School</h1>
            <p class="subtitle">Academic Performance Transcript</p>
        </div>
    </div>
    <div class="header-rule"></div>
</header>

<div class="results">

    <!-- FILTER FORM -->
    <form method="GET" action="" class="filter-form">
        <div class="form-group">
            <label>Grade</label>
            <select name="grade" required>
                <option value="">— Select —</option>
                <?php
                $selectedGrade = $_GET['grade'] ?? '';
                for ($g = 1; $g <= 9; $g++) {
                    $sel = ($selectedGrade == $g) ? 'selected' : '';
                    echo "<option value='$g' $sel>Grade $g</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Term</label>
            <select name="term" required>
                <option value="">— Select —</option>
                <?php
                $selectedTerm = $_GET['term'] ?? '';
                foreach (['Term 1','Term 2','Term 3'] as $t) {
                    $sel = ($selectedTerm === $t) ? 'selected' : '';
                    echo "<option value='$t' $sel>$t</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Exam Type</label>
            <select name="exam_type" required>
                <option value="">— Select —</option>
                <?php
                $selectedExam = $_GET['exam_type'] ?? '';
                foreach (['opener'=>'Opener','mid-term'=>'Mid-Term','end-term'=>'End Term'] as $v=>$l) {
                    $sel = ($selectedExam === $v) ? 'selected' : '';
                    echo "<option value='$v' $sel>$l</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Year</label>
            <select name="year" required>
                <option value="">— Select —</option>
                <?php
                $selectedYear = $_GET['year'] ?? '';
                for ($y = 2026; $y <= 2030; $y++) {
                    $sel = ($selectedYear == $y) ? 'selected' : '';
                    echo "<option value='$y' $sel>$y</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn-view">View Results</button>
    </form>

<?php
include 'conn.php';
include 'subjects_config.php';

/* ── Award helpers (grade-aware) ──
   awardPill() renders one pill, e.g. <span class='award ee2' title='Exceeding Expectation 2'>EE2</span>
   for Grades 7-9, or <span class='award ee' title='Exceeding Expectation'>E.E</span> for Grades 1-6.
   $cssPrefix lets the same helper be reused for the dark mean-row styling if ever needed. */
function awardPill($score, $grade, $cssPrefix = 'award') {
    $b = bandInfoForGrade($score, $grade);
    $tierClass = bandCssClass($b['code']); // ee2, ee1, ... OR ee, me, ae, be
    return "<span class='$cssPrefix $tierClass' title=\"{$b['label']}\">{$b['code']}</span>";
}

/* Accumulates per-student totals into per-subject and per-class band tier
   counts (ee/me/ae/be) for a previous-exam result set. Mirrors the same
   band logic used for the current exam so current vs previous comparisons
   are apples-to-apples. */
function computePreviousStats($result, array $subjects) {
    $totals        = array_fill_keys($subjects, 0);
    $subjectBands  = array_fill_keys($subjects, ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0]);
    $classBands    = ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0];
    $count         = 0;
    $subjectCount  = count($subjects);

    while ($pr = mysqli_fetch_assoc($result)) {
        $rowTotal = 0;
        foreach ($subjects as $s) {
            $score = (int)($pr[$s] ?? 0);
            $totals[$s] += $score;
            $rowTotal   += $score;
            $b = bandInfo($score);
            $subjectBands[$s][$b['tier']]++;
        }
        $avgBand = bandInfo($subjectCount ? $rowTotal / $subjectCount : 0);
        $classBands[$avgBand['tier']]++;
        $count++;
    }

    return [$totals, $subjectBands, $classBands, $count];
}

/* ── Parameters ── */
$termLabel  = $_GET['term']      ?? '';
$examLabel  = $_GET['exam_type'] ?? '';
$year       = $_GET['year']      ?? '';
$grade      = $_GET['grade']     ?? '';
$gradeInt   = (int)$grade;

/*
 * FIX: exam2.term stores the literal string "Term 1" / "Term 2" / "Term 3"
 * (see upload.php's $termLabel = "Term $termNum"). The old code mapped this
 * down to a bare '1'/'2'/'3' before querying, which meant the WHERE clause
 * could never match a row that upload.php had actually written — results
 * silently came back empty. $termLabel IS the value to match on; no
 * conversion needed.
 */
$examMap    = ['opener'=>'opener','mid-term'=>'midterm','end-term'=>'endterm'];
$exam_type  = $examMap[$examLabel] ?? $examLabel;
$examDisplayMap = ['opener'=>'Opener','mid-term'=>'Mid-Term','end-term'=>'End Term'];
$examDisplay    = $examDisplayMap[$examLabel] ?? $examLabel;

/* Subjects are now grade-dependent:
     Grade 1-3: math, eng, kisw, envt
     Grade 4-6: math, eng, kisw, sst, scie, ca, agri, re (no pretec)
     Grade 7-9: all 9 (unchanged) */
$subjectMapForGrade = $gradeInt ? getSubjectsForGrade($gradeInt) : [];
$subjects      = array_keys($subjectMapForGrade);
$subjectLabels = array_values($subjectMapForGrade);
$subjectCount  = count($subjects); // 4, 8, or 9 depending on grade band
$totalOutOf    = $subjectCount * 100;

if ($grade && $termLabel && $exam_type && $year && $subjectCount > 0) {

    /* ════ FETCH CURRENT RESULTS ════
       FIX: uses a prepared statement instead of interpolating $_GET
       values straight into the SQL string, and binds $termLabel
       (e.g. "Term 1") which is what's actually stored in exam2.term. */
    $stmt = $conn->prepare(
        "SELECT DISTINCT * FROM exam2 WHERE grade = ? AND term = ? AND exam_type = ? AND year = ?"
    );
    $stmt->bind_param('ssss', $grade, $termLabel, $exam_type, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0) {

        $subjectTotals = array_fill_keys($subjects, 0);
        $subjectBands  = array_fill_keys($subjects, ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0]);
        $classBands    = ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0];

        /* 8-level parallel breakdown (used only for Grades 7-9) */
        $subjectBands8 = array_fill_keys($subjects, array_fill_keys(allBandCodes(), 0));
        $classBands8   = array_fill_keys(allBandCodes(), 0);

        $totalSum      = 0;
        $students      = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $subs = [];
            foreach ($subjects as $s) $subs[$s] = (int)($row[$s] ?? 0);
            $total = array_sum($subs); // out of $totalOutOf
            $row['total']    = $total;
            $row['subjects'] = $subs;
            $students[]      = $row;

            foreach ($subs as $s => $score) {
                $subjectTotals[$s] += $score;
                $b = bandInfo($score);
                $subjectBands[$s][$b['tier']]++;
                $subjectBands8[$s][$b['code']]++;
            }
            /* Band classification based on per-subject average */
            $avgPerSubject = $total / $subjectCount;
            $avgBand = bandInfo($avgPerSubject);
            $classBands[$avgBand['tier']]++;
            $classBands8[$avgBand['code']]++;
            $totalSum += $total;
        }
        $stmt->close();

        $studentCount = count($students);
        /* Class mean out of $totalOutOf */
        $classMeanOfTotal = round($totalSum / $studentCount, 1);
        /* Class mean per subject (out of 100) */
        $classMeanPerSubject = round($classMeanOfTotal / $subjectCount, 1);

        /* Sort by total descending */
        usort($students, fn($a,$b) => $b['total'] <=> $a['total']);
        $rank = 1; $prevTotal = null; $repeatCount = 0;
        foreach ($students as $i => $student) {
            if ($student['total'] === $prevTotal) {
                $students[$i]['rank'] = $rank;
                $repeatCount++;
            } else {
                $rank += $repeatCount;
                $students[$i]['rank'] = $rank;
                $prevTotal = $student['total'];
                $repeatCount = 1;
            }
        }

        /* Subject means (out of 100 per subject) */
        $subjectMeans = [];
        foreach ($subjects as $s) $subjectMeans[$s] = round($subjectTotals[$s] / $studentCount, 1);

        /* ════ FETCH PREVIOUS RESULTS ════ */
        $prevMeans = [];
        $prevLabel = '';
        $hasPrev   = false;

        /* Extra previous-exam stats (populated whenever $hasPrev becomes true)
           used to power the "Exam Comparison" analysis section/sheet. */
        $prevSubjectBands    = array_fill_keys($subjects, ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0]);
        $prevClassBands      = ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0];
        $prevStudentCount    = 0;
        $prevClassMeanOfTotal = 0;

        $prevExamMap  = ['opener'=>null, 'midterm'=>'opener', 'endterm'=>'midterm'];
        $prevExamType = $prevExamMap[$exam_type] ?? null;

        if ($prevExamType) {
            $prevLabel = ucfirst($prevExamType);

            $prevStmt = $conn->prepare(
                "SELECT * FROM exam2 WHERE grade = ? AND term = ? AND exam_type = ? AND year = ?"
            );
            $prevStmt->bind_param('ssss', $grade, $termLabel, $prevExamType, $year);
            $prevStmt->execute();
            $prevResult = $prevStmt->get_result();

            if ($prevResult && mysqli_num_rows($prevResult) > 0) {
                [$prevTotals, $prevSubjectBands, $prevClassBands, $prevCount] = computePreviousStats($prevResult, $subjects);
                if ($prevCount > 0) {
                    foreach ($subjects as $s) $prevMeans[$s] = round($prevTotals[$s] / $prevCount, 1);
                    $prevStudentCount     = $prevCount;
                    $prevClassMeanOfTotal = round(array_sum($prevTotals) / $prevCount, 1);
                    $hasPrev = true;
                }
            }
            $prevStmt->close();
        }

        /* Fallback: previous year same exam */
        if (!$hasPrev && (int)$year > 2026) {
            $prevYear    = (int)$year - 1;
            $prevYearStr = (string)$prevYear;
            $prevLabel   = "Year $prevYear ($examDisplay)";

            $prevStmt = $conn->prepare(
                "SELECT * FROM exam2 WHERE grade = ? AND term = ? AND exam_type = ? AND year = ?"
            );
            $prevStmt->bind_param('ssss', $grade, $termLabel, $exam_type, $prevYearStr);
            $prevStmt->execute();
            $prevResult = $prevStmt->get_result();

            if ($prevResult && mysqli_num_rows($prevResult) > 0) {
                [$prevTotals, $prevSubjectBands, $prevClassBands, $prevCount] = computePreviousStats($prevResult, $subjects);
                if ($prevCount > 0) {
                    foreach ($subjects as $s) $prevMeans[$s] = round($prevTotals[$s] / $prevCount, 1);
                    $prevStudentCount     = $prevCount;
                    $prevClassMeanOfTotal = round(array_sum($prevTotals) / $prevCount, 1);
                    $hasPrev = true;
                }
            }
            $prevStmt->close();
        }

        /* ── JSON for JS charts ── */
        $jsSubjectLabels = json_encode($subjectLabels);
        $jsSubjectMeans  = json_encode(array_values($subjectMeans));
        $jsPrevMeans     = $hasPrev ? json_encode(array_values($prevMeans)) : 'null';
        $jsBandsEE       = json_encode(array_map(fn($s) => $subjectBands[$s]['ee'], $subjects));
        $jsBandsME       = json_encode(array_map(fn($s) => $subjectBands[$s]['me'], $subjects));
        $jsBandsAE       = json_encode(array_map(fn($s) => $subjectBands[$s]['ae'], $subjects));
        $jsBandsBE       = json_encode(array_map(fn($s) => $subjectBands[$s]['be'], $subjects));
        $jsClassBands    = json_encode([$classBands['ee'],$classBands['me'],$classBands['ae'],$classBands['be']]);
        $jsStudentCount  = $studentCount;

        $eeCount = $classBands['ee'];
        $meCount = $classBands['me'];
        $aeCount = $classBands['ae'];
        $beCount = $classBands['be'];
        $eeP = $studentCount ? round($eeCount/$studentCount*100) : 0;
        $meP = $studentCount ? round($meCount/$studentCount*100) : 0;
        $aeP = $studentCount ? round($aeCount/$studentCount*100) : 0;
        $beP = $studentCount ? round($beCount/$studentCount*100) : 0;

        /* ════ OUTPUT ════ */
        echo "
        <div class='section-divider'>
            <h2>Grade $grade &mdash; $examDisplay ($termLabel, $year)</h2>
            <span class='section-badge'>$studentCount Students</span>
        </div>

        <!-- KPI STRIP -->
        <div class='kpi-strip'>
            <div class='kpi-card kpi-total' style='animation-delay:0.04s'>
                <div class='kpi-label'>Total Students</div>
                <div class='kpi-val'>$studentCount</div>
                <div class='kpi-sub'>in this grade &amp; exam</div>
            </div>
            <div class='kpi-card kpi-ee' style='animation-delay:0.08s'>
                <div class='kpi-label'>Exceeding Expectations</div>
                <div class='kpi-val'>$eeCount</div>
                <div class='kpi-sub'>$eeP% of class &mdash; Avg &ge;75/subject</div>
            </div>
            <div class='kpi-card kpi-me' style='animation-delay:0.12s'>
                <div class='kpi-label'>Class Mean Score</div>
                <div class='kpi-val'>$classMeanOfTotal</div>
                <div class='kpi-sub'>out of $totalOutOf &mdash; $classMeanPerSubject avg per subject</div>
            </div>
            <div class='kpi-card kpi-below' style='animation-delay:0.16s'>
                <div class='kpi-label'>Below Expectations</div>
                <div class='kpi-val'>$beCount</div>
                <div class='kpi-sub'>$beP% of class &mdash; Avg &lt;26/subject</div>
            </div>
        </div>
        ";

        /* ── PERFORMANCE BANDS ── */
        echo "
        <div class='section-divider'><h2>Performance Analysis</h2></div>
        <div class='bands-grid'>
            <div class='band-card'>
                <h3>Student Achievement Bands (Overall)</h3>
                <div class='band-row'>
                    <div class='band-label' style='color:var(--ee)'>&#9679; Exceeding (E.E)</div>
                    <div class='band-bar-wrap'><div class='band-bar band-ee' style='width:{$eeP}%'></div></div>
                    <div class='band-count'>$eeCount ($eeP%)</div>
                </div>
                <div class='band-row'>
                    <div class='band-label' style='color:var(--me)'>&#9679; Meeting (M.E)</div>
                    <div class='band-bar-wrap'><div class='band-bar band-me' style='width:{$meP}%'></div></div>
                    <div class='band-count'>$meCount ($meP%)</div>
                </div>
                <div class='band-row'>
                    <div class='band-label' style='color:#d97706'>&#9679; Approaching (A.E)</div>
                    <div class='band-bar-wrap'><div class='band-bar band-ae' style='width:{$aeP}%'></div></div>
                    <div class='band-count'>$aeCount ($aeP%)</div>
                </div>
                <div class='band-row'>
                    <div class='band-label' style='color:var(--be)'>&#9679; Below (B.E)</div>
                    <div class='band-bar-wrap'><div class='band-bar band-be' style='width:{$beP}%'></div></div>
                    <div class='band-count'>$beCount ($beP%)</div>
                </div>
            </div>
            <div class='band-card'>
                <h3>Subject Means vs Benchmark (out of 100)</h3>
        ";

        $barColors = ['ee'=>'var(--ee)','me'=>'var(--me)','ae'=>'#d97706','be'=>'var(--be)'];
        foreach ($subjects as $idx => $s) {
            $mean = $subjectMeans[$s];
            $label = $subjectLabels[$idx];
            $pct  = min(100, round($mean));
            $cls  = bandLabel($mean);
            $col  = $barColors[$cls];
            echo "
                <div class='band-row'>
                    <div class='band-label'>$label</div>
                    <div class='band-bar-wrap'><div class='band-bar' style='background:$col;width:{$pct}%'></div></div>
                    <div class='band-count'>$mean</div>
                </div>
            ";
        }
        echo "</div></div>";

        /* ── PERFORMANCE BREAKDOWN TABLE (grade-aware: 4-level for G1-6, 8-level for G7-9) ── */
        $breakdownTitle = ($gradeInt >= 1 && $gradeInt <= 6) ? 'Performance Breakdown' : 'Performance Breakdown (8-Level)';
        echo "
        <div class='means-card'>
            <h3>$breakdownTitle</h3>
            <table class='means-table'>
                <thead><tr><th>Level</th><th>Meaning</th><th>Students (Class Avg)</th><th>% of Class</th></tr></thead>
                <tbody>
        ";
        if ($gradeInt >= 1 && $gradeInt <= 6) {
            $simpleTierMap = ['E.E'=>'ee','M.E'=>'me','A.E'=>'ae','B.E'=>'be'];
            foreach (allBandCodesForGrade($gradeInt) as $code) {
                $tier  = $simpleTierMap[$code];
                $count = $classBands[$tier];
                $pct   = $studentCount ? round($count / $studentCount * 100) : 0;
                $label = bandInfoForGrade(representativeScoreForGrade($code, $gradeInt), $gradeInt)['label'];
                echo "<tr><td><strong>$code</strong></td><td>$label</td><td>$count</td><td>$pct%</td></tr>";
            }
        } else {
            foreach (allBandCodes() as $code) {
                $count = $classBands8[$code];
                $pct   = $studentCount ? round($count / $studentCount * 100) : 0;
                $label = bandInfo(representativeScoreFor($code))['label'];
                echo "<tr><td><strong>$code</strong></td><td>$label</td><td>$count</td><td>$pct%</td></tr>";
            }
        }
        echo "</tbody></table></div>";

        /* ── CHARTS ── */
        echo "
        <div class='charts-grid'>
            <div class='chart-card'>
                <h3>Subject Mean Scores (out of 100)</h3>
                <div class='chart-wrap'><canvas id='subjectMeansChart'></canvas></div>
            </div>
            <div class='chart-card'>
                <h3>Class Achievement Distribution</h3>
                <div class='chart-wrap'><canvas id='classBandsChart'></canvas></div>
            </div>
            <div class='chart-card full-width'>
                <h3>Per-Subject Band Breakdown (All Students)</h3>
                <div class='chart-wrap tall'><canvas id='subjectBandsChart'></canvas></div>
            </div>
        </div>
        ";

        /* ── PREVIOUS COMPARISON ── */
        echo "<div class='section-divider'><h2>Comparison with Previous Exam</h2></div>";
        if ($hasPrev) {
            echo "
            <div class='compare-card'>
                <h3>Current ($examDisplay) vs Previous ($prevLabel) — Subject Means</h3>
                <div class='compare-meta'>
                    <div class='compare-pill'>
                        <div class='compare-pill-dot' style='background:var(--blue)'></div>
                        Current: $examDisplay $termLabel $year
                    </div>
                    <div class='compare-pill'>
                        <div class='compare-pill-dot' style='background:rgba(15,25,35,0.3)'></div>
                        Previous: $prevLabel
                    </div>
                </div>
                <div class='chart-wrap tall'><canvas id='compareChart'></canvas></div>
            </div>
            ";

            echo "
            <div class='means-card'>
                <h3>Subject-by-Subject Comparison Table</h3>
                <table class='means-table'>
                    <thead><tr>
                        <th>Subject</th><th>Current Mean</th><th>Previous Mean</th><th>Change</th>
                        <th>E.E</th><th>M.E</th><th>A.E</th><th>B.E</th>
                    </tr></thead>
                    <tbody>
            ";
            foreach ($subjects as $idx => $s) {
                $cur  = $subjectMeans[$s];
                $prev = $prevMeans[$s] ?? null;
                $diff = $prev !== null ? round($cur - $prev, 1) : null;
                $trendClass = $diff === null ? 'trend-same' : ($diff > 0 ? 'trend-up' : ($diff < 0 ? 'trend-down' : 'trend-same'));
                $trendStr   = $diff === null ? '—' : ($diff > 0 ? "▲ +$diff" : ($diff < 0 ? "▼ $diff" : "→ 0"));
                $prevStr    = $prev !== null ? $prev : '—';
                $eeC = $subjectBands[$s]['ee'];
                $meC = $subjectBands[$s]['me'];
                $aeC = $subjectBands[$s]['ae'];
                $beC = $subjectBands[$s]['be'];
                echo "<tr>
                    <td><strong>{$subjectLabels[$idx]}</strong></td>
                    <td class='mean-val'>$cur ".awardPill($cur, $gradeInt)."</td>
                    <td>$prevStr</td>
                    <td class='$trendClass'>$trendStr</td>
                    <td>$eeC</td><td>$meC</td><td>$aeC</td><td>$beC</td>
                </tr>";
            }
            echo "</tbody></table></div>";
        } else {
            echo "<div class='compare-card'><div class='no-prev'>📊 No previous exam data found to compare against.<br>
            <small>Previous comparison shows when prior exam data exists for the same grade and term.</small></div></div>";
        }

        /* ── RESULTS TABLE ── */
        echo "
        <div class='section-divider'>
            <h2>Full Results Table</h2>
            <div class='export-btns'>
                <button type='button' id='exportExcelBtn' class='btn-export'>
                    <svg width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4'/><polyline points='7 10 12 15 17 10'/><line x1='12' y1='15' x2='12' y2='3'/></svg>
                    Export to Excel
                </button>
                <button type='button' id='downloadReportCardsBtn' class='btn-export btn-export-gold'>
                    <svg width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z'/><polyline points='14 2 14 8 20 8'/><line x1='16' y1='13' x2='8' y2='13'/><line x1='16' y1='17' x2='8' y2='17'/></svg>
                    Download Report Cards (PDF)
                </button>
            </div>
        </div>
        <p class='scroll-hint'>← Scroll sideways to see all columns →</p>
        <div class='table-scroll'>
        <table class='results-table'>
            <thead><tr>
                <th>Rank</th><th>Assess. No</th><th>First Name</th><th>Surname</th>
        ";
        foreach ($subjectLabels as $label) echo "<th>$label</th>";
        echo "
                <th>Total (/$totalOutOf)</th>
            </tr></thead>
            <tbody>
        ";

        foreach ($students as $student) {
            $r  = $student['rank'];
            $bc = $r===1 ? 'gold' : ($r===2 ? 'silver' : ($r===3 ? 'bronze' : ''));
            echo "<tr>
                <td><span class='rank-badge $bc'>$r</span></td>
                <td>{$student['Assesment']}</td>
                <td><strong>{$student['firstName']}</strong></td>
                <td>{$student['lastName']}</td>";
            foreach ($student['subjects'] as $sub => $score) echo "<td>{$score} ".awardPill($score, $gradeInt)."</td>";
            echo "<td>{$student['total']} ".awardPill($student['total'] / $subjectCount, $gradeInt)."</td></tr>";
        }

        /* Mean row — per subject means + total mean */
        echo "<tr class='mean-row'><td colspan='4'>Class Mean</td>";
        foreach ($subjects as $s) {
            $sm = round($subjectTotals[$s] / $studentCount, 1);
            echo "<td>$sm ".awardPill($sm, $gradeInt)."</td>";
        }
        echo "<td>$classMeanOfTotal ".awardPill($classMeanPerSubject, $gradeInt)."</td>";
        echo "</tr></tbody></table></div>";

        /* ── Build a plain PHP array for the export ── */
        $exportStudents = [];
        foreach ($students as $student) {
            $rowOut = [
                'rank'      => $student['rank'],
                'assesment' => $student['Assesment'],
                'firstName' => $student['firstName'],
                'lastName'  => $student['lastName'],
                'subjects'  => array_values($student['subjects']),
                'total'     => $student['total'],
            ];
            $exportStudents[] = $rowOut;
        }
        $jsExportStudents = json_encode($exportStudents);
        $jsExportMeta = json_encode([
            'school'        => 'Stephen Kanja Primary & Junior School',
            'grade'         => $grade,
            'term'          => $termLabel,
            'exam'          => $examDisplay,
            'year'          => $year,
            'studentCount'  => $studentCount,
            'subjectCount'  => $subjectCount,
            'totalOutOf'    => $totalOutOf,
            'classMeanTotal'=> $classMeanOfTotal,
            'classMeanSubj' => $classMeanPerSubject,
            'eeCount' => $eeCount, 'eeP' => $eeP,
            'meCount' => $meCount, 'meP' => $meP,
            'aeCount' => $aeCount, 'aeP' => $aeP,
            'beCount' => $beCount, 'beP' => $beP,
            'gradeInt' => $gradeInt,
            'generatedAt' => date('jS F Y, g:i A'),
            'hasPrev' => $hasPrev,
            'prevLabel' => $prevLabel,
            'prevStudentCount' => $prevStudentCount,
            'prevClassMeanTotal' => $prevClassMeanOfTotal,
        ]);
        $jsSubjectTotalsCounts = json_encode(array_map(fn($s) => $subjectBands[$s], $subjects));
        $jsPrevMeansForExport  = $hasPrev ? json_encode(array_values($prevMeans)) : 'null';
        $jsPrevLabel = json_encode($prevLabel);
        $jsPrevSubjectBandCounts = json_encode(array_map(fn($s) => $prevSubjectBands[$s], $subjects));
        $jsPrevClassBands = json_encode([$prevClassBands['ee'],$prevClassBands['me'],$prevClassBands['ae'],$prevClassBands['be']]);

        /* ── CHART.JS INLINE (loaded at bottom so canvases exist in DOM) ── */
        echo "
        <script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js'></script>
        <script>
        (function() {
            const subjectLabels = $jsSubjectLabels;
            const subjectMeans  = $jsSubjectMeans;
            const prevMeans     = $jsPrevMeans;
            const bandsEE       = $jsBandsEE;
            const bandsME       = $jsBandsME;
            const bandsAE       = $jsBandsAE;
            const bandsBE       = $jsBandsBE;
            const classBands    = $jsClassBands; // [ee, me, ae, be]
            const n             = $jsStudentCount;

            const shortLabels = subjectLabels.map(l => l.length > 8 ? l.slice(0,4) + '.' : l);

            Chart.defaults.font.family = \"'DM Sans', sans-serif\";
            Chart.defaults.color       = '#7a909e';

            /* ── Subject Means Bar ── */
            const ctx1 = document.getElementById('subjectMeansChart');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: shortLabels,
                        datasets: [{
                            label: 'Mean Score',
                            data: subjectMeans,
                            backgroundColor: subjectMeans.map(v =>
                                v >= 75 ? 'rgba(22,163,74,0.75)' :
                                v >= 50 ? 'rgba(26,74,122,0.75)' :
                                v >= 26 ? 'rgba(217,119,6,0.75)' :
                                          'rgba(153,27,27,0.75)'
                            ),
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: ctx => {
                                        const v = ctx.raw;
                                        return v >= 75 ? 'Exceeding Expectations' :
                                               v >= 50 ? 'Meeting Expectations' :
                                               v >= 26 ? 'Approaching Expectations' : 'Below Expectations';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { min: 0, max: 100, grid: { color: 'rgba(0,0,0,0.05)' },
                                 ticks: { callback: v => v } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            /* ── Class Bands Doughnut ── */
            const ctx2 = document.getElementById('classBandsChart');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: ['Exceeding (E.E)','Meeting (M.E)','Approaching (A.E)','Below (B.E)'],
                        datasets: [{
                            data: classBands,
                            backgroundColor: [
                                'rgba(22,163,74,0.8)',
                                'rgba(26,74,122,0.8)',
                                'rgba(217,119,6,0.8)',
                                'rgba(153,27,27,0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 16, boxWidth: 12, borderRadius: 4 } },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const v = ctx.raw;
                                        const pct = n ? Math.round(v/n*100) : 0;
                                        return ' ' + v + ' students (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            /* ── Per-Subject Stacked Band Chart ── */
            const ctx3 = document.getElementById('subjectBandsChart');
            if (ctx3) {
                new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: shortLabels,
                        datasets: [
                            { label:'Exceeding (E.E)', data: bandsEE, backgroundColor:'rgba(22,163,74,0.8)',  borderRadius: 0 },
                            { label:'Meeting (M.E)',   data: bandsME, backgroundColor:'rgba(26,74,122,0.8)',  borderRadius: 0 },
                            { label:'Approaching (A.E)',data: bandsAE,backgroundColor:'rgba(217,119,6,0.8)', borderRadius: 0 },
                            { label:'Below (B.E)',     data: bandsBE, backgroundColor:'rgba(153,27,27,0.8)', borderRadius: {topLeft:4,topRight:4} }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position:'bottom', labels:{ padding:16, boxWidth:12, borderRadius:4 } },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const v = ctx.raw;
                                        const pct = n ? Math.round(v/n*100) : 0;
                                        return ' ' + ctx.dataset.label + ': ' + v + ' (' + pct + '%)';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { stacked: true, grid: { display: false } },
                            y: { stacked: true, grid: { color: 'rgba(0,0,0,0.05)' },
                                 ticks: { stepSize: 1 },
                                 title: { display: true, text: 'No. of Students' } }
                        }
                    }
                });
            }

            /* ── Comparison Line Chart ── */
            const ctx4 = document.getElementById('compareChart');
            if (ctx4 && prevMeans !== null) {
                new Chart(ctx4, {
                    type: 'line',
                    data: {
                        labels: shortLabels,
                        datasets: [
                            {
                                label: 'Current Exam',
                                data: subjectMeans,
                                borderColor: 'rgba(26,74,122,1)',
                                backgroundColor: 'rgba(26,74,122,0.1)',
                                borderWidth: 3,
                                pointRadius: 5, pointHoverRadius: 8,
                                tension: 0.35, fill: true
                            },
                            {
                                label: 'Previous Exam',
                                data: prevMeans,
                                borderColor: 'rgba(15,25,35,0.4)',
                                backgroundColor: 'rgba(15,25,35,0.05)',
                                borderWidth: 2,
                                borderDash: [6,4],
                                pointRadius: 4, pointHoverRadius: 7,
                                tension: 0.35, fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position:'bottom', labels:{ padding:16, boxWidth:14, borderRadius:4 } },
                            tooltip: {
                                callbacks: {
                                    afterBody: items => {
                                        if (items.length === 2) {
                                            const diff = parseFloat((items[0].raw - items[1].raw).toFixed(1));
                                            if (diff > 0) return ['▲ Improved by ' + diff];
                                            if (diff < 0) return ['▼ Dropped by ' + Math.abs(diff)];
                                            return ['→ No change'];
                                        }
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { min: 0, max: 100, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            /* ══════════════════════════════════════════════
               EXCEL EXPORT — builds a styled, multi-sheet
               workbook (Summary / Subject Analysis / Full
               Results / Exam Comparison) that mirrors the
               on-page design.
               ══════════════════════════════════════════════ */
            const exportMeta       = $jsExportMeta;
            const exportStudents   = $jsExportStudents;
            const subjectFullNames = $jsSubjectLabels;
            const subjectBandCounts= $jsSubjectTotalsCounts; // [{ee,me,ae,be}, ...] per subject
            const exportPrevMeans  = $jsPrevMeansForExport;
            const exportPrevLabel  = $jsPrevLabel;
            const prevSubjectBandCounts = $jsPrevSubjectBandCounts; // [{ee,me,ae,be}, ...] per subject (previous exam)
            const prevClassBandCounts   = $jsPrevClassBands;        // [ee, me, ae, be] (previous exam, whole class)
            const isLowerGrade     = exportMeta.gradeInt >= 1 && exportMeta.gradeInt <= 6;

            const INK   = 'FF0F1923';
            const GOLD  = 'FFC8992A';
            const WHITE = 'FFFFFFFF';
            const ROW_ALT = 'FFF5F2EE';

            /* 8-level band lookup — keep in sync with bandInfo() in subjects_config.php */
            function bandFor8(score) {
                if (score >= 90) return { code:'EE2', bg:'FFBBF7D0', fg:'FF15803D' };
                if (score >= 75) return { code:'EE1', bg:'FFDCFCE7', fg:'FF16A34A' };
                if (score >= 63) return { code:'ME2', bg:'FFCFE0F3', fg:'FF123657' };
                if (score >= 50) return { code:'ME1', bg:'FFDCE8F5', fg:'FF1A4A7A' };
                if (score >= 38) return { code:'AE2', bg:'FFFDE68A', fg:'FF92400E' };
                if (score >= 26) return { code:'AE1', bg:'FFFEF3C7', fg:'FFB45309' };
                if (score >= 13) return { code:'BE2', bg:'FFFECACA', fg:'FF7F1D1D' };
                return                 { code:'BE1', bg:'FFFEE2E2', fg:'FF991B1B' };
            }
            /* simple 4-level band lookup — keep in sync with bandInfoSimple() in subjects_config.php */
            function bandFor4(score) {
                if (score >= 75) return { code:'E.E', bg:'FFDCFCE7', fg:'FF16A34A' };
                if (score >= 50) return { code:'M.E', bg:'FFDCE8F5', fg:'FF1A4A7A' };
                if (score >= 26) return { code:'A.E', bg:'FFFEF3C7', fg:'FFB45309' };
                return                 { code:'B.E', bg:'FFFEE2E2', fg:'FF991B1B' };
            }
            /* grade-aware dispatcher, mirrors bandInfoForGrade() in PHP */
            function bandFor(score) {
                return isLowerGrade ? bandFor4(score) : bandFor8(score);
            }

            function styleHeaderRow(row) {
                row.eachCell(cell => {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: INK } };
                    cell.font = { color: { argb: WHITE }, bold: true, size: 11, name: 'Calibri' };
                    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
                    cell.border = { bottom: { style: 'thin', color: { argb: GOLD } } };
                });
                row.height = 24;
            }

            function titleCell(ws, ref, text, size) {
                ws.getCell(ref).value = text;
                ws.getCell(ref).font = { bold: true, size: size || 14, color: { argb: INK }, name: 'Calibri' };
            }

            /* Decorative letterhead block, reused at the top of every sheet.
               Returns the row number the table/content should start on. */
            function drawLetterhead(ws, lastColLetter, subtitle) {
                ws.mergeCells('A1:' + lastColLetter + '1');
                ws.getCell('A1').value = exportMeta.school;
                ws.getCell('A1').font = { bold: true, size: 16, color: { argb: WHITE }, name: 'Calibri' };
                ws.getCell('A1').alignment = { vertical: 'middle', horizontal: 'left', indent: 1 };
                ws.getCell('A1').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: INK } };
                ws.getRow(1).height = 30;

                ws.mergeCells('A2:' + lastColLetter + '2');
                ws.getCell('A2').value = subtitle;
                ws.getCell('A2').font = { italic: true, size: 10.5, color: { argb: 'FFF5E9C8' }, name: 'Calibri' };
                ws.getCell('A2').alignment = { vertical: 'middle', horizontal: 'left', indent: 1 };
                ws.getCell('A2').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: INK } };
                ws.getRow(2).height = 20;

                ws.mergeCells('A3:' + lastColLetter + '3');
                ws.getCell('A3').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: GOLD } };
                ws.getRow(3).height = 4;

                ws.mergeCells('A4:' + lastColLetter + '4');
                const metaLine = 'Grade ' + exportMeta.grade + '   |   ' + exportMeta.exam + ', ' + exportMeta.term + ' ' + exportMeta.year +
                                  '   |   ' + exportMeta.studentCount + ' Students   |   Generated ' + exportMeta.generatedAt;
                ws.getCell('A4').value = metaLine;
                ws.getCell('A4').font = { size: 9.5, italic: true, color: { argb: 'FF7A909E' }, name: 'Calibri' };
                ws.getCell('A4').alignment = { vertical: 'middle', horizontal: 'left', indent: 1 };
                ws.getRow(4).height = 18;

                return 6; // next usable row
            }

            async function buildWorkbook() {
                const wb = new ExcelJS.Workbook();
                wb.creator = 'Stephen Kanja Primary & Junior School';
                wb.created = new Date();

                /* ═══ SHEET 1 — SUMMARY ═══ */
                const ws1 = wb.addWorksheet('Summary', { views: [{ showGridLines: false }] });
                ws1.columns = [{ width: 30 }, { width: 20 }, { width: 18 }, { width: 18 }, { width: 18 }];
                let r = drawLetterhead(ws1, 'E', 'Academic Performance Transcript — Summary');

                ws1.getCell('A' + r).value = 'Report Details';
                ws1.getCell('A' + r).font = { bold: true, size: 12, color: { argb: INK } };
                r++;
                const metaRows = [
                    ['School', exportMeta.school],
                    ['Grade', 'Grade ' + exportMeta.grade],
                    ['Term', exportMeta.term],
                    ['Exam', exportMeta.exam],
                    ['Year', exportMeta.year],
                    ['Total Students', exportMeta.studentCount],
                    ['Subjects Examined', exportMeta.subjectCount],
                    ['Class Mean (out of ' + exportMeta.totalOutOf + ')', exportMeta.classMeanTotal],
                    ['Class Mean per Subject (out of 100)', exportMeta.classMeanSubj],
                    ['Report Generated', exportMeta.generatedAt],
                ];
                metaRows.forEach(([label, val], i) => {
                    const row = ws1.getRow(r);
                    row.getCell(1).value = label;
                    row.getCell(1).font = { bold: true, color: { argb: 'FF3D4F5C' } };
                    row.getCell(2).value = val;
                    row.getCell(2).font = { color: { argb: INK } };
                    if (i % 2 === 1) {
                        [1, 2].forEach(c => row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROW_ALT } });
                    }
                    r++;
                });

                r += 1;
                const bandsHeading = isLowerGrade ? 'Achievement Band Distribution (E.E / M.E / A.E / B.E)' : 'Achievement Band Distribution';
                ws1.getCell('A' + r).value = bandsHeading;
                ws1.getCell('A' + r).font = { bold: true, size: 12, color: { argb: INK } };
                r++;
                const bandHeaderRow = ws1.getRow(r);
                ['Band', 'Students', '% of Class'].forEach((h, i) => bandHeaderRow.getCell(i + 1).value = h);
                styleHeaderRow(bandHeaderRow);
                r++;
                const bandData = [
                    ['Exceeding Expectations (E.E)', exportMeta.eeCount, exportMeta.eeP, 'FFDCFCE7', 'FF16A34A'],
                    ['Meeting Expectations (M.E)', exportMeta.meCount, exportMeta.meP, 'FFDCE8F5', 'FF1A4A7A'],
                    ['Approaching Expectations (A.E)', exportMeta.aeCount, exportMeta.aeP, 'FFFEF3C7', 'FF92400E'],
                    ['Below Expectations (B.E)', exportMeta.beCount, exportMeta.beP, 'FFFEE2E2', 'FF991B1B'],
                ];
                bandData.forEach(([label, count, pct, bg, fg]) => {
                    const row = ws1.getRow(r);
                    row.getCell(1).value = label;
                    row.getCell(2).value = count;
                    row.getCell(3).value = pct / 100;
                    row.getCell(3).numFmt = '0%';
                    [1, 2, 3].forEach(c => {
                        row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
                        row.getCell(c).font = { color: { argb: fg }, bold: c === 1 };
                        row.getCell(c).alignment = { vertical: 'middle', horizontal: c === 1 ? 'left' : 'center' };
                    });
                    r++;
                });

                r += 2;
                ws1.mergeCells('A' + r + ':E' + r);
                ws1.getCell('A' + r).value = '“Every learner is a promise waiting to be kept.”';
                ws1.getCell('A' + r).font = { italic: true, size: 9.5, color: { argb: 'FF7A909E' } };
                ws1.getCell('A' + r).alignment = { horizontal: 'center' };

                /* ═══ SHEET 2 — SUBJECT ANALYSIS ═══ */
                const ws2 = wb.addWorksheet('Subject Analysis', { views: [{ showGridLines: false }] });
                const hasComparison = exportPrevMeans !== null;
                const headers2 = ['Subject', 'Class Mean (/100)', 'Level'];
                if (hasComparison) headers2.push('Previous Mean', 'Change');
                headers2.push('E.E Count', 'M.E Count', 'A.E Count', 'B.E Count');
                ws2.columns = headers2.map((h, i) => ({ width: i === 0 ? 22 : 16 }));
                const lastCol2 = String.fromCharCode(65 + headers2.length - 1);
                let r2 = drawLetterhead(ws2, lastCol2, 'Subject Analysis — ' + exportMeta.exam + ' (' + exportMeta.term + ' ' + exportMeta.year + ')');
                const hRow2 = ws2.getRow(r2);
                headers2.forEach((h, i) => hRow2.getCell(i + 1).value = h);
                styleHeaderRow(hRow2);
                r2++;

                subjectFullNames.forEach((name, i) => {
                    const mean = subjectMeans[i];
                    const b = bandFor(mean);
                    const counts = subjectBandCounts[i];
                    const rowVals = [name, mean, b.code];
                    if (hasComparison) {
                        const prev = exportPrevMeans[i];
                        const diff = Math.round((mean - prev) * 10) / 10;
                        rowVals.push(prev, diff);
                    }
                    rowVals.push(counts.ee, counts.me, counts.ae, counts.be);
                    const row = ws2.getRow(r2);
                    rowVals.forEach((v, ci) => row.getCell(ci + 1).value = v);
                    row.getCell(1).font = { bold: true, color: { argb: INK } };
                    row.getCell(2).font = { bold: true };
                    row.getCell(3).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: b.bg } };
                    row.getCell(3).font = { color: { argb: b.fg }, bold: true };
                    row.getCell(3).alignment = { horizontal: 'center' };
                    if (hasComparison) {
                        const diffCell = row.getCell(5);
                        const diffVal = rowVals[4];
                        diffCell.font = { color: { argb: diffVal > 0 ? 'FF16A34A' : diffVal < 0 ? 'FF991B1B' : 'FF7A909E' }, bold: true };
                    }
                    row.eachCell(c => c.alignment = { ...(c.alignment||{}), vertical: 'middle' });
                    if (i % 2 === 1) {
                        row.eachCell({ includeEmpty: true }, cell => {
                            if (!cell.fill) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROW_ALT } };
                        });
                    }
                    r2++;
                });
                ws2.autoFilter = { from: 'A' + (r2 - subjectFullNames.length - 1), to: lastCol2 + (r2 - subjectFullNames.length - 1) };

                /* ═══ SHEET 3 — FULL RESULTS (MARKLIST) ═══ */
                const headers3 = ['Rank', 'Assess. No', 'First Name', 'Surname'].concat(subjectFullNames).concat(['Total (/' + exportMeta.totalOutOf + ')', 'Overall Level']);
                const lastCol3Idx = headers3.length;
                const lastCol3 = lastCol3Idx <= 26 ? String.fromCharCode(64 + lastCol3Idx) : 'AA';
                const ws3 = wb.addWorksheet('Full Results (Marklist)', { views: [{ state: 'frozen', ySplit: 7, showGridLines: false }] });
                ws3.columns = headers3.map((h, i) => ({
                    width: i === 0 ? 10 : i === 1 ? 16 : (i === 2 || i === 3) ? 20 : 16
                }));

                /* Print setup: landscape, scaled to fill the page (≈95%+ of the
                   printable width) instead of shrinking to fit a portrait sheet,
                   with the letterhead + header row repeating on every printed page. */
                ws3.pageSetup = {
                    orientation: 'landscape',
                    fitToPage: true,
                    fitToWidth: 1,
                    fitToHeight: 0,
                    paperSize: 9, // A4
                    horizontalCentered: true,
                    margins: { left: 0.2, right: 0.2, top: 0.3, bottom: 0.3, header: 0.15, footer: 0.15 },
                };

                let r3 = drawLetterhead(ws3, lastCol3, 'Class Marklist — ' + exportMeta.exam + ', ' + exportMeta.term + ' ' + exportMeta.year);
                ws3.pageSetup.printTitlesRow = '1:' + r3;

                const hRow3 = ws3.getRow(r3);
                headers3.forEach((h, i) => hRow3.getCell(i + 1).value = h);
                styleHeaderRow(hRow3);
                hRow3.height = 30;
                hRow3.eachCell(cell => cell.font = { ...cell.font, size: 12 });
                r3++;

                exportStudents.forEach((s, idx) => {
                    const rowVals = [s.rank, s.assesment, s.firstName, s.lastName].concat(s.subjects).concat([s.total]);
                    const tb = bandFor(s.total / exportMeta.subjectCount);
                    rowVals.push(tb.code);
                    const row = ws3.getRow(r3);
                    row.height = 22;
                    row.eachCell({ includeEmpty: true }, cell => cell.font = { ...(cell.font||{}), size: 11 });
                    rowVals.forEach((v, ci) => row.getCell(ci + 1).value = v);

                    row.getCell(1).alignment = { horizontal: 'center' };
                    row.getCell(1).font = { size: 12, bold: true, color: { argb: s.rank === 1 ? 'FF7A5A10' : s.rank === 2 ? 'FF4A4A6A' : s.rank === 3 ? 'FF7A4A2A' : 'FF1A4A7A' } };
                    if (s.rank <= 3) {
                        row.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: s.rank === 1 ? 'FFF5E9C8' : s.rank === 2 ? 'FFE8E8F0' : 'FFF0E6D8' } };
                    }
                    row.getCell(3).font = { size: 11, bold: true };

                    /* Each subject cell shows the raw score plus its performance
                       level right next to it (e.g. 78 (M.E)), on top of the
                       existing band-colour fill, so a level is visible for
                       every subject for every learner without adding columns. */
                    s.subjects.forEach((score, sIdx) => {
                        const cell = row.getCell(5 + sIdx);
                        const b = bandFor(score);
                        cell.value = {
                            richText: [
                                { text: String(score), font: { size: 11, bold: true, color: { argb: b.fg }, name: 'Calibri' } },
                                { text: '  (' + b.code + ')', font: { size: 9, italic: true, color: { argb: b.fg }, name: 'Calibri' } },
                            ]
                        };
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: b.bg } };
                        cell.alignment = { horizontal: 'center', vertical: 'middle' };
                    });

                    const totalCell = row.getCell(5 + s.subjects.length);
                    totalCell.font = { size: 12, bold: true, color: { argb: INK } };
                    totalCell.alignment = { horizontal: 'center' };

                    const awardCell = row.getCell(6 + s.subjects.length);
                    awardCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: tb.bg } };
                    awardCell.font = { size: 11, color: { argb: tb.fg }, bold: true };
                    awardCell.alignment = { horizontal: 'center' };

                    if (idx % 2 === 1) {
                        [2, 4].forEach(c => {
                            if (!row.getCell(c).fill) row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROW_ALT } };
                        });
                    }
                    r3++;
                });

                /* Class mean row at the bottom */
                const meanRowVals = ['', '', 'Class Mean', ''].concat(
                    subjectFullNames.map((_, i) => subjectMeans[i])
                ).concat([exportMeta.classMeanTotal, '']);
                const meanRow = ws3.getRow(r3);
                meanRowVals.forEach((v, ci) => meanRow.getCell(ci + 1).value = v);
                meanRow.eachCell({ includeEmpty: true }, cell => {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: INK } };
                    cell.font = { color: { argb: WHITE }, bold: true };
                });
                meanRow.getCell(3).alignment = { horizontal: 'left' };

                ws3.autoFilter = { from: 'A' + (r3 - exportStudents.length - 1), to: lastCol3 + (r3 - exportStudents.length - 1) };

                /* Signature / footer block under the marklist */
                let fr = r3 + 2;
                ws3.getCell('A' + fr).value = 'Class Teacher: ______________________________';
                ws3.getCell('A' + fr).font = { size: 10, color: { argb: 'FF3D4F5C' } };
                fr++;
                ws3.getCell('A' + fr).value = 'Head of Institution: ______________________________';
                ws3.getCell('A' + fr).font = { size: 10, color: { argb: 'FF3D4F5C' } };
                fr += 2;
                ws3.getCell('A' + fr).value = exportMeta.school + '  •  Generated ' + exportMeta.generatedAt;
                ws3.getCell('A' + fr).font = { italic: true, size: 8.5, color: { argb: 'FF7A909E' } };

                /* ═══ SHEET 4 — EXAM COMPARISON (only when previous-exam data exists) ═══ */
                let ws4 = null;
                if (hasComparison) {
                    ws4 = wb.addWorksheet('Exam Comparison', { views: [{ showGridLines: false }] });
                    ws4.columns = [{ width: 26 }, { width: 20 }, { width: 20 }, { width: 16 }, { width: 16 }, { width: 16 }, { width: 16 }];
                    let r4 = drawLetterhead(ws4, 'G', 'Current vs Previous Exam — ' + exportMeta.exam + ' (' + exportMeta.term + ' ' + exportMeta.year + ') vs ' + exportPrevLabel);

                    /* ── Class-level overview: current vs previous ── */
                    ws4.getCell('A' + r4).value = 'Class Overview';
                    ws4.getCell('A' + r4).font = { bold: true, size: 12, color: { argb: INK } };
                    r4++;

                    const ovHeaderRow = ws4.getRow(r4);
                    ['Metric', 'Current (' + exportMeta.exam + ')', 'Previous (' + exportPrevLabel + ')', 'Change'].forEach((h, i) => ovHeaderRow.getCell(i + 1).value = h);
                    styleHeaderRow(ovHeaderRow);
                    r4++;

                    const prevN = exportMeta.prevStudentCount || 0;
                    const curN  = exportMeta.studentCount;
                    const meanDiff = Math.round((exportMeta.classMeanTotal - exportMeta.prevClassMeanTotal) * 10) / 10;
                    const bandLabels4 = ['Exceeding (E.E)', 'Meeting (M.E)', 'Approaching (A.E)', 'Below (B.E)'];
                    const curBandCounts  = [exportMeta.eeCount, exportMeta.meCount, exportMeta.aeCount, exportMeta.beCount];
                    const curBandPct     = [exportMeta.eeP, exportMeta.meP, exportMeta.aeP, exportMeta.beP];
                    const bandFillFg = [['FFDCFCE7','FF16A34A'], ['FFDCE8F5','FF1A4A7A'], ['FFFEF3C7','FF92400E'], ['FFFEE2E2','FF991B1B']];

                    const overviewRows = [
                        ['Total Students', curN, prevN, curN - prevN],
                        ['Class Mean (/' + exportMeta.totalOutOf + ')', exportMeta.classMeanTotal, exportMeta.prevClassMeanTotal, meanDiff],
                    ];
                    overviewRows.forEach(([label, cur, prev, diff], i) => {
                        const row = ws4.getRow(r4);
                        row.getCell(1).value = label; row.getCell(1).font = { bold: true, color: { argb: 'FF3D4F5C' } };
                        row.getCell(2).value = cur; row.getCell(2).font = { bold: true };
                        row.getCell(3).value = prev;
                        row.getCell(4).value = diff;
                        row.getCell(4).font = { bold: true, color: { argb: diff > 0 ? 'FF16A34A' : diff < 0 ? 'FF991B1B' : 'FF7A909E' } };
                        if (i % 2 === 1) [1,2,3,4].forEach(c => row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROW_ALT } });
                        r4++;
                    });

                    r4 += 1;
                    ws4.getCell('A' + r4).value = 'Achievement Band Shift (Current vs Previous)';
                    ws4.getCell('A' + r4).font = { bold: true, size: 12, color: { argb: INK } };
                    r4++;
                    const bandCompareHeader = ws4.getRow(r4);
                    ['Band', 'Current Students', 'Current %', 'Previous Students', 'Previous %', 'Student Change'].forEach((h, i) => bandCompareHeader.getCell(i + 1).value = h);
                    styleHeaderRow(bandCompareHeader);
                    r4++;
                    bandLabels4.forEach((label, i) => {
                        const prevCount = prevClassBandCounts[i] || 0;
                        const prevPct = prevN ? Math.round(prevCount / prevN * 100) : 0;
                        const row = ws4.getRow(r4);
                        const vals = [label, curBandCounts[i], curBandPct[i] / 100, prevCount, prevPct / 100, curBandCounts[i] - prevCount];
                        vals.forEach((v, ci) => row.getCell(ci + 1).value = v);
                        row.getCell(3).numFmt = '0%';
                        row.getCell(5).numFmt = '0%';
                        const [bg, fg] = bandFillFg[i];
                        [1,2,3,4,5].forEach(c => {
                            row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bg } };
                            row.getCell(c).font = { color: { argb: fg }, bold: c === 1 };
                            row.getCell(c).alignment = { horizontal: c === 1 ? 'left' : 'center' };
                        });
                        const changeVal = curBandCounts[i] - prevCount;
                        row.getCell(6).font = { bold: true, color: { argb: changeVal > 0 ? 'FF16A34A' : changeVal < 0 ? 'FF991B1B' : 'FF7A909E' } };
                        row.getCell(6).alignment = { horizontal: 'center' };
                        r4++;
                    });

                    /* ── Subject-by-subject comparison ── */
                    r4 += 1;
                    ws4.getCell('A' + r4).value = 'Subject-by-Subject Comparison';
                    ws4.getCell('A' + r4).font = { bold: true, size: 12, color: { argb: INK } };
                    r4++;
                    const subjHeader = ws4.getRow(r4);
                    ['Subject', 'Current Mean', 'Previous Mean', 'Change', 'Current Level', 'Previous Level'].forEach((h, i) => subjHeader.getCell(i + 1).value = h);
                    styleHeaderRow(subjHeader);
                    r4++;
                    subjectFullNames.forEach((name, i) => {
                        const cur  = subjectMeans[i];
                        const prev = exportPrevMeans[i];
                        const diff = Math.round((cur - prev) * 10) / 10;
                        const curB = bandFor(cur);
                        const prevB = bandFor(prev);
                        const row = ws4.getRow(r4);
                        [name, cur, prev, diff].forEach((v, ci) => row.getCell(ci + 1).value = v);
                        row.getCell(1).font = { bold: true, color: { argb: INK } };
                        row.getCell(4).font = { bold: true, color: { argb: diff > 0 ? 'FF16A34A' : diff < 0 ? 'FF991B1B' : 'FF7A909E' } };
                        row.getCell(5).value = curB.code;
                        row.getCell(5).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: curB.bg } };
                        row.getCell(5).font = { color: { argb: curB.fg }, bold: true };
                        row.getCell(5).alignment = { horizontal: 'center' };
                        row.getCell(6).value = prevB.code;
                        row.getCell(6).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: prevB.bg } };
                        row.getCell(6).font = { color: { argb: prevB.fg }, bold: true };
                        row.getCell(6).alignment = { horizontal: 'center' };
                        if (i % 2 === 1) {
                            row.eachCell({ includeEmpty: true }, cell => {
                                if (!cell.fill) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ROW_ALT } };
                            });
                        }
                        r4++;
                    });
                }

                /* Borders on all data cells across every sheet */
                [ws1, ws2, ws3, ws4].filter(Boolean).forEach(ws => {
                    ws.eachRow({ includeEmpty: false }, row => {
                        row.eachCell({ includeEmpty: false }, cell => {
                            if (!cell.fill) {
                                cell.border = {
                                    ...cell.border,
                                    top: cell.border && cell.border.top ? cell.border.top : { style: 'thin', color: { argb: 'FFE5E5E5' } },
                                    left: { style: 'thin', color: { argb: 'FFE5E5E5' } },
                                    right: { style: 'thin', color: { argb: 'FFE5E5E5' } },
                                    bottom: cell.border && cell.border.bottom ? cell.border.bottom : { style: 'thin', color: { argb: 'FFE5E5E5' } },
                                };
                            }
                        });
                    });
                });

                return wb;
            }

            const exportBtn = document.getElementById('exportExcelBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', async function() {
                    const originalHtml = exportBtn.innerHTML;
                    exportBtn.disabled = true;
                    exportBtn.innerHTML = 'Preparing…';
                    try {
                        const wb = await buildWorkbook();
                        const buffer = await wb.xlsx.writeBuffer();
                        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        const fname = 'Grade' + exportMeta.grade + '_' + exportMeta.exam.replace(/\\s+/g,'') + '_' + exportMeta.term.replace(/\\s+/g,'') + '_' + exportMeta.year + '_Marklist.xlsx';
                        a.href = url;
                        a.download = fname;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } catch (err) {
                        console.error('Export failed:', err);
                        alert('Sorry, the export failed. Please try again.');
                    } finally {
                        exportBtn.disabled = false;
                        exportBtn.innerHTML = originalHtml;
                    }
                });
            }

            /* ══════════════════════════════════════════════
               REPORT CARDS (PDF) — one page per student,
               with position/rank, per-subject scores, a
               grade-aware performance level, and an
               auto-generated teacher's comment.
               ══════════════════════════════════════════════ */
            const RC_BAND_COLORS = {
                'EE2': { bg: [187,247,208], fg: [21,128,61]  },
                'EE1': { bg: [220,252,231], fg: [22,163,74]  },
                'ME2': { bg: [207,224,243], fg: [18,54,87]   },
                'ME1': { bg: [220,232,245], fg: [26,74,122]  },
                'AE2': { bg: [253,230,138], fg: [146,64,14]  },
                'AE1': { bg: [254,243,199], fg: [180,83,9]   },
                'BE2': { bg: [254,202,202], fg: [127,29,29]  },
                'BE1': { bg: [254,226,226], fg: [153,27,27]  },
                'E.E': { bg: [220,252,231], fg: [22,163,74]  },
                'M.E': { bg: [220,232,245], fg: [26,74,122]  },
                'A.E': { bg: [254,243,199], fg: [180,83,9]   },
                'B.E': { bg: [254,226,226], fg: [153,27,27]  },
            };

            function rcBandFor8(score) {
                if (score >= 90) return 'EE2';
                if (score >= 75) return 'EE1';
                if (score >= 63) return 'ME2';
                if (score >= 50) return 'ME1';
                if (score >= 38) return 'AE2';
                if (score >= 26) return 'AE1';
                if (score >= 13) return 'BE2';
                return 'BE1';
            }
            function rcBandFor4(score) {
                if (score >= 75) return 'E.E';
                if (score >= 50) return 'M.E';
                if (score >= 26) return 'A.E';
                return 'B.E';
            }
            function rcBandFor(score) {
                return isLowerGrade ? rcBandFor4(score) : rcBandFor8(score);
            }

            /* Performance comment, generated from the student's overall band. */
            const RC_COMMENTS = {
                'EE2': 'An outstanding performance across the board. Keep up this excellent standard.',
                'EE1': 'A very strong performance this term. Well done — keep up the excellent work.',
                'ME2': 'A good, solid performance. With a bit more focus you can reach the top band.',
                'ME1': 'A fair, satisfactory performance. Continued effort will improve these results.',
                'AE2': 'Performance is approaching expectations. More consistent study time is needed.',
                'AE1': 'Performance is below the expected standard. Extra practice and support are recommended.',
                'BE2': 'Significant support is needed in most areas. Please arrange a meeting with the class teacher.',
                'BE1': 'Performance is well below expectations. Close guidance and remedial support are strongly advised.',
                'E.E': 'An excellent performance this term. Keep up the great work!',
                'M.E': 'A good, solid performance. Keep striving for even better results.',
                'A.E': 'Fair performance — more effort and practice will help improve these results.',
                'B.E': 'Performance needs significant improvement. Extra support at home and school is advised.',
            };
            function commentFor(bandCode) {
                return RC_COMMENTS[bandCode] || '';
            }

            function buildReportCards() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ unit: 'pt', format: 'a4' });
                const pageWidth  = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const totalStudents = exportStudents.length;
                const subjCount = exportMeta.subjectCount;
                const totalOutOf = exportMeta.totalOutOf;

                exportStudents.forEach((s, idx) => {
                    if (idx > 0) doc.addPage();

                    /* ── Header band ── */
                    doc.setFillColor(15, 25, 35);
                    doc.rect(0, 0, pageWidth, 74, 'F');
                    doc.setFillColor(200, 153, 42);
                    doc.rect(0, 74, pageWidth, 3, 'F');

                    doc.setTextColor(255, 255, 255);
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(15);
                    doc.text(exportMeta.school, 40, 30);

                    doc.setTextColor(200, 153, 42);
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(9.5);
                    doc.text('ACADEMIC PERFORMANCE REPORT CARD', 40, 47);

                    doc.setTextColor(220, 232, 245);
                    doc.setFontSize(9);
                    doc.text('Grade ' + exportMeta.grade + '  |  ' + exportMeta.exam + '  |  ' + exportMeta.term + ', ' + exportMeta.year, 40, 62);

                    /* ── Student identity block ── */
                    let y = 108;
                    doc.setTextColor(15, 25, 35);
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(15);
                    doc.text((s.firstName + ' ' + s.lastName).trim(), 40, y);

                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(9.5);
                    doc.setTextColor(122, 144, 158);
                    doc.text('Assessment No: ' + s.assesment, 40, y + 16);

                    /* ── Position / total badges, right-aligned ── */
                    const badgeW = 170;
                    const badgeX = pageWidth - 40 - badgeW;

                    doc.setFillColor(245, 233, 200);
                    doc.roundedRect(badgeX, y - 22, badgeW, 26, 4, 4, 'F');
                    doc.setTextColor(122, 90, 16);
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(11);
                    doc.text('Position: ' + s.rank + ' of ' + totalStudents, badgeX + badgeW / 2, y - 5, { align: 'center' });

                    const ob = rcBandFor(s.total / subjCount);
                    const obCol = RC_BAND_COLORS[ob];
                    doc.setFillColor(obCol.bg[0], obCol.bg[1], obCol.bg[2]);
                    doc.roundedRect(badgeX, y + 8, badgeW, 26, 4, 4, 'F');
                    doc.setTextColor(obCol.fg[0], obCol.fg[1], obCol.fg[2]);
                    doc.setFontSize(11);
                    doc.text('Total: ' + s.total + '/' + totalOutOf + '  (' + ob + ')', badgeX + badgeW / 2, y + 25, { align: 'center' });

                    /* ── Subject score table ── */
                    const tableBody = subjectFullNames.map((name, i) => {
                        const score = s.subjects[i];
                        return [name, String(score), rcBandFor(score)];
                    });

                    doc.autoTable({
                        startY: y + 48,
                        margin: { left: 40, right: 40 },
                        head: [['Subject', 'Score (/100)', 'Level']],
                        body: tableBody,
                        theme: 'grid',
                        styles: { font: 'helvetica', fontSize: 10, cellPadding: 6, textColor: [61, 79, 92] },
                        headStyles: { fillColor: [15, 25, 35], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'left' },
                        columnStyles: {
                            0: { cellWidth: 260 },
                            1: { halign: 'center', cellWidth: 100 },
                            2: { halign: 'center', cellWidth: 100 },
                        },
                        didParseCell: function (data) {
                            if (data.section === 'body' && data.column.index === 2) {
                                const label = data.cell.raw;
                                const c = RC_BAND_COLORS[label];
                                if (c) {
                                    data.cell.styles.fillColor = c.bg;
                                    data.cell.styles.textColor = c.fg;
                                    data.cell.styles.fontStyle = 'bold';
                                }
                            }
                        }
                    });

                    let finalY = doc.lastAutoTable.finalY + 22;

                    /* ── Summary line ── */
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(10);
                    doc.setTextColor(15, 25, 35);
                    doc.text('Total Score: ' + s.total + ' / ' + totalOutOf, 40, finalY);
                    doc.text('Class Mean: ' + exportMeta.classMeanTotal + ' / ' + totalOutOf, 40, finalY + 15);
                    doc.text('Class Position: ' + s.rank + ' out of ' + totalStudents + ' students', 40, finalY + 30);

                    /* ── Teacher's Comment box (performance-based) ── */
                    let cy = finalY + 52;
                    const boxH = 54;
                    doc.setFillColor(obCol.bg[0], obCol.bg[1], obCol.bg[2]);
                    doc.roundedRect(40, cy, pageWidth - 80, boxH, 5, 5, 'F');
                    doc.setDrawColor(obCol.fg[0], obCol.fg[1], obCol.fg[2]);
                    doc.setLineWidth(0.75);
                    doc.roundedRect(40, cy, pageWidth - 80, boxH, 5, 5, 'S');

                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(9.5);
                    doc.setTextColor(obCol.fg[0], obCol.fg[1], obCol.fg[2]);
                    doc.text('TEACHER\\'S COMMENT', 52, cy + 16);

                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(9.5);
                    doc.setTextColor(61, 79, 92);
                    const commentText = commentFor(ob);
                    doc.text(commentText, 52, cy + 32, { maxWidth: pageWidth - 104 });

                    /* ── Key / legend ── */
                    let legendY = cy + boxH + 20;
                    doc.setFont('helvetica', 'italic');
                    doc.setFontSize(7.5);
                    doc.setTextColor(122, 144, 158);
                    const legendText = isLowerGrade
                        ? 'Key: E.E Exceeding Expectation (75-100) | M.E Meeting Expectation (50-74) | A.E Approaching Expectation (26-49) | B.E Below Expectation (0-25)'
                        : 'Key: EE2 90-100 | EE1 75-89 | ME2 63-74 | ME1 50-62 | AE2 38-49 | AE1 26-37 | BE2 13-25 | BE1 0-12';
                    doc.text(legendText, 40, legendY, { maxWidth: pageWidth - 80 });

                    /* ── Signature lines pinned near the bottom ── */
                    const sigY = pageHeight - 70;
                    doc.setDrawColor(200, 200, 200);
                    doc.setLineWidth(0.75);
                    doc.line(40, sigY, 240, sigY);
                    doc.line(pageWidth - 240, sigY, pageWidth - 40, sigY);

                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(9);
                    doc.setTextColor(61, 79, 92);
                    doc.text('Class Teacher', 40, sigY + 14);
                    doc.text('Head of Institution', pageWidth - 240, sigY + 14);

                    /* ── Footer page number ── */
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8);
                    doc.setTextColor(122, 144, 158);
                    doc.text('Page ' + (idx + 1) + ' of ' + totalStudents, pageWidth / 2, pageHeight - 20, { align: 'center' });
                });

                const fname = 'Grade' + exportMeta.grade + '_' + exportMeta.exam.replace(/\s+/g, '') + '_' + exportMeta.term.replace(/\s+/g, '') + '_' + exportMeta.year + '_ReportCards.pdf';
                doc.save(fname);
            }

            const reportCardsBtn = document.getElementById('downloadReportCardsBtn');
            if (reportCardsBtn) {
                reportCardsBtn.addEventListener('click', function () {
                    const originalHtml = reportCardsBtn.innerHTML;
                    reportCardsBtn.disabled = true;
                    reportCardsBtn.innerHTML = 'Preparing…';
                    try {
                        buildReportCards();
                    } catch (err) {
                        console.error('Report card generation failed:', err);
                        alert('Sorry, the report cards could not be generated. Please try again.');
                    } finally {
                        reportCardsBtn.disabled = false;
                        reportCardsBtn.innerHTML = originalHtml;
                    }
                });
            }
        })();
        </script>
        ";

    } else {
        echo "<p class='no-results'>No results found for the selected filters.</p>";
    }

} else {
    echo "<p class='no-results'>Select a grade, term, exam type, and year above to view results.</p>";
}
?>

</div><!-- .results -->
</body>
</html>

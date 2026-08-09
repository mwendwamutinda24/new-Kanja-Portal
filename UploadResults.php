<?php
// Start output buffering FIRST, before anything else executes, so
// ob_clean() below is always guaranteed to have something to clean —
// this wipes out any stray whitespace/notice/BOM that might otherwise
// leak into the AJAX response and corrupt it.
ob_start();

include 'conn.php';

mysqli_report(MYSQLI_REPORT_OFF);

// FIX: Detecting AJAX via the X-Requested-With HTTP header is
// unreliable — many hosting/proxy/server configs silently strip
// custom headers before PHP ever sees them, even though jQuery sent
// it correctly. That made $isAjax false every time on some setups,
// so the script always fell through to rendering the full HTML page,
// which is what was being dumped into the <tbody> on the frontend.
//
// A plain POST field can never be stripped like that, so this is the
// reliable way to tell "give me table rows" apart from "give me the
// full page". The manual-entry JS below now sends ajax: 1 alongside
// grade/term/examType/year.
if (isset($_POST['ajax']) && $_POST['ajax'] === '1' && isset($_POST['grade']) && $_POST['grade'] !== '') {

    // Wipe out anything buffered so far (BOM, stray whitespace,
    // notices from conn.php, etc) before writing the real response.
    ob_clean();

    // Treat this strictly as an HTML fragment, and never let it be
    // cached — a cached AJAX response is indistinguishable from "the
    // fix isn't working" while debugging.
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $grade = mysqli_real_escape_string($conn, $_POST['grade']);

    $query = "SELECT id, Assesment, firstName, surname
              FROM Student
              WHERE Grade = '$grade'
              ORDER BY firstName, surname";
    $result = mysqli_query($conn, $query);

    if ($result === false) {
        echo "<tr><td colspan='12'><div class='empty-state'>"
           . "<div class='empty-icon'><i class=\"fa-solid fa-triangle-exclamation\"></i></div>"
           . "<p>Database error loading students: " . htmlspecialchars(mysqli_error($conn)) . "</p>"
           . "</div></td></tr>";
        ob_end_flush();
        exit;
    }

    if (mysqli_num_rows($result) === 0) {
        echo "<tr><td colspan='12'><div class='empty-state'>"
           . "<div class='empty-icon'><i class=\"fa-solid fa-user-slash\"></i></div>"
           . "<p>No students found for this grade.</p>"
           . "</div></td></tr>";
        ob_end_flush();
        exit;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $studentIdSafe = htmlspecialchars($row['id'], ENT_QUOTES);
        $assesmentSafe = htmlspecialchars($row['Assesment'], ENT_QUOTES);
        $firstNameSafe = htmlspecialchars($row['firstName'], ENT_QUOTES);
        $surnameSafe   = htmlspecialchars($row['surname'], ENT_QUOTES);
        $fullName      = htmlspecialchars($row['firstName'] . ' ' . $row['surname'], ENT_QUOTES);

        echo "<tr>
           <td class='col-select'><input type='checkbox' class='learner-select' checked title='Include this learner in the submission'></td>
           <td><input type='text' name='assesment[]' value='{$assesmentSafe}' readonly></td>
            <td>
                <input type='text' class='name-display' value='{$fullName}' readonly>
                <input type='hidden' name='studentId[]' value='{$studentIdSafe}'>
                <input type='hidden' name='firstName[]' value='{$firstNameSafe}'>
                <input type='hidden' name='surname[]' value='{$surnameSafe}'>
            </td>
            <td class='col-subj' data-subj='MATH'><input type='number' name='MATH[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='ENG'><input type='number' name='ENG[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='KISW'><input type='number' name='KISW[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='SCIE'><input type='number' name='SCIE[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='sst'><input type='number' name='sst[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='ca'><input type='number' name='ca[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='AGRI'><input type='number' name='AGRI[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='re'><input type='number' name='re[]' value='' placeholder='—' min='0' max='100'></td>
            <td class='col-subj' data-subj='pretec'><input type='number' name='pretec[]' value='' placeholder='—' min='0' max='100'></td>
        </tr>";
    }

    ob_end_flush();
    exit; // hard stop — nothing after this point may run for an AJAX request
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Results — Stephen Kanja School</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
    .page-title-bar {
      display: flex;
      align-items: flex-start;
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
      font-family: 'DM Sans', sans-serif;
      font-size: 24px;
      font-weight: 400;
      color: var(--text-primary);
      line-height: 1.2;
    }

    /* ══════════════════════════════════
       FILTER CARD
    ══════════════════════════════════ */
    .filter-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      animation: fadeUp 0.4s 0.05s ease both;
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

    .filter-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem 1.2rem;
      align-items: end;
    }

    .filter-field {
      display: flex;
      flex-direction: column;
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
      transition: border-color 0.15s, box-shadow 0.15s;
      display: block;
    }

    .filter-field select:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.12);
    }

    /* ── Status banner ── */
    .status-bar {
      display: none;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      background: #fffbea;
      border: 1px solid var(--gold);
      border-radius: var(--radius-md);
      font-size: 13px;
      color: #7a5c00;
    }
    .status-bar.visible { display: flex; }
    .status-bar i { color: var(--gold-dim); flex-shrink: 0; }

    /* ── Table card ── */
    .table-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      animation: fadeUp 0.4s 0.12s ease both;
    }

    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.9rem 1.4rem;
      background: var(--black);
      border-bottom: 2px solid var(--gold);
    }

    .table-card-header span.label {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1rem;
      letter-spacing: 0.1em;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .table-card-header span.label i { color: var(--gold); }

    .count-badge {
      background: var(--gold);
      color: var(--black);
      font-size: 11px;
      font-weight: 700;
      padding: 2px 10px;
      border-radius: 20px;
      letter-spacing: 0.04em;
    }

    /* ══════════════════════════════════
       LEARNER SEARCH BAR
       Disabled/greyed until a grade is
       loaded, so it's clear the search
       has nothing to search yet.
    ══════════════════════════════════ */
    .learner-search-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0.75rem 1.4rem;
      background: var(--bg-input);
      border-bottom: 1px solid var(--border);
      transition: opacity 0.15s;
    }

    .learner-search-bar.disabled { opacity: 0.55; }

    .learner-search-wrap {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
    }

    .learner-search-wrap > i.fa-magnifying-glass {
      position: absolute;
      left: 12px;
      font-size: 12px;
      color: var(--text-tertiary);
      pointer-events: none;
    }

    .learner-search-wrap input {
      width: 100%;
      padding: 8px 34px 8px 32px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
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

    .learner-search-wrap input:disabled { cursor: not-allowed; background: var(--bg-input); }

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
      .learner-search-bar { padding: 0.6rem 0.85rem; flex-wrap: wrap; }
      .learner-search-count { width: 100%; }
    }

    /* ══════════════════════════════════
       MOBILE SUBJECT SELECTOR
       Only visible on screens ≤ 700px.
       Hidden completely on desktop —
       zero layout impact above breakpoint.
    ══════════════════════════════════ */
    .mobile-subject-bar { display: none; }

    @media (max-width: 700px) {
      .mobile-subject-bar {
        display: block;
        background: var(--bg-card);
        border-bottom: 1px solid var(--border);
      }

      .subject-mode-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: var(--bg-input);
        border-bottom: 1px solid var(--border);
      }

      .subject-mode-label {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-tertiary);
        white-space: nowrap;
      }

      .subject-mode-btn {
        font-family: 'DM Sans', sans-serif;
        font-size: 12px;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid var(--border);
        background: var(--bg-card);
        color: var(--text-secondary);
        transition: all 0.15s;
      }

      .subject-mode-btn.active {
        background: var(--black);
        color: var(--gold);
        border-color: transparent;
      }

      .subject-chip-scroll {
        display: none;
        overflow-x: auto;
        gap: 6px;
        padding: 8px 12px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        border-bottom: 1px solid var(--border);
      }
      .subject-chip-scroll::-webkit-scrollbar { display: none; }
      .subject-chip-scroll.visible { display: flex; }

      .subject-chip {
        font-size: 11px;
        font-weight: 500;
        padding: 5px 13px;
        border-radius: 20px;
        white-space: nowrap;
        cursor: pointer;
        border: 1px solid var(--border);
        background: var(--bg-input);
        color: var(--text-secondary);
        flex-shrink: 0;
        transition: all 0.15s;
      }

      .subject-chip.active {
        background: var(--gold);
        color: var(--black);
        border-color: transparent;
        font-weight: 600;
      }

      .active-subject-banner {
        display: none;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #7a5c00;
        background: #fffbea;
        border-bottom: 1px solid var(--gold);
      }
      .active-subject-banner.visible { display: flex; }
      .active-subject-banner-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--gold);
        flex-shrink: 0;
      }

      /* Hide subject columns when in single-subject mode */
      .results-table .col-subj.hidden-col { display: none; }

      /* ── Compact sizing on mobile ──
         The identity columns (Assess. No / Name) and the
         visible mark input no longer need desktop-width padding once
         most of the table is hidden — this removes the dead space
         that made rows feel stretched out in one-subject mode. */
      table.results-table { min-width: 0; font-size: 11.5px; }

      .results-table td { padding: 6px 4px; }

      .results-table input[readonly] {
        min-width: 0;
        width: 100%;
        padding: 5px 5px;
        font-size: 11px;
      }

      .results-table input[type="number"] {
        width: 44px;
        padding: 5px 4px;
        font-size: 12px;
      }

      /* In single-subject mode only 3 columns remain visible
         (Assess. No / Name / the one active subject). Switch to
         a fixed table layout so those three columns stretch to
         share the FULL screen width, instead of shrinking to a
         cramped minimum with empty space left over. */
      .results-table.single-subject-mode {
        table-layout: fixed;
        width: 100%;
      }
      .results-table.single-subject-mode th:nth-child(1),
      .results-table.single-subject-mode td:nth-child(1) { width: 30%; }
      .results-table.single-subject-mode th:nth-child(2),
      .results-table.single-subject-mode td:nth-child(2) { width: 38%; }
      .results-table.single-subject-mode th.col-subj:not(.hidden-col),
      .results-table.single-subject-mode td.col-subj:not(.hidden-col) { width: 32%; }

      .results-table.single-subject-mode td { padding: 8px 6px; }

      .results-table.single-subject-mode input[readonly] {
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        padding: 8px 6px;
        min-width: 0;
        font-size: 13px;
      }

      .results-table.single-subject-mode input[type="number"] {
        width: 100%;
        font-size: 14px;
        padding: 9px 4px;
      }
    }

    /* ── Table scroll ── */
    .table-scroll {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table.results-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12.5px;
      min-width: 900px;
    }

    .results-table thead tr.col-row th {
      background: #fafafa;
      color: var(--text-tertiary);
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 10px 10px;
      border-bottom: 1px solid rgba(0,0,0,0.07);
      text-align: left;
      white-space: nowrap;
    }

    .results-table tbody tr {
      border-bottom: 1px solid rgba(0,0,0,0.05);
      transition: background 0.12s;
    }
    .results-table tbody tr:nth-child(even) { background: rgba(0,0,0,0.012); }
    .results-table tbody tr:hover { background: var(--bg-input); }
    .results-table tbody tr:last-child { border-bottom: none; }

    .results-table td {
      padding: 7px 8px;
      vertical-align: middle;
    }

    /* Select-learner checkbox column */
    .results-table th.col-select,
    .results-table td.col-select {
      width: 34px;
      text-align: center;
      padding-left: 10px;
      padding-right: 4px;
    }
    .results-table .learner-select,
    #selectAllLearners {
      width: 15px;
      height: 15px;
      accent-color: var(--gold-dim);
      cursor: pointer;
    }
    /* A deselected row is dimmed and its marks visually locked out,
       so it's obvious at a glance that this learner won't be included
       in the submission — even though the inputs stay usable until
       re-checked, in case it was unchecked by mistake. */
    .results-table tbody tr.learner-deselected {
      opacity: 0.45;
    }

    /* Readonly text inputs */
    .results-table input[readonly] {
      width: 100%;
      padding: 6px 8px;
      background: var(--bg-input);
      border: 1px solid transparent;
      border-radius: 6px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      color: var(--text-secondary);
      font-weight: 500;
      min-width: 80px;
    }

    /* Mark number inputs */
    .results-table input[type="number"] {
      width: 58px;
      padding: 6px;
      background: var(--bg-input);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 6px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      color: var(--text-primary);
      text-align: center;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      -moz-appearance: textfield;
    }
    .results-table input[type="number"]::-webkit-inner-spin-button,
    .results-table input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
    .results-table input[type="number"]:focus {
      border-color: var(--gold);
      background: #fff;
      box-shadow: 0 0 0 2px rgba(240,192,64,0.12);
    }

    /* Empty / loading states */
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

    /* ── Submit footer ── */
    .submit-section {
      display: none;
      align-items: center;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1rem 1.4rem;
      border-top: 1px solid var(--border);
      background: var(--bg-input);
      flex-wrap: wrap;
    }

    .btn-reset-marks {
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 500;
      color: var(--text-secondary);
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 9px 18px;
      cursor: pointer;
      transition: all 0.15s;
      display: flex; align-items: center; gap: 6px;
    }
    .btn-reset-marks:hover { border-color: rgba(0,0,0,0.2); color: var(--text-primary); }

    .btn-submit {
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      color: var(--black);
      background: var(--gold);
      border: none;
      border-radius: var(--radius-md);
      padding: 10px 26px;
      cursor: pointer;
      display: flex; align-items: center; gap: 8px;
      transition: background 0.15s, transform 0.1s;
    }
    .btn-submit:hover { background: var(--gold-dim); }
    .btn-submit:active { transform: scale(0.98); }

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

    /* Tablet: 2 columns */
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

      .filter-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    /* Mobile: 1 column */
    @media (max-width: 520px) {
      .school-name { font-size: 1.1rem; }
      .filter-body { padding: 1rem; }

      .main { padding: 1rem 0.6rem 3rem; }

      .filter-grid {
        grid-template-columns: 1fr;
        gap: 0.85rem;
      }

      .filter-field select { font-size: 14px; }

      .btn-submit,
      .btn-reset-marks { width: 100%; justify-content: center; }

      .submit-section { flex-direction: column; align-items: stretch; }
    }

    /* ══════════════════════════════════════════
       MODE TABS (Manual Entry / Bulk Excel Upload)
    ══════════════════════════════════════════ */
    .mode-tabs {
      display: flex;
      gap: 8px;
      border-bottom: 2px solid var(--border);
      padding-bottom: 0;
    }

    .mode-tab {
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 600;
      color: var(--text-secondary);
      background: transparent;
      border: none;
      border-bottom: 3px solid transparent;
      padding: 10px 6px 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: color 0.15s, border-color 0.15s;
      margin-bottom: -2px;
    }
    .mode-tab i { font-size: 13px; color: var(--text-tertiary); }
    .mode-tab:hover { color: var(--text-primary); }
    .mode-tab.active { color: var(--gold-dim); border-bottom-color: var(--gold); }
    .mode-tab.active i { color: var(--gold-dim); }

    /* ══════════════════════════════════════════
       SUBJECT CHECKBOXES (Excel section)
    ══════════════════════════════════════════ */
    .subject-checkbox-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 18px;
    }

    .subject-check {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 13px;
      font-weight: 500;
      color: var(--text-primary);
      cursor: pointer;
      user-select: none;
    }

    .subject-check input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--gold-dim);
      cursor: pointer;
    }

    .subject-select-actions {
      display: flex;
      gap: 10px;
      margin-top: 14px;
      flex-wrap: wrap;
    }

    /* ══════════════════════════════════════════
       EXCEL ACTIONS (template + upload)
    ══════════════════════════════════════════ */
    .excel-actions {
      display: flex;
      gap: 2rem;
      padding: 1.1rem 1.4rem 1.3rem;
      border-top: 1px solid var(--border);
      background: var(--bg-input);
      flex-wrap: wrap;
    }

    .excel-actions-col { display: flex; flex-direction: column; gap: 8px; }
    .excel-actions-col-grow { flex: 1; min-width: 280px; }

    .excel-actions-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--text-tertiary);
    }

    .excel-upload-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: stretch;
    }

    .file-input-label {
      flex: 1;
      min-width: 220px;
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 9px 14px;
      background: var(--bg-card);
      border: 1px dashed rgba(0,0,0,0.2);
      border-radius: var(--radius-md);
      font-size: 13px;
      color: var(--text-secondary);
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
      position: relative;
    }
    .file-input-label:hover { border-color: var(--gold-dim); background: #fffdf6; }
    .file-input-label i { color: var(--gold-dim); }
    .file-input-label input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
    }

    .status-bar.status-error {
      background: #fff5f5;
      border-color: var(--red, #dc2626);
      color: #a12020;
    }
    .status-bar.status-error i { color: #dc2626; }

    @media (max-width: 700px) {
      .excel-actions { flex-direction: column; gap: 1.1rem; }
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
  <div class="header-tag"><i class="fa-solid fa-circle-dot"></i> Upload Results</div>
</header>

<div class="layout">

  <!-- ════════════ SIDEBAR ════════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <p class="nav-label">Main</p>
      <nav>
        <a href="teacherpanel.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
        <a href="progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
      </nav>
      <p class="nav-label">Academics</p>
      <nav>
        <a href="ViewResults.php"><i class="fa-solid fa-chart-pie"></i> Results</a>
        <a href="UploadResults.php" class="active"><i class="fa-solid fa-upload"></i> Upload Results</a>
        <a href="track.php"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
        <a href="LearningMaterials.php"><i class="fa-solid fa-book"></i> Learning Materials</a>
      </nav>
      <p class="nav-label">Administration</p>
      <nav>
        <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
        <a href="RegisterTeacher.php"><i class="fa-solid fa-user-plus"></i> Register Teachers</a>
      </nav>
      <p class="nav-label">Finance</p>
      <nav>
        <a href="Fee.php"><i class="fa-solid fa-coins"></i> Finances</a>
      </nav>
    </div>
    <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
  </aside>

  <!-- ════════════ MAIN ════════════ -->
  <main class="main">

    <div class="page-title-bar">
      <div>
        <p class="page-eyebrow">Academic Records</p>
        <h1 class="page-title">Upload Results</h1>
        <p style="font-size:13px;color:var(--text-tertiary);margin-top:4px;">
          Select a grade and enter marks for each subject below, or switch to Bulk Excel Upload
          to grade a whole class from a spreadsheet in one go.
        </p>
      </div>
    </div>

    <!-- ── MODE TABS ──
         Manual Entry keeps the existing row-by-row grid.
         Bulk Excel Upload is the new spreadsheet-based path
         added below — pick one or many subjects, download a
         ready-made template for the selected grade, fill it in
         offline, then upload it back. -->
    <div class="mode-tabs">
      <button type="button" class="mode-tab active" id="tabManual" onclick="setUploadMode('manual')">
        <i class="fa-solid fa-keyboard"></i> Manual Entry
      </button>
      <button type="button" class="mode-tab" id="tabExcel" onclick="setUploadMode('excel')">
        <i class="fa-solid fa-file-excel"></i> Bulk Excel Upload
      </button>
    </div>

    <div id="manualEntrySection">
    <form method="POST" action="/upload" id="resultsForm">

      <?php if (isset($_GET['upload_msg'])): ?>
      <div class="status-bar visible <?= ($_GET['upload_status'] ?? '') === 'error' ? 'status-error' : '' ?>" style="margin-bottom:1rem;">
        <i class="fa-solid <?= ($_GET['upload_status'] ?? '') === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
        <span><?= htmlspecialchars($_GET['upload_msg']) ?></span>
      </div>
      <?php endif; ?>

      <!-- ── Filter Card ── -->
      <div class="filter-card">
        <div class="filter-card-head">
          <i class="fa-solid fa-sliders"></i>
          <span>Filter Options</span>
        </div>
        <div class="filter-body">
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
              <label for="examSelect">Exam Type</label>
              <select id="examSelect" name="examType" required>
                <option value="">— Select Type —</option>
                <option value="opener">Opener</option>
                <option value="midterm">Midterm</option>
                <option value="endterm">End Term</option>
              </select>
            </div>

            <div class="filter-field">
              <label for="yearSelect">Year</label>
              <select id="yearSelect" name="year" required>
                <option value="">— Select Year —</option>
                <?php for ($y = 2026; $y <= 2030; $y++) echo "<option value='$y'>$y</option>"; ?>
              </select>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Status banner ── -->
      <div class="status-bar" id="statusBar">
        <i class="fa-solid fa-circle-info"></i>
        <span id="statusText">Students loaded. Fill in the marks and submit.</span>
      </div>

      <!-- ── Table Card ── -->
      <div class="table-card">
        <div class="table-card-header">
          <span class="label"><i class="fa-solid fa-table"></i> Student Results</span>
          <span class="count-badge" id="studentCount">0 students</span>
        </div>

        <!-- ══ LEARNER SEARCH ══
             Filters the already-loaded rows by name or assessment
             number, so a teacher can jump straight to one learner
             instead of scrolling/scanning the whole grade. Purely
             client-side — no extra request, since the rows are
             already in the DOM after the grade loads. Greyed out
             and disabled until a grade has actually been loaded. -->
        <div class="learner-search-bar disabled" id="learnerSearchBar">
          <div class="learner-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="learnerSearch" placeholder="Select a grade first…" autocomplete="off" disabled>
            <button type="button" id="learnerSearchClear" title="Clear search" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
          <span class="learner-search-count" id="learnerSearchCount"></span>
        </div>
        <!-- ══ END LEARNER SEARCH ══ -->

        <!-- ══ MOBILE SUBJECT SELECTOR ══
             Shown only on screens ≤ 700px.
             Lets the teacher switch between
             "All subjects" (horizontal scroll)
             or "One subject" (pick a chip →
             only that column is visible).
        ════════════════════════════════ -->
        <div class="mobile-subject-bar" id="mobileSubjectBar">

          <div class="subject-mode-row">
            <span class="subject-mode-label">View:</span>
            <button type="button" class="subject-mode-btn active" id="modeAll"
                    onclick="setSubjectMode('all')">
              <i class="fa-solid fa-table-columns" style="font-size:10px;margin-right:4px;"></i>All subjects
            </button>
            <button type="button" class="subject-mode-btn" id="modeOne"
                    onclick="setSubjectMode('one')">
              <i class="fa-solid fa-crosshairs" style="font-size:10px;margin-right:4px;"></i>One subject
            </button>
          </div>

          <div class="subject-chip-scroll" id="subjectChips">
            <div class="subject-chip active"  onclick="pickSubject(this,'MATH')">Maths</div>
            <div class="subject-chip"         onclick="pickSubject(this,'ENG')">English</div>
            <div class="subject-chip"         onclick="pickSubject(this,'KISW')">Kiswahili</div>
            <div class="subject-chip"         onclick="pickSubject(this,'SCIE')">Science</div>
            <div class="subject-chip"         onclick="pickSubject(this,'sst')">SST</div>
            <div class="subject-chip"         onclick="pickSubject(this,'ca')">C/A</div>
            <div class="subject-chip"         onclick="pickSubject(this,'AGRI')">Agriculture</div>
            <div class="subject-chip"         onclick="pickSubject(this,'re')">RE</div>
            <div class="subject-chip"         onclick="pickSubject(this,'pretec')">Pre-Tech</div>
          </div>

          <div class="active-subject-banner" id="activeSubjectBanner">
            <div class="active-subject-banner-dot"></div>
            <span id="activeSubjectName">Maths</span>
          </div>

        </div>
        <!-- ══ END MOBILE SUBJECT SELECTOR ══ -->

        <div class="table-scroll">
          <div class="spinner" id="spinner">
            <i class="fa-solid fa-circle-notch fa-spin"></i> Loading students…
          </div>

          <table class="results-table">
            <thead>
              <tr class="col-row">
                <th class="col-select">
                  <input type="checkbox" id="selectAllLearners" checked title="Select / deselect all learners">
                </th>
                <th>Assess. No</th>
                <th>Name</th>
                <th class="col-subj" data-subj="MATH">Maths</th>
                <th class="col-subj" data-subj="ENG">English</th>
                <th class="col-subj" data-subj="KISW">Kiswahili</th>
                <th class="col-subj" data-subj="SCIE">Science</th>
                <th class="col-subj" data-subj="sst">Soc. Studies</th>
                <th class="col-subj" data-subj="ca">C/A</th>
                <th class="col-subj" data-subj="AGRI">Agriculture</th>
                <th class="col-subj" data-subj="re">RE</th>
                <th class="col-subj" data-subj="pretec">Pre-Tech</th>
              </tr>
            </thead>
            <tbody id="studentTable">
              <tr>
                <td colspan="12">
                  <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>
                    <p>Select a grade above to load students</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="submit-section" id="submitSection">
          <button type="button" class="btn-reset-marks" onclick="clearTable()">
            <i class="fa-solid fa-rotate-left"></i> Clear Marks
          </button>
          <button type="submit" class="btn-submit">
            <i class="fa-solid fa-floppy-disk"></i> Submit Results
          </button>
        </div>
      </div>

    </form>
    </div>
    <!-- ══ END MANUAL ENTRY SECTION ══ -->

    <!-- ══════════════════════════════════════════════════════════
         BULK EXCEL UPLOAD SECTION
         Independent of the manual-entry form above. Flow:
           1. Pick Grade / Term / Exam Type / Year here.
           2. Tick one or more subjects and download a template —
              a spreadsheet pre-filled with that grade's learners
              (Assessment No, First Name, Surname) plus one blank
              column per chosen subject.
           3. Fill in marks offline, then upload the completed file.
         The upload is parsed and saved by upload_excel_marks.php,
         which matches columns by header name against the same
         subject list used everywhere else in the system, and
         updates an existing exam2 row for that student/term/year/
         exam type if one exists, or creates a new one otherwise —
         it never blindly INSERTs a duplicate row.
    ══════════════════════════════════════════════════════════ -->
    <div id="excelSection" style="display:none;">

      <?php if (isset($_GET['excel_msg'])): ?>
      <div class="status-bar visible <?= ($_GET['excel_status'] ?? '') === 'error' ? 'status-error' : '' ?>">
        <i class="fa-solid <?= ($_GET['excel_status'] ?? '') === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
        <span><?= htmlspecialchars($_GET['excel_msg']) ?></span>
      </div>
      <?php endif; ?>

      <!-- ── Filter Card (Excel context) ── -->
      <div class="filter-card">
        <div class="filter-card-head">
          <i class="fa-solid fa-sliders"></i>
          <span>Excel Upload Settings</span>
        </div>
        <div class="filter-body">
          <div class="filter-grid">

            <div class="filter-field">
              <label for="excelGrade">Grade</label>
              <select id="excelGrade" required>
                <option value="">— Select Grade —</option>
                <?php for ($i = 1; $i <= 9; $i++) echo "<option value='$i'>Grade $i</option>"; ?>
              </select>
            </div>

            <div class="filter-field">
              <label for="excelTerm">Term</label>
              <select id="excelTerm" required>
                <option value="">— Select Term —</option>
                <option value="1">Term 1</option>
                <option value="2">Term 2</option>
                <option value="3">Term 3</option>
              </select>
            </div>

            <div class="filter-field">
              <label for="excelExamType">Exam Type</label>
              <select id="excelExamType" required>
                <option value="">— Select Type —</option>
                <option value="opener">Opener</option>
                <option value="midterm">Midterm</option>
                <option value="endterm">End Term</option>
              </select>
            </div>

            <div class="filter-field">
              <label for="excelYear">Year</label>
              <select id="excelYear" required>
                <option value="">— Select Year —</option>
                <?php for ($y = 2026; $y <= 2030; $y++) echo "<option value='$y'>$y</option>"; ?>
              </select>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Subject Selection + Template + Upload ── -->
      <div class="table-card">
        <div class="table-card-header">
          <span class="label"><i class="fa-solid fa-list-check"></i> Subjects to Upload</span>
        </div>

        <div class="filter-body">
          <p style="font-size:12.5px;color:var(--text-tertiary);margin-bottom:10px;">
            Choose one subject to grade a single paper, or several to grade multiple subjects
            from the same spreadsheet in one pass. The template downloads as a <strong>.csv</strong> file —
            it opens perfectly in Excel, Google Sheets, or Numbers, just keep it saved as CSV
            (not .xlsx) when you upload it back.
          </p>

          <div class="subject-checkbox-row">
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="math" checked> Mathematics</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="eng"> English</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="kisw"> Kiswahili</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="scie"> Science</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="sst"> Social Studies</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="ca"> C/A</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="agri"> Agriculture</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="re"> RE</label>
            <label class="subject-check"><input type="checkbox" class="subj-cb" value="pretec"> Pre-Technical</label>
          </div>

          <div class="subject-select-actions">
            <button type="button" class="btn-reset-marks" id="selectAllSubjects">
              <i class="fa-solid fa-check-double"></i> Select All
            </button>
            <button type="button" class="btn-reset-marks" id="clearAllSubjects">
              <i class="fa-solid fa-xmark"></i> Clear All
            </button>
          </div>
        </div>

        <div class="excel-actions">
          <div class="excel-actions-col">
            <p class="excel-actions-label">Step 1 — Get the template</p>
            <button type="button" class="btn-filter" id="downloadTemplateBtn">
              <i class="fa-solid fa-download"></i> Download Template (CSV)
            </button>
          </div>

          <div class="excel-actions-col excel-actions-col-grow">
            <p class="excel-actions-label">Step 2 — Upload the completed file</p>
            <form action="upload_excel_marks.php" method="POST" enctype="multipart/form-data" id="excelUploadForm">
              <input type="hidden" name="grade" id="excelGradeHidden">
              <input type="hidden" name="term" id="excelTermHidden">
              <input type="hidden" name="examType" id="excelExamTypeHidden">
              <input type="hidden" name="year" id="excelYearHidden">
              <div class="excel-upload-row">
                <label class="file-input-label" id="fileInputLabel">
                  <i class="fa-solid fa-file-arrow-up"></i>
                  <span id="fileNameLabel">Choose completed .csv file…</span>
                  <input type="file" name="marksFile" id="marksFileInput" accept=".csv" required>
                </label>
                <button type="submit" class="btn-submit" id="uploadExcelBtn">
                  <i class="fa-solid fa-cloud-arrow-up"></i> Upload Marks
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- ══ END BULK EXCEL UPLOAD SECTION ══ -->

  </main>
</div>

<!-- ════════════ FOOTER ════════════ -->
<footer class="site-footer">
  &copy; Designed by Kelvin Mutinda 2026. All rights reserved.
</footer>

<script>
  /* ── Sidebar toggle ── */
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

  /* ── Grade/Term/ExamType/Year select → AJAX load ──
     FIX: previously only `grade` was sent to the server, so the
     table could never know which term/exam/year's marks to
     pre-fill (it didn't even try). Now any of the four filters
     changing re-triggers the load, and all four are sent together —
     matching what upload.php itself requires to save correctly.
     ALSO FIX: an explicit ajax:1 flag is now sent, since relying on
     the X-Requested-With header alone is unreliable across server
     configs — some strip custom headers before PHP ever sees them,
     which made the server always fall through to the full page. */
  function loadStudentTable() {
    const grade      = $('#gradeSelect').val();
    const term       = $('#termSelect').val();
    const examType   = $('#examSelect').val();
    const year       = $('#yearSelect').val();
    const statusBar  = $('#statusBar');
    const spinner    = $('#spinner');
    const submitSec  = $('#submitSection');
    const countBadge = $('#studentCount');

    if (grade) {
      spinner.show();
      $('#studentTable').html('');
      statusBar.removeClass('visible');
      submitSec.hide();

      $.ajax({
        url: 'UploadResults',
        method: 'POST',
        cache: false, // never let the browser reuse a stale cached response
        data: { ajax: 1, grade: grade, term: term, examType: examType, year: year },
        success: function (response) {
          spinner.hide();

          // Safety net: if the server ever falls back to returning
          // the full page instead of just row markup (misconfig,
          // stale cache, opcache, etc), this stops it being dumped
          // into the table and shows a clear error instead of a
          // silently mangled nested page.
          if (/<!DOCTYPE/i.test(response) || /<html[\s>]/i.test(response)) {
            $('#studentTable').html(
              '<tr><td colspan="12"><div class="empty-state">' +
              '<div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>' +
              '<p>Unexpected server response. Please contact the administrator.</p>' +
              '</div></td></tr>'
            );
            countBadge.text('0 students');
            statusBar.removeClass('visible');
            submitSec.hide();
            return;
          }

          $('#studentTable').html(response);

          const count = $('#studentTable tr').length;
          countBadge.text(count + ' student' + (count !== 1 ? 's' : ''));
          statusBar.addClass('visible');
          $('#statusText').text(count + ' students loaded for Grade ' + grade + '. Enter marks and submit.');
          submitSec.css('display', 'flex');

          /* Enable the learner search now that rows actually exist,
             and reset any leftover search term from a previous grade */
          $('#learnerSearchBar').toggleClass('disabled', count === 0);
          $('#learnerSearch')
            .prop('disabled', count === 0)
            .attr('placeholder', count === 0 ? 'No students in this grade' : 'Search learner by name or assessment number…')
            .val('');
          $('#learnerSearchClear').removeClass('visible');
          $('#learnerSearchCount').text('');

          /* Re-apply mobile subject filter after new rows are injected */
          applyMobileSubjectFilter();
        },
        error: function () {
          spinner.hide();
          $('#studentTable').html(
            '<tr><td colspan="12"><div class="empty-state">' +
            '<div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>' +
            '<p>Error loading students. Please try again.</p>' +
            '</div></td></tr>'
          );
        }
      });
    } else {
      $('#studentTable').html(
        '<tr><td colspan="12"><div class="empty-state">' +
        '<div class="empty-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></div>' +
        '<p>Select a grade above to load students</p>' +
        '</div></td></tr>'
      );
      countBadge.text('0 students');
      statusBar.removeClass('visible');
      submitSec.hide();

      $('#learnerSearchBar').addClass('disabled');
      $('#learnerSearch').prop('disabled', true).attr('placeholder', 'Select a grade first…').val('');
      $('#learnerSearchClear').removeClass('visible');
      $('#learnerSearchCount').text('');
    }
  }

  /* Any of the four filters changing reloads the table, so switching
     term/exam/year for an already-picked grade re-fetches the correct
     saved marks instead of leaving stale ones on screen. */
  $('#gradeSelect, #termSelect, #examSelect, #yearSelect').on('change', loadStudentTable);

  function clearTable() {
    $('#studentTable input[type="number"]').val('');
  }

  /* ══════════════════════════════════════════
     LEARNER SELECTION (Select All / per-row)
     Every row starts checked, so by default a
     submission applies to the whole grade — same
     as before this feature existed. Unchecking a
     learner just means: don't touch their marks
     this time, no matter what's typed in their row.

     Rows are injected dynamically via AJAX, so both
     handlers are delegated from a static ancestor
     (#studentTable) rather than bound directly.
  ══════════════════════════════════════════ */
  $('#selectAllLearners').on('change', function () {
    const checked = $(this).is(':checked');
    $('#studentTable .learner-select').prop('checked', checked)
      .closest('tr').toggleClass('learner-deselected', !checked);
  });

  $('#studentTable').on('change', '.learner-select', function () {
    $(this).closest('tr').toggleClass('learner-deselected', !this.checked);

    // Keep the header "select all" box in sync: checked only when
    // every row is checked, unchecked otherwise.
    const total   = $('#studentTable .learner-select').length;
    const checked = $('#studentTable .learner-select:checked').length;
    $('#selectAllLearners').prop('checked', total > 0 && total === checked);
  });

  // Right before the manual-entry form actually posts: any row whose
  // checkbox is unchecked gets every mark field in that row blanked out.
  // upload.php already skips a row entirely when all its marks arrive
  // blank ("no marks entered") — this reuses that exact logic instead of
  // removing the row's inputs, which would misalign the parallel arrays
  // upload.php depends on (every field must have the same row count).
  $('#resultsForm').on('submit', function () {
    $('#studentTable tr').each(function () {
      const $row = $(this);
      if ($row.find('.learner-select').length && !$row.find('.learner-select').is(':checked')) {
        $row.find('input[type="number"]').val('');
      }
    });
  });

  /* ══════════════════════════════════════════
     LEARNER SEARCH
     Filters rows already in the table by
     assessment number, first name, or surname.
     Pure client-side text match — runs on every
     keystroke against the visible row count.
     (The name is displayed as one merged field,
     but the underlying hidden firstName[]/surname[]
     inputs are still searched individually here.)
  ══════════════════════════════════════════ */
  function filterLearnerRows() {
    const term = $('#learnerSearch').val().trim().toLowerCase();
    const rows = $('#studentTable tr').filter(function () {
      return $(this).find('input[name="assesment[]"]').length > 0;
    });

    $('#learnerSearchClear').toggleClass('visible', term.length > 0);

    if (!term) {
      rows.removeClass('row-no-match');
      $('#learnerSearchCount').text('');
      return;
    }

    let visibleCount = 0;
    rows.each(function () {
      const $row   = $(this);
      const assess = ($row.find('input[name="assesment[]"]').val() || '').toLowerCase();
      const first  = ($row.find('input[name="firstName[]"]').val() || '').toLowerCase();
      const last   = ($row.find('input[name="surname[]"]').val() || '').toLowerCase();
      const isMatch = assess.includes(term) || first.includes(term) || last.includes(term);

      $row.toggleClass('row-no-match', !isMatch);
      if (isMatch) visibleCount++;
    });

    $('#learnerSearchCount').text(
      visibleCount + ' of ' + rows.length + ' shown'
    );
  }

  $('#learnerSearch').on('input', filterLearnerRows);

  $('#learnerSearchClear').on('click', function () {
    $('#learnerSearch').val('').trigger('input').focus();
  });

  /* ══════════════════════════════════════════
     MOBILE SUBJECT SELECTOR
     State is kept in two variables.
     applyMobileSubjectFilter() reads them and
     toggles .hidden-col on every .col-subj cell
     (both <th> and <td>) in the table.
  ══════════════════════════════════════════ */
  let mobileSubjectMode   = 'all';   /* 'all' | 'one' */
  let mobileActiveSubject = 'MATH';  /* matches data-subj values */

  function setSubjectMode(mode) {
    mobileSubjectMode = mode;

    document.getElementById('modeAll').classList.toggle('active', mode === 'all');
    document.getElementById('modeOne').classList.toggle('active', mode === 'one');
    document.getElementById('subjectChips').classList.toggle('visible', mode === 'one');
    document.getElementById('activeSubjectBanner').classList.toggle('visible', mode === 'one');

    applyMobileSubjectFilter();
  }

  function pickSubject(el, subjKey) {
    mobileActiveSubject = subjKey;

    /* Update chip highlight */
    document.querySelectorAll('.subject-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');

    /* Update banner label */
    document.getElementById('activeSubjectName').textContent = el.textContent.trim();

    applyMobileSubjectFilter();
  }

  function applyMobileSubjectFilter() {
    /* Target both thead <th> and tbody <td> cells */
    document.querySelectorAll('.results-table .col-subj').forEach(function(el) {
      if (mobileSubjectMode === 'all') {
        el.classList.remove('hidden-col');
      } else {
        const match = el.dataset.subj === mobileActiveSubject;
        el.classList.toggle('hidden-col', !match);
      }
    });

    /* Compact identity columns only kick in for single-subject mode —
       see the .single-subject-mode rules in the mobile media query */
    document.querySelector('.results-table')
      .classList.toggle('single-subject-mode', mobileSubjectMode === 'one');
  }

  /* ══════════════════════════════════════════════════════════
     MODE SWITCH — Manual Entry vs Bulk Excel Upload
  ══════════════════════════════════════════════════════════ */
  function setUploadMode(mode) {
    const manual = document.getElementById('manualEntrySection');
    const excel  = document.getElementById('excelSection');
    const tabManual = document.getElementById('tabManual');
    const tabExcel  = document.getElementById('tabExcel');

    if (mode === 'excel') {
      manual.style.display = 'none';
      excel.style.display = 'block';
      tabManual.classList.remove('active');
      tabExcel.classList.add('active');
    } else {
      manual.style.display = 'block';
      excel.style.display = 'none';
      tabExcel.classList.remove('active');
      tabManual.classList.add('active');
    }
  }

  <?php if (isset($_GET['excel_msg'])): ?>
  // A result message from a previous Excel upload is present — open on that tab.
  document.addEventListener('DOMContentLoaded', () => setUploadMode('excel'));
  <?php endif; ?>

  /* ══════════════════════════════════════════════════════════
     SUBJECT CHECKBOXES — select all / clear all
  ══════════════════════════════════════════════════════════ */
  document.getElementById('selectAllSubjects').addEventListener('click', () => {
    document.querySelectorAll('.subj-cb').forEach(cb => cb.checked = true);
  });
  document.getElementById('clearAllSubjects').addEventListener('click', () => {
    document.querySelectorAll('.subj-cb').forEach(cb => cb.checked = false);
  });

  function getSelectedSubjects() {
    return Array.from(document.querySelectorAll('.subj-cb:checked')).map(cb => cb.value);
  }

  function getExcelFilters() {
    return {
      grade: document.getElementById('excelGrade').value,
      term: document.getElementById('excelTerm').value,
      examType: document.getElementById('excelExamType').value,
      year: document.getElementById('excelYear').value,
    };
  }

  /* ══════════════════════════════════════════════════════════
     DOWNLOAD TEMPLATE
     Builds a GET request to generate_template.php with the
     chosen grade + subject codes, which streams back a
     ready-to-fill .csv pre-populated with that grade's roster.
  ══════════════════════════════════════════════════════════ */
  document.getElementById('downloadTemplateBtn').addEventListener('click', () => {
    const { grade } = getExcelFilters();
    const subjects = getSelectedSubjects();

    if (!grade) {
      alert('Please select a grade first.');
      return;
    }
    if (subjects.length === 0) {
      alert('Please select at least one subject.');
      return;
    }

    const params = new URLSearchParams();
    params.append('grade', grade);
    subjects.forEach(s => params.append('subjects[]', s));

    window.location = 'generate_template.php?' + params.toString();
  });

  /* ══════════════════════════════════════════════════════════
     UPLOAD FORM — sync the shared filters into hidden fields
     right before submit, and do a last sanity check.
  ══════════════════════════════════════════════════════════ */
  document.getElementById('excelUploadForm').addEventListener('submit', function (e) {
    const { grade, term, examType, year } = getExcelFilters();

    if (!grade || !term || !examType || !year) {
      e.preventDefault();
      alert('Please select Grade, Term, Exam Type, and Year before uploading.');
      return;
    }

    document.getElementById('excelGradeHidden').value = grade;
    document.getElementById('excelTermHidden').value = term;
    document.getElementById('excelExamTypeHidden').value = examType;
    document.getElementById('excelYearHidden').value = year;
  });

  /* Show the chosen filename in the drop-style file input */
  document.getElementById('marksFileInput').addEventListener('change', function () {
    const label = document.getElementById('fileNameLabel');
    label.textContent = this.files.length ? this.files[0].name : 'Choose completed .csv file…';
  });
</script>

</body>
</html>
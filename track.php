<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Performance — Stephen Kanja School</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Crimson+Pro:ital,wght@0,300;0,400;0,600;1,300;1,400&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:        #f0c040;
      --gold-dim:    #c9a030;
      --gold-pale:   #fdf6e0;
      --black:       #0d0d0d;
      --dark:        #161616;
      --mid:         #262626;
      --bg:          #f2f1ef;
      --surface:     #ffffff;
      --surface-2:   #f8f7f5;
      --border:      rgba(0,0,0,0.08);
      --border-hard: #e0ddd8;
      --text:        #181816;
      --text-2:      #4a4a46;
      --text-3:      #8a8a84;
      --sidebar-w:   260px;

      --ee:  #059669; --ee-bg:  #d1fae5; --ee-mid: rgba(5,150,105,0.12);
      --me:  #2563eb; --me-bg:  #dbeafe; --me-mid: rgba(37,99,235,0.12);
      --ae:  #d97706; --ae-bg:  #fef3c7; --ae-mid: rgba(217,119,6,0.12);
      --be:  #dc2626; --be-bg:  #fee2e2; --be-mid: rgba(220,38,38,0.12);

      --r-sm: 8px;
      --r-md: 12px;
      --r-lg: 16px;
      --r-xl: 20px;

      --shadow-1: 0 1px 4px rgba(0,0,0,0.06);
      --shadow-2: 0 4px 16px rgba(0,0,0,0.08);
      --shadow-3: 0 12px 40px rgba(0,0,0,0.12);
      --shadow-4: 0 24px 64px rgba(0,0,0,0.16);
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      font-size: 14.5px;
      line-height: 1.6;
    }

    /* ══════════ OVERLAY ══════════ */
    .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:200; backdrop-filter:blur(3px); }
    .overlay.active { display:block; }

    /* ══════════ SIDEBAR ══════════ */
    .sidebar {
      position:fixed; top:0; left:0;
      width:var(--sidebar-w); height:100vh;
      background:var(--black);
      display:flex; flex-direction:column;
      z-index:300;
      transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
      overflow:hidden;
    }
    .sidebar::before {
      content:''; position:absolute; top:0; left:0;
      width:3px; height:100%;
      background:linear-gradient(180deg, var(--gold) 0%, transparent 100%);
    }
    .sidebar-brand {
      padding:1.6rem 1.4rem 1.2rem;
      border-bottom:1px solid var(--mid);
      display:flex; align-items:center; gap:10px;
    }
    .brand-icon { width:36px; height:36px; background:var(--gold); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .brand-icon i { color:var(--black); font-size:16px; }
    .brand-text { display:flex; flex-direction:column; line-height:1.15; }
    .brand-text .bn { font-family:'Bebas Neue',sans-serif; font-size:1.15rem; letter-spacing:0.12em; color:#fff; }
    .brand-text .bs { font-size:9.5px; color:#555; letter-spacing:0.1em; text-transform:uppercase; }
    .sidebar-close { display:none; position:absolute; top:1rem; right:1rem; background:var(--mid); border:none; color:#fff; width:28px; height:28px; border-radius:6px; cursor:pointer; align-items:center; justify-content:center; font-size:12px; transition:background 0.2s; }
    .sidebar-close:hover { background:var(--gold); color:var(--black); }
    .sidebar-nav { flex:1; overflow-y:auto; padding:0.9rem 0; scrollbar-width:thin; scrollbar-color:var(--mid) transparent; }
    .nav-label { font-size:9.5px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#444; padding:0.75rem 1.4rem 0.35rem; }
    .sidebar-nav a { display:flex; align-items:center; gap:11px; padding:0.65rem 1.4rem; color:#999; text-decoration:none; font-size:13px; font-weight:500; border-left:3px solid transparent; transition:all 0.16s; }
    .sidebar-nav a i { width:17px; text-align:center; font-size:12px; flex-shrink:0; }
    .sidebar-nav a:hover, .sidebar-nav a.active { color:#fff; background:rgba(255,255,255,0.04); border-left-color:var(--gold); }
    .sidebar-nav a.active { background:rgba(240,192,64,0.07); }
    .sidebar-nav a:hover i, .sidebar-nav a.active i { color:var(--gold); }
    .sidebar-footer { padding:0.9rem 1.4rem; border-top:1px solid var(--mid); font-size:10.5px; color:#3a3a3a; }

    /* ══════════ MAIN ══════════ */
    .main { margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; transition:margin-left 0.3s; }

    /* ── Topbar ── */
    .topbar { background:var(--black); border-bottom:3px solid var(--gold); height:62px; padding:0 2rem; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; flex-shrink:0; }
    .topbar-l { display:flex; align-items:center; gap:14px; }
    .menu-btn { display:none; background:var(--mid); border:none; color:#fff; width:32px; height:32px; border-radius:7px; cursor:pointer; align-items:center; justify-content:center; font-size:13px; transition:background 0.2s; flex-shrink:0; }
    .menu-btn:hover { background:var(--gold); color:var(--black); }
    .topbar-title { font-family:'Bebas Neue',sans-serif; font-size:1.55rem; letter-spacing:0.12em; color:#fff; }
    .topbar-title span { color:var(--gold); }
    .topbar-tag { font-size:10.5px; color:#555; letter-spacing:0.05em; display:flex; align-items:center; gap:6px; }
    .topbar-tag i { color:var(--gold); font-size:9px; }

    /* ══════════ HERO ══════════ */
    .page-hero {
      background:var(--black);
      position:relative; overflow:hidden;
      padding:2.5rem 2.4rem 2.2rem;
      border-bottom:1px solid #1e1e1e;
    }
    .page-hero::before {
      content:'';
      position:absolute; inset:0;
      background:
        radial-gradient(ellipse 55% 90% at 8% 60%, rgba(240,192,64,0.09) 0%, transparent 55%),
        radial-gradient(ellipse 35% 55% at 92% 10%, rgba(37,99,235,0.12) 0%, transparent 55%),
        radial-gradient(ellipse 25% 40% at 60% 100%, rgba(5,150,105,0.06) 0%, transparent 50%);
    }
    .hero-grid {
      position:relative;
      display:grid; grid-template-columns:1fr auto;
      align-items:end; gap:2rem;
    }
    .hero-eyebrow { font-size:9.5px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; color:var(--gold-dim); margin-bottom:7px; }
    .hero-title { font-family:'Bebas Neue',sans-serif; font-size:clamp(2.2rem,4vw,3.2rem); letter-spacing:0.07em; color:#fff; line-height:1; }
    .hero-title em { color:var(--gold); font-style:normal; }
    .hero-sub { font-family:'Crimson Pro',serif; font-size:1.05rem; color:#555; margin-top:9px; font-style:italic; }
    .hero-kpis { display:flex; gap:2.4rem; flex-shrink:0; }
    .hkpi { text-align:right; }
    .hkpi-val { font-family:'Bebas Neue',sans-serif; font-size:2.4rem; color:var(--gold); letter-spacing:0.04em; line-height:1; }
    .hkpi-lbl { font-size:9px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:#444; margin-top:2px; }

    /* ══════════ TABS ══════════ */
    .tabs-bar {
      background:var(--black);
      border-bottom:1px solid #1a1a1a;
      padding:0 2.4rem;
      display:flex; gap:0;
      position:sticky; top:62px; z-index:90;
    }
    .tab-btn {
      background:transparent; border:none; border-bottom:3px solid transparent;
      color:#555; font-family:'DM Sans',sans-serif; font-size:12.5px; font-weight:600;
      letter-spacing:0.04em; padding:0.9rem 1.4rem;
      cursor:pointer; display:flex; align-items:center; gap:8px;
      transition:all 0.2s; white-space:nowrap;
    }
    .tab-btn i { font-size:11px; }
    .tab-btn:hover { color:#ccc; border-bottom-color:#444; }
    .tab-btn.active { color:var(--gold); border-bottom-color:var(--gold); }

    /* ══════════ CONTENT ══════════ */
    .content { flex:1; padding:2rem 2.4rem 3rem; }

    /* tab panes */
    .tab-pane { display:none; animation:fadeUp 0.35s ease; }
    .tab-pane.active { display:block; }

    /* ══════════ SHARED COMPONENTS ══════════ */

    /* section heading */
    .sec-row { display:flex; align-items:center; gap:12px; margin-bottom:1.3rem; }
    .sec-row h2 { font-family:'Bebas Neue',sans-serif; font-size:1.5rem; letter-spacing:0.09em; color:var(--text); }
    .sec-row h2 i { color:var(--gold); font-size:1rem; margin-right:5px; }
    .sec-row::after { content:''; flex:1; height:1px; background:var(--border-hard); }
    .sec-pill { font-size:9.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; background:var(--black); color:var(--gold); padding:3px 10px; border-radius:20px; white-space:nowrap; flex-shrink:0; }

    /* card base */
    .card { background:var(--surface); border:1px solid var(--border-hard); border-radius:var(--r-lg); overflow:hidden; box-shadow:var(--shadow-1); }
    .card-head { background:var(--black); border-bottom:2px solid var(--gold); padding:0.85rem 1.4rem; display:flex; align-items:center; gap:8px; }
    .card-head h3 { font-family:'Bebas Neue',sans-serif; font-size:1rem; letter-spacing:0.1em; color:#fff; }
    .card-head h3::before { content:'◆ '; color:var(--gold); font-size:0.65rem; }
    .card-body { padding:1.4rem 1.6rem; }

    /* grid helpers */
    .g2 { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; }
    .g3 { display:grid; grid-template-columns:repeat(3,1fr); gap:1.2rem; }
    .g4 { display:grid; grid-template-columns:repeat(4,1fr); gap:1.1rem; }
    .mb { margin-bottom:1.6rem; }
    .mt { margin-top:2rem; }

    /* chart wrappers */
    .ch { position:relative; }
    .ch-260 { height:260px; }
    .ch-300 { height:300px; }
    .ch-340 { height:340px; }
    .ch-220 { height:220px; }

    /* award badges */
    .aw { display:inline-block; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px; letter-spacing:0.4px; vertical-align:middle; }
    .aw-ee { background:var(--ee-bg); color:var(--ee); }
    .aw-me { background:var(--me-bg); color:var(--me); }
    .aw-ae { background:var(--ae-bg); color:var(--ae); }
    .aw-be { background:var(--be-bg); color:var(--be); }

    /* kpi chips */
    .kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:1.1rem; margin-bottom:1.6rem; }
    .kpi { background:var(--surface); border:1px solid var(--border-hard); border-radius:var(--r-lg); padding:1.2rem 1.4rem; position:relative; overflow:hidden; transition:transform 0.2s, box-shadow 0.2s; animation:fadeUp 0.4s ease both; }
    .kpi:hover { transform:translateY(-3px); box-shadow:var(--shadow-3); }
    .kpi::before { content:''; display:block; position:absolute; top:0; left:0; right:0; height:3px; }
    .kpi.k-dark::before  { background:var(--black); }
    .kpi.k-ee::before    { background:var(--ee); }
    .kpi.k-me::before    { background:var(--me); }
    .kpi.k-ae::before    { background:var(--ae); }
    .kpi.k-be::before    { background:var(--be); }
    .kpi.k-gold::before  { background:var(--gold); }
    .kpi-lbl { font-size:9.5px; font-weight:700; letter-spacing:1.3px; text-transform:uppercase; color:var(--text-3); margin-bottom:6px; }
    .kpi-val { font-family:'Bebas Neue',sans-serif; font-size:2.3rem; color:var(--text); line-height:1; letter-spacing:0.02em; }
    .kpi-val.sm { font-size:1.7rem; }
    .kpi-sub { font-size:11px; color:var(--text-3); margin-top:5px; }
    .kpi-icon { position:absolute; top:1.1rem; right:1.2rem; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; }
    .kpi-icon.ic-gold  { background:var(--gold); color:var(--black); }
    .kpi-icon.ic-ee    { background:var(--ee-bg); color:var(--ee); }
    .kpi-icon.ic-me    { background:var(--me-bg); color:var(--me); }
    .kpi-icon.ic-be    { background:var(--be-bg); color:var(--be); }

    /* band bar */
    .band-row { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .band-label { font-size:11.5px; font-weight:600; width:120px; flex-shrink:0; }
    .band-track { flex:1; background:var(--bg); border-radius:99px; height:9px; overflow:hidden; }
    .band-fill { height:100%; border-radius:99px; transition:width 1.2s cubic-bezier(0.4,0,0.2,1); }
    .bf-ee { background:var(--ee); }
    .bf-me { background:var(--me); }
    .bf-ae { background:var(--ae); }
    .bf-be { background:var(--be); }
    .band-count { font-size:11px; font-weight:700; width:60px; text-align:right; color:var(--text-2); flex-shrink:0; }

    /* table */
    .tbl-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; }
    table.t { width:100%; border-collapse:collapse; font-size:12.5px; min-width:600px; }
    table.t thead th { background:var(--surface-2); color:var(--text-3); font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:9px 12px; border-bottom:1px solid var(--border-hard); text-align:left; white-space:nowrap; }
    table.t tbody tr { border-bottom:1px solid rgba(0,0,0,0.04); transition:background 0.12s; }
    table.t tbody tr:hover { background:var(--surface-2); }
    table.t tbody tr:last-child { border-bottom:none; }
    table.t td { padding:9px 12px; color:var(--text-2); vertical-align:middle; }
    table.t .rank { font-family:'Bebas Neue',sans-serif; font-size:1.1rem; color:var(--text-3); }
    table.t .strong { font-weight:600; color:var(--text); }
    .rank-gold   { color:#c9a030; }
    .rank-silver { color:#888; }
    .rank-bronze { color:#b87333; }

    /* mini sparkline placeholder text */
    .trend-up   { color:var(--ee); font-weight:700; font-size:11px; }
    .trend-down { color:var(--be); font-weight:700; font-size:11px; }
    .trend-flat { color:var(--text-3); font-weight:600; font-size:11px; }

    /* empty state */
    .empty-box { text-align:center; padding:3.5rem 2rem; }
    .empty-box .eico { width:60px; height:60px; background:var(--black); border-radius:14px; display:flex; align-items:center; justify-content:center; margin:0 auto 1.1rem; }
    .empty-box .eico i { color:var(--gold); font-size:24px; }
    .empty-box h3 { font-size:1rem; font-weight:600; margin-bottom:5px; }
    .empty-box p  { font-size:12.5px; color:var(--text-3); }

    /* ══════════ TAB 1: INDIVIDUAL SEARCH ══════════ */
    .search-card {
      background:var(--surface);
      border:1px solid var(--border-hard);
      border-radius:var(--r-xl);
      overflow:hidden;
      margin-bottom:1.8rem;
      box-shadow:var(--shadow-2);
    }
    .search-head {
      background:var(--black);
      border-bottom:2px solid var(--gold);
      padding:1.1rem 1.6rem;
      display:flex; align-items:center; gap:10px;
    }
    .search-head h3 { font-family:'Bebas Neue',sans-serif; font-size:1.15rem; letter-spacing:0.1em; color:#fff; }
    .search-head h3::before { content:'◆ '; color:var(--gold); font-size:0.7rem; }
    .search-body { padding:1.6rem 2rem; }
    .search-form { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:1rem; align-items:end; }
    .sf-group { display:flex; flex-direction:column; gap:5px; }
    .sf-group label { font-size:10px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--text-2); }
    .sf-control {
      padding:10px 14px;
      background:var(--surface-2);
      border:1.5px solid var(--border-hard);
      border-radius:var(--r-sm);
      font-family:'DM Sans',sans-serif;
      font-size:13.5px; color:var(--text);
      outline:none; width:100%;
      transition:border-color 0.15s, box-shadow 0.15s;
    }
    select.sf-control {
      appearance:none; -webkit-appearance:none;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7'%3E%3Cpath d='M1 1l4.5 4.5L10 1' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 11px center;
      padding-right:32px; cursor:pointer;
    }
    .sf-control:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(240,192,64,0.12); }
    .btn-search {
      background:var(--black); color:#fff;
      border:none; border-radius:var(--r-sm);
      padding:10px 24px; font-family:'DM Sans',sans-serif;
      font-size:13px; font-weight:600;
      cursor:pointer; display:flex; align-items:center; gap:8px;
      transition:background 0.15s; height:42px; white-space:nowrap;
    }
    .btn-search:hover { background:var(--gold); color:var(--black); }

    /* learner profile card */
    .profile-card {
      background:var(--black);
      border-radius:var(--r-xl);
      padding:2rem 2.2rem;
      display:flex; align-items:center; gap:2rem;
      margin-bottom:1.6rem;
      position:relative; overflow:hidden;
      box-shadow:var(--shadow-3);
    }
    .profile-card::before {
      content:''; position:absolute; inset:0;
      background:radial-gradient(ellipse 60% 80% at 5% 50%, rgba(240,192,64,0.08) 0%, transparent 60%);
    }
    .profile-avatar {
      width:72px; height:72px; border-radius:50%;
      background:linear-gradient(135deg, var(--gold) 0%, var(--gold-dim) 100%);
      display:flex; align-items:center; justify-content:center;
      flex-shrink:0; font-size:28px; color:var(--black);
      font-family:'Bebas Neue',sans-serif; letter-spacing:0.05em;
      position:relative; z-index:1;
      box-shadow:0 0 0 4px rgba(240,192,64,0.2);
    }
    .profile-info { position:relative; z-index:1; flex:1; min-width:0; }
    .profile-name { font-family:'Bebas Neue',sans-serif; font-size:1.9rem; letter-spacing:0.08em; color:#fff; line-height:1.1; }
    .profile-detail { font-size:12px; color:#666; margin-top:5px; display:flex; gap:1.2rem; flex-wrap:wrap; }
    .profile-detail span { display:flex; align-items:center; gap:5px; }
    .profile-detail i { color:var(--gold); font-size:10px; }
    .profile-badges { position:relative; z-index:1; display:flex; flex-direction:column; gap:8px; flex-shrink:0; }
    .pb { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:7px 14px; }
    .pb-label { font-size:9.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#555; }
    .pb-val { font-family:'Bebas Neue',sans-serif; font-size:1.5rem; color:var(--gold); letter-spacing:0.04em; line-height:1; }

    /* ══════════ VERTICAL SUBJECT SCORE TABLE (individual + grade) ══════════ */
    .perf-table-card { margin-bottom:1.6rem; }
    table.perf-table { width:100%; border-collapse:collapse; font-size:13px; min-width:720px; }
    table.perf-table thead th {
      background:var(--surface-2); color:var(--text-3);
      font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase;
      padding:11px 16px; border-bottom:1px solid var(--border-hard);
      text-align:left; white-space:nowrap;
    }
    table.perf-table tbody tr { border-bottom:1px solid rgba(0,0,0,0.04); transition:background 0.12s; }
    table.perf-table tbody tr:last-child { border-bottom:none; }
    table.perf-table tbody tr:hover { background:var(--surface-2); }
    table.perf-table td { padding:12px 16px; color:var(--text-2); vertical-align:middle; }
    table.perf-table .pt-subject { font-weight:600; color:var(--text); white-space:nowrap; }
    table.perf-table .pt-score { font-family:'Bebas Neue',sans-serif; font-size:1.35rem; letter-spacing:0.02em; color:var(--text); }
    table.perf-table .pt-outof { color:var(--text-3); font-size:12px; }
    table.perf-table .pt-pct { font-weight:600; color:var(--text-2); }
    .pt-progress-track { width:150px; background:var(--bg); border-radius:99px; height:8px; overflow:hidden; }
    .pt-progress-fill { height:100%; border-radius:99px; transition:width 1s cubic-bezier(0.4,0,0.2,1); }
    .pt-vs-pos  { color:var(--ee); font-weight:700; font-size:12px; white-space:nowrap; }
    .pt-vs-neg  { color:var(--be); font-weight:700; font-size:12px; white-space:nowrap; }
    .pt-vs-flat { color:var(--text-3); font-weight:600; font-size:12px; white-space:nowrap; }

    /* rank timeline */
    .rank-history { display:flex; gap:0.7rem; overflow-x:auto; padding-bottom:0.4rem; scrollbar-width:thin; }
    .rh-item { flex-shrink:0; background:var(--surface-2); border:1px solid var(--border-hard); border-radius:var(--r-md); padding:0.8rem 1.2rem; text-align:center; min-width:100px; transition:box-shadow 0.2s; }
    .rh-item:hover { box-shadow:var(--shadow-2); }
    .rh-exam { font-size:9.5px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:var(--text-3); margin-bottom:4px; white-space:nowrap; }
    .rh-rank { font-family:'Bebas Neue',sans-serif; font-size:1.8rem; color:var(--text); line-height:1; }
    .rh-total { font-size:10px; color:var(--text-3); margin-top:2px; }

    /* ══════════ FOOTER ══════════ */
    .footer { text-align:center; padding:1.3rem 2rem; border-top:1px solid var(--border-hard); font-size:10.5px; color:#ccc; letter-spacing:0.04em; }

    /* ══════════ ANIMATIONS ══════════ */
    @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }

    /* ══════════ RESPONSIVE ══════════ */
    @media(max-width:1100px) {
      .kpi-row { grid-template-columns:repeat(2,1fr); }
      .g4 { grid-template-columns:repeat(2,1fr); }
      .search-form { grid-template-columns:1fr 1fr; }
      .btn-search-wrap { grid-column:span 2; }
    }
    @media(max-width:900px) {
      .sidebar{transform:translateX(-100%)}
      .sidebar.open{transform:translateX(0);box-shadow:8px 0 40px rgba(0,0,0,0.5)}
      .sidebar-close{display:flex} .main{margin-left:0} .menu-btn{display:flex}
      .topbar-tag{display:none} .content{padding:1.4rem 1.2rem 3rem}
      .g2,.g3,.g4{grid-template-columns:1fr}
      .hero-kpis{display:none}
    }
    @media(max-width:640px) {
      .page-hero{padding:1.6rem 1.2rem}
      .tabs-bar{overflow-x:auto; -webkit-overflow-scrolling:touch}
      .search-form{grid-template-columns:1fr}
      .btn-search-wrap{grid-column:1}
      .kpi-row{grid-template-columns:1fr 1fr}
      .profile-card{flex-direction:column; align-items:flex-start}
    }
  </style>
</head>
<body>

<?php
/* ══════════════════════════════════════
   DATABASE HELPERS
══════════════════════════════════════ */
include('conn.php');

$subjects    = ['math','eng','kisw','sst','scie','ca','agri','re','pretec'];
$subjectNames= ['Mathematics','English','Kiswahili','Social Studies','Science','Creative Arts','Agriculture','RE','Pre-Tech'];
$short       = ['Maths','Eng','Kisw','SST','Sci','CA','Agri','RE','PreTec'];

function bandClass($v)  { return $v>74?'aw-ee':($v>49?'aw-me':($v>25?'aw-ae':'aw-be')); }
function bandLabel($v)  { return $v>74?'E.E':($v>49?'M.E':($v>25?'A.E':'B.E')); }
function bandFill($v)   { return $v>74?'bf-ee':($v>49?'bf-me':($v>25?'bf-ae':'bf-be')); }
function bandColor($v)  { return $v>74?'#059669':($v>49?'#2563eb':($v>25?'#d97706':'#dc2626')); }
function examDisp($e)   { return ['opener'=>'Opener','midterm'=>'Mid-Term','endterm'=>'End Term'][$e]??ucfirst($e); }
/* vs-average helper: formats a signed percentage-point delta with the right CSS class */
function vsAvgFmt($diff) {
    if ($diff === null) return ['cls'=>'pt-vs-flat', 'txt'=>'—'];
    if ($diff > 0)  return ['cls'=>'pt-vs-pos',  'txt'=>'+'.number_format($diff,1).'%'];
    if ($diff < 0)  return ['cls'=>'pt-vs-neg',  'txt'=>number_format($diff,1).'%'];
    return ['cls'=>'pt-vs-flat', 'txt'=>'0%'];
}

/* ── School-wide stats (for hero) ── */
$schoolStats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT CONCAT(grade,term,exam_type,year)) AS exams,
            COUNT(*) AS records,
            AVG((math+eng+kisw+sst+scie+ca+agri+re+pretec)/9) AS school_mean
     FROM exam2"));
$totalStudents = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM Student"))['c']??0);

/* ── Grade performance (for school tab) ── */
$gradePerf = [];
$gpRes = mysqli_query($conn,
    "SELECT grade, term, exam_type, year,
            AVG((math+eng+kisw+sst+scie+ca+agri+re+pretec)/9) AS mean,
            COUNT(*) AS cnt
     FROM exam2 GROUP BY grade,term,exam_type,year ORDER BY grade+0,year,term,exam_type");
while ($r = mysqli_fetch_assoc($gpRes)) $gradePerf[] = $r;

/* ── Latest mean per grade ── */
$latestGrade = [];
foreach ($gradePerf as $p) $latestGrade[$p['grade']] = $p;

/* ── Overall band distribution ── */
$bandAll = ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0];
$bRes = mysqli_query($conn,"SELECT (math+eng+kisw+sst+scie+ca+agri+re+pretec)/9 AS m FROM exam2");
while ($b = mysqli_fetch_assoc($bRes)) {
    $s=(float)$b['m'];
    if($s>74)$bandAll['ee']++;elseif($s>49)$bandAll['me']++;elseif($s>25)$bandAll['ae']++;else$bandAll['be']++;
}
$bandTotal = array_sum($bandAll);

/* ── Subject school means (used as the "vs School Avg" benchmark on the Grade tab) ── */
$schoolSubjMeans = [];
foreach ($subjects as $s) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT AVG($s) AS m FROM exam2"));
    $schoolSubjMeans[$s] = round((float)($r['m']??0),1);
}

/* ── Grade rankings (latest exam per grade) ── */
$gradeRankData = [];
for ($g=1;$g<=9;$g++) {
    $m = isset($latestGrade[$g]) ? round((float)$latestGrade[$g]['mean'],1) : 0;
    $gradeRankData[] = ['grade'=>$g,'mean'=>$m];
}
usort($gradeRankData, fn($a,$b)=>$b['mean']<=>$a['mean']);

/* ── Individual learner search ── */
$learner     = null;
$learnerExams= [];
$learnerRanks= [];
$searchError = '';
$searchAssess= trim($_GET['assess'] ?? '');
$searchName  = trim($_GET['sname']  ?? '');
$searchGrade = trim($_GET['sgrade'] ?? '');

/* per-subject "vs Grade Avg" benchmark for the learner's most recent exam session */
$gradeSubjAvgForLatest = [];

if ($searchAssess || $searchName) {
    $where = [];
    if ($searchAssess) $where[] = "Assesment = '".mysqli_real_escape_string($conn,$searchAssess)."'";
    if ($searchName)   $where[] = "(firstName LIKE '%".mysqli_real_escape_string($conn,$searchName)."%' OR surname LIKE '%".mysqli_real_escape_string($conn,$searchName)."%')";
    if ($searchGrade)  $where[] = "Grade = '".mysqli_real_escape_string($conn,$searchGrade)."'";
    $wStr = implode(' AND ',$where);
    $learner = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM Student WHERE $wStr LIMIT 1"));
    if (!$learner) $searchError = 'No learner found matching your search.';

    if ($learner) {
        $assess = mysqli_real_escape_string($conn, $learner['Assesment']);
        $grade  = mysqli_real_escape_string($conn, $learner['grade']);
        /* all exams for this learner */
        $eRes = mysqli_query($conn,
            "SELECT * FROM exam2 WHERE Assesment='$assess' ORDER BY year,term,exam_type");
        while ($e = mysqli_fetch_assoc($eRes)) {
            $e['total'] = array_sum(array_map(fn($s)=>(int)($e[$s]??0),$subjects));
            $e['mean']  = round($e['total']/count($subjects),1);
            $learnerExams[] = $e;
        }
        /* class rank per exam */
        foreach ($learnerExams as &$exam) {
            $t   = (int)$exam['term'];
            $et  = $exam['exam_type'];
            $yr  = (int)$exam['year'];
            $tot = $exam['total'];
            $rRes = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT COUNT(*)+1 AS rnk FROM exam2
                 WHERE grade='$grade' AND term='$t' AND exam_type='$et' AND year='$yr'
                 AND (math+eng+kisw+sst+scie+ca+agri+re+pretec) > $tot"));
            $exam['rank']      = (int)($rRes['rnk']??1);
            $classSize = (int)(mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT COUNT(*) AS c FROM exam2
                 WHERE grade='$grade' AND term='$t' AND exam_type='$et' AND year='$yr'"))['c']??1);
            $exam['class_size']= $classSize;
        }
        unset($exam);

        /* best exam */
        if ($learnerExams) {
            usort($learnerExams, fn($a,$b)=>$b['total']<=>$a['total']);
            $bestExam    = $learnerExams[0];
            // resort chronologically for charts
            usort($learnerExams, fn($a,$b)=>
                $a['year']==$b['year'] ? ($a['term']==$b['term'] ? strcmp($a['exam_type'],$b['exam_type']) : $a['term']<=>$b['term']) : $a['year']<=>$b['year']
            );

            /* grade-wide subject averages for the same exam session as the learner's LATEST exam,
               used to power the "vs Grade Avg" column on the Subject Scores table */
            $latestForAvg = end($learnerExams);
            reset($learnerExams);
            $lt  = (int)$latestForAvg['term'];
            $let = mysqli_real_escape_string($conn, $latestForAvg['exam_type']);
            $ly  = (int)$latestForAvg['year'];
            foreach ($subjects as $s) {
                $r = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT AVG($s) AS m FROM exam2 WHERE grade='$grade' AND term='$lt' AND exam_type='$let' AND year='$ly'"));
                $gradeSubjAvgForLatest[$s] = round((float)($r['m']??0),1);
            }
        }
    }
}

/* ── Grade-level filter ── */
$gTab     = (int)($_GET['gtab'] ?? 0);
$gGrade   = (int)($_GET['ggrade'] ?? 1);
$gTerm    = $_GET['gterm'] ?? '';
$gExam    = $_GET['gexam'] ?? '';
$gYear    = $_GET['gyear'] ?? '';

$gradeStudents = [];
$gradeSubjMeans= [];
$gradeBands    = ['ee'=>0,'me'=>0,'ae'=>0,'be'=>0];
$gradeTopStudents = [];
$gradeExamLabel = '';

if ($gGrade && $gTerm && $gExam && $gYear) {
    $gt  = (int)$gTerm; $gy = (int)$gYear;
    $gSql = "SELECT * FROM exam2 WHERE grade='$gGrade' AND term='$gt' AND exam_type='".mysqli_real_escape_string($conn,$gExam)."' AND year='$gy'";
    $gRes = mysqli_query($conn, $gSql);
    while ($r = mysqli_fetch_assoc($gRes)) {
        $r['total'] = array_sum(array_map(fn($s)=>(int)($r[$s]??0),$subjects));
        $r['mean']  = round($r['total']/count($subjects),1);
        $gradeStudents[] = $r;
    }
    if ($gradeStudents) {
        usort($gradeStudents, fn($a,$b)=>$b['total']<=>$a['total']);
        $cnt = count($gradeStudents);
        foreach ($subjects as $s) {
            $gradeSubjMeans[$s] = round(array_sum(array_map(fn($r)=>(float)($r[$s]??0),$gradeStudents))/$cnt,1);
        }
        foreach ($gradeStudents as $gs) {
            $m = $gs['mean'];
            if($m>74)$gradeBands['ee']++;elseif($m>49)$gradeBands['me']++;elseif($m>25)$gradeBands['ae']++;else$gradeBands['be']++;
        }
        $gradeTopStudents = array_slice($gradeStudents,0,10);
        $gradeExamLabel = "Grade $gGrade · Term $gTerm · ".examDisp($gExam)." $gYear";
    }
}

/* distinct years in exam2 */
$yearsRes = mysqli_query($conn,"SELECT DISTINCT year FROM exam2 ORDER BY year DESC");
$examYears = [];
while ($y=mysqli_fetch_assoc($yearsRes)) $examYears[]=$y['year'];

/* school timeline: average per year-term-exam */
$schoolTimeline = [];
$stRes = mysqli_query($conn,
    "SELECT year,term,exam_type, AVG((math+eng+kisw+sst+scie+ca+agri+re+pretec)/9) AS m, COUNT(*) AS cnt
     FROM exam2 GROUP BY year,term,exam_type ORDER BY year,term,exam_type");
while ($r=mysqli_fetch_assoc($stRes)) {
    $r['label'] = "T{$r['term']} ".examDisp($r['exam_type'])." {$r['year']}";
    $schoolTimeline[] = $r;
}
?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ░░░ SIDEBAR ░░░ -->
<aside class="sidebar" id="sidebar">
  <button class="sidebar-close" onclick="closeSidebar()"><i class="fa-solid fa-xmark"></i></button>
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
    <div class="brand-text">
      <span class="bn">Kanja School</span>
      <span class="bs">Management System</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Main</div>
    <a href="Hoi.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
    <a href="Progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
    <div class="nav-label">Administration</div>
    <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
    <a href="RegisterTeacher.php"><i class="fa-solid fa-user-plus"></i> Register Teachers</a>
    <div class="nav-label">Academics</div>
    <a href="ViewResults.php"><i class="fa-solid fa-chart-pie"></i> Results</a>
    <a href="UploadResults.php"><i class="fa-solid fa-upload"></i> Upload Results</a>
    <a href="track.php" class="active"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
    <a href="LearningMaterials.php"><i class="fa-solid fa-book-open"></i> Learning Materials</a>
    <div class="nav-label">Finance</div>
    <a href="Fee.php"><i class="fa-solid fa-coins"></i> Finances</a>
  </nav>
  <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
</aside>

<!-- ░░░ MAIN ░░░ -->
<div class="main" id="main">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-l">
      <button class="menu-btn" onclick="openSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-title">Stephen Kanja <span>School</span></div>
    </div>
    <div class="topbar-tag"><i class="fa-solid fa-bullseye"></i> Performance Tracker</div>
  </header>

  <!-- Hero -->
  <div class="page-hero">
    <div class="hero-grid">
      <div>
        <div class="hero-eyebrow">Analytics &amp; Intelligence</div>
        <div class="hero-title">Performance <em>Tracker</em></div>
        <div class="hero-sub">Drill into individual learners, grade cohorts and school-wide academic trends.</div>
      </div>
      <div class="hero-kpis">
        <div class="hkpi">
          <div class="hkpi-val"><?= $totalStudents ?></div>
          <div class="hkpi-lbl">Learners</div>
        </div>
        <div class="hkpi">
          <div class="hkpi-val"><?= (int)($schoolStats['exams']??0) ?></div>
          <div class="hkpi-lbl">Exam Sessions</div>
        </div>
        <div class="hkpi">
          <div class="hkpi-val"><?= round((float)($schoolStats['school_mean']??0),1) ?></div>
          <div class="hkpi-lbl">School Mean</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs-bar">
    <button class="tab-btn <?= (!isset($_GET['tab'])||$_GET['tab']==='individual')?'active':'' ?>" onclick="switchTab('individual',this)">
      <i class="fa-solid fa-user-magnifying-glass"></i> Individual Learner
    </button>
    <button class="tab-btn <?= ($_GET['tab']==='grade')?'active':'' ?>" onclick="switchTab('grade',this)">
      <i class="fa-solid fa-users"></i> Grade Performance
    </button>
    <button class="tab-btn <?= ($_GET['tab']==='school')?'active':'' ?>" onclick="switchTab('school',this)">
      <i class="fa-solid fa-school"></i> School Overview
    </button>
  </div>

  <div class="content">

    <!-- ══════════════════════════════════
         TAB 1: INDIVIDUAL
    ══════════════════════════════════ -->
    <div class="tab-pane <?= (!isset($_GET['tab'])||$_GET['tab']==='individual')?'active':'' ?>" id="tab-individual">

      <!-- Search -->
      <div class="search-card">
        <div class="search-head"><h3>Search Learner</h3></div>
        <div class="search-body">
          <form method="GET" action="" id="searchForm">
            <input type="hidden" name="tab" value="individual">
            <div class="search-form">
              <div class="sf-group">
                <label>Assessment Number</label>
                <input type="text" name="assess" class="sf-control" placeholder="e.g. SKS/2024/001" value="<?= htmlspecialchars($searchAssess) ?>">
              </div>
              <div class="sf-group">
                <label>Learner Name</label>
                <input type="text" name="sname" class="sf-control" placeholder="First or last name…" value="<?= htmlspecialchars($searchName) ?>">
              </div>
              <div class="sf-group">
                <label>Grade (optional)</label>
                <select name="sgrade" class="sf-control">
                  <option value="">— All Grades —</option>
                  <?php for($g=1;$g<=9;$g++){$s=$searchGrade==$g?'selected':'';echo"<option value='$g' $s>Grade $g</option>";}?>
                </select>
              </div>
              <div class="sf-group btn-search-wrap">
                <label>&nbsp;</label>
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
              </div>
            </div>
          </form>

          <?php if ($searchError): ?>
          <div style="margin-top:1rem;padding:12px 16px;background:var(--be-bg);color:var(--be);border-left:4px solid var(--be);border-radius:8px;font-size:13px;display:flex;align-items:center;gap:9px">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($searchError) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($learner && $learnerExams): ?>

      <!-- Profile Banner -->
      <div class="profile-card mb">
        <div class="profile-avatar"><?= strtoupper(substr($learner['firstName'],0,1).substr($learner['surname'],0,1)) ?></div>
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($learner['firstName'].' '.$learner['surname']) ?></div>
          <div class="profile-detail">
            <span><i class="fa-solid fa-id-badge"></i><?= htmlspecialchars($learner['Assesment']) ?></span>
            <span><i class="fa-solid fa-door-open"></i>Grade <?= htmlspecialchars($learner['Grade']) ?></span>
            <?php if (!empty($learner['gender'])): ?>
            <span><i class="fa-solid fa-venus-mars"></i><?= htmlspecialchars($learner['gender']) ?></span>
            <?php endif; ?>
            <span><i class="fa-solid fa-file-lines"></i><?= count($learnerExams) ?> exams recorded</span>
          </div>
        </div>
        <?php
        $latestExam = end($learnerExams);
        reset($learnerExams);
        $overallMean = count($learnerExams) ? round(array_sum(array_map(fn($e)=>$e['mean'],$learnerExams))/count($learnerExams),1) : 0;
        $bestRank = min(array_column($learnerExams,'rank'));
        ?>
        <div class="profile-badges">
          <div class="pb">
            <div>
              <div class="pb-label">Overall Avg</div>
              <div class="pb-val"><?= $overallMean ?></div>
            </div>
            <span class="aw <?= bandClass($overallMean) ?>"><?= bandLabel($overallMean) ?></span>
          </div>
          <div class="pb">
            <div>
              <div class="pb-label">Best Rank</div>
              <div class="pb-val">#<?= $bestRank ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- KPIs -->
      <div class="kpi-row">
        <?php
        $latE = $latestExam;
        $latMean = $latE['mean'];
        $firstMean = $learnerExams[0]['mean'];
        $delta = round($latMean - $firstMean, 1);
        ?>
        <div class="kpi k-dark" style="animation-delay:0.04s">
          <div class="kpi-icon ic-gold"><i class="fa-solid fa-list-ol"></i></div>
          <div class="kpi-lbl">Latest Rank</div>
          <div class="kpi-val">#<?= $latE['rank'] ?></div>
          <div class="kpi-sub">of <?= $latE['class_size'] ?> in class</div>
        </div>
        <div class="kpi k-<?= $latMean>74?'ee':($latMean>49?'me':($latMean>25?'ae':'be')) ?>" style="animation-delay:0.08s">
          <div class="kpi-lbl">Latest Mean</div>
          <div class="kpi-val sm"><?= $latMean ?><small style="font-size:1rem">/100</small></div>
          <div class="kpi-sub"><span class="aw <?= bandClass($latMean) ?>"><?= bandLabel($latMean) ?></span></div>
        </div>
        <div class="kpi k-gold" style="animation-delay:0.12s">
          <div class="kpi-lbl">Latest Total</div>
          <div class="kpi-val sm"><?= $latE['total'] ?><small style="font-size:1rem">/900</small></div>
          <div class="kpi-sub">across 9 subjects</div>
        </div>
        <div class="kpi k-<?= $delta>=0?'ee':'be' ?>" style="animation-delay:0.16s">
          <div class="kpi-lbl">Improvement</div>
          <div class="kpi-val sm" style="color:<?= $delta>=0?'var(--ee)':'var(--be)' ?>"><?= $delta>=0?'+':'' ?><?= $delta ?></div>
          <div class="kpi-sub">since first recorded exam</div>
        </div>
      </div>

      <!-- Charts row -->
      <div class="g2 mb">
        <div class="card">
          <div class="card-head"><h3>Score Trend Across Exams</h3></div>
          <div class="card-body"><div class="ch ch-260"><canvas id="learnerTrendChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-head"><h3>Latest Exam — Subject Breakdown</h3></div>
          <div class="card-body"><div class="ch ch-260"><canvas id="learnerSubjectChart"></canvas></div></div>
        </div>
      </div>

      <!-- Rank history strip -->
      <div class="sec-row mt"><h2><i class="fa-solid fa-trophy"></i>Class Rank History</h2></div>
      <div class="rank-history mb">
        <?php foreach ($learnerExams as $ex): ?>
        <div class="rh-item">
          <div class="rh-exam">T<?= $ex['term'] ?> <?= examDisp($ex['exam_type']) ?><br><?= $ex['year'] ?></div>
          <div class="rh-rank" style="color:<?= $ex['rank']===1?'var(--gold)':($ex['rank']<=3?'var(--ee)':'var(--text)') ?>">#<?= $ex['rank'] ?></div>
          <div class="rh-total"><?= $ex['total'] ?>/900</div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Subject performance detail — vertical table -->
      <div class="card perf-table-card">
        <div class="card-head"><h3>Subject Scores — T<?= $latestExam['term'] ?> <?= examDisp($latestExam['exam_type']) ?> <?= $latestExam['year'] ?></h3></div>
        <div class="tbl-scroll">
          <table class="perf-table">
            <thead>
              <tr>
                <th>Subject</th><th>Score</th><th>Out of</th><th>%</th><th>Band</th><th>Progress</th><th>vs Grade Avg</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($subjects as $idx => $s):
                  $score = (int)($latestExam[$s] ?? 0);
                  $col   = bandColor($score);
                  $gAvg  = $gradeSubjAvgForLatest[$s] ?? null;
                  $diff  = $gAvg !== null ? round($score - $gAvg, 1) : null;
                  $vs    = vsAvgFmt($diff);
              ?>
              <tr>
                <td class="pt-subject"><?= $subjectNames[$idx] ?></td>
                <td class="pt-score"><?= $score ?></td>
                <td class="pt-outof">100</td>
                <td class="pt-pct"><?= $score ?>%</td>
                <td><span class="aw <?= bandClass($score) ?>"><?= bandLabel($score) ?></span></td>
                <td><div class="pt-progress-track"><div class="pt-progress-fill" style="width:<?= $score ?>%;background:<?= $col ?>"></div></div></td>
                <td class="<?= $vs['cls'] ?>"><?= $vs['txt'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Full exam history table -->
      <div class="sec-row"><h2><i class="fa-solid fa-table-list"></i>Full Exam History</h2></div>
      <div class="card mb">
        <div class="tbl-scroll">
          <table class="t">
            <thead>
              <tr>
                <th>Exam</th><th>Year</th>
                <?php foreach($short as $sh) echo "<th>$sh</th>"; ?>
                <th>Total</th><th>Mean</th><th>Rank</th><th>Band</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($learnerExams as $ex):
                $exMean=$ex['mean'];
              ?>
              <tr>
                <td class="strong">T<?=$ex['term']?> <?=examDisp($ex['exam_type'])?></td>
                <td><?=$ex['year']?></td>
                <?php foreach($subjects as $s): $sv=(int)($ex[$s]??0); ?>
                <td><span class="aw <?=bandClass($sv)?>"><?=$sv?></span></td>
                <?php endforeach; ?>
                <td class="strong"><?=$ex['total']?></td>
                <td><?=$exMean?></td>
                <td class="strong <?= $ex['rank']===1?'rank-gold':($ex['rank']===2?'rank-silver':($ex['rank']===3?'rank-bronze':'')) ?>">#<?=$ex['rank']?>/<?=$ex['class_size']?></td>
                <td><span class="aw <?=bandClass($exMean)?>"><?=bandLabel($exMean)?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php elseif (!$searchAssess && !$searchName): ?>
      <div class="card">
        <div class="empty-box">
          <div class="eico"><i class="fa-solid fa-user-magnifying-glass"></i></div>
          <h3>Search for a Learner</h3>
          <p>Enter an assessment number or learner name above to view their full performance profile.</p>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════
         TAB 2: GRADE
    ══════════════════════════════════ -->
    <div class="tab-pane <?= ($_GET['tab']==='grade')?'active':'' ?>" id="tab-grade">

      <!-- Grade filter -->
      <div class="card mb">
        <div class="card-head"><h3>Select Grade &amp; Exam</h3></div>
        <div class="card-body">
          <form method="GET" action="">
            <input type="hidden" name="tab" value="grade">
            <div class="search-form">
              <div class="sf-group">
                <label>Grade</label>
                <select name="ggrade" class="sf-control">
                  <?php for($g=1;$g<=9;$g++){$s=$gGrade==$g?'selected':'';echo"<option value='$g' $s>Grade $g</option>";}?>
                </select>
              </div>
              <div class="sf-group">
                <label>Term</label>
                <select name="gterm" class="sf-control">
                  <option value="">— Term —</option>
                  <?php foreach([1,2,3] as $t){$s=$gTerm==$t?'selected':'';echo"<option value='$t' $s>Term $t</option>";}?>
                </select>
              </div>
              <div class="sf-group">
                <label>Exam Type</label>
                <select name="gexam" class="sf-control">
                  <option value="">— Exam —</option>
                  <?php foreach(['opener'=>'Opener','midterm'=>'Mid-Term','endterm'=>'End Term'] as $v=>$l){$s=$gExam===$v?'selected':'';echo"<option value='$v' $s>$l</option>";}?>
                </select>
              </div>
              <div class="sf-group">
                <label>Year</label>
                <select name="gyear" class="sf-control">
                  <option value="">— Year —</option>
                  <?php foreach($examYears as $y){$s=$gYear==$y?'selected':'';echo"<option value='$y' $s>$y</option>";}?>
                </select>
              </div>
              <div class="sf-group btn-search-wrap">
                <label>&nbsp;</label>
                <button type="submit" class="btn-search"><i class="fa-solid fa-filter"></i> Load</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <?php if ($gradeStudents): ?>
      <?php
        $gcnt   = count($gradeStudents);
        $gMean  = round(array_sum(array_map(fn($s)=>$s['mean'],$gradeStudents))/$gcnt,1);
        $gTotal = round(array_sum(array_map(fn($s)=>$s['total'],$gradeStudents))/$gcnt,1);
        $gBandTotal = array_sum($gradeBands);
      ?>

      <!-- Grade KPIs -->
      <div class="kpi-row">
        <div class="kpi k-dark" style="animation-delay:0.04s">
          <div class="kpi-icon ic-gold"><i class="fa-solid fa-users"></i></div>
          <div class="kpi-lbl">Students Tested</div>
          <div class="kpi-val"><?= $gcnt ?></div>
          <div class="kpi-sub"><?= $gradeExamLabel ?></div>
        </div>
        <div class="kpi k-<?= bandClass($gMean) ?>" style="animation-delay:0.08s">
          <div class="kpi-lbl">Class Mean</div>
          <div class="kpi-val sm"><?= $gMean ?></div>
          <div class="kpi-sub"><span class="aw <?= bandClass($gMean) ?>"><?= bandLabel($gMean) ?></span></div>
        </div>
        <div class="kpi k-ee" style="animation-delay:0.12s">
          <div class="kpi-lbl">Exceeding (E.E)</div>
          <div class="kpi-val"><?= $gradeBands['ee'] ?></div>
          <div class="kpi-sub"><?= $gBandTotal?round($gradeBands['ee']/$gBandTotal*100):0 ?>% of class</div>
        </div>
        <div class="kpi k-be" style="animation-delay:0.16s">
          <div class="kpi-lbl">Below (B.E)</div>
          <div class="kpi-val"><?= $gradeBands['be'] ?></div>
          <div class="kpi-sub"><?= $gBandTotal?round($gradeBands['be']/$gBandTotal*100):0 ?>% need support</div>
        </div>
      </div>

      <!-- Subject means detail — vertical table (replaces the old bar chart) -->
      <div class="card perf-table-card">
        <div class="card-head"><h3>Subject Scores — <?= htmlspecialchars($gradeExamLabel) ?></h3></div>
        <div class="tbl-scroll">
          <table class="perf-table">
            <thead>
              <tr>
                <th>Subject</th><th>Class Mean</th><th>Out of</th><th>%</th><th>Band</th><th>Progress</th><th>vs School Avg</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($subjects as $idx => $s):
                  $gm    = $gradeSubjMeans[$s] ?? 0;
                  $col   = bandColor($gm);
                  $sAvg  = $schoolSubjMeans[$s] ?? null;
                  $diff  = $sAvg !== null ? round($gm - $sAvg, 1) : null;
                  $vs    = vsAvgFmt($diff);
              ?>
              <tr>
                <td class="pt-subject"><?= $subjectNames[$idx] ?></td>
                <td class="pt-score"><?= $gm ?></td>
                <td class="pt-outof">100</td>
                <td class="pt-pct"><?= $gm ?>%</td>
                <td><span class="aw <?= bandClass($gm) ?>"><?= bandLabel($gm) ?></span></td>
                <td><div class="pt-progress-track"><div class="pt-progress-fill" style="width:<?= min(100,$gm) ?>%;background:<?= $col ?>"></div></div></td>
                <td class="<?= $vs['cls'] ?>"><?= $vs['txt'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="g2 mb">
        <!-- Band distribution -->
        <div class="card">
          <div class="card-head"><h3>Achievement Band Distribution</h3></div>
          <div class="card-body">
            <?php
            $bands = [
              ['Exceeding Expectations (E.E)', $gradeBands['ee'], 'bf-ee', 'var(--ee)'],
              ['Meeting Expectations (M.E)',   $gradeBands['me'], 'bf-me', 'var(--me)'],
              ['Approaching Expectations (A.E)',$gradeBands['ae'],'bf-ae', 'var(--ae)'],
              ['Below Expectations (B.E)',      $gradeBands['be'], 'bf-be', 'var(--be)'],
            ];
            foreach ($bands as $b):
              $pct = $gBandTotal ? round($b[1]/$gBandTotal*100) : 0;
            ?>
            <div class="band-row">
              <div class="band-label" style="color:<?= $b[3] ?>"><?= $b[0] ?></div>
              <div class="band-track"><div class="band-fill <?= $b[2] ?>" style="width:<?= $pct ?>%"></div></div>
              <div class="band-count"><?= $b[1] ?> (<?= $pct ?>%)</div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- Doughnut -->
        <div class="card">
          <div class="card-head"><h3>Band Breakdown (Doughnut)</h3></div>
          <div class="card-body"><div class="ch ch-260"><canvas id="gradeDoughnut"></canvas></div></div>
        </div>
      </div>

      <!-- Top students -->
      <div class="sec-row"><h2><i class="fa-solid fa-ranking-star"></i>Top 10 Students This Exam</h2></div>
      <div class="card mb">
          <div class="tbl-scroll">
            <table class="t">
              <thead><tr><th>#</th><th>Assessment</th><th>Name</th><th>Total</th><th>Mean</th><th>Band</th></tr></thead>
              <tbody>
                <?php foreach($gradeTopStudents as $idx=>$st):
                  $rc = $idx===0?'rank-gold':($idx===1?'rank-silver':($idx===2?'rank-bronze':''));
                ?>
                <tr>
                  <td class="rank <?= $rc ?>"><?=$idx+1?></td>
                  <td style="font-size:11px;color:var(--text-3)"><?=htmlspecialchars($st['Assesment']??'')?></td>
                  <td class="strong"><?=htmlspecialchars(($st['firstName']??'').' '.($st['lastName']??$st['surname']??''))?></td>
                  <td><?=$st['total']?></td>
                  <td><?=$st['mean']?></td>
                  <td><span class="aw <?=bandClass($st['mean'])?>"><?=bandLabel($st['mean'])?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
      </div>

      <?php else: ?>
      <div class="card">
        <div class="empty-box">
          <div class="eico"><i class="fa-solid fa-users"></i></div>
          <h3>Select a Grade &amp; Exam</h3>
          <p>Choose a grade, term, exam type and year above to load grade-level analytics.</p>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════
         TAB 3: SCHOOL OVERVIEW
    ══════════════════════════════════ -->
    <div class="tab-pane <?= ($_GET['tab']==='school')?'active':'' ?>" id="tab-school">

      <!-- School KPIs -->
      <?php
        $sTot  = $bandTotal;
        $sMean = round((float)($schoolStats['school_mean']??0),1);
        $eeP   = $sTot?round($bandAll['ee']/$sTot*100):0;
        $beP   = $sTot?round($bandAll['be']/$sTot*100):0;
      ?>
      <div class="kpi-row">
        <div class="kpi k-dark" style="animation-delay:0.04s">
          <div class="kpi-icon ic-gold"><i class="fa-solid fa-school"></i></div>
          <div class="kpi-lbl">Total Students</div>
          <div class="kpi-val"><?= $totalStudents ?></div>
          <div class="kpi-sub">9 grades enrolled</div>
        </div>
        <div class="kpi k-<?= bandClass($sMean) ?>" style="animation-delay:0.08s">
          <div class="kpi-lbl">School Mean</div>
          <div class="kpi-val sm"><?= $sMean ?></div>
          <div class="kpi-sub"><span class="aw <?= bandClass($sMean) ?>"><?= bandLabel($sMean) ?></span> overall</div>
        </div>
        <div class="kpi k-ee" style="animation-delay:0.12s">
          <div class="kpi-icon ic-ee"><i class="fa-solid fa-star"></i></div>
          <div class="kpi-lbl">Exceeding (E.E)</div>
          <div class="kpi-val"><?= $bandAll['ee'] ?></div>
          <div class="kpi-sub"><?= $eeP ?>% of all records</div>
        </div>
        <div class="kpi k-be" style="animation-delay:0.16s">
          <div class="kpi-lbl">Below (B.E)</div>
          <div class="kpi-val"><?= $bandAll['be'] ?></div>
          <div class="kpi-sub"><?= $beP ?>% need intervention</div>
        </div>
      </div>

      <!-- School timeline + grade bar -->
      <div class="g2 mb">
        <div class="card" style="grid-column:1/-1">
          <div class="card-head"><h3>School Mean Trend — All Exam Sessions</h3></div>
          <div class="card-body"><div class="ch ch-300"><canvas id="schoolTimelineChart"></canvas></div></div>
        </div>
      </div>

      <div class="g2 mb">
        <div class="card">
          <div class="card-head"><h3>Latest Mean Score by Grade</h3></div>
          <div class="card-body"><div class="ch ch-300"><canvas id="schoolGradeChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-head"><h3>School-Wide Achievement Bands</h3></div>
          <div class="card-body">
            <div class="ch ch-220"><canvas id="schoolBandDoughnut"></canvas></div>
            <div style="margin-top:1rem">
              <?php
              $schoolBands = [
                ['Exceeding (E.E)', $bandAll['ee'], 'bf-ee', 'var(--ee)'],
                ['Meeting (M.E)',   $bandAll['me'], 'bf-me', 'var(--me)'],
                ['Approaching (A.E)',$bandAll['ae'],'bf-ae', 'var(--ae)'],
                ['Below (B.E)',      $bandAll['be'], 'bf-be', 'var(--be)'],
              ];
              foreach($schoolBands as $b):
                $pct = $sTot?round($b[1]/$sTot*100):0;
              ?>
              <div class="band-row" style="margin-bottom:8px">
                <div class="band-label" style="color:<?=$b[3]?>;width:130px"><?=$b[0]?></div>
                <div class="band-track"><div class="band-fill <?=$b[2]?>" style="width:<?=$pct?>%"></div></div>
                <div class="band-count"><?=$b[1]?> (<?=$pct?>%)</div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Subject means school-wide -->
      <div class="sec-row mt"><h2><i class="fa-solid fa-chart-bar"></i>School-Wide Subject Means</h2></div>
      <div class="card mb">
        <div class="card-body"><div class="ch ch-300"><canvas id="schoolSubjChart"></canvas></div></div>
      </div>

      <!-- Grade ranking table -->
      <div class="sec-row"><h2><i class="fa-solid fa-ranking-star"></i>Grade Performance Rankings (Latest)</h2></div>
      <div class="card mb">
        <div class="tbl-scroll">
          <table class="t">
            <thead>
              <tr><th>Rank</th><th>Grade</th><th>Mean /100</th><th>Band</th><th>Students Tested</th><th>Last Exam</th></tr>
            </thead>
            <tbody>
              <?php foreach($gradeRankData as $idx=>$gr):
                $rc=$idx===0?'rank-gold':($idx===1?'rank-silver':($idx===2?'rank-bronze':''));
                $latG = $latestGrade[$gr['grade']] ?? null;
                $cnt  = $latG['cnt'] ?? '—';
                $lastExam = $latG ? "T{$latG['term']} ".examDisp($latG['exam_type'])." {$latG['year']}" : '—';
              ?>
              <tr>
                <td class="rank <?=$rc?>"><?=$idx+1?></td>
                <td class="strong">Grade <?=$gr['grade']?></td>
                <td><?=$gr['mean']?></td>
                <td><span class="aw <?=bandClass($gr['mean'])?>"><?=bandLabel($gr['mean'])?></span></td>
                <td><?=$cnt?></td>
                <td style="font-size:11.5px;color:var(--text-3)"><?=$lastExam?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- All exams overview table -->
      <div class="sec-row"><h2><i class="fa-solid fa-calendar-days"></i>All Exam Sessions Summary</h2></div>
      <div class="card mb">
        <div class="tbl-scroll">
          <table class="t">
            <thead><tr><th>Exam Session</th><th>Year</th><th>Students</th><th>School Mean</th><th>Band</th></tr></thead>
            <tbody>
              <?php foreach(array_reverse($schoolTimeline) as $stl):
                $sm = round((float)$stl['m'],1);
              ?>
              <tr>
                <td class="strong"><?=htmlspecialchars($stl['label'])?></td>
                <td><?=$stl['year']?></td>
                <td><?=$stl['cnt']?></td>
                <td><?=$sm?></td>
                <td><span class="aw <?=bandClass($sm)?>"><?=bandLabel($sm)?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /tab-school -->

  </div><!-- .content -->

  <div class="footer">&copy; Designed by Kelvin Mutinda 2026 &nbsp;·&nbsp; Stephen Kanja School Performance Tracker</div>
</div>

<!-- ════════════════════════════
     JAVASCRIPT
════════════════════════════ -->
<script>
/* ── Sidebar ── */
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('active'); document.body.style.overflow='hidden'; }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('active'); document.body.style.overflow=''; }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeSidebar(); });

/* ── Tabs ── */
function switchTab(id, btn) {
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
  btn.classList.add('active');
  // update URL without reload
  const url = new URL(window.location);
  url.searchParams.set('tab', id);
  window.history.replaceState(null,'',url);
}

/* ── Chart defaults ── */
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color       = '#888';

const GOLD='#f0c040', BLACK='#0d0d0d', EE='#059669', ME='#2563eb', AE='#d97706', BE='#dc2626';

/* ════ INDIVIDUAL TAB CHARTS ════ */
<?php if ($learner && $learnerExams): ?>
(function(){
  const examLabels = <?= json_encode(array_map(fn($e)=>"T{$e['term']} ".examDisp($e['exam_type'])." {$e['year']}",$learnerExams)) ?>;
  const means      = <?= json_encode(array_map(fn($e)=>$e['mean'],$learnerExams)) ?>;
  const totals     = <?= json_encode(array_map(fn($e)=>$e['total'],$learnerExams)) ?>;
  const ranks      = <?= json_encode(array_map(fn($e)=>$e['rank'],$learnerExams)) ?>;

  /* Trend chart */
  new Chart(document.getElementById('learnerTrendChart'),{
    type:'line',
    data:{
      labels:examLabels,
      datasets:[
        {
          label:'Mean /100',
          data:means,
          borderColor:GOLD, backgroundColor:'rgba(240,192,64,0.08)',
          borderWidth:3, pointRadius:5, pointHoverRadius:8,
          tension:0.38, fill:true, yAxisID:'y'
        },
        {
          label:'Class Rank',
          data:ranks,
          borderColor:'rgba(37,99,235,0.7)',
          backgroundColor:'transparent',
          borderWidth:2, borderDash:[5,4],
          pointRadius:4, tension:0.3,
          yAxisID:'y2'
        }
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{mode:'index',intersect:false},
      plugins:{legend:{position:'bottom',labels:{padding:14,boxWidth:12,borderRadius:4}}},
      scales:{
        y :{position:'left',  min:0,max:100,grid:{color:'rgba(0,0,0,0.04)'},title:{display:true,text:'Mean /100'}},
        y2:{position:'right', reverse:true, grid:{drawOnChartArea:false},title:{display:true,text:'Rank'}}
      }
    }
  });

  /* Subject radar-ish bar */
  const latest = <?= json_encode(array_map(fn($s)=>(int)($latestExam[$s]??0),$subjects)) ?>;
  new Chart(document.getElementById('learnerSubjectChart'),{
    type:'bar',
    data:{
      labels: <?= json_encode($short) ?>,
      datasets:[{
        label:'Score',
        data:latest,
        backgroundColor:latest.map(v=>v>74?'rgba(5,150,105,0.8)':v>49?'rgba(37,99,235,0.8)':v>25?'rgba(217,119,6,0.8)':'rgba(220,38,38,0.8)'),
        borderRadius:5, borderSkipped:false
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false}},
        y:{min:0,max:100,grid:{color:'rgba(0,0,0,0.04)'}}
      }
    }
  });
})();
<?php endif; ?>

/* ════ GRADE TAB CHARTS ════ */
<?php if ($gradeStudents): ?>
(function(){
  const bands = <?= json_encode(array_values($gradeBands)) ?>;

  new Chart(document.getElementById('gradeDoughnut'),{
    type:'doughnut',
    data:{
      labels:['Exceeding (E.E)','Meeting (M.E)','Approaching (A.E)','Below (B.E)'],
      datasets:[{
        data:bands,
        backgroundColor:['rgba(5,150,105,0.82)','rgba(37,99,235,0.82)','rgba(217,119,6,0.82)','rgba(220,38,38,0.82)'],
        borderWidth:2, borderColor:'#fff', hoverOffset:8
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,cutout:'62%',
      plugins:{legend:{position:'bottom',labels:{padding:14,boxWidth:12,borderRadius:4}}}
    }
  });
})();
<?php endif; ?>

/* ════ SCHOOL TAB CHARTS ════ */
(function(){
  /* Timeline */
  const tlLabels = <?= json_encode(array_column($schoolTimeline,'label')) ?>;
  const tlMeans  = <?= json_encode(array_map(fn($r)=>round((float)$r['m'],1),$schoolTimeline)) ?>;

  if (document.getElementById('schoolTimelineChart')) {
    new Chart(document.getElementById('schoolTimelineChart'),{
      type:'line',
      data:{
        labels:tlLabels,
        datasets:[{
          label:'School Mean /100',
          data:tlMeans,
          borderColor:GOLD, backgroundColor:'rgba(240,192,64,0.07)',
          borderWidth:3, pointRadius:5, pointHoverRadius:8,
          tension:0.38, fill:true
        }]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{
          x:{grid:{color:'rgba(0,0,0,0.04)'},ticks:{maxRotation:40,minRotation:25}},
          y:{min:0,max:100,grid:{color:'rgba(0,0,0,0.04)'}}
        }
      }
    });
  }

  /* Grade bar */
  const gradeLabels = <?= json_encode(array_map(fn($g)=>"Grade {$g['grade']}",$gradeRankData)) ?>;
  const gradeMeans  = <?= json_encode(array_column($gradeRankData,'mean')) ?>;
  if (document.getElementById('schoolGradeChart')) {
    new Chart(document.getElementById('schoolGradeChart'),{
      type:'bar',
      data:{
        labels:gradeLabels,
        datasets:[{
          label:'Mean /100',
          data:gradeMeans,
          backgroundColor:gradeMeans.map(v=>v>74?'rgba(5,150,105,0.82)':v>49?'rgba(37,99,235,0.82)':v>25?'rgba(217,119,6,0.82)':'rgba(220,38,38,0.82)'),
          borderRadius:6, borderSkipped:false
        }]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{x:{grid:{display:false}},y:{min:0,max:100,grid:{color:'rgba(0,0,0,0.04)'}}}
      }
    });
  }

  /* School band doughnut */
  const bandVals = <?= json_encode(array_values($bandAll)) ?>;
  if (document.getElementById('schoolBandDoughnut')) {
    new Chart(document.getElementById('schoolBandDoughnut'),{
      type:'doughnut',
      data:{
        labels:['E.E','M.E','A.E','B.E'],
        datasets:[{
          data:bandVals,
          backgroundColor:['rgba(5,150,105,0.82)','rgba(37,99,235,0.82)','rgba(217,119,6,0.82)','rgba(220,38,38,0.82)'],
          borderWidth:2, borderColor:'#fff', hoverOffset:6
        }]
      },
      options:{
        responsive:true,maintainAspectRatio:false,cutout:'62%',
        plugins:{legend:{position:'bottom',labels:{padding:12,boxWidth:10,borderRadius:3}}}
      }
    });
  }

  /* School subject means */
  const schoolSubjMeans = <?= json_encode(array_values($schoolSubjMeans)) ?>;
  if (document.getElementById('schoolSubjChart')) {
    new Chart(document.getElementById('schoolSubjChart'),{
      type:'bar',
      data:{
        labels: <?= json_encode($short) ?>,
        datasets:[{
          label:'School Mean /100',
          data:schoolSubjMeans,
          backgroundColor:schoolSubjMeans.map(v=>v>74?'rgba(5,150,105,0.82)':v>49?'rgba(37,99,235,0.82)':v>25?'rgba(217,119,6,0.82)':'rgba(220,38,38,0.82)'),
          borderRadius:6, borderSkipped:false
        }]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{
          x:{grid:{display:false}},
          y:{min:0,max:100,grid:{color:'rgba(0,0,0,0.04)'},
             title:{display:true,text:'Average Score /100'}}
        }
      }
    });
  }
})();
</script>

</body>
</html>
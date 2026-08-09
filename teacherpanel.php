<?php
include 'logger.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Teacher Panel — Kanja School</title>
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

    .header-user {
      display: flex;
      align-items: center;
      gap: 9px;
      flex-shrink: 0;
    }

    .user-avatar {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: var(--mid);
      border: 2px solid var(--gold);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px;
      color: var(--gold);
    }

    .user-label {
      font-size: 12px;
      color: #666;
      letter-spacing: 0.04em;
    }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 6px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      font-weight: 500;
      color: #888;
      background: transparent;
      border: 1px solid var(--mid);
      border-radius: var(--radius-md);
      padding: 5px 10px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.18s;
    }
    .btn-logout:hover { border-color: var(--gold); color: var(--gold); }

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
      gap: 2rem;
    }

    /* ── Welcome banner ── */
    .welcome-banner {
      background: var(--black);
      border-radius: var(--radius-lg);
      padding: 1.8rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      flex-wrap: wrap;
      position: relative;
      overflow: hidden;
      animation: fadeUp 0.4s ease both;
    }

    /* subtle gold grid pattern */
    .welcome-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(240,192,64,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(240,192,64,0.04) 1px, transparent 1px);
      background-size: 32px 32px;
    }

    /* gold accent bar */
    .welcome-banner::after {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 4px;
      background: var(--gold);
      border-radius: 0 4px 4px 0;
    }

    .welcome-text { position: relative; }

    .welcome-eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--gold-dim);
      margin-bottom: 6px;
    }

    .welcome-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      letter-spacing: 0.1em;
      color: #fff;
      line-height: 1;
      margin-bottom: 6px;
    }

    .welcome-title span { color: var(--gold); }

    .welcome-sub {
      font-size: 13px;
      color: #666;
    }

    .welcome-stats {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      position: relative;
    }

    .w-stat {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
    }

    .w-stat .val {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      color: var(--gold);
      line-height: 1;
    }

    .w-stat .lbl {
      font-size: 10px;
      color: #555;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 600;
      text-align: center;
    }

    .w-divider {
      width: 1px;
      background: var(--mid);
      align-self: stretch;
    }

    /* ── Section heading ── */
    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .section-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.5rem;
      letter-spacing: 0.1em;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-title::before {
      content: '◆';
      color: var(--gold);
      font-size: 0.75rem;
    }

    /* ── Quick nav cards ── */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
    }

    .nav-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.4rem 1.2rem 1.2rem;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 0.9rem;
      text-decoration: none;
      color: var(--text-primary);
      position: relative;
      overflow: hidden;
      transition: transform 0.22s, box-shadow 0.22s, border-color 0.22s;
      animation: fadeUp 0.4s ease both;
    }

    .nav-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 14px 36px rgba(0,0,0,0.1);
      border-color: var(--gold);
    }

    /* top accent bar */
    .nav-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--black);
      transition: background 0.22s;
    }

    .nav-card:hover::before { background: var(--gold); }

    /* decorative circle */
    .nav-card::after {
      content: '';
      position: absolute;
      bottom: -20px; right: -20px;
      width: 80px; height: 80px;
      border-radius: 50%;
      background: rgba(0,0,0,0.02);
      transition: background 0.22s;
    }

    .nav-card:hover::after { background: rgba(240,192,64,0.04); }

    .card-icon-wrap {
      width: 42px; height: 42px;
      border-radius: 10px;
      background: var(--black);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      transition: background 0.22s;
    }

    .nav-card:hover .card-icon-wrap { background: var(--gold); }

    .card-icon-wrap i {
      font-size: 17px;
      color: var(--gold);
      transition: color 0.22s;
    }

    .nav-card:hover .card-icon-wrap i { color: var(--black); }

    .card-content { display: flex; flex-direction: column; gap: 3px; }

    .card-title {
      font-size: 14px;
      font-weight: 500;
      color: var(--text-primary);
      line-height: 1.2;
    }

    .card-desc {
      font-size: 11.5px;
      color: var(--text-tertiary);
      line-height: 1.4;
    }

    .card-arrow {
      position: absolute;
      bottom: 1rem; right: 1rem;
      font-size: 11px;
      color: var(--text-tertiary);
      opacity: 0;
      transform: translateX(-4px);
      transition: opacity 0.2s, transform 0.2s;
    }

    .nav-card:hover .card-arrow { opacity: 1; transform: translateX(0); }

    /* stagger animation */
    .nav-card:nth-child(1)  { animation-delay: 0.04s; }
    .nav-card:nth-child(2)  { animation-delay: 0.08s; }
    .nav-card:nth-child(3)  { animation-delay: 0.12s; }
    .nav-card:nth-child(4)  { animation-delay: 0.16s; }
    .nav-card:nth-child(5)  { animation-delay: 0.20s; }
    .nav-card:nth-child(6)  { animation-delay: 0.24s; }
    .nav-card:nth-child(7)  { animation-delay: 0.28s; }
    .nav-card:nth-child(8)  { animation-delay: 0.32s; }
    .nav-card:nth-child(9)  { animation-delay: 0.36s; }

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
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ══════════ RESPONSIVE ══════════ */
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
      .user-label { display: none; }
      .main { padding: 1.25rem 1rem 3rem; }
      .welcome-banner { padding: 1.4rem 1.2rem; }
      .welcome-title { font-size: 1.6rem; }
      .cards-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.85rem; }
    }

    @media (max-width: 480px) {
      .school-name { font-size: 1.1rem; }
      .welcome-stats { gap: 1rem; }
      .w-stat .val { font-size: 1.6rem; }
      .cards-grid { grid-template-columns: repeat(2, 1fr); }
      .btn-logout span { display: none; }
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
  <div class="header-user">
    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
    <span class="user-label">Teacher Panel</span>
    <a href="logout.php" class="btn-logout">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Logout</span>
    </a>
  </div>
</header>

<div class="layout">

  <!-- ════════════ SIDEBAR ════════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <p class="nav-label">Main</p>
      <nav>
        <a href="teacherpanel.php" class="active"><i class="fa-solid fa-house"></i> Home</a>
        <a href="Students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
        <a href="Progress.php"><i class="fa-solid fa-chart-line"></i> Progress Records</a>
      </nav>

      <p class="nav-label">Academics</p>
      <nav>
        <a href="ViewResults.php"><i class="fa-solid fa-chart-pie"></i> Results</a>
        <a href="UploadResults.php"><i class="fa-solid fa-upload"></i> Upload Results</a>
        <a href="track.php"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
        <a href="LearningMaterials.php"><i class="fa-solid fa-book"></i> Learning Materials</a>
      </nav>

      <p class="nav-label">Administration</p>
      <nav>
        <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
      </nav>
    </div>
    <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
  </aside>

  <!-- ════════════ MAIN ════════════ -->
  <main class="main">

    <!-- Welcome banner -->
    <div class="welcome-banner">
      <div class="welcome-text">
        <p class="welcome-eyebrow">Teacher Dashboard</p>
        <h1 class="welcome-title">Welcome Back <span>✦</span></h1>
        <p class="welcome-sub">Stephen Kanja Primary & Junior School — Academic Portal</p>
      </div>
      <?php
        include 'conn.php';
        $students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM Student WHERE role='user'"))['c'] ?? 0;
        $teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM Teachers"))['c'] ?? 0;
        $classes  = 9;
      ?>
      <div class="welcome-stats">
        <div class="w-stat">
          <span class="val"><?= $students ?></span>
          <span class="lbl">Learners</span>
        </div>
        <div class="w-divider"></div>
        <div class="w-stat">
          <span class="val"><?= $teachers ?></span>
          <span class="lbl">Teachers</span>
        </div>
        <div class="w-divider"></div>
        <div class="w-stat">
          <span class="val"><?= $classes ?></span>
          <span class="lbl">Classes</span>
        </div>
      </div>
    </div>

    <!-- Quick nav -->
    <div>
      <div class="section-head">
        <h2 class="section-title">Quick Navigation</h2>
      </div>
    </div>

    <div class="cards-grid">

      <a href="teacherpanel.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-gauge"></i></div>
        <div class="card-content">
          <span class="card-title">Dashboard</span>
          <span class="card-desc">Overview & statistics</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="Students.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="card-content">
          <span class="card-title">Students</span>
          <span class="card-desc">Browse enrolled learners</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="UploadResults.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-file-circle-check"></i></div>
        <div class="card-content">
          <span class="card-title">Upload Results</span>
          <span class="card-desc">Submit exam scores</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="RegisterStudent.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-user-plus"></i></div>
        <div class="card-content">
          <span class="card-title">Register Learner</span>
          <span class="card-desc">Enroll a new student</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="ViewResults.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-chart-pie"></i></div>
        <div class="card-content">
          <span class="card-title">Results</span>
          <span class="card-desc">View exam results</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="ViewResults.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-book-open-reader"></i></div>
        <div class="card-content">
          <span class="card-title">Academic Journey</span>
          <span class="card-desc">Full academic history</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="LearningMaterials.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-book"></i></div>
        <div class="card-content">
          <span class="card-title">Learning Materials</span>
          <span class="card-desc">Resources & documents</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="Progress.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-chart-line"></i></div>
        <div class="card-content">
          <span class="card-title">Progress Records</span>
          <span class="card-desc">Track student progress</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

      <a href="track.php" class="nav-card">
        <div class="card-icon-wrap"><i class="fa-solid fa-bullseye"></i></div>
        <div class="card-content">
          <span class="card-title">Track Learners</span>
          <span class="card-desc">Monitor performance</span>
        </div>
        <i class="fa-solid fa-arrow-right card-arrow"></i>
      </a>

    </div>

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

  hamburger.addEventListener('click', () =>
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar()
  );
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
</script>
</body>
</html>
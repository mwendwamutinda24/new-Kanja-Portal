<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register Teacher — Kanja School</title>
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
    .header-tag {
      font-size: 11px;
      color: #555;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      gap: 6px;
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
      font-family: 'DM Serif Display', serif;
      font-size: 24px;
      font-weight: 400;
      color: var(--text-primary);
      line-height: 1.2;
    }

    /* ══════════ FORM CARD ══════════ */
    .form-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      animation: fadeUp 0.4s 0.05s ease both;
      max-width: 860px;
    }

    /* dark card header */
    .form-card-head {
      background: var(--black);
      border-bottom: 2px solid var(--gold);
      padding: 1.1rem 1.6rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .form-card-head .head-icon {
      width: 36px; height: 36px;
      background: var(--gold);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .form-card-head .head-icon i { color: var(--black); font-size: 15px; }

    .form-card-head h2 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.3rem;
      letter-spacing: 0.12em;
      color: #fff;
    }

    .form-card-head p {
      font-size: 12px;
      color: #555;
      margin-top: 1px;
    }

    /* form body */
    .form-body {
      padding: 1.8rem 1.6rem;
    }

    /* section divider */
    .form-section-label {
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

    .form-section-label:first-child { margin-top: 0; }

    /* grid */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.1rem 1.4rem;
    }

    .form-grid .span-full { grid-column: 1 / -1; }

    /* field */
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

    .field label .req {
      color: var(--gold-dim);
      font-size: 14px;
      line-height: 1;
    }

    .field label .opt-tag {
      font-size: 10px;
      color: var(--text-tertiary);
      background: var(--bg-input);
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 1px 6px;
      font-weight: 400;
      letter-spacing: 0.04em;
    }

    .field input,
    .field select,
    .field textarea {
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      color: var(--text-primary);
      background: var(--bg-input);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: var(--radius-md);
      padding: 10px 13px;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
      width: 100%;
    }

    .field input::placeholder,
    .field textarea::placeholder { color: var(--text-tertiary); }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.12);
      background: #fff;
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

    /* input with icon */
    .input-wrap {
      position: relative;
    }
    .input-wrap i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-tertiary);
      font-size: 13px;
      pointer-events: none;
    }
    .input-wrap input { padding-left: 34px; }

    /* helper text */
    .field-hint {
      font-size: 11px;
      color: var(--text-tertiary);
      margin-top: 1px;
    }

    /* ── Form footer ── */
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

    .form-footer-note {
      font-size: 12px;
      color: var(--text-tertiary);
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .form-footer-note i { color: var(--gold-dim); }

    .btn-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

    .btn-reset {
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 500;
      color: var(--text-secondary);
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 9px 20px;
      cursor: pointer;
      transition: background 0.15s, border-color 0.15s;
    }
    .btn-reset:hover { background: var(--bg-input); border-color: rgba(0,0,0,0.15); }

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
      white-space: nowrap;
    }
    .btn-submit:hover { background: var(--gold-dim); }
    .btn-submit:active { transform: scale(0.98); }

    /* ── Success / error flash ── */
    .flash {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      border-radius: var(--radius-md);
      padding: 12px 16px;
      font-size: 13.5px;
      animation: fadeUp 0.3s ease both;
    }
    .flash.success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
    .flash.error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
    .flash i { margin-top: 1px; flex-shrink: 0; }

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
      .form-grid { grid-template-columns: 1fr; }
      .form-grid .span-full { grid-column: 1; }
    }

    @media (max-width: 480px) {
      .school-name { font-size: 1.1rem; }
      .form-footer { flex-direction: column; align-items: stretch; }
      .btn-actions { flex-direction: column; }
      .btn-submit, .btn-reset { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- HEADER -->
<header class="site-header">
  <button class="hamburger" id="hamburger" aria-label="Open menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="school-logo"><i class="fa-solid fa-graduation-cap"></i></div>
  <div class="school-name-wrap">
    <div class="school-name">Stephen Kanja <span>School</span></div>
    <div class="school-motto">Aim Higher</div>
  </div>
  <div class="header-tag"><i class="fa-solid fa-circle-dot"></i> Teacher Registration</div>
</header>

<div class="layout">

  <!-- SIDEBAR -->
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
        <a href="RegisterStudent.php"><i class="fa-solid fa-user-plus"></i> Register Learners</a>
        <a href="RegisterTeacher.php" class="active"><i class="fa-solid fa-user-plus"></i> Register Teachers</a>
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

  <!-- MAIN -->
  <main class="main">

    <div class="page-title-bar">
      <div>
        <p class="page-eyebrow">Administration</p>
        <h1 class="page-title">Register New Teacher</h1>
      </div>
    </div>

    <?php
    /* ── Flash message after redirect ── */
    if (isset($_GET['success'])): ?>
    <div class="flash success">
      <i class="fa-solid fa-circle-check"></i>
      Teacher registered successfully. You can add another below.
    </div>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="flash error">
      <i class="fa-solid fa-circle-exclamation"></i>
      Something went wrong. Please check your details and try again.
    </div>
    <?php endif; ?>

    <!-- FORM CARD -->
    <div class="form-card">

      <div class="form-card-head">
        <div class="head-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div>
          <h2>Teacher Details</h2>
          <p>Complete all required fields marked with ✦</p>
        </div>
      </div>

      <form method="POST" action="regT.php" id="teacherForm">
        <div class="form-body">

          <!-- Personal info -->
          <p class="form-section-label">Personal Information</p>
          <div class="form-grid">

            <div class="field span-full">
              <label for="name">Full Name <span class="req">✦</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-user"></i>
                <input type="text" id="name" name="name" placeholder="e.g. Jane Wanjiku Mwangi" required>
              </div>
            </div>

            <div class="field">
              <label for="email">Email Address <span class="req">✦</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="jane@school.ac.ke" required>
              </div>
            </div>

            <div class="field">
              <label for="phone">Phone Number <span class="req">✦</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-phone"></i>
                <input type="tel" id="phone" name="phone" placeholder="07XX XXX XXX" required>
              </div>
            </div>

          </div>

          <!-- Professional info -->
          <p class="form-section-label">Professional Details</p>
          <div class="form-grid">

            <div class="field">
              <label for="tsc">TSC Number <span class="opt-tag">Optional</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-id-badge"></i>
                <input type="text" id="tsc" name="tsc" placeholder="e.g. 0123456">
              </div>
              <span class="field-hint">Teachers Service Commission registration number</span>
            </div>

            <div class="field">
              <label for="role">Role / Designation <span class="req">✦</span></label>
              <select id="role" name="role" required>
                <option value="" disabled selected>— Select role —</option>
                <option value="teacher">Teacher</option>
                <option value="head_teacher">Head Teacher</option>
                <option value="deputy_head">Deputy Head Teacher</option>
                <option value="senior_teacher">Senior Teacher</option>
                <option value="support_staff">Support Staff</option>
              </select>
            </div>

            <div class="field">
              <label for="subject">Subject of Profession <span class="opt-tag">Optional</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-book-open"></i>
                <input type="text" id="subject" name="subject" placeholder="e.g. Mathematics">
              </div>
            </div>

            <div class="field">
              <label for="grade">Class Teacher For <span class="opt-tag">Optional</span></label>
              <select id="grade" name="grade">
                <option value="">— Not a class teacher —</option>
                <?php for ($g = 1; $g <= 9; $g++): ?>
                  <option value="<?= $g ?>">Grade <?= $g ?></option>
                <?php endfor; ?>
              </select>
              <span class="field-hint">Leave blank if this teacher has no assigned class</span>
            </div>

          </div>

        </div><!-- end .form-body -->

        <div class="form-footer">
          <span class="form-footer-note">
            <i class="fa-solid fa-lock"></i>
            Teacher records are saved securely to the school database.
          </span>
          <div class="btn-actions">
            <button type="reset" class="btn-reset">Clear Form</button>
            <button type="submit" class="btn-submit">
              <i class="fa-solid fa-user-plus"></i> Register Teacher
            </button>
          </div>
        </div>

      </form>
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

  /* Auto-dismiss flash after 5 s */
  const flash = document.querySelector('.flash');
  if (flash) setTimeout(() => { flash.style.opacity = '0'; flash.style.transition = 'opacity 0.4s'; setTimeout(() => flash.remove(), 400); }, 5000);
</script>
</body>
</html>
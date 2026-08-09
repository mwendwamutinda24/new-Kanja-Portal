<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning Materials — Stephen Kanja School</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:       #f0c040;
      --gold-dim:   #c9a030;
      --gold-pale:  #fdf3d0;
      --black:      #111111;
      --dark:       #1a1a1a;
      --mid:        #2a2a2a;
      --bg:         #f4f4f2;
      --cream:      #faf8f4;
      --white:      #ffffff;
      --border:     #e4e4e0;
      --border-soft:rgba(0,0,0,0.07);
      --muted:      #999;
      --text:       #1c1c1a;
      --text-soft:  #5a5a56;
      --sidebar-w:  260px;
      --green:      #16a34a; --green-bg: #dcfce7;
      --blue:       #1e40af; --blue-bg:  #dbeafe;
      --red:        #dc2626; --red-bg:   #fee2e2;
      --amber:      #d97706; --amber-bg: #fef3c7;
      --purple:     #7c3aed; --purple-bg:#ede9fe;
      --teal:       #0d9488; --teal-bg:  #ccfbf1;
      --shadow-sm:  0 2px 8px rgba(0,0,0,0.06);
      --shadow-md:  0 6px 24px rgba(0,0,0,0.09);
      --shadow-lg:  0 16px 48px rgba(0,0,0,0.13);
      --radius:     12px;
      --radius-lg:  16px;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      font-size: 15px;
      line-height: 1.6;
    }

    /* ══════════════════════════════
       OVERLAY
    ══════════════════════════════ */
    .overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 200;
      backdrop-filter: blur(2px);
    }
    .overlay.active { display: block; }

    /* ══════════════════════════════
       SIDEBAR
    ══════════════════════════════ */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: var(--sidebar-w); height: 100vh;
      background: var(--black);
      display: flex; flex-direction: column;
      z-index: 300;
      transition: transform 0.32s cubic-bezier(0.4,0,0.2,1);
      overflow: hidden;
    }
    .sidebar::before {
      content: '';
      position: absolute; top: 0; left: 0;
      width: 3px; height: 100%;
      background: linear-gradient(to bottom, var(--gold) 0%, transparent 100%);
    }
    .sidebar-brand {
      padding: 1.6rem 1.4rem 1.2rem;
      border-bottom: 1px solid var(--mid);
      display: flex; align-items: center; gap: 10px;
    }
    .brand-icon {
      width: 36px; height: 36px;
      background: var(--gold); border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .brand-icon i { color: var(--black); font-size: 16px; }
    .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
    .brand-text span:first-child {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.15rem; letter-spacing: 0.12em; color: var(--white);
    }
    .brand-text span:last-child {
      font-size: 10px; color: var(--muted);
      letter-spacing: 0.08em; text-transform: uppercase;
    }
    .sidebar-close {
      display: none;
      position: absolute; top: 1rem; right: 1rem;
      background: var(--mid); border: none; color: var(--white);
      width: 30px; height: 30px; border-radius: 6px; cursor: pointer;
      align-items: center; justify-content: center;
      font-size: 13px; transition: background 0.2s;
    }
    .sidebar-close:hover { background: var(--gold); color: var(--black); }
    .sidebar-nav {
      flex: 1; overflow-y: auto; padding: 1rem 0;
      scrollbar-width: thin; scrollbar-color: var(--mid) transparent;
    }
    .nav-label {
      font-size: 10px; letter-spacing: 0.12em;
      text-transform: uppercase; color: #555;
      padding: 0.8rem 1.4rem 0.4rem; font-weight: 600;
    }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 11px;
      padding: 0.7rem 1.4rem;
      color: #aaa; text-decoration: none;
      font-size: 13.5px; font-weight: 500;
      border-left: 3px solid transparent;
      transition: all 0.18s;
    }
    .sidebar-nav a:hover,
    .sidebar-nav a.active {
      color: var(--white); background: rgba(255,255,255,0.04);
      border-left-color: var(--gold);
    }
    .sidebar-nav a.active { background: rgba(240,192,64,0.08); }
    .sidebar-nav a i { width: 18px; text-align: center; font-size: 13px; flex-shrink: 0; }
    .sidebar-nav a:hover i, .sidebar-nav a.active i { color: var(--gold); }
    .sidebar-footer {
      padding: 1rem 1.4rem; border-top: 1px solid var(--mid);
      font-size: 11px; color: #444; letter-spacing: 0.04em;
    }

    /* ══════════════════════════════
       MAIN
    ══════════════════════════════ */
    .main {
      margin-left: var(--sidebar-w);
      min-height: 100vh; display: flex; flex-direction: column;
      transition: margin-left 0.32s cubic-bezier(0.4,0,0.2,1);
    }

    /* ── Topbar ── */
    .topbar {
      background: var(--black);
      border-bottom: 3px solid var(--gold);
      padding: 0 2rem;
      height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
      flex-shrink: 0;
    }
    .topbar-left { display: flex; align-items: center; gap: 14px; }
    .menu-toggle {
      display: none;
      background: var(--mid); border: none; color: var(--white);
      width: 34px; height: 34px; border-radius: 7px; cursor: pointer;
      align-items: center; justify-content: center;
      font-size: 14px; transition: background 0.2s; flex-shrink: 0;
    }
    .menu-toggle:hover { background: var(--gold); color: var(--black); }
    .topbar-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.6rem; letter-spacing: 0.12em; color: var(--white);
    }
    .topbar-title span { color: var(--gold); }
    .topbar-right {
      display: flex; align-items: center; gap: 16px;
    }
    .topbar-tag {
      font-size: 11px; color: #555;
      letter-spacing: 0.05em;
      display: flex; align-items: center; gap: 6px;
    }
    .topbar-tag i { color: var(--gold); font-size: 10px; }

    /* ══════════════════════════════
       HERO BANNER
    ══════════════════════════════ */
    .page-hero {
      background: var(--black);
      border-bottom: 1px solid var(--mid);
      padding: 2.4rem 2.4rem 2rem;
      position: relative;
      overflow: hidden;
    }
    .page-hero::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 50% 100% at 5% 50%, rgba(240,192,64,0.1) 0%, transparent 60%),
        radial-gradient(ellipse 30% 60% at 95% 0%, rgba(30,64,175,0.15) 0%, transparent 60%);
      pointer-events: none;
    }
    .page-hero::after {
      content: '"';
      position: absolute;
      right: 3rem; top: -1rem;
      font-family: 'Lora', serif;
      font-size: 14rem;
      color: rgba(240,192,64,0.04);
      line-height: 1;
      pointer-events: none;
      user-select: none;
    }
    .hero-inner {
      position: relative;
      display: flex; align-items: flex-end;
      justify-content: space-between; gap: 2rem;
      flex-wrap: wrap;
    }
    .hero-text {}
    .hero-eyebrow {
      font-size: 10px; font-weight: 700;
      letter-spacing: 2px; text-transform: uppercase;
      color: var(--gold-dim); margin-bottom: 8px;
    }
    .hero-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(2rem, 4vw, 3rem);
      letter-spacing: 0.08em;
      color: var(--white); line-height: 1;
    }
    .hero-title span { color: var(--gold); }
    .hero-sub {
      font-family: 'Lora', serif;
      font-style: italic;
      font-size: 0.92rem; color: #666;
      margin-top: 8px; max-width: 480px;
    }
    .hero-stats {
      display: flex; gap: 2rem;
      flex-shrink: 0;
    }
    .hero-stat { text-align: right; }
    .hero-stat .hval {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2.2rem; color: var(--gold);
      letter-spacing: 0.04em; line-height: 1;
    }
    .hero-stat .hlbl {
      font-size: 10px; font-weight: 600;
      letter-spacing: 1.5px; text-transform: uppercase;
      color: #555; margin-top: 2px;
    }

    /* ══════════════════════════════
       CONTENT
    ══════════════════════════════ */
    .content { flex: 1; padding: 2rem 2.4rem; }

    /* ── Section head ── */
    .sec-head {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 1.4rem;
    }
    .sec-head h2 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.6rem; letter-spacing: 0.1em;
      color: var(--black);
    }
    .sec-head h2 i { color: var(--gold); font-size: 1.1rem; margin-right: 6px; }
    .sec-head::after {
      content: ''; flex: 1;
      height: 1px; background: var(--border);
    }
    .sec-badge {
      font-size: 10px; font-weight: 700;
      letter-spacing: 1px; text-transform: uppercase;
      background: var(--black); color: var(--gold);
      padding: 3px 10px; border-radius: 20px;
      white-space: nowrap;
    }

    /* ══════════════════════════════
       UPLOAD SECTION
    ══════════════════════════════ */
    .upload-section {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      margin-bottom: 2.5rem;
      box-shadow: var(--shadow-sm);
      animation: fadeUp 0.4s ease both;
    }
    .upload-head {
      background: var(--black);
      border-bottom: 2px solid var(--gold);
      padding: 1rem 1.6rem;
      display: flex; align-items: center; gap: 10px;
    }
    .upload-head h3 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.1rem; letter-spacing: 0.1em; color: var(--white);
    }
    .upload-head h3::before { content: '◆ '; color: var(--gold); font-size: 0.7rem; }

    .upload-body { padding: 1.8rem 2rem; }

    /* drop zone */
    .dropzone {
      border: 2px dashed var(--border);
      border-radius: var(--radius);
      padding: 2.5rem 2rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: var(--cream);
      position: relative;
      margin-bottom: 1.6rem;
    }
    .dropzone:hover, .dropzone.dragover {
      border-color: var(--gold);
      background: var(--gold-pale);
    }
    .dropzone input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .dz-icon {
      width: 56px; height: 56px;
      background: var(--black); border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem;
      transition: background 0.2s;
    }
    .dropzone:hover .dz-icon, .dropzone.dragover .dz-icon { background: var(--gold-dim); }
    .dz-icon i { color: var(--gold); font-size: 22px; }
    .dropzone:hover .dz-icon i, .dropzone.dragover .dz-icon i { color: var(--black); }
    .dz-title {
      font-weight: 600; font-size: 1rem; color: var(--text);
      margin-bottom: 4px;
    }
    .dz-sub { font-size: 12px; color: var(--muted); }
    .dz-sub strong { color: var(--gold-dim); }
    .dz-selected {
      display: none;
      align-items: center; gap: 10px;
      background: var(--gold-pale);
      border: 1px solid var(--gold-dim);
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px; font-weight: 500;
      color: var(--text); margin-top: 10px;
    }
    .dz-selected.show { display: flex; }
    .dz-selected i { color: var(--gold-dim); }

    /* form grid */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.1rem 1.4rem;
    }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.span2 { grid-column: span 2; }
    .form-group.span3 { grid-column: span 3; }
    .form-group label {
      font-size: 11px; font-weight: 700;
      letter-spacing: 1.2px; text-transform: uppercase;
      color: var(--text-soft);
    }
    .form-group label span.req { color: #dc2626; margin-left: 2px; }
    .form-control {
      padding: 10px 14px;
      background: var(--cream);
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px; color: var(--text);
      outline: none; transition: border-color 0.18s, box-shadow 0.18s;
      width: 100%;
    }
    select.form-control {
      appearance: none; -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 34px;
      cursor: pointer;
    }
    textarea.form-control { resize: vertical; min-height: 80px; line-height: 1.6; }
    .form-control:focus {
      border-color: var(--gold);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(240,192,64,0.15);
    }

    .form-actions {
      display: flex; align-items: center; gap: 12px;
      margin-top: 1.4rem; padding-top: 1.4rem;
      border-top: 1px solid var(--border);
      flex-wrap: wrap;
    }
    .btn-upload {
      background: var(--black); color: var(--white);
      font-family: 'DM Sans', sans-serif; font-size: 13.5px; font-weight: 600;
      border: none; border-radius: 9px;
      padding: 11px 28px;
      display: flex; align-items: center; gap: 8px;
      cursor: pointer; transition: background 0.18s, transform 0.12s;
    }
    .btn-upload:hover { background: var(--gold); color: var(--black); }
    .btn-upload:active { transform: scale(0.98); }
    .btn-upload i { font-size: 12px; }
    .btn-reset {
      background: transparent;
      color: var(--text-soft); font-family: 'DM Sans', sans-serif;
      font-size: 13px; font-weight: 500;
      border: 1.5px solid var(--border); border-radius: 9px;
      padding: 10px 18px; cursor: pointer;
      display: flex; align-items: center; gap: 7px;
      transition: all 0.15s;
    }
    .btn-reset:hover { border-color: #999; color: var(--text); }
    .upload-hint {
      margin-left: auto;
      font-size: 11.5px; color: var(--muted);
      display: flex; align-items: center; gap: 6px;
    }
    .upload-hint i { color: var(--gold); font-size: 10px; }

    /* success / error flash */
    .flash {
      padding: 12px 16px; border-radius: 9px;
      font-size: 13px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 1.4rem;
      animation: fadeUp 0.3s ease;
    }
    .flash.success { background: var(--green-bg); color: var(--green); border-left: 4px solid var(--green); }
    .flash.error   { background: var(--red-bg);   color: var(--red);   border-left: 4px solid var(--red); }

    /* ══════════════════════════════
       FILTER BAR
    ══════════════════════════════ */
    .filter-bar {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.1rem 1.4rem;
      display: flex; align-items: flex-end; gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1.4rem;
      box-shadow: var(--shadow-sm);
      animation: fadeUp 0.4s 0.08s ease both;
    }
    .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 130px; }
    .filter-group label {
      font-size: 10px; font-weight: 700;
      letter-spacing: 1.2px; text-transform: uppercase;
      color: var(--text-soft);
    }
    .filter-group select, .filter-group input {
      padding: 8px 28px 8px 11px;
      background: var(--cream);
      border: 1.5px solid var(--border);
      border-radius: 8px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px; color: var(--text);
      outline: none; appearance: none; -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 9px center;
      cursor: pointer; transition: border-color 0.15s;
    }
    .filter-group input {
      background-image: none; padding-right: 11px;
      min-width: 200px;
    }
    .filter-group select:focus, .filter-group input:focus {
      border-color: var(--gold);
      outline: none;
    }
    .filter-btn {
      background: var(--black); color: var(--white);
      border: none; border-radius: 8px;
      padding: 9px 20px; font-family: 'DM Sans', sans-serif;
      font-size: 13px; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; gap: 7px;
      transition: background 0.15s; height: 38px; flex-shrink: 0;
    }
    .filter-btn:hover { background: var(--gold); color: var(--black); }
    .filter-btn.clear-btn {
      background: transparent; color: var(--text-soft);
      border: 1.5px solid var(--border);
    }
    .filter-btn.clear-btn:hover { border-color: #999; color: var(--text); background: transparent; }

    /* ══════════════════════════════
       MATERIALS GRID
    ══════════════════════════════ */
    .materials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2.5rem;
    }

    .material-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: transform 0.22s, box-shadow 0.22s;
      animation: fadeUp 0.4s ease both;
      display: flex; flex-direction: column;
    }
    .material-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
    }

    /* colour stripe top */
    .material-card::before {
      content: '';
      display: block; height: 4px;
    }
    .material-card.type-pdf::before    { background: #dc2626; }
    .material-card.type-doc::before    { background: #2563eb; }
    .material-card.type-ppt::before    { background: #d97706; }
    .material-card.type-image::before  { background: #16a34a; }
    .material-card.type-video::before  { background: #7c3aed; }
    .material-card.type-other::before  { background: #6b7280; }

    .card-top {
      padding: 1.2rem 1.4rem 0.8rem;
      display: flex; align-items: flex-start; gap: 12px;
      flex: 1;
    }

    .file-icon-wrap {
      width: 44px; height: 44px;
      border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
    }
    .fi-pdf    { background: var(--red-bg);    color: var(--red); }
    .fi-doc    { background: var(--blue-bg);   color: var(--blue); }
    .fi-ppt    { background: var(--amber-bg);  color: var(--amber); }
    .fi-image  { background: var(--green-bg);  color: var(--green); }
    .fi-video  { background: var(--purple-bg); color: var(--purple); }
    .fi-other  { background: #f3f4f6; color: #6b7280; }

    .card-meta { flex: 1; min-width: 0; }
    .card-title {
      font-family: 'Lora', serif;
      font-size: 0.95rem; font-weight: 600;
      color: var(--text); line-height: 1.4;
      margin-bottom: 5px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .card-desc {
      font-size: 12px; color: var(--text-soft);
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin-bottom: 8px;
    }
    .card-tags {
      display: flex; flex-wrap: wrap; gap: 5px;
    }
    .tag {
      display: inline-block; font-size: 10px; font-weight: 700;
      padding: 2px 7px; border-radius: 4px;
      letter-spacing: 0.4px;
    }
    .tag-grade   { background: #f0f0ee; color: #555; }
    .tag-subject { background: var(--gold-pale); color: #7a5a10; }
    .tag-term    { background: #e8f0fe; color: #1e40af; }
    .tag-type    { background: #fce7f3; color: #9d174d; }

    .card-footer {
      padding: 0.8rem 1.4rem;
      border-top: 1px solid var(--border-soft);
      background: var(--cream);
      display: flex; align-items: center;
      justify-content: space-between; gap: 8px;
    }
    .card-uploader {
      display: flex; align-items: center; gap: 7px;
      font-size: 11px; color: var(--muted);
      min-width: 0;
    }
    .uploader-avatar {
      width: 22px; height: 22px;
      background: var(--black); border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .uploader-avatar i { color: var(--gold); font-size: 9px; }
    .uploader-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .card-date { font-size: 10px; color: var(--muted); flex-shrink: 0; }

    .card-actions {
      padding: 0.7rem 1.4rem;
      display: flex; gap: 8px;
      border-top: 1px solid var(--border-soft);
    }
    .btn-download {
      flex: 1;
      background: var(--black); color: var(--white);
      border: none; border-radius: 7px;
      padding: 8px 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px; font-weight: 600;
      cursor: pointer; display: flex; align-items: center;
      justify-content: center; gap: 6px;
      transition: background 0.15s;
      text-decoration: none;
    }
    .btn-download:hover { background: var(--gold); color: var(--black); }
    .btn-preview {
      background: var(--cream);
      color: var(--text-soft);
      border: 1.5px solid var(--border);
      border-radius: 7px; padding: 8px 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px; font-weight: 500;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      transition: all 0.15s; text-decoration: none;
    }
    .btn-preview:hover { border-color: var(--gold); color: var(--text); }
    .btn-delete {
      background: transparent; color: #ccc;
      border: 1.5px solid var(--border); border-radius: 7px;
      padding: 8px 10px; cursor: pointer;
      font-size: 12px; transition: all 0.15s;
    }
    .btn-delete:hover { background: var(--red-bg); border-color: var(--red); color: var(--red); }

    /* download counter */
    .dl-count {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10px; color: var(--muted);
      background: #f5f5f3; border-radius: 4px;
      padding: 2px 7px;
    }
    .dl-count i { color: var(--gold-dim); font-size: 9px; }

    /* ── Empty state ── */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
    }
    .empty-icon {
      width: 68px; height: 68px;
      background: var(--black); border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.2rem;
    }
    .empty-icon i { color: var(--gold); font-size: 26px; }
    .empty-state h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 6px; }
    .empty-state p { font-size: 13px; color: var(--muted); }

    /* ── Pagination ── */
    .pagination {
      display: flex; align-items: center; gap: 6px;
      justify-content: center; margin-top: 1.6rem;
    }
    .pag-btn {
      width: 34px; height: 34px;
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 7px; display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 600; color: var(--text-soft);
      cursor: pointer; text-decoration: none; transition: all 0.15s;
    }
    .pag-btn:hover { border-color: var(--gold); color: var(--text); }
    .pag-btn.active { background: var(--black); color: var(--white); border-color: var(--black); }
    .pag-btn.disabled { opacity: 0.4; pointer-events: none; }

    /* ── Recently uploaded strip ── */
    .recent-strip {
      display: flex; gap: 1rem; overflow-x: auto;
      padding-bottom: 0.5rem; margin-bottom: 2rem;
      scrollbar-width: thin; scrollbar-color: var(--border) transparent;
    }
    .recent-item {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 0.9rem 1.2rem;
      min-width: 220px; flex-shrink: 0;
      display: flex; align-items: center; gap: 10px;
      transition: box-shadow 0.2s;
      animation: fadeUp 0.4s ease both;
    }
    .recent-item:hover { box-shadow: var(--shadow-md); }
    .recent-icon { width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .recent-info { min-width: 0; }
    .recent-name { font-size: 12.5px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
    .recent-meta { font-size: 10px; color: var(--muted); margin-top: 2px; }

    /* ── Subject stats mini ── */
    .subject-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 0.8rem; margin-bottom: 2rem;
    }
    .subj-chip {
      background: var(--white); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 0.9rem 1rem;
      text-align: center; animation: fadeUp 0.4s ease both;
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: default;
    }
    .subj-chip:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .subj-num {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.9rem; color: var(--black); line-height: 1;
    }
    .subj-name { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.8px; margin-top: 3px; }

    /* ── Footer ── */
    .footer {
      text-align: center; padding: 1.4rem 2rem;
      border-top: 1px solid var(--border);
      font-size: 11px; color: #bbb; letter-spacing: 0.04em;
    }

    /* ══════════════════════════════
       ANIMATIONS
    ══════════════════════════════ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ══════════════════════════════
       RESPONSIVE
    ══════════════════════════════ */
    @media (max-width: 1100px) {
      .form-grid { grid-template-columns: repeat(2, 1fr); }
      .form-group.span3 { grid-column: span 2; }
    }
    @media (max-width: 900px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0,0,0,0.4); }
      .sidebar-close { display: flex; }
      .main { margin-left: 0; }
      .menu-toggle { display: flex; }
      .topbar-tag { display: none; }
      .content { padding: 1.4rem 1.2rem; }
      .hero-stats { gap: 1.2rem; }
    }
    @media (max-width: 640px) {
      .page-hero { padding: 1.6rem 1.2rem 1.4rem; }
      .hero-stats { display: none; }
      .form-grid { grid-template-columns: 1fr; }
      .form-group.span2, .form-group.span3 { grid-column: span 1; }
      .filter-bar { flex-direction: column; align-items: stretch; }
      .filter-group { min-width: unset; }
      .filter-group input { min-width: unset; }
      .materials-grid { grid-template-columns: 1fr; }
      .form-actions { flex-direction: column; align-items: stretch; }
      .upload-hint { margin-left: 0; }
    }
  </style>
</head>
<body>

<?php
/* ══════════════════════════════
   DATABASE & SETUP
══════════════════════════════ */
include('conn.php');

/* Create table if not exists */
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS learning_materials (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255)   NOT NULL,
    description   TEXT,
    subject       VARCHAR(100)   NOT NULL,
    grade         VARCHAR(20)    NOT NULL,
    term          VARCHAR(20)    NOT NULL,
    material_type VARCHAR(50)    NOT NULL,
    file_name     VARCHAR(255)   NOT NULL,
    file_original VARCHAR(255)   NOT NULL,
    file_size     INT            DEFAULT 0,
    file_ext      VARCHAR(20),
    uploaded_by   VARCHAR(150)   NOT NULL,
    downloads     INT            DEFAULT 0,
    uploaded_at   DATETIME       DEFAULT CURRENT_TIMESTAMP,
    tags          VARCHAR(255)
)
");

/* ── Upload directory ── */
$uploadDir = 'uploads/materials/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* ── Flash message ── */
$flash = '';
$flashType = '';

/* ══════════════════════════════
   HANDLE DELETE
══════════════════════════════ */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $fr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_name FROM learning_materials WHERE id=$delId"));
    if ($fr) {
        $fpath = $uploadDir . $fr['file_name'];
        if (file_exists($fpath)) unlink($fpath);
        mysqli_query($conn, "DELETE FROM learning_materials WHERE id=$delId");
        $flash = 'Material deleted successfully.';
        $flashType = 'success';
    }
}

/* ══════════════════════════════
   HANDLE DOWNLOAD COUNT
══════════════════════════════ */
if (isset($_GET['dl']) && is_numeric($_GET['dl'])) {
    $dlId = (int)$_GET['dl'];
    mysqli_query($conn, "UPDATE learning_materials SET downloads=downloads+1 WHERE id=$dlId");
    $dlRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_name, file_original FROM learning_materials WHERE id=$dlId"));
    if ($dlRow && file_exists($uploadDir.$dlRow['file_name'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.addslashes($dlRow['file_original']).'"');
        header('Content-Length: '.filesize($uploadDir.$dlRow['file_name']));
        readfile($uploadDir.$dlRow['file_name']);
        exit;
    }
}

/* ══════════════════════════════
   HANDLE UPLOAD
══════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material_file'])) {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject     = trim($_POST['subject']     ?? '');
    $grade       = trim($_POST['grade']       ?? '');
    $term        = trim($_POST['term']        ?? '');
    $matType     = trim($_POST['material_type'] ?? '');
    $uploadedBy  = trim($_POST['uploaded_by'] ?? '');
    $tags        = trim($_POST['tags']        ?? '');
    $file        = $_FILES['material_file'];

    $allowedExt = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','gif','mp4','mkv','avi','mov','txt','zip','rar'];
    $maxSize    = 50 * 1024 * 1024; // 50 MB

    $errors = [];
    if (empty($title))      $errors[] = 'Title is required.';
    if (empty($subject))    $errors[] = 'Subject is required.';
    if (empty($grade))      $errors[] = 'Grade is required.';
    if (empty($term))       $errors[] = 'Term is required.';
    if (empty($uploadedBy)) $errors[] = 'Uploaded By is required.';
    if ($file['error'] !== UPLOAD_ERR_OK) $errors[] = 'File upload error. Please try again.';
    if (empty($errors)) {
        $origName = $file['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) $errors[] = "File type .$ext is not allowed.";
        if ($file['size'] > $maxSize)     $errors[] = 'File exceeds 50 MB limit.';
        if (empty($errors)) {
            $safeName = uniqid('mat_', true).'.'.$ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir.$safeName)) {
                $title       = mysqli_real_escape_string($conn, $title);
                $description = mysqli_real_escape_string($conn, $description);
                $subject     = mysqli_real_escape_string($conn, $subject);
                $grade       = mysqli_real_escape_string($conn, $grade);
                $term        = mysqli_real_escape_string($conn, $term);
                $matType     = mysqli_real_escape_string($conn, $matType);
                $uploadedBy  = mysqli_real_escape_string($conn, $uploadedBy);
                $tags        = mysqli_real_escape_string($conn, $tags);
                $origName_db = mysqli_real_escape_string($conn, $origName);
                mysqli_query($conn, "INSERT INTO learning_materials
                    (title,description,subject,grade,term,material_type,file_name,file_original,file_size,file_ext,uploaded_by,tags)
                    VALUES
                    ('$title','$description','$subject','$grade','$term','$matType','$safeName','$origName_db',{$file['size']},'$ext','$uploadedBy','$tags')");
                $flash     = "Material \"$title\" uploaded successfully!";
                $flashType = 'success';
            } else {
                $errors[] = 'Could not save file. Check server permissions.';
            }
        }
    }
    if (!empty($errors)) {
        $flash     = implode(' ', $errors);
        $flashType = 'error';
    }
}

/* ══════════════════════════════
   FETCH / FILTER
══════════════════════════════ */
$fGrade   = trim($_GET['fgrade']   ?? '');
$fSubject = trim($_GET['fsubject'] ?? '');
$fTerm    = trim($_GET['fterm']    ?? '');
$fType    = trim($_GET['ftype']    ?? '');
$fSearch  = trim($_GET['search']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$where = ['1=1'];
if ($fGrade)   $where[] = "grade = '".mysqli_real_escape_string($conn,$fGrade)."'";
if ($fSubject) $where[] = "subject LIKE '%".mysqli_real_escape_string($conn,$fSubject)."%'";
if ($fTerm)    $where[] = "term = '".mysqli_real_escape_string($conn,$fTerm)."'";
if ($fType)    $where[] = "material_type = '".mysqli_real_escape_string($conn,$fType)."'";
if ($fSearch)  $where[] = "(title LIKE '%".mysqli_real_escape_string($conn,$fSearch)."%' OR description LIKE '%".mysqli_real_escape_string($conn,$fSearch)."%' OR uploaded_by LIKE '%".mysqli_real_escape_string($conn,$fSearch)."%' OR tags LIKE '%".mysqli_real_escape_string($conn,$fSearch)."%')";
$whereStr = implode(' AND ', $where);

$totalRes  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM learning_materials WHERE $whereStr"));
$totalMats = (int)$totalRes['c'];
$totalPages = (int)ceil($totalMats / $perPage);

$matsRes = mysqli_query($conn,"SELECT * FROM learning_materials WHERE $whereStr ORDER BY uploaded_at DESC LIMIT $perPage OFFSET $offset");

/* Stats */
$allTotal    = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM learning_materials"))['c']);
$allDownloads= (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(downloads) AS c FROM learning_materials"))['c'] ?? 0);
$recentRes   = mysqli_query($conn,"SELECT * FROM learning_materials ORDER BY uploaded_at DESC LIMIT 6");

/* Subject counts */
$subjectCountsRes = mysqli_query($conn,"SELECT subject, COUNT(*) AS cnt FROM learning_materials GROUP BY subject ORDER BY cnt DESC LIMIT 10");
$subjectCounts = [];
while ($sc = mysqli_fetch_assoc($subjectCountsRes)) $subjectCounts[] = $sc;

/* Helpers */
function fileIcon($ext) {
    $ext = strtolower($ext);
    if (in_array($ext,['pdf']))                     return ['fa-file-pdf','fi-pdf','type-pdf'];
    if (in_array($ext,['doc','docx','txt']))        return ['fa-file-word','fi-doc','type-doc'];
    if (in_array($ext,['ppt','pptx']))              return ['fa-file-powerpoint','fi-ppt','type-ppt'];
    if (in_array($ext,['jpg','jpeg','png','gif']))  return ['fa-file-image','fi-image','type-image'];
    if (in_array($ext,['mp4','avi','mkv','mov']))   return ['fa-file-video','fi-video','type-video'];
    return ['fa-file','fi-other','type-other'];
}
function formatBytes($b) {
    if ($b >= 1048576) return round($b/1048576,1).' MB';
    if ($b >= 1024) return round($b/1024,1).' KB';
    return $b.' B';
}
function timeAgo($dt) {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return round($diff/60).'m ago';
    if ($diff < 86400) return round($diff/3600).'h ago';
    if ($diff < 604800) return round($diff/86400).'d ago';
    return date('d M Y', strtotime($dt));
}
?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ░░░ SIDEBAR ░░░ -->
<aside class="sidebar" id="sidebar">
  <button class="sidebar-close" onclick="closeSidebar()"><i class="fa-solid fa-xmark"></i></button>
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div>
    <div class="brand-text">
      <span>Kanja School</span>
      <span>Management System</span>
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
    <a href="track.php"><i class="fa-solid fa-bullseye"></i> Track Performance</a>
    <a href="learningMaterial.php" class="active"><i class="fa-solid fa-book-open"></i> Learning Materials</a>
    <div class="nav-label">Finance</div>
    <a href="Fee.php"><i class="fa-solid fa-coins"></i> Finances</a>
  </nav>
  <div class="sidebar-footer">© 2026 Kelvin Mutinda</div>
</aside>

<!-- ░░░ MAIN ░░░ -->
<div class="main" id="main">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" onclick="openSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-title">Stephen Kanja <span>School</span></div>
    </div>
    <div class="topbar-right">
      <div class="topbar-tag"><i class="fa-solid fa-book-open"></i> Learning Materials Library</div>
    </div>
  </header>

  <!-- Hero Banner -->
  <div class="page-hero">
    <div class="hero-inner">
      <div class="hero-text">
        <div class="hero-eyebrow">Academic Resources</div>
        <div class="hero-title">Learning <span>Materials</span><br>Library</div>
        <div class="hero-sub">Upload, organise and access teaching &amp; learning resources across all grades and subjects.</div>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hval"><?= $allTotal ?></div>
          <div class="hlbl">Total Materials</div>
        </div>
        <div class="hero-stat">
          <div class="hval"><?= count($subjectCounts) ?></div>
          <div class="hlbl">Subjects Covered</div>
        </div>
        <div class="hero-stat">
          <div class="hval"><?= number_format($allDownloads) ?></div>
          <div class="hlbl">Total Downloads</div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div class="flash <?= $flashType ?>">
      <i class="fa-solid <?= $flashType==='success'?'fa-circle-check':'fa-circle-exclamation' ?>"></i>
      <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <!-- ════ UPLOAD SECTION ════ -->
    <div class="sec-head">
      <h2><i class="fa-solid fa-cloud-arrow-up"></i>Upload New Material</h2>
    </div>

    <div class="upload-section">
      <div class="upload-head">
        <h3>Add a Learning Resource</h3>
      </div>
      <div class="upload-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">

          <!-- Drop zone -->
          <div class="dropzone" id="dropzone">
            <input type="file" name="material_file" id="fileInput" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.mp4,.mkv,.avi,.mov,.txt,.zip,.rar">
            <div class="dz-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div class="dz-title">Drag &amp; Drop your file here</div>
            <div class="dz-sub">or <strong>click to browse</strong> &nbsp;·&nbsp; PDF, DOC, PPT, Images, Video, ZIP supported &nbsp;·&nbsp; Max 50 MB</div>
          </div>
          <div class="dz-selected" id="dzSelected">
            <i class="fa-solid fa-file-circle-check"></i>
            <span id="dzFileName">No file selected</span>
            <span id="dzFileSize" style="margin-left:auto;color:var(--muted);font-size:11px"></span>
          </div>

          <div class="form-grid" style="margin-top:1.4rem">
            <div class="form-group span2">
              <label>Title <span class="req">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Grade 5 Mathematics — Fractions Notes" required>
            </div>
            <div class="form-group">
              <label>Uploaded By <span class="req">*</span></label>
              <input type="text" name="uploaded_by" class="form-control" placeholder="Teacher / Staff name" required>
            </div>

            <div class="form-group">
              <label>Subject <span class="req">*</span></label>
              <select name="subject" class="form-control" required>
                <option value="">— Select Subject —</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Kiswahili</option>
                <option>Science &amp; Technology</option>
                <option>Social Studies</option>
                <option>Creative Arts</option>
                <option>Agriculture</option>
                <option>Religious Education</option>
                <option>Pre-Technical Studies</option>
                <option>Physical Education</option>
                <option>Life Skills</option>
                <option>General</option>
              </select>
            </div>
            <div class="form-group">
              <label>Grade <span class="req">*</span></label>
              <select name="grade" class="form-control" required>
                <option value="">— Select Grade —</option>
                <?php for ($g=1;$g<=9;$g++) echo "<option value='Grade $g'>Grade $g</option>"; ?>
                <option value="All Grades">All Grades</option>
              </select>
            </div>
            <div class="form-group">
              <label>Term <span class="req">*</span></label>
              <select name="term" class="form-control" required>
                <option value="">— Select Term —</option>
                <option>Term 1</option>
                <option>Term 2</option>
                <option>Term 3</option>
                <option>All Terms</option>
              </select>
            </div>

            <div class="form-group">
              <label>Material Type</label>
              <select name="material_type" class="form-control">
                <option value="">— Select Type —</option>
                <option>Notes</option>
                <option>Past Paper</option>
                <option>Revision Exercise</option>
                <option>Scheme of Work</option>
                <option>Lesson Plan</option>
                <option>Textbook Chapter</option>
                <option>Assignment</option>
                <option>Reference Material</option>
                <option>Video Lesson</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group span2">
              <label>Tags <small style="font-weight:400;text-transform:none;letter-spacing:0">(comma-separated, optional)</small></label>
              <input type="text" name="tags" class="form-control" placeholder="e.g. fractions, multiplication, term1, revision">
            </div>

            <div class="form-group span3">
              <label>Description</label>
              <textarea name="description" class="form-control" placeholder="Brief description of what this material covers…"></textarea>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-upload" id="submitBtn">
              <i class="fa-solid fa-cloud-arrow-up"></i> Upload Material
            </button>
            <button type="reset" class="btn-reset" onclick="resetForm()">
              <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
            <span class="upload-hint">
              <i class="fa-solid fa-shield-halved"></i>
              Files are stored securely on the school server.
            </span>
          </div>
        </form>
      </div>
    </div>

    <!-- ════ RECENTLY UPLOADED ════ -->
    <?php if ($allTotal > 0): ?>
    <div class="sec-head">
      <h2><i class="fa-solid fa-clock-rotate-left"></i>Recently Added</h2>
    </div>
    <div class="recent-strip">
    <?php
$ri = 0;
mysqli_data_seek($recentRes, 0);
while ($r = mysqli_fetch_assoc($recentRes)):
    list($ico) = fileIcon($r['file_ext']); // only grab the first value
    $ri++;
?>
  
      <div class="recent-item" style="animation-delay:<?= $ri*0.06 ?>s">
        <div class="recent-icon fi-<?= strtolower($r['file_ext']==='pdf'?'pdf':($r['file_ext']==='doc'||$r['file_ext']==='docx'?'doc':($r['file_ext']==='ppt'||$r['file_ext']==='pptx'?'ppt':(in_array($r['file_ext'],['jpg','jpeg','png','gif'])?'image':(in_array($r['file_ext'],['mp4','avi','mkv'])?'video':'other'))))) ?>">
          <i class="fa-solid <?= $ico ?>"></i>
        </div>
        <div class="recent-info">
          <div class="recent-name" title="<?= htmlspecialchars($r['title']) ?>"><?= htmlspecialchars($r['title']) ?></div>
          <div class="recent-meta"><?= htmlspecialchars($r['subject']) ?> · <?= htmlspecialchars($r['grade']) ?> · <?= timeAgo($r['uploaded_at']) ?></div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- ════ SUBJECT STATS ════ -->
    <?php if (!empty($subjectCounts)): ?>
    <div class="sec-head">
      <h2><i class="fa-solid fa-layer-group"></i>Materials by Subject</h2>
    </div>
    <div class="subject-stats">
      <?php foreach ($subjectCounts as $idx => $sc): ?>
      <div class="subj-chip" style="animation-delay:<?= $idx*0.05 ?>s">
        <div class="subj-num"><?= $sc['cnt'] ?></div>
        <div class="subj-name"><?= htmlspecialchars($sc['subject']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ════ FILTER & LIBRARY ════ -->
    <div class="sec-head">
      <h2><i class="fa-solid fa-book-open-reader"></i>Materials Library</h2>
      <span class="sec-badge"><?= $totalMats ?> found</span>
    </div>

    <form method="GET" action="" id="filterForm">
      <div class="filter-bar">
        <div class="filter-group">
          <label>Search</label>
          <input type="text" name="search" placeholder="Title, tag, uploader…" value="<?= htmlspecialchars($fSearch) ?>">
        </div>
        <div class="filter-group">
          <label>Grade</label>
          <select name="fgrade">
            <option value="">All Grades</option>
            <?php for ($g=1;$g<=9;$g++) { $sel=$fGrade==="Grade $g"?'selected':''; echo "<option value='Grade $g' $sel>Grade $g</option>"; } ?>
            <option value="All Grades" <?= $fGrade==='All Grades'?'selected':'' ?>>All Grades</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Subject</label>
          <select name="fsubject">
            <option value="">All Subjects</option>
            <?php
            $subjects = ['Mathematics','English','Kiswahili','Science & Technology','Social Studies','Creative Arts','Agriculture','Religious Education','Pre-Technical Studies','Physical Education','Life Skills','General'];
            foreach ($subjects as $s) { $sel=$fSubject===$s?'selected':''; echo "<option value='$s' $sel>$s</option>"; }
            ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Term</label>
          <select name="fterm">
            <option value="">All Terms</option>
            <?php foreach(['Term 1','Term 2','Term 3','All Terms'] as $t) { $sel=$fTerm===$t?'selected':''; echo "<option value='$t' $sel>$t</option>"; } ?>
          </select>
        </div>
        <div class="filter-group">
          <label>Type</label>
          <select name="ftype">
            <option value="">All Types</option>
            <?php foreach(['Notes','Past Paper','Revision Exercise','Scheme of Work','Lesson Plan','Textbook Chapter','Assignment','Reference Material','Video Lesson','Other'] as $tp) { $sel=$fType===$tp?'selected':''; echo "<option value='$tp' $sel>$tp</option>"; } ?>
          </select>
        </div>
        <button type="submit" class="filter-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        <?php if ($fGrade || $fSubject || $fTerm || $fType || $fSearch): ?>
        <a href="learningMaterial.php" class="filter-btn clear-btn"><i class="fa-solid fa-xmark"></i> Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- GRID -->
    <?php if ($totalMats === 0): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fa-solid fa-book-open"></i></div>
      <h3>No materials found</h3>
      <p><?= ($fGrade||$fSubject||$fTerm||$fType||$fSearch) ? 'Try adjusting your filters.' : 'Be the first to upload a learning material above.' ?></p>
    </div>
    <?php else: ?>
    <div class="materials-grid">
      <?php
      $cardIdx = 0;
      while ($mat = mysqli_fetch_assoc($matsRes)):
          [$ico, $fiClass, $typeClass] = fileIcon($mat['file_ext']);
          $cardIdx++;
          $sizeStr = $mat['file_size'] ? formatBytes($mat['file_size']) : '';
          $tagsArr  = $mat['tags'] ? array_map('trim', explode(',', $mat['tags'])) : [];
      ?>
      <div class="material-card <?= $typeClass ?>" style="animation-delay:<?= min($cardIdx*0.05,0.6) ?>s">
        <div class="card-top">
          <div class="file-icon-wrap <?= $fiClass ?>">
            <i class="fa-solid <?= $ico ?>"></i>
          </div>
          <div class="card-meta">
            <div class="card-title" title="<?= htmlspecialchars($mat['title']) ?>">
              <?= htmlspecialchars($mat['title']) ?>
            </div>
            <?php if ($mat['description']): ?>
            <div class="card-desc"><?= htmlspecialchars($mat['description']) ?></div>
            <?php endif; ?>
            <div class="card-tags">
              <span class="tag tag-grade"><?= htmlspecialchars($mat['grade']) ?></span>
              <span class="tag tag-subject"><?= htmlspecialchars($mat['subject']) ?></span>
              <span class="tag tag-term"><?= htmlspecialchars($mat['term']) ?></span>
              <?php if ($mat['material_type']): ?>
              <span class="tag tag-type"><?= htmlspecialchars($mat['material_type']) ?></span>
              <?php endif; ?>
              <?php foreach (array_slice($tagsArr,0,2) as $tg): ?>
              <span class="tag" style="background:#f0f0ee;color:#666"><?= htmlspecialchars($tg) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="card-uploader">
            <div class="uploader-avatar"><i class="fa-solid fa-user"></i></div>
            <span class="uploader-name"><?= htmlspecialchars($mat['uploaded_by']) ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
            <?php if ($sizeStr): ?><span style="font-size:10px;color:var(--muted)"><?= $sizeStr ?></span><?php endif; ?>
            <span class="dl-count"><i class="fa-solid fa-download"></i><?= $mat['downloads'] ?></span>
          </div>
        </div>
        <div style="padding:0 1.4rem 4px;font-size:10px;color:var(--muted)"><?= date('d M Y, g:ia', strtotime($mat['uploaded_at'])) ?></div>

        <div class="card-actions">
          <a href="learningMaterial.php?dl=<?= $mat['id'] ?>" class="btn-download" title="Download <?= htmlspecialchars($mat['file_original']) ?>">
            <i class="fa-solid fa-download"></i> Download
          </a>
          <?php
          $previewable = in_array(strtolower($mat['file_ext']),['pdf','jpg','jpeg','png','gif']);
          if ($previewable):
          ?>
          <a href="<?= $uploadDir.$mat['file_name'] ?>" target="_blank" class="btn-preview">
            <i class="fa-solid fa-eye"></i> Preview
          </a>
          <?php endif; ?>
          <form method="GET" action="" style="margin:0" onsubmit="return confirmDelete('<?= addslashes(htmlspecialchars($mat['title'])) ?>')">
            <input type="hidden" name="delete" value="<?= $mat['id'] ?>">
            <button type="submit" class="btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php
      $qs = http_build_query(array_filter(['search'=>$fSearch,'fgrade'=>$fGrade,'fsubject'=>$fSubject,'fterm'=>$fTerm,'ftype'=>$fType]));
      if ($qs) $qs = '&'.$qs;
      ?>
      <a href="?page=<?= max(1,$page-1).$qs ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
      <?php for ($p=1;$p<=$totalPages;$p++): ?>
      <a href="?page=<?= $p.$qs ?>" class="pag-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="?page=<?= min($totalPages,$page+1).$qs ?>" class="pag-btn <?= $page>=$totalPages?'disabled':'' ?>">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  </div><!-- .content -->

  <div class="footer">
    &copy; Designed by Kelvin Mutinda 2026 &nbsp;·&nbsp; Stephen Kanja School Learning Materials Library
  </div>

</div><!-- .main -->

<script>
  /* ── Sidebar ── */
  function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('active'); document.body.style.overflow='hidden'; }
  function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('active'); document.body.style.overflow=''; }
  document.addEventListener('keydown', e => { if (e.key==='Escape') closeSidebar(); });

  /* ── Drop zone ── */
  const dropzone  = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const dzSelected= document.getElementById('dzSelected');
  const dzFileName= document.getElementById('dzFileName');
  const dzFileSize= document.getElementById('dzFileSize');

  fileInput.addEventListener('change', () => showFile(fileInput.files[0]));

  dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone.addEventListener('dragleave',()=>  dropzone.classList.remove('dragover'));
  dropzone.addEventListener('drop', e => {
    e.preventDefault(); dropzone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      showFile(e.dataTransfer.files[0]);
    }
  });

  function showFile(f) {
    if (!f) return;
    dzFileName.textContent = f.name;
    dzFileSize.textContent = formatBytes(f.size);
    dzSelected.classList.add('show');
  }

  function formatBytes(b) {
    if (b >= 1048576) return (b/1048576).toFixed(1)+' MB';
    if (b >= 1024) return (b/1024).toFixed(1)+' KB';
    return b+' B';
  }

  /* ── Reset ── */
  function resetForm() {
    dzSelected.classList.remove('show');
    dzFileName.textContent = 'No file selected';
    dzFileSize.textContent = '';
    dropzone.classList.remove('dragover');
  }

  /* ── Delete confirm ── */
  function confirmDelete(title) {
    return confirm('Are you sure you want to delete "' + title + '"?\nThis action cannot be undone.');
  }

  /* ── Scroll form on load if flash ── */
  <?php if ($flash && $flashType==='error'): ?>
  document.addEventListener('DOMContentLoaded', ()=>{ document.querySelector('.upload-section')?.scrollIntoView({behavior:'smooth',block:'start'}); });
  <?php endif; ?>

  /* ── Upload button loading state ── */
  document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    const fi  = document.getElementById('fileInput');
    if (!fi.files.length) { e.preventDefault(); alert('Please select a file to upload.'); return; }
    btn.innerHTML = '<i class="fa-solid fa-circle-notch" style="animation:spin 0.8s linear infinite"></i> Uploading…';
    btn.disabled = true;
  });
</script>

</body>
</html>
<?php
/**
 * StudyVault — Shared Theme System
 * Include in <head> of every page BEFORE any stylesheets.
 * Provides: CSS variables (light + dark), base resets, font import,
 * theme toggle JS, FOUC prevention.
 */
?>
<!-- Theme: FOUC Prevention -->
<script>
  (function() {
    var t = localStorage.getItem('sv-theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ════════════════════════════════════════════
   STUDYVAULT — CSS DESIGN SYSTEM
   Light theme = default | Dark = [data-theme="dark"]
   ════════════════════════════════════════════ */

/* ── Light Theme (Default) ── */
:root {
  --bg:          #f2f4fb;
  --bg2:         #eaedf8;
  --surface:     #ffffff;
  --card:        #ffffff;
  --card-hover:  #f8f9ff;
  --border:      #dde2f0;
  --border2:     #c8cfdf;
  --input-bg:    #f6f8ff;

  --accent:      #5b4fcf;
  --accent-h:    #4a3fb8;
  --accent-2:    #0891b2;
  --accent-3:    #059669;
  --accent-4:    #d97706;
  --danger:      #dc2626;
  --danger-bg:   rgba(220,38,38,.08);
  --success:     #059669;
  --success-bg:  rgba(5,150,105,.08);
  --warning:     #d97706;
  --warning-bg:  rgba(217,119,6,.08);

  --text:        #1e1b4b;
  --text-2:      #3d3a6b;
  --text-3:      #6b7280;
  --muted:       #9ca3af;

  --shadow-xs:   0 1px 2px rgba(0,0,0,.05);
  --shadow-sm:   0 1px 4px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
  --shadow:      0 2px 8px rgba(0,0,0,.08), 0 8px 24px rgba(0,0,0,.04);
  --shadow-lg:   0 8px 32px rgba(0,0,0,.1), 0 2px 8px rgba(0,0,0,.06);
  --shadow-xl:   0 20px 60px rgba(0,0,0,.12), 0 4px 16px rgba(0,0,0,.08);

  --radius-sm:   8px;
  --radius:      12px;
  --radius-lg:   18px;
  --radius-xl:   24px;

  --sidebar-w:   252px;

  --accent-glow:  0 0 0 3px rgba(91,79,207,.18);
}

/* ── Dark Theme Override ── */
[data-theme="dark"] {
  --bg:          #0e0f18;
  --bg2:         #13151f;
  --surface:     #13151f;
  --card:        #1a1d2e;
  --card-hover:  #20243a;
  --border:      #272c42;
  --border2:     #313757;
  --input-bg:    #131625;
  --text:        #f0f2ff;
  --text-2:      #c4caef;
  --text-3:      #7b82a8;
  --muted:       #5a6080;
  --shadow-xs:   0 1px 2px rgba(0,0,0,.3);
  --shadow-sm:   0 1px 4px rgba(0,0,0,.4), 0 2px 8px rgba(0,0,0,.2);
  --shadow:      0 2px 8px rgba(0,0,0,.4), 0 8px 24px rgba(0,0,0,.2);
  --shadow-lg:   0 8px 32px rgba(0,0,0,.5), 0 2px 8px rgba(0,0,0,.3);
  --shadow-xl:   0 20px 60px rgba(0,0,0,.6), 0 4px 16px rgba(0,0,0,.4);
  --accent-glow: 0 0 0 3px rgba(91,79,207,.25);
}

/* ── Base Reset ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Nunito', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  line-height: 1.6;
  transition: background .3s, color .3s;
  -webkit-font-smoothing: antialiased;
}
a { color: inherit; text-decoration: none; }
button { font-family: inherit; }
input, select, textarea { font-family: inherit; }

/* ── Typography ── */
.font-display { font-family: 'Bricolage Grotesque', sans-serif; }

/* ── Theme Toggle Button ── */
.theme-toggle {
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 1.5px solid var(--border);
  background: var(--surface);
  color: var(--text-3);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  transition: all .2s;
  box-shadow: var(--shadow-xs);
  flex-shrink: 0;
}
.theme-toggle:hover {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--card-hover);
  transform: rotate(20deg);
}

/* ── Shared Sidebar Styles ── */
.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  position: fixed; left: 0; top: 0; bottom: 0; z-index: 100;
  transition: background .3s, border-color .3s;
  overflow-y: auto;
}
.sidebar-logo {
  padding: 22px 20px 18px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 10px;
}
.logo-mark {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--accent), var(--accent-2));
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(91,79,207,.3);
}
.logo-text {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 700;
  font-size: 18px;
  color: var(--text);
  transition: color .3s;
}

.nav-section {
  padding: 18px 14px 4px;
  font-size: 10.5px;
  font-weight: 700;
  color: var(--muted);
  letter-spacing: 1px;
  text-transform: uppercase;
}
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 18px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-3);
  text-decoration: none;
  border-left: 3px solid transparent;
  transition: all .18s;
  border-radius: 0;
}
.nav-item:hover { color: var(--text); background: var(--bg); }
.nav-item.active {
  color: var(--accent);
  background: rgba(91,79,207,.07);
  border-left-color: var(--accent);
}
.nav-item .ni { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }

.nav-item.admin-nav { color: color-mix(in srgb, var(--accent-2) 80%, var(--text-3)); }
.nav-item.admin-nav:hover { color: var(--accent-2); background: rgba(8,145,178,.06); }
.nav-item.admin-nav.active { color: var(--accent-2); background: rgba(8,145,178,.08); border-left-color: var(--accent-2); }

.sidebar-bottom {
  margin-top: auto;
  padding: 14px;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.user-chip {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
}
.user-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent-2));
  display: flex; align-items: center; justify-content: center;
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 700;
  font-size: 13px;
  color: #fff;
  flex-shrink: 0;
}
.user-info { flex: 1; overflow: hidden; }
.user-name {
  font-size: 13px; font-weight: 700;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  color: var(--text);
}
.user-role-badge {
  font-size: 10px; font-weight: 700;
  color: var(--muted);
  text-transform: capitalize;
  margin-top: 1px;
}
.user-role-badge.admin { color: var(--accent-2); }

.btn-logout {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 9px;
  background: transparent;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-3);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all .18s;
  width: 100%;
}
.btn-logout:hover {
  background: var(--danger-bg);
  border-color: var(--danger);
  color: var(--danger);
}
.btn-logout svg { width: 14px; height: 14px; flex-shrink: 0; }

/* ── Main Content Area ── */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  padding: 32px 36px;
  min-height: 100vh;
  transition: background .3s;
}

/* ── Common Cards ── */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  transition: background .3s, border-color .3s, box-shadow .3s;
}
.card:hover { box-shadow: var(--shadow); }

/* ── Badges ── */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 700;
}
.badge-purple { background:rgba(91,79,207,.1); color:var(--accent); border:1px solid rgba(91,79,207,.2); }
.badge-teal   { background:rgba(8,145,178,.1);  color:var(--accent-2); border:1px solid rgba(8,145,178,.2); }
.badge-green  { background:rgba(5,150,105,.1);  color:var(--accent-3); border:1px solid rgba(5,150,105,.2); }
.badge-amber  { background:rgba(217,119,6,.1);  color:var(--accent-4); border:1px solid rgba(217,119,6,.2); }
.badge-red    { background:rgba(220,38,38,.1);  color:var(--danger);   border:1px solid rgba(220,38,38,.2); }

/* ── Buttons ── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px;
  border-radius: var(--radius);
  font-family: 'Nunito', sans-serif;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all .18s;
  text-decoration: none;
}
.btn-primary {
  background: linear-gradient(135deg, var(--accent), var(--accent-h));
  color: #fff;
  box-shadow: 0 4px 14px rgba(91,79,207,.3);
}
.btn-primary:hover { box-shadow: 0 6px 20px rgba(91,79,207,.4); transform: translateY(-1px); }
.btn-secondary {
  background: var(--surface);
  color: var(--text-2);
  border: 1.5px solid var(--border);
  box-shadow: var(--shadow-xs);
}
.btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
.btn-danger { background: var(--danger-bg); color: var(--danger); border: 1.5px solid rgba(220,38,38,.25); }
.btn-danger:hover { background: var(--danger); color: #fff; }

/* ── Inputs ── */
.form-group { margin-bottom: 18px; }
.form-label {
  display: block;
  font-size: 12.5px;
  font-weight: 700;
  color: var(--text-2);
  margin-bottom: 7px;
  letter-spacing: .3px;
}
.input-wrap { position: relative; }
.input-icon {
  position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
  font-size: 15px; color: var(--muted); pointer-events: none;
}
.form-input {
  width: 100%;
  background: var(--input-bg);
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  color: var(--text);
  font-family: 'Nunito', sans-serif;
  font-size: 14px;
  padding: 11px 14px 11px 40px;
  outline: none;
  transition: border-color .2s, box-shadow .2s, background .3s;
}
.form-input:focus {
  border-color: var(--accent);
  box-shadow: var(--accent-glow);
  background: var(--surface);
}
.form-input::placeholder { color: var(--muted); }
.form-input.no-icon { padding-left: 14px; }

/* ── Alerts ── */
.alert {
  padding: 12px 16px;
  border-radius: var(--radius);
  font-size: 13.5px;
  font-weight: 600;
  display: none;
  animation: fadeSlide .3s ease;
}
.alert.show { display: block; }
.alert-error   { background: var(--danger-bg);  border: 1.5px solid rgba(220,38,38,.25);  color: var(--danger); }
.alert-success { background: var(--success-bg); border: 1.5px solid rgba(5,150,105,.25);  color: var(--success); }
.alert-warning { background: var(--warning-bg); border: 1.5px solid rgba(217,119,6,.25);  color: var(--warning); }

/* ── Animations ── */
@keyframes fadeSlide { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeUp    { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeIn    { from{opacity:0} to{opacity:1} }
@keyframes spin      { to{transform:rotate(360deg)} }
@keyframes slideLeft { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
@keyframes pulse     { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }

.anim-up  { animation: fadeUp .45s cubic-bezier(.22,1,.36,1) both; }
.anim-up-1 { animation-delay: .06s; }
.anim-up-2 { animation-delay: .12s; }
.anim-up-3 { animation-delay: .18s; }
.anim-up-4 { animation-delay: .24s; }
.anim-up-5 { animation-delay: .30s; }
.anim-up-6 { animation-delay: .36s; }

/* ── Spinner ── */
.spinner {
  display: inline-block; width: 15px; height: 15px;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
  margin-right: 7px; vertical-align: middle;
}
.spinner.dark { border-color: rgba(0,0,0,.15); border-top-color: var(--text); }

/* ── Toast ── */
.toast {
  position: fixed; bottom: 24px; right: 24px; z-index: 9999;
  display: flex; align-items: center; gap: 10px;
  padding: 13px 20px;
  background: var(--card);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  font-size: 14px; font-weight: 600;
  min-width: 260px; max-width: 380px;
  transform: translateY(100px); opacity: 0;
  transition: all .35s cubic-bezier(.16,1,.3,1);
  pointer-events: none;
}
.toast.show { transform: translateY(0); opacity: 1; pointer-events: all; }
.toast.toast-success { border-color: rgba(5,150,105,.3); color: var(--success); }
.toast.toast-error   { border-color: rgba(220,38,38,.3); color: var(--danger); }
.toast.toast-warning { border-color: rgba(217,119,6,.3); color: var(--warning); }

/* ── Table Styles ── */
.tbl-wrap {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.tbl-header {
  padding: 18px 22px;
  display: flex; justify-content: space-between; align-items: center;
  border-bottom: 1px solid var(--border);
  background: var(--card);
}
.tbl-title {
  font-family: 'Bricolage Grotesque', sans-serif;
  font-weight: 700; font-size: 15px;
  color: var(--text);
  display: flex; align-items: center; gap: 8px;
}
table { width: 100%; border-collapse: collapse; }
thead th {
  padding: 10px 18px;
  text-align: left;
  font-size: 11px; font-weight: 700; letter-spacing: .7px;
  text-transform: uppercase;
  color: var(--muted);
  background: var(--bg);
  border-bottom: 1px solid var(--border);
}
tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--bg); }
tbody td { padding: 13px 18px; font-size: 13.5px; vertical-align: middle; color: var(--text-2); }
.empty-row td { text-align: center; padding: 50px; color: var(--muted); font-size: 14px; }

/* ── Table Search ── */
.tbl-search {
  background: var(--input-bg);
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 8px 14px;
  color: var(--text);
  font-size: 13px;
  outline: none;
  width: 200px;
  transition: border-color .2s;
}
.tbl-search:focus { border-color: var(--accent); }

/* ── Responsive ── */
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .main-content { margin-left: 0; padding: 20px 16px; }
}
</style>
<!-- Theme Toggle JS -->
<script>
function toggleTheme() {
  const html = document.documentElement;
  const current = html.getAttribute('data-theme') || 'light';
  const next = current === 'light' ? 'dark' : 'light';
  html.setAttribute('data-theme', next);
  localStorage.setItem('sv-theme', next);
  updateThemeBtn();
}
function updateThemeBtn() {
  const t = document.documentElement.getAttribute('data-theme') || 'light';
  document.querySelectorAll('.theme-toggle').forEach(btn => {
    btn.title = t === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode';
    btn.textContent = t === 'light' ? '🌙' : '☀️';
  });
}
document.addEventListener('DOMContentLoaded', updateThemeBtn);
function showToast(msg, type = 'success') {
  const t = document.getElementById('sv-toast');
  if (!t) return;
  t.textContent = (type==='success'?'✓ ':type==='error'?'✕ ':'⚠ ') + msg;
  t.className = 'toast show toast-' + type;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 3500);
}
</script>

<?php
// navbar.php — reusable navbar for all pages
$_nav_email   = $_SESSION['email'] ?? '';
$_nav_user    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '" . mysqli_real_escape_string($conn, $_nav_email) . "'"));
$_nav_name    = $_nav_user ? $_nav_user['Name'] : $_nav_email;
$_nav_id      = $_nav_user ? (int)$_nav_user['id'] : 0;
$_nav_init    = strtoupper(substr($_nav_name, 0, 1));
$_nav_current = basename($_SERVER['PHP_SELF']);

$_nav_links = [
    'home.php'         => ['Home',        '🏠'],
    'dashbord.php'     => ['Dashboard',   '📊'],
    'skill_market.php' => ['Marketplace', '🛒'],
    'add_proof.php'    => ['My Skills',   '📚'],
    'requests.php'     => ['Requests',    '🤝'],
    'sessions.php'     => ['Sessions',    '📅'],
    'messaging.php'    => ['Messages',    '💬'],
];

// Badge counts for tab bar
$_pending_count = $_nav_id ? (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM skill_requests WHERE receiver_id=$_nav_id AND status='pending'"))['c'] : 0;
$_unread_count  = $_nav_id ? (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM messages WHERE receiver_id=$_nav_id AND is_read=0"))['c'] : 0;
?>
<style>
/* ── Notification Bell ── */
.notif-bell { position: relative; }
.notif-trigger {
  width: 38px; height: 38px; border-radius: 10px;
  background: var(--glass-2); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; cursor: pointer; position: relative;
  transition: background .2s, border-color .2s;
}
.notif-trigger:hover { background: var(--glass-3); border-color: rgba(14,165,233,.3); }
.notif-badge {
  position: absolute; top: -5px; right: -5px;
  background: linear-gradient(135deg, #f87171, #ef4444);
  color: #fff; font-size: .65rem; font-weight: 800;
  min-width: 18px; height: 18px; border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px; box-shadow: 0 0 8px rgba(248,113,113,.5);
  animation: notif-pulse 2s ease-in-out infinite;
}
@keyframes notif-pulse {
  0%,100% { box-shadow: 0 0 6px rgba(248,113,113,.4); }
  50%      { box-shadow: 0 0 14px rgba(248,113,113,.7); }
}
.notif-dropdown {
  display: none; position: absolute; top: calc(100% + 10px); right: 0;
  background: rgba(10,18,35,.97); backdrop-filter: blur(24px);
  border: 1px solid var(--border); border-radius: var(--radius-lg);
  width: 320px; box-shadow: 0 16px 40px rgba(0,0,0,.5);
  z-index: 9999; overflow: hidden; flex-direction: column;
}
.notif-bell.open .notif-dropdown { display: flex; flex-direction: column; }
.notif-header {
  padding: .9rem 1.1rem; border-bottom: 1px solid var(--border-2);
  display: flex; justify-content: space-between; align-items: center;
  background: rgba(14,165,233,.06);
}
.notif-mark-all {
  background: none; border: none; cursor: pointer;
  font-size: .75rem; color: var(--teal); font-weight: 600; font-family: var(--font);
}
.notif-list { overflow-y: auto; max-height: 360px; }
.notif-item {
  display: flex; align-items: flex-start; gap: .8rem;
  padding: .9rem 1.1rem; border-bottom: 1px solid var(--border-2);
  text-decoration: none; color: inherit; transition: background .15s;
  cursor: pointer; position: relative;
}
.notif-item:hover { background: var(--glass-2); }
.notif-item.unread { background: rgba(14,165,233,.04); }
.notif-item.unread::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0;
  width: 3px; background: var(--teal); border-radius: 0 2px 2px 0;
}
.notif-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.notif-icon.request_received { background: rgba(14,165,233,.15); }
.notif-icon.request_accepted { background: rgba(16,185,129,.15); }
.notif-icon.request_rejected { background: rgba(248,113,113,.15); }
.notif-icon.session_scheduled{ background: rgba(59,130,246,.15); }
.notif-icon.message          { background: rgba(139,92,246,.15); }
.notif-content { flex: 1; min-width: 0; }
.notif-title { font-size: .85rem; font-weight: 700; color: var(--text); margin-bottom: .15rem; }
.notif-msg   { font-size: .78rem; color: var(--text-2); line-height: 1.4; }
.notif-time  { font-size: .7rem; color: var(--text-3); margin-top: .25rem; }
.notif-empty { padding: 2.5rem 1.5rem; text-align: center; color: var(--text-3); }
.notif-empty-icon { font-size: 2rem; margin-bottom: .5rem; opacity: .4; }
.notif-empty p { font-size: .82rem; }
.notif-footer { padding: .7rem 1.1rem; border-top: 1px solid var(--border-2); text-align: center; }
.notif-footer a { font-size: .8rem; color: var(--teal); text-decoration: none; font-weight: 600; }
.notif-loading { padding: 1.5rem; text-align: center; color: var(--text-3); font-size: .85rem; }

/* ── Mobile elements — hidden on desktop ── */
.mob-hamburger { display: none; }
.mob-drawer    { display: none; }
.mob-tabbar    { display: none; }
.mob-back-btn  { display: none; }
</style>

<!-- ════════════════════════════════
     DESKTOP NAVBAR
     ════════════════════════════════ -->
<nav class="navbar">
  <a class="navbar-brand" href="home.php">
    <span class="brand-dot"></span>TechTalk
  </a>

  <!-- Desktop links -->
  <div class="nav-links">
    <?php foreach ($_nav_links as $file => $info):
      $cls = ($_nav_current === $file) ? ' class="nav-active"' : '';
      echo "<a href=\"{$file}\"{$cls}>{$info[0]}</a>";
    endforeach; ?>

    <!-- Notification Bell -->
    <div class="notif-bell" id="notifBell">
      <button class="notif-trigger" onclick="toggleNotifications()" title="Notifications">
        🔔
        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
          <span style="font-weight:700;color:var(--text);font-size:.9rem;">Notifications</span>
          <button class="notif-mark-all" onclick="markAllRead()">Mark all read</button>
        </div>
        <div class="notif-list" id="notifList">
          <div class="notif-loading">Loading...</div>
        </div>
        <div class="notif-footer"><a href="notifications.php">View all</a></div>
      </div>
    </div>

    <!-- User dropdown -->
    <div class="user-menu" id="userMenu">
      <button class="user-trigger" onclick="toggleUserMenu()">
        <div class="user-trigger-avatar"><?= $_nav_init ?></div>
        <span class="user-trigger-name"><?= htmlspecialchars($_nav_name) ?></span>
        <span class="user-trigger-chevron">▼</span>
      </button>
      <div class="user-dropdown">
        <div class="dropdown-header">
          <div class="dropdown-header-name"><?= htmlspecialchars($_nav_name) ?></div>
          <div class="dropdown-header-email"><?= htmlspecialchars($_nav_email) ?></div>
        </div>
        <a href="profile.php"      class="dropdown-item"><span class="dropdown-item-icon">👤</span> Edit Profile</a>
        <a href="view_profile.php" class="dropdown-item"><span class="dropdown-item-icon">🪪</span> View Profile</a>
        <a href="add_proof.php"    class="dropdown-item"><span class="dropdown-item-icon">📚</span> My Skills</a>
        <a href="certificates.php" class="dropdown-item"><span class="dropdown-item-icon">🎓</span> Certificates</a>
        <a href="reviews.php"      class="dropdown-item"><span class="dropdown-item-icon">⭐</span> Reviews</a>
        <div class="dropdown-divider"></div>
        <a href="logout.php" class="dropdown-item danger"><span class="dropdown-item-icon">🚪</span> Sign out</a>
      </div>
    </div>
  </div>

  <!-- Mobile hamburger (only visible on mobile via CSS) -->
  <button class="mob-hamburger" id="mobHamburger" onclick="mobToggleDrawer()">☰</button>
</nav>

<!-- ════════════════════════════════
     MOBILE DRAWER (hidden by default)
     ════════════════════════════════ -->
<div class="mob-drawer" id="mobDrawer">
  <div class="mob-drawer-user">
    <div class="mob-drawer-avatar"><?= $_nav_init ?></div>
    <div>
      <div class="mob-drawer-name"><?= htmlspecialchars($_nav_name) ?></div>
      <div class="mob-drawer-email"><?= htmlspecialchars($_nav_email) ?></div>
    </div>
  </div>
  <?php foreach ($_nav_links as $file => $info):
    $active = ($_nav_current === $file) ? ' mob-drawer-active' : '';
    echo "<a href=\"{$file}\" class=\"mob-drawer-link{$active}\">{$info[1]} {$info[0]}</a>";
  endforeach; ?>
  <div class="mob-drawer-divider"></div>
  <a href="notifications.php" class="mob-drawer-link<?= $_nav_current==='notifications.php'?' mob-drawer-active':'' ?>">🔔 Notifications</a>
  <a href="profile.php"       class="mob-drawer-link">👤 Edit Profile</a>
  <a href="view_profile.php"  class="mob-drawer-link">🪪 View Profile</a>
  <a href="certificates.php"  class="mob-drawer-link">🎓 Certificates</a>
  <div class="mob-drawer-divider"></div>
  <a href="logout.php" class="mob-drawer-link mob-drawer-logout">🚪 Sign out</a>
</div>

<!-- ════════════════════════════════
     MOBILE BOTTOM TAB BAR
     ════════════════════════════════ -->
<nav class="mob-tabbar">
  <a href="home.php"         class="mob-tab <?= $_nav_current==='home.php'         ?'mob-tab-active':'' ?>"><span>🏠</span>Home</a>
  <a href="skill_market.php" class="mob-tab <?= $_nav_current==='skill_market.php' ?'mob-tab-active':'' ?>"><span>🛒</span>Market</a>
  <a href="requests.php"     class="mob-tab <?= $_nav_current==='requests.php'     ?'mob-tab-active':'' ?>">
    <span style="position:relative;">🤝<?php if($_pending_count>0):?><i class="mob-tab-dot"></i><?php endif;?></span>Requests
  </a>
  <a href="messaging.php"    class="mob-tab <?= $_nav_current==='messaging.php'    ?'mob-tab-active':'' ?>">
    <span style="position:relative;">💬<?php if($_unread_count>0):?><i class="mob-tab-dot"></i><?php endif;?></span>Chat
  </a>
  <a href="sessions.php"     class="mob-tab <?= $_nav_current==='sessions.php'     ?'mob-tab-active':'' ?>"><span>📅</span>Sessions</a>
</nav>

<style>
/* ════════════════════════════════════════
   MOBILE STYLES — only apply ≤ 768px
   Desktop is completely untouched
   ════════════════════════════════════════ */
@media (max-width: 768px) {

  /* Show mobile elements */
  .mob-hamburger { display: flex !important; }
  .mob-tabbar    { display: flex !important; }

  /* Navbar adjustments */
  .navbar { padding: 0 1rem; height: 56px; position: relative; }
  .nav-links { display: none !important; }

  /* Hamburger button */
  .mob-hamburger {
    align-items: center; justify-content: center;
    width: 40px; height: 40px;
    background: var(--glass-2);
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 1.3rem; color: var(--text);
    cursor: pointer;
  }

  /* Drawer — hidden until .mob-open added */
  .mob-drawer {
    position: fixed;
    top: 56px; left: 0; right: 0;
    background: rgba(6,11,20,.98);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-bottom: 1px solid rgba(255,255,255,.1);
    z-index: 9998;
    padding: 1rem;
    flex-direction: column;
    gap: .25rem;
    max-height: calc(100vh - 56px);
    overflow-y: auto;
  }
  .mob-drawer.mob-open { display: flex !important; }

  .mob-drawer-user {
    display: flex; align-items: center; gap: .8rem;
    padding: .9rem 1rem;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    margin-bottom: .5rem;
  }
  .mob-drawer-avatar {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg,#0ea5e9,#10b981);
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; font-weight: 800; color: #fff; flex-shrink: 0;
  }
  .mob-drawer-name  { font-size: .9rem; font-weight: 700; color: #f0f9ff; }
  .mob-drawer-email { font-size: .75rem; color: #475569; margin-top: .1rem; }

  .mob-drawer-link {
    display: flex; align-items: center; gap: .7rem;
    padding: .8rem 1rem;
    color: #94a3b8; text-decoration: none;
    font-size: .9rem; font-weight: 500;
    border-radius: 10px;
    border: 1px solid transparent;
    transition: background .15s, color .15s;
  }
  .mob-drawer-link:hover    { background: rgba(255,255,255,.06); color: #f0f9ff; }
  .mob-drawer-active        { color: #0ea5e9 !important; background: rgba(14,165,233,.1) !important; border-color: rgba(14,165,233,.2) !important; }
  .mob-drawer-logout        { color: #fca5a5 !important; }
  .mob-drawer-logout:hover  { background: rgba(248,113,113,.1) !important; }
  .mob-drawer-divider       { height: 1px; background: rgba(255,255,255,.08); margin: .3rem 0; }

  /* Bottom tab bar */
  .mob-tabbar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 60px;
    background: rgba(6,11,20,.97);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-top: 1px solid rgba(255,255,255,.1);
    z-index: 9997;
    align-items: center;
    justify-content: space-around;
    padding-bottom: env(safe-area-inset-bottom, 0px);
  }
  .mob-tab {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: .15rem; flex: 1;
    text-decoration: none;
    color: #475569;
    font-size: .58rem; font-weight: 600;
    padding: .3rem .1rem;
    transition: color .15s;
  }
  .mob-tab span { font-size: 1.25rem; line-height: 1; }
  .mob-tab-active { color: #0ea5e9 !important; }
  .mob-tab-dot {
    position: absolute; top: -2px; right: -4px;
    width: 8px; height: 8px; border-radius: 50%;
    background: #ef4444; display: block;
    font-style: normal;
  }

  /* Push content above tab bar */
  .page-wrap { padding-bottom: 68px; }

  /* Page padding */
  .page, .page-sm, .page-xs { padding: 1.2rem 1rem; }
  .page-title    { font-size: 1.4rem; }
  .page-subtitle { font-size: .85rem; margin-bottom: 1.2rem; }

  /* Grids → single column */
  .grid-2, .grid-3 { grid-template-columns: 1fr; gap: .9rem; }

  /* Modals → bottom sheet */
  .modal-overlay { align-items: flex-end; padding: 0; }
  .modal {
    max-width: 100%; width: 100%;
    border-radius: 20px 20px 0 0;
    max-height: 92vh; overflow-y: auto;
  }
  .modal::before {
    content: ''; display: block;
    width: 40px; height: 4px;
    background: rgba(255,255,255,.15);
    border-radius: 2px; margin: .8rem auto .2rem;
  }

  /* Buttons — bigger tap targets */
  .btn { min-height: 44px; padding: .7rem 1.2rem; font-size: .9rem; }

  /* Forms — prevent iOS zoom */
  .form-input, .form-select, .form-textarea { font-size: 16px; padding: .85rem 1rem; }

  /* Tabs — scrollable */
  .tabs { overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; gap: 0; }
  .tabs::-webkit-scrollbar { display: none; }
  .tab-btn { white-space: nowrap; flex-shrink: 0; padding: .65rem .9rem; font-size: .82rem; }

  /* Dashboard welcome */
  .welcome { flex-direction: column; align-items: flex-start; gap: 1rem; padding: 1.4rem; }
  .welcome-avatar { display: none; }

  /* Footer */
  .rich-footer-grid { grid-template-columns: 1fr; gap: 1.5rem; }
  .rich-footer { margin-bottom: 60px; }

  /* Flex between stack */
  .flex-between.mb-3 { flex-direction: column; align-items: flex-start; gap: .8rem; }

  /* Notification dropdown full width */
  .notif-dropdown {
    position: fixed !important;
    top: 56px !important; left: 0 !important; right: 0 !important;
    width: 100% !important;
    border-radius: 0 0 16px 16px;
    max-height: 70vh;
  }

  /* Video call controls */
  .ctrl-btn  { width: 56px !important; height: 56px !important; font-size: 1.3rem !important; }
  .ctrl-end  { width: 66px !important; height: 66px !important; font-size: 1.5rem !important; }
  .controls  { height: 96px !important; gap: .6rem !important; }
  #localVideo { width: 110px !important; height: 80px !important; bottom: 106px !important; right: 10px !important; }
  .call-topbar { padding: 0 .8rem !important; }
  .call-session-info { display: none !important; }
  .report-overlay { align-items: flex-end !important; }
  .report-modal { max-width: 100% !important; width: 100% !important; border-radius: 20px 20px 0 0 !important; }
  .reason-grid { grid-template-columns: 1fr !important; }

  /* Messaging */
  .chat-layout { flex-direction: column !important; height: calc(100vh - 56px - 60px) !important; }
  .sidebar { width: 100% !important; height: 100% !important; border-right: none !important; }
  .sidebar.mob-hidden { display: none !important; }
  .chat-area.mob-full { flex: 1 !important; }
  .chat-head { padding: .8rem 1rem !important; }
  .chat-messages { padding: .8rem 1rem !important; }
  .chat-input { padding: .8rem 1rem !important; }
  .mob-back-btn {
    display: flex !important;
    align-items: center; gap: .4rem;
    background: none; border: none;
    color: #0ea5e9; font-size: .9rem; font-weight: 600;
    cursor: pointer; padding: .3rem 0;
    font-family: var(--font); margin-right: .5rem;
  }
}
</style>

<script>
// ── Desktop user menu ──
function toggleUserMenu() {
  document.getElementById('userMenu').classList.toggle('open');
  document.getElementById('notifBell').classList.remove('open');
}

// ── Mobile drawer ──
function mobToggleDrawer() {
  const drawer = document.getElementById('mobDrawer');
  const btn    = document.getElementById('mobHamburger');
  drawer.classList.toggle('mob-open');
  btn.textContent = drawer.classList.contains('mob-open') ? '✕' : '☰';
}

// Close drawer when a link is tapped
document.querySelectorAll('.mob-drawer-link').forEach(a => {
  a.addEventListener('click', () => {
    document.getElementById('mobDrawer').classList.remove('mob-open');
    document.getElementById('mobHamburger').textContent = '☰';
  });
});

// ── Notifications ──
const NOTIF_ICONS = {
  request_received:'🤝', request_accepted:'✅', request_rejected:'❌',
  session_scheduled:'📅', message:'💬', default:'🔔'
};

function toggleNotifications() {
  const bell = document.getElementById('notifBell');
  const isOpen = bell.classList.contains('open');
  document.getElementById('userMenu').classList.remove('open');
  if (!isOpen) { bell.classList.add('open'); loadNotifications(); }
  else { bell.classList.remove('open'); }
}

function loadNotifications() {
  fetch('get_notifications.php')
    .then(r => r.json())
    .then(data => { updateBadge(data.count); renderNotifications(data.notifications); })
    .catch(() => { document.getElementById('notifList').innerHTML = '<div class="notif-loading">Could not load</div>'; });
}

function renderNotifications(notifications) {
  const list = document.getElementById('notifList');
  if (!notifications || notifications.length === 0) {
    list.innerHTML = '<div class="notif-empty"><div class="notif-empty-icon">🔔</div><p>No notifications yet</p></div>';
    return;
  }
  list.innerHTML = notifications.map(n => `
    <div class="notif-item ${n.is_read ? '' : 'unread'}" onclick="handleNotifClick(${n.id}, '${n.link || ''}')">
      <div class="notif-icon ${n.type}">${NOTIF_ICONS[n.type] || NOTIF_ICONS.default}</div>
      <div class="notif-content">
        <div class="notif-title">${escHtml(n.title)}</div>
        <div class="notif-msg">${escHtml(n.message)}</div>
        <div class="notif-time">${n.time_ago}</div>
      </div>
    </div>`).join('');
}

function handleNotifClick(id, link) {
  fetch('mark_notifications_read.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
  if (link) window.location.href = link;
  document.getElementById('notifBell').classList.remove('open');
}

function markAllRead() {
  fetch('mark_notifications_read.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({}) })
    .then(() => { updateBadge(0); document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread')); });
}

function updateBadge(count) {
  const badge = document.getElementById('notifBadge');
  if (count > 0) { badge.textContent = count > 99 ? '99+' : count; badge.style.display = 'flex'; }
  else { badge.style.display = 'none'; }
}

function escHtml(str) {
  const d = document.createElement('div'); d.textContent = str; return d.innerHTML;
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
  const bell = document.getElementById('notifBell');
  const menu = document.getElementById('userMenu');
  const drawer = document.getElementById('mobDrawer');
  const hamburger = document.getElementById('mobHamburger');
  if (bell && !bell.contains(e.target)) bell.classList.remove('open');
  if (menu && !menu.contains(e.target)) menu.classList.remove('open');
  if (drawer && !drawer.contains(e.target) && hamburger && !hamburger.contains(e.target)) {
    drawer.classList.remove('mob-open');
    hamburger.textContent = '☰';
  }
});

// Auto-fetch notification count
fetch('get_notifications.php').then(r=>r.json()).then(data=>updateBadge(data.count)).catch(()=>{});
setInterval(() => {
  fetch('get_notifications.php').then(r=>r.json()).then(data=>updateBadge(data.count)).catch(()=>{});
}, 30000);
</script>

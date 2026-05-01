<?php
require_once 'config.php';
check_admin();
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='user'"))['c'];
$total_skills   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM skills"))['c'];
$total_sessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM sessions"))['c'];
$total_certs    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM certificates"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Admin navbar — red accent */
    .admin-navbar {
      position: sticky; top: 0; z-index: 200;
      background: rgba(6,11,20,.85);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(248,113,113,.2);
      padding: 0 2rem; height: 64px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .admin-brand {
      font-size: 1.1rem; font-weight: 800; color: var(--text);
      display: flex; align-items: center; gap: .6rem; text-decoration: none;
    }
    .admin-badge-nav {
      background: rgba(248,113,113,.15);
      border: 1px solid rgba(248,113,113,.3);
      color: #fca5a5; font-size: .72rem; font-weight: 700;
      padding: .2rem .6rem; border-radius: 20px;
    }
    .admin-nav-links { display: flex; gap: .3rem; align-items: center; }
    .admin-nav-links a {
      color: var(--text-2); text-decoration: none; font-size: .875rem;
      font-weight: 500; padding: .45rem .85rem; border-radius: var(--radius-sm);
      transition: background .2s, color .2s;
    }
    .admin-nav-links a:hover { background: var(--glass-2); color: var(--text); }
    .admin-nav-links .logout {
      color: #fca5a5; border: 1px solid rgba(248,113,113,.25);
    }
    .admin-nav-links .logout:hover { background: rgba(248,113,113,.1); }

    /* Stats row */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.2rem; margin-bottom: 2rem;
    }
    .stat-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 1.5rem; text-align: center;
      transition: border-color .2s, box-shadow .2s;
    }
    .stat-card:hover { border-color: rgba(14,165,233,.3); box-shadow: 0 4px 20px rgba(14,165,233,.1); }
    .stat-num {
      font-size: 2.5rem; font-weight: 900;
      background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      line-height: 1; margin-bottom: .4rem;
    }
    .stat-label { font-size: .82rem; color: var(--text-2); font-weight: 500; text-transform: uppercase; letter-spacing: .06em; }

    /* Admin cards */
    .admin-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.2rem;
    }
    .admin-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 1.8rem; text-decoration: none; color: inherit;
      transition: border-color .2s, box-shadow .2s, transform .2s;
      display: flex; flex-direction: column; gap: .6rem;
    }
    .admin-card:hover {
      border-color: rgba(248,113,113,.3);
      box-shadow: 0 8px 32px rgba(248,113,113,.1);
      transform: translateY(-3px);
    }
    .admin-card-icon {
      width: 48px; height: 48px; border-radius: 14px;
      background: rgba(248,113,113,.12);
      border: 1px solid rgba(248,113,113,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; margin-bottom: .4rem;
    }
    .admin-card-title { font-size: 1.05rem; font-weight: 700; color: var(--text); }
    .admin-card-desc  { font-size: .85rem; color: var(--text-2); line-height: 1.55; flex: 1; }
    .admin-card-action { font-size: .8rem; font-weight: 600; color: #fca5a5; margin-top: .3rem; }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="page-wrap">

  <nav class="admin-navbar">
    <a class="admin-brand" href="admin_home.php">
      ⚙️ TechTalk Admin
      <span class="admin-badge-nav">ADMIN</span>
    </a>
    <div class="admin-nav-links">
      <a href="admin_user.php">Users</a>
      <a href="admin_skills.php">Skills</a>
      <a href="logout.php" class="logout">Sign out</a>
    </div>
  </nav>

  <div class="page">

    <h1 class="page-title">Admin Panel</h1>
    <p class="page-subtitle">Manage users, skills, and platform content</p>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-num"><?= $total_users ?></div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $total_skills ?></div>
        <div class="stat-label">Skills Listed</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $total_sessions ?></div>
        <div class="stat-label">Sessions</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $total_certs ?></div>
        <div class="stat-label">Certificates</div>
      </div>
    </div>

    <!-- Admin cards -->
    <div class="admin-grid">
      <a href="admin_user.php" class="admin-card">
        <div class="admin-card-icon">👤</div>
        <div class="admin-card-title">User Management</div>
        <div class="admin-card-desc">View, block, unblock, or delete registered users on the platform.</div>
        <div class="admin-card-action">Manage users →</div>
      </a>
      <a href="admin_skills.php" class="admin-card">
        <div class="admin-card-icon">🎓</div>
        <div class="admin-card-title">Skill Management</div>
        <div class="admin-card-desc">Add, view, or remove skills listed on the platform.</div>
        <div class="admin-card-action">Manage skills →</div>
      </a>
      <a href="admin_reports.php" class="admin-card">
        <div class="admin-card-icon">🚩</div>
        <div class="admin-card-title">User Reports</div>
        <div class="admin-card-desc">Review reports submitted during video call sessions. Watch recordings and take action.</div>
        <div class="admin-card-action" style="color:#fca5a5;">Review reports →</div>
      </a>
      <div class="admin-card" style="cursor:default;">
        <div class="admin-card-icon">🗑️</div>
        <div class="admin-card-title">Storage Cleanup</div>
        <div class="admin-card-desc">Delete session recordings older than 30 days to free up disk space.</div>
        <button class="btn btn-danger" style="margin-top:.5rem;font-size:.82rem;padding:.5rem 1rem;" onclick="runCleanup()">Run Cleanup Now</button>
        <div id="cleanupResult" style="font-size:.8rem;color:#6ee7b7;margin-top:.5rem;"></div>
      </div>
    </div>

  </div>
</div>

<script>
function runCleanup() {
  if (!confirm('Delete all recordings older than 30 days?')) return;
  const btn = event.target;
  btn.disabled = true;
  btn.textContent = 'Running...';
  fetch('cleanup_recordings.php')
    .then(r => r.json())
    .then(data => {
      document.getElementById('cleanupResult').textContent =
        data.success
          ? `✅ ${data.files_deleted} files deleted, ${data.freed_mb} MB freed`
          : '❌ Error: ' + (data.error || 'unknown');
      btn.textContent = 'Run Cleanup Now';
      btn.disabled = false;
    })
    .catch(() => {
      document.getElementById('cleanupResult').textContent = '❌ Request failed';
      btn.textContent = 'Run Cleanup Now';
      btn.disabled = false;
    });
}
</script>

</body>
</html>

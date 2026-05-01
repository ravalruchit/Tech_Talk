<?php
require_once 'config.php';
check_admin();

// Handle status update
if (isset($_GET['action'], $_GET['id'])) {
    $report_id = (int)$_GET['id'];
    $action    = in_array($_GET['action'], ['reviewed', 'resolved', 'pending']) ? $_GET['action'] : '';
    if ($action && $report_id > 0) {
        mysqli_query($conn, "UPDATE reports SET status = '$action' WHERE id = $report_id");
    }
    header("Location: admin_reports.php"); exit();
}

$reports = mysqli_query($conn,
    "SELECT r.*,
            reporter.Name  AS reporter_name,
            reporter.Email AS reporter_email,
            reported.Name  AS reported_name,
            reported.Email AS reported_email,
            s.session_date,
            s.recording_path,
            sk.title AS skill_title,
            sr.file_path AS reported_recording
     FROM reports r
     JOIN users reporter ON r.reporter_id = reporter.id
     JOIN users reported ON r.reported_id = reported.id
     JOIN sessions s ON r.session_id = s.id
     JOIN skills sk ON s.skill_id = sk.id
     LEFT JOIN session_recordings sr ON sr.session_id = r.session_id AND sr.recorder_id = r.reported_id
     ORDER BY r.created_at DESC"
);

$total    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM reports"))['c'];
$pending  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM reports WHERE status='pending'"))['c'];
$reviewed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM reports WHERE status='reviewed'"))['c'];
$resolved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM reports WHERE status='resolved'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports | TechTalk Admin</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .admin-navbar {
      position: sticky; top: 0; z-index: 200;
      background: rgba(6,11,20,.85); backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(248,113,113,.2);
      padding: 0 2rem; height: 64px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .admin-brand { font-size: 1.1rem; font-weight: 800; color: var(--text); display: flex; align-items: center; gap: .6rem; text-decoration: none; }
    .admin-badge-nav { background: rgba(248,113,113,.15); border: 1px solid rgba(248,113,113,.3); color: #fca5a5; font-size: .72rem; font-weight: 700; padding: .2rem .6rem; border-radius: 20px; }
    .admin-nav-links { display: flex; gap: .3rem; align-items: center; }
    .admin-nav-links a { color: var(--text-2); text-decoration: none; font-size: .875rem; font-weight: 500; padding: .45rem .85rem; border-radius: var(--radius-sm); transition: background .2s, color .2s; }
    .admin-nav-links a:hover { background: var(--glass-2); color: var(--text); }
    .admin-nav-links .logout { color: #fca5a5; border: 1px solid rgba(248,113,113,.25); }
    .admin-nav-links .logout:hover { background: rgba(248,113,113,.1); }

    .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: var(--glass); backdrop-filter: blur(16px); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.2rem 1.5rem; text-align: center; }
    .stat-num { font-size: 2rem; font-weight: 900; line-height: 1; margin-bottom: .3rem; }
    .stat-num.red    { color: #fca5a5; }
    .stat-num.yellow { color: #fcd34d; }
    .stat-num.green  { color: #6ee7b7; }
    .stat-num.teal   { background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .stat-label { font-size: .78rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; }

    .report-card { background: var(--glass); backdrop-filter: blur(16px); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.2rem; }
    .report-card.pending  { border-left: 3px solid #fbbf24; }
    .report-card.reviewed { border-left: 3px solid #0ea5e9; }
    .report-card.resolved { border-left: 3px solid #10b981; }

    .report-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
    .report-reason { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: .3rem; }
    .report-meta { font-size: .82rem; color: var(--text-2); }
    .report-meta strong { color: var(--text); }

    .user-pills { display: flex; gap: .8rem; flex-wrap: wrap; margin: .8rem 0; align-items: center; }
    .user-pill { display: flex; align-items: center; gap: .5rem; background: var(--glass-2); border: 1px solid var(--border-2); border-radius: var(--radius-sm); padding: .5rem .9rem; font-size: .82rem; }
    .user-pill-avatar { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; color: #fff; flex-shrink: 0; }
    .reporter-pill .user-pill-avatar { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    .reported-pill .user-pill-avatar { background: linear-gradient(135deg, #f87171, #ef4444); }
    .user-pill-name { font-weight: 600; color: var(--text); }
    .user-pill-role { font-size: .72rem; color: var(--text-3); }

    .report-desc { background: var(--glass-2); border: 1px solid var(--border-2); border-radius: var(--radius-sm); padding: .8rem 1rem; font-size: .875rem; color: var(--text-2); line-height: 1.6; margin: .8rem 0; }

    .recording-section { background: rgba(14,165,233,.06); border: 1px solid rgba(14,165,233,.2); border-radius: var(--radius-sm); padding: .8rem 1rem; margin: .8rem 0; display: flex; align-items: center; gap: .8rem; }
    .recording-info { flex: 1; }
    .recording-label { font-size: .75rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; }
    .recording-name  { font-size: .875rem; color: #7dd3fc; font-weight: 600; }

    .report-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }

    .video-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(6px); }
    .video-overlay.open { display: flex; }
    .video-modal { background: rgba(10,18,35,.97); backdrop-filter: blur(24px); border: 1px solid var(--border); border-radius: var(--radius-lg); width: 100%; max-width: 800px; overflow: hidden; box-shadow: 0 24px 60px rgba(0,0,0,.6); }
    .video-modal-head { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border-2); display: flex; justify-content: space-between; align-items: center; background: rgba(14,165,233,.06); }
    .video-modal-head h3 { font-size: 1rem; font-weight: 700; color: var(--text); }
    .video-modal-close { background: none; border: none; color: var(--text-3); font-size: 1.4rem; cursor: pointer; }
    .video-modal-body { padding: 1.5rem; }
    .video-modal-body video { width: 100%; border-radius: var(--radius-sm); background: #000; max-height: 450px; }
    .video-modal-foot { padding: 1rem 1.5rem; border-top: 1px solid var(--border-2); display: flex; justify-content: flex-end; }

    .empty-reports { background: var(--glass); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center; color: var(--text-3); }
    .empty-icon { font-size: 3rem; margin-bottom: .8rem; opacity: .4; }

    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr 1fr; } }
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
      ⚙️ TechTalk Admin <span class="admin-badge-nav">ADMIN</span>
    </a>
    <div class="admin-nav-links">
      <a href="admin_home.php">Dashboard</a>
      <a href="admin_user.php">Users</a>
      <a href="admin_skills.php">Skills</a>
      <a href="admin_reports.php" style="color:var(--text);font-weight:700;">🚩 Reports</a>
      <a href="logout.php" class="logout">Sign out</a>
    </div>
  </nav>

  <div class="page">

    <h1 class="page-title">🚩 User Reports</h1>
    <p class="page-subtitle">Review reports submitted during video call sessions</p>

    <div class="stats-row">
      <div class="stat-card"><div class="stat-num teal"><?= $total ?></div><div class="stat-label">Total</div></div>
      <div class="stat-card"><div class="stat-num red"><?= $pending ?></div><div class="stat-label">Pending</div></div>
      <div class="stat-card"><div class="stat-num yellow"><?= $reviewed ?></div><div class="stat-label">Reviewing</div></div>
      <div class="stat-card"><div class="stat-num green"><?= $resolved ?></div><div class="stat-label">Resolved</div></div>
    </div>

    <?php if (mysqli_num_rows($reports) > 0): ?>
      <?php while ($r = mysqli_fetch_assoc($reports)): ?>
        <div class="report-card <?= $r['status'] ?>">

          <div class="report-top">
            <div>
              <div class="report-reason">😡 <?= htmlspecialchars($r['reason']) ?></div>
              <div class="report-meta">
                Session: <strong><?= htmlspecialchars($r['skill_title']) ?></strong> &middot;
                <?= date('M d, Y g:i A', strtotime($r['created_at'])) ?>
              </div>
            </div>
            <span class="status status-<?= $r['status'] === 'pending' ? 'pending' : ($r['status'] === 'reviewed' ? 'completed' : 'accepted') ?>">
              <?= ucfirst($r['status']) ?>
            </span>
          </div>

          <div class="user-pills">
            <div class="user-pill reporter-pill">
              <div class="user-pill-avatar"><?= strtoupper(substr($r['reporter_name'], 0, 1)) ?></div>
              <div>
                <div class="user-pill-name"><?= htmlspecialchars($r['reporter_name']) ?></div>
                <div class="user-pill-role">🚩 Reporter</div>
              </div>
            </div>
            <span style="color:var(--text-3);font-size:.8rem;">reported →</span>
            <div class="user-pill reported-pill">
              <div class="user-pill-avatar"><?= strtoupper(substr($r['reported_name'], 0, 1)) ?></div>
              <div>
                <div class="user-pill-name"><?= htmlspecialchars($r['reported_name']) ?></div>
                <div class="user-pill-role">⚠️ Reported</div>
              </div>
            </div>
          </div>

          <?php if (!empty($r['description'])): ?>
            <div class="report-desc">
              <strong style="color:var(--text-3);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Description:</strong><br>
              <?= nl2br(htmlspecialchars($r['description'])) ?>
            </div>
          <?php endif; ?>

          <?php
          // Show reported person's recording first, fall back to any session recording
          $rec_path = !empty($r['reported_recording']) ? $r['reported_recording'] : $r['recording_path'];
          ?>
          <?php if (!empty($rec_path)): ?>
            <div class="recording-section">
              <span style="font-size:1.2rem;">🎥</span>
              <div class="recording-info">
                <div class="recording-label">
                  <?= !empty($r['reported_recording']) ? "Recording of reported user: " . htmlspecialchars($r['reported_name']) : "Session Recording" ?>
                </div>
                <div class="recording-name"><?= htmlspecialchars(basename($rec_path)) ?></div>
              </div>
              <button class="btn btn-primary" style="padding:.5rem 1rem;font-size:.82rem;"
                onclick="watchRecording('<?= htmlspecialchars($rec_path) ?>', '<?= htmlspecialchars(addslashes($r['skill_title'])) ?>')">
                ▶ Watch Recording
              </button>
            </div>
          <?php else: ?>
            <div style="font-size:.8rem;color:var(--text-3);margin:.5rem 0;">📭 No recording available for this session</div>
          <?php endif; ?>

          <div class="report-actions">
            <?php if ($r['status'] !== 'reviewed'): ?>
              <a href="?action=reviewed&id=<?= $r['id'] ?>" class="btn btn-glass" style="font-size:.82rem;padding:.5rem 1rem;color:#fcd34d;border-color:rgba(251,191,36,.3);">🔍 Mark Reviewing</a>
            <?php endif; ?>
            <?php if ($r['status'] !== 'resolved'): ?>
              <a href="?action=resolved&id=<?= $r['id'] ?>" class="btn btn-success" style="font-size:.82rem;padding:.5rem 1rem;" onclick="return confirm('Mark as resolved?')">✅ Resolve</a>
            <?php endif; ?>
            <?php if ($r['status'] !== 'pending'): ?>
              <a href="?action=pending&id=<?= $r['id'] ?>" class="btn btn-ghost" style="font-size:.82rem;padding:.5rem 1rem;">↩ Reopen</a>
            <?php endif; ?>
            <a href="admin_user.php" class="btn btn-danger" style="font-size:.82rem;padding:.5rem 1rem;">🚫 View Reported User</a>
          </div>

        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-reports">
        <div class="empty-icon">🎉</div>
        <h3 style="color:var(--text-2);margin-bottom:.4rem;">No reports yet</h3>
        <p>All sessions are running smoothly.</p>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Video Modal -->
<div class="video-overlay" id="videoOverlay">
  <div class="video-modal">
    <div class="video-modal-head">
      <h3 id="videoTitle">Session Recording</h3>
      <button class="video-modal-close" onclick="closeVideo()">✕</button>
    </div>
    <div class="video-modal-body">
      <video id="recordingPlayer" controls></video>
    </div>
    <div class="video-modal-foot">
      <button class="btn btn-glass" onclick="closeVideo()">Close</button>
    </div>
  </div>
</div>

<script>
  function watchRecording(path, title) {
    document.getElementById('videoTitle').textContent = 'Recording: ' + title;
    document.getElementById('recordingPlayer').src = path;
    document.getElementById('videoOverlay').classList.add('open');
  }
  function closeVideo() {
    const player = document.getElementById('recordingPlayer');
    player.pause();
    player.src = '';
    document.getElementById('videoOverlay').classList.remove('open');
  }
  document.getElementById('videoOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeVideo();
  });
</script>
</body>
</html>

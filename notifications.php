<?php
require_once 'config.php';
check_login();

$me_email = $_SESSION['email'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { header("Location: logout.php"); exit(); }
$me_id = (int)$me['id'];

// Mark all as read when page is opened
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $me_id");

$result = mysqli_query($conn,
    "SELECT * FROM notifications WHERE user_id = $me_id ORDER BY created_at DESC LIMIT 50"
);

$ICONS = [
    'request_received'  => '🤝',
    'request_accepted'  => '✅',
    'request_rejected'  => '❌',
    'session_scheduled' => '📅',
    'message'           => '💬',
];

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M d, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .notif-page-item {
      display: flex; align-items: flex-start; gap: 1rem;
      background: var(--glass); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.1rem 1.3rem;
      margin-bottom: .8rem; text-decoration: none; color: inherit;
      transition: border-color .2s, box-shadow .2s;
      cursor: pointer;
    }
    .notif-page-item:hover { border-color: rgba(14,165,233,.3); box-shadow: 0 4px 20px rgba(14,165,233,.08); }
    .notif-page-icon {
      width: 44px; height: 44px; border-radius: 12px;
      background: rgba(14,165,233,.12);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; flex-shrink: 0;
    }
    .notif-page-title { font-weight: 700; color: var(--text); margin-bottom: .2rem; }
    .notif-page-msg   { font-size: .88rem; color: var(--text-2); line-height: 1.5; }
    .notif-page-time  { font-size: .75rem; color: var(--text-3); margin-top: .3rem; }
    .empty-card {
      background: var(--glass); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 3rem 2rem;
      text-align: center; color: var(--text-3);
    }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="page-wrap">
  <?php include 'navbar.php'; ?>

  <div class="page page-sm">
    <h1 class="page-title">All Notifications</h1>
    <p class="page-subtitle">Your recent activity and updates</p>

    <?php if (mysqli_num_rows($result) > 0): ?>
      <?php while ($n = mysqli_fetch_assoc($result)): ?>
        <div class="notif-page-item"
             onclick="<?= $n['link'] ? "window.location.href='" . htmlspecialchars($n['link']) . "'" : '' ?>">
          <div class="notif-page-icon">
            <?= $ICONS[$n['type']] ?? '🔔' ?>
          </div>
          <div style="flex:1;">
            <div class="notif-page-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notif-page-msg"><?= htmlspecialchars($n['message']) ?></div>
            <div class="notif-page-time"><?= time_ago($n['created_at']) ?> &middot; <?= date('M d, Y g:i A', strtotime($n['created_at'])) ?></div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-card">
        <div style="font-size:2.5rem;margin-bottom:.8rem;opacity:.4;">🔔</div>
        <p>No notifications yet. Start by browsing the <a href="skill_market.php" style="color:var(--teal);">marketplace</a>!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>

<?php
require_once 'config.php';
check_admin();

if (isset($_GET['action'], $_GET['email'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    if ($_GET['action'] === 'block') {
        mysqli_query($conn, "UPDATE users SET status='blocked' WHERE Email='$email' AND role='user'");
    } elseif ($_GET['action'] === 'unblock') {
        mysqli_query($conn, "UPDATE users SET status='active' WHERE Email='$email' AND role='user'");
    } elseif ($_GET['action'] === 'delete') {
        // Get user id first for cascade cleanup
        $del_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email='$email' AND role='user'"));
        if ($del_user) {
            $del_id = (int)$del_user['id'];
            mysqli_query($conn, "DELETE FROM notifications WHERE user_id=$del_id");
            mysqli_query($conn, "DELETE FROM messages WHERE sender_id=$del_id OR receiver_id=$del_id");
            mysqli_query($conn, "DELETE FROM reviews WHERE reviewer_id=$del_id OR reviewed_id=$del_id");
            mysqli_query($conn, "DELETE FROM skill_requests WHERE sender_id=$del_id OR receiver_id=$del_id");
            mysqli_query($conn, "DELETE FROM skills WHERE email='$email'");
            mysqli_query($conn, "DELETE FROM users WHERE id=$del_id");
        }
    }
    header("Location: admin_user.php"); exit();
}

$result = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY Name ASC");
$total  = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | TechTalk Admin</title>
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

    /* Table */
    .table-wrap {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      overflow: hidden;
    }
    .table-head {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .table-head-title { font-size: 1rem; font-weight: 700; color: var(--text); }
    .table-head-count { font-size: .82rem; color: var(--text-3); }

    table { width: 100%; border-collapse: collapse; }
    thead tr { border-bottom: 1px solid var(--border); }
    thead th {
      padding: .85rem 1.2rem; text-align: left;
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--text-3);
    }
    tbody tr { border-bottom: 1px solid var(--border-2); transition: background .15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--glass-2); }
    tbody td { padding: .9rem 1.2rem; font-size: .875rem; color: var(--text-2); vertical-align: middle; }
    tbody td:first-child { color: var(--text); font-weight: 600; }

    .user-row-avatar {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--grad);
      display: inline-flex; align-items: center; justify-content: center;
      color: #fff; font-size: .8rem; font-weight: 700;
      margin-right: .6rem; vertical-align: middle;
    }
    .actions { display: flex; gap: .4rem; flex-wrap: wrap; }
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
      <a href="admin_skills.php">Skills</a>
      <a href="logout.php" class="logout">Sign out</a>
    </div>
  </nav>

  <div class="page">

    <h1 class="page-title">User Management</h1>
    <p class="page-subtitle">Block, unblock, or remove users from the platform</p>

    <div class="table-wrap">
      <div class="table-head">
        <div class="table-head-title">All Users</div>
        <div class="table-head-count"><?= $total ?> user<?= $total != 1 ? 's' : '' ?></div>
      </div>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td>
                <span class="user-row-avatar"><?= strtoupper(substr($row['Name'], 0, 1)) ?></span>
                <?= htmlspecialchars($row['Name']) ?>
              </td>
              <td><?= htmlspecialchars($row['Email']) ?></td>
              <td>
                <span class="status status-<?= $row['status'] === 'active' ? 'accepted' : 'rejected' ?>">
                  <?= ucfirst($row['status']) ?>
                </span>
              </td>
              <td>
                <div class="actions">
                  <?php if ($row['status'] === 'active'): ?>
                    <a href="?action=block&email=<?= urlencode($row['Email']) ?>" class="btn btn-danger" style="padding:.4rem .9rem;font-size:.78rem;" onclick="return confirm('Block this user?')">Block</a>
                  <?php else: ?>
                    <a href="?action=unblock&email=<?= urlencode($row['Email']) ?>" class="btn btn-success" style="padding:.4rem .9rem;font-size:.78rem;">Unblock</a>
                  <?php endif; ?>
                  <a href="?action=delete&email=<?= urlencode($row['Email']) ?>" class="btn btn-glass" style="padding:.4rem .9rem;font-size:.78rem;color:#fca5a5;" onclick="return confirm('Permanently delete this user?')">Delete</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

</body>
</html>

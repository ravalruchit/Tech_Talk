<?php
require_once 'config.php';
check_admin();

if (isset($_POST['add_skill'])) {
    $title    = mysqli_real_escape_string($conn, trim($_POST['skill_name']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $desc     = mysqli_real_escape_string($conn, trim($_POST['description']));
    mysqli_query($conn, "INSERT INTO skills (email, title, category, description, status) VALUES ('admin@techtalk.com', '$title', '$category', '$desc', 'approved')");
    header("Location: admin_skills.php"); exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM skills WHERE id = $id");
    header("Location: admin_skills.php"); exit();
}

$skills = mysqli_query($conn,
    "SELECT s.*, u.Name AS owner_name FROM skills s
     LEFT JOIN users u ON s.email = u.Email
     ORDER BY s.created_at DESC"
);
$total = mysqli_num_rows($skills);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skill Management | TechTalk Admin</title>
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

    .skills-layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 2rem; align-items: start;
    }
    @media (max-width: 900px) { .skills-layout { grid-template-columns: 1fr; } }

    .add-form-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 1.8rem; position: sticky; top: 84px;
    }
    .add-form-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 1.4rem; }

    .table-wrap {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      overflow: hidden;
    }
    .table-head {
      padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .table-head-title { font-size: 1rem; font-weight: 700; color: var(--text); }
    .table-head-count { font-size: .82rem; color: var(--text-3); }

    table { width: 100%; border-collapse: collapse; }
    thead tr { border-bottom: 1px solid var(--border); }
    thead th { padding: .85rem 1.2rem; text-align: left; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--text-3); }
    tbody tr { border-bottom: 1px solid var(--border-2); transition: background .15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--glass-2); }
    tbody td { padding: .9rem 1.2rem; font-size: .875rem; color: var(--text-2); vertical-align: middle; }
    tbody td:first-child { color: var(--text); font-weight: 600; }
    .skill-desc-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
      <a href="logout.php" class="logout">Sign out</a>
    </div>
  </nav>

  <div class="page">

    <h1 class="page-title">Skill Management</h1>
    <p class="page-subtitle">Add or remove skills listed on the platform</p>

    <div class="skills-layout">

      <!-- Add skill form -->
      <div class="add-form-card">
        <div class="add-form-title">➕ Add New Skill</div>
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Skill Name *</label>
            <input class="form-input" type="text" name="skill_name" required placeholder="e.g. Python Programming">
          </div>
          <div class="form-group">
            <label class="form-label">Category *</label>
            <select class="form-select" name="category" required>
              <option value="">Select category</option>
              <option value="Programming">Programming</option>
              <option value="Design">Design</option>
              <option value="Marketing">Marketing</option>
              <option value="Business">Business</option>
              <option value="Photography">Photography</option>
              <option value="Video Editing">Video Editing</option>
              <option value="Music">Music</option>
              <option value="Writing">Writing</option>
              <option value="Languages">Languages</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description" placeholder="Brief description..." style="min-height:80px;"></textarea>
          </div>
          <button type="submit" name="add_skill" class="btn btn-primary btn-full">Add Skill</button>
        </form>
      </div>

      <!-- Skills table -->
      <div class="table-wrap">
        <div class="table-head">
          <div class="table-head-title">All Skills</div>
          <div class="table-head-count"><?= $total ?> skill<?= $total != 1 ? 's' : '' ?></div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Owner</th>
              <th>Description</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($skills)): ?>
              <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><span class="badge badge-teal"><?= htmlspecialchars($row['category']) ?></span></td>
                <td><?= htmlspecialchars($row['owner_name'] ?? $row['email']) ?></td>
                <td class="skill-desc-cell"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
                <td>
                  <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger" style="padding:.4rem .9rem;font-size:.78rem;" onclick="return confirm('Delete this skill?')">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

</body>
</html>

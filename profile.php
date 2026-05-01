<?php
require_once 'config.php';
check_login();

$me_email = $_SESSION['email'];
$user     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE Email = '$me_email'"));
if (!$user) { header("Location: logout.php"); exit(); }

$flash = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = mysqli_real_escape_string($conn, trim($_POST['name']));
    $bio    = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $skills = mysqli_real_escape_string($conn, trim($_POST['skills']));

    if (empty($name)) {
        $flash = ['type' => 'error', 'text' => 'Name cannot be empty.'];
    } else {
        mysqli_query($conn, "UPDATE users SET Name = '$name', bio = '$bio', skills = '$skills' WHERE Email = '$me_email'");
        $_SESSION['name'] = $name;
        $flash = ['type' => 'success', 'text' => 'Profile updated!'];
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE Email = '$me_email'"));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .profile-wrap {
      max-width: 620px; margin: 0 auto; padding: 2.5rem 2rem;
    }
    .profile-card {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2rem;
    }
    .profile-card-title {
      font-size: 1.3rem; font-weight: 800;
      color: var(--text); letter-spacing: -.03em;
      margin-bottom: 1.8rem;
    }
    .avatar-row {
      display: flex; align-items: center; gap: 1.2rem;
      padding: 1.2rem;
      background: var(--glass-2);
      border: 1px solid var(--border-2);
      border-radius: var(--radius);
      margin-bottom: 1.8rem;
    }
    .profile-avatar {
      width: 56px; height: 56px; border-radius: 14px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.4rem; font-weight: 800;
      box-shadow: 0 4px 16px rgba(14,165,233,.35);
      flex-shrink: 0;
    }
    .avatar-info-name { font-weight: 700; color: var(--text); font-size: .95rem; }
    .avatar-info-email { font-size: .82rem; color: var(--text-3); margin-top: .15rem; }

    .form-disabled {
      width: 100%; padding: .75rem 1rem;
      background: rgba(255,255,255,.03);
      border: 1.5px solid var(--border-2);
      border-radius: var(--radius-sm);
      color: var(--text-3); font-size: .95rem;
      font-family: var(--font);
    }
    .btn-row { display: flex; gap: .8rem; margin-top: 1.8rem; }
    .btn-row .btn { flex: 1; padding: .8rem; justify-content: center; }
    .btn-row .btn-cancel {
      background: var(--glass-2); color: var(--text-2);
      border: 1px solid var(--border); border-radius: 20px;
      font-size: .875rem; font-weight: 600; cursor: pointer;
      text-decoration: none; display: flex; align-items: center; justify-content: center;
      transition: background .2s;
    }
    .btn-row .btn-cancel:hover { background: var(--glass-3); color: var(--text); }
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

  <div class="profile-wrap">

    <?php if ($flash['text']): ?>
      <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['text']) ?></div>
    <?php endif; ?>

    <div class="profile-card">
      <div class="profile-card-title">Edit Profile</div>

      <div class="avatar-row">
        <div class="profile-avatar"><?= strtoupper(substr($user['Name'], 0, 1)) ?></div>
        <div>
          <div class="avatar-info-name"><?= htmlspecialchars($user['Name']) ?></div>
          <div class="avatar-info-email"><?= htmlspecialchars($user['Email']) ?></div>
        </div>
      </div>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input class="form-input" type="text" name="name" required
                 value="<?= htmlspecialchars($user['Name']) ?>" placeholder="Your full name">
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="form-disabled"><?= htmlspecialchars($user['Email']) ?></div>
          <div class="form-hint">Email cannot be changed.</div>
        </div>

        <div class="form-group">
          <label class="form-label">Bio</label>
          <textarea class="form-textarea" name="bio" placeholder="Tell others about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Skills (comma separated)</label>
          <input class="form-input" type="text" name="skills"
                 value="<?= htmlspecialchars($user['skills'] ?? '') ?>"
                 placeholder="e.g. Python, Guitar, Cooking">
          <div class="form-hint">General skills shown on your public profile.</div>
        </div>

        <div class="btn-row">
          <button type="submit" class="btn btn-primary">💾 Save Changes</button>
          <a href="view_profile.php" class="btn-cancel">View Profile</a>
          <a href="dashbord.php" class="btn-cancel">Cancel</a>
        </div>
      </form>
    </div>

  </div>

  <?php include 'footer.php'; ?>
</div>

</body>
</html>

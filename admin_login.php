<?php
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_home.php");
    exit();
}

// Admin login — credentials from environment variables
// Set ADMIN_EMAIL and ADMIN_PASS in Railway environment settings
$adminEmail = getenv('ADMIN_EMAIL') ?: 'ruchit@techtalk.com';
$adminPass  = getenv('ADMIN_PASS')  ?: 'techtalk2025';
$flash = ['type' => '', 'text' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === $adminEmail && $password === $adminPass) {
        $_SESSION['role'] = "admin";
        header("Location: admin_home.php");
        exit();
    } else {
        $flash = ['type' => 'error', 'text' => 'Invalid admin credentials.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 1rem;
    }
    .login-card {
      position: relative; z-index: 1;
      background: rgba(6,11,20,.88);
      backdrop-filter: blur(24px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2.8rem 2.2rem;
      width: 100%; max-width: 420px;
      box-shadow: 0 24px 60px rgba(0,0,0,.5);
    }
    .admin-badge {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(248,113,113,.12);
      border: 1px solid rgba(248,113,113,.25);
      color: #fca5a5; font-size: .78rem; font-weight: 600;
      padding: .3rem .8rem; border-radius: 20px;
      margin-bottom: 1.5rem;
    }
    .login-heading { font-size: 1.8rem; font-weight: 900; color: var(--text); letter-spacing: -.04em; margin-bottom: .4rem; }
    .login-sub { color: var(--text-2); font-size: .875rem; margin-bottom: 2rem; }
    .back-link { display: block; text-align: center; margin-top: 1.2rem; font-size: .875rem; color: var(--text-3); text-decoration: none; }
    .back-link:hover { color: var(--teal); }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="login-card">
  <div class="admin-badge">🔐 Admin Access</div>
  <h1 class="login-heading">Admin Login</h1>
  <p class="login-sub">Restricted area — authorized personnel only</p>

  <?php if ($flash['text']): ?>
    <div class="flash <?= $flash['type'] ?>">⚠️ <?= htmlspecialchars($flash['text']) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Admin Email</label>
      <input class="form-input" type="email" name="email" required placeholder="admin@techtalk.com">
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-input" type="password" name="password" required placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;padding:.8rem;">
      Sign in as Admin →
    </button>
  </form>

  <a href="login.php" class="back-link">← Back to user login</a>
</div>

</body>
</html>

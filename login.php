<?php
require_once 'config.php';

// Already logged in → go to dashboard
if (isset($_SESSION['email'])) {
    header("Location: dashbord.php");
    exit();
}

$flash = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE Email = '$email'"));

    if (!$user) {
        $flash = ['type' => 'error', 'text' => 'No account found with this email.'];
    } elseif ($user['status'] === 'blocked') {
        $flash = ['type' => 'error', 'text' => 'Your account has been blocked. Contact admin.'];
    } elseif (!password_verify($password, $user['Password'])) {
        $flash = ['type' => 'error', 'text' => 'Incorrect password.'];
    } else {
        $_SESSION['email']   = $user['Email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['Name'];
        header("Location: dashbord.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { display: flex; min-height: 100vh; overflow: hidden; }

    /* ── Mesh background ── */
    .mesh-bg { position: fixed; inset: 0; z-index: 0; }

    /* ── Left panel ── */
    .left-panel {
      position: relative; z-index: 1;
      width: 460px; flex-shrink: 0;
      display: flex; flex-direction: column;
      justify-content: center; padding: 3rem;
      background: rgba(6,11,20,.85);
      backdrop-filter: blur(24px);
      border-right: 1px solid var(--border);
      overflow-y: auto;
    }

    .brand {
      display: flex; align-items: center; gap: .6rem;
      margin-bottom: 3rem;
    }
    .brand-icon {
      width: 38px; height: 38px;
      background: var(--grad);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
      box-shadow: 0 4px 16px rgba(14,165,233,.4);
    }
    .brand-name {
      font-size: 1.15rem; font-weight: 800;
      color: var(--text); letter-spacing: -.02em;
    }

    .login-heading {
      font-size: 2rem; font-weight: 900;
      color: var(--text); letter-spacing: -.04em;
      line-height: 1.15; margin-bottom: .5rem;
    }
    .login-heading span {
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .login-sub { color: var(--text-2); font-size: .9rem; margin-bottom: 2.5rem; }

    .admin-btn {
      width: 100%; padding: .7rem;
      background: var(--glass);
      border: 1px solid var(--border);
      border-radius: 20px;
      font-size: .875rem; font-weight: 600;
      color: var(--text-2); cursor: pointer;
      font-family: var(--font);
      backdrop-filter: blur(8px);
      transition: background .2s, border-color .2s, color .2s;
    }
    .admin-btn:hover {
      background: var(--glass-2);
      border-color: rgba(14,165,233,.3);
      color: var(--text);
    }

    .footer-text {
      text-align: center; font-size: .875rem;
      color: var(--text-3); margin-top: 1.5rem;
    }
    .footer-text a { color: var(--teal); text-decoration: none; font-weight: 600; }
    .footer-text a:hover { text-decoration: underline; }

    /* ── Right panel ── */
    .right-panel {
      flex: 1; position: relative; z-index: 1;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 3rem; overflow: hidden;
    }

    .right-content { text-align: center; max-width: 420px; }
    .right-content h2 {
      font-size: 2.4rem; font-weight: 900;
      color: var(--text); letter-spacing: -.04em;
      line-height: 1.2; margin-bottom: 1rem;
    }
    .right-content h2 span {
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .right-content p {
      color: var(--text-2); font-size: .95rem;
      line-height: 1.7; margin-bottom: 2.5rem;
    }

    .feature-cards { display: flex; flex-direction: column; gap: .8rem; }
    .feature-card {
      display: flex; align-items: center; gap: 1rem;
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1rem 1.2rem;
      text-align: left;
      transition: border-color .2s, background .2s;
    }
    .feature-card:hover {
      border-color: rgba(14,165,233,.3);
      background: var(--glass-2);
    }
    .feature-card-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(14,165,233,.3);
    }
    .feature-card-title { font-size: .9rem; font-weight: 700; color: var(--text); }
    .feature-card-desc  { font-size: .8rem; color: var(--text-2); margin-top: .15rem; }

    @media (max-width: 768px) {
      .left-panel { width: 100%; border-right: none; }
      .right-panel { display: none; }
    }
  </style>
</head>
<body>

<!-- Animated mesh -->
<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<!-- Left: form -->
<div class="left-panel">
  <div class="brand">
    <div class="brand-icon">🌟</div>
    <span class="brand-name">TechTalk</span>
  </div>

  <h1 class="login-heading">Welcome<br><span>back.</span></h1>
  <p class="login-sub">Sign in to continue your skill exchange journey</p>

  <?php if ($flash['text']): ?>
    <div class="flash <?= $flash['type'] ?>">
      <?= $flash['type'] === 'error' ? '⚠️' : '✅' ?>
      <?= htmlspecialchars($flash['text']) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Email address</label>
      <input class="form-input" type="email" name="email" required
             placeholder="you@example.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-input" type="password" name="password" required placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;padding:.8rem;">
      Sign in →
    </button>
  </form>

  <div class="divider">or</div>

  <button class="admin-btn" onclick="window.location.href='admin_login.php'">
    🔐 Continue as Admin
  </button>

  <p class="footer-text">
    No account? <a href="main.php">Create one free</a>
  </p>
</div>

<!-- Right: marketing -->
<div class="right-panel">
  <div class="right-content">
    <h2>Learn by teaching,<br><span>teach by learning.</span></h2>
    <p>Connect with people who have the skills you want — and share yours in return. No money, just knowledge.</p>

    <div class="feature-cards">
      <div class="feature-card">
        <div class="feature-card-icon">🔄</div>
        <div>
          <div class="feature-card-title">Real skill swaps</div>
          <div class="feature-card-desc">You teach what you know, they teach what you want</div>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-card-icon">📅</div>
        <div>
          <div class="feature-card-title">Auto-scheduled sessions</div>
          <div class="feature-card-desc">Accept a request and sessions are created instantly</div>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-card-icon">🎓</div>
        <div>
          <div class="feature-card-title">Earn certificates</div>
          <div class="feature-card-desc">Get verified proof of every skill you complete</div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>

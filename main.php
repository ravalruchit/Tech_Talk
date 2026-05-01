<?php
require_once 'config.php';
// Redirect if already logged in
if (isset($_SESSION['email'])) {
    header("Location: dashbord.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { display: flex; min-height: 100vh; overflow: hidden; }

    .left-panel {
      position: relative; z-index: 1;
      width: 460px; flex-shrink: 0;
      display: flex; flex-direction: column;
      justify-content: center; padding: 3rem;
      background: rgba(6,11,20,.88);
      backdrop-filter: blur(24px);
      border-right: 1px solid var(--border);
      overflow-y: auto;
    }
    .brand { display: flex; align-items: center; gap: .6rem; margin-bottom: 2.5rem; }
    .brand-icon {
      width: 38px; height: 38px; background: var(--grad);
      border-radius: 10px; display: flex; align-items: center;
      justify-content: center; font-size: 1.1rem;
      box-shadow: 0 4px 16px rgba(14,165,233,.4);
    }
    .brand-name { font-size: 1.15rem; font-weight: 800; color: var(--text); letter-spacing: -.02em; }

    .reg-heading {
      font-size: 2rem; font-weight: 900; color: var(--text);
      letter-spacing: -.04em; line-height: 1.15; margin-bottom: .5rem;
    }
    .reg-heading span { background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .reg-sub { color: var(--text-2); font-size: .9rem; margin-bottom: 2rem; }

    .footer-text { text-align: center; font-size: .875rem; color: var(--text-3); margin-top: 1.5rem; }
    .footer-text a { color: var(--teal); text-decoration: none; font-weight: 600; }
    .footer-text a:hover { text-decoration: underline; }

    .right-panel {
      flex: 1; position: relative; z-index: 1;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center; padding: 3rem;
    }
    .right-content { text-align: center; max-width: 400px; }
    .right-content h2 {
      font-size: 2.2rem; font-weight: 900; color: var(--text);
      letter-spacing: -.04em; line-height: 1.2; margin-bottom: 1rem;
    }
    .right-content h2 span { background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .right-content p { color: var(--text-2); font-size: .95rem; line-height: 1.7; margin-bottom: 2.5rem; }

    .steps { display: flex; flex-direction: column; gap: .8rem; text-align: left; }
    .step-item {
      display: flex; align-items: center; gap: .9rem;
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius);
      padding: .85rem 1.1rem;
      transition: border-color .2s;
    }
    .step-item:hover { border-color: rgba(14,165,233,.3); }
    .step-num {
      width: 28px; height: 28px; border-radius: 50%;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; font-weight: 800; color: #fff; flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(14,165,233,.35);
    }
    .step-text { font-size: .875rem; font-weight: 500; color: var(--text-2); }

    @media (max-width: 768px) {
      .left-panel { width: 100%; border-right: none; }
      .right-panel { display: none; }
    }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="left-panel">
  <div class="brand">
    <div class="brand-icon">🌟</div>
    <span class="brand-name">TechTalk</span>
  </div>

  <h1 class="reg-heading">Create your<br><span>account.</span></h1>
  <p class="reg-sub">Join thousands of people exchanging skills for free</p>

  <?php if(isset($_GET['success'])): ?>
    <div class="flash success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
  <?php elseif(isset($_GET['error'])): ?>
    <div class="flash error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="ragister.php">
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input class="form-input" type="text" name="name" required placeholder="Your full name">
    </div>
    <div class="form-group">
      <label class="form-label">Email address</label>
      <input class="form-input" type="email" name="email" required placeholder="you@example.com">
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-input" type="password" name="password" required placeholder="Min. 6 characters">
    </div>
    <div class="form-group">
      <label class="form-label">Confirm Password</label>
      <input class="form-input" type="password" name="confirm_password" required placeholder="Repeat your password">
    </div>
    <button type="submit" name="register" class="btn btn-primary btn-full" style="margin-top:.5rem;padding:.8rem;">
      Create Account →
    </button>
  </form>

  <p class="footer-text">Already have an account? <a href="login.php">Sign in</a></p>
</div>

<div class="right-panel">
  <div class="right-content">
    <h2>Start your<br><span>skill journey.</span></h2>
    <p>It only takes a minute. No credit card, no fees — just knowledge exchange.</p>
    <div class="steps">
      <div class="step-item"><div class="step-num">1</div><span class="step-text">Create your free account</span></div>
      <div class="step-item"><div class="step-num">2</div><span class="step-text">Add the skills you can teach</span></div>
      <div class="step-item"><div class="step-num">3</div><span class="step-text">Browse and request skills you want</span></div>
      <div class="step-item"><div class="step-num">4</div><span class="step-text">Start learning and earn certificates</span></div>
    </div>
  </div>
</div>

</body>
</html>
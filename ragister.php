<?php
require_once 'config.php';

if (isset($_SESSION['email'])) {
    header("Location: dashbord.php");
    exit();
}

$flash = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $flash = ['type' => 'error', 'text' => 'All fields are required.'];
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $name)) {
        $flash = ['type' => 'error', 'text' => 'Name must contain letters only.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash = ['type' => 'error', 'text' => 'Enter a valid email address.'];
    } elseif (strlen($password) < 6) {
        $flash = ['type' => 'error', 'text' => 'Password must be at least 6 characters.'];
    } elseif ($password !== $confirm) {
        $flash = ['type' => 'error', 'text' => 'Passwords do not match.'];
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $safe_name  = mysqli_real_escape_string($conn, $name);
        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '$safe_email'"));
        if ($exists) {
            $flash = ['type' => 'error', 'text' => 'An account with this email already exists.'];
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO users (Name, Email, Password, role, status) VALUES ('$safe_name', '$safe_email', '$hashed', 'user', 'active')");
            if (mysqli_affected_rows($conn) > 0) {
                $_SESSION['email']   = $email;
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['name']    = $name;
                header("Location: dashbord.php");
                exit();
            } else {
                $flash = ['type' => 'error', 'text' => 'Registration failed. Please try again.'];
            }
        }
    }
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
      width: 480px; flex-shrink: 0;
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

    .reg-heading { font-size: 1.9rem; font-weight: 900; color: var(--text); letter-spacing: -.04em; line-height: 1.15; margin-bottom: .4rem; }
    .reg-heading span { background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .reg-sub { color: var(--text-2); font-size: .875rem; margin-bottom: 2rem; }

    .footer-text { text-align: center; font-size: .875rem; color: var(--text-3); margin-top: 1.2rem; }
    .footer-text a { color: var(--teal); text-decoration: none; font-weight: 600; }
    .footer-text a:hover { text-decoration: underline; }

    .right-panel {
      flex: 1; position: relative; z-index: 1;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center; padding: 3rem;
    }
    .right-content { text-align: center; max-width: 420px; }
    .right-content h2 { font-size: 2.2rem; font-weight: 900; color: var(--text); letter-spacing: -.04em; line-height: 1.2; margin-bottom: 1rem; }
    .right-content h2 span { background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .right-content p { color: var(--text-2); font-size: .9rem; line-height: 1.7; margin-bottom: 2.5rem; }

    .step-cards { display: flex; flex-direction: column; gap: .8rem; }
    .step-card {
      display: flex; align-items: center; gap: 1rem;
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius);
      padding: 1rem 1.2rem; text-align: left;
      transition: border-color .2s, background .2s;
    }
    .step-card:hover { border-color: rgba(14,165,233,.3); background: var(--glass-2); }
    .step-number {
      width: 40px; height: 40px; border-radius: 10px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: .95rem; font-weight: 900; color: #fff; flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(14,165,233,.3);
    }
    .step-title { font-size: .9rem; font-weight: 700; color: var(--text); }
    .step-desc  { font-size: .8rem; color: var(--text-2); margin-top: .15rem; }

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
  <p class="reg-sub">Join the skill exchange community — it's free</p>

  <?php if ($flash['text']): ?>
    <div class="flash <?= $flash['type'] ?>">
      <?= $flash['type'] === 'error' ? '⚠️' : '✅' ?>
      <?= htmlspecialchars($flash['text']) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input class="form-input" type="text" name="name" required placeholder="Your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Email address</label>
      <input class="form-input" type="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-input" type="password" name="password" required placeholder="Min. 6 characters">
    </div>
    <div class="form-group">
      <label class="form-label">Confirm Password</label>
      <input class="form-input" type="password" name="confirm_password" required placeholder="Repeat your password">
    </div>
    <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;padding:.8rem;">
      Create Account →
    </button>
  </form>

  <p class="footer-text">Already have an account? <a href="login.php">Sign in here</a></p>
</div>

<div class="right-panel">
  <div class="right-content">
    <h2>Start your<br><span>skill journey.</span></h2>
    <p>Four simple steps to connect, learn, and grow with people who share your passion for knowledge.</p>
    <div class="step-cards">
      <div class="step-card"><div class="step-number">1</div><div><div class="step-title">Create your account</div><div class="step-desc">Sign up in seconds — no credit card needed</div></div></div>
      <div class="step-card"><div class="step-number">2</div><div><div class="step-title">Add your skills</div><div class="step-desc">List what you know and what you want to learn</div></div></div>
      <div class="step-card"><div class="step-number">3</div><div><div class="step-title">Find a match</div><div class="step-desc">Browse the marketplace and send a swap request</div></div></div>
      <div class="step-card"><div class="step-number">4</div><div><div class="step-title">Earn certificates</div><div class="step-desc">Complete sessions and get verified proof of your skills</div></div></div>
    </div>
  </div>
</div>

</body>
</html>

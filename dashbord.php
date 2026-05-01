<?php
require_once 'config.php';
check_login();

$me_email = $_SESSION['email'];
$me       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { header("Location: logout.php"); exit(); }
$me_id   = (int)$me['id'];
$me_name = $me['Name'];

// ─── Stats ────────────────────────────────────────────────────────────────────
$skills_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM skills WHERE email = '$me_email'"))['c'];

$pending_requests = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM skill_requests WHERE receiver_id = $me_id AND status = 'pending'"))['c'];

$unread_messages = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM messages WHERE receiver_id = $me_id AND is_read = 0"))['c'];

$upcoming_sessions = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM sessions
     WHERE (teacher_id = $me_id OR learner_id = $me_id) AND status = 'scheduled'"))['c'];

$certificates = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM certificates WHERE learner_id = $me_id"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Welcome ── */
    .welcome {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2rem 2.5rem;
      margin-bottom: 2rem;
      display: flex; align-items: center; justify-content: space-between;
      position: relative; overflow: hidden;
    }
    .welcome::before {
      content: '';
      position: absolute; top: -40px; right: -40px;
      width: 200px; height: 200px; border-radius: 50%;
      background: radial-gradient(circle, rgba(14,165,233,.15), transparent 70%);
    }
    .welcome-text { position: relative; z-index: 1; }
    .welcome-text h2 {
      font-size: 1.6rem; font-weight: 900;
      color: var(--text); letter-spacing: -.04em; margin-bottom: .3rem;
    }
    .welcome-text h2 span {
      background: var(--grad);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .welcome-text p { color: var(--text-2); font-size: .9rem; }
    .welcome-avatar {
      width: 56px; height: 56px; border-radius: 16px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.4rem; font-weight: 900;
      flex-shrink: 0; position: relative; z-index: 1;
      box-shadow: 0 8px 24px rgba(14,165,233,.4);
    }

    /* ── Section label ── */
    .section-label {
      font-size: .72rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      color: var(--text-3); margin-bottom: 1rem;
    }

    /* ── Dash card ── */
    .dash-card {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      display: flex; flex-direction: column; gap: .5rem;
      text-decoration: none; color: inherit;
      transition: border-color .2s, box-shadow .2s, transform .2s, background .2s;
      position: relative; overflow: hidden;
    }
    .dash-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 1px;
      background: var(--grad);
      opacity: 0; transition: opacity .2s;
    }
    .dash-card:hover {
      border-color: rgba(14,165,233,.35);
      box-shadow: 0 8px 32px rgba(14,165,233,.12);
      transform: translateY(-3px);
      background: var(--glass-2);
    }
    .dash-card:hover::before { opacity: 1; }

    .dash-card-top {
      display: flex; align-items: flex-start; justify-content: space-between;
    }
    .dash-card-icon {
      width: 42px; height: 42px; border-radius: 12px;
      background: rgba(14,165,233,.1);
      border: 1px solid rgba(14,165,233,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
    }
    .dash-card-title {
      font-size: .95rem; font-weight: 700;
      color: var(--text); margin-top: .8rem;
    }
    .dash-card-desc {
      font-size: .82rem; color: var(--text-2);
      line-height: 1.55; flex: 1;
    }
    .dash-card-action {
      font-size: .8rem; font-weight: 600;
      color: var(--teal); margin-top: .3rem;
      display: flex; align-items: center; gap: .3rem;
    }
  </style>
</head>
<body>

<!-- Mesh -->
<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="page-wrap">

  <?php include 'navbar.php'; ?>

  <div class="page">

    <div class="welcome">
      <div class="welcome-text">
        <h2>Hey, <span><?= htmlspecialchars($me_name) ?></span> 👋</h2>
        <p>Here's what's happening with your skill exchanges today.</p>
      </div>
      <div class="welcome-avatar"><?= strtoupper(substr($me_name, 0, 1)) ?></div>
    </div>

    <p class="section-label">Your workspace</p>

    <div class="grid-2">

      <a href="add_proof.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">📚</div>
          <?php if ($skills_count > 0): ?>
            <span class="badge badge-teal"><?= $skills_count ?> skills</span>
          <?php endif; ?>
        </div>
        <div class="dash-card-title">My Skills</div>
        <div class="dash-card-desc">Add and showcase your skills with proof links, GitHub, or portfolio.</div>
        <div class="dash-card-action">Manage skills →</div>
      </a>

      <a href="skill_market.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">🛒</div>
        </div>
        <div class="dash-card-title">Marketplace</div>
        <div class="dash-card-desc">Browse skills offered by others and send a swap request.</div>
        <div class="dash-card-action">Browse skills →</div>
      </a>

      <a href="requests.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">🤝</div>
          <?php if ($pending_requests > 0): ?>
            <span class="badge badge-count"><?= $pending_requests ?></span>
          <?php endif; ?>
        </div>
        <div class="dash-card-title">Requests</div>
        <div class="dash-card-desc">View incoming and outgoing skill swap requests.</div>
        <div class="dash-card-action">View requests →</div>
      </a>

      <a href="messaging.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">💬</div>
          <?php if ($unread_messages > 0): ?>
            <span class="badge badge-count"><?= $unread_messages ?></span>
          <?php endif; ?>
        </div>
        <div class="dash-card-title">Messages</div>
        <div class="dash-card-desc">Chat directly with your skill swap partners.</div>
        <div class="dash-card-action">Open chat →</div>
      </a>

      <a href="sessions.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">📅</div>
          <?php if ($upcoming_sessions > 0): ?>
            <span class="badge badge-green"><?= $upcoming_sessions ?> upcoming</span>
          <?php endif; ?>
        </div>
        <div class="dash-card-title">Sessions</div>
        <div class="dash-card-desc">Track your scheduled skill exchange sessions.</div>
        <div class="dash-card-action">View sessions →</div>
      </a>

      <a href="reviews.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">⭐</div>
        </div>
        <div class="dash-card-title">Reviews</div>
        <div class="dash-card-desc">See ratings and feedback from your exchange partners.</div>
        <div class="dash-card-action">Check reviews →</div>
      </a>

      <a href="certificates.php" class="dash-card">
        <div class="dash-card-top">
          <div class="dash-card-icon">🎓</div>
          <?php if ($certificates > 0): ?>
            <span class="badge badge-blue"><?= $certificates ?></span>
          <?php endif; ?>
        </div>
        <div class="dash-card-title">Certificates</div>
        <div class="dash-card-desc">Download certificates earned after completing sessions.</div>
        <div class="dash-card-action">View certificates →</div>
      </a>

    </div>
  </div>
</div>

<?php include 'footer.php'; ?>


</body>
</html>

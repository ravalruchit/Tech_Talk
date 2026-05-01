<?php
require_once 'config.php';
check_login();

$email = $_SESSION['email'];
$profile = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE Email = '$email'"));
if (!$profile) { header("Location: profile.php"); exit(); }

// Get user's skills from skills table
$my_skills_result = mysqli_query($conn, "SELECT * FROM skills WHERE email = '$email' ORDER BY created_at DESC");

// Get average rating
$rating_data = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(r.rating) AS avg_rating, COUNT(*) AS total
     FROM reviews r
     JOIN sessions s ON r.session_id = s.id
     JOIN users u ON r.reviewed_id = u.id
     WHERE u.Email = '$email'"
));
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = (int)($rating_data['total'] ?? 0);

// Get completed sessions count
$sessions_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM sessions s
     JOIN users u ON (s.teacher_id = u.id OR s.learner_id = u.id)
     WHERE u.Email = '$email' AND s.status = 'completed'"
))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($profile['Name']) ?> | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .profile-wrap { max-width: 720px; margin: 0 auto; padding: 2.5rem 2rem; }

    /* ── Profile header card ── */
    .profile-hero {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2rem;
      display: flex; align-items: center; gap: 1.8rem;
      margin-bottom: 1.5rem;
      position: relative; overflow: hidden;
    }
    .profile-hero::before {
      content: '';
      position: absolute; top: -40px; right: -40px;
      width: 180px; height: 180px; border-radius: 50%;
      background: radial-gradient(circle, rgba(14,165,233,.12), transparent 70%);
    }
    .profile-big-avatar {
      width: 80px; height: 80px; border-radius: 20px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 2rem; font-weight: 900;
      box-shadow: 0 8px 24px rgba(14,165,233,.4);
      flex-shrink: 0; position: relative; z-index: 1;
    }
    .profile-hero-info { position: relative; z-index: 1; flex: 1; }
    .profile-name { font-size: 1.5rem; font-weight: 800; color: var(--text); letter-spacing: -.03em; margin-bottom: .3rem; }
    .profile-email { font-size: .85rem; color: var(--text-3); margin-bottom: .8rem; }
    .profile-stats { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .stat-item { text-align: center; }
    .stat-num { font-size: 1.2rem; font-weight: 800; color: var(--teal); }
    .stat-label { font-size: .72rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; }

    /* ── Sections ── */
    .section-card {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      margin-bottom: 1.2rem;
    }
    .section-title {
      font-size: .72rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      color: var(--text-3); margin-bottom: 1rem;
    }
    .bio-text { font-size: .9rem; color: var(--text-2); line-height: 1.7; }
    .bio-empty { font-size: .9rem; color: var(--text-3); font-style: italic; }

    /* ── Skill tags ── */
    .skill-tags { display: flex; flex-wrap: wrap; gap: .6rem; }
    .skill-tag {
      padding: .3rem .85rem;
      background: rgba(14,165,233,.1);
      border: 1px solid rgba(14,165,233,.2);
      border-radius: 20px;
      font-size: .82rem; font-weight: 600; color: #7dd3fc;
    }

    /* ── Skill portfolio ── */
    .skill-portfolio-card {
      background: var(--glass-2);
      border: 1px solid var(--border-2);
      border-radius: var(--radius);
      padding: 1rem 1.2rem;
      margin-bottom: .8rem;
      display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    }
    .sp-title { font-size: .9rem; font-weight: 700; color: var(--text); margin-bottom: .3rem; }
    .sp-desc  { font-size: .82rem; color: var(--text-2); line-height: 1.5; }
    .sp-proof {
      display: inline-flex; align-items: center; gap: .3rem;
      font-size: .78rem; color: var(--teal); text-decoration: none;
      margin-top: .4rem;
    }
    .sp-proof:hover { text-decoration: underline; }

    /* ── Stars ── */
    .stars { color: #fbbf24; font-size: 1rem; letter-spacing: .05em; }

    /* ── Edit button ── */
    .edit-btn-wrap { margin-top: 1.5rem; }
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

    <!-- Hero -->
    <div class="profile-hero">
      <div class="profile-big-avatar"><?= strtoupper(substr($profile['Name'], 0, 1)) ?></div>
      <div class="profile-hero-info">
        <div class="profile-name"><?= htmlspecialchars($profile['Name']) ?></div>
        <div class="profile-email"><?= htmlspecialchars($profile['Email']) ?></div>
        <div class="profile-stats">
          <div class="stat-item">
            <div class="stat-num"><?= $sessions_count ?></div>
            <div class="stat-label">Sessions</div>
          </div>
          <div class="stat-item">
            <div class="stat-num"><?= $total_reviews ?></div>
            <div class="stat-label">Reviews</div>
          </div>
          <?php if ($avg_rating > 0): ?>
          <div class="stat-item">
            <div class="stat-num"><?= $avg_rating ?> ⭐</div>
            <div class="stat-label">Rating</div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Bio -->
    <div class="section-card">
      <div class="section-title">About</div>
      <?php if (!empty($profile['bio'])): ?>
        <div class="bio-text"><?= nl2br(htmlspecialchars($profile['bio'])) ?></div>
      <?php else: ?>
        <div class="bio-empty">No bio added yet.</div>
      <?php endif; ?>
    </div>

    <!-- General skills tags -->
    <?php if (!empty($profile['skills'])): ?>
    <div class="section-card">
      <div class="section-title">Skills</div>
      <div class="skill-tags">
        <?php foreach (array_filter(array_map('trim', explode(',', $profile['skills']))) as $s): ?>
          <span class="skill-tag"><?= htmlspecialchars($s) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Skill portfolio -->
    <?php if (mysqli_num_rows($my_skills_result) > 0): ?>
    <div class="section-card">
      <div class="section-title">Skill Portfolio</div>
      <?php while ($sk = mysqli_fetch_assoc($my_skills_result)): ?>
        <div class="skill-portfolio-card">
          <div>
            <div class="sp-title"><?= htmlspecialchars($sk['title']) ?></div>
            <div class="sp-desc"><?= nl2br(htmlspecialchars($sk['description'])) ?></div>
            <?php if (!empty($sk['proof_link'])): ?>
              <a href="<?= htmlspecialchars($sk['proof_link']) ?>" target="_blank" class="sp-proof">🔗 View Proof</a>
            <?php endif; ?>
          </div>
          <span class="badge badge-teal"><?= htmlspecialchars($sk['category']) ?></span>
        </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Edit button -->
    <div class="edit-btn-wrap">
      <a href="profile.php" class="btn btn-primary btn-full">✏️ Edit Profile</a>
    </div>

  </div>

  <?php include 'footer.php'; ?>
</div>

</body>
</html>

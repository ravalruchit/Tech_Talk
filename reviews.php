<?php
require_once 'config.php';
check_login();

$user_email = $_SESSION['email'];
$message = '';
$error = '';

$user_query = "SELECT id FROM users WHERE Email = '$user_email'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    $session_id  = (int)$_POST['session_id'];
    $reviewed_id = (int)$_POST['reviewed_id'];
    $rating      = (int)$_POST['rating'];
    $review_text = mysqli_real_escape_string($conn, trim($_POST['review_text']));
    $check_result = mysqli_query($conn, "SELECT id FROM reviews WHERE session_id = $session_id AND reviewer_id = $user_id");
    if (mysqli_num_rows($check_result) == 0) {
        if (mysqli_query($conn, "INSERT INTO reviews (session_id, reviewer_id, reviewed_id, rating, review_text) VALUES ($session_id, $user_id, $reviewed_id, $rating, '$review_text')")) {
            $message = "Review submitted successfully!";
        } else { $error = "Error submitting review."; }
    } else { $error = "You have already reviewed this session."; }
}

$received_result = mysqli_query($conn,
    "SELECT r.*, u.Name as reviewer_name, sk.title as skill_title
     FROM reviews r JOIN users u ON r.reviewer_id = u.id
     JOIN sessions s ON r.session_id = s.id JOIN skills sk ON s.skill_id = sk.id
     WHERE r.reviewed_id = $user_id ORDER BY r.created_at DESC");

$avg_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewed_id = $user_id"));
$avg_rating = round($avg_result['avg_rating'], 1);
$total_reviews = $avg_result['total_reviews'];

$given_result = mysqli_query($conn,
    "SELECT r.*, u.Name as reviewed_name, sk.title as skill_title
     FROM reviews r JOIN users u ON r.reviewed_id = u.id
     JOIN sessions s ON r.session_id = s.id JOIN skills sk ON s.skill_id = sk.id
     WHERE r.reviewer_id = $user_id ORDER BY r.created_at DESC");

$pending_reviews_result = mysqli_query($conn,
    "SELECT s.*, sk.title as skill_title,
     CASE WHEN s.teacher_id = $user_id THEN l.id ELSE t.id END as other_user_id,
     CASE WHEN s.teacher_id = $user_id THEN l.Name ELSE t.Name END as other_username,
     CASE WHEN s.teacher_id = $user_id THEN 'learner' ELSE 'teacher' END as role
     FROM sessions s JOIN skills sk ON s.skill_id = sk.id
     JOIN users t ON s.teacher_id = t.id JOIN users l ON s.learner_id = l.id
     WHERE (s.teacher_id = $user_id OR s.learner_id = $user_id)
     AND s.status = 'completed'
     AND s.id NOT IN (SELECT session_id FROM reviews WHERE reviewer_id = $user_id)
     ORDER BY s.session_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .rating-summary-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 2rem; text-align: center; margin-bottom: 1.5rem;
    }
    .rating-big {
      font-size: 3.5rem; font-weight: 900;
      background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      line-height: 1; margin-bottom: .4rem;
    }
    .rating-stars-display { font-size: 1.8rem; color: #fbbf24; margin-bottom: .5rem; letter-spacing: .1em; }
    .rating-count { color: var(--text-2); font-size: .9rem; }

    .review-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 1.5rem; margin-bottom: 1rem;
      transition: border-color .2s, box-shadow .2s;
    }
    .review-card:hover { border-color: rgba(14,165,233,.25); box-shadow: 0 4px 20px rgba(14,165,233,.08); }
    .review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
    .reviewer-info { display: flex; align-items: center; gap: .85rem; }
    .reviewer-avatar {
      width: 46px; height: 46px; border-radius: 12px;
      background: var(--grad); display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(14,165,233,.3);
    }
    .reviewer-name { font-size: .95rem; font-weight: 700; color: var(--text); margin-bottom: .15rem; }
    .reviewer-skill { font-size: .8rem; color: var(--text-2); }
    .review-stars { font-size: 1.1rem; color: #fbbf24; letter-spacing: .05em; }
    .review-text { color: var(--text-2); font-size: .9rem; line-height: 1.65; margin-bottom: .75rem; }
    .review-date { font-size: .78rem; color: var(--text-3); }

    .pending-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 1.4rem 1.5rem; margin-bottom: 1rem;
      display: flex; align-items: center; justify-content: space-between; gap: 1rem;
      transition: border-color .2s;
    }
    .pending-card:hover { border-color: rgba(14,165,233,.25); }
    .pending-skill { font-size: .95rem; font-weight: 700; color: var(--text); margin-bottom: .25rem; }
    .pending-meta { font-size: .82rem; color: var(--text-2); }
    .pending-meta strong { color: var(--text); }

    .star-selector { display: flex; gap: .4rem; font-size: 2rem; margin-bottom: .5rem; }
    .star-selector .star { cursor: pointer; color: var(--text-3); transition: color .15s, transform .1s; line-height: 1; }
    .star-selector .star:hover, .star-selector .star.active { color: #fbbf24; }
    .star-selector .star:hover { transform: scale(1.15); }
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

  <div class="page">

    <h1 class="page-title">⭐ Reviews</h1>
    <p class="page-subtitle">Your ratings and feedback from skill exchange partners</p>

    <?php if ($message): ?><div class="flash success">✅ <?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="flash error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($total_reviews > 0): ?>
      <div class="rating-summary-card">
        <div class="rating-big"><?= $avg_rating ?></div>
        <div class="rating-stars-display">
          <?php for ($i = 1; $i <= 5; $i++): ?><?= ($i <= round($avg_rating)) ? '★' : '☆' ?><?php endfor; ?>
        </div>
        <div class="rating-count">Based on <?= $total_reviews ?> review<?= ($total_reviews != 1) ? 's' : '' ?></div>
      </div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('received', this)">📥 Reviews Received</button>
      <button class="tab-btn" onclick="showTab('given', this)">📤 Reviews Given</button>
      <button class="tab-btn" onclick="showTab('pending', this)">✍️ Pending Reviews</button>
    </div>

    <div id="received" class="tab-pane active">
      <?php if (mysqli_num_rows($received_result) > 0): ?>
        <?php while ($review = mysqli_fetch_assoc($received_result)): ?>
          <div class="review-card">
            <div class="review-header">
              <div class="reviewer-info">
                <div class="reviewer-avatar"><?= strtoupper(substr($review['reviewer_name'], 0, 1)) ?></div>
                <div>
                  <div class="reviewer-name"><?= htmlspecialchars($review['reviewer_name']) ?></div>
                  <div class="reviewer-skill">for <?= htmlspecialchars($review['skill_title']) ?></div>
                </div>
              </div>
              <div class="review-stars"><?php for ($i=1;$i<=5;$i++) echo ($i<=$review['rating'])?'★':'☆'; ?></div>
            </div>
            <?php if (!empty($review['review_text'])): ?>
              <div class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
            <?php endif; ?>
            <div class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state"><div class="empty-state-icon">📭</div><h3>No reviews received yet</h3><p>Complete skill exchange sessions to start receiving feedback.</p></div>
      <?php endif; ?>
    </div>

    <div id="given" class="tab-pane">
      <?php if (mysqli_num_rows($given_result) > 0): ?>
        <?php while ($review = mysqli_fetch_assoc($given_result)): ?>
          <div class="review-card">
            <div class="review-header">
              <div class="reviewer-info">
                <div class="reviewer-avatar"><?= strtoupper(substr($review['reviewed_name'], 0, 1)) ?></div>
                <div>
                  <div class="reviewer-name">Review for <?= htmlspecialchars($review['reviewed_name']) ?></div>
                  <div class="reviewer-skill"><?= htmlspecialchars($review['skill_title']) ?></div>
                </div>
              </div>
              <div class="review-stars"><?php for ($i=1;$i<=5;$i++) echo ($i<=$review['rating'])?'★':'☆'; ?></div>
            </div>
            <?php if (!empty($review['review_text'])): ?>
              <div class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
            <?php endif; ?>
            <div class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state"><div class="empty-state-icon">✍️</div><h3>No reviews given yet</h3></div>
      <?php endif; ?>
    </div>

    <div id="pending" class="tab-pane">
      <?php if (mysqli_num_rows($pending_reviews_result) > 0): ?>
        <?php while ($session = mysqli_fetch_assoc($pending_reviews_result)): ?>
          <div class="pending-card">
            <div>
              <div class="pending-skill"><?= htmlspecialchars($session['skill_title']) ?></div>
              <div class="pending-meta">With: <strong><?= htmlspecialchars($session['other_username']) ?></strong> (<?= ucfirst($session['role']) ?>) &middot; <?= date('M d, Y', strtotime($session['session_date'])) ?></div>
            </div>
            <button onclick="openReviewModal(<?= $session['id'] ?>, <?= $session['other_user_id'] ?>, '<?= htmlspecialchars(addslashes($session['skill_title'])) ?>', '<?= htmlspecialchars(addslashes($session['other_username'])) ?>')" class="btn btn-primary">Leave Review</button>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state"><div class="empty-state-icon">🎉</div><h3>All caught up!</h3><p>No pending reviews.</p></div>
      <?php endif; ?>
    </div>

  </div>

  <?php include 'footer.php'; ?>
</div>

<!-- Review Modal -->
<div class="modal-overlay" id="reviewModalOverlay">
  <div class="modal">
    <div class="modal-head">
      <h3>Leave a Review</h3>
      <button class="modal-close" onclick="closeReviewModal()">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="session_id"  id="modal_session_id">
        <input type="hidden" name="reviewed_id" id="modal_reviewed_id">
        <input type="hidden" name="rating"       id="rating_value" value="5">
        <div class="form-group">
          <div class="form-label">Skill</div>
          <div style="color:var(--text);font-weight:600;" id="modal_skill_title"></div>
          <div style="font-size:.82rem;color:var(--text-2);margin-top:.25rem;">Reviewing: <strong id="modal_reviewed_name" style="color:var(--text);"></strong></div>
        </div>
        <div class="form-group">
          <div class="form-label">Your Rating</div>
          <div class="star-selector" id="star_rating">
            <span class="star active" data-rating="1">★</span>
            <span class="star active" data-rating="2">★</span>
            <span class="star active" data-rating="3">★</span>
            <span class="star active" data-rating="4">★</span>
            <span class="star active" data-rating="5">★</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="review_text">Your Review</label>
          <textarea class="form-textarea" name="review_text" id="review_text" placeholder="Share your experience..." required></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-glass" onclick="closeReviewModal()">Cancel</button>
        <button type="submit" name="add_review" class="btn btn-primary">Submit Review</button>
      </div>
    </form>
  </div>
</div>

<script>
  function showTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(name).classList.add('active');
    btn.classList.add('active');
  }
  function openReviewModal(sessionId, reviewedId, skillTitle, reviewedName) {
    document.getElementById('modal_session_id').value = sessionId;
    document.getElementById('modal_reviewed_id').value = reviewedId;
    document.getElementById('modal_skill_title').textContent = skillTitle;
    document.getElementById('modal_reviewed_name').textContent = reviewedName;
    setStars(5);
    document.getElementById('reviewModalOverlay').classList.add('open');
  }
  function closeReviewModal() { document.getElementById('reviewModalOverlay').classList.remove('open'); }
  document.getElementById('reviewModalOverlay').addEventListener('click', function(e) { if (e.target === this) closeReviewModal(); });
  function setStars(rating) {
    document.getElementById('rating_value').value = rating;
    document.querySelectorAll('#star_rating .star').forEach(s => {
      s.classList.toggle('active', parseInt(s.getAttribute('data-rating')) <= rating);
    });
  }
  document.querySelectorAll('#star_rating .star').forEach(star => {
    star.addEventListener('click', function() { setStars(parseInt(this.getAttribute('data-rating'))); });
    star.addEventListener('mouseenter', function() {
      const r = parseInt(this.getAttribute('data-rating'));
      document.querySelectorAll('#star_rating .star').forEach(s => {
        s.style.color = parseInt(s.getAttribute('data-rating')) <= r ? '#fbbf24' : '';
      });
    });
    star.addEventListener('mouseleave', function() {
      const current = parseInt(document.getElementById('rating_value').value);
      document.querySelectorAll('#star_rating .star').forEach(s => {
        s.style.color = '';
        s.classList.toggle('active', parseInt(s.getAttribute('data-rating')) <= current);
      });
    });
  });
</script>
</body>
</html>

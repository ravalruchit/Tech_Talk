<?php
require_once 'config.php';
check_login();

// ─── Current user ────────────────────────────────────────────────────────────
$me_email = $_SESSION['email'];
$me       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { die("Session error. Please log in again."); }
$me_id   = (int)$me['id'];
$me_name = $me['Name'];

// ─── My skills (for the "offering" dropdown) ─────────────────────────────────
$my_skills = [];
$r = mysqli_query($conn, "SELECT id, title FROM skills WHERE email = '$me_email' ORDER BY title ASC");
while ($row = mysqli_fetch_assoc($r)) { $my_skills[] = $row; }

// ─── Handle send request ─────────────────────────────────────────────────────
$flash = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $skill_id         = (int)$_POST['skill_id'];
    $offered_skill_id = (int)$_POST['offered_skill_id'];
    $msg              = mysqli_real_escape_string($conn, trim($_POST['message']));
    $receiver_email   = mysqli_real_escape_string($conn, trim($_POST['receiver_email']));

    if ($skill_id <= 0 || $offered_skill_id <= 0 || $msg === '' || $receiver_email === '') {
        $flash = ['type' => 'error', 'text' => 'All fields are required.'];
    } else {
        $recv = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '$receiver_email'"));

        if (!$recv) {
            $flash = ['type' => 'error', 'text' => 'Skill owner not found.'];
        } elseif ((int)$recv['id'] === $me_id) {
            $flash = ['type' => 'error', 'text' => 'You cannot send a request to yourself.'];
        } else {
            $recv_id = (int)$recv['id'];
            $dup = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id FROM skill_requests
                 WHERE sender_id = $me_id AND receiver_id = $recv_id
                   AND skill_id = $skill_id AND status = 'pending'"
            ));
            if ($dup) {
                $flash = ['type' => 'error', 'text' => 'You already have a pending request for this skill.'];
            } else {
                mysqli_query($conn,
                    "INSERT INTO skill_requests
                        (sender_id, receiver_id, skill_id, offered_skill_id, message, status)
                     VALUES
                        ($me_id, $recv_id, $skill_id, $offered_skill_id, '$msg', 'pending')"
                );
                if (mysqli_affected_rows($conn) > 0) {
                    $flash = ['type' => 'success', 'text' => 'Request sent! The skill owner will review it shortly.'];

                    // Create notification for the receiver
                    $my_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Name FROM users WHERE id=$me_id"))['Name'] ?? 'Someone';
                    $skill_title = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM skills WHERE id=$skill_id"))['title'] ?? 'a skill';
                    create_notification($conn, $recv_id, 'request_received',
                        '🤝 New Skill Request',
                        $my_name . ' wants to learn ' . $skill_title . ' from you',
                        'requests.php'
                    );
                } else {
                    $flash = ['type' => 'error', 'text' => 'Could not send request: ' . mysqli_error($conn)];
                }
            }
        }
    }
}

// ─── Search / filter ─────────────────────────────────────────────────────────
$search   = isset($_GET['search'])   ? mysqli_real_escape_string($conn, trim($_GET['search']))   : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, trim($_GET['category'])) : '';

$where = ["s.email != '$me_email'"];
if ($search   !== '') $where[] = "(s.title LIKE '%$search%' OR s.description LIKE '%$search%' OR u.Name LIKE '%$search%')";
if ($category !== '') $where[] = "s.category = '$category'";
$where_sql = implode(' AND ', $where);

$skills_result = mysqli_query($conn,
    "SELECT s.*, u.Name AS teacher_name, u.Email AS teacher_email,
            (SELECT COUNT(*) FROM skill_requests
             WHERE skill_id = s.id AND status = 'accepted') AS completed_exchanges
     FROM skills s
     LEFT JOIN users u ON s.email = u.Email
     WHERE $where_sql
     ORDER BY s.created_at DESC"
);

$cats_result = null; // categories now use predefined list
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skill Marketplace | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .skill-card {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      display: flex; flex-direction: column;
      transition: border-color .2s, box-shadow .2s, transform .2s, background .2s;
      overflow: hidden;
    }
    .skill-card:hover {
      border-color: rgba(14,165,233,.35);
      box-shadow: 0 8px 32px rgba(14,165,233,.12);
      transform: translateY(-3px);
      background: var(--glass-2);
    }

    .card-head { padding: 1.3rem 1.4rem .8rem; }
    .card-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: .5rem; }

    .card-body { padding: .4rem 1.4rem; flex: 1; }
    .card-desc { color: var(--text-2); font-size: .85rem; line-height: 1.6; margin-bottom: 1rem; }

    .teacher-row {
      display: flex; align-items: center; gap: .7rem;
      background: rgba(255,255,255,.03);
      border: 1px solid var(--border-2);
      padding: .65rem .9rem; border-radius: var(--radius-sm);
      margin-bottom: .8rem;
    }
    .teacher-name  { font-weight: 600; font-size: .85rem; color: var(--text); }
    .teacher-stats { font-size: .76rem; color: var(--text-3); }

    .proof-link {
      display: inline-flex; align-items: center; gap: .3rem;
      color: var(--teal); font-size: .82rem;
      text-decoration: none; margin-bottom: .8rem; font-weight: 500;
    }
    .proof-link:hover { text-decoration: underline; }

    .card-foot { padding: .8rem 1.4rem 1.3rem; }

    .search-section {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.1rem 1.4rem;
      margin-bottom: 1.5rem;
    }
    .search-section form { display: flex; gap: .6rem; flex-wrap: wrap; }
    .search-section .form-input { flex: 1; min-width: 200px; }
    .search-section .form-select { min-width: 160px; }
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

<?php if ($flash['text']): ?>
  <div style="padding:0 2rem;">
    <div class="flash <?= $flash['type'] ?>" style="margin-top:1.5rem;">
      <?= $flash['type'] === 'error' ? '⚠️' : '✅' ?>
      <?= htmlspecialchars($flash['text']) ?>
    </div>
  </div>
<?php endif; ?>

<div class="page">

  <div class="flex-between mb-3">
    <div>
      <h1 class="page-title">Skill Marketplace</h1>
      <p class="page-subtitle">Find someone to learn from — and offer your own skill in return</p>
    </div>
    <a href="add_proof.php" class="btn btn-glass">+ Add my skill</a>
  </div>

  <div class="search-section">
    <form method="GET">
      <input class="form-input" type="text" name="search"
             placeholder="🔍 Search skills, teachers..."
             value="<?= htmlspecialchars($search) ?>">
      <select class="form-select" name="category">
        <option value="">All categories</option>
        <?php
        $all_cats = ['Programming','Design','Marketing','Business','Photography','Video Editing','Music','Writing','Languages','Other'];
        foreach ($all_cats as $cat):
        ?>
          <option value="<?= htmlspecialchars($cat) ?>"
            <?= $category === $cat ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($search !== '' || $category !== ''): ?>
        <a href="skill_market.php" class="btn btn-ghost">✕ Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="grid-3">
    <?php if (mysqli_num_rows($skills_result) > 0): ?>
      <?php while ($skill = mysqli_fetch_assoc($skills_result)): ?>
        <div class="skill-card">
          <div class="card-head">
            <div class="flex-between">
              <div class="card-title"><?= htmlspecialchars($skill['title']) ?></div>
              <span class="badge badge-teal"><?= htmlspecialchars($skill['category']) ?></span>
            </div>
          </div>
          <div class="card-body">
            <div class="card-desc"><?= nl2br(htmlspecialchars($skill['description'])) ?></div>
            <div class="teacher-row">
              <div class="avatar avatar-sm"><?= strtoupper(substr($skill['teacher_name'] ?? 'U', 0, 1)) ?></div>
              <div>
                <div class="teacher-name"><?= htmlspecialchars($skill['teacher_name'] ?? 'Unknown') ?></div>
                <div class="teacher-stats"><?= (int)$skill['completed_exchanges'] ?> completed exchanges</div>
              </div>
            </div>
            <?php if (!empty($skill['proof_link'])): ?>
              <a href="<?= htmlspecialchars($skill['proof_link']) ?>" target="_blank" class="proof-link">
                🔗 View proof
              </a>
            <?php endif; ?>
          </div>
          <div class="card-foot">
            <button class="btn btn-primary btn-full" onclick="openModal(
              <?= $skill['id'] ?>,
              '<?= addslashes(htmlspecialchars($skill['title'])) ?>',
              '<?= addslashes(htmlspecialchars($skill['teacher_email'])) ?>',
              '<?= addslashes(htmlspecialchars($skill['teacher_name'] ?? 'Unknown')) ?>'
            )">Send request</button>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state" style="grid-column:1/-1;background:var(--glass);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:var(--radius-lg);">
        <div class="empty-state-icon">🔍</div>
        <h3>No skills found</h3>
        <p>Try a different search or category</p>
      </div>
    <?php endif; ?>
  </div>

</div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-head">
      <h3>Send Skill Swap Request</h3>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="skill_id"       id="f_skill_id">
      <input type="hidden" name="receiver_email" id="f_receiver_email">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">You want to learn</label>
          <div style="font-size:.95rem;font-weight:700;color:var(--text);" id="f_skill_title"></div>
          <div style="font-size:.82rem;color:var(--text-2);margin-top:.2rem;">
            From: <span id="f_teacher_name"></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Skill you're offering in return *</label>
          <?php if (empty($my_skills)): ?>
            <p style="font-size:.85rem;color:#fca5a5;margin-bottom:.5rem;">
              ⚠️ No skills listed yet. <a href="add_proof.php" style="color:#fca5a5;">Add one first</a>.
            </p>
            <select class="form-select" disabled>
              <option>-- No skills available --</option>
            </select>
          <?php else: ?>
            <select class="form-select" id="f_offered" name="offered_skill_id" required>
              <option value="">-- Select one of your skills --</option>
              <?php foreach ($my_skills as $ms): ?>
                <option value="<?= $ms['id'] ?>"><?= htmlspecialchars($ms['title']) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Your message *</label>
          <textarea class="form-textarea" id="f_message" name="message" required
            placeholder="Introduce yourself. Mention your skill level and what you hope to learn..."></textarea>
        </div>
        <div class="info-box">
          💡 <strong>Skill swap:</strong> They teach you their skill, you teach them yours. Two sessions are scheduled automatically on accept.
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" name="send_request" class="btn btn-primary"
          <?= empty($my_skills) ? 'disabled' : '' ?>>Send Request</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(skillId, skillTitle, receiverEmail, teacherName) {
    document.getElementById('f_skill_id').value            = skillId;
    document.getElementById('f_receiver_email').value      = receiverEmail;
    document.getElementById('f_skill_title').textContent   = skillTitle;
    document.getElementById('f_teacher_name').textContent  = teacherName;
    document.getElementById('f_message').value             = '';
    if (document.getElementById('f_offered')) document.getElementById('f_offered').value = '';
    document.getElementById('modalOverlay').classList.add('open');
  }
  function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
  }
  document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });
</script>

<?php include 'footer.php'; ?>

</body>
</html>
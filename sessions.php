<?php
require_once 'config.php';
check_login();

$me_email = $_SESSION['email'];
$me       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { die("Session error. Please log in again."); }
$me_id = (int)$me['id'];

$flash = ['type' => '', 'text' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_session'])) {
    $sess_id = (int)$_POST['session_id'];
    $status  = in_array($_POST['status'], ['completed', 'cancelled']) ? $_POST['status'] : '';
    if ($status === '') {
        $flash = ['type' => 'error', 'text' => 'Invalid status.'];
    } else {
        mysqli_query($conn, "UPDATE sessions SET status = '$status' WHERE id = $sess_id AND (teacher_id = $me_id OR learner_id = $me_id)");
        if (mysqli_affected_rows($conn) > 0) {
            $flash = ['type' => 'success', 'text' => 'Session ' . ucfirst($status) . '!'];
            if ($status === 'completed') {
                $sess = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sessions WHERE id = $sess_id"));
                if ($sess) {
                    $code = 'CERT-' . strtoupper(substr(md5(uniqid()), 0, 10));
                    mysqli_query($conn, "INSERT INTO certificates (session_id, learner_id, teacher_id, skill_id, certificate_code, issued_date) VALUES ($sess_id, {$sess['learner_id']}, {$sess['teacher_id']}, {$sess['skill_id']}, '$code', CURDATE())");
                    $flash['text'] .= ' Certificate generated!';
                }
            }
        } else {
            $flash = ['type' => 'error', 'text' => 'Could not update session.'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_session'])) {
    $sess_id  = (int)$_POST['session_id'];
    $date     = mysqli_real_escape_string($conn, trim($_POST['session_date']));
    $duration = (int)$_POST['duration'];
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));
    $notes    = mysqli_real_escape_string($conn, trim($_POST['notes']));
    mysqli_query($conn, "UPDATE sessions SET session_date = '$date', duration_minutes = $duration, location = '$location', notes = '$notes' WHERE id = $sess_id AND (teacher_id = $me_id OR learner_id = $me_id)");
    $flash = mysqli_affected_rows($conn) > 0
        ? ['type' => 'success', 'text' => 'Session updated!']
        : ['type' => 'error',   'text' => 'Could not update session.'];
}

$upcoming = mysqli_query($conn,
    "SELECT s.*, sk.title AS skill_title, sk.category, t.Name AS teacher_name, l.Name AS learner_name, t.id AS t_id, l.id AS l_id
     FROM sessions s JOIN skills sk ON s.skill_id=sk.id JOIN users t ON s.teacher_id=t.id JOIN users l ON s.learner_id=l.id
     WHERE (s.teacher_id=$me_id OR s.learner_id=$me_id) AND s.status='scheduled' ORDER BY s.session_date ASC"
);

$past = mysqli_query($conn,
    "SELECT s.*, sk.title AS skill_title, sk.category, t.Name AS teacher_name, l.Name AS learner_name, t.id AS t_id, l.id AS l_id
     FROM sessions s JOIN skills sk ON s.skill_id=sk.id JOIN users t ON s.teacher_id=t.id JOIN users l ON s.learner_id=l.id
     WHERE (s.teacher_id=$me_id OR s.learner_id=$me_id) AND s.status IN ('completed','cancelled') ORDER BY s.session_date DESC LIMIT 30"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Sessions | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .sess-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius);
      padding: 1.3rem 1.5rem; margin-bottom: 1.2rem;
      transition: border-color .2s, box-shadow .2s;
    }
    .sess-card:hover { border-color: rgba(14,165,233,.3); box-shadow: 0 8px 32px rgba(14,165,233,.08); }
    .sess-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
    .sess-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: .3rem; }
    .role-pill {
      display: inline-flex; align-items: center; gap: .3rem;
      padding: .25rem .75rem; border-radius: 20px;
      font-size: .8rem; font-weight: 600; margin-top: .4rem;
    }
    .role-teacher { background: rgba(14,165,233,.12); color: #7dd3fc; border: 1px solid rgba(14,165,233,.2); }
    .role-learner { background: rgba(139,92,246,.12); color: #c4b5fd; border: 1px solid rgba(139,92,246,.2); }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .8rem; margin: 1rem 0; }
    .info-item {
      background: var(--glass-2); border: 1px solid var(--border-2);
      border-radius: var(--radius-sm); padding: .75rem 1rem;
      display: flex; align-items: flex-start; gap: .5rem;
    }
    .info-icon { font-size: 1rem; margin-top: .1rem; }
    .info-label { font-size: .75rem; color: var(--text-3); margin-bottom: .15rem; }
    .info-val   { font-weight: 600; color: var(--text); font-size: .88rem; }
    .sess-notes {
      background: rgba(14,165,233,.06); border-left: 3px solid rgba(14,165,233,.4);
      padding: .8rem 1rem; border-radius: 6px; margin: .8rem 0;
      font-size: .88rem; color: var(--text-2); line-height: 1.6;
    }
    .sess-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .8rem; }
    .empty-card {
      background: var(--glass); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 2.5rem 2rem;
      text-align: center; color: var(--text-3);
    }
    .empty-card a { color: var(--teal); text-decoration: none; }
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

  <div class="page page-sm">

    <?php if ($flash['text']): ?>
      <div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['text']) ?></div>
    <?php endif; ?>

    <h1 class="page-title">My Sessions</h1>
    <p class="page-subtitle">Track your upcoming and past skill exchange sessions</p>

    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('upcoming', this)">📆 Upcoming</button>
      <button class="tab-btn" onclick="showTab('past', this)">📋 Past Sessions</button>
    </div>

    <div id="upcoming" class="tab-pane active">
      <?php if (mysqli_num_rows($upcoming) > 0): ?>
        <?php while ($s = mysqli_fetch_assoc($upcoming)):
          $i_teach = ($s['t_id'] == $me_id);
          $partner = $i_teach ? $s['learner_name'] : $s['teacher_name'];
          $partner_id = $i_teach ? $s['l_id'] : $s['t_id'];
        ?>
          <div class="sess-card">
            <div class="sess-top">
              <div>
                <div class="sess-title"><?= htmlspecialchars($s['skill_title']) ?></div>
                <span class="badge badge-teal"><?= htmlspecialchars($s['category']) ?></span>
                <span class="role-pill <?= $i_teach ? 'role-teacher' : 'role-learner' ?>">
                  <?= $i_teach ? '👨‍🏫 You are teaching' : '👨‍🎓 You are learning' ?>
                </span>
              </div>
              <span class="status status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
            </div>
            <div class="info-grid">
              <div class="info-item"><span class="info-icon">📅</span><div><div class="info-label">Date &amp; Time</div><div class="info-val"><?= date('M d, Y g:i A', strtotime($s['session_date'])) ?></div></div></div>
              <div class="info-item"><span class="info-icon">⏱️</span><div><div class="info-label">Duration</div><div class="info-val"><?= $s['duration_minutes'] ?> min</div></div></div>
              <div class="info-item"><span class="info-icon">👤</span><div><div class="info-label"><?= $i_teach ? 'Learner' : 'Teacher' ?></div><div class="info-val"><?= htmlspecialchars($partner) ?></div></div></div>
              <?php if (!empty($s['location'])): ?>
                <div class="info-item"><span class="info-icon">📍</span><div><div class="info-label">Location</div><div class="info-val"><?= htmlspecialchars($s['location']) ?></div></div></div>
              <?php endif; ?>
            </div>
            <?php if (!empty($s['notes'])): ?>
              <div class="sess-notes"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($s['notes'])) ?></div>
            <?php endif; ?>
            <div class="sess-actions">
              <?php if ($i_teach): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                  <input type="hidden" name="status" value="completed">
                  <button type="submit" name="update_session" class="btn btn-success">✓ Mark Complete</button>
                </form>
              <?php endif; ?>
              <button class="btn btn-glass" onclick="openEdit(<?= $s['id'] ?>,'<?= str_replace(' ','T',$s['session_date']) ?>',<?= $s['duration_minutes'] ?>,'<?= addslashes($s['location']??'') ?>','<?= addslashes($s['notes']??'') ?>')">✏️ Edit</button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Cancel this session?')">
                <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="status" value="cancelled">
                <button type="submit" name="update_session" class="btn btn-danger">✗ Cancel</button>
              </form>
              <a href="messaging.php?chat_with=<?= $partner_id ?>" class="btn btn-primary">💬 Message</a>
              <a href="video_call.php?session=<?= $s['id'] ?>" class="btn btn-glass" target="_blank" style="border-color:rgba(14,165,233,.4);color:#7dd3fc;">📹 Join Video Call</a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-card">No upcoming sessions. <a href="skill_market.php">Browse the marketplace</a> to get started.</div>
      <?php endif; ?>
    </div>

    <div id="past" class="tab-pane">
      <?php if (mysqli_num_rows($past) > 0): ?>
        <?php while ($s = mysqli_fetch_assoc($past)):
          $i_teach = ($s['t_id'] == $me_id);
          $partner = $i_teach ? $s['learner_name'] : $s['teacher_name'];
        ?>
          <div class="sess-card">
            <div class="sess-top">
              <div>
                <div class="sess-title"><?= htmlspecialchars($s['skill_title']) ?></div>
                <span class="badge badge-teal"><?= htmlspecialchars($s['category']) ?></span>
                <span class="role-pill <?= $i_teach ? 'role-teacher' : 'role-learner' ?>"><?= $i_teach ? '👨‍🏫 Taught' : '👨‍🎓 Learned' ?></span>
              </div>
              <span class="status status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
            </div>
            <div class="info-grid">
              <div class="info-item"><span class="info-icon">📅</span><div><div class="info-label">Date</div><div class="info-val"><?= date('M d, Y', strtotime($s['session_date'])) ?></div></div></div>
              <div class="info-item"><span class="info-icon">👤</span><div><div class="info-label"><?= $i_teach ? 'Learner' : 'Teacher' ?></div><div class="info-val"><?= htmlspecialchars($partner) ?></div></div></div>
            </div>
            <?php if ($s['status'] === 'completed'): ?>
              <div class="sess-actions">
                <a href="reviews.php?session=<?= $s['id'] ?>" class="btn btn-glass">⭐ Leave Review</a>
                <?php if (!$i_teach): ?><a href="certificates.php" class="btn btn-glass">🎓 View Certificate</a><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-card">No past sessions yet.</div>
      <?php endif; ?>
    </div>

  </div><!-- /page-sm -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>

<!-- Edit Modal -->
<div class="modal-overlay" id="editOverlay">
  <div class="modal">
    <div class="modal-head">
      <h3>✏️ Edit Session</h3>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="session_id" id="e_id">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Date &amp; Time</label><input type="datetime-local" name="session_date" id="e_date" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Duration (minutes)</label><input type="number" name="duration" id="e_dur" class="form-input" min="15" step="15" required></div>
        <div class="form-group"><label class="form-label">Location</label><input type="text" name="location" id="e_loc" class="form-input" placeholder="e.g. Zoom, Room 201"></div>
        <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" id="e_notes" class="form-textarea" placeholder="Any extra info..."></textarea></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeEdit()">Cancel</button>
        <button type="submit" name="edit_session" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
  function showTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
    document.getElementById(name).classList.add('active');
    btn.classList.add('active');
  }
  function openEdit(id, date, dur, loc, notes) {
    document.getElementById('e_id').value    = id;
    document.getElementById('e_date').value  = date;
    document.getElementById('e_dur').value   = dur;
    document.getElementById('e_loc').value   = loc;
    document.getElementById('e_notes').value = notes;
    document.getElementById('editOverlay').classList.add('open');
  }
  function closeEdit() { document.getElementById('editOverlay').classList.remove('open'); }
  document.getElementById('editOverlay').addEventListener('click', function(e) { if (e.target === this) closeEdit(); });
</script>
</body>
</html>

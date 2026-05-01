<?php
require_once 'config.php';
check_login();

// ─── Current user ─────────────────────────────────────────────────────────────
$me_email = $_SESSION['email'];
$me       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { die("Session error. Please log in again."); }
$me_id = (int)$me['id'];

// ─── Flash ────────────────────────────────────────────────────────────────────
$flash = ['type' => '', 'text' => ''];

// ─── Accept / Reject a received request ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $req_id = (int)$_POST['request_id'];
    $status = in_array($_POST['update_status'], ['accepted', 'rejected'])
              ? $_POST['update_status'] : '';

    if ($status === '') {
        $flash = ['type' => 'error', 'text' => 'Invalid status.'];
    } else {
        $req = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM skill_requests WHERE id = $req_id AND receiver_id = $me_id"
        ));

        if (!$req) {
            $flash = ['type' => 'error', 'text' => 'Request not found or access denied.'];
        } else {
            mysqli_query($conn, "UPDATE skill_requests SET status = '$status' WHERE id = $req_id");
            $flash = ['type' => 'success', 'text' => 'Request ' . ucfirst($status) . ' successfully!'];

            // Create notification for the sender
            $skill_title = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.title FROM skill_requests sr JOIN skills s ON sr.skill_id=s.id WHERE sr.id=$req_id"))['title'] ?? 'skill';
            if ($status === 'accepted') {
                create_notification($conn, $req['sender_id'], 'request_accepted',
                    '✅ Request Accepted!',
                    $me['Name'] . ' accepted your request to learn ' . $skill_title,
                    'sessions.php'
                );
            } else {
                create_notification($conn, $req['sender_id'], 'request_rejected',
                    '❌ Request Declined',
                    $me['Name'] . ' declined your request for ' . $skill_title,
                    'requests.php'
                );
            }

            if ($status === 'accepted') {
                $date1 = date('Y-m-d H:i:s', strtotime('+3 days'));
                $date2 = date('Y-m-d H:i:s', strtotime('+4 days'));

                // Session 1: receiver teaches sender the requested skill
                mysqli_query($conn,
                    "INSERT INTO sessions (request_id, teacher_id, learner_id, skill_id, session_date, status)
                     VALUES ($req_id, {$req['receiver_id']}, {$req['sender_id']}, {$req['skill_id']}, '$date1', 'scheduled')"
                );

                // Session 2: sender teaches receiver their offered skill (or same skill if none offered)
                $swap_skill = !empty($req['offered_skill_id']) ? (int)$req['offered_skill_id'] : (int)$req['skill_id'];
                mysqli_query($conn,
                    "INSERT INTO sessions (request_id, teacher_id, learner_id, skill_id, session_date, status)
                     VALUES ($req_id, {$req['sender_id']}, {$req['receiver_id']}, $swap_skill, '$date2', 'scheduled')"
                );

                // Notify both users about their new sessions
                create_notification($conn, $req['sender_id'], 'session_scheduled',
                    '📅 Session Scheduled!',
                    'Your session for ' . $skill_title . ' has been scheduled. Check your sessions page.',
                    'sessions.php'
                );
                create_notification($conn, $req['receiver_id'], 'session_scheduled',
                    '📅 Session Scheduled!',
                    'Your session for ' . $skill_title . ' has been scheduled. Check your sessions page.',
                    'sessions.php'
                );

                $flash['text'] .= ' Two sessions have been scheduled for both of you.';
            }
        }
    }
}

// ─── Fetch received requests ──────────────────────────────────────────────────
$received_result = mysqli_query($conn,
    "SELECT sr.*,
            s.title          AS skill_title,
            s.category,
            u.Name           AS sender_name,
            u.Email          AS sender_email,
            os.title         AS offered_title
     FROM skill_requests sr
     JOIN skills s  ON sr.skill_id         = s.id
     JOIN users  u  ON sr.sender_id        = u.id
     LEFT JOIN skills os ON sr.offered_skill_id = os.id
     WHERE sr.receiver_id = $me_id
     ORDER BY sr.created_at DESC"
);

// ─── Fetch sent requests ──────────────────────────────────────────────────────
$sent_result = mysqli_query($conn,
    "SELECT sr.*,
            s.title          AS skill_title,
            s.category,
            u.Name           AS receiver_name,
            os.title         AS offered_title
     FROM skill_requests sr
     JOIN skills s  ON sr.skill_id          = s.id
     JOIN users  u  ON sr.receiver_id       = u.id
     LEFT JOIN skills os ON sr.offered_skill_id = os.id
     WHERE sr.sender_id = $me_id
     ORDER BY sr.created_at DESC"
);

$pending_count = 0;
$tmp = mysqli_query($conn, "SELECT COUNT(*) AS c FROM skill_requests WHERE receiver_id = $me_id AND status = 'pending'");
if ($tmp) $pending_count = (int)mysqli_fetch_assoc($tmp)['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Requests | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .req-card {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-left: 3px solid rgba(14,165,233,.4);
      border-radius: var(--radius);
      padding: 1.3rem 1.5rem;
      margin-bottom: 1rem;
      transition: border-color .2s, box-shadow .2s;
    }
    .req-card:hover { border-color: rgba(14,165,233,.4); box-shadow: 0 8px 32px rgba(14,165,233,.08); }
    .req-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: .8rem; }
    .req-title { font-size: 1.05rem; font-weight: 700; color: var(--text); margin-bottom: .25rem; }
    .req-meta  { font-size: .85rem; color: var(--text-2); }
    .swap-pill {
      display: inline-flex; align-items: center; gap: .3rem;
      padding: .3rem .8rem; border-radius: 20px;
      font-size: .82rem; font-weight: 500; margin-top: .5rem;
    }
    .swap-pill.has-offer { background: rgba(16,185,129,.12); color: #6ee7b7; border: 1px solid rgba(16,185,129,.2); }
    .swap-pill.no-offer  { background: rgba(251,146,60,.12);  color: #fdba74; border: 1px solid rgba(251,146,60,.2); }
    .req-msg {
      background: var(--glass-2); padding: .8rem 1rem;
      border-radius: var(--radius-sm); margin: .8rem 0;
      font-size: .88rem; color: var(--text-2); line-height: 1.6;
      border: 1px solid var(--border-2);
    }
    .req-actions { display: flex; gap: .5rem; margin-top: .8rem; }
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

    <h1 class="page-title">Skill Requests</h1>
    <p class="page-subtitle">Manage incoming and outgoing skill exchange requests</p>

    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('received', this)">
        📥 Received
        <?php if ($pending_count > 0): ?>
          <span class="badge badge-count"><?= $pending_count ?></span>
        <?php endif; ?>
      </button>
      <button class="tab-btn" onclick="showTab('sent', this)">📤 Sent</button>
    </div>

    <div id="received" class="tab-pane active">
      <?php if (mysqli_num_rows($received_result) > 0): ?>
        <?php while ($req = mysqli_fetch_assoc($received_result)): ?>
          <div class="req-card">
            <div class="req-top">
              <div>
                <div class="req-title">Wants to learn: <?= htmlspecialchars($req['skill_title']) ?></div>
                <div class="req-meta">
                  From <strong><?= htmlspecialchars($req['sender_name']) ?></strong>
                  (<?= htmlspecialchars($req['sender_email']) ?>) &bull;
                  <?= date('M d, Y', strtotime($req['created_at'])) ?>
                </div>
                <?php if (!empty($req['offered_title'])): ?>
                  <span class="swap-pill has-offer">🔄 Offering to teach you: <strong><?= htmlspecialchars($req['offered_title']) ?></strong></span>
                <?php else: ?>
                  <span class="swap-pill no-offer">⚠️ No skill offered in return</span>
                <?php endif; ?>
              </div>
              <span class="status status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span>
            </div>
            <?php if (!empty($req['message'])): ?>
              <div class="req-msg"><strong>Message:</strong><br><?= nl2br(htmlspecialchars($req['message'])) ?></div>
            <?php endif; ?>
            <?php if ($req['status'] === 'pending'): ?>
              <form method="POST" class="req-actions">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <button type="submit" name="update_status" value="accepted" class="btn btn-success">✓ Accept</button>
                <button type="submit" name="update_status" value="rejected" class="btn btn-danger">✗ Reject</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-card">No received requests yet.</div>
      <?php endif; ?>
    </div>

    <div id="sent" class="tab-pane">
      <?php if (mysqli_num_rows($sent_result) > 0): ?>
        <?php while ($req = mysqli_fetch_assoc($sent_result)): ?>
          <div class="req-card">
            <div class="req-top">
              <div>
                <div class="req-title">Learning: <?= htmlspecialchars($req['skill_title']) ?></div>
                <div class="req-meta">
                  To <strong><?= htmlspecialchars($req['receiver_name']) ?></strong> &bull;
                  <?= date('M d, Y', strtotime($req['created_at'])) ?>
                </div>
                <?php if (!empty($req['offered_title'])): ?>
                  <span class="swap-pill has-offer">🔄 You offered to teach: <strong><?= htmlspecialchars($req['offered_title']) ?></strong></span>
                <?php endif; ?>
              </div>
              <span class="status status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span>
            </div>
            <?php if (!empty($req['message'])): ?>
              <div class="req-msg"><strong>Your message:</strong><br><?= nl2br(htmlspecialchars($req['message'])) ?></div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-card">
          No sent requests yet. <a href="skill_market.php">Browse the marketplace</a> to send your first one.
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /page-sm -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>

<script>
  function showTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
    document.getElementById(name).classList.add('active');
    btn.classList.add('active');
  }
</script>
</body>
</html>

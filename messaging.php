<?php
require_once 'config.php';
check_login();

$me_email = $_SESSION['email'];
$me       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { header("Location: logout.php"); exit(); }
$me_id = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recv_id = (int)$_POST['receiver_id'];
    $text    = trim($_POST['message']);
    if ($recv_id > 0 && $text !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $me_id, $recv_id, $text);
        $stmt->execute();
        $stmt->close();

        // Notify receiver only if they haven't been notified recently (avoid spam)
        $recent = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM notifications
             WHERE user_id = $recv_id AND type = 'message' AND is_read = 0
             AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             AND message LIKE '%" . mysqli_real_escape_string($conn, $me['Name']) . "%'
             LIMIT 1"
        ));
        if (!$recent) {
            create_notification($conn, $recv_id, 'message',
                '💬 New Message',
                $me['Name'] . ' sent you a message',
                'messaging.php?chat_with=' . $me_id
            );
        }
    }
    header("Location: messaging.php?chat_with=" . $recv_id);
    exit();
}

$chat_with = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
if ($chat_with > 0) {
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE sender_id = $chat_with AND receiver_id = $me_id");
}

$contacts = mysqli_query($conn,
    "SELECT u.id, u.Name,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = $me_id AND is_read = 0) AS unread,
            (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = $me_id) OR (sender_id = $me_id AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_msg,
            (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = $me_id) OR (sender_id = $me_id AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_time
     FROM users u
     WHERE u.id != $me_id AND u.id IN (
         SELECT sender_id FROM messages WHERE receiver_id = $me_id UNION
         SELECT receiver_id FROM messages WHERE sender_id = $me_id UNION
         SELECT sender_id FROM skill_requests WHERE receiver_id = $me_id UNION
         SELECT receiver_id FROM skill_requests WHERE sender_id = $me_id
     ) ORDER BY last_time DESC"
);

$messages = null;
$selected_user = null;
if ($chat_with > 0) {
    $messages = mysqli_query($conn,
        "SELECT m.*, u.Name AS sender_name FROM messages m
         JOIN users u ON m.sender_id = u.id
         WHERE (m.sender_id = $me_id AND m.receiver_id = $chat_with)
            OR (m.sender_id = $chat_with AND m.receiver_id = $me_id)
         ORDER BY m.created_at ASC"
    );
    $selected_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE id = $chat_with"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { height: 100vh; overflow: hidden; display: flex; flex-direction: column; }

    .chat-layout {
      display: flex; flex: 1; overflow: hidden;
      position: relative; z-index: 1;
    }

    /* ── Mobile override — allow scroll so keyboard doesn't block input ── */
    @media (max-width: 768px) {
      body { height: auto; overflow: visible; }
      .chat-layout { height: calc(100dvh - 56px - 60px); overflow: hidden; }
    }

    /* ── Sidebar ── */
    .sidebar {
      width: 300px; flex-shrink: 0;
      background: rgba(6,11,20,.8);
      backdrop-filter: blur(20px);
      border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
    }
    .sidebar-head {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--border);
      font-size: .95rem; font-weight: 700; color: var(--text);
    }
    .sidebar-list { overflow-y: auto; flex: 1; }

    .contact {
      display: block; padding: .9rem 1.5rem;
      border-bottom: 1px solid var(--border-2);
      text-decoration: none; color: var(--text);
      transition: background .15s;
    }
    .contact:hover { background: var(--glass-2); }
    .contact.active {
      background: rgba(14,165,233,.1);
      border-left: 3px solid var(--teal);
    }
    .contact-row {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: .2rem;
    }
    .contact-name { font-weight: 600; font-size: .9rem; color: var(--text); }
    .unread-dot {
      background: var(--grad); color: #fff;
      padding: .1rem .45rem; border-radius: 12px;
      font-size: .7rem; font-weight: 700;
      box-shadow: 0 0 8px rgba(14,165,233,.4);
    }
    .contact-preview {
      font-size: .8rem; color: var(--text-3);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;
    }
    .contact-time { font-size: .72rem; color: var(--text-3); margin-top: .15rem; }
    .no-contacts { padding: 2rem 1.5rem; text-align: center; color: var(--text-3); font-size: .875rem; }

    /* ── Chat area ── */
    .chat-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

    .chat-head {
      padding: 1rem 1.5rem;
      background: rgba(6,11,20,.7);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
      display: flex; align-items: center; gap: .8rem;
    }
    .chat-head-avatar {
      width: 36px; height: 36px; border-radius: 10px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; font-weight: 700; color: #fff;
      box-shadow: 0 2px 8px rgba(14,165,233,.3);
    }
    .chat-head h2 { font-size: 1rem; font-weight: 700; color: var(--text); }

    .chat-messages {
      flex: 1; overflow-y: auto; padding: 1.2rem 1.5rem;
      display: flex; flex-direction: column; gap: .6rem;
    }

    .msg { display: flex; }
    .msg.sent     { justify-content: flex-end; }
    .msg.received { justify-content: flex-start; }

    .bubble {
      max-width: 65%; padding: .65rem 1rem;
      border-radius: 16px; font-size: .9rem; line-height: 1.5;
    }
    .msg.received .bubble {
      background: var(--glass-2);
      border: 1px solid var(--border);
      color: var(--text);
      border-bottom-left-radius: 4px;
    }
    .msg.sent .bubble {
      background: var(--grad);
      color: #fff;
      border-bottom-right-radius: 4px;
      box-shadow: 0 4px 12px rgba(14,165,233,.25);
    }
    .msg-time { font-size: .68rem; margin-top: .3rem; opacity: .6; text-align: right; }
    .msg.received .msg-time { text-align: left; }
    .no-messages { text-align: center; color: var(--text-3); padding: 2rem; font-size: .875rem; }

    /* ── Input ── */
    .chat-input {
      padding: 1rem 1.5rem;
      background: rgba(6,11,20,.8);
      backdrop-filter: blur(20px);
      border-top: 1px solid var(--border);
      flex-shrink: 0;
    }
    .input-row { display: flex; gap: .7rem; align-items: flex-end; }
    .input-row textarea {
      flex: 1; padding: .7rem 1rem;
      background: var(--glass); border: 1.5px solid var(--border);
      border-radius: 20px; color: var(--text);
      font-size: .9rem; font-family: var(--font);
      resize: none; max-height: 100px; line-height: 1.4;
      transition: border-color .2s;
    }
    .input-row textarea::placeholder { color: var(--text-3); }
    .input-row textarea:focus { outline: none; border-color: var(--teal); }

    @media (max-width: 768px) {
      .input-row textarea { font-size: 16px; } /* prevent iOS zoom */
      .chat-input { padding: .6rem .8rem; position: sticky; bottom: 0; }
      .btn-send { padding: .7rem 1rem; font-size: .85rem; }
    }    .btn-send {
      padding: .7rem 1.4rem; background: var(--grad);
      color: #fff; border: none; border-radius: 20px;
      font-weight: 700; cursor: pointer; transition: opacity .2s;
      white-space: nowrap; font-family: var(--font);
      box-shadow: 0 4px 12px rgba(14,165,233,.3);
    }
    .btn-send:hover { opacity: .9; }

    /* ── Empty chat ── */
    .empty-chat {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--text-3); gap: .8rem;
    }
    .empty-chat-icon { font-size: 3rem; opacity: .4; }
    .empty-chat h3 { color: var(--text-2); font-size: 1rem; }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<?php include 'navbar.php'; ?>

<div class="chat-layout">

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-head">💬 Conversations</div>
    <div class="sidebar-list">
      <?php if (mysqli_num_rows($contacts) > 0): ?>
        <?php while ($c = mysqli_fetch_assoc($contacts)): ?>
          <a href="messaging.php?chat_with=<?= $c['id'] ?>"
             class="contact <?= $chat_with === (int)$c['id'] ? 'active' : '' ?>">
            <div class="contact-row">
              <span class="contact-name"><?= htmlspecialchars($c['Name']) ?></span>
              <?php if ($c['unread'] > 0): ?>
                <span class="unread-dot"><?= $c['unread'] ?></span>
              <?php endif; ?>
            </div>
            <?php if (!empty($c['last_msg'])): ?>
              <div class="contact-preview"><?= htmlspecialchars(substr($c['last_msg'], 0, 50)) ?></div>
              <div class="contact-time"><?= date('M d, g:i A', strtotime($c['last_time'])) ?></div>
            <?php endif; ?>
          </a>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-contacts">No conversations yet.<br><small>Accept a skill request to start chatting.</small></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat -->
  <div class="chat-area">
    <?php if ($selected_user): ?>
      <div class="chat-head">
        <!-- Mobile back button -->
        <button class="mob-back-btn" style="display:none;" onclick="mobileBackToContacts()">← Back</button>
        <div class="chat-head-avatar"><?= strtoupper(substr($selected_user['Name'], 0, 1)) ?></div>
        <h2><?= htmlspecialchars($selected_user['Name']) ?></h2>
      </div>

      <div class="chat-messages" id="chatMessages">
        <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
          <?php while ($msg = mysqli_fetch_assoc($messages)): ?>
            <div class="msg <?= $msg['sender_id'] == $me_id ? 'sent' : 'received' ?>">
              <div class="bubble">
                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                <div class="msg-time"><?= date('g:i A', strtotime($msg['created_at'])) ?></div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-messages">No messages yet. Say hello! 👋</div>
        <?php endif; ?>
      </div>

      <div class="chat-input">
        <form method="POST" class="input-row" id="msgForm">
          <input type="hidden" name="receiver_id" value="<?= $chat_with ?>">
          <textarea name="message" id="msgInput" rows="1" placeholder="Type a message..." required></textarea>
          <button type="submit" name="send_message" class="btn-send">Send ↑</button>
        </form>
      </div>

    <?php else: ?>
      <div class="empty-chat">
        <div class="empty-chat-icon">💬</div>
        <h3>Select a conversation</h3>
        <p>Pick someone from the sidebar to start chatting</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
  const chat = document.getElementById('chatMessages');
  if (chat) chat.scrollTop = chat.scrollHeight;

  const input = document.getElementById('msgInput');
  if (input) {
    input.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (this.value.trim()) document.getElementById('msgForm').submit();
      }
    });
  }

  <?php if ($chat_with > 0): ?>
  setInterval(function () {
    fetch('get_messages.php?chat_with=<?= $chat_with ?>')
      .then(r => r.text())
      .then(html => {
        const c = document.getElementById('chatMessages');
        const atBottom = c.scrollHeight - c.scrollTop <= c.clientHeight + 50;
        c.innerHTML = html;
        if (atBottom) c.scrollTop = c.scrollHeight;
      });
  }, 4000);
  <?php endif; ?>

  // ── Mobile chat UX ──
  function isMobile() { return window.innerWidth <= 768; }

  function mobileBackToContacts() {
    document.querySelector('.sidebar').classList.remove('mob-hidden');
    document.querySelector('.chat-area').classList.remove('mob-full');
  }

  if (isMobile()) {
    <?php if ($chat_with > 0): ?>
      document.querySelector('.sidebar').classList.add('mob-hidden');
      document.querySelector('.chat-area').classList.add('mob-full');
      const backBtn = document.querySelector('.mob-back-btn');
      if (backBtn) backBtn.style.display = 'flex';
    <?php endif; ?>
  }
</script>
</body>
</html>

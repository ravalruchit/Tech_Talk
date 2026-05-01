<?php
require_once 'config.php';
check_login();

$me_email = $_SESSION['email'];
$me       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '$me_email'"));
if (!$me) { exit(); }
$me_id = (int)$me['id'];

$chat_with = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
if ($chat_with <= 0) { exit(); }

// Mark as read
mysqli_query($conn,
    "UPDATE messages SET is_read = 1
     WHERE sender_id = $chat_with AND receiver_id = $me_id"
);

// Fetch messages
$result = mysqli_query($conn,
    "SELECT m.*, u.Name AS sender_name
     FROM messages m
     JOIN users u ON m.sender_id = u.id
     WHERE (m.sender_id = $me_id AND m.receiver_id = $chat_with)
        OR (m.sender_id = $chat_with AND m.receiver_id = $me_id)
     ORDER BY m.created_at ASC"
);

if ($result && mysqli_num_rows($result) > 0) {
    while ($msg = mysqli_fetch_assoc($result)) {
        $cls = $msg['sender_id'] == $me_id ? 'sent' : 'received';
        echo '<div class="msg ' . $cls . '">';
        echo '<div class="bubble">';
        echo nl2br(htmlspecialchars($msg['message']));
        echo '<div class="msg-time">' . date('g:i A', strtotime($msg['created_at'])) . '</div>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<div class="no-messages">No messages yet. Say hello! 👋</div>';
}

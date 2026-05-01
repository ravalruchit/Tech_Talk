<?php
require_once 'config.php';
check_login();
header('Content-Type: application/json');

$me_email = $_SESSION['email'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '$me_email'"));
if (!$me) { echo json_encode(['count' => 0, 'notifications' => []]); exit(); }
$me_id = (int)$me['id'];

$result = mysqli_query($conn,
    "SELECT * FROM notifications WHERE user_id = $me_id ORDER BY created_at DESC LIMIT 20"
);

$notifications = [];
$unread = 0;
while ($n = mysqli_fetch_assoc($result)) {
    if (!$n['is_read']) $unread++;
    $notifications[] = [
        'id'         => $n['id'],
        'type'       => $n['type'],
        'title'      => $n['title'],
        'message'    => $n['message'],
        'link'       => $n['link'],
        'is_read'    => (bool)$n['is_read'],
        'time'       => $n['created_at'],
        'time_ago'   => time_ago($n['created_at'])
    ];
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M d', strtotime($datetime));
}

echo json_encode(['count' => $unread, 'notifications' => $notifications]);

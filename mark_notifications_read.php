<?php
require_once 'config.php';
check_login();
header('Content-Type: application/json');

$me_email = $_SESSION['email'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '$me_email'"));
if (!$me) { echo json_encode(['success' => false]); exit(); }
$me_id = (int)$me['id'];

$data = json_decode(file_get_contents('php://input'), true);
$notif_id = isset($data['id']) ? (int)$data['id'] : 0;

if ($notif_id > 0) {
    // Mark single notification as read
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $me_id");
} else {
    // Mark all as read
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $me_id");
}

echo json_encode(['success' => true]);

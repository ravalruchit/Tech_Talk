<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$session_id  = (int)($data['session_id']  ?? 0);
$reporter_id = (int)($data['reporter_id'] ?? 0);
$reported_id = (int)($data['reported_id'] ?? 0);
$reason      = mysqli_real_escape_string($conn, trim($data['reason']      ?? ''));
$description = mysqli_real_escape_string($conn, trim($data['description'] ?? ''));

if ($session_id <= 0 || $reporter_id <= 0 || $reported_id <= 0 || empty($reason)) {
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    exit();
}

mysqli_query($conn,
    "INSERT INTO reports (session_id, reporter_id, reported_id, reason, description)
     VALUES ($session_id, $reporter_id, $reported_id, '$reason', '$description')"
);

if (mysqli_affected_rows($conn) > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . mysqli_error($conn)]);
}

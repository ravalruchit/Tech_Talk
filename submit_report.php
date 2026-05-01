<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit();
}

$me_email = $_SESSION['email'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '" . mysqli_real_escape_string($conn, $me_email) . "'"));
if (!$me) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit(); }
$me_id = (int)$me['id'];

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

// Verify reporter is actually part of this session
$session_check = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM sessions WHERE id = $session_id AND (teacher_id = $me_id OR learner_id = $me_id)"
));
if (!$session_check) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized — you are not part of this session']);
    exit();
}

// Check if reports table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'reports'");
if (mysqli_num_rows($table_check) === 0) {
    // Create reports table if missing
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS reports (
            id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id int(11) NOT NULL,
            reporter_id int(11) NOT NULL,
            reported_id int(11) NOT NULL,
            reason varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'pending',
            created_at timestamp DEFAULT current_timestamp()
        )
    ");
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

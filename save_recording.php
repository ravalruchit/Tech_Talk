<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$me_email = $_SESSION['email'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE Email = '" . mysqli_real_escape_string($conn, $me_email) . "'"));
if (!$me) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit(); }
$me_id = (int)$me['id'];

$session_id  = (int)($_POST['session_id'] ?? 0);
$recorder_id = (int)($_POST['recorder_id'] ?? 0);

if ($session_id <= 0 || $recorder_id <= 0 || !isset($_FILES['recording'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

// Verify the recorder is actually part of this session
$session_check = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM sessions WHERE id = $session_id AND (teacher_id = $me_id OR learner_id = $me_id)"
));
if (!$session_check) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check for upload errors
if ($_FILES['recording']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (server limit).',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit).',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    ];
    $err_msg = $upload_errors[$_FILES['recording']['error']] ?? 'Unknown upload error.';
    echo json_encode(['success' => false, 'error' => $err_msg]);
    exit();
}

// File size limit — 200MB max
$max_size = 200 * 1024 * 1024;
if ($_FILES['recording']['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 200MB)']);
    exit();
}

// Validate file type — only allow webm/mp4
$allowed_types = ['video/webm', 'video/mp4', 'application/octet-stream'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $_FILES['recording']['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowed_types) && !in_array($_FILES['recording']['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit();
}

$dir = __DIR__ . '/recordings/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$filename = 'session_' . $session_id . '_user_' . $recorder_id . '_' . time() . '.webm';
$filepath = $dir . $filename;
$db_path  = 'recordings/' . $filename;
$db_path_escaped = mysqli_real_escape_string($conn, $db_path);

if (move_uploaded_file($_FILES['recording']['tmp_name'], $filepath)) {
    // Check if session_recordings table exists before inserting
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'session_recordings'");
    if (mysqli_num_rows($table_check) > 0) {
        mysqli_query($conn,
            "INSERT INTO session_recordings (session_id, recorder_id, file_path)
             VALUES ($session_id, $recorder_id, '$db_path_escaped')"
        );
    }

    // Update sessions table if recording_path column exists
    $col_check = mysqli_query($conn, "SHOW COLUMNS FROM sessions LIKE 'recording_path'");
    if (mysqli_num_rows($col_check) > 0) {
        mysqli_query($conn, "UPDATE sessions SET recording_path = '$db_path_escaped' WHERE id = $session_id");
    }

    echo json_encode(['success' => true, 'path' => $db_path]);
} else {
    $err = error_get_last();
    echo json_encode(['success' => false, 'error' => 'Move failed: ' . ($err['message'] ?? 'unknown')]);
}

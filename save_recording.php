<?php
require_once 'config.php';
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$session_id  = (int)($_POST['session_id'] ?? 0);
$recorder_id = (int)($_POST['recorder_id'] ?? 0);

if ($session_id <= 0 || $recorder_id <= 0 || !isset($_FILES['recording'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit();
}

// Check for upload errors
if ($_FILES['recording']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (server limit). Increase upload_max_filesize in php.ini.',
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

$dir = __DIR__ . '/recordings/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$filename = 'session_' . $session_id . '_user_' . $recorder_id . '_' . time() . '.webm';
$filepath = $dir . $filename;
$db_path  = 'recordings/' . $filename;

if (move_uploaded_file($_FILES['recording']['tmp_name'], $filepath)) {
    mysqli_query($conn,
        "INSERT INTO session_recordings (session_id, recorder_id, file_path)
         VALUES ($session_id, $recorder_id, '$db_path')"
    );
    mysqli_query($conn, "UPDATE sessions SET recording_path = '$db_path' WHERE id = $session_id");
    echo json_encode(['success' => true, 'path' => $db_path]);
} else {
    $err = error_get_last();
    echo json_encode(['success' => false, 'error' => 'Move failed: ' . ($err['message'] ?? 'unknown')]);
}

<?php
/**
 * cleanup_recordings.php
 * Deletes recordings older than 30 days from disk and database.
 *
 * HOW TO RUN:
 * Option 1 — Run manually in browser: http://localhost/dashboard/TECHTALK/cleanup_recordings.php
 * Option 2 — Run via command line:    php cleanup_recordings.php
 * Option 3 — Schedule with Windows Task Scheduler to run daily automatically
 */

require_once 'config.php';

// Only allow admin or CLI to run this
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Admin access required']));
    }
}

$days = 30; // Delete recordings older than this many days
$deleted_files   = 0;
$deleted_db_rows = 0;
$freed_bytes     = 0;
$errors          = [];

// Get all recordings older than 30 days
$old_recordings = mysqli_query($conn,
    "SELECT id, file_path FROM session_recordings
     WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)"
);

if (!$old_recordings) {
    die('DB error: ' . mysqli_error($conn));
}

$ids_to_delete = [];

while ($row = mysqli_fetch_assoc($old_recordings)) {
    $ids_to_delete[] = (int)$row['id'];
    $full_path = __DIR__ . '/' . $row['file_path'];

    if (file_exists($full_path)) {
        $size = filesize($full_path);
        if (unlink($full_path)) {
            $deleted_files++;
            $freed_bytes += $size;
        } else {
            $errors[] = 'Could not delete file: ' . $row['file_path'];
        }
    }
    // File already gone — still clean up DB row
}

// Delete DB rows
if (!empty($ids_to_delete)) {
    $ids_sql = implode(',', $ids_to_delete);
    mysqli_query($conn, "DELETE FROM session_recordings WHERE id IN ($ids_sql)");
    $deleted_db_rows = mysqli_affected_rows($conn);
}

// Also clear recording_path on sessions where the file is now gone
mysqli_query($conn,
    "UPDATE sessions SET recording_path = NULL
     WHERE recording_path IS NOT NULL
     AND recording_path NOT IN (SELECT file_path FROM session_recordings)"
);

$freed_mb = round($freed_bytes / 1024 / 1024, 2);

if ($is_cli) {
    echo "✅ Cleanup complete\n";
    echo "   Files deleted : $deleted_files\n";
    echo "   DB rows removed: $deleted_db_rows\n";
    echo "   Space freed   : {$freed_mb} MB\n";
    if (!empty($errors)) {
        echo "   Errors:\n";
        foreach ($errors as $e) echo "   - $e\n";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success'       => true,
        'files_deleted' => $deleted_files,
        'db_rows'       => $deleted_db_rows,
        'freed_mb'      => $freed_mb,
        'errors'        => $errors,
        'message'       => "Deleted $deleted_files files, freed {$freed_mb} MB"
    ]);
}

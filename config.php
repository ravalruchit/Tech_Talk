<?php
// ─── Database Configuration ───────────────────────────────────────────────
// On Railway: set these as environment variables in your service settings.
// Locally: falls back to XAMPP defaults.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'tachtalk');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Don't expose DB details in production
    $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
    die($is_local
        ? "DB Connection failed: " . $conn->connect_error
        : "Service temporarily unavailable. Please try again later."
    );
}

$conn->set_charset('utf8mb4');

// ─── Session ──────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Auth helpers ─────────────────────────────────────────────────────────
function check_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['email'])) {
        header("Location: login.php");
        exit();
    }
}

function check_admin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: admin_login.php");
        exit();
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

function create_notification($conn, $user_id, $type, $title, $message, $link = null) {
    $user_id = (int)$user_id;
    $type    = mysqli_real_escape_string($conn, $type);
    $title   = mysqli_real_escape_string($conn, $title);
    $message = mysqli_real_escape_string($conn, $message);
    $link    = $link ? "'" . mysqli_real_escape_string($conn, $link) . "'" : "NULL";
    mysqli_query($conn,
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES ($user_id, '$type', '$title', '$message', $link)"
    );
}

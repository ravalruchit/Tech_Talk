<?php
session_start();

// Protect admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <style>
    body { font-family: Poppins, sans-serif; background: #f5f5f5; margin: 0; }
    .navbar { background: #222; color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; }
    .navbar h1 { margin: 0; font-size: 22px; }
    .container { padding: 30px; }
    .card { background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .card a { text-decoration: none; font-weight: bold; color: #ff9800; }
  </style>
</head>
<body>
  <div class="navbar">
    <h1>⚙️ Admin Dashboard</h1>
    <a href="logout.php" style="color:#ff9800;">Logout</a>
  </div>
  <div class="container">
    <div class="card"><a href="admin_users.php">👤 User Management</a></div>
    <div class="card"><a href="admin_skills.php">🎓 Skill Management</a></div>
    <div class="card"><a href="admin_content.php">📑 Content Moderation</a></div>
  </div>
</body>
</html>
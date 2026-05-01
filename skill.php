<?php
session_start();
$conn = new mysqli("localhost","root","","tachtalk");
$email = $_SESSION['email']; // logged-in user's email

// Add skill
if(isset($_POST['add_skill'])){
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);

    $conn->query("INSERT INTO skills (email, title, category, description) VALUES ('$email', '$title', '$category', '$description')");
}

// Delete skill
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM skills WHERE id=$id AND user_email='$user_email'");
}

// Fetch user's skills only
$result = $conn->query("SELECT * FROM skills WHERE email='$email' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Skills</title>
    <style>
body {
    font-family: "Inter", sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 20px;
}

/* Headings */
h2 {
    color: #ff9f43;
    text-align: center;
    margin-bottom: 20px;
}

/* Form Container */
.form-container {
    max-width: 500px;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    margin: 0 auto 40px auto;
}

.form-container h3 {
    color: #ff9f43;
    text-align: center;
    margin-bottom: 20px;
}

/* Form Inputs */
input, textarea, select {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 1rem;
}

textarea {
    resize: vertical;
}

/* Buttons */
.btn {
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #ff9f43, #d16ba5);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: opacity 0.3s;
}

.btn:hover {
    opacity: 0.9;
}

/* Skill Cards Container */
.skill-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
}

/* Skill Card */
.skill-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.skill-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

.skill-card h3 {
    margin: 0 0 10px;
    color: #ff9f43;
}

.skill-card p {
    margin: 5px 0;
    color: #555;
    line-height: 1.4;
}

.skill-card .category {
    font-size: 0.85rem;
    font-weight: bold;
    color: #888;
    margin-bottom: 10px;
}

/* Delete Button */
.delete-btn {
    display: inline-block;
    padding: 6px 12px;
    background: #e74c3c;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.3s;
}

.delete-btn:hover {
    opacity: 0.85;
}

/* Alerts */
.alert {
    padding: 12px;
    margin: 20px auto;
    max-width: 500px;
    border-radius: 6px;
    text-align: center;
    font-weight: bold;
}

.success {
    background: #c6f6d5;
    color: #22543d;
    border: 1px solid #48bb78;
}

.error {
    background: #fed7d7;
    color: #742a2a;
    border: 1px solid #f56565;
}
</style>
</head>
<body>

<h2>Add New Skill</h2>
<div class="form-container">
    <form method="POST">
        <input type="text" name="title" placeholder="Skill Title" required>
        <input type="text" name="category" placeholder="Category" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <button type="submit" name="add_skill" class="btn">Add Skill</button>
    </form>
</div>

<h2>My Skills</h2>
<?php while($row = $result->fetch_assoc()): ?>
    <div class="skill-card">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Delete this skill?')">Delete</a>
    </div>
<?php endwhile; ?>

</body>
</html>
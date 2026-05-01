<?php
require_once 'config.php';
check_login();

$email = $_SESSION['email'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_skill'])) {
    $title       = mysqli_real_escape_string($conn, trim($_POST['title']));
    $category    = mysqli_real_escape_string($conn, trim($_POST['category']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $proof_link  = mysqli_real_escape_string($conn, trim($_POST['proof_link']));
    if (!empty($title) && !empty($category) && !empty($description)) {
        $query = "INSERT INTO skills (email, title, category, description, proof_link, status) VALUES ('$email', '$title', '$category', '$description', '$proof_link', 'pending')";
        if (mysqli_query($conn, $query)) { $message = "Skill added successfully!"; }
        else { $error = "Error adding skill: " . mysqli_error($conn); }
    } else { $error = "Please fill in all required fields."; }
}

if (isset($_GET['delete'])) {
    $skill_id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM skills WHERE id = $skill_id AND email = '$email'";
    if (mysqli_query($conn, $delete_query)) { $message = "Skill deleted successfully!"; }
    else { $error = "Error deleting skill."; }
}

$skills_result = mysqli_query($conn, "SELECT * FROM skills WHERE email = '$email' ORDER BY created_at DESC");
$skill_count   = mysqli_num_rows($skills_result);
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Name FROM users WHERE Email = '$email'"));
$me_name = $me ? $me['Name'] : $email;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Skills | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Add skill bar (compact, collapsible) ── */
    .add-bar {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    .add-bar-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1rem 1.5rem; cursor: pointer;
      transition: background .15s;
    }
    .add-bar-header:hover { background: var(--glass-2); }
    .add-bar-title {
      display: flex; align-items: center; gap: .6rem;
      font-size: .95rem; font-weight: 700; color: var(--text);
    }
    .add-bar-toggle {
      width: 28px; height: 28px; border-radius: 8px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #fff; font-weight: 700;
      transition: transform .2s;
      box-shadow: 0 2px 8px rgba(14,165,233,.3);
      flex-shrink: 0;
    }
    .add-bar.open .add-bar-toggle { transform: rotate(45deg); }
    .add-bar-form {
      display: none;
      padding: 0 1.5rem 1.5rem;
      border-top: 1px solid var(--border-2);
    }
    .add-bar.open .add-bar-form { display: block; padding-top: 1.2rem; }

    /* Inline form grid */
    .form-inline-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .form-inline-grid .span-2 { grid-column: 1 / -1; }

    /* ── Skills list ── */
    .skills-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 1rem;
    }
    .skills-header-title {
      font-size: .72rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      color: var(--text-3);
    }
    .skills-count {
      background: var(--glass-2); border: 1px solid var(--border);
      color: var(--text-2); font-size: .75rem; font-weight: 600;
      padding: .2rem .65rem; border-radius: 20px;
    }

    /* ── Skill row card ── */
    .skill-row {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.1rem 1.4rem;
      margin-bottom: .8rem;
      display: flex; align-items: flex-start; gap: 1.2rem;
      transition: border-color .2s, box-shadow .2s, transform .15s;
    }
    .skill-row:hover {
      border-color: rgba(14,165,233,.3);
      box-shadow: 0 4px 20px rgba(14,165,233,.08);
      transform: translateX(3px);
    }

    /* Category icon box */
    .skill-cat-icon {
      width: 44px; height: 44px; border-radius: 12px;
      background: rgba(14,165,233,.1);
      border: 1px solid rgba(14,165,233,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; flex-shrink: 0;
    }

    .skill-row-body { flex: 1; min-width: 0; }
    .skill-row-top {
      display: flex; align-items: center; gap: .7rem;
      margin-bottom: .35rem; flex-wrap: wrap;
    }
    .skill-row-title {
      font-size: .95rem; font-weight: 700; color: var(--text);
    }
    .skill-row-desc {
      font-size: .82rem; color: var(--text-2);
      line-height: 1.55;
      display: -webkit-box; -webkit-line-clamp: 2;
      -webkit-box-orient: vertical; overflow: hidden;
    }
    .skill-row-proof {
      display: inline-flex; align-items: center; gap: .3rem;
      font-size: .78rem; color: var(--teal);
      text-decoration: none; margin-top: .4rem;
      transition: color .15s;
    }
    .skill-row-proof:hover { color: #38bdf8; text-decoration: underline; }

    .skill-row-actions {
      display: flex; align-items: center; gap: .5rem;
      flex-shrink: 0;
    }

    /* Category icons map */
    .cat-programming { background: rgba(59,130,246,.12); border-color: rgba(59,130,246,.25); }
    .cat-design      { background: rgba(139,92,246,.12); border-color: rgba(139,92,246,.25); }
    .cat-marketing   { background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.25); }
    .cat-business    { background: rgba(251,191,36,.12); border-color: rgba(251,191,36,.25); }
    .cat-photography { background: rgba(248,113,113,.12); border-color: rgba(248,113,113,.25); }
    .cat-other       { background: rgba(14,165,233,.12); border-color: rgba(14,165,233,.25); }

    /* Empty state */
    .empty-skills {
      background: var(--glass); border: 1px solid var(--border);
      border-radius: var(--radius-lg); padding: 4rem 2rem;
      text-align: center; color: var(--text-3);
    }
    .empty-skills-icon { font-size: 3rem; margin-bottom: .8rem; opacity: .35; }
    .empty-skills h3 { color: var(--text-2); font-size: 1rem; margin-bottom: .4rem; }
    .empty-skills p { font-size: .875rem; }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="page-wrap">
  <?php include 'navbar.php'; ?>

  <div class="page page-sm">

    <?php if ($message): ?>
      <div class="flash success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="flash error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Page heading -->
    <div class="flex-between mb-2">
      <div>
        <h1 class="page-title">My Skills</h1>
        <p class="page-subtitle">Skills you can teach others</p>
      </div>
    </div>

    <!-- ── Add skill bar (collapsible) ── -->
    <div class="add-bar <?= $message === 'Skill added successfully!' ? '' : '' ?>" id="addBar">
      <div class="add-bar-header" onclick="toggleAddBar()">
        <div class="add-bar-title">
          <span>➕</span> Add a new skill
        </div>
        <div class="add-bar-toggle" id="addToggle">+</div>
      </div>
      <div class="add-bar-form">
        <form method="POST">
          <div class="form-inline-grid">
            <div class="form-group">
              <label class="form-label">Skill Title *</label>
              <input class="form-input" type="text" name="title" required placeholder="e.g. Python Programming">
            </div>
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select class="form-select" name="category" required>
                <option value="">Select category</option>
                <option value="Programming">💻 Programming</option>
                <option value="Design">🎨 Design</option>
                <option value="Marketing">📱 Marketing</option>
                <option value="Business">💼 Business</option>
                <option value="Photography">📸 Photography</option>
                <option value="Video Editing">🎬 Video Editing</option>
                <option value="Music">🎵 Music</option>
                <option value="Writing">✍️ Writing</option>
                <option value="Languages">🌍 Languages</option>
                <option value="Other">❓ Other</option>
              </select>
            </div>
            <div class="form-group span-2">
              <label class="form-label">Description *</label>
              <textarea class="form-textarea" name="description" required placeholder="Describe what you can teach and your experience level..." style="min-height:80px;"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Proof Link <span style="color:var(--text-3);font-weight:400;">(optional)</span></label>
              <input class="form-input" type="url" name="proof_link" placeholder="GitHub, Portfolio, Certificate URL...">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
              <button type="submit" name="add_skill" class="btn btn-primary btn-full">Add Skill</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Skills list ── -->
    <div class="skills-header">
      <div class="skills-header-title">Your skills</div>
      <div class="skills-count"><?= $skill_count ?> skill<?= $skill_count !== 1 ? 's' : '' ?></div>
    </div>

    <?php
    // Category icons
    $cat_icons = [
      'Programming'  => ['💻', 'cat-programming'],
      'Design'       => ['🎨', 'cat-design'],
      'Marketing'    => ['📱', 'cat-marketing'],
      'Business'     => ['💼', 'cat-business'],
      'Photography'  => ['📸', 'cat-photography'],
      'Video Editing'=> ['🎬', 'cat-other'],
      'Music'        => ['🎵', 'cat-other'],
      'Writing'      => ['✍️', 'cat-other'],
      'Languages'    => ['🌍', 'cat-other'],
      'Other'        => ['❓', 'cat-other'],
    ];
    ?>

    <?php if ($skill_count > 0): ?>
      <?php while ($skill = mysqli_fetch_assoc($skills_result)):
        $cat = $skill['category'];
        $icon = $cat_icons[$cat] ?? ['📚', 'cat-other'];
      ?>
        <div class="skill-row">
          <div class="skill-cat-icon <?= $icon[1] ?>"><?= $icon[0] ?></div>
          <div class="skill-row-body">
            <div class="skill-row-top">
              <span class="skill-row-title"><?= htmlspecialchars($skill['title']) ?></span>
              <span class="badge badge-teal"><?= htmlspecialchars($skill['category']) ?></span>
            </div>
            <div class="skill-row-desc"><?= htmlspecialchars($skill['description']) ?></div>
            <?php if (!empty($skill['proof_link'])): ?>
              <a href="<?= htmlspecialchars($skill['proof_link']) ?>" target="_blank" class="skill-row-proof">
                🔗 View Proof
              </a>
            <?php endif; ?>
          </div>
          <div class="skill-row-actions">
            <button onclick="confirmDelete(<?= $skill['id'] ?>)" class="btn btn-danger" style="padding:.4rem .9rem;font-size:.78rem;">
              🗑
            </button>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-skills">
        <div class="empty-skills-icon">📚</div>
        <h3>No skills added yet</h3>
        <p>Click the <strong>+ Add a new skill</strong> bar above to get started.</p>
      </div>
    <?php endif; ?>

  </div>

  <?php include 'footer.php'; ?>
</div>

<script>
  function toggleAddBar() {
    const bar = document.getElementById('addBar');
    bar.classList.toggle('open');
  }

  // Auto-open if there was a message (skill just added or error)
  <?php if ($message || $error): ?>
    document.getElementById('addBar').classList.add('open');
  <?php endif; ?>

  function confirmDelete(skillId) {
    if (confirm('Delete this skill?')) {
      window.location.href = 'add_proof.php?delete=' + skillId;
    }
  }

  function toggleUserMenu() { document.getElementById('userMenu').classList.toggle('open'); }
  document.addEventListener('click', function(e) {
    const m = document.getElementById('userMenu');
    if (m && !m.contains(e.target)) m.classList.remove('open');
  });
</script>
</body>
</html>

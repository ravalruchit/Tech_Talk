<?php
require_once 'config.php';
check_login();

$user_email = $_SESSION['email'];
$user_data  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$user_email'"));
$user_id    = $user_data['id'];
$full_name  = $user_data['Name'];

$certificates_result = mysqli_query($conn,
    "SELECT c.*, sk.title as skill_title, sk.category, t.Name as teacher_name
     FROM certificates c
     JOIN skills sk ON c.skill_id = sk.id
     JOIN users t ON c.teacher_id = t.id
     WHERE c.learner_id = $user_id
     ORDER BY c.issued_date DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificates | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .cert-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.4rem;
    }
    .cert-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      overflow: hidden;
      transition: transform .2s, border-color .2s, box-shadow .2s;
    }
    .cert-card:hover {
      transform: translateY(-4px);
      border-color: rgba(14,165,233,.3);
      box-shadow: 0 8px 32px rgba(14,165,233,.12);
    }
    .cert-top {
      background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
      padding: 2rem 1.5rem 1.5rem; text-align: center;
    }
    .cert-top-icon { font-size: 2.8rem; margin-bottom: .75rem; display: block; }
    .cert-top-title { font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: .6rem; }
    .cert-category-badge {
      display: inline-block; background: rgba(255,255,255,.2); color: #fff;
      font-size: .75rem; font-weight: 600; padding: .25rem .75rem;
      border-radius: 20px; border: 1px solid rgba(255,255,255,.3);
    }
    .cert-details { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-2); }
    .cert-detail-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: .55rem 0; border-bottom: 1px solid var(--border-2);
    }
    .cert-detail-row:last-child { border-bottom: none; }
    .cert-detail-label { font-size: .78rem; color: var(--text-3); font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
    .cert-detail-value { font-size: .875rem; color: var(--text); font-weight: 600; }
    .cert-code-box {
      margin-top: .9rem; background: rgba(14,165,233,.07);
      border: 1px dashed rgba(14,165,233,.3); border-radius: var(--radius-sm);
      padding: .65rem 1rem; text-align: center;
      font-family: 'Courier New', monospace; font-size: .82rem;
      font-weight: 600; color: #7dd3fc; letter-spacing: .04em;
    }
    .cert-actions { padding: 1rem 1.5rem; display: flex; gap: .6rem; }
    .cert-actions .btn { flex: 1; }

    /* Modal */
    .cert-modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.75); z-index: 999;
      align-items: center; justify-content: center;
      padding: 1rem; backdrop-filter: blur(6px);
    }
    .cert-modal-overlay.open { display: flex; }
    .cert-modal {
      background: rgba(10,18,35,.95); backdrop-filter: blur(24px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      width: 100%; max-width: 780px;
      box-shadow: 0 24px 60px rgba(0,0,0,.6); overflow: hidden;
    }
    .cert-modal-head {
      padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border-2);
      display: flex; justify-content: space-between; align-items: center;
      background: rgba(14,165,233,.06);
    }
    .cert-modal-head h3 { font-size: 1rem; font-weight: 700; color: var(--text); }
    .cert-modal-close {
      background: none; border: none; cursor: pointer;
      color: var(--text-3); font-size: 1.4rem; line-height: 1;
      padding: .2rem; border-radius: 6px; transition: color .15s, background .15s;
    }
    .cert-modal-close:hover { background: var(--glass-2); color: var(--text); }
    .certificate-full {
      margin: 1.5rem; border: 3px solid rgba(14,165,233,.4);
      border-radius: var(--radius); padding: 2.5rem 2rem;
      background: linear-gradient(135deg, rgba(14,165,233,.05) 0%, rgba(16,185,129,.05) 100%);
      position: relative; text-align: center;
    }
    .cert-border-inner { border: 1px solid rgba(14,165,233,.2); border-radius: 12px; padding: 2rem; }
    .cert-logo { font-size: 3.5rem; margin-bottom: .75rem; display: block; }
    .cert-header {
      font-size: 1.8rem; font-weight: 900;
      background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      letter-spacing: -.02em; margin-bottom: .5rem;
    }
    .cert-subtitle { font-size: .95rem; color: var(--text-2); margin-bottom: 1.8rem; }
    .cert-recipient { font-size: .9rem; color: var(--text-2); margin-bottom: .3rem; }
    .cert-name {
      font-size: 2rem; font-weight: 800; color: var(--text); margin-bottom: 1.5rem;
      display: inline-block; border-bottom: 2px solid rgba(14,165,233,.4); padding-bottom: .4rem;
    }
    .cert-skill { font-size: 1.4rem; font-weight: 700; color: var(--text); margin-bottom: 1.5rem; }
    .cert-footer {
      display: flex; justify-content: space-around;
      margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);
    }
    .cert-signature { text-align: center; }
    .signature-line { border-top: 1px solid var(--border); width: 160px; margin: 0 auto .4rem; padding-top: .4rem; }
    .signature-label { font-size: .8rem; color: var(--text-2); }
    .cert-code-display {
      position: absolute; bottom: .75rem; right: 1rem;
      font-size: .72rem; color: var(--text-3); font-family: 'Courier New', monospace;
    }
    .cert-modal-foot {
      padding: 1rem 1.5rem; border-top: 1px solid var(--border-2);
      display: flex; justify-content: flex-end; gap: .6rem;
    }

    @media print {
      body * { visibility: hidden; }
      .certificate-full, .certificate-full * { visibility: visible; }
      .certificate-full { position: absolute; left: 0; top: 0; width: 100%; border: 3px solid #0ea5e9; background: #fff !important; }
      .cert-header { color: #0ea5e9 !important; -webkit-text-fill-color: #0ea5e9 !important; }
      .cert-name, .cert-skill, .cert-recipient, .cert-subtitle { color: #222 !important; }
      .cert-modal-head, .cert-modal-foot { display: none; }
    }
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

  <div class="page">
    <h1 class="page-title">🎓 My Certificates</h1>
    <p class="page-subtitle">Your verified skill achievements — earned through real exchanges</p>

    <?php if (mysqli_num_rows($certificates_result) > 0): ?>
      <div class="cert-grid">
        <?php while ($cert = mysqli_fetch_assoc($certificates_result)): ?>
          <div class="cert-card">
            <div class="cert-top">
              <span class="cert-top-icon">🎓</span>
              <div class="cert-top-title"><?= htmlspecialchars($cert['skill_title']) ?></div>
              <span class="cert-category-badge"><?= htmlspecialchars($cert['category']) ?></span>
            </div>
            <div class="cert-details">
              <div class="cert-detail-row">
                <span class="cert-detail-label">Issued</span>
                <span class="cert-detail-value"><?= date('M d, Y', strtotime($cert['issued_date'])) ?></span>
              </div>
              <div class="cert-detail-row">
                <span class="cert-detail-label">Teacher</span>
                <span class="cert-detail-value"><?= htmlspecialchars($cert['teacher_name']) ?></span>
              </div>
              <div class="cert-code-box">ID: <?= htmlspecialchars($cert['certificate_code']) ?></div>
            </div>
            <div class="cert-actions">
              <button onclick="viewCertificate('<?= addslashes(htmlspecialchars($cert['skill_title'])) ?>', '<?= addslashes(htmlspecialchars($cert['category'])) ?>', '<?= addslashes(htmlspecialchars($cert['teacher_name'])) ?>', '<?= $cert['certificate_code'] ?>', '<?= date('F d, Y', strtotime($cert['issued_date'])) ?>')" class="btn btn-primary">👁️ View</button>
              <button onclick="printCertificate()" class="btn btn-glass">🖨️ Print</button>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon">🎓</div>
        <h3>No Certificates Yet</h3>
        <p>Complete skill exchange sessions to earn certificates!</p>
        <a href="sessions.php" class="btn btn-primary" style="margin-top:1.2rem;">View Your Sessions →</a>
      </div>
    <?php endif; ?>
  </div>

  <?php include 'footer.php'; ?>
</div>

<!-- Certificate View Modal -->
<div class="cert-modal-overlay" id="certModalOverlay">
  <div class="cert-modal">
    <div class="cert-modal-head">
      <h3>Certificate of Completion</h3>
      <button class="cert-modal-close" onclick="closeCertificateModal()">✕</button>
    </div>
    <div class="certificate-full" id="certificateContent">
      <div class="cert-border-inner">
        <span class="cert-logo">🎓</span>
        <div class="cert-header">CERTIFICATE OF COMPLETION</div>
        <div class="cert-subtitle">TechTalk Skill Exchange Platform</div>
        <div class="cert-recipient">This is to certify that</div>
        <div class="cert-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="cert-recipient">has successfully completed the skill exchange program in</div>
        <div class="cert-skill" id="cert_skill_name"></div>
        <div class="cert-recipient">Under the guidance of <strong id="cert_teacher_name" style="color:var(--text);"></strong></div>
        <div class="cert-footer">
          <div class="cert-signature">
            <div class="signature-line"></div>
            <div class="signature-label">Date: <span id="cert_date"></span></div>
          </div>
          <div class="cert-signature">
            <div class="signature-line"></div>
            <div class="signature-label">TechTalk Platform</div>
          </div>
        </div>
        <div class="cert-code-display" id="cert_code_display"></div>
      </div>
    </div>
    <div class="cert-modal-foot">
      <button class="btn btn-glass" onclick="closeCertificateModal()">Close</button>
      <button class="btn btn-primary" onclick="printCertificate()">🖨️ Print Certificate</button>
    </div>
  </div>
</div>

<script>
  function viewCertificate(skillTitle, category, teacherName, certCode, issuedDate) {
    document.getElementById('cert_skill_name').textContent = skillTitle + ' (' + category + ')';
    document.getElementById('cert_teacher_name').textContent = teacherName;
    document.getElementById('cert_date').textContent = issuedDate;
    document.getElementById('cert_code_display').textContent = 'Certificate ID: ' + certCode;
    document.getElementById('certModalOverlay').classList.add('open');
  }
  function closeCertificateModal() { document.getElementById('certModalOverlay').classList.remove('open'); }
  function printCertificate() { window.print(); }
  document.getElementById('certModalOverlay').addEventListener('click', function(e) { if (e.target === this) closeCertificateModal(); });
</script>
</body>
</html>

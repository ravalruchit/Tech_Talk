<?php require_once 'config.php'; check_login(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTalk | Skill Exchange</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .hero {
      padding: 6rem 2rem 5rem;
      text-align: center;
      position: relative;
    }
    .hero h1 {
      font-size: 3.5rem; font-weight: 900;
      color: var(--text); letter-spacing: -.05em;
      line-height: 1.1; margin-bottom: 1.2rem;
    }
    .hero h1 span {
      background: var(--grad);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hero p {
      font-size: 1.1rem; color: var(--text-2);
      max-width: 520px; margin: 0 auto 2.5rem; line-height: 1.7;
    }
    .hero-btns { display: flex; gap: .8rem; justify-content: center; flex-wrap: wrap; }

    .section { padding: 4rem 0; }
    .section-title {
      font-size: 1.8rem; font-weight: 900;
      color: var(--text); letter-spacing: -.04em;
      margin-bottom: .5rem; text-align: center;
    }
    .section-sub { color: var(--text-2); text-align: center; margin-bottom: 2.5rem; }

    .step-card {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.8rem; text-align: center;
      transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .step-card:hover {
      border-color: rgba(14,165,233,.35);
      box-shadow: 0 8px 32px rgba(14,165,233,.12);
      transform: translateY(-3px);
    }
    .step-icon {
      width: 56px; height: 56px; border-radius: 16px;
      background: rgba(14,165,233,.1);
      border: 1px solid rgba(14,165,233,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; margin: 0 auto 1rem;
    }
    .step-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: .5rem; }
    .step-desc  { font-size: .875rem; color: var(--text-2); line-height: 1.6; margin-bottom: 1.2rem; }

    .stats-section {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 3rem 0;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1.5rem; text-align: center;
    }
    .stat-num {
      font-size: 2.4rem; font-weight: 900;
      background: var(--grad);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      letter-spacing: -.04em;
    }
    .stat-label { font-size: .82rem; color: var(--text-3); margin-top: .3rem; font-weight: 500; }

    .cat-chip {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .65rem 1.2rem;
      background: var(--glass);
      backdrop-filter: blur(8px);
      border: 1px solid var(--border);
      border-radius: 20px; font-size: .875rem; font-weight: 600;
      color: var(--text-2); text-decoration: none;
      transition: border-color .2s, color .2s, box-shadow .2s;
    }
    .cat-chip:hover {
      border-color: rgba(14,165,233,.4);
      color: var(--teal);
      box-shadow: 0 0 16px rgba(14,165,233,.15);
    }
    .cats-wrap { display: flex; flex-wrap: wrap; gap: .7rem; justify-content: center; }

    .cta-section {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 4rem 2rem; text-align: center;
      position: relative; overflow: hidden;
    }
    .cta-section::before {
      content: '';
      position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
      width: 400px; height: 400px; border-radius: 50%;
      background: radial-gradient(circle, rgba(14,165,233,.12), transparent 70%);
    }
    .cta-section h2 {
      font-size: 2rem; font-weight: 900; color: var(--text);
      letter-spacing: -.04em; margin-bottom: .6rem; position: relative;
    }
    .cta-section p { color: var(--text-2); margin-bottom: 2rem; position: relative; }

    .footer {
      background: rgba(6,11,20,.9);
      backdrop-filter: blur(20px);
      border-top: 1px solid var(--border);
      padding: 3rem 0 1.5rem; margin-top: 4rem;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 2.5rem; margin-bottom: 2.5rem;
    }
    .footer-brand { font-size: 1.1rem; font-weight: 800; color: var(--text); margin-bottom: .8rem; }
    .footer-desc  { font-size: .85rem; color: var(--text-2); line-height: 1.7; }
    .footer-col h4 {
      font-size: .72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .1em; color: var(--text-3); margin-bottom: 1rem;
    }
    .footer-col a {
      display: block; color: var(--text-2); text-decoration: none;
      font-size: .875rem; margin-bottom: .6rem; transition: color .15s;
    }
    .footer-col a:hover { color: var(--teal); }
    .footer-bottom {
      border-top: 1px solid var(--border);
      padding-top: 1.5rem; font-size: .8rem;
      text-align: center; color: var(--text-3);
    }

    @media (max-width: 768px) {
      .hero h1 { font-size: 2.2rem; }
      .footer-grid { grid-template-columns: 1fr 1fr; }
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

<!-- Hero -->
<div class="hero">
  <h1>Learn by teaching,<br><span>teach by learning.</span></h1>
  <p>Connect with people who have the skills you want — and share yours in return. No money, just knowledge.</p>
  <div class="hero-btns">
    <a href="skill_market.php" class="btn btn-primary" style="padding:.85rem 2rem;font-size:.95rem;">Browse Skills</a>
    <a href="add_proof.php" class="btn btn-glass" style="padding:.85rem 2rem;font-size:.95rem;">Share My Skills</a>
  </div>
</div>

<!-- How it works -->
<div class="page section">
  <h2 class="section-title">How TechTalk Works</h2>
  <p class="section-sub">Four simple steps to start your skill exchange journey</p>
  <div class="grid-2">
    <div class="step-card">
      <div class="step-icon">📚</div>
      <div class="step-title">Share Your Skills</div>
      <div class="step-desc">Create your skill portfolio with proof links, GitHub, or demo videos.</div>
      <a href="add_proof.php" class="btn btn-primary">Add Skills</a>
    </div>
    <div class="step-card">
      <div class="step-icon">🔍</div>
      <div class="step-title">Find Teachers</div>
      <div class="step-desc">Browse skills from talented people. Send a swap request to learn from them.</div>
      <a href="skill_market.php" class="btn btn-primary">Browse Skills</a>
    </div>
    <div class="step-card">
      <div class="step-icon">🤝</div>
      <div class="step-title">Connect & Learn</div>
      <div class="step-desc">Chat with your learning partners, schedule sessions, and start exchanging.</div>
      <a href="messaging.php" class="btn btn-primary">Start Chatting</a>
    </div>
    <div class="step-card">
      <div class="step-icon">🎓</div>
      <div class="step-title">Earn Certificates</div>
      <div class="step-desc">Complete sessions and receive auto-generated certificates.</div>
      <a href="certificates.php" class="btn btn-primary">View Certificates</a>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="stats-section">
  <div class="page">
    <div class="stats-grid">
      <div><div class="stat-num">10+</div><div class="stat-label">Active Users</div></div>
      <div><div class="stat-num">6+</div><div class="stat-label">Skills Shared</div></div>
      <div><div class="stat-num">6+</div><div class="stat-label">Sessions Completed</div></div>
      <div><div class="stat-num">99%</div><div class="stat-label">Satisfaction Rate</div></div>
    </div>
  </div>
</div>

<!-- Categories -->
<div class="page section">
  <h2 class="section-title">Popular Categories</h2>
  <p class="section-sub">Explore skills across every domain</p>
  <div class="cats-wrap">
    <a href="skill_market.php?category=Programming"  class="cat-chip">💻 Programming</a>
    <a href="skill_market.php?category=Design"       class="cat-chip">🎨 Design</a>
    <a href="skill_market.php?category=Marketing"    class="cat-chip">📱 Marketing</a>
    <a href="skill_market.php?category=Business"     class="cat-chip">💼 Business</a>
    <a href="skill_market.php?category=Photography"  class="cat-chip">📸 Photography</a>
    <a href="skill_market.php?category=Video+Editing" class="cat-chip">🎬 Video Editing</a>
    <a href="skill_market.php?category=Music"        class="cat-chip">🎵 Music</a>
    <a href="skill_market.php?category=Languages"    class="cat-chip">🌍 Languages</a>
  </div>
</div>

<!-- CTA -->
<div class="page" style="padding-bottom:4rem;">
  <div class="cta-section">
    <h2>Ready to start your journey?</h2>
    <p>Join TechTalk today and unlock endless learning opportunities</p>
    <a href="dashbord.php" class="btn btn-primary" style="padding:.85rem 2.5rem;font-size:.95rem;">Go to Dashboard</a>
  </div>
</div>

<?php include 'footer.php'; ?>

</div>
</body>
</html>
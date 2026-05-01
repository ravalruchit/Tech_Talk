<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTalk | Skill Exchange Platform</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .landing-nav {
      position: sticky; top: 0; z-index: 200;
      background: rgba(6,11,20,0.85);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      padding: 0 2rem; height: 64px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .landing-brand {
      font-size: 1.15rem; font-weight: 800;
      text-decoration: none; color: var(--text);
      display: flex; align-items: center; gap: .5rem;
    }
    .landing-nav-links { display: flex; align-items: center; gap: .8rem; }

    .hero {
      padding: 7rem 2rem 5rem;
      text-align: center; position: relative;
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

    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.2rem; padding: 4rem 2rem; max-width: 1100px; margin: 0 auto;
    }
    .feature-card {
      background: var(--glass); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: var(--radius-lg);
      padding: 1.8rem; text-align: center;
      transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .feature-card:hover {
      border-color: rgba(14,165,233,.35);
      box-shadow: 0 8px 32px rgba(14,165,233,.12);
      transform: translateY(-3px);
    }
    .feature-icon {
      font-size: 2.5rem; margin-bottom: 1rem; display: block;
    }
    .feature-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: .5rem; }
    .feature-desc  { font-size: .875rem; color: var(--text-2); line-height: 1.6; }

    .stats-section {
      background: var(--glass); backdrop-filter: blur(16px);
      border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
      padding: 3rem 2rem;
    }
    .stats-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1.5rem; text-align: center; max-width: 800px; margin: 0 auto;
    }
    .stat-num {
      font-size: 2.4rem; font-weight: 900;
      background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .stat-label { font-size: .82rem; color: var(--text-3); margin-top: .3rem; }

    .cta-section {
      text-align: center; padding: 5rem 2rem;
    }
    .cta-section h2 {
      font-size: 2.2rem; font-weight: 900; color: var(--text);
      letter-spacing: -.04em; margin-bottom: .8rem;
    }
    .cta-section p { color: var(--text-2); margin-bottom: 2rem; font-size: 1rem; }

    @media (max-width: 768px) {
      .hero h1 { font-size: 2.2rem; }
      .landing-nav { padding: 0 1rem; }
    }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<!-- Navbar -->
<nav class="landing-nav">
  <a class="landing-brand" href="landing.php">
    <span class="brand-dot"></span>TechTalk
  </a>
  <div class="landing-nav-links">
    <a href="login.php"  class="btn btn-glass"    style="padding:.45rem 1.1rem;">Log in</a>
    <a href="main.php"   class="btn btn-primary"  style="padding:.45rem 1.1rem;">Sign up free</a>
  </div>
</nav>

<!-- Hero -->
<div class="hero">
  <h1>Learn by teaching,<br><span>teach by learning.</span></h1>
  <p>Connect with people who have the skills you want — and share yours in return. No money, just knowledge.</p>
  <div class="hero-btns">
    <a href="main.php"  class="btn btn-primary" style="padding:.85rem 2rem;font-size:.95rem;">Get started free →</a>
    <a href="login.php" class="btn btn-glass"   style="padding:.85rem 2rem;font-size:.95rem;">Log in</a>
  </div>
</div>

<!-- Features -->
<div class="features">
  <div class="feature-card">
    <span class="feature-icon">🔄</span>
    <div class="feature-title">Real Skill Swaps</div>
    <div class="feature-desc">You teach what you know, they teach what you want. Fair exchange, no money.</div>
  </div>
  <div class="feature-card">
    <span class="feature-icon">📹</span>
    <div class="feature-title">Video Sessions</div>
    <div class="feature-desc">Built-in video calling so you can learn face to face from anywhere.</div>
  </div>
  <div class="feature-card">
    <span class="feature-icon">📅</span>
    <div class="feature-title">Auto Scheduling</div>
    <div class="feature-desc">Accept a request and sessions are created instantly for both of you.</div>
  </div>
  <div class="feature-card">
    <span class="feature-icon">🎓</span>
    <div class="feature-title">Earn Certificates</div>
    <div class="feature-desc">Get verified proof of every skill you complete. Download and share.</div>
  </div>
</div>

<!-- Stats -->
<div class="stats-section">
  <div class="stats-grid">
    <div><div class="stat-num">10+</div><div class="stat-label">Active Users</div></div>
    <div><div class="stat-num">6+</div><div class="stat-label">Skills Shared</div></div>
    <div><div class="stat-num">6+</div><div class="stat-label">Sessions Done</div></div>
    <div><div class="stat-num">100%</div><div class="stat-label">Free Forever</div></div>
  </div>
</div>

<!-- CTA -->
<div class="cta-section">
  <h2>Ready to start learning?</h2>
  <p>Join TechTalk today — it's completely free, no credit card needed.</p>
  <a href="main.php" class="btn btn-primary" style="padding:.9rem 2.5rem;font-size:1rem;">Create Free Account →</a>
</div>

<?php include 'footer.php'; ?>

</body>
</html>

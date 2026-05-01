<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privacy Policy | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .policy-hero {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 3rem 2.5rem;
      text-align: center;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    .policy-hero::before {
      content: '';
      position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(14,165,233,.12), transparent 70%);
    }
    .policy-hero-icon {
      font-size: 3.5rem; margin-bottom: 1rem;
      display: block; position: relative; z-index: 1;
    }
    .policy-hero-title {
      font-size: 2.2rem; font-weight: 900;
      color: var(--text); letter-spacing: -.04em;
      margin-bottom: .5rem; position: relative; z-index: 1;
    }
    .policy-hero-sub {
      color: var(--text-2); font-size: .95rem;
      position: relative; z-index: 1;
    }
    .last-updated {
      display: inline-flex; align-items: center; gap: .4rem;
      background: rgba(14,165,233,.1);
      border: 1px solid rgba(14,165,233,.2);
      color: #7dd3fc; font-size: .78rem; font-weight: 600;
      padding: .3rem .8rem; border-radius: 20px;
      margin-top: 1rem; position: relative; z-index: 1;
    }

    /* Section cards */
    .section-card {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2rem;
      margin-bottom: 1.5rem;
      transition: border-color .2s;
    }
    .section-card:hover {
      border-color: rgba(14,165,233,.25);
    }
    .section-num {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px; height: 32px;
      background: var(--grad);
      border-radius: 10px;
      font-size: .85rem; font-weight: 900; color: #fff;
      margin-right: .8rem;
      box-shadow: 0 4px 12px rgba(14,165,233,.3);
    }
    .section-title {
      font-size: 1.15rem; font-weight: 800;
      color: var(--text); letter-spacing: -.02em;
      margin-bottom: 1rem;
      display: flex; align-items: center;
    }
    .section-text {
      font-size: .9rem; color: var(--text-2);
      line-height: 1.75; margin-bottom: 1rem;
    }
    .section-list {
      list-style: none; padding: 0;
      margin: 1rem 0;
    }
    .section-list li {
      font-size: .875rem; color: var(--text-2);
      line-height: 1.7; margin-bottom: .7rem;
      padding-left: 1.8rem; position: relative;
    }
    .section-list li::before {
      content: '→';
      position: absolute; left: 0;
      color: var(--teal); font-weight: 700;
    }
    .section-list li strong {
      color: var(--text); font-weight: 600;
    }

    /* Important notice box */
    .important-box {
      background: rgba(248,113,113,.08);
      border: 1px solid rgba(248,113,113,.25);
      border-radius: var(--radius);
      padding: 1.2rem 1.5rem;
      margin: 1.5rem 0;
    }
    .important-box-title {
      display: flex; align-items: center; gap: .5rem;
      font-size: .9rem; font-weight: 700;
      color: #fca5a5; margin-bottom: .6rem;
    }
    .important-box-text {
      font-size: .85rem; color: var(--text-2);
      line-height: 1.7;
    }

    /* Contact card */
    .contact-card {
      background: rgba(14,165,233,.06);
      border: 1px solid rgba(14,165,233,.2);
      border-radius: var(--radius);
      padding: 1.2rem 1.5rem;
      display: flex; align-items: center; gap: 1rem;
    }
    .contact-icon {
      width: 48px; height: 48px; border-radius: 12px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(14,165,233,.3);
    }
    .contact-info { flex: 1; }
    .contact-label { font-size: .72rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .2rem; }
    .contact-value { font-size: .9rem; color: #7dd3fc; font-weight: 600; }
  </style>
</head>
<body>

<div class="mesh-bg">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="page-wrap">

  <?php if (isset($_SESSION['email'])): ?>
    <?php include 'navbar.php'; ?>
  <?php else: ?>
    <nav class="navbar">
      <a class="navbar-brand" href="home.php"><span class="brand-dot"></span>TechTalk</a>
      <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="login.php">Login</a>
        <a href="main.php">Sign Up</a>
      </div>
    </nav>
  <?php endif; ?>

  <div class="page page-sm">

    <!-- Hero -->
    <div class="policy-hero">
      <span class="policy-hero-icon">🔒</span>
      <h1 class="policy-hero-title">Privacy Policy</h1>
      <p class="policy-hero-sub">Your privacy matters. Here's how we protect your data.</p>
      <span class="last-updated">📅 Last Updated: January 24, 2026</span>
    </div>

    <!-- Section 1 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">1</span> Introduction</h3>
      <p class="section-text">
        Welcome to TechTalk, a skill exchange platform where knowledge is shared freely. We respect your privacy and are committed to protecting your personal data. This policy explains how we collect, use, and safeguard your information when you use our platform.
      </p>
    </div>

    <!-- Section 2 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">2</span> Information We Collect</h3>
      <p class="section-text">We collect the following types of information:</p>
      <ul class="section-list">
        <li><strong>Account Information:</strong> Name, email address, and encrypted password when you register</li>
        <li><strong>Profile Data:</strong> Bio, skills, proof links, and other details you choose to share</li>
        <li><strong>Communication:</strong> Messages you send through our messaging system</li>
        <li><strong>Session Data:</strong> Information about skill exchange sessions, including dates, times, and participants</li>
        <li><strong>Reviews & Ratings:</strong> Feedback you give and receive from other users</li>
        <li><strong>Usage Data:</strong> How you interact with our platform (pages visited, features used)</li>
      </ul>
    </div>

    <!-- Section 3 — VIDEO RECORDING (CRITICAL) -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">3</span> Video Call Recording</h3>
      <div class="important-box">
        <div class="important-box-title">⚠️ Important Notice</div>
        <div class="important-box-text">
          <strong>All video call sessions on TechTalk are automatically recorded.</strong> These recordings are stored securely on our servers and are used for safety, dispute resolution, and quality assurance purposes. By using our video call feature, you consent to being recorded.
        </div>
      </div>
      <p class="section-text">
        Recordings are only accessible to:
      </p>
      <ul class="section-list">
        <li>Platform administrators for safety review and dispute resolution</li>
        <li>Law enforcement if legally required</li>
        <li>Users involved in the session (upon request for dispute resolution)</li>
      </ul>
      <p class="section-text">
        Recordings are automatically deleted after 90 days unless flagged for review or involved in an active dispute.
      </p>
    </div>

    <!-- Section 4 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">4</span> How We Use Your Information</h3>
      <ul class="section-list">
        <li>Provide and maintain our skill-sharing services</li>
        <li>Connect you with other users for learning and teaching</li>
        <li>Send important updates about your account and scheduled sessions</li>
        <li>Improve platform features and user experience</li>
        <li>Ensure safety and security of all users</li>
        <li>Investigate reports of misconduct or policy violations</li>
        <li>Comply with legal obligations and enforce our terms</li>
      </ul>
    </div>

    <!-- Section 5 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">5</span> Information Sharing</h3>
      <p class="section-text">
        <strong>We do not sell your personal information.</strong> We may share your information only in these circumstances:
      </p>
      <ul class="section-list">
        <li><strong>With Other Users:</strong> Your profile, skills, and reviews are visible to facilitate skill matching</li>
        <li><strong>Safety & Moderation:</strong> Reports and recordings may be reviewed by our admin team</li>
        <li><strong>Legal Requirements:</strong> When required by law, court order, or to protect rights and safety</li>
        <li><strong>Service Providers:</strong> Trusted third parties who help operate our platform (under strict confidentiality)</li>
      </ul>
    </div>

    <!-- Section 6 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">6</span> Data Security</h3>
      <p class="section-text">
        We implement industry-standard security measures to protect your data:
      </p>
      <ul class="section-list">
        <li>Passwords are encrypted using bcrypt hashing</li>
        <li>Secure HTTPS connections for all data transmission</li>
        <li>Regular security audits and updates</li>
        <li>Access controls limiting who can view sensitive data</li>
        <li>Encrypted storage for video recordings</li>
      </ul>
      <p class="section-text">
        However, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security.
      </p>
    </div>

    <!-- Section 7 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">7</span> Your Rights</h3>
      <p class="section-text">You have the right to:</p>
      <ul class="section-list">
        <li><strong>Access:</strong> View all personal data we hold about you</li>
        <li><strong>Update:</strong> Correct or update your profile information anytime</li>
        <li><strong>Delete:</strong> Request deletion of your account and associated data</li>
        <li><strong>Export:</strong> Request a copy of your data in a portable format</li>
        <li><strong>Object:</strong> Opt-out of certain data processing activities</li>
        <li><strong>Withdraw Consent:</strong> Stop using features that require consent (like video recording)</li>
      </ul>
      <p class="section-text">
        To exercise these rights, contact us at <strong style="color:var(--teal);">privacy@techtalk.com</strong>
      </p>
    </div>

    <!-- Section 8 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">8</span> Cookies & Tracking</h3>
      <p class="section-text">
        We use cookies and similar technologies to:
      </p>
      <ul class="section-list">
        <li>Maintain your login session</li>
        <li>Remember your preferences</li>
        <li>Analyze platform usage to improve features</li>
      </ul>
      <p class="section-text">
        You can control cookie settings through your browser preferences. Disabling cookies may limit some platform features.
      </p>
    </div>

    <!-- Section 9 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">9</span> Children's Privacy</h3>
      <p class="section-text">
        TechTalk is not intended for users under 13 years of age. We do not knowingly collect information from children under 13. If you believe a child has provided us with personal information, please contact us immediately.
      </p>
    </div>

    <!-- Section 10 -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">10</span> Changes to This Policy</h3>
      <p class="section-text">
        We may update this privacy policy from time to time to reflect changes in our practices or legal requirements. We will notify you of significant changes by:
      </p>
      <ul class="section-list">
        <li>Posting the updated policy on this page</li>
        <li>Updating the "Last Updated" date</li>
        <li>Sending an email notification for major changes</li>
      </ul>
      <p class="section-text">
        Your continued use of TechTalk after changes constitutes acceptance of the updated policy.
      </p>
    </div>

    <!-- Contact -->
    <div class="section-card">
      <h3 class="section-title"><span class="section-num">11</span> Contact Us</h3>
      <p class="section-text" style="margin-bottom:1.5rem;">
        If you have questions about this privacy policy or our data practices, reach out to us:
      </p>
      <div class="contact-card">
        <div class="contact-icon">📧</div>
        <div class="contact-info">
          <div class="contact-label">Email</div>
          <div class="contact-value">privacy@techtalk.com</div>
        </div>
      </div>
      <div class="contact-card" style="margin-top:.8rem;">
        <div class="contact-icon">📍</div>
        <div class="contact-info">
          <div class="contact-label">Address</div>
          <div class="contact-value">TechTalk, Ahmedabad, Gujarat, India</div>
        </div>
      </div>
    </div>

    <!-- Back button -->
    <div style="text-align:center;margin-top:2rem;">
      <a href="<?= isset($_SESSION['email']) ? 'dashbord.php' : 'home.php' ?>" class="btn btn-primary">
        ← Back to <?= isset($_SESSION['email']) ? 'Dashboard' : 'Home' ?>
      </a>
    </div>

  </div><!-- /page -->

  <?php include 'footer.php'; ?>

</div><!-- /page-wrap -->

</body>
</html>

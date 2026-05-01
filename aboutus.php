<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us | Skill Exchange</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
/* 🌟 Reset */
body {
  margin: 0;
  font-family: "Inter", sans-serif;
  background: #f8f8f8;
  color: #333;
  line-height: 1.7;
}

/* 🌟 Navbar */
.navbar {
  background: #fff;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  position: sticky;
  top: 0;
  z-index: 1000;
}
.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0.8rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.navbar-logo {
  font-size: 1.4rem;
  font-weight: 700;
  background: linear-gradient(135deg, #ff9f43, #d16ba5);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.nav-links {
  display: flex;
  gap: 1.5rem;
}
.nav-links a {
  text-decoration: none;
  color: #444;
  font-weight: 500;
  transition: color 0.3s;
}
.nav-links a:hover {
  color: #ff9f43;
}

/* 🌟 Hero Section */
.about-hero {
  text-align: center;
  padding: 4rem 2rem;
  background: linear-gradient(135deg, #ff9f43, #ff6b6b, #9b5de5);
  color: white;
}
.about-hero h1 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}
.about-hero p {
  font-size: 1.2rem;
  max-width: 700px;
  margin: 0 auto;
  opacity: 0.9;
}

/* 🌟 About Sections */
.about-container {
  max-width: 1000px;
  margin: 3rem auto;
  padding: 0 1rem;
}
.about-section {
  background: #fff;
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
  transition: transform 0.3s ease;
}
.about-section:hover {
  transform: translateY(-6px);
}
.about-section h2 {
  font-size: 1.6rem;
  margin-bottom: 1rem;
  background: linear-gradient(135deg, #ff9f43, #d16ba5);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.about-section p {
  color: #555;
  font-size: 1rem;
  margin: 0;
}

/* 🌟 Footer */
.footer {
  background: #222;
  color: #ccc;
  padding: 2rem 1rem;
  margin-top: 3rem;
}
.footer-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  max-width: 1100px;
  margin: auto;
}
.footer h3, .footer h4 {
  color: #fff;
}
.footer p {
  font-size: 0.95rem;
  line-height: 1.5;
}
.footer ul {
  list-style: none;
  padding: 0;
}
.footer ul li {
  margin-bottom: 0.5rem;
}
.footer ul li a {
  text-decoration: none;
  color: #ccc;
  transition: color 0.3s;
}
.footer ul li a:hover {
  color: #ff9f43;
}
.social-icons a {
  color: #ff9f43;
  margin-right: 0.8rem;
  font-size: 1.3rem;
  transition: opacity 0.3s;
}
.social-icons a:hover {
  opacity: 0.7;
}
.footer-bottom {
  text-align: center;
  margin-top: 1.5rem;
  border-top: 1px solid #444;
  padding-top: 1rem;
  font-size: 0.9rem;
  color: #aaa;
}
  </style>
</head>
<body>

<!-- 🌟 Navbar -->
<nav class="navbar">
  <div class="navbar-container">
    <div class="navbar-logo">Skill Exchange</div>
    <div class="nav-links">
      <a href="home.php">Home</a>
      <a href="skill_market.php">Skill Marketplace</a>
      <a href="messaging.php">Messages</a>
      <a href="Login.php">Logout</a>
    </div>
  </div>
</nav>

<!-- 🌟 Hero -->
<div class="about-hero">
  <h1>👋 Hey, Welcome to TechTalk!</h1>
  <p>We're not just a platform — we're a community of learners and teachers helping each other grow.</p>
</div>

<!-- 🌟 About Sections -->
<div class="about-container">
  <div class="about-section">
    <h2>🌱 Our Story</h2>
    <p>
      TechTalk started with a simple belief: <strong>everyone has something worth teaching, 
      and everyone has something worth learning</strong>.  
      Whether it's coding your first website, learning guitar chords, or cooking your favorite dish,  
      this is the place to exchange skills and support each other.
    </p>
  </div>

  <div class="about-section">
    <h2>🎯 Our Mission</h2>
    <p>
      To create a friendly, supportive space where people can share their knowledge, 
      grow their confidence, and connect with others across the globe.  
      We're not about textbooks — we're about <em>real people</em> helping <em>real people</em>.
    </p>
  </div>

  <div class="about-section">
    <h2>💙 Why You’ll Love TechTalk</h2>
    <p>
      ✨ Learn anything, from anywhere.  
      ✨ Teach what you know and earn recognition.  
      ✨ Build friendships, not just connections.  
    </p>
  </div>
</div>

<!-- 🌟 Footer -->
<footer class="footer">
  <div class="footer-container">
    <div class="footer-column">
      <h3>Skill Exchange</h3>
      <p>Learn. Teach. Grow.<br> A platform to connect learners & mentors worldwide.</p>
    </div>
    <div class="footer-column">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="Skill_market.php">Skill Marketplace</a></li>
        <li><a href="#">Contact</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h4>Follow us</h4>
      <div class="social-icons">
        <a href="https://www.instagram.com/ravalruchit09/?__pwa=1#"><i class="fa-brands fa-instagram"></i></a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?php echo date("Y"); ?> Skill Exchange. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
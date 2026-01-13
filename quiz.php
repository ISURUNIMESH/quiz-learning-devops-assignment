<?php
// quiz.php: Restrict access to signed-in users
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$quizzes = [];
$res = $conn->query("SELECT q.id,q.title,q.description,q.time_limit_seconds,q.points_per_question,q.max_time_bonus,q.featured_image, (SELECT COUNT(*) FROM questions t WHERE t.quiz_id = q.id) AS question_count FROM quizzes q ORDER BY q.id ASC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $quizzes[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quizzes - InfoNix</title>
  <style>
    /* Reset */
    * {margin:0; padding:0; box-sizing:border-box;}

    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background: linear-gradient(-45deg, #e0f2fe, #bfdbfe, #93c5fd, #e0f2fe);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      color: #111;
    }
    @keyframes gradientBG {
      0% {background-position: 0% 50%;}
      50% {background-position: 100% 50%;}
      100% {background-position: 0% 50%;}
    }

    /* Header */
    header {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(0,0,0,0.1);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 90%;
      margin: auto;
    }
    header h1 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e40af;
    }

    nav {
      display: flex;
      gap: 1rem;
    }
    nav a {
      text-decoration: none;
      font-weight: 500;
      color: #111;
      transition: color 0.3s;
    }
    nav a:hover { color: #1d4ed8; }

    /* Hamburger Menu */
    .menu-toggle {
      display: none;
      flex-direction: column;
      cursor: pointer;
    }
    .menu-toggle span {
      width: 25px;
      height: 3px;
      background: #111;
      margin: 4px 0;
      border-radius: 2px;
    }

    @media(max-width: 768px) {
      nav {
        display: none;
        flex-direction: column;
        background: rgba(255,255,255,0.95);
        position: absolute;
        top: 70px;
        right: 20px;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
      }
      nav.active { display: flex; }
      .menu-toggle { display: flex; }
    }

    /* Quizzes */
    .quizzes {
      padding: 2rem 1rem;
      text-align: center;
    }
    .quizzes h2 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 2rem;
      color: #1e3a8a;
    }
    .quiz-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      justify-content: center;
    }
    .quiz-card {
      background: #ffffffdd;
      border-radius: 1rem;
      padding: 1rem;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .quiz-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 32px rgba(0,0,0,0.25);
    }
    .quiz-card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 0.75rem;
    }
    .quiz-card h4 {
      margin: 0.8rem 0 0.4rem;
      font-size: 1.2rem;
      font-weight: 600;
      color: #1e40af;
    }
    .quiz-card p {
      font-size: 0.95rem;
      color: #333;
      margin: 0.3rem 0;
    }

    /* Buttons */
    .auth-btn {
      display: inline-block;
      padding: 0.6rem 1.2rem;
      background: linear-gradient(90deg, #2563eb, #3b82f6);
      color: #fff;
      font-weight: 600;
      border-radius: 0.75rem;
      text-decoration: none;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .auth-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0,0,0,0.3);
    }

    /* Footer */
    footer {
      text-align: center;
      padding: 1.5rem 0;
      background: rgba(255,255,255,0.9);
      border-top: 1px solid rgba(0,0,0,0.1);
      margin-top: 2rem;
      color: #333;
    }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <h1>InfoNix</h1>
      <nav id="navMenu">
        <a href="index.html">Home</a>
        <a href="dashboard.html">Dashboard</a>
        <a href="quiz.php">Quizzes</a>
        <a href="enhanced_leaderboard.php">Leaderboard</a>
        <a href="news.php">News & Updates</a>
        <a href="support.html">Support</a>
      </nav>
      <div class="menu-toggle" id="menuToggle">
        <span></span><span></span><span></span>
      </div>
    </div>
  </header>

  <main>
    <section class="quizzes">
      <h2>Choose a Quiz</h2>
      <div class="quiz-cards">
        <?php if(empty($quizzes)): ?>
          <p>No quizzes available.</p>
        <?php else: foreach($quizzes as $q): ?>
          <div class="quiz-card">
            <?php $img = !empty($q['featured_image']) ? $q['featured_image'] : 'assets/default_quiz.jpg'; ?>
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($q['title']); ?>">
            <h4><?php echo htmlspecialchars($q['title']); ?></h4>
            <p><?php echo htmlspecialchars($q['description']); ?></p>
            <p style="font-size:0.85rem;color:#555;">
              Time: <?php echo intval($q['time_limit_seconds']); ?>s · Points/Q: <?php echo intval($q['points_per_question']); ?>
            </p>
            <?php if(intval($q['question_count']) === 0): ?>
              <div style="margin-top:0.5rem;color:#777;font-weight:600;">Not ready — no questions</div>
              <a class="auth-btn" style="opacity:0.5;pointer-events:none;margin-top:0.5rem;">Take Quiz</a>
            <?php else: ?>
              <a class="auth-btn" href="take_quiz.php?quiz_id=<?php echo intval($q['id']); ?>" style="margin-top:0.5rem;">Take Quiz</a>
            <?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 InfoNix. All rights reserved.</p>
  </footer>

  <script>
    // Toggle menu on mobile
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    menuToggle.addEventListener('click', () => {
      navMenu.classList.toggle('active');
    });
  </script>
</body>
</html>

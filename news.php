<?php
// news.php: Display article summaries from the database

$servername = "sql12.freesqldatabase.com";
$username   = "sql12814273";
$password   = "aw2rwFjSiF";
$dbname     = "sql12814273";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$articles = [];

/*
  Check if the `articles` table exists BEFORE querying it
  This prevents fatal errors when the table is missing
*/
$tableCheck = $conn->query("
    SELECT 1 
    FROM information_schema.tables 
    WHERE table_schema = '$dbname' 
      AND table_name = 'articles'
    LIMIT 1
");

if ($tableCheck && $tableCheck->num_rows === 1) {

    // Table exists → safe to query
    $result = $conn->query("
        SELECT * 
        FROM articles 
        ORDER BY id DESC 
        LIMIT 12
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
    }
}

// If table does NOT exist → $articles remains empty (NO CRASH)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>News & Updates - InfoNix</title>
  <style>
    /* Reset */
    * {margin:0; padding:0; box-sizing:border-box;}

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg,#3b82f6,#9333ea,#f43f5e);
      background-size: 300% 300%;
      animation: gradientMove 12s ease infinite;
      color: #fff;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    @keyframes gradientMove {
      0%{background-position:0% 50%;}
      50%{background-position:100% 50%;}
      100%{background-position:0% 50%;}
    }

    .container {
      width: 90%;
      max-width: 1200px;
      margin: auto;
    }

    header {
      background: rgba(0,0,0,0.3);
      backdrop-filter: blur(6px);
      padding: 1rem 0;
      border-bottom: 2px solid rgba(255,255,255,0.1);
    }
    header .container {
      display:flex;
      justify-content:space-between;
      align-items:center;
    }
    header h1 {
      font-size:1.8rem;
      font-weight:bold;
      color:#fff;
    }
    nav a {
      margin:0 10px;
      color:#fff;
      text-decoration:none;
      font-weight:500;
      padding:6px 12px;
      border-radius:6px;
      transition:background 0.3s;
    }
    nav a:hover {
      background:rgba(255,255,255,0.2);
    }

    /* Hero */
    .news-hero {
      text-align:center;
      padding:2rem 1rem;
    }
    .news-hero h2 {
      font-size:2.2rem;
      margin-bottom:0.5rem;
    }
    .news-hero p {
      font-size:1rem;
      opacity:0.9;
    }

    /* News Grid */
    .news-grid {
      display:grid;
      grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
      gap:1.5rem;
      padding:2rem;
      width:90%;
      margin:auto;
    }
    .news-card {
      background: rgba(255,255,255,0.15);
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 8px 20px rgba(0,0,0,0.3);
      display:flex;
      flex-direction:column;
      transition:transform 0.3s;
    }
    .news-card:hover {
      transform: translateY(-6px);
    }
    .news-card img {
      width:100%;
      height:180px;
      object-fit:cover;
      border-bottom:4px solid rgba(255,255,255,0.2);
    }
    .news-card-body {
      padding:1rem;
      flex:1;
      display:flex;
      flex-direction:column;
    }
    .news-card-body h3 {
      font-size:1.2rem;
      margin-bottom:0.5rem;
      color:#ffdd57;
    }
    .news-date {
      font-size:0.85rem;
      opacity:0.8;
      margin-bottom:0.8rem;
    }
    .news-excerpt {
      flex:1;
      font-size:0.95rem;
      line-height:1.5;
      margin-bottom:1rem;
      color:#f1f1f1;
    }
    .read-more-btn {
      align-self:flex-start;
      background:linear-gradient(45deg,#3b82f6,#9333ea);
      color:#fff;
      text-decoration:none;
      padding:0.6rem 1rem;
      border-radius:8px;
      font-weight:bold;
      transition: transform 0.2s, background 0.3s;
    }
    .read-more-btn:hover {
      transform:scale(1.05);
      background:linear-gradient(45deg,#2563eb,#7e22ce);
    }

    footer {
      background:rgba(0,0,0,0.3);
      padding:1rem 0;
      text-align:center;
      font-size:0.9rem;
      color:#ddd;
      border-top:2px solid rgba(255,255,255,0.1);
      margin-top:auto;
    }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <h1>InfoNix</h1>
      <nav>
        <a href="index.html">Home</a>
        <a href="dashboard.html">Dashboard</a>
        <a href="quiz.php">Quizzes</a>
        <a href="enhanced_leaderboard.php">Leaderboard</a>
        <a href="news.php">News & Updates</a>
        <a href="support.html">Support</a>
      </nav>
      <a href="profile.php" class="signin-icon" title="Profile">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="8" r="4" fill="#3b82f6"/>
          <path d="M4 20c0-3.3137 3.134-6 7-6s7 2.6863 7 6" fill="#3b82f6"/>
        </svg>
      </a>
    </div>
  </header>
  <main>
    <section class="news-hero">
      <h2>News & Updates</h2>
      <p>Latest IT announcements, product updates, and community highlights — curated for InfoNix learners.</p>
    </section>
    <section class="news-grid">
      <?php
      if ($articles && $articles->num_rows > 0) {
          while ($row = $articles->fetch_assoc()) {
              echo '<article class="news-card">';
              // prefer `image`, then `image_url`, then placeholder
              if (!empty($row['image'])) {
                  $img = $row['image'];
              } elseif (isset($row['image_url']) && !empty($row['image_url'])) {
                  $img = $row['image_url'];
              } else {
                  $img = 'https://via.placeholder.com/800x400?text=No+Image';
              }
              $imgEsc = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
              $titleEsc = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
              echo '<img src="'.$imgEsc.'" alt="'.$titleEsc.'" onerror="this.onerror=null;this.src=\'https://via.placeholder.com/800x400?text=Image+not+available\';">';
              echo '<div class="news-card-body">';
              echo '<h3>'.$titleEsc.'</h3>';
              echo '<div class="news-date">'.date('F Y', strtotime($row['date'])).'</div>';
              echo '<div class="news-excerpt">'.htmlspecialchars($row['summary']).'</div>';
              echo '<a class="read-more-btn" href="article.php?id='.$row['id'].'">Read more</a>';
              echo '</div></article>';
          }
      } else {
          echo '<p>No articles found.</p>';
      }
      ?>
    </section>
  </main>
  <footer>
    <div class="container">
      <p>&copy; 2025 InfoNix. All rights reserved.</p>
    </div>
  </footer>
</body>
</html>

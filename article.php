<?php
// article.php: Display full article from the database
$servername = "sql12.freesqldatabase.com";
$username = "sql12814273";
$password = "aw2rwFjSiF";
$dbname = "sql12814273";
        require_once 'db_connect.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = $conn->query("SELECT * FROM articles WHERE id = $id LIMIT 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Article - InfoNix</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3b82f6, #9333ea);
            color: #222;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* HEADER */
        header {
            background: linear-gradient(90deg, #3b82f6, #9333ea);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        header h1 {
            color: #fff;
            font-size: 2rem;
            margin: 0;
            font-weight: bold;
        }

        header .container {
            max-width: 1200px;
            margin: auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        nav a {
            color: #fff;
            margin: 0 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        nav a:hover {
            color: #fef9c3;
            text-shadow: 0 0 8px rgba(255,255,255,0.9);
        }

        /* ARTICLE SECTION */
        .article-full {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2.5rem 2rem;
            max-width: 850px;
            margin: 3rem auto;
            animation: fadeIn 0.8s ease-in-out;
        }

        .article-full h2 {
            font-size: 2.4rem;
            background: linear-gradient(90deg, #3b82f6, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.7rem;
            text-align: center;
        }

        .news-date {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .article-content {
            font-size: 1.2rem;
            line-height: 1.9;
            color: #374151;
            padding: 1.2rem;
            background: linear-gradient(145deg, #f9fafb, #f3f4f6);
            border-radius: 1rem;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.05);
        }

        /* BUTTON */
        .auth-btn {
            background: linear-gradient(90deg, #3b82f6, #9333ea);
            color: #fff !important;
            padding: 0.7rem 2rem;
            border: none;
            border-radius: 2rem;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 1.5rem;
        }

        .auth-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(147,51,234,0.4);
        }

        /* FOOTER */
        footer {
            background: linear-gradient(90deg, #3b82f6, #9333ea);
            color: #fff;
            padding: 1.2rem 0;
            text-align: center;
            margin-top: auto;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
        }

        /* PROFILE ICON */
        .signin-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            padding: 6px;
            transition: 0.3s;
        }

        .signin-icon:hover {
            background: rgba(255,255,255,0.35);
            transform: scale(1.1);
        }

        /* FADE ANIMATION */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>InfoNix</h1>
            <nav>
                <a href="index.html">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="quiz.php">Quizzes</a>
                <a href="enhanced_leaderboard.php">Leaderboard</a>
                <a href="news.php">News & Updates</a>
                <a href="support.html">Support</a>
            </nav>
            <a href="profile.php" class="signin-icon" title="Profile">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="8" r="4" fill="#fff"/>
                    <path d="M4 20c0-3.3137 3.134-6 7-6s7 2.6863 7 6" fill="#fff"/>
                </svg>
            </a>
        </div>
    </header>
    <main>
        <section class="article-full">
            <?php
            if ($article && $article->num_rows > 0) {
                $row = $article->fetch_assoc();
                if (!empty($row['image'])) {
                    $img = $row['image'];
                } elseif (isset($row['image_url']) && !empty($row['image_url'])) {
                    $img = $row['image_url'];
                } else {
                    $img = 'https://via.placeholder.com/1000x400?text=No+Image';
                }
                $imgEsc = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
                $titleEsc = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
                echo '<div style="display:flex;flex-direction:column;align-items:center;">';
                echo '<img src="' . $imgEsc . '" alt="' . $titleEsc . '" style="max-width:100%;max-height:340px;border-radius:1rem;box-shadow:0 6px 20px rgba(0,0,0,0.3);margin-bottom:2rem;object-fit:cover;" onerror="this.onerror=null;this.src=\'https://via.placeholder.com/1000x400?text=Image+not+available\';">';
                echo '<h2>' . $titleEsc . '</h2>';
                echo '<div class="news-date">' . date('F Y', strtotime($row['date'])) . '</div>';
                echo '</div>';
                echo '<div class="article-content">' . nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8')) . '</div>';
            } else {
                echo '<p>Article not found.</p>';
            }
            ?>
            <a href="news.php" class="auth-btn">⬅ Back to News</a>
        </section>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2025 InfoNix. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

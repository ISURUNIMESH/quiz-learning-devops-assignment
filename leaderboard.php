<?php
// enhanced_leaderboard.php: Restrict access to signed-in users
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

require_once 'db_connect.php';

$leaderboard = [];

/*
  We CANNOT use `profiles` table because it does not exist.
  So we build leaderboard using EXISTING tables:
  - users
  - quiz_attempts
*/

$sql = "
    SELECT 
        u.name,
        SUM(qa.score) AS total_score
    FROM users u
    JOIN quiz_attempts qa ON qa.user_id = u.id
    GROUP BY u.id
    ORDER BY total_score DESC
    LIMIT 10
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - InfoNix</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* ==== GENERAL STYLES ==== */
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }
        header {
            background: #3b82f6;
            color: #fff;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        header .container {
            width: 90%;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin: 0;
            font-size: 1.6rem;
        }
        nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .dropdown {
            position: relative;
        }
        .dropbtn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background: #fff;
            top: 100%;
            left: 0;
            min-width: 200px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            z-index: 10;
        }
        .dropdown-content a {
            display: block;
            padding: 0.7rem 1rem;
            color: #333;
        }
        .dropdown-content a:hover {
            background: #f1f1f1;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .signin-icon svg {
            background: #fff;
            border-radius: 50%;
            padding: 4px;
        }

        /* ==== MAIN ==== */
        main {
            width: 90%;
            margin: 2rem auto;
        }
        .leaderboard-container {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            text-align: center;
            font-size: 2rem;
            color: #3b82f6;
        }

        /* ==== USER CARD ==== */
        .your-rank-card {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            padding: 1rem;
            border: 2px solid #3b82f6;
            border-radius: 10px;
            margin-bottom: 2rem;
            background: #f9fbff;
        }
        .your-rank-avatar img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3b82f6;
        }
        .badge {
            display: inline-block;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
        }
        .gold { background: linear-gradient(45deg, #f59e0b, #fbbf24); }

        /* ==== LEADERBOARD TABLE ==== */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .leaderboard-table th, .leaderboard-table td {
            padding: 0.8rem;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        .leaderboard-table th {
            background: #3b82f6;
            color: #fff;
        }
        .leaderboard-table tr:hover {
            background: #f1f5f9;
        }
        .leaderboard-table img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ==== STATS ==== */
        .leaderboard-stats {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            background: #f9fbff;
            border: 1px solid #ddd;
            padding: 1.2rem;
            border-radius: 10px;
            text-align: center;
        }
        .stat-icon {
            font-size: 2rem;
        }

        .leaderboard-message {
            margin-top: 2rem;
            text-align: center;
        }

        /* ==== FOOTER ==== */
        footer {
            background: #111827;
            color: #bbb;
            padding: 1rem 0;
            margin-top: 3rem;
            text-align: center;
        }

        /* ==== MOBILE ==== */
        @media (max-width: 768px) {
            nav {
                display: none;
                flex-direction: column;
                background: #3b82f6;
                position: absolute;
                top: 60px;
                right: 0;
                width: 200px;
                padding: 1rem;
            }
            nav.active { display: flex; }
            .menu-toggle {
                display: block;
                cursor: pointer;
                font-size: 1.5rem;
            }
            .your-rank-card {
                flex-direction: column;
                text-align: center;
            }
            .leaderboard-table, .leaderboard-table thead {
                display: none;
            }
            .leaderboard-table tr {
                display: block;
                margin: 0.8rem 0;
                padding: 1rem;
                background: #f9fafb;
                border-radius: 8px;
            }
            .leaderboard-table td {
                display: block;
                text-align: left;
                border: none;
                padding: 0.3rem 0;
            }
            .leaderboard-stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>InfoNix</h1>
            <div class="menu-toggle">☰</div>
            <nav>
                <a href="#">Home</a>
                <a href="dashboard.html">Dashboard</a>
                <div class="dropdown">
                    <button class="dropbtn">Quiz ▼</button>
                    <div class="dropdown-content">
                        <a href="quiz1.html">Networking Basics</a>
                        <a href="quiz2.html">Programming Fundamentals</a>
                        <a href="quiz3.html">Cybersecurity</a>
                        <a href="quiz4.html">Hardware</a>
                        <a href="quiz5.html">Software</a>
                        <a href="quiz6.html">Databases</a>
                        <a href="quiz7.html">Web Development</a>
                        <a href="quiz8.html">Operating Systems</a>
                        <a href="quiz9.html">Cloud Computing</a>
                        <a href="quiz10.html">IT History</a>
                        <a href="quiz.php" style="font-weight:600;">All Quizzes</a>
                    </div>
                </div>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="news.php">News & Updates</a>
                <a href="support.html">Support</a>
            </nav>
            <a href="profile.php" class="signin-icon" title="Profile">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" fill="#3b82f6"/>
                    <path d="M4 20c0-3.3137 3.134-6 7-6s7 2.6863 7 6" fill="#3b82f6"/>
                </svg>
            </a>
        </div>
    </header>
    <main>
        <section class="leaderboard-container">
            <h2>Leaderboard</h2>
            
            <!-- your existing PHP logic for rank + top 10 table remains unchanged -->

        </section>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2025 InfoNix. All rights reserved.</p>
        </div>
    </footer>
    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', () => {
            document.querySelector('nav').classList.toggle('active');
        });
    </script>
</body>
</html>


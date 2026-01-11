<?php
// dashboard.php: Restrict access to signed-in users
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

$user_id = intval($_SESSION['user_id']);
 $conn->query("SELECT COUNT(*) AS c FROM quizzes");
$totalQuizzes = $res ? intval($res->fetch_assoc()['c']) : 0;

// Completed quizzes (distinct quizzes finished by user)
$res = $conn->query("SELECT COUNT(DISTINCT quiz_id) AS c FROM quiz_attempts WHERE user_id = $user_id AND finished_at IS NOT NULL");
$completedQuizzes = $res ? intval($res->fetch_assoc()['c']) : 0;

// Completion rate
$completionRate = $totalQuizzes ? round(($completedQuizzes / $totalQuizzes) * 100) : 0;

// Best score
$res = $conn->query("SELECT MAX(score) AS best FROM quiz_attempts WHERE user_id = $user_id");
$bestScore = ($res && $res->num_rows) ? intval($res->fetch_assoc()['best']) : 0;

// In-progress quizzes (attempts without finished_at)
$res = $conn->query("SELECT COUNT(DISTINCT quiz_id) AS c FROM quiz_attempts WHERE user_id = $user_id AND finished_at IS NULL");
$inProgress = $res ? intval($res->fetch_assoc()['c']) : 0;

// Recent activity (last 6 attempts)
$recent = [];
$res = $conn->query("SELECT qa.*, q.title FROM quiz_attempts qa LEFT JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.user_id = $user_id ORDER BY qa.created_at DESC LIMIT 6");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recent[] = $row;
    }
}

// Close connection later after rendering
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - InfoNix</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="quiz-bg">
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
                    <circle cx="12" cy="8" r="4" fill="#3b82f6"/>
                    <path d="M4 20c0-3.3137 3.134-6 7-6s7 2.6863 7 6" fill="#3b82f6"/>
                </svg>
            </a>
        </div>
    </header>
    <main>
        <section class="dashboard-container">
            <h2>Welcome to Your Dashboard</h2>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3 id="totalQuizzes"><?php echo $totalQuizzes; ?></h3>
                        <p>Total Quizzes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3 id="completionRate"><?php echo $completionRate; ?>%</h3>
                        <p>Completion Rate</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-info">
                        <h3 id="bestScore"><?php echo $bestScore; ?></h3>
                        <p>Best Score</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3 id="inProgress"><?php echo $inProgress; ?></h3>
                        <p>Quizzes In Progress</p>
                    </div>
                </div>
            </div>
            <div class="dashboard-activity">
                <h3>Recent Activity</h3>
                <ul id="recentActivity">
                    <?php if(empty($recent)): ?>
                        <li>No activity yet.</li>
                    <?php else: foreach($recent as $r): ?>
                        <li>
                            <?php if($r['finished_at']): ?>
                                Completed <strong><?php echo htmlspecialchars($r['title']); ?></strong> - Score: <?php echo intval($r['score']); ?>
                            <?php else: ?>
                                Started <strong><?php echo htmlspecialchars($r['title']); ?></strong> - In Progress
                            <?php endif; ?>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
            <div class="dashboard-links">
                <a href="quiz.php" class="auth-btn">Go to Quizzes</a>
                <a href="profile.php" class="auth-btn">View Profile</a>
            </div>
        </section>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2025 InfoNix. All rights reserved.</p>
        </div>
    </footer>
    <script src="assets/dashboard.js"></script>
    <?php $conn->close(); ?>
</body>
</html>

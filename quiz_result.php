<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}
if (!isset($_GET['attempt_id'])) die('Attempt not specified');
$attempt_id = intval($_GET['attempt_id']);
$user_id = intval($_SESSION['user_id']);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('DB connect error');

$att = $conn->query("SELECT qa.*, q.title FROM quiz_attempts qa LEFT JOIN quizzes q ON qa.quiz_id=q.id WHERE qa.id=".$attempt_id);
if (!$att || $att->num_rows==0) die('Attempt not found');
$attempt = $att->fetch_assoc();

$profileQuery = $conn->query("SELECT score FROM profiles WHERE user_id=".$user_id);
$totalScore = 0;
if ($profileQuery && $profileQuery->num_rows > 0) {
    $profileData = $profileQuery->fetch_assoc();
    $totalScore = $profileData['score'];
}

$answers = [];
$res = $conn->query("SELECT aa.*, qu.question_text, qu.options, qu.correct_index FROM attempt_answers aa JOIN questions qu ON aa.question_id=qu.id WHERE aa.attempt_id=".$attempt_id);
if ($res){
    while($r = $res->fetch_assoc()){
        $r['options'] = json_decode($r['options'], true);
        $answers[] = $r;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Quiz Result</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* Page Background */
body {
    margin:0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg,#2563eb,#3b82f6);
    color: #111;
}

/* Header */
header {
    background: #1e40af;
    padding: 10px 20px;
    color: #fff;
    text-align:center;
    font-size:1.6rem;
    font-weight:bold;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

/* Navigation Bar */
nav {
    background: #1e3a8a;
    display:flex;
    justify-content:center;
    gap:30px;
    padding:12px 0;
    box-shadow:0 4px 12px rgba(0,0,0,0.3);
}
nav a {
    color:#fff;
    text-decoration:none;
    font-weight:500;
    font-size:1rem;
    transition:color 0.3s, transform 0.2s;
}
nav a:hover {
    color:#93c5fd;
    transform:scale(1.1);
}

/* Main */
main {
    max-width: 900px;
    margin: 2rem auto;
    padding: 20px;
}

/* Title */
h2 {
    text-align:center;
    color: #fff;
    font-size:2rem;
    margin-bottom:1.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

/* Score Summary */
.score-summary {
    background: #fff;
    border-left: 6px solid #1e40af;
    padding:1.2rem 1.5rem;
    border-radius:0.8rem;
    margin-bottom:2rem;
    color:#1e3a8a;
    box-shadow:0 4px 15px rgba(0,0,0,0.2);
}
.score-summary p {
    margin:0.4rem 0;
    font-size:1rem;
}
.score-summary a {
    color:#1e40af;
    font-weight:bold;
    text-decoration:underline;
}

/* Question Cards */
.question-card {
    background: #f0f9ff;
    padding:1.2rem;
    border-radius:0.8rem;
    margin-bottom:1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: transform 0.2s;
}
.question-card:hover {
    transform: scale(1.02);
}
.question-card p {
    font-weight:600;
    margin-bottom:0.5rem;
    font-size:1.05rem;
}
.option {
    padding:0.5rem 0.8rem;
    margin:0.3rem 0;
    border-radius:0.5rem;
    font-size:0.95rem;
}
.correct {
    font-weight:bold;
    color:#065f46;
    background: #d1fae5;
}
.incorrect {
    text-decoration:line-through;
    color:#b91c1c;
    background: #fee2e2;
}
.awarded {
    margin-top:0.5rem;
    font-weight:bold;
    color:#1e3a8a;
    font-size:0.95rem;
}

/* Footer */
footer {
    text-align:center;
    padding:15px;
    background:#1e40af;
    color:#fff;
    margin-top:3rem;
    box-shadow: 0 -4px 10px rgba(0,0,0,0.3);
}
</style>
</head>
<body>
<header>InfoNix</header>

<!-- Navigation Bar -->
<nav>
    <a href="index.html">Home</a>
    <a href="dashboard.html">Dashboard</a>
    <a href="quiz.php">Quizzes</a>
    <a href="enhanced_leaderboard.php">Leaderboard</a>
    <a href="news.php">News</a>
    <a href="support.html">Support</a>
</nav>

<main>
    <h2>Result for: <?php echo htmlspecialchars($attempt['title']); ?></h2>

    <div class="score-summary">
        <p>Quiz Score: <strong><?php echo intval($attempt['score']); ?></strong> (Base + Time bonus: <?php echo intval($attempt['time_bonus']); ?>)</p>
        <p>Time taken: <?php echo intval($attempt['time_taken_seconds']); ?> seconds</p>
        <p><strong>Your profile has been updated!</strong></p>
        <p>Total Leaderboard Score: <strong><?php echo number_format($totalScore); ?></strong></p>
        <p><a href="enhanced_leaderboard.php">View Your Rank on the Leaderboard</a></p>
    </div>

    <section>
        <?php foreach($answers as $i => $a): ?>
        <div class="question-card">
            <p>Q<?php echo $i+1; ?>. <?php echo htmlspecialchars($a['question_text']); ?></p>
            <?php foreach($a['options'] as $optIdx => $opt): 
                $class = '';
                if($optIdx == $a['correct_index']) $class = 'correct';
                elseif($optIdx == $a['chosen_index']) $class = 'incorrect';
            ?>
                <div class="option <?php echo $class; ?>"><?php echo htmlspecialchars($opt); ?></div>
            <?php endforeach; ?>
            <div class="awarded">Awarded Points: <?php echo intval($a['awarded_points']); ?></div>
        </div>
        <?php endforeach; ?>
    </section>
</main>

<footer>&copy; 2025 InfoNix</footer>
</body>
</html>

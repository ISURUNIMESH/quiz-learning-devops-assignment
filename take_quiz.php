<?php
// take_quiz.php - Render quiz questions one by one with client-side timer
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}
if (!isset($_GET['quiz_id'])) {
    die('Quiz not specified');
}
$quiz_id = intval($_GET['quiz_id']);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch quiz
$res = $conn->query("SELECT id,title,description,time_limit_seconds,points_per_question,max_time_bonus FROM quizzes WHERE id = " . $quiz_id);
if (!$res) {
    die('Database error: ' . $conn->error);
}
$quiz = $res->fetch_assoc();
if (!$quiz) {
    die('Quiz not found');
}

// Fetch questions
$qres = $conn->query("SELECT id,question_text,options,correct_index,points FROM questions WHERE quiz_id = " . $quiz_id . " ORDER BY id ASC LIMIT 15");
if (!$qres) {
    die('Database error: ' . $conn->error);
}
$questions = [];
while ($row = $qres->fetch_assoc()) {
    $decoded = json_decode($row['options'], true);
    $row['options'] = is_array($decoded) ? $decoded : [];
    $questions[] = $row;
}
$hasAnyOptions = false;
foreach($questions as $qq){
    if (!empty($qq['options'])) { $hasAnyOptions = true; break; }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($quiz['title']); ?> - Take Quiz</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.question-card{background:#fff;padding:1rem;border-radius:0.75rem;box-shadow:0 8px 24px rgba(0,0,0,0.06);margin-bottom:1rem}
.option-btn{display:block;width:100%;margin:0.4rem 0;padding:0.6rem;border-radius:0.6rem;border:1px solid #e5e7eb;background:#fafafa;text-align:left}
.auth-btn{padding:0.5rem 1rem;border:none;border-radius:0.5rem;color:#fff;background:#2563eb;cursor:pointer}
</style>
</head>
<body class="quiz-bg">
<header>
<div class="container"><h1>InfoNix</h1></div>
</header>
<main>
<section style="max-width:900px;margin:2rem auto;padding:1rem;">
<h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
<p><?php echo htmlspecialchars($quiz['description']); ?></p>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <div>Time limit: <strong><?php echo intval($quiz['time_limit_seconds']); ?>s</strong></div>
    <div>Points/q: <strong><?php echo intval($quiz['points_per_question']); ?></strong></div>
    <div>Max time bonus: <strong><?php echo intval($quiz['max_time_bonus']); ?></strong></div>
</div>

<form id="quizForm" method="post" action="submit_quiz.php">
    <input type="hidden" name="quiz_id" value="<?php echo intval($quiz['id']); ?>">
    <input type="hidden" name="started_at" id="started_at" value="">
    <input type="hidden" name="finished_at" id="finished_at" value="">
    <input type="hidden" name="time_taken_seconds" id="time_taken_seconds" value="">

    <?php if (empty($questions)): ?>
        <div class="question-card">No questions are available for this quiz yet.</div>
    <?php else: ?>
        <?php foreach($questions as $i => $q): ?>
            <div class="question-card question" id="q<?php echo $i; ?>" style="display:<?php echo $i==0?'block':'none'; ?>">
                <p style="font-weight:600;">Q<?php echo $i+1; ?>. <?php echo htmlspecialchars($q['question_text']); ?></p>
                <?php if (empty($q['options'])): ?>
                    <div style="color:#9ca3af;">No options available for this question.</div>
                <?php else: ?>
                    <?php foreach($q['options'] as $optIdx => $opt): ?>
                        <label class="option-btn">
                            <input type="radio" name="answer_<?php echo intval($q['id']); ?>" value="<?php echo intval($optIdx); ?>"> 
                            <?php echo htmlspecialchars($opt); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div style="margin-top:1rem; display:flex; justify-content:space-between;">
                    <?php if ($i > 0): ?>
                        <button type="button" class="auth-btn" onclick="showQuestion(<?php echo $i-1; ?>)">Previous</button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    <?php if ($i < count($questions)-1): ?>
                        <button type="button" class="auth-btn" onclick="showQuestion(<?php echo $i+1; ?>)">Next</button>
                    <?php else: ?>
                        <button type="button" class="auth-btn" style="background:#16a34a;" onclick="submitQuiz()">Submit Quiz</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <div style="margin-top:1rem; font-weight:700;color:#ef4444;">Time: <span id="timer">00:00</span></div>
</form>
</section>
</main>
<footer>
<div class="container"><p>&copy; 2025 InfoNix</p></div>
</footer>

<script>
const timeLimit = <?php echo intval($quiz['time_limit_seconds']); ?>;
let seconds = 0;
let timerInterval;
const questions = document.querySelectorAll('.question');
let current = 0;

function pad(n){ return n<10?'0'+n:n; }

function startTimer(){
    document.getElementById('started_at').value = new Date().toISOString();
    timerInterval = setInterval(()=>{
        seconds++;
        let mm = Math.floor(seconds/60);
        let ss = seconds%60;
        document.getElementById('timer').textContent = pad(mm)+':'+pad(ss);
        if(timeLimit>0 && seconds>=timeLimit){
            submitQuiz();
        }
    },1000);
}

function showQuestion(index){
    questions[current].style.display = 'none';
    questions[index].style.display = 'block';
    current = index;
}

function submitQuiz(){
    clearInterval(timerInterval);
    document.getElementById('finished_at').value = new Date().toISOString();
    document.getElementById('time_taken_seconds').value = seconds;
    document.getElementById('quizForm').submit();
}

window.addEventListener('load', ()=>{
    startTimer();
});
</script>
</body>
</html>

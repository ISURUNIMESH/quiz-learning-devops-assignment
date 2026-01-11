<?php
// submit_quiz.php - Score quiz, compute time bonus, save attempt and answers
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = intval($_SESSION['user_id']);
$quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
$time_taken = isset($_POST['time_taken_seconds']) ? intval($_POST['time_taken_seconds']) : null;

// load quiz params
$qres = $conn->query("SELECT time_limit_seconds,points_per_question,max_time_bonus FROM quizzes WHERE id=". $quiz_id);
if (!$qres || $qres->num_rows==0) die('Quiz not found');
$quiz = $qres->fetch_assoc();
$time_limit = intval($quiz['time_limit_seconds']);
$points_per_q = intval($quiz['points_per_question']);
$max_time_bonus = intval($quiz['max_time_bonus']);

// fetch questions for quiz
$questions = [];
$res = $conn->query("SELECT id,correct_index,points FROM questions WHERE quiz_id=".$quiz_id." ORDER BY id ASC LIMIT 1000");
if ($res){
    while($r = $res->fetch_assoc()){
        $questions[$r['id']] = $r;
    }
}

$base_score = 0;
$answers_saved = 0;
foreach($_POST as $k => $v){
    if (strpos($k,'answer_') === 0){
        $qid = intval(substr($k,7));
        $chosen = is_numeric($v)? intval($v) : null;
        if (!isset($questions[$qid])) continue; // question not found
        $correct_index = intval($questions[$qid]['correct_index']);
        $qpoints = intval($questions[$qid]['points'])>0?intval($questions[$qid]['points']):$points_per_q;
        $is_correct = ($chosen !== null && $chosen === $correct_index) ? 1 : 0;
        $awarded = $is_correct ? $qpoints : 0;
        $base_score += $awarded;
        // save answer later after creating attempt id
        $answers_saved++;
        $answers_data[] = [ 'question_id'=>$qid, 'chosen_index'=>$chosen, 'is_correct'=>$is_correct, 'awarded_points'=>$awarded ];
    }
}

// compute time bonus
$time_bonus = 0;
if ($time_limit > 0 && $time_taken !== null){
    $ratio = ($time_limit - $time_taken) / $time_limit;
    if ($ratio < 0) $ratio = 0;
    $time_bonus = floor($ratio * $max_time_bonus);
}

$final_score = $base_score + $time_bonus;

// Save attempt
$stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id,quiz_id,started_at,finished_at,time_taken_seconds,score,time_bonus) VALUES (?,?,?,?,?,?,?)");
$started_at = isset($_POST['started_at']) ? $_POST['started_at'] : null;
$finished_at = isset($_POST['finished_at']) ? $_POST['finished_at'] : null;
$stmt->bind_param('iissiii', $user_id, $quiz_id, $started_at, $finished_at, $time_taken, $final_score, $time_bonus);
$stmt->execute();
$attempt_id = $stmt->insert_id;

// Save answers
if (!empty($answers_data) && $attempt_id){
    $ins = $conn->prepare("INSERT INTO attempt_answers (attempt_id,question_id,chosen_index,is_correct,awarded_points) VALUES (?,?,?,?,?)");
    foreach($answers_data as $ad){
        $ins->bind_param('iiiis', $attempt_id, $ad['question_id'], $ad['chosen_index'], $ad['is_correct'], $ad['awarded_points']);
        // note: type mismatch for awarded_points as string if large; keep as int
        $ins->execute();
    }
}

// Update the user's profile score
// This query gets the sum of the best scores for each quiz the user has taken
$updateProfileQuery = "
    UPDATE profiles p 
    LEFT JOIN (
        SELECT user_id, SUM(best_score) AS total_score 
        FROM (
            SELECT user_id, quiz_id, MAX(score) AS best_score 
            FROM quiz_attempts 
            WHERE user_id = ? 
            GROUP BY user_id, quiz_id
        ) x 
        GROUP BY user_id
    ) agg ON p.user_id = agg.user_id
    SET p.score = COALESCE(agg.total_score, 0)
    WHERE p.user_id = ?
";

$updateStmt = $conn->prepare($updateProfileQuery);
$updateStmt->bind_param("ii", $user_id, $user_id);
$updateStmt->execute();

// Create profile if it doesn't exist
if ($updateStmt->affected_rows == 0) {
    // Check if profile exists
    $profileCheck = $conn->prepare("SELECT 1 FROM profiles WHERE user_id = ? LIMIT 1");
    $profileCheck->bind_param("i", $user_id);
    $profileCheck->execute();
    $profileResult = $profileCheck->get_result();
    
    if ($profileResult->num_rows == 0) {
        // Get user's email to create default name
        $userQuery = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $userQuery->bind_param("i", $user_id);
        $userQuery->execute();
        $userResult = $userQuery->get_result();
        
        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $defaultName = explode('@', $userRow['email'])[0]; // Use part before @ as default name
            
            // Insert new profile
            $insertProfile = $conn->prepare("INSERT INTO profiles (user_id, name, score) VALUES (?, ?, ?)");
            $insertProfile->bind_param("isi", $user_id, $defaultName, $final_score);
            $insertProfile->execute();
        }
    }
}

$conn->close();

// Redirect to a result page or show inline
header('Location: quiz_result.php?attempt_id=' . $attempt_id);
exit();
?>

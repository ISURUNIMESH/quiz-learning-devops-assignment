<?php
// update_profile.php: Handles profile updates and image upload
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
$age = isset($_POST['age']) ? intval($_POST['age']) : null;
$bio = isset($_POST['bio']) ? $conn->real_escape_string($_POST['bio']) : '';
$profile_pic = null;
// New fields
$gender = isset($_POST['gender']) ? $conn->real_escape_string($_POST['gender']) : '';
$profession = isset($_POST['profession']) ? $conn->real_escape_string($_POST['profession']) : '';
$institution = isset($_POST['institution']) ? $conn->real_escape_string($_POST['institution']) : '';

// Handle profile pic upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_name = basename($_FILES['profile_pic']['name']);
    $target_file = $target_dir . uniqid() . "_" . $file_name;
    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
        $profile_pic = $conn->real_escape_string($target_file);
    }
}

// Check if profile exists and get current score if any
$check = $conn->query("SELECT id, score FROM profiles WHERE user_id = $user_id");
$current_score = 0;
if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $current_score = isset($row['score']) ? (int)$row['score'] : 0;
    
    // Update profile (preserving the score)
    $sql = "UPDATE profiles SET name='$name', age=$age, bio='$bio', gender='$gender', profession='$profession', institution='$institution'" . ($profile_pic ? ", profile_pic='$profile_pic'" : "") . " WHERE user_id=$user_id";
    $conn->query($sql);
} else {
    // Check if score column exists
    $result = $conn->query("SHOW COLUMNS FROM profiles LIKE 'score'");
    $scoreColumnExists = $result->num_rows > 0;
    
    if (!$scoreColumnExists) {
        // Add score column if it doesn't exist
        $conn->query("ALTER TABLE profiles ADD COLUMN score INT DEFAULT 0");
    }
    
    // Compute user's score from quiz_attempts
    $scoreSql = "SELECT COALESCE(SUM(best_score),0) AS total_score FROM (
        SELECT user_id, quiz_id, MAX(score) AS best_score FROM quiz_attempts 
        WHERE user_id = $user_id GROUP BY user_id, quiz_id
    ) x";
    
    $score_result = $conn->query($scoreSql);
    if ($score_result && $score_result->num_rows > 0) {
        $sr = $score_result->fetch_assoc();
        $current_score = (int)$sr['total_score'];
    }
    
    // Insert profile with score
    $sql = "INSERT INTO profiles (user_id, name, age, bio, gender, profession, institution, profile_pic, score) 
            VALUES ($user_id, '$name', $age, '$bio', '$gender', '$profession', '$institution', '$profile_pic', $current_score)";
    $conn->query($sql);
}
$conn->close();
header('Location: profile.php');
exit();
?>

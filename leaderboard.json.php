<?php
// leaderboard.json.php - returns JSON top-10 and current user summary based on quiz_attempts
session_start();
header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$servername = "sql12.freesqldatabase.com";
$username = "sql12814273";
$password = "aw2rwFjSiF";
$dbname = "sql12814273";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

// We now assume scores are already stored in profiles table
// and are updated separately by update_profiles_scores.php

// Top 10 scores from profiles table
$topSql = "SELECT p.user_id AS id, COALESCE(p.name,'User') AS name, COALESCE(p.profile_pic,'') AS profile_pic, 
           COALESCE(p.score,0) AS score
    FROM profiles p
    ORDER BY p.score DESC, p.user_id ASC
    LIMIT 10";

$top = [];
$res = $conn->query($topSql);
if ($res) {
    $rank = 1;
    while ($r = $res->fetch_assoc()) {
        $avatar = $r['profile_pic'] ? $r['profile_pic'] : 'https://randomuser.me/api/portraits/men/' . (10+$rank) . '.jpg';
        $top[] = [
            'rank' => $rank,
            'user_id' => intval($r['id']),
            'username' => $r['name'],
            'score' => (int)$r['score'],
            'avatar' => $avatar
        ];
        $rank++;
    }
}

// current user summary: total_score and rank
$userSummary = ['user_id' => $user_id, 'username' => 'You', 'score' => 0, 'rank' => null, 'avatar' => ''];
if ($user_id) {
    // Get user profile with score
    $p = $conn->query("SELECT name, profile_pic, COALESCE(score, 0) AS score FROM profiles WHERE user_id = " . $user_id);
    if ($p && $p->num_rows > 0) {
        $prow = $p->fetch_assoc();
        $userSummary['username'] = $prow['name'] ? $prow['name'] : $userSummary['username'];
        $userSummary['avatar'] = $prow['profile_pic'] ? $prow['profile_pic'] : '';
        $userSummary['score'] = (int)$prow['score'];
    }

    // compute rank by counting users with higher score
    $rankCountSql = "SELECT COALESCE(COUNT(*),0)+1 AS rank FROM profiles WHERE score > " . $userSummary['score'];
    $rc = $conn->query($rankCountSql);
    if ($rc && $rc->num_rows > 0) {
        $rrow = $rc->fetch_assoc();
        $userSummary['rank'] = intval($rrow['rank']);
    }
}

$conn->close();

echo json_encode(['top' => $top, 'you' => $userSummary]);
exit();
?>

<?php
// Script to update profile scores from quiz_attempts
$servername = "sql12.freesqldatabase.com";
$username = "sql12814273";
$password = "aw2rwFjSiF";
$dbname = "sql12814273";

require_once 'db_connect.php';

// Check if score column exists in profiles table
$result = $conn->query("SHOW COLUMNS FROM profiles LIKE 'score'");
$scoreColumnExists = $result->num_rows > 0;

if (!$scoreColumnExists) {
    // Add score column if it doesn't exist
    $alterSql = "ALTER TABLE profiles ADD COLUMN score INT DEFAULT 0";
    if ($conn->query($alterSql) === TRUE) {
        echo "Score column added successfully to profiles table<br>";
    } else {
        echo "Error adding score column: " . $conn->error . "<br>";
        exit;
    }
}

// Calculate aggregated scores (sum of best score per quiz) for each user
$updateScoresSql = "
    UPDATE profiles p 
    LEFT JOIN (
        SELECT user_id, COALESCE(SUM(best_score),0) AS total_score 
        FROM (
            SELECT user_id, quiz_id, MAX(score) AS best_score 
            FROM quiz_attempts 
            GROUP BY user_id, quiz_id
        ) x 
        GROUP BY user_id
    ) agg ON p.user_id = agg.user_id
    SET p.score = COALESCE(agg.total_score, 0)
";

// Execute the update query
if ($conn->query($updateScoresSql) === TRUE) {
    echo "Updated scores for " . $conn->affected_rows . " profiles<br>";
} else {
    echo "Error updating scores: " . $conn->error . "<br>";
}

// Show top 10 profiles by score
$topProfilesSql = "SELECT p.user_id, p.name, p.score, p.profile_pic FROM profiles p ORDER BY p.score DESC LIMIT 10";
$result = $conn->query($topProfilesSql);

echo "<br><h3>Top 10 Profiles by Score</h3>";
echo "<table border='1'><tr><th>Rank</th><th>User ID</th><th>Name</th><th>Score</th><th>Profile Pic</th></tr>";
$rank = 1;
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $rank . "</td>";
    echo "<td>" . $row["user_id"] . "</td>";
    echo "<td>" . $row["name"] . "</td>";
    echo "<td>" . $row["score"] . "</td>";
    echo "<td>" . $row["profile_pic"] . "</td>";
    echo "</tr>";
    $rank++;
}
echo "</table>";

$conn->close();
?>

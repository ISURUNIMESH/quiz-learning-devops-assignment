<?php
// view_leaderboard_scores.php - A diagnostic page to view all scores in the system
// This page shows scores from both profiles table and calculated from quiz_attempts

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>InfoNix Leaderboard Scores</h1>";

// Get scores from profiles table
echo "<h2>Scores from Profiles Table</h2>";
$profilesSql = "SELECT p.user_id, p.name, COALESCE(p.score, 0) AS score
               FROM profiles p
               ORDER BY p.score DESC";

$profilesResult = $conn->query($profilesSql);
if ($profilesResult && $profilesResult->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Rank</th><th>User ID</th><th>Name</th><th>Score (from profiles)</th></tr>";
    
    $rank = 1;
    while ($row = $profilesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $rank . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['score'] . "</td>";
        echo "</tr>";
        $rank++;
    }
    
    echo "</table>";
} else {
    echo "<p>No profile scores found.</p>";
}

// Get calculated scores from quiz_attempts for comparison
echo "<h2>Calculated Scores from quiz_attempts</h2>";
$calculatedSql = "SELECT u.id AS user_id, 
                  COALESCE(p.name, CONCAT('User ', u.id)) AS name,
                  COALESCE(agg.total_score, 0) AS calculated_score
                FROM users u
                LEFT JOIN profiles p ON u.id = p.user_id
                LEFT JOIN (
                    SELECT user_id, SUM(best_score) AS total_score 
                    FROM (
                        SELECT user_id, quiz_id, MAX(score) AS best_score 
                        FROM quiz_attempts 
                        GROUP BY user_id, quiz_id
                    ) x 
                    GROUP BY user_id
                ) agg ON u.id = agg.user_id
                WHERE agg.total_score > 0
                ORDER BY calculated_score DESC";

$calculatedResult = $conn->query($calculatedSql);
if ($calculatedResult && $calculatedResult->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Rank</th><th>User ID</th><th>Name</th><th>Calculated Score</th></tr>";
    
    $rank = 1;
    while ($row = $calculatedResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $rank . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['calculated_score'] . "</td>";
        echo "</tr>";
        $rank++;
    }
    
    echo "</table>";
} else {
    echo "<p>No calculated scores found.</p>";
}

$conn->close();
echo "<p><a href='leaderboard.php'>Back to Leaderboard</a> | <a href='update_profiles_scores.php'>Update Scores</a></p>";
?>

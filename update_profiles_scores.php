<?php
require_once 'db_connect.php';
// update_profiles_scores.php - A maintenance script to update profile scores from quiz_attempts
// This should be run periodically or after new quiz attempts are added

// Connected via db_connect.php

echo "<h1>Profile Scores Update Utility</h1>";

// Check if score column exists in profiles table
$result = $conn->query("SHOW COLUMNS FROM profiles LIKE 'score'");
if ($result->num_rows == 0) {
    echo "Adding score column to profiles table...<br>";
    $conn->query("ALTER TABLE profiles ADD COLUMN score INT DEFAULT 0");
    echo "Score column added successfully.<br>";
}

// Update all profile scores with the sum of best scores per quiz from quiz_attempts
echo "Updating profile scores from quiz attempts data...<br>";
$updateQuery = "
    UPDATE profiles p 
    LEFT JOIN (
        SELECT user_id, SUM(best_score) AS total_score 
        FROM (
            SELECT user_id, quiz_id, MAX(score) AS best_score 
            FROM quiz_attempts 
            GROUP BY user_id, quiz_id
        ) x 
        GROUP BY user_id
    ) agg ON p.user_id = agg.user_id
    SET p.score = COALESCE(agg.total_score, 0)
";

if ($conn->query($updateQuery)) {
    echo "Updated scores for " . $conn->affected_rows . " profiles.<br>";
} else {
    echo "Error updating scores: " . $conn->error . "<br>";
}

// Create profiles for users with quiz attempts but no profile
echo "Creating profiles for users with quiz attempts but no profile entry...<br>";
$insertQuery = "
    INSERT IGNORE INTO profiles (user_id, name, score)
    SELECT qa.user_id, SUBSTRING_INDEX(u.email, '@', 1), 
           COALESCE((SELECT SUM(best_score) FROM (
               SELECT user_id, quiz_id, MAX(score) AS best_score 
               FROM quiz_attempts 
               WHERE user_id = qa.user_id
               GROUP BY user_id, quiz_id
           ) x), 0) AS score
    FROM quiz_attempts qa
    JOIN users u ON qa.user_id = u.id
    LEFT JOIN profiles p ON qa.user_id = p.user_id
    WHERE p.id IS NULL
    GROUP BY qa.user_id
";

if ($conn->query($insertQuery)) {
    echo "Created " . $conn->affected_rows . " new profiles for users with quiz attempts.<br>";
} else {
    echo "Error creating profiles: " . $conn->error . "<br>";
}

// Show top 10 profiles by score
echo "<h2>Current Top 10 Profiles by Score</h2>";
$topQuery = "SELECT p.user_id, p.name, COALESCE(p.score, 0) AS score FROM profiles p ORDER BY p.score DESC LIMIT 10";
$result = $conn->query($topQuery);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Rank</th><th>User ID</th><th>Name</th><th>Score</th></tr>";
    
    $rank = 1;
    while ($row = $result->fetch_assoc()) {
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
    echo "No profiles found.<br>";
}

$conn->close();
echo "<p>Profile scores update completed!</p>";
echo "<p><a href='leaderboard.php'>Go to Leaderboard</a></p>";
?>

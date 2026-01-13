<?php
// update_all_profile_scores.php: Script to update all profile scores from quiz_attempts
// This can be run manually or scheduled to ensure profile scores are updated

session_start();
// Allow this script to run for longer tasks
set_time_limit(300);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Profile Score Update Tool</h1>";

// Check if score column exists in profiles table
$result = $conn->query("SHOW COLUMNS FROM profiles LIKE 'score'");
if ($result->num_rows == 0) {
    echo "Adding score column to profiles table...<br>";
    $conn->query("ALTER TABLE profiles ADD COLUMN score INT DEFAULT 0");
    echo "Score column added.<br>";
}

echo "Updating all profile scores from quiz_attempts data...<br>";

// Update all profile scores with aggregated quiz_attempts data
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

if ($conn->query($updateScoresSql) === TRUE) {
    echo "Updated " . $conn->affected_rows . " profile records.<br>";
} else {
    echo "Error updating profile scores: " . $conn->error . "<br>";
}

// Now check if there are any users with quiz_attempts but no profile
echo "Checking for users with quiz attempts but no profile...<br>";

$missingProfilesSql = "
    SELECT DISTINCT qa.user_id, u.email
    FROM quiz_attempts qa
    JOIN users u ON qa.user_id = u.id
    LEFT JOIN profiles p ON qa.user_id = p.user_id
    WHERE p.id IS NULL
";

$missingResult = $conn->query($missingProfilesSql);
if ($missingResult && $missingResult->num_rows > 0) {
    echo "Found " . $missingResult->num_rows . " users with quiz attempts but no profile.<br>";
    echo "Creating placeholder profiles for these users...<br>";
    
    while ($row = $missingResult->fetch_assoc()) {
        $userId = (int)$row['user_id'];
        $email = $row['email'];
        $username = explode('@', $email)[0]; // Use part before @ as name
        
        // Get user's score from quiz_attempts
        $scoreSql = "
            SELECT COALESCE(SUM(best_score),0) AS total_score 
            FROM (
                SELECT user_id, quiz_id, MAX(score) AS best_score 
                FROM quiz_attempts 
                WHERE user_id = $userId
                GROUP BY user_id, quiz_id
            ) x
        ";
        
        $scoreResult = $conn->query($scoreSql);
        $score = 0;
        if ($scoreResult && $scoreResult->num_rows > 0) {
            $scoreRow = $scoreResult->fetch_assoc();
            $score = (int)$scoreRow['total_score'];
        }
        
        // Create placeholder profile
        $insertSql = "INSERT INTO profiles (user_id, name, score) VALUES ($userId, '$username', $score)";
        if ($conn->query($insertSql) === TRUE) {
            echo "Created profile for user $userId with score $score<br>";
        } else {
            echo "Error creating profile for user $userId: " . $conn->error . "<br>";
        }
    }
} else {
    echo "All users with quiz attempts have profiles.<br>";
}

// Show top 10 scores for verification
echo "<h2>Current Top 10 Scores</h2>";
$topScoresSql = "SELECT p.user_id, p.name, p.score FROM profiles p ORDER BY p.score DESC LIMIT 10";
$topResult = $conn->query($topScoresSql);

if ($topResult && $topResult->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Rank</th><th>User ID</th><th>Name</th><th>Score</th></tr>";
    
    $rank = 1;
    while ($row = $topResult->fetch_assoc()) {
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
    echo "No profile scores found.<br>";
}

$conn->close();
echo "<p>Score update completed successfully!</p>";
?>

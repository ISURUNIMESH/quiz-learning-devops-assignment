<?php
// Script to check if profiles table has a score column and add if missing
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if score column exists in profiles table
$result = $conn->query("SHOW COLUMNS FROM profiles LIKE 'score'");
$scoreColumnExists = $result->num_rows > 0;

echo "Score column exists in profiles table: " . ($scoreColumnExists ? "Yes" : "No") . "<br>";

// Add score column if it doesn't exist
if (!$scoreColumnExists) {
    $alterSql = "ALTER TABLE profiles ADD COLUMN score INT DEFAULT 0";
    if ($conn->query($alterSql) === TRUE) {
        echo "Score column added successfully to profiles table<br>";
    } else {
        echo "Error adding score column: " . $conn->error . "<br>";
    }
}

// Show the profiles table structure
$columnsResult = $conn->query("SHOW COLUMNS FROM profiles");
echo "<br>Current profiles table structure:<br>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($row = $columnsResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row["Field"] . "</td>";
    echo "<td>" . $row["Type"] . "</td>";
    echo "<td>" . $row["Null"] . "</td>";
    echo "<td>" . $row["Key"] . "</td>";
    echo "<td>" . $row["Default"] . "</td>";
    echo "<td>" . $row["Extra"] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>

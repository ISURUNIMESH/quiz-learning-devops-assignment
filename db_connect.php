<?php
$servername = "sql12.freesqldatabase.com";
$username = "sql12814273";
$password = "aw2rwFjSiF";
$dbname = "sql12814273";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

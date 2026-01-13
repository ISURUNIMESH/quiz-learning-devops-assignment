<?php
// signin.php: Handles user login and session
session_start();

require_once 'db_connect.php';

// Get form data
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email && $password) {
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    if ($result === false) {
        echo "<h2>Database error: " . htmlspecialchars($conn->error) . "</h2>";
    } elseif ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Login success
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            // store role if available (default to 'user')
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.html");
            }
            exit();
        } else {
            echo "<h2>Invalid password.</h2>";
        }
    } else {
        echo "<h2>No user found with that email.</h2>";
    }
} else {
    echo "<h2>All fields are required.</h2>";
}

$conn->close();
?>

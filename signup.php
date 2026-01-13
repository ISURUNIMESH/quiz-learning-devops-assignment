<?php
// signup.php: Handles user registration and inserts into MySQL

require_once 'db_connect.php';

// Initialize message
$message = "";
$messageType = "";
$redirect = false;

// Get form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
  $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $role = isset($_POST['role']) ? $conn->real_escape_string($_POST['role']) : 'user';

    if ($name && $email && $password) {

        // ✅ Password validation
        $passwordPattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/";
        if (!preg_match($passwordPattern, $password)) {
            $message = "Password must be at least 6 characters long and include uppercase, lowercase, number, and special character.";
            $messageType = "error";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Check if email already exists
            $check_sql = "SELECT id FROM users WHERE email = '$email'";
            $check_result = $conn->query($check_sql);
      if ($check_result && $check_result->num_rows > 0) {
        $message = "Email already registered.";
        $messageType = "error";
      } else {
        // Insert user with role
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";
        if ($conn->query($sql) === TRUE) {
          $message = "✅ Registration successful! Redirecting to Sign In...";
          $messageType = "success";
          $redirect = true; // trigger redirect
        } else {
          $message = "Registration failed: " . htmlspecialchars($conn->error);
          $messageType = "error";
        }
      }
        }
    } else {
        $message = "All fields are required.";
        $messageType = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      width: 350px;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    input {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #007bff;
      border: none;
      color: #fff;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .message {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 6px;
      text-align: center;
      font-weight: bold;
    }
    .error {
      background: #f8d7da;
      border: 1px solid #f5c2c7;
      color: #842029;
    }
    .success {
      background: #d1e7dd;
      border: 1px solid #badbcc;
      color: #0f5132;
    }
  </style>
  <?php if ($redirect): ?>
    <!-- Auto redirect after 2 seconds -->
    <meta http-equiv="refresh" content="2;url=signin.html">
  <?php endif; ?>
</head>
<body>
  <div class="container">
    <h2>Create Account</h2>

    <?php if (!empty($message)): ?>
      <div class="message <?= $messageType; ?>"><?= $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
  <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
    </form>
  </div>
</body>
</html>

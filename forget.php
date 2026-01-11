<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "quizhub";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $result = $conn->query("SELECT password FROM users WHERE id = $user_id");
    if ($result && $row = $result->fetch_assoc()) {
        if (password_verify($current_password, $row['password'])) {
            if ($new_password === $confirm_password) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$hashed' WHERE id=$user_id");
                $message = "✅ Password updated successfully!";
            } else {
                $message = "❌ New passwords do not match!";
            }
        } else {
            $message = "❌ Current password is incorrect!";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password - InfoNix</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(to right,#6366f1,#3b82f6); margin:0; padding:0; }
    .container { display:flex; justify-content:center; align-items:center; height:100vh; }
    .card { background:#fff; padding:2rem; border-radius:1.2rem; box-shadow:0 8px 24px rgba(0,0,0,0.1); width:100%; max-width:400px; text-align:center; }
    h2 { margin-bottom:1.5rem; color:#1f2937; }
    input { width:100%; padding:0.8rem; margin-bottom:1rem; border:1px solid #e5e7eb; border-radius:0.8rem; }
    button { background:#3b82f6; color:#fff; border:none; padding:0.8rem 2rem; border-radius:0.8rem; cursor:pointer; font-size:1rem; }
    button:hover { background:#2563eb; }
    .message { margin-top:1rem; font-weight:600; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2>🔐 Change Password</h2>
      <form method="post" action="">
        <input type="password" name="current_password" placeholder="Current Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Update Password</button>
      </form>
      <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>
      <p style="margin-top:1.5rem;"><a href="profile.php" style="color:#3b82f6;text-decoration:none;">⬅ Back to Profile</a></p>
    </div>
  </div>
</body>
</html>

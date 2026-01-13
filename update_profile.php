<?php
// update_profile.php - SAFE PHP-only version (NO profiles table)

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}

// Disable warnings on production
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once 'db_connect.php';

$user_id = (int) $_SESSION['user_id'];

// ---------------------------
// Read inputs safely
// ---------------------------
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Ignore unsupported fields safely
// age, bio, gender, profession, institution
// (DB has no place to store them)

// ---------------------------
// Handle profile picture upload (FILE ONLY, no DB)
// ---------------------------
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
    $safeName = 'user_' . $user_id . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $safeName;

    move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath);
    // Image saved, but NOT stored in DB (no column exists)
}

// ---------------------------
// Update user name ONLY
// ---------------------------
if ($name !== '') {
    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $user_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Redirect back to profile
header('Location: profile.php');
exit();
?>

<?php
// profile.php: Restrict access to signed-in users
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
$clearMsg = "";

// Handle clear profile request - deletes the current user's profile row
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_profile'])) {
    $stmt = $conn->prepare("DELETE FROM profiles WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $clearMsg = "Profile cleared successfully.";
        } else {
            $clearMsg = "Error clearing profile: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $clearMsg = "Error preparing delete statement: " . htmlspecialchars($conn->error);
    }
    // reset local profile variables so UI shows cleared state
    $profile = ["name"=>"","age"=>"","bio"=>"","profile_pic"=>""];
    $gender = $profession = $institution = '';
}

$profile = ["name"=>"","age"=>"","bio"=>"","profile_pic"=>""];
$result = $conn->query("SELECT * FROM profiles WHERE user_id = $user_id");
if ($result && $result->num_rows > 0) {
    $profile = $result->fetch_assoc();
}
$gender = isset($profile['gender']) ? $profile['gender'] : '';
$profession = isset($profile['profession']) ? $profile['profession'] : '';
$institution = isset($profile['institution']) ? $profile['institution'] : '';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - InfoNix</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Background */
        body.quiz-bg {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #2563eb, #3b82f6, #60a5fa);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
        }
        header h1 {
            color: #fff;
            font-size: 1.5rem;
        }
        header nav a {
            color: #f0f0f0;
            margin: 0 0.8rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        header nav a:hover {
            color: #ffd166;
        }

        /* Profile Card */
        .profile-card {
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 2.5rem 2rem;
            max-width: 430px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);} 
            to {opacity: 1; transform: translateY(0);} 
        }

        /* Avatar */
        .profile-card img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #3b82f6;
            margin-bottom: 1rem;
        }

        /* Inputs */
        .profile-card input[type="text"],
        .profile-card input[type="number"],
        .profile-card textarea,
        .profile-card select {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            border-radius: 0.8rem;
            border: 1px solid #d1d5db;
            font-size: 1rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .profile-card input:focus,
        .profile-card textarea:focus,
        .profile-card select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
            outline: none;
        }

        /* Buttons */
        .auth-btn {
            display: inline-block;
            width: 100%;
            padding: 0.9rem;
            font-size: 1rem;
            border: none;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
        }
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(0,0,0,0.1);
        }
        .auth-btn.save {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
        }
        .auth-btn.logout {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            margin-top: 1rem;
        }

        /* Footer */
        footer {
            margin-top: auto;
            text-align: center;
            padding: 1rem;
            color: #fff;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="quiz-bg">
    <header>
        <div class="container" style="display:flex;justify-content:space-between;align-items:center;max-width:1100px;margin:0 auto;">
            <h1>InfoNix</h1>
            <nav>
                <a href="index.html">Home</a>
                <a href="dashboard.html">Dashboard</a>
                <a href="quiz.php">Quizzes</a>
                <a href="enhanced_leaderboard.php">Leaderboard</a>
                <a href="news.php">News</a>
                <a href="support.html">Support</a>
            </nav>
        </div>
    </header>

    <main style="display:flex;justify-content:center;align-items:center;flex:1;">
        <section class="profile-container">
            <div class="profile-card">
                <img src="<?php echo $profile['profile_pic'] ? htmlspecialchars($profile['profile_pic']) : 'https://randomuser.me/api/portraits/men/32.jpg'; ?>" alt="Avatar">
                
                <form action="update_profile.php" method="post" enctype="multipart/form-data">
                    <input aria-label="Upload profile picture" type="file" name="profile_pic" accept="image/*" style="margin:0.7rem 0;">
                    <input type="text" name="name" placeholder="Your Name" value="<?php echo htmlspecialchars($profile['name']); ?>">
                    <input type="number" name="age" placeholder="Age" value="<?php echo htmlspecialchars($profile['age']); ?>">
                    <textarea name="bio" placeholder="Short Bio"><?php echo htmlspecialchars($profile['bio']); ?></textarea>

                    <div style="text-align:left;margin-bottom:0.7rem;">
                        <label style="font-weight:600;">Gender:</label><br>
                        <label><input type="radio" name="gender" value="Male" <?php if($gender=="Male") echo "checked"; ?>> Male</label>
                        <label><input type="radio" name="gender" value="Female" <?php if($gender=="Female") echo "checked"; ?>> Female</label>
                        <label><input type="radio" name="gender" value="Other" <?php if($gender=="Other") echo "checked"; ?>> Other</label>
                    </div>

                    <div style="text-align:left;margin-bottom:0.7rem;">
                        <label style="font-weight:600;">Profession:</label><br>
                        <select name="profession" id="profession-select">
                            <option value="">Select Profession</option>
                            <option value="Student" <?php if($profession=="Student") echo "selected"; ?>>Student</option>
                            <option value="Software Developer" <?php if($profession=="Software Developer") echo "selected"; ?>>Software Developer</option>
                            <option value="Web Developer" <?php if($profession=="Web Developer") echo "selected"; ?>>Web Developer</option>
                            <option value="Network Engineer" <?php if($profession=="Network Engineer") echo "selected"; ?>>Network Engineer</option>
                            <option value="Database Admin" <?php if($profession=="Database Admin") echo "selected"; ?>>Database Admin</option>
                            <option value="Cybersecurity Analyst" <?php if($profession=="Cybersecurity Analyst") echo "selected"; ?>>Cybersecurity Analyst</option>
                            <option value="IT Support" <?php if($profession=="IT Support") echo "selected"; ?>>IT Support</option>
                            <option value="Other" <?php if($profession=="Other") echo "selected"; ?>>Other</option>
                        </select>
                    </div>

                    <div id="institution-field" style="text-align:left;display:none;">
                        <label style="font-weight:600;">School/University/College:</label>
                        <input type="text" name="institution" id="institution-input" value="<?php echo htmlspecialchars($institution); ?>">
                    </div>

                    <button type="submit" class="auth-btn save">Save Profile</button>

                </form>

                <!-- Separate form for clearing profile so it posts to this page and triggers the handler -->
                <form action="profile.php" method="post" style="margin-top:0.6rem;">
                    <button type="submit" name="clear_profile" class="auth-btn logout" style="background:linear-gradient(135deg,#f97316,#fb923c);" onclick="return confirm('This will permanently remove your profile data. Continue?')">Clear Profile</button>
                </form>

                <?php if (!empty($clearMsg)): ?>
                    <div style="margin-top:0.6rem;font-weight:600;color:#fff;background:rgba(0,0,0,0.12);padding:0.6rem;border-radius:0.6rem;">
                        <?php echo htmlspecialchars($clearMsg); ?>
                    </div>
                <?php endif; ?>

                <form action="signout.php" method="post">
                    <button type="submit" class="auth-btn logout">Sign Out</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
    <p>&copy; 2025 InfoNix. All rights reserved.</p>
    </footer>
</body>
</html>

<script>
// Toggle institution field based on profession select
(function(){
    function toggleInstitutionField(){
        var prof = document.getElementById('profession-select');
        if (!prof) return;
        var field = document.getElementById('institution-field');
        field.style.display = (prof.value === 'Student') ? 'block' : 'none';
    }
    var profEl = document.getElementById('profession-select');
    if (profEl) {
        profEl.addEventListener('change', toggleInstitutionField);
        window.addEventListener('load', toggleInstitutionField);
    }
})();
</script>

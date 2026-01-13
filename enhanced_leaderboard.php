<?php
// enhanced_leaderboard.php - Colorful, responsive leaderboard

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
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = intval($_SESSION['user_id']);

// Get current user
$userQuery = "SELECT name, profile_pic, COALESCE(score, 0) AS score, profession 
             FROM profiles WHERE user_id = $user_id";
$userResult = $conn->query($userQuery);
$userName = 'User';
$userAvatar = 'assets/default_avatar.png';
$userScore = 0;
$userProfession = '';
if ($userResult && $userResult->num_rows > 0) {
    $userRow = $userResult->fetch_assoc();
    $userName = htmlspecialchars($userRow['name'] ?? $userName);
    $userAvatar = htmlspecialchars($userRow['profile_pic'] ?? $userAvatar);
    $userScore = (int)($userRow['score'] ?? 0);
    $userProfession = htmlspecialchars($userRow['profession'] ?? '');
}

// Rank
$rankQuery = "SELECT COUNT(*) + 1 AS rank FROM profiles WHERE score > $userScore";
$rankResult = $conn->query($rankQuery);
$userRank = 'N/A';
if ($rankResult && $rankResult->num_rows > 0) $userRank = (int)$rankResult->fetch_assoc()['rank'];

// Leaderboard
$leaderboardQuery = "SELECT user_id, name, profile_pic, score, profession FROM profiles ORDER BY score DESC, user_id ASC LIMIT 50";
$leaderboardResult = $conn->query($leaderboardQuery);
$leaderboardUsers = [];
$rank = 1;
if ($leaderboardResult && $leaderboardResult->num_rows > 0) {
    while ($row = $leaderboardResult->fetch_assoc()) {
        $score = (int)$row['score'];
        if ($score >= 1000) { $badge = 'grandmaster'; $badgeTitle = 'Quiz Grandmaster'; }
        elseif ($score >= 750) { $badge = 'master'; $badgeTitle = 'Quiz Master'; }
        elseif ($score >= 500) { $badge = 'expert'; $badgeTitle = 'Quiz Expert'; }
        elseif ($score >= 250) { $badge = 'pro'; $badgeTitle = 'Quiz Pro'; }
        else { $badge = 'beginner'; $badgeTitle = 'Quiz Beginner'; }

        $leaderboardUsers[] = [
            'rank' => $rank,
            'user_id' => (int)$row['user_id'],
            'name' => htmlspecialchars($row['name'] ?? 'User'),
            'profile_pic' => htmlspecialchars($row['profile_pic'] ?? ''),
            'score' => $score,
            'profession' => htmlspecialchars($row['profession'] ?? ''),
            'badge' => $badge,
            'badgeTitle' => $badgeTitle,
            'isCurrentUser' => ((int)$row['user_id'] === $user_id)
        ];
        $rank++;
    }
}

// Top 3 users
$topUsersQuery = "SELECT user_id, name, profile_pic, score FROM profiles ORDER BY score DESC LIMIT 3";
$topUsersResult = $conn->query($topUsersQuery);
$topUsers = [];
if ($topUsersResult && $topUsersResult->num_rows > 0) {
    $pos = 1;
    while ($row = $topUsersResult->fetch_assoc()) {
        $topUsers[] = [
            'position' => $pos,
            'user_id' => (int)$row['user_id'],
            'name' => htmlspecialchars($row['name'] ?? 'User'),
            'profile_pic' => htmlspecialchars($row['profile_pic'] ?? ''),
            'score' => (int)$row['score'],
            'isCurrentUser' => ((int)$row['user_id'] === $user_id)
        ];
        $pos++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leaderboard - InfoNix</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* General */
body {
    font-family: 'Segoe UI', sans-serif;
    margin:0;
    background: linear-gradient(135deg,#6a11cb,#2575fc,#ff6a00);
    background-size: 200% 200%;
    animation: gradient 12s ease infinite;
    color: #fff;
}
@keyframes gradient {
  0%{background-position:0% 50%;}
  50%{background-position:100% 50%;}
  100%{background-position:0% 50%;}
}

/* Header */
header {
    background: rgba(0,0,0,0.4);
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(10px);
    position: sticky;
    top: 0;
    z-index: 1000;
}
header h1 {font-size:1.6rem; font-weight:bold; color:#ffdd57;}
nav {
    display: flex;
    gap: 15px;
}
nav a {
    color:#fff;
    text-decoration:none;
    font-weight:500;
    padding:8px 14px;
    border-radius:8px;
    transition:.3s;
}
nav a:hover, nav a.active {background:#ffdd57; color:#222;}
/* Mobile Nav */
.menu-toggle {display:none; flex-direction:column; cursor:pointer;}
.menu-toggle span {height:3px;width:25px;background:#fff;margin:4px 0;border-radius:5px;}
@media(max-width:768px){
  nav {display:none; flex-direction:column; gap:10px; background:rgba(0,0,0,0.8); position:absolute; top:60px; right:20px; padding:15px; border-radius:8px;}
  nav.show {display:flex;}
  .menu-toggle {display:flex;}
}

/* Leaderboard Container */
.leaderboard-container {
    max-width:1100px;
    margin:30px auto;
    background: rgba(255,255,255,0.1);
    border-radius:16px;
    padding:30px;
    backdrop-filter: blur(12px);
    box-shadow:0 8px 20px rgba(0,0,0,.4);
}

/* Podium */
.top-users {
    display:flex; justify-content:center; gap:20px; margin:40px 0; flex-wrap:wrap;
}
.podium {text-align:center;}
.podium-avatar {width:90px;height:90px;border-radius:50%;border:4px solid #fff;object-fit:cover;box-shadow:0 4px 12px rgba(0,0,0,.4);}
.podium-1 .podium-avatar{width:110px;height:110px;border:4px solid gold;}
.podium-2 .podium-avatar{border:4px solid silver;}
.podium-3 .podium-avatar{border:4px solid #cd7f32;}
.podium-score {color:#ffdd57;font-weight:bold;}
.podium-base {background:#3b82f6; padding:6px 14px; border-radius:6px; margin-top:5px;}

/* Table */
.table-container {overflow-x:auto;}
table {width:100%; border-collapse:collapse; margin-top:20px; color:#fff;}
th,td{padding:12px; text-align:left;}
th{background:#1e3a8a;}
tr:nth-child(even){background:rgba(255,255,255,0.08);}
.user-row{background:rgba(255,255,255,0.2);}
.avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;}
.badge{padding:4px 10px;border-radius:12px;font-size:12px;font-weight:bold;color:#fff;}
.badge.grandmaster{background:linear-gradient(45deg,#ff4500,#ffd700);}
.badge.master{background:linear-gradient(45deg,#7e22ce,#4f46e5);}
.badge.expert{background:#3b82f6;}
.badge.pro{background:#10b981;}
.badge.beginner{background:#6b7280;}

/* Stats */
.stats {display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-top:30px;}
.card{background:rgba(0,0,0,0.4); padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.3);}
</style>
</head>
<body>
<header>
  <h1>InfoNix</h1>
  <div class="menu-toggle" onclick="document.querySelector('nav').classList.toggle('show')">
    <span></span><span></span><span></span>
  </div>
  <nav>
    <a href="index.html">Home</a>
    <a href="dashboard.html">Dashboard</a>
    <a href="quiz.php">Quizzes</a>
    <a href="enhanced_leaderboard.php" class="active">Leaderboard</a>
    <a href="news.php">News</a>
    <a href="support.html">Support</a>
  </nav>
</header>

<main>
<div class="leaderboard-container">
  <h2 style="text-align:center; color:#ffdd57;">🌟 Leaderboard</h2>

  <!-- Podium -->
  <?php if (!empty($topUsers)): ?>
  <div class="top-users">
    <?php foreach($topUsers as $u): ?>
    <div class="podium podium-<?php echo $u['position']; ?>">
      <img src="<?php echo $u['profile_pic'] ?: 'https://via.placeholder.com/100'; ?>" class="podium-avatar">
      <p><strong><?php echo $u['name']; ?></strong><?php if($u['isCurrentUser']) echo " (You)"; ?></p>
      <p class="podium-score"><?php echo number_format($u['score']); ?> pts</p>
      <div class="podium-base"><?php echo $u['position']; ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Rankings Table -->
  <h3>🏆 Rankings</h3>
  <div class="table-container">
    <table>
      <thead>
        <tr><th>Rank</th><th></th><th>Name</th><th>Score</th><th>Profession</th><th>Badge</th></tr>
      </thead>
      <tbody>
      <?php foreach($leaderboardUsers as $u): ?>
        <tr class="<?php echo $u['isCurrentUser']?'user-row':''; ?>">
          <td><?php echo $u['rank']; ?></td>
          <td><img src="<?php echo $u['profile_pic'] ?: 'https://via.placeholder.com/40'; ?>" class="avatar"></td>
          <td><?php echo $u['name']; ?> <?php if($u['isCurrentUser']) echo "<span style='color:#ffdd57;'>(You)</span>"; ?></td>
          <td><strong><?php echo number_format($u['score']); ?></strong></td>
          <td><?php echo $u['profession'] ?: 'N/A'; ?></td>
          <td><span class="badge <?php echo $u['badge']; ?>"><?php echo $u['badgeTitle']; ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($leaderboardUsers)) echo "<tr><td colspan='6' style='text-align:center;'>No data</td></tr>"; ?>
      </tbody>
    </table>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="card">
      <h3>Your Stats</h3>
      <p><strong>Name:</strong> <?php echo $userName; ?></p>
      <p><strong>Rank:</strong> #<?php echo $userRank; ?></p>
      <p><strong>Score:</strong> <?php echo number_format($userScore); ?></p>
    </div>
    <div class="card">
      <h3>Motivation</h3>
      <p>🚀 Keep learning & climb the ranks!</p>
      <p>🎯 Aim for the next badge level!</p>
    </div>
  </div>
</div>
</main>
</body>
</html>
<?php $conn->close(); ?>

i <?php
// admin_dashboard.php: Admin dashboard for uploading articles/quizzes and viewing user stats
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: signin.html');
    exit();
}

// Database config
require_once 'db_connect.php';

// Handle article upload (create or update)
$articleMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_article'])) {
    $title = isset($_POST['title']) ? $conn->real_escape_string($_POST['title']) : '';
    $summary = isset($_POST['summary']) ? $conn->real_escape_string($_POST['summary']) : '';
    $content = isset($_POST['content']) ? $conn->real_escape_string($_POST['content']) : '';
    $featured_image_url = isset($_POST['featured_image_url']) ? trim($_POST['featured_image_url']) : '';
    $article_id = isset($_POST['article_id']) ? intval($_POST['article_id']) : 0; // 0 = create

    // Handle file upload if present (preferred over URL)
    $image = '';
    if (!empty($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        if (!is_dir($targetDir)) { @mkdir($targetDir, 0755, true); }
        $orig = basename($_FILES['image']['name']);
        $safe = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $orig);
        $unique = time() . '_' . $safe;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $unique)) {
            // store relative path
            $image = 'uploads/' . $unique;
        }
    }

    // If no uploaded image, but a featured_image_url was provided, use it
    if (empty($image) && !empty($featured_image_url)) {
        // Basic sanitize: allow relative path inside uploads/ or full URL
        $image = $conn->real_escape_string($featured_image_url);
    }

    if ($article_id > 0) {
        // Update existing article: only update fields if non-empty (empty string will overwrite)
        $updateParts = [];
        if ($title !== '') { $updateParts[] = "title = '" . $title . "'"; }
        if ($summary !== '') { $updateParts[] = "summary = '" . $summary . "'"; }
        if ($content !== '') { $updateParts[] = "content = '" . $content . "'"; }
        if ($image !== '') { $updateParts[] = "image = '" . $conn->real_escape_string($image) . "'"; }
        if (!empty($updateParts)) {
            $sql = "UPDATE articles SET " . implode(', ', $updateParts) . " WHERE id = " . $article_id;
            if ($conn->query($sql) === TRUE) {
                $articleMsg = "✅ Article updated successfully.";
            } else {
                $articleMsg = "❌ Error updating article: " . htmlspecialchars($conn->error);
            }
        } else {
            $articleMsg = "⚠️ No changes provided to update.";
        }
    } else {
        // Create new article
        $imgVal = $image ? $conn->real_escape_string($image) : '';
        $sql = "INSERT INTO articles (title, summary, content, image, date) VALUES ('" . $title . "', '" . $summary . "', '" . $content . "', '" . $imgVal . "', CURDATE())";
        if ($conn->query($sql) === TRUE) {
            $articleMsg = "✅ Article uploaded!";
        } else {
            $articleMsg = "❌ Error: " . htmlspecialchars($conn->error);
        }
    }
}

// Handle quiz upload
$quizMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_quiz'])) {
    // Expect structured fields: title, description, time_limit_seconds, points_per_question, featured_image
    $quiz_title = isset($_POST['quiz_title']) ? $conn->real_escape_string($_POST['quiz_title']) : '';
    $quiz_description = isset($_POST['quiz_description']) ? $conn->real_escape_string($_POST['quiz_description']) : '';
    $time_limit_seconds = isset($_POST['time_limit_seconds']) ? intval($_POST['time_limit_seconds']) : 0;
    $points_per_question = isset($_POST['points_per_question']) ? intval($_POST['points_per_question']) : 1;
    $featured_image = isset($_POST['featured_image']) ? $conn->real_escape_string($_POST['featured_image']) : '';

    if (!$quiz_title) {
        $quizMsg = "❌ Quiz title is required.";
    } else {
        // Insert into quizzes table and then insert questions
        $insertQuiz = "INSERT INTO quizzes (title, description, time_limit_seconds, points_per_question, featured_image, created_at) VALUES ('$quiz_title', '$quiz_description', $time_limit_seconds, $points_per_question, '$featured_image', NOW())";
        if ($conn->query($insertQuiz) === TRUE) {
            $newQuizId = $conn->insert_id;

            // Questions come from dynamic fields: q_text[], q_option_?_[], q_correct_[]
            // We'll accept a JSON payload fallback if present
            if (!empty($_POST['questions_json'])) {
                $questionsArr = json_decode($_POST['questions_json'], true);
            } else {
                // Build questions from indexed inputs
                $questionsArr = [];
                if (!empty($_POST['q_text']) && is_array($_POST['q_text'])) {
                    foreach ($_POST['q_text'] as $idx => $qtext) {
                        $qtext = $conn->real_escape_string($qtext);
                        $options = [];
                        if (!empty($_POST['q_option_' . $idx]) && is_array($_POST['q_option_' . $idx])) {
                            foreach ($_POST['q_option_' . $idx] as $opt) { $options[] = $conn->real_escape_string($opt); }
                        }
                        $correct = isset($_POST['q_correct'][$idx]) ? intval($_POST['q_correct'][$idx]) : 0;
                        $questionsArr[] = ['question_text'=>$qtext, 'options'=>$options, 'correct_index'=>$correct, 'points'=> $points_per_question];
                    }
                }
            }

            $qInsertErrors = [];
            foreach ($questionsArr as $q) {
                $qText = $conn->real_escape_string($q['question_text']);
                $optsJson = $conn->real_escape_string(json_encode(array_values($q['options'])));
                $correctIndex = isset($q['correct_index']) ? intval($q['correct_index']) : 0;
                $pts = isset($q['points']) ? intval($q['points']) : $points_per_question;
                $qSql = "INSERT INTO questions (quiz_id, question_text, options, correct_index, points) VALUES ($newQuizId, '$qText', '$optsJson', $correctIndex, $pts)";
                if ($conn->query($qSql) !== TRUE) {
                    $qInsertErrors[] = $conn->error;
                }
            }

            if (empty($qInsertErrors)) {
                $quizMsg = "✅ Quiz created successfully!";
            } else {
                $quizMsg = "⚠️ Quiz created but some questions failed to save: " . htmlspecialchars(implode('; ', $qInsertErrors));
            }
        } else {
            $quizMsg = "❌ Error creating quiz: " . htmlspecialchars($conn->error);
        }
    }
}

// Handle deleting selected articles
$deleteArticleMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_articles'])) {
    if (!empty($_POST['article_ids']) && is_array($_POST['article_ids'])) {
        $ids = array_map(function($id) use ($conn) { return (int)$id; }, $_POST['article_ids']);
        $idList = implode(',', $ids);
        $delSql = "DELETE FROM articles WHERE id IN ($idList)";
        if ($conn->query($delSql) === TRUE) {
            $deleteArticleMsg = "✅ Selected articles deleted.";
        } else {
            $deleteArticleMsg = "❌ Error deleting articles: " . htmlspecialchars($conn->error);
        }
    } else {
        $deleteArticleMsg = "⚠️ No articles selected.";
    }
}

// Handle deleting selected quizzes
$deleteQuizMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_quizzes'])) {
    if (!empty($_POST['quiz_ids']) && is_array($_POST['quiz_ids'])) {
        $ids = array_map(function($id) use ($conn) { return (int)$id; }, $_POST['quiz_ids']);
        $idList = implode(',', $ids);
        $delSql = "DELETE FROM quizzes WHERE id IN ($idList)";
        if ($conn->query($delSql) === TRUE) {
            $deleteQuizMsg = "✅ Selected quizzes deleted.";
        } else {
            $deleteQuizMsg = "❌ Error deleting quizzes: " . htmlspecialchars($conn->error);
        }
    } else {
        $deleteQuizMsg = "⚠️ No quizzes selected.";
    }
}

// Get user stats
$userStats = [];
$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $userStats[$row['role']] = $row['count'];
}
$totalUsers = array_sum($userStats);

// Get recent users
$recentUsers = $conn->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Fetch articles and quizzes for admin selection lists
$articlesList = $conn->query("SELECT id, title, date FROM articles ORDER BY date DESC LIMIT 50");
$quizzesList = $conn->query("SELECT id, title, created_at FROM quizzes ORDER BY created_at DESC LIMIT 50");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #ff9a9e, #fad0c4, #fad0c4, #fbc2eb, #a18cd1, #fbc2eb, #84fab0, #8fd3f4);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
        }

        @keyframes gradientBG {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: rgba(255,255,255,0.9);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeIn 0.7s ease-in-out;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            background: linear-gradient(90deg, #ff0080, #7928ca, #2afadf, #4facfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stats {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }
        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            color: #fff;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
        }
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #11998e, #38ef7d);
        }
        .stat-card:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 24px rgba(0,0,0,0.25);
        }
        .stat-card div:first-child {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .stat-card div:last-child {
            font-size: 2.4em;
        }
        .forms {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }
        .form-section {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 14px;
            border-left: 6px solid #ff0080;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .form-section:hover {
            transform: translateY(-5px);
        }
        .form-section h2 {
            color: #ff0080;
            margin-top: 0;
        }
        label {
            font-weight: 600;
            display: block;
            margin-top: 12px;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
        }
        button {
            margin-top: 16px;
            padding: 12px 20px;
            background: linear-gradient(90deg, #ff0080, #7928ca);
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        .msg {
            margin-top: 10px;
            font-weight: bold;
        }
        .recent-users {
            margin-top: 30px;
        }
        .recent-users h2 {
            color: #7928ca;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        th {
            background: linear-gradient(90deg, #ff0080, #7928ca);
            color: #fff;
            padding: 12px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #ffe4f3;
        }
        @keyframes fadeIn {
            from {opacity:0; transform: translateY(20px);}
            to {opacity:1; transform: translateY(0);}
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h1>Admin Dashboard</h1>
            <div>
                Logged in as: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
                &nbsp;|&nbsp; <a href="signout.php" style="color:#ff0080; font-weight:bold; text-decoration:none;">Sign Out</a>
            </div>
        </div>
        <div class="stats">
            <div class="stat-card">
                <div>Total Users</div>
                <div><?= $totalUsers ?></div>
            </div>
            <div class="stat-card">
                <div>Admins</div>
                <div><?= isset($userStats['admin']) ? $userStats['admin'] : 0 ?></div>
            </div>
            <div class="stat-card">
                <div>Regular Users</div>
                <div><?= isset($userStats['user']) ? $userStats['user'] : 0 ?></div>
            </div>
        </div>
        <div class="forms">
            <div class="form-section" id="articleFormSection">
                <h2>Create / Update Article</h2>
                <form id="articleForm" method="POST" enctype="multipart/form-data">
                    <label>Article ID (leave blank to create)</label>
                    <input type="number" name="article_id" id="article_id" placeholder="Optional: id to update">

                    <label>Title</label>
                    <input type="text" name="title" id="article_title" required>
                    <label>Summary</label>
                    <textarea name="summary" id="article_summary" required></textarea>
                    <label>Content</label>
                    <textarea name="content" id="article_content" required></textarea>

                    <label>Featured Image URL (optional)</label>
                    <input type="text" name="featured_image_url" id="featured_image_url" placeholder="uploads/your-image.jpg or https://...">

                    <label>Or Upload Image</label>
                    <input type="file" name="image" id="article_image" accept="image/*">

                    <div id="imagePreview" style="margin-top:10px;display:none;">
                        <label style="font-weight:600;">Image Preview</label>
                        <div style="margin-top:6px;"><img id="previewImg" src="" alt="preview" style="max-width:100%;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.08);"></div>
                    </div>

                    <button type="submit" name="upload_article">Submit Article</button>
                </form>
                <div class="msg"><?= $articleMsg ?></div>
            </div>
                <div class="form-section">
                    <h2>Create Quiz (friendly)</h2>
                    <form id="quizCreateForm" method="POST">
                        <label>Quiz Title</label>
                        <input type="text" name="quiz_title" id="quiz_title" required>
                        <label>Short Description</label>
                        <input type="text" name="quiz_description" id="quiz_description">
                        <label>Time Limit (seconds, 0 for no limit)</label>
                        <input type="number" name="time_limit_seconds" id="time_limit_seconds" value="0" min="0">
                        <label>Points per question</label>
                        <input type="number" name="points_per_question" id="points_per_question" value="1" min="0">
                        <label>Featured Image URL (optional)</label>
                        <input type="text" name="featured_image" id="featured_image" placeholder="uploads/example.jpg or https://...">

                        <div id="questionsContainer">
                            <!-- Question blocks will be inserted here -->
                        </div>

                        <button type="button" id="addQuestionBtn">+ Add Question</button>
                        <button type="submit" name="upload_quiz">Create Quiz</button>
                    </form>
                    <div class="msg"><?= $quizMsg ?></div>
                </div>
        </div>
        <div class="recent-users">
            <h2>Recent Users</h2>
            <table>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
                <?php while ($row = $recentUsers->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['role']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        
        <div class="recent-users" style="margin-top:30px;">
            <h2>Manage Articles</h2>
            <form method="POST">
                <table>
                    <tr><th style="width:40px"></th><th>Title</th><th>Date</th></tr>
                    <?php if ($articlesList && $articlesList->num_rows > 0): ?>
                        <?php while ($a = $articlesList->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" name="article_ids[]" value="<?= (int)$a['id'] ?>"></td>
                                <td><?= htmlspecialchars($a['title']) ?></td>
                                <td><?= htmlspecialchars($a['date']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No articles found.</td></tr>
                    <?php endif; ?>
                </table>
                <button type="submit" name="delete_articles" style="background:#e74c3c;">Delete Selected Articles</button>
                <div class="msg"><?= $deleteArticleMsg ?></div>
            </form>
        </div>

        <div class="recent-users" style="margin-top:30px;">
            <h2>Manage Quizzes</h2>
            <form method="POST">
                <table>
                    <tr><th style="width:40px"></th><th>Title</th><th>Created</th></tr>
                    <?php if ($quizzesList && $quizzesList->num_rows > 0): ?>
                        <?php while ($q = $quizzesList->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" name="quiz_ids[]" value="<?= (int)$q['id'] ?>"></td>
                                <td><?= htmlspecialchars($q['title']) ?></td>
                                <td><?= htmlspecialchars($q['created_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No quizzes found.</td></tr>
                    <?php endif; ?>
                </table>
                <button type="submit" name="delete_quizzes" style="background:#e74c3c;">Delete Selected Quizzes</button>
                <div class="msg"><?= $deleteQuizMsg ?></div>
            </form>
        </div>
    </div>
</body>
</html>
<script>
// Modal popup for delete messages
document.addEventListener('DOMContentLoaded', function() {
    const msg = <?= json_encode($deleteArticleMsg ?: $deleteQuizMsg ?: '') ?>;
    if (msg) {
        // create modal container
        const modal = document.createElement('div');
        modal.id = 'cop_modal';
        modal.style.position = 'fixed';
        modal.style.left = '0';
        modal.style.top = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.background = 'rgba(0,0,0,0.4)';
        modal.style.zIndex = '9999';

        const box = document.createElement('div');
        box.style.background = '#fff';
        box.style.padding = '18px 22px';
        box.style.borderRadius = '8px';
        box.style.boxShadow = '0 8px 24px rgba(0,0,0,0.2)';
        box.style.maxWidth = '520px';
        box.style.width = '90%';
        box.style.fontFamily = 'Segoe UI, Arial, sans-serif';
        box.style.fontSize = '15px';

        const text = document.createElement('div');
        text.innerText = msg;
        box.appendChild(text);

        const closeBtn = document.createElement('button');
        closeBtn.innerText = 'Close';
        closeBtn.style.marginTop = '12px';
        closeBtn.style.padding = '8px 12px';
        closeBtn.style.border = 'none';
        closeBtn.style.background = '#007bff';
        closeBtn.style.color = '#fff';
        closeBtn.style.borderRadius = '6px';
        closeBtn.style.cursor = 'pointer';
        closeBtn.addEventListener('click', function() { document.body.removeChild(modal); });
        box.appendChild(closeBtn);

        modal.appendChild(box);
        document.body.appendChild(modal);

        // auto-hide after 3 seconds
        setTimeout(function() { if (document.getElementById('cop_modal')) document.body.removeChild(modal); }, 3000);
    }
});
</script>
<script>
// Quiz creation helper (dynamic question blocks)
document.addEventListener('DOMContentLoaded', function(){
    const addBtn = document.getElementById('addQuestionBtn');
$conn = new mysqli($servername, $username, $password, $dbname);

require_once 'db_connect.php';
    const container = document.getElementById('questionsContainer');
    let qIndex = 0;

    function createQuestionBlock(idx){
        const wrap = document.createElement('div');
        wrap.className = 'question-block';
        wrap.style.border = '1px solid #eee';
        wrap.style.padding = '10px';
        wrap.style.marginTop = '10px';
        wrap.innerHTML = `
            <label style="font-weight:700;">Question</label>
            <input type="text" name="q_text[]" required style="width:100%;padding:8px;margin-top:6px;" />
            <div class="options" style="margin-top:8px;"></div>
            <button type="button" class="addOptBtn">Add Option</button>
            <label style="display:block;margin-top:8px;">Correct Option Index</label>
            <input type="number" name="q_correct[]" value="0" min="0" style="width:80px;" />
            <button type="button" class="removeQBtn" style="float:right;background:#e74c3c;color:#fff;padding:6px;border:none;border-radius:6px;margin-top:-30px;">Remove Question</button>
        `;

        // add first two option inputs by default
        const optsDiv = wrap.querySelector('.options');
        function addOption(val=''){
            const oi = document.createElement('div');
            oi.style.display='flex'; oi.style.gap='8px'; oi.style.marginTop='6px';
            oi.innerHTML = `<input type="text" name="q_option_${qIndex}[]" value="${val}" required style="flex:1;padding:6px;" /> <button type="button" class="remOpt" style="background:#e74c3c;color:#fff;border:none;border-radius:6px;padding:6px;">X</button>`;
            oi.querySelector('.remOpt').addEventListener('click', ()=> oi.remove());
            optsDiv.appendChild(oi);
        }
        addOption(); addOption();

        wrap.querySelector('.addOptBtn').addEventListener('click', function(){ addOption(); });
        wrap.querySelector('.removeQBtn').addEventListener('click', function(){ wrap.remove(); });

        container.appendChild(wrap);
        qIndex++;
    }

    addBtn.addEventListener('click', function(){ createQuestionBlock(qIndex); });

    // Create initial one question block to start
    createQuestionBlock(qIndex);

    // Before submit, gather options into indexed arrays expected by server
    const form = document.getElementById('quizCreateForm');
    form.addEventListener('submit', function(e){
        // Convert question blocks into explicit q_option_<idx>[] inputs are already in DOM
        // However, our naming uses q_option_0[], q_option_1[] dynamically; that's fine.
        // No extra action needed unless older browsers; optionally build questions_json if desired.
        // As a safeguard, build a questions_json hidden field.
        const blocks = container.querySelectorAll('.question-block');
        const questions = [];
        blocks.forEach((b, idx)=>{
            const qtext = b.querySelector('input[name="q_text[]"]').value;
            const opts = Array.from(b.querySelectorAll(`input[name^="q_option_"]`))
                .filter(inp => inp.name.startsWith(`q_option_${idx}`))
                .map(i=>i.value);
            const correct = b.querySelector('input[name="q_correct[]"]').value || 0;
            questions.push({question_text:qtext, options:opts, correct_index:parseInt(correct||0,10)});
        });
        let hidden = document.querySelector('input[name="questions_json"]');
        if (!hidden) { hidden = document.createElement('input'); hidden.type='hidden'; hidden.name='questions_json'; form.appendChild(hidden); }
        hidden.value = JSON.stringify(questions);
    });
});
</script>
<script>
// Article image preview and simple UX
document.addEventListener('DOMContentLoaded', function(){
    const imgUrlInput = document.getElementById('featured_image_url');
    const fileInput = document.getElementById('article_image');
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    function showPreview(src){
        previewImg.src = src;
        preview.style.display = 'block';
    }

    imgUrlInput.addEventListener('input', function(){
        const v = imgUrlInput.value.trim();
        if (v) { showPreview(v); }
        else { preview.style.display = 'none'; }
    });

    fileInput.addEventListener('change', function(){
        const f = fileInput.files && fileInput.files[0];
        if (f) {
            const reader = new FileReader();
            reader.onload = function(e){ showPreview(e.target.result); };
            reader.readAsDataURL(f);
        }
    });
});
</script>

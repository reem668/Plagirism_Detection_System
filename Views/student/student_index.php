<?php
/**
 * Protected Student Dashboard
 * Only accessible by authenticated student users
 */

require_once __DIR__ . '../../../Helpers/SessionManager.php';
require_once __DIR__ . '../../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '../../../Helpers/Csrf.php';
require_once __DIR__ . '../../../Controllers/SubmissionController.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\SubmissionController;
use Helpers\Csrf;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// CRITICAL: Require student role - this blocks unauthorized access
$auth->requireRole('student');

// Get authenticated user info
$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];
$username = $currentUser['name'];

// Initialize controller
$ctrl = new SubmissionController();

// Verify CSRF for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? '')) {
        die('CSRF token validation failed. Please refresh and try again.');
    }
}

// Handle DELETE action
if (isset($_POST['delete_id'])) {
    // Verify ownership before deleting
    if ($auth->ownsResource($_POST['delete_id'])) {
        $ctrl->delete($_POST['delete_id'], $userId);
        header("Location: student_index.php");
        exit;
    } else {
        die('Unauthorized: You can only delete your own submissions.');
    }
}

// Handle RESTORE action
if (isset($_POST['restore_id'])) {
    // Verify ownership before restoring
    if ($auth->ownsResource($_POST['restore_id'])) {
        $ctrl->restore($_POST['restore_id'], $userId);
        header("Location: student_index.php");
        exit;
    } else {
        die('Unauthorized: You can only restore your own submissions.');
    }
}

// Handle SUBMISSION
$submissionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id']) && !isset($_POST['restore_id'])) {
    // Additional validation: ensure user_id in submission matches authenticated user
    $_POST['user_id'] = $userId; // Force correct user ID
    $submissionResult = $ctrl->submit();
}

// Generate CSRF token for forms
$csrfToken = Csrf::token();

// Fetch submissions - only for authenticated user
$submissions = $ctrl->getUserSubmissions($userId, 'active');
$deletedSubmissions = $ctrl->getUserSubmissions($userId, 'deleted');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plagiarism Detection - Student Dashboard</title>
<link rel="stylesheet" href="../../assets/css/student.css">
<link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="user-profile">
        <p class="username"><?= htmlspecialchars($username) ?></p>
        <p class="user-role">Student</p>
        <p class="user-id">ID: <?= htmlspecialchars($userId) ?></p>
    </div>
    <div class="menu">
        <a href="#" id="homeBtn" data-tooltip="Home">🏠</a>
        <a href="#" id="historyBtn" data-tooltip="Past History">📜</a>
        <a href="#" id="trashBtn" data-tooltip="Trash">🗑️</a>
    </div>
    <a href="<?= htmlspecialchars('../../logout.php', ENT_QUOTES) ?>" class="logout" data-tooltip="Logout">↻</a>
</nav>

<!-- Main content -->
<main class="main-content">

    <!-- Submission Page -->
    <section id="mainPage" class="page active">
        <h1>Submit Your Work</h1>
        <div class="content-grid">

            <!-- Submission Form -->
            <div class="submission-box">
                <h2>Submission Form</h2>
                <form id="submissionForm" method="POST" enctype="multipart/form-data">
                    <!-- CSRF Protection -->
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    
                    <!-- Hidden user ID - server will verify this matches session -->
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId, ENT_QUOTES) ?>">

                    <label for="submissionType">Submission Type</label>
                    <select id="submissionType" name="submissionType" required>
                        <option value="general">General Submission</option>
                        <option value="specific">Specific Teacher</option>
                    </select>

                    <div id="teacherDropdown" style="display:none;">
                        <label for="teacherSelect">Teacher</label>
                        <select id="teacherSelect" name="teacherSelect">
                            <option value="">-- Select a Teacher --</option>
                            <option value="Mr. Ahmed">Mr. Ahmed</option>
                            <option value="Ms. Fatma">Ms. Fatma</option>
                            <option value="Dr. Khaled">Dr. Khaled</option>
                        </select>
                    </div>

                    <label for="textInput">Text</label>
                    <textarea id="textInput" name="textInput" placeholder="Enter your text..." rows="8"></textarea>

                    <label for="fileInput">Upload File</label>
                    <input type="file" id="fileInput" name="fileInput" accept=".txt,.docx">

                    <button type="submit">Submit</button>
                </form>
            </div>

            <!-- Plagiarism Wheel -->
            <aside class="results-box">
                <h2>Plagiarism Overview</h2>
                <div class="ring-container">
                    <div class="ring" id="ring"></div>
                    <div class="ring-value" id="ringValue">
                        <?= $submissionResult['plagiarised'] ?? 0 ?>%
                    </div>
                </div>
                <div class="percent-breakdown">
                    <div>
                        <span class="indicator-box unique-box"></span>
                        Unique: <span id="uniqueValue"><?= 100 - ($submissionResult['plagiarised'] ?? 0) ?>%</span>
                    </div>
                    <div>
                        <span class="indicator-box exact-box"></span>
                        Exact Match: <span id="exactValue"><?= $submissionResult['exact'] ?? 0 ?>%</span>
                    </div>
                    <div>
                        <span class="indicator-box partial-box"></span>
                        Partial Match: <span id="partialValue"><?= $submissionResult['partial'] ?? 0 ?>%</span>
                    </div>
                </div>

              <?php if($submissionResult): ?>
                <?php if(!empty($submissionResult['alert_message'])): ?>
                  <div class="alert-warning" style="background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-weight: bold;">
                    <?= htmlspecialchars($submissionResult['alert_message']) ?>
                  </div>
                <?php endif; ?>
                <a href="download.php?id=<?php echo $submissionResult['submission_id']; ?>" class="download-btn">
                  Download Report
                </a>
              <?php endif; ?>

            </aside>

        </div>
    </section>

    <!-- History Page -->
    <section id="historyPage" class="page">
        <h1>Past Submissions</h1>
        <?php if($submissions): ?>
            <?php foreach($submissions as $sub): ?>
                <div class="history-item">
                    <h3>Submission #<?= $sub['id'] ?></h3>
                    <p><strong>Date:</strong> <?= htmlspecialchars($sub['created_at']) ?></p>
                    <p><strong>Plagiarism:</strong> <?= $sub['similarity'] ?>%</p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($sub['status']) ?></p>
                    
                    <?php if(!empty($sub['file_path'])): ?>
                        <a href="<?= htmlspecialchars($sub['file_path']) ?>" download class="btn-download">Download File</a>
                    <?php endif; ?>
                    
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="delete_id" value="<?= $sub['id'] ?>">
                        <button type="submit" class="btn-delete" onclick="return confirm('Move to trash?')">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-message">No submissions yet.</p>
        <?php endif; ?>
    </section>

    <!-- Trash Page -->
    <section id="trashPage" class="page">
        <h1>Trash</h1>
        <?php if($deletedSubmissions): ?>
            <?php foreach($deletedSubmissions as $sub): ?>
                <div class="history-item deleted">
                    <h3>Submission #<?= $sub['id'] ?></h3>
                    <p><strong>Date:</strong> <?= htmlspecialchars($sub['created_at']) ?></p>
                    <p><strong>Plagiarism:</strong> <?= $sub['similarity'] ?>%</p>
                    
                    <?php if(!empty($sub['file_path'])): ?>
                        <a href="<?= htmlspecialchars($sub['file_path']) ?>" download class="btn-download">Download File</a>
                    <?php endif; ?>
                    
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="restore_id" value="<?= $sub['id'] ?>">
                        <button type="submit" class="btn-restore">Restore</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-message">Trash is empty.</p>
        <?php endif; ?>
    </section>

</main>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Page navigation
    const pages = {home:'mainPage', history:'historyPage', trash:'trashPage'};
    Object.keys(pages).forEach(key => {
        document.getElementById(key+'Btn')?.addEventListener('click', e=>{
            e.preventDefault();
            document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
            document.getElementById(pages[key]).classList.add('active');
        });
    });

    // Teacher dropdown toggle
    const submissionType = document.getElementById('submissionType');
    const teacherDropdown = document.getElementById('teacherDropdown');
    submissionType?.addEventListener('change', ()=> {
        teacherDropdown.style.display = (submissionType.value==='specific') ? 'block' : 'none';
    });
});
</script>

</body>
</html>
Claude
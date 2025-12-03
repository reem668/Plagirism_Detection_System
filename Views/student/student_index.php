<?php
session_start();

// Resolve project root (C:\xamppp\htdocs\Plagirism_Detection_System)
$rootPath = dirname(dirname(__DIR__)); // Views/student -> Views -> project root

// Core dependencies from project root
require_once $rootPath . '/Helpers/Csrf.php';
require_once $rootPath . '/Controllers/SubmissionController.php';
require_once $rootPath . '/Models/Instructor.php';

use Controllers\SubmissionController;
use Helpers\Csrf;
use Models\Instructor;

// Only students can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../signup.php");
    exit();
}

// DB connection for this view and the Instructor model
require_once $rootPath . '/includes/db.php';

// Ensure $conn is available and valid before using it
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection not available. Please check includes/db.php configuration.');
}

// Controllers / Models
$ctrl = new SubmissionController();
$instructorModel = new Instructor($conn);
$instructors = $instructorModel->getAllInstructors();

// DELETE
if (isset($_POST['delete_id'])) {
    $ctrl->delete($_POST['delete_id'], $_SESSION['user_id']);
    header("Location: student_index.php");
    exit;
}

// RESTORE
if (isset($_POST['restore_id'])) {
    $ctrl->restore($_POST['restore_id'], $_SESSION['user_id']);
    header("Location: student_index.php");
    exit;
}

// SUBMISSION
$submissionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id']) && !isset($_POST['restore_id'])) {
    $submissionResult = $ctrl->submit();
}

// CSRF token
$csrfToken = Csrf::token();

// Fetch submissions
$submissions = $ctrl->getUserSubmissions($_SESSION['user_id'], 'active');
$deletedSubmissions = $ctrl->getUserSubmissions($_SESSION['user_id'], 'deleted');

$username = $_SESSION['user_name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plagiarism Detection - Student</title>
<link rel="stylesheet" href="../../assets/css/student.css">
<link rel="stylesheet" href="../../assets/css/user.css">
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="user-profile">
        <p class="username"><?= htmlspecialchars($username) ?></p>
        <p class="user-role">Student</p>
    </div>
    <div class="menu">
        <a href="#" id="homeBtn" data-tooltip="Home">🏠</a>
        <a href="#" id="historyBtn" data-tooltip="Past History">📜</a>
        <a href="#" id="trashBtn" data-tooltip="Trash">🗑️</a>
    </div>
    <a href="<?= htmlspecialchars('../../logout.php', ENT_QUOTES) ?>" class="logout" data-tooltip="Logout">⏻</a>
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
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

                    <label for="submissionType">Submission Type</label>
                    <select id="submissionType" name="submissionType">
                        <option value="general">General Submission</option>
                        <option value="specific">Specific Teacher</option>
                    </select>

                    <div id="teacherDropdown" style="display:none;">
                        <label for="teacherSelect">Instructor</label>
                        <select id="teacherSelect" name="teacherSelect">
                            <option value="">-- Select an Instructor --</option>
                            <?php if (!empty($instructors)): ?>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo htmlspecialchars($instructor['name']); ?>">
                                        <?php echo htmlspecialchars($instructor['name']); ?> (<?php echo htmlspecialchars($instructor['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No instructors available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <label for="textInput">Text</label>
                    <textarea id="textInput" name="textInput" placeholder="Enter your text..."></textarea>

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
                    <p>Plagiarism: <?= $sub['similarity'] ?>%</p>
                    <?php if(!empty($sub['file_path'])): ?>
                        <a href="<?= htmlspecialchars($sub['file_path']) ?>" download>Download File</a>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= $sub['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No submissions yet.</p>
        <?php endif; ?>
    </section>

    <!-- Trash Page -->
    <section id="trashPage" class="page">
        <h1>Trash</h1>
        <?php if($deletedSubmissions): ?>
            <?php foreach($deletedSubmissions as $sub): ?>
                <div class="history-item">
                    <h3>Submission #<?= $sub['id'] ?></h3>
                    <p>Plagiarism: <?= $sub['similarity'] ?>%</p>
                    <?php if(!empty($sub['file_path'])): ?>
                        <a href="<?= htmlspecialchars($sub['file_path']) ?>" download>Download File</a>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="restore_id" value="<?= $sub['id'] ?>">
                        <button type="submit">Restore</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Trash is empty.</p>
        <?php endif; ?>
    </section>

</main>

<!-- JS -->
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
    const teacherSelect = document.getElementById('teacherSelect');
    const submissionForm = document.getElementById('submissionForm');
    
    submissionType?.addEventListener('change', ()=> {
        const isSpecific = submissionType.value === 'specific';
        teacherDropdown.style.display = isSpecific ? 'block' : 'none';
        // Make teacher field required only when specific teacher is selected
        if (teacherSelect) {
            teacherSelect.required = isSpecific;
            if (!isSpecific) {
                teacherSelect.value = '';
            }
        }
    });
    
    // Form validation
    submissionForm?.addEventListener('submit', (e) => {
        const isSpecific = submissionType?.value === 'specific';
        if (isSpecific && (!teacherSelect || !teacherSelect.value)) {
            e.preventDefault();
            alert('Please select an instructor for specific teacher submission.');
            return false;
        }
    });
});
</script>

</body>
</html>

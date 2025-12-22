<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Protected Student Dashboard
 * Only accessible by authenticated student users
 * 
 * @author Your Team
 * @version 2.0
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../Helpers/Csrf.php';
require_once __DIR__ . '/../../Controllers/SubmissionController.php';
require_once __DIR__ . '/../../Controllers/StudentController.php';
// Ensure global database connection
if (!isset($conn)) {
    require __DIR__ . '/../../../includes/db.php';
}
if (!isset($conn)) {
    die("Database connection failed in student dashboard.");
}

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\SubmissionController;
use Controllers\StudentController;
use Helpers\Csrf;

// ============================================
// CONSTANTS
// ============================================
const STATUS_ACCEPTED = 'accepted';
const STATUS_REJECTED = 'rejected';
const STATUS_PENDING = 'pending';

const ROLE_INSTRUCTOR = 'instructor';
const ROLE_STUDENT = 'student';

const PLAGIARISM_HIGH = 70;
const PLAGIARISM_MEDIUM = 40;

// ============================================
// INITIALIZATION
// ============================================
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();
$auth->requireRole(ROLE_STUDENT);

$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];
$username = $currentUser['name'];

$ctrl = new SubmissionController();
$csrfToken = Csrf::token();

// ============================================
// CSRF PROTECTION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? '')) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh and try again.');
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Verify user owns a submission
 * 
 * @param int $submissionId
 * @param int $userId
 * @param array $submissions
 * @return bool
 */
function verifyOwnership(int $submissionId, int $userId, array $submissions): bool
{
    foreach ($submissions as $submission) {
        if ($submission['id'] === $submissionId) {
            return true;
        }
    }
    return false;
}

/**
 * Get plagiarism color based on percentage
 * 
 * @param float $similarity
 * @return string Hex color code
 */
function getPlagiarismColor(float $similarity): string
{
    if ($similarity > PLAGIARISM_HIGH) {
        return '#ef4444'; // Red
    } elseif ($similarity > PLAGIARISM_MEDIUM) {
        return '#f59e0b'; // Orange
    }
    return '#10b981'; // Green
}

/**
 * Get status badge styling
 * 
 * @param string $status
 * @return array ['color' => string, 'badge' => string]
 */
function getStatusBadge(string $status): array
{
    switch ($status) {
        case STATUS_ACCEPTED:
            return ['color' => '#10b981', 'badge' => '✅ Accepted'];
        case STATUS_REJECTED:
            return ['color' => '#ef4444', 'badge' => '❌ Rejected'];
        default:
            return ['color' => '#f59e0b', 'badge' => '⏳ Pending'];
    }
}

/**
 * Fetch active instructors from database
 * 
 * @param mysqli $conn Database connection
 * @return array List of instructors
 */
function fetchInstructors($conn): array
{
    $instructors = [];

    if (!$conn) {
        return $instructors;
    }

    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = ? AND status = 'active' ORDER BY name ASC");
    $role = ROLE_INSTRUCTOR;
    $stmt->bind_param('s', $role);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
    }

    $stmt->close();
    return $instructors;
}

/**
 * Count unseen notifications for user
 * 
 * @param array $submissions
 * @return int
 */
function countUnseenNotifications(array $submissions): int
{
    $count = 0;

    foreach ($submissions as $submission) {
        $hasFeedback = !empty($submission['feedback']);
        $isAccepted = $submission['status'] === STATUS_ACCEPTED;
        $isRejected = $submission['status'] === STATUS_REJECTED;
        $seen = $submission['notification_seen'] ?? 0;

        if (($hasFeedback || $isAccepted || $isRejected) && !$seen) {
            $count++;
        }
    }

    return $count;
}

/**
 * Redirect with message
 * 
 * @param string $location
 * @param string $message
 * @param string $type 'success' or 'error'
 */
function redirectWithMessage(string $location, string $message, string $type = 'success'): void
{
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit;
}

// ============================================
// POST REQUEST HANDLERS
// ============================================

// Handle DELETE action
if (isset($_POST['delete_id'])) {
    $submissionId = intval($_POST['delete_id']);
    $activeSubmissions = $ctrl->getUserSubmissions($userId, 'active');

    if (verifyOwnership($submissionId, $userId, $activeSubmissions)) {
        $result = $ctrl->delete($submissionId, $userId);

        if ($result === true) {
            redirectWithMessage(
                'student_index.php?view=trash',
                "Submission #$submissionId moved to trash successfully",
                'success'
            );
        } else {
            redirectWithMessage(
                'student_index.php?view=history',
                "Failed to delete submission #$submissionId",
                'error'
            );
        }
    } else {
        redirectWithMessage(
            'student_index.php?view=history',
            'Unauthorized: You can only delete your own submissions.',
            'error'
        );
    }
}

// Handle RESTORE action
if (isset($_POST['restore_id'])) {
    $submissionId = intval($_POST['restore_id']);
    $deletedSubmissions = $ctrl->getUserSubmissions($userId, 'deleted');

    if (verifyOwnership($submissionId, $userId, $deletedSubmissions)) {
        $ctrl->restore($submissionId, $userId);
        redirectWithMessage(
            'student_index.php?view=trash',
            "Submission #$submissionId restored successfully",
            'success'
        );
    } else {
        redirectWithMessage(
            'student_index.php?view=trash',
            'Unauthorized: You can only restore your own submissions.',
            'error'
        );
    }
}

// Handle SUBMISSION
$submissionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id']) && !isset($_POST['restore_id'])) {
    $_POST['user_id'] = $userId; // Force correct user ID
    $submissionResult = $ctrl->submit();
}

// ============================================
// DATA FETCHING
// ============================================
$submissions = $ctrl->getUserSubmissions($userId, 'active');
$deletedSubmissions = $ctrl->getUserSubmissions($userId, 'deleted');
$instructors = fetchInstructors($conn);
$notificationCount = countUnseenNotifications($submissions);

// ============================================
// VIEW SELECTION (SERVER-SIDE FALLBACK)
// ============================================
$currentView = $_GET['view'] ?? 'home';
$validViews = ['home', 'history', 'notifications', 'trash', 'chat'];
if (!in_array($currentView, $validViews, true)) {
    $currentView = 'home';
}
function isActiveView(string $view, string $currentView): string
{
    return $view === $currentView ? 'active' : '';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plagiarism Detection - Student Dashboard</title>
    <link rel="stylesheet" href="/Plagirism_Detection_System/assets/css/student.css">
    <link rel="stylesheet" href="/Plagirism_Detection_System/assets/css/user.css">
    <link rel="stylesheet" href="/Plagirism_Detection_System/assets/css/chatbot.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="user-profile">
            <p class="username"><?= htmlspecialchars($username, ENT_QUOTES) ?></p>
            <p class="user-role">Student</p>
            <p class="user-id">ID: <?= htmlspecialchars($userId, ENT_QUOTES) ?></p>
        </div>
        <div class="menu">
            <a href="student_index.php?view=home" id="homeBtn" data-tooltip="Home">🏠</a>
            <a href="student_index.php?view=history" id="historyBtn" data-tooltip="Past History">📜</a>
            <a href="student_index.php?view=notifications" id="notificationsBtn" data-tooltip="Notifications"
                class="notification-link">
                🔔
                <?php if ($notificationCount > 0): ?>
                    <span id="notificationBadge" class="notification-badge">
                        <?= $notificationCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="student_index.php?view=trash" id="trashBtn" data-tooltip="Trash">🗑️</a>
            <a href="student_index.php?view=chat" id="chatBtn" data-tooltip="Chat with Instructor">💬</a>
        </div>
        <a href="<?= BASE_URL ?>/logout" class="logout" id="logoutBtn" data-tooltip="Logout">↻</a>

    </nav>

    <!-- Main content -->
    <main class="main-content">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Submission Page -->
        <section id="mainPage" class="page <?= isActiveView('home', $currentView) ?>">
            <h1>Submit Your Work</h1>
            <div class="content-grid">
                <!-- Submission Form -->
                <div class="submission-box">
                    <h2>Submission Form</h2>
                    <form id="submissionForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId, ENT_QUOTES) ?>">

                        <label for="instructorSelect">Instructor (Optional)</label>
                        <select id="instructorSelect" name="instructorSelect">
                            <option value="">-- General Submission --</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?= htmlspecialchars($instructor['id'], ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($instructor['name'], ENT_QUOTES) ?>
                                    (<?= htmlspecialchars($instructor['email'], ENT_QUOTES) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="textInput">Text</label>
                        <textarea id="textInput" name="textInput" rows="8"></textarea>

                        <label for="fileInput">Upload File</label>
                        <input type="file" id="fileInput" name="fileInput" accept=".txt,.docx">

                        <button type="submit">Submit</button>
                    </form>
                </div>

                <!-- Plagiarism Overview -->
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
                            Unique: <?= 100 - ($submissionResult['plagiarised'] ?? 0) ?>%
                        </div>
                        <div>
                            <span class="indicator-box exact-box"></span>
                            Exact Match: <?= $submissionResult['exact'] ?? 0 ?>%
                        </div>
                        <div>
                            <span class="indicator-box partial-box"></span>
                            Partial Match: <?= $submissionResult['partial'] ?? 0 ?>%
                        </div>
                    </div>

                    <?php if ($submissionResult && !empty($submissionResult['alert_message'])): ?>
                        <div class="alert-warning">
                            <?= htmlspecialchars($submissionResult['alert_message'], ENT_QUOTES) ?>
                        </div>
                        <a href="download.php?id=<?= $submissionResult['submission_id'] ?>" class="download-btn">
                            Download Report
                        </a>
                    <?php endif; ?>
                </aside>
            </div>
        </section>

        <!-- History Page -->
        <section id="historyPage" class="page <?= isActiveView('history', $currentView) ?>">
            <h1>Past Submissions</h1>
            <?php if ($submissions): ?>
                <?php foreach ($submissions as $submission):
                    $statusInfo = getStatusBadge($submission['status']);
                    $plagColor = getPlagiarismColor($submission['similarity']);
                    ?>
                    <div class="history-item">
                        <h3>Submission #<?= $submission['id'] ?></h3>
                        <span class="status-badge" style="background: <?= $statusInfo['color'] ?>;">
                            <?= $statusInfo['badge'] ?>
                        </span>
                        <p>Date: <?= htmlspecialchars($submission['created_at'], ENT_QUOTES) ?></p>
                        <p>
                            Plagiarism:
                            <span class="plagiarism-score" style="color: <?= $plagColor ?>;">
                                <?= $submission['similarity'] ?>%
                            </span>
                        </p>
                        <?php if (!empty($submission['teacher'])): ?>
                            <p>Instructor: <?= htmlspecialchars($submission['teacher'], ENT_QUOTES) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($submission['feedback'])): ?>
                            <div class="feedback-box">
                                <?= nl2br(htmlspecialchars($submission['feedback'], ENT_QUOTES)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($submission['file_path'])): ?>
                            <a href="<?= htmlspecialchars($submission['file_path'], ENT_QUOTES) ?>" download>
                                Download File
                            </a>
                        <?php endif; ?>

                        <?php if ($ctrl->getReportPath($submission['id'])): ?>
                            <a href="download.php?id=<?= $submission['id'] ?>">Download Report</a>
                        <?php endif; ?>

                        <form method="POST" class="delete-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                            <input type="hidden" name="delete_id" value="<?= $submission['id'] ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">No submissions yet.</p>
            <?php endif; ?>
        </section>

        <!-- Notifications Page -->
        <section id="notificationsPage" class="page <?= isActiveView('notifications', $currentView) ?>">
            <h1>🔔 Notifications</h1>
            <p class="page-description">Instructor updates on your submissions</p>

            <?php
            $hasNotifications = false;

            foreach ($submissions as $submission):
                $hasFeedback = !empty($submission['feedback']);
                $isAccepted = $submission['status'] === STATUS_ACCEPTED;
                $isRejected = $submission['status'] === STATUS_REJECTED;

                if (!$hasFeedback && !$isAccepted && !$isRejected) {
                    continue;
                }

                $hasNotifications = true;
                $statusInfo = getStatusBadge($submission['status']);
                $plagColor = getPlagiarismColor($submission['similarity']);
                ?>
                <div class="notification-card" style="border-left-color: <?= $statusInfo['color'] ?>;">
                    <div class="notification-header">
                        <div>
                            <h3><?= $statusInfo['badge'] ?></h3>
                            <p class="notification-meta">
                                Submission #<?= $submission['id'] ?> •
                                <?= date('M j, Y g:i A', strtotime($submission['created_at'])) ?>
                            </p>
                        </div>
                        <?php if (isset($submission['notification_seen']) && $submission['notification_seen'] == 0): ?>
                            <span class="badge-new">NEW</span>
                        <?php endif; ?>
                    </div>

                    <div class="notification-info">
                        <div class="info-item">
                            <p class="info-label">Plagiarism Score</p>
                            <p class="info-value" style="color: <?= $plagColor ?>;">
                                <?= $submission['similarity'] ?>%
                            </p>
                        </div>

                        <?php if (!empty($submission['teacher'])): ?>
                            <div class="info-item">
                                <p class="info-label">Instructor</p>
                                <p class="info-value">
                                    👨‍🏫 <?= htmlspecialchars($submission['teacher'], ENT_QUOTES) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasFeedback): ?>
                        <div class="feedback-section">
                            <strong>💬 Instructor Feedback</strong>
                            <p><?= nl2br(htmlspecialchars($submission['feedback'], ENT_QUOTES)) ?></p>
                            <p class="feedback-author">
                                — <?= htmlspecialchars($submission['teacher'] ?? 'Your Instructor', ENT_QUOTES) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php
                    $reportPath = $ctrl->getReportPath($submission['id']);
                    if ($reportPath && file_exists($reportPath)):
                        ?>
                        <div class="report-actions">
                            <strong>📊 Detailed Report Available</strong>
                            <div class="action-buttons">
                                <a href="view_report.php?id=<?= $submission['id'] ?>" target="_blank" class="btn btn-view">
                                    👁 View Report
                                </a>
                                <a href="download.php?id=<?= $submission['id'] ?>" class="btn btn-download">
                                    📥 Download Report
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$hasNotifications): ?>
                <div class="empty-state-notifications">
                    <div class="empty-icon">🔔</div>
                    <h3>No new notifications</h3>
                    <p>You'll be notified when your instructor reviews your submissions</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Trash Page -->
        <section id="trashPage" class="page <?= isActiveView('trash', $currentView) ?>">
            <h1>Trash</h1>
            <?php if ($deletedSubmissions): ?>
                <?php foreach ($deletedSubmissions as $submission): ?>
                    <div class="history-item deleted">
                        <h3>Submission #<?= $submission['id'] ?></h3>
                        <p>Date: <?= htmlspecialchars($submission['created_at'], ENT_QUOTES) ?></p>
                        <p>Plagiarism: <?= $submission['similarity'] ?>%</p>
                        <?php if (!empty($submission['teacher'])): ?>
                            <p>Instructor: <?= htmlspecialchars($submission['teacher'], ENT_QUOTES) ?></p>
                        <?php endif; ?>
                        <form method="POST" class="restore-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                            <input type="hidden" name="restore_id" value="<?= $submission['id'] ?>">
                            <button type="submit" class="btn-restore">Restore</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-state">Trash is empty.</p>
            <?php endif; ?>
        </section>

        <!-- Chat Page -->
        <!-- Chat Page -->
        <section id="chatPage" class="page <?= isActiveView('chat', $currentView) ?>">
            <h1>💬 Chat with Instructor</h1>

            <div class="chat-instructor-select">
                <label for="chatInstructorSelect">Select Instructor:</label>
                <select id="chatInstructorSelect">
                    <option value="">-- Select Instructor --</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= htmlspecialchars($instructor['id'], ENT_QUOTES) ?>">
                            <?= htmlspecialchars($instructor['name'], ENT_QUOTES) ?>
                            (<?= htmlspecialchars($instructor['email'], ENT_QUOTES) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="chatWindow" class="chat-window">
                <p class="chat-placeholder">Select an instructor to start chatting</p>
            </div>

            <form id="chatForm" class="chat-form">
                <input type="text" id="chatMessage" placeholder="Type your message..." disabled>
                <button type="submit" id="chatSendBtn" disabled>
                    Send 📤
                </button>
            </form>
        </section>
    </main>
    <script>
        window.CSRF_TOKEN = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';
        window.USER_ID = '<?= htmlspecialchars($userId, ENT_QUOTES) ?>';
    </script>
    <script src="/Plagirism_Detection_System/assets/js/student_dashboard.js"></script>
    <script src="/Plagirism_Detection_System/assets/js/chat.js"></script>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');

            Swal.fire({
                title: 'Ready to leave?',
                text: "Your session will be closed securely.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Log Out',
                cancelButtonText: 'Stay',
                reverseButtons: true,
                padding: '2em',
                backdrop: `
                    rgba(15, 23, 42, 0.4)
                    left top
                    no-repeat
                `
            }).then((result) => {
                if (result.isConfirmed) {
                    // thorough cleanup or explicit redirect
                    window.location.href = href;
                }
            });
        });
    </script>
    <script src="/Plagirism_Detection_System/assets/js/chatbot.js"></script>



</body>

</html>
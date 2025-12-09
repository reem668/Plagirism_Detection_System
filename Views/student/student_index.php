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
// Handle DELETE action
if (isset($_POST['delete_id'])) {
    $ctrl->delete($_POST['delete_id'], $userId);
    header("Location: student_index.php");
    exit;
}

// Handle RESTORE action
if (isset($_POST['restore_id'])) {
    $ctrl->restore($_POST['restore_id'], $userId);
    header("Location: student_index.php");
    exit;
}

// Handle SUBMISSION
$submissionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id']) && !isset($_POST['restore_id'])) {
    $_POST['user_id'] = $userId;
    $submissionResult = $ctrl->submit();
}

// Generate CSRF token for forms
$csrfToken = Csrf::token();

// Fetch submissions - only for authenticated user
$submissions = $ctrl->getUserSubmissions($userId, 'active');
$deletedSubmissions = $ctrl->getUserSubmissions($userId, 'deleted');

// Fetch instructors from database using the controller's connection
$instructors = [];
try {
    // Use reflection to access the controller's connection
    $reflection = new ReflectionClass($ctrl);
    $connProperty = $reflection->getProperty('conn');
    $connProperty->setAccessible(true);
    $conn = $connProperty->getValue($ctrl);
    
    if ($conn && is_object($conn) && method_exists($conn, 'query')) {
        $instructorQuery = $conn->query("SELECT id, name, email FROM users WHERE role='instructor' AND status='active' ORDER BY name ASC");
        if ($instructorQuery && $instructorQuery->num_rows > 0) {
            while ($row = $instructorQuery->fetch_assoc()) {
                $instructors[] = $row;
            }
        }
    }
} catch (Exception $e) {
    // Fallback: try direct database connection
    $rootPath = dirname(dirname(_DIR_));
    require_once $rootPath . '/includes/db.php';
    if (isset($conn) && is_object($conn) && method_exists($conn, 'query')) {
        $instructorQuery = $conn->query("SELECT id, name, email FROM users WHERE role='instructor' AND status='active' ORDER BY name ASC");
        if ($instructorQuery && $instructorQuery->num_rows > 0) {
            while ($row = $instructorQuery->fetch_assoc()) {
                $instructors[] = $row;
            }
        }
    }
}


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
        <a href="#" id="notificationsBtn" data-tooltip="Notifications" style="position: relative;">
            🔔
            <?php 
            // Count only submissions with feedback from instructor
                     $notificationCount = 0;
            foreach($submissions as $sub) {
                $hasFeedback = !empty($sub['feedback']);
                $isAccepted = $sub['status'] === 'accepted';
                $isRejected = $sub['status'] === 'rejected';
                
                // Only count if instructor took action
                if ($hasFeedback || $isAccepted || $isRejected) {
                    $notificationCount++;
                }
            }
            if ($notificationCount > 0): ?>
                <span class="notification-badge" style="background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; position: absolute; top: -5px; right: -5px; min-width: 18px; text-align: center; line-height: 14px; font-weight: bold;"><?= $notificationCount ?></span>
            <?php endif; ?>
        </a>
        <a href="#" id="trashBtn" data-tooltip="Trash">🗑</a>
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

                    <label for="instructorSelect">Instructor (Optional - Leave empty for general submission)</label>
                    <select id="instructorSelect" name="instructorSelect">
                        <option value="">-- General Submission (No Instructor) --</option>
                        <?php if (!empty($instructors)): ?>
                            <?php foreach($instructors as $instructor): ?>
                                <option value="<?= htmlspecialchars($instructor['id']) ?>">
                                    <?= htmlspecialchars($instructor['name']) ?> (<?= htmlspecialchars($instructor['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No instructors available</option>
                        <?php endif; ?>
                    </select>
                    <small style="display: block; color: #666; margin-top: 5px;">Select an instructor if you want to submit to a specific instructor, or leave empty for a general submission.</small>

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
        <?php foreach($submissions as $sub): 
            // Determine status badge styling
            $statusBadge = '';
            $statusColor = '#64748b'; // default gray
            
            if ($sub['status'] === 'accepted') {
                $statusBadge = '✅ Accepted';
                $statusColor = '#10b981'; // green
            } elseif ($sub['status'] === 'rejected') {
                $statusBadge = '❌ Rejected';
                $statusColor = '#ef4444'; // red
            } elseif ($sub['status'] === 'pending') {
                $statusBadge = '⏳ Pending Review';
                $statusColor = '#f59e0b'; // orange
            } else {
                $statusBadge = '📝 ' . ucfirst($sub['status']);
            }
            
            // Plagiarism color coding
            $plagColor = $sub['similarity'] > 70 ? '#ef4444' : ($sub['similarity'] > 40 ? '#f59e0b' : '#10b981');
        ?>
            <div class="history-item">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                    <h3>Submission #<?= $sub['id'] ?></h3>
                    <span style="background: <?= $statusColor ?>; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                        <?= $statusBadge ?>
                    </span>
                </div>
                
                <p><strong>📅 Date:</strong> <?= htmlspecialchars($sub['created_at']) ?></p>
                <p><strong>📊 Plagiarism:</strong> 
                    <span style="color: <?= $plagColor ?>; font-weight: bold;">
                        <?= $sub['similarity'] ?>%
                    </span>
                </p>
                
                <?php if(!empty($sub['teacher'])): ?>
                    <p><strong>👨‍🏫 Instructor:</strong> <?= htmlspecialchars($sub['teacher']) ?></p>
                <?php endif; ?>
                
                <?php if(!empty($sub['stored_name'])): ?>
                    <p><strong>📄 File:</strong> <?= htmlspecialchars($sub['stored_name']) ?></p>
                <?php endif; ?>
                
                <?php if(!empty($sub['feedback'])): ?>
                    <div style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #3b82f6;">
                        <strong>📝 Instructor Feedback:</strong>
                        <p style="margin-top: 8px; color: #1e293b;"><?= nl2br(htmlspecialchars($sub['feedback'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($sub['file_path'])): ?>
                    <a href="<?= htmlspecialchars($sub['file_path']) ?>" download class="btn-download" style="display: inline-block; margin-top: 10px; margin-right: 10px;">📥 Download File</a>
                <?php endif; ?>
                
                <?php 
                // Check if report exists
                $reportPath = $ctrl->getReportPath($sub['id']);
                if ($reportPath && file_exists($reportPath)): 
                ?>
                    <a href="download.php?id=<?= $sub['id'] ?>" class="btn-download" style="display: inline-block; margin-top: 10px; margin-right: 10px;">📊 Download Report</a>
                <?php endif; ?>
                
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="delete_id" value="<?= $sub['id'] ?>">
                    <button type="submit" class="btn-delete" onclick="return confirm('Move to trash?')" style="margin-top: 10px;">🗑 Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="empty-message">No submissions yet.</p>
    <?php endif; ?>
</section>

    <<!-- Notifications Page -->
<section id="notificationsPage" class="page">
    <h1>🔔 Notifications</h1>
    <p style="color: #64748b; margin-bottom: 20px;">Instructor updates on your submissions</p>
    
    <?php 
    $hasNotifications = false;
    
    // Filter: ONLY show submissions with instructor actions
    foreach($submissions as $sub): 
        $hasFeedback = !empty($sub['feedback']);
        $isAccepted = $sub['status'] === 'accepted';
        $isRejected = $sub['status'] === 'rejected';
        
        // SKIP if no instructor action (pending or active without feedback)
        if (!$hasFeedback && !$isAccepted && !$isRejected) {
            continue;
        }
        
        $hasNotifications = true;
        
        // Determine notification styling
        $notificationColor = '#3b82f6'; // default blue
        $notificationIcon = '💬';
        $notificationTitle = 'Feedback Received';
        
        if ($isAccepted) {
            $notificationColor = '#10b981'; // green
            $notificationIcon = '✅';
            $notificationTitle = 'Submission Accepted';
        } elseif ($isRejected) {
            $notificationColor = '#ef4444'; // red
            $notificationIcon = '❌';
            $notificationTitle = 'Submission Rejected';
        }
        
        // Plagiarism color
        $plagColor = $sub['similarity'] > 70 ? '#ef4444' : ($sub['similarity'] > 40 ? '#f59e0b' : '#10b981');
    ?>
        <div class="notification-card" style="background: white; border: 1px solid #e2e8f0; border-left: 4px solid <?= $notificationColor ?>; border-radius: 8px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <!-- Notification Header -->
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="font-size: 20px;"><?= $notificationIcon ?></span>
                        <h3 style="margin: 0; color: #1e293b;"><?= $notificationTitle ?></h3>
                    </div>
                    <p style="margin: 0; font-size: 13px; color: #64748b;">Submission #<?= $sub['id'] ?> • <?= date('M j, Y g:i A', strtotime($sub['created_at'])) ?></p>
                </div>
                <span style="background: <?= $notificationColor ?>; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; white-space: nowrap;">
                    NEW
                </span>
            </div>
            
            <!-- Quick Info -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 6px;">
                <div>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Plagiarism Score</p>
                    <p style="margin: 4px 0 0 0; font-size: 18px; font-weight: bold; color: <?= $plagColor ?>;">
                        <?= $sub['similarity'] ?>%
                    </p>
                </div>
                <?php if(!empty($sub['teacher'])): ?>
                <div>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">Instructor</p>
                    <p style="margin: 4px 0 0 0; font-size: 14px; font-weight: 600; color: #1e293b;">
                        👨‍🏫 <?= htmlspecialchars($sub['teacher']) ?>
                    </p>
                </div>
                <?php endif; ?>
                <?php if(!empty($sub['stored_name'])): ?>
                <div>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">File Name</p>
                    <p style="margin: 4px 0 0 0; font-size: 14px; color: #1e293b;">
                        📄 <?= htmlspecialchars($sub['stored_name']) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Status Update -->
            <?php if ($isAccepted || $isRejected): ?>
                <div style="background: <?= $isAccepted ? '#d1fae5' : '#fee2e2' ?>; padding: 14px; border-radius: 6px; margin-bottom: 12px; border-left: 4px solid <?= $isAccepted ? '#10b981' : '#ef4444' ?>;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 18px;"><?= $isAccepted ? '✅' : '❌' ?></span>
                        <strong style="color: #1e293b;">
                            <?= $isAccepted ? 'Submission Accepted!' : 'Submission Rejected' ?>
                        </strong>
                    </div>
                    <p style="margin: 0; color: #1e293b; font-size: 14px;">
                        <?= $isAccepted 
                            ? 'Great work! Your instructor has reviewed and accepted your submission.' 
                            : 'Your submission needs revision. Please review the feedback below.' 
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Instructor Feedback -->
            <?php if($hasFeedback): ?>
                <div style="background: #eff6ff; padding: 14px; border-radius: 6px; margin-bottom: 12px; border-left: 4px solid #3b82f6;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 18px;">💬</span>
                        <strong style="color: #1e293b;">Instructor Feedback</strong>
                    </div>
                    <p style="margin: 0; color: #1e293b; white-space: pre-wrap; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($sub['feedback'])) ?>
                    </p>
                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;">
                        <em>— <?= htmlspecialchars($sub['teacher'] ?? 'Your Instructor') ?></em>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Report Actions -->
            <?php 
            $reportPath = $ctrl->getReportPath($sub['id']);
            if ($reportPath && file_exists($reportPath)): 
            ?>
                <div style="background: #f0fdf4; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                    <strong style="color: #166534;">📊 Detailed Report Available</strong>
                    <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="view_report.php?id=<?= $sub['id'] ?>" target="_blank" style="display: inline-block; padding: 8px 16px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500;">
                            👁 View Report
                        </a>
                        <a href="download.php?id=<?= $sub['id'] ?>" style="display: inline-block; padding: 8px 16px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500;">
                            📥 Download Report
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Submission Preview -->
            <details style="margin-top: 12px;">
                <summary style="cursor: pointer; color: #3b82f6; font-weight: 500; user-select: none; padding: 8px 0;">
                    📝 View Submission Text
                </summary>
                <div style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-top: 8px; max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0;">
                    <p style="margin: 0; color: #4b5563; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">
                        <?= nl2br(htmlspecialchars($sub['text_content'] ?? '')) ?>
                    </p>
                </div>
            </details>
        </div>
    <?php 
    endforeach; 
    
    if (!$hasNotifications): ?>
        <div style="text-align: center; padding: 60px 20px; color: #64748b;">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">🔔</div>
            <h3 style="color: #475569; margin-bottom: 8px; font-size: 20px;">No new notifications</h3>
            <p style="margin: 0; font-size: 15px;">You'll be notified when your instructor reviews your submissions</p>
        </div>
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
                    <?php if(!empty($sub['teacher'])): ?>
                        <p><strong>Instructor:</strong> <?= htmlspecialchars($sub['teacher']) ?></p>
                    <?php endif; ?>
                    
                    <?php if(!empty($sub['feedback'])): ?>
                        <div style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin: 10px 0; border-left: 4px solid #3b82f6;">
                            <strong>📝 Instructor Feedback:</strong>
                            <p style="margin-top: 8px; color: #1e293b;"><?= nl2br(htmlspecialchars($sub['feedback'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
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
    const pages = {home:'mainPage', history:'historyPage', notifications:'notificationsPage', trash:'trashPage'};
    Object.keys(pages).forEach(key => {
        document.getElementById(key+'Btn')?.addEventListener('click', e=>{
            e.preventDefault();
            document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
            document.getElementById(pages[key]).classList.add('active');
        });
    });

    // No auto-selection needed - instructor selection is independent
 });
 </script>

</body>
</html>
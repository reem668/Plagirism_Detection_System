
<?php
/**
 * Protected Instructor Dashboard View
 * This file should only be accessed through Instructordashboard.php
 * Additional security checks included
 */

// Security check - ensure this file is accessed through Instructordashboard.php
if (!defined('INSTRUCTOR_ACCESS')) {
    die('Direct access not permitted. Please access through Instructordashboard.php');
}

// Additional authentication verification
require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Double-check authentication
if (!$session->isLoggedIn() || $session->getUserRole() !== 'instructor') {
    header("Location: /Plagirism_Detection_System/signup.php");
    exit();
}

// Verify the instructor owns the data being viewed
$currentInstructor = $auth->getCurrentUser();
if ($currentInstructor['id'] != $instructor_id) {
    http_response_code(403);
    die('Error: Unauthorized access. You can only view your own dashboard.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Plagiarism Detector</title>
    <!-- Correct CSS path from root assets folder -->
    <link rel="stylesheet" href="/Plagirism_Detection_System/assets/css/Instructor.css">
    <style>
        /* Additional security styles */
        .security-badge {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: #10b981;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .security-badge::before {
            content: "🔒";
        }
    </style>
</head>
<body>
    <!-- Security badge indicator -->
    <div class="security-badge">Secure Session</div>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📚 Similyze</h2>
            <p>Instructor Portal</p>
        </div>
        
        <div class="profile-section">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($instructor['name'] ?? 'IN', 0, 2)); ?>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($instructor['name'] ?? 'Unknown'); ?></h3>
                <p>📧 <?php echo htmlspecialchars($instructor['email'] ?? 'N/A'); ?></p>
                <p>🏫 <?php echo htmlspecialchars($instructor['role'] ?? 'N/A'); ?></p>
                <p>👥 <?php echo $stats['students_enrolled'] ?? 0; ?> Students Enrolled</p>
            </div>
        </div>
        
        <div class="nav-menu">
            <a href="?view=submissions" class="nav-item <?php echo ($current_view ?? 'submissions') === 'submissions' ? 'active' : ''; ?>">
                <span>📋</span> Submissions
            </a>
            <a href="?view=trash" class="nav-item <?php echo ($current_view ?? '') === 'trash' ? 'active' : ''; ?>">
                <span>🗑️</span> Trash
                <?php if (!empty($trash) && count($trash) > 0): ?>
                    <span class="trash-badge"><?php echo count($trash); ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="students-section">
            <h3>👥 Enrolled Students</h3>
            <?php if (empty($enrolled_students)): ?>
                <p style="padding: 10px; color: #aaa;">No students enrolled yet.</p>
            <?php else: ?>
                <?php foreach ($enrolled_students as $student): ?>
                    <div class="student-item">
                        <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                        <p class="student-id"><?php echo htmlspecialchars($student['id']); ?></p>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="signout-section">
            <a href="/Plagirism_Detection_System/logout.php" style="text-decoration: none;">
                <button class="btn-signout">🚪 Sign Out</button>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>📚 Instructor Dashboard</h1>
                <p>Review and manage student submissions</p>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin: 20px 30px;">
                    ✅ <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin: 20px 30px;">
                    ❌ <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Submissions</h3>
                    <p><?php echo count($submissions ?? []); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Review</h3>
                    <p><?php echo count(array_filter($submissions ?? [], fn($s) => $s['status'] === 'pending')); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Accepted</h3>
                    <p><?php echo count(array_filter($submissions ?? [], fn($s) => $s['status'] === 'accepted')); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Rejected</h3>
                    <p><?php echo count(array_filter($submissions ?? [], fn($s) => $s['status'] === 'rejected')); ?></p>
                </div>
            </div>
            
            <div class="submissions">
                <?php if (($current_view ?? 'submissions') === 'trash'): ?>
                    <h2>🗑️ Trash</h2>
                    <?php if (empty($trash)): ?>
                        <div class="no-submissions">
                            <h3>Trash is empty</h3>
                            <p>Deleted submissions will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($trash as $submission): ?>
                            <div class="submission-card">
                                <div class="submission-header">
                                    <div class="student-info">
                                        <h3>
                                            <?php echo htmlspecialchars($submission['student_name']); ?>
                                            <span class="status-badge status-rejected">🗑️ Deleted</span>
                                        </h3>
                                        <p>📧 <?php echo htmlspecialchars($submission['student_email']); ?> | 📅 <?php echo date('F j, Y g:i A', strtotime($submission['created_at'] ?? 'now')); ?></p>
                                    </div>
                                    <div class="plagiarism-badge plagiarism-medium">
                                        <?php echo $submission['similarity'] ?? 0; ?>% Plagiarism
                                    </div>
                                </div>
                                <div class="submission-content">
                                    <h4>📄 Document Title: <?php echo htmlspecialchars($submission['stored_name'] ?? 'N/A'); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars(substr($submission['text_content'] ?? '', 0, 300))); ?><?php echo strlen($submission['text_content'] ?? '') > 300 ? '...' : ''; ?></p>
                                </div>
                                <?php if (!empty($submission['feedback'])): ?>
                                    <div class="feedback-section" style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 10px;">
                                        <strong>Feedback:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="actions" style="margin-top: 15px;">
                                    <form method="POST" action="/Plagirism_Detection_System/instructor_actions.php" style="display: inline;">
                                        <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="btn btn-restore">🔄 Restore</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <h2>Student Submissions</h2>
                    <?php if (empty($submissions)): ?>
                        <div class="no-submissions">
                            <h3>No submissions yet</h3>
                            <p>Student submissions will appear here when they upload their work.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <div class="submission-card">
                                <div class="submission-header">
                                    <div class="student-info">
                                        <h3>
                                            <?php echo htmlspecialchars($submission['student_name']); ?>
                                            <span class="status-badge <?php 
                                                echo match($submission['status'] ?? '') {
                                                    'accepted' => 'status-accepted',
                                                    'rejected' => 'status-rejected',
                                                    default => 'status-pending'
                                                };
                                            ?>">
                                                <?php 
                                                echo match($submission['status'] ?? '') {
                                                    'accepted' => '✓ Accepted',
                                                    'rejected' => '✗ Rejected',
                                                    default => '⏳ Pending'
                                                };
                                                ?>
                                            </span>
                                        </h3>
                                        <p>📧 <?php echo htmlspecialchars($submission['student_email']); ?> | 📅 <?php echo date('F j, Y g:i A', strtotime($submission['created_at'] ?? 'now')); ?></p>
                                    </div>
                                    <div class="plagiarism-badge <?php 
                                        $similarity = $submission['similarity'] ?? 0;
                                        echo $similarity <= 30 ? 'plagiarism-low' : ($similarity <= 70 ? 'plagiarism-medium' : 'plagiarism-high');
                                    ?>">
                                        <?php echo $similarity; ?>% Plagiarism
                                    </div>
                                </div>
                                <div class="submission-content">
                                    <h4>📄 Document Title: <?php echo htmlspecialchars($submission['stored_name'] ?? 'N/A'); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars(substr($submission['text_content'] ?? '', 0, 300))); ?><?php echo strlen($submission['text_content'] ?? '') > 300 ? '...' : ''; ?></p>
                                </div>

                                <?php if (!empty($submission['feedback'])): ?>
                                    <div class="feedback-section" style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 10px; margin-bottom: 15px;">
                                        <strong>Your Feedback:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="actions" style="margin-top: 15px; margin-bottom: 15px;">
                                    <form method="POST" action="/Plagirism_Detection_System/instructor_actions.php" style="display: inline;">
                                        <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="btn btn-accept" <?php echo ($submission['status'] ?? '') === 'accepted' ? 'disabled' : ''; ?>>✓ Accept</button>
                                    </form>

                                    <form method="POST" action="/Plagirism_Detection_System/instructor_actions.php" style="display: inline;">
                                        <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="btn btn-reject" <?php echo ($submission['status'] ?? '') === 'rejected' ? 'disabled' : ''; ?>>✗ Reject</button>
                                    </form>

                                    <form method="POST" action="/Plagirism_Detection_System/instructor_actions.php" style="display: inline;">
                                        <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to move this submission to trash?');">🗑️ Delete</button>
                                    </form>

                                    <a href="/Plagirism_Detection_System/instructor_actions.php?action=view_report&id=<?php echo $submission['id']; ?>" target="_blank" class="btn btn-feedback" style="text-decoration: none; display: inline-block; padding: 10px 20px; background: #0891b2; color: white; border-radius: 6px; border: none; cursor: pointer;">📊 View Report</a>

                                    <a href="/Plagirism_Detection_System/instructor_actions.php?action=download_report&id=<?php echo $submission['id']; ?>" class="btn btn-feedback" style="text-decoration: none; display: inline-block; padding: 10px 20px; background: #7c3aed; color: white; border-radius: 6px; border: none; cursor: pointer;">⬇️ Download Report</a>
                                </div>

                                <div class="feedback-section" style="margin-top: 15px;">
                                    <form method="POST" action="/Plagirism_Detection_System/instructor_actions.php">
                                        <input type="hidden" name="_csrf" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="add_feedback">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <label for="feedback_<?php echo $submission['id']; ?>" style="display: block; margin-bottom: 8px; font-weight: 600;">Add/Update Feedback:</label>
                                        <textarea 
                                            id="feedback_<?php echo $submission['id']; ?>" 
                                            name="feedback" 
                                            class="feedback-textarea" 
                                            placeholder="Enter your feedback for this submission..."
                                            rows="4"
                                        ><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                        <button type="submit" class="btn btn-feedback" style="margin-top: 10px;">💬 Save Feedback</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Security: Auto-logout notification -->
    <script>
        // Warn user before session expires
        const SESSION_TIMEOUT = 3600000; // 1 hour in milliseconds
        const WARNING_TIME = 300000; // 5 minutes before timeout

        setTimeout(() => {
            if (confirm('Your session will expire in 5 minutes. Do you want to continue?')) {
                // Reload page to refresh session
                window.location.reload();
            }
        }, SESSION_TIMEOUT - WARNING_TIME);

        // Auto-logout after session expires
        setTimeout(() => {
            alert('Your session has expired. You will be redirected to login.');
            window.location.href = '/Plagirism_Detection_System/logout.php';
        }, SESSION_TIMEOUT);
    </script>
</body>
</html>
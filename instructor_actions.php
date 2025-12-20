<?php
/**
 * Protected Instructor Actions Handler
 * Handles all instructor actions with authentication and authorization
 *
 * Security Features:
 * - Requires instructor authentication
 * - Verifies instructor owns the submission
 * - CSRF token validation
 * - Input validation and sanitization
 * - Audit logging
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1) Initialize database connection FIRST (same as student/dashboard)
require_once __DIR__ . '/includes/db.php';
if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check your database configuration.");
}

// 2) Load app classes
require_once __DIR__ . '/app/Core/autoload.php';
require_once __DIR__ . '/app/Helpers/SessionManager.php';
require_once __DIR__ . '/app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/app/Controllers/InstructorController.php';
require_once __DIR__ . '/app/Helpers/Csrf.php';
require_once __DIR__ . '/app/Helpers/Validator.php';
require_once __DIR__ . '/app/Models/Instructor.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;
use Helpers\Validator;
use Controllers\InstructorController;
use Models\Instructor;

// Initialize authentication
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// Require instructor role
$auth->requireRole('instructor');

// Get current authenticated instructor
$currentUser   = $auth->getCurrentUser();
$instructor_id = $currentUser['id'];

// Initialize controller with existing connection
$controller = new InstructorController($conn);

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Validate action parameter
if (empty($action)) {
    header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('No action specified'));
    exit;
}

// Log all instructor actions for audit trail
function logInstructorAction($instructor_id, $action, $submission_id, $success) {
    $logFile = __DIR__ . '/storage/logs/instructor_actions.log';
    $logDir  = dirname($logFile);

    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $status    = $success ? 'SUCCESS' : 'FAILED';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $logEntry = "[{$timestamp}] {$status} - Instructor ID: {$instructor_id}, Action: {$action}, Submission ID: {$submission_id}, IP: {$ip}\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// ============================================================
// Handle GET requests (view and download actions)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $submission_id = (int)($_GET['id'] ?? 0);

    // Validate submission ID
    if ($submission_id <= 0) {
        logInstructorAction($instructor_id, $action, $submission_id, false);
        header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Invalid submission ID'));
        exit;
    }

    // Verify instructor owns this submission
    $instructorModel = new Instructor($conn);

    if (!$instructorModel->ownsSubmission($instructor_id, $submission_id)) {
        logInstructorAction($instructor_id, $action, $submission_id, false);
        http_response_code(403);
        die('⛔ Access denied. You can only access submissions assigned to you.');
    }

    switch ($action) {
        case 'view_report':
            try {
                $controller->viewReport($submission_id, $instructor_id);
                logInstructorAction($instructor_id, $action, $submission_id, true);
            } catch (Exception $e) {
                logInstructorAction($instructor_id, $action, $submission_id, false);
                header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Error viewing report: ' . $e->getMessage()));
                exit;
            }
            break;

        case 'download_report':
            try {
                $controller->downloadReport($submission_id, $instructor_id);
                logInstructorAction($instructor_id, $action, $submission_id, true);
            } catch (Exception $e) {
                logInstructorAction($instructor_id, $action, $submission_id, false);
                header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Error downloading report: ' . $e->getMessage()));
                exit;
            }
            break;

        default:
            logInstructorAction($instructor_id, $action, $submission_id, false);
            header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Invalid action'));
            exit;
    }
}

// ============================================================
// Handle POST requests (modify actions)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for all POST requests
    if (!Csrf::verify($_POST['_csrf'] ?? '')) {
        logInstructorAction($instructor_id, $action, 0, false);
        header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Security token validation failed. Please try again.'));
        exit;
    }

    // Get and validate submission ID
    $submission_id = (int)($_POST['submission_id'] ?? 0);

    if ($submission_id <= 0) {
        logInstructorAction($instructor_id, $action, $submission_id, false);
        header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Invalid submission ID'));
        exit;
    }

    // Verify instructor owns this submission
    $instructorModel = new Instructor($conn);

    if (!$instructorModel->ownsSubmission($instructor_id, $submission_id)) {
        logInstructorAction($instructor_id, $action, $submission_id, false);
        http_response_code(403);
        die('⛔ Access denied. You can only modify submissions assigned to you.');
    }

    $success = false;
    $message = '';

    // Process action
    switch ($action) {
        case 'accept':
            $success = $controller->acceptSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission accepted successfully.' : 'Failed to accept submission.';
            logInstructorAction($instructor_id, $action, $submission_id, $success);
            break;

        case 'reject':
            $success = $controller->rejectSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission rejected successfully.' : 'Failed to reject submission.';
            logInstructorAction($instructor_id, $action, $submission_id, $success);
            break;

        case 'delete':
            $success = $controller->deleteSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission moved to trash successfully.' : 'Failed to delete submission.';
            logInstructorAction($instructor_id, $action, $submission_id, $success);
            break;

        case 'add_feedback':
            $feedback = $_POST['feedback'] ?? '';

            if (empty(trim($feedback))) {
                $message = 'Feedback cannot be empty.';
                $success = false;
                logInstructorAction($instructor_id, $action, $submission_id, false);
            } else {
                $feedback = Validator::sanitize($feedback);

                if (strlen($feedback) > 5000) {
                    $message = 'Feedback is too long. Maximum 5000 characters allowed.';
                    $success = false;
                    logInstructorAction($instructor_id, $action, $submission_id, false);
                } else {
                    $success = $controller->addFeedback($submission_id, $instructor_id, $feedback);
                    $message = $success ? 'Feedback saved successfully.' : 'Failed to save feedback.';
                    logInstructorAction($instructor_id, $action, $submission_id, $success);
                }
            }
            break;

        case 'restore':
            $success = $controller->restoreSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission restored successfully.' : 'Failed to restore submission.';
            logInstructorAction($instructor_id, $action, $submission_id, $success);
            break;

        default:
            $message = 'Invalid action specified.';
            $success  = false;
            logInstructorAction($instructor_id, $action, $submission_id, false);
            break;
    }

    // Redirect with appropriate message
    $status      = $success ? 'success' : 'error';
    $redirectUrl = "/Plagirism_Detection_System/Instructordashboard.php?{$status}=" . urlencode($message);

    if (isset($_POST['current_view'])) {
        $redirectUrl .= "&view=" . urlencode($_POST['current_view']);
    }

    header("Location: {$redirectUrl}");
    exit;
}

// If no valid HTTP method matched, redirect to dashboard
logInstructorAction($instructor_id, $action, 0, false);
header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=" . urlencode('Invalid request method'));
exit;

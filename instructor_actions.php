<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Controllers/InstructorController.php';
require_once __DIR__ . '/Helpers/Csrf.php';

use Controllers\InstructorController;
use Helpers\Csrf;

// Verify instructor session
$instructor_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if (!$instructor_id) {
    header("Location: /Plagirism_Detection_System/signup.php");
    exit;
}

// Verify instructor role
if (($_SESSION['user_role'] ?? '') !== 'instructor' && ($_SESSION['user']['role'] ?? '') !== 'instructor') {
    http_response_code(403);
    die('Access denied. Instructor privileges required.');
}

$controller = new InstructorController();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'view_report':
            $submission_id = (int)($_GET['id'] ?? 0);
            if ($submission_id > 0) {
                $controller->viewReport($submission_id, $instructor_id);
            } else {
                header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=invalid_id");
                exit;
            }
            break;

        case 'download_report':
            $submission_id = (int)($_GET['id'] ?? 0);
            if ($submission_id > 0) {
                $controller->downloadReport($submission_id, $instructor_id);
            } else {
                header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=invalid_id");
                exit;
            }
            break;

        default:
            header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=invalid_action");
            exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Csrf::verify($_POST['_csrf'] ?? '')) {
        header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=csrf_invalid");
        exit;
    }

    $submission_id = (int)($_POST['submission_id'] ?? 0);
    if ($submission_id <= 0) {
        header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=invalid_id");
        exit;
    }

    $success = false;
    $message = '';

    switch ($action) {
        case 'accept':
            $success = $controller->acceptSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission accepted successfully.' : 'Failed to accept submission.';
            break;

        case 'reject':
            $success = $controller->rejectSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission rejected successfully.' : 'Failed to reject submission.';
            break;

        case 'delete':
            $success = $controller->deleteSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission moved to trash successfully.' : 'Failed to delete submission.';
            break;

        case 'add_feedback':
            $feedback = $_POST['feedback'] ?? '';
            if (empty(trim($feedback))) {
                $message = 'Feedback cannot be empty.';
            } else {
                $success = $controller->addFeedback($submission_id, $instructor_id, $feedback);
                $message = $success ? 'Feedback added successfully.' : 'Failed to add feedback.';
            }
            break;

        case 'restore':
            $success = $controller->restoreSubmission($submission_id, $instructor_id);
            $message = $success ? 'Submission restored successfully.' : 'Failed to restore submission.';
            break;

        default:
            header("Location: /Plagirism_Detection_System/Instructordashboard.php?error=invalid_action");
            exit;
    }

    // Redirect with message
    $status = $success ? 'success' : 'error';
    header("Location: /Plagirism_Detection_System/Instructordashboard.php?{$status}=" . urlencode($message));
    exit;
}

// If no action matched, redirect to dashboard
header("Location: /Plagirism_Detection_System/Instructordashboard.php");
exit;


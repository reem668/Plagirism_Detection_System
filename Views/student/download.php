<?php
/**
 * Protected File Download Handler
 * Only authenticated students can download their own reports
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../Controllers/SubmissionController.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\SubmissionController;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// CRITICAL: Require student role
$auth->requireRole('student');

// Get current authenticated user
$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];

// Get submission ID
$submissionId = $_GET['id'] ?? null;

if (!$submissionId) {
    http_response_code(400);
    die('Error: Submission ID not provided.');
}

// Initialize controller
$ctrl = new SubmissionController();

// Verify ownership - student can only download their own submissions
if (!$auth->ownsResource($userId)) {
    http_response_code(403);
    die('Error: Unauthorized access. You can only download your own submissions.');
}

// Download the report
$ctrl->downloadReport($submissionId, $userId);
exit();
?>
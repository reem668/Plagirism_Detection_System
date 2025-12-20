<?php
/**
 * Protected Report Viewer for Students
 * Only authenticated students can view their own reports
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

// Initialize controller (it has its own connection)
$ctrl = new SubmissionController();

// Get submission to verify ownership using controller's connection
require_once __DIR__ . '/../../Models/Submission.php';
use Models\Submission;

// Use reflection to get controller's connection
$reflection = new ReflectionClass($ctrl);
$connProperty = $reflection->getProperty('conn');
$connProperty->setAccessible(true);
$conn = $connProperty->getValue($ctrl);

$submissionModel = new Submission($conn);
$submission = $submissionModel->find(intval($submissionId));

if (!$submission || $submission['user_id'] != $userId) {
    http_response_code(403);
    die('Error: Unauthorized access. You can only view your own reports.');
}

// Get report path
$reportPath = $ctrl->getReportPath(intval($submissionId));

if (!$reportPath || !file_exists($reportPath)) {
    http_response_code(404);
    die('Error: Report not found for this submission.');
}

// Security: Validate file path is within reports directory
$realPath = realpath($reportPath);
$rootPath = dirname(dirname(__DIR__));
$reportsDir = realpath($rootPath . '/storage/reports');

if (strpos($realPath, $reportsDir) !== 0) {
    http_response_code(403);
    die('Error: Security violation detected.');
}

// Output the report
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
readfile($reportPath);
exit;
?>


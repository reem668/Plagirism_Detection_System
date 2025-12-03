<?php
/**
 * Protected Results Page
 * Only authenticated students can view their own submission results
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

// Check if submission ID is provided
$submissionId = $_GET['id'] ?? null;
if (!$submissionId) {
    die('Error: Submission ID not specified.');
}

// Initialize controller
$ctrl = new SubmissionController();

// Fetch user's submissions
$submissions = $ctrl->getUserSubmissions($userId);
$submission = null;

// Find the specific submission and verify ownership
foreach ($submissions as $sub) {
    if ($sub['id'] == $submissionId) {
        $submission = $sub;
        break;
    }
}

// Verify ownership
if (!$submission) {
    http_response_code(403);
    die('Error: Submission not found or you do not have permission to view it.');
}

// Additional ownership check
if (!$auth->ownsResource($submission['user_id'])) {
    http_response_code(403);
    die('Error: Unauthorized access. You can only view your own submissions.');
}

// Prepare data for display
$textContent = htmlspecialchars($submission['text_content']);
$plagPercent = $submission['similarity'];
$exact = $submission['exact_match'];
$partial = $submission['partial_match'];

// Optional: Highlight repeated words
$existing = $ctrl->getUserSubmissions($userId, 'active');
$matchingWords = [];
$words = preg_split('/\s+/', strtolower($submission['text_content']));
$totalChunks = max(1, count($words) - 4);

for ($i = 0; $i < $totalChunks; $i++) {
    $chunk = implode(' ', array_slice($words, $i, 5));
    foreach ($existing as $sub) {
        if ($sub['id'] == $submissionId) continue; // skip self
        if (stripos($sub['text_content'], $chunk) !== false) {
            $matchingWords[] = $chunk;
            break;
        }
    }
}

// Highlight matching chunks
foreach ($matchingWords as $chunk) {
    $pattern = '/\b(' . preg_quote($chunk, '/') . ')\b/i';
    $textContent = preg_replace($pattern, '<mark>$1</mark>', $textContent);
}

// Generate HTML report
$reportHtml = "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Submission Report #{$submissionId}</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    mark { background-color: yellow; }
    .summary { margin-bottom: 20px; }
    .download-btn { margin-top: 20px; display:inline-block; padding:10px 15px; background:#28a745; color:#fff; text-decoration:none; border-radius:5px; }
</style>
</head>
<body>
<div class='container'>
    <h1>📄 Submission Report #{$submissionId}</h1>
    
    <div class='summary'>
        <div class='summary-item'>
            <strong>Overall Plagiarism</strong>
            <span style='color: " . ($plagPercent > 70 ? '#f44336' : ($plagPercent > 30 ? '#ff9800' : '#4CAF50')) . ";'>{$plagPercent}%</span>
        </div>
        <div class='summary-item'>
            <strong>Exact Match</strong>
            <span>{$exact}%</span>
        </div>
        <div class='summary-item'>
            <strong>Partial Match</strong>
            <span>{$partial}%</span>
        </div>
    </div>
    
    <h2>📝 Text Analysis with Highlighted Matches</h2>
    <div class='text-section'>
        <p>{$textContent}</p>
    </div>
    
    <div style='margin-top: 30px;'>
        <a class='back-btn' href='student_index.php'>← Back to Dashboard</a>
        <a class='download-btn' href='download.php?id={$submissionId}'>📥 Download Report</a>
    </div>
</div>
</body>
</html>";

// Output report
echo $reportHtml;
?>
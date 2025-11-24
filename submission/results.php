<?php
session_start();

require_once __DIR__ . '/../../Controllers/SubmissionController.php';

use Controllers\SubmissionController;

// Only students can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../signup.php");
    exit();
}

// Check if submission ID is provided
$submissionId = $_GET['id'] ?? null;
if (!$submissionId) {
    die('Submission ID not specified.');
}

$ctrl = new SubmissionController();

// Fetch submission info
$submissions = $ctrl->getUserSubmissions($_SESSION['user_id']);
$submission = null;
foreach ($submissions as $sub) {
    if ($sub['id'] == $submissionId) {
        $submission = $sub;
        break;
    }
}

if (!$submission) {
    die('Submission not found.');
}

// Prepare highlighted report
$textContent = htmlspecialchars($submission['text_content']);
$plagPercent = $submission['similarity'];
$exact = $submission['exact_match'];
$partial = $submission['partial_match'];

// Optional: highlight repeated words
$existing = $ctrl->getUserSubmissions($_SESSION['user_id'], 'active');
$matchingWords = [];
$words = preg_split('/\s+/', strtolower($textContent));
$totalChunks = max(1, count($words) - 4);

for ($i = 0; $i < $totalChunks; $i++) {
    $chunk = implode(' ', array_slice($words, $i, 5));
    foreach ($existing as $sub) {
        if ($sub['id'] == $submissionId) continue; // skip self
        if (strpos(strtolower($sub['text_content']), $chunk) !== false) {
            $matchingWords[] = $chunk;
            break;
        }
    }
}

// Highlight matching chunks
foreach ($matchingWords as $chunk) {
    $textContent = preg_replace('/\b(' . preg_quote($chunk, '/') . ')\b/i', '<mark>$1</mark>', $textContent);
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
<h1>Report for Submission #{$submissionId}</h1>
<div class='summary'>
    <p><strong>Plagiarised:</strong> {$plagPercent}%</p>
    <p><strong>Exact Match:</strong> {$exact}%</p>
    <p><strong>Partial Match:</strong> {$partial}%</p>
</div>
<h2>Text with highlighted matches</h2>
<p>{$textContent}</p>
<a class='download-btn' href='download_report.php?id={$submissionId}'>Download Report</a>
</body>
</html>";

// Output report
echo $reportHtml;

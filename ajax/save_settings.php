<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
require_once __DIR__ . '/../app/Models/Settings.php';

use Helpers\Csrf;
use Models\Settings;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$maxUpload = (int)($_POST['maxUploadSize'] ?? 10);
$threshold = (int)($_POST['plagiarismThreshold'] ?? 50);
$quota     = (int)($_POST['submissionQuota'] ?? 20);

// Validation
if ($threshold < 10 || $threshold > 90) {
    echo json_encode(['success' => false, 'message' => 'Plagiarism threshold must be between 10-90%']);
    exit;
}

if ($maxUpload < 1 || $maxUpload > 1000) {
    echo json_encode(['success' => false, 'message' => 'Max upload size must be between 1-1000 MB']);
    exit;
}

if ($quota < 5 || $quota > 100) {
    echo json_encode(['success' => false, 'message' => 'Submission quota must be between 5-100']);
    exit;
}

$settingsModel = new Settings($conn);
$success       = $settingsModel->updateMultiple([
    'max_upload_size'       => $maxUpload,
    'plagiarism_threshold'  => $threshold,
    'submission_quota'      => $quota,
]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}

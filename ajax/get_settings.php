<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Models/Settings.php';

use Models\Settings;

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$settingsModel = new Settings($conn);
$settings      = $settingsModel->getAll();

echo json_encode([
    'success'  => true,
    'settings' => [
        'maxUploadSize'        => $settings['max_upload_size'] ?? 10,
        'plagiarismThreshold'  => $settings['plagiarism_threshold'] ?? 50,
        'submissionQuota'      => $settings['submission_quota'] ?? 20,
    ],
]);

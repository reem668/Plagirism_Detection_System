<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Controllers/AdminSubmissionController.php';

use Controllers\AdminSubmissionController;

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$controller = new AdminSubmissionController();
$submission = $controller->getSubmissionDetails((int)$id);

if ($submission) {
    echo json_encode(['success' => true, 'submission' => $submission]);
} else {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
}

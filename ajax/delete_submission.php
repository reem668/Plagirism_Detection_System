<?php
session_start();
require_once '../includes/db.php';
require_once '../Helpers/Csrf.php';
require_once '../Controllers/AdminSubmissionController.php';

use Helpers\Csrf;
use Controllers\AdminSubmissionController;

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

$id = $_POST['id'] ?? 0;
if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$controller = new AdminSubmissionController();
$result = $controller->deleteSubmission($id);

echo json_encode($result);
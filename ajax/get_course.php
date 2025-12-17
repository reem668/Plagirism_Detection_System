<?php
/**
 * AJAX Endpoint - Get Single Course
 * Protected: Admin only
 */

require_once dirname(__DIR__) . '/Controllers/CourseController.php';
require_once dirname(__DIR__) . '/Helpers/SessionManager.php';
require_once dirname(__DIR__) . '/Middleware/AuthMiddleware.php';

use Controllers\CourseController;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get course ID
$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

// Initialize controller
$controller = new CourseController();

// Get course
$result = $controller->getCourse($courseId);

echo json_encode($result);


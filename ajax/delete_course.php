<?php
/**
 * AJAX Endpoint - Delete Course
 * Protected: Admin only
 */

require_once dirname(__DIR__) . '/Controllers/CourseController.php';
require_once dirname(__DIR__) . '/Helpers/SessionManager.php';
require_once dirname(__DIR__) . '/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/Helpers/Csrf.php';

use Controllers\CourseController;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

// CSRF validation
if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get course ID
$courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

if ($courseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

// Initialize controller
$controller = new CourseController();

// Delete course
$result = $controller->deleteCourse($courseId);

echo json_encode($result);


<?php
/**
 * AJAX Endpoint - Assign Instructor to Course
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

// Get and prepare data
$data = [
    '_csrf' => $_POST['_csrf'] ?? '',
    'course_id' => $_POST['course_id'] ?? 0,
    'instructor_id' => $_POST['instructor_id'] ?? 0
];

// Initialize controller
$controller = new CourseController();

// Assign instructor
$result = $controller->assignInstructor($data);

echo json_encode($result);


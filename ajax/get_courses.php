<?php
/**
 * AJAX Endpoint - Get All Courses
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

// Get parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$instructorFilter = isset($_GET['instructor_id']) ? trim($_GET['instructor_id']) : '';

// Initialize controller
$controller = new CourseController();

// Get courses
$result = $controller->getCourses($page, $limit, $search, $instructorFilter);

echo json_encode($result);


<?php
/**
 * AJAX Endpoint - Get All Instructors
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

// Initialize controller
$controller = new CourseController();

// Get instructors
$result = $controller->getInstructors();

echo json_encode($result);


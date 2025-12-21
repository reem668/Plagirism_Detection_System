<?php
/**
 * Protected Instructor Dashboard - Main Entry Point
 * This file should only be accessed by authenticated instructors
 * Follows the same pattern as admin dashboard views
 */

// 1) Load DB connection FIRST (defines $conn)
require_once __DIR__ . '/../../../includes/db.php';

// 2) Load autoloader and middleware
require_once __DIR__ . '/../../Core/autoload.php';
require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\InstructorController;
use Helpers\Csrf;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Require instructor role
$auth->requireRole('instructor');

// Security constant (prevents direct access to Instructor.php view)
define('INSTRUCTOR_ACCESS', true);

// Authenticated instructor
$currentUser = $auth->getCurrentUser();
$instructor_id = $currentUser['id'];

// 3) $conn now exists, pass into controller
$controller = new InstructorController($conn);

// Get the current view
$current_view = $_GET['view'] ?? 'submissions';

// Validate view parameter
$allowed_views = ['submissions', 'trash'];
if (!in_array($current_view, $allowed_views, true)) {
    $current_view = 'submissions';
}

// Fetch instructor data
$instructor = $controller->getInstructor($instructor_id);
if (!$instructor) {
    $session->destroy();
    header("Location: /Plagirism_Detection_System/signup.php?error=instructor_not_found");
    exit;
}

// Fetch dashboard data
$stats = $controller->getStats($instructor_id);
$enrolled_students = $controller->getEnrolledStudents($instructor_id);
$submissions = $controller->getSubmissions($instructor_id);
$trash = $controller->getTrash($instructor_id);

// CSRF
require_once __DIR__ . '/../../Helpers/Csrf.php';
$csrf_token = Csrf::token();

// Messages
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// Include the view
require __DIR__ . '/Instructor.php';

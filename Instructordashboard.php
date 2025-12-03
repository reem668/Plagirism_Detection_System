<?php
/**
 * Protected Instructor Dashboard - Main Entry Point
 * Only accessible by authenticated instructors
 */

require_once __DIR__ . '/Helpers/SessionManager.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Controllers/InstructorController.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// CRITICAL: Require instructor role - this blocks unauthorized access
$auth->requireRole('instructor');

// Define security constant - this prevents direct access to view files
define('INSTRUCTOR_ACCESS', true);

// Get current user
$currentUser = $auth->getCurrentUser();
$instructor_id = $currentUser['id'];

// Initialize the instructor controller
$controller = new InstructorController();

// Get the current view
$current_view = $_GET['view'] ?? 'submissions';

// Validate view parameter
$allowed_views = ['submissions', 'trash'];
if (!in_array($current_view, $allowed_views)) {
    $current_view = 'submissions';
}

// Fetch instructor data using helper methods
$instructor = $controller->getInstructor($instructor_id);
if (!$instructor) {
    $session->destroy();
    header("Location: /Plagirism_Detection_System/signup.php?error=instructor_not_found");
    exit;
}

// Fetch dashboard data
$stats = $controller->getStats();
$enrolled_students = $controller->getEnrolledStudents();
$submissions = $controller->getSubmissions($instructor_id);
$trash = $controller->getTrash($instructor_id);

// Generate CSRF token
require_once __DIR__ . '/Helpers/Csrf.php';
use Helpers\Csrf;
$csrf_token = Csrf::token();

// Get messages
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// NOW include the view - with INSTRUCTOR_ACCESS defined
require __DIR__ . '/Views/instructor/Instructor.php';
?>
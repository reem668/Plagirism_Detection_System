<?php
/**
 * Protected Instructor Dashboard - Main Entry Point
 * Only accessible by authenticated instructors
 * 
 * This is the main entry point for all instructor pages
 * All instructor views are protected and can only be accessed through this file
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

// If we reach here, user is authenticated as instructor
$currentUser = $auth->getCurrentUser();
$instructor_id = $currentUser['id'];

// Initialize the instructor controller
$controller = new InstructorController();

// Get the current view (submissions or trash)
$current_view = $_GET['view'] ?? 'submissions';

// Validate view parameter - additional security layer
$allowed_views = ['submissions', 'trash'];
if (!in_array($current_view, $allowed_views)) {
    $current_view = 'submissions';
}

// Fetch instructor data
$instructor = $controller->getInstructor($instructor_id);
if (!$instructor) {
    // Instructor not found in database - possible data integrity issue
    $session->destroy();
    header("Location: /Plagirism_Detection_System/signup.php?error=instructor_not_found");
    exit;
}

// Fetch dashboard data
$stats = $controller->getStats($instructor_id);
$enrolled_students = $controller->getEnrolledStudents($instructor_id);
$stats = [
    'students_enrolled' => count($enrolled_students)
];


$submissions = $controller->getSubmissions($instructor_id);
$trash = $controller->getTrash($instructor_id);

// Generate CSRF token for forms
require_once __DIR__ . '/Helpers/Csrf.php';
use Helpers\Csrf;
$csrf_token = Csrf::token();

// Get success/error messages from query string
$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

// Include the protected view
require __DIR__ . '/Views/instructor/Instructor.php';
?>
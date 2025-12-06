<?php
/**
 * AJAX Endpoint - Edit User
 * Protected: Admin only
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/Helpers/SessionManager.php';
require_once dirname(__DIR__) . '/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/Helpers/Csrf.php';
require_once dirname(__DIR__) . '/Helpers/Validator.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;
use Helpers\Validator;

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

// Get and validate input
$userId = intval($_POST['userId'] ?? 0);
$name = Validator::sanitize($_POST['name'] ?? '');
$email = Validator::sanitize($_POST['email'] ?? '');
$role = Validator::sanitize($_POST['role'] ?? '');
$status = Validator::sanitize($_POST['status'] ?? '');

// Validation
$errors = [];

if ($userId <= 0) {
    $errors[] = 'Invalid user ID';
}

if (strlen($name) < 3) {
    $errors[] = 'Name must be at least 3 characters';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

$allowedRoles = ['student', 'instructor', 'admin'];
if (!in_array($role, $allowedRoles)) {
    $errors[] = 'Invalid role';
}

$allowedStatuses = ['active', 'banned'];
if (!in_array($status, $allowedStatuses)) {
    $errors[] = 'Invalid status';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Check if user exists
$checkStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$checkStmt->bind_param("i", $userId);
$checkStmt->execute();
$result = $checkStmt->get_result();
if ($result->num_rows === 0) {
    $checkStmt->close();
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$oldName = $result->fetch_assoc()['name'];
$checkStmt->close();

// Check if email is taken by another user
$emailCheckStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$emailCheckStmt->bind_param("si", $email, $userId);
$emailCheckStmt->execute();
if ($emailCheckStmt->get_result()->num_rows > 0) {
    $emailCheckStmt->close();
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}
$emailCheckStmt->close();

// Prevent admin from demoting themselves
if ($userId == $session->getUserId() && $role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own role']);
    exit;
}

// Prevent admin from banning themselves
if ($userId == $session->getUserId() && $status === 'banned') {
    echo json_encode(['success' => false, 'message' => 'You cannot ban yourself']);
    exit;
}

// Update user
$stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
$stmt->bind_param("ssssi", $name, $email, $role, $status, $userId);

if ($stmt->execute()) {
    $stmt->close();
    
    // Log action
    $logMsg = "[" . date('Y-m-d H:i:s') . "] Admin {$session->getUserName()} updated user ID {$userId}: {$oldName} -> {$name}, role: {$role}, status: {$status}\n";
    @file_put_contents(dirname(__DIR__) . '/storage/logs/admin_actions.log', $logMsg, FILE_APPEND);
    
    echo json_encode([
        'success' => true, 
        'message' => 'User updated successfully'
    ]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to update user']);
}
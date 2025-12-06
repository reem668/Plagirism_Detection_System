<?php
/**
 * AJAX Endpoint - Add New User
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
$name = Validator::sanitize($_POST['name'] ?? '');
$email = Validator::sanitize($_POST['email'] ?? '');
$role = Validator::sanitize($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
$errors = [];

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

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters';
}

if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$/", $password)) {
    $errors[] = 'Password must contain uppercase, number, and special character';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Check if email already exists
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}
$checkStmt->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
$stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    $newUserId = $stmt->insert_id;
    $stmt->close();
    
    // Log action
    $logMsg = "[" . date('Y-m-d H:i:s') . "] Admin {$session->getUserName()} added user: {$name} ({$email}) as {$role}\n";
    @file_put_contents(dirname(__DIR__) . '/storage/logs/admin_actions.log', $logMsg, FILE_APPEND);
    
    echo json_encode([
        'success' => true, 
        'message' => 'User added successfully',
        'userId' => $newUserId
    ]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to add user']);
}
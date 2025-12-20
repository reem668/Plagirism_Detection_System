<?php
/**
 * AJAX Endpoint - Edit User with Admin Key Support
 * Protected: Admin only
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
require_once __DIR__ . '/../app/Helpers/Validator.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;
use Helpers\Validator;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// $conn from includes/db.php

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
$userId   = (int)($_POST['userId'] ?? 0);
$name     = Validator::sanitize($_POST['name'] ?? '');
$email    = Validator::sanitize($_POST['email'] ?? '');
$role     = Validator::sanitize($_POST['role'] ?? '');
$status   = Validator::sanitize($_POST['status'] ?? '');
$adminKey = isset($_POST['admin_key']) ? Validator::sanitize($_POST['admin_key']) : null;

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
if (!in_array($role, $allowedRoles, true)) {
    $errors[] = 'Invalid role';
}

$allowedStatuses = ['active', 'banned'];
if (!in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Invalid status';
}

// First, load existing user so we know old role, for later checks
$checkStmt = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
$checkStmt->bind_param("i", $userId);
$checkStmt->execute();
$result = $checkStmt->get_result();
if ($result->num_rows === 0) {
    $checkStmt->close();
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$oldData = $result->fetch_assoc();
$checkStmt->close();

// Validate admin key if role is admin and key is provided
if ($role === 'admin' && !empty($adminKey)) {
    if (strlen($adminKey) < 6) {
        $errors[] = 'Admin secret key must be at least 6 characters';
    }
}

// Require admin key when assigning admin role to a non-admin user
if ($role === 'admin' && $oldData['role'] !== 'admin' && empty($adminKey)) {
    $errors[] = 'Admin secret key is required when assigning admin role';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

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
if ($userId === (int)$session->getUserId() && $role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own role']);
    exit;
}

// Prevent admin from banning themselves
if ($userId === (int)$session->getUserId() && $status === 'banned') {
    echo json_encode(['success' => false, 'message' => 'You cannot ban yourself']);
    exit;
}

// Update user - include admin key if role is admin and key is provided
if ($role === 'admin' && !empty($adminKey)) {
    $stmt = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, role = ?, status = ?, admin_key = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $name, $email, $role, $status, $adminKey, $userId);
} elseif ($role === 'admin' && empty($adminKey)) {
    // Keep existing admin key
    $stmt = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, role = ?, status = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssssi", $name, $email, $role, $status, $userId);
} else {
    // Not admin role - clear admin key if it exists
    $nullKey = null;
    $stmt = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, role = ?, status = ?, admin_key = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $name, $email, $role, $status, $nullKey, $userId);
}

if ($stmt->execute()) {
    $stmt->close();

    // Log action
    $logMsg = "[" . date('Y-m-d H:i:s') . "] Admin {$session->getUserName()} updated user ID {$userId}: {$oldData['name']} -> {$name}, role: {$role}, status: {$status}";
    if ($role === 'admin' && !empty($adminKey)) {
        $logMsg .= " (admin key updated)";
    }
    $logMsg .= "\n";

    @file_put_contents(__DIR__ . '/../storage/logs/admin_actions.log', $logMsg, FILE_APPEND);

    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    error_log("Edit user failed: " . $error);
    echo json_encode(['success' => false, 'message' => 'Failed to update user']);
}

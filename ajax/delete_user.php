<?php
/**
 * AJAX Endpoint - Delete User
 * Protected: Admin only
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// $conn comes from includes/db.php

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

// Get user ID
$userId = (int)($_POST['userId'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent admin from deleting themselves
if ($userId === (int)$session->getUserId()) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
    exit;
}

// Get user info before deletion (for logging)
$infoStmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$infoStmt->bind_param("i", $userId);
$infoStmt->execute();
$result = $infoStmt->get_result();

if ($result->num_rows === 0) {
    $infoStmt->close();
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$userInfo = $result->fetch_assoc();
$infoStmt->close();

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();

    // Log action
    $logMsg = sprintf(
        "[%s] Admin %s deleted user ID %d: %s (%s) - Role: %s\n",
        date('Y-m-d H:i:s'),
        $session->getUserName(),
        $userId,
        $userInfo['name'],
        $userInfo['email'],
        $userInfo['role']
    );

    @file_put_contents(__DIR__ . '/../storage/logs/admin_actions.log', $logMsg, FILE_APPEND);

    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully',
    ]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to delete user or user not found']);
}

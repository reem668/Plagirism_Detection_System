<?php
/**
 * AJAX Endpoint - Get Single User Data
 * Protected: Admin only
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// $conn comes from includes/db.php

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get user ID
$userId = (int)($_GET['userId'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'user'    => $user,
]);

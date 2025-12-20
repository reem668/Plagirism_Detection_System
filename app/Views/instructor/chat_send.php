<?php



// app/Views/instructor/chat_send.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// from app/Views/instructor/ to app/Helpers = ../../../app/Helpers
require_once __DIR__ . '/../../../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../app/Helpers/Csrf.php';
// from app/Views/instructor/ to includes = ../../../includes
require_once __DIR__ . '/../../../includes/db.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

// ... rest of your file unchanged ...

header('Content-Type: application/json');

$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// DEBUG: log what we actually receive and what is in session
error_log('INSTR chat_send POST _csrf = ' . ($_POST['_csrf'] ?? 'missing'));
error_log('INSTR chat_send SESSION _csrf = ' . ($_SESSION['_csrf'] ?? 'missing'));

// CSRF verification
if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

// Get current user info
$currentUser = $auth->getCurrentUser();
$senderId    = $currentUser['id'] ?? 0;
$senderRole  = $currentUser['role'] ?? '';

// Determine receiver ID based on sender role
if ($senderRole === 'student') {
    // Student sending to instructor
    $receiverId = intval($_POST['instructor_id'] ?? 0);
} elseif ($senderRole === 'instructor') {
    // Instructor sending to student
    $receiverId = intval($_POST['student_id'] ?? 0);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid user role']);
    exit;
}

// Get and sanitize message
$messageRaw = $_POST['message'] ?? '';
$message    = is_array($messageRaw) ? trim($messageRaw['text'] ?? '') : trim($messageRaw);

// Validate inputs
if (!$senderId || !$receiverId || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Validate message length (optional security measure)
if (strlen($message) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Message too long (max 5000 characters)']);
    exit;
}

try {
    // Insert message into database
    $stmt = $conn->prepare(
        "INSERT INTO chat_messages (sender_id, receiver_id, message, created_at)
         VALUES (?, ?, ?, NOW())"
    );

    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . $conn->error);
    }

    $stmt->bind_param("iis", $senderId, $receiverId, $message);
    $success = $stmt->execute();

    if (!$success) {
        throw new Exception('Failed to send message: ' . $stmt->error);
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);
} catch (Exception $e) {
    error_log("Chat send error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message. Please try again.'
    ]);
}

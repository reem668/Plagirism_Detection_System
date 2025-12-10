<?php
/**
 * Universal Chat Fetch Handler
 * Works for both students and instructors
 * Location: /Views/instructor/chat_fetch.php
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../includes/db.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'messages' => []]);
    exit;
}

// Get current user info
$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'] ?? 0;
$userRole = $currentUser['role'] ?? '';

// Determine the other party ID based on user role
if ($userRole === 'student') {
    // Student viewing chat with instructor
    $otherPartyId = intval($_GET['instructor_id'] ?? 0);
} elseif ($userRole === 'instructor') {
    // Instructor viewing chat with student
    $otherPartyId = intval($_GET['student_id'] ?? 0);
} else {
    echo json_encode(['success' => false, 'messages' => []]);
    exit;
}

// Validate other party ID
if (!$otherPartyId || !$userId) {
    echo json_encode(['success' => false, 'messages' => []]);
    exit;
}

try {
    // Fetch all messages between these two users (both directions)
    $stmt = $conn->prepare("
        SELECT 
            cm.sender_id, 
            cm.message, 
            cm.created_at,
            u.name as sender_name,
            u.role as sender_role
        FROM chat_messages cm
        LEFT JOIN users u ON cm.sender_id = u.id
        WHERE 
            (cm.sender_id = ? AND cm.receiver_id = ?) OR 
            (cm.sender_id = ? AND cm.receiver_id = ?)
        ORDER BY cm.created_at ASC
    ");
    
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iiii", $userId, $otherPartyId, $otherPartyId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Determine if message is from current user or other party
        $isCurrentUser = ($row['sender_id'] == $userId);
        
        $messages[] = [
            'sender' => $isCurrentUser ? $userRole : ($userRole === 'student' ? 'instructor' : 'student'),
            'sender_name' => htmlspecialchars($row['sender_name'] ?? 'Unknown'),
            'message' => htmlspecialchars($row['message']),
            'time' => date('M j, g:i A', strtotime($row['created_at'])),
            'timestamp' => strtotime($row['created_at']),
            'is_mine' => $isCurrentUser
        ];
    }

    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
    
} catch (Exception $e) {
    error_log("Chat fetch error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'messages' => [],
        'error' => 'Failed to load messages'
    ]);
}
?>
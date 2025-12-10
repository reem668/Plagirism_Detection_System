<?php
/**
 * Mark Notifications as Seen
 * Updates notification_seen flag for student's submissions
 * Location: /Views/student/mark_notifications_seen.php
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../Helpers/Csrf.php';
require_once __DIR__ . '/../../includes/db.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

header('Content-Type: application/json');

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Check if user is logged in and is a student
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$currentUser = $auth->getCurrentUser();
if ($currentUser['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// CSRF verification
if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

$userId = $currentUser['id'];

try {
    // Update all submissions for this user to mark notifications as seen
    $stmt = $conn->prepare("
        UPDATE submissions 
        SET notification_seen = 1 
        WHERE user_id = ? 
        AND notification_seen = 0
        AND (
            feedback IS NOT NULL 
            OR status = 'accepted' 
            OR status = 'rejected'
        )
    ");
    
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $success = $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    
    $stmt->close();
    
    echo json_encode([
        'success' => $success,
        'message' => 'Notifications marked as seen',
        'count' => $affectedRows
    ]);
    
} catch (Exception $e) {
    error_log("Mark notifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark notifications as seen'
    ]);
}
?>
<?php
/**
 * Chatbot API Endpoint
 * Handles chatbot interactions with ML-based responses
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../Services/ChatbotService.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];
$userRole = $currentUser['role'];

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

try {
    $result = ChatbotService::getResponse($message, $userId, $userRole, $conn);
    
    echo json_encode([
        'success' => true,
        'message' => $result['response'],
        'intent' => $result['intent'],
        'confidence' => $result['confidence'],
        'suggestions' => $result['suggestions']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'I encountered an error. Please try again later.'
    ]);
}
?>


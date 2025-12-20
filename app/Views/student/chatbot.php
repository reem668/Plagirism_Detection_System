<?php
/**
 * Chatbot API Endpoint
 * Handles chatbot interactions with ML-based responses
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../includes/db.php';

// Check if ChatbotService file exists before requiring
$chatbotServicePath = __DIR__ . '/../../Services/ChatbotService.php';
if (!file_exists($chatbotServicePath)) {
    error_log("ChatbotService not found at: $chatbotServicePath");
    echo json_encode([
        'success' => false,
        'message' => 'Chatbot service not found. Please contact administrator.',
        'error' => 'File not found: ' . $chatbotServicePath
    ]);
    exit;
}
require_once $chatbotServicePath;

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
    if (!isset($conn) || !$conn instanceof mysqli) {
        throw new Exception('Database connection not available');
    }
    
    // Check if ChatbotService class exists
    if (!class_exists('ChatbotService')) {
        throw new Exception('ChatbotService class not found. File may not be loaded correctly.');
    }
    
    $result = ChatbotService::getResponse($message, $userId, $userRole, $conn);
    
    echo json_encode([
        'success' => true,
        'message' => $result['response'],
        'intent' => $result['intent'] ?? null,
        'confidence' => $result['confidence'] ?? 0,
        'suggestions' => $result['suggestions'] ?? []
    ]);
} catch (Exception $e) {
    error_log('Chatbot error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    // Return error details for debugging
    echo json_encode([
        'success' => false,
        'message' => 'I encountered an error. Please try again later.',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>


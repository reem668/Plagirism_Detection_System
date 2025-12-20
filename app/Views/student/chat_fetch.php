<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../app/Models/ChatMessage.php';
require_once __DIR__ . '/../../../app/Controllers/ChatController.php';

use Controllers\ChatController;

header('Content-Type: application/json');

try {
    $controller = new ChatController($conn);
    // student page → JS calls ?instructor_id=...
    $data = $controller->fetchConversation('instructor_id');
    echo json_encode($data);
} catch (Exception $e) {
    error_log('Student chat_fetch error: '.$e->getMessage());
    echo json_encode([
        'success'  => false,
        'messages' => [],
        'error'    => 'Server error'
    ]);
}

<?php
// app/Views/instructor/chat_fetch.php

require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../app/Models/ChatMessage.php';
require_once __DIR__ . '/../../../app/Controllers/ChatController.php';

use Controllers\ChatController;

header('Content-Type: application/json; charset=utf-8');

try {
    // Instructor page: other party is a student → JS must call ?student_id=...
    $controller = new ChatController($conn);

    // This key must match the GET parameter in your JS fetch URL
    $data = $controller->fetchConversation('student_id');

    echo json_encode($data);
} catch (Exception $e) {
    error_log('Instructor chat_fetch error: ' . $e->getMessage());

    echo json_encode([
        'success'  => false,
        'messages' => [],
        'error'    => 'Failed to load messages'
    ]);
}

exit;

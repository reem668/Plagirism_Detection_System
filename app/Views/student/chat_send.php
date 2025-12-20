<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
error_log('STUDENT chat_send POST _csrf = ' . ($_POST['_csrf'] ?? 'missing'));
error_log('STUDENT chat_send SESSION _csrf = ' . ($_SESSION['_csrf'] ?? 'missing'));

require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../app/Helpers/Csrf.php';
require_once __DIR__ . '/../../../app/Models/ChatMessage.php';
require_once __DIR__ . '/../../../app/Controllers/ChatController.php';

use Controllers\ChatController;
use Helpers\Csrf;

header('Content-Type: application/json');

if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    $controller = new ChatController($conn);
    // student page → other party is instructor, key name must match JS
    $data = $controller->sendMessage('instructor_id', 'message');
    echo json_encode($data);
} catch (Exception $e) {
    error_log('Student chat_send error: '.$e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

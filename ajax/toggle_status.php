<?php
/**
 * AJAX Endpoint - Toggle User Status (Ban/Unban)
 */

session_start();
require_once dirname(__DIR__) . '/Controllers/UserController.php';
require_once dirname(__DIR__) . '/Helpers/Csrf.php';

use Controllers\UserController;
use Helpers\Csrf;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify CSRF token
if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Initialize controller
$controller = new UserController();

$userId = intval($_POST['user_id'] ?? 0);

// Toggle status
try {
    $result = $controller->toggleStatus($userId);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error toggling status: ' . $e->getMessage()
    ]);
}
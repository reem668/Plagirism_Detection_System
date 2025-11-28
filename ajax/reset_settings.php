<?php
session_start();
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/Helpers/Csrf.php';
require_once dirname(__DIR__) . '/Models/Settings.php';

use Helpers\Csrf;
use Models\Settings;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!Csrf::verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$settingsModel = new Settings($conn);
$success = $settingsModel->reset();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Settings reset to defaults']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset settings']);
}


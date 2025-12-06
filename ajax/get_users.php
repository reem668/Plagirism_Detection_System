<?php
/**
 * AJAX Endpoint - Get Users with Pagination and Search
 * Protected: Admin only
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/Helpers/SessionManager.php';
require_once dirname(__DIR__) . '/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get parameters
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$sql = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
$params = [];
$types = "";

// Search filter
if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Role filter
if (!empty($role) && $role !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
if (!empty($search)) {
    $countSql .= " AND (name LIKE ? OR email LIKE ?)";
}
if (!empty($role) && $role !== 'all') {
    $countSql .= " AND role = ?";
}

$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Get users with pagination
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// Calculate pagination info
$totalPages = ceil($totalUsers / $perPage);

echo json_encode([
    'success' => true,
    'users' => $users,
    'pagination' => [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalUsers' => $totalUsers,
        'perPage' => $perPage
    ]
]);
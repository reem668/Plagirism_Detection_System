<?php
/**
 * AJAX Endpoint - Get Users with Pagination and Search
 * Protected: Admin only
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../app/Helpers/SessionManager.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

// Authentication check
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// $conn comes from includes/db.php

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get parameters
$search  = trim($_GET['search'] ?? '');
$role    = trim($_GET['role'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Build query
$sql    = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
$params = [];
$types  = "";

// Search filter
if ($search !== '') {
    $sql        .= " AND (name LIKE ? OR email LIKE ?)";
    $searchTerm  = "%{$search}%";
    $params[]    = $searchTerm;
    $params[]    = $searchTerm;
    $types      .= "ss";
}

// Role filter
if ($role !== '' && $role !== 'all') {
    $sql     .= " AND role = ?";
    $params[] = $role;
    $types   .= "s";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) AS total FROM users WHERE 1=1";
$countParams = [];
$countTypes  = "";

// reuse same filters for count
if ($search !== '') {
    $countSql    .= " AND (name LIKE ? OR email LIKE ?)";
    $searchTerm   = "%{$search}%";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countTypes   .= "ss";
}

if ($role !== '' && $role !== 'all') {
    $countSql     .= " AND role = ?";
    $countParams[] = $role;
    $countTypes   .= "s";
}

$countStmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$countStmt->close();

// Get users with pagination
$sql     .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types   .= "ii";

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
$totalPages = $perPage > 0 ? (int)ceil($totalUsers / $perPage) : 0;

echo json_encode([
    'success'    => true,
    'users'      => $users,
    'pagination' => [
        'currentPage' => $page,
        'totalPages'  => $totalPages,
        'totalUsers'  => $totalUsers,
        'perPage'     => $perPage,
    ],
]);

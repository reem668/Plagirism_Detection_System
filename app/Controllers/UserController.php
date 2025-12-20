<?php
namespace Controllers;

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/SessionManager.php';
require_once __DIR__ . '/../../includes/db.php';

use Helpers\Csrf;
use Helpers\Validator;
use Helpers\SessionManager;

/**
 * UserController - Handles all user management operations
 * Used by admin panel for CRUD operations on users
 */
class UserController
{
    protected $conn;
    protected $session;

    public function __construct($testConnection = null)
    {
        $this->session = SessionManager::getInstance();

        if ($testConnection !== null) {
            // Use provided test connection (for tests)
            $this->conn = $testConnection;
        } else {
            // $conn is defined in includes/db.php at project root
            global $conn;
            if (!$conn || $conn->connect_error) {
                die('DB connection failed: ' . ($conn ? $conn->connect_error : 'no connection'));
            }
            $this->conn = $conn;
        }
    }

    /**
     * Require admin authentication
     */
    protected function requireAdmin()
    {
        if (!$this->session->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
            throw new \Exception('Authentication required');
        }

        if ($this->session->getUserRole() !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - Admin role required']);
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
            throw new \Exception('Unauthorized access');
        }
    }

    /**
     * Get all users with pagination, search, and filtering
     */
    public function getUsers($page = 1, $limit = 10, $search = '', $roleFilter = '')
    {
        $this->requireAdmin();

        $offset = ($page - 1) * $limit;

        $sql      = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
        $countSql = "SELECT COUNT(*) AS total FROM users WHERE 1=1";

        $params = [];
        $types  = "";

        // Search filter
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $sql      .= " AND (name LIKE ? OR email LIKE ?)";
            $countSql .= " AND (name LIKE ? OR email LIKE ?)";
            $params[]  = $searchTerm;
            $params[]  = $searchTerm;
            $types    .= "ss";
        }

        // Role filter
        if (!empty($roleFilter) && in_array($roleFilter, ['student', 'instructor', 'admin'], true)) {
            $sql      .= " AND role = ?";
            $countSql .= " AND role = ?";
            $params[]  = $roleFilter;
            $types    .= "s";
        }

        // Get total count
        $countStmt = $this->conn->prepare($countSql);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $total       = $totalResult->fetch_assoc()['total'] ?? 0;
        $countStmt->close();

        // Paginated results
        $sql     .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types   .= "ii";

        $stmt = $this->conn->prepare($sql);
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

        return [
            'success'    => true,
            'users'      => $users,
            'pagination' => [
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => $limit > 0 ? (int)ceil($total / $limit) : 0,
            ],
        ];
    }

    /**
     * Add new user
     */
    public function addUser($data)
    {
        $this->requireAdmin();

        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        $name  = Validator::sanitize($data['name'] ?? '');
        $email = Validator::sanitize($data['email'] ?? '');
        $role  = Validator::sanitize($data['role'] ?? '');

        if (empty($name) || empty($email) || empty($role)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (!in_array($role, ['student', 'instructor', 'admin'], true)) {
            return ['success' => false, 'message' => 'Invalid role selected'];
        }

        // Email uniqueness
        $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Email already exists'];
        }
        $checkStmt->close();

        $status    = 'active';
        $password  = password_hash('Welcome123!', PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $name, $email, $password, $role, $status, $createdAt);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();

            $this->logAction('add_user', $userId, "Added user: {$name} ({$email})");

            return [
                'success' => true,
                'message' => 'User added successfully. Default password: Welcome123!',
                'user_id' => $userId,
            ];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Failed to add user'];
    }

    /**
     * Edit user
     */
    public function editUser($data)
    {
        $this->requireAdmin();

        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        $userId = (int)($data['user_id'] ?? 0);
        $name   = Validator::sanitize($data['name'] ?? '');
        $email  = Validator::sanitize($data['email'] ?? '');
        $role   = Validator::sanitize($data['role'] ?? '');
        $status = Validator::sanitize($data['status'] ?? '');

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }

        if (empty($name) || empty($email) || empty($role) || empty($status)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (!in_array($role, ['student', 'instructor', 'admin'], true)) {
            return ['success' => false, 'message' => 'Invalid role'];
        }

        if (!in_array($status, ['active', 'banned'], true)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        // Email uniqueness for others
        $checkStmt = $this->conn->prepare(
            "SELECT id FROM users WHERE email = ? AND id != ?"
        );
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Email already used by another user'];
        }
        $checkStmt->close();

        // Prevent self‑demotion
        $currentUserId = $this->session->getUserId();
        if ($userId === (int)$currentUserId && $role !== 'admin') {
            return ['success' => false, 'message' => 'You cannot change your own role'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?"
        );
        $stmt->bind_param("ssssi", $name, $email, $role, $status, $userId);

        if ($stmt->execute()) {
            $stmt->close();

            $this->logAction('edit_user', $userId, "Edited user: {$name} ({$email})");

            return ['success' => true, 'message' => 'User updated successfully'];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Failed to update user'];
    }

    /**
     * Delete user
     */
    public function deleteUser($userId)
    {
        $this->requireAdmin();

        $userId = (int)$userId;

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }

        $currentUserId = $this->session->getUserId();
        if ($userId === (int)$currentUserId) {
            return ['success' => false, 'message' => 'You cannot delete your own account'];
        }

        // Get user info before deletion
        $stmt = $this->conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $deleteStmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param("i", $userId);

        if ($deleteStmt->execute()) {
            $deleteStmt->close();

            $this->logAction(
                'delete_user',
                $userId,
                "Deleted user: {$user['name']} ({$user['email']})"
            );

            return ['success' => true, 'message' => 'User deleted successfully'];
        }

        $deleteStmt->close();
        return ['success' => false, 'message' => 'Failed to delete user'];
    }

    /**
     * Toggle user status (ban/unban)
     */
    public function toggleStatus($userId)
    {
        $this->requireAdmin();

        $userId = (int)$userId;

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }

        $currentUserId = $this->session->getUserId();
        if ($userId === (int)$currentUserId) {
            return ['success' => false, 'message' => 'You cannot ban/unban yourself'];
        }

        $stmt = $this->conn->prepare("SELECT status, name FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $newStatus = ($user['status'] === 'active') ? 'banned' : 'active';

        $updateStmt = $this->conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $userId);

        if ($updateStmt->execute()) {
            $updateStmt->close();

            $action = ($newStatus === 'banned') ? 'banned' : 'unbanned';
            $this->logAction('toggle_status', $userId, "User {$action}: {$user['name']}");

            return [
                'success'    => true,
                'message'    => "User {$action} successfully",
                'new_status' => $newStatus,
            ];
        }

        $updateStmt->close();
        return ['success' => false, 'message' => 'Failed to update status'];
    }

    /**
     * Get user statistics
     */
    public function getStatistics()
    {
        $this->requireAdmin();

        $stats = [
            'by_role'   => [],
            'by_status' => [],
        ];

        // Total users
        $result          = $this->conn->query("SELECT COUNT(*) AS total FROM users");
        $stats['total']  = $result->fetch_assoc()['total'] ?? 0;

        // By role
        $result = $this->conn->query("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
        while ($row = $result->fetch_assoc()) {
            $stats['by_role'][$row['role']] = $row['count'];
        }

        // By status
        $result = $this->conn->query("SELECT status, COUNT(*) AS count FROM users GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }

        return ['success' => true, 'stats' => $stats];
    }

    /**
     * Log admin action for audit trail
     */
    private function logAction($action, $targetUserId, $description)
    {
        // project root is one level above app/
        $rootPath = dirname(__DIR__, 1);
        $logFile  = $rootPath . '/storage/logs/admin_actions.log';
        $logDir   = dirname($logFile);

        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $adminId   = $this->session->getUserId();
        $adminName = $this->session->getUserName();
        $timestamp = date('Y-m-d H:i:s');
        $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $logEntry = "[{$timestamp}] Admin: {$adminName} (ID: {$adminId}) | Action: {$action} | Target User ID: {$targetUserId} | Description: {$description} | IP: {$ip}\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

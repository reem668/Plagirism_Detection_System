<?php
namespace Controllers;

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/SessionManager.php';

use Helpers\Csrf;
use Helpers\Validator;
use Helpers\SessionManager;

/**
 * UserController - Handles all user management operations
 * Used by admin panel for CRUD operations on users
 */
class UserController {
    protected $conn;
    protected $session;

    public function __construct($testConnection = null) {
        $this->session = SessionManager::getInstance();
        
        if ($testConnection !== null) {
            // Use provided test connection
            $this->conn = $testConnection;
        } else {
            // Initialize database connection for production
            $rootPath = dirname(__DIR__);
            require $rootPath . '/includes/db.php';
            $this->conn = $conn;
            
            if ($this->conn->connect_error) {
                die("DB connection failed: " . $this->conn->connect_error);
            }
        }
    }

    /**
     * Require admin authentication
     */
    protected function requireAdmin() {
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
    public function getUsers($page = 1, $limit = 10, $search = '', $roleFilter = '') {
        $this->requireAdmin();

        $offset = ($page - 1) * $limit;
        
        // Build query
        $sql = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        
        $params = [];
        $types = "";

        // Search filter
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $sql .= " AND (name LIKE ? OR email LIKE ?)";
            $countSql .= " AND (name LIKE ? OR email LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        // Role filter
        if (!empty($roleFilter) && in_array($roleFilter, ['student', 'instructor', 'admin'])) {
            $sql .= " AND role = ?";
            $countSql .= " AND role = ?";
            $params[] = $roleFilter;
            $types .= "s";
        }

        // Get total count
        $countStmt = $this->conn->prepare($countSql);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        $countStmt->close();

        // Get paginated results
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

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
            'success' => true,
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Add new user
     */
    public function addUser($data) {
        $this->requireAdmin();

        // Validate CSRF
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        // Validate input
        $name = Validator::sanitize($data['name'] ?? '');
        $email = Validator::sanitize($data['email'] ?? '');
        $role = Validator::sanitize($data['role'] ?? '');

        if (empty($name) || empty($email) || empty($role)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Validate role
        if (!in_array($role, ['student', 'instructor', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role selected'];
        }

        // Check if email already exists
        $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Email already exists'];
        }
        $checkStmt->close();

        // Insert user
        $status = 'active';
        $password = password_hash('Welcome123!', PASSWORD_DEFAULT); // Default password
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $name, $email, $password, $role, $status, $createdAt);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();
            
            // Log action
            $this->logAction('add_user', $userId, "Added user: {$name} ({$email})");
            
            return [
                'success' => true,
                'message' => 'User added successfully. Default password: Welcome123!',
                'user_id' => $userId
            ];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Failed to add user'];
        }
    }

    /**
     * Edit user
     */
    public function editUser($data) {
        $this->requireAdmin();

        // Validate CSRF
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        $userId = intval($data['user_id'] ?? 0);
        $name = Validator::sanitize($data['name'] ?? '');
        $email = Validator::sanitize($data['email'] ?? '');
        $role = Validator::sanitize($data['role'] ?? '');
        $status = Validator::sanitize($data['status'] ?? '');

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }

        if (empty($name) || empty($email) || empty($role) || empty($status)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Validate role
        if (!in_array($role, ['student', 'instructor', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role'];
        }

        // Validate status
        if (!in_array($status, ['active', 'banned'])) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        // Check if email exists for another user
        $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'Email already used by another user'];
        }
        $checkStmt->close();

        // Prevent admin from demoting themselves
        $currentUserId = $this->session->getUserId();
        if ($userId == $currentUserId && $role !== 'admin') {
            return ['success' => false, 'message' => 'You cannot change your own role'];
        }

        // Update user
        $stmt = $this->conn->prepare(
            "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?"
        );
        $stmt->bind_param("ssssi", $name, $email, $role, $status, $userId);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log action
            $this->logAction('edit_user', $userId, "Edited user: {$name} ({$email})");
            
            return ['success' => true, 'message' => 'User updated successfully'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Failed to update user'];
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($userId) {
        $this->requireAdmin();

        $userId = intval($userId);
        
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }

        // Prevent admin from deleting themselves
        $currentUserId = $this->session->getUserId();
        if ($userId == $currentUserId) {
            return ['success' => false, 'message' => 'You cannot delete your own account'];
        }

        // Get user info before deletion
        $stmt = $this->conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Delete user
        $deleteStmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param("i", $userId);
        
        if ($deleteStmt->execute()) {
            $deleteStmt->close();
            
            // Log action
            $this->logAction('delete_user', $userId, "Deleted user: {$user['name']} ({$user['email']})");
            
            return ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            $deleteStmt->close();
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }

    /**
     * Toggle user status (ban/unban)
     */
    public function toggleStatus($userId) {
        $this->requireAdmin();

        $userId = intval($userId);
        
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }

        // Prevent admin from banning themselves
        $currentUserId = $this->session->getUserId();
        if ($userId == $currentUserId) {
            return ['success' => false, 'message' => 'You cannot ban/unban yourself'];
        }

        // Get current status
        $stmt = $this->conn->prepare("SELECT status, name FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Toggle status
        $newStatus = ($user['status'] === 'active') ? 'banned' : 'active';
        
        $updateStmt = $this->conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $userId);
        
        if ($updateStmt->execute()) {
            $updateStmt->close();
            
            // Log action
            $action = ($newStatus === 'banned') ? 'banned' : 'unbanned';
            $this->logAction('toggle_status', $userId, "User {$action}: {$user['name']}");
            
            return [
                'success' => true,
                'message' => "User {$action} successfully",
                'new_status' => $newStatus
            ];
        } else {
            $updateStmt->close();
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }

    /**
     * Get user statistics
     */
    public function getStatistics() {
        $this->requireAdmin();

        $stats = [];

        // Total users
        $result = $this->conn->query("SELECT COUNT(*) as total FROM users");
        $stats['total'] = $result->fetch_assoc()['total'];

        // By role
        $result = $this->conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        while ($row = $result->fetch_assoc()) {
            $stats['by_role'][$row['role']] = $row['count'];
        }

        // By status
        $result = $this->conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }

        return ['success' => true, 'stats' => $stats];
    }

    /**
     * Log admin action for audit trail
     */
    private function logAction($action, $targetUserId, $description) {
        $logFile = dirname(__DIR__) . '/storage/logs/admin_actions.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $adminId = $this->session->getUserId();
        $adminName = $this->session->getUserName();
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logEntry = "[{$timestamp}] Admin: {$adminName} (ID: {$adminId}) | Action: {$action} | Target User ID: {$targetUserId} | Description: {$description} | IP: {$ip}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

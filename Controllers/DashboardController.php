<?php
namespace Controllers;

require_once __DIR__ . '/../Helpers/SessionManager.php';
require_once __DIR__ . '/../includes/db.php';

use Helpers\SessionManager;

/**
 * DashboardController
 * Handles admin dashboard statistics and data
 */
class DashboardController {
    protected $conn;
    protected $session;

    public function __construct($testConnection = null) {
        $this->session = SessionManager::getInstance();
        
        if ($testConnection !== null) {
            $this->conn = $testConnection;
        } else {
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
            throw new \Exception('Authentication required');
        }
        
        if ($this->session->getUserRole() !== 'admin') {
            http_response_code(403);
            throw new \Exception('Unauthorized access - Admin role required');
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics() {
        $this->requireAdmin();
        
        $stats = [];
        
        // Total Users
        $result = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $stats['totalUsers'] = (int)$result->fetch_assoc()['count'];
        
        // Total Submissions
        $result = $this->conn->query("SELECT COUNT(*) as count FROM submissions WHERE status != 'deleted'");
        $stats['totalSubmissions'] = (int)$result->fetch_assoc()['count'];
        
        // Total Courses
        $result = $this->conn->query("SELECT COUNT(*) as count FROM courses");
        $stats['totalCourses'] = (int)$result->fetch_assoc()['count'];
        
        // High-Risk Submissions (similarity > 70)
        $result = $this->conn->query("SELECT COUNT(*) as count FROM submissions WHERE similarity > 70 AND status != 'deleted'");
        $stats['highRiskCount'] = (int)$result->fetch_assoc()['count'];
        
        // User Distribution (by role)
        $result = $this->conn->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ");
        $stats['userDistribution'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['userDistribution'][$row['role']] = (int)$row['count'];
        }
        
        // Similarity Score Distribution
        $result = $this->conn->query("
            SELECT 
                CASE 
                    WHEN similarity <= 30 THEN 'Low (0-30%)'
                    WHEN similarity <= 70 THEN 'Medium (31-70%)'
                    ELSE 'High (71-100%)'
                END as category,
                COUNT(*) as count
            FROM submissions
            WHERE similarity IS NOT NULL AND status != 'deleted'
            GROUP BY category
        ");
        $stats['similarityDistribution'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['similarityDistribution'][$row['category']] = (int)$row['count'];
        }
        
        // Course Activity (submissions per course)
        $result = $this->conn->query("
            SELECT 
                c.name as course_name,
                COUNT(s.id) as submission_count
            FROM courses c
            LEFT JOIN submissions s ON c.id = s.course_id AND s.status != 'deleted'
            GROUP BY c.id, c.name
            ORDER BY submission_count DESC
            LIMIT 10
        ");
        $stats['courseActivity'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['courseActivity'][] = [
                'name' => $row['course_name'],
                'count' => (int)$row['submission_count']
            ];
        }
        
        // Recent Submissions (last 10)
        $result = $this->conn->query("
            SELECT 
                s.id,
                s.similarity,
                s.status,
                s.created_at,
                u.name as student_name,
                u.email as student_email,
                c.name as course_name
            FROM submissions s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE s.status != 'deleted'
            ORDER BY s.created_at DESC
            LIMIT 10
        ");
        $stats['recentSubmissions'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['recentSubmissions'][] = [
                'id' => (int)$row['id'],
                'student_name' => $row['student_name'],
                'student_email' => $row['student_email'],
                'course_name' => $row['course_name'] ?? 'General Submission',
                'similarity' => (int)$row['similarity'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }
        
        return $stats;
    }
}


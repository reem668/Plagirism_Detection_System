<?php
namespace Controllers;

require_once __DIR__ . '/../Repositories/CourseRepository.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/SessionManager.php';
require_once __DIR__ . '/../Helpers/ResponseFactory.php';

use Repositories\CourseRepository;
use Helpers\Csrf;
use Helpers\Validator;
use Helpers\SessionManager;
use Helpers\ResponseFactory;

/**
 * CourseController - Handles all course management operations
 * Used by admin panel for CRUD operations on courses
 * Implements MVC architecture with Repository and Factory patterns
 */
class CourseController {
    protected $conn;
    protected $session;
    protected $repository;
    
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
        
        // Initialize repository
        $this->repository = new CourseRepository($this->conn);
    }
    
    /**
     * Require admin authentication
     */
    protected function requireAdmin() {
        if (!$this->session->isLoggedIn()) {
            http_response_code(401);
            $response = ResponseFactory::error('Authentication required', 401);
            if (!defined('PHPUNIT_RUNNING')) {
                ResponseFactory::json($response);
            }
            throw new \Exception('Authentication required');
        }
        
        if ($this->session->getUserRole() !== 'admin') {
            http_response_code(403);
            $response = ResponseFactory::error('Unauthorized access - Admin role required', 403);
            if (!defined('PHPUNIT_RUNNING')) {
                ResponseFactory::json($response);
            }
            throw new \Exception('Unauthorized access');
        }
    }
    
    /**
     * Get all courses with pagination, search, and filtering
     */
    public function getCourses($page = 1, $limit = 10, $search = '', $instructorFilter = '') {
        $this->requireAdmin();
        
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($instructorFilter)) {
            $filters['instructor_id'] = intval($instructorFilter);
        }
        
        $courses = $this->repository->findAll($filters);
        $total = $this->repository->count($filters);
        
        // Apply pagination
        $offset = ($page - 1) * $limit;
        $paginatedCourses = array_slice($courses, $offset, $limit);
        
        return ResponseFactory::paginated($paginatedCourses, [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ]);
    }
    
    /**
     * Get single course by ID
     */
    public function getCourse($id) {
        $this->requireAdmin();
        
        $id = intval($id);
        if ($id <= 0) {
            return ResponseFactory::error('Invalid course ID', 400);
        }
        
        $course = $this->repository->findByIdWithInstructor($id);
        
        if (!$course) {
            return ResponseFactory::error('Course not found', 404);
        }
        
        return ResponseFactory::success('Course retrieved successfully', $course);
    }
    
    /**
     * Add new course
     */
    public function addCourse($data) {
        $this->requireAdmin();
        
        // Validate CSRF
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ResponseFactory::error('Invalid CSRF token', 403);
        }
        
        // Validate and sanitize input
        $name = Validator::sanitize($data['name'] ?? '');
        $description = Validator::sanitize($data['description'] ?? '');
        $instructor_id = intval($data['instructor_id'] ?? 0);
        
        // Validation
        if (empty($name)) {
            return ResponseFactory::error('Course name is required', 400);
        }
        
        if (strlen($name) < 3) {
            return ResponseFactory::error('Course name must be at least 3 characters', 400);
        }
        
        if ($instructor_id <= 0) {
            return ResponseFactory::error('Valid instructor is required', 400);
        }
        
        // Check if instructor exists and is actually an instructor
        $instructorStmt = $this->conn->prepare("SELECT id, role FROM users WHERE id = ? AND role = 'instructor'");
        $instructorStmt->bind_param("i", $instructor_id);
        $instructorStmt->execute();
        $instructorResult = $instructorStmt->get_result();
        
        if ($instructorResult->num_rows === 0) {
            $instructorStmt->close();
            return ResponseFactory::error('Invalid instructor selected', 400);
        }
        $instructorStmt->close();
        
        // Check if course name already exists
        if ($this->repository->nameExists($name)) {
            return ResponseFactory::error('Course name already exists', 400);
        }
        
        // Create course
        $courseData = [
            'name' => $name,
            'description' => $description,
            'instructor_id' => $instructor_id
        ];
        
        $course = $this->repository->create($courseData);
        
        if ($course) {
            // Log action
            $this->logAction('add_course', $course['id'], "Added course: {$name}");
            
            return ResponseFactory::success('Course added successfully', $course);
        } else {
            return ResponseFactory::error('Failed to add course', 500);
        }
    }
    
    /**
     * Edit course
     */
    public function editCourse($data) {
        $this->requireAdmin();
        
        // Validate CSRF
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ResponseFactory::error('Invalid CSRF token', 403);
        }
        
        $courseId = intval($data['course_id'] ?? 0);
        $name = Validator::sanitize($data['name'] ?? '');
        $description = Validator::sanitize($data['description'] ?? '');
        $instructor_id = intval($data['instructor_id'] ?? 0);
        
        if ($courseId <= 0) {
            return ResponseFactory::error('Invalid course ID', 400);
        }
        
        if (empty($name)) {
            return ResponseFactory::error('Course name is required', 400);
        }
        
        if (strlen($name) < 3) {
            return ResponseFactory::error('Course name must be at least 3 characters', 400);
        }
        
        if ($instructor_id <= 0) {
            return ResponseFactory::error('Valid instructor is required', 400);
        }
        
        // Check if course exists
        $existingCourse = $this->repository->findById($courseId);
        if (!$existingCourse) {
            return ResponseFactory::error('Course not found', 404);
        }
        
        // Check if instructor exists and is actually an instructor
        $instructorStmt = $this->conn->prepare("SELECT id, role FROM users WHERE id = ? AND role = 'instructor'");
        $instructorStmt->bind_param("i", $instructor_id);
        $instructorStmt->execute();
        $instructorResult = $instructorStmt->get_result();
        
        if ($instructorResult->num_rows === 0) {
            $instructorStmt->close();
            return ResponseFactory::error('Invalid instructor selected', 400);
        }
        $instructorStmt->close();
        
        // Check if course name already exists (excluding current course)
        if ($this->repository->nameExists($name, $courseId)) {
            return ResponseFactory::error('Course name already exists', 400);
        }
        
        // Update course
        $courseData = [
            'name' => $name,
            'description' => $description,
            'instructor_id' => $instructor_id
        ];
        
        if ($this->repository->update($courseId, $courseData)) {
            // Log action
            $this->logAction('edit_course', $courseId, "Edited course: {$name}");
            
            return ResponseFactory::success('Course updated successfully');
        } else {
            return ResponseFactory::error('Failed to update course', 500);
        }
    }
    
    /**
     * Delete course
     */
    public function deleteCourse($courseId) {
        $this->requireAdmin();
        
        $courseId = intval($courseId);
        
        if ($courseId <= 0) {
            return ResponseFactory::error('Invalid course ID', 400);
        }
        
        // Get course info before deletion
        $course = $this->repository->findById($courseId);
        
        if (!$course) {
            return ResponseFactory::error('Course not found', 404);
        }
        
        // Note: Database CASCADE DELETE will automatically delete submissions
        // No need to check or manually delete submissions
        
        // Delete course (CASCADE DELETE will handle submissions automatically)
        if ($this->repository->delete($courseId)) {
            // Log action
            $this->logAction('delete_course', $courseId, "Deleted course: {$course['name']}");
            
            return ResponseFactory::success('Course deleted successfully');
        } else {
            return ResponseFactory::error('Failed to delete course', 500);
        }
    }
    
    /**
     * Get all instructors (for dropdown/selection)
     */
    public function getInstructors() {
        $this->requireAdmin();
        
        $stmt = $this->conn->prepare("SELECT id, name, email FROM users WHERE role = 'instructor' AND status = 'active' ORDER BY name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $instructors = [];
        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
        $stmt->close();
        
        return ResponseFactory::success('Instructors retrieved successfully', $instructors);
    }
    
    /**
     * Assign instructor to course
     */
    public function assignInstructor($data) {
        $this->requireAdmin();
        
        // Validate CSRF
        if (!Csrf::verify($data['_csrf'] ?? '')) {
            return ResponseFactory::error('Invalid CSRF token', 403);
        }
        
        $courseId = intval($data['course_id'] ?? 0);
        $instructorId = intval($data['instructor_id'] ?? 0);
        
        if ($courseId <= 0) {
            return ResponseFactory::error('Invalid course ID', 400);
        }
        
        if ($instructorId <= 0) {
            return ResponseFactory::error('Invalid instructor ID', 400);
        }
        
        // Check if course exists
        $course = $this->repository->findById($courseId);
        if (!$course) {
            return ResponseFactory::error('Course not found', 404);
        }
        
        // Check if instructor exists and is actually an instructor
        $instructorStmt = $this->conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'instructor' AND status = 'active'");
        $instructorStmt->bind_param("i", $instructorId);
        $instructorStmt->execute();
        $instructorResult = $instructorStmt->get_result();
        
        if ($instructorResult->num_rows === 0) {
            $instructorStmt->close();
            return ResponseFactory::error('Invalid instructor selected', 400);
        }
        $instructor = $instructorResult->fetch_assoc();
        $instructorStmt->close();
        
        // Update course instructor
        $courseData = [
            'name' => $course['name'],
            'description' => $course['description'],
            'instructor_id' => $instructorId
        ];
        
        if ($this->repository->update($courseId, $courseData)) {
            // Log action
            $this->logAction('assign_instructor', $courseId, "Assigned instructor: {$instructor['name']} to course: {$course['name']}");
            
            return ResponseFactory::success('Instructor assigned successfully', [
                'instructor' => $instructor
            ]);
        } else {
            return ResponseFactory::error('Failed to assign instructor', 500);
        }
    }
    
    /**
     * Get course statistics
     */
    public function getStatistics() {
        $this->requireAdmin();
        
        $stats = [];
        
        // Total courses
        $result = $this->conn->query("SELECT COUNT(*) as total FROM courses");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        // Courses by instructor
        $result = $this->conn->query("
            SELECT u.id, u.name, COUNT(c.id) as course_count 
            FROM users u
            LEFT JOIN courses c ON u.id = c.instructor_id
            WHERE u.role = 'instructor'
            GROUP BY u.id, u.name
        ");
        $stats['by_instructor'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['by_instructor'][] = $row;
        }
        
        return ResponseFactory::success('Statistics retrieved successfully', $stats);
    }
    
    /**
     * Log admin action for audit trail
     */
    private function logAction($action, $targetId, $description) {
        $logFile = dirname(__DIR__) . '/storage/logs/admin_actions.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $adminId = $this->session->getUserId();
        $adminName = $this->session->getUserName();
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $logEntry = "[{$timestamp}] Admin: {$adminName} (ID: {$adminId}) | Action: {$action} | Target ID: {$targetId} | Description: {$description} | IP: {$ip}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}


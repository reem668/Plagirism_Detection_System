<?php
namespace Repositories;

require_once __DIR__ . '/../Models/Course.php';

use Models\Course;

/**
 * CourseRepository - Repository Pattern Implementation
 * Separates data access logic from business logic
 */
class CourseRepository {
    private $conn;
    private $courseModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->courseModel = new Course($conn);
    }
    
    /**
     * Get all courses with optional filters
     */
    public function findAll($filters = []) {
        return $this->courseModel->getAll($filters);
    }
    
    /**
     * Find course by ID
     */
    public function findById($id) {
        $course = new Course($this->conn);
        if ($course->findById($id)) {
            return [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
                'instructor_id' => $course->getInstructorId(),
                'created_at' => $course->getCreatedAt(),
                'updated_at' => $course->getUpdatedAt()
            ];
        }
        return null;
    }
    
    /**
     * Find course by ID with instructor details
     */
    public function findByIdWithInstructor($id) {
        $sql = "SELECT c.*, 
                       u.id as instructor_user_id,
                       u.name as instructor_name,
                       u.email as instructor_email
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE c.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $course = $result->fetch_assoc();
            $stmt->close();
            return $course;
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * Create new course
     */
    public function create($data) {
        $course = new Course($this->conn);
        $course->setName($data['name']);
        $course->setDescription($data['description'] ?? null);
        $course->setInstructorId($data['instructor_id']);
        
        if ($course->save()) {
            return [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
                'instructor_id' => $course->getInstructorId()
            ];
        }
        return null;
    }
    
    /**
     * Update course
     */
    public function update($id, $data) {
        $course = new Course($this->conn);
        if (!$course->findById($id)) {
            return false;
        }
        
        $course->setName($data['name']);
        $course->setDescription($data['description'] ?? null);
        $course->setInstructorId($data['instructor_id']);
        
        return $course->update();
    }
    
    /**
     * Delete course
     */
    public function delete($id) {
        $course = new Course($this->conn);
        if (!$course->findById($id)) {
            return false;
        }
        return $course->delete();
    }
    
    /**
     * Check if course name exists
     */
    public function nameExists($name, $excludeId = null) {
        $course = new Course($this->conn);
        return $course->nameExists($name, $excludeId);
    }
    
    /**
     * Get course count
     */
    public function count($filters = []) {
        $course = new Course($this->conn);
        return $course->getCount($filters);
    }
}


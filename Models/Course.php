<?php
namespace Models;

/**
 * Course Model - Handles all course database operations
 * Follows OOP principles and supports test connections
 */
class Course {
    private $db;
    private $id;
    private $name;
    private $description;
    private $instructor_id;
    private $created_at;
    private $updated_at;
    private $isTestConnection = false;
    
    public function __construct($testConnection = null) {
        if ($testConnection !== null) {
            // Use provided test connection
            $this->db = $testConnection;
            $this->isTestConnection = true;
        } else {
            // Connect to database directly for production
            $host = "localhost";
            $user = "root";
            $pass = "";
            $dbname = "pal";
            
            $this->db = new \mysqli($host, $user, $pass, $dbname);
            
            if ($this->db->connect_error) {
                die("Connection failed: " . $this->db->connect_error);
            }
            
            $this->db->set_charset("utf8mb4");
        }
    }
    
    // ========== GETTERS ==========
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getInstructorId() { return $this->instructor_id; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }
    
    // ========== SETTERS ==========
    public function setName($name) { 
        $this->name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8'); 
    }
    
    public function setDescription($description) { 
        $this->description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8'); 
    }
    
    public function setInstructorId($instructor_id) { 
        $this->instructor_id = intval($instructor_id); 
    }
    
    // ========== DATABASE OPERATIONS ==========
    
    /**
     * Find course by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'] ?? null;
            $this->instructor_id = $row['instructor_id'];
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    
    /**
     * Check if course name already exists (excluding current course)
     */
    public function nameExists($name, $excludeId = null) {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT id FROM courses WHERE name = ? AND id != ?");
            $stmt->bind_param("si", $name, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM courses WHERE name = ?");
            $stmt->bind_param("s", $name);
        }
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    /**
     * Save new course to database
     */
    public function save() {
        $stmt = $this->db->prepare(
            "INSERT INTO courses (name, description, instructor_id, created_at, updated_at) 
             VALUES (?, ?, ?, NOW(), NOW())"
        );
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("ssi", 
            $this->name, 
            $this->description,
            $this->instructor_id
        );
        
        $success = $stmt->execute();
        if ($success) {
            $this->id = $stmt->insert_id;
        } else {
            error_log("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        return $success;
    }
    
    /**
     * Update course in database
     */
    public function update() {
        if (!$this->id) {
            return false;
        }
        
        $stmt = $this->db->prepare(
            "UPDATE courses SET name = ?, description = ?, instructor_id = ?, updated_at = NOW() 
             WHERE id = ?"
        );
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("ssii", 
            $this->name, 
            $this->description,
            $this->instructor_id,
            $this->id
        );
        
        $success = $stmt->execute();
        if (!$success) {
            error_log("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        return $success;
    }
    
    /**
     * Delete course from database
     */
    public function delete() {
        if (!$this->id) {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $this->id);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        return $success;
    }
    
    /**
     * Get all courses with instructor information
     */
    public function getAll($filters = []) {
        $sql = "SELECT c.*, 
                       u.id as instructor_user_id,
                       u.name as instructor_name,
                       u.email as instructor_email
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }
        
        if (!empty($filters['instructor_id'])) {
            $sql .= " AND c.instructor_id = ?";
            $params[] = intval($filters['instructor_id']);
            $types .= "i";
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        $stmt->close();
        return $courses;
    }
    
    /**
     * Get course count
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM courses WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }
        
        if (!empty($filters['instructor_id'])) {
            $sql .= " AND instructor_id = ?";
            $params[] = intval($filters['instructor_id']);
            $types .= "i";
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return 0;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['total']);
    }
    
    /**
     * Close connection when done (only if not using test connection)
     */
    public function __destruct() {
        // Don't close test connections
        if ($this->db && !$this->isTestConnection && !defined('PHPUNIT_RUNNING')) {
            $this->db->close();
        }
    }
}


<?php
namespace Models;

class Instructor {
    private $conn;

    public function __construct($conn = null) {
        // Prefer an explicitly passed connection
        if ($conn instanceof \mysqli) {
            $this->conn = $conn;
        } elseif (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof \mysqli) {
            // Try global connection set by includes/db.php
            $this->conn = $GLOBALS['conn'];
        } else {
            // Last resort: include db.php using project root
            $rootPath = dirname(__DIR__); // project root
            require_once $rootPath . '/includes/db.php';
            if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof \mysqli) {
                $this->conn = $GLOBALS['conn'];
            }
        }

        if (!$this->conn || !($this->conn instanceof \mysqli)) {
            die("Database connection not available in Instructor model. Please ensure includes/db.php is included and connection is established.");
        }
    }

    public function getInstructor($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id=? AND role='instructor'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $instructor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $instructor;
    }

    public function getSubmissions($instructor_id) {
    $submissions = [];

    // Fetch instructor name for this ID
    $stmt = $this->conn->prepare("SELECT name FROM users WHERE id=?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $instructor_result = $stmt->get_result();
    $instructor_data = $instructor_result->fetch_assoc();
    $stmt->close();
    
    if (!$instructor_data || !isset($instructor_data['name'])) {
        return [];
    }
    
    $instructor_name = trim($instructor_data['name']);

    // Fetch submissions where teacher matches this instructor (exclude deleted via status)
    // Match by exact name (case-insensitive in MySQL by default)
    $sql = "
        SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
               s.file_size, s.similarity, s.status, s.created_at, s.feedback,
               u.name AS student_name, u.email AS student_email
        FROM submissions s
        JOIN users u ON s.user_id = u.id
        WHERE TRIM(s.teacher) = ? AND s.status <> 'deleted' AND s.teacher IS NOT NULL AND s.teacher != ''
        ORDER BY s.created_at DESC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }

    $stmt->close();
    return $submissions;
}
public function getTrash($instructor_id) {
    $trash = [];

    $stmt = $this->conn->prepare("SELECT name FROM users WHERE id=?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $instructor_result = $stmt->get_result();
    $instructor_data = $instructor_result->fetch_assoc();
    $stmt->close();
    
    if (!$instructor_data || !isset($instructor_data['name'])) {
        return [];
    }
    
    $instructor_name = trim($instructor_data['name']);

    $sql = "
        SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
               s.file_size, s.similarity, s.status, s.created_at, s.feedback,
               u.name AS student_name, u.email AS student_email
        FROM submissions s
        JOIN users u ON s.user_id = u.id
        WHERE TRIM(s.teacher) = ? AND s.status = 'deleted' AND s.teacher IS NOT NULL AND s.teacher != ''
        ORDER BY s.created_at DESC
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trash[] = $row;
    }

    $stmt->close();
    return $trash;
}

    public function getStats($instructor_id) {
        // Get instructor name first
        $stmt = $this->conn->prepare("SELECT name FROM users WHERE id=?");
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $instructor_result = $stmt->get_result();
        $instructor = $instructor_result->fetch_assoc();
        $stmt->close();
        
        if (!$instructor || !isset($instructor['name'])) {
            return ['students_enrolled' => 0];
        }
        
        $instructor_name = trim($instructor['name']);
        
        // Count unique students who have submitted to this instructor (excluding deleted)
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT s.user_id) AS students_enrolled 
            FROM submissions s 
            WHERE TRIM(s.teacher) = ? AND s.status <> 'deleted' AND s.teacher IS NOT NULL AND s.teacher != ''
        ");
        $stmt->bind_param("s", $instructor_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats ?: ['students_enrolled' => 0];
    }

    public function getEnrolledStudents($instructor_id) {
        // Get instructor name first
        $stmt = $this->conn->prepare("SELECT name FROM users WHERE id=?");
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $instructor_result = $stmt->get_result();
        $instructor = $instructor_result->fetch_assoc();
        $stmt->close();
        
        if (!$instructor || !isset($instructor['name'])) {
            return [];
        }
        
        $instructor_name = trim($instructor['name']);
        
        // Get only students who have submitted to this instructor (excluding deleted)
        $students = [];
        $stmt = $this->conn->prepare("
            SELECT DISTINCT u.id, u.name, u.email, u.mobile, u.country, u.role, u.status, u.created_at
            FROM users u
            INNER JOIN submissions s ON u.id = s.user_id
            WHERE TRIM(s.teacher) = ? AND s.status <> 'deleted' AND s.teacher IS NOT NULL AND s.teacher != '' AND u.role = 'student'
            ORDER BY u.name ASC
        ");
        $stmt->bind_param("s", $instructor_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
        
        return $students;
    }

    /**
     * Get all instructors from database
     */
    public function getAllInstructors() {
        $instructors = [];
        $result = $this->conn->query("SELECT id, name, email FROM users WHERE role='instructor' ORDER BY name ASC");
        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
        return $instructors;
    }

      public function getByInstructor(int $instructor_id, string $status = 'active'): array {
    $stmt = $this->conn->prepare("
        SELECT s.*, u.name AS student_name, u.email AS student_email
        FROM submissions s
        JOIN users u ON s.user_id = u.id
        WHERE s.teacher = (
            SELECT name FROM users WHERE id = ?
        ) AND s.status = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->bind_param("is", $instructor_id, $status);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Verify if instructor owns a submission
 */
public function ownsSubmission(int $instructor_id, int $submission_id): bool {
    $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $instructor = $res->fetch_assoc();
    $stmt->close();
    
    if (!$instructor) {
        return false;
    }
    
    $stmt = $this->conn->prepare("SELECT teacher FROM submissions WHERE id = ?");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $submission = $res->fetch_assoc();
    $stmt->close();
    
    return $submission && $submission['teacher'] === $instructor['name'];
}

}

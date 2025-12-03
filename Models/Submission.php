<?php
namespace Models;

class Submission {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    /**
     * Get or create a default course for general submissions
     */
    public function getOrCreateDefaultCourse(): int {
        // Try to get a default "General" course
        $stmt = $this->conn->prepare("SELECT id FROM courses WHERE name = 'General' LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $course = $result->fetch_assoc();
        $stmt->close();
        
        if ($course) {
            return $course['id'];
        }
        
        // If no default course exists, create one with a default instructor
        // First, get any instructor ID (or use 1 as fallback)
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE role = 'instructor' LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $instructor = $result->fetch_assoc();
        $stmt->close();
        
        $instructorId = $instructor ? $instructor['id'] : 1;
        
        // Create the default course
        $stmt = $this->conn->prepare("INSERT INTO courses (name, instructor_id) VALUES ('General', ?)");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $courseId = $stmt->insert_id;
        $stmt->close();
        
        return $courseId;
    }

    /**
     * Create a new submission
     */
    public function create(array $data): int {
        // Ensure course_id is set, use default if not provided
        if (!isset($data['course_id']) || empty($data['course_id'])) {
            $data['course_id'] = $this->getOrCreateDefaultCourse();
        }
        
        $sql = "
            INSERT INTO submissions 
            (user_id, course_id, teacher, text_content, file_path, stored_name, file_size, similarity, exact_match, partial_match, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) die("Prepare failed: ".$this->conn->error);

        $stmt->bind_param(
            "iissssiiii",
            $data['user_id'],
            $data['course_id'],
            $data['teacher'],
            $data['text_content'],
            $data['file_path'],
            $data['stored_name'],
            $data['file_size'],
            $data['similarity'],
            $data['exact_match'],
            $data['partial_match']
        );

        if (!$stmt->execute()) die("Execute failed: ".$stmt->error);

        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Get submissions for a user with optional status and deleted flag
     */
    public function getByUser(int $uid, string $status = 'active'): array {
        // Use the existing `status` column: 'active' or 'deleted'
        $stmt = $this->conn->prepare("
            SELECT * FROM submissions WHERE user_id = ? AND status = ? ORDER BY created_at DESC
        ");
        $stmt->bind_param("is", $uid, $status);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Find submission by ID
     */
    public function find(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Get all active submissions text for plagiarism comparison
     */
    public function getAllSubmissions(): array {
        // Only consider non-deleted submissions
        $res = $this->conn->query("SELECT text_content FROM submissions WHERE status = 'active'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

  public function moveToTrash(int $submission_id): bool {
    // Soft delete via status column
    $stmt = $this->conn->prepare("
        UPDATE submissions SET status='deleted' WHERE id=?
    ");
    $stmt->bind_param("i", $submission_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

public function accept(int $submission_id): bool {
    $stmt = $this->conn->prepare("
        UPDATE submissions SET status='accepted' WHERE id=?
    ");
    $stmt->bind_param("i", $submission_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
public function addFeedback(int $submission_id, string $feedback): bool {
    $stmt = $this->conn->prepare("
        UPDATE submissions SET feedback=? WHERE id=?
    ");
    $stmt->bind_param("si", $feedback, $submission_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

public function reject(int $submission_id): bool {
    $stmt = $this->conn->prepare("
        UPDATE submissions SET status='rejected' WHERE id=?
    ");
    $stmt->bind_param("i", $submission_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get report file path for a submission
 */
public function getReportPath(int $submission_id): ?string {
    $rootPath = dirname(dirname(__DIR__));
    $storageDir = $rootPath . '/storage/reports';
    $files = glob($storageDir . "/report_{$submission_id}_*.html");
    if (empty($files)) {
        return null;
    }
    return $files[0];
}

/**
 * Check if instructor owns this submission (by teacher name)
 */
public function isOwnedByInstructor(int $submission_id, string $instructor_name): bool {
    $stmt = $this->conn->prepare("SELECT teacher FROM submissions WHERE id = ?");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row && $row['teacher'] === $instructor_name;
}

}

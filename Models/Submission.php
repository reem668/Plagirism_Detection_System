<?php
namespace Models;

class Submission {
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    /**
     * Create a new submission
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO submissions 
            (user_id, teacher, text_content, file_path, stored_name, file_size, similarity, exact_match, partial_match, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) die("Prepare failed: ".$this->conn->error);

        $stmt->bind_param(
            "issssiiii",
            $data['user_id'],
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
     * Get submissions for a user with optional status
     */
    public function getByUser(int $uid, string $status = 'active'): array {
        $stmt = $this->conn->prepare("
            SELECT * FROM submissions WHERE user_id = ? AND status=? ORDER BY created_at DESC
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
        $res = $this->conn->query("SELECT text_content FROM submissions WHERE status='active'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get ALL submissions (for admin)
     * No user filter - returns everything
     */
    public function getAll($limit = 100, $offset = 0) {
        $sql = "SELECT * FROM submissions ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rows;
    }

    /**
     * Delete a submission by ID
     */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM submissions WHERE id = ?");
        
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $affected > 0;
    }

    /**
     * Update submission fields
     */
    public function update($id, $data) {
        $setClauses = [];
        $params = [];
        $types = "";

        $allowedFields = [
            'status' => 's',
            'similarity' => 'i',
            'exact_match' => 'i',
            'partial_match' => 'i',
            'course_id' => 'i',
            'instructor_id' => 'i',
            'teacher' => 's',
            'text_content' => 's'
        ];

        foreach ($data as $field => $value) {
            if (isset($allowedFields[$field])) {
                $setClauses[] = "$field = ?";
                $params[] = $value;
                $types .= $allowedFields[$field];
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        $sql = "UPDATE submissions SET " . implode(", ", $setClauses) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Count total submissions (for statistics)
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM submissions WHERE 1=1";
        $params = [];
        $types = "";

        if (isset($conditions['status'])) {
            $sql .= " AND status = ?";
            $params[] = $conditions['status'];
            $types .= "s";
        }

        if (isset($conditions['min_similarity'])) {
            $sql .= " AND similarity >= ?";
            $params[] = $conditions['min_similarity'];
            $types .= "i";
        }

        if (isset($conditions['max_similarity'])) {
            $sql .= " AND similarity <= ?";
            $params[] = $conditions['max_similarity'];
            $types .= "i";
        }

        if (isset($conditions['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $conditions['user_id'];
            $types .= "i";
        }

        if (isset($conditions['course_id'])) {
            $sql .= " AND course_id = ?";
            $params[] = $conditions['course_id'];
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['total'];
    }

    /**
     * Get submissions by course
     */
    public function getByCourse($courseId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM submissions WHERE course_id = ? ORDER BY created_at DESC"
        );
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rows;
    }

    /**
     * Get submissions by instructor
     */
    public function getByInstructor($instructorId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM submissions WHERE instructor_id = ? ORDER BY created_at DESC"
        );
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rows;
    }

    /**
     * Get average similarity score
     */
    public function getAverageSimilarity($conditions = []) {
        $sql = "SELECT AVG(similarity) as avg FROM submissions WHERE similarity IS NOT NULL";
        $params = [];
        $types = "";

        if (isset($conditions['course_id'])) {
            $sql .= " AND course_id = ?";
            $params[] = $conditions['course_id'];
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['avg'] ? round($row['avg'], 1) : 0;
    }
}
?>

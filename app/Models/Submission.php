<?php
namespace Models;

class Submission
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get or create a default "General" course for general submissions
     */
    private function getOrCreateGeneralCourse(): int
    {
        // First, try to find an existing "General" course
        $stmt = $this->conn->prepare("SELECT id FROM courses WHERE name = 'General Submission' LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return intval($row['id']);
            }
            $stmt->close();
        }

        // If not found, create a default "General" course
        // We need an instructor_id - use the first available instructor or 0
        $instructorStmt = $this->conn->prepare("SELECT id FROM users WHERE role='instructor' AND status='active' LIMIT 1");
        $instructor_id = 0;
        if ($instructorStmt) {
            $instructorStmt->execute();
            $instructorResult = $instructorStmt->get_result();
            if ($instructorRow = $instructorResult->fetch_assoc()) {
                $instructor_id = intval($instructorRow['id']);
            }
            $instructorStmt->close();
        }

        // Create the General course
        $insertStmt = $this->conn->prepare("INSERT INTO courses (name, instructor_id) VALUES ('General Submission', ?)");
        if ($insertStmt) {
            $insertStmt->bind_param("i", $instructor_id);
            $insertStmt->execute();
            $generalCourseId = $insertStmt->insert_id;
            $insertStmt->close();
            return $generalCourseId;
        }

        // Fallback: if we can't create, try to use course_id = 1 (first course)
        $fallbackStmt = $this->conn->query("SELECT id FROM courses LIMIT 1");
        if ($fallbackStmt && $fallbackRow = $fallbackStmt->fetch_assoc()) {
            return intval($fallbackRow['id']);
        }

        // Last resort: return 1 (hopefully exists)
        return 1;
    }

    /**
     * Create a new submission
     */
public function create(array $data): int
{
    $sql = "
        INSERT INTO submissions 
        (user_id, course_id, instructor_id, teacher, text_content, file_path, stored_name, file_size, similarity, exact_match, partial_match, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ";

    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $this->conn->error);
    }

    // course_id: use given id or NULL for general submissions
    $course_id = isset($data['course_id']) && $data['course_id'] > 0
        ? (int)$data['course_id']
        : null;   // <‑‑ NULL, not 0

    // instructor_id: NULL when none
    $instructor_id = isset($data['instructor_id']) && $data['instructor_id'] > 0
        ? (int)$data['instructor_id']
        : null;   // <‑‑ NULL, not 0

    $user_id       = (int)$data['user_id'];
    $teacher       = $data['teacher']      ?? null;
    $text_content  = $data['text_content'] ?? '';
    $file_path     = $data['file_path']    ?? null;
    $stored_name   = $data['stored_name']  ?? null;
    $file_size     = (int)($data['file_size'] ?? 0);
    $similarity    = (int)($data['similarity'] ?? 0);
    $exact_match   = (int)($data['exact_match'] ?? 0);
    $partial_match = (int)($data['partial_match'] ?? 0);

    // bind: mysqli will send real SQL NULL when the PHP variable is null
    $stmt->bind_param(
        "iiissssiiii",
        $user_id,
        $course_id,
        $instructor_id,
        $teacher,
        $text_content,
        $file_path,
        $stored_name,
        $file_size,
        $similarity,
        $exact_match,
        $partial_match
    );

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}


    /**
     * Get submissions for a user with optional status
     */
    public function getByUser(int $uid, string $status = 'active'): array
    {
        if ($status === 'visible') {
            // 'visible' includes all non-deleted statuses
            $stmt = $this->conn->prepare("
                SELECT * FROM submissions 
                WHERE user_id = ? 
                AND status IN ('active', 'pending', 'accepted', 'rejected') 
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("i", $uid);
        } else {
            $stmt = $this->conn->prepare("
                SELECT * FROM submissions WHERE user_id = ? AND status=? ORDER BY created_at DESC
            ");
            $stmt->bind_param("is", $uid, $status);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Find submission by ID
     */
    public function find(int $id): ?array
    {
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
    public function getAllSubmissions(): array
    {
        $res = $this->conn->query("SELECT text_content FROM submissions WHERE status='active'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get ALL submissions (for admin)
     * No user filter - returns everything
     */
    public function getAll($limit = 100, $offset = 0)
    {
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
     **/

    public function deleteByStudent(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare("UPDATE submissions SET status='deleted' WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }

    /**
     * Update submission fields
     */
    public function update($id, $data)
    {
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
    public function count($conditions = [])
    {
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
    public function getByCourse($courseId)
    {
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
    public function getByInstructor($instructorId)
    {
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
    public function getAverageSimilarity($conditions = [])
    {
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

    /**
     * Add or update feedback for a submission
     */
    public function addFeedback(int $submission_id, string $feedback): bool
    {
        $stmt = $this->conn->prepare("UPDATE submissions SET notification_seen = 0, feedback = ? WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("si", $feedback, $submission_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get report path for a submission
     */
    /**
     * Get report path for a submission
     */
    public function getReportPath(int $submission_id): ?string
    {
        // First, check database for stored path
        $stmt = $this->conn->prepare("SELECT report_path FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row && !empty($row['report_path']) && file_exists($row['report_path'])) {
            return $row['report_path'];
        }

        // Fallback: search for file by submission ID (get most recent)
        $storageDir = dirname(__DIR__) . '/storage/reports';
        $files = glob($storageDir . "/report_{$submission_id}_*.html");

        if (!empty($files)) {
            // Sort by modification time (newest first)
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            return $files[0];
        }

        return null;
    }

    /**
     * Accept a submission (set status to 'accepted')
     */
    public function accept(int $submission_id): bool
    {
        $stmt = $this->conn->prepare("UPDATE submissions SET status = 'accepted' WHERE id = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $submission_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Reject a submission (set status to 'rejected')
     */
    public function reject(int $submission_id): bool
    {
        $stmt = $this->conn->prepare("UPDATE submissions SET status = 'rejected' WHERE id = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $submission_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Move a submission to trash (set status to 'deleted')
     */
    public function moveToTrash(int $submission_id): bool
    {
        $stmt = $this->conn->prepare("UPDATE submissions SET status = 'deleted' WHERE id = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $submission_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
?>
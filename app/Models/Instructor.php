<?php
namespace Models;

class Instructor
{
    private $conn;

    public function __construct($conn = null)
    {
        if ($conn instanceof \mysqli) {
            $this->conn = $conn;
        } elseif (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof \mysqli) {
            $this->conn = $GLOBALS['conn'];
        } else {
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

    public function getInstructor($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'instructor'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $instructor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $instructor;
    }

    public function getSubmissions($instructor_id)
    {
        $submissions = [];

        $sql = "
            SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
                   s.file_size, s.similarity, s.status, s.created_at, s.feedback,
                   u.name AS student_name, u.email AS student_email,
                   c.name AS course_name
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role = 'instructor'))
              AND s.status <> 'deleted'
            ORDER BY s.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }

        $stmt->close();
        return $submissions;
    }

    public function getTrash($instructor_id)
    {
        $trash = [];

        $sql = "
            SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
                   s.file_size, s.similarity, s.status, s.created_at, s.feedback,
                   u.name AS student_name, u.email AS student_email,
                   c.name AS course_name
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (SELECT name FROM users WHERE id = ? AND role = 'instructor'))
              AND s.status = 'deleted'
            ORDER BY s.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $trash[] = $row;
        }

        $stmt->close();
        return $trash;
    }

    /**
     * Get statistics for instructor dashboard
     */
    public function getStats($instructor_id = null)
    {
        if ($instructor_id === null) {
            if (isset($_SESSION['user']['id'])) {
                $instructor_id = $_SESSION['user']['id'];
            } elseif (isset($_SESSION['user_id'])) {
                $instructor_id = $_SESSION['user_id'];
            } else {
                return [
                    'students_enrolled'    => 0,
                    'total_submissions'    => 0,
                    'pending_submissions'  => 0,
                    'accepted_submissions' => 0,
                    'rejected_submissions' => 0,
                ];
            }
        }

        // Instructor name
        $instructorStmt = $this->conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'instructor'");
        $instructorStmt->bind_param("i", $instructor_id);
        $instructorStmt->execute();
        $instructorResult = $instructorStmt->get_result();
        $instructorData   = $instructorResult->fetch_assoc();
        $instructorName   = $instructorData['name'] ?? '';
        $instructorStmt->close();

        // Students enrolled
        $studentQuery = "
            SELECT COUNT(DISTINCT u.id) AS students_enrolled
            FROM users u
            JOIN submissions s ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = ?)
        ";
        $stmt = $this->conn->prepare($studentQuery);
        $stmt->bind_param("is", $instructor_id, $instructorName);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        $studentData   = $studentResult->fetch_assoc();
        $stmt->close();

        // Total submissions (not deleted)
        $totalQuery = "
            SELECT COUNT(*) AS total_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = ?)
              AND s.status <> 'deleted'
        ";
        $stmt = $this->conn->prepare($totalQuery);
        $stmt->bind_param("is", $instructor_id, $instructorName);
        $stmt->execute();
        $totalResult = $stmt->get_result();
        $totalData   = $totalResult->fetch_assoc();
        $stmt->close();

        // Pending submissions
        $pendingQuery = "
            SELECT COUNT(*) AS pending_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = ?)
              AND (s.status = 'active' OR s.status = 'pending' OR s.status IS NULL OR s.status = '')
        ";
        $stmt = $this->conn->prepare($pendingQuery);
        $stmt->bind_param("is", $instructor_id, $instructorName);
        $stmt->execute();
        $pendingResult = $stmt->get_result();
        $pendingData   = $pendingResult->fetch_assoc();
        $stmt->close();

        // Accepted submissions
        $acceptedQuery = "
            SELECT COUNT(*) AS accepted_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = ?)
              AND s.status = 'accepted'
        ";
        $stmt = $this->conn->prepare($acceptedQuery);
        $stmt->bind_param("is", $instructor_id, $instructorName);
        $stmt->execute();
        $acceptedResult = $stmt->get_result();
        $acceptedData   = $acceptedResult->fetch_assoc();
        $stmt->close();

        // Rejected submissions
        $rejectedQuery = "
            SELECT COUNT(*) AS rejected_submissions
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = ?)
              AND s.status = 'rejected'
        ";
        $stmt = $this->conn->prepare($rejectedQuery);
        $stmt->bind_param("is", $instructor_id, $instructorName);
        $stmt->execute();
        $rejectedResult = $stmt->get_result();
        $rejectedData   = $rejectedResult->fetch_assoc();
        $stmt->close();

        return [
            'students_enrolled'    => $studentData['students_enrolled'] ?? 0,
            'total_submissions'    => $totalData['total_submissions'] ?? 0,
            'pending_submissions'  => $pendingData['pending_submissions'] ?? 0,
            'accepted_submissions' => $acceptedData['accepted_submissions'] ?? 0,
            'rejected_submissions' => $rejectedData['rejected_submissions'] ?? 0,
        ];
    }

    /**
     * Get students who have submissions assigned to this instructor
     */
    public function getEnrolledStudents(int $instructor_id): array
    {
        $students = [];

        $sql = "
            SELECT DISTINCT u.id, u.name, u.email
            FROM users u
            JOIN submissions s ON s.user_id = u.id
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE (c.instructor_id = ? OR s.teacher = (
                SELECT name FROM users WHERE id = ? AND role = 'instructor'
            ))
            ORDER BY u.name ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $instructor_id, $instructor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return $students;
    }

    /**
     * Get all instructors
     */
    public function getAllInstructors()
    {
        $instructors = [];
        $result      = $this->conn->query("SELECT id, name, email FROM users WHERE role = 'instructor' ORDER BY name ASC");
        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
        return $instructors;
    }

    public function getByInstructor(int $instructor_id, string $status = 'active'): array
    {
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
        $res  = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Verify if instructor owns a submission
     */
    public function ownsSubmission(int $instructor_id, int $submission_id): bool
    {
        $stmt = $this->conn->prepare("
            SELECT s.id, s.teacher, c.instructor_id
            FROM submissions s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $res        = $stmt->get_result();
        $submission = $res->fetch_assoc();
        $stmt->close();

        if (!$submission) {
            return false;
        }

        if ((int)$submission['instructor_id'] === $instructor_id) {
            return true;
        }

        $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'instructor'");
        $stmt->bind_param("i", $instructor_id);
        $stmt->execute();
        $res        = $stmt->get_result();
        $instructor = $res->fetch_assoc();
        $stmt->close();

        return $instructor && $submission['teacher'] === $instructor['name'];
    }
}

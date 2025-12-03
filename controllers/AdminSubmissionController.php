<?php
namespace Controllers;

require_once __DIR__ . '/../Models/Submission.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

use Models\Submission;
use Helpers\Csrf;

/**
 * AdminSubmissionController
 * 
 * This controller handles admin-specific submission operations.
 * It uses the same Submission model as the student SubmissionController,
 * but adds methods needed for admin oversight.
 */
class AdminSubmissionController {
    protected $conn;
    protected $submission;

    public function __construct() {
         require dirname(__DIR__) . '/includes/db.php';
        $this->conn = $conn;
        
        if ($this->conn->connect_error) {
            die("DB connection failed: " . $this->conn->connect_error);
        }
        
        // Use the SAME Submission model your friend created
        $this->submission = new Submission($this->conn);
    }

    /**
     * Check if current user is admin
     */
    protected function requireAdmin() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }

    /**
     * Get ALL submissions for admin overview
     * This is the key difference from student's getByUser()
     */
    public function getAllSubmissions($filters = []) {
        $this->requireAdmin();
        
        $sql = "SELECT s.*, 
                       u.name as student_name, 
                       u.email as student_email,
                       c.id as course_code,
                       c.name as course_name,
                       i.name as instructor_name
                FROM submissions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN users i ON s.instructor_id = i.id
                WHERE 1=1";
        
        $params = [];
        $types = "";

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['risk'])) {
            switch ($filters['risk']) {
                case 'low':
                    $sql .= " AND s.similarity <= 30";
                    break;
                case 'medium':
                    $sql .= " AND s.similarity > 30 AND s.similarity <= 70";
                    break;
                case 'high':
                    $sql .= " AND s.similarity > 70";
                    break;
            }
        }

        if (!empty($filters['course_id'])) {
            $sql .= " AND s.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= "i";
        }

        if (!empty($filters['instructor_id'])) {
            $sql .= " AND s.instructor_id = ?";
            $params[] = $filters['instructor_id'];
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE ? OR s.text_content LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $sql .= " ORDER BY s.created_at DESC";

        // Pagination
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $submissions;
    }

    /**
     * Get statistics for admin dashboard
     */
    public function getStatistics() {
        $this->requireAdmin();

        $stats = [];

        // Total submissions
        $result = $this->conn->query("SELECT COUNT(*) as total FROM submissions");
        $stats['total'] = $result->fetch_assoc()['total'];

        // Completed submissions
        $result = $this->conn->query("SELECT COUNT(*) as completed FROM submissions WHERE status = 'completed'");
        $stats['completed'] = $result->fetch_assoc()['completed'];

        // Processing submissions
        $result = $this->conn->query("SELECT COUNT(*) as processing FROM submissions WHERE status = 'processing'");
        $stats['processing'] = $result->fetch_assoc()['processing'];

        // Average similarity (only for completed with non-null similarity)
        $result = $this->conn->query("SELECT AVG(similarity) as avg_similarity FROM submissions WHERE similarity IS NOT NULL");
        $row = $result->fetch_assoc();
        $stats['avg_similarity'] = $row['avg_similarity'] ? round($row['avg_similarity'], 1) : 0;

        // High risk count (>70%)
        $result = $this->conn->query("SELECT COUNT(*) as high_risk FROM submissions WHERE similarity > 70");
        $stats['high_risk'] = $result->fetch_assoc()['high_risk'];

        // Medium risk count (31-70%)
        $result = $this->conn->query("SELECT COUNT(*) as medium_risk FROM submissions WHERE similarity > 30 AND similarity <= 70");
        $stats['medium_risk'] = $result->fetch_assoc()['medium_risk'];

        // Low risk count (0-30%)
        $result = $this->conn->query("SELECT COUNT(*) as low_risk FROM submissions WHERE similarity <= 30 AND similarity IS NOT NULL");
        $stats['low_risk'] = $result->fetch_assoc()['low_risk'];

        return $stats;
    }

    /**
     * Get single submission with full details (for admin panel)
     * Uses the model's find() method but enriches with related data
     */
    public function getSubmissionDetails($id) {
        $this->requireAdmin();

        $sql = "SELECT s.*, 
                       u.name as student_name, 
                       u.email as student_email,
                       c.id as course_id,
                       c.id as course_code,
                       c.name as course_name,
                       i.name as instructor_name,
                       i.email as instructor_email
                FROM submissions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN users i ON s.instructor_id = i.id
                WHERE s.id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $submission = $result->fetch_assoc();
        $stmt->close();

        return $submission;
    }

    /**
     * Delete submission (admin only)
     */
    public function deleteSubmission($id) {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['success' => false, 'message' => 'Method not allowed'];
        }

        // Verify CSRF
        if (!Csrf::verify($_POST['_csrf'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }

        // Get submission to delete associated files
        $submission = $this->submission->find($id);
        
        if (!$submission) {
            return ['success' => false, 'message' => 'Submission not found'];
        }

        // Delete associated file if exists
        if (!empty($submission['file_path']) && file_exists($submission['file_path'])) {
            unlink($submission['file_path']);
        }

        // Delete associated report
        $reportDir = __DIR__ . '/../../storage/reports';
        $reportFiles = glob($reportDir . "/report_{$id}_*.txt");
        foreach ($reportFiles as $file) {
            unlink($file);
        }

        // Delete from database
        $stmt = $this->conn->prepare("DELETE FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Submission deleted successfully' : 'Failed to delete submission'
        ];
    }

    /**
     * Get submissions by course (for admin course analytics)
     */
    public function getSubmissionsByCourse($courseId) {
        $this->requireAdmin();

        $sql = "SELECT s.*, u.name as student_name 
                FROM submissions s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.course_id = ?
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $submissions;
    }

    /**
     * Get submissions by instructor (for admin oversight)
     */
    public function getSubmissionsByInstructor($instructorId) {
        $this->requireAdmin();

        $sql = "SELECT s.*, 
                       u.name as student_name,
                       c.id as course_code
                FROM submissions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN courses c ON s.course_id = c.id
                WHERE s.instructor_id = ?
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $submissions;
    }

    /**
     * Export submissions to CSV
     */
    public function exportToCSV($filters = []) {
        $this->requireAdmin();

        $submissions = $this->getAllSubmissions($filters);

        $filename = 'submissions_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'ID',
            'Student Name',
            'Student Email',
            'Course Code',
            'Course Name',
            'Instructor',
            'Similarity %',
            'Status',
            'Submitted At'
        ]);

        // CSV data rows
        foreach ($submissions as $sub) {
            fputcsv($output, [
                $sub['id'],
                $sub['student_name'] ?? 'Unknown',
                $sub['student_email'] ?? 'N/A',
                $sub['course_code'] ?? 'N/A',
                $sub['course_name'] ?? 'N/A',
                $sub['instructor_name'] ?? 'None',
                $sub['similarity'] ?? 'Processing',
                $sub['status'],
                $sub['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Update submission status (admin override)
     */
    public function updateStatus($id, $status) {
        $this->requireAdmin();

        $allowedStatuses = ['uploaded', 'processing', 'completed', 'failed'];
        
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $stmt = $this->conn->prepare("UPDATE submissions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Status updated' : 'Failed to update status'
        ];
    }
}
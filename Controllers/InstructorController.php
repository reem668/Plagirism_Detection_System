<?php
require_once __DIR__ . '/../Models/Instructor.php';
require_once __DIR__ . '/../Models/Submission.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';

use Models\Instructor;
use Models\Submission;
use Helpers\Csrf;
use Helpers\Validator;

class InstructorController {
    protected $conn;
    protected $instructorModel;
    protected $submissionModel;
    protected $rootPath;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->rootPath = dirname(__DIR__);
        require $this->rootPath . '/includes/db.php';
        $this->conn = $conn;
        if ($this->conn->connect_error) {
            die("DB connection failed: " . $this->conn->connect_error);
        }
        $this->instructorModel = new Instructor($this->conn);
        $this->submissionModel = new Submission($this->conn);
    }

    public function dashboard() {
        $instructor_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$instructor_id) {
            header("Location: /Plagirism_Detection_System/signup.php");
            exit;
        }

        $current_view = $_GET['view'] ?? 'submissions';

        $instructor = $this->instructorModel->getInstructor($instructor_id);
        if (!$instructor) {
            header("Location: /Plagirism_Detection_System/signup.php");
            exit;
        }

        $stats = $this->instructorModel->getStats();
        $enrolled_students = $this->instructorModel->getEnrolledStudents();
        $submissions = $this->instructorModel->getSubmissions($instructor_id);
        $trash = $this->instructorModel->getTrash($instructor_id);

        require __DIR__ . '/../Views/instructor/Instructor.php';
    }

    /**
     * Accept a submission
     */
    public function acceptSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        return $this->submissionModel->accept($submission_id);
    }

    /**
     * Reject a submission
     */
    public function rejectSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        return $this->submissionModel->reject($submission_id);
    }

    /**
     * Delete a submission (move to trash)
     */
    public function deleteSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        return $this->submissionModel->moveToTrash($submission_id);
    }

    /**
     * Add feedback to a submission
     */
    public function addFeedback(int $submission_id, int $instructor_id, string $feedback): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        $sanitized = Validator::sanitize($feedback);
        return $this->submissionModel->addFeedback($submission_id, $sanitized);
    }

    /**
     * View report in browser
     */
    public function viewReport(int $submission_id, int $instructor_id) {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            http_response_code(403);
            die('Access denied. You do not own this submission.');
        }

        $reportPath = $this->submissionModel->getReportPath($submission_id);
        if (!$reportPath || !file_exists($reportPath)) {
            http_response_code(404);
            die('Report not found for this submission.');
        }

        header('Content-Type: text/html; charset=UTF-8');
        readfile($reportPath);
        exit;
    }

    /**
     * Download report
     */
    public function downloadReport(int $submission_id, int $instructor_id) {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            http_response_code(403);
            die('Access denied. You do not own this submission.');
        }

        $reportPath = $this->submissionModel->getReportPath($submission_id);
        if (!$reportPath || !file_exists($reportPath)) {
            http_response_code(404);
            die('Report not found for this submission.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($reportPath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($reportPath));
        readfile($reportPath);
        exit;
    }

    /**
     * Restore submission from trash
     */
    public function restoreSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        // Restore to active status using `status` column
        $stmt = $this->conn->prepare("UPDATE submissions SET status='active' WHERE id=?");
        $stmt->bind_param("i", $submission_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

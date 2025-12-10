<?php
/**
 * Enhanced Instructor Controller with Security Features
 * Handles all instructor-related operations with authentication
 */

require_once __DIR__ . '/../Models/Instructor.php';
require_once __DIR__ . '/../Models/Submission.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/SessionManager.php';

use Models\Instructor;
use Models\Submission;
use Helpers\Csrf;
use Helpers\Validator;
use Helpers\SessionManager;

class InstructorController {
    protected $conn;
    protected $instructorModel;
    protected $submissionModel;
    protected $session;
    protected $rootPath;

    public function __construct() {
        // Initialize session management
        $this->session = SessionManager::getInstance();
        
        $this->rootPath = dirname(__DIR__);
        require $this->rootPath . '/includes/db.php';
        $this->conn = $conn;
        
        if ($this->conn->connect_error) {
            die("DB connection failed: " . $this->conn->connect_error);
        }
        
        $this->instructorModel = new Instructor($this->conn);
        $this->submissionModel = new Submission($this->conn);
    }

    /**
     * Get instructor data by ID
     */
    public function getInstructor(int $instructor_id): ?array {
        return $this->instructorModel->getInstructor($instructor_id);
    }

    /**
     * Get statistics for dashboard
     */
    public function getStats(): array {
        return $this->instructorModel->getStats();
    }

    /**
     * Get enrolled students
     */
  public function getEnrolledStudents($instructor_id)
{
    return $this->instructorModel->getEnrolledStudents($instructor_id);
}




    /**
     * Get submissions for instructor
     */
    public function getSubmissions(int $instructor_id): array {
        return $this->instructorModel->getSubmissions($instructor_id);
    }

    /**
     * Get trash submissions for instructor
     */
    public function getTrash(int $instructor_id): array {
        return $this->instructorModel->getTrash($instructor_id);
    }

    /**
     * Dashboard method - displays instructor dashboard
     * This method is kept for backwards compatibility
     * The new approach uses Instructordashboard.php as entry point
     */
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
       $enrolled_students = $this->getEnrolledStudents($instructor_id);
        $submissions = $this->instructorModel->getSubmissions($instructor_id);
        $trash = $this->instructorModel->getTrash($instructor_id);

        require __DIR__ . '/../Views/instructor/Instructor.php';
    }

    /**
     * Accept a submission
     * Enhanced with audit logging
     */
    public function acceptSubmission(int $submission_id, int $instructor_id): bool {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_ACCEPT_ATTEMPT', $submission_id);
            return false;
        }
        
        $result = $this->submissionModel->accept($submission_id);
        
        if ($result) {
            $this->logAction($instructor_id, 'accept', $submission_id);
        }
        
        return $result;
    }

    /**
     * Reject a submission
     * Enhanced with audit logging
     */
    public function rejectSubmission(int $submission_id, int $instructor_id): bool {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_REJECT_ATTEMPT', $submission_id);
            return false;
        }
        
        $result = $this->submissionModel->reject($submission_id);
        
        if ($result) {
            $this->logAction($instructor_id, 'reject', $submission_id);
        }
        
        return $result;
    }

    /**
     * Delete a submission (move to trash)
     * Enhanced with audit logging
     */
    public function deleteSubmission(int $submission_id, int $instructor_id): bool {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_DELETE_ATTEMPT', $submission_id);
            return false;
        }
        
        $result = $this->submissionModel->moveToTrash($submission_id);
        
        if ($result) {
            $this->logAction($instructor_id, 'delete', $submission_id);
        }
        
        return $result;
    }

    /**
     * Add feedback to a submission
     * Enhanced with validation and audit logging
     */
    public function addFeedback(int $submission_id, int $instructor_id, string $feedback): bool {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_FEEDBACK_ATTEMPT', $submission_id);
            return false;
        }
        
        // Sanitize input
        $sanitized = Validator::sanitize($feedback);
        
        // Validate length
        if (strlen($sanitized) > 5000) {
            return false;
        }
        
        $result = $this->submissionModel->addFeedback($submission_id, $sanitized);
        
        if ($result) {
            $this->logAction($instructor_id, 'add_feedback', $submission_id);
        }
        
        return $result;
    }

    /**
     * View report in browser
     * Enhanced with security checks
     */
    public function viewReport(int $submission_id, int $instructor_id) {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_VIEW_REPORT_ATTEMPT', $submission_id);
            http_response_code(403);
            die('⛔ Access denied. You do not have permission to view this report.');
        }

        $reportPath = $this->submissionModel->getReportPath($submission_id);
        
        if (!$reportPath || !file_exists($reportPath)) {
            http_response_code(404);
            die('❌ Report not found for this submission.');
        }

        // Security: Validate file path is within reports directory
        $realPath = realpath($reportPath);
        $reportsDir = realpath($this->rootPath . '/storage/reports');
        
        if (strpos($realPath, $reportsDir) !== 0) {
            $this->logSecurityEvent($instructor_id, 'PATH_TRAVERSAL_ATTEMPT', $submission_id);
            http_response_code(403);
            die('⛔ Security violation detected.');
        }

        $this->logAction($instructor_id, 'view_report', $submission_id);
        
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        readfile($reportPath);
        exit;
    }

    /**
     * Download report
     * Enhanced with security checks
     */
    public function downloadReport(int $submission_id, int $instructor_id) {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_DOWNLOAD_ATTEMPT', $submission_id);
            http_response_code(403);
            die('⛔ Access denied. You do not have permission to download this report.');
        }

        $reportPath = $this->submissionModel->getReportPath($submission_id);
        
        if (!$reportPath || !file_exists($reportPath)) {
            http_response_code(404);
            die('❌ Report not found for this submission.');
        }

        // Security: Validate file path is within reports directory
        $realPath = realpath($reportPath);
        $reportsDir = realpath($this->rootPath . '/storage/reports');
        
        if (strpos($realPath, $reportsDir) !== 0) {
            $this->logSecurityEvent($instructor_id, 'PATH_TRAVERSAL_ATTEMPT', $submission_id);
            http_response_code(403);
            die('⛔ Security violation detected.');
        }

        $this->logAction($instructor_id, 'download_report', $submission_id);
        
        // Sanitize filename
        $filename = basename($reportPath);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($reportPath));
        header('X-Content-Type-Options: nosniff');
        readfile($reportPath);
        exit;
    }

    /**
     * Restore submission from trash
     * Enhanced with audit logging
     */
    public function restoreSubmission(int $submission_id, int $instructor_id): bool {
        // Verify ownership
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            $this->logSecurityEvent($instructor_id, 'UNAUTHORIZED_RESTORE_ATTEMPT', $submission_id);
            return false;
        }
        
        // Restore to active status using `status` column
        $stmt = $this->conn->prepare("UPDATE submissions SET status='active' WHERE id=?");
        $stmt->bind_param("i", $submission_id);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $this->logAction($instructor_id, 'restore', $submission_id);
        }
        
        return $result;
    }

    /**
     * Log instructor action for audit trail
     */
    private function logAction(int $instructor_id, string $action, int $submission_id): void {
        $logFile = $this->rootPath . '/storage/logs/instructor_actions.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logEntry = "[{$timestamp}] SUCCESS - Instructor ID: {$instructor_id}, Action: {$action}, Submission ID: {$submission_id}, IP: {$ip}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Log security event for suspicious activity
     */
    private function logSecurityEvent(int $instructor_id, string $event, int $submission_id): void {
        $logFile = $this->rootPath . '/storage/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logEntry = "[{$timestamp}] SECURITY_EVENT - Event: {$event}, Instructor ID: {$instructor_id}, Submission ID: {$submission_id}, IP: {$ip}, User-Agent: {$userAgent}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
?>

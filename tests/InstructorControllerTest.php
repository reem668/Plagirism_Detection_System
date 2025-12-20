<?php

require_once __DIR__ . '/DatabaseTestCase.php';

use PHPUnit\Framework\TestCase;

/**
 * Modified InstructorController for testing
 * Allows dependency injection of database connection
 */
class TestableInstructorController {
    protected $conn;
    protected $instructorModel;
    protected $submissionModel;
    
    public function __construct($testConnection = null) {
        // Use injected test connection
        if ($testConnection) {
            $this->conn = $testConnection;
        } else {
            $rootPath = dirname(__DIR__);
            require $rootPath . '/includes/db.php';
            $this->conn = $conn;
        }
        
        if ($this->conn->connect_error) {
            die("DB connection failed: " . $this->conn->connect_error);
        }
        
        require_once __DIR__ . '/../app/Models/Instructor.php';
        require_once __DIR__ . '/../app/Models/Submission.php';
        
        $this->instructorModel = new Models\Instructor($this->conn);
        $this->submissionModel = new Models\Submission($this->conn);
    }
    
    public function getInstructor(int $instructor_id): ?array {
        return $this->instructorModel->getInstructor($instructor_id);
    }
    
    public function getStats(int $instructor_id): array {
        return $this->instructorModel->getStats($instructor_id);
    }
    
    public function getEnrolledStudents($instructor_id) {
        return $this->instructorModel->getEnrolledStudents($instructor_id);
    }
    
    public function getSubmissions(int $instructor_id): array {
        return $this->instructorModel->getSubmissions($instructor_id);
    }
    
    public function getTrash(int $instructor_id): array {
        return $this->instructorModel->getTrash($instructor_id);
    }
    
    public function acceptSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        return $this->submissionModel->accept($submission_id);
    }
    
    public function rejectSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        return $this->submissionModel->reject($submission_id);
    }
    
    public function deleteSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        return $this->submissionModel->moveToTrash($submission_id);
    }
    
    public function addFeedback(int $submission_id, int $instructor_id, string $feedback): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        
        // Validate length
        if (strlen($feedback) > 5000) {
            return false;
        }
        
        return $this->submissionModel->addFeedback($submission_id, $feedback);
    }
    
    public function restoreSubmission(int $submission_id, int $instructor_id): bool {
        if (!$this->instructorModel->ownsSubmission($instructor_id, $submission_id)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("UPDATE submissions SET status='active' WHERE id=?");
        $stmt->bind_param("i", $submission_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}

class InstructorControllerTest extends DatabaseTestCase
{
    private TestableInstructorController $controller;
    private int $testInstructorId;
    private int $testStudentId;
    private int $testCourseId;
    private int $testSubmissionId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up session for controller
        $_SESSION['user'] = [
            'id' => 1,
            'role' => 'instructor'
        ];
        
        // Create test instructor
        self::$conn->query("
            INSERT INTO users (name, email, role, password, status) 
            VALUES ('Test Instructor', 'instructor@test.com', 'instructor', 'hashed_password', 'active')
        ");
        $this->testInstructorId = self::$conn->insert_id;
        
        // Create test student
        self::$conn->query("
            INSERT INTO users (name, email, role, password, status) 
            VALUES ('Test Student', 'student@test.com', 'student', 'hashed_password', 'active')
        ");
        $this->testStudentId = self::$conn->insert_id;
        
        // Create test course
        self::$conn->query("
            INSERT INTO courses (name, instructor_id) 
            VALUES ('Test Course', {$this->testInstructorId})
        ");
        $this->testCourseId = self::$conn->insert_id;
        
        // Create test submission
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, text_content, status, created_at) 
            VALUES (
                {$this->testStudentId}, 
                {$this->testCourseId}, 
                'Test submission content', 
                'active', 
                NOW()
            )
        ");
        $this->testSubmissionId = self::$conn->insert_id;
        
        // Initialize controller with test connection
        $this->controller = new TestableInstructorController(self::$conn);
    }

    public function testGetInstructorReturnsData()
    {
        $instructor = $this->controller->getInstructor($this->testInstructorId);
        
        $this->assertNotNull($instructor);
        $this->assertEquals('Test Instructor', $instructor['name']);
        $this->assertEquals('instructor', $instructor['role']);
    }

    public function testGetStatsReturnsArray()
    {
        $stats = $this->controller->getStats($this->testInstructorId);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('students_enrolled', $stats);
        $this->assertArrayHasKey('total_submissions', $stats);
        $this->assertArrayHasKey('pending_submissions', $stats);
        $this->assertArrayHasKey('accepted_submissions', $stats);
        $this->assertArrayHasKey('rejected_submissions', $stats);
    }

    public function testGetEnrolledStudentsReturnsArray()
    {
        $students = $this->controller->getEnrolledStudents($this->testInstructorId);
        
        $this->assertIsArray($students);
        $this->assertGreaterThanOrEqual(1, count($students));
    }

    public function testGetSubmissionsReturnsArray()
    {
        $submissions = $this->controller->getSubmissions($this->testInstructorId);
        
        $this->assertIsArray($submissions);
        $this->assertCount(1, $submissions);
        $this->assertEquals($this->testSubmissionId, $submissions[0]['id']);
    }

    public function testAcceptSubmissionSuccess()
    {
        $result = $this->controller->acceptSubmission($this->testSubmissionId, $this->testInstructorId);
        
        $this->assertTrue($result);
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT status FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $this->testSubmissionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertEquals('accepted', $row['status']);
    }

    public function testAcceptSubmissionFailsForUnownedSubmission()
    {
        // Create another instructor
        self::$conn->query("
            INSERT INTO users (name, email, role, password) 
            VALUES ('Other Instructor', 'other@test.com', 'instructor', 'hashed_password')
        ");
        $otherInstructorId = self::$conn->insert_id;
        
        $result = $this->controller->acceptSubmission($this->testSubmissionId, $otherInstructorId);
        
        $this->assertFalse($result);
    }

    public function testRejectSubmissionSuccess()
    {
        $result = $this->controller->rejectSubmission($this->testSubmissionId, $this->testInstructorId);
        
        $this->assertTrue($result);
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT status FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $this->testSubmissionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertEquals('rejected', $row['status']);
    }

    public function testDeleteSubmissionSuccess()
    {
        $result = $this->controller->deleteSubmission($this->testSubmissionId, $this->testInstructorId);
        
        $this->assertTrue($result);
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT status FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $this->testSubmissionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertEquals('deleted', $row['status']);
    }

    public function testAddFeedbackSuccess()
    {
        $feedback = "Great work! Keep it up.";
        $result = $this->controller->addFeedback($this->testSubmissionId, $this->testInstructorId, $feedback);
        
        $this->assertTrue($result);
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT feedback FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $this->testSubmissionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertEquals($feedback, $row['feedback']);
    }

    public function testAddFeedbackFailsForTooLongText()
    {
        $longFeedback = str_repeat("a", 5001);
        $result = $this->controller->addFeedback($this->testSubmissionId, $this->testInstructorId, $longFeedback);
        
        $this->assertFalse($result);
    }

    public function testAddFeedbackFailsForUnownedSubmission()
    {
        // Create another instructor
        self::$conn->query("
            INSERT INTO users (name, email, role, password) 
            VALUES ('Other Instructor', 'other@test.com', 'instructor', 'hashed_password')
        ");
        $otherInstructorId = self::$conn->insert_id;
        
        $result = $this->controller->addFeedback($this->testSubmissionId, $otherInstructorId, "Feedback");
        
        $this->assertFalse($result);
    }

    public function testRestoreSubmissionSuccess()
    {
        // First delete the submission
        $this->controller->deleteSubmission($this->testSubmissionId, $this->testInstructorId);
        
        // Then restore it
        $result = $this->controller->restoreSubmission($this->testSubmissionId, $this->testInstructorId);
        
        $this->assertTrue($result);
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT status FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $this->testSubmissionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertEquals('active', $row['status']);
    }

    public function testGetTrashReturnsOnlyDeletedSubmissions()
    {
        // Delete the submission
        $this->controller->deleteSubmission($this->testSubmissionId, $this->testInstructorId);
        
        $trash = $this->controller->getTrash($this->testInstructorId);
        
        $this->assertCount(1, $trash);
        $this->assertEquals('deleted', $trash[0]['status']);
    }
}
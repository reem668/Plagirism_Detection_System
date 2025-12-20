<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Models/Instructor.php';

use PHPUnit\Framework\TestCase;
use Models\Instructor;

class InstructorTest extends DatabaseTestCase
{
    private Instructor $instructorModel;
    private int $testInstructorId;
    private int $testStudentId;
    private int $testCourseId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->instructorModel = new Instructor(self::$conn);
        
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
    }

    public function testGetInstructor()
    {
        $instructor = $this->instructorModel->getInstructor($this->testInstructorId);
        
        $this->assertNotNull($instructor);
        $this->assertEquals('Test Instructor', $instructor['name']);
        $this->assertEquals('instructor@test.com', $instructor['email']);
        $this->assertEquals('instructor', $instructor['role']);
    }

    public function testGetInstructorReturnsNullForNonExistent()
    {
        $instructor = $this->instructorModel->getInstructor(99999);
        $this->assertNull($instructor);
    }

    public function testGetInstructorReturnsNullForStudentRole()
    {
        $instructor = $this->instructorModel->getInstructor($this->testStudentId);
        $this->assertNull($instructor);
    }

    public function testGetSubmissionsReturnsEmpty()
    {
        $submissions = $this->instructorModel->getSubmissions($this->testInstructorId);
        $this->assertIsArray($submissions);
        $this->assertEmpty($submissions);
    }

    public function testGetSubmissionsReturnsCourseSubmissions()
    {
        // Create a submission for the test course
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, teacher, text_content, status, created_at) 
            VALUES (
                {$this->testStudentId}, 
                {$this->testCourseId}, 
                'Test Instructor', 
                'Sample submission text', 
                'active', 
                NOW()
            )
        ");
        
        $submissions = $this->instructorModel->getSubmissions($this->testInstructorId);
        
        $this->assertIsArray($submissions);
        $this->assertCount(1, $submissions);
        $this->assertEquals('Test Student', $submissions[0]['student_name']);
        $this->assertEquals('Test Course', $submissions[0]['course_name']);
    }

    public function testGetSubmissionsExcludesDeletedSubmissions()
    {
        // Create active submission
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES ({$this->testStudentId}, {$this->testCourseId}, 'active')
        ");
        
        // Create deleted submission
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES ({$this->testStudentId}, {$this->testCourseId}, 'deleted')
        ");
        
        $submissions = $this->instructorModel->getSubmissions($this->testInstructorId);
        
        $this->assertCount(1, $submissions);
        $this->assertEquals('active', $submissions[0]['status']);
    }

    public function testGetTrashReturnsOnlyDeletedSubmissions()
    {
        // Create active submission
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES ({$this->testStudentId}, {$this->testCourseId}, 'active')
        ");
        
        // Create deleted submission
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES ({$this->testStudentId}, {$this->testCourseId}, 'deleted')
        ");
        
        $trash = $this->instructorModel->getTrash($this->testInstructorId);
        
        $this->assertCount(1, $trash);
        $this->assertEquals('deleted', $trash[0]['status']);
    }

    public function testGetStatsReturnsCorrectCounts()
    {
        // Create test submissions with different statuses
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES 
                ({$this->testStudentId}, {$this->testCourseId}, 'active'),
                ({$this->testStudentId}, {$this->testCourseId}, 'pending'),
                ({$this->testStudentId}, {$this->testCourseId}, 'accepted'),
                ({$this->testStudentId}, {$this->testCourseId}, 'rejected'),
                ({$this->testStudentId}, {$this->testCourseId}, 'deleted')
        ");
        
        $stats = $this->instructorModel->getStats($this->testInstructorId);
        
        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['students_enrolled']); // 1 student created in setUp
        $this->assertEquals(4, $stats['total_submissions']); // Excludes deleted
        $this->assertEquals(2, $stats['pending_submissions']); // active + pending
        $this->assertEquals(1, $stats['accepted_submissions']);
        $this->assertEquals(1, $stats['rejected_submissions']);
    }

    public function testGetEnrolledStudentsReturnsAllStudents()
    {
        // Create additional student
        self::$conn->query("
            INSERT INTO users (name, email, role, password) 
            VALUES ('Student Two', 'student2@test.com', 'student', 'hashed_password')
        ");
        
        $students = $this->instructorModel->getEnrolledStudents();
        
        $this->assertIsArray($students);
        $this->assertCount(2, $students);
        $this->assertEquals('student', $students[0]['role']);
    }

    public function testOwnsSubmissionReturnsTrueForOwnedSubmission()
    {
        // Create submission for this instructor's course
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES ({$this->testStudentId}, {$this->testCourseId}, 'active')
        ");
        $submissionId = self::$conn->insert_id;
        
        $owns = $this->instructorModel->ownsSubmission($this->testInstructorId, $submissionId);
        
        $this->assertTrue($owns);
    }

    public function testOwnsSubmissionReturnsFalseForOtherInstructorSubmission()
    {
        // Create another instructor
        self::$conn->query("
            INSERT INTO users (name, email, role, password) 
            VALUES ('Other Instructor', 'other@test.com', 'instructor', 'hashed_password')
        ");
        $otherInstructorId = self::$conn->insert_id;
        
        // Create course for other instructor
        self::$conn->query("
            INSERT INTO courses (name, instructor_id) 
            VALUES ('Other Course', {$otherInstructorId})
        ");
        $otherCourseId = self::$conn->insert_id;
        
        // Create submission for other instructor's course
        self::$conn->query("
            INSERT INTO submissions (user_id, course_id, status) 
            VALUES ({$this->testStudentId}, {$otherCourseId}, 'active')
        ");
        $submissionId = self::$conn->insert_id;
        
        $owns = $this->instructorModel->ownsSubmission($this->testInstructorId, $submissionId);
        
        $this->assertFalse($owns);
    }

    public function testOwnsSubmissionReturnsFalseForNonExistentSubmission()
    {
        $owns = $this->instructorModel->ownsSubmission($this->testInstructorId, 99999);
        
        $this->assertFalse($owns);
    }

    public function testGetAllInstructorsReturnsOnlyInstructors()
    {
        // Create another instructor
        self::$conn->query("
            INSERT INTO users (name, email, role, password) 
            VALUES ('Second Instructor', 'instructor2@test.com', 'instructor', 'hashed_password')
        ");
        
        $instructors = $this->instructorModel->getAllInstructors();
        
        $this->assertIsArray($instructors);
        $this->assertCount(2, $instructors);
        
        foreach ($instructors as $instructor) {
            $this->assertArrayHasKey('id', $instructor);
            $this->assertArrayHasKey('name', $instructor);
            $this->assertArrayHasKey('email', $instructor);
        }
    }
}

<?php

use Controllers\CourseController;
use Repositories\CourseRepository;
use Models\Course;

require_once __DIR__ . '/DatabaseTestCase.php';

class CourseManagementTest extends DatabaseTestCase
{
    private CourseController $controller;
    private int $adminUserId;
    private int $instructorUserId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for testing
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status, admin_key) 
            VALUES (?, ?, ?, 'admin', 'active', ?)
        ");
        $name = 'Test Admin';
        $email = 'admin.' . uniqid() . '@test.com';
        $password = password_hash('Admin123!', PASSWORD_DEFAULT);
        $adminKey = 'test_admin_key';
        $stmt->bind_param('ssss', $name, $email, $password, $adminKey);
        $stmt->execute();
        $this->adminUserId = $stmt->insert_id;
        $stmt->close();

        // Create an instructor user for testing
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, 'instructor', 'active')
        ");
        $instName = 'Test Instructor';
        $instEmail = 'instructor.' . uniqid() . '@test.com';
        $instPassword = password_hash('Instructor123!', PASSWORD_DEFAULT);
        $stmt->bind_param('sss', $instName, $instEmail, $instPassword);
        $stmt->execute();
        $this->instructorUserId = $stmt->insert_id;
        $stmt->close();

        // Set up session for admin
        $_SESSION['user_id'] = $this->adminUserId;
        $_SESSION['user_name'] = 'Test Admin';
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['logged_in'] = true;
        $_SESSION['_csrf'] = 'test_token';

        // Pass test connection to controller
        $this->controller = new CourseController(self::$conn);
    }

    public function testGetCoursesReturnsAllCourses(): void
    {
        // Create test courses
        $this->createTestCourse('Course 1', $this->instructorUserId);
        $this->createTestCourse('Course 2', $this->instructorUserId);

        $result = $this->controller->getCourses(1, 10);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(2, count($result['data']));
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testGetCoursesWithSearch(): void
    {
        $this->createTestCourse('Introduction to Programming', $this->instructorUserId);
        $this->createTestCourse('Advanced Mathematics', $this->instructorUserId);

        $result = $this->controller->getCourses(1, 10, 'Programming');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, count($result['data']));
        
        $found = false;
        foreach ($result['data'] as $course) {
            if (stripos($course['name'], 'Programming') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetCourseById(): void
    {
        $courseId = $this->createTestCourse('Test Course', $this->instructorUserId);

        $result = $this->controller->getCourse($courseId);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Test Course', $result['data']['name']);
    }

    public function testGetCourseWithInvalidId(): void
    {
        $result = $this->controller->getCourse(99999);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testAddCourseSuccess(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'name' => 'New Course',
            'description' => 'Course description',
            'instructor_id' => $this->instructorUserId
        ];

        $result = $this->controller->addCourse($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('New Course', $result['data']['name']);

        // Verify course was created
        $stmt = self::$conn->prepare("SELECT * FROM courses WHERE name = ?");
        $name = 'New Course';
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($course);
        $this->assertEquals('New Course', $course['name']);
        $this->assertEquals($this->instructorUserId, $course['instructor_id']);
    }

    public function testAddCourseWithInvalidInstructor(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'name' => 'New Course',
            'description' => 'Course description',
            'instructor_id' => 99999 // Invalid instructor ID
        ];

        $result = $this->controller->addCourse($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid instructor', $result['message']);
    }

    public function testAddCourseWithDuplicateName(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $this->createTestCourse('Duplicate Course', $this->instructorUserId);

        $data = [
            '_csrf' => 'test_token',
            'name' => 'Duplicate Course',
            'description' => 'Course description',
            'instructor_id' => $this->instructorUserId
        ];

        $result = $this->controller->addCourse($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }

    public function testAddCourseWithEmptyName(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'name' => '',
            'description' => 'Course description',
            'instructor_id' => $this->instructorUserId
        ];

        $result = $this->controller->addCourse($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', $result['message']);
    }

    public function testEditCourseSuccess(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $courseId = $this->createTestCourse('Original Course', $this->instructorUserId);

        $data = [
            '_csrf' => 'test_token',
            'course_id' => $courseId,
            'name' => 'Updated Course',
            'description' => 'Updated description',
            'instructor_id' => $this->instructorUserId
        ];

        $result = $this->controller->editCourse($data);

        $this->assertTrue($result['success']);

        // Verify changes
        $stmt = self::$conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertEquals('Updated Course', $course['name']);
        $this->assertEquals('Updated description', $course['description']);
    }

    public function testEditCourseWithInvalidId(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'course_id' => 99999,
            'name' => 'Updated Course',
            'description' => 'Updated description',
            'instructor_id' => $this->instructorUserId
        ];

        $result = $this->controller->editCourse($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testDeleteCourseSuccess(): void
    {
        $courseId = $this->createTestCourse('Course to Delete', $this->instructorUserId);

        $result = $this->controller->deleteCourse($courseId);

        $this->assertTrue($result['success']);

        // Verify deletion
        $stmt = self::$conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNull($course);
    }

    public function testDeleteCourseWithSubmissions(): void
    {
        $courseId = $this->createTestCourse('Course with Submissions', $this->instructorUserId);
        
        // Create a submission for this course
        $studentId = $this->createTestUser('student@test.com', 'student');
        $stmt = self::$conn->prepare("
            INSERT INTO submissions (user_id, course_id, instructor_id, title, status) 
            VALUES (?, ?, ?, 'Test Submission', 'active')
        ");
        $title = 'Test Submission';
        $stmt->bind_param('iii', $studentId, $courseId, $this->instructorUserId);
        $stmt->execute();
        $stmt->close();

        $result = $this->controller->deleteCourse($courseId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('submission', $result['message']);
    }

    public function testGetInstructors(): void
    {
        // Create another instructor
        $this->createTestUser('instructor2@test.com', 'instructor');

        $result = $this->controller->getInstructors();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertGreaterThanOrEqual(2, count($result['data']));
        
        // Verify all are instructors
        foreach ($result['data'] as $instructor) {
            $this->assertArrayHasKey('id', $instructor);
            $this->assertArrayHasKey('name', $instructor);
            $this->assertArrayHasKey('email', $instructor);
        }
    }

    public function testAssignInstructor(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $courseId = $this->createTestCourse('Test Course', $this->instructorUserId);
        
        // Create another instructor
        $newInstructorId = $this->createTestUser('newinstructor@test.com', 'instructor');

        $data = [
            '_csrf' => 'test_token',
            'course_id' => $courseId,
            'instructor_id' => $newInstructorId
        ];

        $result = $this->controller->assignInstructor($data);

        $this->assertTrue($result['success']);

        // Verify assignment
        $stmt = self::$conn->prepare("SELECT instructor_id FROM courses WHERE id = ?");
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertEquals($newInstructorId, $course['instructor_id']);
    }

    public function testAssignInstructorWithInvalidCourse(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'course_id' => 99999,
            'instructor_id' => $this->instructorUserId
        ];

        $result = $this->controller->assignInstructor($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testUnauthorizedAccessDenied(): void
    {
        // Clear admin session
        $_SESSION = [];

        $this->expectException(\Exception::class);
        
        $controller = new CourseController();
        $controller->getCourses();
    }

    public function testNonAdminAccessDenied(): void
    {
        // Change to student role
        $_SESSION['user_role'] = 'student';
        $_SESSION['logged_in'] = true;

        $this->expectException(\Exception::class);
        
        $controller = new CourseController();
        $controller->getCourses();
    }

    // Helper methods
    private function createTestCourse(string $name, int $instructorId, string $description = null): int
    {
        $stmt = self::$conn->prepare("
            INSERT INTO courses (name, description, instructor_id) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ssi', $name, $description, $instructorId);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    private function createTestUser(string $email, string $role, string $name = 'Test User'): int
    {
        // Make email unique
        $email = uniqid() . '.' . $email;
        
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        $password = password_hash('Password123!', PASSWORD_DEFAULT);
        $stmt->bind_param('ssss', $name, $email, $password, $role);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
}


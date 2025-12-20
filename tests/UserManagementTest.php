<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';

use Controllers\UserController;

class UserManagementTest extends DatabaseTestCase
{
    private UserController $controller;
    private int $adminUserId;

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

        // Set up session for admin
        $_SESSION['user_id'] = $this->adminUserId;
        $_SESSION['user_name'] = 'Test Admin';
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['logged_in'] = true;

        // Pass test connection to controller
        $this->controller = new UserController(self::$conn);
    }

    public function testGetUsersReturnsAllUsers(): void
    {
        // Create test users
        $this->createTestUser('student1@test.com', 'student');
        $this->createTestUser('instructor1@test.com', 'instructor');

        $result = $this->controller->getUsers(1, 10);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(3, count($result['users'])); // Admin + 2 test users
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testGetUsersWithRoleFilter(): void
    {
        $this->createTestUser('student1@test.com', 'student');
        $this->createTestUser('instructor1@test.com', 'instructor');

        $result = $this->controller->getUsers(1, 10, '', 'student');

        $this->assertTrue($result['success']);
        foreach ($result['users'] as $user) {
            $this->assertEquals('student', $user['role']);
        }
    }

    public function testGetUsersWithSearch(): void
    {
        $this->createTestUser('john.doe@test.com', 'student', 'John Doe');
        $this->createTestUser('jane.smith@test.com', 'student', 'Jane Smith');

        $result = $this->controller->getUsers(1, 10, 'john');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, count($result['users']));
        
        $found = false;
        foreach ($result['users'] as $user) {
            if (stripos($user['name'], 'john') !== false || 
                stripos($user['email'], 'john') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testAddUserSuccess(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'name' => 'New Student',
            'email' => 'newstudent@test.com',
            'role' => 'student',
            'password' => 'Password123!'
        ];

        $result = $this->controller->addUser($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);

        // Verify user was created
        $stmt = self::$conn->prepare("SELECT * FROM users WHERE email = ?");
        $email = 'newstudent@test.com';
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($user);
        $this->assertEquals('New Student', $user['name']);
        $this->assertEquals('student', $user['role']);
        $this->assertEquals('active', $user['status']);
    }

    public function testAddUserWithInvalidEmail(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'name' => 'Test User',
            'email' => 'invalid-email',
            'role' => 'student',
            'password' => 'Password123!'
        ];

        $result = $this->controller->addUser($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email', $result['message']);
    }

    public function testAddUserWithDuplicateEmail(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        // Create a user directly without unique prefix to test duplicate email
        $email = 'duplicate@test.com';
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, 'student', 'active')
        ");
        $name = 'First User';
        $password = password_hash('Password123!', PASSWORD_DEFAULT);
        $stmt->bind_param('sss', $name, $email, $password);
        $stmt->execute();
        $stmt->close();

        $data = [
            '_csrf' => 'test_token',
            'name' => 'Another User',
            'email' => 'duplicate@test.com',
            'role' => 'student',
            'password' => 'Password123!'
        ];

        $result = $this->controller->addUser($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }

    public function testAddUserWithInvalidRole(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'name' => 'Test User',
            'email' => 'test@test.com',
            'role' => 'invalid_role',
            'password' => 'Password123!'
        ];

        $result = $this->controller->addUser($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid role', $result['message']);
    }

    public function testEditUserSuccess(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $userId = $this->createTestUser('edit@test.com', 'student', 'Original Name');

        $data = [
            '_csrf' => 'test_token',
            'user_id' => $userId,
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'role' => 'instructor',
            'status' => 'active'
        ];

        $result = $this->controller->editUser($data);

        $this->assertTrue($result['success']);

        // Verify changes
        $stmt = self::$conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertEquals('Updated Name', $user['name']);
        $this->assertEquals('updated@test.com', $user['email']);
        $this->assertEquals('instructor', $user['role']);
    }

    public function testEditUserCannotChangeSelfRole(): void
    {
        $_POST['_csrf'] = $_SESSION['_csrf'] = 'test_token';
        
        $data = [
            '_csrf' => 'test_token',
            'user_id' => $this->adminUserId,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'role' => 'student', // Trying to demote self
            'status' => 'active'
        ];

        $result = $this->controller->editUser($data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot change your own role', $result['message']);
    }

    public function testDeleteUserSuccess(): void
    {
        $userId = $this->createTestUser('delete@test.com', 'student');

        $result = $this->controller->deleteUser($userId);

        $this->assertTrue($result['success']);

        // Verify deletion
        $stmt = self::$conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNull($user);
    }

    public function testDeleteUserCannotDeleteSelf(): void
    {
        $result = $this->controller->deleteUser($this->adminUserId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot delete your own account', $result['message']);
    }

    public function testToggleStatusSuccess(): void
    {
        $userId = $this->createTestUser('toggle@test.com', 'student');

        // Ban user
        $result = $this->controller->toggleStatus($userId);

        $this->assertTrue($result['success']);
        $this->assertEquals('banned', $result['new_status']);

        // Verify status
        $stmt = self::$conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $status = $stmt->get_result()->fetch_assoc()['status'];
        $stmt->close();

        $this->assertEquals('banned', $status);

        // Unban user
        $result = $this->controller->toggleStatus($userId);

        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['new_status']);
    }

    public function testToggleStatusCannotBanSelf(): void
    {
        $result = $this->controller->toggleStatus($this->adminUserId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot ban/unban yourself', $result['message']);
    }

    public function testUnauthorizedAccessDenied(): void
    {
        // Clear admin session
        $_SESSION = [];

        $this->expectException(\Exception::class);
        
        $controller = new UserController();
        $controller->getUsers();
    }

    public function testNonAdminAccessDenied(): void
    {
        // Change to student role
        $_SESSION['user_role'] = 'student';

        $this->expectException(\Exception::class);
        
        $controller = new UserController();
        $controller->getUsers();
    }

    // Helper method
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
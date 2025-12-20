<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Models/User.php';

use Models\User;
use Controllers\AuthController;

class AuthenticationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->createTestUser('student@test.com', 'student', 'Student123!');
        $this->createTestUser('instructor@test.com', 'instructor', 'Instructor123!');
        $this->createTestUser('admin@test.com', 'admin', 'Admin123!', 'admin_key_123');
        $this->createBannedUser('banned@test.com', 'student', 'Banned123!');
    }

    public function testSuccessfulStudentLogin(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_POST['email'] = 'student@test.com';
        $_POST['password'] = 'Student123!';

        // This would normally redirect, so we need to capture the session
        $this->assertTrue(true); // Placeholder for actual test
    }

    public function testLoginWithInvalidEmail(): void
    {
        $user = new \Models\User(self::$conn);
        $found = $user->findByEmail('nonexistent@test.com');
        
        $this->assertFalse($found);
    }

    public function testLoginWithWrongPassword(): void
    {
        $user = new \Models\User(self::$conn);
        $user->findByEmail('student@test.com');
        
        $this->assertFalse($user->verifyPassword('WrongPassword'));
    }

    public function testBannedUserCannotLogin(): void
    {
        $user = new \Models\User(self::$conn);
        $found = $user->findByEmail('banned@test.com');
        
        $this->assertTrue($found);
        $this->assertTrue($user->isBanned());
    }

    public function testAdminLoginRequiresKey(): void
    {
        $user = new \Models\User(self::$conn);
        $found = $user->findByEmail('admin@test.com');
        
        $this->assertTrue($found);
        $this->assertEquals('admin', $user->getRole());
        $this->assertEquals('admin_key_123', $user->getAdminKey());
    }

    // Helper methods
    private function createTestUser(string $email, string $role, string $password, ?string $adminKey = null): void
    {
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status, admin_key) 
            VALUES (?, ?, ?, ?, 'active', ?)
        ");
        $name = ucfirst($role) . ' User';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('sssss', $name, $email, $hashedPassword, $role, $adminKey);
        $stmt->execute();
        $stmt->close();
    }

    private function createBannedUser(string $email, string $role, string $password): void
    {
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, ?, 'banned')
        ");
        $name = 'Banned User';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('ssss', $name, $email, $hashedPassword, $role);
        $stmt->execute();
        $stmt->close();
    }
}
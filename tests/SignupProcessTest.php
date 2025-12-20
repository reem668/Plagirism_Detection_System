<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Models/User.php';

class SignupProcessTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User(self::$conn);
    }

    public function testSuccessfulSignup(): void
    {
        $email = 'john.doe.' . uniqid() . '@gmail.com';
        
        $this->user->setName('John Doe');
        $this->user->setEmail($email);
        $this->user->setPassword('SecurePass123!');
        $this->user->setMobile('01234567890');
        $this->user->setCountry('us');
        $this->user->setRole('student');

        $result = $this->user->save();
        $this->assertTrue($result);

        $userId = $this->user->getId();
        $this->assertGreaterThan(0, $userId);

        $stmt = self::$conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $savedUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($savedUser, "User should exist in database");
        $this->assertEquals('John Doe', $savedUser['name']);
        $this->assertEquals('student', $savedUser['role']);
        $this->assertEquals('active', $savedUser['status']);
    }

    public function testSignupWithShortName(): void
    {
        $email = 'test.' . uniqid() . '@gmail.com';
        
        $user = new User(self::$conn);
        $user->setName('Jo');
        $user->setEmail($email);
        $user->setPassword('SecurePass123!');
        $user->setMobile('01234567890');
        $user->setCountry('us');
        $user->setRole('student');

        $result = $user->save();
        $this->assertTrue($result);
    }

    public function testEmailUniqueness(): void
    {
        $email = 'unique.' . uniqid() . '@gmail.com';
        
        $user1 = new User(self::$conn);
        $user1->setName('First User');
        $user1->setEmail($email);
        $user1->setPassword('SecurePass123!');
        $user1->setMobile('01234567890');
        $user1->setCountry('us');
        $user1->setRole('student');
        $user1->save();

        $exists = $user1->emailExists($email);
        $this->assertTrue($exists);

        $user2 = new User(self::$conn);
        $user2->setName('Second User');
        $user2->setEmail($email);
        $user2->setPassword('AnotherPass123!');
        $user2->setMobile('09876543210');
        $user2->setCountry('us');
        $user2->setRole('instructor');

        $this->expectException(mysqli_sql_exception::class);
        $user2->save();
    }

    public function testPasswordHashing(): void
    {
        $plainPassword = 'MySecurePassword123!';
        $email = 'test.' . uniqid() . '@gmail.com';
        
        $user = new User(self::$conn);
        $user->setName('Test User');
        $user->setEmail($email);
        $user->setPassword($plainPassword);
        $user->setMobile('01234567890');
        $user->setCountry('us');
        $user->setRole('student');
        $saved = $user->save();
        
        $this->assertTrue($saved, "User should be saved");

        $stmt = self::$conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertNotNull($result, "Password result should not be null");
        $hashedPassword = $result['password'];

        $this->assertNotEquals($plainPassword, $hashedPassword);
        $this->assertTrue(password_verify($plainPassword, $hashedPassword));
    }

    public function testFindByEmail(): void
    {
        $email = 'findme.' . uniqid() . '@gmail.com';
        
        $user1 = new User(self::$conn);
        $user1->setName('Findable User');
        $user1->setEmail($email);
        $user1->setPassword('SecurePass123!');
        $user1->setMobile('01234567890');
        $user1->setCountry('us');
        $user1->setRole('instructor');
        $user1->save();

        $user2 = new User(self::$conn);
        $found = $user2->findByEmail($email);

        $this->assertTrue($found);
        $this->assertEquals('Findable User', $user2->getName());
        $this->assertEquals('instructor', $user2->getRole());
        $this->assertEquals('active', $user2->getStatus());
    }

    public function testVerifyPassword(): void
    {
        $password = 'CorrectPassword123!';
        $email = 'verify.' . uniqid() . '@gmail.com';
        
        $user1 = new User(self::$conn);
        $user1->setName('Test User');
        $user1->setEmail($email);
        $user1->setPassword($password);
        $user1->setMobile('01234567890');
        $user1->setCountry('us');
        $user1->setRole('student');
        $user1->save();

        $user2 = new User(self::$conn);
        $user2->findByEmail($email);

        $this->assertTrue($user2->verifyPassword($password));
        $this->assertFalse($user2->verifyPassword('WrongPassword'));
    }

    public function testDefaultStatusIsActive(): void
    {
        $email = 'active.' . uniqid() . '@gmail.com';
        
        $user = new User(self::$conn);
        $user->setName('Active User');
        $user->setEmail($email);
        $user->setPassword('SecurePass123!');
        $user->setMobile('01234567890');
        $user->setCountry('us');
        $user->setRole('student');
        $saved = $user->save();
        
        $this->assertTrue($saved, "User should be saved");

        $stmt = self::$conn->prepare("SELECT status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertNotNull($result, "Status result should not be null");
        $status = $result['status'];

        $this->assertEquals('active', $status);
    }

    public function testBannedUserCannotLogin(): void
    {
        $email = 'banned.' . uniqid() . '@gmail.com';
        
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, ?, 'student', 'banned')
        ");
        $name = 'Banned User';
        $password = password_hash('SecurePass123!', PASSWORD_DEFAULT);
        $stmt->bind_param('sss', $name, $email, $password);
        $executed = $stmt->execute();
        $stmt->close();
        
        $this->assertTrue($executed, "Banned user should be inserted");

        $user = new User(self::$conn);
        $found = $user->findByEmail($email);

        $this->assertTrue($found, "Should find banned user");
        $this->assertTrue($user->isBanned());
        $this->assertEquals('banned', $user->getStatus());
    }

    public function testMultipleRoles(): void
    {
        $roles = ['student', 'instructor', 'admin'];
        $createdIds = [];

        foreach ($roles as $role) {
            $user = new User(self::$conn);
            $user->setName("User $role");
            $user->setEmail($role . '.' . uniqid() . "@gmail.com");
            $user->setPassword('SecurePass123!');
            $user->setMobile('01234567890');
            $user->setCountry('us');
            $user->setRole($role);
            $result = $user->save();

            $this->assertTrue($result, "User with role $role should be saved");
            $createdIds[$role] = $user->getId();
        }

        // Verify by checking the actual IDs we created
        foreach ($createdIds as $role => $userId) {
            $stmt = self::$conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $this->assertNotNull($result, "User with role $role should exist");
            $this->assertEquals($role, $result['role']);
        }
    }
}
?>
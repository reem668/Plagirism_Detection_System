<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../app/Services/ChatbotService.php';
require_once __DIR__ . '/DatabaseTestCase.php';

class ChatbotServiceTest extends DatabaseTestCase
{
    public function testGreetingIntentReturnsSuccessResponse(): void
    {
        $conn = self::$conn;

        // Arrange: create a student user
        $conn->query("
            INSERT INTO users (name, email, password, role, status)
            VALUES ('Student One', 'student1@example.com', 'hash', 'student', 'active')
        ");
        $userId = (int)$conn->insert_id;

        // Act
        $result = ChatbotService::getResponse('Hello, bot!', $userId, 'student', $conn);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('intent', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('suggestions', $result);

        $this->assertNotEmpty($result['response']);
        $this->assertIsString($result['response']);
        $this->assertEquals('greeting', $result['intent']);
        $this->assertIsArray($result['suggestions']);
    }
}



<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Controllers/AdminSubmissionController.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

class AdminSubmissionTest extends TestCase
{
    private $mockConn;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set admin session for tests
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_id'] = 1;

        // Create mock database connection
        $this->mockConn = $this->createMock(mysqli::class);
        $this->mockConn->connect_error = null;
    }

    public function testGetStatisticsReturnsCorrectStructure(): void
    {
        // Create mock results for each query
        $mockResult1 = $this->createMock(mysqli_result::class);
        $mockResult1->method('fetch_assoc')->willReturn(['total' => 10]);
        
        $mockResult2 = $this->createMock(mysqli_result::class);
        $mockResult2->method('fetch_assoc')->willReturn(['completed' => 7]);
        
        $mockResult3 = $this->createMock(mysqli_result::class);
        $mockResult3->method('fetch_assoc')->willReturn(['processing' => 2]);
        
        $mockResult4 = $this->createMock(mysqli_result::class);
        $mockResult4->method('fetch_assoc')->willReturn(['avg_similarity' => 45.5]);
        
        $mockResult5 = $this->createMock(mysqli_result::class);
        $mockResult5->method('fetch_assoc')->willReturn(['high_risk' => 3]);
        
        $mockResult6 = $this->createMock(mysqli_result::class);
        $mockResult6->method('fetch_assoc')->willReturn(['medium_risk' => 4]);
        
        $mockResult7 = $this->createMock(mysqli_result::class);
        $mockResult7->method('fetch_assoc')->willReturn(['low_risk' => 3]);
        
        $this->mockConn->method('query')
            ->willReturnOnConsecutiveCalls(
                $mockResult1, $mockResult2, $mockResult3, 
                $mockResult4, $mockResult5, $mockResult6, $mockResult7
            );
        
        // Create controller with mocked connection
        $controller = $this->createControllerWithMock();
        $stats = $controller->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('avg_similarity', $stats);
        $this->assertArrayHasKey('high_risk', $stats);
        $this->assertArrayHasKey('medium_risk', $stats);
        $this->assertArrayHasKey('low_risk', $stats);
        
        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(7, $stats['completed']);
        $this->assertEquals(2, $stats['processing']);
        $this->assertEquals(45.5, $stats['avg_similarity']);
        $this->assertEquals(3, $stats['high_risk']);
        $this->assertEquals(4, $stats['medium_risk']);
        $this->assertEquals(3, $stats['low_risk']);
    }

    public function testUpdateStatusAcceptsValidStatus(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        
        $controller = $this->createControllerWithMock();
        $result = $controller->updateStatus(1, 'completed');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testUpdateStatusRejectsInvalidStatus(): void
    {
        $controller = $this->createControllerWithMock();
        $result = $controller->updateStatus(1, 'invalid_status');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid status', $result['message']);
    }

    public function testGetSubmissionDetailsReturnsSubmission(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        
        $submissionData = [
            'id' => 1,
            'student_name' => 'Ahmed Hassan',
            'student_email' => 'ahmed@student.edu',
            'similarity' => 45,
            'status' => 'completed',
            'created_at' => '2024-10-20 10:30:00'
        ];
        
        $mockResult->method('fetch_assoc')->willReturn($submissionData);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);
        $mockStmt->method('close')->willReturn(true);
        $mockStmt->method('bind_param')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        
        $controller = $this->createControllerWithMock();
        $result = $controller->getSubmissionDetails(1);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Ahmed Hassan', $result['student_name']);
    }

    public function testDeleteSubmissionRequiresPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $controller = $this->createControllerWithMock();
        $result = $controller->deleteSubmission(1);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
    }

    public function testDeleteSubmissionRequiresCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_csrf'] = 'invalid_token';
        
        $controller = $this->createControllerWithMock();
        $result = $controller->deleteSubmission(1);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid CSRF token', $result['message']);
    }

    public function testRequireAdminBlocksNonAdmin(): void
    {
        // Change to non-admin
        $_SESSION['user_role'] = 'student';
        
        $this->expectException(Exception::class);
        
        $controller = $this->createControllerWithMock();
        
        // Use reflection to call protected method
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('requireAdmin');
        $method->setAccessible(true);
        
        // This should throw exception or exit
        $method->invoke($controller);
    }

    /**
     * Helper method to create controller with mocked connection
     */
    private function createControllerWithMock()
    {
        // Create a temporary include file for db.php
        $tempDbFile = sys_get_temp_dir() . '/test_db.php';
        file_put_contents($tempDbFile, '<?php $conn = null;');
        
        // Create controller
        $controller = new Controllers\AdminSubmissionController();
        
        // Use reflection to inject mock connection
        $reflection = new ReflectionClass($controller);
        $property = $reflection->getProperty('conn');
        $property->setAccessible(true);
        $property->setValue($controller, $this->mockConn);
        
        // Clean up
        @unlink($tempDbFile);
        
        return $controller;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        $this->mockConn = null;
    }
}
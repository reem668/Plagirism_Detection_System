<?php

require_once __DIR__ . '/../app/Controllers/SubmissionController.php';
require_once __DIR__ . '/../app/Models/Submission.php';

use PHPUnit\Framework\TestCase;
use Controllers\SubmissionController;
use Models\Submission;

class SubmissionControllerTest extends TestCase
{
    public function testGetUserSubmissionsReturnsData()
    {
        $conn = $this->createMock(mysqli::class);
        $stmt = $this->createMock(mysqli_stmt::class);
        $result = $this->createMock(mysqli_result::class);

        $conn->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($result);

        $result->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'status' => 'pending'],
                null
            );

        $submissionMock = $this->createMock(Submission::class);

        $controller = new SubmissionController($submissionMock, $conn);
        $data = $controller->getUserSubmissions(1);

        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['id']);
    }
}

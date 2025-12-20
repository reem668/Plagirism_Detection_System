<?php

require_once __DIR__ . '/../app/Services/PlagiarismService.php';

use PHPUnit\Framework\TestCase;

class PlagiarismServiceTest extends TestCase
{
    public function testDetectsPlagiarism()
{
    $service = new PlagiarismService();

    $existing = [
        ['text_content' => 'this is a plagiarized sentence example']
    ];

    $result = $service->check(
        'this is a plagiarized sentence example',
        $existing
    );

    var_dump($result); // <--- add this line

    $this->assertGreaterThan(0, $result['plagiarised']);
    $this->assertNotEmpty($result['matchingWords']);
}


    public function testNoPlagiarism()
    {
        $service = new PlagiarismService();

        $result = $service->check(
            'completely unique text',
            []
        );

        $this->assertEquals(0, $result['plagiarised']);
    }
}

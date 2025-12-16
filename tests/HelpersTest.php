<?php

use PHPUnit\Framework\TestCase;
use Controllers\SubmissionController;

require_once __DIR__ . '/../Controllers/SubmissionController.php';

class HelpersTest extends TestCase
{
    public function testExtractFileTextTxt()
    {
        $controller = $this->getMockBuilder(SubmissionController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // ✅ Create a REAL .txt file
        $file = sys_get_temp_dir() . '/test_file.txt';
        file_put_contents($file, 'hello world');

        $method = new ReflectionMethod($controller, 'extractFileText');
        $method->setAccessible(true);

        $text = $method->invoke($controller, $file);

        $this->assertEquals('hello world', $text);

        unlink($file); // cleanup
    }
}

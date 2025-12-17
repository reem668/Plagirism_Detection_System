<?php

require_once __DIR__ . '/../Helpers/Validator.php';

use PHPUnit\Framework\TestCase;
use Helpers\Validator;

class ValidationTest extends TestCase
{
    public function testSanitizeRemovesXSS(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $sanitized = Validator::sanitize($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
    }

    public function testSanitizeTrimWhitespace(): void
    {
        $input = '  Hello World  ';
        $sanitized = Validator::sanitize($input);
        
        $this->assertEquals('Hello World', $sanitized);
    }

    public function testSanitizeHandlesQuotes(): void
    {
        $input = "It's a \"test\"";
        $sanitized = Validator::sanitize($input);
        
        $this->assertNotEquals($input, $sanitized);
    }
}
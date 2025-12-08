<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Models/Settings.php';

class SettingsTest extends TestCase
{
    private $mockDb;
    private $settings;

    protected function setUp(): void
    {
        // Mock database connection
        $this->mockDb = $this->createMock(mysqli::class);
        $this->settings = new Models\Settings($this->mockDb);
    }

    public function testGetDefaultSettings()
    {
        // Mock empty database - should return defaults
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn(null);
        
        $this->mockDb->method('query')->willReturn($mockResult);
        
        $defaults = $this->settings->getDefaults();
        
        $this->assertEquals('10', $defaults['max_upload_size']);
        $this->assertEquals('50', $defaults['plagiarism_threshold']);
        $this->assertEquals('20', $defaults['submission_quota']);
    }

    public function testSetSetting()
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockDb->method('prepare')->willReturn($mockStmt);
        
        $result = $this->settings->set('plagiarism_threshold', '75');
        
        $this->assertTrue($result);
    }

    public function testUpdateMultipleSettings()
    {
        $this->mockDb->method('begin_transaction')->willReturn(true);
        $this->mockDb->method('commit')->willReturn(true);
        
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockDb->method('prepare')->willReturn($mockStmt);
        
        $newSettings = [
            'max_upload_size' => '15',
            'plagiarism_threshold' => '60',
            'submission_quota' => '25'
        ];
        
        $result = $this->settings->updateMultiple($newSettings);
        
        $this->assertTrue($result);
    }

    public function testResetToDefaults()
    {
        $this->mockDb->method('begin_transaction')->willReturn(true);
        $this->mockDb->method('commit')->willReturn(true);
        
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockDb->method('prepare')->willReturn($mockStmt);
        
        $result = $this->settings->reset();
        
        $this->assertTrue($result);
    }
}
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Models/Settings.php';

class AdminSettingsTest extends TestCase
{
    private $mockConn;
    private $settings;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock mysqli connection
        $this->mockConn = $this->createMock(mysqli::class);
        $this->mockConn->connect_error = null;
        
        // Initialize settings model with mock
        $this->settings = new Models\Settings($this->mockConn);
    }

    public function testGetAllReturnsDefaults(): void
    {
        // Mock empty query result (no settings in DB)
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn(false);
        
        $this->mockConn->method('query')->willReturn($mockResult);
        
        $result = $this->settings->getAll();
        
        $this->assertEquals('10', $result['max_upload_size']);
        $this->assertEquals('50', $result['plagiarism_threshold']);
        $this->assertEquals('20', $result['submission_quota']);
    }

    public function testGetReturnsSpecificSetting(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        
        // Mock returning a setting value
        $mockResult->method('fetch_assoc')->willReturn(['setting_value' => '15']);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        
        $value = $this->settings->get('max_upload_size');
        
        $this->assertEquals('15', $value);
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);
        
        // Mock returning null (key not found)
        $mockResult->method('fetch_assoc')->willReturn(null);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        
        $value = $this->settings->get('non_existent_key', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function testSetStoresSetting(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->expects($this->once())
                 ->method('bind_param')
                 ->with('sss', 'max_upload_size', '20', '20')
                 ->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        
        $result = $this->settings->set('max_upload_size', '20');
        
        $this->assertTrue($result);
    }

    public function testUpdateMultipleStoresAllSettings(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        $this->mockConn->expects($this->once())->method('begin_transaction')->willReturn(true);
        $this->mockConn->expects($this->once())->method('commit')->willReturn(true);
        
        $settings = [
            'max_upload_size' => '25',
            'plagiarism_threshold' => '60',
            'submission_quota' => '30'
        ];
        
        $result = $this->settings->updateMultiple($settings);
        
        $this->assertTrue($result);
    }

    public function testUpdateMultipleRollbacksOnError(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willThrowException(new Exception('DB Error'));
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        $this->mockConn->expects($this->once())->method('begin_transaction')->willReturn(true);
        $this->mockConn->expects($this->once())->method('rollback')->willReturn(true);
        
        $settings = [
            'max_upload_size' => '25',
            'plagiarism_threshold' => '60'
        ];
        
        $result = $this->settings->updateMultiple($settings);
        
        $this->assertFalse($result);
    }

    public function testResetRestoresDefaults(): void
    {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('close')->willReturn(true);
        
        $this->mockConn->method('prepare')->willReturn($mockStmt);
        $this->mockConn->method('begin_transaction')->willReturn(true);
        $this->mockConn->method('commit')->willReturn(true);
        
        $result = $this->settings->reset();
        
        $this->assertTrue($result);
    }

    public function testGetDefaultsReturnsCorrectValues(): void
    {
        $defaults = $this->settings->getDefaults();
        
        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('max_upload_size', $defaults);
        $this->assertArrayHasKey('plagiarism_threshold', $defaults);
        $this->assertArrayHasKey('submission_quota', $defaults);
        $this->assertEquals('10', $defaults['max_upload_size']);
        $this->assertEquals('50', $defaults['plagiarism_threshold']);
        $this->assertEquals('20', $defaults['submission_quota']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->mockConn = null;
        $this->settings = null;
    }
}
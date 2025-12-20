<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Models/Settings.php';

use Models\Settings;

class SettingsTest extends DatabaseTestCase
{
    public function testDefaultsReturnWhenTableEmpty(): void
    {
        $settings = new Settings(self::$conn);
        $all = $settings->getAll();

        $this->assertEquals($settings->getDefaults(), $all);
    }

    public function testSetAndGetSetting(): void
    {
        $settings = new Settings(self::$conn);
        $this->assertTrue($settings->set('max_upload_size', '20'));

        $value = $settings->get('max_upload_size');
        $this->assertSame('20', $value);
    }

    public function testUpdateMultipleIsTransactional(): void
    {
        $settings = new Settings(self::$conn);
        $result = $settings->updateMultiple([
            'max_upload_size' => '15',
            'plagiarism_threshold' => '70',
            'submission_quota' => '5',
        ]);

        $this->assertTrue($result);
        $this->assertSame('15', $settings->get('max_upload_size'));
        $this->assertSame('70', $settings->get('plagiarism_threshold'));
        $this->assertSame('5', $settings->get('submission_quota'));
    }
}


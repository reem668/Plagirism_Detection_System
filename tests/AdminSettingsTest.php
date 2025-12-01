<?php
require_once __DIR__ . '/../Models/Settings.php';
require_once __DIR__ . '/Support/TestHarness.php';
require_once __DIR__ . '/Support/FakeMysqli.php';

use Models\Settings;
use Tests\Support\TestHarness;
use Tests\Support\FakeMysqli;

$harness = new TestHarness('Admin Settings');
$db = new FakeMysqli();
$settings = new Settings($db);

$harness->run('Defaults fallback when empty', function (TestHarness $t) use ($settings) {
    $data = $settings->getAll();
    $t->assertEquals('10', $data['max_upload_size']);
    $t->assertEquals('50', $data['plagiarism_threshold']);
    $t->assertEquals('20', $data['submission_quota']);
});

$harness->run('updateMultiple persists overrides', function (TestHarness $t) use ($settings, $db) {
    $settings->updateMultiple([
        'max_upload_size' => '15',
        'plagiarism_threshold' => '60',
        'submission_quota' => '25',
    ]);

    $t->assertEquals('15', $db->settings['max_upload_size']);
    $t->assertEquals('60', $settings->get('plagiarism_threshold'));
    $t->assertEquals('25', $settings->get('submission_quota'));
});

$harness->run('reset restores defaults', function (TestHarness $t) use ($settings, $db) {
    $settings->reset();
    $t->assertEquals('10', $db->settings['max_upload_size']);
    $t->assertEquals('50', $db->settings['plagiarism_threshold']);
    $t->assertEquals('20', $db->settings['submission_quota']);
});

$harness->report();


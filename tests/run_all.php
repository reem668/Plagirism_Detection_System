<?php
// Simple test runner without Composer/PHPUnit

$tests = [
    __DIR__ . '/AdminSettingsTest.php',
    __DIR__ . '/AdminSubmissionTest.php',
];

$exitCode = 0;

foreach ($tests as $testFile) {
    echo PHP_EOL . "Running " . basename($testFile) . PHP_EOL;
    passthru(PHP_BINARY . ' ' . escapeshellarg($testFile), $code);
    if ($code !== 0) {
        $exitCode = $code;
        break;
    }
}

exit($exitCode);


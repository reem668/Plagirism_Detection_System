<?php
// Define PHPUNIT_RUNNING constant for test environment
define('PHPUNIT_RUNNING', true);

// Simple PSR-4 style autoloader for the project's namespaces
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    
    // Map namespaces to app/ directory
    $map = [
        'Models\\'      => $baseDir . 'app/Models/',
        'Controllers\\' => $baseDir . 'app/Controllers/',
        'Helpers\\'     => $baseDir . 'app/Helpers/',
        'Middleware\\'  => $baseDir . 'app/Middleware/',
        'Services\\'    => $baseDir . 'app/Services/',
    ];
    
    foreach ($map as $ns => $dir) {
        if (strpos($class, $ns) === 0) {
            $relative = substr($class, strlen($ns));
            $file = $dir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Fallback: try app/ directory
    $file = $baseDir . 'app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Fallback: try root directory
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

date_default_timezone_set('UTC');


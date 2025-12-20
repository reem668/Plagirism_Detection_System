<?php
// Simple PSR-4-like autoloader for app/ namespace
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../../'; // points to project root

    // Map common namespaces to folders
    $map = [
        'Helpers\\'     => $base . 'app/Helpers/',
        'Models\\'      => $base . 'app/Models/',
        'Controllers\\' => $base . 'app/Controllers/',
        'Middleware\\'  => $base . 'app/Middleware/',
        'Services\\'    => $base . 'app/Services/',
        'App\\'         => $base . 'app/Core/',
    ];

    foreach ($map as $ns => $dir) {
        if (strpos($class, $ns) === 0) {
            $relative = substr($class, strlen($ns));
            $file     = $dir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    }

    // Fallback: try resolving to app/ path
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

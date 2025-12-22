<?php
/**
 * Main Application Entry Point
 * All requests flow through this file
 * 
 * MVC Structure:
 * - Controllers handle business logic
 * - Models handle data operations
 * - Views handle presentation
 */

// Start session and error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define application root directory
define('APP_ROOT', __DIR__);
define('BASE_URL', '/Plagirism_Detection_System');

// Autoload dependencies
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/app/Helpers/SessionManager.php';
require_once APP_ROOT . '/app/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

// Initialize session
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Parse the request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if application is in subdirectory
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/' && $scriptName !== '\\') {
    $scriptName = rtrim(str_replace('\\', '/', $scriptName), '/');
    if (strpos($requestPath, $scriptName) === 0) {
        $requestPath = substr($requestPath, strlen($scriptName));
    }
}

// Normalize path
$requestPath = '/' . trim($requestPath, '/');

// Route the request
$route = trim($requestPath, '/');
$routeParts = explode('/', $route);
$mainRoute = $routeParts[0] ?? '';

// Handle static files (CSS, JS, images)
$staticExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'pdf'];
$fileExtension = strtolower(pathinfo($requestPath, PATHINFO_EXTENSION));

if (in_array($fileExtension, $staticExtensions)) {
    $staticFile = APP_ROOT . $requestPath;
    if (file_exists($staticFile) && is_file($staticFile)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'pdf' => 'application/pdf',
        ];
        
        $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        readfile($staticFile);
        exit;
    }
}

// Route mappings
switch ($mainRoute) {
    case '':
    case 'login':
    case 'signup':
        // If already logged in, redirect to dashboard
        if ($session->isLoggedIn()) {
            $role = $session->getUserRole();
            redirectToDashboard($role);
        }
        // Show login/signup page
        require_once APP_ROOT . '/app/Views/auth/signup.php';
        break;

    case 'admin':
        // Admin dashboard - requires authentication
        $auth->requireRole('admin');
        require_once APP_ROOT . '/app/Views/admin/index.php';
        break;

    case 'instructor':
        // Instructor dashboard - requires authentication
        $auth->requireRole('instructor');
        require_once APP_ROOT . '/app/Views/instructor/dashboard.php';
        break;

    case 'student':
        // Student dashboard - requires authentication
        $auth->requireRole('student');
        require_once APP_ROOT . '/app/Views/student/student_index.php';
        break;

    case 'logout':
        // Handle logout
        $session->destroy();
        header('Location: ' . BASE_URL . '/signup?logout=success');
        exit;
        break;

    case 'ajax':
        // AJAX endpoints - handle directly
        $ajaxFile = APP_ROOT . $requestPath;
        if (file_exists($ajaxFile)) {
            require $ajaxFile;
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
        }
        exit;
        break;

    case 'app':
        // Direct access to app files (controllers, etc.)
        $appFile = APP_ROOT . $requestPath;
        if (file_exists($appFile)) {
            require $appFile;
        } else {
            http_response_code(404);
            show404();
        }
        exit;
        break;

    default:
        // 404 - Not Found
        http_response_code(404);
        show404();
        break;
}

/**
 * Helper function to redirect to appropriate dashboard
 */
function redirectToDashboard($role) {
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin');
            break;
        case 'instructor':
            header('Location: ' . BASE_URL . '/instructor');
            break;
        case 'student':
            header('Location: ' . BASE_URL . '/student');
            break;
        default:
            header('Location: ' . BASE_URL . '/signup');
    }
    exit;
}

/**
 * Display 404 page
 */
function show404() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Page Not Found</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .error-container {
                text-align: center;
                padding: 60px 40px;
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
            }
            .error-code {
                font-size: 120px;
                font-weight: 700;
                color: #667eea;
                margin-bottom: 20px;
                line-height: 1;
            }
            h1 {
                font-size: 32px;
                color: #333;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                font-size: 16px;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 14px 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 600;
                transition: transform 0.3s;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-code">404</div>
            <h1>Page Not Found</h1>
            <p>The page you're looking for doesn't exist or has been moved.</p>
            <a href="<?= BASE_URL ?>/" class="btn">Go to Home</a>
        </div>
    </body>
    </html>
    <?php
}
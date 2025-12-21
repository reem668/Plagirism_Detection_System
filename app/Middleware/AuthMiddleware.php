<?php
namespace Middleware;

require_once __DIR__ . '/../Helpers/SessionManager.php';

use Helpers\SessionManager;

/**
 * AuthMiddleware - Handles authentication and authorization
 * Implements Authorization Matrix
 * Updated with ban status logging
 */
class AuthMiddleware
{
    private $session;
    private $baseUrl;

    // Authorization matrix - defines which roles can access which resources
    private $accessMatrix = [
        'admin' => [
            'allowed_pages' => [
                'admin.php',
                'dashboard',
                'user_management',
                'course_management',
                'submissions_overview',
                'system_settings',
            ],
            'redirect_on_fail' => '/Plagirism_Detection_System/signup.php',
        ],
        'instructor' => [
            'allowed_pages' => [
                'Instructor.php',
                'instructor_dashboard',
                'submissions',
            ],
            'redirect_on_fail' => '/Plagirism_Detection_System/signup.php',
        ],
        'student' => [
            'allowed_pages' => [
                'student_index.php',
                'student_dashboard',
                'submit',
                'history',
            ],
            'redirect_on_fail' => '/Plagirism_Detection_System/signup.php',
        ],
    ];

    public function __construct()
    {
        $this->session = SessionManager::getInstance();

        // Project is at: C:\xampp\htdocs\Plagirism_Detection_System
        // URL base is:   /Plagirism_Detection_System
        $this->baseUrl = '/Plagirism_Detection_System';
    }

    /**
     * Require authentication - user must be logged in
     */
    public function requireAuth(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->redirectToLogin('You must be logged in to access this page.');
        }
    }

    /**
     * Require specific role
     */
    public function requireRole(string $requiredRole): bool
    {
        $this->requireAuth();

        $userRole = $this->session->getUserRole();

        if ($userRole !== $requiredRole) {
            $this->redirectToUnauthorized();
        }

        return true;
    }

    /**
     * Require one of multiple roles
     */
    public function requireAnyRole(array $allowedRoles = []): bool
    {
        $this->requireAuth();

        $userRole = $this->session->getUserRole();

        if (!in_array($userRole, $allowedRoles, true)) {
            $this->redirectToUnauthorized();
        }

        return true;
    }

    /**
     * Check if user can access specific page/resource
     */
    public function canAccess(string $resource): bool
    {
        if (!$this->session->isLoggedIn()) {
            return false;
        }

        $userRole = $this->session->getUserRole();

        if (!isset($this->accessMatrix[$userRole])) {
            return false;
        }

        $allowedPages = $this->accessMatrix[$userRole]['allowed_pages'];

        foreach ($allowedPages as $page) {
            if (strpos($resource, $page) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(string $message = ''): void
    {
        if (!empty($message)) {
            $_SESSION['auth_error'] = $message;
        }
        header("Location: {$this->baseUrl}/signup.php");
        exit();
    }

    /**
     * Redirect to unauthorized page
     */
    private function redirectToUnauthorized(): void
    {
        http_response_code(403);

        $userRole = $this->session->getUserRole();

        switch ($userRole) {
            case 'admin':
                header("Location: {$this->baseUrl}/admin.php");
                break;
            case 'instructor':
                header("Location: {$this->baseUrl}/app/Views/instructor/dashboard.php");
                break;
            case 'student':
                header("Location: {$this->baseUrl}/app/Views/student/student_index.php");
                break;
            default:
                header("Location: {$this->baseUrl}/signup.php");
        }
        exit();
    }

    /**
     * Redirect already logged-in users to their dashboard
     */
    public function redirectIfAuthenticated(): void
    {
        if ($this->session->isLoggedIn()) {
            $role = $this->session->getUserRole();

            switch ($role) {
                case 'admin':
                    header("Location: {$this->baseUrl}/admin.php");
                    break;
                case 'instructor':
                    header("Location: {$this->baseUrl}/app/Views/instructor/dashboard.php");
                    break;
                case 'student':
                    header("Location: {$this->baseUrl}/app/Views/student/student_index.php");
                    break;
            }
            exit();
        }
    }

    /**
     * Get current user info (safe)
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->session->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $this->session->getUserId(),
            'name' => $this->session->getUserName(),
            'email' => $this->session->getUserEmail(),
            'role' => $this->session->getUserRole(),
        ];
    }

    /**
     * Check if user owns resource
     */
    public function ownsResource($resourceUserId): bool
    {
        if (!$this->session->isLoggedIn()) {
            return false;
        }

        $currentUserId = $this->session->getUserId();

        if ($this->session->getUserRole() === 'admin') {
            return true;
        }

        return (int) $currentUserId === (int) $resourceUserId;
    }

    /**
     * Log authentication attempt
     */
    public function logAuthAttempt(bool $success, string $username, string $ipAddress, string $reason = ''): void
    {
        $logEntry = sprintf(
            "[%s] %s login attempt for '%s' from IP: %s",
            date('Y-m-d H:i:s'),
            $success ? 'SUCCESS' : 'FAILED',
            $username,
            $ipAddress
        );

        if (!empty($reason)) {
            $logEntry .= " | Reason: " . strtoupper($reason);
        }

        $logEntry .= "\n";

        $logDir = __DIR__ . '/../../storage/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/auth.log';
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

<?php
namespace Controllers;

/**
 * Authentication Controller - Handles Login, Signup & Forgot Password
 * Updated with SessionManager and AuthMiddleware integration
 * Added banned user check during login
 */

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/SessionManager.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

use Models\User;
use Helpers\SessionManager;
use Middleware\AuthMiddleware;

class AuthController
{
    private $user;
    private $session;
    private $auth;

    public function __construct()
    {
        $this->user = new User();
        $this->session = SessionManager::getInstance();
        $this->auth = new AuthMiddleware();
    }

    /**
     * Handle Login
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Plagirism_Detection_System/signup.php");
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $adminKey = isset($_POST['admin_key']) ? trim($_POST['admin_key']) : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Input validation
        if (empty($email) || empty($password)) {
            $this->auth->logAuthAttempt(false, $email, $ipAddress);
            $this->alertBack("Please enter both email and password.");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->auth->logAuthAttempt(false, $email, $ipAddress);
            $this->alertBack("Invalid email format.");
        }

        // Check if user exists
        if (!$this->user->findByEmail($email)) {
            $this->auth->logAuthAttempt(false, $email, $ipAddress);
            $this->alertBack("No account found with this email.");
        }

        // NEW: Check if user is banned
        if ($this->user->getStatus() === 'banned') {
            $this->auth->logAuthAttempt(false, $email, $ipAddress, 'banned');
            $this->alertBack("Your account has been banned. Please contact the administrator for more information.");
        }

        // Admin key verification for admin users
        if ($this->user->getRole() === 'admin') {
            if (empty($adminKey)) {
                $this->auth->logAuthAttempt(false, $email, $ipAddress);
                $this->alertBack("Admin access requires a secret key.");
            }
            if ($this->user->getAdminKey() !== $adminKey) {
                $this->auth->logAuthAttempt(false, $email, $ipAddress);
                $this->alertBack("Invalid admin key. Access denied.");
            }
        }

        // Verify password
        if (!$this->user->verifyPassword($password)) {
            $this->auth->logAuthAttempt(false, $email, $ipAddress);
            $this->alertBack("Incorrect password. Please try again.");
        }

        // SUCCESS - Set session using SessionManager
        $this->session->setUserSession(
            $this->user->getId(),
            $this->user->getName(),
            $this->user->getEmail(),
            $this->user->getRole()
        );

        // Log successful login
        $this->auth->logAuthAttempt(true, $email, $ipAddress);

        // Redirect based on role
        switch ($this->user->getRole()) {
            case 'admin':
                $this->alertRedirect("Welcome Admin!", "/Plagirism_Detection_System/admin.php");
                break;
            case 'instructor':
                $this->alertRedirect("Welcome Instructor!", "/Plagirism_Detection_System/app/Views/instructor/dashboard.php");
                break;
            default:
                $this->alertRedirect("Welcome Student!", "/Plagirism_Detection_System/app/Views/student/student_index.php");
                break;
        }
    }

    /**
     * Handle Signup
     */
    public function signup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Plagirism_Detection_System/signup.php");
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm-password'] ?? '';

        // Validate name
        if (strlen($name) < 3) {
            $this->alertBack("Name must be at least 3 characters long.");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->alertBack("Invalid email format.");
        }

        // Validate email provider
        $allowedProviders = ["gmail.com", "yahoo.com", "outlook.com", "hotmail.com"];
        $domain = substr(strrchr($email, "@"), 1);
        if (!in_array($domain, $allowedProviders, true)) {
            $this->alertBack("Email must be Gmail, Yahoo, Outlook, or Hotmail.");
        }

        // Validate password strength
        if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$/", $password)) {
            $this->alertBack("Password must have at least 8 chars, uppercase, number, and special symbol.");
        }

        // Confirm password match
        if ($password !== $confirmPassword) {
            $this->alertBack("Passwords do not match.");
        }

        // Validate phone
        if (!preg_match("/^\d{11}$/", $mobile)) {
            $this->alertBack("Please enter a valid 11-digit mobile number.");
        }

        // Validate role
        $allowedRoles = ['student', 'instructor'];
        if (!in_array($role, $allowedRoles, true)) {
            $this->alertBack("Invalid role selected.");
        }

        // Check required fields
        if (empty($country)) {
            $this->alertBack("Please select your country.");
        }

        // Check if email exists
        if ($this->user->emailExists($email)) {
            $this->alertBack("This email is already registered.");
        }

        // Create user
        $this->user->setName($name);
        $this->user->setEmail($email);
        $this->user->setMobile($mobile);
        $this->user->setCountry($country);
        $this->user->setRole($role);
        $this->user->setPassword($password);

        if ($this->user->save()) {
            header("Location: /Plagirism_Detection_System/signup.php?signup=success");
            exit();
        }

        $this->alertBack("Signup failed. Please try again.");
    }

    /**
     * Handle Forgot Password
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /Plagirism_Detection_System/signup.php");
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm-password'] ?? '';

        // Basic validation
        if (empty($name) || empty($email) || empty($mobile)) {
            $this->alertBack("Please fill in all fields.");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->alertBack("Invalid email format.");
        }

        // Validate phone
        if (!preg_match("/^\d{11}$/", $mobile)) {
            $this->alertBack("Please enter a valid 11-digit mobile number.");
        }

        // Verify user exists and information matches
        if (!$this->user->verifyUserInfo($name, $email, $mobile)) {
            $this->alertBack("Account information does not match our records. Please check your name, email, and mobile number.");
        }

        // Validate password strength
        if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$/", $password)) {
            $this->alertBack("Password must have at least 8 chars, uppercase, number, and special symbol.");
        }

        // Confirm password match
        if ($password !== $confirmPassword) {
            $this->alertBack("Passwords do not match.");
        }

        // Update password in database
        if ($this->user->updatePassword($email, $password)) {
            header("Location: /Plagirism_Detection_System/signup.php?reset=success");
            exit();
        }

        $this->alertBack("Password reset failed. Please try again.");
    }

    /**
     * Handle Logout
     */
    public function logout()
    {
        $this->session->destroy();
        header("Location: /Plagirism_Detection_System/signup.php");
        exit();
    }

    /**
     * Helper: Alert and go back
     */
    private function alertBack($message)
    {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo "<script>alert('$message'); window.history.back();</script>";
        exit();
    }

    /**
     * Helper: Alert and redirect with modern styled notification
     */
    private function alertRedirect($message, $url)
    {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        // Determine notification type based on message
        $isSuccess = stripos($message, 'welcome') !== false || stripos($message, 'success') !== false;
        $icon = $isSuccess ? '✓' : 'ℹ';
        $bgColor = $isSuccess ? '#10b981' : '#3b82f6';

        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #000080 0%, #0056b3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .notification-container {
            animation: slideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .notification-card {
            background: white;
            border-radius: 20px;
            padding: 40px 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .notification-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: $bgColor;
        }
        
        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: $bgColor;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 15px rgba(16, 185, 129, 0);
            }
        }
        
        .icon {
            font-size: 40px;
            color: white;
            font-weight: bold;
        }
        
        .message {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .submessage {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .progress-fill {
            height: 100%;
            background: $bgColor;
            animation: progress 2s linear;
            border-radius: 2px;
        }
        
        @keyframes progress {
            from {
                width: 0%;
            }
            to {
                width: 100%;
            }
        }
        
        .redirect-text {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="notification-container">
        <div class="notification-card">
            <div class="icon-wrapper">
                <div class="icon">$icon</div>
            </div>
            <div class="message">$message</div>
            <div class="submessage">Redirecting you to your dashboard...</div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="redirect-text">Please wait...</div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = '$url';
        }, 2000);
    </script>
</body>
</html>
HTML;
        exit();
    }
}

// Handle requests when this file is called directly via ?action=
$action = $_GET['action'] ?? '';
$controller = new AuthController();

switch ($action) {
    case 'login':
        $controller->login();
        break;
    case 'signup':
        $controller->signup();
        break;
    case 'forgot_password':
        $controller->forgotPassword();
        break;
    case 'logout':
        $controller->logout();
        break;
    default:
        header("Location: /Plagirism_Detection_System/signup.php");
        exit();
}

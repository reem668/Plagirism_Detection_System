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
        $this->user    = new User();
        $this->session = SessionManager::getInstance();
        $this->auth    = new AuthMiddleware();
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

        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $adminKey  = isset($_POST['admin_key']) ? trim($_POST['admin_key']) : null;
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
                $this->alertRedirect("Welcome Instructor!", "/Plagirism_Detection_System/Instructordashboard.php");
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

        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $mobile          = trim($_POST['mobile'] ?? '');
        $country         = trim($_POST['country'] ?? '');
        $role            = trim($_POST['role'] ?? '');
        $password        = $_POST['password'] ?? '';
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
        $domain           = substr(strrchr($email, "@"), 1);
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

        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $mobile          = trim($_POST['mobile'] ?? '');
        $password        = $_POST['password'] ?? '';
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
     * Helper: Alert and redirect
     */
    private function alertRedirect($message, $url)
    {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $url     = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo "<script>alert('$message'); window.location.href='$url';</script>";
        exit();
    }
}

// Handle requests when this file is called directly via ?action=
$action      = $_GET['action'] ?? '';
$controller  = new AuthController();

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

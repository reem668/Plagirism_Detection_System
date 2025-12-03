<?php
/**
 * Authentication Controller - Handles Login & Signup
 */
session_start();

require_once __DIR__ . '/../Models/User.php';

class AuthController {
    
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    /**
     * Handle Login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../signup.php");
            exit();
        }
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $adminKey = isset($_POST['admin_key']) ? trim($_POST['admin_key']) : null;
        
        // Basic validation
        if (empty($email) || empty($password)) {
            $this->alertBack("Please enter both email and password.");
        }
        
        // Check if user exists
        if (!$this->user->findByEmail($email)) {
            $this->alertBack("No account found with this email.");
        }
        
        // Admin key check
        if ($this->user->getRole() === 'admin') {
            if (empty($adminKey)) {
                $this->alertBack("Admin access requires a secret key.");
            }
            if ($this->user->getAdminKey() !== $adminKey) {
                $this->alertBack("Invalid admin key. Access denied.");
            }
        }
        
        // Verify password
        if (!$this->user->verifyPassword($password)) {
            $this->alertBack("Incorrect password. Please try again.");
        }
        
        // Set session
        $_SESSION['user_id'] = $this->user->getId();
        $_SESSION['user_name'] = $this->user->getName();
        $_SESSION['user_email'] = $this->user->getEmail();
        $_SESSION['user_role'] = $this->user->getRole();
        
       switch ($this->user->getRole()) {
    case 'admin':
        $this->alertRedirect("Welcome Admin!", "../admin.php");
        break;
    case 'instructor':
        $this->alertRedirect("Welcome Instructor!", "/Plagirism_Detection_System/Instructordashboard.php");
        break;
    default:
        $this->alertRedirect("Welcome Student!", "/Plagirism_Detection_System/Views/student/student_index.php");
        break;
}
    }
    
    /**
     * Handle Signup
     */
    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../signup.php");
            exit();
        }
        
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $country = trim($_POST['country']);
        $role = trim($_POST['role']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm-password'];
        
        // Validate email provider
        $allowedProviders = ["gmail.com", "yahoo.com", "outlook.com", "hotmail.com"];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->alertBack("Invalid email format.");
        }
        
        $domain = substr(strrchr($email, "@"), 1);
        if (!in_array($domain, $allowedProviders)) {
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
        
        // Check required fields
        if (empty($country) || empty($role)) {
            $this->alertBack("Please select your role and country.");
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
            header("Location: ../signup.php?signup=success");
            exit();
        } else {
            $this->alertBack("Signup failed. Please try again.");
        }
    }
    
    /**
     * Helper: Alert and go back
     */
    private function alertBack($message) {
        echo "<script>alert('$message'); window.history.back();</script>";
        exit();
    }
    
    /**
     * Helper: Alert and redirect
     */
    private function alertRedirect($message, $url) {
        echo "<script>alert('$message'); window.location.href='$url';</script>";
        exit();
    }
}

// Handle requests
$action = $_GET['action'] ?? '';
$controller = new AuthController();

switch ($action) {
    case 'login':
        $controller->login();
        break;
    case 'signup':
        $controller->signup();
        break;
    default:
        header("Location: ../signup.php");
        exit();
}
?>
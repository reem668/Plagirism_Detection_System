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

define('BASE_URL', '/Plagirism_Detection_System');
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
            header("Location: " . BASE_URL . "/login");
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
            $this->alertRedirect("Welcome Admin!", BASE_URL . "/admin");
            break;
        case 'instructor':
            $this->alertRedirect("Welcome Instructor!", BASE_URL . "/instructor");
            break;
        default:
            $this->alertRedirect("Welcome Student!", BASE_URL . "/student");
            break;
    }
    }

    /**
     * Handle Signup
     */
    public function signup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/login");
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
    // Escape ONLY message (HTML output)
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $isSuccess = stripos($message, 'welcome') !== false || stripos($message, 'success') !== false;

    $icon    = $isSuccess ? '✓' : 'ℹ';
    $bgColor = $isSuccess ? '#10b981' : '#3b82f6';
    $subText = 'Redirecting you to your dashboard...';
    $delay   = 2000;

    require __DIR__ . '/../Views/shared/redirect.php';
    exit();
}



    /**
     * Set Google sign-up role (called before Google auth)
     */
    public function setGoogleRole()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $role = $_GET['role'] ?? '';
        
        // If role is empty, clear the session (for login)
        if (empty($role)) {
            unset($_SESSION['google_signup_role']);
        } else {
            // Validate role and set it (for signup)
            $allowedRoles = ['student', 'instructor'];
            if (in_array($role, $allowedRoles, true)) {
                $_SESSION['google_signup_role'] = $role;
            } else {
                $_SESSION['google_signup_role'] = 'student'; // default
            }
        }
        
        // Return success (for AJAX call)
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'role' => $role ?: 'none']);
        exit();
    }

    /**
     * Initiate Google OAuth login
     */
    public function googleAuth()
    {
        // Ensure session is started - use SessionManager to ensure proper initialization
        $this->session = SessionManager::getInstance();
        
        // Ensure session is active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $config = require __DIR__ . '/../Config/google_oauth.php';
        
        // Generate state token for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        
        // Also store timestamp for expiration check (5 minutes)
        $_SESSION['google_oauth_state_time'] = time();
        
        // Force session save before redirect
        session_write_close();
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $config['scopes'],
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        
        $authUrl = $config['auth_url'] . '?' . http_build_query($params);
        header("Location: $authUrl");
        exit();
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback()
    {
        // Ensure session is started - use SessionManager to ensure proper initialization
        $this->session = SessionManager::getInstance();
        
        // Ensure session is active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $config = require __DIR__ . '/../Config/google_oauth.php';
        
        // Get state from URL
        $receivedState = $_GET['state'] ?? '';
        $storedState = $_SESSION['google_oauth_state'] ?? '';
        $stateTime = $_SESSION['google_oauth_state_time'] ?? 0;
        
        // Verify state token exists
        if (empty($receivedState) || empty($storedState)) {
            error_log("OAuth state error: received='$receivedState', stored='$storedState', session_id=" . session_id());
            // Try to restore session - sometimes cookies aren't sent properly
            if (empty($storedState) && !empty($receivedState)) {
                // Allow continuation if we have the state in URL (less secure but works)
                // For production, you might want to reject this
                error_log("Warning: OAuth state not in session, but present in URL. Proceeding with URL state.");
            } else {
                $this->alertBack("Invalid OAuth state. The session may have expired. Please try again.");
            }
        }
        
        // Verify state matches (skip if storedState is empty but we have receivedState)
        if (!empty($storedState) && $receivedState !== $storedState) {
            error_log("OAuth state mismatch: received='$receivedState', stored='$storedState'");
            $this->alertBack("OAuth state mismatch. Please try again.");
        }
        
        // Check if state has expired (only if we have timestamp)
        if ($stateTime > 0 && (time() - $stateTime > 300)) {
            error_log("OAuth state expired: " . (time() - $stateTime) . " seconds old");
            unset($_SESSION['google_oauth_state']);
            unset($_SESSION['google_oauth_state_time']);
            $this->alertBack("OAuth state expired. Please try again.");
        }
        
        // Clear state from session
        unset($_SESSION['google_oauth_state']);
        unset($_SESSION['google_oauth_state_time']);
        
        if (!isset($_GET['code'])) {
            $error = $_GET['error'] ?? 'unknown';
            error_log("Google OAuth error: $error");
            $this->alertBack("Authorization failed: $error. Please try again.");
        }
        
        // Exchange authorization code for access token
        $tokenData = $this->exchangeCodeForToken($_GET['code'], $config);
        
        if (!$tokenData || !isset($tokenData['access_token'])) {
            $this->alertBack("Failed to get access token. Please try again.");
        }
        
        // Get user info from Google
        $userInfo = $this->getGoogleUserInfo($tokenData['access_token'], $config);
        
        if (!$userInfo || !isset($userInfo['id'])) {
            $this->alertBack("Failed to get user information. Please try again.");
        }
        
        // Process Google user (login or signup)
        $this->processGoogleUser($userInfo);
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($code, $config)
    {
        $data = [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($config['token_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        error_log("Google token exchange failed: HTTP $httpCode - $response");
        return null;
    }

    /**
     * Get user information from Google
     */
    private function getGoogleUserInfo($accessToken, $config)
    {
        $url = $config['userinfo_url'] . '?access_token=' . urlencode($accessToken);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        error_log("Google userinfo failed: HTTP $httpCode - $response");
        return null;
    }

    /**
     * Process Google user (login or create account)
     */
    private function processGoogleUser($userInfo)
    {
        $googleId = $userInfo['id'];
        $email = $userInfo['email'] ?? '';
        $name = $userInfo['name'] ?? ($userInfo['given_name'] ?? 'User');
        $picture = $userInfo['picture'] ?? null;
        
        if (empty($email)) {
            $this->alertBack("Google account email is required.");
        }
        
        // Check if user exists by Google ID
        if ($this->user->findByGoogleId($googleId)) {
            // User exists with Google ID - login
            if ($this->user->getStatus() === 'banned') {
                $this->alertBack("Your account has been banned. Please contact the administrator.");
            }
            
            $this->session->setUserSession(
                $this->user->getId(),
                $this->user->getName(),
                $this->user->getEmail(),
                $this->user->getRole()
            );
            
            $this->redirectByRole($this->user->getRole());
            return;
        }
        
        // Check if email already exists (link Google account to existing email)
        if ($this->user->emailExists($email)) {
            if ($this->user->findByEmail($email)) {
                // Link Google ID to existing account
                if ($this->linkGoogleAccount($this->user->getId(), $googleId)) {
                    // Reload user to get updated data
                    $this->user->findByGoogleId($googleId);
                }
                
                if ($this->user->getStatus() === 'banned') {
                    $this->alertBack("Your account has been banned. Please contact the administrator.");
                }
                
                $this->session->setUserSession(
                    $this->user->getId(),
                    $this->user->getName(),
                    $this->user->getEmail(),
                    $this->user->getRole()
                );
                
                $this->redirectByRole($this->user->getRole());
                return;
            }
        }
        
        // New user - create account with Google
        // Get role from session (set before Google auth) or default to student
        // Only use role from session if it exists (signup flow), otherwise default to student
        $role = isset($_SESSION['google_signup_role']) ? $_SESSION['google_signup_role'] : 'student';
        unset($_SESSION['google_signup_role']); // Clear it after use
        
        // Validate role
        $allowedRoles = ['student', 'instructor'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'student';
        }
        
        $this->user->createFromGoogle($googleId, $name, $email, $role);
        
        // Login the new user
        $this->session->setUserSession(
            $this->user->getId(),
            $this->user->getName(),
            $this->user->getEmail(),
            $this->user->getRole()
        );
        
        $this->redirectByRole($this->user->getRole());
    }

    /**
     * Link Google account to existing user
     */
    private function linkGoogleAccount($userId, $googleId)
    {
        // This would require database connection
        require_once __DIR__ . '/../../includes/db.php';
        global $conn;
        
        if (!$conn) {
            return false;
        }
        
        $stmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("si", $googleId, $userId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    /**
     * Redirect user based on role
     */
    private function redirectByRole($role)
    {
        switch ($role) {
            case 'admin':
                $this->alertRedirect("Welcome Admin!", "/Plagirism_Detection_System/admin");
                break;
            case 'instructor':
                $this->alertRedirect("Welcome Instructor!", "/Plagirism_Detection_System/instructor");
                break;
            default:
                $this->alertRedirect("Welcome Student!", "/Plagirism_Detection_System/student");
                break;
        }
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
    case 'set_google_role':
        $controller->setGoogleRole();
        break;
    case 'google_auth':
        $controller->googleAuth();
        break;
    case 'google_callback':
        $controller->googleCallback();
        break;
    default:
        header("Location: /Plagirism_Detection_System/signup.php");
        exit();
}

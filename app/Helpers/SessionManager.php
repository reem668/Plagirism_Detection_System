<?php
namespace Helpers;

/**
 * SessionManager - Centralized session handling and authentication
 * Fully test-compatible version
 */
class SessionManager {
    
    private static $instance = null;
    private $sessionTimeout = 3600;
    private $db = null;
    
    private function __construct() {
        // Completely skip session/db initialization during testing
        if (defined('PHPUNIT_RUNNING')) {
            return;
        }
        
        $this->initSession();
        $this->initDatabase();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initDatabase() {
        $host = "localhost";
        $user = "root";
        $pass = "";
        $dbname = "pal";
        
        $this->db = new \mysqli($host, $user, $pass, $dbname);
        
        if ($this->db->connect_error) {
            error_log("SessionManager DB connection failed: " . $this->db->connect_error);
            $this->db = null;
        } else {
            $this->db->set_charset("utf8");
        }
    }
    
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['created_at'] = time();
            }
            
            $this->validateSession();
        }
    }
    
    private function validateSession() {
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                $this->destroy();
                return false;
            }
        } else if (isset($_SESSION['user_id'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
                $this->destroy();
                return false;
            }
        }
        
        if ($this->isLoggedIn() && $this->checkUserBanned()) {
            $this->destroy();
            session_start();
            $_SESSION['auth_error'] = 'Your account has been banned.';
            header("Location: /Plagirism_Detection_System/signup.php");
            exit();
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    private function checkUserBanned() {
        if (!$this->db || !isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userId = intval($_SESSION['user_id']);
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return ($row['status'] === 'banned');
        }
        
        $stmt->close();
        return false;
    }
    
    public function setUserSession($userId, $userName, $userEmail, $userRole) {
        if (!defined('PHPUNIT_RUNNING') && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        if (!defined('PHPUNIT_RUNNING')) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true && 
               isset($_SESSION['user_id']);
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }
    
    public function getUserEmail() {
        return $_SESSION['user_email'] ?? null;
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $this->getUserRole() === $role;
    }
    
    public function setSessionTimeout($seconds) {
        $this->sessionTimeout = $seconds;
    }
    
    public function destroy() {
        $_SESSION = array();
        
        if (session_status() === PHP_SESSION_ACTIVE && !defined('PHPUNIT_RUNNING')) {
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            session_destroy();
        }
    }
    
    public function getSessionData() {
        return $_SESSION;
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
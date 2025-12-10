<?php
/**
 * User Model - Handles all user database operations
 */
class User {
    private $db;
    private $id;
    private $name;
    private $email;
    private $password;
    private $mobile;
    private $country;
    private $role;
    private $admin_key;
    
    public function __construct() {
        // Connect to database directly
        $host = "localhost";
        $user = "root";
        $pass = "";
        $dbname = "pal";
        
        $this->db = new mysqli($host, $user, $pass, $dbname);
        
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
        
        $this->db->set_charset("utf8");
    }
    
    // ========== GETTERS ==========
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getAdminKey() { return $this->admin_key; }
    public function getHashedPassword() { return $this->password; }
    
    // ========== SETTERS ==========
    public function setName($name) { 
        $this->name = htmlspecialchars(trim($name)); 
    }
    
    public function setEmail($email) { 
        $this->email = filter_var(trim($email), FILTER_SANITIZE_EMAIL); 
    }
    
    public function setPassword($password) { 
        $this->password = password_hash($password, PASSWORD_DEFAULT); 
    }
    
    public function setMobile($mobile) { 
        $this->mobile = trim($mobile); 
    }
    
    public function setCountry($country) { 
        $this->country = trim($country); 
    }
    
    public function setRole($role) { 
        $this->role = trim($role); 
    }
    
    // ========== DATABASE OPERATIONS ==========
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            $this->admin_key = $row['admin_key'] ?? null;
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    
    /**
     * Check if email already exists
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    
    /**
     * Verify user information (for password reset)
     */
    public function verifyUserInfo($name, $email, $mobile) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE name = ? AND email = ? AND mobile = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        
        $stmt->bind_param("sss", $name, $email, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->mobile = $row['mobile'];
            $this->role = $row['role'];
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    
    /**
     * Update password in database
     */
    public function updatePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        
        $stmt->bind_param("ss", $hashedPassword, $email);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Save new user to database
     */
    public function save() {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, mobile, country, password, role) VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        
        $stmt->bind_param("ssssss", 
            $this->name, 
            $this->email, 
            $this->mobile, 
            $this->country, 
            $this->password, 
            $this->role
        );
        
        $success = $stmt->execute();
        if ($success) {
            $this->id = $stmt->insert_id;
        }
        $stmt->close();
        return $success;
    }
    
    /**
     * Close connection when done
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
?>
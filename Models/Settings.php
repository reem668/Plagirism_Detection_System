<?php
namespace Models;

/**
 * Settings Model - Handles system settings database operations
 */
class Settings {
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Check if system_settings table exists
     */
    private function tableExists(): bool {
        $result = $this->conn->query("SHOW TABLES LIKE 'system_settings'");
        return $result && $result->num_rows > 0;
    }

    /**
     * Get all settings
     */
    public function getAll(): array {
        // If table doesn't exist, return defaults
        if (!$this->tableExists()) {
            return $this->getDefaults();
        }
        
        $result = $this->conn->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        
        // Return defaults if no settings exist
        if (empty($settings)) {
            return $this->getDefaults();
        }
        
        return $settings;
    }

    /**
     * Get a specific setting value
     */
    public function get(string $key, $default = null) {
        // If table doesn't exist, return default or value from defaults
        if (!$this->tableExists()) {
            $defaults = $this->getDefaults();
            return $defaults[$key] ?? $default;
        }
        
        try {
            $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            if (!$stmt) {
                return $default;
            }
            
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row ? $row['setting_value'] : $default;
        } catch (\Exception $e) {
            // If query fails, return default or value from defaults
            $defaults = $this->getDefaults();
            return $defaults[$key] ?? $default;
        }
    }

    /**
     * Set/Update a setting
     */
    public function set(string $key, $value): bool {
        // If table doesn't exist, return false
        if (!$this->tableExists()) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("sss", $key, $value, $value);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update multiple settings at once
     */
    public function updateMultiple(array $settings): bool {
        // If table doesn't exist, return false
        if (!$this->tableExists()) {
            return false;
        }
        
        $this->conn->begin_transaction();
        
        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    /**
     * Get default settings
     */
    public function getDefaults(): array {
        return [
            'max_upload_size' => '10',
            'plagiarism_threshold' => '50',
            'submission_quota' => '20'
        ];
    }

    /**
     * Reset to defaults
     */
    public function reset(): bool {
        return $this->updateMultiple($this->getDefaults());
    }
}



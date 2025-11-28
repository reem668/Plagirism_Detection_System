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
     * Get all settings
     */
    public function getAll(): array {
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
        $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Set/Update a setting
     */
    public function set(string $key, $value): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->bind_param("sss", $key, $value, $value);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    /**
     * Update multiple settings at once
     */
    public function updateMultiple(array $settings): bool {
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


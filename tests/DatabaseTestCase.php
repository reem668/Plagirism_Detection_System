<?php

use PHPUnit\Framework\TestCase;

/**
 * Base test case that wires a disposable MySQL database and rolls back
 * every test so production data remains untouched.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static ?mysqli $conn = null;
    protected static string $dbName;

    public static function setUpBeforeClass(): void
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        self::$dbName = getenv('DB_NAME') ?: 'tests';

        // Create the test database if it does not exist
        $rootConn = new mysqli($host, $user, $pass);
        $rootConn->query("CREATE DATABASE IF NOT EXISTS " . self::$dbName . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $rootConn->close();

        self::$conn = new mysqli($host, $user, $pass, self::$dbName);
        self::$conn->set_charset('utf8mb4');

        self::migrateSchema();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$conn) {
            self::$conn->close();
        }

        // Keep the test database so the user can inspect it after runs.
        // If you ever want cleanup, add an env flag and conditionally drop.
    }

    protected function setUp(): void
    {
        self::$conn->begin_transaction();
    }

    protected function tearDown(): void
    {
        if (self::$conn && self::$conn->errno === 0) {
            self::$conn->rollback();
        }
    }

    protected static function migrateSchema(): void
    {
        $c = self::$conn;
        $c->query("SET foreign_key_checks = 0");
        $c->query("DROP TABLE IF EXISTS submissions");
        $c->query("DROP TABLE IF EXISTS courses");
        $c->query("DROP TABLE IF EXISTS users");
        $c->query("DROP TABLE IF EXISTS system_settings");
        $c->query("SET foreign_key_checks = 1");

        $c->query("
            CREATE TABLE system_settings (
              id INT(11) NOT NULL AUTO_INCREMENT,
              setting_key VARCHAR(100) NOT NULL,
              setting_value TEXT NOT NULL,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY setting_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $c->query("
            CREATE TABLE users (
              id INT(11) NOT NULL AUTO_INCREMENT,
              name VARCHAR(255) NOT NULL,
              email VARCHAR(255) NOT NULL,
              mobile VARCHAR(15) DEFAULT NULL,
              country VARCHAR(50) DEFAULT NULL,
              role ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',
              password VARCHAR(255) NOT NULL,
              admin_key VARCHAR(255) DEFAULT NULL,
              status ENUM('active','banned') DEFAULT 'active',
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $c->query("
            CREATE TABLE courses (
              id INT(11) NOT NULL AUTO_INCREMENT,
              name VARCHAR(255) NOT NULL,
              instructor_id INT(11) NOT NULL,
              PRIMARY KEY (id),
              KEY fk_course_instructor (instructor_id),
              CONSTRAINT fk_course_instructor FOREIGN KEY (instructor_id) REFERENCES users (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $c->query("
            CREATE TABLE submissions (
              id INT(11) NOT NULL AUTO_INCREMENT,
              user_id INT(11) NOT NULL,
              course_id INT(11) DEFAULT NULL,
              instructor_id INT(11) DEFAULT NULL,
              teacher VARCHAR(255) DEFAULT NULL,
              title VARCHAR(255) DEFAULT NULL,
              text_content TEXT DEFAULT NULL,
              file_path VARCHAR(255) DEFAULT NULL,
              original_filename VARCHAR(255) DEFAULT NULL,
              stored_name VARCHAR(255) DEFAULT NULL,
              file_size INT(11) DEFAULT 0,
              report_path VARCHAR(500) DEFAULT NULL,
              similarity INT(11) DEFAULT 0,
              exact_match INT(11) DEFAULT 0,
              partial_match INT(11) DEFAULT 0,
              status ENUM('uploaded','processing','completed','failed','active','deleted','accepted','rejected') DEFAULT 'active',
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              feedback TEXT DEFAULT NULL,
              notification_seen TINYINT(1) DEFAULT 0,
              PRIMARY KEY (id),
              KEY idx_user_id (user_id),
              KEY idx_course_id (course_id),
              KEY idx_instructor_id (instructor_id),
              KEY idx_status (status),
              KEY idx_similarity (similarity),
              KEY idx_created_at (created_at),
              CONSTRAINT fk_submissions_instructor FOREIGN KEY (instructor_id) REFERENCES users (id) ON DELETE SET NULL,
              CONSTRAINT fk_submissions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    }
}
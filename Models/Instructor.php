<?php
require_once __DIR__ . "/../includes/db.php";

class Instructor {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function getInstructor($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id=? AND role='instructor'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $instructor = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $instructor;
    }

    public function getSubmissions($instructor_id) {
    $submissions = [];

    // Fetch instructor name for this ID
    $stmt = $this->conn->prepare("SELECT name FROM users WHERE id=?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $instructor_name = $stmt->get_result()->fetch_assoc()['name'];
    $stmt->close();

    // Fetch submissions where teacher matches this instructor and status is active
    $sql = "
        SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
               s.file_size, s.similarity, s.status, s.created_at,
               u.name AS student_name, u.email AS student_email
        FROM submissions s
        JOIN users u ON s.user_id = u.id
        WHERE s.teacher = ? AND s.status = 'active'
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }

    $stmt->close();
    return $submissions;
}
public function getTrash($instructor_id) {
    $trash = [];

    $stmt = $this->conn->prepare("SELECT name FROM users WHERE id=?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $instructor_name = $stmt->get_result()->fetch_assoc()['name'];
    $stmt->close();

    $sql = "
        SELECT s.id, s.user_id, s.course_id, s.teacher, s.text_content, s.file_path, s.stored_name,
               s.file_size, s.similarity, s.status, s.created_at,
               u.name AS student_name, u.email AS student_email
        FROM submissions s
        JOIN users u ON s.user_id = u.id
        WHERE s.teacher = ? AND s.status = 'deleted'
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trash[] = $row;
    }

    $stmt->close();
    return $trash;
}

    public function getStats() {
        $result = $this->conn->query("SELECT COUNT(*) AS students_enrolled FROM users WHERE role='student'");
        return $result->fetch_assoc();
    }

    public function getEnrolledStudents() {
        $students = [];
        $result = $this->conn->query("SELECT * FROM users WHERE role='student'");
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        return $students;
    }
}

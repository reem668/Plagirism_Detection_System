<?php
require_once __DIR__ . "/../config/db.php";  // uses your mysqli connection

class Instructor {

    private $conn;

    public function __construct() {
        global $conn;     // use your mysql connection
        $this->conn = $conn;
    }

    public function getInstructor($id) {
        $sql = "SELECT * FROM instructors WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Temporary static values (you said these are fine)
    public function getStats() {
        return [
            "total_courses" => 5,
            "students_enrolled" => 124,
            "ratings" => 4.8
        ];
    }

    // Static example courses (you can later move to DB)
    public function getCourses($instructor_id) {
        return [
            ["title" => "Web Development 101", "students" => 45],
            ["title" => "Advanced JavaScript", "students" => 32],
            ["title" => "PHP & MySQL", "students" => 27],
            ["title" => "UI/UX Design Fundamentals", "students" => 20],
        ];
    }
}

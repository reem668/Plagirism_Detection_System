<?php
require_once __DIR__ . "/../models/Instructor.php";

class InstructorController {

    public function dashboard() {
        session_start();

        $instructor_id = $_SESSION["instructor"]["id"] ?? 1;

        $model = new Instructor();

        $instructor = $model->getInstructor($instructor_id);
        $stats = $model->getStats();
        $courses = $model->getCourses($instructor_id);

        // DO NOT TOUCH SUBMISSIONS (your request)
        $submissions = $_SESSION["submissions"] ?? [];

        require __DIR__ . "/../views/instructor/dashboard.php";
    }
}

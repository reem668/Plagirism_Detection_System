<?php
require_once __DIR__ . '/../Models/Instructor.php';

class InstructorController {
    public function dashboard() {
        session_start();

        $instructor_id = $_SESSION['user']['id'] ?? 2; // test instructor
        $current_view = $_GET['view'] ?? 'submissions';

        $model = new Instructor();

        $instructor = $model->getInstructor($instructor_id);
        $stats = $model->getStats();
        $enrolled_students = $model->getEnrolledStudents();
        $submissions = $model->getSubmissions($instructor_id); // ✅ pass instructor id

       $trash = $model->getTrash($instructor_id); // pass the same id


        require __DIR__ . '/../Views/instructor/Instructor.php';
    }
}

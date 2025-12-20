<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../app/Models/Submission.php';

use Models\Submission;


class SubmissionOverviewTest extends DatabaseTestCase
{
    private function createUser(string $role = 'student'): int
    {
        $stmt = self::$conn->prepare("
            INSERT INTO users (name, email, password, role, status) 
            VALUES (?, ?, 'secret', ?, 'active')
        ");
        $email = uniqid($role . '_', true) . '@example.com';
        $stmt->bind_param('sss', $role, $email, $role);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    private function ensureInstructor(): int
    {
        $instructorId = $this->createUser('instructor');
        return $instructorId;
    }

    public function testCreateCreatesGeneralCourseWhenMissing(): void
    {
        $this->ensureInstructor();
        $studentId = $this->createUser('student');

        $submissionModel = new Submission(self::$conn);
        $submissionId = $submissionModel->create([
            'user_id' => $studentId,
            'teacher' => 'Dr. Smith',
            'text_content' => 'Sample text',
            'file_path' => '/tmp/sample.txt',
            'stored_name' => 'sample.txt',
            'file_size' => 123,
            'similarity' => 0,
            'exact_match' => 0,
            'partial_match' => 0,
        ]);

        $this->assertGreaterThan(0, $submissionId);

        $courseResult = self::$conn->query("SELECT * FROM courses WHERE name = 'General Submission'");
        $this->assertSame(1, $courseResult->num_rows);
        $courseRow = $courseResult->fetch_assoc();

        $submissionRow = $submissionModel->find($submissionId);
        $this->assertSame((int) $courseRow['id'], (int) $submissionRow['course_id']);
    }

    public function testGetByUserReturnsOnlyActive(): void
    {
        $this->ensureInstructor();
        $studentId = $this->createUser('student');
        $submissionModel = new Submission(self::$conn);

        $idActive = $submissionModel->create([
            'user_id' => $studentId,
            'teacher' => 'Active Teacher',
            'text_content' => 'Active text',
            'file_path' => '/tmp/active.txt',
            'stored_name' => 'active.txt',
            'file_size' => 1,
            'similarity' => 10,
            'exact_match' => 1,
            'partial_match' => 2,
        ]);

        $idDeleted = $submissionModel->create([
            'user_id' => $studentId,
            'teacher' => 'Deleted Teacher',
            'text_content' => 'Deleted text',
            'file_path' => '/tmp/deleted.txt',
            'stored_name' => 'deleted.txt',
            'file_size' => 1,
            'similarity' => 20,
            'exact_match' => 2,
            'partial_match' => 3,
        ]);

        $submissionModel->update($idDeleted, ['status' => 'deleted']);

        $rows = $submissionModel->getByUser($studentId);
        $this->assertCount(1, $rows);
        $this->assertSame($idActive, (int) $rows[0]['id']);
    }

    public function testUpdateChangesFields(): void
    {
        $this->ensureInstructor();
        $studentId = $this->createUser('student');
        $submissionModel = new Submission(self::$conn);
        $submissionId = $submissionModel->create([
            'user_id' => $studentId,
            'teacher' => 'Teacher',
            'text_content' => 'Text',
            'file_path' => '/tmp/file.txt',
            'stored_name' => 'file.txt',
            'file_size' => 1,
            'similarity' => 0,
            'exact_match' => 0,
            'partial_match' => 0,
        ]);

        $success = $submissionModel->update($submissionId, [
            'status' => 'accepted',
            'similarity' => 80,
            'exact_match' => 5,
            'partial_match' => 10,
        ]);

        $this->assertTrue($success);

        $row = $submissionModel->find($submissionId);
        $this->assertSame('accepted', $row['status']);
        $this->assertSame(80, (int) $row['similarity']);
        $this->assertSame(5, (int) $row['exact_match']);
        $this->assertSame(10, (int) $row['partial_match']);
    }
}
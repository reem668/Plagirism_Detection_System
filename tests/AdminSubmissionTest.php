<?php
session_start();

require_once __DIR__ . '/../Controllers/AdminSubmissionController.php';
require_once __DIR__ . '/../Models/Submission.php';
require_once __DIR__ . '/Support/TestHarness.php';
require_once __DIR__ . '/Support/FakeMysqli.php';

use Controllers\AdminSubmissionController;
use Models\Submission;
use Tests\Support\TestHarness;
use Tests\Support\FakeMysqli;

class FakeSubmissionModel extends Submission
{
    private $rows;

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    public function find($id): ?array
    {
        foreach ($this->rows as $row) {
            if ($row['id'] == $id) {
                return $row;
            }
        }
        return null;
    }
}

$_SESSION['user_role'] = 'admin';

$seed = [
    'submissions' => [
        ['id' => 1, 'status' => 'completed', 'similarity' => 80],
        ['id' => 2, 'status' => 'processing', 'similarity' => 40],
        ['id' => 3, 'status' => 'completed', 'similarity' => 15],
    ],
];

$db = new FakeMysqli($seed);
$submissionModel = new FakeSubmissionModel($seed['submissions']);
$controller = new AdminSubmissionController($db, $submissionModel);
$harness = new TestHarness('Admin Submission');

$harness->run('Statistics aggregate correctly', function (TestHarness $t) use ($controller) {
    $stats = $controller->getStatistics();
    $t->assertEquals(3, $stats['total']);
    $t->assertEquals(2, $stats['completed']);
    $t->assertEquals(1, $stats['processing']);
    $t->assertEquals(1, $stats['high_risk']);
    $t->assertEquals(1, $stats['low_risk']);
    $t->assertTrue($stats['avg_similarity'] > 0, 'Average similarity should be computed');
});

$harness->run('updateStatus rejects invalid value', function (TestHarness $t) use ($controller) {
    $result = $controller->updateStatus(1, 'invalid');
    $t->assertEquals(false, $result['success']);
});

$harness->run('updateStatus accepts allowed value', function (TestHarness $t) use ($controller, $db) {
    $controller->updateStatus(2, 'completed');
    $t->assertEquals('completed', $db->submissions[1]['status']);
});

$harness->report();


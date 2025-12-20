<?php
namespace Controllers;

require_once __DIR__ . '/../Models/Submission.php';
require_once __DIR__ . '/../Models/Settings.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/FileStorage.php';
require_once __DIR__ . '/../../includes/db.php';

use Helpers\Csrf;
use Helpers\Validator;
use Helpers\FileStorage;
use Models\Submission;
use Models\Settings;

class SubmissionController
{
    protected $conn;
    protected $submission;
    protected $rootPath;

    public function __construct(
        ?Submission $submission = null,
        ?\mysqli $conn = null
    ) {
        // project root = one level above app
        $this->rootPath = dirname(__DIR__, 1); // app/

        if ($conn === null) {
            // $conn defined in includes/db.php at project root
            global $conn;
            if (!$conn || $conn->connect_error) {
                die('DB connection failed: ' . ($conn ? $conn->connect_error : 'no connection'));
            }
            $this->conn = $conn;
        } else {
            $this->conn = $conn;
        }

        $this->submission = $submission ?? new Submission($this->conn);
    }

    /**
     * Handle submission
     */
    public function submit(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (!Csrf::verify($_POST['_csrf'] ?? '')) {
            die('Invalid CSRF token');
        }

        $text         = Validator::sanitize($_POST['textInput'] ?? '');
        $instructorId = (int)($_POST['instructorSelect'] ?? 0);
        $userId       = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            die('Invalid user ID. Please log in again.');
        }

        // Instructor name (teacher field, for backward compatibility)
        $teacher  = null;
        $courseId = null;

        if ($instructorId > 0) {
            $instructorStmt = $this->conn->prepare(
                "SELECT name FROM users WHERE id = ? AND role = 'instructor'"
            );
            $instructorStmt->bind_param("i", $instructorId);
            $instructorStmt->execute();
            $instructorResult = $instructorStmt->get_result();
            if ($instructorRow = $instructorResult->fetch_assoc()) {
                $teacher = $instructorRow['name'];
            }
            $instructorStmt->close();
        }

        $fileInfo = null;
        if (isset($_FILES['fileInput']) && $_FILES['fileInput']['name']) {
            try {
                $fileInfo = FileStorage::store($_FILES['fileInput']);
            } catch (\Exception $ex) {
                die("File upload error: " . $ex->getMessage());
            }

            $textFromFile = $this->extractFileText($fileInfo['path']);
            if ($textFromFile) {
                $text .= ' ' . $textFromFile;
            }
        }

        // Check plagiarism
        $plagData = $this->checkPlagiarism($text);

        // Threshold from settings
        $settings   = new Settings($this->conn);
        $threshold  = (float)$settings->get('plagiarism_threshold', 50);
        $similarity = $plagData['plagiarised'];

        $exceedsThreshold = $similarity > $threshold;
        $alertMessage     = $exceedsThreshold
            ? "⚠️ WARNING: This submission has a similarity score of {$similarity}%, which exceeds the threshold of {$threshold}%!"
            : null;

        $data = [
            'user_id'       => $userId,
            'course_id'     => $courseId > 0 ? $courseId : 0, // 0 for general submissions
            'teacher'       => $teacher,
            'text_content'  => $text,
            'file_path'     => $fileInfo['path']   ?? null,
            'stored_name'   => $fileInfo['stored'] ?? null,
            'file_size'     => $fileInfo['size']   ?? 0,
            'similarity'    => $similarity,
            'exact_match'   => $plagData['exact'],
            'partial_match' => $plagData['partial'],
        ];

        $id         = $this->submission->create($data);
        $reportPath = $this->generateReport($id, $plagData, $text, $plagData['matchingWords']);

        return [
            'submission_id'     => $id,
            'plagiarised'       => $similarity,
            'exact'             => $plagData['exact'],
            'partial'           => $plagData['partial'],
            'reportPath'        => $reportPath,
            'exceeds_threshold' => $exceedsThreshold,
            'threshold'         => $threshold,
            'alert_message'     => $alertMessage,
        ];
    }

    /**
     * Download report
     */
    public function downloadReport($id): void
    {
        $storageDir = $this->rootPath . '/storage/reports';
        $files      = glob($storageDir . "/report_{$id}_*.html");
        if (!$files) {
            die('Report not found.');
        }

        $path = $files[0];
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Delete submission (soft delete)
     */
    public function delete(int $submissionId, int $studentId)
    {
        // Confirm ownership
        $stmt = $this->conn->prepare("SELECT user_id FROM submissions WHERE id = ?");
        $stmt->bind_param("i", $submissionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return "invalid";
        }

        if ((int)$row['user_id'] !== $studentId) {
            return "unauthorized";
        }

        $stmt = $this->conn->prepare(
            "UPDATE submissions SET status = 'deleted' WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $submissionId, $studentId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }

    public function restore(int $id, int $userId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE submissions SET status = 'pending' WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Get user submissions (active or deleted)
     */
    public function getUserSubmissions(int $userId, string $filter = 'active'): array
    {
        if ($filter === 'deleted') {
            return $this->submission->getByUser($userId, 'deleted');
        }

        $stmt = $this->conn->prepare("
            SELECT s.*
            FROM submissions s
            WHERE s.user_id = ? AND s.status != 'deleted'
            ORDER BY s.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }

        $stmt->close();
        return $submissions;
    }

    /**
     * Get report path
     */
    public function getReportPath(int $submission_id): ?string
    {
        return $this->submission->getReportPath($submission_id);
    }

    /**
     * Extract text from file
     */
    protected function extractFileText(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'txt') {
            return file_get_contents($filePath);
        }

        if ($ext === 'docx') {
            $text = '';
            $zip  = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $xml = $zip->getFromIndex($index);
                    $xml = str_replace(['<w:p>', '<w:br/>'], ["\n", "\n"], $xml);
                    $text = strip_tags($xml);
                }
                $zip->close();
            }
            return $text;
        }

        return '';
    }

    /**
     * Plagiarism check
     */
 protected function checkPlagiarism(string $text): array
{
    // Go up from app/ to project root, then into app/Services
    require_once dirname(__DIR__) . '/Services/PlagiarismService.php';

    $service  = new \PlagiarismService();
    $existing = $this->submission->getAllSubmissions();

    return $service->check($text, $existing);
}

    /**
     * Generate HTML report
     */
    protected function generateReport($id, array $plag, string $text, array $matchingWords = []): string
    {
        $storageDir = $this->rootPath . '/storage/reports';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = "report_{$id}_" . time() . ".html";
        $path     = $storageDir . '/' . $filename;

        $highlightedText = $text;
        foreach ($matchingWords as $word) {
            $highlightedText = preg_replace(
                '/\b(' . preg_quote($word, '/') . ')\b/i',
                '<mark>$1</mark>',
                $highlightedText
            );
        }

        $content = "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Submission Report #{$id}</title>
<style>
body{font-family:Arial,sans-serif;padding:20px;}
mark{background:yellow;}
.summary{margin-top:20px;}
</style>
</head>
<body>
<h1>Report for Submission #{$id}</h1>
<div class='summary'>
<p><strong>Plagiarised:</strong> {$plag['plagiarised']}%</p>
<p><strong>Exact Match:</strong> {$plag['exact']}%</p>
<p><strong>Partial Match:</strong> {$plag['partial']}%</p>
</div>
<h2>Text with highlighted matches</h2>
<p>{$highlightedText}</p>
</body>
</html>";

        file_put_contents($path, $content);

        // public URL
        return '/Plagirism_Detection_System/storage/reports/' . $filename;
    }
}

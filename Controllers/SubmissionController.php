<?php
namespace Controllers;

require_once __DIR__ . '/../Models/Submission.php';
require_once __DIR__ . '/../Models/Settings.php';
require_once __DIR__ . '/../Helpers/Csrf.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Helpers/FileStorage.php';

use Helpers\Csrf;
use Helpers\Validator;
use Helpers\FileStorage;
use Models\Submission;
use Models\Settings;

class SubmissionController {
    protected $conn;
    protected $submission;
    protected $rootPath;

    public function __construct(){
        $this->rootPath = dirname(__DIR__);
        require $this->rootPath . '/includes/db.php';
        $this->conn = $conn;
        if($this->conn->connect_error) die("DB connection failed: ".$this->conn->connect_error);
        $this->submission = new Submission($this->conn);
    }

    /**
     * Handle submission
     */
    public function submit(): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        if (!Csrf::verify($_POST['_csrf'] ?? '')) die('Invalid CSRF token');

        $text = Validator::sanitize($_POST['textInput'] ?? '');
        $instructorId = intval($_POST['instructorSelect'] ?? 0);
        $userId = intval($_POST['user_id'] ?? 0);
        
        if ($userId <= 0) {
            die('Invalid user ID. Please log in again.');
        }

        // Get instructor name for teacher field (for backward compatibility)
        // If no instructor selected, teacher will be null (general submission)
        $teacher = null;
        $courseId = null;
        
        if ($instructorId > 0) {
            $instructorStmt = $this->conn->prepare("SELECT name FROM users WHERE id = ? AND role='instructor'");
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
            try { $fileInfo = FileStorage::store($_FILES['fileInput']); } 
            catch (\Exception $ex) { die("File upload error: ".$ex->getMessage()); }

            $textFromFile = $this->extractFileText($fileInfo['path']);
            if ($textFromFile) $text .= ' ' . $textFromFile;
        }

        // Check plagiarism and get matching words
        $plagData = $this->checkPlagiarism($text);

        // Get plagiarism threshold from settings
        $settings = new Settings($this->conn);
        $threshold = floatval($settings->get('plagiarism_threshold', 50));
        $similarity = $plagData['plagiarised'];
        
        // Check if similarity exceeds threshold
        $exceedsThreshold = $similarity > $threshold;
        $alertMessage = $exceedsThreshold 
            ? "⚠️ WARNING: This submission has a similarity score of {$similarity}%, which exceeds the threshold of {$threshold}%!"
            : null;

        $data = [
            'user_id' => $userId,
            'course_id' => $courseId > 0 ? $courseId : 0, // Use 0 for general submissions (course_id is NOT NULL in DB)
            'teacher' => $teacher,
            'text_content' => $text,
            'file_path' => $fileInfo['path'] ?? null,
            'stored_name' => $fileInfo['stored'] ?? null,
            'file_size' => $fileInfo['size'] ?? 0,
            'similarity' => $similarity,
            'exact_match' => $plagData['exact'],
            'partial_match' => $plagData['partial']
        ];

        $id = $this->submission->create($data);
        $reportPath = $this->generateReport($id, $plagData, $text, $plagData['matchingWords']);

        return [
            'submission_id' => $id,
            'plagiarised' => $similarity,
            'exact' => $plagData['exact'],
            'partial' => $plagData['partial'],
            'reportPath' => $reportPath,
            'exceeds_threshold' => $exceedsThreshold,
            'threshold' => $threshold,
            'alert_message' => $alertMessage
        ];
    }

    /**
     * Download report
     */
    public function downloadReport($id){
        $storageDir = $this->rootPath . '/storage/reports';
        $files = glob($storageDir."/report_{$id}_*.html");
        if(!$files) die('Report not found.');

        $path = $files[0];
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Delete / restore
     */
    public function delete(int $id, int $userId){
        $stmt = $this->conn->prepare("UPDATE submissions SET status='deleted' WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function restore(int $id, int $userId){
        $stmt = $this->conn->prepare("UPDATE submissions SET status='active' WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function getUserSubmissions(int $userId, string $status = 'active'): array {
        return $this->submission->getByUser($userId, $status);
    }

    /**
     * Get report path for a submission
     */
    public function getReportPath(int $submission_id): ?string {
        return $this->submission->getReportPath($submission_id);
    }

    /**
     * Extract text from file
     */
    protected function extractFileText(string $filePath): string {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'txt') return file_get_contents($filePath);

        if ($ext === 'docx') {
            $text = '';
            $zip = new \ZipArchive;
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
     * Plagiarism check (5-word chunks)
     */
    protected function checkPlagiarism(string $text): array {
        $existing = $this->submission->getAllSubmissions();
        $words = preg_split('/\s+/', strtolower($text));
        $totalChunks = max(1, count($words) - 4);
        $matchCount = 0;
        $matchingWords = [];

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunk = implode(' ', array_slice($words, $i, 5));
            foreach ($existing as $sub) {
                $subText = strtolower($sub['text_content']);
                if (strpos($subText, $chunk) !== false) {
                    $matchCount++;
                    $matchingWords = array_merge($matchingWords, array_slice($words, $i, 5));
                    break;
                }
            }
        }

        $plagPercent = ($matchCount / $totalChunks) * 100;
        $exact = intval($plagPercent * 0.3);
        $partial = intval($plagPercent - $exact);

        return [
            'plagiarised' => round($plagPercent,2),
            'exact' => $exact,
            'partial' => $partial,
            'matchingWords' => array_unique($matchingWords)
        ];
    }

    /**
     * Generate HTML report with highlighted words
     */
    protected function generateReport($id, $plag, $text, $matchingWords = []): string {
        $storageDir = $this->rootPath . '/storage/reports';
        if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);

        $filename = "report_{$id}_" . time() . ".html";
        $path = $storageDir . '/' . $filename;

        $highlightedText = $text;
        foreach ($matchingWords as $word) {
            $highlightedText = preg_replace('/\b(' . preg_quote($word, '/') . ')\b/i', '<mark>$1</mark>', $highlightedText);
        }

        $content = "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<title>Submission Report #$id</title>
<style>body{font-family:Arial,sans-serif;padding:20px;} mark{background:yellow;} .summary{margin-top:20px;}</style>
</head>
<body>
<h1>Report for Submission #$id</h1>
<div class='summary'>
<p><strong>Plagiarised:</strong> {$plag['plagiarised']}%</p>
<p><strong>Exact Match:</strong> {$plag['exact']}%</p>
<p><strong>Partial Match:</strong> {$plag['partial']}%</p>
</div>
<h2>Text with highlighted matches</h2>
<p>$highlightedText</p>
</body>
</html>";

        file_put_contents($path, $content);
        return '/Plagirism_Detection_System/storage/reports/' . $filename;
    }
}
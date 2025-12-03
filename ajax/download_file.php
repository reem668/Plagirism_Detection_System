<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('Access denied');
}

$id = $_GET['id'] ?? 0;
if (!$id || !is_numeric($id)) {
    die('Invalid ID');
}

$stmt = $conn->prepare("SELECT original_filename, file_path FROM submissions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($original_filename, $file_path);
if ($stmt->fetch() && $file_path && file_exists($file_path)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $original_filename . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    die('File not found or not available');
}
$stmt->close();
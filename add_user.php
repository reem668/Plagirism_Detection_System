<?php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /Plagirism_Detection_System/admin.php?page=user_management");
  exit;
}

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$role = trim($_POST['role']);

if (empty($name) || empty($email) || empty($role)) {
  header("Location: /Plagirism_Detection_System/admin.php?page=user_management&error=missing");
  exit;
}

$status = 'active';
$created_at = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO users (name, email, role, status, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $role, $status, $created_at);
$stmt->execute();
$stmt->close();

header("Location: /Plagirism_Detection_System/admin.php?page=user_management&success=1");
exit;
?>
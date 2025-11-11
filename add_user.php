<?php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: admin.php?page=user_management");
  exit;
}

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$role = trim($_POST['role']);

if (empty($name) || empty($email) || empty($role)) {
  header("Location: admin.php?page=user_management&error=missing");
  exit;
}

// Default values
$status = 'active';
$created_at = date('Y-m-d H:i:s');
$deleted_at = null;

$stmt = $conn->prepare("INSERT INTO users (name, email, role, status, created_at, deleted_at) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $role, $status, $created_at, $deleted_at);
$stmt->execute();
$stmt->close();

header("Location: admin.php?page=user_management&success=1"); // CHANGED from index.php
exit;
?>

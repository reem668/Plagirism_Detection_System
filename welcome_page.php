<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php"); // not logged in
    exit();
}
$role = $_SESSION['user_role'];
$name = htmlspecialchars($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      padding: 50px;
      background-color: <?php echo $role === 'teacher' ? '#f0f8ff' : '#fff0f0'; ?>;
    }
    h1 {
      color: navy;
    }
  </style>
</head>
<body>
  <h1>Welcome, <?php echo $name; ?>!</h1>

  <?php if ($role === 'instructor'): ?>
      <p>You are logged in as a <strong>Teacher</strong>.</p>
      <p><a href="teacher_dashboard.php">Go to Teacher Dashboard</a></p>
  <?php else: ?>
      <p>You are logged in as a <strong>Student</strong>.</p>
      <p><a href="student_dashboard.php">Go to Student Dashboard</a></p>
  <?php endif; ?>

  <a href="logout.php">Logout</a>
</body>
</html>

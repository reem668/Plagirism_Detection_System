<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signup.php");
    exit();
}

$page = $_GET['page'] ?? 'home';

// Validate page
$allowed_pages = ['home', 'user_management', 'course_management', 'submissions_overview', 'system_settings'];
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - <?= ucwords(str_replace('_', ' ', $page)) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
  <?php include 'includes/header.php'; ?>
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content" id="mainContent">
    <?php
      $page_file = $page . '.php';
      
      if (file_exists($page_file)) {
          include $page_file;
      } else {
          echo "<div style='padding:40px;text-align:center;color:#666;'>";
          echo "<h2>⚠️ Page Not Found</h2>";
          echo "<p>The page <strong>{$page}</strong> doesn't exist.</p>";
          echo "<a href='index.php?page=home' class='btn primary'>Go to Home</a>";
          echo "</div>";
      }
    ?>
  </main>
  
  <script src="assets/js/script.js"></script>
</body>
</html>
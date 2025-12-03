<?php
/**
 * Protected Admin Dashboard
 * Only accessible by authenticated admin users
 * 
 * This is the main entry point for all admin pages
 * All admin views are protected and can only be accessed through this file
 */

require_once __DIR__ . '/Helpers/SessionManager.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

// Initialize authentication
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// CRITICAL: Require admin role - this blocks unauthorized access
$auth->requireRole('admin');

// Define security constant - this prevents direct access to view files
define('ADMIN_ACCESS', true);

// Base URL
define('BASE_URL', '/Plagirism_Detection_System');

// If we reach here, user is authenticated as admin
$page = $_GET['page'] ?? 'dashboard';

// Allowed pages - additional security layer
$allowed_pages = [
    'dashboard', 
    'user_management', 
    'course_management', 
    'submissions_overview', 
    'system_settings'
];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Get current user info safely
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - <?= ucwords(str_replace('_', ' ', $page)) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
  <?php 
  // Pass authenticated user info to header
  $_SESSION['user_name'] = $currentUser['name'];
  $_SESSION['user_email'] = $currentUser['email'];
  include 'includes/admin_header.php'; 
  ?>
  
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="main-content" id="mainContent">
    <?php
      $page_file = "Views/admin/" . $page . ".php";
      
      if (file_exists($page_file)) {
          // Additional security check before including page
          if ($auth->canAccess($page)) {
              include $page_file;
          } else {
              echo "<div style='padding:40px;text-align:center;color:#dc2626;'>";
              echo "<h2>⛔ Access Denied</h2>";
              echo "<p>You don't have permission to access this page.</p>";
              echo "</div>";
          }
      } else {
          echo "<div style='padding:40px;text-align:center;color:#666;'>";
          echo "<h2>⚠️ Page Not Found</h2>";
          echo "<p>The page <strong>{$page}</strong> doesn't exist.</p>";
          echo "<a href='" . BASE_URL . "/admin.php?page=dashboard' class='btn primary'>Go to Dashboard</a>";
          echo "</div>";
      }
    ?>
  </main>
  
  <!-- Security: Add CSRF token to all forms -->
  <script>
    // Add CSRF token to all forms automatically
    document.addEventListener('DOMContentLoaded', function() {
      const csrfToken = '<?= \Helpers\Csrf::token() ?>';
      document.querySelectorAll('form').forEach(form => {
        if (!form.querySelector('input[name="_csrf"]')) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = '_csrf';
          input.value = csrfToken;
          form.appendChild(input);
        }
      });
    });
  </script>
  
  <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
  
  <?php
  // Load page-specific JS files
  if ($page === 'dashboard') {
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>' . "\n";
    echo '<script src="' . BASE_URL . '/assets/js/admin_dashboard.js"></script>' . "\n";
  } elseif ($page === 'user_management') {
    echo '<script src="' . BASE_URL . '/assets/js/admin_users.js"></script>' . "\n";
  } elseif ($page === 'course_management') {
    echo '<script src="' . BASE_URL . '/assets/js/admin_courses.js"></script>' . "\n";
  } elseif ($page === 'system_settings') {
    echo '<script src="' . BASE_URL . '/assets/js/admin_settings.js"></script>' . "\n";
  }
  // submissions_overview has inline script in the view file (needs CSRF token)
  ?>
</body>
</html>
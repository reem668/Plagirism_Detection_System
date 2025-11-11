<?php
// Get admin name from session, with fallback
$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminEmail = $_SESSION['user_email'] ?? 'admin@example.com';
$current = $page ?? 'dashboard'; // Get current page
?>
<aside class="sidebar" id="sidebar">
  <div class="logo"> ⚙️ </div>

  <div class="admin-profile">
    <div class="admin-avatar"><?= strtoupper($adminName[0]) ?></div>
    <div class="admin-info">
      <span class="admin-name"><?= htmlspecialchars($adminName) ?></span>
      <span class="admin-role">Administrator</span>
    </div>
  </div>

  <nav class="nav">
     <a href="admin.php?page=dashboard" class="nav-item <?= $current=='dashboard' ? 'active' : '' ?>"> <!-- CHANGED -->
    <i class="fas fa-home"></i><span>Dashboard</span> <!-- CHANGED text -->
  </a>

    <a href="admin.php?page=user_management" class="nav-item <?= $current=='user_management' ? 'active' : '' ?>">
      <i class="fas fa-users"></i><span>User Management</span>
    </a>

    <a href="admin.php?page=course_management" class="nav-item <?= $current=='course_management' ? 'active' : '' ?>">
      <i class="fas fa-book"></i><span>Course Management</span>
    </a>

    <a href="admin.php?page=submissions_overview" class="nav-item <?= $current=='submissions_overview' ? 'active' : '' ?>">
      <i class="fas fa-file-alt"></i><span>Submissions</span>
    </a>

    <a href="admin.php?page=system_settings" class="nav-item <?= $current=='system_settings' ? 'active' : '' ?>">
      <i class="fas fa-cog"></i><span>System Settings</span>
    </a>
  </nav>
  <div class="logout-section">
    <a href="logout.php" class="btn-logout">
      <i class="fas fa-sign-out-alt"></i>
      
    </a>
  </div>
</aside>

<?php
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<header class="header">
  <div class="header-left">
    <button id="sidebarToggle" class="icon-btn"><i class="fas fa-bars"></i></button>
    <h1 class="brand">Admin Dashboard</h1>
  </div>
  <div class="header-right">
    <div class="profile">
      <i class="fas fa-user-circle"></i>
      <span style="margin-left: 8px;"><?= htmlspecialchars($adminName) ?></span>
    </div>
  </div>
</header>

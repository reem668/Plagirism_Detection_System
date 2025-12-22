<?php
/**
 * Protected Admin Dashboard View
 * This file should only be accessed through admin.php which handles authentication
 * Additional security check included
 */

// Security check - ensure this file is accessed through admin.php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted. Please access through admin.php');
}

// Additional authentication verification
require_once dirname(__DIR__, 2) . '/Helpers/SessionManager.php';
require_once dirname(__DIR__, 2) . '/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__, 2) . '/Controllers/DashboardController.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\DashboardController;

$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// Double-check authentication
if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
     header('Location: ' . BASE_URL . '/signup');
    exit();
}

// Fetch dashboard statistics
$dashboardController = new DashboardController();
$stats               = $dashboardController->getStatistics();
?>

<section class="dashboard">
  <h2>Dashboard Overview 📊</h2>

  <!-- Stats Cards -->
  <div class="stats-cards">
    <div class="stat-card gradient-blue">
      <div class="icon-wrap"><i class="fas fa-users"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalUsers">
          <?= htmlspecialchars($stats['totalUsers'] ?? 0, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="stat-label">Total Users</div>
      </div>
    </div>

    <div class="stat-card gradient-green">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalSubmissions">
          <?= htmlspecialchars($stats['totalSubmissions'] ?? 0, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card gradient-purple">
      <div class="icon-wrap"><i class="fas fa-book-open"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalCourses">
          <?= htmlspecialchars($stats['totalCourses'] ?? 0, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="stat-label">Total Courses</div>
      </div>
    </div>

    <div class="stat-card gradient-red">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="highRiskCount">
          <?= htmlspecialchars($stats['highRiskCount'] ?? 0, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="stat-label">High-Risk Submissions</div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="charts-grid">
    <!-- Pie Chart - User Distribution -->
    <div class="chart-card">
      <h3><i class="fas fa-chart-pie"></i> User Distribution</h3>
      <canvas id="userPieChart"></canvas>
      <div class="chart-legend" id="userLegend"></div>
    </div>

    <!-- Doughnut Chart - Similarity Distribution -->
    <div class="chart-card">
      <h3><i class="fas fa-chart-donut"></i> Similarity Score Distribution</h3>
      <canvas id="similarityChart"></canvas>
      <div class="chart-legend" id="similarityLegend"></div>
    </div>

    <!-- Bar Chart - Course Activity -->
    <div class="chart-card wide">
      <h3><i class="fas fa-chart-bar"></i> Course Activity</h3>
      <canvas id="courseBarChart"></canvas>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="dashboard-section">
    <h3><i class="fas fa-clock"></i> Recent Submissions</h3>
    <div class="recent-submissions" id="recentSubmissions">
      <!-- Loaded by JavaScript -->
    </div>
  </div>
</section>

<!-- Pass PHP stats data to JavaScript -->
<script>
  window.dashboardStats = <?= json_encode($stats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>

<!-- Load Chart.js and dashboard JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/Plagirism_Detection_System/assets/js/admin_dashboard.js"></script>

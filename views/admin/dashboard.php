<section class="dashboard">
  <h2>Dashboard Overview 📊</h2>

  <!-- Stats Cards -->
  <div class="stats-cards">
    <div class="stat-card gradient-blue">
      <div class="icon-wrap"><i class="fas fa-users"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalUsers">6</div>
        <div class="stat-label">Total Users</div>
      </div>
    </div>

    <div class="stat-card gradient-green">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalSubmissions">5</div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card gradient-purple">
      <div class="icon-wrap"><i class="fas fa-book-open"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="totalCourses">3</div>
        <div class="stat-label">Total Courses</div>
      </div>
    </div>

    <div class="stat-card gradient-red">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="highRiskCount">0</div>
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






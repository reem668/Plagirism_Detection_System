
 <link rel="stylesheet" href="assets/css/admin.css">
<section class="submissions-overview">
  <h2>Submissions Overview 📄</h2>

  <!-- Statistics Cards -->
  <div class="stats-cards">
    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="subTotal">0</div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="subAvg">0%</div>
        <div class="stat-label">Average Similarity</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="subHighRisk">0</div>
        <div class="stat-label">High-Risk (>70%)</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-calendar"></i></div>
      <div class="stat-body">
        <div class="stat-number" id="todayDate"></div>
        <div class="stat-label">Today's Date</div>
      </div>
    </div>
  </div>

  <!-- Search Bar -->
  <div class="search-filter-bar">
    <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search by student name or title..." onkeyup="filterSubmissions()">
    
    <select id="statusFilter" class="filter-select" onchange="filterSubmissions()">
      <option value="">All Status</option>
      <option value="completed">Completed</option>
      <option value="processing">Processing</option>
    </select>

    <select id="riskFilter" class="filter-select" onchange="filterSubmissions()">
      <option value="">All Risk Levels</option>
      <option value="low">Low (0-30%)</option>
      <option value="medium">Medium (31-70%)</option>
      <option value="high">High (>70%)</option>
    </select>

    <button class="btn primary" onclick="exportToCSV()">
      <i class="fas fa-download"></i> Export CSV
    </button>
  </div>

  <!-- Submissions Table -->
  <div class="submissions-table-container">
    <table class="submissions-table" id="submissionsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student Name</th>
          <th>Document Title</th>
          <th>Course</th>
          <th>Instructor</th>
          <th>Submission Date</th>
          <th>Similarity Score</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="submissionsTableBody">
        <!-- Submissions loaded by JavaScript -->
      </tbody>
    </table>
  </div>
</section>

<!-- Side Panel for Submission Details -->
<div id="submissionPanel" class="side-panel">
  <div class="side-panel-content">
    <div class="side-panel-header">
      <h3><i class="fas fa-file-alt"></i> Submission Details</h3>
      <button class="close-panel-btn" onclick="closeSubmissionPanel()">&times;</button>
    </div>
    <div class="side-panel-body">
      <div class="submission-details-section">
        <h4>📋 Document Information</h4>
        <div class="info-grid">
          <div class="info-item">
            <label>Submission ID:</label>
            <span id="panelSubmissionId"></span>
          </div>
          <div class="info-item">
            <label>Document Title:</label>
            <span id="panelDocTitle"></span>
          </div>
          <div class="info-item">
            <label>Filename:</label>
            <span id="panelFilename"></span>
          </div>
          <div class="info-item">
            <label>Submission Date:</label>
            <span id="panelSubmitDate"></span>
          </div>
        </div>
      </div>

      <div class="submission-details-section">
        <h4>👤 Student Information</h4>
        <div class="info-grid">
          <div class="info-item">
            <label>Student Name:</label>
            <span id="panelStudentName"></span>
          </div>
          <div class="info-item">
            <label>Student Email:</label>
            <span id="panelStudentEmail"></span>
          </div>
        </div>
      </div>

      <div class="submission-details-section">
        <h4>📚 Course Information</h4>
        <div class="info-grid">
          <div class="info-item">
            <label>Course Code:</label>
            <span id="panelCourseCode"></span>
          </div>
          <div class="info-item">
            <label>Course Name:</label>
            <span id="panelCourseName"></span>
          </div>
          <div class="info-item">
            <label>Instructor:</label>
            <span id="panelInstructor"></span>
          </div>
        </div>
      </div>

      <div class="submission-details-section">
        <h4>📊 Plagiarism Analysis</h4>
        <div class="similarity-display">
          <div class="similarity-ring">
            <svg viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10"/>
              <circle id="similarityCircle" cx="50" cy="50" r="45" fill="none" stroke="var(--accent1)" stroke-width="10" 
                      stroke-dasharray="283" stroke-dashoffset="283" 
                      transform="rotate(-90 50 50)" stroke-linecap="round"/>
            </svg>
            <div class="similarity-percentage" id="panelSimilarity">0%</div>
          </div>
          <div class="similarity-info">
            <div class="info-item">
              <label>Status:</label>
              <span id="panelStatus" class="status-badge"></span>
            </div>
            <div class="info-item" style="margin-top: 15px;">
              <label>Risk Level:</label>
              <span id="panelRiskLevel"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-actions">
        <button class="btn primary" onclick="downloadSubmission()">
          <i class="fas fa-download"></i> Download Document
        </button>
        <button class="btn danger" onclick="deleteCurrentSubmission()">
          <i class="fas fa-trash"></i> Delete Submission
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Panel Overlay -->
<div id="panelOverlay" class="panel-overlay" onclick="closeAllPanels()"></div>




<script src="assets/js/admin_submissions.js"></script>
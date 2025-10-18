<?php
// Dummy data for submissions (frontend only - no backend)
$submissions = [
  [
    'id' => 1,
    'student_name' => 'Ahmed Hassan',
    'document_title' => 'Introduction to Machine Learning Essay',
    'course' => 'CS101',
    'submission_date' => '2024-10-15 14:30',
    'similarity_score' => 18.5,
    'status' => 'completed',
    'instructor' => 'Dr. Ahmed Mohamed'
  ],
  [
    'id' => 2,
    'student_name' => 'Fatma Ali',
    'document_title' => 'Academic Writing Best Practices',
    'course' => 'ENG201',
    'submission_date' => '2024-10-14 10:15',
    'similarity_score' => 45.2,
    'status' => 'completed',
    'instructor' => 'Prof. Sara Ali'
  ],
  [
    'id' => 3,
    'student_name' => 'Mohamed Omar',
    'document_title' => 'Calculus Applications Research Paper',
    'course' => 'MATH150',
    'submission_date' => '2024-10-14 16:45',
    'similarity_score' => 78.9,
    'status' => 'completed',
    'instructor' => 'Dr. Omar Hassan'
  ],
  [
    'id' => 4,
    'student_name' => 'Sara Ibrahim',
    'document_title' => 'Data Structures Assignment',
    'course' => 'CS101',
    'submission_date' => '2024-10-13 09:20',
    'similarity_score' => 12.3,
    'status' => 'completed',
    'instructor' => 'Dr. Ahmed Mohamed'
  ],
  [
    'id' => 5,
    'student_name' => 'Omar Youssef',
    'document_title' => 'Software Engineering Principles',
    'course' => 'CS101',
    'submission_date' => '2024-10-18 11:00',
    'similarity_score' => null,
    'status' => 'processing',
    'instructor' => 'Dr. Ahmed Mohamed'
  ],
];

// Calculate statistics
$totalSubmissions = count($submissions);
$completedSubmissions = array_filter($submissions, fn($s) => $s['status'] == 'completed');
$scores = array_filter(array_column($completedSubmissions, 'similarity_score'));
$avgSimilarity = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
$highRiskCount = count(array_filter($scores, fn($s) => $s > 70));

// Filter handling
$filter = $_GET['filter'] ?? 'all';
?>

<section class="submissions-overview">
  <h2>Submissions Overview 📄</h2>

  <!-- Statistics Cards -->
  <div class="stats-cards">
    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $totalSubmissions ?></div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $avgSimilarity ?>%</div>
        <div class="stat-label">Average Similarity</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= $highRiskCount ?></div>
        <div class="stat-label">High-Risk (>70%)</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-calendar"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= date('M d') ?></div>
        <div class="stat-label">Today's Date</div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filter-row" style="margin-top: 20px;">
    <a class="btn <?= $filter=='all' ? 'active' : '' ?>" href="?page=submissions_overview&filter=all">All Submissions</a>
    <a class="btn <?= $filter=='high_risk' ? 'active' : '' ?>" href="?page=submissions_overview&filter=high_risk">High-Risk (>70%)</a>
    <a class="btn <?= $filter=='recent' ? 'active' : '' ?>" href="?page=submissions_overview&filter=recent">Recent (7 days)</a>
    <a class="btn <?= $filter=='processing' ? 'active' : '' ?>" href="?page=submissions_overview&filter=processing">Processing</a>
  </div>

  <!-- Search and Filters -->
  <div class="search-filter-bar">
    <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search by student name, course, or title..." onkeyup="filterSubmissions()">
    
    <select id="courseFilter" class="filter-select" onchange="filterSubmissions()">
      <option value="">All Courses</option>
      <option value="CS101">CS101</option>
      <option value="ENG201">ENG201</option>
      <option value="MATH150">MATH150</option>
    </select>

    <select id="statusFilter" class="filter-select" onchange="filterSubmissions()">
      <option value="">All Status</option>
      <option value="completed">Completed</option>
      <option value="processing">Processing</option>
      <option value="failed">Failed</option>
    </select>

    <button class="btn primary" onclick="alert('Export to CSV - Frontend only')">
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
      <tbody>
        <?php foreach ($submissions as $sub): ?>
        <tr data-student="<?= strtolower($sub['student_name']) ?>" 
            data-course="<?= $sub['course'] ?>" 
            data-status="<?= $sub['status'] ?>"
            data-title="<?= strtolower($sub['document_title']) ?>">
          <td><strong>#<?= $sub['id'] ?></strong></td>
          <td><?= htmlspecialchars($sub['student_name']) ?></td>
          <td><?= htmlspecialchars($sub['document_title']) ?></td>
          <td><span class="course-badge"><?= htmlspecialchars($sub['course']) ?></span></td>
          <td><small><?= htmlspecialchars($sub['instructor']) ?></small></td>
          <td><small><?= date('M d, Y H:i', strtotime($sub['submission_date'])) ?></small></td>
          <td>
            <?php if ($sub['similarity_score'] !== null): ?>
              <?php
                $score = $sub['similarity_score'];
                $colorClass = $score <= 30 ? 'low' : ($score <= 60 ? 'medium' : 'high');
              ?>
              <span class="similarity-score <?= $colorClass ?>">
                <?= number_format($score, 1) ?>%
              </span>
            <?php else: ?>
              <span class="similarity-score processing">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="status-badge <?= $sub['status'] ?>">
              <?= ucfirst($sub['status']) ?>
            </span>
          </td>
          <td>
            <button class="btn small" onclick="viewSubmissionDetails(<?= $sub['id'] ?>)" 
                    <?= $sub['status'] != 'completed' ? 'disabled' : '' ?>>
              👁️ View Report
            </button>
            <button class="btn small" onclick="downloadOriginal(<?= $sub['id'] ?>)">
              ⬇️ Download
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Submission Details Modal -->
  <div id="submissionModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3>📄 Submission Details</h3>
        <button class="close-btn" onclick="closeSubmissionModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="submission-details">
          <div class="detail-row">
            <strong>Student:</strong> <span>Ahmed Hassan</span>
          </div>
          <div class="detail-row">
            <strong>Document:</strong> <span>Introduction to Machine Learning Essay</span>
          </div>
          <div class="detail-row">
            <strong>Course:</strong> <span>CS101 - Introduction to Programming</span>
          </div>
          <div class="detail-row">
            <strong>Instructor:</strong> <span>Dr. Ahmed Mohamed</span>
          </div>
          <div class="detail-row">
            <strong>Submitted:</strong> <span>Oct 15, 2024 at 14:30</span>
          </div>
          <div class="detail-row">
            <strong>File Size:</strong> <span>2.8 MB</span>
          </div>
        </div>

        <div class="report-summary">
          <h4>📊 Plagiarism Report Summary</h4>
          <div class="similarity-display">
            <div class="similarity-circle low">
              <span class="score-text">18.5%</span>
              <span class="score-label">Similarity</span>
            </div>
            <div class="severity-info">
              <div class="severity-badge low">✅ Low Similarity</div>
              <p>Total Words: 3,542 | Matched Words: 655</p>
              <p>Sources Matched: 4 | Longest Match: 28 words</p>
            </div>
          </div>

          <div class="sources-list">
            <h5>Top Matched Sources:</h5>
            <ol>
              <li>
                <strong>Wikipedia - Machine Learning</strong><br>
                <small>https://en.wikipedia.org/wiki/Machine_learning</small><br>
                <span class="match-percentage">8.2% match</span>
              </li>
              <li>
                <strong>Stanford CS Course Notes</strong><br>
                <small>https://cs.stanford.edu/~notes</small><br>
                <span class="match-percentage">5.1% match</span>
              </li>
              <li>
                <strong>Previous Student Submission (2023)</strong><br>
                <small>Internal Database</small><br>
                <span class="match-percentage">3.4% match</span>
              </li>
            </ol>
          </div>

          <div class="report-actions">
            <button class="btn primary" onclick="alert('Open full report - Frontend only')">
              📑 View Full Report
            </button>
            <button class="btn" onclick="alert('Download PDF - Frontend only')">
              📥 Download PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

</section>

<style>
/* Search and Filter Bar */
.search-filter-bar {
  display: flex;
  gap: 10px;
  margin: 20px 0;
  flex-wrap: wrap;
  align-items: center;
}

.search-bar {
  flex: 1;
  min-width: 250px;
  padding: 10px 15px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
  font-size: 14px;
}

.filter-select {
  padding: 10px 15px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
  cursor: pointer;
}

/* Submissions Table */
.submissions-table-container {
  background: rgba(255,255,255,0.02);
  padding: 20px;
  border-radius: 14px;
  overflow-x: auto;
  margin-top: 20px;
}

.submissions-table {
  width: 100%;
  border-collapse: collapse;
}

.submissions-table thead {
  background: rgba(255,255,255,0.05);
}

.submissions-table th,
.submissions-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  color: #eaf2ff;
}

.submissions-table th {
  font-weight: 600;
  color: var(--accent1);
  font-size: 13px;
}

.submissions-table tbody tr:hover {
  background: rgba(255,255,255,0.03);
}

.course-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 8px;
  background: rgba(106,17,203,0.2);
  color: #c084fc;
  font-size: 12px;
  font-weight: 600;
}

.similarity-score {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
}

.similarity-score.low {
  background: rgba(46,204,113,0.2);
  color: #7ef3b6;
}

.similarity-score.medium {
  background: rgba(243,156,18,0.2);
  color: #ffa94d;
}

.similarity-score.high {
  background: rgba(231,76,60,0.2);
  color: #ff9696;
}

.similarity-score.processing {
  background: rgba(52,152,219,0.2);
  color: #74b9ff;
}

.status-badge {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.completed {
  background: rgba(46,204,113,0.2);
  color: #7ef3b6;
}

.status-badge.processing {
  background: rgba(52,152,219,0.2);
  color: #74b9ff;
}

.status-badge.failed {
  background: rgba(231,76,60,0.2);
  color: #ff9696;
}

.status-badge.uploaded {
  background: rgba(155,89,182,0.2);
  color: #c084fc;
}

/* Submission Details Modal */
.submission-details {
  background: rgba(255,255,255,0.03);
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.detail-row:last-child {
  border-bottom: none;
}

.report-summary {
  margin-top: 20px;
}

.report-summary h4 {
  color: var(--accent1);
  margin-bottom: 15px;
}

.similarity-display {
  display: flex;
  gap: 20px;
  align-items: center;
  padding: 20px;
  background: rgba(255,255,255,0.03);
  border-radius: 12px;
  margin-bottom: 20px;
}

.similarity-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
}

.similarity-circle.low {
  background: radial-gradient(circle, rgba(46,204,113,0.2), rgba(46,204,113,0.05));
  border: 3px solid #7ef3b6;
}

.similarity-circle.medium {
  background: radial-gradient(circle, rgba(243,156,18,0.2), rgba(243,156,18,0.05));
  border: 3px solid #ffa94d;
}

.similarity-circle.high {
  background: radial-gradient(circle, rgba(231,76,60,0.2), rgba(231,76,60,0.05));
  border: 3px solid #ff9696;
}

.score-text {
  font-size: 28px;
  font-weight: 700;
  color: #fff;
}

.score-label {
  font-size: 12px;
  color: #a7b7d6;
  margin-top: 5px;
}

.severity-info {
  flex: 1;
}

.severity-badge {
  display: inline-block;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  margin-bottom: 10px;
}

.severity-badge.low {
  background: rgba(46,204,113,0.2);
  color: #7ef3b6;
}

.severity-badge.medium {
  background: rgba(243,156,18,0.2);
  color: #ffa94d;
}

.severity-badge.high {
  background: rgba(231,76,60,0.2);
  color: #ff9696;
}

.severity-info p {
  margin: 5px 0;
  color: #cfeeff;
  font-size: 13px;
}

.sources-list {
  background: rgba(255,255,255,0.03);
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.sources-list h5 {
  color: var(--accent1);
  margin-bottom: 10px;
}

.sources-list ol {
  padding-left: 20px;
}

.sources-list li {
  margin-bottom: 12px;
  color: #eaf2ff;
  line-height: 1.6;
}

.sources-list small {
  color: #74b9ff;
  font-size: 11px;
}

.match-percentage {
  display: inline-block;
  margin-top: 5px;
  padding: 3px 8px;
  background: rgba(0,198,255,0.15);
  color: var(--accent1);
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
}

.report-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 20px;
}

/* Responsive */
@media (max-width: 768px) {
  .search-filter-bar {
    flex-direction: column;
  }
  
  .search-bar {
    width: 100%;
  }

  .similarity-display {
    flex-direction: column;
    text-align: center;
  }
  
  .submissions-table-container {
    overflow-x: scroll;
  }
}
</style>

<script>
function viewSubmissionDetails(submissionId) {
  document.getElementById('submissionModal').style.display = 'flex';
}

function closeSubmissionModal() {
  document.getElementById('submissionModal').style.display = 'none';
}

function downloadOriginal(submissionId) {
  alert('Download original file for submission #' + submissionId + ' - Frontend only');
}

function filterSubmissions() {
  const searchInput = document.getElementById('searchInput').value.toLowerCase();
  const courseFilter = document.getElementById('courseFilter').value;
  const statusFilter = document.getElementById('statusFilter').value;
  
  const table = document.getElementById('submissionsTable');
  const rows = table.getElementsByTagName('tr');
  
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const studentName = row.getAttribute('data-student');
    const title = row.getAttribute('data-title');
    const course = row.getAttribute('data-course');
    const status = row.getAttribute('data-status');
    
    let showRow = true;
    
    // Search filter
    if (searchInput && !studentName.includes(searchInput) && !title.includes(searchInput)) {
      showRow = false;
    }
    
    // Course filter
    if (courseFilter && course !== courseFilter) {
      showRow = false;
    }
    
    // Status filter
    if (statusFilter && status !== statusFilter) {
      showRow = false;
    }
    
    row.style.display = showRow ? '' : 'none';
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('submissionModal');
  if (event.target == modal) {
    closeSubmissionModal();
  }
}
</script>
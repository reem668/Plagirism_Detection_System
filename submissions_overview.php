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

<style>
.search-filter-bar {
  display: flex;
  gap: 10px;
  margin: 20px 0;
  flex-wrap: wrap;
}

.search-bar {
  flex: 1;
  min-width: 250px;
  padding: 10px 15px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
}

.filter-select {
  padding: 10px 15px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
  cursor: pointer;
}

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
}

.status-badge.completed {
  background: rgba(46,204,113,0.2);
  color: #7ef3b6;
}

.status-badge.processing {
  background: rgba(52,152,219,0.2);
  color: #74b9ff;
}

/* Side Panel Styles */
.side-panel {
  position: fixed;
  top: 0;
  right: -600px;
  width: 600px;
  height: 100vh;
  background: linear-gradient(180deg, rgba(20,30,60,0.98), rgba(10,20,40,0.98));
  backdrop-filter: blur(10px);
  box-shadow: -5px 0 25px rgba(0,0,0,0.5);
  transition: right 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
  z-index: 2000;
  overflow-y: auto;
}

.side-panel.open {
  right: 0;
}

.side-panel-content {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.side-panel-header {
  padding: 25px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(0,198,255,0.05);
}

.side-panel-header h3 {
  margin: 0;
  color: var(--accent1);
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.close-panel-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 32px;
  cursor: pointer;
  transition: all 0.3s;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.close-panel-btn:hover {
  background: rgba(255,90,107,0.2);
  color: #ff5a6b;
  transform: rotate(90deg);
}

.side-panel-body {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}

.submission-details-section {
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(255,255,255,0.03);
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.05);
}

.submission-details-section h4 {
  color: var(--accent1);
  margin-bottom: 15px;
  font-size: 16px;
}

.info-grid {
  display: grid;
  gap: 15px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  color: #a7b7d6;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.info-item span {
  color: #fff;
  font-size: 15px;
}

.similarity-display {
  display: flex;
  gap: 30px;
  align-items: center;
}

.similarity-ring {
  position: relative;
  width: 140px;
  height: 140px;
}

.similarity-ring svg {
  width: 100%;
  height: 100%;
  transform: rotate(0deg);
}

.similarity-percentage {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 28px;
  font-weight: 700;
  color: var(--accent1);
}

.similarity-info {
  flex: 1;
}

.panel-actions {
  display: flex;
  gap: 10px;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid rgba(255,255,255,0.1);
}

.panel-actions .btn {
  flex: 1;
}

/* Panel Overlay */
.panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(2px);
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s;
  z-index: 1999;
}

.panel-overlay.active {
  opacity: 1;
  visibility: visible;
}

@media (max-width: 768px) {
  .side-panel {
    width: 100%;
    right: -100%;
  }
  
  .similarity-display {
    flex-direction: column;
  }
}
</style>

<script>
// Initialize with hardcoded submissions
let submissions = [
  { 
    id: 1, 
    studentId: 1, 
    courseId: 1, 
    instructorId: 4,
    title: 'Introduction to Programming - Assignment 1', 
    filename: 'assignment1.pdf',
    uploadDate: '2024-10-15T10:30:00',
    similarity: 15.5,
    status: 'completed'
  },
  { 
    id: 2, 
    studentId: 2, 
    courseId: 1, 
    instructorId: 4,
    title: 'Programming Basics Essay', 
    filename: 'essay_programming.docx',
    uploadDate: '2024-10-16T14:20:00',
    similarity: 45.2,
    status: 'completed'
  },
  { 
    id: 3, 
    studentId: 1, 
    courseId: 2, 
    instructorId: 5,
    title: 'Academic Writing - Research Paper', 
    filename: 'research_paper.pdf',
    uploadDate: '2024-10-17T09:15:00',
    similarity: 78.9,
    status: 'completed'
  },
  { 
    id: 4, 
    studentId: 3, 
    courseId: 3, 
    instructorId: 4,
    title: 'Calculus Problem Set 1', 
    filename: 'calculus_hw1.pdf',
    uploadDate: '2024-10-18T16:45:00',
    similarity: 22.3,
    status: 'completed'
  },
  { 
    id: 5, 
    studentId: 2, 
    courseId: 3, 
    instructorId: 5,
    title: 'Mathematical Analysis Report', 
    filename: 'math_report.pdf',
    uploadDate: '2024-10-19T11:00:00',
    similarity: null,
    status: 'processing'
  },
];

let nextSubmissionId = 6;
let currentSubmissionId = null;

// Load from localStorage
function loadSubmissions() {
  const saved = localStorage.getItem('submissions');
  if (saved) {
    const data = JSON.parse(saved);
    submissions = data.submissions;
    nextSubmissionId = data.nextSubmissionId;
  }
}

// Save to localStorage
function saveSubmissions() {
  localStorage.setItem('submissions', JSON.stringify({
    submissions: submissions,
    nextSubmissionId: nextSubmissionId
  }));
  
  updateStats();
  updateDashboard();
}

// Get users
function getUsers() {
  const saved = localStorage.getItem('users');
  if (saved) {
    return JSON.parse(saved).users;
  }
  return [
    { id: 1, name: 'Ahmed Hassan', email: 'ahmed@student.edu', role: 'student' },
    { id: 2, name: 'Fatma Ali', email: 'fatma@student.edu', role: 'student' },
    { id: 3, name: 'Mohamed Omar', email: 'mohamed@student.edu', role: 'student' },
    { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor' },
    { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor' },
  ];
}

// Get courses
function getCourses() {
  const saved = localStorage.getItem('courses');
  if (saved) {
    return JSON.parse(saved).courses;
  }
  return [
    { id: 1, code: 'CS101', name: 'Introduction to Programming' },
    { id: 2, code: 'ENG201', name: 'Academic Writing' },
    { id: 3, code: 'MATH150', name: 'Calculus I' },
  ];
}

// Helper functions
function getUserById(id) {
  return getUsers().find(u => u.id === id);
}

function getCourseById(id) {
  return getCourses().find(c => c.id === id);
}

function getSubmissionById(id) {
  return submissions.find(s => s.id === id);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  loadSubmissions();
  updateStats();
  renderSubmissions();
  
  const today = new Date();
  document.getElementById('todayDate').textContent = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

function updateStats() {
  const completed = submissions.filter(s => s.status === 'completed');
  const scores = completed.map(s => s.similarity).filter(s => s !== null);
  
  document.getElementById('subTotal').textContent = submissions.length;
  
  if (scores.length > 0) {
    const avg = scores.reduce((a,b) => a+b, 0) / scores.length;
    document.getElementById('subAvg').textContent = avg.toFixed(1) + '%';
    document.getElementById('subHighRisk').textContent = scores.filter(s => s > 70).length;
  } else {
    document.getElementById('subAvg').textContent = '0%';
    document.getElementById('subHighRisk').textContent = '0';
  }
}

function renderSubmissions() {
  const tbody = document.getElementById('submissionsTableBody');
  
  if (submissions.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#a7b7d6;padding:20px;">No submissions yet.</td></tr>';
    return;
  }
  
  tbody.innerHTML = submissions.map(sub => {
    const student = getUserById(sub.studentId);
    const course = getCourseById(sub.courseId);
    const instructor = sub.instructorId ? getUserById(sub.instructorId) : null;
    
    const scoreClass = sub.similarity === null ? 'processing' 
      : sub.similarity <= 30 ? 'low'
      : sub.similarity <= 70 ? 'medium' : 'high';
    
    return `
      <tr data-student="${student?.name.toLowerCase() || ''}" 
          data-title="${sub.title.toLowerCase()}" 
          data-status="${sub.status}"
          data-risk="${scoreClass}">
        <td><strong>#${sub.id}</strong></td>
        <td>${student?.name || 'Unknown'}</td>
        <td>${sub.title}</td>
        <td><span class="badge">${course?.code || 'N/A'}</span></td>
        <td><small>${instructor?.name || 'None'}</small></td>
        <td><small>${new Date(sub.uploadDate).toLocaleString()}</small></td>
        <td>
          <span class="similarity-score ${scoreClass}">
            ${sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : '—'}
          </span>
        </td>
        <td>
          <span class="status-badge ${sub.status}">
            ${sub.status.charAt(0).toUpperCase() + sub.status.slice(1)}
          </span>
        </td>
        <td>
          <button class="btn small" onclick="viewSubmissionDetails(${sub.id})">
            👁️ View
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function filterSubmissions() {
  const searchInput = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const riskFilter = document.getElementById('riskFilter').value;
  
  const rows = document.querySelectorAll('#submissionsTable tbody tr');
  
  rows.forEach(row => {
    const student = row.getAttribute('data-student');
    const title = row.getAttribute('data-title');
    const status = row.getAttribute('data-status');
    const risk = row.getAttribute('data-risk');
    
    let showRow = true;
    
    if (searchInput && student && title && !student.includes(searchInput) && !title.includes(searchInput)) {
      showRow = false;
    }
    
    if (statusFilter && status !== statusFilter) {
      showRow = false;
    }
    
    if (riskFilter && risk !== riskFilter) {
      showRow = false;
    }
    
    row.style.display = showRow ? '' : 'none';
  });
}

function viewSubmissionDetails(submissionId) {
  currentSubmissionId = submissionId;
  const sub = getSubmissionById(submissionId);
  if (!sub) return;
  
  const student = getUserById(sub.studentId);
  const course = getCourseById(sub.courseId);
  const instructor = sub.instructorId ? getUserById(sub.instructorId) : null;
  
  // Fill panel data
  document.getElementById('panelSubmissionId').textContent = '#' + sub.id;
  document.getElementById('panelDocTitle').textContent = sub.title;
  document.getElementById('panelFilename').textContent = sub.filename;
  document.getElementById('panelSubmitDate').textContent = new Date(sub.uploadDate).toLocaleString();
  
  document.getElementById('panelStudentName').textContent = student?.name || 'Unknown';
  document.getElementById('panelStudentEmail').textContent = student?.email || 'N/A';
  
  document.getElementById('panelCourseCode').textContent = course?.code || 'N/A';
  document.getElementById('panelCourseName').textContent = course?.name || 'N/A';
  document.getElementById('panelInstructor').textContent = instructor?.name || 'None assigned';
  
  // Update similarity ring
  const similarity = sub.similarity !== null ? sub.similarity : 0;
  const circumference = 2 * Math.PI * 45; // radius = 45
  const offset = circumference - (similarity / 100) * circumference;
  
  const circle = document.getElementById('similarityCircle');
  circle.style.strokeDashoffset = offset;
  
  // Color based on risk
  if (sub.similarity === null) {
    circle.style.stroke = '#74b9ff';
  } else if (similarity <= 30) {
    circle.style.stroke = '#7ef3b6';
  } else if (similarity <= 70) {
    circle.style.stroke = '#ffa94d';
  } else {
    circle.style.stroke = '#ff9696';
  }
  
  document.getElementById('panelSimilarity').textContent = sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : 'Processing...';
  
  // Status badge
  const statusBadge = document.getElementById('panelStatus');
  statusBadge.textContent = sub.status.charAt(0).toUpperCase() + sub.status.slice(1);
  statusBadge.className = 'status-badge ' + sub.status;
  
  // Risk level
  let riskText = 'Processing';
  let riskColor = '#74b9ff';
  if (sub.similarity !== null) {
    if (similarity <= 30) {
      riskText = 'Low Risk ✅';
      riskColor = '#7ef3b6';
    } else if (similarity <= 70) {
      riskText = 'Medium Risk ⚠️';
      riskColor = '#ffa94d';
    } else {
      riskText = 'High Risk 🚨';
      riskColor = '#ff9696';
    }
  }
  document.getElementById('panelRiskLevel').innerHTML = `<span style="color: ${riskColor}; font-weight: 600;">${riskText}</span>`;
  
  openPanel('submissionPanel');
}

function closeSubmissionPanel() {
  closePanel('submissionPanel');
  currentSubmissionId = null;
}

function downloadSubmission() {
  const sub = getSubmissionById(currentSubmissionId);
  if (!sub) return;
  
  // Simulate download (since this is frontend only)
  alert(`📥 Downloading: ${sub.filename}\n\nNote: This is a simulated download for demonstration purposes.`);
  
  // In a real application, you would:
  // window.location.href = '/download/' + sub.id;
}

function deleteCurrentSubmission() {
  if (!currentSubmissionId) return;
  
  if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) return;
  
  const index = submissions.findIndex(s => s.id === currentSubmissionId);
  if (index !== -1) {
    submissions.splice(index, 1);
    saveSubmissions();
    renderSubmissions();
    closeSubmissionPanel();
    alert('✅ Submission deleted successfully!');
  }
}

function exportToCSV() {
  const headers = ['ID', 'Student', 'Title', 'Course', 'Instructor', 'Date', 'Similarity', 'Status'];
  const rows = submissions.map(sub => {
    const student = getUserById(sub.studentId);
    const course = getCourseById(sub.courseId);
    const instructor = getUserById(sub.instructorId);
    
    return [
      sub.id,
      student?.name || 'Unknown',
      sub.title,
      course?.code || 'N/A',
      instructor?.name || 'None',
      new Date(sub.uploadDate).toLocaleString(),
      sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : 'Processing',
      sub.status
    ];
  });
  
  let csvContent = headers.join(',') + '\n';
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });
  
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `submissions_${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
  window.URL.revokeObjectURL(url);
}

function updateDashboard() {
  const totalSub = document.getElementById('totalSubmissions');
  if (totalSub) {
    totalSub.textContent = submissions.length;
  }
}

// Panel helper functions
function openPanel(panelId) {
  document.getElementById(panelId).classList.add('open');
  document.getElementById('panelOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closePanel(panelId) {
  document.getElementById(panelId).classList.remove('open');
  document.getElementById('panelOverlay').classList.remove('active');
  document.body.style.overflow = '';
}

function closeAllPanels() {
  document.querySelectorAll('.side-panel').forEach(panel => {
    panel.classList.remove('open');
  });
  document.getElementById('panelOverlay').classList.remove('active');
  document.body.style.overflow = '';
  currentSubmissionId = null;
}
</script>
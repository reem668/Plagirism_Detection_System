
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

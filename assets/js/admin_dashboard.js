
let userChart, similarityChart, courseChart;

document.addEventListener('DOMContentLoaded', function() {
  updateDashboardStats();
  createCharts();
  loadRecentSubmissions();
});

function updateDashboardStats() {
  try {
    const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    
    const users = usersData.users || [];
    const courses = coursesData.courses || [];
    const submissions = submissionsData.submissions || [];
    
    document.getElementById('totalUsers').textContent = users.length;
    document.getElementById('totalCourses').textContent = courses.length;
    document.getElementById('totalSubmissions').textContent = submissions.length;
    
    const highRisk = submissions.filter(s => s.similarity && s.similarity > 70).length;
    document.getElementById('highRiskCount').textContent = highRisk;
  } catch(e) {
    console.log('Using default values');
  }
}

function createCharts() {
  createUserPieChart();
  createSimilarityChart();
  createCourseBarChart();
}

function createUserPieChart() {
  const ctx = document.getElementById('userPieChart');
  
  try {
    const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const users = usersData.users || [];
    
    const students = users.filter(u => u.role === 'student').length;
    const instructors = users.filter(u => u.role === 'instructor').length;
    const admins = users.filter(u => u.role === 'admin').length;
    
    if (userChart) userChart.destroy();
    
    userChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: ['Students', 'Instructors', 'Admins'],
        datasets: [{
          data: [students, instructors, admins],
          backgroundColor: [
            'rgba(0, 198, 255, 0.8)',
            'rgba(126, 243, 182, 0.8)',
            'rgba(255, 169, 77, 0.8)'
          ],
          borderColor: [
            'rgba(0, 198, 255, 1)',
            'rgba(126, 243, 182, 1)',
            'rgba(255, 169, 77, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff',
            callbacks: {
              label: function(context) {
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
              }
            }
          }
        }
      }
    });
    
    // Create legend
    document.getElementById('userLegend').innerHTML = `
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(0, 198, 255, 0.8);"></div>
        <span>Students (${students})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(126, 243, 182, 0.8);"></div>
        <span>Instructors (${instructors})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(255, 169, 77, 0.8);"></div>
        <span>Admins (${admins})</span>
      </div>
    `;
  } catch(e) {
    console.error('Error creating user chart:', e);
  }
}

function createSimilarityChart() {
  const ctx = document.getElementById('similarityChart');
  
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const submissions = submissionsData.submissions || [];
    
    const completed = submissions.filter(s => s.status === 'completed' && s.similarity !== null);
    
    const low = completed.filter(s => s.similarity <= 30).length;
    const medium = completed.filter(s => s.similarity > 30 && s.similarity <= 70).length;
    const high = completed.filter(s => s.similarity > 70).length;
    
    if (similarityChart) similarityChart.destroy();
    
    similarityChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Low (0-30%)', 'Medium (31-70%)', 'High (>70%)'],
        datasets: [{
          data: [low, medium, high],
          backgroundColor: [
            'rgba(126, 243, 182, 0.8)',
            'rgba(255, 169, 77, 0.8)',
            'rgba(255, 90, 107, 0.8)'
          ],
          borderColor: [
            'rgba(126, 243, 182, 1)',
            'rgba(255, 169, 77, 1)',
            'rgba(255, 90, 107, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff'
          }
        },
        cutout: '60%'
      }
    });
    
    // Create legend
    document.getElementById('similarityLegend').innerHTML = `
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(126, 243, 182, 0.8);"></div>
        <span>Low Risk (${low})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(255, 169, 77, 0.8);"></div>
        <span>Medium Risk (${medium})</span>
      </div>
      <div class="legend-item">
        <div class="legend-color" style="background: rgba(255, 90, 107, 0.8);"></div>
        <span>High Risk (${high})</span>
      </div>
    `;
  } catch(e) {
    console.error('Error creating similarity chart:', e);
  }
}

function createCourseBarChart() {
  const ctx = document.getElementById('courseBarChart');
  
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    
    const submissions = submissionsData.submissions || [];
    const courses = coursesData.courses || [];
    
    const courseLabels = courses.map(c => c.code);
    const courseCounts = courses.map(course => {
      return submissions.filter(s => s.courseId === course.id).length;
    });
    
    if (courseChart) courseChart.destroy();
    
    courseChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: courseLabels,
        datasets: [{
          label: 'Submissions',
          data: courseCounts,
          backgroundColor: 'rgba(0, 198, 255, 0.7)',
          borderColor: 'rgba(0, 198, 255, 1)',
          borderWidth: 2,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              color: '#a7b7d6',
              stepSize: 1
            },
            grid: {
              color: 'rgba(255, 255, 255, 0.05)'
            }
          },
          x: {
            ticks: {
              color: '#a7b7d6'
            },
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#fff',
            bodyColor: '#fff'
          }
        }
      }
    });
  } catch(e) {
    console.error('Error creating course chart:', e);
  }
}

function loadRecentSubmissions() {
  const container = document.getElementById('recentSubmissions');
  
  try {
    const submissionsData = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    const usersData = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    const coursesData = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    
    const submissions = submissionsData.submissions || [];
    const users = usersData.users || [];
    const courses = coursesData.courses || [];
    
    if (submissions.length === 0) {
      container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet</p></div>';
      return;
    }
    
    const recent = submissions.slice(-5).reverse();
    
    container.innerHTML = recent.map(sub => {
      const student = users.find(u => u.id === sub.studentId);
      const course = courses.find(c => c.id === sub.courseId);
      
      const scoreClass = sub.similarity === null ? 'processing' 
        : sub.similarity <= 30 ? 'low'
        : sub.similarity <= 70 ? 'medium' : 'high';
      
      return `
        <div class="submission-item">
          <div class="submission-info">
            <div class="submission-title">${sub.title}</div>
            <div class="submission-meta">
              <span class="meta-item"><i class="fas fa-user"></i> ${student?.name || 'Unknown'}</span>
              <span class="meta-item"><i class="fas fa-book"></i> ${course?.code || 'N/A'}</span>
              <span class="meta-item"><i class="fas fa-calendar"></i> ${new Date(sub.uploadDate).toLocaleDateString()}</span>
            </div>
          </div>
          <span class="similarity-score ${scoreClass}">
            ${sub.similarity !== null ? sub.similarity.toFixed(1) + '%' : '⏳ Processing'}
          </span>
        </div>
      `;
    }).join('');
  } catch(e) {
    container.innerHTML = '<div class="empty-state"><p>No data available</p></div>';
  }
}

// Refresh dashboard when window regains focus
window.addEventListener('focus', function() {
  updateDashboardStats();
  createCharts();
  loadRecentSubmissions();
});

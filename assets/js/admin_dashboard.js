
let userChart, similarityChart, courseChart;

document.addEventListener('DOMContentLoaded', function() {
  // Stats are already populated by PHP in the HTML
  createCharts();
  loadRecentSubmissions();
});

function createCharts() {
  createUserPieChart();
  createSimilarityChart();
  createCourseBarChart();
}

function createUserPieChart() {
  const ctx = document.getElementById('userPieChart');
  
  try {
    const stats = window.dashboardStats || {};
    const userDist = stats.userDistribution || {};
    
    const students = userDist.student || 0;
    const instructors = userDist.instructor || 0;
    const admins = userDist.admin || 0;
    
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
    const stats = window.dashboardStats || {};
    const similarityDist = stats.similarityDistribution || {};
    
    const low = similarityDist['Low (0-30%)'] || 0;
    const medium = similarityDist['Medium (31-70%)'] || 0;
    const high = similarityDist['High (71-100%)'] || 0;
    
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
    const stats = window.dashboardStats || {};
    const courseActivity = stats.courseActivity || [];
    
    const courseLabels = courseActivity.map(c => c.name);
    const courseCounts = courseActivity.map(c => c.count);
    
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
    const stats = window.dashboardStats || {};
    const recentSubmissions = stats.recentSubmissions || [];
    
    if (recentSubmissions.length === 0) {
      container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No submissions yet</p></div>';
      return;
    }
    
    container.innerHTML = recentSubmissions.map(sub => {
      const scoreClass = sub.similarity === null ? 'processing' 
        : sub.similarity <= 30 ? 'low'
        : sub.similarity <= 70 ? 'medium' : 'high';
      
      const statusBadge = sub.status === 'accepted' ? '✓ Accepted' 
        : sub.status === 'rejected' ? '✗ Rejected'
        : sub.status === 'pending' ? '⏳ Pending' : 'Active';
      
      return `
        <div class="submission-item">
          <div class="submission-info">
            <div class="submission-title">Submission #${sub.id}</div>
            <div class="submission-meta">
              <span class="meta-item"><i class="fas fa-user"></i> ${sub.student_name || 'Unknown'}</span>
              <span class="meta-item"><i class="fas fa-book"></i> ${sub.course_name || 'General Submission'}</span>
              <span class="meta-item"><i class="fas fa-calendar"></i> ${new Date(sub.created_at).toLocaleDateString()}</span>
              <span class="meta-item"><i class="fas fa-info-circle"></i> ${statusBadge}</span>
            </div>
          </div>
          <span class="similarity-score ${scoreClass}">
            ${sub.similarity !== null ? sub.similarity + '%' : '⏳ Processing'}
          </span>
        </div>
      `;
    }).join('');
  } catch(e) {
    console.error('Error loading recent submissions:', e);
    container.innerHTML = '<div class="empty-state"><p>Error loading data</p></div>';
  }
}

// Refresh dashboard when window regains focus
window.addEventListener('focus', function() {
  location.reload(); // Reload to get fresh data from database
});

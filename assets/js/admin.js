document.addEventListener('DOMContentLoaded', () => {
  // sidebar toggle (button)
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const main = document.getElementById('mainContent');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      if (sidebar.classList.contains('forced-open')) {
        sidebar.classList.remove('forced-open');
        sidebar.style.width = '';
        main.style.marginLeft = '';
      } else {
        sidebar.classList.add('forced-open');
        sidebar.style.width = '220px';
        main.style.marginLeft = '220px';
      }
    });
  }

  // filtering (client-side helper for pages that need it)
  const filterButtons = document.querySelectorAll('.filter-row .btn');
  if (filterButtons.length) {
    filterButtons.forEach(b => {
      b.addEventListener('click', function(e){
        filterButtons.forEach(x => x.classList.remove('active'));
        this.classList.add('active');
      });
    });
  }
});
// Global Data Management System
// Add this to your existing assets/js/script.js file

// Initialize default data if localStorage is empty
function initializeDefaultData() {
  // Initialize Users
  if (!localStorage.getItem('users')) {
    const defaultUsers = {
      users: [
        { id: 1, name: 'Ahmed Hassan', email: 'ahmed@student.edu', role: 'student', status: 'active' },
        { id: 2, name: 'Fatma Ali', email: 'fatma@student.edu', role: 'student', status: 'active' },
        { id: 3, name: 'Mohamed Omar', email: 'mohamed@student.edu', role: 'student', status: 'banned' },
        { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor', status: 'active' },
        { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor', status: 'active' },
        { id: 6, name: 'Admin User', email: 'admin@university.edu', role: 'admin', status: 'active' },
      ],
      nextUserId: 7
    };
    localStorage.setItem('users', JSON.stringify(defaultUsers));
  }

  // Initialize Courses
  if (!localStorage.getItem('courses')) {
    const defaultCourses = {
      courses: [
        { 
          id: 1, 
          code: 'CS101', 
          name: 'Introduction to Programming', 
          department: 'Computer Science', 
          term: 'Fall 2024',
          instructors: [4]
        },
        { 
          id: 2, 
          code: 'ENG201', 
          name: 'Academic Writing', 
          department: 'English', 
          term: 'Fall 2024',
          instructors: [5]
        },
        { 
          id: 3, 
          code: 'MATH150', 
          name: 'Calculus I', 
          department: 'Mathematics', 
          term: 'Spring 2025',
          instructors: [4, 5]
        },
      ],
      nextCourseId: 4
    };
    localStorage.setItem('courses', JSON.stringify(defaultCourses));
  }

  // Initialize Submissions
  if (!localStorage.getItem('submissions')) {
    const defaultSubmissions = {
      submissions: [
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
      ],
      nextSubmissionId: 6
    };
    localStorage.setItem('submissions', JSON.stringify(defaultSubmissions));
  }

  // Initialize System Settings
  if (!localStorage.getItem('systemSettings')) {
    const defaultSettings = {
      maxUploadSize: 10,
      plagiarismThreshold: 50,
      submissionQuota: 20
    };
    localStorage.setItem('systemSettings', JSON.stringify(defaultSettings));
  }
}

// Global Data Access Helper
window.AppData = {
  // Get all users
  getUsers: function() {
    const data = JSON.parse(localStorage.getItem('users') || '{"users":[]}');
    return data.users || [];
  },

  // Get user by ID
  getUserById: function(id) {
    return this.getUsers().find(u => u.id === id);
  },

  // Get all courses
  getCourses: function() {
    const data = JSON.parse(localStorage.getItem('courses') || '{"courses":[]}');
    return data.courses || [];
  },

  // Get course by ID
  getCourseById: function(id) {
    return this.getCourses().find(c => c.id === id);
  },

  // Get all submissions
  getSubmissions: function() {
    const data = JSON.parse(localStorage.getItem('submissions') || '{"submissions":[]}');
    return data.submissions || [];
  },

  // Get submission by ID
  getSubmissionById: function(id) {
    return this.getSubmissions().find(s => s.id === id);
  },

  // Get system settings
  getSettings: function() {
    return JSON.parse(localStorage.getItem('systemSettings') || '{}');
  },

  // Get submissions by student
  getSubmissionsByStudent: function(studentId) {
    return this.getSubmissions().filter(s => s.studentId === studentId);
  },

  // Get submissions by course
  getSubmissionsByCourse: function(courseId) {
    return this.getSubmissions().filter(s => s.courseId === courseId);
  },

  // Get submissions by instructor
  getSubmissionsByInstructor: function(instructorId) {
    return this.getSubmissions().filter(s => s.instructorId === instructorId);
  },

  // Get statistics
  getStats: function() {
    const users = this.getUsers();
    const courses = this.getCourses();
    const submissions = this.getSubmissions();
    
    const completed = submissions.filter(s => s.status === 'completed' && s.similarity !== null);
    const scores = completed.map(s => s.similarity);
    
    return {
      totalUsers: users.length,
      totalStudents: users.filter(u => u.role === 'student').length,
      totalInstructors: users.filter(u => u.role === 'instructor').length,
      totalAdmins: users.filter(u => u.role === 'admin').length,
      totalCourses: courses.length,
      totalSubmissions: submissions.length,
      completedSubmissions: completed.length,
      processingSubmissions: submissions.filter(s => s.status === 'processing').length,
      averageSimilarity: scores.length > 0 ? scores.reduce((a,b) => a+b, 0) / scores.length : 0,
      highRiskSubmissions: scores.filter(s => s > 70).length,
      mediumRiskSubmissions: scores.filter(s => s > 30 && s <= 70).length,
      lowRiskSubmissions: scores.filter(s => s <= 30).length,
    };
  }
};

// Initialize data when page loads
document.addEventListener('DOMContentLoaded', function() {
  initializeDefaultData();
});

// Event to refresh dashboard when data changes
function triggerDataUpdate() {
  // Dispatch custom event that other pages can listen to
  window.dispatchEvent(new CustomEvent('dataUpdated'));
  
  // Update dashboard if on home page
  if (typeof updateDashboardStats === 'function') {
    updateDashboardStats();
  }
}

// Export helper for localStorage save operations
window.saveWithUpdate = function(key, data) {
  localStorage.setItem(key, JSON.stringify(data));
  triggerDataUpdate();
};

console.log('📊 Global Data Management initialized');
console.log('📈 Current Stats:', window.AppData.getStats());


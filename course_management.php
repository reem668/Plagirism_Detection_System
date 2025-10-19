<section class="course-management">
  <h2>Course Management 📚</h2>

  <div id="courseNotification" class="notice" style="display:none;"></div>

  <!-- Add New Course Form -->
  <div class="add-user-form">
    <h3>Create New Course ➕</h3>
    <form id="addCourseForm" class="add-form" onsubmit="addCourse(event)">
      <input type="text" id="newCourseCode" placeholder="Course Code (e.g., CS101)" required>
      <input type="text" id="newCourseName" placeholder="Course Name" required>
      <input type="text" id="newDepartment" placeholder="Department" required>
      <select id="newTerm" required>
        <option value="">Select Term</option>
        <option value="Fall 2024">Fall 2024</option>
        <option value="Spring 2025">Spring 2025</option>
        <option value="Summer 2025">Summer 2025</option>
      </select>
      <button type="submit" class="btn primary">Create Course</button>
    </form>
  </div>

  <!-- Courses Table -->
  <div class="courses-table-container">
    <h3>All Courses</h3>
    <table class="courses-table">
      <thead>
        <tr>
          <th>Course Code</th>
          <th>Course Name</th>
          <th>Department</th>
          <th>Term</th>
          <th>Instructors</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="coursesTableBody">
        <!-- Courses will be loaded here by JavaScript -->
      </tbody>
    </table>
  </div>
</section>

<!-- Side Panel for Course Details -->
<div id="courseDetailsPanel" class="side-panel">
  <div class="side-panel-content">
    <div class="side-panel-header">
      <h3><i class="fas fa-book"></i> Course Details</h3>
      <button class="close-panel-btn" onclick="closeCoursePanel()">&times;</button>
    </div>
    <div class="side-panel-body">
      <div class="course-info-section">
        <h4>Course Information</h4>
        <div class="info-grid">
          <div class="info-item">
            <label>Code:</label>
            <span id="panelCourseCode"></span>
          </div>
          <div class="info-item">
            <label>Name:</label>
            <span id="panelCourseName"></span>
          </div>
          <div class="info-item">
            <label>Department:</label>
            <span id="panelDepartment"></span>
          </div>
          <div class="info-item">
            <label>Term:</label>
            <span id="panelTerm"></span>
          </div>
        </div>
      </div>

      <div class="instructors-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
          <h4>👨‍🏫 Assigned Instructors</h4>
          <button class="btn small primary" onclick="openAssignInstructorPanel()">+ Assign</button>
        </div>
        <div class="instructors-list" id="panelInstructorsList"></div>
      </div>
    </div>
  </div>
</div>

<!-- Side Panel for Edit Course -->
<div id="editCoursePanel" class="side-panel">
  <div class="side-panel-content">
    <div class="side-panel-header">
      <h3><i class="fas fa-edit"></i> Edit Course</h3>
      <button class="close-panel-btn" onclick="closeEditCoursePanel()">&times;</button>
    </div>
    <div class="side-panel-body">
      <form id="editCourseForm" onsubmit="saveCourseEdit(event)">
        <input type="hidden" id="editCourseId">
        
        <div class="form-group">
          <label>Course Code</label>
          <input type="text" id="editCourseCode" class="form-input" required>
        </div>

        <div class="form-group">
          <label>Course Name</label>
          <input type="text" id="editCourseName" class="form-input" required>
        </div>

        <div class="form-group">
          <label>Department</label>
          <input type="text" id="editDepartment" class="form-input" required>
        </div>

        <div class="form-group">
          <label>Term</label>
          <select id="editTerm" class="form-input" required>
            <option value="Fall 2024">Fall 2024</option>
            <option value="Spring 2025">Spring 2025</option>
            <option value="Summer 2025">Summer 2025</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn primary">💾 Save Changes</button>
          <button type="button" class="btn" onclick="closeEditCoursePanel()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Side Panel for Assign Instructor -->
<div id="assignInstructorPanel" class="side-panel">
  <div class="side-panel-content">
    <div class="side-panel-header">
      <h3><i class="fas fa-user-plus"></i> Assign Instructor</h3>
      <button class="close-panel-btn" onclick="closeAssignInstructorPanel()">&times;</button>
    </div>
    <div class="side-panel-body">
      <div class="instructor-select-list" id="instructorSelectList"></div>
      <div class="form-actions">
        <button class="btn primary" onclick="assignSelectedInstructors()">Assign Selected</button>
        <button class="btn" onclick="closeAssignInstructorPanel()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<style>
.courses-table-container {
  margin-top: 20px;
  background: rgba(255,255,255,0.02);
  padding: 20px;
  border-radius: 14px;
  overflow-x: auto;
}

.courses-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.courses-table thead {
  background: rgba(255,255,255,0.05);
}

.courses-table th,
.courses-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  color: #eaf2ff;
}

.courses-table th {
  font-weight: 600;
  color: var(--accent1);
}

.courses-table tbody tr:hover {
  background: rgba(255,255,255,0.03);
}

.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  background: rgba(0,198,255,0.15);
  color: var(--accent1);
  font-size: 12px;
  font-weight: 600;
}

/* Side Panel Styles */
.side-panel {
  position: fixed;
  top: 0;
  right: -500px;
  width: 500px;
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

.course-info-section,
.instructors-section {
  margin-bottom: 30px;
  padding: 20px;
  background: rgba(255,255,255,0.03);
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.05);
}

.course-info-section h4,
.instructors-section h4 {
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

.instructor-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  margin: 10px 0;
  background: rgba(255,255,255,0.05);
  border-radius: 10px;
  border-left: 3px solid var(--accent1);
  transition: all 0.3s;
}

.instructor-item:hover {
  background: rgba(255,255,255,0.08);
  transform: translateX(5px);
}

.instructor-info strong {
  color: #fff;
  display: block;
  margin-bottom: 4px;
}

.instructor-info small {
  color: #a7b7d6;
}

.instructors-list {
  margin-top: 10px;
  max-height: 400px;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  color: #a7b7d6;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.05);
  color: #fff;
  font-size: 14px;
  transition: all 0.3s;
}

.form-input:focus {
  outline: none;
  background: rgba(255,255,255,0.08);
  border-color: var(--accent1);
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 25px;
}

.instructor-select-list {
  max-height: 500px;
  overflow-y: auto;
}

.instructor-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  background: rgba(255,255,255,0.03);
  border-radius: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: all 0.3s;
  border: 2px solid transparent;
}

.instructor-option:hover {
  background: rgba(255,255,255,0.06);
  border-color: var(--accent1);
}

.instructor-option input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: var(--accent1);
}

.instructor-option-info {
  flex: 1;
}

.instructor-option-info strong {
  color: #fff;
  display: block;
  margin-bottom: 4px;
}

.instructor-option-info small {
  color: #a7b7d6;
  font-size: 12px;
}

.instructor-option-info .assigned-badge {
  display: inline-block;
  margin-top: 5px;
  padding: 3px 8px;
  background: rgba(126,243,182,0.2);
  color: #7ef3b6;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
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
}
</style>

<!-- Panel Overlay -->
<div id="panelOverlay" class="panel-overlay" onclick="closeAllPanels()"></div>

<script>
// Initialize with hardcoded data
let courses = [
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
];

let nextCourseId = 4;
let currentCourseId = null;

function getUsers() {
  const saved = localStorage.getItem('users');
  if (saved) {
    return JSON.parse(saved).users;
  }
  return [
    { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor' },
    { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor' },
  ];
}

function loadCourses() {
  const saved = localStorage.getItem('courses');
  if (saved) {
    const data = JSON.parse(saved);
    courses = data.courses;
    nextCourseId = data.nextCourseId;
  }
}

function saveCourses() {
  localStorage.setItem('courses', JSON.stringify({
    courses: courses,
    nextCourseId: nextCourseId
  }));
  
  const totalCourses = document.getElementById('totalCourses');
  if (totalCourses) {
    totalCourses.textContent = courses.length;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  loadCourses();
  renderCourses();
});

function renderCourses() {
  const tbody = document.getElementById('coursesTableBody');
  
  if (courses.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#a7b7d6;padding:20px;">No courses yet. Create one above!</td></tr>';
    return;
  }
  
  tbody.innerHTML = courses.map(course => {
    const instructorCount = course.instructors.length;
    
    return `
      <tr>
        <td><strong>${course.code}</strong></td>
        <td>${course.name}</td>
        <td>${course.department}</td>
        <td>${course.term}</td>
        <td><span class="badge">${instructorCount} Assigned</span></td>
        <td>
          <button class="btn small" onclick="viewCourseDetails(${course.id})">👁️ View</button>
          <button class="btn small" onclick="openEditCoursePanel(${course.id})">✏️ Edit</button>
          <button class="btn small danger" onclick="deleteCourse(${course.id})">🗑️ Delete</button>
        </td>
      </tr>
    `;
  }).join('');
}

function addCourse(event) {
  event.preventDefault();
  
  const code = document.getElementById('newCourseCode').value.trim();
  const name = document.getElementById('newCourseName').value.trim();
  const department = document.getElementById('newDepartment').value.trim();
  const term = document.getElementById('newTerm').value;

  if (courses.find(c => c.code.toLowerCase() === code.toLowerCase())) {
    showCourseNotification('⚠️ Course code already exists!', 'error');
    return;
  }

  const newCourse = {
    id: nextCourseId++,
    code: code,
    name: name,
    department: department,
    term: term,
    instructors: []
  };

  courses.push(newCourse);
  saveCourses();

  document.getElementById('addCourseForm').reset();
  renderCourses();
  showCourseNotification('✅ Course created successfully!', 'success');
}

function viewCourseDetails(courseId) {
  currentCourseId = courseId;
  const course = courses.find(c => c.id === courseId);
  if (!course) return;

  document.getElementById('panelCourseCode').textContent = course.code;
  document.getElementById('panelCourseName').textContent = course.name;
  document.getElementById('panelDepartment').textContent = course.department;
  document.getElementById('panelTerm').textContent = course.term;

  const users = getUsers();
  const instructors = course.instructors.map(id => users.find(u => u.id === id)).filter(u => u);
  
  document.getElementById('panelInstructorsList').innerHTML = instructors.length > 0 
    ? instructors.map(inst => `
      <div class="instructor-item">
        <div class="instructor-info">
          <strong>${inst.name}</strong>
          <small>${inst.email}</small>
        </div>
        <button class="btn small danger" onclick="removeInstructor(${courseId}, ${inst.id})">Remove</button>
      </div>
    `).join('')
    : '<p style="color:#a7b7d6;padding:10px;">No instructors assigned yet.</p>';

  openPanel('courseDetailsPanel');
}

function closeCoursePanel() {
  closePanel('courseDetailsPanel');
  currentCourseId = null;
}

function openEditCoursePanel(courseId) {
  const course = courses.find(c => c.id === courseId);
  if (!course) return;

  document.getElementById('editCourseId').value = course.id;
  document.getElementById('editCourseCode').value = course.code;
  document.getElementById('editCourseName').value = course.name;
  document.getElementById('editDepartment').value = course.department;
  document.getElementById('editTerm').value = course.term;

  openPanel('editCoursePanel');
}

function closeEditCoursePanel() {
  closePanel('editCoursePanel');
}

function saveCourseEdit(event) {
  event.preventDefault();

  const courseId = parseInt(document.getElementById('editCourseId').value);
  const code = document.getElementById('editCourseCode').value.trim();
  const name = document.getElementById('editCourseName').value.trim();
  const department = document.getElementById('editDepartment').value.trim();
  const term = document.getElementById('editTerm').value;

  if (courses.find(c => c.code.toLowerCase() === code.toLowerCase() && c.id !== courseId)) {
    showCourseNotification('⚠️ Course code already exists!', 'error');
    return;
  }

  const course = courses.find(c => c.id === courseId);
  if (course) {
    course.code = code;
    course.name = name;
    course.department = department;
    course.term = term;
    
    saveCourses();
    renderCourses();
    closeEditCoursePanel();
    showCourseNotification('✅ Course updated successfully!', 'success');
  }
}

function deleteCourse(courseId) {
  if (!confirm('Are you sure you want to delete this course?')) return;

  const index = courses.findIndex(c => c.id === courseId);
  if (index !== -1) {
    courses.splice(index, 1);
    saveCourses();
    renderCourses();
    showCourseNotification('🗑️ Course deleted successfully!', 'success');
  }
}

function openAssignInstructorPanel() {
  const users = getUsers();
  const instructors = users.filter(u => u.role === 'instructor');
  const course = courses.find(c => c.id === currentCourseId);
  
  if (instructors.length === 0) {
    alert('No instructors available! Please add instructors in User Management first.');
    return;
  }
  
  document.getElementById('instructorSelectList').innerHTML = instructors.map(inst => {
    const isAssigned = course.instructors.includes(inst.id);
    return `
      <label class="instructor-option">
        <input type="checkbox" value="${inst.id}" ${isAssigned ? 'checked disabled' : ''}>
        <div class="instructor-option-info">
          <strong>${inst.name}</strong>
          <small>${inst.email}</small>
          ${isAssigned ? '<span class="assigned-badge">✓ Already assigned</span>' : ''}
        </div>
      </label>
    `;
  }).join('');

  openPanel('assignInstructorPanel');
}

function closeAssignInstructorPanel() {
  closePanel('assignInstructorPanel');
}

function assignSelectedInstructors() {
  const checkboxes = document.querySelectorAll('#instructorSelectList input[type="checkbox"]:checked:not(:disabled)');
  const instructorIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
  
  if (instructorIds.length === 0) {
    showCourseNotification('⚠️ Please select at least one instructor!', 'error');
    return;
  }

  const course = courses.find(c => c.id === currentCourseId);
  if (course) {
    instructorIds.forEach(id => {
      if (!course.instructors.includes(id)) {
        course.instructors.push(id);
      }
    });
    
    saveCourses();
    closeAssignInstructorPanel();
    viewCourseDetails(currentCourseId);
    renderCourses();
    showCourseNotification('✅ Instructor(s) assigned successfully!', 'success');
  }
}

function removeInstructor(courseId, instructorId) {
  if (!confirm('Remove this instructor from the course?')) return;

  const course = courses.find(c => c.id === courseId);
  if (course) {
    const index = course.instructors.indexOf(instructorId);
    if (index !== -1) {
      course.instructors.splice(index, 1);
      saveCourses();
      viewCourseDetails(courseId);
      renderCourses();
      showCourseNotification('✅ Instructor removed successfully!', 'success');
    }
  }
}

function showCourseNotification(message, type) {
  const notification = document.getElementById('courseNotification');
  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
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
}
</script>
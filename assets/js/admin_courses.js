
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

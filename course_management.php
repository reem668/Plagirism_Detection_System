<?php
// Dummy data for courses (frontend only - no backend)
$courses = [
  [
    'id' => 1,
    'code' => 'CS101',
    'name' => 'Introduction to Programming',
    'department' => 'Computer Science',
    'term' => 'Fall 2024',
    'instructors_count' => 2,
    'students_count' => 45
  ],
  [
    'id' => 2,
    'code' => 'ENG201',
    'name' => 'Academic Writing',
    'department' => 'English',
    'term' => 'Fall 2024',
    'instructors_count' => 1,
    'students_count' => 32
  ],
  [
    'id' => 3,
    'code' => 'MATH150',
    'name' => 'Calculus I',
    'department' => 'Mathematics',
    'term' => 'Spring 2025',
    'instructors_count' => 3,
    'students_count' => 60
  ],
];

// Dummy instructors list
$instructors = [
  ['id' => 1, 'name' => 'Dr. Ahmed Mohamed', 'email' => 'ahmed@university.edu'],
  ['id' => 2, 'name' => 'Prof. Sara Ali', 'email' => 'sara@university.edu'],
  ['id' => 3, 'name' => 'Dr. Omar Hassan', 'email' => 'omar@university.edu'],
  ['id' => 4, 'name' => 'Dr. Fatma Ibrahim', 'email' => 'fatma@university.edu'],
];
?>

<section class="course-management">
  <h2>Course Management 📚</h2>

  <!-- Success/Error Messages -->
  <?php if (isset($_GET['course_added'])): ?>
    <div class="notice success">✅ Course created successfully!</div>
  <?php endif; ?>

  <!-- Add New Course Form -->
  <div class="add-user-form">
    <h3>Create New Course ➕</h3>
    <form method="POST" action="#" class="add-form" onsubmit="event.preventDefault(); alert('Frontend only - Backend not implemented yet');">
      <input type="text" name="course_code" placeholder="Course Code (e.g., CS101)" required>
      <input type="text" name="course_name" placeholder="Course Name" required>
      <input type="text" name="department" placeholder="Department" required>
      <select name="term" required>
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
          <th>Students</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($courses as $course): ?>
        <tr>
          <td><strong><?= htmlspecialchars($course['code']) ?></strong></td>
          <td><?= htmlspecialchars($course['name']) ?></td>
          <td><?= htmlspecialchars($course['department']) ?></td>
          <td><?= htmlspecialchars($course['term']) ?></td>
          <td><span class="badge"><?= $course['instructors_count'] ?> Assigned</span></td>
          <td><span class="badge"><?= $course['students_count'] ?> Enrolled</span></td>
          <td>
            <button class="btn small" onclick="viewCourseDetails(<?= $course['id'] ?>)">👁️ View</button>
            <button class="btn small" onclick="editCourse(<?= $course['id'] ?>)">✏️ Edit</button>
            <button class="btn small danger" onclick="if(confirm('Delete this course?')) alert('Frontend only')">🗑️ Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Course Details Modal (Hidden by default) -->
  <div id="courseDetailsModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Course Details</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="course-info-section">
          <h4>📖 Course Information</h4>
          <p><strong>Code:</strong> <span id="modalCourseCode">CS101</span></p>
          <p><strong>Name:</strong> <span id="modalCourseName">Introduction to Programming</span></p>
          <p><strong>Department:</strong> <span id="modalDepartment">Computer Science</span></p>
          <p><strong>Term:</strong> <span id="modalTerm">Fall 2024</span></p>
        </div>

        <div class="instructors-section">
          <h4>👨‍🏫 Assigned Instructors (2)</h4>
          <button class="btn small primary" onclick="openAssignInstructorModal()">+ Assign Instructor</button>
          <div class="instructors-list">
            <div class="instructor-item">
              <div>
                <strong>Dr. Ahmed Mohamed</strong><br>
                <small>ahmed@university.edu</small><br>
                <small class="text-muted">Assigned: Oct 1, 2024</small>
              </div>
              <button class="btn small danger" onclick="if(confirm('Remove instructor?')) alert('Frontend only')">Remove</button>
            </div>
            <div class="instructor-item">
              <div>
                <strong>Prof. Sara Ali</strong><br>
                <small>sara@university.edu</small><br>
                <small class="text-muted">Assigned: Oct 1, 2024</small>
              </div>
              <button class="btn small danger" onclick="if(confirm('Remove instructor?')) alert('Frontend only')">Remove</button>
            </div>
          </div>
        </div>

        <div class="students-section">
          <h4>👥 Enrolled Students (45)</h4>
          <button class="btn small primary" onclick="alert('Enroll student feature - Frontend only')">+ Enroll Student</button>
          <button class="btn small" onclick="alert('Bulk enroll feature - Frontend only')">📄 Bulk Enroll (CSV)</button>
          <div class="students-list">
            <div class="student-item">
              <div>
                <strong>Ahmed Hassan</strong><br>
                <small>ahmed.hassan@student.edu</small><br>
                <small class="text-muted">Enrolled: Sep 15, 2024 | Submissions: 3</small>
              </div>
              <button class="btn small danger" onclick="if(confirm('Remove student?')) alert('Frontend only')">Remove</button>
            </div>
            <div class="student-item">
              <div>
                <strong>Fatma Ali</strong><br>
                <small>fatma.ali@student.edu</small><br>
                <small class="text-muted">Enrolled: Sep 15, 2024 | Submissions: 5</small>
              </div>
              <button class="btn small danger" onclick="if(confirm('Remove student?')) alert('Frontend only')">Remove</button>
            </div>
            <div class="student-item">
              <div>
                <strong>Mohamed Omar</strong><br>
                <small>mohamed.omar@student.edu</small><br>
                <small class="text-muted">Enrolled: Sep 16, 2024 | Submissions: 2</small>
              </div>
              <button class="btn small danger" onclick="if(confirm('Remove student?')) alert('Frontend only')">Remove</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Instructor Modal -->
  <div id="assignInstructorModal" class="modal" style="display: none;">
    <div class="modal-content small">
      <div class="modal-header">
        <h3>Assign Instructor</h3>
        <button class="close-btn" onclick="closeAssignModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form onsubmit="event.preventDefault(); alert('Frontend only - Backend not implemented'); closeAssignModal();">
          <label>Search Instructor:</label>
          <input type="text" placeholder="Type name or email..." class="search-input">
          
          <div class="instructor-select-list">
            <?php foreach ($instructors as $inst): ?>
            <label class="instructor-option">
              <input type="checkbox" name="instructor[]" value="<?= $inst['id'] ?>">
              <span>
                <strong><?= htmlspecialchars($inst['name']) ?></strong><br>
                <small><?= htmlspecialchars($inst['email']) ?></small>
              </span>
            </label>
            <?php endforeach; ?>
          </div>

          <div style="margin-top: 15px;">
            <button type="submit" class="btn primary">Assign Selected</button>
            <button type="button" class="btn" onclick="closeAssignModal()">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</section>

<style>
/* Table Styles */
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

/* Modal Styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
  border-radius: 16px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.modal-content.small {
  max-width: 500px;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: #fff;
}

.close-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 28px;
  cursor: pointer;
  transition: transform 0.2s;
}

.close-btn:hover {
  transform: rotate(90deg);
  color: #ff5a6b;
}

.modal-body {
  padding: 20px;
}

.course-info-section,
.instructors-section,
.students-section {
  margin-bottom: 25px;
  padding: 15px;
  background: rgba(255,255,255,0.02);
  border-radius: 10px;
}

.course-info-section h4,
.instructors-section h4,
.students-section h4 {
  color: var(--accent1);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.instructor-item,
.student-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  margin: 8px 0;
  background: rgba(255,255,255,0.03);
  border-radius: 8px;
  transition: background 0.2s;
}

.instructor-item:hover,
.student-item:hover {
  background: rgba(255,255,255,0.05);
}

.text-muted {
  color: #a7b7d6;
  font-size: 11px;
}

.instructors-list,
.students-list {
  margin-top: 10px;
  max-height: 200px;
  overflow-y: auto;
}

.search-input {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: none;
  background: rgba(255,255,255,0.05);
  color: #fff;
  margin-bottom: 15px;
}

.instructor-select-list {
  max-height: 300px;
  overflow-y: auto;
  margin-top: 10px;
}

.instructor-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: rgba(255,255,255,0.03);
  border-radius: 8px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.instructor-option:hover {
  background: rgba(255,255,255,0.06);
}

.instructor-option input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
</style>

<script>
function viewCourseDetails(courseId) {
  document.getElementById('courseDetailsModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('courseDetailsModal').style.display = 'none';
}

function editCourse(courseId) {
  alert('Edit course #' + courseId + ' - Frontend only (Backend not implemented)');
}

function openAssignInstructorModal() {
  document.getElementById('assignInstructorModal').style.display = 'flex';
}

function closeAssignModal() {
  document.getElementById('assignInstructorModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  const courseModal = document.getElementById('courseDetailsModal');
  const assignModal = document.getElementById('assignInstructorModal');
  if (event.target == courseModal) {
    closeModal();
  }
  if (event.target == assignModal) {
    closeAssignModal();
  }
}
</script>
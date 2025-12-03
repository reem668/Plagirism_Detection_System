
<?php
/**
 * Protected Admin Course Management View
 * This file should only be accessed through admin.php
 */

// Security check - ensure this file is accessed through admin.php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted. Please access through admin.php');
}

// Additional authentication verification
require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Double-check authentication
if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    header("Location: /Plagirism_Detection_System/signup.php");
    exit();
}
?>

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

<!-- Panel Overlay -->
<div id="panelOverlay" class="panel-overlay" onclick="closeAllPanels()"></div>

<script src="/Plagirism_Detection_System/assets/js/admin_courses.js"></script>

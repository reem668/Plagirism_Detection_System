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
require_once dirname(__DIR__, 2) . '/Helpers/SessionManager.php';
require_once dirname(__DIR__, 2) . '/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__, 2) . '/Helpers/Csrf.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;



$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();

// Double-check authentication
if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    header('Location: ' . BASE_URL . '/signup');
    exit();
}
?>

<section class="course-management">
  <div class="page-header">
    <h2>📚 Course Management</h2>
    <button class="btn primary" id="toggleAddCourseBtn">
      <i class="fas fa-plus"></i> Create New Course
    </button>
  </div>

  <div id="courseNotification" class="notice" style="display:none;"></div>

  <!-- Add New Course Form -->
  <div class="add-user-form" id="addCourseFormContainer" style="display:none;">
    <h3><i class="fas fa-book"></i> Create New Course</h3>
    <form id="addCourseForm" class="add-form" onsubmit="addCourse(event)">
      <input type="hidden" id="addCourseCsrf" name="_csrf" value="<?= \Helpers\Csrf::token() ?>">

      <div class="form-group">
        <label for="newCourseName">Course Name *</label>
        <input type="text" id="newCourseName" name="name" class="form-input"
               placeholder="Enter course name" required minlength="3">
      </div>

      <div class="form-group">
        <label for="newCourseDescription">Description</label>
        <textarea id="newCourseDescription" name="description" class="form-input"
                  placeholder="Enter course description (optional)" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label for="newInstructorId">Instructor *</label>
        <select id="newInstructorId" name="instructor_id" class="form-input" required>
          <option value="">Select Instructor</option>
          <!-- Instructors will be loaded dynamically -->
        </select>
        <small class="form-hint" id="instructorLoadingHint" style="display:none;">
          Loading instructors...
        </small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn primary">
          <i class="fas fa-save"></i> Create Course
        </button>
        <button type="button" class="btn" id="cancelAddCourseBtn">
          Cancel
        </button>
      </div>
    </form>
  </div>

  <!-- Courses Table -->
  <div class="courses-table-container">
    <h3>All Courses</h3>
    <table class="courses-table">
      <thead>
        <tr>
          <th>Course Name</th>
          <th>Description</th>
          <th>Instructor</th>
          <th>Created</th>
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
            <label>Name:</label>
            <span id="panelCourseName"></span>
          </div>
          <div class="info-item">
            <label>Description:</label>
            <span id="panelCourseDescription"></span>
          </div>
          <div class="info-item">
            <label>Instructor:</label>
            <span id="panelInstructorName"></span>
            <small id="panelInstructorEmail"></small>
          </div>
          <div class="info-item">
            <label>Created:</label>
            <span id="panelCreatedAt"></span>
          </div>
        </div>
      </div>

      <div class="instructors-section">
        <h4>👨‍🏫 Instructor Information</h4>
        <p style="color:#a7b7d6;font-size:13px;">
          Each course is assigned to one instructor. To change the instructor, edit the course.
        </p>
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
        <input type="hidden" id="editCourseId" name="course_id">
        <input type="hidden" id="editCourseCsrf" name="_csrf" value="<?= \Helpers\Csrf::token() ?>">

        <div class="form-group">
          <label>Course Name *</label>
          <input type="text" id="editCourseName" name="name" class="form-input" required>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea id="editCourseDescription" name="description"
                    class="form-input" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label>Instructor *</label>
          <select id="editInstructorId" name="instructor_id" class="form-input" required>
            <option value="">Select Instructor</option>
            <!-- Instructors will be loaded dynamically -->
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

<!-- Panel Overlay -->
<div id="panelOverlay" class="panel-overlay" onclick="closeAllPanels()"></div>


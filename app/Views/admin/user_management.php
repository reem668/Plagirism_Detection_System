<?php
/**
 * Protected Admin User Management View - Enhanced with Admin Key Support
 * Features: Search, Pagination, AJAX CRUD operations, Admin Secret Key
 */

// Security check
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted. Please access through the admin panel.');
}

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../Helpers/Csrf.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Helpers\Csrf;

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    header('Location: ' . BASE_URL . '/signup');
    exit();
}
?>

<section class="user-management">
  <div class="page-header">
    <h2>👥 User Management</h2>
    <button class="btn primary" onclick="openAddUserModal()">
      <i class="fas fa-plus"></i> Add New User
    </button>
  </div>

  <!-- Notification Area -->
  <div id="notification" class="notice" style="display:none;"></div>

  <!-- Hidden CSRF Token -->
  <input type="hidden" id="csrfToken" value="<?= Csrf::token() ?>">

  <!-- Search and Filter Bar -->
  <div class="search-filter-container">
    <div class="search-box">
      <i class="fas fa-search"></i>
      <input type="text" 
             id="searchInput" 
             class="search-input" 
             placeholder="Search by name or email..." 
             onkeyup="handleSearch()">
    </div>
    
    <div class="filter-buttons">
      <button class="btn filter-btn active" onclick="filterByRole('all')" data-role="all">
        All Users
      </button>
      <button class="btn filter-btn" onclick="filterByRole('student')" data-role="student">
        <i class="fas fa-user-graduate"></i> Students
      </button>
      <button class="btn filter-btn" onclick="filterByRole('instructor')" data-role="instructor">
        <i class="fas fa-chalkboard-teacher"></i> Instructors
      </button>
      <button class="btn filter-btn" onclick="filterByRole('admin')" data-role="admin">
        <i class="fas fa-user-shield"></i> Admins
      </button>
    </div>
  </div>

  <!-- Users Table -->
  <div class="users-table-wrapper">
    <div id="loadingSpinner" class="loading-spinner" style="display:none;">
      <i class="fas fa-spinner fa-spin"></i> Loading users...
    </div>
    
    <div id="userCardsContainer" class="user-cards">
      <!-- Users will be loaded here via AJAX -->
    </div>
    
    <div id="emptyState" class="empty-state" style="display:none;">
      <i class="fas fa-users"></i>
      <p>No users found</p>
    </div>
  </div>

  <!-- Pagination -->
  <div id="paginationContainer" class="pagination-container" style="display:none;">
    <div class="pagination-info">
      Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalUsers">0</span> users
    </div>
    <div class="pagination-buttons" id="paginationButtons">
      <!-- Pagination buttons will be generated here -->
    </div>
  </div>
</section>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fas fa-user-plus"></i> Add New User</h3>
      <button class="close-btn" onclick="closeAddUserModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="addUserForm" onsubmit="submitAddUser(event)">
        <div class="form-group">
          <label for="addName">
            <i class="fas fa-user"></i> Full Name
          </label>
          <input type="text" 
                 id="addName" 
                 name="name" 
                 class="form-input" 
                 placeholder="Enter full name" 
                 required 
                 minlength="3">
        </div>

        <div class="form-group">
          <label for="addEmail">
            <i class="fas fa-envelope"></i> Email Address
          </label>
          <input type="email" 
                 id="addEmail" 
                 name="email" 
                 class="form-input" 
                 placeholder="Enter email address" 
                 required>
        </div>

        <div class="form-group">
          <label for="addPassword">
            <i class="fas fa-lock"></i> Password
          </label>
          <input type="password" 
                 id="addPassword" 
                 name="password" 
                 class="form-input" 
                 placeholder="Enter password (min 8 chars)" 
                 required 
                 minlength="8">
          <small class="form-hint">Must contain uppercase, number, and special character</small>
        </div>

        <div class="form-group">
          <label for="addRole">
            <i class="fas fa-user-tag"></i> Role
          </label>
          <select id="addRole" name="role" class="form-input" required onchange="toggleAdminKeyField('add')">
            <option value="">Select Role</option>
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="form-group" id="addAdminKeyGroup" style="display:none;">
          <label for="addAdminKey">
            <i class="fas fa-key"></i> Admin Secret Key
          </label>
          <input type="text" 
                 id="addAdminKey" 
                 name="admin_key" 
                 class="form-input" 
                 placeholder="Enter admin secret key">
          <small class="form-hint">Required for admin role. Keep this secure!</small>
        </div>

        <div class="modal-actions">
          <button type="submit" class="btn primary">
            <i class="fas fa-save"></i> Add User
          </button>
          <button type="button" class="btn" onclick="closeAddUserModal()">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fas fa-edit"></i> Edit User</h3>
      <button class="close-btn" onclick="closeEditUserModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="editUserForm" onsubmit="submitEditUser(event)">
        <input type="hidden" id="editUserId" name="userId">
        
        <div class="form-group">
          <label for="editName">
            <i class="fas fa-user"></i> Full Name
          </label>
          <input type="text" 
                 id="editName" 
                 name="name" 
                 class="form-input" 
                 required 
                 minlength="3">
        </div>

        <div class="form-group">
          <label for="editEmail">
            <i class="fas fa-envelope"></i> Email Address
          </label>
          <input type="email" 
                 id="editEmail" 
                 name="email" 
                 class="form-input" 
                 required>
        </div>

        <div class="form-group">
          <label for="editRole">
            <i class="fas fa-user-tag"></i> Role
          </label>
          <select id="editRole" name="role" class="form-input" required onchange="toggleAdminKeyField('edit')">
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="form-group" id="editAdminKeyGroup" style="display:none;">
          <label for="editAdminKey">
            <i class="fas fa-key"></i> Admin Secret Key
          </label>
          <input type="text" 
                 id="editAdminKey" 
                 name="admin_key" 
                 class="form-input" 
                 placeholder="Enter new admin secret key (leave blank to keep current)">
          <small class="form-hint">Leave blank to keep existing key</small>
        </div>

        <div class="form-group">
          <label for="editStatus">
            <i class="fas fa-toggle-on"></i> Status
          </label>
          <select id="editStatus" name="status" class="form-input" required>
            <option value="active">Active</option>
            <option value="banned">Banned</option>
          </select>
        </div>

        <div class="modal-actions">
          <button type="submit" class="btn primary">
            <i class="fas fa-save"></i> Save Changes
          </button>
          <button type="button" class="btn" onclick="closeEditUserModal()">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="modal">
  <div class="modal-content small">
    <div class="modal-header">
      <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
      <button class="close-btn" onclick="closeDeleteUserModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete this user?</p>
      <p class="warning-text">
        <strong>Warning:</strong> This action cannot be undone. All user data will be permanently deleted.
      </p>
      <input type="hidden" id="deleteUserId">
    </div>
    <div class="modal-actions">
      <button class="btn danger" onclick="confirmDeleteUser()">
        <i class="fas fa-trash"></i> Yes, Delete
      </button>
      <button class="btn" onclick="closeDeleteUserModal()">
        Cancel
      </button>
    </div>
  </div>
</div>

<script src="/Plagirism_Detection_System/assets/js/admin_users.js"></script>
<script>
function toggleAdminKeyField(mode) {
    const roleSelect = document.getElementById(mode + 'Role');
    const adminKeyGroup = document.getElementById(mode + 'AdminKeyGroup');
    const adminKeyInput = document.getElementById(mode + 'AdminKey');

    if (!roleSelect) return;

    if (roleSelect.value === 'admin') {
        adminKeyGroup.style.display = 'block';
        adminKeyInput.required = true;
    } else {
        adminKeyGroup.style.display = 'none';
        adminKeyInput.required = false;
        adminKeyInput.value = '';
    }
}

// Run on page load (important part)
document.addEventListener('DOMContentLoaded', function () {
    toggleAdminKeyField('add');   // if you have add form
    toggleAdminKeyField('edit');  // if you have edit form
});
</script>

<link rel="stylesheet" href="assets/css/admin.css" />
<section class="user-management">
  <h2>User Management 👥</h2>

  <div id="notification" class="notice" style="display:none;"></div>

  <div class="filter-row">
    <button class="btn active" onclick="filterUsers('all')">All</button>
    <button class="btn" onclick="filterUsers('student')">Students</button>
    <button class="btn" onclick="filterUsers('instructor')">Instructors</button>
    <button class="btn" onclick="filterUsers('admin')">Admins</button>
  </div>

  <div class="add-user-form">
    <h3>Add New User ➕</h3>
    <form id="addUserForm" class="add-form" onsubmit="addUser(event)">
      <input type="text" id="newName" placeholder="Full Name" required>
      <input type="email" id="newEmail" placeholder="Email Address" required>
      <select id="newRole" required>
        <option value="">Select Role</option>
        <option value="student">Student</option>
        <option value="instructor">Instructor</option>
        <option value="admin">Admin</option>
      </select>
      <button type="submit" class="btn primary">Add User</button>
    </form>
  </div>

  <div class="user-cards" id="userCardsContainer">
    <!-- Users will be loaded here -->
  </div>
</section>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal" style="display: none;">
  <div class="modal-content small">
    <div class="modal-header">
      <h3>✏️ Edit User</h3>
      <button class="close-btn" onclick="closeEditModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="editUserForm" onsubmit="saveUserEdit(event)">
        <input type="hidden" id="edit_user_id">
        
        <label>Full Name</label>
        <input type="text" id="edit_name" class="edit-input" required>

        <label>Email</label>
        <input type="email" id="edit_email" class="edit-input" required>

        <label>Role</label>
        <select id="edit_role" class="edit-input" required>
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
          <option value="admin">Admin</option>
        </select>

        <label>Status</label>
        <select id="edit_status" class="edit-input" required>
          <option value="active">Active</option>
          <option value="banned">Banned</option>
        </select>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
          <button type="submit" class="btn primary">💾 Save Changes</button>
          <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="assets/js/admin_users.js"></script>
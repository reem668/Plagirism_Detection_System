
// Initialize with hardcoded data
let users = [
  { id: 1, name: 'Ahmed Hassan', email: 'ahmed@student.edu', role: 'student', status: 'active' },
  { id: 2, name: 'Fatma Ali', email: 'fatma@student.edu', role: 'student', status: 'active' },
  { id: 3, name: 'Mohamed Omar', email: 'mohamed@student.edu', role: 'student', status: 'banned' },
  { id: 4, name: 'Dr. Ahmed Mohamed', email: 'ahmed.m@university.edu', role: 'instructor', status: 'active' },
  { id: 5, name: 'Prof. Sara Ali', email: 'sara.ali@university.edu', role: 'instructor', status: 'active' },
  { id: 6, name: 'Admin User', email: 'admin@university.edu', role: 'admin', status: 'active' },
];

let nextUserId = 7;
let currentFilter = 'all';

// Load from localStorage if exists
function loadUsers() {
  const saved = localStorage.getItem('users');
  if (saved) {
    const data = JSON.parse(saved);
    users = data.users;
    nextUserId = data.nextUserId;
  }
}

// Save to localStorage
function saveUsers() {
  localStorage.setItem('users', JSON.stringify({
    users: users,
    nextUserId: nextUserId
  }));
  
  // Update dashboard if exists
  updateDashboard();
}

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
  loadUsers();
  renderUsers();
});

function renderUsers() {
  const container = document.getElementById('userCardsContainer');
  let filteredUsers = currentFilter === 'all' 
    ? users 
    : users.filter(u => u.role === currentFilter);

  if (filteredUsers.length === 0) {
    container.innerHTML = '<p style="color:#a7b7d6;padding:20px;text-align:center;">No users found.</p>';
    return;
  }

  container.innerHTML = filteredUsers.map(user => `
    <div class="user-card" data-role="${user.role}" id="user-card-${user.id}">
      <div class="user-top">
        <div class="avatar">${user.name.charAt(0).toUpperCase()}</div>
        <div>
          <div class="user-name">${user.name}</div>
          <div class="user-email">${user.email}</div>
        </div>
      </div>

      <div class="user-info">
        <div><strong>Role:</strong> <span>${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></div>
        <div><strong>Status:</strong> <span class="status ${user.status}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></div>
      </div>

      <div class="user-actions">
        <button class="btn small ${user.status === 'active' ? 'danger' : 'success'}" onclick="toggleUserStatus(${user.id})">
          ${user.status === 'active' ? 'Ban' : 'Unban'}
        </button>

        <button class="btn small" onclick="openEditModal(${user.id})">✏️ Edit</button>

        <button class="btn small danger" onclick="deleteUser(${user.id})">🗑️ Delete</button>
      </div>
    </div>
  `).join('');
}

function filterUsers(role) {
  currentFilter = role;
  
  // Update active button
  document.querySelectorAll('.filter-row .btn').forEach((btn, index) => {
    btn.classList.remove('active');
    if ((role === 'all' && index === 0) ||
        (role === 'student' && index === 1) ||
        (role === 'instructor' && index === 2) ||
        (role === 'admin' && index === 3)) {
      btn.classList.add('active');
    }
  });
  
  renderUsers();
}

function addUser(event) {
  event.preventDefault();
  
  const name = document.getElementById('newName').value.trim();
  const email = document.getElementById('newEmail').value.trim();
  const role = document.getElementById('newRole').value;

  // Check for duplicate email
  if (users.find(u => u.email.toLowerCase() === email.toLowerCase())) {
    showNotification('⚠️ Email already exists!', 'error');
    return;
  }

  const newUser = {
    id: nextUserId++,
    name: name,
    email: email,
    role: role,
    status: 'active'
  };

  users.push(newUser);
  saveUsers();

  document.getElementById('addUserForm').reset();
  renderUsers();
  showNotification('✅ User added successfully!', 'success');
}

function openEditModal(userId) {
  const user = users.find(u => u.id === userId);
  if (!user) return;

  document.getElementById('edit_user_id').value = user.id;
  document.getElementById('edit_name').value = user.name;
  document.getElementById('edit_email').value = user.email;
  document.getElementById('edit_role').value = user.role;
  document.getElementById('edit_status').value = user.status;

  document.getElementById('editUserModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editUserModal').style.display = 'none';
}

function saveUserEdit(event) {
  event.preventDefault();

  const userId = parseInt(document.getElementById('edit_user_id').value);
  const name = document.getElementById('edit_name').value.trim();
  const email = document.getElementById('edit_email').value.trim();
  const role = document.getElementById('edit_role').value;
  const status = document.getElementById('edit_status').value;

  // Check for duplicate email (excluding current user)
  if (users.find(u => u.email.toLowerCase() === email.toLowerCase() && u.id !== userId)) {
    showNotification('⚠️ Email already exists!', 'error');
    return;
  }

  const user = users.find(u => u.id === userId);
  if (user) {
    user.name = name;
    user.email = email;
    user.role = role;
    user.status = status;
    
    saveUsers();
    renderUsers();
    closeEditModal();
    showNotification('✅ User updated successfully!', 'success');
  }
}

function toggleUserStatus(userId) {
  const user = users.find(u => u.id === userId);
  if (user) {
    user.status = user.status === 'active' ? 'banned' : 'active';
    saveUsers();
    renderUsers();
    showNotification(`✅ User ${user.status === 'active' ? 'unbanned' : 'banned'} successfully!`, 'success');
  }
}

function deleteUser(userId) {
  if (!confirm('Are you sure you want to delete this user?')) return;

  const index = users.findIndex(u => u.id === userId);
  if (index !== -1) {
    users.splice(index, 1);
    saveUsers();
    renderUsers();
    showNotification('🗑️ User deleted successfully!', 'success');
  }
}

function showNotification(message, type) {
  const notification = document.getElementById('notification');
  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
}

function updateDashboard() {
  const totalUsers = document.getElementById('totalUsers');
  if (totalUsers) {
    totalUsers.textContent = users.length;
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('editUserModal');
  if (event.target == modal) {
    closeEditModal();
  }
}

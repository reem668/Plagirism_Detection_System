/**
 * Admin Users Management - JavaScript
 * Handles AJAX operations, search, pagination, and UI interactions
 */

// Global state
let currentPage = 1;
let currentRole = 'all';
let currentSearch = '';
let searchTimeout = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

/**
 * Load users with current filters
 */
function loadUsers() {
    const container = document.getElementById('userCardsContainer');
    const spinner = document.getElementById('loadingSpinner');
    const emptyState = document.getElementById('emptyState');
    
    // Show loading
    spinner.style.display = 'block';
    container.style.display = 'none';
    emptyState.style.display = 'none';
    
    // Build query params
    const params = new URLSearchParams({
        page: currentPage,
        role: currentRole,
        search: currentSearch
    });
    
    fetch(`/Plagirism_Detection_System/ajax/get_users.php?${params}`, {
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => response.json())
        .then(data => {
            spinner.style.display = 'none';
            
            if (data.success) {
                if (data.users.length === 0) {
                    emptyState.style.display = 'block';
                } else {
                    container.style.display = 'flex';
                    renderUsers(data.users);
                    renderPagination(data.pagination);
                }
            } else {
                showNotification(data.message || 'Failed to load users', 'error');
                emptyState.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            spinner.style.display = 'none';
            showNotification('Failed to load users', 'error');
            emptyState.style.display = 'block';
        });
}

/**
 * Render users in card format
 */
function renderUsers(users) {
    const container = document.getElementById('userCardsContainer');
    container.innerHTML = '';
    
    users.forEach(user => {
        const card = createUserCard(user);
        container.appendChild(card);
    });
}

/**
 * Create a user card element
 */
function createUserCard(user) {
    const card = document.createElement('div');
    card.className = 'user-card';
    card.setAttribute('data-user-id', user.id);
    
    // Get initials for avatar
    const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    
    // Role icon
    const roleIcons = {
        admin: 'fas fa-user-shield',
        instructor: 'fas fa-chalkboard-teacher',
        student: 'fas fa-user-graduate'
    };
    
    // Status badge
    const statusClass = user.status === 'active' ? 'status-active' : 'status-banned';
    const statusText = user.status === 'active' ? 'Active' : 'Banned';
    
    card.innerHTML = `
        <div class="user-top">
            <div class="avatar">${initials}</div>
            <div class="user-details">
                <div class="user-name">${escapeHtml(user.name)}</div>
                <div class="user-email">${escapeHtml(user.email)}</div>
            </div>
        </div>
        
        <div class="user-info">
            <span class="info-item">
                <i class="${roleIcons[user.role] || 'fas fa-user'}"></i>
                ${capitalizeFirst(user.role)}
            </span>
            <span class="info-item ${statusClass}">
                <i class="fas fa-circle"></i>
                ${statusText}
            </span>
        </div>
        
        <div class="user-meta">
            <small>
                <i class="fas fa-calendar-alt"></i>
                Joined ${formatDate(user.created_at)}
            </small>
        </div>
        
        <div class="user-actions">
            <button class="btn small primary" onclick="openEditUserModal(${user.id})" title="Edit User">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn small danger" onclick="openDeleteUserModal(${user.id})" title="Delete User">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    return card;
}

/**
 * Render pagination
 */
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const buttonsContainer = document.getElementById('paginationButtons');
    
    if (pagination.totalPages <= 1) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    
    // Update info
    const showingFrom = (pagination.currentPage - 1) * pagination.perPage + 1;
    const showingTo = Math.min(pagination.currentPage * pagination.perPage, pagination.totalUsers);
    
    document.getElementById('showingFrom').textContent = showingFrom;
    document.getElementById('showingTo').textContent = showingTo;
    document.getElementById('totalUsers').textContent = pagination.totalUsers;
    
    // Generate buttons
    buttonsContainer.innerHTML = '';
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.currentPage === 1;
    prevBtn.onclick = () => goToPage(pagination.currentPage - 1);
    buttonsContainer.appendChild(prevBtn);
    
    // Page numbers
    const maxButtons = 5;
    let startPage = Math.max(1, pagination.currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(pagination.totalPages, startPage + maxButtons - 1);
    
    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'pagination-btn' + (i === pagination.currentPage ? ' active' : '');
        pageBtn.textContent = i;
        pageBtn.onclick = () => goToPage(i);
        buttonsContainer.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.currentPage === pagination.totalPages;
    nextBtn.onclick = () => goToPage(pagination.currentPage + 1);
    buttonsContainer.appendChild(nextBtn);
}

/**
 * Go to specific page
 */
function goToPage(page) {
    currentPage = page;
    loadUsers();
}

/**
 * Filter by role
 */
function filterByRole(role) {
    currentRole = role;
    currentPage = 1;
    
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-role') === role) {
            btn.classList.add('active');
        }
    });
    
    loadUsers();
}

/**
 * Handle search with debounce
 */
function handleSearch() {
    clearTimeout(searchTimeout);
    
    searchTimeout = setTimeout(() => {
        currentSearch = document.getElementById('searchInput').value.trim();
        currentPage = 1;
        loadUsers();
    }, 500);
}

/**
 * Open Add User Modal
 */
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
    document.getElementById('addUserForm').reset();
}

/**
 * Close Add User Modal
 */
function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}

/**
 * Submit Add User Form
 */
function submitAddUser(event) {
    event.preventDefault();
    
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    formData.append('_csrf', document.getElementById('csrfToken').value);
    
    // Disable submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    fetch('/Plagirism_Detection_System/ajax/add_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeAddUserModal();
            loadUsers();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error adding user:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        showNotification('Failed to add user', 'error');
    });
}

/**
 * Open Edit User Modal
 */
function openEditUserModal(userId) {
    // Find user data from current cards
    const card = document.querySelector(`[data-user-id="${userId}"]`);
    if (!card) return;
    
    // Get user data by making AJAX call
    fetch(`/Plagirism_Detection_System/ajax/get_users.php?page=1&role=all`, {
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.users.find(u => u.id == userId);
                if (user) {
                    document.getElementById('editUserId').value = user.id;
                    document.getElementById('editName').value = user.name;
                    document.getElementById('editEmail').value = user.email;
                    document.getElementById('editRole').value = user.role;
                    document.getElementById('editStatus').value = user.status;
                    document.getElementById('editUserModal').style.display = 'flex';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            showNotification('Failed to load user data', 'error');
        });
}

/**
 * Close Edit User Modal
 */
function closeEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

/**
 * Submit Edit User Form
 */
function submitEditUser(event) {
    event.preventDefault();
    
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    formData.append('_csrf', document.getElementById('csrfToken').value);
    
    // Disable submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('/Plagirism_Detection_System/ajax/edit_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeEditUserModal();
            loadUsers();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error editing user:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        showNotification('Failed to update user', 'error');
    });
}

/**
 * Open Delete User Modal
 */
function openDeleteUserModal(userId) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserModal').style.display = 'flex';
}

/**
 * Close Delete User Modal
 */
function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').style.display = 'none';
}

/**
 * Confirm Delete User
 */
function confirmDeleteUser() {
    const userId = document.getElementById('deleteUserId').value;
    const csrfToken = document.getElementById('csrfToken').value;
    
    const formData = new FormData();
    formData.append('userId', userId);
    formData.append('_csrf', csrfToken);
    
    fetch('/Plagirism_Detection_System/ajax/delete_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeDeleteUserModal();
            loadUsers();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        showNotification('Failed to delete user', 'error');
    });
}

/**
 * Show notification
 */
function showNotification(message, type) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = 'notice ' + type;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

/**
 * Utility: Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Utility: Capitalize first letter
 */
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Utility: Format date
 */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}
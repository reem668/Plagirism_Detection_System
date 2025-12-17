/**
 * Admin Courses Management - AJAX-based dynamic course management
 * All operations use AJAX with no hardcoded values
 */

const BASE_URL = '/Plagirism_Detection_System';
let currentCourseId = null;
let instructors = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
    loadInstructors();
    
    // Ensure form is hidden initially
    const formContainer = document.getElementById('addCourseFormContainer');
    if (formContainer) {
        formContainer.style.display = 'none';
    }
    
    // Attach event listener to toggle button
    const toggleBtn = document.getElementById('toggleAddCourseBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleAddCourseForm);
    }
    
    // Attach event listener to cancel button
    const cancelBtn = document.getElementById('cancelAddCourseBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', toggleAddCourseForm);
    }
});

/**
 * Load all courses from server
 */
function loadCourses() {
    fetch(`${BASE_URL}/ajax/get_courses.php?page=1&limit=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCourses(data.data || []);
            } else {
                showCourseNotification('Error loading courses: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCourseNotification('Failed to load courses', 'error');
        });
}

/**
 * Load all instructors from server
 */
function loadInstructors() {
    const loadingHint = document.getElementById('instructorLoadingHint');
    if (loadingHint) {
        loadingHint.style.display = 'block';
    }
    
    fetch(`${BASE_URL}/ajax/get_instructors.php`)
        .then(response => response.json())
        .then(data => {
            if (loadingHint) {
                loadingHint.style.display = 'none';
            }
            
            if (data.success && data.data) {
                instructors = data.data;
                populateInstructorDropdowns();
            } else {
                showCourseNotification('Error loading instructors: ' + (data.message || 'Unknown error'), 'error');
                populateInstructorDropdowns(); // Still populate with empty message
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (loadingHint) {
                loadingHint.style.display = 'none';
            }
            showCourseNotification('Failed to load instructors', 'error');
            populateInstructorDropdowns(); // Still populate with empty message
        });
}

/**
 * Populate instructor dropdowns in forms
 */
function populateInstructorDropdowns() {
    const addDropdown = document.getElementById('newInstructorId');
    const editDropdown = document.getElementById('editInstructorId');
    const loadingHint = document.getElementById('instructorLoadingHint');
    
    if (loadingHint) {
        loadingHint.style.display = 'none';
    }
    
    if (!instructors || instructors.length === 0) {
        const options = '<option value="">No instructors available</option>';
        if (addDropdown) addDropdown.innerHTML = options;
        if (editDropdown) editDropdown.innerHTML = options;
        return;
    }
    
    const options = instructors.map(inst => 
        `<option value="${inst.id}">${inst.name} (${inst.email})</option>`
    ).join('');
    
    if (addDropdown) {
        addDropdown.innerHTML = '<option value="">Select Instructor</option>' + options;
    }
    if (editDropdown) {
        editDropdown.innerHTML = '<option value="">Select Instructor</option>' + options;
    }
}

/**
 * Toggle add course form visibility
 * Made globally available for both event listeners and inline handlers
 */
function toggleAddCourseForm() {
    const formContainer = document.getElementById('addCourseFormContainer');
    const toggleBtn = document.getElementById('toggleAddCourseBtn');
    
    if (!formContainer) {
        console.error('Form container not found');
        return;
    }
    
    if (formContainer.style.display === 'none' || !formContainer.style.display) {
        formContainer.style.display = 'block';
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        }
        // Ensure instructors are loaded
        if (instructors.length === 0) {
            loadInstructors();
        }
    } else {
        formContainer.style.display = 'none';
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="fas fa-plus"></i> Create New Course';
        }
        // Reset form
        const form = document.getElementById('addCourseForm');
        if (form) {
            form.reset();
        }
    }
}

/**
 * Render courses table
 */
function renderCourses(courses) {
    const tbody = document.getElementById('coursesTableBody');
    
    if (!courses || courses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#a7b7d6;padding:20px;">No courses yet. Create one above!</td></tr>';
        return;
    }
    
    tbody.innerHTML = courses.map(course => {
        const instructorName = course.instructor_name || 'Unassigned';
        const instructorEmail = course.instructor_email || '';
        const description = course.description ? 
            (course.description.length > 50 ? course.description.substring(0, 50) + '...' : course.description) : 
            'No description';
        const createdDate = course.created_at ? 
            new Date(course.created_at).toLocaleDateString() : 
            'N/A';
        
        return `
            <tr>
                <td><strong>${escapeHtml(course.name)}</strong></td>
                <td>${escapeHtml(description)}</td>
                <td>${escapeHtml(instructorName)}${instructorEmail ? '<br><small>' + escapeHtml(instructorEmail) + '</small>' : ''}</td>
                <td>${createdDate}</td>
                <td>
                    <button class="btn small" onclick="viewCourseDetails(${course.id})">👁️ View</button>
                    <button class="btn small" onclick="openEditCoursePanel(${course.id})">✏️ Edit</button>
                    <button class="btn small danger" onclick="deleteCourse(${course.id})">🗑️ Delete</button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Add new course
 */
function addCourse(event) {
    event.preventDefault();
    
    const form = document.getElementById('addCourseForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';
    
    fetch(`${BASE_URL}/ajax/add_course.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        
        if (data.success) {
            form.reset();
            loadCourses();
            toggleAddCourseForm(); // Hide form after successful creation
            showCourseNotification('✅ Course created successfully!', 'success');
        } else {
            showCourseNotification('⚠️ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        showCourseNotification('Failed to create course', 'error');
    });
}

/**
 * View course details
 */
function viewCourseDetails(courseId) {
    currentCourseId = courseId;
    
    fetch(`${BASE_URL}/ajax/get_course.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const course = data.data;
                
                document.getElementById('panelCourseName').textContent = course.name || 'N/A';
                document.getElementById('panelCourseDescription').textContent = course.description || 'No description';
                document.getElementById('panelInstructorName').textContent = course.instructor_name || 'Unassigned';
                document.getElementById('panelInstructorEmail').textContent = course.instructor_email || '';
                document.getElementById('panelCreatedAt').textContent = course.created_at ? 
                    new Date(course.created_at).toLocaleString() : 'N/A';
                
                openPanel('courseDetailsPanel');
            } else {
                showCourseNotification('Error loading course details: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCourseNotification('Failed to load course details', 'error');
        });
}

/**
 * Close course details panel
 */
function closeCoursePanel() {
    closePanel('courseDetailsPanel');
    currentCourseId = null;
}

/**
 * Open edit course panel
 */
function openEditCoursePanel(courseId) {
    currentCourseId = courseId;
    
    fetch(`${BASE_URL}/ajax/get_course.php?id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const course = data.data;
                
                document.getElementById('editCourseId').value = course.id;
                document.getElementById('editCourseName').value = course.name || '';
                document.getElementById('editCourseDescription').value = course.description || '';
                document.getElementById('editInstructorId').value = course.instructor_id || '';
                
                openPanel('editCoursePanel');
            } else {
                showCourseNotification('Error loading course: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCourseNotification('Failed to load course', 'error');
        });
}

/**
 * Close edit course panel
 */
function closeEditCoursePanel() {
    closePanel('editCoursePanel');
    currentCourseId = null;
}

/**
 * Save course edit
 */
function saveCourseEdit(event) {
    event.preventDefault();
    
    const form = document.getElementById('editCourseForm');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    fetch(`${BASE_URL}/ajax/edit_course.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        
        if (data.success) {
            loadCourses();
            closeEditCoursePanel();
            showCourseNotification('✅ Course updated successfully!', 'success');
        } else {
            showCourseNotification('⚠️ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        showCourseNotification('Failed to update course', 'error');
    });
}

/**
 * Delete course
 */
function deleteCourse(courseId) {
    if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        return;
    }
    
    // Get CSRF token from the page (injected by admin.php)
    const csrfInput = document.querySelector('input[name="_csrf"]');
    const csrf = csrfInput ? csrfInput.value : '';
    
    const formData = new FormData();
    formData.append('_csrf', csrf);
    formData.append('course_id', courseId);
    
    fetch(`${BASE_URL}/ajax/delete_course.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCourses();
            showCourseNotification('🗑️ Course deleted successfully!', 'success');
        } else {
            showCourseNotification('⚠️ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCourseNotification('Failed to delete course', 'error');
    });
}


/**
 * Show notification
 */
function showCourseNotification(message, type) {
    const notification = document.getElementById('courseNotification');
    if (!notification) return;
    
    notification.textContent = message;
    notification.className = 'notice ' + type;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

/**
 * Panel helper functions
 */
function openPanel(panelId) {
    const panel = document.getElementById(panelId);
    const overlay = document.getElementById('panelOverlay');
    
    if (panel) panel.classList.add('open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePanel(panelId) {
    const panel = document.getElementById(panelId);
    const overlay = document.getElementById('panelOverlay');
    
    if (panel) panel.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function closeAllPanels() {
    document.querySelectorAll('.side-panel').forEach(panel => {
        panel.classList.remove('open');
    });
    const overlay = document.getElementById('panelOverlay');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Make functions globally available for inline onclick handlers
// These are attached immediately so they're available when the HTML renders
window.toggleAddCourseForm = toggleAddCourseForm;
window.addCourse = addCourse;
window.viewCourseDetails = viewCourseDetails;
window.closeCoursePanel = closeCoursePanel;
window.openEditCoursePanel = openEditCoursePanel;
window.closeEditCoursePanel = closeEditCoursePanel;
window.saveCourseEdit = saveCourseEdit;
window.deleteCourse = deleteCourse;
window.closeAllPanels = closeAllPanels;

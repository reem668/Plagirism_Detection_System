// admin_courses.js – DB-backed version

let currentCourseId = null;

// -----------------------------
// Helpers: AJAX + CSRF
// -----------------------------
function getCsrfTokenAdd() {
  const el = document.getElementById('addCourseCsrf');
  return el ? el.value : '';
}

function getCsrfTokenEdit() {
  const el = document.getElementById('editCourseCsrf');
  return el ? el.value : '';
}

async function ajaxGet(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  return res.json();
}

async function ajaxPost(url, data) {
  const formData = new FormData();
  Object.entries(data).forEach(([k, v]) => formData.append(k, v));

  const res = await fetch(url, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  });

  const text = await res.text();
  console.log('RAW RESPONSE from', url, 'status', res.status, '=>', text);

  if (!text.trim()) {
    throw new Error('Empty response from server');
  }

  let json;
  try {
    json = JSON.parse(text);
  } catch (e) {
    console.error('JSON parse failed. Text was:', text);
    throw e;
  }

  if (!res.ok || !json.success) {
    throw new Error(json.message || 'Request failed');
  }

  return json;
}

// Centralized URLs (match your PHP file names)
const BASE = '/Plagirism_Detection_System/ajax';
const URL_LIST_COURSES    = BASE + '/get_courses.php';
const URL_GET_COURSE      = BASE + '/get_course.php';
const URL_ADD_COURSE      = BASE + '/add_course.php';
const URL_EDIT_COURSE     = BASE + '/edit_course.php';
const URL_DELETE_COURSE   = BASE + '/delete_course.php';
const URL_GET_INSTRUCTORS = BASE + '/get_instructors.php';

// -----------------------------
// Initial load
// -----------------------------
document.addEventListener('DOMContentLoaded', () => {
  loadCoursesFromServer();
  loadInstructorsForAdd();
  setupButtons();
});

function setupButtons() {
  const toggleBtn = document.getElementById('toggleAddCourseBtn');
  const cancelAddBtn = document.getElementById('cancelAddCourseBtn');

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      const container = document.getElementById('addCourseFormContainer');
      if (container) {
        container.style.display =
          container.style.display === 'none' || !container.style.display
            ? 'block'
            : 'none';
      }
    });
  }

  if (cancelAddBtn) {
    cancelAddBtn.addEventListener('click', () => {
      const container = document.getElementById('addCourseFormContainer');
      if (container) container.style.display = 'none';
    });
  }
}

// -----------------------------
// Load courses (list)
// -----------------------------
async function loadCoursesFromServer() {
  try {
    const result = await ajaxGet(URL_LIST_COURSES);
    if (!result.success) {
      console.error('Failed to load courses:', result.message);
      renderCourses([]);
      showCourseNotification('Failed to load courses', 'error');
      return;
    }

    renderCourses(result.data || []);
    const totalCourses = document.getElementById('totalCourses');
    if (totalCourses) {
      totalCourses.textContent = result.total ?? (result.data?.length || 0);
    }
  } catch (e) {
    console.error('Error loading courses:', e);
    renderCourses([]);
    showCourseNotification('Error loading courses', 'error');
  }
}

function renderCourses(courses) {
  const tbody = document.getElementById('coursesTableBody');
  if (!tbody) return;

  if (!courses || courses.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="5" style="text-align:center;color:#a7b7d6;padding:20px;">No courses yet. Create one above!</td></tr>';
    return;
  }

  tbody.innerHTML = courses
    .map(course => {
      const description =
        course.description && course.description.trim() !== ''
          ? course.description
          : '<span style="color:#a7b7d6;">No description</span>';

      const instructor =
        course.instructor_name
          ? `${course.instructor_name}<br><small>${course.instructor_email || ''}</small>`
          : '<span style="color:#a7b7d6;">No instructor</span>';

      const created =
        course.created_at
          ? new Date(course.created_at).toLocaleDateString()
          : '-';

      return `
        <tr>
          <td>${course.name}</td>
          <td>${description}</td>
          <td>${instructor}</td>
          <td>${created}</td>
          <td>
            <button class="btn small" onclick="viewCourseDetails(${course.id})">👁️ View</button>
            <button class="btn small" onclick="openEditCoursePanel(${course.id})">✏️ Edit</button>
            <button class="btn small danger" onclick="deleteCourse(${course.id})">🗑️ Delete</button>
          </td>
        </tr>
      `;
    })
    .join('');
}

// -----------------------------
// Add new course
// -----------------------------
async function addCourse(event) {
  event.preventDefault();

  const nameEl = document.getElementById('newCourseName');
  const descEl = document.getElementById('newCourseDescription');
  const instructorEl = document.getElementById('newInstructorId');

  const name = nameEl.value.trim();
  const description = descEl.value.trim();
  const instructorId = instructorEl.value;

  if (!name || !instructorId) {
    showCourseNotification('Name and instructor are required', 'error');
    return;
  }

  try {
    const result = await ajaxPost(URL_ADD_COURSE, {
      _csrf: getCsrfTokenAdd(),
      name,
      description,
      instructor_id: instructorId
    });

    if (!result.success) {
      showCourseNotification(result.message || 'Failed to create course', 'error');
      return;
    }

    document.getElementById('addCourseForm').reset();
    document.getElementById('addCourseFormContainer').style.display = 'none';
    showCourseNotification('✅ Course created successfully!', 'success');
    loadCoursesFromServer();
  } catch (e) {
    console.error('Error adding course:', e);
    showCourseNotification('Error creating course', 'error');
  }
}

// -----------------------------
// View single course (details panel)
// -----------------------------
async function viewCourseDetails(courseId) {
  currentCourseId = courseId;

  try {
    const result = await ajaxGet(`${URL_GET_COURSE}?id=${encodeURIComponent(courseId)}`);

    if (!result.success || !result.data) {
      showCourseNotification(result.message || 'Course not found', 'error');
      return;
    }

    const course = result.data;

    document.getElementById('panelCourseName').textContent = course.name;
    document.getElementById('panelCourseDescription').textContent =
      course.description || 'No description';
    document.getElementById('panelInstructorName').textContent =
      course.instructor_name || 'No instructor';
    document.getElementById('panelInstructorEmail').textContent =
      course.instructor_email || '';
    document.getElementById('panelCreatedAt').textContent =
      course.created_at
        ? new Date(course.created_at).toLocaleString()
        : '-';

    openPanel('courseDetailsPanel');
  } catch (e) {
    console.error('Error loading course:', e);
    showCourseNotification('Error loading course details', 'error');
  }
}

function closeCoursePanel() {
  closePanel('courseDetailsPanel');
  currentCourseId = null;
}

// -----------------------------
// Edit course
// -----------------------------
async function openEditCoursePanel(courseId) {
  currentCourseId = courseId;

  try {
    const result = await ajaxGet(`${URL_GET_COURSE}?id=${encodeURIComponent(courseId)}`);

    if (!result.success || !result.data) {
      showCourseNotification(result.message || 'Course not found', 'error');
      return;
    }

    const course = result.data;

    document.getElementById('editCourseId').value = course.id;
    document.getElementById('editCourseName').value = course.name;
    document.getElementById('editCourseDescription').value =
      course.description || '';

    await loadInstructorsForEdit(course.instructor_id);

    openPanel('editCoursePanel');
  } catch (e) {
    console.error('Error opening edit panel:', e);
    showCourseNotification('Error loading course for edit', 'error');
  }
}

function closeEditCoursePanel() {
  closePanel('editCoursePanel');
  currentCourseId = null;
}

async function saveCourseEdit(event) {
  event.preventDefault();

  const id = document.getElementById('editCourseId').value;
  const name = document.getElementById('editCourseName').value.trim();
  const description = document.getElementById('editCourseDescription').value.trim();
  const instructorId = document.getElementById('editInstructorId').value;

  if (!id || !name || !instructorId) {
    showCourseNotification('Course ID, name and instructor are required', 'error');
    return;
  }

  try {
    const result = await ajaxPost(URL_EDIT_COURSE, {
      _csrf: getCsrfTokenEdit(),
      course_id: id,
      name,
      description,
      instructor_id: instructorId
    });

    if (!result.success) {
      showCourseNotification(result.message || 'Failed to update course', 'error');
      return;
    }

    closeEditCoursePanel();
    showCourseNotification('✅ Course updated successfully!', 'success');
    loadCoursesFromServer();
  } catch (e) {
    console.error('Error updating course:', e);
    showCourseNotification('Error updating course', 'error');
  }
}

// -----------------------------
// Delete course
// -----------------------------
async function deleteCourse(courseId) {
  if (!confirm('Are you sure you want to delete this course?')) return;

  try {
    const result = await ajaxPost(URL_DELETE_COURSE, {
      _csrf: getCsrfTokenEdit() || getCsrfTokenAdd(),
      course_id: courseId
    });

    if (!result.success) {
      showCourseNotification(result.message || 'Failed to delete course', 'error');
      return;
    }

    showCourseNotification('🗑️ Course deleted successfully!', 'success');
    loadCoursesFromServer();
  } catch (e) {
    console.error('Error deleting course:', e);
    showCourseNotification('Error deleting course', 'error');
  }
}

// -----------------------------
// Load instructors for dropdowns
// -----------------------------
async function loadInstructorsForAdd() {
  const select = document.getElementById('newInstructorId');
  if (!select) return;

  const hint = document.getElementById('instructorLoadingHint');
  if (hint) hint.style.display = 'block';

  try {
    const result = await ajaxGet(URL_GET_INSTRUCTORS);

    if (!result.success || !Array.isArray(result.data)) {
      select.innerHTML = '<option value="">No instructors found</option>';
      if (hint) hint.style.display = 'none';
      return;
    }

    select.innerHTML =
      '<option value="">Select Instructor</option>' +
      result.data
        .map(inst => `<option value="${inst.id}">${inst.name} (${inst.email})</option>`)
        .join('');

    if (hint) hint.style.display = 'none';
  } catch (e) {
    console.error('Error loading instructors:', e);
    select.innerHTML = '<option value="">Error loading instructors</option>';
    if (hint) hint.style.display = 'none';
  }
}

async function loadInstructorsForEdit(selectedId) {
  const select = document.getElementById('editInstructorId');
  if (!select) return;

  try {
    const result = await ajaxGet(URL_GET_INSTRUCTORS);

    if (!result.success || !Array.isArray(result.data)) {
      select.innerHTML = '<option value="">No instructors found</option>';
      return;
    }

    select.innerHTML =
      '<option value="">Select Instructor</option>' +
      result.data
        .map(
          inst =>
            `<option value="${inst.id}" ${
              inst.id == selectedId ? 'selected' : ''
            }>${inst.name} (${inst.email})</option>`
        )
        .join('');
  } catch (e) {
    console.error('Error loading instructors for edit:', e);
    select.innerHTML = '<option value="">Error loading instructors</option>';
  }
}

// -----------------------------
// Notifications & panel helpers
// -----------------------------
function showCourseNotification(message, type) {
  const notification = document.getElementById('courseNotification');
  if (!notification) return;

  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
}

function openPanel(panelId) {
  const panel = document.getElementById(panelId);
  const overlay = document.getElementById('panelOverlay');
  if (!panel || !overlay) return;

  panel.classList.add('open');
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closePanel(panelId) {
  const panel = document.getElementById(panelId);
  const overlay = document.getElementById('panelOverlay');
  if (!panel || !overlay) return;

  panel.classList.remove('open');
  overlay.classList.remove('active');
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

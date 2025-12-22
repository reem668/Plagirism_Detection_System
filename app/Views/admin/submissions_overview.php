<?php
/**
 * Protected Admin Submissions Overview View
 * This file should only be accessed through admin.php
 */

// Security check - ensure this file is accessed through admin.php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted. Please access through admin.php');
}

// Core auth helpers
require_once APP_ROOT . '/app/Helpers/SessionManager.php';
require_once APP_ROOT . '/app/Middleware/AuthMiddleware.php';

// Controller + CSRF + DB
require_once APP_ROOT . '/app/Controllers/AdminSubmissionController.php';
require_once APP_ROOT . '/app/Helpers/Csrf.php';
require_once APP_ROOT . '/includes/db.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\AdminSubmissionController;

// Auth check
$session = SessionManager::getInstance();
$auth    = new AuthMiddleware();


if (!$session->isLoggedIn() || $session->getUserRole() !== 'admin') {
    header('Location: ' . BASE_URL . '/signup');
    exit();
}

// Initialize controller
$adminController = new AdminSubmissionController();

// Get filters from query string with validation
$filters = [
    'status' => $_GET['status'] ?? '',
    'risk'   => $_GET['risk'] ?? '',
    'search' => $_GET['search'] ?? '',
    'limit'  => 50,
    'offset' => (isset($_GET['offset']) && is_numeric($_GET['offset']))
        ? (int)$_GET['offset']
        : 0,
];

// Sanitize filter inputs - use actual database status values
$filters['status'] = in_array($filters['status'], ['active', 'pending', 'accepted', 'rejected', 'deleted', ''], true)
    ? $filters['status']
    : '';
$filters['risk'] = in_array($filters['risk'], ['low', 'medium', 'high', ''], true)
    ? $filters['risk']
    : '';
$filters['search'] = htmlspecialchars(strip_tags($filters['search']), ENT_QUOTES, 'UTF-8');

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $adminController->exportToCSV($filters);
    exit;
}

// Fetch data from database
$submissions = $adminController->getAllSubmissions($filters);
$stats       = $adminController->getStatistics();

// Helper functions using existing $conn from includes/db.php
function getUserById($conn, $id) {
    if (!is_numeric($id)) return null;
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getCourseById($conn, $id) {
    if (!is_numeric($id)) return null;
    $stmt = $conn->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Generate CSRF token
$csrfToken = \Helpers\Csrf::token();
?>
<section class="submissions-overview">
  <h2>Submissions Overview 📄</h2>

  <!-- Statistics Cards -->
  <div class="stats-cards">
    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= htmlspecialchars($stats['total'] ?? 0, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= htmlspecialchars($stats['avg_similarity'] ?? 0, ENT_QUOTES, 'UTF-8') ?>%</div>
        <div class="stat-label">Average Similarity</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= htmlspecialchars($stats['high_risk'] ?? 0, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="stat-label">High-Risk (>70%)</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-calendar"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= date('M j') ?></div>
        <div class="stat-label">Today's Date</div>
      </div>
    </div>
  </div>

  <!-- Search and Filter Form -->
  <form method="GET" action="<?= BASE_URL ?>/admin" class="search-filter-bar">
    <input type="hidden" name="page" value="submissions_overview">
    
    <input type="text" 
           name="search" 
           class="search-bar" 
           placeholder="🔍 Search by student name or title..." 
           value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
    
    <select name="status" class="filter-select">
      <option value="">All Status</option>
      <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="accepted" <?= $filters['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
      <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      <option value="deleted" <?= $filters['status'] === 'deleted' ? 'selected' : '' ?>>Deleted</option>
    </select>

    <select name="risk" class="filter-select">
      <option value="">All Risk Levels</option>
      <option value="low" <?= $filters['risk'] === 'low' ? 'selected' : '' ?>>Low (0-30%)</option>
      <option value="medium" <?= $filters['risk'] === 'medium' ? 'selected' : '' ?>>Medium (31-70%)</option>
      <option value="high" <?= $filters['risk'] === 'high' ? 'selected' : '' ?>>High (>70%)</option>
    </select>

    <button type="submit" class="btn primary">
      <i class="fas fa-search"></i> Filter
    </button>

    <a href="<?= BASE_URL ?>/admin?page=submissions_overview&export=csv" class="btn primary">
      <i class="fas fa-download"></i> Export CSV
    </a>
  </form>

  <!-- Submissions Table -->
  <div class="submissions-table-container">
    <table class="submissions-table" id="submissionsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student Name</th>
          <th>Document Title</th>
          <th>Course</th>
          <th>Instructor</th>
          <th>Submission Date</th>
          <th>Similarity Score</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($submissions)): ?>
          <tr>
            <td colspan="9" style="text-align:center;color:#a7b7d6;padding:20px;">
              No submissions found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($submissions as $sub): ?>
            <?php
              // Determine risk class
              $scoreClass = 'processing';
              $similarity = $sub['similarity'] ?? null;
              if ($similarity !== null) {
                  if ($similarity <= 30) $scoreClass = 'low';
                  elseif ($similarity <= 70) $scoreClass = 'medium';
                  else $scoreClass = 'high';
              }
              
              // Format date safely
              $createdAt = $sub['created_at'] ?? '';
              $formattedDate = '';
              if ($createdAt) {
                  try {
                      $formattedDate = date('M j, Y g:i A', strtotime($createdAt));
                  } catch (Exception $e) {
                      $formattedDate = 'Invalid date';
                  }
              }
            ?>
            <tr>
              <td><strong>#<?= htmlspecialchars($sub['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($sub['student_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($sub['title'] ?? $sub['stored_name'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge">
                  <?= htmlspecialchars($sub['course_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td>
                <small><?= htmlspecialchars($sub['instructor_name'] ?? $sub['teacher'] ?? 'None', ENT_QUOTES, 'UTF-8') ?></small>
              </td>
              <td>
                <small><?= htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') ?></small>
              </td>
              <td>
                <span class="similarity-score <?= htmlspecialchars($scoreClass, ENT_QUOTES, 'UTF-8') ?>">
                  <?= $similarity !== null ? htmlspecialchars($similarity, ENT_QUOTES, 'UTF-8') . '%' : '—' ?>
                </span>
              </td>
              <td>
                <span class="status-badge <?= htmlspecialchars($sub['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  <?= ucfirst(htmlspecialchars($sub['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8')) ?>
                </span>
              </td>
              <td>
                <button class="btn small" onclick="viewSubmissionDetails(<?= (int)($sub['id'] ?? 0) ?>)">
                  🔍 View
                </button>
                <button class="btn small danger" onclick="deleteSubmission(<?= (int)($sub['id'] ?? 0) ?>)">
                  🗑️
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- CSRF token for AJAX requests -->
  <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
</section>

<!-- Side Panel for Submission Details -->
<div id="submissionPanel" class="side-panel">
  <div class="side-panel-content">
    <div class="side-panel-header">
      <h3><i class="fas fa-file-alt"></i> Submission Details</h3>
      <button class="close-panel-btn" onclick="closeSubmissionPanel()">&times;</button>
    </div>
    <div class="side-panel-body" id="submissionPanelBody">
      <p>Loading...</p>
    </div>
  </div>
</div>

<!-- Panel Overlay -->
<div id="panelOverlay" class="panel-overlay" onclick="closeAllPanels()"></div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal" style="display:none;">
  <div class="modal-content small">
    <div class="modal-header">
      <h3>🗑️ Delete Submission</h3>
      <button class="close-btn" onclick="this.closest('.modal').style.display='none'">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete this submission? This action cannot be undone.</p>
    </div>
    <div class="modal-footer">
      <input type="hidden" id="deleteConfirmId" value="">
      <button class="btn" onclick="this.closest('.modal').style.display='none'">Cancel</button>
      <button class="btn danger" onclick="confirmDeleteSubmission()">Yes, Delete</button>
    </div>
  </div>
</div>

<script>
(function() {
  function getEl(id) {
    return document.getElementById(id);
  }

  function openPanel() {
    var panel = getEl('submissionPanel');
    var overlay = getEl('panelOverlay');
    if (!panel || !overlay) return;
    panel.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closePanel() {
    var panel = getEl('submissionPanel');
    var overlay = getEl('panelOverlay');
    if (!panel || !overlay) return;
    panel.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  // View details
  window.viewSubmissionDetails = function (submissionId) {
    var panelBody = getEl('submissionPanelBody');
    if (!panelBody) return;

    openPanel();
    panelBody.innerHTML = '<p>Loading...</p>';

    if (!submissionId || isNaN(submissionId)) {
      panelBody.innerHTML = '<p style="color:red">Error: Invalid submission ID</p>';
      return;
    }

    fetch('<?= BASE_URL ?>/ajax/get_submission_details.php?id=' +
          encodeURIComponent(submissionId))
      .then(function (r) {
        if (!r.ok) throw new Error('Network response was not ok');
        return r.json();
      })
      .then(function (data) {
        if (data && data.success) {
          panelBody.innerHTML = renderSubmissionDetails(data.submission);
        } else {
          panelBody.innerHTML =
            '<p style="color:red">Error: ' +
            ((data && data.message) || 'Failed to load submission') +
            '</p>';
        }
      })
      .catch(function (error) {
        console.error('Error:', error);
        panelBody.innerHTML =
          '<p style="color:red">Error loading submission details.</p>';
      });
  };

  function renderSubmissionDetails(s) {
    if (!s) return '<p style="color:red">No submission data available.</p>';

    var sim = (s.similarity != null) ? parseFloat(s.similarity) : 0;
    var risk, riskText;
    if (sim <= 30) {
      risk = 'low';
      riskText = 'Low Risk';
    } else if (sim <= 70) {
      risk = 'medium';
      riskText = 'Medium Risk';
    } else {
      risk = 'high';
      riskText = 'High Risk';
    }

    function escapeHtml(text) {
      if (text === null || text === undefined) return '';
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    var id           = escapeHtml(s.id || '');
    var title        = escapeHtml(s.title || s.stored_name || s.original_filename || 'Untitled');
    var studentName  = escapeHtml(s.student_name || 'Unknown');
    var studentEmail = escapeHtml(s.student_email || 'N/A');
    var createdAt    = s.created_at ? new Date(s.created_at).toLocaleString() : 'N/A';
    var exactMatch   = escapeHtml(s.exact_match || 0);
    var partialMatch = escapeHtml(s.partial_match || 0);
    var status       = escapeHtml(s.status || 'unknown');
    var statusCapitalized = status.charAt(0).toUpperCase() + status.slice(1);

    return '' +
      '<div class="submission-details-section">' +
        '<h4>📄 Document Information</h4>' +
        '<div class="info-grid">' +
          '<div class="info-item">' +
            '<label>Submission ID:</label>' +
            '<span>#' + id + '</span>' +
          '</div>' +
          '<div class="info-item">' +
            '<label>Document Title:</label>' +
            '<span>' + title + '</span>' +
          '</div>' +
          '<div class="info-item">' +
            '<label>Submission Date:</label>' +
            '<span>' + createdAt + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="submission-details-section">' +
        '<h4>👤 Student Information</h4>' +
        '<div class="info-grid">' +
          '<div class="info-item">' +
            '<label>Student Name:</label>' +
            '<span>' + studentName + '</span>' +
          '</div>' +
          '<div class="info-item">' +
            '<label>Student Email:</label>' +
            '<span>' + studentEmail + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="submission-details-section">' +
        '<h4>📊 Plagiarism Analysis</h4>' +
        '<div class="similarity-display">' +
          '<div class="similarity-info">' +
            '<div class="info-item">' +
              '<label>Similarity Score:</label>' +
              '<span class="similarity-score ' + risk + '">' +
                (s.similarity != null ? sim + '%' : 'Processing') +
              '</span>' +
            '</div>' +
            '<div class="info-item">' +
              '<label>Exact Match:</label>' +
              '<span>' + exactMatch + '%</span>' +
            '</div>' +
            '<div class="info-item">' +
              '<label>Partial Match:</label>' +
              '<span>' + partialMatch + '%</span>' +
            '</div>' +
            '<div class="info-item">' +
              '<label>Status:</label>' +
              '<span class="status-badge ' + status + '">' +
                statusCapitalized +
              '</span>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="panel-actions">' +
        '<button class="btn primary" onclick="window.location.href=\'' +
          '<?= BASE_URL ?>/ajax/download_file.php?id=' + id + '\'">' +
          '<i class="fas fa-download"></i> Download Report' +
        '</button>' +
        '<button class="btn danger" onclick="openDeleteConfirm(' + id + ')">' +
          '<i class="fas fa-trash"></i> Delete' +
        '</button>' +
      '</div>';
  }

  function openDeleteConfirm(id) {
    var modal = getEl('deleteConfirmModal');
    var input = getEl('deleteConfirmId');
    if (!modal || !input) return;

    if (!id || isNaN(id)) {
      alert('Invalid submission ID');
      return;
    }

    input.value = id;
    modal.style.display = 'flex';
  }

  window.confirmDeleteSubmission = function () {
    var input = getEl('deleteConfirmId');
    var id    = input ? input.value : '';
    if (!id || isNaN(id)) {
      alert('Invalid submission ID');
      return;
    }

    var csrfInput = getEl('csrfToken');
    var csrfToken = csrfInput ? csrfInput.value : '';
    if (!csrfToken) {
      alert('Missing CSRF token, please refresh the page.');
      return;
    }

    if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
      return;
    }

    fetch('<?= BASE_URL ?>/ajax/delete_submission.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'id=' + encodeURIComponent(id) +
            '&_csrf=' + encodeURIComponent(csrfToken)
    })
      .then(function (r) {
        if (!r.ok) throw new Error('Network response was not ok');
        return r.json();
      })
      .then(function (d) {
        if (d && d.success) {
          closeDeleteModal();
          location.reload();
        } else {
          alert('Error: ' + (d && d.message || 'Failed to delete submission'));
        }
      })
      .catch(function (error) {
        console.error('Error:', error);
        alert('Error deleting submission. Please try again.');
      });
  };

  function closeDeleteModal() {
    var modal = getEl('deleteConfirmModal');
    if (modal) modal.style.display = 'none';
  }

  window.deleteSubmission = function (id) {
    if (!id || isNaN(id)) {
      alert('Invalid submission ID');
      return;
    }
    openDeleteConfirm(id);
  };

  window.closeSubmissionPanel = function () {
    closePanel();
  };

  window.closeAllPanels = function () {
    closePanel();
  };
})();
</script>

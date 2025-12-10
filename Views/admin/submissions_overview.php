<?php
/**
 * Protected Admin Submissions Overview View
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

// Include the controller
require_once dirname(__DIR__, 2) . '/controllers/AdminSubmissionController.php';
require_once dirname(__DIR__, 2) . '/Helpers/Csrf.php';

use Controllers\AdminSubmissionController;

// Initialize controller
$adminController = new AdminSubmissionController();

// Get filters from query string
$filters = [
    'status' => $_GET['status'] ?? '',
    'risk' => $_GET['risk'] ?? '',
    'search' => $_GET['search'] ?? '',
    'limit' => 50,
    'offset' => $_GET['offset'] ?? 0
];

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $adminController->exportToCSV($filters);
    exit;
}

// Fetch data from database
$submissions = $adminController->getAllSubmissions($filters);
$stats = $adminController->getStatistics();

// Get users and courses for display
require_once dirname(__DIR__, 2) . '/includes/db.php';

function getUserById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getCourseById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>

<section class="submissions-overview">
  <h2>Submissions Overview ðŸ“„</h2>

  <!-- Statistics Cards -->
  <div class="stats-cards">
    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-file-alt"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= htmlspecialchars($stats['total']) ?></div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= htmlspecialchars($stats['avg_similarity']) ?>%</div>
        <div class="stat-label">Average Similarity</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-number"><?= htmlspecialchars($stats['high_risk']) ?></div>
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
  <form method="GET" action="admin.php" class="search-filter-bar">
    <input type="hidden" name="page" value="submissions_overview">
    
    <input type="text" 
           name="search" 
           class="search-bar" 
           placeholder="ðŸ” Search by student name or title..." 
           value="<?= htmlspecialchars($filters['search']) ?>">
    
    <select name="status" class="filter-select">
      <option value="">All Status</option>
      <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
      <option value="processing" <?= $filters['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
      <option value="uploaded" <?= $filters['status'] === 'uploaded' ? 'selected' : '' ?>>Uploaded</option>
      <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
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

    <a href="admin.php?page=submissions_overview&export=csv" class="btn primary">
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
              if ($sub['similarity'] !== null) {
                  if ($sub['similarity'] <= 30) $scoreClass = 'low';
                  elseif ($sub['similarity'] <= 70) $scoreClass = 'medium';
                  else $scoreClass = 'high';
              }
            ?>
            <tr>
              <td><strong>#<?= htmlspecialchars($sub['id']) ?></strong></td>
              <td><?= htmlspecialchars($sub['student_name'] ?? 'Unknown') ?></td>
              <td><?= htmlspecialchars($sub['title'] ?? $sub['stored_name'] ?? 'Untitled') ?></td>
              <td>
                <span class="badge">
                  <?= htmlspecialchars($sub['course_id'] ?? 'N/A') ?>
                </span>
              </td>
              <td>
                <small><?= htmlspecialchars($sub['instructor_name'] ?? $sub['teacher'] ?? 'None') ?></small>
              </td>
              <td>
                <small><?= date('M j, Y g:i A', strtotime($sub['created_at'])) ?></small>
              </td>
              <td>
                <span class="similarity-score <?= $scoreClass ?>">
                  <?= $sub['similarity'] !== null ? htmlspecialchars($sub['similarity']) . '%' : 'â€”' ?>
                </span>
              </td>
              <td>
                <span class="status-badge <?= htmlspecialchars($sub['status']) ?>">
                  <?= ucfirst(htmlspecialchars($sub['status'])) ?>
                </span>
              </td>
              <td>
                <button class="btn small" onclick="viewSubmissionDetails(<?= $sub['id'] ?>)">
                  ðŸ‘ï¸ View
                </button>
                <button class="btn small danger" onclick="deleteSubmission(<?= $sub['id'] ?>)">
                  ðŸ—‘ï¸
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- CSRF token for AJAX requests -->
  <input type="hidden" id="csrfToken" value="<?= \Helpers\Csrf::token() ?>">
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
      <h3>ðŸ—‘ï¸ Delete Submission</h3>
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

  window.viewSubmissionDetails = function (submissionId) {
    var panelBody = getEl('submissionPanelBody');
    if (!panelBody) return;

    openPanel();
    panelBody.innerHTML = '<p>Loading...</p>';

    fetch('ajax/get_submission_details.php?id=' + encodeURIComponent(submissionId))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          panelBody.innerHTML = renderSubmissionDetails(data.submission);
        } else {
          panelBody.innerHTML = '<p style="color:red">Error: ' + (data.message || 'Failed to load submission') + '</p>';
        }
      })
      .catch(function () {
        panelBody.innerHTML = '<p style="color:red">Error loading submission.</p>';
      });
  };

  function renderSubmissionDetails(s) {
    var sim = (s.similarity != null) ? s.similarity : 0;
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

    return '' +
    '<div class="submission-details-section">' +
      '<h4>ðŸ“‹ Document Information</h4>' +
      '<div class="info-grid">' +
        '<div class="info-item">' +
          '<label>Submission ID:</label>' +
          '<span>#' + s.id + '</span>' +
        '</div>' +
        '<div class="info-item">' +
          '<label>Document Title:</label>' +
          '<span>' + (s.title || s.stored_name || s.original_filename || 'Untitled') + '</span>' +
        '</div>' +
        '<div class="info-item">' +
          '<label>Submission Date:</label>' +
          '<span>' + new Date(s.created_at).toLocaleString() + '</span>' +
        '</div>' +
      '</div>' +
    '</div>' +

    '<div class="submission-details-section">' +
      '<h4>ðŸ‘¤ Student Information</h4>' +
      '<div class="info-grid">' +
        '<div class="info-item">' +
          '<label>Student Name:</label>' +
          '<span>' + (s.student_name || 'Unknown') + '</span>' +
        '</div>' +
        '<div class="info-item">' +
          '<label>Student Email:</label>' +
          '<span>' + (s.student_email || 'N/A') + '</span>' +
        '</div>' +
      '</div>' +
    '</div>' +

    '<div class="submission-details-section">' +
      '<h4>ðŸ“Š Plagiarism Analysis</h4>' +
      '<div class="similarity-display">' +
        '<div class="similarity-info">' +
          '<div class="info-item">' +
            '<label>Similarity Score:</label>' +
            '<span class="similarity-score ' + risk + '">' + (s.similarity != null ? s.similarity + '%' : 'Processing') + '</span>' +
          '</div>' +
          '<div class="info-item">' +
            '<label>Exact Match:</label>' +
            '<span>' + (s.exact_match || 0) + '%</span>' +
          '</div>' +
          '<div class="info-item">' +
            '<label>Partial Match:</label>' +
            '<span>' + (s.partial_match || 0) + '%</span>' +
          '</div>' +
          '<div class="info-item">' +
            '<label>Status:</label>' +
            '<span class="status-badge ' + s.status + '">' + s.status.charAt(0).toUpperCase() + s.status.slice(1) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
    '</div>' +

    '<div class="panel-actions">' +
      '<button class="btn primary" onclick="window.location.href=\'ajax/download_file.php?id=' + s.id + '\'">' +
        '<i class="fas fa-download"></i> Download Report' +
      '</button>' +
      '<button class="btn danger" onclick="openDeleteConfirm(' + s.id + ')">' +
        '<i class="fas fa-trash"></i> Delete' +
      '</button>' +
    '</div>';
  }

  function openDeleteConfirm(id) {
    var modal = getEl('deleteConfirmModal');
    var input = getEl('deleteConfirmId');
    if (!modal || !input) return;
    input.value = id;
    modal.style.display = 'flex';
  }

  window.confirmDeleteSubmission = function () {
    var input = getEl('deleteConfirmId');
    var id = input ? input.value : '';
    if (!id) return;

    var csrfInput = getEl('csrfToken');
    var csrfToken = csrfInput ? csrfInput.value : '';
    if (!csrfToken) {
      alert('Missing CSRF token, please refresh the page.');
      return;
    }

    fetch('ajax/delete_submission.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id) + '&_csrf=' + encodeURIComponent(csrfToken)
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.success) {
          closeDeleteModal();
          location.reload();
        } else {
          alert('Error: ' + (d.message || 'Failed to delete submission'));
        }
      })
      .catch(function () {
        alert('Error deleting submission.');
      });
  };

  function closeDeleteModal() {
    var modal = getEl('deleteConfirmModal');
    if (modal) modal.style.display = 'none';
  }

  window.deleteSubmission = function (id) {
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
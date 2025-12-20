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
    header('Location: ' . BASE_URL . '/signup.php');
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

// Sanitize filter inputs
$filters['status'] = in_array($filters['status'], ['completed', 'processing', 'uploaded', 'failed', ''], true)
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
  <!-- your existing HTML table / stats / filters markup here -->

  <!-- CSRF token for AJAX requests -->
  <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
</section>

<!-- Side Panel, Overlay, Modal HTML remains as you already wrote it -->

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

// Helper to safely get elements (page may not be submissions_overview)
function getEl(id) {
  return document.getElementById(id);
}

function openSubmissionPanel() {
  var panel = getEl('submissionPanel');
  var overlay = getEl('panelOverlay');
  if (!panel || !overlay) return;
  panel.classList.add('open');
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeSubmissionPanelInternal() {
  var panel = getEl('submissionPanel');
  var overlay = getEl('panelOverlay');
  if (!panel || !overlay) return;
  panel.classList.remove('open');
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

// Global: View details
window.viewSubmissionDetails = function (submissionId) {
  var panelBody = getEl('submissionPanelBody');
  if (!panelBody) return;

  openSubmissionPanel();
  panelBody.innerHTML = '<p>Loading...</p>';

  fetch('/Plagirism_Detection_System/ajax/get_submission_details.php?id=' +
        encodeURIComponent(submissionId))
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        panelBody.innerHTML = renderSubmissionDetails(data.submission);
      } else {
        panelBody.innerHTML =
          '<p style="color:red">Error: ' +
          (data.message || 'Failed to load submission') +
          '</p>';
      }
    })
    .catch(function () {
      panelBody.innerHTML =
        '<p style="color:red">Error loading submission.</p>';
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

  return (
    '<div style="padding:15px;">' +
      '<h4>Submission #' + s.id + '</h4>' +
      '<p><strong>Title:</strong> ' +
        (s.title || s.original_filename || s.stored_name || 'Untitled') +
      '</p>' +
      '<p><strong>Student:</strong> ' +
        (s.student_name || 'Unknown') +
      '</p>' +
      '<p><strong>Course ID:</strong> ' +
        (s.course_id || '—') +
      '</p>' +
      '<p><strong>Submitted:</strong> ' +
        new Date(s.created_at).toLocaleString() +
      '</p>' +
      '<p><strong>Similarity:</strong> ' +
        '<span class="similarity-score ' + risk + '">' +
          (s.similarity != null ? s.similarity + '%' : 'Processing') +
        '</span>' +
      '</p>' +
      '<p><strong>Risk Level:</strong> <strong>' + riskText + '</strong></p>' +
    '</div>'
  );
}

// Global: Delete submission
window.deleteSubmission = function (id) {
  if (!confirm('Delete this submission permanently?')) return;

  var csrfInput = getEl('csrfToken');
  var csrfToken = csrfInput ? csrfInput.value : '';
  if (!csrfToken) {
    alert('Missing CSRF token, please refresh the page.');
    return;
  }

  fetch('/Plagirism_Detection_System/ajax/delete_submission.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(id) +
          '&_csrf=' + encodeURIComponent(csrfToken)
  })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d.success) {
        alert('Deleted!');
        location.reload();
      } else {
        alert('Error: ' + (d.message || 'Failed to delete submission'));
      }
    })
    .catch(function () {
      alert('Error deleting submission.');
    });
};

// Global: close panel helpers for inline handlers
window.closeSubmissionPanel = function () {
  closeSubmissionPanelInternal();
};

window.closeAllPanels = function () {
  closeSubmissionPanelInternal();
};

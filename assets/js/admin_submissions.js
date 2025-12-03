
// CSRF Token
const csrfToken = '<?= \Helpers\Csrf::generate() ?>';

function viewSubmissionDetails(submissionId) {
  document.getElementById('submissionPanel').classList.add('open');
  document.getElementById('panelOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
  document.getElementById('submissionPanelBody').innerHTML = '<p>Loading...</p>';

  fetch(`ajax/get_submission_details.php?id=${submissionId}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('submissionPanelBody').innerHTML = renderDetails(data.submission);
      } else {
        document.getElementById('submissionPanelBody').innerHTML = '<p style="color:red">Error: ' + data.message + '</p>';
      }
    });
}

function renderDetails(s) {
  const sim = s.similarity ?? 0;
  const risk = sim <= 30 ? 'low' : sim <= 70 ? 'medium' : 'high';
  const riskText = sim <= 30 ? 'Low Risk' : sim <= 70 ? 'Medium Risk' : 'High Risk';

  return `
    <div style="padding:15px;">
      <h4>Submission #${s.id}</h4>
      <p><strong>Title:</strong> ${s.title || s.original_filename || 'Untitled'}</p>
      <p><strong>Student:</strong> ${s.student_name || 'Unknown'}</p>
      <p><strong>Course ID:</strong> ${s.course_id || '—'}</p>
      <p><strong>Submitted:</strong> ${new Date(s.created_at).toLocaleString()}</p>
      <p><strong>Similarity:</strong> <span class="similarity-score ${risk}">${s.similarity !== null ? s.similarity + '%' : 'Processing'}</span></p>
      <p><strong>Risk Level:</strong> <strong style="color:${risk==='low'?'#27ae60':risk==='medium'?'#e67e22':'#e74c3c'}">${riskText}</strong></p>
      <div style="margin-top:20px;">
        <button class="btn primary" onclick="window.location.href='ajax/download_file.php?id=${s.id}'">Download File</button>
        <button class="btn danger" onclick="deleteSubmission(${s.id})">Delete</button>
      </div>
    </div>
  `;
}

function deleteSubmission(id) {
  if (!confirm('Delete this submission permanently?')) return;
  fetch('ajax/delete_submission.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + id + '&_csrf=' + csrfToken
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      alert('Deleted!');
      location.reload();
    } else {
      alert('Error: ' + d.message);
    }
  });
}

function closeSubmissionPanel() {
  document.getElementById('submissionPanel').classList.remove('open');
  document.getElementById('panelOverlay').classList.remove('active');
  document.body.style.overflow = '';
}

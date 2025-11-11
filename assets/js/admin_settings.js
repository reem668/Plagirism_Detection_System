
document.addEventListener('DOMContentLoaded', function() {
  loadSettings();
});

function loadSettings() {
  // Try to load from localStorage
  const saved = localStorage.getItem('systemSettings');
  if (saved) {
    const settings = JSON.parse(saved);
    document.getElementById('maxUploadSize').value = settings.maxUploadSize || 10;
    document.getElementById('plagiarismThreshold').value = settings.plagiarismThreshold || 50;
    document.getElementById('submissionQuota').value = settings.submissionQuota || 20;
  }
}

function saveSettings(event) {
  event.preventDefault();

  const maxUpload = parseInt(document.getElementById('maxUploadSize').value);
  const threshold = parseInt(document.getElementById('plagiarismThreshold').value);
  const quota = parseInt(document.getElementById('submissionQuota').value);

  if (threshold < 10 || threshold > 90) {
    showSettingsNotification('⚠️ Plagiarism threshold must be between 10-90%', 'error');
    return;
  }

  const settings = {
    maxUploadSize: maxUpload,
    plagiarismThreshold: threshold,
    submissionQuota: quota
  };
  
  localStorage.setItem('systemSettings', JSON.stringify(settings));
  
  showSettingsNotification('✅ Settings saved successfully!', 'success');
}

function resetSettings() {
  if (!confirm('Reset settings to default values?')) return;
  
  document.getElementById('maxUploadSize').value = 10;
  document.getElementById('plagiarismThreshold').value = 50;
  document.getElementById('submissionQuota').value = 20;
  
  localStorage.removeItem('systemSettings');
  showSettingsNotification('✅ Settings reset to defaults!', 'success');
}

function showSettingsNotification(message, type) {
  const notification = document.getElementById('settingsNotification');
  notification.textContent = message;
  notification.className = 'notice ' + type;
  notification.style.display = 'block';

  setTimeout(() => {
    notification.style.display = 'none';
  }, 3000);
}

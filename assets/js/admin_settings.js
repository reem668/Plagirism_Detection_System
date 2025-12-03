document.addEventListener('DOMContentLoaded', function() {
  loadSettings();
});

function loadSettings() {
  fetch('ajax/get_settings.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('maxUploadSize').value = data.settings.maxUploadSize || 10;
        document.getElementById('plagiarismThreshold').value = data.settings.plagiarismThreshold || 50;
        document.getElementById('submissionQuota').value = data.settings.submissionQuota || 20;
      } else {
        showSettingsNotification('⚠️ ' + (data.message || 'Failed to load settings'), 'error');
      }
    })
    .catch(error => {
      console.error('Error loading settings:', error);
      showSettingsNotification('⚠️ Error loading settings', 'error');
    });
}

function saveSettings(event) {
  event.preventDefault();

  const maxUpload = parseInt(document.getElementById('maxUploadSize').value);
  const threshold = parseInt(document.getElementById('plagiarismThreshold').value);
  const quota = parseInt(document.getElementById('submissionQuota').value);
  const csrfToken = document.getElementById('csrfToken').value;

  if (threshold < 10 || threshold > 90) {
    showSettingsNotification('⚠️ Plagiarism threshold must be between 10-90%', 'error');
    return;
  }

  if (maxUpload < 1 || maxUpload > 1000) {
    showSettingsNotification('⚠️ Max upload size must be between 1-1000 MB', 'error');
    return;
  }

  if (quota < 5 || quota > 100) {
    showSettingsNotification('⚠️ Submission quota must be between 5-100', 'error');
    return;
  }

  fetch('ajax/save_settings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `maxUploadSize=${maxUpload}&plagiarismThreshold=${threshold}&submissionQuota=${quota}&_csrf=${encodeURIComponent(csrfToken)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showSettingsNotification('✅ ' + data.message, 'success');
    } else {
      showSettingsNotification('⚠️ ' + (data.message || 'Failed to save settings'), 'error');
    }
  })
  .catch(error => {
    console.error('Error saving settings:', error);
    showSettingsNotification('⚠️ Error saving settings', 'error');
  });
}

function resetSettings() {
  if (!confirm('Reset settings to default values?')) return;
  
  const csrfToken = document.getElementById('csrfToken').value;

  fetch('ajax/reset_settings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `_csrf=${encodeURIComponent(csrfToken)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showSettingsNotification('✅ ' + data.message, 'success');
      loadSettings(); // Reload to show default values
    } else {
      showSettingsNotification('⚠️ ' + (data.message || 'Failed to reset settings'), 'error');
    }
  })
  .catch(error => {
    console.error('Error resetting settings:', error);
    showSettingsNotification('⚠️ Error resetting settings', 'error');
  });
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

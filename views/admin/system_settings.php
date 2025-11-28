<?php
require_once dirname(__DIR__, 2) . '/Helpers/Csrf.php';
?>
<section class="settings">
  <h2>System Settings ⚙️</h2>

  <div id="settingsNotification" class="notice" style="display:none;"></div>

  <input type="hidden" id="csrfToken" value="<?= \Helpers\Csrf::token() ?>">

  <form id="settingsForm" onsubmit="saveSettings(event)" class="settings-form">
    <label>Max Upload File Size (MB)</label>
    <input type="number" id="maxUploadSize" name="upload_limit" min="1" max="1000" value="10" required>

    <label>Plagiarism Threshold (%)</label>
    <input type="number" id="plagiarismThreshold" min="10" max="90" value="50" required>
    <small style="color:#a7b7d6;">Alert when similarity score exceeds this percentage</small>

    <label>Monthly Submission Quota</label>
    <input type="number" id="submissionQuota" min="5" max="100" value="20" required>
    <small style="color:#a7b7d6;">Maximum submissions per student per month</small>

    <button type="submit" class="btn primary">💾 Save Settings</button>
    <button type="button" class="btn danger" onclick="resetSettings()">🔄 Reset to Defaults</button>
  </form>
</section>
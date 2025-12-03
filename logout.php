<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

require_once __DIR__ . '/Helpers/SessionManager.php';

use Helpers\SessionManager;

// Get session instance
$session = SessionManager::getInstance();

// Destroy session
$session->destroy();

// Redirect to login page
header("Location: /Plagirism_Detection_System/signup.php?logout=success");
exit();
?>

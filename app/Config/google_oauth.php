<?php


// Auto-detect protocol and host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));

$config = [
    'client_id' => '705576794480-9jr6et4j31h50um1ks4mqstr1ktfjsla.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uri' => $protocol . '://' . $host . $basePath . '/app/Controllers/AuthController.php?action=google_callback',
    'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
    'scopes' => 'email profile'
];

// Validate if credentials are set
if (strpos($config['client_id'], 'YOUR_GOOGLE_CLIENT_ID_HERE') !== false ||
    strpos($config['client_secret'], 'YOUR_GOOGLE_CLIENT_SECRET_HERE') !== false) {
    die("Google OAuth credentials are not configured. Please update app/Config/google_oauth.php with your Client ID and Client Secret.");
}

return $config;

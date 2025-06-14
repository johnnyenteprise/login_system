<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Consider using environment variables in production
define('DB_NAME', 'login_system');

// Site configuration
define('SITE_NAME', 'Login System with 2FA');
define('SITE_URL', 'http://localhost/log-system'); // No trailing slash

// PHPMailer configuration (Gmail example)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'aseinjohn1@gmail.com'); // Your full Gmail address
define('SMTP_PASS', 'ozcy pkbw wcrq pxpd'); // App password (consider moving to env)
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_FROM', 'aseinjohn1@gmail.com'); // Must match SMTP_USER
define('SMTP_FROM_NAME', 'Your Website Name');

// Security headers (added protection)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Add these BEFORE session_start()
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 86400); // 1 day
ini_set('session.gc_maxlifetime', 86400);  // 1 day
ini_set('session.cookie_secure', 1);       // Enable when using HTTPS
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Custom session path with proper permissions
$sessionPath = __DIR__ . '/../sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0700, true);
}
ini_set('session.save_path', $sessionPath);

// Start session with additional protection
session_start();

// Regenerate ID periodically for security
if (!isset($_SESSION['canary'])) {
    session_regenerate_id(true);
    $_SESSION['canary'] = time();
}
if ($_SESSION['canary'] < time() - 300) { // Every 5 minutes
    session_regenerate_id(true);
    $_SESSION['canary'] = time();
}
// Include required files
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// CSRF token generation (if not exists)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
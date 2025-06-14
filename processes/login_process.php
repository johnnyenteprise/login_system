<?php
require_once '../includes/config.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method for login");
    header('Location: ../login.php');
    exit();
}

$username = sanitizeInput($_POST['username']);
$password = sanitizeInput($_POST['password']);

if (loginUser($username, $password)) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Debug output
    error_log("Login successful for user: $username, 2FA user ID set to: " . $_SESSION['2fa_user_id']);
    
    header('Location: ../verify_2fa.php');
    exit();
} else {
    error_log("Login failed for user: $username");
    $_SESSION['error'] = "Invalid username or password";
    header('Location: ../login.php');
    exit();
}
?>
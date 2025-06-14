<?php
require_once '../includes/config.php';

// Validate request method and session
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['2fa_user_id'])) {
    error_log("Invalid 2FA verification attempt: Missing POST data or session");
    $_SESSION['error'] = "Invalid verification request";
    redirect('../login.php');
    exit();
}

// Sanitize and validate input
$code = sanitizeInput($_POST['code'] ?? '');
if (empty($code)) {
    error_log("Empty 2FA code submitted for user ID: " . $_SESSION['2fa_user_id']);
    $_SESSION['error'] = "Please enter the verification code";
    redirect('../verify_2fa.php');
    exit();
}

// Track verification attempts
if (!isset($_SESSION['2fa_attempts'])) {
    $_SESSION['2fa_attempts'] = 0;
}

// Check attempt limit
if ($_SESSION['2fa_attempts'] >= 3) {
    error_log("2FA attempt limit reached for user ID: " . $_SESSION['2fa_user_id']);
    $_SESSION['error'] = "Too many attempts. Please request a new code.";
    redirect('../login.php');
    exit();
}

// Verify the code
if (verify2FACode($_SESSION['2fa_user_id'], $code)) {
    // Security measures on successful verification
    session_regenerate_id(true); // Prevent session fixation

    // After successful code verification:
if (verify2FACode($_SESSION['2fa_user_id'], $code)) {
    // Destroy old session completely
    session_unset();
    session_destroy();
    
    // Start fresh session with new ID
    session_start();
    session_regenerate_id(true);
    
    // Set all required session variables
    $_SESSION['user_id'] = $user_id; // From verification
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['last_activity'] = time();
    
    // Store session in database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $stmt = $conn->prepare("INSERT INTO sessions 
                          (user_id, session_token, expires_at) 
                          VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit();
}
    
    // Store user ID in session
    $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
    
    // Set session security parameters
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['verified'] = true;
    
    // Clear 2FA-specific session data
    unset($_SESSION['2fa_user_id']);
    unset($_SESSION['2fa_attempts']);
    
    // Create session in database (if using database sessions)
    $sessionToken = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $_SESSION['user_id'], $sessionToken, $expires);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    $_SESSION['session_token'] = $sessionToken;
    
    // Redirect to dashboard with success message
    $_SESSION['success'] = "Login successful!";
    redirect('../dashboard.php');
    exit();
} else {
    // Failed verification
    $_SESSION['2fa_attempts']++;
    error_log("Failed 2FA attempt for user ID: " . $_SESSION['2fa_user_id'] . " Attempt: " . $_SESSION['2fa_attempts']);
    
    $_SESSION['error'] = "Invalid verification code. Attempt " . $_SESSION['2fa_attempts'] . " of 3";
    redirect('../verify_2fa.php');
    exit();
}
?>
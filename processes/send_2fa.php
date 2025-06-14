<?php
require_once '../includes/config.php';

if (!isset($_SESSION['2fa_user_id'])) {
    redirect('../login.php');
}

// Get user email
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['2fa_user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Generate new 2FA code
$code = generateRandomString();
$expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Store new 2FA code
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $conn->prepare("INSERT INTO two_fa_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $_SESSION['2fa_user_id'], $code, $expires);
$stmt->execute();
$stmt->close();
$conn->close();

// Send 2FA email
$subject = "Your New 2FA Code";
$body = "Your new verification code is: <strong>$code</strong><br>This code will expire in 15 minutes.";
sendEmail($user['email'], $subject, $body);

$_SESSION['success'] = "A new verification code has been sent to your email";
redirect('../verify_2fa.php');
?>
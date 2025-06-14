<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}

$username = sanitizeInput($_POST['username']);
$email = sanitizeInput($_POST['email']);
$password = sanitizeInput($_POST['password']);
$confirmPassword = sanitizeInput($_POST['confirm_password']);

// Validate inputs
if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
    $_SESSION['error'] = "All fields are required";
    redirect('../register.php');
}

if ($password !== $confirmPassword) {
    $_SESSION['error'] = "Passwords do not match";
    redirect('../register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    redirect('../register.php');
}

if (strlen($password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters long";
    redirect('../register.php');
}

if (registerUser($username, $email, $password)) {
    $_SESSION['success'] = "Registration successful! You can now login.";
    redirect('../login.php');
} else {
    $_SESSION['error'] = "Username or email already exists";
    redirect('../register.php');
}
// After successful registration in register_process.php
if (registerUser($username, $email, $password)) {
    // Debug: Check what was actually stored in the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $stmt = $conn->prepare("SELECT username, email, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    error_log("Registered user: " . print_r($user, true));
    $stmt->close();
    $conn->close();
    
    $_SESSION['success'] = "Registration successful! You can now login.";
    redirect('../login.php');
}
?>
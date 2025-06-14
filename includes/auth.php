<?php
function registerUser($username, $email, $password) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }

    // Case-insensitive check for existing user
    $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("ss", $username, $email);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        error_log("Registration failed: User already exists");
        $stmt->close();
        $conn->close();
        return false;
    }
    $stmt->close();

    // Hash password with current best practice algorithm
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    if ($hashedPassword === false) {
        error_log("Password hashing failed");
        $conn->close();
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Registration failed: " . $stmt->error);
    } else {
        error_log("Registration successful for user: " . $username);
    }
    
    $stmt->close();
    $conn->close();
    return $success;
}

function loginUser($username, $password) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }

    $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        error_log("Login failed: User not found - " . $username);
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password'])) {
        error_log("Login failed: Invalid password for user - " . $username);
        $conn->close();
        return false;
    }

    // Generate 2FA code
    $code = generateRandomString();
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store 2FA code
    $stmt = $conn->prepare("INSERT INTO two_fa_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed for 2FA code: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("iss", $user['id'], $code, $expires);
    if (!$stmt->execute()) {
        error_log("Failed to store 2FA code: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    $stmt->close();
    
    // Store user ID in session for verification
    $_SESSION['2fa_user_id'] = $user['id'];
    $_SESSION['2fa_attempts'] = 0;
    
    // Send 2FA email
    $subject = "Your 2FA Verification Code";
    $body = "Your verification code is: <strong>$code</strong><br>This code will expire in 15 minutes.";
    
    if (!sendEmail($user['email'], $subject, $body)) {
        error_log("Failed to send 2FA email to " . $user['email']);
        $conn->close();
        return false;
    }
    
    $conn->close();
    return true;
}

function verify2FACode($userId, $code) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }

    $currentTime = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT id FROM two_fa_codes 
                          WHERE user_id = ? 
                          AND code = ? 
                          AND expires_at > ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("iss", $userId, $code, $currentTime);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        error_log("Invalid 2FA code for user ID: $userId");
        $stmt->close();
        $conn->close();
        return false;
    }
    $stmt->close();

    // Create new session
    $sessionToken = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed for session: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("iss", $userId, $sessionToken, $expires);
    if (!$stmt->execute()) {
        error_log("Failed to create session: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    $stmt->close();
    
    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['session_token'] = $sessionToken;
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['last_activity'] = time();
    
    // Delete used 2FA code
    $stmt = $conn->prepare("DELETE FROM two_fa_codes WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
    return true;
}

function validateSession() {
    // Check basic session variables
    $requiredVars = ['user_id', 'session_token', 'ip_address', 'user_agent', 'last_activity'];
    foreach ($requiredVars as $var) {
        if (!isset($_SESSION[$var])) {
            error_log("Session validation failed: Missing $var");
            return false;
        }
    }
    
    // Verify security consistency
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] ||
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        error_log("Session hijacking attempt detected");
        return false;
    }
    
    // Check session activity timeout (30 minutes)
    if (time() - $_SESSION['last_activity'] > 1800) {
        error_log("Session expired due to inactivity");
        return false;
    }
    
    // Check database session
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    $stmt = $conn->prepare("SELECT 1 FROM sessions 
                           WHERE user_id = ? 
                           AND session_token = ? 
                           AND expires_at > NOW()");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $valid = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    
    if ($valid) {
        // Update session expiration
        $stmt = $conn->prepare("UPDATE sessions 
                               SET expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                               WHERE user_id = ? AND session_token = ?");
        if ($stmt) {
            $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    } else {
        error_log("Invalid session for user ID: {$_SESSION['user_id']}");
    }
    
    $conn->close();
    return $valid;
}
?>
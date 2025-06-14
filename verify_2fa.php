<?php 
require_once 'includes/config.php';

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate 2FA session
if (!isset($_SESSION['2fa_user_id'])) {
    error_log("2FA verification failed: No user ID in session. Session data: " . print_r($_SESSION, true));
    $_SESSION['error'] = "Your session expired or is invalid. Please login again.";
    header('Location: login.php');
    exit();
}

// Check if user exists
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['2fa_user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("2FA verification failed: User ID not found. ID: " . $_SESSION['2fa_user_id']);
    $_SESSION['error'] = "Invalid account. Please register or contact support.";
    header('Location: register.php');
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2FA Verification - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background: radial-gradient(circle at top left, #0f2027, #203a43, #2c5364);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #1c1f26;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
            animation: floatUp 0.9s ease-out;
        }
        @keyframes floatUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .card-title {
            font-size: 1.75rem;
            font-weight: bold;
            color: #0ff;
        }
        .form-control {
            background-color: #2a2e38;
            border: 1px solid #444;
            color: #fff;
        }
        .form-control:focus {
            border-color: #0ff;
            box-shadow: 0 0 5px #0ff;
        }
        .btn-primary {
            background-color: #00c6ff;
            border: none;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #0078a3;
        }
        a {
            color: #0ff;
        }
        a:hover {
            color: #00c6ff;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">
                        <i class="fas fa-shield-alt me-2"></i>2FA Verification
                    </h3>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-center mb-3">We've sent a verification code to your email. 🔐</p>

                    <form action="processes/verify_2fa.php" method="POST">
                        <div class="mb-3">
                            <label for="code" class="form-label">Enter Verification Code</label>
                            <input type="text" class="form-control" id="code" name="code" required autofocus>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Verify Now</button>
                        </div>
                    </form>

                    <div class="mt-4 text-center">
                        <p>Didn't get it? <a href="processes/send_2fa.php">Resend Code</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

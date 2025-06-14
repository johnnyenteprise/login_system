<?php
require_once 'includes/config.php';

if (!validateSession()) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = "Your session expired or is invalid. Please login again.";
    header('Location: login.php');
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body {
            background: linear-gradient(to right, #141e30, #243b55);
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: #1f1f1f !important;
        }
        .card {
            background-color: #2c3e50;
            border: none;
            border-radius: 1rem;
            color: #fff;
            animation: fadeIn 0.8s ease;
            box-shadow: 0 0 20px rgba(0,0,0,0.4);
        }
        .card-title {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .btn-logout {
            color: #fff;
            font-weight: 600;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link btn-logout" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card p-4">
                    <div class="card-body text-center">
                        <h3 class="card-title">👋 Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h3>
                        <p class="mt-3"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-success">✅ Logged in with 2FA</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Session keep-alive
setInterval(() => {
    fetch('/keepalive.php', { credentials: 'include' })
    .then(res => { if (!res.ok) throw new Error(); })
    .catch(() => window.location.href = '/login.php?expired=1');
}, 300000);
</script>
</body>
</html>

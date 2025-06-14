<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register - <?php echo SITE_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>

    <!-- Custom Style -->
    <style>
        body {
            background: linear-gradient(to right, #3a1c71, #d76d77, #ffaf7b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-in-out;
        }
        .card-title {
            font-weight: 700;
            color: #3a1c71;
        }
        .btn-primary {
            background-color: #3a1c71;
            border: none;
            font-weight: bold;
            transition: 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2b0e5a;
        }
        .form-label {
            font-weight: 600;
        }
        .icon-header {
            font-size: 2.5rem;
            color: #3a1c71;
            margin-bottom: 1rem;
        }
        a {
            font-weight: 600;
            color: #3a1c71;
        }
        @keyframes fadeIn {
            0% { transform: translateY(20px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4">
                <div class="card-body text-center">
                    <div class="icon-header"><i class="fas fa-user-plus"></i></div>
                    <h3 class="card-title mb-3">Create an Account</h3>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>

                    <form action="processes/register_process.php" method="POST">
                        <div class="mb-3 text-start">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Your email address" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Choose a password" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Register <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

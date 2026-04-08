<?php
session_start();
require_once 'auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CDF Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1516387938699-a93567ec168e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .forgot-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 400px;
            max-width: 90%;
        }
        .forgot-header {
            background: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .forgot-form {
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="forgot-container">
            <div class="forgot-header">
                <h3><i class="fas fa-key me-2"></i>Reset Password</h3>
                <p class="mb-0">Recover your CDF Management account</p>
            </div>
            <div class="forgot-form">
                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info"><?php echo $_SESSION['info']; unset($_SESSION['info']); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="auth.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Enter your email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-text">We'll send a password reset link to your email.</div>
                    </div>
                    <button type="submit" name="forgot_password" class="btn btn-primary w-100 mb-3">Send Reset Link</button>
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
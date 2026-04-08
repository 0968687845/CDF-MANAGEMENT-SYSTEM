<?php
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    switch (getUserRole()) {
        case 'admin':
            redirect('admin_dashboard.php');
            break;
        case 'officer':
            redirect('officer_dashboard.php');
            break;
        case 'beneficiary':
            redirect('beneficiary_dashboard.php');
            break;
        default:
            redirect('index.php');
    }
}

$success = '';
$error = '';
$token = $_GET['token'] ?? '';
$user_email = '';
$showForm = false;

// Validate token if provided
if (!empty($token)) {
    global $pdo;
    
    try {
        // Check if token exists and is not expired
        $query = "SELECT email, user_id FROM password_resets 
                  WHERE token = :token 
                  AND expires_at > NOW() 
                  AND is_used = FALSE
                  LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_email = $reset['email'];
            $showForm = true;
        } else {
            $error = "This password reset link is invalid or has expired. Please request a new one.";
        }
    } catch (Exception $e) {
        error_log("Error validating reset token: " . $e->getMessage());
        $error = "An error occurred. Please try again.";
    }
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please enter and confirm your new password";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match";
        } elseif (!validatePassword($new_password)) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
        } else {
            try {
                global $pdo;

                // Get user from reset token
                $query = "SELECT user_id FROM password_resets
                          WHERE token = :token
                          AND expires_at > NOW()
                          AND is_used = FALSE
                          LIMIT 1";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':token', $token);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
                    $user_id = $reset['user_id'];

                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update user password
                    $updateQuery = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :user_id";
                    $updateStmt = $pdo->prepare($updateQuery);
                    $updateStmt->bindParam(':password', $hashed_password);
                    $updateStmt->bindParam(':user_id', $user_id);

                    if ($updateStmt->execute()) {
                        // Mark token as used
                        $markQuery = "UPDATE password_resets SET is_used = TRUE, used_at = NOW() WHERE token = :token";
                        $markStmt = $pdo->prepare($markQuery);
                        $markStmt->bindParam(':token', $token);
                        $markStmt->execute();

                        // Log activity
                        logActivity($user_id, 'password_reset_completed', 'Password reset completed via reset link');

                        $success = "Your password has been successfully reset. You can now login with your new password.";
                        $showForm = false;
                    } else {
                        $error = "Failed to update password. Please try again.";
                    }
                } else {
                    $error = "This password reset link is invalid or has expired. Please request a new one.";
                }
            } catch (Exception $e) {
                error_log("Error resetting password: " . $e->getMessage());
                $error = "An error occurred. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CDF Management System | Government of Zambia</title>
    <meta name="description" content="Reset your password for CDF Management System - Government of the Republic of Zambia">
    <meta name="keywords" content="Zambia Government, CDF, Password Reset, Secure Access, Constituency Development Fund">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
            color: #212529;
            line-height: 1.7;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            border-bottom: 3px solid var(--secondary);
        }

        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 60px;
            min-height: 100vh;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            width: 100%;
            max-width: 500px;
            animation: fadeInUp 0.6s ease-out;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-form {
            padding: 2rem;
        }

        .form-control {
            border: 2.5px solid rgba(26, 78, 138, 0.2);
            border-radius: 12px;
            padding: 0.875rem 1.125rem;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 5px rgba(26, 78, 138, 0.15);
            border-color: var(--primary-dark);
        }

        .form-label {
            font-weight: 800;
            color: #212529;
            margin-bottom: 0.75rem;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
        }

        .password-requirements {
            background: #f0f7ff;
            border: 2px solid #1a4e8a;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .password-requirements h6 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .password-requirements li {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #333;
        }

        .password-strength {
            height: 10px;
            border-radius: 10px;
            margin-top: 0.75rem;
            background: #d0d0d0;
            overflow: hidden;
            border: 1px solid #bbb;
        }

        .password-strength-bar {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .strength-weak { 
            background: linear-gradient(90deg, #d32f2f 0%, #b71c1c 100%);
            width: 25%;
        }
        .strength-fair { 
            background: linear-gradient(90deg, #f57c00 0%, #e65100 100%);
            width: 50%;
        }
        .strength-good { 
            background: linear-gradient(90deg, #1976d2 0%, #1565c0 100%);
            width: 75%;
        }
        .strength-strong { 
            background: linear-gradient(90deg, #388e3c 0%, #2e7d32 100%);
            width: 100%;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 90px 1rem 2rem;
            }

            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40" style="filter: brightness(1.05) contrast(1.1); margin-right: 12px;">
                Government of Zambia - CDF System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h4 class="mb-0">Set New Password</h4>
                <p class="mb-0 opacity-75">Create a secure password for your account</p>
            </div>
            <div class="login-form">
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4">
                        <i class="fas fa-check-circle me-3 fs-5"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                    <div class="text-center">
                        <p class="text-muted mb-3">Return to login with your new password</p>
                        <a href="login.php" class="btn btn-custom">
                            <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                        </a>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                    <div class="text-center">
                        <p class="text-muted mb-3">Request a new password reset</p>
                        <a href="forgot_password.php" class="btn btn-custom">
                            <i class="fas fa-key me-2"></i>Forgot Password
                        </a>
                    </div>
                <?php elseif ($showForm): ?>
                    <div class="password-requirements">
                        <h6><i class="fas fa-shield-alt me-2"></i>Password Requirements</h6>
                        <ul>
                            <li>At least 8 characters</li>
                            <li>One uppercase letter (A-Z)</li>
                            <li>One lowercase letter (a-z)</li>
                            <li>One number (0-9)</li>
                            <li>One special character (!@#$%^&*)</li>
                        </ul>
                    </div>

                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required 
                                   placeholder="Enter your new password" onkeyup="checkPasswordStrength(this.value)">
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="form-text" id="passwordStrengthText">Password strength</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your new password">
                            <div class="form-text" id="passwordMatchText"></div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-custom btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Update Password
                            </button>
                        </div>
                    </form>

                    <div class="text-center">
                        <a href="login.php" class="text-muted text-decoration-none">Back to Login</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please wait while we verify your reset link...
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            let text = '';
            let barClass = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    text = 'Weak';
                    barClass = 'strength-weak';
                    break;
                case 2:
                    text = 'Fair';
                    barClass = 'strength-fair';
                    break;
                case 3:
                    text = 'Good';
                    barClass = 'strength-good';
                    break;
                case 4:
                    text = 'Strong';
                    barClass = 'strength-strong';
                    break;
            }
            
            strengthBar.className = 'password-strength-bar ' + barClass;
            strengthText.textContent = text;
            strengthText.className = 'form-text ' + (strength >= 3 ? 'text-success' : strength >= 2 ? 'text-warning' : 'text-danger');
        }

        // Password match checker
        document.addEventListener('DOMContentLoaded', function() {
            const confirmPasswordField = document.getElementById('confirm_password');
            if (confirmPasswordField) {
                confirmPasswordField.addEventListener('input', function() {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = this.value;
                    const matchText = document.getElementById('passwordMatchText');
                    
                    if (confirmPassword === '') {
                        matchText.textContent = '';
                        matchText.className = 'form-text';
                    } else if (newPassword === confirmPassword) {
                        matchText.textContent = 'Passwords match';
                        matchText.className = 'form-text text-success';
                    } else {
                        matchText.textContent = 'Passwords do not match';
                        matchText.className = 'form-text text-danger';
                    }
                });
            }
        });
    </script>
</body>
</html>

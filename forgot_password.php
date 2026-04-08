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

// Process forgot password form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            $error = "Please enter your email address";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address";
        } else {
            // Rate limit: max 3 reset requests per email per 15 minutes
            global $pdo;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() >= 3) {
                $error = "Too many reset requests. Please wait 15 minutes before trying again.";
            } elseif (emailExists($email)) {
                $resetToken = generateResetToken();
                $result = sendPasswordResetEmail($email, $resetToken);

                if ($result === true) {
                    $success = "Password reset instructions have been sent to your email address. Please check your inbox and follow the instructions to reset your password.";
                } else {
                    $error = "Failed to send reset email. Please try again later or contact support.";
                }
            } else {
                // Generic message to avoid email enumeration
                $success = "If an account exists for that email, reset instructions have been sent.";
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
    <title>Forgot Password - CDF Management System | Government of Zambia</title>
    <meta name="description" content="Reset your password for CDF Management System - Government of the Republic of Zambia">
    <meta name="keywords" content="Zambia Government, CDF, Password Reset, Secure Access, Constituency Development Fund">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Color System - Enhanced Contrast */
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            
            /* Neutral Colors - Improved Readability */
            --light: #f8f9fa;
            --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            
            /* Semantic Colors - Enhanced Visibility */
            --success: #28a745;
            --success-light: #d4edda;
            --success-dark: #1e7e34;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --warning-dark: #e0a800;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --danger-dark: #c82333;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --info-dark: #138496;
            --white: #ffffff;
            --black: #000000;
            
            /* Design Tokens */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.18);
            --shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.22);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            
            --border-radius-sm: 8px;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 20px;
            
            /* Typography Scale - Enhanced Readability */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            --text-4xl: 2.25rem;
            --text-5xl: 3rem;
            
            /* Spacing Scale */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --space-20: 5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
            color: var(--gray-900);
            line-height: 1.7;
            font-weight: 400;
            min-height: 100vh;
            position: relative;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            overflow-x: hidden;
        }

        /* Enhanced Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(26, 78, 138, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(233, 185, 73, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Enhanced Navigation - Maximum Visibility */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow-lg);
            padding: var(--space-3) 0;
            backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--secondary);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: var(--space-3);
            transition: var(--transition);
            font-size: var(--text-lg);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.7);
            padding: var(--space-2) 0;
        }

        .navbar-brand:hover {
            color: var(--white) !important;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.8);
        }

        /* Enhanced Coat of Arms Images */
        .coat-of-arms {
            width: 45px !important;
            height: 45px !important;
            filter: 
                drop-shadow(0 4px 8px rgba(0, 0, 0, 0.5))
                brightness(1.05)
                contrast(1.1);
            transition: var(--transition);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            padding: 3px;
            background: rgba(255, 255, 255, 0.1);
            object-fit: contain;
        }

        .navbar-brand:hover .coat-of-arms {
            transform: scale(1.1);
            filter: 
                drop-shadow(0 6px 12px rgba(0, 0, 0, 0.7))
                brightness(1.1)
                contrast(1.2);
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-link {
            color: var(--white) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: var(--space-3) var(--space-4) !important;
            border-radius: var(--border-radius-sm);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin: 0 var(--space-1);
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--secondary);
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-link:hover::before,
        .nav-link:focus::before,
        .nav-link.active::before {
            width: 80%;
        }

        .nav-link:hover, 
        .nav-link:focus,
        .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.8);
        }

        /* Login Container */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 60px;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        .login-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .login-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-8);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="0,0 1000,100 1000,0"/></svg>');
            background-size: cover;
        }

        .login-form {
            padding: var(--space-8);
        }

        .form-section {
            margin-bottom: var(--space-6);
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: var(--space-5);
            padding-bottom: var(--space-3);
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-xl);
        }

        .section-title i {
            margin-right: var(--space-3);
            font-size: 1.3rem;
        }

        .government-notice {
            background: var(--light-gradient);
            border: 2px solid var(--primary);
            border-radius: var(--border-radius);
            padding: var(--space-5);
            margin-bottom: var(--space-5);
            position: relative;
            overflow: hidden;
        }

        .government-notice::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--secondary-gradient);
        }

        /* Enhanced Buttons */
        .btn-custom {
            background: var(--primary-gradient);
            color: var(--white) !important;
            border: none;
            padding: var(--space-4) var(--space-6);
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: var(--transition-slow);
        }

        .btn-custom:hover::before {
            left: 100%;
        }

        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            background: var(--primary-gradient);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary) !important;
            border: 2px solid var(--primary);
            padding: var(--space-4) var(--space-6);
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-outline-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--primary);
            transition: var(--transition);
            z-index: -1;
        }

        .btn-outline-custom:hover::before {
            width: 100%;
        }

        .btn-outline-custom:hover {
            color: var(--white) !important;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        .form-control {
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            padding: var(--space-4) var(--space-5);
            transition: var(--transition);
            font-size: var(--text-base);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .input-group-text {
            background-color: var(--primary);
            border: 2px solid var(--primary);
            color: var(--white);
            border-radius: var(--border-radius) 0 0 var(--border-radius) !important;
        }

        /* Footer */
        .footer {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-8) 0 var(--space-4);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.05"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
            pointer-events: none;
        }

        .footer-bottom {
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            padding-top: var(--space-6);
            margin-top: var(--space-6);
            position: relative;
        }

        .disclaimer {
            background-color: rgba(0, 0, 0, 0.3);
            padding: var(--space-5);
            border-radius: var(--border-radius);
            margin-top: var(--space-6);
            font-size: var(--text-sm);
            border-left: 4px solid var(--secondary);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Loading Animation */
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

        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Instructions Box */
        .instructions-box {
            background: var(--info-light);
            border: 2px solid var(--info);
            border-radius: var(--border-radius);
            padding: var(--space-5);
            margin-bottom: var(--space-5);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-card {
                margin: var(--space-4);
                width: auto;
            }
            
            .login-container {
                padding: 100px var(--space-4) var(--space-8);
            }
            
            .login-form {
                padding: var(--space-6);
            }
            
            .btn-custom, .btn-outline-custom {
                display: block;
                width: 100%;
                margin-bottom: var(--space-3);
            }
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 90px var(--space-3) var(--space-6);
            }
            
            .login-form {
                padding: var(--space-5);
            }
            
            .government-notice {
                padding: var(--space-4);
            }
            
            .instructions-box {
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" class="coat-of-arms">
                <span>Government of Zambia - CDF System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia Coat of Arms" width="80" height="80" class="me-4 rounded" style="filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3)) brightness(1.05) contrast(1.1); object-fit: contain; border: 3px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); padding: 8px;">
                </div>
                <h4 class="mb-0">Password Recovery</h4>
                <p class="mb-0 opacity-75">Reset your account password</p>
            </div>
            <div class="login-form">
                <!-- Government Notice -->
                <div class="government-notice">
                    <h6 class="mb-2"><i class="fas fa-info-circle me-2 text-primary"></i>Official Government Notice</h6>
                    <p class="mb-0 small text-muted">This is a secure government system. Password reset requests are logged and monitored for security purposes.</p>
                </div>

                <!-- Back to Login Button -->
                <div class="mb-4">
                    <a href="login.php" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fs-5"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fs-5"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- Instructions -->
                <div class="instructions-box">
                    <h6 class="mb-3"><i class="fas fa-key me-2 text-info"></i>How to Reset Your Password</h6>
                    <ol class="small mb-0 ps-3">
                        <li>Enter your registered email address below</li>
                        <li>Check your email for password reset instructions</li>
                        <li>Click the reset link in the email</li>
                        <li>Create a new secure password</li>
                        <li>Return to login with your new password</li>
                    </ol>
                </div>

                <!-- Password Reset Form Section -->
                <div class="form-section">
                    <h5 class="section-title"><i class="fas fa-unlock-alt"></i>Reset Your Password</h5>
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="Enter your registered email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="form-text">Enter the email address associated with your account</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-custom btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Registration -->
                <div class="text-center mt-4">
                    <p class="text-muted mb-3">Don't have an account?</p>
                    <a href="register.php" class="btn btn-outline-custom">
                        <i class="fas fa-user-plus me-2"></i>Official Registration
                    </a>
                </div>

                <!-- Support Information -->
                <div class="mt-4 p-4 bg-light rounded">
                    <h6 class="mb-3"><i class="fas fa-question-circle me-2 text-primary"></i>Need Assistance?</h6>
                    <p class="mb-0 small">If you're having trouble resetting your password, please contact the CDF support team at <a href="mailto:support@cdf.gov.zm" class="text-primary fw-semibold">support@cdf.gov.zm</a> or call +260-211-123456.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 small">&copy; <?php echo date('Y'); ?> Government of the Republic of Zambia. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end align-items-center">
                        <span class="me-3 small">Official Government System</span>
                        <img src="Flag_of_Zambia.svg" alt="Zambia Flag" width="30" height="20">
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="disclaimer">
                    <p class="mb-2"><strong>Government Disclaimer:</strong> This is an official Government of Zambia system developed in compliance with the Constituency Development Fund Act Cap 324 and 2022 CDF Guidelines.</p>
                    <p class="mb-0"><strong>System Development:</strong> Web-based system developed by Samuel Sitemba (Student ID: 2200340), Zambia University College of Technology, under supervision of Ms. C. Mushikwa, 2025.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();

                if (!email) {
                    e.preventDefault();
                    alert('Please enter your email address.');
                    return false;
                }

                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                    return false;
                }

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending Instructions...';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds if form doesn't redirect
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            });
            
            // Navbar background change on scroll
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.backgroundColor = 'var(--primary-dark)';
                    navbar.style.padding = 'var(--space-2) 0';
                } else {
                    navbar.style.backgroundColor = '';
                    navbar.style.padding = '';
                }
            });
        });
    </script>
</body>
</html>

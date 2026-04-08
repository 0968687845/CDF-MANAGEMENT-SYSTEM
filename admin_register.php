<?php
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pageTitle = "Admin Registration - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Administrator registration for CDF Management System">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(rgba(13, 110, 253, 0.05), rgba(13, 110, 253, 0.1)), url('https://images.unsplash.com/photo-1516387938699-a93567ec168e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
        }
        .register-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        .register-form {
            padding: 30px;
        }
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .section-title {
            color: #0d6efd;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0d6efd;
            display: flex;
            align-items: center;
        }
        .section-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .admin-access-note {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .register-container {
                margin: 20px;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="register-container">
            <div class="register-header">
                <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia Coat of Arms" width="60" height="60" class="me-3">
                <h3><i class="fas fa-user-plus me-2"></i>Administrator Registration</h3>
                <p class="mb-0">Government of Zambia - CDF Management System</p>
            </div>
            <div class="register-form">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <!-- Back to Home Button -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                    <a href="register.php" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-users me-2"></i>View All Roles
                    </a>
                </div>
                
                <div class="admin-access-note">
                    <h6><i class="fas fa-info-circle me-2"></i>Administrator Registration</h6>
                    <p class="mb-0 small">Administrator accounts have full system access. Please provide accurate information.</p>
                </div>
                
                <form method="POST" action="auth.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="role" value="admin">
                    
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h5 class="section-title"><i class="fas fa-user-circle"></i>Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label required-field">First Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label required-field">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label required-field">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nrc" class="form-label required-field">National Registration Card (NRC) Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="nrc" name="nrc" placeholder="Enter your NRC number" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="dob" class="form-label required-field">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="dob" name="dob" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="form-section">
                        <h5 class="section-title"><i class="fas fa-lock"></i>Account Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label required-field">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-note">Minimum 4 characters, letters and numbers only</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label required-field">Department</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <input type="text" class="form-control" id="department" name="department" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label required-field">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength-container mt-2">
                                    <div class="progress">
                                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small id="passwordStrengthText" class="form-note">Password strength</small>
                                </div>
                                <div class="form-note">Minimum 8 characters with uppercase, lowercase, number, and special character</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div id="passwordMatch" class="form-note"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary w-100 btn-lg mb-3">
                        <i class="fas fa-user-plus me-2"></i>Register as Administrator
                    </button>
                </form>

                <!-- Already have an account -->
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Government of Zambia - CDF Management System</h6>
                    <p>By registering for this system, you agree to the following terms and conditions:</p>
                    <ol>
                        <li>All information provided must be accurate and truthful</li>
                        <li>Your data will be used in accordance with the Data Protection Act</li>
                        <li>Misuse of the system may result in legal action</li>
                        <li>You are responsible for maintaining the confidentiality of your account</li>
                        <li>The system administrators reserve the right to verify all information provided</li>
                        <li>Administrator accounts require special authorization and verification</li>
                    </ol>
                    <p><strong>Note:</strong> This is an official Government of Zambia system. False information may lead to prosecution under the laws of Zambia.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password strength indicator
            function checkPasswordStrength(password, strengthBar, strengthText) {
                let strength = 0;
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                const strengthPercent = (strength / 5) * 100;
                strengthBar.style.width = strengthPercent + '%';
                
                let color, text;
                switch(strength) {
                    case 0:
                    case 1:
                        color = '#dc3545';
                        text = 'Very Weak';
                        break;
                    case 2:
                        color = '#fd7e14';
                        text = 'Weak';
                        break;
                    case 3:
                        color = '#ffc107';
                        text = 'Medium';
                        break;
                    case 4:
                        color = '#20c997';
                        text = 'Strong';
                        break;
                    case 5:
                        color = '#198754';
                        text = 'Very Strong';
                        break;
                }
                
                strengthBar.style.backgroundColor = color;
                strengthText.textContent = text;
                strengthText.style.color = color;
            }

            // Password confirmation check
            function checkPasswordMatch(password, confirmPassword, matchElement) {
                if (password === '' && confirmPassword === '') {
                    matchElement.textContent = '';
                    matchElement.style.color = '';
                } else if (password === confirmPassword) {
                    matchElement.textContent = 'Passwords match';
                    matchElement.style.color = '#198754';
                } else {
                    matchElement.textContent = 'Passwords do not match';
                    matchElement.style.color = '#dc3545';
                }
            }

            // Setup password strength and confirmation
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('confirm_password');
            const matchElement = document.getElementById('passwordMatch');
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            const toggleButton = document.getElementById('togglePassword');

            if (passwordField && confirmField) {
                passwordField.addEventListener('input', function() {
                    checkPasswordStrength(this.value, strengthBar, strengthText);
                    checkPasswordMatch(this.value, confirmField.value, matchElement);
                });

                confirmField.addEventListener('input', function() {
                    checkPasswordMatch(passwordField.value, this.value, matchElement);
                });
            }

            // Toggle password visibility
            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
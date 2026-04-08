<?php
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pageTitle = "Official Registration - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Official registration portal for CDF Management System">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --success: #28a745;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        .navbar {
            background-color: var(--primary);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            transition: var(--transition);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem !important;
            border-radius: 4px;
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Registration Container */
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.6;
        }

        .register-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            z-index: 1;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .register-form {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .section-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .role-card {
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            height: 100%;
            text-decoration: none;
            color: inherit;
            border-radius: 10px;
            overflow: hidden;
        }

        .role-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .role-card .card-header {
            transition: var(--transition);
        }

        .role-card:hover .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
        }

        .role-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
            margin-left: 5px;
        }

        .approval-notice {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid var(--primary);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 12px 30px;
            color: var(--white);
            transition: var(--transition);
            font-weight: 600;
            border-radius: 6px;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: var(--white);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        }

        .btn-outline-custom {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        .role-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            transition: var(--transition);
        }

        .role-card:hover .role-icon {
            color: var(--white);
            transform: scale(1.1);
        }

        /* Footer */
        .footer {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 2rem 0 1rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 2rem;
        }

        .disclaimer {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .register-card {
                margin: 20px;
                width: auto;
            }
            
            .register-container {
                padding: 80px 10px 40px;
            }
            
            .role-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="45" height="45">
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
                </ul>
            </div>
        </div>
    </nav>

    <!-- Registration Container -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia Coat of Arms" width="70" height="70" class="me-3">
                    <div>
                        <h3><i class="fas fa-user-plus me-2"></i>Official Registration Portal</h3>
                        <p class="mb-0">Government of Zambia - CDF Management System</p>
                    </div>
                </div>
            </div>
            <div class="register-form">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <!-- Approval Notice -->
                <div class="approval-notice">
                    <h6><i class="fas fa-info-circle me-2 text-primary"></i>Registration Approval Process</h6>
                    <p class="mb-0 small text-muted">All M&E Officer registrations require administrative approval. You will receive an email notification once your account has been reviewed and activated.</p>
                </div>
                
                <!-- Back to Home Button -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
                
                <!-- Role Selection -->
                <div class="form-section">
                    <h5 class="section-title"><i class="fas fa-user-tag"></i>Select Your Role</h5>
                    <p class="mb-4 text-center">Please select your role to proceed with registration. Each role has different requirements and access levels.</p>
                    <div class="row justify-content-center">
                        <div class="col-lg-5 col-md-6 mb-4">
                            <a href="officer_register.php" class="role-card card text-decoration-none">
                                <div class="card-header text-center py-4">
                                    <i class="fas fa-user-tie role-icon"></i>
                                    <h4 class="mb-0">M&E Officer</h4>
                                </div>
                                <div class="card-body text-center">
                                    <p class="card-text">Government staff responsible for monitoring and evaluating CDF projects.</p>
                                    <div class="mt-3">
                                        <span class="badge bg-primary role-badge">Government Staff</span>
                                        <span class="badge bg-warning text-dark role-badge">Approval Required</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-5 col-md-6 mb-4">
                            <a href="beneficiary_register.php" class="role-card card text-decoration-none">
                                <div class="card-header text-center py-4">
                                    <i class="fas fa-users role-icon"></i>
                                    <h4 class="mb-0">Beneficiary</h4>
                                </div>
                                <div class="card-body text-center">
                                    <p class="card-text">Community members receiving CDF support for projects and initiatives.</p>
                                    <div class="mt-3">
                                        <span class="badge bg-success role-badge">Community Member</span>
                                        <span class="badge bg-info role-badge">Direct Access</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Role Comparison -->
                <div class="form-section">
                    <h5 class="section-title"><i class="fas fa-balance-scale"></i>Role Comparison</h5>
                    <div class="row justify-content-center">
                        <div class="col-md-10">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Feature</th>
                                            <th class="text-center">M&E Officer</th>
                                            <th class="text-center">Beneficiary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Access Level</td>
                                            <td class="text-center">Monitoring & Evaluation</td>
                                            <td class="text-center">Project Management</td>
                                        </tr>
                                        <tr>
                                            <td>Approval Process</td>
                                            <td class="text-center"><span class="badge bg-warning text-dark">Admin Approval Required</span></td>
                                            <td class="text-center"><span class="badge bg-success">Immediate Access</span></td>
                                        </tr>
                                        <tr>
                                            <td>Primary Function</td>
                                            <td class="text-center">Oversee project compliance and reporting</td>
                                            <td class="text-center">Manage funded projects and submit reports</td>
                                        </tr>
                                        <tr>
                                            <td>System Access</td>
                                            <td class="text-center">Multiple constituencies</td>
                                            <td class="text-center">Assigned projects only</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Already have an account -->
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php" class="text-primary fw-bold">Login here</a></p>
                </div>

                <!-- Support Information -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-question-circle me-2 text-primary"></i>Need Help with Registration?</h6>
                    <p class="mb-0 small">If you encounter any issues during registration or have questions about the required documents, please contact the CDF support team at <a href="mailto:support@cdf.gov.zm" class="text-primary">support@cdf.gov.zm</a> or call +260-211-123456 during business hours.</p>
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
                        <span class="me-3 small text-white-50">Official Government System</span>
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

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar background change on scroll
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.backgroundColor = 'var(--primary-dark)';
                    navbar.style.padding = '0.5rem 0';
                } else {
                    navbar.style.backgroundColor = 'var(--primary)';
                    navbar.style.padding = '0.8rem 0';
                }
            });
        });
    </script>
</body>
</html>
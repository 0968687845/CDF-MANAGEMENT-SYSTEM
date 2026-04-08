<?php
session_start();
require_once 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

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
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Government of Zambia - CDF Management System | Official Portal</title>
    <meta name="description" content="Official Government of Zambia Constituency Development Fund Management System for monitoring small projects targeting women and youth empowerment">
    <meta name="keywords" content="Zambia Government, CDF, Constituency Development Fund, Women Empowerment, Youth Development, Project Monitoring">
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
            overflow-x: hidden;
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

        .navbar-brand img {
            filter: brightness(0) invert(1);
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

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow);
            border-radius: 8px;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.6;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-logo {
            max-height: 120px;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .hero-description {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 700px;
            opacity: 0.85;
        }

        .btn-hero {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-hero:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-outline-hero {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-outline-hero:hover {
            background-color: var(--white);
            color: var(--primary);
        }

        /* Stats Section */
        .stats-section {
            background-color: var(--white);
            padding: 4rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
            border-radius: 10px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-text {
            font-size: 1rem;
            color: var(--gray);
        }

        /* Features Section */
        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 3rem;
        }

        .features-section {
            padding: 5rem 0;
            background-color: var(--light);
        }

        .feature-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            height: 100%;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .feature-description {
            color: var(--gray);
        }

        /* About Section */
        .about-section {
            padding: 5rem 0;
            background-color: var(--white);
        }

        .about-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .about-text {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .highlight-box {
            background-color: var(--primary-light);
            color: var(--white);
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        /* Footer */
        .footer {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 4rem 0 2rem;
        }

        .footer-logo {
            max-height: 60px;
            margin-bottom: 1rem;
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.7rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--white);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            margin-top: 3rem;
        }

        .disclaimer {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.3rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .btn-hero, .btn-outline-hero {
                display: block;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .btn-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                <span>Government of Zambia - CDF System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Secure Login
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia Coat of Arms" class="hero-logo">
                <h1 class="hero-title">Government of the Republic of Zambia</h1>
                <h2 class="hero-subtitle">Constituency Development Fund Management System</h2>
                <p class="hero-description">
                    Official web-based platform for monitoring and evaluating small Constituency Development Fund projects 
                    in compliance with the CDF Act Cap 324 and 2022 CDF Guidelines
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="register.php" class="btn btn-hero">
                        <i class="fas fa-user-plus me-2"></i>Official Registration
                    </a>
                    <a href="login.php" class="btn btn-outline-hero">
                        <i class="fas fa-shield-alt me-2"></i>Secure Access
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number">156</div>
                        <div class="stat-text">Constituencies Covered</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-text">CDF Guidelines Compliant</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-text">Secure Monitoring</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number">2022</div>
                        <div class="stat-text">Latest Standards</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">System Features</h2>
            <p class="section-subtitle">
                Comprehensive tools designed to streamline CDF project management and monitoring
            </p>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <h4 class="feature-title">Web-Based Interface</h4>
                        <p class="feature-description">
                            Intuitive web interface accessible through any modern browser, designed for users with varying levels of digital literacy.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="feature-title">Real-Time Project Tracking</h4>
                        <p class="feature-description">
                            Monitor progress, budget usage, and outcomes in real-time with comprehensive dashboards for all stakeholder types.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="feature-title">Location-Based Monitoring</h4>
                        <p class="feature-description">
                            GPS functionality for project site verification and location-based reporting of geographically dispersed small projects.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h4 class="feature-title">Multimedia Documentation</h4>
                        <p class="feature-description">
                            Capture photos, videos, and audio recordings to comprehensively document project progress directly from field sites.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="feature-title">Role-Based Access Control</h4>
                        <p class="feature-description">
                            Different access levels for administrators, M&E officers, and beneficiaries with context-sensitive interfaces.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="feature-title">Secure Authentication</h4>
                        <p class="feature-description">
                            Multi-factor authentication with biometric support where available, ensuring data security and user verification.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <h2 class="section-title">About CDF Management System</h2>
                <p class="about-text">
                    The Constituency Development Fund (CDF) in Zambia plays a critical role in promoting local development 
                    and supporting marginalized groups, particularly women and youth through small projects.
                </p>
                <p class="about-text">
                    This web-based management system addresses key operational needs through continuous monitoring and 
                    evaluation frameworks, streamlined web-based processes, enhanced transparency in project tracking, 
                    and robust accountability mechanisms.
                </p>
                
                <div class="highlight-box">
                    <h4 class="mb-3">Web-Based Solution</h4>
                    <p class="mb-0">
                        A comprehensive web-based CDF Management System that provides accessible monitoring and evaluation 
                        capabilities, promoting efficient project oversight through modern browser technology.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" class="footer-logo me-3">
                        <div>
                            <h5 class="mb-0">Government of Zambia</h5>
                            <small>CDF Management System</small>
                        </div>
                    </div>
                    <p>
                        Official government portal for monitoring Constituency Development Fund projects 
                        in compliance with national legislation and international standards.
                    </p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title">Government Links</h6>
                    <ul class="footer-links">
                        <li><a href="https://www.gov.zm" target="_blank">Gov.zm Portal</a></li>
                        <li><a href="#compliance">Legal Framework</a></li>
                        <li><a href="#contact">Official Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="footer-title">System Access</h6>
                    <ul class="footer-links">
                        <li><a href="login.php">Secure Login</a></li>
                        <li><a href="register.php">Official Registration</a></li>
                        <li><a href="#">Admin Portal</a></li>
                        <li><a href="#">Help & Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6 class="footer-title">Legal Information</h6>
                    <div class="small">
                        <p class="mb-2"><i class="fas fa-gavel me-2"></i>Constituency Development Fund Act Cap 324</p>
                        <p class="mb-2"><i class="fas fa-book me-2"></i>2022 CDF Guidelines - Ministry of Local Government</p>
                        <p class="mb-2"><i class="fas fa-shield-alt me-2"></i>Data Protection & Privacy Compliant</p>
                        <p class="mb-0"><i class="fas fa-balance-scale me-2"></i>Anti-Corruption Commission Oversight</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2025 Government of the Republic of Zambia. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex justify-content-md-end align-items-center">
                            <span class="me-3 small">Official Government System</span>
                            <img src="Flag_of_Zambia.svg" alt="Zambia Flag" width="30" height="20">
                        </div>
                    </div>
                </div>
                
                <!-- Government Disclaimer -->
                <div class="disclaimer mt-4">
                    <p class="mb-2"><strong>Government Disclaimer:</strong> This is an official Government of Zambia system developed in compliance with the Constituency Development Fund Act Cap 324 and 2022 CDF Guidelines.</p>
                    <p class="mb-0"><strong>System Development:</strong> Web-based system developed by Samuel Sitemba (Student ID: 2200340), Zambia University College of Technology, under supervision of Ms. C. Mushikwa, 2025.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>
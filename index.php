<?php
session_start();
require_once 'functions.php';

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

        /* Enhanced Typography Hierarchy - Maximum Contrast */
        h1, .h1 {
            font-size: var(--text-5xl);
            font-weight: 800;
            line-height: 1.1;
            color: var(--white);
            margin-bottom: var(--space-6);
            letter-spacing: -0.025em;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
        }

        h2, .h2 {
            font-size: var(--text-4xl);
            font-weight: 700;
            line-height: 1.2;
            color: var(--primary-dark);
            margin-bottom: var(--space-5);
            letter-spacing: -0.02em;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        h3, .h3 {
            font-size: var(--text-3xl);
            font-weight: 600;
            line-height: 1.3;
            color: var(--primary);
            margin-bottom: var(--space-4);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        h4, .h4 {
            font-size: var(--text-2xl);
            font-weight: 600;
            line-height: 1.4;
            color: var(--gray-800);
            margin-bottom: var(--space-4);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        h5, .h5 {
            font-size: var(--text-xl);
            font-weight: 600;
            line-height: 1.4;
            color: var(--gray-800);
            margin-bottom: var(--space-3);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        h6, .h6 {
            font-size: var(--text-lg);
            font-weight: 600;
            line-height: 1.5;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        p {
            margin-bottom: var(--space-4);
            color: var(--gray-700);
            line-height: 1.7;
            font-size: var(--text-base);
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        }

        .lead {
            font-size: var(--text-xl);
            font-weight: 500;
            color: var(--white);
            line-height: 1.6;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .text-muted {
            color: var(--gray-600) !important;
            opacity: 0.9;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
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

        .nav-link i {
            font-size: 1.1em;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
            transition: var(--transition);
        }

        .nav-link:hover i {
            transform: scale(1.2);
            color: var(--secondary) !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius);
            padding: var(--space-2) 0;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .dropdown-item {
            padding: var(--space-3) var(--space-5);
            transition: var(--transition);
            color: var(--gray-800);
            font-weight: 500;
            text-shadow: none;
        }

        .dropdown-item:hover {
            background: var(--primary-gradient);
            color: var(--white);
            transform: translateX(5px);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.9);
            padding: var(--space-2) var(--space-3);
            transition: var(--transition);
        }

        .navbar-toggler:hover {
            border-color: var(--white);
            background: rgba(255, 255, 255, 0.1);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='3' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-20) 0 var(--space-16);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-top: 76px;
        }

        .hero-section::before {
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
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        /* Hero Logo - Enhanced Visibility */
        .hero-logo {
            max-height: 140px;
            margin-bottom: var(--space-6);
            filter: 
                drop-shadow(0 8px 16px rgba(0, 0, 0, 0.4))
                brightness(1.08)
                contrast(1.15);
            border: 4px solid rgba(233, 185, 73, 0.4);
            border-radius: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            transition: var(--transition);
            object-fit: contain;
        }

        .hero-logo:hover {
            transform: scale(1.08);
            filter: 
                drop-shadow(0 12px 24px rgba(0, 0, 0, 0.5))
                brightness(1.15)
                contrast(1.25);
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 40px rgba(233, 185, 73, 0.3);
        }

        .hero-title {
            font-size: var(--text-5xl);
            font-weight: 800;
            margin-bottom: var(--space-4);
            line-height: 1.1;
            text-shadow: 0 3px 8px rgba(0, 0, 0, 0.7);
            color: var(--white) !important;
        }

        .hero-subtitle {
            font-size: var(--text-2xl);
            font-weight: 600;
            margin-bottom: var(--space-5);
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.6);
            color: var(--white) !important;
        }

        .hero-description {
            font-size: var(--text-lg);
            margin-bottom: var(--space-8);
            max-width: 700px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            margin-left: auto;
            margin-right: auto;
            color: var(--white) !important;
        }

        /* Enhanced Buttons */
        .btn-hero {
            background: var(--secondary-gradient);
            color: var(--dark) !important;
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

        .btn-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: var(--transition-slow);
        }

        .btn-hero:hover::before {
            left: 100%;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            background: var(--secondary-gradient);
        }

        .btn-outline-hero {
            background: transparent;
            color: var(--white) !important;
            border: 2px solid rgba(255, 255, 255, 0.9);
            padding: var(--space-4) var(--space-6);
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-outline-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: var(--transition);
            z-index: -1;
        }

        .btn-outline-hero:hover::before {
            width: 100%;
        }

        .btn-outline-hero:hover {
            color: var(--white) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
            border-color: var(--white);
        }

        /* Stats Section */
        .stats-section {
            background-color: var(--white);
            padding: var(--space-16) 0;
            box-shadow: var(--shadow);
        }

        .stat-card {
            text-align: center;
            padding: var(--space-8) var(--space-4);
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
            background: var(--white);
            box-shadow: var(--shadow);
            border-top: 5px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .stat-number {
            font-size: var(--text-4xl);
            font-weight: 800;
            color: var(--primary);
            margin-bottom: var(--space-3);
            line-height: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .stat-text {
            font-size: var(--text-lg);
            color: var(--gray-700);
            font-weight: 600;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        }

        /* Features Section */
        .section-title {
            font-size: var(--text-4xl);
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: var(--space-4);
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-subtitle {
            font-size: var(--text-lg);
            color: var(--gray-600);
            text-align: center;
            max-width: 700px;
            margin: 0 auto var(--space-12);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .features-section {
            padding: var(--space-20) 0;
            background-color: var(--light);
        }

        .feature-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--space-10) var(--space-6);
            text-align: center;
            height: 100%;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-top: 5px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: var(--space-6);
            transition: var(--transition);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }

        .feature-title {
            font-size: var(--text-xl);
            font-weight: 700;
            margin-bottom: var(--space-4);
            color: var(--primary-dark);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .feature-description {
            color: var(--gray-700);
            line-height: 1.6;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        }

        /* About Section */
        .about-section {
            padding: var(--space-20) 0;
            background-color: var(--white);
        }

        .about-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .about-text {
            font-size: var(--text-lg);
            line-height: 1.7;
            margin-bottom: var(--space-6);
            color: var(--gray-700);
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
        }

        .highlight-box {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-8);
            border-radius: var(--border-radius-lg);
            margin-top: var(--space-8);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .highlight-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--secondary-gradient);
        }

        .highlight-box h4 {
            color: var(--white);
            margin-bottom: var(--space-4);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }

        .highlight-box p {
            color: var(--white);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        /* Enhanced Footer - Maximum Visibility */
        .footer {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-16) 0 var(--space-8);
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

        /* Footer Logo - Enhanced Visibility */
        .footer-logo {
            max-height: 70px;
            margin-bottom: var(--space-4);
            filter: 
                drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4))
                brightness(1.08)
                contrast(1.15);
            border: 2px solid rgba(233, 185, 73, 0.35);
            border-radius: 8px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            transition: var(--transition);
            object-fit: contain;
        }

        .footer-logo:hover {
            transform: scale(1.08);
            filter: 
                drop-shadow(0 6px 12px rgba(0, 0, 0, 0.5))
                brightness(1.15)
                contrast(1.25);
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.18);
        }

        .footer-title {
            font-size: var(--text-xl);
            font-weight: 700;
            margin-bottom: var(--space-5);
            color: var(--white);
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.7);
            position: relative;
            display: inline-block;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: var(--space-3);
            transition: var(--transition);
        }

        .footer-links li:hover {
            transform: translateX(5px);
        }

        .footer-links a {
            color: var(--white) !important;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-weight: 500;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
            padding: var(--space-2) 0;
            border-bottom: 1px solid transparent;
        }

        .footer-links a:hover {
            color: var(--secondary) !important;
            border-bottom: 1px solid var(--secondary);
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        .footer-links i {
            font-size: 1.1em;
            width: 20px;
            text-align: center;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.4));
            transition: var(--transition);
        }

        .footer-links a:hover i {
            transform: scale(1.2);
            color: var(--secondary) !important;
        }

        /* Footer text elements - ensuring all are visible */
        .footer h5,
        .footer h6,
        .footer p,
        .footer span,
        .footer small,
        .footer div {
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        .footer .small {
            font-size: var(--text-sm);
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
            line-height: 1.6;
        }

        .footer .small p {
            margin-bottom: var(--space-3);
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        .footer .small i {
            color: var(--secondary);
            margin-right: var(--space-2);
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.4));
        }

        .footer-bottom {
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            padding-top: var(--space-8);
            margin-top: var(--space-12);
            position: relative;
        }

        .footer-bottom p {
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
            margin-bottom: var(--space-2);
            font-weight: 500;
        }

        .footer-bottom .text-muted {
            color: rgba(255, 255, 255, 0.9) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        .footer-bottom .mb-0 {
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        .disclaimer {
            background: rgba(0, 0, 0, 0.3);
            padding: var(--space-6);
            border-radius: var(--border-radius);
            margin-top: var(--space-8);
            font-size: var(--text-sm);
            border-left: 4px solid var(--secondary);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .disclaimer p {
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
            margin-bottom: var(--space-3);
            font-weight: 500;
            line-height: 1.6;
        }

        .disclaimer strong {
            color: var(--secondary);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        }

        .disclaimer .mb-2 {
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }

        .disclaimer .mb-0 {
            color: var(--white) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                padding: var(--space-16) 0 var(--space-12);
                text-align: center;
            }
            
            .hero-title {
                font-size: var(--text-4xl);
            }
            
            .hero-subtitle {
                font-size: var(--text-xl);
            }
            
            .section-title {
                font-size: var(--text-3xl);
            }
            
            .btn-hero, .btn-outline-hero {
                display: block;
                width: 100%;
                margin-bottom: var(--space-4);
            }
            
            .btn-group {
                width: 100%;
            }
            
            .stat-number {
                font-size: var(--text-3xl);
            }
            
            .navbar-brand {
                font-size: var(--text-base);
            }
            
            .nav-link {
                font-size: var(--text-sm);
                padding: var(--space-3) var(--space-3) !important;
            }
            
            .coat-of-arms {
                width: 40px !important;
                height: 40px !important;
            }
            
            .hero-logo {
                max-height: 120px;
            }
            
            .footer {
                text-align: center;
            }
            
            .footer-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .footer-links {
                text-align: center;
            }
            
            .footer-links a {
                justify-content: center;
            }
            
            .footer .small {
                text-align: center;
            }
            
            .footer-logo {
                max-height: 60px;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 var(--space-4);
            }
            
            .hero-title {
                font-size: var(--text-3xl);
            }
            
            .hero-subtitle {
                font-size: var(--text-lg);
            }
            
            .feature-card {
                padding: var(--space-8) var(--space-4);
            }
            
            .navbar-brand span {
                font-size: var(--text-sm);
            }
            
            .coat-of-arms {
                width: 35px !important;
                height: 35px !important;
            }
            
            .hero-logo {
                max-height: 100px;
            }
            
            .footer {
                padding: var(--space-12) 0 var(--space-6);
            }
            
            .footer-title {
                font-size: var(--text-lg);
            }
            
            .footer-links a {
                font-size: var(--text-sm);
            }
            
            .disclaimer {
                padding: var(--space-4);
            }
            
            .footer-logo {
                max-height: 50px;
            }
        }

        /* High contrast mode for all sections */
        @media (prefers-contrast: high) {
            :root {
                --primary: #000080;
                --secondary: #ffa500;
                --gray-600: #000000;
                --gray-900: #000000;
            }
            
            .navbar {
                background: #000080 !important;
                border-bottom: 4px solid #ffa500;
            }
            
            .navbar-brand {
                font-weight: 900;
                text-shadow: 0 2px 4px #000000;
            }
            
            .nav-link {
                font-weight: 700;
                text-shadow: 0 2px 4px #000000;
            }
            
            .coat-of-arms {
                filter: brightness(0) invert(1) drop-shadow(0 3px 6px #000000);
                border: 3px solid #ffffff;
            }
            
            .hero-title, 
            .hero-subtitle, 
            .hero-description {
                color: #ffffff !important;
                text-shadow: 0 3px 6px #000000;
            }
            
            .hero-logo {
                filter: drop-shadow(0 4px 8px #000000) brightness(1.2) contrast(1.5);
                border: 3px solid #ffffff;
            }
            
            .footer {
                background: #000080 !important;
                border-top: 4px solid #ffa500;
            }
            
            .footer-logo {
                filter: brightness(0) invert(1) drop-shadow(0 3px 6px #000000);
                border: 3px solid #ffffff;
            }
            
            .footer-title {
                text-shadow: 0 2px 4px #000000;
                font-weight: 900;
            }
            
            .footer-links a {
                text-shadow: 0 2px 4px #000000;
                font-weight: 700;
            }
            
            .footer .small,
            .footer-bottom p {
                text-shadow: 0 1px 3px #000000;
                font-weight: 600;
            }
            
            .disclaimer {
                background: #000000;
                border-left: 6px solid #ffa500;
            }
        }

        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
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

        .stat-card,
        .feature-card {
            animation: fadeInUp 0.6s ease-out;
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
                navbar.style.backgroundColor = '';
                navbar.style.padding = '';
            }
        });
    </script>
</body>
</html>
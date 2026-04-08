<?php
require_once '../functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$projects = getBeneficiaryProjects($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id']);

// Calculate project progress the same way as beneficiary_dashboard.php
if (!empty($projects)) {
    foreach ($projects as &$project) {
        $ml_result = getRecommendedProgressPercentage($project['id'] ?? 0);
        $automated_progress = isset($ml_result['recommended']) ? intval($ml_result['recommended']) : 0;

        if ($automated_progress == 0 && isset($project['progress'])) {
            $automated_progress = intval($project['progress']);
        }

        $project['progress'] = $automated_progress;
        $project['total_expenses'] = getTotalProjectExpenses($project['id'] ?? 0);
        $project['budget_utilization'] = ($project['budget'] > 0) ?
            round(($project['total_expenses'] / $project['budget']) * 100, 1) : 0;
    }
    unset($project);
}

// Handle project status filter
$status_filter = $_GET['status'] ?? 'all';
if ($status_filter !== 'all') {
    $projects = array_filter($projects, function($project) use ($status_filter) {
        return $project['status'] === $status_filter;
    });
}

// Handle search
$search_term = $_GET['search'] ?? '';
if (!empty($search_term)) {
    $projects = array_filter($projects, function($project) use ($search_term) {
        return stripos($project['title'], $search_term) !== false || 
               stripos($project['description'], $search_term) !== false;
    });
}

$pageTitle = "My Projects - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Beneficiary projects management - CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Color System */
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            
            /* Neutral Colors */
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
            
            /* Semantic Colors */
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
            
            /* Design Tokens */
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 6px 18px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-hover: 0 12px 35px rgba(0, 0, 0, 0.18);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            
            --border-radius-sm: 6px;
            --border-radius: 10px;
            --border-radius-lg: 15px;
            --border-radius-xl: 20px;
            
            /* Typography Scale */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            --text-4xl: 2.25rem;
            
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
        }

        /* Reset and Base Styles */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
            line-height: 1.6;
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
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(26, 78, 138, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(233, 185, 73, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Typography Hierarchy */
        h1, .h1 {
            font-size: var(--text-4xl);
            font-weight: 800;
            line-height: 1.2;
            color: var(--primary-dark);
            margin-bottom: var(--space-6);
            letter-spacing: -0.025em;
        }

        h2, .h2 {
            font-size: var(--text-3xl);
            font-weight: 700;
            line-height: 1.3;
            color: var(--primary-dark);
            margin-bottom: var(--space-5);
            letter-spacing: -0.02em;
        }

        h3, .h3 {
            font-size: var(--text-2xl);
            font-weight: 600;
            line-height: 1.4;
            color: var(--primary);
            margin-bottom: var(--space-4);
        }

        h4, .h4 {
            font-size: var(--text-xl);
            font-weight: 600;
            line-height: 1.4;
            color: var(--gray-800);
            margin-bottom: var(--space-4);
        }

        h5, .h5 {
            font-size: var(--text-lg);
            font-weight: 600;
            line-height: 1.5;
            color: var(--gray-800);
            margin-bottom: var(--space-3);
        }

        h6, .h6 {
            font-size: var(--text-base);
            font-weight: 600;
            line-height: 1.5;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        p {
            margin-bottom: var(--space-4);
            color: var(--gray-700);
            line-height: 1.7;
        }

        .lead {
            font-size: var(--text-lg);
            font-weight: 400;
            color: var(--gray-600);
            line-height: 1.6;
        }

        .text-muted {
            color: var(--gray-600) !important;
            opacity: 0.9;
        }

        /* Enhanced Text Formatting */
        strong, b {
            font-weight: 600;
            color: var(--gray-900);
        }

        em, i {
            font-style: italic;
            color: var(--gray-700);
        }

        small, .small {
            font-size: var(--text-sm);
            color: var(--gray-600);
        }

        /* Navigation */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow);
            padding: var(--space-3) 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: var(--space-3);
            transition: var(--transition);
            font-size: var(--text-lg);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .navbar-brand img {
            height: 40px;
            width: auto;
            object-fit: contain;
            filter: brightness(1.05) contrast(1.1) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            filter: brightness(1.15) contrast(1.2) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.3));
            transform: scale(1.05);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: var(--space-3) var(--space-4) !important;
            border-radius: var(--border-radius-sm);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
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
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius);
            padding: var(--space-2) 0;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dropdown-item {
            padding: var(--space-3) var(--space-5);
            transition: var(--transition);
            font-weight: 500;
            color: var(--gray-800);
            font-size: var(--text-base);
        }

        .dropdown-item:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateX(8px);
        }

        .dropdown-item.active {
            background: var(--primary-gradient);
            color: var(--white);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            padding: 0 var(--space-6);
            margin: 0 auto;
        }

        /* Enhanced Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: var(--space-6);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .content-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
            border-color: var(--primary-light);
        }

        .card-header {
            background: var(--light-gradient);
            border-bottom: 3px solid var(--primary);
            padding: var(--space-6);
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--text-lg);
        }

        .card-header h5 i {
            color: var(--secondary);
            font-size: 1.2em;
        }

        .card-body {
            padding: var(--space-8);
        }

        /* Stats Cards */
        .stats-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: var(--space-6);
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(26, 78, 138, 0.05), transparent);
            transition: var(--transition-slow);
        }

        .stats-card:hover::before {
            left: 100%;
        }

        .stats-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
        }

        .stats-number {
            font-size: var(--text-3xl);
            font-weight: 800;
            color: var(--primary);
            margin-bottom: var(--space-2);
            line-height: 1;
        }

        .stats-title {
            font-size: var(--text-base);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--space-1);
        }

        .stats-subtitle {
            font-size: var(--text-sm);
            color: var(--gray-600);
        }

        /* Project Cards */
        .project-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: var(--space-6);
            transition: var(--transition);
            overflow: hidden;
            background: var(--white);
            position: relative;
        }

        .project-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
        }

        .project-card .card-body {
            padding: var(--space-6);
        }

        .project-status-badge {
            position: absolute;
            top: var(--space-4);
            right: var(--space-4);
            z-index: 2;
            font-size: var(--text-xs);
            font-weight: 700;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--border-radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .project-card .card-title {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: var(--space-3);
            line-height: 1.3;
        }

        .project-card .card-text {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: var(--space-4);
        }

        /* Progress Bars */
        .progress-section {
            margin: var(--space-5) 0;
        }

        .progress {
            height: 10px;
            border-radius: var(--border-radius-sm);
            background: var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .progress-bar {
            border-radius: var(--border-radius-sm);
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: var(--space-6);
            box-shadow: var(--shadow);
            margin-bottom: var(--space-6);
            border: 1px solid var(--gray-200);
        }

        /* Form Controls */
        .form-control, .form-select {
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            padding: var(--space-3) var(--space-4);
            transition: var(--transition);
            font-size: var(--text-base);
            background: var(--white);
            box-shadow: var(--shadow-sm);
            font-family: inherit;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.3rem rgba(26, 78, 138, 0.15);
            transform: translateY(-1px);
            background: var(--white);
            outline: none;
        }

        /* Buttons */
        .btn-primary-custom {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            padding: var(--space-3) var(--space-5);
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition-slow);
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            background: var(--secondary-gradient);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: var(--space-3) var(--space-5);
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
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
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Badge Colors */
        .badge-completed { 
            background: var(--success); 
            color: var(--white);
        }
        .badge-in-progress { 
            background: var(--warning); 
            color: var(--dark);
        }
        .badge-delayed { 
            background: var(--danger); 
            color: var(--white);
        }
        .badge-planning { 
            background: var(--info); 
            color: var(--white);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: var(--text-xs);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: var(--space-16) var(--space-8);
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: var(--space-6);
            color: var(--gray-400);
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: var(--text-2xl);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--space-4);
        }

        .empty-state p {
            font-size: var(--text-lg);
            color: var(--gray-600);
            margin-bottom: var(--space-8);
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: var(--space-8);
            margin-top: var(--space-12);
            border-top: 3px solid var(--primary);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            :root {
                --text-4xl: 2rem;
                --text-3xl: 1.75rem;
                --text-2xl: 1.5rem;
                --text-xl: 1.25rem;
                --text-lg: 1.125rem;
            }
            
            .container {
                padding: 0 var(--space-4);
            }
            
            .card-body {
                padding: var(--space-6);
            }
            
            .stats-card {
                margin-bottom: var(--space-4);
            }
            
            .project-card:hover {
                transform: translateY(-2px);
            }
            
            .empty-state {
                padding: var(--space-12) var(--space-6);
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: var(--space-4);
            }
            
            .project-card .card-body {
                padding: var(--space-4);
            }
            
            .filter-section {
                padding: var(--space-4);
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                padding: var(--space-3);
                width: 100%;
                margin-bottom: var(--space-2);
            }
        }

        /* Selection Styling */
        ::selection {
            background: rgba(26, 78, 138, 0.2);
            color: var(--primary-dark);
            text-shadow: none;
        }

        ::-moz-selection {
            background: rgba(26, 78, 138, 0.2);
            color: var(--primary-dark);
            text-shadow: none;
        }

        /* Enhanced Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: var(--border-radius-sm);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: var(--border-radius-sm);
            border: 2px solid var(--gray-100);
            transition: var(--transition);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        /* Print Optimization */
        @media print {
            .navbar,
            .btn,
            .filter-section,
            .dashboard-footer {
                display: none !important;
            }
            
            .content-card,
            .project-card,
            .stats-card {
                box-shadow: none !important;
                border: 1px solid var(--gray-400) !important;
                break-inside: avoid;
            }
        }

        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            :root {
                --primary: #000080;
                --secondary: #ffa500;
                --gray-600: #000000;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Beneficiary Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../beneficiary_dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="index.php">
                                <i class="fas fa-project-diagram me-2"></i>My Projects
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="../support/help.php">
                                <i class="fas fa-question-circle me-2"></i>Help
                            </a></li>
                            <li><a class="dropdown-item" href="../financial/expenses.php">
                                <i class="fas fa-receipt me-2"></i>My Expenses
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="../communication/notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="../communication/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="../communication/notifications.php">No new notifications</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
                                            <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                                            <small class="text-muted">CDF Beneficiary</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../settings/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="../settings/system.php">
                                <i class="fas fa-cog me-2"></i>Account Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?logout=true">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5 mb-5" style="margin-top: 100px !important;">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-2">My Projects</h1>
                <p class="text-muted mb-0">Manage and track all your CDF projects</p>
            </div>
            <a href="setup.php" class="btn btn-primary-custom">
                <i class="fas fa-plus-circle me-2"></i>New Project
            </a>
        </div>

        <!-- Statistics Overview -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count(getBeneficiaryProjects($_SESSION['user_id'])); ?></div>
                    <div class="stats-title">Total Projects</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number text-success">
                        <?php echo count(array_filter(getBeneficiaryProjects($_SESSION['user_id']), function($p) { return $p['status'] === 'completed'; })); ?>
                    </div>
                    <div class="stats-title">Completed</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number text-warning">
                        <?php echo count(array_filter(getBeneficiaryProjects($_SESSION['user_id']), function($p) { return $p['status'] === 'in-progress'; })); ?>
                    </div>
                    <div class="stats-title">In Progress</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-number text-primary">
                        <?php echo count(array_filter(getBeneficiaryProjects($_SESSION['user_id']), function($p) { return $p['status'] === 'planning'; })); ?>
                    </div>
                    <div class="stats-title">Planning</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="content-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search projects by title or description..." 
                                   value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-md-end gap-2">
                            <select class="form-select" onchange="window.location.href='?status='+this.value+'&search=<?php echo urlencode($search_term); ?>'" style="max-width: 200px;">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="planning" <?php echo $status_filter === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                <option value="in-progress" <?php echo $status_filter === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="delayed" <?php echo $status_filter === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                            </select>
                            <?php if ($status_filter !== 'all' || !empty($search_term)): ?>
                                <a href="?" class="btn btn-outline-custom">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Grid -->
        <?php if (count($projects) > 0): ?>
            <div class="row">
                <?php foreach ($projects as $project): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="project-card card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($project['title'] ?? 'Untitled Project'); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars(substr($project['description'] ?? 'No description available', 0, 100)); ?><?php if (strlen($project['description'] ?? '') > 100): ?>...<?php endif; ?></p>
                                </div>
                                <span class="badge project-status-badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                    <?php echo htmlspecialchars($project['status'] ?? 'Unknown'); ?>
                                </span>
                            </div>
                            <div class="progress-section">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>
                                        Progress: <?php echo $project['progress'] ?? 0; ?>% 
                                        <span class="text-muted small">(Automated)</span>
                                    </small>
                                    <small>Budget: ZMW <?php echo number_format($project['budget'] ?? 0, 2); ?></small>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?php echo $project['status'] ?? 'planning'; ?>" 
                                         style="width: <?php echo $project['progress'] ?? 0; ?>%">
                                    </div>
                                </div>
                                <div class="budget-utilization mt-2">
                                    <small>
                                        Budget used: 
                                        <?php 
                                        $utilization = $project['budget_utilization'] ?? 0;
                                        if ($utilization >= 90) {
                                            echo '<span class="text-danger">' . $utilization . '%</span>';
                                        } elseif ($utilization >= 75) {
                                            echo '<span class="text-warning">' . $utilization . '%</span>';
                                        } else {
                                            echo '<span class="text-success">' . $utilization . '%</span>';
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Started: <?php echo date('M j, Y', strtotime($project['start_date'] ?? 'now')); ?>
                                </small>
                                <div class="btn-group">
                                    <a href="details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                    <a href="../progress/updates.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-sync-alt me-1"></i>Update
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="content-card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-project-diagram"></i>
                        <h3>No Projects Found</h3>
                        <p class="mb-4">
                            <?php if ($status_filter !== 'all' || !empty($search_term)): ?>
                                No projects match your current filters. <a href="?" class="text-primary">Clear filters</a> to see all projects.
                            <?php else: ?>
                                You haven't created any projects yet. Get started by creating your first project.
                            <?php endif; ?>
                        </p>
                        <a href="setup.php" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>Create Your First Project
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <img src="../coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3">
                    <div>
                        <h5 class="mb-0">CDF Management System</h5>
                        <p class="mb-0 text-muted">Government of the Republic of Zambia</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> - All Rights Reserved</p>
                <p class="mb-0 text-muted">Version 2.5.1 | <span id="serverTime"><?php echo date('H:i:s'); ?></span></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            });
        });
    </script>
</body>
</html>
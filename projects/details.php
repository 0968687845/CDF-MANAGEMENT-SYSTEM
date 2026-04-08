<?php
require_once '../functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Get project ID from URL
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    $_SESSION['error'] = 'Project ID is required';
    redirect('index.php');
}

// Fetch project details
$project = getProjectById($project_id);
if (!$project) {
    $_SESSION['error'] = 'Project not found';
    redirect('index.php');
}

// Verify project belongs to current beneficiary
if ($project['beneficiary_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = 'Access denied';
    redirect('index.php');
}

// Fetch project progress updates
$progress_updates = getProjectProgress($project_id);

// Get automatically calculated progress based on three-factor average
$calculated_progress = getRecommendedProgressPercentage($project_id);
$automated_progress = $calculated_progress['recommended'];

// Get latest progress update timestamp
$latest_progress = null;
if (count($progress_updates) > 0) {
    $latest_progress = $progress_updates[0]['created_at'];
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

$pageTitle = "Project Details - " . htmlspecialchars($project['title']) . " - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Project details - CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Enhanced Typography Hierarchy - High Contrast */
        h1, .h1 {
            font-size: var(--text-5xl);
            font-weight: 800;
            line-height: 1.1;
            color: var(--white);
            margin-bottom: var(--space-6);
            letter-spacing: -0.025em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        h2, .h2 {
            font-size: var(--text-4xl);
            font-weight: 700;
            line-height: 1.2;
            color: var(--primary-dark);
            margin-bottom: var(--space-5);
            letter-spacing: -0.02em;
        }

        h3, .h3 {
            font-size: var(--text-3xl);
            font-weight: 600;
            line-height: 1.3;
            color: var(--primary);
            margin-bottom: var(--space-4);
        }

        h4, .h4 {
            font-size: var(--text-2xl);
            font-weight: 600;
            line-height: 1.4;
            color: var(--gray-800);
            margin-bottom: var(--space-4);
        }

        h5, .h5 {
            font-size: var(--text-xl);
            font-weight: 600;
            line-height: 1.4;
            color: var(--gray-800);
            margin-bottom: var(--space-3);
        }

        h6, .h6 {
            font-size: var(--text-lg);
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
            font-size: var(--text-base);
        }

        .lead {
            font-size: var(--text-xl);
            font-weight: 400;
            color: var(--white);
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .text-muted {
            color: var(--gray-600) !important;
            opacity: 0.9;
        }

        /* Enhanced Navigation */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow-lg);
            padding: var(--space-3) 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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

        /* Enhanced Project Header - High Visibility */
        .project-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-20) 0 var(--space-16);
            margin-top: 76px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .project-header::before {
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

        .project-title {
            font-size: var(--text-5xl);
            font-weight: 900;
            margin-bottom: var(--space-6);
            line-height: 1.1;
            color: var(--white);
            text-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.3),
                0 4px 8px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.02em;
        }

        .project-subtitle {
            font-size: var(--text-xl);
            font-weight: 500;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
            margin-bottom: var(--space-8);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            max-width: 600px;
        }

        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-8);
            margin-bottom: var(--space-8);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--text-lg);
            font-weight: 500;
            color: var(--white);
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .meta-item i {
            color: var(--secondary);
            font-size: 1.2em;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
        }

        /* Enhanced Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-5);
            border-radius: var(--border-radius-lg);
            font-weight: 800;
            font-size: var(--text-base);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            box-shadow: var(--shadow);
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .status-planning { 
            background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
            color: var(--white);
        }
        .status-in-progress { 
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
            color: var(--dark);
        }
        .status-completed { 
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: var(--white);
        }
        .status-delayed { 
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: var(--white);
        }

        /* Enhanced Progress Section */
        .progress-main {
            background: rgba(255, 255, 255, 0.95);
            padding: var(--space-8);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .progress-circle {
            width: 140px;
            height: 140px;
            margin: 0 auto var(--space-6);
            position: relative;
        }

        .progress-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: var(--text-3xl);
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: var(--space-8);
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
            border-bottom: 4px solid var(--primary);
            padding: var(--space-6) var(--space-8);
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .card-header h5 {
            color: var(--primary-dark);
            font-weight: 800;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: var(--space-4);
            font-size: var(--text-xl);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .card-header h5 i {
            color: var(--secondary);
            font-size: 1.3em;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }

        .card-body {
            padding: var(--space-8);
        }

        /* Enhanced Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }

        .info-item {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            padding: var(--space-6);
            border-radius: var(--border-radius);
            border-left: 5px solid var(--primary);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .info-label {
            font-size: var(--text-sm);
            font-weight: 700;
            color: var(--gray-600);
            margin-bottom: var(--space-2);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .info-value {
            font-size: var(--text-lg);
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.4;
        }

        /* Enhanced Buttons */
        .btn-primary-custom {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            padding: var(--space-4) var(--space-6);
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            font-size: var(--text-base);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: var(--transition-slow);
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            background: var(--secondary-gradient);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary);
            border: 3px solid var(--primary);
            padding: var(--space-4) var(--space-6);
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
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
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: var(--space-4);
            flex-wrap: wrap;
        }

        /* Enhanced Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--space-8);
            margin-top: var(--space-16);
            border-top: 4px solid var(--primary);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .project-title {
                font-size: var(--text-4xl);
            }
            
            .project-header {
                padding: var(--space-16) 0 var(--space-12);
            }
            
            .project-meta {
                flex-direction: column;
                gap: var(--space-4);
            }
            
            .card-body {
                padding: var(--space-6);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 var(--space-4);
            }
            
            .card-body {
                padding: var(--space-4);
            }
            
            .project-title {
                font-size: var(--text-3xl);
            }
            
            .project-subtitle {
                font-size: var(--text-lg);
            }
            
            .progress-main {
                padding: var(--space-6);
            }
        }

        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            :root {
                --primary: #000080;
                --secondary: #ffa500;
                --gray-600: #000000;
                --gray-900: #000000;
            }
            
            .project-title {
                color: #000000 !important;
                text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8) !important;
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
                            <li><a class="dropdown-item" href="index.php">
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

    <!-- Project Header -->
    <section class="project-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h1>
                    <p class="lead mb-4 opacity-90"><?php echo htmlspecialchars($project['description']); ?></p>
                    
                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($project['location']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('M j, Y', strtotime($project['start_date'])); ?> - <?php echo date('M j, Y', strtotime($project['end_date'])); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>ZMW <?php echo number_format($project['budget'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="status-badge status-<?php echo $project['status']; ?>">
                        <i class="fas fa-<?php echo $project['status'] === 'completed' ? 'check-circle' : ($project['status'] === 'in-progress' ? 'sync-alt' : 'clock'); ?>"></i>
                        <?php echo ucfirst($project['status']); ?>
                    </span>
                    
                    <div class="progress-main mt-4">
                        <div class="progress-circle">
                            <canvas id="progressChart" width="120" height="120"></canvas>
                            <div class="progress-value"><?php echo $automated_progress; ?>%</div>
                        </div>
                        <h5 class="text-primary mb-2">Overall Progress</h5>
                        <p class="text-muted small mb-3">Based on budget utilization, photo uploads, and achievements/milestones</p>
                        <p class="text-muted small">Last updated: <?php echo $latest_progress ? time_elapsed_string($latest_progress) : 'Never'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mt-5 mb-5">
        <!-- Action Buttons -->
        <div class="content-card">
            <div class="card-body">
                <div class="action-buttons">
                    <a href="../progress/updates.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary-custom">
                        <i class="fas fa-sync-alt me-2"></i>Update Progress
                    </a>
                    <a href="../communication/messages.php?officer_id=<?php echo $project['officer_id']; ?>" class="btn btn-outline-custom">
                        <i class="fas fa-envelope me-2"></i>Contact Officer
                    </a>
                    <a href="index.php" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Back to Projects
                    </a>
                    <a href="../financial/expenses.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-custom">
                        <i class="fas fa-receipt me-2"></i>View Expenses
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Project Details -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Project Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Category</div>
                                <div class="info-value"><?php echo htmlspecialchars(ucfirst($project['category'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Constituency</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['constituency']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Funding Source</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['funding_source'] ?? 'CDF'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Assigned Officer</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['officer_name'] ?? 'Not Assigned'); ?></div>
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Project Description</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>

                        <?php if (!empty($project['additional_notes'])): ?>
                        <h6 class="mt-4 mb-3">Additional Notes</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['additional_notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Timeline -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Progress Timeline</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($progress_updates) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($progress_updates as $update): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($update['created_at'])); ?>
                                        <?php if ($update['created_by_name']): ?>
                                            <span class="text-muted">by <?php echo htmlspecialchars($update['created_by_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Progress: <?php echo $update['progress_percentage']; ?>%</h6>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($update['description'])); ?></p>
                                        
                                        <?php if (!empty($update['challenges'])): ?>
                                        <div class="alert alert-warning alert-sm mt-2 mb-2">
                                            <strong><i class="fas fa-exclamation-triangle me-1"></i>Challenges:</strong>
                                            <?php echo nl2br(htmlspecialchars($update['challenges'])); ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($update['next_steps'])): ?>
                                        <div class="alert alert-info alert-sm mt-2">
                                            <strong><i class="fas fa-arrow-right me-1"></i>Next Steps:</strong>
                                            <?php echo nl2br(htmlspecialchars($update['next_steps'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Progress Updates</h5>
                                <p class="text-muted">Start tracking your project progress by submitting the first update.</p>
                                <a href="../progress/updates.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary-custom">
                                    <i class="fas fa-plus-circle me-2"></i>Add Progress Update
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Project Requirements -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools me-2"></i>Project Requirements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($project['required_materials'])): ?>
                        <h6 class="mb-3">Materials Needed</h6>
                        <p class="text-muted small"><?php echo nl2br(htmlspecialchars($project['required_materials'])); ?></p>
                        <hr>
                        <?php endif; ?>

                        <?php if (!empty($project['human_resources'])): ?>
                        <h6 class="mb-3">Human Resources</h6>
                        <p class="text-muted small"><?php echo nl2br(htmlspecialchars($project['human_resources'])); ?></p>
                        <hr>
                        <?php endif; ?>

                        <?php if (!empty($project['stakeholders'])): ?>
                        <h6 class="mb-3">Key Stakeholders</h6>
                        <p class="text-muted small"><?php echo nl2br(htmlspecialchars($project['stakeholders'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Compliance Status -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-check me-2"></i>Compliance Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Community Approval</span>
                                <span class="badge <?php echo $project['community_approval'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $project['community_approval'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Environmental Compliance</span>
                                <span class="badge <?php echo $project['environmental_compliance'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $project['environmental_compliance'] ? 'Compliant' : 'Pending'; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Land Ownership</span>
                                <span class="badge <?php echo $project['land_ownership'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $project['land_ownership'] ? 'Cleared' : 'Pending'; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Technical Feasibility</span>
                                <span class="badge <?php echo $project['technical_feasibility'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $project['technical_feasibility'] ? 'Feasible' : 'Pending'; ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Budget Approval</span>
                                <span class="badge <?php echo $project['budget_approval'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $project['budget_approval'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../progress/updates.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-custom text-start">
                                <i class="fas fa-sync-alt me-2"></i>Update Progress
                            </a>
                            <a href="../financial/expenses.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-custom text-start">
                                <i class="fas fa-receipt me-2"></i>Record Expense
                            </a>
                            <a href="../communication/messages.php?officer_id=<?php echo $project['officer_id']; ?>" class="btn btn-outline-custom text-start">
                                <i class="fas fa-envelope me-2"></i>Message Officer
                            </a>
                            <a href="index.php" class="btn btn-outline-custom text-start">
                                <i class="fas fa-list me-2"></i>All Projects
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

        // Progress Circle Chart
        document.addEventListener('DOMContentLoaded', function() {
            const progress = <?php echo $automated_progress; ?>;
            const ctx = document.getElementById('progressChart').getContext('2d');
            
            const progressChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [progress, 100 - progress],
                        backgroundColor: [
                            getComputedStyle(document.documentElement).getPropertyValue('--primary'),
                            getComputedStyle(document.documentElement).getPropertyValue('--gray-300')
                        ],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        });
    </script>
</body>
</html>
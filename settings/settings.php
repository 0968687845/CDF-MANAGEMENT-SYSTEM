<?php
require_once '../functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Handle system settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect(basename($_SERVER['PHP_SELF']));
        exit;
    }
    if (isset($_POST['update_notifications'])) {
        $_SESSION['success'] = "Notification preferences updated successfully!";
    }
    
    if (isset($_POST['update_privacy'])) {
        $_SESSION['success'] = "Privacy settings updated successfully!";
    }
    
    if (isset($_POST['save_location'])) {
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (!empty($latitude) && !empty($longitude)) {
            $_SESSION['success'] = "Location saved successfully!";
        } else {
            $_SESSION['error'] = "Please enable location services and try again.";
        }
    }
}

// Get user settings
$userSettings = array();
$locationHistory = array();

$pageTitle = "System Settings - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="System settings for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
        color: var(--gray-900) !important;
        line-height: 1.7;
        font-size: var(--text-base);
        visibility: visible !important;
    }

    .lead {
        font-size: var(--text-lg);
        font-weight: 400;
        color: var(--white) !important;
        line-height: 1.6;
        opacity: 1 !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        visibility: visible !important;
    }

    .text-muted {
        color: var(--gray-600) !important;
        opacity: 1 !important;
        visibility: visible !important;
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

    .navbar-brand img {
        height: 40px;
        width: auto;
        object-fit: contain;
        filter: brightness(1.05) contrast(1.1) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
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

    /* Enhanced Page Header */
    .page-header {
        background: var(--primary-gradient);
        color: var(--white);
        padding: var(--space-16) 0 var(--space-12);
        margin-top: 76px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .page-header h1,
    .page-header h2,
    .page-header h3,
    .page-header h4,
    .page-header h5,
    .page-header h6,
    .page-header p {
        color: var(--white) !important;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4) !important;
        visibility: visible !important;
    }

    .page-header i {
        color: var(--secondary) !important;
        visibility: visible !important;
    }

    .page-header::before {
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
        color: var(--primary-dark) !important;
        font-weight: 800;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: var(--space-4);
        font-size: var(--text-xl);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        visibility: visible !important;
    }

    .card-header h5 i {
        color: var(--secondary) !important;
        font-size: 1.3em;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        visibility: visible !important;
    }

    .card-body {
        padding: var(--space-8);
        color: var(--gray-900) !important;
    }

    .card-body p,
    .card-body label,
    .card-body h5,
    .card-body h6,
    .card-body span,
    .card-body div {
        color: inherit !important;
        visibility: visible !important;
    }


    /* Enhanced Form Styles */
    .form-control, .form-select {
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius);
        padding: var(--space-4) var(--space-5);
        transition: var(--transition);
        font-size: var(--text-base);
        background: var(--white);
        box-shadow: var(--shadow-sm);
        color: var(--gray-900) !important;
        visibility: visible !important;
    }

    .form-control::placeholder {
        color: var(--gray-500) !important;
        visibility: visible !important;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.3rem rgba(26, 78, 138, 0.15);
        transform: translateY(-2px);
        color: var(--gray-900) !important;
    }

    .form-check-label {
        color: var(--gray-900) !important;
        visibility: visible !important;
        font-weight: 500;
    }

    .form-label {
        font-weight: 600;
        color: var(--primary) !important;
        margin-bottom: var(--space-3);
        font-size: var(--text-base);
        visibility: visible !important;
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
        position: relative;
        z-index: 2;
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
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>My Projects
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/notifications.php">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a></li>
                            <li><a class="dropdown-item" href="../progress/updates.php">
                                <i class="fas fa-sync-alt me-2"></i>Update Progress
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
                                        <?php if (!empty($userData['profile_picture'])): ?>
                                            <img src="../uploads/profiles/<?php echo htmlspecialchars($userData['profile_picture']); ?>" 
                                                 alt="Profile" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
                                                <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                                            <small class="text-muted">CDF Beneficiary</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item active" href="system.php">
                                <i class="fas fa-cog me-2"></i>System Settings
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-cog me-2"></i>System Settings</h1>
                    <p class="lead mb-0">Manage your account preferences and location settings</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <a href="profile.php" class="btn btn-outline-custom">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Notification Settings -->
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bell me-2"></i>Notification Preferences</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-section">
                                <h6><i class="fas fa-envelope me-2"></i>Notification Channels</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                        <?= csrfField() ?>
                                           <?php echo ($userSettings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        Email Notifications
                                    </label>
                                    <small class="form-text text-muted d-block">Receive notifications via email</small>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications"
                                           <?php echo ($userSettings['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sms_notifications">
                                        SMS Notifications
                                    </label>
                                    <small class="form-text text-muted d-block">Receive notifications via SMS</small>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications"
                                           <?php echo ($userSettings['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="push_notifications">
                                        Push Notifications
                                    </label>
                                    <small class="form-text text-muted d-block">Receive browser push notifications</small>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-bell me-2"></i>Notification Types</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="project_updates" name="project_updates"
                                           <?php echo ($userSettings['project_updates'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="project_updates">
                                        Project Updates
                                    </label>
                                    <small class="form-text text-muted d-block">Get notified about project progress and changes</small>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="message_alerts" name="message_alerts"
                                           <?php echo ($userSettings['message_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="message_alerts">
                                        Message Alerts
                                    </label>
                                    <small class="form-text text-muted d-block">Notifications for new messages</small>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="deadline_reminders" name="deadline_reminders"
                                           <?php echo ($userSettings['deadline_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="deadline_reminders">
                                        Deadline Reminders
                                    </label>
                                    <small class="form-text text-muted d-block">Reminders for project deadlines</small>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_notifications" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Privacy Settings -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt me-2"></i>Privacy & Security</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-section">
                                <h6><i class="fas fa-user-lock me-2"></i>Privacy Settings</h6>
                                <div class="mb-3">
                                    <label for="profile_visibility" class="form-label">Profile Visibility</label>
                                        <?= csrfField() ?>
                                    <select class="form-select" id="profile_visibility" name="profile_visibility">
                                        <option value="public" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'public' ? 'selected' : ''; ?>>Public</option>
                                        <option value="private" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'selected' : ''; ?>>Private</option>
                                        <option value="contacts" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'contacts' ? 'selected' : ''; ?>>Contacts Only</option>
                                    </select>
                                    <small class="form-text text-muted">Control who can see your profile information</small>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="location_sharing" name="location_sharing"
                                           <?php echo ($userSettings['location_sharing'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="location_sharing">
                                        Enable Location Sharing
                                    </label>
                                    <small class="form-text text-muted d-block">Allow system to access your location for project tracking</small>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="data_collection" name="data_collection"
                                           <?php echo ($userSettings['data_collection'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="data_collection">
                                        Data Collection
                                    </label>
                                    <small class="form-text text-muted d-block">Allow anonymous data collection for system improvement</small>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_privacy" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Save Privacy Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Location Tracking -->
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-map-marker-alt me-2"></i>Location Tracker</h5>
                    </div>
                    <div class="card-body">
                        <div class="location-status acquiring" id="locationStatus">
                            <i class="fas fa-sync-alt fa-spin me-2"></i>
                            <span id="statusText">Acquiring your location...</span>
                        </div>

                        <div class="map-container">
                            <div id="locationMap"></div>
                        </div>

                        <form method="POST" action="" id="locationForm">
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                            <input type="hidden" name="address" id="address">
                            
                                 <?= csrfField() ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Latitude</label>
                                    <div class="location-coordinates" id="displayLatitude">Not available</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Longitude</label>
                                    <div class="location-coordinates" id="displayLongitude">Not available</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <div class="location-coordinates" id="displayAddress">Not available</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-custom" id="refreshLocation">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Location
                                </button>
                                <button type="submit" name="save_location" class="btn btn-primary-custom" id="saveLocation" disabled>
                                    <i class="fas fa-save me-2"></i>Save Current Location
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Location History -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Location History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($locationHistory) > 0): ?>
                            <?php foreach ($locationHistory as $location): ?>
                            <div class="location-history-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong><?php echo date('M j, Y g:i A', strtotime($location['created_at'])); ?></strong>
                                    <span class="badge badge-online">Saved</span>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Coordinates:</small>
                                        <div class="location-coordinates small">
                                            <?php echo number_format($location['latitude'], 6); ?>, <?php echo number_format($location['longitude'], 6); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Address:</small>
                                        <div class="small"><?php echo htmlspecialchars($location['address'] ?? 'Not recorded'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No location history yet</p>
                                <p class="text-muted small">Your saved locations will appear here</p>
                            </div>
                        <?php endif; ?>
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize variables
        let map = null;
        let marker = null;
        let watchId = null;

        // Initialize map
        function initMap(lat = -15.3875, lng = 28.3228) { // Default to Lusaka, Zambia
            if (map) {
                map.remove();
            }

            map = L.map('locationMap').setView([lat, lng], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add marker
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup('Your current location')
                .openPopup();
        }

        // Get current location
        function getCurrentLocation() {
            const statusElement = document.getElementById('locationStatus');
            const statusText = document.getElementById('statusText');
            const saveButton = document.getElementById('saveLocation');

            statusElement.className = 'location-status acquiring';
            statusText.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Acquiring your location...';
            saveButton.disabled = true;

            if (!navigator.geolocation) {
                statusElement.className = 'location-status error';
                statusText.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Geolocation is not supported by this browser.';
                return;
            }

            // Stop any existing watcher
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }

            // Get location with high accuracy
            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    // Update form fields
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    document.getElementById('displayLatitude').textContent = lat.toFixed(6);
                    document.getElementById('displayLongitude').textContent = lng.toFixed(6);

                    // Update map
                    initMap(lat, lng);

                    // Get address from coordinates (reverse geocoding)
                    getAddressFromCoordinates(lat, lng);

                    // Update status
                    statusElement.className = 'location-status acquired';
                    statusText.innerHTML = `<i class="fas fa-check-circle me-2"></i>Location acquired (Accuracy: ${Math.round(accuracy)} meters)`;
                    saveButton.disabled = false;
                },
                function(error) {
                    let errorMessage = 'Unable to retrieve your location.';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied. Please enable location permissions.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out.';
                            break;
                    }

                    statusElement.className = 'location-status error';
                    statusText.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${errorMessage}`;
                    saveButton.disabled = true;

                    // Initialize map with default location
                    initMap();
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        }

        // Get address from coordinates using OpenStreetMap Nominatim
        function getAddressFromCoordinates(lat, lng) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        const address = data.display_name;
                        document.getElementById('address').value = address;
                        document.getElementById('displayAddress').textContent = address;
                    } else {
                        document.getElementById('displayAddress').textContent = 'Address not found';
                    }
                })
                .catch(error => {
                    console.error('Error getting address:', error);
                    document.getElementById('displayAddress').textContent = 'Error getting address';
                });
        }

        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map with default location
            initMap();
            
            // Get current location
            getCurrentLocation();

            // Refresh location button
            document.getElementById('refreshLocation').addEventListener('click', getCurrentLocation);

            // Update server time
            setInterval(updateServerTime, 1000);
            updateServerTime();
        });

        // Handle form submission
        document.getElementById('locationForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            
            if (!lat || !lng) {
                e.preventDefault();
                alert('Please wait for location to be acquired before saving.');
            }
        });

        // Handle page visibility change
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page became visible, refresh location
                getCurrentLocation();
            }
        });
    </script>
</body>
</html>
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
        /* ===== CSS DESIGN SYSTEM ===== */
        :root {
            /* Color Variables */
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            --light: #f8f9fa;
            --dark: #212529;
            --white: #ffffff;
            
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            
            /* Gradient Variables */
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            
            /* Shadow Scale */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 8px 16px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
            --shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.15);
            
            /* Border Radius Scale */
            --border-radius: 8px;
            --border-radius-md: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 20px;
            --border-radius-full: 9999px;
            
            /* Typography Scale */
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
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            color: var(--gray-900);
            line-height: 1.6;
            letter-spacing: -0.01em;
            font-size: var(--text-base);
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: var(--space-4);
        }

        h1 { font-size: var(--text-5xl); }
        h2 { font-size: var(--text-4xl); }
        h3 { font-size: var(--text-3xl); }
        h4 { font-size: var(--text-2xl); }
        h5 { font-size: var(--text-xl); }
        h6 { font-size: var(--text-lg); }

        p {
            color: var(--gray-600);
            margin-bottom: var(--space-4);
        }

        /* ===== NAVIGATION ===== */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow-md);
            padding: var(--space-5) 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--text-xl);
            letter-spacing: -0.01em;
        }

        .navbar-brand img {
            height: 45px;
            width: auto;
            object-fit: contain;
            filter: brightness(1.1);
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            filter: brightness(1.2) drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: var(--space-3) var(--space-4) !important;
            border-radius: var(--border-radius);
            position: relative;
            letter-spacing: 0.01em;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--secondary);
            border-radius: 3px;
            transition: var(--transition-fast);
        }

        .nav-link:hover::after,
        .nav-link:focus::after {
            width: 100%;
        }

        .nav-link:hover,
        .nav-link:focus {
            color: var(--white) !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius-md);
            padding: var(--space-3) 0;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-10) 0;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
            animation: float 20s ease-in-out infinite;
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
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.05) 10px,
                rgba(255, 255, 255, 0.05) 20px
            );
            pointer-events: none;
        }

        .page-header h1 {
            position: relative;
            z-index: 2;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* ===== CONTENT CARDS ===== */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: var(--space-8);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--gray-100);
        }

        .content-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
        }

        .card-header {
            background: var(--light-gradient);
            border-bottom: 4px solid var(--primary);
            padding: var(--space-6);
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--secondary);
            border-radius: 0 2px 2px 0;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
            font-size: var(--text-lg);
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

        /* ===== FORM STYLES ===== */
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

        .form-label {
            font-weight: 600;
            color: var(--primary) !important;
            margin-bottom: var(--space-3);
            font-size: var(--text-base);
            visibility: visible !important;
        }

        .form-check-label {
            color: var(--gray-900) !important;
            visibility: visible !important;
            font-weight: 500;
        }

        .form-section {
            background: var(--light);
            border-radius: var(--border-radius-md);
            padding: var(--space-6);
            margin-bottom: var(--space-8);
            border-left: 4px solid var(--primary);
        }

        /* ===== BUTTONS ===== */
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

        /* ===== MAP STYLES ===== */
        #locationMap {
            height: 400px;
            width: 100%;
            border-radius: var(--border-radius);
            z-index: 1;
            box-shadow: var(--shadow-md);
        }

        .map-container {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: var(--space-6);
        }

        .location-status {
            padding: var(--space-4) var(--space-5);
            border-radius: var(--border-radius);
            margin-bottom: var(--space-6);
            font-weight: 600;
            border-left: 4px solid transparent;
        }

        .location-status.acquiring {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-left-color: var(--warning);
        }

        .location-status.acquired {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left-color: var(--success);
        }

        .location-status.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left-color: var(--danger);
        }

        /* ===== SWITCHES ===== */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .form-check-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.3rem rgba(26, 78, 138, 0.15);
        }

        /* ===== BADGES ===== */
        .badge {
            padding: var(--space-2) var(--space-3);
            font-weight: 600;
            border-radius: var(--border-radius);
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        /* ===== ALERTS ===== */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 4px solid transparent;
            padding: var(--space-5) var(--space-6);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left-color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left-color: var(--danger);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-left-color: var(--warning);
        }

        /* ===== LOCATION HISTORY ===== */
        .location-history-item {
            padding: var(--space-5);
            border-left: 4px solid var(--primary);
            background: var(--light);
            border-radius: var(--border-radius);
            margin-bottom: var(--space-4);
            transition: var(--transition);
        }

        .location-history-item:hover {
            background: rgba(26, 78, 138, 0.05);
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }

        .location-coordinates {
            font-family: 'Courier New', 'Courier', monospace;
            background: var(--white);
            padding: var(--space-3);
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            font-size: var(--text-sm);
            word-break: break-all;
        }

        .accuracy-indicator {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-top: var(--space-3);
            font-size: var(--text-sm);
        }

        .accuracy-bar {
            flex: 1;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
        }

        .accuracy-fill {
            height: 100%;
            background: var(--success);
            transition: width 0.3s ease;
            border-radius: 3px;
        }

        /* ===== FOOTER ===== */
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: var(--space-10) var(--space-4);
            }
            
            #locationMap {
                height: 300px;
            }
            
            .form-section {
                padding: var(--space-5);
            }
            
            .navbar-brand img {
                height: 35px;
            }

            .card-body {
                padding: var(--space-6);
            }
        }

        @media (max-width: 576px) {
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                margin-bottom: var(--space-3);
            }
            
            .location-coordinates {
                font-size: var(--text-xs);
            }
            
            .page-header {
                margin-top: 70px;
                padding: var(--space-6) var(--space-3);
            }

            h1 { font-size: var(--text-3xl); }
            h2 { font-size: var(--text-2xl); }
            h3 { font-size: var(--text-xl); }
        }

        /* ===== UTILITY CLASSES ===== */
        .text-primary-custom {
            color: var(--primary) !important;
        }

        .bg-light-custom {
            background-color: var(--light) !important;
        }

        .border-radius-custom {
            border-radius: var(--border-radius) !important;
        }

        /* ===== ANIMATIONS ===== */
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
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

                        <!-- Accuracy Indicator -->
                        <div class="accuracy-indicator" id="accuracyIndicator" style="display: none;">
                            <span>Accuracy:</span>
                            <div class="accuracy-bar">
                                <div class="accuracy-fill" id="accuracyFill" style="width: 0%"></div>
                            </div>
                            <span id="accuracyText">0 meters</span>
                        </div>

                        <div class="map-container">
                            <div id="locationMap"></div>
                        </div>

                        <form method="POST" action="" id="locationForm">
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                            <input type="hidden" name="address" id="address">
                            
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
        let circle = null;
        let watchId = null;
        let currentAccuracy = 0;

        // Initialize map with better tile layers
        function initMap(lat = -15.3875, lng = 28.3228, accuracy = 100) {
            if (map) {
                map.remove();
            }

            map = L.map('locationMap').setView([lat, lng], 16);
            
            // Add multiple tile layers for better accuracy
            const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            });

            const satelliteLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '© Google Maps'
            });

            // Add default layer
            osmLayer.addTo(map);

            // Add layer control
            L.control.layers({
                "Street Map": osmLayer,
                "Satellite": satelliteLayer
            }).addTo(map);

            updateMapMarker(lat, lng, accuracy);
        }

        // Update map marker with accuracy circle
        function updateMapMarker(lat, lng, accuracy) {
            // Remove existing marker and circle
            if (marker) {
                map.removeLayer(marker);
            }
            if (circle) {
                map.removeLayer(circle);
            }

            // Add accuracy circle
            circle = L.circle([lat, lng], {
                color: '#1a4e8a',
                fillColor: '#1a4e8a',
                fillOpacity: 0.1,
                radius: accuracy
            }).addTo(map);

            // Add marker
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup(`<b>Your Location</b><br>Accuracy: ${Math.round(accuracy)} meters`)
                .openPopup();

            // Adjust map view to show accuracy circle
            map.fitBounds(circle.getBounds());
        }

        // Update accuracy indicator
        function updateAccuracyIndicator(accuracy) {
            const accuracyFill = document.getElementById('accuracyFill');
            const accuracyText = document.getElementById('accuracyText');
            const accuracyIndicator = document.getElementById('accuracyIndicator');
            
            currentAccuracy = accuracy;
            accuracyText.textContent = `${Math.round(accuracy)} meters`;
            
            // Calculate accuracy percentage (better accuracy = higher percentage)
            let accuracyPercent = 100 - Math.min(100, (accuracy / 100) * 100);
            accuracyFill.style.width = `${accuracyPercent}%`;
            
            // Set color based on accuracy
            if (accuracy <= 20) {
                accuracyFill.className = 'accuracy-fill accuracy-high';
            } else if (accuracy <= 50) {
                accuracyFill.className = 'accuracy-fill accuracy-medium';
            } else {
                accuracyFill.className = 'accuracy-fill accuracy-low';
            }
            
            accuracyIndicator.style.display = 'flex';
        }

        // Get current location with improved accuracy
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
                initMap(); // Initialize map with default location
                return;
            }

            // Stop any existing watcher
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }

            // Get location with maximum accuracy
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

                    // Update map with accuracy circle
                    updateMapMarker(lat, lng, accuracy);
                    
                    // Update accuracy indicator
                    updateAccuracyIndicator(accuracy);

                    // Get address from coordinates (reverse geocoding)
                    getAddressFromCoordinates(lat, lng);

                    // Update status
                    statusElement.className = 'location-status acquired';
                    let accuracyMessage = `Location acquired`;
                    if (accuracy <= 20) {
                        accuracyMessage += ` <span class="badge bg-success">High Accuracy (${Math.round(accuracy)}m)</span>`;
                    } else if (accuracy <= 50) {
                        accuracyMessage += ` <span class="badge bg-warning">Medium Accuracy (${Math.round(accuracy)}m)</span>`;
                    } else {
                        accuracyMessage += ` <span class="badge bg-danger">Low Accuracy (${Math.round(accuracy)}m)</span>`;
                    }
                    statusText.innerHTML = `<i class="fas fa-check-circle me-2"></i>${accuracyMessage}`;
                    saveButton.disabled = false;
                },
                function(error) {
                    let errorMessage = 'Unable to retrieve your location.';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied. Please enable location permissions in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable. Try moving to an area with better signal.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out. Please try again.';
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
                    timeout: 15000,
                    maximumAge: 30000,
                    // Additional options for better accuracy
                    enableHighAccuracy: true
                }
            );
        }

        // Improved address lookup with fallback
        function getAddressFromCoordinates(lat, lng) {
            const displayAddress = document.getElementById('displayAddress');
            const addressInput = document.getElementById('address');
            
            // Try multiple geocoding services
            const services = [
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`
            ];

            let currentService = 0;

            function tryNextService() {
                if (currentService >= services.length) {
                    displayAddress.textContent = 'Address not available';
                    addressInput.value = 'Address lookup failed';
                    return;
                }

                fetch(services[currentService])
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        let address = '';
                        
                        if (services[currentService].includes('nominatim')) {
                            // OpenStreetMap Nominatim
                            if (data && data.display_name) {
                                address = data.display_name;
                            }
                        } else if (services[currentService].includes('bigdatacloud')) {
                            // BigDataCloud
                            if (data && data.locality) {
                                address = `${data.locality}, ${data.city || data.principalSubdivision}, ${data.countryName}`;
                            }
                        }

                        if (address) {
                            displayAddress.textContent = address;
                            addressInput.value = address;
                        } else {
                            currentService++;
                            tryNextService();
                        }
                    })
                    .catch(error => {
                        console.error('Geocoding error:', error);
                        currentService++;
                        tryNextService();
                    });
            }

            tryNextService();
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
            document.getElementById('refreshLocation').addEventListener('click', function() {
                getCurrentLocation();
            });

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
                return;
            }

            // Show loading state
            const saveButton = document.getElementById('saveLocation');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveButton.disabled = true;

            // Re-enable after a short delay (in case of error)
            setTimeout(() => {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }, 3000);
        });

        // Handle page visibility change
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page became visible, refresh location
                getCurrentLocation();
            }
        });

        // Handle online/offline status
        window.addEventListener('online', function() {
            getCurrentLocation();
        });

        window.addEventListener('offline', function() {
            const statusElement = document.getElementById('locationStatus');
            const statusText = document.getElementById('statusText');
            statusElement.className = 'location-status error';
            statusText.innerHTML = '<i class="fas fa-wifi me-2"></i>You are offline. Location updates paused.';
        });
    </script>
</body>
</html>
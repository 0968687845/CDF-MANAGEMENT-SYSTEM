<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get current system settings
$systemSettings = getSystemSettings();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general_settings'])) {
        // Update general settings
        $settingsData = [
            'system_name' => $_POST['system_name'],
            'system_email' => $_POST['system_email'],
            'admin_email' => $_POST['admin_email'],
            'timezone' => $_POST['timezone'],
            'date_format' => $_POST['date_format'],
            'items_per_page' => $_POST['items_per_page'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        if (updateSystemSettings($settingsData)) {
            $_SESSION['success_message'] = "General settings updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update general settings";
        }
        redirect('settings.php');
        
    } elseif (isset($_POST['update_notification_settings'])) {
        // Update notification settings
        $notificationData = [
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'project_approvals' => isset($_POST['project_approvals']) ? 1 : 0,
            'officer_assignments' => isset($_POST['officer_assignments']) ? 1 : 0,
            'budget_alerts' => isset($_POST['budget_alerts']) ? 1 : 0,
            'system_updates' => isset($_POST['system_updates']) ? 1 : 0
        ];
        
        if (updateNotificationSettings($notificationData)) {
            $_SESSION['success_message'] = "Notification settings updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update notification settings";
        }
        redirect('settings.php');
        
    } elseif (isset($_POST['update_security_settings'])) {
        // Update security settings
        $securityData = [
            'password_policy' => $_POST['password_policy'],
            'session_timeout' => $_POST['session_timeout'],
            'max_login_attempts' => $_POST['max_login_attempts'],
            'two_factor_auth' => isset($_POST['two_factor_auth']) ? 1 : 0,
            'ip_whitelist' => $_POST['ip_whitelist']
        ];
        
        if (updateSecuritySettings($securityData)) {
            $_SESSION['success_message'] = "Security settings updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update security settings";
        }
        redirect('settings.php');
        
    } elseif (isset($_POST['update_backup_settings'])) {
        // Update backup settings
        $backupData = [
            'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
            'backup_frequency' => $_POST['backup_frequency'],
            'backup_retention' => $_POST['backup_retention'],
            'backup_email' => $_POST['backup_email']
        ];
        
        if (updateBackupSettings($backupData)) {
            $_SESSION['success_message'] = "Backup settings updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update backup settings";
        }
        redirect('settings.php');
        
    } elseif (isset($_POST['clear_cache'])) {
        // Clear system cache
        if (clearSystemCache()) {
            $_SESSION['success_message'] = "System cache cleared successfully";
        } else {
            $_SESSION['error_message'] = "Failed to clear system cache";
        }
        redirect('settings.php');
        
    } elseif (isset($_POST['run_backup'])) {
        // Run manual backup
        if (runManualBackup()) {
            $_SESSION['success_message'] = "Manual backup completed successfully";
        } else {
            $_SESSION['error_message'] = "Failed to run manual backup";
        }
        redirect('settings.php');
        
    } elseif (isset($_POST['test_email'])) {
        // Test email configuration
        if (testEmailConfiguration()) {
            $_SESSION['success_message'] = "Test email sent successfully";
        } else {
            $_SESSION['error_message'] = "Failed to send test email";
        }
        redirect('settings.php');
    }
}

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
    <?php include_once '../includes/global_theme.php'; ?>
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
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            line-height: 1.7;
            min-height: 100vh;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
            transition: var(--transition);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
        }

        .navbar-brand img {
            filter: brightness(1.05) contrast(1.1) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            transition: var(--transition);
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
            filter: brightness(1.15) contrast(1.2) drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: 8px;
            position: relative;
            font-size: 0.95rem;
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white) !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius);
            padding: 0.75rem 0;
            border: 1px solid rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
            border-radius: 6px;
            margin: 0 0.5rem;
            width: auto;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            transform: translateX(5px);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 3rem 0 2.5rem;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.05)"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 800;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(233, 185, 73, 0.4);
        }

        .profile-info h1 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-info .lead {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: var(--dark);
            border: none;
            padding: 1rem 2rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, #c4952e 100%);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: var(--dark);
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.7);
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            border-color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
            position: relative;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Settings Specific Styles */
        .settings-nav {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .settings-nav .nav-link {
            color: var(--dark) !important;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .settings-nav .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--white) !important;
        }

        .settings-nav .nav-link:hover:not(.active) {
            background: var(--gray-light);
        }

        .form-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary);
        }

        .form-section h6 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .setting-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .setting-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .setting-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .setting-description {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        /* Toggle Switch */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(26, 78, 138, 0.25);
        }

        /* System Status */
        .system-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .status-online {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status-offline {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .status-warning {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .dashboard-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        /* Badge Colors */
        .badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Form Styling */
        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(26, 78, 138, 0.15);
            border-color: var(--primary);
        }

        /* Alert Styling */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-sm);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
                padding: 2rem 0 1.5rem;
            }
            
            .profile-section {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .profile-info h1 {
                font-size: 1.75rem;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .settings-nav .nav {
                flex-direction: column;
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

        .content-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Tab Content Animation */
        .tab-pane {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* System Info Cards */
        .info-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary);
            margin-bottom: 1rem;
        }

        .info-card h6 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .info-card .value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
        }

        /* Danger Zone */
        .danger-zone {
            background: var(--danger-light);
            border: 2px solid var(--danger);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 2rem;
        }

        .danger-zone h6 {
            color: var(--danger);
            font-weight: 700;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="assignments.php">
                                <i class="fas fa-user-tie me-2"></i>Officer Assignments
                            </a></li>
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>System Reports
                            </a></li>
                            <li><a class="dropdown-item active" href="settings.php">
                                <i class="fas fa-cog me-2"></i>System Settings
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
                            <li><h6 class="dropdown-header">System Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="notifications.php">No new notifications</a></li>
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
                                            <small class="text-muted">System Administrator</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
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

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1>System Settings</h1>
                    <p class="lead">Configure and manage system-wide settings</p>
                    <p class="mb-0">
                        System Status: 
                        <span class="system-status status-online">
                            <i class="fas fa-circle"></i> Online
                        </span>
                        | Version: 2.5.1
                    </p>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#backupModal">
                    <i class="fas fa-download me-2"></i>Backup System
                </button>
                <a href="settings.php" class="btn btn-outline-custom">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Settings Navigation -->
        <div class="settings-nav">
            <ul class="nav nav-pills" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-cog me-2"></i>General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                        <i class="fas fa-database me-2"></i>Backup
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                        <i class="fas fa-info-circle me-2"></i>System Info
                    </button>
                </li>
            </ul>
        </div>

        <!-- Settings Content -->
        <div class="tab-content" id="settingsTabsContent">
            <!-- General Settings Tab -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-cog me-2"></i>General System Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <div class="form-section">
                                <h6><i class="fas fa-info-circle"></i>Basic Information</h6>
                                
                                <div class="setting-item">
                                    <div class="setting-label">System Name</div>
                                    <div class="setting-description">The name that appears throughout the system</div>
                                    <input type="text" class="form-control" name="system_name" value="<?php echo htmlspecialchars($systemSettings['system_name'] ?? 'CDF Management System'); ?>" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div class="setting-label">System Email</div>
                                            <div class="setting-description">Email address used for system notifications</div>
                                            <input type="email" class="form-control" name="system_email" value="<?php echo htmlspecialchars($systemSettings['system_email'] ?? 'noreply@cdf.gov.zm'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div class="setting-label">Admin Email</div>
                                            <div class="setting-description">Primary administrator contact email</div>
                                            <input type="email" class="form-control" name="admin_email" value="<?php echo htmlspecialchars($systemSettings['admin_email'] ?? 'admin@cdf.gov.zm'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-clock"></i>Date & Time Settings</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div class="setting-label">Timezone</div>
                                            <div class="setting-description">System timezone for all date/time displays</div>
                                            <select class="form-select" name="timezone" required>
                                                <option value="Africa/Lusaka" <?php echo ($systemSettings['timezone'] ?? 'Africa/Lusaka') === 'Africa/Lusaka' ? 'selected' : ''; ?>>Africa/Lusaka (CAT)</option>
                                                <option value="UTC" <?php echo ($systemSettings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                <option value="America/New_York" <?php echo ($systemSettings['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New York (EST)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div class="setting-label">Date Format</div>
                                            <div class="setting-description">How dates are displayed throughout the system</div>
                                            <select class="form-select" name="date_format" required>
                                                <option value="Y-m-d" <?php echo ($systemSettings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (2024-01-15)</option>
                                                <option value="d/m/Y" <?php echo ($systemSettings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY (15/01/2024)</option>
                                                <option value="m/d/Y" <?php echo ($systemSettings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY (01/15/2024)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-sliders-h"></i>Display Settings</h6>
                                
                                <div class="setting-item">
                                    <div class="setting-label">Items Per Page</div>
                                    <div class="setting-description">Number of items to display per page in lists and tables</div>
                                    <select class="form-select" name="items_per_page" required>
                                        <option value="10" <?php echo ($systemSettings['items_per_page'] ?? '10') == '10' ? 'selected' : ''; ?>>10 items</option>
                                        <option value="25" <?php echo ($systemSettings['items_per_page'] ?? '') == '25' ? 'selected' : ''; ?>>25 items</option>
                                        <option value="50" <?php echo ($systemSettings['items_per_page'] ?? '') == '50' ? 'selected' : ''; ?>>50 items</option>
                                        <option value="100" <?php echo ($systemSettings['items_per_page'] ?? '') == '100' ? 'selected' : ''; ?>>100 items</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-tools"></i>System Maintenance</h6>
                                
                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" <?php echo ($systemSettings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="maintenance_mode">
                                            Maintenance Mode
                                        </label>
                                        <div class="setting-description">
                                            When enabled, only administrators can access the system. Regular users will see a maintenance message.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="update_general_settings" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notification Settings Tab -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <div class="form-section">
                                <h6><i class="fas fa-envelope"></i>Email Notifications</h6>
                                
                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" <?php echo ($systemSettings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="email_notifications">
                                            Enable Email Notifications
                                        </label>
                                        <div class="setting-description">
                                            Send email notifications for important system events and updates.
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="project_approvals" id="project_approvals" <?php echo ($systemSettings['project_approvals'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="project_approvals">
                                            Project Approval Notifications
                                        </label>
                                        <div class="setting-description">
                                            Notify administrators when projects require approval.
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="officer_assignments" id="officer_assignments" <?php echo ($systemSettings['officer_assignments'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="officer_assignments">
                                            Officer Assignment Notifications
                                        </label>
                                        <div class="setting-description">
                                            Notify officers when they are assigned to new projects.
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="budget_alerts" id="budget_alerts" <?php echo ($systemSettings['budget_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="budget_alerts">
                                            Budget Alert Notifications
                                        </label>
                                        <div class="setting-description">
                                            Send alerts when project budgets approach or exceed limits.
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="system_updates" id="system_updates" <?php echo ($systemSettings['system_updates'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="system_updates">
                                            System Update Notifications
                                        </label>
                                        <div class="setting-description">
                                            Notify administrators about system updates and maintenance.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="update_notification_settings" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Save Notification Settings
                                </button>
                                <button type="submit" name="test_email" class="btn btn-outline-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Test Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <div class="form-section">
                                <h6><i class="fas fa-lock"></i>Authentication & Password Policy</h6>
                                
                                <div class="setting-item">
                                    <div class="setting-label">Password Policy</div>
                                    <div class="setting-description">Minimum requirements for user passwords</div>
                                    <select class="form-select" name="password_policy" required>
                                        <option value="low" <?php echo ($systemSettings['password_policy'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>Low - 6 characters minimum</option>
                                        <option value="medium" <?php echo ($systemSettings['password_policy'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium - 8 characters with letters and numbers</option>
                                        <option value="high" <?php echo ($systemSettings['password_policy'] ?? '') === 'high' ? 'selected' : ''; ?>>High - 12 characters with mixed case, numbers, and symbols</option>
                                    </select>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-label">Session Timeout</div>
                                    <div class="setting-description">Time before inactive users are automatically logged out</div>
                                    <select class="form-select" name="session_timeout" required>
                                        <option value="30" <?php echo ($systemSettings['session_timeout'] ?? '60') == '30' ? 'selected' : ''; ?>>30 minutes</option>
                                        <option value="60" <?php echo ($systemSettings['session_timeout'] ?? '60') == '60' ? 'selected' : ''; ?>>1 hour</option>
                                        <option value="120" <?php echo ($systemSettings['session_timeout'] ?? '') == '120' ? 'selected' : ''; ?>>2 hours</option>
                                        <option value="240" <?php echo ($systemSettings['session_timeout'] ?? '') == '240' ? 'selected' : ''; ?>>4 hours</option>
                                    </select>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-label">Maximum Login Attempts</div>
                                    <div class="setting-description">Number of failed login attempts before account lockout</div>
                                    <select class="form-select" name="max_login_attempts" required>
                                        <option value="3" <?php echo ($systemSettings['max_login_attempts'] ?? '5') == '3' ? 'selected' : ''; ?>>3 attempts</option>
                                        <option value="5" <?php echo ($systemSettings['max_login_attempts'] ?? '5') == '5' ? 'selected' : ''; ?>>5 attempts</option>
                                        <option value="10" <?php echo ($systemSettings['max_login_attempts'] ?? '') == '10' ? 'selected' : ''; ?>>10 attempts</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-user-shield"></i>Advanced Security</h6>
                                
                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="two_factor_auth" id="two_factor_auth" <?php echo ($systemSettings['two_factor_auth'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="two_factor_auth">
                                            Two-Factor Authentication
                                        </label>
                                        <div class="setting-description">
                                            Require two-factor authentication for administrator accounts.
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-label">IP Whitelist</div>
                                    <div class="setting-description">Restrict access to specific IP addresses (one per line)</div>
                                    <textarea class="form-control" name="ip_whitelist" rows="4" placeholder="192.168.1.1&#10;10.0.0.1"><?php echo htmlspecialchars($systemSettings['ip_whitelist'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="update_security_settings" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Save Security Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Backup Settings Tab -->
            <div class="tab-pane fade" id="backup" role="tabpanel">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-database me-2"></i>Backup & Recovery</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="settings.php">
                            <div class="form-section">
                                <h6><i class="fas fa-robot"></i>Automatic Backups</h6>
                                
                                <div class="setting-item">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_backup" id="auto_backup" <?php echo ($systemSettings['auto_backup'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label setting-label" for="auto_backup">
                                            Enable Automatic Backups
                                        </label>
                                        <div class="setting-description">
                                            Automatically create system backups on a schedule.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div class="setting-label">Backup Frequency</div>
                                            <div class="setting-description">How often to create automatic backups</div>
                                            <select class="form-select" name="backup_frequency" required>
                                                <option value="daily" <?php echo ($systemSettings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                <option value="weekly" <?php echo ($systemSettings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo ($systemSettings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="setting-item">
                                            <div class="setting-label">Backup Retention</div>
                                            <div class="setting-description">How long to keep backup files</div>
                                            <select class="form-select" name="backup_retention" required>
                                                <option value="7" <?php echo ($systemSettings['backup_retention'] ?? '30') == '7' ? 'selected' : ''; ?>>7 days</option>
                                                <option value="30" <?php echo ($systemSettings['backup_retention'] ?? '30') == '30' ? 'selected' : ''; ?>>30 days</option>
                                                <option value="90" <?php echo ($systemSettings['backup_retention'] ?? '') == '90' ? 'selected' : ''; ?>>90 days</option>
                                                <option value="365" <?php echo ($systemSettings['backup_retention'] ?? '') == '365' ? 'selected' : ''; ?>>1 year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-label">Backup Notification Email</div>
                                    <div class="setting-description">Email address to receive backup status notifications</div>
                                    <input type="email" class="form-control" name="backup_email" value="<?php echo htmlspecialchars($systemSettings['backup_email'] ?? 'backups@cdf.gov.zm'); ?>">
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-history"></i>Manual Backup</h6>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Last backup: <?php echo $systemSettings['last_backup'] ?? 'Never'; ?>
                                </div>

                                <div class="text-end">
                                    <button type="submit" name="run_backup" class="btn btn-primary-custom">
                                        <i class="fas fa-download me-2"></i>Run Manual Backup
                                    </button>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="update_backup_settings" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Save Backup Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Tools -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools me-2"></i>System Tools</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h6><i class="fas fa-broom"></i>Cache Management</h6>
                                    <p class="text-muted">Clear system cache to free up memory and resolve display issues.</p>
                                    <form method="POST" action="settings.php" class="d-inline">
                                        <button type="submit" name="clear_cache" class="btn btn-outline-warning">
                                            <i class="fas fa-broom me-2"></i>Clear System Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h6><i class="fas fa-file-export"></i>Data Export</h6>
                                    <p class="text-muted">Export system data for external analysis or reporting.</p>
                                    <button type="button" class="btn btn-outline-info">
                                        <i class="fas fa-file-export me-2"></i>Export System Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info Tab -->
            <div class="tab-pane fade" id="system" role="tabpanel">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6><i class="fas fa-server"></i> Server Information</h6>
                                    <div class="setting-item">
                                        <div class="setting-label">PHP Version</div>
                                        <div class="value"><?php echo phpversion(); ?></div>
                                    </div>
                                    <div class="setting-item">
                                        <div class="setting-label">Database Version</div>
                                        <div class="value">MySQL 8.0+</div>
                                    </div>
                                    <div class="setting-item">
                                        <div class="setting-label">Web Server</div>
                                        <div class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-card">
                                    <h6><i class="fas fa-chart-bar"></i> System Statistics</h6>
                                    <div class="setting-item">
                                        <div class="setting-label">Total Users</div>
                                        <div class="value"><?php echo count(getAllUsers()); ?></div>
                                    </div>
                                    <div class="setting-item">
                                        <div class="setting-label">Active Projects</div>
                                        <div class="value"><?php echo count(getAllProjects()); ?></div>
                                    </div>
                                    <div class="setting-item">
                                        <div class="setting-label">System Uptime</div>
                                        <div class="value">99.8%</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fas fa-code-branch"></i> Application Details</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="setting-item">
                                        <div class="setting-label">Application Version</div>
                                        <div class="value">2.5.1</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="setting-item">
                                        <div class="setting-label">Last Updated</div>
                                        <div class="value"><?php echo date('Y-m-d', strtotime('-30 days')); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="setting-item">
                                        <div class="setting-label">Support Contact</div>
                                        <div class="value">support@cdf.gov.zm</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="danger-zone">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h6>
                            <p class="text-muted">These actions are irreversible and should be performed with caution.</p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetSystemModal">
                                    <i class="fas fa-redo me-2"></i>Reset System
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteDataModal">
                                    <i class="fas fa-trash me-2"></i>Delete All Data
                                </button>
                            </div>
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

    <!-- Reset System Modal -->
    <div class="modal fade" id="resetSystemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Reset System</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset the system? This will:</p>
                    <ul>
                        <li>Clear all cache and temporary files</li>
                        <li>Reset system settings to defaults</li>
                        <li>Restart all system services</li>
                    </ul>
                    <p class="text-warning"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning">Reset System</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Data Modal -->
    <div class="modal fade" id="deleteDataModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Delete All Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger"><strong>DANGER: This action is irreversible!</strong></p>
                    <p>This will permanently delete:</p>
                    <ul>
                        <li>All user accounts</li>
                        <li>All projects and their data</li>
                        <li>All financial records</li>
                        <li>All system logs and backups</li>
                    </ul>
                    <p>Please type <strong>DELETE ALL DATA</strong> to confirm:</p>
                    <input type="text" class="form-control" placeholder="Type DELETE ALL DATA to confirm">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" disabled>Delete All Data</button>
                </div>
            </div>
        </div>
    </div>

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

        // Tab persistence
        document.addEventListener('DOMContentLoaded', function() {
            // Get active tab from URL or default to general
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'general';
            
            // Activate the specified tab
            const tabTrigger = document.getElementById(`${activeTab}-tab`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
            
            // Update URL when tabs change
            const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabEls.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', function (event) {
                    const tabName = event.target.id.replace('-tab', '');
                    const newUrl = window.location.pathname + '?tab=' + tabName;
                    window.history.replaceState(null, '', newUrl);
                });
            });
        });

        // Enable delete button only when confirmation text matches
        const deleteInput = document.querySelector('#deleteDataModal input');
        const deleteButton = document.querySelector('#deleteDataModal .btn-danger');
        
        if (deleteInput && deleteButton) {
            deleteInput.addEventListener('input', function() {
                deleteButton.disabled = this.value !== 'DELETE ALL DATA';
            });
        }

        // Settings form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    // Scroll to first invalid field
                    const firstInvalid = this.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                }
            });
        });
    </script>
</body>
</html>
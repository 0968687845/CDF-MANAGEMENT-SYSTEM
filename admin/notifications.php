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
$unreadCount = count(array_filter($notifications, function($n) { 
    return isset($n['is_read']) && $n['is_read'] == 0; 
}));

// Handle notification actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $notificationId = $_GET['id'] ?? null;
    
    if ($action === 'mark_read' && $notificationId) {
        if (markNotificationAsRead($notificationId)) {
            $_SESSION['success_message'] = "Notification marked as read";
        } else {
            $_SESSION['error_message'] = "Failed to mark notification as read";
        }
        redirect('notifications.php');
        
    } elseif ($action === 'mark_all_read') {
        if (markAllNotificationsAsRead($_SESSION['user_id'])) {
            $_SESSION['success_message'] = "All notifications marked as read";
        } else {
            $_SESSION['error_message'] = "Failed to mark all notifications as read";
        }
        redirect('notifications.php');
        
    } elseif ($action === 'delete' && $notificationId) {
        if (deleteNotification($notificationId)) {
            $_SESSION['success_message'] = "Notification deleted successfully";
        } else {
            $_SESSION['error_message'] = "Failed to delete notification";
        }
        redirect('notifications.php');
        
    } elseif ($action === 'clear_all') {
        if (clearAllNotifications($_SESSION['user_id'])) {
            $_SESSION['success_message'] = "All notifications cleared";
        } else {
            $_SESSION['error_message'] = "Failed to clear all notifications";
        }
        redirect('notifications.php');
    }
}

// Handle notification creation (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_notification'])) {
    if (createNotification(
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['message'],
        $_POST['type'],
        $_POST['priority']
    )) {
        $_SESSION['success_message'] = "Test notification created successfully";
    } else {
        $_SESSION['error_message'] = "Failed to create test notification";
    }
    redirect('notifications.php');
}

$pageTitle = "Notifications - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Notification management for CDF Management System - Government of Zambia">
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

        /* Notification Styles */
        .notification-item {
            padding: 1.5rem;
            border-left: 4px solid transparent;
            transition: var(--transition);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            background: var(--white);
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .notification-item.unread {
            background: linear-gradient(135deg, #f8fbff 0%, #e8f2ff 100%);
            border-left-color: var(--primary);
            border: 1px solid rgba(26, 78, 138, 0.1);
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .notification-item.read {
            opacity: 0.8;
            background: var(--white);
        }

        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            flex-shrink: 0;
            font-size: 1.25rem;
            transition: var(--transition);
        }

        .notification-item:hover .notification-icon {
            transform: scale(1.1);
        }

        .notification-icon.info { 
            background: rgba(23, 162, 184, 0.1); 
            color: var(--info); 
        }
        .notification-icon.success { 
            background: rgba(40, 167, 69, 0.1); 
            color: var(--success); 
        }
        .notification-icon.warning { 
            background: rgba(255, 193, 7, 0.1); 
            color: var(--warning); 
        }
        .notification-icon.danger { 
            background: rgba(220, 53, 69, 0.1); 
            color: var(--danger); 
        }
        .notification-icon.primary { 
            background: rgba(26, 78, 138, 0.1); 
            color: var(--primary); 
        }

        .notification-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
        }

        .badge-high { background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%); color: white; }
        .badge-medium { background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%); color: var(--dark); }
        .badge-low { background: linear-gradient(135deg, var(--info) 0%, #138496 100%); color: white; }

        .notification-time {
            font-size: 0.875rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-actions {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            opacity: 0;
            transition: var(--transition);
        }

        .notification-item:hover .notification-actions {
            opacity: 1;
        }

        .notification-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
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
        .nav-notification-badge {
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
            
            .notification-item {
                padding: 1rem;
            }
            
            .notification-actions {
                position: static;
                opacity: 1;
                margin-top: 1rem;
                text-align: right;
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

        .notification-item {
            animation: fadeInUp 0.4s ease-out;
        }

        /* Notification Preview */
        .notification-preview {
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }

        .notification-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(transparent, var(--white));
        }

        .notification-item.expanded .notification-preview {
            max-height: none;
        }

        .notification-item.expanded .notification-preview::after {
            display: none;
        }

        .read-more {
            color: var(--primary);
            font-size: 0.875rem;
            cursor: pointer;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .read-more:hover {
            text-decoration: underline;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <li><a class="dropdown-item active" href="notifications.php">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a></li>
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>System Reports
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>System Settings
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="nav-notification-badge"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">System Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? 'No Title'); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at'] ?? 'now'); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'] ?? 'No message', 0, 50)); ?>...</p>
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <h1>Notifications</h1>
                    <p class="lead">Manage your system notifications and alerts</p>
                    <p class="mb-0">
                        Unread: <strong><?php echo $unreadCount; ?></strong> | 
                        Total: <strong><?php echo count($notifications); ?></strong>
                    </p>
                </div>
            </div>
            
            <div class="action-buttons">
                <?php if ($unreadCount > 0): ?>
                <a href="notifications.php?action=mark_all_read" class="btn btn-primary-custom">
                    <i class="fas fa-check-double me-2"></i>Mark All as Read
                </a>
                <?php endif; ?>
                <?php if (count($notifications) > 0): ?>
                <a href="notifications.php?action=clear_all" class="btn btn-outline-custom" onclick="return confirm('Are you sure you want to clear all notifications?')">
                    <i class="fas fa-trash me-2"></i>Clear All
                </a>
                <?php endif; ?>
                <button class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
                    <i class="fas fa-plus me-2"></i>Test Notification
                </button>
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

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="all">All Notifications</option>
                        <option value="unread">Unread Only</option>
                        <option value="read">Read Only</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="all">All Types</option>
                        <option value="info">Information</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Critical</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Priority</label>
                    <select class="form-select" id="priorityFilter">
                        <option value="all">All Priorities</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-bell me-2"></i>All Notifications</h5>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 250px;">
                        <input type="text" class="form-control" placeholder="Search notifications..." id="searchInput">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($notifications) > 0): ?>
                    <div id="notificationsList">
                        <?php foreach ($notifications as $notification): 
                            $isRead = isset($notification['is_read']) ? $notification['is_read'] : 0;
                            $type = $notification['type'] ?? 'info';
                            $priority = $notification['priority'] ?? 'medium';
                            $title = $notification['title'] ?? 'No Title';
                            $message = $notification['message'] ?? 'No message available';
                            $createdAt = $notification['created_at'] ?? 'now';
                        ?>
                        <div class="notification-item <?php echo $isRead ? 'read' : 'unread'; ?>" 
                             data-status="<?php echo $isRead ? 'read' : 'unread'; ?>"
                             data-type="<?php echo $type; ?>"
                             data-priority="<?php echo $priority; ?>">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon <?php echo $type; ?>">
                                    <i class="fas fa-<?php echo getNotificationIcon($type); ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($title); ?></h6>
                                        <span class="notification-badge badge-<?php echo $priority; ?>">
                                            <?php echo ucfirst($priority); ?>
                                        </span>
                                    </div>
                                    <div class="notification-preview">
                                        <p class="mb-2"><?php echo htmlspecialchars($message); ?></p>
                                    </div>
                                    <?php if (strlen($message) > 200): ?>
                                        <span class="read-more" onclick="toggleExpand(this)">Read more</span>
                                    <?php endif; ?>
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo time_elapsed_string($createdAt); ?>
                                        <?php if (!$isRead): ?>
                                            <span class="badge bg-primary ms-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$isRead && isset($notification['id'])): ?>
                                        <a href="notifications.php?action=mark_read&id=<?php echo $notification['id']; ?>" class="btn btn-outline-success" title="Mark as Read">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (isset($notification['id'])): ?>
                                        <a href="notifications.php?action=delete&id=<?php echo $notification['id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this notification?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h5>No Notifications</h5>
                        <p class="text-muted">You're all caught up! There are no notifications to display.</p>
                        <button class="btn btn-primary-custom mt-3" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
                            <i class="fas fa-plus me-2"></i>Create Test Notification
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notification Statistics -->
        <?php if (count($notifications) > 0): ?>
        <div class="row">
            <div class="col-md-3">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-bell fa-2x text-primary mb-3"></i>
                        <h3><?php echo count($notifications); ?></h3>
                        <p class="text-muted mb-0">Total Notifications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-envelope fa-2x text-warning mb-3"></i>
                        <h3><?php echo $unreadCount; ?></h3>
                        <p class="text-muted mb-0">Unread</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                        <h3><?php echo count(array_filter($notifications, function($n) { 
                            return isset($n['priority']) && $n['priority'] === 'high'; 
                        })); ?></h3>
                        <p class="text-muted mb-0">High Priority</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-day fa-2x text-success mb-3"></i>
                        <h3><?php echo count(array_filter($notifications, function($n) { 
                            return isset($n['created_at']) && date('Y-m-d', strtotime($n['created_at'])) === date('Y-m-d'); 
                        })); ?></h3>
                        <p class="text-muted mb-0">Today</p>
                    </div>
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

    <!-- Create Notification Modal -->
    <div class="modal fade" id="createNotificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Create Test Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="notifications.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Notification Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="Test Notification" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required>This is a test notification created to verify the notification system is working properly.</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="info">Information</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="danger">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_notification" class="btn btn-primary-custom">Create Notification</button>
                    </div>
                </form>
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

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const priorityFilter = document.getElementById('priorityFilter');
            const searchInput = document.getElementById('searchInput');
            const notificationItems = document.querySelectorAll('.notification-item');

            function filterNotifications() {
                const statusValue = statusFilter.value;
                const typeValue = typeFilter.value;
                const priorityValue = priorityFilter.value;
                const searchTerm = searchInput.value.toLowerCase();

                notificationItems.forEach(item => {
                    const status = item.getAttribute('data-status');
                    const type = item.getAttribute('data-type');
                    const priority = item.getAttribute('data-priority');
                    const title = item.querySelector('h6').textContent.toLowerCase();
                    const message = item.querySelector('.notification-preview p').textContent.toLowerCase();

                    const statusMatch = statusValue === 'all' || status === statusValue;
                    const typeMatch = typeValue === 'all' || type === typeValue;
                    const priorityMatch = priorityValue === 'all' || priority === priorityValue;
                    const searchMatch = searchTerm === '' || title.includes(searchTerm) || message.includes(searchTerm);

                    if (statusMatch && typeMatch && priorityMatch && searchMatch) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            statusFilter.addEventListener('change', filterNotifications);
            typeFilter.addEventListener('change', filterNotifications);
            priorityFilter.addEventListener('change', filterNotifications);
            searchInput.addEventListener('input', filterNotifications);

            // Auto-mark as read when clicked
            notificationItems.forEach(item => {
                if (item.classList.contains('unread')) {
                    item.addEventListener('click', function(e) {
                        if (!e.target.closest('.notification-actions') && !e.target.classList.contains('read-more')) {
                            const markReadLink = item.querySelector('a[href*="mark_read"]');
                            if (markReadLink) {
                                window.location.href = markReadLink.href;
                            }
                        }
                    });
                }
            });
        });

        // Toggle expand/collapse for long messages
        function toggleExpand(element) {
            const notificationItem = element.closest('.notification-item');
            const isExpanded = notificationItem.classList.contains('expanded');
            
            if (isExpanded) {
                notificationItem.classList.remove('expanded');
                element.textContent = 'Read more';
            } else {
                notificationItem.classList.add('expanded');
                element.textContent = 'Read less';
            }
        }

        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            const unreadCount = document.querySelectorAll('.notification-item.unread').length;
            const navBadge = document.querySelector('.nav-notification-badge');
            
            if (unreadCount > 0) {
                if (navBadge) {
                    navBadge.textContent = unreadCount;
                } else {
                    // Create badge if it doesn't exist
                    const bellIcon = document.querySelector('.nav-link.active .fa-bell');
                    if (bellIcon) {
                        const badge = document.createElement('span');
                        badge.className = 'nav-notification-badge';
                        badge.textContent = unreadCount;
                        bellIcon.parentNode.appendChild(badge);
                    }
                }
            } else if (navBadge) {
                navBadge.remove();
            }
        }, 30000);

        // Mark as read on hover (with delay)
        let hoverTimer;
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('mouseenter', function() {
                hoverTimer = setTimeout(() => {
                    this.style.opacity = '0.7';
                }, 2000);
            });

            item.addEventListener('mouseleave', function() {
                clearTimeout(hoverTimer);
                this.style.opacity = '1';
            });
        });
    </script>
</body>
</html>
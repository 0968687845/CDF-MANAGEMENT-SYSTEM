<?php
require_once '../../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['notification_ids'])) {
    $notification_ids = $_POST['notification_ids'];
    $action = $_POST['bulk_action'];
    
    switch ($action) {
        case 'mark_read':
            foreach ($notification_ids as $notification_id) {
                markNotificationAsRead($notification_id);
            }
            $_SESSION['success'] = count($notification_ids) . ' notifications marked as read';
            break;
            
        case 'mark_unread':
            foreach ($notification_ids as $notification_id) {
                // Create a function to mark as unread
                global $pdo;
                $query = "UPDATE notifications SET is_read = 0 WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':id', $notification_id);
                $stmt->execute();
            }
            $_SESSION['success'] = count($notification_ids) . ' notifications marked as unread';
            break;
            
        case 'delete':
            foreach ($notification_ids as $notification_id) {
                deleteNotification($notification_id);
            }
            $_SESSION['success'] = count($notification_ids) . ' notifications deleted';
            break;
    }
    
    redirect('notifications.php');
}

// Handle single actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $notification_id = $_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'read':
            markNotificationAsRead($notification_id);
            $_SESSION['success'] = 'Notification marked as read';
            break;
            
        case 'unread':
            global $pdo;
            $query = "UPDATE notifications SET is_read = 0 WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $notification_id);
            $stmt->execute();
            $_SESSION['success'] = 'Notification marked as unread';
            break;
            
        case 'delete':
            deleteNotification($notification_id);
            $_SESSION['success'] = 'Notification deleted';
            break;
            
        case 'clear_all':
            clearAllNotifications($_SESSION['user_id']);
            $_SESSION['success'] = 'All notifications cleared';
            break;
    }
    
    redirect('notifications.php');
}

// Get all notifications for the admin
$all_notifications = getAllNotifications();

$pageTitle = "Notifications Management - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Notifications management for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
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
            filter: brightness(0) invert(1);
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
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

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem 0 1.5rem;
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

        /* Notification Items */
        .notification-item {
            padding: 1.5rem;
            border-left: 4px solid transparent;
            transition: var(--transition);
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            background: var(--white);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .notification-item:hover {
            background: rgba(13, 110, 253, 0.03);
            border-left-color: var(--primary);
            transform: translateX(8px);
            box-shadow: var(--shadow);
        }

        .notification-item.unread {
            background: rgba(13, 110, 253, 0.05);
            border-left-color: var(--primary);
        }

        .notification-item.urgent {
            background: rgba(220, 53, 69, 0.05);
            border-left-color: var(--danger);
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

        .notification-icon.primary { background: rgba(26, 78, 138, 0.1); color: var(--primary); }
        .notification-icon.success { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .notification-icon.warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .notification-icon.danger { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .notification-icon.info { background: rgba(23, 162, 184, 0.1); color: var(--info); }

        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        .badge-unread {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .badge-urgent {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
        }

        .badge-read {
            background: linear-gradient(135deg, var(--success) 0%, #24a140 100%);
            color: white;
        }

        /* Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, #c4952e 100%);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: var(--dark);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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

        /* Table Improvements */
        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table th {
            border-top: none;
            font-weight: 700;
            color: var(--primary);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.25rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 1.25rem;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background: rgba(26, 78, 138, 0.03);
            transform: scale(1.01);
            transition: var(--transition);
        }

        /* Bulk Actions */
        .bulk-actions {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
                padding: 1.5rem 0 1rem;
            }
            
            .notification-item {
                padding: 1rem;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
                margin-right: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <img src="../../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="../projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="../assignments.php">
                                <i class="fas fa-user-tie me-2"></i>Officer Assignments
                            </a></li>
                            <li><a class="dropdown-item" href="../reports.php">
                                <i class="fas fa-chart-bar me-2"></i>System Reports
                            </a></li>
                            <li><a class="dropdown-item" href="notifications.php">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a></li>
                            <li><a class="dropdown-item" href="../settings.php">
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
                            <li><a class="dropdown-item" href="../profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="../settings.php">
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
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-2">Notifications Management</h1>
                    <p class="lead mb-0">Manage system notifications and alerts</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <a href="?action=clear_all" class="btn btn-outline-custom" onclick="return confirm('Are you sure you want to clear all notifications?')">
                            <i class="fas fa-trash-alt me-2"></i>Clear All
                        </a>
                        <a href="../../admin_dashboard.php" class="btn btn-primary-custom">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Bulk Actions -->
        <?php if (count($all_notifications) > 0): ?>
        <div class="bulk-actions">
            <form method="POST" id="bulkForm">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label fw-bold" for="selectAll">
                                Select All Notifications
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <select name="bulk_action" class="form-select" style="width: auto;">
                                <option value="">Bulk Actions</option>
                                <option value="mark_read">Mark as Read</option>
                                <option value="mark_unread">Mark as Unread</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" class="btn btn-primary-custom btn-sm">Apply</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Notifications List -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-bell me-2"></i>System Notifications</h5>
                <span class="badge bg-primary"><?php echo count($all_notifications); ?> total</span>
            </div>
            <div class="card-body">
                <?php if (count($all_notifications) > 0): ?>
                    <div class="notifications-list">
                        <?php foreach ($all_notifications as $notification): ?>
                            <div class="notification-item <?php echo (!$notification['is_read']) ? 'unread' : ''; ?> <?php echo (isNotificationUrgent($notification)) ? 'urgent' : ''; ?>">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon <?php echo getNotificationIconClass($notification); ?>">
                                        <i class="fas fa-<?php echo getNotificationIcon($notification); ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <div class="d-flex gap-2">
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge badge-unread">Unread</span>
                                                <?php else: ?>
                                                    <span class="badge badge-read">Read</span>
                                                <?php endif; ?>
                                                
                                                <?php if (isNotificationUrgent($notification)): ?>
                                                    <span class="badge badge-urgent">Urgent</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo time_elapsed_string($notification['created_at']); ?>
                                            </small>
                                            <div class="notification-actions">
                                                <?php if (!$notification['is_read']): ?>
                                                    <a href="?action=read&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-check me-1"></i>Mark Read
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?action=unread&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-envelope me-1"></i>Mark Unread
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?')">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <input type="checkbox" name="notification_ids[]" value="<?php echo $notification['id']; ?>" class="notification-checkbox form-check-input">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4 class="text-muted">No Notifications</h4>
                        <p class="text-muted mb-4">You don't have any notifications at the moment.</p>
                        <a href="../../admin_dashboard.php" class="btn btn-primary-custom">
                            <i class="fas fa-home me-2"></i>Return to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="row">
            <div class="col-md-4">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-bell fa-3x text-primary mb-3"></i>
                        <h3 class="text-primary"><?php echo count($all_notifications); ?></h3>
                        <p class="text-muted mb-0">Total Notifications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-envelope fa-3x text-warning mb-3"></i>
                        <h3 class="text-warning"><?php echo count(array_filter($all_notifications, function($n) { return !$n['is_read']; })); ?></h3>
                        <p class="text-muted mb-0">Unread Notifications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h3 class="text-danger"><?php echo count(array_filter($all_notifications, 'isNotificationUrgent')); ?></h3>
                        <p class="text-muted mb-0">Urgent Notifications</p>
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
                    <img src="../../coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3">
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
        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.notification-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Form validation for bulk actions
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const selectedAction = document.querySelector('select[name="bulk_action"]').value;
            const selectedNotifications = document.querySelectorAll('.notification-checkbox:checked');
            
            if (!selectedAction) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return false;
            }
            
            if (selectedNotifications.length === 0) {
                e.preventDefault();
                alert('Please select at least one notification.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>

<?php
// Helper function to get all notifications for admin
function getAllNotifications() {
    global $pdo;
    
    $query = "SELECT * FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

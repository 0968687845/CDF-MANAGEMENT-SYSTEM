<?php
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Allow both beneficiaries and officers to access notifications
$user_role = getUserRole();
if (!in_array($user_role, ['beneficiary', 'officer'])) {
    redirect('../index.php');
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../index.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_as_read'])) {
        $notification_id = $_POST['notification_id'] ?? '';
        if (!empty($notification_id)) {
            if (markNotificationAsRead($notification_id)) {
                $_SESSION['success'] = "Notification marked as read.";
                // Refresh notifications
                $notifications = getNotifications($_SESSION['user_id']);
            } else {
                $_SESSION['error'] = "Failed to mark notification as read.";
            }
        }
    }
    
    if (isset($_POST['mark_all_read'])) {
        if (markAllNotificationsAsRead($_SESSION['user_id'])) {
            $_SESSION['success'] = "All notifications marked as read.";
            // Refresh notifications
            $notifications = getNotifications($_SESSION['user_id']);
        } else {
            $_SESSION['error'] = "Failed to mark all notifications as read.";
        }
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = $_POST['notification_id'] ?? '';
        if (!empty($notification_id)) {
            if (deleteNotification($notification_id)) {
                $_SESSION['success'] = "Notification deleted successfully.";
                // Refresh notifications
                $notifications = getNotifications($_SESSION['user_id']);
            } else {
                $_SESSION['error'] = "Failed to delete notification.";
            }
        }
    }
    
    if (isset($_POST['clear_all'])) {
        if (clearAllNotifications($_SESSION['user_id'])) {
            $_SESSION['success'] = "All notifications cleared.";
            // Refresh notifications
            $notifications = getNotifications($_SESSION['user_id']);
        } else {
            $_SESSION['error'] = "Failed to clear all notifications.";
        }
    }
}

// Get notification statistics
$unread_count = count(array_filter($notifications, function($notification) {
    return !$notification['is_read'];
}));
$total_count = count($notifications);

$pageTitle = "Notifications - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Notifications management for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add your notification styles here - use the same styles from your existing notifications.php */
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
            --white: #ffffff;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
            --border-radius: 10px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--primary);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 76px;
        }

        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
        }

        .notification-item:hover {
            background-color: rgba(26, 78, 138, 0.05);
        }

        .notification-item.unread {
            background-color: rgba(26, 78, 138, 0.08);
            border-left: 4px solid var(--primary);
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .notification-icon.info { background: rgba(23, 162, 184, 0.1); color: var(--info); }
        .notification-icon.success { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .notification-icon.warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .notification-icon.danger { background: rgba(220, 53, 69, 0.1); color: var(--danger); }

        .badge-unread {
            background: var(--primary);
            color: white;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-urgent {
            background: var(--danger);
            color: white;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .btn-primary-custom {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--gray);
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
                <?php echo $user_role === 'officer' ? 'CDF M&E Officer Portal' : 'CDF Beneficiary Portal'; ?>
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
                            <?php if ($user_role === 'officer'): ?>
                                <li><a class="dropdown-item" href="login.php">
                                    <i class="fas fa-comments me-2"></i>Communication Center
                                </a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="messages.php">
                                    <i class="fas fa-comments me-2"></i>Chats
                                </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item active" href="notifications.php">
                                <i class="fas fa-bell me-2"></i>Notifications
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
                                            <small class="text-muted"><?php echo $user_role === 'officer' ? 'M&E Officer' : 'CDF Beneficiary'; ?></small>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-bell me-2"></i>Notifications</h1>
                    <p class="lead mb-0">Manage your CDF system notifications and alerts</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="mark_all_read" class="btn btn-outline-light">
                                <i class="fas fa-check-double me-2"></i>Mark All Read
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="clear_all" class="btn btn-outline-light" onclick="return confirm('Are you sure you want to clear all notifications?');">
                                <i class="fas fa-trash me-2"></i>Clear All
                            </button>
                        </form>
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

        <!-- Notification Statistics -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_count; ?></div>
                        <div class="stat-title">Total Notifications</div>
                        <div class="stat-subtitle">All system notifications</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $unread_count; ?></div>
                        <div class="stat-title">Unread</div>
                        <div class="stat-subtitle">Require your attention</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_count - $unread_count; ?></div>
                        <div class="stat-title">Read</div>
                        <div class="stat-subtitle">Already reviewed</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_slice($notifications, 0, 7)); ?></div>
                        <div class="stat-title">Last 7 Days</div>
                        <div class="stat-subtitle">Recent activity</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list me-2"></i>All Notifications</h5>
                <div>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge badge-unread"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                        <div class="d-flex align-items-start">
                            <div class="notification-icon info">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge badge-unread ms-2">New</span>
                                            <?php endif; ?>
                                        </h6>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                                </div>
                                <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="action-buttons">
                                    <?php if (!$notification['is_read']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-check me-1"></i>Mark as Read
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?');">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <h4 class="text-muted">No Notifications</h4>
                        <p class="text-muted mb-4">You don't have any notifications at the moment.</p>
                        <p class="text-muted small">You'll see important updates and alerts here when they become available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
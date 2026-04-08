<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$users = getAllUsers();
$notifications = getNotifications($_SESSION['user_id']);

// Handle user actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $userId = $_GET['id'] ?? null;
    
    if ($action === 'edit' && $userId) {
        // Get user data for editing
        $editUser = getUserById($userId);
    } elseif ($action === 'delete' && $userId) {
        // Delete user (prevent self-deletion)
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "You cannot delete your own account";
        } else {
            if (deleteUser($userId)) {
                $_SESSION['success_message'] = "User deleted successfully";
            } else {
                $_SESSION['error_message'] = "Failed to delete user";
            }
        }
        redirect('users.php');
    } elseif ($action === 'toggle_status' && $userId) {
        // Toggle user status (prevent self-deactivation)
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "You cannot deactivate your own account";
        } else {
            $currentUser = getUserById($userId);
            $newStatus = $currentUser['status'] === 'active' ? 'inactive' : 'active';
            if (updateUserStatus($userId, $newStatus)) {
                $_SESSION['success_message'] = "User status updated successfully";
            } else {
                $_SESSION['error_message'] = "Failed to update user status";
            }
        }
        redirect('users.php');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        // Create new user
        $userData = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'role' => $_POST['role'],
            'status' => 'active'
        ];
        
        // Set default password (users should reset it)
        $tempPassword = 'Temp123!';
        
        if (createUser($userData, $tempPassword)) {
            $_SESSION['success_message'] = "User created successfully. Temporary password: " . $tempPassword;
        } else {
            $_SESSION['error_message'] = "Failed to create user. Email may already exist.";
        }
        redirect('users.php');
    } elseif (isset($_POST['update_user'])) {
        // Update existing user
        $userId = $_POST['user_id'];
        $userData = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'role' => $_POST['role']
        ];
        
        if (updateUser($userId, $userData)) {
            $_SESSION['success_message'] = "User updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update user";
        }
        redirect('users.php');
    } elseif (isset($_POST['reset_password'])) {
        // Reset user password
        $userId = $_POST['user_id'];
        $tempPassword = 'Temp123!';
        
        if (resetUserPassword($userId, $tempPassword)) {
            $_SESSION['success_message'] = "Password reset successfully. New temporary password: " . $tempPassword;
        } else {
            $_SESSION['error_message'] = "Failed to reset password";
        }
        redirect('users.php');
    }
}

$pageTitle = "User Management - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="User management for CDF Management System - Government of Zambia">
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

        /* User Cards */
        .user-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.25rem;
            transition: var(--transition);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--white);
        }

        .avatar-admin { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); }
        .avatar-officer { background: linear-gradient(135deg, var(--info) 0%, #138496 100%); }
        .avatar-beneficiary { background: linear-gradient(135deg, var(--success) 0%, #24a140 100%); }

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
        .badge-active { 
            background: linear-gradient(135deg, var(--success) 0%, #24a140 100%);
            color: white;
        }
        .badge-inactive { 
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
        }
        .badge-admin { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        .badge-officer { 
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            color: white;
        }
        .badge-beneficiary { 
            background: linear-gradient(135deg, var(--success) 0%, #28a745 100%);
            color: white;
        }

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

        /* Button Groups */
        .btn-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .btn-group .btn {
            border: none;
            padding: 0.5rem 0.75rem;
            transition: var(--transition);
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
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

        /* Modal Styling */
        .modal-content {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
        }

        .modal-title {
            color: var(--primary);
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Stats Cards */
        .stats-container {
            margin: 2.5rem 0;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 5px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.75rem;
            line-height: 1;
        }

        .stat-title {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            opacity: 0.8;
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
            
            .table-responsive {
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-sm);
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
        .content-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Alert Styling */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-sm);
        }

        /* User Role Colors */
        .role-admin { color: var(--primary); }
        .role-officer { color: var(--info); }
        .role-beneficiary { color: var(--success); }
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
                            <li><a class="dropdown-item active" href="users.php">
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
                            <li><a class="dropdown-item" href="settings.php">
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
                    <i class="fas fa-users"></i>
                </div>
                <div class="profile-info">
                    <h1>User Management</h1>
                    <p class="lead">Manage system users and their permissions</p>
                    <p class="mb-0">Total Users: <strong><?php echo count($users); ?></strong> | 
                    Active Users: <strong><?php echo count(array_filter($users, function($u) { return ($u['status'] ?? 'active') === 'active'; })); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-2"></i>Create New User
                </button>
                <a href="users.php" class="btn btn-outline-custom">
                    <i class="fas fa-sync-alt me-2"></i>Refresh List
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

        <!-- User Statistics -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($users); ?></div>
                        <div class="stat-title">Total Users</div>
                        <div class="stat-subtitle">All system users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return ($u['role'] ?? '') === 'admin'; })); ?></div>
                        <div class="stat-title">Administrators</div>
                        <div class="stat-subtitle">System administrators</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return ($u['role'] ?? '') === 'officer'; })); ?></div>
                        <div class="stat-title">M&E Officers</div>
                        <div class="stat-subtitle">Monitoring officers</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return ($u['role'] ?? '') === 'beneficiary'; })); ?></div>
                        <div class="stat-title">Beneficiaries</div>
                        <div class="stat-subtitle">Project beneficiaries</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-users me-2"></i>All Users</h5>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 250px;">
                        <input type="text" class="form-control" placeholder="Search users..." id="searchInput">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <select class="form-select" style="width: 180px;" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="admin">Administrator</option>
                        <option value="officer">M&E Officer</option>
                        <option value="beneficiary">Beneficiary</option>
                    </select>
                    <select class="form-select" style="width: 180px;" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar avatar-<?php echo $user['role'] ?? 'beneficiary'; ?> me-3">
                                                <?php echo strtoupper(substr($user['first_name'] ?? '', 0, 1) . substr($user['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></strong>
                                                <?php if (($user['id'] ?? '') == $_SESSION['user_id']): ?>
                                                    <span class="badge badge-primary ms-2">You</span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted">ID: <?php echo $user['id'] ?? ''; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-envelope me-2 text-muted"></i>
                                            <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                            <?php if (!empty($user['phone'])): ?>
                                            <br>
                                            <i class="fas fa-phone me-2 text-muted"></i>
                                            <?php echo htmlspecialchars($user['phone']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] ?? 'beneficiary'; ?>">
                                            <?php echo ucfirst($user['role'] ?? 'beneficiary'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['status'] ?? 'active'; ?>">
                                            <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['last_login'])): ?>
                                            <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'] ?? date('Y-m-d H:i:s'))); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-outline-warning" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (($user['id'] ?? '') != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>" title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="btn btn-outline-<?php echo ($user['status'] ?? 'active') === 'active' ? 'warning' : 'success'; ?>" title="<?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate'; ?> User">
                                                    <i class="fas fa-<?php echo ($user['status'] ?? 'active') === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="btn btn-outline-secondary disabled" title="Cannot modify your own account">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Users Found</h5>
                        <p class="text-muted mb-4">Get started by creating the first user.</p>
                        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="fas fa-user-plus me-2"></i>Create User
                        </button>
                    </div>
                <?php endif; ?>
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

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="users.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">User Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Administrator</option>
                                <option value="officer">Monitoring & Evaluation Officer</option>
                                <option value="beneficiary">Beneficiary</option>
                            </select>
                            <div class="form-text">
                                <strong>Administrator:</strong> Full system access<br>
                                <strong>M&E Officer:</strong> Project monitoring and reporting<br>
                                <strong>Beneficiary:</strong> Project beneficiary access
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            A temporary password will be generated automatically. The user will be required to reset their password on first login.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_user" class="btn btn-primary-custom">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <?php if (isset($editUser)): ?>
    <div class="modal fade show" id="editUserModal" tabindex="-1" style="display: block; padding-right: 17px;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit User</h5>
                    <a href="users.php" class="btn-close"></a>
                </div>
                <form method="POST" action="users.php">
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" value="<?php echo htmlspecialchars($editUser['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" value="<?php echo htmlspecialchars($editUser['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone" value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">User Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="officer" <?php echo ($editUser['role'] ?? '') === 'officer' ? 'selected' : ''; ?>>Monitoring & Evaluation Officer</option>
                                <option value="beneficiary" <?php echo ($editUser['role'] ?? '') === 'beneficiary' ? 'selected' : ''; ?>>Beneficiary</option>
                            </select>
                        </div>
                        <?php if (($editUser['id'] ?? '') != $_SESSION['user_id']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Changing a user's role may affect their system permissions and access levels.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You are editing your own profile. Some restrictions may apply.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_user" class="btn btn-primary-custom">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2 text-warning"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="users.php">
                    <input type="hidden" name="user_id" id="resetPasswordUserId">
                    <div class="modal-body">
                        <p>Are you sure you want to reset the password for <strong id="resetPasswordUserName"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            The user's password will be reset to a temporary password. They will be required to set a new password on next login.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user: <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone. All user data will be permanently removed.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete User</a>
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

        // Reset Password Modal Handler
        const resetPasswordModal = document.getElementById('resetPasswordModal');
        if (resetPasswordModal) {
            resetPasswordModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                document.getElementById('resetPasswordUserName').textContent = userName;
                document.getElementById('resetPasswordUserId').value = userId;
            });
        }

        // Delete User Modal Handler
        const deleteUserModal = document.getElementById('deleteUserModal');
        if (deleteUserModal) {
            deleteUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                
                document.getElementById('deleteUserName').textContent = userName;
                document.getElementById('confirmDeleteBtn').href = 'users.php?action=delete&id=' + userId;
            });
        }

        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const statusFilter = document.getElementById('statusFilter');
            const usersTable = document.getElementById('usersTable');
            
            if (searchInput && usersTable) {
                searchInput.addEventListener('keyup', filterUsers);
            }
            
            if (roleFilter && usersTable) {
                roleFilter.addEventListener('change', filterUsers);
            }
            
            if (statusFilter && usersTable) {
                statusFilter.addEventListener('change', filterUsers);
            }
            
            function filterUsers() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleValue = roleFilter.value;
                const statusValue = statusFilter.value;
                const rows = usersTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const name = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                    const role = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                    const status = rows[i].getElementsByTagName('td')[3].textContent.toLowerCase();
                    
                    const nameMatch = name.includes(searchTerm);
                    const roleMatch = roleValue === '' || role.includes(roleValue);
                    const statusMatch = statusValue === '' || status.includes(statusValue);
                    
                    if (nameMatch && roleMatch && statusMatch) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });

        // Auto-show edit modal if editing
        <?php if (isset($editUser)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
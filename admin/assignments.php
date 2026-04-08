<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$projects = getAllProjects();
$officers = getUsersByRole('officer');
$notifications = getNotifications($_SESSION['user_id']);

// Handle assignment actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $projectId = $_GET['project_id'] ?? null;
    $officerId = $_GET['officer_id'] ?? null;
    
    if ($action === 'assign' && $projectId && $officerId) {
        // Assign officer to project
        if (assignOfficerToProject($projectId, $officerId)) {
            $_SESSION['success_message'] = "Officer assigned to project successfully";
        } else {
            $_SESSION['error_message'] = "Failed to assign officer to project";
        }
        redirect('assignments.php');
    } elseif ($action === 'unassign' && $projectId) {
        // Remove officer from project
        if (removeOfficerFromProject($projectId)) {
            $_SESSION['success_message'] = "Officer removed from project successfully";
        } else {
            $_SESSION['error_message'] = "Failed to remove officer from project";
        }
        redirect('assignments.php');
    }
}

// Handle bulk assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect(basename($_SERVER['PHP_SELF']));
        exit;
    }
    if (isset($_POST['bulk_assign'])) {
        $projectIds = $_POST['project_ids'] ?? [];
        $officerId = $_POST['officer_id'];
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($projectIds as $projectId) {
            if (assignOfficerToProject($projectId, $officerId)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        if ($successCount > 0) {
            $_SESSION['success_message'] = "Successfully assigned officer to {$successCount} project(s)";
        }
        if ($errorCount > 0) {
            $_SESSION['error_message'] = "Failed to assign officer to {$errorCount} project(s)";
        }
        
        redirect('assignments.php');
    }
}

$pageTitle = "Officer Assignments - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Officer assignments for CDF Management System - Government of Zambia">
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

        /* Project Cards */
        .project-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.25rem;
            transition: var(--transition);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
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
        .badge-completed { 
            background: linear-gradient(135deg, var(--success) 0%, #24a140 100%);
            color: white;
        }
        .badge-in-progress { 
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: var(--dark);
        }
        .badge-delayed { 
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
        }
        .badge-planning { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        .badge-assigned { 
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            color: white;
        }
        .badge-unassigned {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: var(--dark);
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

        /* Officer Cards */
        .officer-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border-left: 4px solid var(--info);
            margin-bottom: 1rem;
        }

        .officer-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .officer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 1.25rem;
            margin-right: 1rem;
        }

        /* Assignment Status */
        .assignment-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .assigned {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .unassigned {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        /* Bulk Assignment */
        .bulk-assignment-panel {
            background: var(--info-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--info);
        }

        /* Project Selection */
        .project-checkbox {
            margin-right: 0.5rem;
        }

        .project-selection-item {
            padding: 0.75rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .project-selection-item:hover {
            background: var(--light);
            border-color: var(--primary);
        }

        .project-selection-item.selected {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--white);
        }

        /* Workload Indicators */
        .workload-light { color: var(--success); }
        .workload-medium { color: var(--warning); }
        .workload-heavy { color: var(--danger); }

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
                            <li><a class="dropdown-item active" href="assignments.php">
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
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="profile-info">
                    <h1>Officer Assignments</h1>
                    <p class="lead">Assign monitoring officers to oversee CDF projects</p>
                    <p class="mb-0">
                        Total Projects: <strong><?php echo count($projects); ?></strong> | 
                        Unassigned Projects: <strong><?php echo count(array_filter($projects, function($p) { return empty($p['officer_id']); })); ?></strong> |
                        Available Officers: <strong><?php echo count($officers); ?></strong>
                    </p>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#bulkAssignmentModal">
                    <i class="fas fa-users me-2"></i>Bulk Assignments
                </button>
                <a href="assignments.php" class="btn btn-outline-custom">
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

        <!-- Assignment Statistics -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($projects); ?></div>
                        <div class="stat-title">Total Projects</div>
                        <div class="stat-subtitle">All CDF projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($projects, function($p) { return !empty($p['officer_id']); })); ?></div>
                        <div class="stat-title">Assigned Projects</div>
                        <div class="stat-subtitle">With monitoring officers</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($projects, function($p) { return empty($p['officer_id']); })); ?></div>
                        <div class="stat-title">Unassigned Projects</div>
                        <div class="stat-subtitle">Need monitoring officers</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($officers); ?></div>
                        <div class="stat-title">Available Officers</div>
                        <div class="stat-subtitle">M&E officers</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Officers -->
        <div class="content-card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user-tie me-2"></i>Available Monitoring Officers</h5>
            </div>
            <div class="card-body">
                <?php if (count($officers) > 0): ?>
                    <div class="row">
                        <?php foreach ($officers as $officer): 
                            $projectCount = getOfficerProjectCount($officer['id']);
                            $workloadClass = $projectCount < 3 ? 'workload-light' : ($projectCount < 6 ? 'workload-medium' : 'workload-heavy');
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="officer-card">
                                <div class="d-flex align-items-center">
                                    <div class="officer-avatar">
                                        <?php echo strtoupper(substr($officer['first_name'], 0, 1) . substr($officer['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></h6>
                                        <p class="mb-1 text-muted small"><?php echo htmlspecialchars($officer['email']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge badge-assigned">
                                                <i class="fas fa-project-diagram me-1"></i>
                                                <?php echo $projectCount; ?> projects
                                            </span>
                                            <span class="small <?php echo $workloadClass; ?>">
                                                <i class="fas fa-chart-line me-1"></i>
                                                <?php echo $projectCount < 3 ? 'Light' : ($projectCount < 6 ? 'Medium' : 'Heavy'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Monitoring Officers Available</h5>
                        <p class="text-muted mb-4">Create M&E officer accounts in user management first.</p>
                        <a href="users.php" class="btn btn-primary-custom">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Projects Assignment Table -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-project-diagram me-2"></i>Project Assignments</h5>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 250px;">
                        <input type="text" class="form-control" placeholder="Search projects..." id="searchInput">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <select class="form-select" style="width: 180px;" id="assignmentFilter">
                        <option value="">All Projects</option>
                        <option value="assigned">Assigned</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($projects) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="projectsTable">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="selectAllProjects" class="form-check-input">
                                    </th>
                                    <th>Project Details</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Budget</th>
                                    <th>Current Officer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input project-checkbox" name="project_ids[]" value="<?php echo $project['id']; ?>">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?>...</small>
                                    </td>
                                    <td><?php echo htmlspecialchars($project['location'] ?? 'Not specified'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                            <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td>ZMW <?php echo number_format($project['budget'], 0); ?></td>
                                    <td>
                                        <?php if ($project['officer_name'] ?? false): ?>
                                            <div class="assignment-status assigned">
                                                <i class="fas fa-user-check"></i>
                                                <?php echo htmlspecialchars($project['officer_name']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="assignment-status unassigned">
                                                <i class="fas fa-user-times"></i>
                                                Not Assigned
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (empty($project['officer_id'])): ?>
                                                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#assignOfficerModal" data-project-id="<?php echo $project['id']; ?>" data-project-title="<?php echo htmlspecialchars($project['title']); ?>">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reassignOfficerModal" data-project-id="<?php echo $project['id']; ?>" data-project-title="<?php echo htmlspecialchars($project['title']); ?>" data-current-officer="<?php echo htmlspecialchars($project['officer_name']); ?>">
                                                    <i class="fas fa-user-edit"></i>
                                                </button>
                                                <a href="assignments.php?action=unassign&project_id=<?php echo $project['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to remove the officer from this project?')">
                                                    <i class="fas fa-user-minus"></i>
                                                </a>
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
                        <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Projects Found</h5>
                        <p class="text-muted mb-4">Create projects first to assign monitoring officers.</p>
                        <a href="projects.php" class="btn btn-primary-custom">
                            <i class="fas fa-plus-circle me-2"></i>Create Projects
                        </a>
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

    <!-- Bulk Assignment Modal -->
    <div class="modal fade" id="bulkAssignmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Bulk Officer Assignments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="assignments.php">
                    <div class="modal-body">
                        <div class="bulk-assignment-panel">
                            <h6><i class="fas fa-info-circle me-2"></i>Assign multiple projects to one officer</h6>
                            <p class="mb-0">Select projects from the list below and choose an officer to assign them all at once.</p>
                                <?= csrfField() ?>
                        </div>

                        <div class="mb-4">
                            <label for="bulk_officer_id" class="form-label">Select Monitoring Officer</label>
                            <select class="form-select" id="bulk_officer_id" name="officer_id" required>
                                <option value="">Choose an officer...</option>
                                <?php foreach ($officers as $officer): ?>
                                <option value="<?php echo $officer['id']; ?>">
                                    <?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>
                                    (Currently monitoring <?php echo getOfficerProjectCount($officer['id']); ?> projects)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Projects to Assign</label>
                            <div class="project-selection-list" style="max-height: 300px; overflow-y: auto;">
                                <?php 
                                $unassignedProjects = array_filter($projects, function($p) { return empty($p['officer_id']); });
                                if (count($unassignedProjects) > 0): ?>
                                    <?php foreach ($unassignedProjects as $project): ?>
                                    <div class="project-selection-item">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="project_ids[]" value="<?php echo $project['id']; ?>" id="project_<?php echo $project['id']; ?>">
                                            <label class="form-check-label w-100" for="project_<?php echo $project['id']; ?>">
                                                <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($project['location']); ?> | 
                                                    ZMW <?php echo number_format($project['budget'], 0); ?> | 
                                                    <span class="badge badge-<?php echo $project['status']; ?>"><?php echo ucfirst($project['status']); ?></span>
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                        <p class="text-muted mb-0">All projects are already assigned to officers.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (count($unassignedProjects) > 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tip:</strong> This will assign all selected projects to the chosen officer. 
                            You can reassign individual projects later if needed.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if (count($unassignedProjects) > 0): ?>
                        <button type="submit" name="bulk_assign" class="btn btn-primary-custom">Assign Selected Projects</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Officer Modal -->
    <div class="modal fade" id="assignOfficerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Officer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Assign an officer to monitor: <strong id="assignProjectTitle"></strong></p>
                    
                    <div class="list-group">
                        <?php foreach ($officers as $officer): 
                            $projectCount = getOfficerProjectCount($officer['id']);
                            $workloadClass = $projectCount < 3 ? 'list-group-item-success' : ($projectCount < 6 ? 'list-group-item-warning' : 'list-group-item-danger');
                        ?>
                        <a href="assignments.php?action=assign&project_id=&officer_id=<?php echo $officer['id']; ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center assign-officer-link <?php echo $workloadClass; ?>"
                           data-project-id="">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($officer['email']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary rounded-pill"><?php echo $projectCount; ?> projects</span>
                                <br>
                                <small><?php echo $projectCount < 3 ? 'Light' : ($projectCount < 6 ? 'Medium' : 'Heavy'); ?> workload</small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reassign Officer Modal -->
    <div class="modal fade" id="reassignOfficerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Reassign Officer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Reassign officer for: <strong id="reassignProjectTitle"></strong></p>
                    <p class="text-muted">Currently assigned to: <strong id="currentOfficerName"></strong></p>
                    
                    <div class="list-group">
                        <?php foreach ($officers as $officer): 
                            $projectCount = getOfficerProjectCount($officer['id']);
                            $workloadClass = $projectCount < 3 ? 'list-group-item-success' : ($projectCount < 6 ? 'list-group-item-warning' : 'list-group-item-danger');
                        ?>
                        <a href="assignments.php?action=assign&project_id=&officer_id=<?php echo $officer['id']; ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center reassign-officer-link <?php echo $workloadClass; ?>"
                           data-project-id="">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($officer['email']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary rounded-pill"><?php echo $projectCount; ?> projects</span>
                                <br>
                                <small><?php echo $projectCount < 3 ? 'Light' : ($projectCount < 6 ? 'Medium' : 'Heavy'); ?> workload</small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
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

        // Select All Projects Checkbox
        document.getElementById('selectAllProjects')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.project-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Assign Officer Modal Handler
        const assignOfficerModal = document.getElementById('assignOfficerModal');
        if (assignOfficerModal) {
            assignOfficerModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const projectId = button.getAttribute('data-project-id');
                const projectTitle = button.getAttribute('data-project-title');
                
                document.getElementById('assignProjectTitle').textContent = projectTitle;
                
                // Update all assign links with the correct project ID
                const assignLinks = document.querySelectorAll('.assign-officer-link');
                assignLinks.forEach(link => {
                    const currentHref = link.getAttribute('href');
                    const newHref = currentHref.replace('project_id=', `project_id=${projectId}`);
                    link.setAttribute('href', newHref);
                });
            });
        }

        // Reassign Officer Modal Handler
        const reassignOfficerModal = document.getElementById('reassignOfficerModal');
        if (reassignOfficerModal) {
            reassignOfficerModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const projectId = button.getAttribute('data-project-id');
                const projectTitle = button.getAttribute('data-project-title');
                const currentOfficer = button.getAttribute('data-current-officer');
                
                document.getElementById('reassignProjectTitle').textContent = projectTitle;
                document.getElementById('currentOfficerName').textContent = currentOfficer;
                
                // Update all reassign links with the correct project ID
                const reassignLinks = document.querySelectorAll('.reassign-officer-link');
                reassignLinks.forEach(link => {
                    const currentHref = link.getAttribute('href');
                    const newHref = currentHref.replace('project_id=', `project_id=${projectId}`);
                    link.setAttribute('href', newHref);
                });
            });
        }

        // Search and Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const assignmentFilter = document.getElementById('assignmentFilter');
            const projectsTable = document.getElementById('projectsTable');
            
            if (searchInput && projectsTable) {
                searchInput.addEventListener('keyup', filterProjects);
            }
            
            if (assignmentFilter && projectsTable) {
                assignmentFilter.addEventListener('change', filterProjects);
            }
            
            function filterProjects() {
                const searchTerm = searchInput.value.toLowerCase();
                const assignmentValue = assignmentFilter.value;
                const rows = projectsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const title = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                    const officerCell = rows[i].getElementsByTagName('td')[5];
                    const isAssigned = officerCell.textContent.includes('Not Assigned') ? 'unassigned' : 'assigned';
                    
                    const titleMatch = title.includes(searchTerm);
                    const assignmentMatch = assignmentValue === '' || isAssigned === assignmentValue;
                    
                    if (titleMatch && assignmentMatch) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });

        // Project Selection Highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.project-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const row = this.closest('tr');
                    if (this.checked) {
                        row.style.backgroundColor = 'var(--info-light)';
                    } else {
                        row.style.backgroundColor = '';
                    }
                });
            });
        });
    </script>
</body>
</html>
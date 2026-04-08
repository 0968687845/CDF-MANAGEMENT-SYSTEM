<?php
require_once '../functions.php';
requireRole('admin');

// Auto-update all project statuses using ML analysis
autoUpdateAllProjectStatuses();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$users = getUsersByRole('beneficiary');
$officers = getUsersByRole('officer');
$projects = getAllProjects();
$notifications = getNotifications($_SESSION['user_id']);

// Get dashboard statistics
$stats = getDashboardStats($_SESSION['user_id'], 'admin');

$pageTitle = "Admin Dashboard - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Administrator dashboard for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background-color: var(--primary);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
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
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem !important;
            border-radius: 4px;
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow);
            border-radius: 8px;
            padding: 0.5rem 0;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 76px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2rem;
            font-weight: 700;
            box-shadow: var(--shadow);
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .profile-info h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
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
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-outline-custom {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--white);
            color: var(--primary);
        }

        /* Stats Cards */
        .stats-container {
            margin: 2rem 0;
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

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .stat-subtitle {
            font-size: 0.85rem;
            color: var(--gray);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .content-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.25rem;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
        }

        /* Admin Tools */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .tool-card {
            background: var(--white);
            border: none;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 4px solid var(--primary);
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .tool-card.success { border-left-color: var(--success); }
        .tool-card.warning { border-left-color: var(--warning); }
        .tool-card.info { border-left-color: var(--info); }
        .tool-card.danger { border-left-color: var(--danger); }

        .tool-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
            transition: color 0.3s ease;
        }

        .tool-card:hover .tool-icon {
            color: white;
        }

        .tool-card.success .tool-icon { color: var(--success); }
        .tool-card.warning .tool-icon { color: var(--warning); }
        .tool-card.info .tool-icon { color: var(--info); }
        .tool-card.danger .tool-icon { color: var(--danger); }

        /* Project Cards */
        .project-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            transition: var(--transition);
            overflow: hidden;
        }

        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .project-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
        }

        .progress-section {
            margin: 1rem 0;
        }

        .progress {
            height: 8px;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            border-radius: 5px;
            transition: width 0.6s ease;
        }

        /* Activity Items */
        .activity-item {
            padding: 1.25rem;
            border-left: 4px solid transparent;
            transition: var(--transition);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .activity-item:hover {
            background: rgba(13, 110, 253, 0.05);
            border-left-color: var(--primary);
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .activity-icon.primary { background: rgba(26, 78, 138, 0.1); color: var(--primary); }
        .activity-icon.success { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .activity-icon.warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .activity-icon.info { background: rgba(23, 162, 184, 0.1); color: var(--info); }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--primary);
        }

        /* Badge Colors */
        .badge-completed { background-color: var(--success); }
        .badge-in-progress { background-color: var(--warning); color: var(--dark); }
        .badge-delayed { background-color: var(--danger); }
        .badge-planning { background-color: var(--primary); }
        .badge-assigned { background-color: var(--info); }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* Table Improvements */
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--primary);
            background-color: var(--gray-light);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
                padding: 1.5rem 0;
            }
            
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .tools-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .tools-grid {
                grid-template-columns: 1fr;
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
                            <li><a class="dropdown-item active" href="project_reports.php">
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
                    <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1>System Administrator</h1>
                    <p class="lead">Welcome back, <?php echo htmlspecialchars($userData['first_name']); ?>! - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Last Login: <strong><?php echo date('M j, Y g:i A', strtotime($userData['updated_at'] ?? date('Y-m-d H:i:s'))); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="users.php" class="btn btn-primary-custom">
                    <i class="fas fa-users me-2"></i>Manage Users
                </a>
                <a href="projects.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>Manage Projects
                </a>
                <a href="assignments.php" class="btn btn-outline-custom">
                    <i class="fas fa-user-tie me-2"></i>Officer Assignments
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_projects'] ?? count($projects); ?></div>
                        <div class="stat-title">Total Projects</div>
                        <div class="stat-subtitle">All projects in system</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_beneficiaries'] ?? count($users); ?></div>
                        <div class="stat-title">Beneficiaries</div>
                        <div class="stat-subtitle">Registered beneficiaries</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_officers'] ?? count($officers); ?></div>
                        <div class="stat-title">M&E Officers</div>
                        <div class="stat-subtitle">Active officers</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">ZMW <?php echo number_format($stats['total_budget'] ?? array_sum(array_column($projects, 'budget')), 0); ?></div>
                        <div class="stat-title">Total Budget</div>
                        <div class="stat-subtitle">Allocated funds</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Tools -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-cogs me-2"></i>Administration Tools</h5>
            </div>
            <div class="card-body">
                <div class="tools-grid">
                    <div class="tool-card success" onclick="location.href='users.php'">
                        <i class="fas fa-users tool-icon"></i>
                        <h6>User Management</h6>
                        <p class="small mb-0">Manage system users and permissions</p>
                    </div>
                    <div class="tool-card warning" onclick="location.href='assignments.php'">
                        <i class="fas fa-user-tie tool-icon"></i>
                        <h6>Officer Assignments</h6>
                        <p class="small mb-0">Assign officers to projects</p>
                    </div>
                    <div class="tool-card info" onclick="location.href='projects.php'">
                        <i class="fas fa-project-diagram tool-icon"></i>
                        <h6>Project Management</h6>
                        <p class="small mb-0">Create and manage projects</p>
                    </div>
                    <div class="tool-card danger" onclick="location.href='reports.php'">
                        <i class="fas fa-chart-bar tool-icon"></i>
                        <h6>System Reports</h6>
                        <p class="small mb-0">Generate analytical reports</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Projects Overview -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-project-diagram me-2"></i>Recent Projects</h5>
                        <a href="projects.php" class="btn btn-primary-custom btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>New Project
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($projects) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Beneficiary</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Officer</th>
                                            <th>Budget</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($projects, 0, 5) as $project): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?>...</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                                    <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 8px; width: 100px;">
                                                    <div class="progress-bar bg-<?php echo $project['status'] ?? 'planning'; ?>" 
                                                         style="width: <?php echo $project['progress'] ?? 0; ?>%"></div>
                                                </div>
                                                <small><?php echo $project['progress'] ?? 0; ?>%</small>
                                            </td>
                                            <td>
                                                <?php if ($project['officer_name'] ?? false): ?>
                                                <span class="badge badge-assigned"><?php echo htmlspecialchars($project['officer_name']); ?></span>
                                                <?php else: ?>
                                                <span class="badge badge-warning">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>ZMW <?php echo number_format($project['budget'], 0); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="projects.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="assignments.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-info">
                                                        <i class="fas fa-user-tie"></i>
                                                    </a>
                                                    <a href="projects.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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
                                <h5 class="text-muted">No Projects Created</h5>
                                <p class="text-muted mb-4">Get started by creating the first project.</p>
                                <a href="projects.php" class="btn btn-primary-custom">
                                    <i class="fas fa-plus-circle me-2"></i>Create Project
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Overview & Activity -->
            <div class="col-lg-4">
                <!-- System Status -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>System Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="systemOverviewChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Get recent admin activities
                        $recent_activities = getRecentActivities($_SESSION['user_id'], 5) ?? [];
                        ?>
                        
                        <?php if (count($recent_activities) > 0): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon <?php echo $activity['type'] ?? 'primary'; ?>">
                                        <i class="fas fa-<?php echo $activity['icon'] ?? 'history'; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['title'] ?? 'Activity'); ?></h6>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($activity['description'] ?? 'No description'); ?></p>
                                        <small class="text-muted"><?php echo time_elapsed_string($activity['created_at'] ?? 'now'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent activity</p>
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
    <script>
        // Initialize System Overview Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('systemOverviewChart').getContext('2d');
            const systemOverviewChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Active Projects', 'Completed', 'In Progress', 'Planning'],
                    datasets: [{
                        data: [
                            <?php echo count(array_filter($projects, function($p) { return $p['status'] === 'active'; })); ?>,
                            <?php echo count(array_filter($projects, function($p) { return $p['status'] === 'completed'; })); ?>,
                            <?php echo count(array_filter($projects, function($p) { return $p['status'] === 'in-progress'; })); ?>,
                            <?php echo count(array_filter($projects, function($p) { return $p['status'] === 'planning'; })); ?>
                        ],
                        backgroundColor: [
                            '#1a4e8a',
                            '#28a745',
                            '#ffc107',
                            '#6c757d'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
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
    </script>
</body>
</html>
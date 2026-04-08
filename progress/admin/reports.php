<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$pageTitle = "System Reports - CDF Management System";

// Get notifications for the top bar
$notifications = getNotifications($_SESSION['user_id']);
$unread_notifications = array_filter($notifications, function($n) { return $n['is_read'] == 0; });

// Get all data for reports
$projects = getAllProjects();
$users = getAllUsers();
$beneficiaries = getUsersByRole('beneficiary');
$officers = getUsersByRole('officer');

// Calculate statistics
$total_projects = count($projects);
$completed_projects = count(array_filter($projects, function($p) { return $p['status'] === 'completed'; }));
$active_projects = count(array_filter($projects, function($p) { return $p['status'] === 'in-progress'; }));
$delayed_projects = count(array_filter($projects, function($p) { return $p['status'] === 'delayed'; }));

$total_users = count($users);
$active_users = count(array_filter($users, function($u) { return $u['status'] === 'active'; }));
$inactive_users = count(array_filter($users, function($u) { return $u['status'] === 'inactive'; }));

// Financial statistics
$total_budget = array_sum(array_column($projects, 'budget'));
$total_expenses = 0;
foreach ($projects as $project) {
    $total_expenses += getTotalProjectExpenses($project['id']);
}

// Get report type and filters
$report_type = $_GET['type'] ?? 'dashboard';
$timeframe = $_GET['timeframe'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Handle report generation
if (isset($_POST['generate_report'])) {
    $report_format = $_POST['report_format'] ?? 'pdf';
    $message = "{$report_format} report generated successfully!";
}

// Get activity log for recent activities
$recent_activities = getActivityLog(null, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --primary-light: #3d8bfd;
            --secondary: #6c757d;
            --success: #198754;
            --success-dark: #146c43;
            --warning: #ffc107;
            --warning-dark: #e0a800;
            --danger: #dc3545;
            --danger-dark: #b02a37;
            --info: #0dcaf0;
            --dark: #212529;
            --light: #f8f9fa;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.15);
            --shadow-xl: 0 16px 48px rgba(0,0,0,0.18);
            
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 20px;
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s ease;
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 3rem 0 2rem;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius-xl) var(--border-radius-xl);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }
        
        .report-card {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            margin-bottom: 2rem;
            background: var(--white);
            position: relative;
            overflow: hidden;
        }

        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            transform: scaleX(0);
            transition: var(--transition);
        }

        .report-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .report-card:hover::before {
            transform: scaleX(1);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            transition: var(--transition);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stats-card:hover::before {
            transform: rotate(45deg) translate(50%, 50%);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 3px solid;
            border-image: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%) 1;
            padding-bottom: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 700;
            font-size: 1.5rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, var(--info) 0%, var(--primary) 100%);
            border-radius: 3px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .action-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(13, 110, 253, 0.1), transparent);
            transition: var(--transition-slow);
        }

        .action-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .action-card:hover::before {
            left: 100%;
        }
        
        .action-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: var(--transition);
        }

        .action-card:hover .action-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: var(--white);
            padding: 0.875rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .btn-custom-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--white);
        }

        .btn-custom-primary:hover::before {
            left: 100%;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: var(--white);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 2rem;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .metric-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border-top: 4px solid var(--primary);
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .metric-label {
            font-size: 0.9rem;
            color: var(--gray-600);
            font-weight: 600;
        }

        .table-card {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            background: var(--white);
        }

        .table th {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            border-bottom: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 700;
            padding: 1.25rem;
        }

        .table td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
        }

        .activity-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary), var(--info));
        }

        .activity-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 2rem;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid var(--white);
            box-shadow: 0 0 0 2px var(--primary);
        }

        .report-type-active {
            border: 2px solid var(--primary);
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 202, 240, 0.05) 100%);
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 0 1.5rem;
                border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
            }

            .stats-card {
                padding: 1.5rem 1rem;
            }

            .stats-number {
                font-size: 2rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .chart-container {
                height: 250px;
                padding: 1rem;
            }
        }

        /* Animation for elements */
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Export options */
        .export-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Status badges */
        .status-badge {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%);">
        <div class="container">
            <a class="navbar-brand" href="../admin_dashboard.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40" class="rounded-circle shadow-sm">
                <span class="ms-2 fw-bold">CDF Admin Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../admin_dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu shadow-lg">
                            <li><a class="dropdown-item" href="users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="assignments.php">
                                <i class="fas fa-user-tie me-2"></i>Officer Assignments
                            </a></li>
                            <li><a class="dropdown-item active" href="reports.php">
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
                            <?php if (count($unread_notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($unread_notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $notification['is_read'] == 0 ? 'fw-bold' : ''; ?>" href="communication/notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="communication/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="communication/notifications.php">No new notifications</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-small rounded-circle shadow-sm" style="width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem;">
                                            <?php 
                                            echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); 
                                            ?>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
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
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../admin_dashboard.php?logout=true">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3"><i class="fas fa-chart-bar me-3"></i>System Reports</h1>
                    <p class="lead mb-0">Comprehensive analytics and reporting dashboard</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../admin_dashboard.php" class="btn btn-light btn-lg shadow-sm me-2">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Messages -->
        <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_projects; ?></div>
                    <div>Total Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);">
                    <div class="stats-number"><?php echo $completed_projects; ?></div>
                    <div>Completed Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%); color: #000;">
                    <div class="stats-number">ZMW <?php echo number_format($total_budget, 2); ?></div>
                    <div>Total Budget</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, var(--info) 0%, #0aa2c0 100%);">
                    <div class="stats-number"><?php echo $total_users; ?></div>
                    <div>System Users</div>
                </div>
            </div>
        </div>

        <!-- Report Type Selection -->
        <div class="quick-actions">
            <div class="action-card <?php echo $report_type === 'dashboard' ? 'report-type-active' : ''; ?>" onclick="changeReportType('dashboard')">
                <div class="action-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h6>Dashboard</h6>
                <small class="text-muted">Overview and key metrics</small>
            </div>
            <div class="action-card <?php echo $report_type === 'projects' ? 'report-type-active' : ''; ?>" onclick="changeReportType('projects')">
                <div class="action-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <h6>Project Reports</h6>
                <small class="text-muted">Project performance analytics</small>
            </div>
            <div class="action-card <?php echo $report_type === 'financial' ? 'report-type-active' : ''; ?>" onclick="changeReportType('financial')">
                <div class="action-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h6>Financial Reports</h6>
                <small class="text-muted">Budget and expense analysis</small>
            </div>
            <div class="action-card <?php echo $report_type === 'users' ? 'report-type-active' : ''; ?>" onclick="changeReportType('users')">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h6>User Reports</h6>
                <small class="text-muted">User activity and statistics</small>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section fade-in">
            <form method="GET" class="row g-3">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Timeframe</label>
                    <select class="form-select" name="timeframe">
                        <option value="all" <?php echo $timeframe === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $timeframe === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $timeframe === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $timeframe === 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="quarter" <?php echo $timeframe === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                        <option value="year" <?php echo $timeframe === 'year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-custom-primary flex-grow-1">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="reports.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <div class="report-card fade-in">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="section-title mb-0">
                        <?php 
                        $report_titles = [
                            'dashboard' => 'Analytics Dashboard',
                            'projects' => 'Project Performance Reports',
                            'financial' => 'Financial Analysis Reports',
                            'users' => 'User Activity Reports'
                        ];
                        echo $report_titles[$report_type] ?? 'System Reports';
                        ?>
                    </h4>
                    <div class="export-options">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="generate_report" value="1">
                            <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                            <input type="hidden" name="report_format" value="pdf">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-file-pdf me-2"></i>Export PDF
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="generate_report" value="1">
                            <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                            <input type="hidden" name="report_format" value="excel">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($report_type === 'dashboard'): ?>
                <!-- Dashboard Report -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <canvas id="projectStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="userDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="budgetUtilizationChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="monthlyProgressChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo $active_projects; ?></div>
                            <div class="metric-label">Active Projects</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo $delayed_projects; ?></div>
                            <div class="metric-label">Delayed Projects</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo count($officers); ?></div>
                            <div class="metric-label">M&E Officers</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo count($beneficiaries); ?></div>
                            <div class="metric-label">Beneficiaries</div>
                        </div>
                    </div>
                </div>

                <?php elseif ($report_type === 'projects'): ?>
                <!-- Project Reports -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="projectCategoryChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="completionRateChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="card-body">
                        <h5 class="mb-3">Project Performance Summary</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Beneficiary</th>
                                        <th>Budget</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Timeline</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($projects, 0, 10) as $project): 
                                        $expenses = getTotalProjectExpenses($project['id']);
                                        $utilization = $project['budget'] > 0 ? ($expenses / $project['budget']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($project['category'] ?? 'Uncategorized'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?></td>
                                        <td>ZMW <?php echo number_format($project['budget'], 2); ?></td>
                                        <td>
                                            <div class="progress" style="height: 8px; width: 100px;">
                                                <div class="progress-bar bg-<?php 
                                                    switch($project['status']) {
                                                        case 'completed': echo 'success'; break;
                                                        case 'in-progress': echo 'primary'; break;
                                                        case 'delayed': echo 'danger'; break;
                                                        default: echo 'info';
                                                    }
                                                ?>" style="width: <?php echo $project['progress']; ?>%"></div>
                                            </div>
                                            <small><?php echo $project['progress']; ?>%</small>
                                        </td>
                                        <td>
                                            <span class="status-badge bg-<?php 
                                                switch($project['status']) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'in-progress': echo 'primary'; break;
                                                    case 'delayed': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst($project['status']); ?></span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('M j, Y', strtotime($project['start_date'])); ?>
                                                <br>to<br>
                                                <?php echo date('M j, Y', strtotime($project['end_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $performance = $project['progress'] - ($utilization > 100 ? 20 : 0);
                                            $performance_class = $performance >= 80 ? 'success' : ($performance >= 60 ? 'warning' : 'danger');
                                            ?>
                                            <span class="fw-bold text-<?php echo $performance_class; ?>">
                                                <?php echo $performance; ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php elseif ($report_type === 'financial'): ?>
                <!-- Financial Reports -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="budgetDistributionChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="expenseTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value">ZMW <?php echo number_format($total_budget, 2); ?></div>
                            <div class="metric-label">Total Budget</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value">ZMW <?php echo number_format($total_expenses, 2); ?></div>
                            <div class="metric-label">Total Expenses</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo $total_budget > 0 ? round(($total_expenses / $total_budget) * 100, 1) : 0; ?>%</div>
                            <div class="metric-label">Budget Utilization</div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- User Reports -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="userActivityChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="roleDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="table-card">
                            <div class="card-body">
                                <h5 class="mb-3">Recent System Activities</h5>
                                <div class="activity-timeline">
                                    <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                            <small class="text-muted"><?php echo time_elapsed_string($activity['created_at']); ?></small>
                                        </div>
                                        <p class="mb-2 small text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                        </p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-card">
                            <div class="card-body">
                                <h5 class="mb-3">User Statistics</h5>
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="metric-value"><?php echo $active_users; ?></div>
                                        <div class="metric-label">Active Users</div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="metric-value"><?php echo $inactive_users; ?></div>
                                        <div class="metric-label">Inactive Users</div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="metric-value"><?php echo count($officers); ?></div>
                                        <div class="metric-label">M&E Officers</div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="metric-value"><?php echo count($beneficiaries); ?></div>
                                        <div class="metric-label">Beneficiaries</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeReportType(type) {
            const url = new URL(window.location);
            url.searchParams.set('type', type);
            window.location.href = url.toString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts based on report type
            const reportType = '<?php echo $report_type; ?>';
            
            if (reportType === 'dashboard') {
                initializeDashboardCharts();
            } else if (reportType === 'projects') {
                initializeProjectCharts();
            } else if (reportType === 'financial') {
                initializeFinancialCharts();
            } else if (reportType === 'users') {
                initializeUserCharts();
            }

            function initializeDashboardCharts() {
                // Project Status Chart
                const statusCtx = document.getElementById('projectStatusChart').getContext('2d');
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'In Progress', 'Delayed', 'Planning'],
                        datasets: [{
                            data: [
                                <?php echo $completed_projects; ?>,
                                <?php echo $active_projects; ?>,
                                <?php echo $delayed_projects; ?>,
                                <?php echo $total_projects - $completed_projects - $active_projects - $delayed_projects; ?>
                            ],
                            backgroundColor: ['#28a745', '#0d6efd', '#dc3545', '#6c757d'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });

                // User Distribution Chart
                const userCtx = document.getElementById('userDistributionChart').getContext('2d');
                new Chart(userCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Beneficiaries', 'M&E Officers', 'Administrators'],
                        datasets: [{
                            data: [
                                <?php echo count($beneficiaries); ?>,
                                <?php echo count($officers); ?>,
                                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?>
                            ],
                            backgroundColor: ['#0dcaf0', '#198754', '#0d6efd'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            function initializeProjectCharts() {
                // Project Category Chart
                const categoryCtx = document.getElementById('projectCategoryChart').getContext('2d');
                const categories = ['Infrastructure', 'Education', 'Health', 'Agriculture', 'Water & Sanitation'];
                const categoryData = [12, 8, 6, 9, 5]; // Sample data

                new Chart(categoryCtx, {
                    type: 'bar',
                    data: {
                        labels: categories,
                        datasets: [{
                            label: 'Number of Projects',
                            data: categoryData,
                            backgroundColor: '#0d6efd'
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });

                // Completion Rate Chart
                const completionCtx = document.getElementById('completionRateChart').getContext('2d');
                new Chart(completionCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Completion Rate (%)',
                            data: [65, 72, 68, 75, 82, 78],
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            function initializeFinancialCharts() {
                // Budget Distribution Chart
                const budgetCtx = document.getElementById('budgetDistributionChart').getContext('2d');
                new Chart(budgetCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Infrastructure', 'Education', 'Health', 'Agriculture', 'Other'],
                        datasets: [{
                            data: [45, 20, 15, 12, 8],
                            backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d']
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });

                // Expense Trend Chart
                const expenseCtx = document.getElementById('expenseTrendChart').getContext('2d');
                new Chart(expenseCtx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Monthly Expenses (ZMW)',
                            data: [125000, 98000, 145000, 112000, 165000, 138000],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            function initializeUserCharts() {
                // User Activity Chart
                const activityCtx = document.getElementById('userActivityChart').getContext('2d');
                new Chart(activityCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'User Logins',
                            data: [45, 52, 48, 55, 60, 35, 25],
                            backgroundColor: '#0d6efd'
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });

                // Role Distribution Chart
                const roleCtx = document.getElementById('roleDistributionChart').getContext('2d');
                new Chart(roleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Beneficiaries', 'M&E Officers', 'Administrators'],
                        datasets: [{
                            data: [
                                <?php echo count($beneficiaries); ?>,
                                <?php echo count($officers); ?>,
                                <?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?>
                            ],
                            backgroundColor: ['#0dcaf0', '#198754', '#0d6efd']
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            // Add animation to elements on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.fade-in').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
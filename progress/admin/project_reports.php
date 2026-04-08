<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$pageTitle = "Project Reports - CDF Management System";

// Get all projects and statistics
$projects = getAllProjects();
$total_projects = count($projects);
$completed_projects = count(array_filter($projects, function($p) { return $p['status'] === 'completed'; }));
$active_projects = count(array_filter($projects, function($p) { return $p['status'] === 'in-progress'; }));
$delayed_projects = count(array_filter($projects, function($p) { return $p['status'] === 'delayed'; }));

// Calculate total budget and expenses
$total_budget = array_sum(array_column($projects, 'budget'));
$total_expenses = 0;
foreach ($projects as $project) {
    $total_expenses += getTotalProjectExpenses($project['id']);
}

// Get all expenses across all projects
$all_expenses = [];
foreach ($projects as $project) {
    $project_expenses = getProjectExpenses($project['id']);
    foreach ($project_expenses as $expense) {
        $expense['project_title'] = $project['title'];
        $expense['project_constituency'] = $project['constituency'];
        $all_expenses[] = $expense;
    }
}

// Sort expenses by date (newest first)
usort($all_expenses, function($a, $b) {
    return strtotime($b['expense_date']) - strtotime($a['expense_date']);
});

// Get report type and filters
$report_type = $_GET['type'] ?? 'overview';
$timeframe = $_GET['timeframe'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$constituency_filter = $_GET['constituency'] ?? 'all';

// Filter projects based on criteria
$filtered_projects = $projects;
if ($category_filter !== 'all') {
    $filtered_projects = array_filter($filtered_projects, function($p) use ($category_filter) {
        return ($p['category'] ?? '') === $category_filter;
    });
}
if ($constituency_filter !== 'all') {
    $filtered_projects = array_filter($filtered_projects, function($p) use ($constituency_filter) {
        return ($p['constituency'] ?? '') === $constituency_filter;
    });
}

// Handle report generation
if (isset($_POST['generate_report'])) {
    $report_format = $_POST['report_format'] ?? 'pdf';
    $report_type = $_POST['report_type'] ?? 'overview';
    
    // In a real application, this would generate and download a PDF/Excel report
    $message = "{$report_format} report for {$report_type} generated successfully!";
    
    // For demo purposes, we'll just show a success message
    echo "<script>alert('{$message}');</script>";
}

// Get unique categories and constituencies for filters
$categories = array_unique(array_column($projects, 'category'));
$constituencies = array_unique(array_column($projects, 'constituency'));

// Get notifications for the top bar
$notifications = getNotifications($_SESSION['user_id']);
$unread_notifications = array_filter($notifications, function($n) { return $n['is_read'] == 0; });
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
            --secondary: #6c757d;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --gov-blue: #003366;
            --gov-gold: #FFD700;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .report-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 1.5rem;
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .report-type-active {
            border: 2px solid var(--primary);
            background: rgba(13, 110, 253, 0.05);
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            border-top: 4px solid var(--primary);
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: var(--secondary);
            font-weight: 600;
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 1rem;
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.3);
            color: white;
        }
        
        .export-options {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .expense-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--success);
        }
        
        .expense-high {
            border-left-color: var(--warning);
        }
        
        .expense-critical {
            border-left-color: var(--danger);
        }
        
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
        
        /* Footer Styles */
        .dashboard-footer {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--primary);
        }
        
        /* Alert Styles */
        .alert-fixed {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: #1a4e8a;">
        <div class="container">
            <a class="navbar-brand" href="../admin_dashboard.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
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
                        <ul class="dropdown-menu dropdown-menu-end">
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
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-small" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">
                                            <?php 
                                            echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); 
                                            ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
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

    <!-- Success Message -->
    <?php if (isset($message)): ?>
    <div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <section class="page-header" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-bar me-3"></i>Project Reports & Analytics</h1>
                    <p class="lead mb-0">Comprehensive project performance analysis and reporting</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="projects.php" class="btn btn-light me-2">
                        <i class="fas fa-project-diagram me-2"></i>All Projects
                    </a>
                    <a href="../admin_dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_projects; ?></div>
                    <div>Total Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                    <div class="stats-number"><?php echo $completed_projects; ?></div>
                    <div>Completed</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffd54f 100%); color: #000;">
                    <div class="stats-number">ZMW <?php echo number_format($total_budget, 2); ?></div>
                    <div>Total Budget</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stats-number"><?php echo $delayed_projects; ?></div>
                    <div>Delayed Projects</div>
                </div>
            </div>
        </div>

        <!-- Report Type Selection -->
        <div class="quick-actions">
            <div class="action-card <?php echo $report_type === 'overview' ? 'report-type-active' : ''; ?>" onclick="changeReportType('overview')">
                <div class="action-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h6>Overview</h6>
                <small class="text-muted">Project summary and metrics</small>
            </div>
            <div class="action-card <?php echo $report_type === 'performance' ? 'report-type-active' : ''; ?>" onclick="changeReportType('performance')">
                <div class="action-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h6>Performance</h6>
                <small class="text-muted">Progress and completion rates</small>
            </div>
            <div class="action-card <?php echo $report_type === 'financial' ? 'report-type-active' : ''; ?>" onclick="changeReportType('financial')">
                <div class="action-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h6>Financial</h6>
                <small class="text-muted">Budget and expense analysis</small>
            </div>
            <div class="action-card <?php echo $report_type === 'detailed' ? 'report-type-active' : ''; ?>" onclick="changeReportType('detailed')">
                <div class="action-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h6>Detailed</h6>
                <small class="text-muted">Comprehensive project data</small>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                <div class="col-md-3">
                    <label class="form-label">Timeframe</label>
                    <select class="form-select" name="timeframe">
                        <option value="all" <?php echo $timeframe === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="this_month" <?php echo $timeframe === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="last_month" <?php echo $timeframe === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                        <option value="this_quarter" <?php echo $timeframe === 'this_quarter' ? 'selected' : ''; ?>>This Quarter</option>
                        <option value="this_year" <?php echo $timeframe === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $category): 
                            if (!empty($category)): ?>
                        <option value="<?php echo $category; ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                            <?php echo $category; ?>
                        </option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Constituency</label>
                    <select class="form-select" name="constituency">
                        <option value="all" <?php echo $constituency_filter === 'all' ? 'selected' : ''; ?>>All Constituencies</option>
                        <?php foreach ($constituencies as $constituency): 
                            if (!empty($constituency)): ?>
                        <option value="<?php echo $constituency; ?>" <?php echo $constituency_filter === $constituency ? 'selected' : ''; ?>>
                            <?php echo $constituency; ?>
                        </option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-grow-1">
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
        <div class="report-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="section-title mb-0">
                        <?php 
                        $report_titles = [
                            'overview' => 'Project Overview Report',
                            'performance' => 'Performance Analytics',
                            'financial' => 'Financial Analysis',
                            'detailed' => 'Detailed Project Report'
                        ];
                        echo $report_titles[$report_type] ?? 'Project Reports';
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

                <?php if ($report_type === 'overview'): ?>
                <!-- Overview Report -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="projectStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="categoryDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo count($filtered_projects); ?></div>
                            <div class="metric-label">Filtered Projects</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value">
                                <?php 
                                $avg_progress = count($filtered_projects) > 0 ? 
                                    array_sum(array_column($filtered_projects, 'progress')) / count($filtered_projects) : 0;
                                echo round($avg_progress, 1); ?>%
                            </div>
                            <div class="metric-label">Average Progress</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <div class="metric-value">
                                <?php 
                                $completion_rate = count($filtered_projects) > 0 ? 
                                    (count(array_filter($filtered_projects, function($p) { return $p['status'] === 'completed'; })) / count($filtered_projects)) * 100 : 0;
                                echo round($completion_rate, 1); ?>%
                            </div>
                            <div class="metric-label">Completion Rate</div>
                        </div>
                    </div>
                </div>

                <?php elseif ($report_type === 'performance'): ?>
                <!-- Performance Report -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="progressDistributionChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="timelinePerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="card-body">
                        <h5 class="mb-3">Project Performance Ranking</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Budget Utilization</th>
                                        <th>Timeline Adherence</th>
                                        <th>Performance Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Calculate performance scores for each project
                                    $performance_data = [];
                                    foreach ($filtered_projects as $project) {
                                        $expenses = getTotalProjectExpenses($project['id']);
                                        $budget_utilization = $project['budget'] > 0 ? ($expenses / $project['budget']) * 100 : 0;
                                        
                                        // Simple performance score calculation
                                        $progress_score = $project['progress'];
                                        $budget_score = max(0, 100 - abs($budget_utilization - 80)); // Ideal utilization ~80%
                                        $timeline_score = $project['status'] === 'delayed' ? 50 : 100;
                                        
                                        $performance_score = ($progress_score + $budget_score + $timeline_score) / 3;
                                        
                                        $performance_data[] = [
                                            'project' => $project,
                                            'performance_score' => $performance_score,
                                            'budget_utilization' => $budget_utilization
                                        ];
                                    }
                                    
                                    // Sort by performance score
                                    usort($performance_data, function($a, $b) {
                                        return $b['performance_score'] - $a['performance_score'];
                                    });
                                    
                                    foreach (array_slice($performance_data, 0, 10) as $data):
                                        $project = $data['project'];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($project['constituency'] ?? 'Unknown'); ?></small>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px; width: 100px;">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $project['progress']; ?>%"></div>
                                            </div>
                                            <small><?php echo $project['progress']; ?>%</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($project['status']) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'in-progress': echo 'primary'; break;
                                                    case 'delayed': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>"><?php echo ucfirst($project['status']); ?></span>
                                        </td>
                                        <td><?php echo round($data['budget_utilization'], 1); ?>%</td>
                                        <td>
                                            <?php echo $project['status'] === 'delayed' ? 'Delayed' : 'On Track'; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-<?php 
                                                echo $data['performance_score'] >= 80 ? 'success' : 
                                                     ($data['performance_score'] >= 60 ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo round($data['performance_score']); ?>%
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
                <!-- Financial Report -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="budgetVsExpenseChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="categorySpendingChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">ZMW <?php echo number_format($total_budget, 2); ?></div>
                            <div class="metric-label">Total Budget</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">ZMW <?php echo number_format($total_expenses, 2); ?></div>
                            <div class="metric-label">Total Expenses</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value">ZMW <?php echo number_format($total_budget - $total_expenses, 2); ?></div>
                            <div class="metric-label">Remaining Budget</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo $total_budget > 0 ? round(($total_expenses / $total_budget) * 100, 1) : 0; ?>%</div>
                            <div class="metric-label">Overall Utilization</div>
                        </div>
                    </div>
                </div>

                <!-- All Expenses Listing -->
                <div class="table-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="section-title mb-0">All Recorded Expenses</h5>
                            <span class="badge bg-primary"><?php echo count($all_expenses); ?> expenses</span>
                        </div>

                        <?php if (count($all_expenses) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Project</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th>Vendor</th>
                                            <th>Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($all_expenses, 0, 20) as $expense): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($expense['project_title']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($expense['project_constituency'] ?? 'Unknown'); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($expense['category']); ?></span>
                                            </td>
                                            <td>
                                                <strong class="text-success">ZMW <?php echo number_format($expense['amount'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <?php if (!empty($expense['vendor'])): ?>
                                                    <small><?php echo htmlspecialchars($expense['vendor']); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">N/A</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($expense['receipt_number'])): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($expense['receipt_number']); ?></span>
                                                <?php else: ?>
                                                    <small class="text-muted">No receipt</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (count($all_expenses) > 20): ?>
                            <div class="text-center mt-3">
                                <a href="expenses.php" class="btn btn-outline-primary">
                                    View All <?php echo count($all_expenses); ?> Expenses
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Expenses Recorded</h5>
                                <p class="text-muted">No expenses have been recorded across all projects.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php else: ?>
                <!-- Detailed Report -->
                <div class="table-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="section-title mb-0">All Projects Detailed View</h5>
                            <span class="badge bg-primary"><?php echo count($filtered_projects); ?> projects</span>
                        </div>

                        <?php if (count($filtered_projects) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Category</th>
                                            <th>Constituency</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Budget</th>
                                            <th>Expenses</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filtered_projects as $project): 
                                            $project_expenses = getTotalProjectExpenses($project['id']);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($project['category']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['constituency']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($project['status']) {
                                                        case 'completed': echo 'success'; break;
                                                        case 'in-progress': echo 'primary'; break;
                                                        case 'delayed': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($project['status']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress" style="height: 8px; width: 100px;">
                                                        <div class="progress-bar bg-primary" style="width: <?php echo $project['progress']; ?>%"></div>
                                                    </div>
                                                    <small class="ms-2"><?php echo $project['progress']; ?>%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>ZMW <?php echo number_format($project['budget'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">ZMW <?php echo number_format($project_expenses, 2); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($project['start_date'])); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($project['end_date'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
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
                                <h5 class="text-muted">No Projects Found</h5>
                                <p class="text-muted">No projects match your current filter criteria.</p>
                                <a href="reports.php" class="btn btn-primary">Clear Filters</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>CDF Management System</h6>
                    <p class="mb-0 text-muted">Constituency Development Fund Administration Portal</p>
                    <small class="text-muted">Version 2.1.0</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <span class="text-muted">© 2023 Government of Zambia</span>
                    </p>
                    <small class="text-muted">Last updated: <?php echo date('F j, Y'); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to change report type
        function changeReportType(type) {
            const url = new URL(window.location.href);
            url.searchParams.set('type', type);
            window.location.href = url.toString();
        }

        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            const reportType = '<?php echo $report_type; ?>';
            
            // Initialize charts based on report type
            if (reportType === 'overview') {
                initializeOverviewCharts();
            } else if (reportType === 'performance') {
                initializePerformanceCharts();
            } else if (reportType === 'financial') {
                initializeFinancialCharts();
            }

            function initializeOverviewCharts() {
                // Project Status Chart
                const statusCtx = document.getElementById('projectStatusChart');
                if (statusCtx) {
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Completed', 'In Progress', 'Delayed', 'Planning'],
                            datasets: [{
                                data: [
                                    <?php echo count(array_filter($filtered_projects, function($p) { return $p['status'] === 'completed'; })); ?>,
                                    <?php echo count(array_filter($filtered_projects, function($p) { return $p['status'] === 'in-progress'; })); ?>,
                                    <?php echo count(array_filter($filtered_projects, function($p) { return $p['status'] === 'delayed'; })); ?>,
                                    <?php echo count(array_filter($filtered_projects, function($p) { return $p['status'] === 'planning'; })); ?>
                                ],
                                backgroundColor: ['#28a745', '#0d6efd', '#dc3545', '#6c757d'],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                title: {
                                    display: true,
                                    text: 'Project Status Distribution'
                                }
                            }
                        }
                    });
                }

                // Category Distribution Chart
                const categoryCtx = document.getElementById('categoryDistributionChart');
                if (categoryCtx) {
                    // Calculate category counts
                    const categoryCounts = {};
                    <?php foreach ($filtered_projects as $project): ?>
                        const category = '<?php echo addslashes($project['category']); ?>';
                        categoryCounts[category] = (categoryCounts[category] || 0) + 1;
                    <?php endforeach; ?>
                    
                    const categoryLabels = Object.keys(categoryCounts);
                    const categoryData = Object.values(categoryCounts);
                    
                    new Chart(categoryCtx, {
                        type: 'bar',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                label: 'Number of Projects',
                                data: categoryData,
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Projects by Category'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }
            }

            function initializePerformanceCharts() {
                // Progress Distribution Chart
                const progressCtx = document.getElementById('progressDistributionChart');
                if (progressCtx) {
                    // Group projects by progress ranges
                    const progressRanges = {
                        '0-20%': 0,
                        '21-40%': 0,
                        '41-60%': 0,
                        '61-80%': 0,
                        '81-100%': 0
                    };
                    
                    <?php foreach ($filtered_projects as $project): ?>
                        const progress = <?php echo $project['progress']; ?>;
                        if (progress <= 20) progressRanges['0-20%']++;
                        else if (progress <= 40) progressRanges['21-40%']++;
                        else if (progress <= 60) progressRanges['41-60%']++;
                        else if (progress <= 80) progressRanges['61-80%']++;
                        else progressRanges['81-100%']++;
                    <?php endforeach; ?>
                    
                    new Chart(progressCtx, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(progressRanges),
                            datasets: [{
                                label: 'Number of Projects',
                                data: Object.values(progressRanges),
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(255, 159, 64, 0.7)',
                                    'rgba(255, 205, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(54, 162, 235, 0.7)'
                                ],
                                borderColor: [
                                    'rgb(255, 99, 132)',
                                    'rgb(255, 159, 64)',
                                    'rgb(255, 205, 86)',
                                    'rgb(75, 192, 192)',
                                    'rgb(54, 162, 235)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Project Progress Distribution'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }

                // Timeline Performance Chart
                const timelineCtx = document.getElementById('timelinePerformanceChart');
                if (timelineCtx) {
                    // Calculate monthly project completion
                    const monthlyData = {};
                    <?php foreach ($filtered_projects as $project): 
                        if ($project['status'] === 'completed' && !empty($project['end_date'])): ?>
                        const month = '<?php echo date('M Y', strtotime($project['end_date'])); ?>';
                        monthlyData[month] = (monthlyData[month] || 0) + 1;
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    // Sort months chronologically
                    const sortedMonths = Object.keys(monthlyData).sort((a, b) => {
                        return new Date(a) - new Date(b);
                    });
                    
                    const monthlyValues = sortedMonths.map(month => monthlyData[month]);
                    
                    new Chart(timelineCtx, {
                        type: 'line',
                        data: {
                            labels: sortedMonths,
                            datasets: [{
                                label: 'Projects Completed',
                                data: monthlyValues,
                                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Monthly Project Completions'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }
            }

            function initializeFinancialCharts() {
                // Budget vs Expense Chart
                const budgetCtx = document.getElementById('budgetVsExpenseChart');
                if (budgetCtx) {
                    // Get top 10 projects by budget
                    const topProjects = <?php echo json_encode($filtered_projects); ?>;
                    const sortedProjects = [...topProjects]
                        .sort((a, b) => b.budget - a.budget)
                        .slice(0, 10);
                    
                    const projectNames = sortedProjects.map(p => p.title.length > 20 ? p.title.substring(0, 20) + '...' : p.title);
                    const budgets = sortedProjects.map(p => parseFloat(p.budget));
                    
                    // Calculate expenses for each project
                    const expenses = sortedProjects.map(p => {
                        // In a real implementation, this would fetch from the database
                        // For demo, we'll use a calculated value based on progress
                        return parseFloat(p.budget) * (parseFloat(p.progress) / 100) * 0.8;
                    });
                    
                    new Chart(budgetCtx, {
                        type: 'bar',
                        data: {
                            labels: projectNames,
                            datasets: [
                                {
                                    label: 'Budget',
                                    data: budgets,
                                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Expenses',
                                    data: expenses,
                                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Top 10 Projects: Budget vs Expenses'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'ZMW ' + value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Category Spending Chart
                const spendingCtx = document.getElementById('categorySpendingChart');
                if (spendingCtx) {
                    // Calculate spending by category
                    const categorySpending = {};
                    <?php foreach ($filtered_projects as $project): ?>
                        const cat = '<?php echo addslashes($project['category']); ?>';
                        const projectBudget = parseFloat(<?php echo $project['budget']; ?>);
                        const projectProgress = parseFloat(<?php echo $project['progress']; ?>);
                        // Estimate expenses based on progress
                        const projectExpenses = projectBudget * (projectProgress / 100) * 0.8;
                        categorySpending[cat] = (categorySpending[cat] || 0) + projectExpenses;
                    <?php endforeach; ?>
                    
                    const spendingLabels = Object.keys(categorySpending);
                    const spendingData = Object.values(categorySpending);
                    
                    new Chart(spendingCtx, {
                        type: 'pie',
                        data: {
                            labels: spendingLabels,
                            datasets: [{
                                data: spendingData,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 205, 86, 0.7)',
                                    'rgba(75, 192, 192, 0.7)',
                                    'rgba(153, 102, 255, 0.7)',
                                    'rgba(255, 159, 64, 0.7)'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                title: {
                                    display: true,
                                    text: 'Spending by Category'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${context.label}: ZMW ${value.toLocaleString()} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
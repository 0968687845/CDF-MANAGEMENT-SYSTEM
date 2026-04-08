<?php
require_once __DIR__ . '/../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get report data
$allProjects = getAllProjects();
$allUsers = getAllUsers();
$beneficiaries = getUsersByRole('beneficiary');
$officers = getUsersByRole('officer');

// Calculate statistics for reports
$totalProjects = count($allProjects);
$totalBeneficiaries = count($beneficiaries);
$totalOfficers = count($officers);
$totalBudget = array_sum(array_column($allProjects, 'budget'));

// Project status counts
$projectStatusCounts = [
    'planning' => count(array_filter($allProjects, function($p) { return $p['status'] === 'planning'; })),
    'in-progress' => count(array_filter($allProjects, function($p) { return $p['status'] === 'in-progress'; })),
    'completed' => count(array_filter($allProjects, function($p) { return $p['status'] === 'completed'; })),
    'delayed' => count(array_filter($allProjects, function($p) { return $p['status'] === 'delayed'; }))
];

// Budget by category
$categories = array_unique(array_column($allProjects, 'category'));
$budgetByCategory = [];
foreach ($categories as $category) {
    $budgetByCategory[$category] = array_sum(array_column(
        array_filter($allProjects, function($p) use ($category) { return $p['category'] === $category; }),
        'budget'
    ));
}

// Handle report generation
$reportData = [];
$reportType = $_GET['report_type'] ?? 'overview';
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Generate reports based on type
switch ($reportType) {
    case 'financial':
        $reportTitle = "Financial Report";
        $reportData = generateFinancialReport($startDate, $endDate);
        break;
    case 'projects':
        $reportTitle = "Projects Report";
        $reportData = generateProjectsReport($startDate, $endDate);
        break;
    case 'users':
        $reportTitle = "Users Report";
        $reportData = generateUsersReport();
        break;
    case 'performance':
        $reportTitle = "Performance Report";
        $reportData = generatePerformanceReport($startDate, $endDate);
        break;
    default:
        $reportTitle = "System Overview Report";
        $reportData = generateOverviewReport();
        break;
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    generatePDFReport($reportType, $reportData, $startDate, $endDate);
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    generateCSVReport($reportType, $reportData, $startDate, $endDate);
}

// Report generation functions
function generateOverviewReport() {
    global $allProjects, $allUsers, $projectStatusCounts, $budgetByCategory;
    
    return [
        'summary' => [
            'total_projects' => count($allProjects),
            'total_users' => count($allUsers),
            'total_budget' => array_sum(array_column($allProjects, 'budget')),
            'avg_project_budget' => count($allProjects) > 0 ? array_sum(array_column($allProjects, 'budget')) / count($allProjects) : 0,
            'completion_rate' => count($allProjects) > 0 ? ($projectStatusCounts['completed'] / count($allProjects)) * 100 : 0
        ],
        'project_status' => $projectStatusCounts,
        'budget_distribution' => $budgetByCategory,
        'recent_activity' => getActivityLog(null, 10)
    ];
}

function generateFinancialReport($startDate, $endDate) {
    global $allProjects;
    
    $filteredProjects = array_filter($allProjects, function($project) use ($startDate, $endDate) {
        $projectDate = $project['created_at'];
        return $projectDate >= $startDate && $projectDate <= $endDate;
    });
    
    $totalBudget = array_sum(array_column($filteredProjects, 'budget'));
    $completedBudget = array_sum(array_column(
        array_filter($filteredProjects, function($p) { return $p['status'] === 'completed'; }),
        'budget'
    ));
    
    return [
        'period' => ['start' => $startDate, 'end' => $endDate],
        'financial_summary' => [
            'total_budget' => $totalBudget,
            'completed_budget' => $completedBudget,
            'utilization_rate' => $totalBudget > 0 ? ($completedBudget / $totalBudget) * 100 : 0,
            'avg_project_cost' => count($filteredProjects) > 0 ? $totalBudget / count($filteredProjects) : 0
        ],
        'projects' => $filteredProjects,
        'budget_by_status' => [
            'planning' => array_sum(array_column(
                array_filter($filteredProjects, function($p) { return $p['status'] === 'planning'; }),
                'budget'
            )),
            'in-progress' => array_sum(array_column(
                array_filter($filteredProjects, function($p) { return $p['status'] === 'in-progress'; }),
                'budget'
            )),
            'completed' => array_sum(array_column(
                array_filter($filteredProjects, function($p) { return $p['status'] === 'completed'; }),
                'budget'
            )),
            'delayed' => array_sum(array_column(
                array_filter($filteredProjects, function($p) { return $p['status'] === 'delayed'; }),
                'budget'
            ))
        ]
    ];
}

function generateProjectsReport($startDate, $endDate) {
    global $allProjects;
    
    $filteredProjects = array_filter($allProjects, function($project) use ($startDate, $endDate) {
        $projectDate = $project['created_at'];
        return $projectDate >= $startDate && $projectDate <= $endDate;
    });
    
    $projectsByStatus = [];
    foreach ($filteredProjects as $project) {
        $status = $project['status'];
        if (!isset($projectsByStatus[$status])) {
            $projectsByStatus[$status] = [];
        }
        $projectsByStatus[$status][] = $project;
    }
    
    return [
        'period' => ['start' => $startDate, 'end' => $endDate],
        'total_projects' => count($filteredProjects),
        'projects_by_status' => $projectsByStatus,
        'projects_by_category' => array_count_values(array_column($filteredProjects, 'category')),
        'completion_timeline' => getCompletionTimeline($startDate, $endDate)
    ];
}

function generateUsersReport() {
    global $allUsers, $beneficiaries, $officers;
    
    $usersByRole = [
        'beneficiary' => $beneficiaries,
        'officer' => $officers,
        'admin' => array_filter($allUsers, function($user) { return $user['role'] === 'admin'; })
    ];
    
    $registrationByMonth = [];
    foreach ($allUsers as $user) {
        $month = date('Y-m', strtotime($user['created_at']));
        if (!isset($registrationByMonth[$month])) {
            $registrationByMonth[$month] = 0;
        }
        $registrationByMonth[$month]++;
    }
    
    return [
        'users_by_role' => $usersByRole,
        'registration_trends' => $registrationByMonth,
        'active_users' => array_filter($allUsers, function($user) { return $user['status'] === 'active'; }),
        'inactive_users' => array_filter($allUsers, function($user) { return $user['status'] === 'inactive'; })
    ];
}

function generatePerformanceReport($startDate, $endDate) {
    global $allProjects, $officers;
    
    $officerPerformance = [];
    foreach ($officers as $officer) {
        $officerProjects = array_filter($allProjects, function($project) use ($officer) {
            return $project['officer_id'] == $officer['id'];
        });
        
        $completedProjects = array_filter($officerProjects, function($project) { 
            return $project['status'] === 'completed'; 
        });
        
        $officerPerformance[$officer['id']] = [
            'officer_name' => $officer['first_name'] . ' ' . $officer['last_name'],
            'total_projects' => count($officerProjects),
            'completed_projects' => count($completedProjects),
            'completion_rate' => count($officerProjects) > 0 ? (count($completedProjects) / count($officerProjects)) * 100 : 0,
            'avg_project_duration' => calculateAverageProjectDuration($officerProjects)
        ];
    }
    
    return [
        'period' => ['start' => $startDate, 'end' => $endDate],
        'officer_performance' => $officerPerformance,
        'system_metrics' => [
            'total_completed_projects' => count(array_filter($allProjects, function($p) { return $p['status'] === 'completed'; })),
            'avg_completion_time' => calculateAverageProjectDuration($allProjects),
            'on_time_completion_rate' => calculateOnTimeCompletionRate($allProjects)
        ]
    ];
}

// Helper functions
function getCompletionTimeline($startDate, $endDate) {
    // This would typically query the database for completion dates
    // For now, return sample data
    return [
        '2024-01' => 5,
        '2024-02' => 8,
        '2024-03' => 12,
        '2024-04' => 7
    ];
}

function calculateAverageProjectDuration($projects) {
    $durations = [];
    foreach ($projects as $project) {
        if ($project['start_date'] && $project['end_date']) {
            $start = new DateTime($project['start_date']);
            $end = new DateTime($project['end_date']);
            $durations[] = $start->diff($end)->days;
        }
    }
    return count($durations) > 0 ? array_sum($durations) / count($durations) : 0;
}

function calculateOnTimeCompletionRate($projects) {
    $completedProjects = array_filter($projects, function($p) { return $p['status'] === 'completed'; });
    $onTimeProjects = array_filter($completedProjects, function($p) {
        if (!$p['end_date'] || !$p['actual_completion_date']) return false;
        $plannedEnd = new DateTime($p['end_date']);
        $actualEnd = new DateTime($p['actual_completion_date']);
        return $actualEnd <= $plannedEnd;
    });
    return count($completedProjects) > 0 ? (count($onTimeProjects) / count($completedProjects)) * 100 : 0;
}

// Export functions (stubs - would need proper implementation)
function generatePDFReport($reportType, $reportData, $startDate, $endDate) {
    // This would generate a PDF using a library like TCPDF or Dompdf
    // For now, just set headers for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
    // PDF generation code would go here
    exit;
}

function generateCSVReport($reportType, $reportData, $startDate, $endDate) {
    // Generate CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers and data based on report type
    switch ($reportType) {
        case 'financial':
            fputcsv($output, ['Project', 'Budget', 'Status', 'Category']);
            foreach ($reportData['projects'] as $project) {
                fputcsv($output, [
                    $project['title'],
                    $project['budget'],
                    $project['status'],
                    $project['category']
                ]);
            }
            break;
        case 'projects':
            fputcsv($output, ['Project', 'Status', 'Progress', 'Officer', 'Beneficiary']);
            // Add project data
            break;
        // Add other report types...
    }
    
    fclose($output);
    exit;
}

$pageTitle = "System Reports - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="System reports for CDF Management System - Government of Zambia">
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
            filter: brightness(0) invert(1);
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

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 76px;
        }

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

        .report-type-card {
            background: var(--white);
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            height: 100%;
        }

        .report-type-card:hover, .report-type-card.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .report-type-card:hover .report-icon, .report-type-card.active .report-icon {
            color: white;
        }

        .report-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
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

        .dashboard-footer {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--primary);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
                padding: 1.5rem 0;
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
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>System Reports & Analytics</h1>
                    <p class="lead">Comprehensive reporting and data analysis for CDF Management System</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="export-buttons">
                        <a href="?export=pdf&report_type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary-custom">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </a>
                        <a href="?export=csv&report_type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-outline-light">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Report Type Selection -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Select Report Type</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="report-type-card <?php echo $reportType === 'overview' ? 'active' : ''; ?>" onclick="location.href='?report_type=overview'">
                            <i class="fas fa-tachometer-alt report-icon"></i>
                            <h6>Overview</h6>
                            <p class="small mb-0">System-wide summary and metrics</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="report-type-card <?php echo $reportType === 'financial' ? 'active' : ''; ?>" onclick="location.href='?report_type=financial'">
                            <i class="fas fa-money-bill-wave report-icon"></i>
                            <h6>Financial</h6>
                            <p class="small mb-0">Budget and expenditure analysis</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="report-type-card <?php echo $reportType === 'projects' ? 'active' : ''; ?>" onclick="location.href='?report_type=projects'">
                            <i class="fas fa-project-diagram report-icon"></i>
                            <h6>Projects</h6>
                            <p class="small mb-0">Project status and progress</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="report-type-card <?php echo $reportType === 'performance' ? 'active' : ''; ?>" onclick="location.href='?report_type=performance'">
                            <i class="fas fa-chart-line report-icon"></i>
                            <h6>Performance</h6>
                            <p class="small mb-0">Officer and system performance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-alt me-2"></i>Report Period</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="report_type" value="<?php echo $reportType; ?>">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-file-alt me-2"></i><?php echo $reportTitle; ?></h5>
                <span class="text-muted">Generated: <?php echo date('M j, Y g:i A'); ?></span>
            </div>
            <div class="card-body">
                <?php if ($reportType === 'overview'): ?>
                    <!-- Overview Report -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $reportData['summary']['total_projects']; ?></div>
                                <div class="stat-title">Total Projects</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $reportData['summary']['total_users']; ?></div>
                                <div class="stat-title">Total Users</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number">ZMW <?php echo number_format($reportData['summary']['total_budget']); ?></div>
                                <div class="stat-title">Total Budget</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($reportData['summary']['completion_rate'], 1); ?>%</div>
                                <div class="stat-title">Completion Rate</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="projectStatusChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="budgetDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>

                <?php elseif ($reportType === 'financial'): ?>
                    <!-- Financial Report -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-number">ZMW <?php echo number_format($reportData['financial_summary']['total_budget']); ?></div>
                                <div class="stat-title">Total Budget</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-number">ZMW <?php echo number_format($reportData['financial_summary']['completed_budget']); ?></div>
                                <div class="stat-title">Completed Budget</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($reportData['financial_summary']['utilization_rate'], 1); ?>%</div>
                                <div class="stat-title">Utilization Rate</div>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container mb-4">
                        <canvas id="financialChart"></canvas>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                    <th>Category</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['projects'] as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td>ZMW <?php echo number_format($project['budget']); ?></td>
                                    <td><span class="badge bg-<?php echo getStatusBadgeClass($project['status']); ?>"><?php echo ucfirst($project['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($project['category']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" style="width: <?php echo $project['progress']; ?>%"></div>
                                        </div>
                                        <small><?php echo $project['progress']; ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reportType === 'projects'): ?>
                    <!-- Projects Report -->
                    <div class="row mb-4">
                        <?php foreach ($reportData['projects_by_status'] as $status => $projects): ?>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($projects); ?></div>
                                <div class="stat-title"><?php echo ucfirst($status); ?> Projects</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Add project-specific charts and tables here -->

                <?php elseif ($reportType === 'performance'): ?>
                    <!-- Performance Report -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Officer</th>
                                    <th>Total Projects</th>
                                    <th>Completed</th>
                                    <th>Completion Rate</th>
                                    <th>Avg Duration (Days)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData['officer_performance'] as $performance): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($performance['officer_name']); ?></td>
                                    <td><?php echo $performance['total_projects']; ?></td>
                                    <td><?php echo $performance['completed_projects']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $performance['completion_rate'] >= 80 ? 'success' : ($performance['completion_rate'] >= 50 ? 'warning' : 'danger'); ?>" 
                                                 style="width: <?php echo $performance['completion_rate']; ?>%"></div>
                                        </div>
                                        <small><?php echo number_format($performance['completion_rate'], 1); ?>%</small>
                                    </td>
                                    <td><?php echo number_format($performance['avg_project_duration'], 1); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($reportType === 'overview'): ?>
            // Project Status Chart
            const statusCtx = document.getElementById('projectStatusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Planning', 'In Progress', 'Completed', 'Delayed'],
                    datasets: [{
                        data: [
                            <?php echo $projectStatusCounts['planning']; ?>,
                            <?php echo $projectStatusCounts['in-progress']; ?>,
                            <?php echo $projectStatusCounts['completed']; ?>,
                            <?php echo $projectStatusCounts['delayed']; ?>
                        ],
                        backgroundColor: ['#6c757d', '#1a4e8a', '#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Budget Distribution Chart
            const budgetCtx = document.getElementById('budgetDistributionChart').getContext('2d');
            new Chart(budgetCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($budgetByCategory)); ?>,
                    datasets: [{
                        label: 'Budget (ZMW)',
                        data: <?php echo json_encode(array_values($budgetByCategory)); ?>,
                        backgroundColor: '#1a4e8a'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            <?php elseif ($reportType === 'financial'): ?>
            // Financial Chart
            const financialCtx = document.getElementById('financialChart').getContext('2d');
            new Chart(financialCtx, {
                type: 'bar',
                data: {
                    labels: ['Planning', 'In Progress', 'Completed', 'Delayed'],
                    datasets: [{
                        label: 'Budget by Status (ZMW)',
                        data: [
                            <?php echo $reportData['budget_by_status']['planning']; ?>,
                            <?php echo $reportData['budget_by_status']['in-progress']; ?>,
                            <?php echo $reportData['budget_by_status']['completed']; ?>,
                            <?php echo $reportData['budget_by_status']['delayed']; ?>
                        ],
                        backgroundColor: ['#6c757d', '#1a4e8a', '#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
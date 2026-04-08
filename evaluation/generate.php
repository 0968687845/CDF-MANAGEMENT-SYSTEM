<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);
$projects = getOfficerProjects($_SESSION['user_id']);

// Get project ID from URL for specific project
$project_id = $_GET['project_id'] ?? null;
$selected_project = $project_id ? getProjectById($project_id) : null;

// Advanced Analytics Calculations
$total_projects = count($projects);

// Fix budget calculation - ensure we're getting numeric values
$total_budget = 0;
foreach ($projects as $project) {
    $budget = is_numeric($project['budget']) ? $project['budget'] : 0;
    $total_budget += $budget;
}

$total_beneficiaries = count(array_unique(array_column($projects, 'beneficiary_id')));

// Progress Analysis
$progress_data = [];
$status_distribution = [];
$compliance_scores = [];
$risk_assessments = [];

foreach ($projects as $project) {
    if ($project_id && $project['id'] != $project_id) {
        continue;
    }
    
    // Get detailed progress data
    $progress_updates = getProjectProgress($project['id']);
    $project_expenses = getProjectExpenses($project['id']);
    $total_expenses = getTotalProjectExpenses($project['id']);
    
    // Calculate advanced metrics
    $budget_utilization = $project['budget'] > 0 ? ($total_expenses / $project['budget']) * 100 : 0;
    $progress_efficiency = $project['progress'] > 0 ? ($project['progress'] / (max(1, count($progress_updates)))) : 0;
    
    // Machine Learning Risk Assessment (Simulated)
    $risk_score = calculateRiskScore($project, $progress_updates, $total_expenses);
    $compliance_score = calculateComplianceScore($project, $progress_updates);
    
    $progress_data[] = [
        'project' => $project,
        'progress_updates' => $progress_updates,
        'expenses' => $project_expenses,
        'total_expenses' => $total_expenses,
        'budget_utilization' => $budget_utilization,
        'progress_efficiency' => $progress_efficiency,
        'risk_score' => $risk_score,
        'compliance_score' => $compliance_score,
        'ml_insights' => generateMLInsights($project, $progress_updates, $risk_score)
    ];
    
    // Status distribution
    $status = $project['status'] ?? 'planning';
    $status_distribution[$status] = ($status_distribution[$status] ?? 0) + 1;
}

// Machine Learning Helper Functions
function calculateRiskScore($project, $progress_updates, $total_expenses) {
    $score = 0;
    
    // Progress-based risk (stalled projects)
    $days_since_start = (time() - strtotime($project['start_date'])) / (60 * 60 * 24);
    $expected_progress = min(100, ($days_since_start / 365) * 100); // Assuming 1-year timeline
    $progress_gap = max(0, $expected_progress - $project['progress']);
    $score += $progress_gap * 0.5;
    
    // Budget risk
    $budget_utilization = $project['budget'] > 0 ? ($total_expenses / $project['budget']) * 100 : 0;
    if ($budget_utilization > $project['progress'] + 20) {
        $score += 30; // Overspending
    }
    
    // Update frequency risk
    $update_count = count($progress_updates);
    $expected_updates = max(1, $days_since_start / 30); // Monthly updates expected
    if ($update_count < $expected_updates * 0.5) {
        $score += 20; // Infrequent updates
    }
    
    // Timeline risk
    $days_remaining = (strtotime($project['end_date']) - time()) / (60 * 60 * 24);
    if ($days_remaining < 30 && $project['progress'] < 80) {
        $score += 40; // Approaching deadline with low progress
    }
    
    return min(100, $score);
}

function calculateComplianceScore($project, $progress_updates) {
    $score = 80; // Base score
    
    // Progress documentation compliance
    $latest_update = !empty($progress_updates) ? $progress_updates[0] : null;
    if ($latest_update) {
        $update_age = (time() - strtotime($latest_update['created_at'])) / (60 * 60 * 24);
        if ($update_age < 30) $score += 10; // Recent update
        if (!empty($latest_update['challenges'])) $score += 5; // Good documentation
        if (!empty($latest_update['next_steps'])) $score += 5; // Good planning
    }
    
    // Project status compliance
    if ($project['progress'] >= 100 && $project['status'] !== 'completed') {
        $score -= 20; // Status mismatch
    }
    
    return min(100, max(0, $score));
}

function generateMLInsights($project, $progress_updates, $risk_score) {
    $insights = [];
    
    // Progress trend analysis
    if (count($progress_updates) >= 3) {
        $recent_updates = array_slice($progress_updates, 0, 3);
        $progress_trend = ($recent_updates[0]['progress_percentage'] - $recent_updates[2]['progress_percentage']) / 2;
        
        if ($progress_trend > 5) {
            $insights[] = "Strong positive momentum detected";
        } elseif ($progress_trend < -2) {
            $insights[] = "Progress slowdown detected";
        }
    }
    
    // Risk-based insights
    if ($risk_score > 70) {
        $insights[] = "High-risk project requiring immediate attention";
    } elseif ($risk_score > 40) {
        $insights[] = "Medium risk - monitor closely";
    } else {
        $insights[] = "Low risk - good progress";
    }
    
    // Timeline insights
    $days_remaining = (strtotime($project['end_date']) - time()) / (60 * 60 * 24);
    if ($days_remaining < 30 && $project['progress'] < 90) {
        $insights[] = "Approaching deadline - consider timeline extension";
    }
    
    return $insights;
}

// Performance Analytics
$average_progress = $total_projects > 0 ? array_sum(array_column($projects, 'progress')) / $total_projects : 0;
$high_risk_projects = array_filter($progress_data, fn($p) => $p['risk_score'] > 70);
$medium_risk_projects = array_filter($progress_data, fn($p) => $p['risk_score'] > 40 && $p['risk_score'] <= 70);
$low_risk_projects = array_filter($progress_data, fn($p) => $p['risk_score'] <= 40);

// Chart Data Preparation
$chart_labels = [];
$chart_progress = [];
$chart_risk = [];
$chart_compliance = [];

foreach ($progress_data as $data) {
    $chart_labels[] = substr($data['project']['title'], 0, 20) . '...';
    $chart_progress[] = $data['project']['progress'];
    $chart_risk[] = $data['risk_score'];
    $chart_compliance[] = $data['compliance_score'];
}

$pageTitle = "AI Progress Analytics - CDF Management System";
if ($selected_project) {
    $pageTitle = "AI Progress Analytics - " . htmlspecialchars($selected_project['title']) . " - CDF Management System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="AI-powered progress analytics and evaluation - CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
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
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
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
            padding: 2.5rem 0;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
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
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .profile-info {
            position: relative;
            z-index: 1;
        }

        .profile-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .btn-primary-custom {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-subtitle {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.2;
            color: var(--primary);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .content-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.25rem 1.5rem;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Analytics Cards */
        .analytics-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
            position: relative;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        /* Risk Indicators */
        .risk-high { border-top-color: var(--danger); }
        .risk-medium { border-top-color: var(--warning); }
        .risk-low { border-top-color: var(--success); }

        .risk-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .risk-high .risk-badge { background: var(--danger); color: white; }
        .risk-medium .risk-badge { background: var(--warning); color: #212529; }
        .risk-low .risk-badge { background: var(--success); color: white; }

        /* Chart Containers */
        .chart-container {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            height: 100%;
            transition: var(--transition);
        }

        .chart-container:hover {
            box-shadow: var(--shadow-lg);
        }

        /* AI Insights */
        .ai-insight {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .ai-insight::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(40%, -40%);
        }

        .insight-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

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

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--primary);
        }

        /* Progress bars */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: var(--gray-light);
        }

        .progress-bar {
            border-radius: 4px;
        }

        /* Table improvements */
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--primary);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(26, 78, 138, 0.05);
        }

        /* Badge improvements */
        .badge {
            font-weight: 500;
            padding: 0.4em 0.6em;
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

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Section spacing */
        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-light);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF AI Analytics Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../officer_dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
                            </a></li>
                            <li><a class="dropdown-item active" href="progress.php">
                                <i class="fas fa-chart-bar me-2"></i>AI Analytics
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
                                    <a class="dropdown-item" href="../communication/notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="../communication/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="../communication/notifications.php">No new notifications</a></li>
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
                                            <small class="text-muted">M&E Officer</small>
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

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1>AI Progress Analytics</h1>
                    <p class="lead">Welcome back, <?php echo htmlspecialchars($userData['first_name']); ?>! - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Machine Learning-powered insights and predictive analytics for project monitoring</p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="../projects/index.php" class="btn btn-primary-custom">
                    <i class="fas fa-project-diagram me-2"></i>View All Projects
                </a>
                <a href="../evaluation/reports.php" class="btn btn-outline-custom">
                    <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                </a>
                <a href="../site-visits/schedule.php" class="btn btn-outline-custom">
                    <i class="fas fa-map-marker-alt me-2"></i>Schedule Site Visit
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Insights Overview -->
        <div class="row mb-4 fade-in">
            <div class="col-12">
                <div class="insight">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="insight-icon">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h3> Insights Dashboard</h3>
                            <p class="mb-0">Machine Learning algorithms are analyzing <?php echo $total_projects; ?> projects in real-time to provide predictive risk assessments and performance insights.</p>
                        </div>
                        <div class="col-md-4 text-center text-md-end">
                            <div class="display-3 fw-bold"><?php echo round($average_progress); ?>%</div>
                            <p class="mb-0">Average Project Progress</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <h3 class="section-title mt-5">Performance Overview</h3>
        <div class="row g-4 mb-5 fade-in">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-project-diagram stat-icon"></i>
                    <div class="stat-number"><?php echo $total_projects; ?></div>
                    <div class="stat-title">Projects Monitored</div>
                    <div class="stat-subtitle">Total assigned projects</div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                    <div class="stat-number"><?php echo count($high_risk_projects); ?></div>
                    <div class="stat-title">High Risk Projects</div>
                    <div class="stat-subtitle">Requiring immediate attention</div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-danger" style="width: <?php echo $total_projects > 0 ? (count($high_risk_projects) / $total_projects) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave stat-icon"></i>
                    <div class="stat-number">ZMW <?php echo number_format($total_budget, 2); ?>M</div>
                    <div class="stat-title">Total Budget</div>
                    <div class="stat-subtitle">Across all projects</div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="fas fa-users stat-icon"></i>
                    <div class="stat-number"><?php echo $total_beneficiaries; ?></div>
                    <div class="stat-title">Beneficiaries</div>
                    <div class="stat-subtitle">Impacted by projects</div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <h3 class="section-title">Risk & Progress Analysis</h3>
        <div class="row g-4 mb-5 fade-in">
            <!-- Progress vs Risk Chart -->
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar me-2"></i>Progress vs Risk Analysis</h5>
                    <canvas id="progressRiskChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Risk Distribution -->
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie me-2"></i>Risk Distribution</h5>
                    <canvas id="riskDistributionChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Project Analytics Table -->
        <h3 class="section-title">Detailed Project Analytics</h3>
        <div class="row fade-in">
            <div class="col-12">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-table me-2"></i>AI Project Analytics</h5>
                        <div>
                            <span class="badge bg-primary me-2">Total: <?php echo $total_projects; ?></span>
                            <span class="badge bg-danger">High Risk: <?php echo count($high_risk_projects); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Project</th>
                                        <th>Progress</th>
                                        <th>Risk Score</th>
                                        <th>Compliance</th>
                                        <th>Budget Util.</th>
                                        <th>AI Insights</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progress_data as $data): 
                                        $project = $data['project'];
                                        $risk_class = $data['risk_score'] > 70 ? 'risk-high' : ($data['risk_score'] > 40 ? 'risk-medium' : 'risk-low');
                                    ?>
                                    <tr class="<?php echo $risk_class; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unknown'); ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo $project['progress']; ?>%"></div>
                                                </div>
                                                <small class="text-nowrap"><?php echo $project['progress']; ?>%</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="risk-badge"><?php echo round($data['risk_score']); ?>%</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-info" style="width: <?php echo $data['compliance_score']; ?>%"></div>
                                                </div>
                                                <small class="text-nowrap"><?php echo round($data['compliance_score']); ?>%</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: <?php echo min(100, $data['budget_utilization']); ?>%"></div>
                                                </div>
                                                <small class="text-nowrap"><?php echo round($data['budget_utilization']); ?>%</small>
                                            </div>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if (!empty($data['ml_insights'])): ?>
                                                    <?php foreach (array_slice($data['ml_insights'], 0, 2) as $insight): ?>
                                                        <span class="badge bg-light text-dark mb-1 d-block"><?php echo $insight; ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No insights</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../projects/review.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary" title="Review Project">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                                <a href="../evaluation/reports.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-success" title="Evaluation Reports">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Predictive Analytics -->
        <h3 class="section-title">Predictive Analytics & Recommendations</h3>
        <div class="row g-4 mt-2 fade-in">
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-robot me-2"></i>Predictive Timeline Analysis</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">AI-powered completion date predictions based on current progress trends</p>
                        <div id="timelinePredictions">
                            <?php foreach (array_slice($progress_data, 0, 4) as $data): 
                                $project = $data['project'];
                                $predicted_completion = date('M j, Y', strtotime($project['end_date']));
                                if ($project['progress'] < 50) {
                                    $predicted_completion = date('M j, Y', strtotime('+30 days'));
                                } elseif ($project['progress'] < 80) {
                                    $predicted_completion = date('M j, Y', strtotime('+15 days'));
                                }
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                                <div>
                                    <strong><?php echo htmlspecialchars(substr($project['title'], 0, 25)); ?>...</strong>
                                    <br><small class="text-muted">Current: <?php echo $project['progress']; ?>%</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Predicted Completion:</small>
                                    <br><strong class="text-primary"><?php echo $predicted_completion; ?></strong>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bell me-2"></i>AI Recommendations</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Machine Learning-generated action items</p>
                        <div id="aiRecommendations">
                            <?php 
                            $recommendations = [
                                "Schedule site visits for high-risk projects immediately",
                                "Review budget utilization for projects exceeding 90% spend",
                                "Contact beneficiaries with stalled progress (>30 days)",
                                "Generate compliance reports for upcoming audits",
                                "Prioritize projects with risk scores above 70%"
                            ];
                            foreach ($recommendations as $rec): 
                            ?>
                            <div class="d-flex align-items-start mb-3 p-2 bg-light rounded">
                                <i class="fas fa-robot text-primary me-3 mt-1"></i>
                                <div>
                                    <small class="fw-medium"><?php echo $rec; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <img src="../coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3">
                        <div>
                            <h5 class="mb-0">CDF AI Analytics System</h5>
                            <p class="mb-0 text-muted">Government of the Republic of Zambia</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> - All Rights Reserved</p>
                    <p class="mb-0 text-muted">Powered by Machine Learning & Real-time Analytics | <span id="serverTime"><?php echo date('H:i:s'); ?></span></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Progress vs Risk Chart
                const progressRiskCtx = document.getElementById('progressRiskChart').getContext('2d');
                <?php
                    // Prepare JS-safe data structures using json_encode to avoid trailing commas or malformed strings
                    $chart_points = array_map(function($d) {
                        return [
                            'x' => (float)($d['project']['progress'] ?? 0),
                            'y' => (float)($d['risk_score'] ?? 0),
                            'project' => $d['project']['title'] ?? ''
                        ];
                    }, $progress_data);
                    $chart_colors = array_map(function($d) {
                        $score = $d['risk_score'] ?? 0;
                        return $score > 70 ? '#dc3545' : ($score > 40 ? '#ffc107' : '#28a745');
                    }, $progress_data);
                ?>
                const progressData = JSON.parse('<?php echo addslashes(json_encode($chart_points, JSON_UNESCAPED_UNICODE)); ?>' || '[]');
                const progressColors = JSON.parse('<?php echo addslashes(json_encode($chart_colors)); ?>' || '[]');
                new Chart(progressRiskCtx, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Project Progress vs Risk',
                            data: progressData,
                            backgroundColor: progressColors,
                            borderColor: '#fff',
                            borderWidth: 2,
                            pointRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.raw.project + ': ' + context.raw.x + '% Progress, ' + context.raw.y + '% Risk';
                                    }
                                }
                            },
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                title: { 
                                    display: true, 
                                    text: 'Progress %',
                                    font: { weight: 'bold' }
                                },
                                min: 0, 
                                max: 100
                            },
                            y: {
                                title: { 
                                    display: true, 
                                    text: 'Risk Score %',
                                    font: { weight: 'bold' }
                                },
                                min: 0, 
                                max: 100
                            }
                        }
                    }
                });

        // Risk Distribution Chart
        const riskDistCtx = document.getElementById('riskDistributionChart').getContext('2d');
        new Chart(riskDistCtx, {
            type: 'doughnut',
            data: {
                labels: ['High Risk', 'Medium Risk', 'Low Risk'],
                datasets: [{
                    data: [
                        <?php echo count($high_risk_projects); ?>,
                        <?php echo count($medium_risk_projects); ?>,
                        <?php echo count($low_risk_projects); ?>
                    ],
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            font: { size: 12 }
                        }
                    }
                }
            }
        });

        // Real-time updates
        function updateServerTime() {
            const now = new Date();
            document.getElementById('serverTime').textContent = now.toLocaleTimeString();
        }
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Add fade-in animation to elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            
            const fadeInObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            fadeElements.forEach(el => {
                el.style.opacity = 0;
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                fadeInObserver.observe(el);
            });
        });
    </script>
</body>
</html>
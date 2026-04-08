<?php
require_once '../functions.php';
requireRole('officer');

// Create table if it doesn't exist
createImpactAssessmentsTable();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get assigned projects for impact assessment
$assigned_projects = getOfficerProjects($_SESSION['user_id']);

// Get impact assessment statistics
$impact_stats = getImpactStatistics($_SESSION['user_id']);

// Handle impact assessment submission
if (isset($_POST['submit_impact_assessment'])) {
    $impact_data = [
        'project_id' => $_POST['project_id'],
        'community_beneficiaries' => $_POST['community_beneficiaries'],
        'employment_generated' => $_POST['employment_generated'],
        'economic_impact' => $_POST['economic_impact'],
        'social_impact' => $_POST['social_impact'],
        'environmental_impact' => $_POST['environmental_impact'],
        'sustainability_score' => $_POST['sustainability_score'],
        'overall_impact' => $_POST['overall_impact'],
        'success_stories' => $_POST['success_stories'],
        'challenges' => $_POST['challenges'],
        'recommendations' => $_POST['recommendations'],
        'officer_id' => $_SESSION['user_id']
    ];
    
    if (submitImpactAssessment($impact_data)) {
        $_SESSION['success'] = "Impact assessment submitted successfully!";
        redirect('impact.php');
    } else {
        $_SESSION['error'] = "Failed to submit impact assessment. Please try again.";
    }
}

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'impact';
$date_range = $_GET['date_range'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$project_filter = $_GET['project_id'] ?? 'all';
$impact_level = $_GET['impact_level'] ?? 'all';

// If user selected a different report type, redirect to the appropriate tool
if ($report_type !== 'all' && $report_type !== 'impact') {
    $map = [
        'progress' => 'progress.php',
        'compliance' => 'compliance.php',
        'quality' => 'quality.php',
        'all' => 'reports.php'
    ];
    $target = $map[$report_type] ?? 'reports.php';
    // Preserve other filters in the query
    $qs = http_build_query([
        'date_range' => $date_range,
        'status' => $status_filter,
        'project_id' => $project_filter,
        'impact_level' => $impact_level
    ]);
    redirect($target . ($qs ? '?' . $qs : ''));
}

// Get recent impact assessments with filters applied
$all_assessments = getRecentImpactAssessments($_SESSION['user_id'], 100);

// Apply filters to assessments
$filtered_assessments = array_filter($all_assessments, function($assessment) use ($date_range, $project_filter, $impact_level, $status_filter) {
    // Date range filter
    if ($date_range !== 'all') {
        $assessment_date = strtotime($assessment['assessment_date'] ?? date('Y-m-d'));
        $today = strtotime(date('Y-m-d'));
        
        switch ($date_range) {
            case 'today':
                if ($assessment_date < $today || $assessment_date > $today + 86400) return false;
                break;
            case 'week':
                if ($today - $assessment_date > 7 * 86400) return false;
                break;
            case 'month':
                if ($today - $assessment_date > 30 * 86400) return false;
                break;
            case 'quarter':
                if ($today - $assessment_date > 90 * 86400) return false;
                break;
            case 'year':
                if ($today - $assessment_date > 365 * 86400) return false;
                break;
        }
    }
    
    // Project filter
    if ($project_filter !== 'all') {
        if ($assessment['project_id'] != $project_filter) return false;
    }
    
    // Impact level filter
    if ($impact_level !== 'all') {
        $overall = $assessment['overall_impact'] ?? 0;
        switch ($impact_level) {
            case 'high':
                if ($overall < 4) return false;
                break;
            case 'moderate':
                if ($overall < 3 || $overall >= 4) return false;
                break;
            case 'low':
                if ($overall < 2 || $overall >= 3) return false;
                break;
            case 'minimal':
                if ($overall >= 2) return false;
                break;
        }
    }
    
    return true;
});

// Re-sort by date
usort($filtered_assessments, function($a, $b) {
    return strtotime($b['assessment_date'] ?? 'now') - strtotime($a['assessment_date'] ?? 'now');
});

$recent_assessments = array_slice($filtered_assessments, 0, 50);

$pageTitle = "Impact Assessment - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Impact Assessment dashboard for CDF Management System - Government of Zambia">
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
        --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        --secondary: #e9b949;
        --secondary-dark: #d4a337;
        --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
        
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
        --shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.18);
        
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --border-radius: 12px;
        --border-radius-lg: 16px;
        
        --success: #28a745;
        --warning: #ffc107;
        --info: #17a2b8;
        --white: #ffffff;
    }

    body {
        font-family: 'Segoe UI', system-ui, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        background-attachment: fixed;
        color: #212529;
        line-height: 1.7;
    }

    .navbar {
        background: var(--primary-gradient);
        box-shadow: var(--shadow-lg);
        padding: 0.8rem 0;
    }

    .navbar-brand {
        font-weight: 800;
        color: white !important;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.95) !important;
        font-weight: 600;
        transition: var(--transition);
        padding: 0.6rem 1rem !important;
        border-radius: 8px;
    }

    .nav-link:hover, 
    .nav-link.active {
        color: white !important;
        background-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .dashboard-header {
        background: var(--primary-gradient);
        color: white;
        padding: 3rem 0 2.5rem;
        margin-top: 76px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
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
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--secondary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #212529;
        font-size: 2rem;
        font-weight: 800;
        box-shadow: var(--shadow-lg);
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .profile-info h1 {
        font-size: 2.25rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    .btn-primary-custom {
        background: var(--secondary-gradient);
        color: #212529;
        border: none;
        padding: 1rem 2rem;
        font-weight: 700;
        border-radius: var(--border-radius);
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .btn-primary-custom:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    .btn-outline-custom {
        background: transparent;
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.7);
        padding: 1rem 2rem;
        font-weight: 600;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .btn-outline-custom:hover {
        background: white;
        color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .stats-container {
        margin: 2.5rem 0;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius-lg);
        padding: 2rem 1.5rem;
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
        height: 100%;
        border-top: 5px solid var(--primary);
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
        color: #6c757d;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .content-card {
        background: white;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: var(--transition);
    }

    .content-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 3px solid var(--primary);
        padding: 1.5rem;
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

    .badge {
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        box-shadow: var(--shadow-sm);
    }

    .badge-excellent { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; }
    .badge-good { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; }
    .badge-fair { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529; }
    .badge-poor { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; }

    .impact-form {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: var(--border-radius);
        border: 1px solid #dee2e6;
    }

    .form-label {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .impact-score {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
    }

    .impact-item {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-left: 4px solid var(--primary);
        box-shadow: var(--shadow-sm);
    }

    .impact-summary {
        background: #e8f4fd;
        border: 1px solid #b6d7f7;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    /* Evaluation Tools Grid */
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

    .beneficiary-count {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        text-align: center;
        margin: 0.5rem 0;
    }

    .impact-metric {
        background: white;
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
    }

    .metric-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }

    /* Quick Actions Grid */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .action-card {
        background: white;
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

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
    }

    .action-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--primary);
        transition: color 0.3s ease;
    }

    .action-card:hover .action-icon {
        color: white;
    }

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

        .quick-actions {
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
                CDF M&E Officer Portal
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
                            <li><a class="dropdown-item" href="http://localhost/cdf_system/projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="http://localhost/cdf_system/evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="http://localhost/cdf_system/site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="http://localhost/cdf_system/communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
                            </a></li>
                            <li><a class="dropdown-item" href="http://localhost/cdf_system/analytics/dashboard.php">
                                <i class="fas fa-chart-bar me-2"></i>Analytics
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
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: #212529; font-weight: 700;">
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
                    <h1>Impact Assessment</h1>
                    <p class="lead">Welcome back, <?php echo htmlspecialchars($userData['first_name']); ?>! - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Evaluate CDF project community impact and sustainability outcomes</p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="#impact-form" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i>New Assessment
                </a>
                <a href="../evaluation/reports.php" class="btn btn-outline-custom">
                    <i class="fas fa-chart-bar me-2"></i>Impact Analytics
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
                        <div class="stat-number"><?php echo $impact_stats['total_assessments'] ?? 0; ?></div>
                        <div class="stat-title">Impact Assessments</div>
                        <div class="stat-subtitle">Community impact evaluations</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $impact_stats['total_beneficiaries'] ?? 0; ?></div>
                        <div class="stat-title">Total Beneficiaries</div>
                        <div class="stat-subtitle">Community members served</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $impact_stats['avg_impact_score'] ?? 0; ?>%</div>
                        <div class="stat-title">Avg Impact Score</div>
                        <div class="stat-subtitle">Overall impact rating</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $impact_stats['jobs_created'] ?? 0; ?></div>
                        <div class="stat-title">Jobs Created</div>
                        <div class="stat-subtitle">Employment opportunities</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluation Tools -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-tools me-2"></i>Evaluation Tools</h5>
            </div>
            <div class="card-body">
                <div class="tools-grid">
                    <div class="tool-card success" onclick="location.href='../evaluation/progress.php'">
                        <i class="fas fa-chart-line tool-icon"></i>
                        <h6>Progress Review</h6>
                        <p class="small mb-0">Review beneficiary progress reports</p>
                    </div>
                    <div class="tool-card warning" onclick="location.href='../evaluation/compliance.php'">
                        <i class="fas fa-check-double tool-icon"></i>
                        <h6>Compliance Check</h6>
                        <p class="small mb-0">Verify CDF guidelines compliance</p>
                    </div>
                    <div class="tool-card info" onclick="location.href='../evaluation/quality.php'">
                        <i class="fas fa-award tool-icon"></i>
                        <h6>Quality Assessment</h6>
                        <p class="small mb-0">Evaluate project quality standards</p>
                    </div>
                    <div class="tool-card" onclick="location.href='../evaluation/impact.php'">
                        <i class="fas fa-bullseye tool-icon"></i>
                        <h6>Impact Evaluation</h6>
                        <p class="small mb-0">Assess project impact metrics</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <div class="action-card" onclick="location.href='#impact-form'">
                        <i class="fas fa-plus-circle action-icon"></i>
                        <h6>New Assessment</h6>
                    </div>
                    <div class="action-card" onclick="location.href='../evaluation/reports.php'">
                        <i class="fas fa-chart-bar action-icon"></i>
                        <h6>Impact Analytics</h6>
                    </div>
                    <div class="action-card" onclick="location.href='../site-visits/index.php'">
                        <i class="fas fa-map-marker-alt action-icon"></i>
                        <h6>Site Visits</h6>
                    </div>
                    <div class="action-card" onclick="location.href='../projects/index.php'">
                        <i class="fas fa-project-diagram action-icon"></i>
                        <h6>My Projects</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Impact Assessment Form -->
        <div class="content-card" id="impact-form">
            <div class="card-header">
                <h5><i class="fas fa-bullseye me-2"></i>New Impact Assessment</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="impact-form" id="impactForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Select Project</label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">Choose a project to assess...</option>
                                    <?php foreach ($assigned_projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>">
                                            <?php echo htmlspecialchars($project['title']); ?> - 
                                            <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unknown Beneficiary'); ?>
                                            (<?php echo htmlspecialchars($project['constituency'] ?? ''); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Select from your assigned CDF projects</small>
                            </div>
                        </div>
                    </div>

                    <!-- Impact Assessment Summary -->
                    <div class="impact-summary">
                        <h6><i class="fas fa-info-circle me-2"></i>Community Impact Assessment</h6>
                        <p class="mb-2">Evaluate the project's impact on the community and sustainability (1-5 scale)</p>
                        <div class="impact-score" id="overallImpact">Overall Impact Score: 0/5</div>
                    </div>

                    <!-- Quantitative Impact Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="impact-metric">
                                <label class="form-label">Direct Beneficiaries</label>
                                <small class="text-muted d-block mb-2">Number of community members directly benefiting</small>
                                <input type="number" name="community_beneficiaries" class="form-control" min="0" max="10000" value="0" required>
                                <div class="beneficiary-count" id="beneficiaryCount">0 people</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="impact-metric">
                                <label class="form-label">Employment Generated</label>
                                <small class="text-muted d-block mb-2">Number of jobs created (temporary + permanent)</small>
                                <input type="number" name="employment_generated" class="form-control" min="0" max="500" value="0" required>
                                <div class="beneficiary-count" id="employmentCount">0 jobs</div>
                            </div>
                        </div>
                    </div>

                    <!-- Qualitative Impact Assessment -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="impact-item">
                                <label class="form-label">Economic Impact</label>
                                <small class="text-muted d-block mb-2">Local economic benefits and income generation</small>
                                <select name="economic_impact" class="form-select" onchange="calculateOverallImpact()" required>
                                    <option value="1">1 - Minimal (No economic benefits)</option>
                                    <option value="2">2 - Limited (Minor local benefits)</option>
                                    <option value="3" selected>3 - Moderate (Some economic activity)</option>
                                    <option value="4">4 - Significant (Substantial benefits)</option>
                                    <option value="5">5 - Transformative (Major economic impact)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="impact-item">
                                <label class="form-label">Social Impact</label>
                                <small class="text-muted d-block mb-2">Community cohesion and social benefits</small>
                                <select name="social_impact" class="form-select" onchange="calculateOverallImpact()" required>
                                    <option value="1">1 - Negative (Community division)</option>
                                    <option value="2">2 - Neutral (No social change)</option>
                                    <option value="3" selected>3 - Positive (Improved relations)</option>
                                    <option value="4">4 - Significant (Strong cohesion)</option>
                                    <option value="5">5 - Transformative (Community empowerment)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="impact-item">
                                <label class="form-label">Environmental Impact</label>
                                <small class="text-muted d-block mb-2">Environmental sustainability and conservation</small>
                                <select name="environmental_impact" class="form-select" onchange="calculateOverallImpact()" required>
                                    <option value="1">1 - Harmful (Environmental damage)</option>
                                    <option value="2">2 - Neutral (No impact)</option>
                                    <option value="3" selected>3 - Positive (Some benefits)</option>
                                    <option value="4">4 - Significant (Environmental improvement)</option>
                                    <option value="5">5 - Excellent (Sustainable practices)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="impact-item">
                                <label class="form-label">Sustainability</label>
                                <small class="text-muted d-block mb-2">Long-term viability and maintenance</small>
                                <select name="sustainability_score" class="form-select" onchange="calculateOverallImpact()" required>
                                    <option value="1">1 - Unsustainable (Will fail quickly)</option>
                                    <option value="2">2 - Limited (Short-term only)</option>
                                    <option value="3" selected>3 - Moderate (Some sustainability)</option>
                                    <option value="4">4 - Good (Likely to continue)</option>
                                    <option value="5">5 - Excellent (Fully sustainable)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="overall_impact" id="overall_impact" value="3">

                    <div class="mb-3">
                        <label class="form-label">Success Stories & Positive Outcomes</label>
                        <textarea name="success_stories" class="form-control" rows="3" 
                                  placeholder="Document specific success stories, positive changes observed in the community, and notable achievements..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Challenges & Lessons Learned</label>
                        <textarea name="challenges" class="form-control" rows="2" 
                                  placeholder="What challenges were encountered? What lessons can be applied to future projects?"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Recommendations for Future Projects</label>
                        <textarea name="recommendations" class="form-control" rows="2" 
                                  placeholder="Recommendations for improving impact in future CDF projects..."></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="submit_impact_assessment" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-2"></i>Submit Impact Assessment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Impact Assessments -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history me-2"></i>Recent Impact Assessments</h5>
                <a href="../evaluation/reports.php" class="btn btn-primary-custom btn-sm">
                    <i class="fas fa-list me-2"></i>View All Reports
                </a>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #dee2e6;">
                    <form method="GET" class="row g-3" id="impactFilters">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Report Type</label>
                            <select name="report_type" class="form-select" onchange="handleReportTypeChange(this)">
                                <option value="impact" <?php echo $report_type === 'impact' ? 'selected' : ''; ?>>Impact Evaluations</option>
                                <option value="progress">Progress Reports</option>
                                <option value="compliance">Compliance Reports</option>
                                <option value="quality">Quality Assessments</option>
                                <option value="all">All Evaluations</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="in-progress" <?php echo $status_filter === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="delayed" <?php echo $status_filter === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                <option value="planning" <?php echo $status_filter === 'planning' ? 'selected' : ''; ?>>Planning</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Date Range</label>
                            <select name="date_range" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $date_range === 'all' ? 'selected' : ''; ?>>All Dates</option>
                                <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="quarter" <?php echo $date_range === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                                <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>This Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Project</label>
                            <select name="project_id" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $project_filter === 'all' ? 'selected' : ''; ?>>All Projects</option>
                                <?php foreach ($assigned_projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>" <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(substr($project['title'], 0, 40)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Impact Level</label>
                            <select name="impact_level" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $impact_level === 'all' ? 'selected' : ''; ?>>All Levels</option>
                                <option value="high" <?php echo $impact_level === 'high' ? 'selected' : ''; ?>>High Impact (4-5)</option>
                                <option value="moderate" <?php echo $impact_level === 'moderate' ? 'selected' : ''; ?>>Moderate (3-3.9)</option>
                                <option value="low" <?php echo $impact_level === 'low' ? 'selected' : ''; ?>>Low (2-2.9)</option>
                                <option value="minimal" <?php echo $impact_level === 'minimal' ? 'selected' : ''; ?>>Minimal (&lt;2)</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <a href="impact.php" class="btn btn-secondary w-100">
                                <i class="fas fa-redo me-1"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Filter Results Info -->
                <?php if ($date_range !== 'all' || $project_filter !== 'all' || $impact_level !== 'all' || $status_filter !== 'all' || $report_type !== 'impact'): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                        <div>
                            <i class="fas fa-filter me-2"></i>
                            <strong>Active Filters:</strong> 
                            <?php
                            $active_filters = [];
                            if ($report_type !== 'impact' && $report_type !== 'all') {
                                $active_filters[] = ucfirst($report_type) . ' Reports';
                            }
                            if ($date_range !== 'all') {
                                $date_labels = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year'];
                                $active_filters[] = $date_labels[$date_range] ?? $date_range;
                            }
                            if ($status_filter !== 'all') {
                                $active_filters[] = 'Status: ' . ucfirst(str_replace('-', ' ', $status_filter));
                            }
                            if ($project_filter !== 'all') {
                                $project = array_filter($assigned_projects, fn($p) => $p['id'] == $project_filter);
                                if (!empty($project)) {
                                    $project = array_values($project)[0];
                                    $active_filters[] = 'Project: ' . htmlspecialchars(substr($project['title'], 0, 30));
                                }
                            }
                            if ($impact_level !== 'all') {
                                $level_labels = ['high' => 'High Impact', 'moderate' => 'Moderate', 'low' => 'Low', 'minimal' => 'Minimal'];
                                $active_filters[] = $level_labels[$impact_level] ?? $impact_level;
                            }
                            echo implode(' | ', $active_filters);
                            ?>
                        </div>
                        <span class="badge bg-info"><?php echo count($filtered_assessments); ?> results</span>
                    </div>
                <?php endif; ?>

                <?php if (count($recent_assessments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Date</th>
                                    <th>Beneficiaries</th>
                                    <th>Economic</th>
                                    <th>Social</th>
                                    <th>Overall</th>
                                    <th>Impact Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_assessments as $assessment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($assessment['project_title'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($assessment['beneficiary_name'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($assessment['assessment_date'] ?? 'now')); ?></td>
                                    <td>
                                        <strong><?php echo $assessment['community_beneficiaries'] ?? 0; ?></strong>
                                        <br><small>people</small>
                                    </td>
                                    <td>
                                        <?php
                                        $economic = $assessment['economic_impact'] ?? 0;
                                        echo str_repeat('⭐', $economic) . str_repeat('☆', 5 - $economic);
                                        ?>
                                        <br><small><?php echo $economic; ?>/5</small>
                                    </td>
                                    <td>
                                        <?php
                                        $social = $assessment['social_impact'] ?? 0;
                                        echo str_repeat('⭐', $social) . str_repeat('☆', 5 - $social);
                                        ?>
                                        <br><small><?php echo $social; ?>/5</small>
                                    </td>
                                    <td>
                                        <strong class="impact-score"><?php echo $assessment['overall_impact'] ?? 0; ?>/5</strong>
                                    </td>
                                    <td>
                                        <?php
                                        $overall = $assessment['overall_impact'] ?? 0;
                                        if ($overall >= 4) {
                                            echo '<span class="badge badge-excellent">High Impact</span>';
                                        } elseif ($overall >= 3) {
                                            echo '<span class="badge badge-good">Moderate Impact</span>';
                                        } elseif ($overall >= 2) {
                                            echo '<span class="badge badge-fair">Low Impact</span>';
                                        } else {
                                            echo '<span class="badge badge-poor">Minimal Impact</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Impact Assessments Found</h5>
                        <p class="text-muted">Submit your first impact assessment using the form above.</p>
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
        // Calculate overall impact score
        function calculateOverallImpact() {
            const fields = [
                'economic_impact', 'social_impact', 'environmental_impact', 'sustainability_score'
            ];
            
            let total = 0;
            let count = 0;
            
            fields.forEach(field => {
                const value = parseInt(document.querySelector(`[name="${field}"]`).value);
                if (!isNaN(value)) {
                    total += value;
                    count++;
                }
            });
            
            const overall = count > 0 ? Math.round(total / count) : 0;
            document.getElementById('overallImpact').textContent = 'Overall Impact Score: ' + overall + '/5';
            document.getElementById('overall_impact').value = overall;
        }

        // Update beneficiary count display
        function updateBeneficiaryCount() {
            const beneficiaries = document.querySelector('[name="community_beneficiaries"]').value;
            document.getElementById('beneficiaryCount').textContent = beneficiaries + ' people';
        }

        // Update employment count display
        function updateEmploymentCount() {
            const jobs = document.querySelector('[name="employment_generated"]').value;
            document.getElementById('employmentCount').textContent = jobs + ' jobs';
        }

        // Handle Report Type select change (redirect to other evaluation tools)
        function handleReportTypeChange(select) {
            const val = select.value;
            if (val === 'impact' || val === 'all') {
                // For 'impact' keep on this page and submit form to apply other filters
                if (val === 'impact') {
                    document.getElementById('impactFilters').submit();
                } else {
                    // 'all' redirects to consolidated reports
                    const form = document.getElementById('impactFilters');
                    const params = new URLSearchParams(new FormData(form));
                    window.location.href = '../evaluation/reports.php?' + params.toString();
                }
                return;
            }
            const map = {
                'progress': '../evaluation/progress.php',
                'compliance': '../evaluation/compliance.php',
                'quality': '../evaluation/quality.php'
            };
            const target = map[val] || '../evaluation/reports.php';
            const form = document.getElementById('impactFilters');
            const params = new URLSearchParams(new FormData(form));
            params.set('report_type', val);
            window.location.href = target + '?' + params.toString();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateOverallImpact();
            updateBeneficiaryCount();
            updateEmploymentCount();
            
            // Add event listeners for number inputs
            document.querySelector('[name="community_beneficiaries"]').addEventListener('input', updateBeneficiaryCount);
            document.querySelector('[name="employment_generated"]').addEventListener('input', updateEmploymentCount);
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
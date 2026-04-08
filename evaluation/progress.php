<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get assigned projects for progress review
$assigned_projects = getOfficerProjects($_SESSION['user_id']);

// Get progress statistics
$progress_stats = getProgressStatistics($_SESSION['user_id']);

// Handle progress review submission
if (isset($_POST['submit_progress_review'])) {
    $review_data = [
        'project_id' => $_POST['project_id'],
        'progress_score' => $_POST['progress_score'],
        'timeline_adherence' => $_POST['timeline_adherence'],
        'quality_rating' => $_POST['quality_rating'],
        'resource_utilization' => $_POST['resource_utilization'],
        'challenges' => $_POST['challenges'],
        'recommendations' => $_POST['recommendations'],
        'next_review_date' => $_POST['next_review_date'],
        'officer_id' => $_SESSION['user_id']
    ];
    
    if (submitProgressReview($review_data)) {
        $_SESSION['success'] = "Progress review submitted successfully!";
        redirect('progress.php');
    } else {
        $_SESSION['error'] = "Failed to submit progress review. Please try again.";
    }
}

// Handle automatic progress calculation
if (isset($_POST['calculate_progress'])) {
    $project_id = $_POST['project_id'];
    
    // Use the same automated progress calculation as beneficiary updates
    $ml_result = getRecommendedProgressPercentage($project_id);
    $calculated_progress = isset($ml_result['recommended']) ? intval($ml_result['recommended']) : 0;
    
    if ($calculated_progress !== false) {
        $_SESSION['calculated_progress'] = $calculated_progress;
        $_SESSION['calculated_project_id'] = $project_id;
        $_SESSION['calculated_breakdown'] = [
            'budget_utilization' => calculateBudgetUtilization($project_id),
            'photo_uploads' => calculatePhotoProgress($project_id),
            'achievements' => calculateAchievementProgress($project_id)
        ];
    } else {
        $_SESSION['error'] = "Unable to calculate automatic progress. Please check project data.";
    }
}

// Handle edit review
if (isset($_GET['edit_review'])) {
    $review_id = $_GET['edit_review'];
    $review_to_edit = getProgressReviewById($review_id, $_SESSION['user_id']);
}

// Handle update review
if (isset($_POST['update_progress_review'])) {
    $review_data = [
        'review_id' => $_POST['review_id'],
        'progress_score' => $_POST['progress_score'],
        'timeline_adherence' => $_POST['timeline_adherence'],
        'quality_rating' => $_POST['quality_rating'],
        'resource_utilization' => $_POST['resource_utilization'],
        'challenges' => $_POST['challenges'],
        'recommendations' => $_POST['recommendations'],
        'next_review_date' => $_POST['next_review_date']
    ];
    
    if (updateProgressReview($review_data, $_SESSION['user_id'])) {
        $_SESSION['success'] = "Progress review updated successfully!";
        redirect('progress.php');
    } else {
        $_SESSION['error'] = "Failed to update progress review. Please try again.";
    }
}

// Get recent progress reviews
$recent_reviews = getRecentProgressReviews($_SESSION['user_id'], 5);

$pageTitle = "Progress Review - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Progress Review dashboard for CDF Management System - Government of Zambia">
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

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .progress-section {
        margin: 1.25rem 0;
    }

    .progress {
        height: 10px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.08);
    }

    .progress-bar {
        border-radius: 10px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .review-form {
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

    .automation-section {
        background: #e8f4fd;
        border: 1px solid #b6d7f7;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .calculated-progress {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 1rem;
    }

    /* Evaluation Tools Grid */
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .tool-card {
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

    .tool-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
    }

    .tool-card.success { border-left-color: #28a745; }
    .tool-card.warning { border-left-color: #ffc107; }
    .tool-card.info { border-left-color: #17a2b8; }

    .tool-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--primary);
        transition: color 0.3s ease;
    }

    .tool-card:hover .tool-icon {
        color: white;
    }

    .tool-card.success .tool-icon { color: #28a745; }
    .tool-card.warning .tool-icon { color: #ffc107; }
    .tool-card.info .tool-icon { color: #17a2b8; }

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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
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
                    <h1>Progress Review Dashboard</h1>
                    <p class="lead">Monitor and evaluate beneficiary project progress - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Department: <strong><?php echo htmlspecialchars($userData['department'] ?? 'M&E Department'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="#review-form" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i>New Progress Review
                </a>
                <a href="reports.php" class="btn btn-outline-custom">
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
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $progress_stats['total_projects'] ?? 0; ?></div>
                        <div class="stat-title">Assigned Projects</div>
                        <div class="stat-subtitle">Under monitoring</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $progress_stats['avg_progress'] ?? 0; ?>%</div>
                        <div class="stat-title">Average Progress</div>
                        <div class="stat-subtitle">Across all projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $progress_stats['reviews_this_month'] ?? 0; ?></div>
                        <div class="stat-title">Reviews This Month</div>
                        <div class="stat-subtitle">Progress assessments</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $progress_stats['behind_schedule'] ?? 0; ?></div>
                        <div class="stat-title">Behind Schedule</div>
                        <div class="stat-subtitle">Require attention</div>
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
                    <div class="tool-card success" onclick="location.href='progress.php'">
                        <i class="fas fa-chart-line tool-icon"></i>
                        <h6>Progress Review</h6>
                        <p class="small mb-0">Review beneficiary progress reports</p>
                    </div>
                    <div class="tool-card warning" onclick="location.href='compliance.php'">
                        <i class="fas fa-check-double tool-icon"></i>
                        <h6>Compliance Check</h6>
                        <p class="small mb-0">Verify CDF guidelines compliance</p>
                    </div>
                    <div class="tool-card info" onclick="location.href='quality.php'">
                        <i class="fas fa-award tool-icon"></i>
                        <h6>Quality Assessment</h6>
                        <p class="small mb-0">Evaluate project quality standards</p>
                    </div>
                    <div class="tool-card" onclick="location.href='impact.php'">
                        <i class="fas fa-bullseye tool-icon"></i>
                        <h6>Impact Evaluation</h6>
                        <p class="small mb-0">Assess project impact metrics</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Progress Overview Chart -->
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Project Progress Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-tachometer-alt me-2"></i>Performance Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Review Form -->
        <div class="content-card" id="review-form">
            <div class="card-header">
                <h5>
                    <i class="fas fa-edit me-2"></i>
                    <?php echo isset($review_to_edit) ? 'Edit Progress Review' : 'New Progress Review'; ?>
                </h5>
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

                <!-- Automated Progress Calculation Section -->
                <?php if (!isset($review_to_edit)): ?>
                <div class="automation-section">
                    <h6><i class="fas fa-robot me-2"></i>Automated Progress Calculation</h6>
                    <p class="mb-3">Calculate progress automatically based on budget utilization, photo uploads, and achievements/milestones:</p>
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Select Project</label>
                            <select name="project_id" class="form-select" required>
                                <option value="">Choose a project...</option>
                                <?php foreach ($assigned_projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>" 
                                        <?php echo isset($_SESSION['calculated_project_id']) && $_SESSION['calculated_project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['title']); ?> - 
                                        <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unknown Beneficiary'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="calculate_progress" class="btn btn-primary-custom w-100">
                                <i class="fas fa-calculator me-2"></i>Calculate Progress
                            </button>
                        </div>
                    </form>

                    <?php if (isset($_SESSION['calculated_progress'])): ?>
                    <div class="calculated-progress mt-3">
                        <h6><i class="fas fa-check-circle me-2 text-success"></i>Calculated Progress</h6>
                        <p class="mb-3">Based on three factors: <strong><?php echo $_SESSION['calculated_progress']; ?>%</strong></p>
                        
                        <!-- Progress Breakdown -->
                        <?php if (isset($_SESSION['calculated_breakdown'])): ?>
                        <div class="breakdown-details mb-3">
                            <small class="d-block mb-2"><strong>Calculation Breakdown:</strong></small>
                            <ul class="small list-unstyled">
                                <li class="mb-1">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    <strong>Budget Utilization (40%):</strong> 
                                    <?php echo round($_SESSION['calculated_breakdown']['budget_utilization'], 1); ?>%
                                </li>
                                <li class="mb-1">
                                    <i class="fas fa-images me-1"></i>
                                    <strong>Photo Uploads (30%):</strong> 
                                    <?php echo round($_SESSION['calculated_breakdown']['photo_uploads'], 1); ?>%
                                </li>
                                <li>
                                    <i class="fas fa-trophy me-1"></i>
                                    <strong>Achievements/Milestones (30%):</strong> 
                                    <?php echo round($_SESSION['calculated_breakdown']['achievements'], 1); ?>%
                                </li>
                            </ul>
                            <small class="text-muted d-block mt-2">
                                Formula: (<?php echo round($_SESSION['calculated_breakdown']['budget_utilization'], 1); ?> + <?php echo round($_SESSION['calculated_breakdown']['photo_uploads'], 1); ?> + <?php echo round($_SESSION['calculated_breakdown']['achievements'], 1); ?>) ÷ 3 = <?php echo $_SESSION['calculated_progress']; ?>%
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <small class="text-muted">You can use this value or adjust it manually in the form below.</small>
                        <?php 
                            unset($_SESSION['calculated_progress']);
                            unset($_SESSION['calculated_project_id']);
                            unset($_SESSION['calculated_breakdown']);
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="review-form">
                    <?php if (isset($review_to_edit)): ?>
                        <input type="hidden" name="review_id" value="<?php echo $review_to_edit['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Select Project</label>
                                <select name="project_id" class="form-select" required 
                                    <?php echo isset($review_to_edit) ? 'disabled' : ''; ?>>
                                    <option value="">Choose a project...</option>
                                    <?php foreach ($assigned_projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>"
                                            <?php echo (isset($review_to_edit) && $review_to_edit['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['title']); ?> - 
                                            <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unknown Beneficiary'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($review_to_edit)): ?>
                                    <input type="hidden" name="project_id" value="<?php echo $review_to_edit['project_id']; ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Next Review Date</label>
                                <input type="date" name="next_review_date" class="form-control" required 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo isset($review_to_edit) ? $review_to_edit['next_review_date'] : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Progress Score (%)</label>
                                <input type="number" name="progress_score" class="form-control" 
                                       min="0" max="100" required placeholder="0-100"
                                       value="<?php echo isset($review_to_edit) ? $review_to_edit['progress_score'] : (isset($_SESSION['calculated_progress']) ? $_SESSION['calculated_progress'] : ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Timeline Adherence (%)</label>
                                <input type="number" name="timeline_adherence" class="form-control" 
                                       min="0" max="100" required placeholder="0-100"
                                       value="<?php echo isset($review_to_edit) ? $review_to_edit['timeline_adherence'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Quality Rating</label>
                                <select name="quality_rating" class="form-select" required>
                                    <option value="">Select rating...</option>
                                    <option value="excellent" <?php echo (isset($review_to_edit) && $review_to_edit['quality_rating'] == 'excellent') ? 'selected' : ''; ?>>Excellent</option>
                                    <option value="good" <?php echo (isset($review_to_edit) && $review_to_edit['quality_rating'] == 'good') ? 'selected' : ''; ?>>Good</option>
                                    <option value="fair" <?php echo (isset($review_to_edit) && $review_to_edit['quality_rating'] == 'fair') ? 'selected' : ''; ?>>Fair</option>
                                    <option value="poor" <?php echo (isset($review_to_edit) && $review_to_edit['quality_rating'] == 'poor') ? 'selected' : ''; ?>>Poor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Resource Utilization (%)</label>
                                <input type="number" name="resource_utilization" class="form-control" 
                                       min="0" max="100" required placeholder="0-100"
                                       value="<?php echo isset($review_to_edit) ? $review_to_edit['resource_utilization'] : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Challenges & Issues</label>
                        <textarea name="challenges" class="form-control" rows="3" 
                                  placeholder="Describe any challenges or issues encountered..."><?php echo isset($review_to_edit) ? $review_to_edit['challenges'] : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Recommendations</label>
                        <textarea name="recommendations" class="form-control" rows="3" 
                                  placeholder="Provide recommendations for improvement..."><?php echo isset($review_to_edit) ? $review_to_edit['recommendations'] : ''; ?></textarea>
                    </div>

                    <div class="text-end">
                        <?php if (isset($review_to_edit)): ?>
                            <button type="submit" name="update_progress_review" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Update Progress Review
                            </button>
                            <a href="progress.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        <?php else: ?>
                            <button type="submit" name="submit_progress_review" class="btn btn-primary-custom">
                                <i class="fas fa-paper-plane me-2"></i>Submit Progress Review
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Progress Reviews -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history me-2"></i>Recent Progress Reviews</h5>
                <a href="reports.php?type=progress" class="btn btn-primary-custom btn-sm">
                    <i class="fas fa-list me-2"></i>View All Reviews
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Review Date</th>
                                <th>Progress</th>
                                <th>Timeline</th>
                                <th>Quality</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_reviews) > 0): ?>
                                <?php foreach ($recent_reviews as $review): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($review['project_title'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($review['beneficiary_name'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($review['review_date'] ?? 'now')); ?></td>
                                    <td>
                                        <div class="progress-section">
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $review['progress_score'] ?? 0; ?>%"></div>
                                            </div>
                                            <small><?php echo $review['progress_score'] ?? 0; ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress-section">
                                            <div class="progress">
                                                <div class="progress-bar bg-info" style="width: <?php echo $review['timeline_adherence'] ?? 0; ?>%"></div>
                                            </div>
                                            <small><?php echo $review['timeline_adherence'] ?? 0; ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $review['quality_rating'] ?? 'fair'; ?>">
                                            <?php echo ucfirst($review['quality_rating'] ?? 'Fair'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $progress_score = $review['progress_score'] ?? 0;
                                        if ($progress_score >= 80) {
                                            echo '<span class="badge badge-excellent">On Track</span>';
                                        } elseif ($progress_score >= 60) {
                                            echo '<span class="badge badge-good">Moderate</span>';
                                        } elseif ($progress_score >= 40) {
                                            echo '<span class="badge badge-fair">Needs Attention</span>';
                                        } else {
                                            echo '<span class="badge badge-poor">Critical</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="review_details.php?id=<?php echo $review['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="progress.php?edit_review=<?php echo $review['id']; ?>" class="btn btn-outline-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_review.php?id=<?php echo $review['id']; ?>" class="btn btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this review?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Progress Reviews Found</h5>
                                        <p class="text-muted">Submit your first progress review using the form above.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Progress Chart
            const progressCtx = document.getElementById('progressChart').getContext('2d');
            const progressChart = new Chart(progressCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($assigned_projects, 'title')); ?>,
                    datasets: [{
                        label: 'Progress %',
                        data: <?php echo json_encode(array_column($assigned_projects, 'progress')); ?>,
                        backgroundColor: '#1a4e8a',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(performanceCtx, {
                type: 'radar',
                data: {
                    labels: ['Timeline', 'Budget', 'Quality', 'Compliance', 'Community Impact'],
                    datasets: [{
                        label: 'Average Performance',
                        data: [75, 82, 68, 90, 78],
                        backgroundColor: 'rgba(26, 78, 138, 0.2)',
                        borderColor: '#1a4e8a',
                        borderWidth: 2,
                        pointBackgroundColor: '#1a4e8a'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
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

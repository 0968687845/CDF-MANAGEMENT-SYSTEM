<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$projects = getOfficerProjects($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id']);

// Get assigned beneficiaries (you may need to implement this function)
$assignedBeneficiaries = getAssignedBeneficiaries($_SESSION['user_id']);

// Prepare projects with automated progress and media data
$enrichedProjects = [];
foreach ($projects as $project) {
    $automated_progress = getRecommendedProgressPercentage($project['id']);
    $progress_updates = getProjectProgress($project['id']);
    
    // Collect photos and achievements
    $all_photos = [];
    $all_achievements = [];
    foreach ($progress_updates as $update) {
        // Collect achievements
        if (!empty($update['achievements'])) {
            $achievements = is_array($update['achievements']) ? $update['achievements'] : json_decode($update['achievements'], true);
            if (is_array($achievements)) {
                $all_achievements = array_merge($all_achievements, $achievements);
            }
        }
        
        // Collect photos
        $photo_dir = '../uploads/progress/' . $project['id'] . '/' . $update['id'] . '/';
        if (is_dir($photo_dir)) {
            $photos = array_filter(scandir($photo_dir), function($file) {
                return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            });
            foreach ($photos as $photo) {
                $all_photos[] = $photo_dir . $photo;
            }
        }
    }
    
    $enrichedProjects[] = array_merge($project, [
        'automated_progress' => $automated_progress['recommended'],
        'photos' => $all_photos,
        'achievements' => $all_achievements
    ]);
}

$pageTitle = "Assigned Projects - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Assigned projects for M&E Officer - CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            /* Color System - Enhanced Contrast */
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            
            /* Neutral Colors - Improved Readability */
            --light: #f8f9fa;
            --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            
            /* Semantic Colors - Enhanced Visibility */
            --success: #28a745;
            --success-light: #d4edda;
            --success-dark: #1e7e34;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --warning-dark: #e0a800;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --danger-dark: #c82333;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --info-dark: #138496;
            --white: #ffffff;
            --black: #000000;
            
            /* Design Tokens */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.18);
            --shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.22);
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            
            --border-radius-sm: 8px;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-xl: 20px;
            
            /* Typography Scale - Enhanced Readability */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            --text-4xl: 2.25rem;
            --text-5xl: 3rem;
        }

        body {
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
            color: var(--gray-900);
            line-height: 1.7;
            font-weight: 400;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Enhanced Navigation */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow-lg);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: var(--border-radius-sm);
            position: relative;
        }

        .nav-link:hover, 
        .nav-link:focus,
        .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        /* Enhanced Dashboard Header */
        .dashboard-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: 3rem 0 2rem;
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
            background: linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            color: var(--dark);
            font-size: 2rem;
            font-weight: 700;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .profile-info h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .profile-info .lead {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .btn-primary-custom {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            background: var(--secondary-gradient);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Enhanced Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .content-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
        }

        .card-header {
            background: var(--light-gradient);
            border-bottom: 4px solid var(--primary);
            padding: 1.5rem;
            position: relative;
        }

        .card-header h5 {
            color: var(--primary-dark);
            font-weight: 800;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Project Cards */
        .project-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            overflow: hidden;
            border-left: 4px solid var(--primary);
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
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
            height: 10px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.08);
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Badge Colors */
        .badge-completed { 
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: var(--white);
        }
        .badge-in-progress { 
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
            color: var(--dark);
        }
        .badge-delayed { 
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: var(--white);
        }
        .badge-planning { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--white);
        }
        .badge-reviewed { 
            background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
            color: var(--white);
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
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        /* Stats Cards */
        .stats-container {
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
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
        }

        .stat-title {
            font-size: 1.1rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .action-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem 1.5rem;
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .action-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .action-card:hover .action-icon {
            transform: scale(1.1);
            color: var(--primary-dark);
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
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
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                text-align: center;
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
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
                            </a></li>
                            <li><a class="dropdown-item" href="../analytics/dashboard.php">
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
                    <h1>Assigned Projects</h1>
                    <p class="lead">Monitor and evaluate your assigned CDF projects</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong> | Department: <strong><?php echo htmlspecialchars($userData['department'] ?? 'M&E Department'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="../site-visits/schedule.php" class="btn btn-primary-custom">
                    <i class="fas fa-map-marker-alt me-2"></i>Schedule Site Visit
                </a>
                <a href="../evaluation/reports.php" class="btn btn-outline-custom">
                    <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                </a>
                <a href="../communication/messages.php" class="btn btn-outline-custom">
                    <i class="fas fa-comments me-2"></i>Contact Beneficiaries
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
                        <div class="stat-number"><?php echo count($projects); ?></div>
                        <div class="stat-title">Total Projects</div>
                        <div class="stat-subtitle">Assigned to you</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($projects, function($p) { return $p['status'] === 'in-progress'; })); ?></div>
                        <div class="stat-title">In Progress</div>
                        <div class="stat-subtitle">Active projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($projects, function($p) { return $p['status'] === 'completed'; })); ?></div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-subtitle">Finished projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($assignedBeneficiaries); ?></div>
                        <div class="stat-title">Beneficiaries</div>
                        <div class="stat-subtitle">Under monitoring</div>
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
                    <div class="action-card" onclick="location.href='../site-visits/schedule.php'">
                        <i class="fas fa-map-marker-alt action-icon"></i>
                        <h6>Schedule Site Visit</h6>
                        <p class="small text-muted mb-0">Plan physical inspections</p>
                    </div>
                    <div class="action-card" onclick="location.href='../evaluation/reports.php'">
                        <i class="fas fa-clipboard-check action-icon"></i>
                        <h6>Evaluation Reports</h6>
                        <p class="small text-muted mb-0">Generate assessment reports</p>
                    </div>
                    <div class="action-card" onclick="location.href='../communication/messages.php'">
                        <i class="fas fa-comments action-icon"></i>
                        <h6>Send Messages</h6>
                        <p class="small text-muted mb-0">Communicate with beneficiaries</p>
                    </div>
                    <div class="action-card" onclick="location.href='../analytics/dashboard.php'">
                        <i class="fas fa-chart-bar action-icon"></i>
                        <h6>View Analytics</h6>
                        <p class="small text-muted mb-0">Performance metrics</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects List -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-project-diagram me-2"></i>All Assigned Projects</h5>
                <div class="btn-group">
                    <button class="btn btn-outline-primary btn-sm" onclick="filterProjects('all')">
                        <i class="fas fa-list me-1"></i>All
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="filterProjects('in-progress')">
                        <i class="fas fa-sync-alt me-1"></i>In Progress
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="filterProjects('completed')">
                        <i class="fas fa-check me-1"></i>Completed
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($projects) > 0): ?>
                    <div class="row" id="projects-container">
                        <?php foreach ($enrichedProjects as $project): ?>
                        <div class="col-lg-6 mb-4 project-item" data-status="<?php echo $project['status']; ?>">
                            <div class="project-card card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                        <span class="badge badge-<?php echo $project['status']; ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text text-muted mb-3">
                                        <?php echo htmlspecialchars(substr($project['description'], 0, 150)); ?>
                                        <?php if (strlen($project['description']) > 150): ?>...<?php endif; ?>
                                    </p>
                                    
                                    <div class="project-info mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?>
                                                </small>
                                            </div>
                                            <div class="col-6 text-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-money-bill me-1"></i>
                                                    ZMW <?php echo number_format($project['budget'], 0); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-section mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small><strong>Automated Progress</strong></small>
                                            <small><strong><?php echo $project['automated_progress']; ?>%</strong></small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?php echo $project['status']; ?>" 
                                                 style="width: <?php echo $project['automated_progress']; ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Photos Gallery -->
                                    <?php if (!empty($project['photos'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-images me-1" style="color: #1a4e8a;"></i>
                                            Photos (<?php echo count($project['photos']); ?>)
                                        </small>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php foreach (array_slice($project['photos'], 0, 3) as $photo): ?>
                                                <a href="<?php echo htmlspecialchars($photo); ?>" data-lightbox="project-<?php echo $project['id']; ?>" class="photo-thumb">
                                                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Progress Photo" 
                                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; cursor: pointer;">
                                                </a>
                                            <?php endforeach; ?>
                                            <?php if (count($project['photos']) > 3): ?>
                                                <div style="width: 60px; height: 60px; border-radius: 6px; background: #f0f0f0; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center;">
                                                    <small class="text-muted">+<?php echo count($project['photos']) - 3; ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Achievements -->
                                    <?php if (!empty($project['achievements'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-star me-1" style="color: #e9b949;"></i>
                                            Milestones (<?php echo count($project['achievements']); ?>)
                                        </small>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach (array_slice($project['achievements'], 0, 2) as $achievement): ?>
                                                <span class="badge bg-success" style="font-size: 0.75rem;">
                                                    <i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars(substr($achievement, 0, 15)); ?><?php echo strlen($achievement) > 15 ? '...' : ''; ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($project['achievements']) > 2): ?>
                                                <span class="badge bg-info" style="font-size: 0.75rem;">
                                                    +<?php echo count($project['achievements']) - 2; ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="project-meta mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($project['location'] ?? 'N/A'); ?>
                                                </small>
                                            </div>
                                            <div class="col-6 text-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M j, Y', strtotime($project['start_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="btn-group w-100">
                                        <a href="../projects/review.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-search me-1"></i>Review
                                        </a>
                                        <a href="../site-visits/schedule.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-map-marker-alt me-1"></i>Site Visit
                                        </a>
                                        <a href="../evaluation/reports.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-file-alt me-1"></i>Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Projects Assigned</h4>
                        <p class="text-muted mb-4">You haven't been assigned any projects for monitoring and evaluation.</p>
                        <a href="../projects/request.php" class="btn btn-primary-custom">
                            <i class="fas fa-envelope me-2"></i>Request Project Assignment
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

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script>
        // Project filtering function
        function filterProjects(status) {
            const projects = document.querySelectorAll('.project-item');
            projects.forEach(project => {
                if (status === 'all' || project.getAttribute('data-status') === status) {
                    project.style.display = 'block';
                } else {
                    project.style.display = 'none';
                }
            });
        }

        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();
        
        // Lightbox configuration
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'showImageNumberLabel': true,
            'albumLabel': 'Image %1 of %2'
        });
    </script>
</body>
</html>
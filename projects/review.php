<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Get project ID from URL
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    redirect('index.php');
}

$userData = getUserData();
$project = getProjectById($project_id);
$notifications = getNotifications($_SESSION['user_id']);

// Check if officer is assigned to this project
if (!$project || $project['officer_id'] != $_SESSION['user_id']) {
    redirect('index.php');
}

// Get project progress updates
$progress_updates = getProjectProgress($project_id);

// Get project expenses
$project_expenses = getProjectExpenses($project_id);
$total_expenses = getTotalProjectExpenses($project_id);

// Get automatically calculated progress based on three-factor average
$calculated_progress = getRecommendedProgressPercentage($project_id);
$automated_progress = $calculated_progress['recommended'];

// Collect all photos and achievements from beneficiary updates
$all_photos = [];
$all_achievements = [];
$update_photos_map = []; // Map to track photos by update ID for display

foreach ($progress_updates as $update) {
    // Collect achievements/milestones
    if (!empty($update['achievements'])) {
        $achievements = is_array($update['achievements']) ? $update['achievements'] : json_decode($update['achievements'], true);
        if (is_array($achievements)) {
            $all_achievements = array_merge($all_achievements, $achievements);
        }
    }
    
    // Collect photos from database JSON field (primary source)
    $update_photos = [];
    if (!empty($update['photos'])) {
        $photos_data = is_array($update['photos']) ? $update['photos'] : json_decode($update['photos'], true);
        if (is_array($photos_data)) {
            foreach ($photos_data as $photo_path) {
                $all_photos[] = [
                    'path' => '../' . $photo_path,
                    'filename' => basename($photo_path),
                    'update_date' => $update['created_at'],
                    'update_id' => $update['id'],
                    'description' => $update['description']
                ];
                $update_photos[] = [
                    'path' => '../' . $photo_path,
                    'filename' => basename($photo_path),
                    'update_date' => $update['created_at']
                ];
            }
        }
    }
    
    // Also collect photos from directory (backup/legacy support)
    $photo_dir = '../uploads/progress/' . $project_id . '/' . $update['id'] . '/';
    if (is_dir($photo_dir)) {
        $dir_photos = array_filter(scandir($photo_dir), function($file) {
            return $file !== '.' && $file !== '..' && 
                   in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });
        
        foreach ($dir_photos as $photo) {
            $photo_path = $photo_dir . $photo;
            // Check if this photo is not already in the list
            $already_exists = false;
            foreach ($all_photos as $existing) {
                if ($existing['filename'] === $photo) {
                    $already_exists = true;
                    break;
                }
            }
            
            if (!$already_exists) {
                $all_photos[] = [
                    'path' => $photo_path,
                    'filename' => $photo,
                    'update_date' => $update['created_at'],
                    'update_id' => $update['id'],
                    'description' => $update['description']
                ];
                $update_photos[] = [
                    'path' => $photo_path,
                    'filename' => $photo,
                    'update_date' => $update['created_at']
                ];
            }
        }
    }
    
    // Store photos for this update
    if (!empty($update_photos)) {
        $update_photos_map[$update['id']] = $update_photos;
    }
}

$pageTitle = "Project Review - " . htmlspecialchars($project['title']) . " - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Project review and evaluation - CDF Management System - Government of Zambia">
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

        /* Project Overview */
        .project-overview {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .project-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .project-title {
            flex: 1;
        }

        .project-title h2 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .meta-item {
            text-align: center;
            padding: 1.5rem;
            background: var(--light);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
        }

        .meta-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .meta-label {
            font-size: 0.9rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Progress Section */
        .progress-section {
            margin: 2rem 0;
        }

        .progress {
            height: 12px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.08);
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            background: var(--primary);
            border-radius: 50%;
            border: 3px solid var(--white);
            box-shadow: var(--shadow-sm);
        }

        .timeline-content {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary);
        }

        /* Evaluation Form */
        .evaluation-form {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .form-section:last-child {
            border-bottom: none;
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
            
            .project-meta {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
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
                    <h1>Project Review</h1>
                    <p class="lead"><?php echo htmlspecialchars($project['title']); ?></p>
                    <p class="mb-0">Beneficiary: <strong><?php echo htmlspecialchars($project['beneficiary_name']); ?></strong> | Location: <strong><?php echo htmlspecialchars($project['location']); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="../site-visits/schedule.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary-custom">
                    <i class="fas fa-map-marker-alt me-2"></i>Schedule Site Visit
                </a>
                <a href="../evaluation/reports.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-custom">
                    <i class="fas fa-clipboard-check me-2"></i>Generate Report
                </a>
                <a href="../communication/messages.php?recipient=<?php echo $project['beneficiary_id']; ?>" class="btn btn-outline-custom">
                    <i class="fas fa-comments me-2"></i>Contact Beneficiary
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Project Overview -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Project Overview</h5>
            </div>
            <div class="card-body">
                <div class="project-meta">
                    <div class="meta-item">
                        <div class="meta-value"><?php echo $automated_progress; ?>%</div>
                        <div class="meta-label">Automated Progress</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-value">ZMW <?php echo number_format($project['budget'], 0); ?></div>
                        <div class="meta-label">Total Budget</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-value">ZMW <?php echo number_format($total_expenses, 0); ?></div>
                        <div class="meta-label">Total Expenses</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-value"><?php echo count($progress_updates); ?></div>
                        <div class="meta-label">Progress Updates</div>
                    </div>
                </div>

                <div class="progress-section">
                    <div class="d-flex justify-content-between mb-2">
                        <h6>Automated Project Progress</h6>
                        <span class="badge badge-<?php echo $project['status']; ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-<?php echo $project['status']; ?>" 
                             style="width: <?php echo $automated_progress; ?>%">
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Based on budget utilization, photo uploads, and achievements/milestones</small>
                </div>
                
                <!-- Milestones & Achievements Section -->
                <?php if (!empty($all_achievements)): ?>
                <div class="mt-4">
                    <h6 class="mb-3">
                        <i class="fas fa-flag me-2" style="color: #e9b949;"></i>Recorded Milestones & Achievements (<?php echo count($all_achievements); ?>)
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($all_achievements as $achievement): ?>
                            <span class="badge bg-success" style="font-size: 0.9rem; padding: 0.6rem 0.9rem; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);">
                                <i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars($achievement); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Progress Photos Section -->
                <?php if (!empty($all_photos)): ?>
                <div class="mt-4">
                    <h6 class="mb-3">
                        <i class="fas fa-images me-2" style="color: #1a4e8a;"></i>Beneficiary Progress Photos (<?php echo count($all_photos); ?>)
                    </h6>
                    <div class="photo-gallery d-flex gap-3 flex-wrap">
                        <?php foreach ($all_photos as $photo): ?>
                            <div style="position: relative;">
                                <a href="<?php echo htmlspecialchars($photo['path']); ?>" 
                                   data-lightbox="all-photos" 
                                   data-title="Progress Photo - <?php echo date('M j, Y g:i A', strtotime($photo['update_date'])); ?>">
                                    <img src="<?php echo htmlspecialchars($photo['path']); ?>" 
                                         alt="Progress Photo" 
                                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 3px solid #1a4e8a; transition: all 0.3s; box-shadow: 0 2px 8px rgba(26, 78, 138, 0.2);"
                                         onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(26, 78, 138, 0.4)';" 
                                         onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(26, 78, 138, 0.2)';"
                                         title="<?php echo !empty($photo['description']) ? htmlspecialchars(substr($photo['description'], 0, 50)) . '...' : 'Progress Photo'; ?>">
                                </a>
                                <small style="position: absolute; bottom: 2px; left: 2px; background: rgba(26, 78, 138, 0.8); color: white; padding: 2px 5px; border-radius: 4px; font-size: 11px;">
                                    <?php echo date('M j', strtotime($photo['update_date'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar me-2"></i>Timeline</h6>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <strong>Start Date</strong>
                                    <p class="mb-0"><?php echo date('F j, Y', strtotime($project['start_date'])); ?></p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <strong>End Date</strong>
                                    <p class="mb-0"><?php echo date('F j, Y', strtotime($project['end_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Location Details</h6>
                        <div class="p-3 bg-light rounded">
                            <p class="mb-2"><strong>Constituency:</strong> <?php echo htmlspecialchars($project['constituency']); ?></p>
                            <p class="mb-2"><strong>Location:</strong> <?php echo htmlspecialchars($project['location']); ?></p>
                            <p class="mb-0"><strong>Category:</strong> <?php echo htmlspecialchars($project['category']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Updates -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-sync-alt me-2"></i>Progress Updates</h5>
                <span class="badge bg-primary"><?php echo count($progress_updates); ?> updates</span>
            </div>
            <div class="card-body">
                <?php if (count($progress_updates) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($progress_updates as $update): ?>
                            <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">Progress: <?php echo $update['progress_percentage']; ?>%</h6>
                                    <small class="text-muted"><?php echo time_elapsed_string($update['created_at']); ?></small>
                                </div>
                                <p class="mb-2"><?php echo htmlspecialchars($update['description']); ?></p>
                                
                                <!-- Achievements/Milestones for this update -->
                                <?php if (!empty($update['achievements'])): 
                                    $achievements_list = is_array($update['achievements']) ? $update['achievements'] : json_decode($update['achievements'], true);
                                    if (is_array($achievements_list) && count($achievements_list) > 0):
                                ?>
                                    <div class="mb-3 p-2 bg-light rounded">
                                        <strong class="d-block mb-2">
                                            <i class="fas fa-flag me-2" style="color: #e9b949;"></i>Milestones Achieved (<?php echo count($achievements_list); ?>):
                                        </strong>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($achievements_list as $achievement): ?>
                                                <span class="badge bg-success" style="font-size: 0.85rem;">
                                                    <i class="fas fa-check me-1"></i><?php echo htmlspecialchars($achievement); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Progress Photos Gallery -->
                                <?php 
                                    $update_photos = [];
                                    
                                    // First try to get from JSON stored photos
                                    if (!empty($update['photos'])) {
                                        $photos_from_db = is_array($update['photos']) ? $update['photos'] : json_decode($update['photos'], true);
                                        if (is_array($photos_from_db)) {
                                            foreach ($photos_from_db as $photo_path) {
                                                if (file_exists('../' . $photo_path)) {
                                                    $update_photos[] = [
                                                        'path' => '../' . $photo_path,
                                                        'filename' => basename($photo_path)
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Also check directory for photos (backup)
                                    $photo_dir = '../uploads/progress/' . $project_id . '/' . $update['id'] . '/';
                                    if (is_dir($photo_dir)) {
                                        $dir_photos = array_filter(scandir($photo_dir), function($file) {
                                            return $file !== '.' && $file !== '..' && 
                                                   in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        });
                                        
                                        foreach ($dir_photos as $photo) {
                                            $photo_path = $photo_dir . $photo;
                                            // Check if not already in list
                                            $already_exists = false;
                                            foreach ($update_photos as $existing) {
                                                if ($existing['filename'] === $photo) {
                                                    $already_exists = true;
                                                    break;
                                                }
                                            }
                                            if (!$already_exists) {
                                                $update_photos[] = [
                                                    'path' => $photo_path,
                                                    'filename' => $photo
                                                ];
                                            }
                                        }
                                    }
                                ?>
                                <?php if (!empty($update_photos)): ?>
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong class="d-block mb-2">
                                                    <i class="fas fa-images me-2" style="color: #1a4e8a;"></i>Photos (<?php echo count($update_photos); ?>)
                                                </strong>
                                                <div class="photo-gallery d-flex gap-2 flex-wrap">
                                                    <?php foreach ($update_photos as $photo): ?>
                                                        <?php if (file_exists($photo['path'])): ?>
                                                            <a href="<?php echo htmlspecialchars($photo['path']); ?>" 
                                                               data-lightbox="beneficiary-<?php echo $update['id']; ?>" 
                                                               data-title="Progress Photo - <?php echo date('M j, Y', strtotime($update['created_at'])); ?>"
                                                               class="photo-thumbnail">
                                                                <img src="<?php echo htmlspecialchars($photo['path']); ?>" 
                                                                     alt="Progress Photo" 
                                                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #1a4e8a; transition: all 0.3s; box-shadow: 0 2px 6px rgba(26, 78, 138, 0.2);"
                                                                     onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 10px rgba(26, 78, 138, 0.4)'" 
                                                                     onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(26, 78, 138, 0.2)'">
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($update['challenges'])): ?>
                                    <div class="alert alert-warning p-2 mb-2">
                                        <strong>Challenges:</strong> <?php echo htmlspecialchars($update['challenges']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($update['next_steps'])): ?>
                                    <div class="alert alert-info p-2 mb-0">
                                        <strong>Next Steps:</strong> <?php echo htmlspecialchars($update['next_steps']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-sync-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Progress Updates</h5>
                        <p class="text-muted">No progress updates have been submitted for this project yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expense Tracking -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-receipt me-2"></i>Expense Tracking</h5>
                <span class="badge bg-success">ZMW <?php echo number_format($total_expenses, 0); ?> spent</span>
            </div>
            <div class="card-body">
                <?php if (count($project_expenses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Vendor</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project_expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($expense['category']); ?></span></td>
                                    <td class="fw-bold">ZMW <?php echo number_format($expense['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($expense['vendor'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                            if (!empty($expense['receipt_path']) && file_exists('../' . $expense['receipt_path'])): 
                                                $ext = strtolower(pathinfo($expense['receipt_path'], PATHINFO_EXTENSION));
                                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): 
                                        ?>
                                            <a href="<?php echo '../' . htmlspecialchars($expense['receipt_path']); ?>" 
                                               data-lightbox="receipts" 
                                               data-title="Receipt - <?php echo htmlspecialchars($expense['description']); ?>">
                                                <img src="<?php echo '../' . htmlspecialchars($expense['receipt_path']); ?>" 
                                                     alt="Receipt" 
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #ddd;">
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo '../' . htmlspecialchars($expense['receipt_path']); ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               download>
                                                <i class="fas fa-download me-1"></i>View
                                            </a>
                                        <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Expenses Recorded</h5>
                        <p class="text-muted">No expenses have been recorded for this project yet.</p>
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
        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        
        // Lightbox configuration
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'showImageNumberLabel': true,
            'albumLabel': 'Image %1 of %2'
        });
        updateServerTime();
    </script>
</body>
</html>
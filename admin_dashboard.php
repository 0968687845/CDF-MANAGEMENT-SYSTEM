<?php
require_once 'functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

$userData = getUserData();
$users = getUsersByRole('beneficiary');
$officers = getUsersByRole('officer');
$projects = getAllProjects();
// Calculate project progress the same way as beneficiary_dashboard.php
if (!empty($projects)) {
    foreach ($projects as &$project) {
        $ml_result = getRecommendedProgressPercentage($project['id'] ?? 0);
        $automated_progress = isset($ml_result['recommended']) ? intval($ml_result['recommended']) : 0;

        if ($automated_progress == 0 && isset($project['progress'])) {
            $automated_progress = intval($project['progress']);
        }

        $project['progress'] = $automated_progress;
        $project['total_expenses'] = getTotalProjectExpenses($project['id'] ?? 0);
        $project['budget_utilization'] = ($project['budget'] > 0) ?
            round(($project['total_expenses'] / $project['budget']) * 100, 1) : 0;
    }
    unset($project);
}
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
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include_once 'includes/global_theme.php'; ?>
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
    font-weight: 900;
    color: var(--primary);
    margin-bottom: 0.75rem;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(26, 78, 138, 0.15);
}

.stat-title {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 0.5rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.stat-subtitle {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
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

/* Admin Tools */
.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.tool-card {
    background: var(--white);
    border: none;
    border-radius: var(--border-radius);
    padding: 2rem 1.5rem;
    text-align: center;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-left: 5px solid var(--primary);
    position: relative;
    overflow: hidden;
}

.tool-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(26, 78, 138, 0.05), transparent);
    transition: var(--transition);
}

.tool-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.tool-card:hover::before {
    left: 100%;
}

.tool-card.success { border-left-color: var(--success); }
.tool-card.warning { border-left-color: var(--warning); }
.tool-card.info { border-left-color: var(--info); }
.tool-card.danger { border-left-color: var(--danger); }

.tool-card:hover {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.tool-icon {
    font-size: 2.5rem;
    margin-bottom: 1.25rem;
    color: var(--primary);
    transition: var(--transition);
}

.tool-card:hover .tool-icon {
    color: white;
    transform: scale(1.1);
}

.tool-card.success .tool-icon { color: var(--success); }
.tool-card.warning .tool-icon { color: var(--warning); }
.tool-card.info .tool-icon { color: var(--info); }
.tool-card.danger .tool-icon { color: var(--danger); }

.tool-card h6 {
    font-weight: 700;
    margin-bottom: 0.75rem;
    font-size: 1.1rem;
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

.project-status-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 2;
}

.progress-section {
    margin: 1.25rem 0;
}

.progress {
    height: 10px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Activity Items */
.activity-item {
    padding: 1.5rem;
    border-left: 5px solid transparent;
    transition: var(--transition);
    border-radius: var(--border-radius);
    margin-bottom: 0.75rem;
    background: rgba(26, 78, 138, 0.02);
    box-shadow: var(--shadow-sm);
}

.activity-item:hover {
    background: rgba(26, 78, 138, 0.05);
    border-left-color: var(--primary);
    transform: translateX(8px);
    box-shadow: var(--shadow);
}

.activity-item h6 {
    color: #333;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.activity-item p {
    color: #666;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.activity-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.25rem;
    flex-shrink: 0;
    font-size: 1.25rem;
    transition: var(--transition);
}

.activity-item:hover .activity-icon {
    transform: scale(1.1);
}

.activity-icon.primary { background: rgba(26, 78, 138, 0.1); color: var(--primary); }
.activity-icon.success { background: rgba(40, 167, 69, 0.1); color: var(--success); }
.activity-icon.warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
.activity-icon.info { background: rgba(23, 162, 184, 0.1); color: var(--info); }

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
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    color: white;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.25);
}
.badge-in-progress { 
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    color: #1a1a1a;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.25);
}
.badge-delayed { 
    background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
    color: white;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.25);
}
.badge-planning { 
    background: linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%);
    color: white;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 8px rgba(26, 78, 138, 0.25);
}
.badge-assigned { 
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.25);
}
.badge-warning { 
    background: linear-gradient(135deg, #fd7e14 0%, #e56c00 100%);
    color: white;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 8px rgba(253, 126, 20, 0.25);
}

.badge {
    font-weight: 700;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-size: 0.85rem;
    box-shadow: var(--shadow-sm);
    display: inline-block;
    letter-spacing: 0.3px;
    transition: var(--transition);
    border: none;
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

/* Chart Container */
.chart-container {
    position: relative;
    height: 280px;
    width: 100%;
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

/* Table Improvements */
.table {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.table th {
    border-top: none;
    font-weight: 800;
    color: var(--primary);
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.25rem;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.table td {
    padding: 1.25rem;
    vertical-align: middle;
    border-color: rgba(0, 0, 0, 0.05);
    color: #333;
    font-weight: 500;
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
    
    .tools-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .stat-number {
        font-size: 2.25rem;
    }
}

@media (max-width: 576px) {
    .tools-grid {
        grid-template-columns: 1fr;
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
.content-card,
.tool-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Hover Effects */
.hover-lift {
    transition: var(--transition);
}

.hover-lift:hover {
    transform: translateY(-5px);
}

/* Text Utilities */
.text-gradient {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin/users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="admin/projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="admin/assignments.php">
                                <i class="fas fa-user-tie me-2"></i>Officer Assignments
                            </a></li>
                            <li><a class="dropdown-item" href="admin/reports.php">
                                <i class="fas fa-chart-bar me-2"></i>System Reports
                            </a></li>
                            <li><a class="dropdown-item" href="admin/settings.php">
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
                                    <a class="dropdown-item" href="admin/notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="admin/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="admin/notifications.php">No new notifications</a></li>
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
                            <li><a class="dropdown-item" href="admin/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="admin/settings.php">
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
                <a href="admin/users.php" class="btn btn-primary-custom">
                    <i class="fas fa-users me-2"></i>Manage Users
                </a>
                <a href="admin/projects.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>Manage Projects
                </a>
                <a href="admin/assignments.php" class="btn btn-outline-custom">
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
                    <div class="tool-card success" onclick="location.href='admin/users.php'">
                        <i class="fas fa-users tool-icon"></i>
                        <h6>User Management</h6>
                        <p class="small mb-0">Manage system users and permissions</p>
                    </div>
                    <div class="tool-card warning" onclick="location.href='admin/assignments.php'">
                        <i class="fas fa-user-tie tool-icon"></i>
                        <h6>Officer Assignments</h6>
                        <p class="small mb-0">Assign officers to projects</p>
                    </div>
                    <div class="tool-card info" onclick="location.href='admin/projects.php'">
                        <i class="fas fa-project-diagram tool-icon"></i>
                        <h6>Project Management</h6>
                        <p class="small mb-0">Create and manage projects</p>
                    </div>
                    <div class="tool-card danger" onclick="location.href='admin/reports.php'">
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
                        <a href="admin/projects.php" class="btn btn-primary-custom btn-sm">
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
                                                    <a href="admin/projects.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="admin/assignments.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-info">
                                                        <i class="fas fa-user-tie"></i>
                                                    </a>
                                                    <a href="admin/projects.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-outline-warning">
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
                                <a href="admin/projects.php" class="btn btn-primary-custom">
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
                    <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3">
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
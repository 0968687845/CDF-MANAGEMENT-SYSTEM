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

// Get officer's assigned projects and site visits
$projects = getOfficerProjects($_SESSION['user_id']);

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if Leaflet map is available (no specific API key needed - uses OpenStreetMap)
$hasValidApiKey = true; // Leaflet with OpenStreetMap is always available

// Check if IP Geolocation API is configured
$hasIPGeolocation = false;
if (function_exists('hasIPGeolocationApiKey')) {
    $hasIPGeolocation = hasIPGeolocationApiKey();
} else {
    // Fallback check
    $configFile = '../config.php';
    if (file_exists($configFile)) {
        include_once $configFile;
        $hasIPGeolocation = defined('IP_GEOLOCATION_API_KEY') && 
                           !empty(IP_GEOLOCATION_API_KEY) && 
                           IP_GEOLOCATION_API_KEY !== '0ae9de6e6f5a4dc6b4d8b300515229cd';
    }
}

// Get site visits with project information
$site_visits = [];
try {
    $query = "SELECT 
                sv.*,
                p.title as project_title,
                p.beneficiary_name,
                p.location as project_location,
                u.first_name,
                u.last_name
              FROM site_visits sv
              LEFT JOIN projects p ON sv.project_id = p.id
              LEFT JOIN users u ON p.beneficiary_id = u.id
              WHERE sv.officer_id = :officer_id
              ORDER BY sv.visit_date DESC, sv.visit_time DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':officer_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $site_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching site visits: " . $e->getMessage());
    $site_visits = [];
}

$pageTitle = "Site Visits Map - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Site Visits Map for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet Map Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <!-- Leaflet Routing Machine -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
    <script src="https://cdn.jsdelivr.net/npm/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
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
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --border-radius: 12px;
            --border-radius-sm: 8px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: var(--shadow-lg);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
        }

        .navbar-brand img {
            filter: brightness(0) invert(1);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: var(--border-radius-sm);
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 3rem 0 2rem;
            margin-top: 76px;
            position: relative;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
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
            border: 6px solid rgba(255, 255, 255, 0.3);
        }

        .profile-info h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            padding: 0.9rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
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
            font-size: 1.3rem;
        }

        /* Map Styles */
        #mainMap {
            width: 100%;
            height: 600px;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow);
            border: 2px solid var(--gray-light);
        }

        .map-controls {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
        }

        .map-controls .btn {
            font-size: 0.9rem;
            padding: 0.7rem 1.2rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
        }

        .map-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--primary);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        /* Visit List */
        .visit-item {
            padding: 1.2rem;
            border-left: 4px solid transparent;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem;
            background: var(--white);
            box-shadow: var(--shadow);
            cursor: pointer;
        }

        .visit-item:hover {
            background: rgba(13, 110, 253, 0.03);
            border-left-color: var(--primary);
            transform: translateX(8px);
        }

        .visit-item.active {
            border-left-color: var(--secondary);
            background: rgba(233, 185, 73, 0.05);
        }

        .visit-status {
            font-size: 0.75rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: 700;
        }

        .status-scheduled { background-color: var(--info); color: white; }
        .status-completed { background-color: var(--success); color: white; }
        .status-cancelled { background-color: var(--danger); color: white; }
        .status-in-progress { background-color: var(--warning); color: var(--dark); }

        /* Map Placeholder */
        .map-placeholder {
            background: linear-gradient(135deg, var(--gray-light) 0%, #dee2e6 100%);
            border-radius: var(--border-radius-sm);
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 3rem;
            color: var(--gray);
            border: 2px dashed #adb5bd;
        }

        /* Legend */
        .map-legend {
            background: var(--white);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* Geolocation Status */
        .geolocation-status {
            position: fixed;
            top: 120px;
            right: 25px;
            z-index: 1000;
            background: var(--info);
            color: white;
            padding: 12px 18px;
            border-radius: var(--border-radius-sm);
            font-size: 0.85rem;
            box-shadow: var(--shadow-lg);
            display: none;
        }

        /* Responsive Design */
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
                font-size: 1.8rem;
            }
            
            .action-buttons {
                justify-content: center;
            }

            #mainMap {
                height: 400px;
            }

            .geolocation-status {
                top: 100px;
                right: 15px;
            }
        }

        @media (max-width: 576px) {
            .btn-outline-custom {
                width: 100%;
                margin-bottom: 0.8rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 3px solid transparent;
            border-top: 3px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
    </style>
</head>
<body>
    <!-- Geolocation Status -->
    <div class="geolocation-status" id="geolocationStatus">
        <i class="fas fa-spinner fa-spin me-2"></i>
        <span id="geolocationMessage">Detecting location...</span>
    </div>

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
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="schedule.php">
                                <i class="fas fa-plus-circle me-2"></i>Schedule Visit
                            </a></li>
                            <li><a class="dropdown-item active" href="map.php">
                                <i class="fas fa-map me-2"></i>View Map
                            </a></li>
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
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
                                <li><a class="dropdown-item text-center" href="../communication/notifications.php">View All</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="../communication/notifications.php">No notifications</a></li>
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
                                        <div class="profile-avatar-small me-3" style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 800; font-size: 1.1rem;">
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
                                <i class="fas fa-cog me-2"></i>Settings
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
                    <h1>Site Visits Map</h1>
                    <p class="lead">View all your scheduled site visits on an interactive map</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-outline-custom">
                    <i class="fas fa-list me-2"></i>View List
                </a>
                <a href="schedule.php" class="btn btn-outline-custom">
                    <i class="fas fa-plus-circle me-2"></i>Schedule Visit
                </a>
                <a href="../projects/index.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>View Projects
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- IP Geolocation Status -->
        <?php if (!$hasIPGeolocation): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>IP Geolocation Limited:</strong>
                Some location detection features may not work optimally without IP geolocation API configuration.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Map Section -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-map me-2"></i>Site Visits Map</h5>
                        <div class="map-controls">
                            <button type="button" class="btn btn-sm btn-outline-info" id="detectLocationBtn" title="Detect my current location" <?php echo !$hasIPGeolocation ? 'disabled' : ''; ?>>
                                <i class="fas fa-crosshairs me-1"></i>My Location
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="zoomInBtn" title="Zoom in">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="zoomOutBtn" title="Zoom out">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="resetViewBtn" title="Reset to Zambia">
                                <i class="fas fa-globe-africa"></i> Reset View
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="mainMap"></div>
                        <div class="map-stats">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary mb-1"><?php echo count($site_visits); ?></h4>
                                    <small class="text-muted">Total Visits</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success mb-1"><?php echo count(array_filter($site_visits, fn($v) => $v['status'] === 'completed')); ?></h4>
                                    <small class="text-muted">Completed</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-warning mb-1"><?php echo count(array_filter($site_visits, fn($v) => $v['status'] === 'scheduled')); ?></h4>
                                    <small class="text-muted">Scheduled</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-info mb-1"><?php echo count(array_filter($site_visits, fn($v) => $v['latitude'] && $v['longitude'])); ?></h4>
                                    <small class="text-muted">With Location</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visits List -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Site Visits</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($site_visits) > 0): ?>
                            <div class="mb-3 search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" id="searchVisits" placeholder="Search visits...">
                            </div>
                            <div class="visits-list" style="max-height: 500px; overflow-y: auto;">
                                <?php foreach ($site_visits as $visit): ?>
                                    <div class="visit-item" data-visit-id="<?php echo $visit['id']; ?>" 
                                         data-lat="<?php echo $visit['latitude'] ?? ''; ?>" 
                                         data-lng="<?php echo $visit['longitude'] ?? ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($visit['project_title']); ?></h6>
                                            <span class="visit-status status-<?php echo $visit['status']; ?>">
                                                <?php echo ucfirst($visit['status']); ?>
                                            </span>
                                        </div>
                                        <p class="small text-muted mb-1">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($visit['beneficiary_name'] ?? $visit['first_name'] . ' ' . $visit['last_name']); ?>
                                        </p>
                                        <p class="small text-muted mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M j, Y', strtotime($visit['visit_date'])); ?> at 
                                            <?php echo date('g:i A', strtotime($visit['visit_time'])); ?>
                                        </p>
                                        <p class="small text-muted mb-0">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($visit['location']); ?>
                                        </p>
                                        <?php if ($visit['latitude'] && $visit['longitude']): ?>
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Location mapped
                                            </small>
                                        <?php else: ?>
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>No coordinates
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Site Visits</h5>
                                <p class="text-muted">You haven't scheduled any site visits yet.</p>
                                <a href="schedule.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Schedule First Visit
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Legend -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Map Legend</h5>
                    </div>
                    <div class="card-body">
                        <div class="map-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #28a745;"></div>
                                <span>Completed Visits</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #17a2b8;"></div>
                                <span>Scheduled Visits</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #ffc107;"></div>
                                <span>In Progress</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #dc3545;"></div>
                                <span>Cancelled Visits</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer" style="background: var(--white); border-radius: var(--border-radius); box-shadow: var(--shadow); padding: 2rem; margin-top: 3rem; border-top: 4px solid var(--primary);">
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
        // Leaflet map functionality
        let map;
        let markers = [];
        let currentMarker = null;
        const hasIPGeolocation = <?php echo $hasIPGeolocation ? 'true' : 'false'; ?>;
        const siteVisits = <?php echo json_encode($site_visits); ?>;

        // Initialize Leaflet map
        function initMap() {
            try {
                // Default center for Zambia
                const defaultCenter = [-15.3875, 28.3228];
                
                // Create the map with OpenStreetMap tiles
                map = L.map('mainMap').setView(defaultCenter, 6);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    minZoom: 3
                }).addTo(map);

                // Add site visit markers
                addSiteVisitMarkers();

                // Setup event listeners
                setupEventListeners();

                console.log('Leaflet map initialized successfully');

            } catch(error) {
                console.error('Error initializing map:', error);
            }
        }

        function addSiteVisitMarkers() {
            if (!map) return;

            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            const bounds = L.latLngBounds();
            let hasValidLocations = false;

            siteVisits.forEach(visit => {
                if (visit.latitude && visit.longitude) {
                    const lat = parseFloat(visit.latitude);
                    const lng = parseFloat(visit.longitude);
                    const position = [lat, lng];

                    // Determine marker color based on status
                    let markerColor;
                    let statusText = '';
                    switch(visit.status) {
                        case 'completed':
                            markerColor = '#28a745'; // Green
                            statusText = 'Completed';
                            break;
                        case 'in-progress':
                            markerColor = '#ffc107'; // Yellow
                            statusText = 'In Progress';
                            break;
                        case 'cancelled':
                            markerColor = '#dc3545'; // Red
                            statusText = 'Cancelled';
                            break;
                        default:
                            markerColor = '#17a2b8'; // Blue (scheduled)
                            statusText = 'Scheduled';
                    }

                    // Create custom marker icon
                    const markerIcon = L.divIcon({
                        html: `<div style="background-color: ${markerColor}; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;"><i class="fas fa-map-marker-alt" style="font-size: 16px;"></i></div>`,
                        iconSize: [30, 30],
                        className: 'custom-div-icon'
                    });

                    const marker = L.marker(position, { icon: markerIcon })
                        .bindPopup(createPopupContent(visit))
                        .addTo(map);

                    // Add click event to highlight visit
                    marker.on('click', function() {
                        highlightVisitItem(visit.id);
                    });

                    markers.push(marker);
                    bounds.extend(position);
                    hasValidLocations = true;
                }
            });

            // Fit map to show all markers if we have valid locations
            if (hasValidLocations && markers.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
                
                // Don't zoom too close if there's only one marker
                if (markers.length === 1 && map.getZoom() > 15) {
                    map.setZoom(15);
                }
            }
        }

        function createPopupContent(visit) {
            const visitDate = new Date(visit.visit_date);
            const formattedDate = visitDate.toLocaleDateString();
            
            return `
                <div style="max-width: 300px;">
                    <h6 style="color: #1a4e8a; margin-bottom: 8px; font-weight: 700;">${visit.project_title}</h6>
                    <div style="font-size: 12px; color: #6c757d; line-height: 1.5;">
                        <strong>Beneficiary:</strong> ${visit.beneficiary_name || visit.first_name + ' ' + visit.last_name}<br>
                        <strong>Date:</strong> ${formattedDate} at ${visit.visit_time}<br>
                        <strong>Location:</strong> ${visit.location}<br>
                        <strong>Coordinates:</strong> ${visit.latitude}, ${visit.longitude}<br>
                        <strong>Status:</strong> <span style="display: inline-block; padding: 2px 6px; border-radius: 3px; background-color: ${getStatusColor(visit.status)}; color: white; font-weight: 600; font-size: 11px;">${visit.status.charAt(0).toUpperCase() + visit.status.slice(1)}</span>
                    </div>
                </div>
            `;
        }

        function getStatusColor(status) {
            switch(status) {
                case 'completed': return '#28a745';
                case 'scheduled': return '#17a2b8';
                case 'in-progress': return '#ffc107';
                case 'cancelled': return '#dc3545';
                default: return '#6c757d';
            }
        }

        function highlightVisitItem(visitId) {
            // Remove active class from all items
            document.querySelectorAll('.visit-item').forEach(item => {
                item.classList.remove('active');
            });

            // Add active class to selected item
            const selectedItem = document.querySelector(`.visit-item[data-visit-id="${visitId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('active');
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function setupEventListeners() {
            // Visit item click events
            document.querySelectorAll('.visit-item').forEach(item => {
                item.addEventListener('click', function() {
                    const visitId = this.getAttribute('data-visit-id');
                    const lat = parseFloat(this.getAttribute('data-lat'));
                    const lng = parseFloat(this.getAttribute('data-lng'));

                    if (!isNaN(lat) && !isNaN(lng) && map) {
                        const position = [lat, lng];
                        map.setView(position, 15);
                        
                        // Find and click the corresponding marker
                        markers.forEach(marker => {
                            const markerLat = marker.getLatLng().lat;
                            const markerLng = marker.getLatLng().lng;
                            if (Math.abs(markerLat - lat) < 0.0001 && Math.abs(markerLng - lng) < 0.0001) {
                                marker.openPopup();
                                highlightVisitItem(visitId);
                            }
                        });
                    }
                });
            });

            // Map control buttons
            document.getElementById('zoomInBtn').addEventListener('click', () => {
                if (map) map.zoomIn();
            });

            document.getElementById('zoomOutBtn').addEventListener('click', () => {
                if (map) map.zoomOut();
            });

            document.getElementById('resetViewBtn').addEventListener('click', () => {
                if (map) {
                    const defaultCenter = [-15.3875, 28.3228];
                    map.setView(defaultCenter, 6);
                }
            });

            // Search functionality
            document.getElementById('searchVisits').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.visit-item').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Geolocation button
            const detectLocationBtn = document.getElementById('detectLocationBtn');
            if (detectLocationBtn) {
                detectLocationBtn.addEventListener('click', detectOfficerLocation);
            }
        }

        // Detect officer's location using IP Geolocation API
        function detectOfficerLocation() {
            if (!hasIPGeolocation) {
                alert('IP Geolocation API not configured. Please configure the API key first.');
                return;
            }

            const btn = document.getElementById('detectLocationBtn');
            const statusElement = document.getElementById('geolocationStatus');
            const messageElement = document.getElementById('geolocationMessage');
            
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Detecting...';
            
            statusElement.style.display = 'block';
            messageElement.textContent = 'Detecting your location via IP...';

            fetch('../api/geolocate_officer.php')
                .then(response => {
                    if (!response.ok) throw new Error('API error: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.latitude && data.longitude) {
                        const location = [parseFloat(data.latitude), parseFloat(data.longitude)];
                        
                        // Remove previous location marker if exists
                        if (currentMarker) {
                            map.removeLayer(currentMarker);
                        }

                        // Add officer location marker
                        currentMarker = L.circleMarker(location, {
                            radius: 8,
                            fillColor: '#1a4e8a',
                            color: '#ffffff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.8
                        }).addTo(map);

                        currentMarker.bindPopup(`<strong>Your Location</strong><br>${data.city}, ${data.state}<br>(${data.latitude}, ${data.longitude})`).openPopup();

                        // Update map
                        if (map) {
                            map.setView(location, 14);
                        }

                        // Show success message
                        messageElement.innerHTML = `<i class="fas fa-check-circle me-2"></i>Location: ${data.city}, ${data.state} (${data.country})`;
                        
                        btn.innerHTML = originalText;
                        btn.disabled = false;

                        // Hide status after 5 seconds
                        setTimeout(() => {
                            statusElement.style.display = 'none';
                        }, 5000);

                    } else {
                        throw new Error(data.message || 'Could not detect location');
                    }
                })
                .catch(error => {
                    console.error('Location detection error:', error);
                    messageElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>Error: ${error.message}`;
                    btn.innerHTML = originalText;
                    btn.disabled = false;

                    // Hide status after 5 seconds
                    setTimeout(() => {
                        statusElement.style.display = 'none';
                    }, 5000);
                });
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            // Update server time
            function updateServerTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { hour12: false });
                document.getElementById('serverTime').textContent = timeString;
            }
            
            setInterval(updateServerTime, 1000);
            updateServerTime();
        });
    </script>
</body>
</html>
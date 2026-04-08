<?php
require_once '../functions.php';
requireRole('officer');

// Add the required functions if they don't exist
if (!function_exists('saveComplianceCheck')) {
    function saveComplianceCheck($data) {
        global $pdo;
        
        try {
            $sql = "INSERT INTO compliance_checks (
                project_id, budget_compliance, timeline_compliance, documentation_compliance,
                quality_standards, community_engagement, environmental_compliance,
                procurement_compliance, safety_standards, overall_compliance,
                findings, recommendations, next_audit_date, officer_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                $data['project_id'],
                $data['budget_compliance'],
                $data['timeline_compliance'],
                $data['documentation_compliance'],
                $data['quality_standards'],
                $data['community_engagement'],
                $data['environmental_compliance'],
                $data['procurement_compliance'],
                $data['safety_standards'],
                $data['overall_compliance'],
                $data['findings'],
                $data['recommendations'],
                $data['next_audit_date'],
                $data['officer_id']
            ]);
        } catch (PDOException $e) {
            error_log("Compliance check save error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getComplianceChecks')) {
    function getComplianceChecks($officer_id = null) {
        global $pdo;
        
        try {
            $sql = "SELECT cc.*, p.title as project_title, p.beneficiary_name, 
                           u.first_name, u.last_name
                    FROM compliance_checks cc
                    JOIN projects p ON cc.project_id = p.id
                    JOIN users u ON cc.officer_id = u.id
                    WHERE 1=1";
            
            $params = [];
            
            if ($officer_id) {
                $sql .= " AND cc.officer_id = ?";
                $params[] = $officer_id;
            }
            
            $sql .= " ORDER BY cc.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Compliance checks fetch error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getOfficerProjects')) {
    function getOfficerProjects($officer_id) {
        global $pdo;
        
        try {
            $sql = "SELECT p.*, u.first_name, u.last_name 
                    FROM projects p 
                    LEFT JOIN users u ON p.beneficiary_id = u.id 
                    WHERE p.officer_id = ? OR p.officer_id IS NULL
                    ORDER BY p.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$officer_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Officer projects fetch error: " . $e->getMessage());
            return [];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect(basename($_SERVER['PHP_SELF']));
        exit;
    }
    $complianceData = [
        'project_id' => $_POST['project_id'],
        'budget_compliance' => $_POST['budget_compliance'],
        'timeline_compliance' => $_POST['timeline_compliance'],
        'documentation_compliance' => $_POST['documentation_compliance'],
        'quality_standards' => $_POST['quality_standards'],
        'community_engagement' => $_POST['community_engagement'],
        'environmental_compliance' => $_POST['environmental_compliance'],
        'procurement_compliance' => $_POST['procurement_compliance'],
        'safety_standards' => $_POST['safety_standards'],
        'findings' => $_POST['findings'],
        'recommendations' => $_POST['recommendations'],
        'next_audit_date' => $_POST['next_audit_date'],
        'officer_id' => $_SESSION['user_id']
    ];
    
    // Calculate overall compliance score
    $complianceScores = [
        $complianceData['budget_compliance'],
        $complianceData['timeline_compliance'],
        $complianceData['documentation_compliance'],
        $complianceData['quality_standards'],
        $complianceData['community_engagement'],
        $complianceData['environmental_compliance'],
        $complianceData['procurement_compliance'],
        $complianceData['safety_standards']
    ];
    
    $complianceData['overall_compliance'] = (array_sum($complianceScores) / count($complianceScores)) * 20;
    
    // Check if this is an update
    if (isset($_POST['check_id']) && !empty($_POST['check_id'])) {
        $complianceData['check_id'] = $_POST['check_id'];
        if (updateComplianceCheck($complianceData, $_SESSION['user_id'])) {
            $_SESSION['success_message'] = "Compliance check updated successfully!";
            redirect('compliance.php');
        } else {
            $_SESSION['error_message'] = "Failed to update compliance check. Please try again.";
        }
    } else {
        if (saveComplianceCheck($complianceData)) {
            $_SESSION['success_message'] = "Compliance check submitted successfully!";
            redirect('compliance.php');
        } else {
            $_SESSION['error_message'] = "Failed to submit compliance check. Please try again.";
        }
    }
}

// Handle edit compliance check
$compliance_to_edit = null;
if (isset($_GET['edit_check'])) {
    $check_id = $_GET['edit_check'];
    $compliance_to_edit = getComplianceCheckById($check_id, $_SESSION['user_id']);
    if (!$compliance_to_edit) {
        $_SESSION['error_message'] = "Compliance evaluation not found.";
        redirect('compliance.php');
    }
}

$userData = getUserData();
$projects = getOfficerProjects($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id']);
$complianceChecks = getComplianceChecks($_SESSION['user_id']);

// Calculate statistics for charts
$complianceStats = [
    'excellent' => count(array_filter($complianceChecks, function($check) { return $check['overall_compliance'] >= 80; })),
    'good' => count(array_filter($complianceChecks, function($check) { return $check['overall_compliance'] >= 60 && $check['overall_compliance'] < 80; })),
    'fair' => count(array_filter($complianceChecks, function($check) { return $check['overall_compliance'] >= 40 && $check['overall_compliance'] < 60; })),
    'poor' => count(array_filter($complianceChecks, function($check) { return $check['overall_compliance'] < 40; }))
];

$metricAverages = [];
if (count($complianceChecks) > 0) {
    $metrics = ['budget_compliance', 'timeline_compliance', 'documentation_compliance', 'quality_standards', 
                'community_engagement', 'environmental_compliance', 'procurement_compliance', 'safety_standards'];
    foreach ($metrics as $metric) {
        $metricAverages[$metric] = array_sum(array_column($complianceChecks, $metric)) / count($complianceChecks);
    }
}

$pageTitle = "Compliance Check - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Compliance check for CDF projects - Government of Zambia">
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
            --border-radius-lg: 16px;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            line-height: 1.7;
            min-height: 100vh;
        }

        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: 8px;
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .dashboard-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: 3rem 0 2.5rem;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
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
            font-weight: 800;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .content-card {
            background: var(--white);
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
            border-bottom: 4px solid var(--primary);
            padding: 1.5rem;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .compliance-score {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin: 1rem 0;
        }

        .score-excellent { color: var(--success); }
        .score-good { color: #20c997; }
        .score-fair { color: var(--warning); }
        .score-poor { color: var(--danger); }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .star {
            color: #dee2e6;
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .star.active {
            color: var(--warning);
        }

        .star:hover {
            transform: scale(1.2);
        }

        .compliance-indicator {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .compliance-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .btn-primary-custom {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            padding: 1rem 2rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn-primary-custom:hover {
            background: var(--secondary-gradient);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: var(--dark);
        }

        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
        }

        .table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: var(--primary);
            font-weight: 700;
            border-bottom: 3px solid var(--primary);
        }

        .compliance-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .badge-excellent { background: var(--success); color: white; }
        .badge-good { background: #20c997; color: white; }
        .badge-fair { background: var(--warning); color: var(--dark); }
        .badge-poor { background: var(--danger); color: white; }

        .metric-score {
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }

        /* Evaluation Tools Grid */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
            box-shadow: var(--shadow);
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: var(--transition);
        }

        .tool-card:hover::before {
            left: 100%;
        }

        .tool-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .tool-card.success { border-left-color: var(--success); }
        .tool-card.warning { border-left-color: var(--warning); }
        .tool-card.info { border-left-color: var(--info); }
        .tool-card.danger { border-left-color: var(--danger); }

        .tool-icon {
            font-size: 2.5rem;
            margin-bottom: 1.25rem;
            color: var(--primary);
            transition: var(--transition);
        }

        .tool-card:hover .tool-icon {
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

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.7rem;
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
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .tools-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
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
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Home
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
                            <li><a class="dropdown-item active" href="compliance.php">
                                <i class="fas fa-check-double me-2"></i>Compliance Check
                            </a></li>
                            <li><a class="dropdown-item" href="reports.php">
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
                            <li><a class="dropdown-item text-danger" href="../?logout=true">
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
                    <h1>Compliance Check</h1>
                    <p class="lead">Verify CDF guidelines compliance for assigned projects</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Evaluation Tools -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-tools me-2"></i>Evaluation Tools</h5>
            </div>
            <div class="card-body">
                <div class="tools-grid">
                    <div class="tool-card success" onclick="location.href='compliance.php'">
                        <i class="fas fa-check-double tool-icon"></i>
                        <h6>Compliance Check</h6>
                        <p class="small mb-0">Verify CDF guidelines compliance</p>
                    </div>
                    <div class="tool-card warning" onclick="location.href='progress.php'">
                        <i class="fas fa-chart-line tool-icon"></i>
                        <h6>Progress Review</h6>
                        <p class="small mb-0">Review beneficiary progress reports</p>
                    </div>
                    <div class="tool-card info" onclick="location.href='quality.php'">
                        <i class="fas fa-award tool-icon"></i>
                        <h6>Quality Assessment</h6>
                        <p class="small mb-0">Evaluate project quality standards</p>
                    </div>
                    <div class="tool-card danger" onclick="location.href='impact.php'">
                        <i class="fas fa-bullseye tool-icon"></i>
                        <h6>Impact Evaluation</h6>
                        <p class="small mb-0">Assess project impact metrics</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Charts and Analytics -->
            <div class="col-lg-4">
                <!-- Compliance Distribution Chart -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Compliance Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="complianceDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Metric Performance Chart -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Metric Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="metricPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Compliance Checks Summary Table -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-table me-2"></i>Recent Compliance Checks Summary</h5>
                        <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#complianceModal">
                            <i class="fas fa-plus-circle me-2"></i>New Compliance Check
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (count($complianceChecks) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>Beneficiary</th>
                                            <th>Overall Score</th>
                                            <th>Budget Compliance</th>
                                            <th>Timeline Compliance</th>
                                            <th>Quality Standards</th>
                                            <th>Safety Standards</th>
                                            <th>Next Audit</th>
                                            <th>Date Conducted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($complianceChecks as $check): 
                                            // Calculate badge class based on overall score
                                            $overallScore = $check['overall_compliance'];
                                            $badgeClass = 'badge-poor';
                                            if ($overallScore >= 80) $badgeClass = 'badge-excellent';
                                            elseif ($overallScore >= 60) $badgeClass = 'badge-good';
                                            elseif ($overallScore >= 40) $badgeClass = 'badge-fair';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($check['project_title'] ?? 'Unknown Project'); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($check['beneficiary_name'] ?? 'N/A'); ?>
                                            </td>
                                            <td>
                                                <span class="compliance-badge <?php echo $badgeClass; ?>">
                                                    <?php echo number_format($overallScore, 1); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="compliance-indicator flex-grow-1">
                                                        <div class="compliance-fill bg-success" 
                                                             style="width: <?php echo ($check['budget_compliance'] / 5) * 100; ?>%"></div>
                                                    </div>
                                                    <span class="metric-score"><?php echo $check['budget_compliance']; ?>/5</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="compliance-indicator flex-grow-1">
                                                        <div class="compliance-fill bg-info" 
                                                             style="width: <?php echo ($check['timeline_compliance'] / 5) * 100; ?>%"></div>
                                                    </div>
                                                    <span class="metric-score"><?php echo $check['timeline_compliance']; ?>/5</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="compliance-indicator flex-grow-1">
                                                        <div class="compliance-fill bg-warning" 
                                                             style="width: <?php echo ($check['quality_standards'] / 5) * 100; ?>%"></div>
                                                    </div>
                                                    <span class="metric-score"><?php echo $check['quality_standards']; ?>/5</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="compliance-indicator flex-grow-1">
                                                        <div class="compliance-fill bg-danger" 
                                                             style="width: <?php echo ($check['safety_standards'] / 5) * 100; ?>%"></div>
                                                    </div>
                                                    <span class="metric-score"><?php echo $check['safety_standards']; ?>/5</span>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $check['next_audit_date'] ? date('M j, Y', strtotime($check['next_audit_date'])) : 'Not set'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($check['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary view-details" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#detailsModal"
                                                            data-check='<?php echo htmlspecialchars(json_encode($check), ENT_QUOTES, 'UTF-8'); ?>'>
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="compliance.php?edit_check=<?php echo $check['id']; ?>" class="btn btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Statistics -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-primary mb-1">
                                            <?php 
                                            $averageScore = count($complianceChecks) > 0 ? 
                                                number_format(array_sum(array_column($complianceChecks, 'overall_compliance')) / count($complianceChecks), 1) : 0;
                                            echo $averageScore; ?>%
                                        </h4>
                                        <small class="text-muted">Average Compliance</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-success mb-1"><?php echo count($complianceChecks); ?></h4>
                                        <small class="text-muted">Total Checks</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-warning mb-1">
                                            <?php 
                                            $excellentChecks = array_filter($complianceChecks, function($check) {
                                                return $check['overall_compliance'] >= 80;
                                            });
                                            echo count($excellentChecks);
                                            ?>
                                        </h4>
                                        <small class="text-muted">Excellent Ratings</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h4 class="text-danger mb-1">
                                            <?php 
                                            $poorChecks = array_filter($complianceChecks, function($check) {
                                                return $check['overall_compliance'] < 40;
                                            });
                                            echo count($poorChecks);
                                            ?>
                                        </h4>
                                        <small class="text-muted">Needs Improvement</small>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-check fa-4x text-muted mb-4"></i>
                                <h4 class="text-muted">No Compliance Checks Conducted Yet</h4>
                                <p class="text-muted mb-4">Start by conducting your first compliance check to monitor project adherence to CDF guidelines.</p>
                                <button class="btn btn-primary-custom btn-lg" data-bs-toggle="modal" data-bs-target="#complianceModal">
                                    <i class="fas fa-plus-circle me-2"></i>Conduct First Compliance Check
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compliance Check Modal -->
    <div class="modal fade" id="complianceModal" tabindex="-1" aria-labelledby="complianceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="complianceModalLabel">
                        <i class="fas fa-clipboard-check me-2"></i><?php echo isset($compliance_to_edit) ? 'Edit Compliance Check' : 'New Compliance Check'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="complianceForm">
                    <?php if (isset($compliance_to_edit)): ?>
                        <input type="hidden" name="check_id" value="<?php echo $compliance_to_edit['id']; ?>">
                    <?php endif; ?>
                    <div class="modal-body">
                        <?= csrfField() ?>
                        <div class="form-section">
                            <h6 class="mb-3"><i class="fas fa-project-diagram me-2"></i>Project Information</h6>
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Select Project</label>
                                <select class="form-select" id="project_id" name="project_id" required <?php echo isset($compliance_to_edit) ? 'disabled' : ''; ?>>
                                    <option value="">Choose a project...</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>" <?php echo (isset($compliance_to_edit) && $compliance_to_edit['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['title']); ?> - 
                                            <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($compliance_to_edit)): ?>
                                    <input type="hidden" name="project_id" value="<?php echo $compliance_to_edit['project_id']; ?>">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Compliance Metrics</h6>
                            
                            <?php 
                            $metrics = [
                                'budget_compliance' => ['Budget Compliance', 'Assessment of adherence to allocated budget', 'bg-success'],
                                'timeline_compliance' => ['Timeline Compliance', 'Adherence to project schedule and deadlines', 'bg-info'],
                                'documentation_compliance' => ['Documentation Compliance', 'Completeness and accuracy of project documentation', 'bg-primary'],
                                'quality_standards' => ['Quality Standards', 'Adherence to quality standards and specifications', 'bg-warning'],
                                'community_engagement' => ['Community Engagement', 'Level of community involvement and satisfaction', 'bg-success'],
                                'environmental_compliance' => ['Environmental Compliance', 'Adherence to environmental regulations', 'bg-info'],
                                'procurement_compliance' => ['Procurement Compliance', 'Compliance with procurement procedures', 'bg-primary'],
                                'safety_standards' => ['Safety Standards', 'Implementation of safety measures and protocols', 'bg-danger']
                            ];
                            
                            foreach ($metrics as $key => $metric): ?>
                            <div class="mb-4">
                                <label class="form-label"><?php echo $metric[0]; ?></label>
                                <div class="rating-stars" data-metric="<?php echo $key; ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star" data-value="<?php echo $i; ?>" <?php echo (isset($compliance_to_edit) && $compliance_to_edit[$key] == $i) ? 'class="active"' : ''; ?>>
                                            <i class="fas fa-star"></i>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value="<?php echo isset($compliance_to_edit) ? $compliance_to_edit[$key] : 0; ?>" required>
                                <div class="compliance-indicator">
                                    <div class="compliance-fill <?php echo $metric[2]; ?>" 
                                         id="<?php echo $key; ?>_indicator" style="width: <?php echo isset($compliance_to_edit) ? ($compliance_to_edit[$key] / 5) * 100 : 0; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $metric[1]; ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-section">
                            <h6 class="mb-3"><i class="fas fa-file-alt me-2"></i>Assessment Details</h6>
                            
                            <div class="mb-3">
                                <label for="findings" class="form-label">Key Findings</label>
                                <textarea class="form-control" id="findings" name="findings" rows="3" placeholder="Describe the main findings from the compliance check..." required><?php echo isset($compliance_to_edit) ? htmlspecialchars($compliance_to_edit['findings'] ?? '') : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="recommendations" class="form-label">Recommendations</label>
                                <textarea class="form-control" id="recommendations" name="recommendations" rows="3" placeholder="Provide recommendations for improvement..." required><?php echo isset($compliance_to_edit) ? htmlspecialchars($compliance_to_edit['recommendations'] ?? '') : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="next_audit_date" class="form-label">Next Audit Date</label>
                                <input type="date" class="form-control" id="next_audit_date" name="next_audit_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($compliance_to_edit) ? $compliance_to_edit['next_audit_date'] : ''; ?>">
                            </div>
                        </div>

                        <!-- Overall Score Display -->
                        <div class="form-section text-center">
                            <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Overall Compliance Score</h6>
                            <div id="overallScore" class="compliance-score score-<?php echo isset($compliance_to_edit) ? ($compliance_to_edit['overall_compliance'] >= 60 ? 'good' : ($compliance_to_edit['overall_compliance'] >= 40 ? 'fair' : 'poor')) : 'poor'; ?>"><?php echo isset($compliance_to_edit) ? number_format($compliance_to_edit['overall_compliance'], 1) : 0; ?>%</div>
                            <div class="compliance-indicator mx-auto" style="max-width: 300px;">
                                <div class="compliance-fill bg-success" id="overall_indicator" style="width: <?php echo isset($compliance_to_edit) ? $compliance_to_edit['overall_compliance'] : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-2"></i><?php echo isset($compliance_to_edit) ? 'Update Compliance Report' : 'Submit Compliance Report'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Compliance Check Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Details will be loaded here by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            // Compliance Distribution Chart
            const distributionCtx = document.getElementById('complianceDistributionChart').getContext('2d');
            const distributionChart = new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent (80-100%)', 'Good (60-79%)', 'Fair (40-59%)', 'Poor (<40%)'],
                    datasets: [{
                        data: [
                            <?php echo $complianceStats['excellent']; ?>,
                            <?php echo $complianceStats['good']; ?>,
                            <?php echo $complianceStats['fair']; ?>,
                            <?php echo $complianceStats['poor']; ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#20c997',
                            '#ffc107',
                            '#dc3545'
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

            // Metric Performance Chart
            const metricCtx = document.getElementById('metricPerformanceChart').getContext('2d');
            const metricChart = new Chart(metricCtx, {
                type: 'bar',
                data: {
                    labels: ['Budget', 'Timeline', 'Documentation', 'Quality', 'Community', 'Environmental', 'Procurement', 'Safety'],
                    datasets: [{
                        label: 'Average Score (out of 5)',
                        data: [
                            <?php echo number_format($metricAverages['budget_compliance'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['timeline_compliance'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['documentation_compliance'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['quality_standards'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['community_engagement'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['environmental_compliance'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['procurement_compliance'] ?? 0, 1); ?>,
                            <?php echo number_format($metricAverages['safety_standards'] ?? 0, 1); ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#17a2b8',
                            '#007bff',
                            '#ffc107',
                            '#28a745',
                            '#17a2b8',
                            '#007bff',
                            '#dc3545'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });

        // Star rating functionality
        document.querySelectorAll('.rating-stars').forEach(starsContainer => {
            const metric = starsContainer.getAttribute('data-metric');
            const hiddenInput = document.getElementById(metric);
            const indicator = document.getElementById(metric + '_indicator');
            
            starsContainer.querySelectorAll('.star').forEach(star => {
                star.addEventListener('click', () => {
                    const value = parseInt(star.getAttribute('data-value'));
                    
                    // Update stars
                    starsContainer.querySelectorAll('.star').forEach(s => {
                        s.classList.remove('active');
                        if (parseInt(s.getAttribute('data-value')) <= value) {
                            s.classList.add('active');
                        }
                    });
                    
                    // Update hidden input
                    hiddenInput.value = value;
                    
                    // Update indicator
                    const percentage = (value / 5) * 100;
                    indicator.style.width = percentage + '%';
                    
                    // Update overall score
                    updateOverallScore();
                });
            });
        });

        function updateOverallScore() {
            const metrics = [
                'budget_compliance',
                'timeline_compliance',
                'documentation_compliance',
                'quality_standards',
                'community_engagement',
                'environmental_compliance',
                'procurement_compliance',
                'safety_standards'
            ];
            
            let total = 0;
            let count = 0;
            
            metrics.forEach(metric => {
                const value = parseInt(document.getElementById(metric).value);
                if (!isNaN(value)) {
                    total += value;
                    count++;
                }
            });
            
            if (count > 0) {
                const average = (total / count) * 20; // Convert to percentage
                const overallScore = document.getElementById('overallScore');
                const overallIndicator = document.getElementById('overall_indicator');
                
                overallScore.textContent = Math.round(average) + '%';
                overallIndicator.style.width = average + '%';
                
                // Update score color
                overallScore.className = 'compliance-score ';
                if (average >= 80) {
                    overallScore.classList.add('score-excellent');
                } else if (average >= 60) {
                    overallScore.classList.add('score-good');
                } else if (average >= 40) {
                    overallScore.classList.add('score-fair');
                } else {
                    overallScore.classList.add('score-poor');
                }
            }
        }

        // View details functionality
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const check = JSON.parse(this.getAttribute('data-check'));
                const detailsContent = document.getElementById('detailsContent');
                
                const detailsHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Project Information</h6>
                            <p><strong>Project:</strong> ${check.project_title || 'Unknown'}</p>
                            <p><strong>Beneficiary:</strong> ${check.beneficiary_name || 'N/A'}</p>
                            <p><strong>Officer:</strong> ${check.first_name} ${check.last_name}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Compliance Scores</h6>
                            <p><strong>Overall:</strong> ${check.overall_compliance}%</p>
                            <p><strong>Budget:</strong> ${check.budget_compliance}/5</p>
                            <p><strong>Timeline:</strong> ${check.timeline_compliance}/5</p>
                            <p><strong>Quality:</strong> ${check.quality_standards}/5</p>
                            <p><strong>Safety:</strong> ${check.safety_standards}/5</p>
                            <p><strong>Documentation:</strong> ${check.documentation_compliance}/5</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Key Findings</h6>
                            <p class="border p-3 rounded bg-light">${check.findings || 'No findings recorded.'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Recommendations</h6>
                            <p class="border p-3 rounded bg-light">${check.recommendations || 'No recommendations provided.'}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Next Audit Date:</strong> ${check.next_audit_date ? new Date(check.next_audit_date).toLocaleDateString() : 'Not set'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Conducted On:</strong> ${new Date(check.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;
                
                detailsContent.innerHTML = detailsHTML;
            });
        });

        // Auto-open modal if editing
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($compliance_to_edit)): ?>
                const modal = new bootstrap.Modal(document.getElementById('complianceModal'), {});
                modal.show();
            <?php endif; ?>
        });

        // Form validation
        document.getElementById('complianceForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields and rate all compliance metrics.');
            }
        });

        // Reset form when modal is closed
        document.getElementById('complianceModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('complianceForm').reset();
            document.querySelectorAll('.star').forEach(star => {
                star.classList.remove('active');
            });
            document.querySelectorAll('.compliance-fill').forEach(indicator => {
                indicator.style.width = '0%';
            });
            document.getElementById('overallScore').textContent = '0%';
            document.getElementById('overallScore').className = 'compliance-score score-poor';
            document.getElementById('overall_indicator').style.width = '0%';
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
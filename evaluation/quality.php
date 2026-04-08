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

// Get quality evaluation data
$qualityEvaluations = getQualityEvaluations($_SESSION['user_id']);
$qualityMetrics = getQualityMetrics();
$evaluationCriteria = getEvaluationCriteria();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_evaluation'])) {
        $projectId = $_POST['project_id'];
        $qualityScore = $_POST['quality_score'];
        $workmanshipScore = $_POST['workmanship_score'];
        $materialsScore = $_POST['materials_score'];
        $safetyScore = $_POST['safety_score'];
        $complianceScore = $_POST['compliance_score'];
        $comments = $_POST['comments'];
        $recommendations = $_POST['recommendations'];
        
        $result = saveQualityEvaluation(
            $projectId,
            $_SESSION['user_id'],
            $qualityScore,
            $workmanshipScore,
            $materialsScore,
            $safetyScore,
            $complianceScore,
            $comments,
            $recommendations
        );
        
        if ($result) {
            $_SESSION['success_message'] = "Quality evaluation submitted successfully!";
            redirect('quality.php');
        } else {
            $_SESSION['error_message'] = "Failed to submit quality evaluation. Please try again.";
        }
    }
}

$pageTitle = "Quality Assessment - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Quality assessment dashboard for CDF Management System - Government of Zambia">
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: var(--shadow);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.75rem 1rem !important;
            border-radius: 6px;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 76px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            box-shadow: var(--shadow);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
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
        }

        .content-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
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

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .score-indicator {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .score-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
            background: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: var(--shadow);
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

        /* Admin Tools Style */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .tool-card {
            background: var(--white);
            border: none;
            border-radius: 8px;
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
        }

        .tool-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
                CDF M&E Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../officer_dashboard.php">
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
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? ''); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at'] ?? ''); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'] ?? '', 0, 50)); ?>...</p>
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
                    <h1>Quality Assessment</h1>
                    <p class="lead">Evaluate project quality standards and workmanship - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Department: <strong><?php echo htmlspecialchars($userData['department'] ?? 'M&E Department'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="#evaluation-form" class="btn btn-primary-custom">
                    <i class="fas fa-clipboard-check me-2"></i>New Assessment
                </a>
                <a href="#evaluations-table" class="btn btn-primary-custom">
                    <i class="fas fa-list me-2"></i>View Assessments
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($qualityEvaluations); ?></div>
                    <div class="stat-title">Total Assessments</div>
                    <div class="stat-subtitle">Quality evaluations conducted</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo calculateAverageQualityScore($qualityEvaluations); ?>%</div>
                    <div class="stat-title">Average Score</div>
                    <div class="stat-subtitle">Overall quality rating</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($projects); ?></div>
                    <div class="stat-title">Assigned Projects</div>
                    <div class="stat-subtitle">Available for assessment</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($qualityEvaluations, function($eval) { return $eval['overall_score'] >= 80; })); ?></div>
                    <div class="stat-title">High Quality</div>
                    <div class="stat-subtitle">Projects scoring 80%+</div>
                </div>
            </div>
        </div>

        <!-- Quality Assessment Tools -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-cogs me-2"></i>Quality Assessment Tools</h5>
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
                    <div class="tool-card danger" onclick="location.href='impact.php'">
                        <i class="fas fa-bullseye tool-icon"></i>
                        <h6>Impact Evaluation</h6>
                        <p class="small mb-0">Assess project impact metrics</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Metrics Charts -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Quality Assessment Metrics</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="qualityScoresChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="qualityDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Assessment Form -->
        <div class="content-card" id="evaluation-form">
            <div class="card-header">
                <h5><i class="fas fa-clipboard-check me-2"></i>New Quality Assessment</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Select Project</label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Choose a project...</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>">
                                            <?php echo htmlspecialchars($project['title']); ?> - <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unknown'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="evaluation_date" class="form-label">Evaluation Date</label>
                                <input type="date" class="form-control" id="evaluation_date" name="evaluation_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3 text-primary">Quality Criteria Assessment</h6>
                        </div>
                    </div>

                    <!-- Quality Score -->
                    <div class="mb-4">
                        <label class="form-label">Overall Quality Score</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="quality_score" name="quality_score" min="0" max="100" value="50" oninput="updateScoreDisplay('quality_score', 'quality_score_display')">
                            <span id="quality_score_display" class="badge bg-primary">50%</span>
                        </div>
                        <div class="score-bar">
                            <div id="quality_score_fill" class="score-fill bg-primary" style="width: 50%"></div>
                        </div>
                    </div>

                    <!-- Workmanship Score -->
                    <div class="mb-4">
                        <label class="form-label">Workmanship & Craftsmanship</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="workmanship_score" name="workmanship_score" min="0" max="100" value="50" oninput="updateScoreDisplay('workmanship_score', 'workmanship_score_display')">
                            <span id="workmanship_score_display" class="badge bg-primary">50%</span>
                        </div>
                        <div class="score-bar">
                            <div id="workmanship_score_fill" class="score-fill bg-primary" style="width: 50%"></div>
                        </div>
                    </div>

                    <!-- Materials Quality -->
                    <div class="mb-4">
                        <label class="form-label">Materials Quality</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="materials_score" name="materials_score" min="0" max="100" value="50" oninput="updateScoreDisplay('materials_score', 'materials_score_display')">
                            <span id="materials_score_display" class="badge bg-primary">50%</span>
                        </div>
                        <div class="score-bar">
                            <div id="materials_score_fill" class="score-fill bg-primary" style="width: 50%"></div>
                        </div>
                    </div>

                    <!-- Safety Standards -->
                    <div class="mb-4">
                        <label class="form-label">Safety Standards Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="safety_score" name="safety_score" min="0" max="100" value="50" oninput="updateScoreDisplay('safety_score', 'safety_score_display')">
                            <span id="safety_score_display" class="badge bg-primary">50%</span>
                        </div>
                        <div class="score-bar">
                            <div id="safety_score_fill" class="score-fill bg-primary" style="width: 50%"></div>
                        </div>
                    </div>

                    <!-- Compliance Score -->
                    <div class="mb-4">
                        <label class="form-label">Regulatory Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="compliance_score" name="compliance_score" min="0" max="100" value="50" oninput="updateScoreDisplay('compliance_score', 'compliance_score_display')">
                            <span id="compliance_score_display" class="badge bg-primary">50%</span>
                        </div>
                        <div class="score-bar">
                            <div id="compliance_score_fill" class="score-fill bg-primary" style="width: 50%"></div>
                        </div>
                    </div>

                    <!-- Comments and Recommendations -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="comments" class="form-label">Assessment Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Provide detailed comments on quality observations..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="recommendations" class="form-label">Recommendations</label>
                                <textarea class="form-control" id="recommendations" name="recommendations" rows="4" placeholder="Provide recommendations for improvement..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="submit_evaluation" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Submit Assessment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Quality Assessments -->
        <div class="content-card" id="evaluations-table">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Recent Quality Assessments</h5>
            </div>
            <div class="card-body">
                <?php if (count($qualityEvaluations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Date</th>
                                    <th>Quality</th>
                                    <th>Workmanship</th>
                                    <th>Materials</th>
                                    <th>Safety</th>
                                    <th>Compliance</th>
                                    <th>Overall</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($qualityEvaluations, 0, 8) as $evaluation): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($evaluation['project_title'] ?? ''); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($evaluation['beneficiary_name'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($evaluation['evaluation_date'] ?? '')); ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px; width: 80px;">
                                            <div class="progress-bar bg-<?php echo getScoreColor($evaluation['quality_score'] ?? 0); ?>" 
                                                 style="width: <?php echo $evaluation['quality_score'] ?? 0; ?>%"></div>
                                        </div>
                                        <small><?php echo $evaluation['quality_score'] ?? 0; ?>%</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px; width: 80px;">
                                            <div class="progress-bar bg-<?php echo getScoreColor($evaluation['workmanship_score'] ?? 0); ?>" 
                                                 style="width: <?php echo $evaluation['workmanship_score'] ?? 0; ?>%"></div>
                                        </div>
                                        <small><?php echo $evaluation['workmanship_score'] ?? 0; ?>%</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px; width: 80px;">
                                            <div class="progress-bar bg-<?php echo getScoreColor($evaluation['materials_score'] ?? 0); ?>" 
                                                 style="width: <?php echo $evaluation['materials_score'] ?? 0; ?>%"></div>
                                        </div>
                                        <small><?php echo $evaluation['materials_score'] ?? 0; ?>%</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px; width: 80px;">
                                            <div class="progress-bar bg-<?php echo getScoreColor($evaluation['safety_score'] ?? 0); ?>" 
                                                 style="width: <?php echo $evaluation['safety_score'] ?? 0; ?>%"></div>
                                        </div>
                                        <small><?php echo $evaluation['safety_score'] ?? 0; ?>%</small>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px; width: 80px;">
                                            <div class="progress-bar bg-<?php echo getScoreColor($evaluation['compliance_score'] ?? 0); ?>" 
                                                 style="width: <?php echo $evaluation['compliance_score'] ?? 0; ?>%"></div>
                                        </div>
                                        <small><?php echo $evaluation['compliance_score'] ?? 0; ?>%</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getScoreColor($evaluation['overall_score'] ?? 0); ?>">
                                            <?php echo $evaluation['overall_score'] ?? 0; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="quality_details.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="quality_edit.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-outline-warning">
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
                        <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Quality Assessments</h5>
                        <p class="text-muted mb-4">Start by conducting your first quality assessment.</p>
                        <a href="#evaluation-form" class="btn btn-primary-custom">
                            <i class="fas fa-plus-circle me-2"></i>Create Assessment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
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
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Score update function
        function updateScoreDisplay(sliderId, displayId) {
            const slider = document.getElementById(sliderId);
            const display = document.getElementById(displayId);
            const fill = document.getElementById(sliderId + '_fill');
            
            const value = slider.value;
            display.textContent = value + '%';
            fill.style.width = value + '%';
        }

        // Initialize all score displays
        document.addEventListener('DOMContentLoaded', function() {
            updateScoreDisplay('quality_score', 'quality_score_display');
            updateScoreDisplay('workmanship_score', 'workmanship_score_display');
            updateScoreDisplay('materials_score', 'materials_score_display');
            updateScoreDisplay('safety_score', 'safety_score_display');
            updateScoreDisplay('compliance_score', 'compliance_score_display');

            // Initialize Charts
            const qualityScoresCtx = document.getElementById('qualityScoresChart').getContext('2d');
            const qualityScoresChart = new Chart(qualityScoresCtx, {
                type: 'bar',
                data: {
                    labels: ['Quality', 'Workmanship', 'Materials', 'Safety', 'Compliance'],
                    datasets: [{
                        label: 'Average Scores',
                        data: [
                            <?php echo calculateAverageScore($qualityEvaluations, 'quality_score'); ?>,
                            <?php echo calculateAverageScore($qualityEvaluations, 'workmanship_score'); ?>,
                            <?php echo calculateAverageScore($qualityEvaluations, 'materials_score'); ?>,
                            <?php echo calculateAverageScore($qualityEvaluations, 'safety_score'); ?>,
                            <?php echo calculateAverageScore($qualityEvaluations, 'compliance_score'); ?>
                        ],
                        backgroundColor: [
                            '#1a4e8a',
                            '#28a745',
                            '#ffc107',
                            '#dc3545',
                            '#17a2b8'
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
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
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

            const qualityDistributionCtx = document.getElementById('qualityDistributionChart').getContext('2d');
            const qualityDistributionChart = new Chart(qualityDistributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent (80-100%)', 'Good (60-79%)', 'Fair (40-59%)', 'Poor (0-39%)'],
                    datasets: [{
                        data: [
                            <?php echo count(array_filter($qualityEvaluations, function($eval) { return $eval['overall_score'] >= 80; })); ?>,
                            <?php echo count(array_filter($qualityEvaluations, function($eval) { return $eval['overall_score'] >= 60 && $eval['overall_score'] < 80; })); ?>,
                            <?php echo count(array_filter($qualityEvaluations, function($eval) { return $eval['overall_score'] >= 40 && $eval['overall_score'] < 60; })); ?>,
                            <?php echo count(array_filter($qualityEvaluations, function($eval) { return $eval['overall_score'] < 40; })); ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#17a2b8',
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
        });
    </script>
</body>
</html>
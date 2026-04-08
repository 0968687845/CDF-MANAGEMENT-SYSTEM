<?php
require_once '../../functions.php';
requireRole('officer');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? '';
    $budget_compliance = $_POST['budget_compliance'] ?? 0;
    $timeline_compliance = $_POST['timeline_compliance'] ?? 0;
    $documentation_compliance = $_POST['documentation_compliance'] ?? 0;
    $quality_standards = $_POST['quality_standards'] ?? 0;
    $community_engagement = $_POST['community_engagement'] ?? 0;
    $environmental_compliance = $_POST['environmental_compliance'] ?? 0;
    $procurement_compliance = $_POST['procurement_compliance'] ?? 0;
    $safety_standards = $_POST['safety_standards'] ?? 0;
    $findings = $_POST['findings'] ?? '';
    $recommendations = $_POST['recommendations'] ?? '';
    $next_audit_date = $_POST['next_audit_date'] ?? '';
    
    // Calculate overall compliance (average of all metrics)
    $overall_compliance = round((
        $budget_compliance + 
        $timeline_compliance + 
        $documentation_compliance + 
        $quality_standards + 
        $community_engagement + 
        $environmental_compliance + 
        $procurement_compliance + 
        $safety_standards
    ) / 8);
    
    // Save to database
    $stmt = $pdo->prepare("
        INSERT INTO compliance_checks 
        (project_id, budget_compliance, timeline_compliance, documentation_compliance, 
         quality_standards, community_engagement, environmental_compliance, 
         procurement_compliance, safety_standards, overall_compliance, 
         findings, recommendations, next_audit_date, officer_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $project_id, $budget_compliance, $timeline_compliance, $documentation_compliance,
        $quality_standards, $community_engagement, $environmental_compliance,
        $procurement_compliance, $safety_standards, $overall_compliance,
        $findings, $recommendations, $next_audit_date, $_SESSION['user_id']
    ]);
    
    if ($success) {
        $_SESSION['success_message'] = "Compliance check submitted successfully!";
        redirect('evaluation/compliance.php');
    } else {
        $_SESSION['error_message'] = "Failed to submit compliance check. Please try again.";
    }
}

$userData = getUserData();
$projects = getOfficerProjects($_SESSION['user_id']);
$compliance_checks = getComplianceChecks($_SESSION['user_id']);

$pageTitle = "Compliance Check - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Compliance check evaluation for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            padding: 0.75rem 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-brand img {
            width: 45px;
            height: 45px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.5)) brightness(1.05) contrast(1.1);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            padding: 3px;
            background: rgba(255, 255, 255, 0.1);
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.1);
            filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.7)) brightness(1.1) contrast(1.2);
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.2);
        }

        .navbar-brand img {
            width: 45px;
            height: 45px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.5)) brightness(1.05) contrast(1.1);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            padding: 3px;
            background: rgba(255, 255, 255, 0.1);
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.75rem 1rem !important;
            border-radius: 6px;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--secondary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::before,
        .nav-link:focus::before,
        .nav-link.active::before {
            width: 80%;
        }

        .nav-link:hover, 
        .nav-link:focus,
        .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
        }

        /* Dashboard Header */
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
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        /* Content Cards */
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

        /* Buttons */
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

        /* Evaluation Tools */
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

        .tool-card h6 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .tool-card p {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .tool-card:hover p {
            color: rgba(255, 255, 255, 0.9);
        }

        .metric-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .metric-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .compliance-slider {
            width: 100%;
            margin: 1rem 0;
        }

        .compliance-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Status Colors */
        .status-excellent { color: var(--success); }
        .status-good { color: #20c997; }
        .status-fair { color: var(--warning); }
        .status-poor { color: var(--danger); }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            padding: 2rem 1.5rem;
            margin-top: 3rem;
            border-top: 3px solid var(--primary);
        }

        .dashboard-footer img {
            max-height: 70px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4)) brightness(1.08) contrast(1.15);
            border: 2px solid rgba(233, 185, 73, 0.35);
            border-radius: 8px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .dashboard-footer:hover img {
            transform: scale(1.08);
            filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.5)) brightness(1.15) contrast(1.25);
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.18);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
                padding: 1.5rem 0;
            }
            
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <img src="../../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item active" href="compliance.php">
                                <i class="fas fa-check-double me-2"></i>Compliance Check
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../settings/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="../settings/system.php">
                                <i class="fas fa-cog me-2"></i>Account Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../index.php?logout=true">
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
                    <p class="lead">Verify CDF guidelines compliance - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Department: <strong><?php echo htmlspecialchars($userData['department'] ?? 'M&E Department'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="reports.php" class="btn btn-primary-custom">
                    <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                </a>
                <a href="progress.php" class="btn btn-outline-custom">
                    <i class="fas fa-chart-line me-2"></i>Progress Review
                </a>
                <a href="quality.php" class="btn btn-outline-custom">
                    <i class="fas fa-award me-2"></i>Quality Assessment
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
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
                    <div class="tool-card warning" onclick="location.href='../../evaluation/compliance.php'">
                        <i class="fas fa-check-double tool-icon"></i>
                        <h6>Compliance Check</h6>
                        <p class="small mb-0">Verify CDF guidelines compliance</p>
                    </div>
                    <div class="tool-card success" onclick="location.href='../../evaluation/progress.php'">
                        <i class="fas fa-chart-line tool-icon"></i>
                        <h6>Progress Review</h6>
                        <p class="small mb-0">Review beneficiary progress reports</p>
                    </div>
                    <div class="tool-card info" onclick="location.href='../../evaluation/quality.php'">
                        <i class="fas fa-award tool-icon"></i>
                        <h6>Quality Assessment</h6>
                        <p class="small mb-0">Evaluate project quality standards</p>
                    </div>
                    <div class="tool-card" onclick="location.href='../../evaluation/impact.php'" style="border-left-color: #6c757d;">
                        <i class="fas fa-bullseye tool-icon" style="color: #6c757d;"></i>
                        <h6>Impact Evaluation</h6>
                        <p class="small mb-0">Assess project impact metrics</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Compliance Form -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-check-double me-2"></i>Compliance Evaluation Form</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="complianceForm">
                            <div class="mb-4">
                                <label for="project_id" class="form-label fw-bold">Select Project</label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Choose a project...</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>">
                                            <?php echo htmlspecialchars($project['title']); ?> - <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Compliance Metrics -->
                            <h6 class="mb-3 text-primary">Compliance Metrics (Rate 0-100%)</h6>

                            <!-- Budget Compliance -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Budget Compliance</div>
                                        <div class="metric-value">
                                            <span id="budgetValue">50</span>%
                                            <span id="budgetStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="budget_compliance" 
                                       name="budget_compliance" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('budget', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Non-compliant)</span>
                                    <span>100% (Fully compliant)</span>
                                </div>
                            </div>

                            <!-- Timeline Compliance -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Timeline Compliance</div>
                                        <div class="metric-value">
                                            <span id="timelineValue">50</span>%
                                            <span id="timelineStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="timeline_compliance" 
                                       name="timeline_compliance" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('timeline', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Non-compliant)</span>
                                    <span>100% (Fully compliant)</span>
                                </div>
                            </div>

                            <!-- Documentation Compliance -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Documentation Compliance</div>
                                        <div class="metric-value">
                                            <span id="documentationValue">50</span>%
                                            <span id="documentationStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="documentation_compliance" 
                                       name="documentation_compliance" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('documentation', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Non-compliant)</span>
                                    <span>100% (Fully compliant)</span>
                                </div>
                            </div>

                            <!-- Quality Standards -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Quality Standards</div>
                                        <div class="metric-value">
                                            <span id="qualityValue">50</span>%
                                            <span id="qualityStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="quality_standards" 
                                       name="quality_standards" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('quality', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Poor quality)</span>
                                    <span>100% (Excellent quality)</span>
                                </div>
                            </div>

                            <!-- Community Engagement -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Community Engagement</div>
                                        <div class="metric-value">
                                            <span id="communityValue">50</span>%
                                            <span id="communityStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="community_engagement" 
                                       name="community_engagement" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('community', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (No engagement)</span>
                                    <span>100% (Full engagement)</span>
                                </div>
                            </div>

                            <!-- Environmental Compliance -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Environmental Compliance</div>
                                        <div class="metric-value">
                                            <span id="environmentalValue">50</span>%
                                            <span id="environmentalStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="environmental_compliance" 
                                       name="environmental_compliance" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('environmental', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Non-compliant)</span>
                                    <span>100% (Fully compliant)</span>
                                </div>
                            </div>

                            <!-- Procurement Compliance -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Procurement Compliance</div>
                                        <div class="metric-value">
                                            <span id="procurementValue">50</span>%
                                            <span id="procurementStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="procurement_compliance" 
                                       name="procurement_compliance" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('procurement', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Non-compliant)</span>
                                    <span>100% (Fully compliant)</span>
                                </div>
                            </div>

                            <!-- Safety Standards -->
                            <div class="compliance-metric">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Safety Standards</div>
                                        <div class="metric-value">
                                            <span id="safetyValue">50</span>%
                                            <span id="safetyStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="form-range compliance-slider" id="safety_standards" 
                                       name="safety_standards" min="0" max="100" value="50" 
                                       oninput="updateSliderValue('safety', this.value)">
                                <div class="compliance-labels">
                                    <span>0% (Unsafe)</span>
                                    <span>100% (Fully safe)</span>
                                </div>
                            </div>

                            <!-- Overall Compliance -->
                            <div class="compliance-metric bg-light">
                                <div class="metric-header">
                                    <div>
                                        <div class="metric-title">Overall Compliance Score</div>
                                        <div class="metric-value">
                                            <span id="overallValue">50</span>%
                                            <span id="overallStatus" class="status-fair ms-2">Fair</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div id="overallProgress" class="progress-bar bg-warning" style="width: 50%"></div>
                                </div>
                            </div>

                            <!-- Findings and Recommendations -->
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="findings" class="form-label fw-bold">Key Findings</label>
                                        <textarea class="form-control" id="findings" name="findings" rows="4" 
                                                  placeholder="Describe any compliance issues or observations..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="recommendations" class="form-label fw-bold">Recommendations</label>
                                        <textarea class="form-control" id="recommendations" name="recommendations" rows="4" 
                                                  placeholder="Provide recommendations for improvement..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Audit Date -->
                            <div class="mb-4">
                                <label for="next_audit_date" class="form-label fw-bold">Recommended Next Audit Date</label>
                                <input type="date" class="form-control" id="next_audit_date" name="next_audit_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">Reset Form</button>
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-check-circle me-2"></i>Submit Compliance Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Compliance Checks -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Compliance Checks</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($compliance_checks) > 0): ?>
                            <?php foreach (array_slice($compliance_checks, 0, 5) as $check): ?>
                            <div class="activity-item mb-3 p-3 border-start border-3 border-primary">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($check['project_title'] ?? 'Unknown Project'); ?></h6>
                                    <span class="badge bg-<?php echo getComplianceBadgeColor($check['overall_compliance']); ?>">
                                        <?php echo $check['overall_compliance']; ?>%
                                    </span>
                                </div>
                                <p class="small text-muted mb-1">
                                    <?php echo date('M j, Y', strtotime($check['created_at'])); ?>
                                </p>
                                <p class="small mb-0">
                                    <?php echo htmlspecialchars(substr($check['findings'] ?? 'No findings recorded', 0, 60)); ?>...
                                </p>
                            </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="compliance_history.php" class="btn btn-outline-primary btn-sm">
                                    View All Compliance Checks
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-check fa-2x text-muted mb-3"></i>
                                <h6 class="text-muted">No Compliance Checks</h6>
                                <p class="text-muted small">No compliance evaluations have been submitted yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Compliance Guidelines -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Compliance Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-2"></i>Rating Scale</h6>
                            <ul class="small mb-0">
                                <li><strong>90-100%:</strong> Excellent - Fully compliant</li>
                                <li><strong>75-89%:</strong> Good - Minor issues</li>
                                <li><strong>60-74%:</strong> Fair - Needs improvement</li>
                                <li><strong>Below 60%:</strong> Poor - Major compliance issues</li>
                            </ul>
                        </div>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Key Areas</h6>
                            <ul class="small mb-0">
                                <li>Budget adherence to allocated amounts</li>
                                <li>Timeline alignment with project schedule</li>
                                <li>Proper documentation and reporting</li>
                                <li>Quality of workmanship and materials</li>
                                <li>Community participation and feedback</li>
                            </ul>
                        </div>
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
                    <img src="../../coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3">
                    <div>
                        <h5 class="mb-0">CDF Management System</h5>
                        <p class="mb-0 text-muted">Government of the Republic of Zambia</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> - All Rights Reserved</p>
                <p class="mb-0 text-muted">Compliance Module | Version 2.5.1</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateSliderValue(type, value) {
            // Update value display
            document.getElementById(type + 'Value').textContent = value;
            
            // Update status text and color
            const statusElement = document.getElementById(type + 'Status');
            let statusText = '';
            let statusClass = '';
            
            if (value >= 90) {
                statusText = 'Excellent';
                statusClass = 'status-excellent';
            } else if (value >= 75) {
                statusText = 'Good';
                statusClass = 'status-good';
            } else if (value >= 60) {
                statusText = 'Fair';
                statusClass = 'status-fair';
            } else {
                statusText = 'Poor';
                statusClass = 'status-poor';
            }
            
            statusElement.textContent = statusText;
            statusElement.className = statusClass + ' ms-2';
            
            // Update overall compliance
            updateOverallCompliance();
        }

        function updateOverallCompliance() {
            const sliders = [
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
            sliders.forEach(slider => {
                total += parseInt(document.getElementById(slider).value);
            });
            
            const overall = Math.round(total / sliders.length);
            
            document.getElementById('overallValue').textContent = overall;
            document.getElementById('overallProgress').style.width = overall + '%';
            
            // Update overall status
            const statusElement = document.getElementById('overallStatus');
            let statusClass = '';
            
            if (overall >= 90) {
                statusElement.textContent = 'Excellent';
                statusClass = 'status-excellent';
                document.getElementById('overallProgress').className = 'progress-bar bg-success';
            } else if (overall >= 75) {
                statusElement.textContent = 'Good';
                statusClass = 'status-good';
                document.getElementById('overallProgress').className = 'progress-bar bg-info';
            } else if (overall >= 60) {
                statusElement.textContent = 'Fair';
                statusClass = 'status-fair';
                document.getElementById('overallProgress').className = 'progress-bar bg-warning';
            } else {
                statusElement.textContent = 'Poor';
                statusClass = 'status-poor';
                document.getElementById('overallProgress').className = 'progress-bar bg-danger';
            }
            
            statusElement.className = statusClass + ' ms-2';
        }

        // Set minimum date for next audit to today
        document.getElementById('next_audit_date').min = new Date().toISOString().split('T')[0];

        // Initialize all sliders
        document.addEventListener('DOMContentLoaded', function() {
            updateOverallCompliance();
        });
    </script>
</body>
</html>
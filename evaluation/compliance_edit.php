<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Get evaluation ID from URL
$evaluationId = $_GET['id'] ?? 0;
if (!$evaluationId) {
    $_SESSION['error_message'] = "No evaluation ID specified.";
    redirect('compliance.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get evaluation data
$evaluation = getComplianceEvaluationById($evaluationId);
if (!$evaluation) {
    $_SESSION['error_message'] = "Compliance evaluation not found.";
    redirect('compliance.php');
}

// Get projects for dropdown
$projects = getOfficerProjects($_SESSION['user_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_evaluation'])) {
        $projectId = $_POST['project_id'];
        $documentationScore = $_POST['documentation_score'];
        $regulatoryScore = $_POST['regulatory_score'];
        $environmentalScore = $_POST['environmental_score'];
        $safetyScore = $_POST['safety_score'];
        $financialScore = $_POST['financial_score'];
        $comments = $_POST['comments'];
        $recommendations = $_POST['recommendations'];
        $status = $_POST['status'];
        
        $result = updateComplianceEvaluation(
            $evaluationId,
            $projectId,
            $documentationScore,
            $regulatoryScore,
            $environmentalScore,
            $safetyScore,
            $financialScore,
            $comments,
            $recommendations,
            $status
        );
        
        if ($result) {
            $_SESSION['success_message'] = "Compliance evaluation updated successfully!";
            redirect('compliance.php');
        } else {
            $_SESSION['error_message'] = "Failed to update compliance evaluation. Please try again.";
        }
    }
}

$pageTitle = "Edit Compliance Evaluation - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Edit compliance evaluation for CDF Management System - Government of Zambia">
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
                    <h1>Edit Compliance Evaluation</h1>
                    <p class="lead">Update compliance assessment details - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Editing evaluation for: <strong><?php echo htmlspecialchars($evaluation['project_title'] ?? 'Unknown Project'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="compliance.php" class="btn btn-outline-custom">
                    <i class="fas fa-arrow-left me-2"></i>Back to Compliance
                </a>
                <button type="submit" form="evaluationForm" class="btn btn-primary-custom">
                    <i class="fas fa-save me-2"></i>Update Evaluation
                </button>
                <a href="compliance_details.php?id=<?php echo $evaluationId; ?>" class="btn btn-outline-custom">
                    <i class="fas fa-eye me-2"></i>View Details
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Edit Compliance Evaluation Form -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i>Edit Compliance Evaluation</h5>
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

                <form method="POST" action="" id="evaluationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project</label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Select a project...</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>" 
                                            <?php echo ($project['id'] == $evaluation['project_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['title']); ?> - <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unknown'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="evaluation_date" class="form-label">Evaluation Date</label>
                                <input type="date" class="form-control" id="evaluation_date" name="evaluation_date" 
                                       value="<?php echo htmlspecialchars($evaluation['evaluation_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Evaluation Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="completed" <?php echo ($evaluation['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="in-progress" <?php echo ($evaluation['status'] == 'in-progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="draft" <?php echo ($evaluation['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Overall Compliance Score</label>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-<?php echo getScoreColor($evaluation['overall_score'] ?? 0); ?> me-2">
                                        <?php echo $evaluation['overall_score'] ?? 0; ?>%
                                    </span>
                                    <small class="text-muted">Calculated automatically</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3 text-primary">Compliance Criteria Assessment</h6>
                        </div>
                    </div>

                    <!-- Documentation Compliance -->
                    <div class="mb-4">
                        <label class="form-label">Documentation Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="documentation_score" name="documentation_score" 
                                   min="0" max="100" value="<?php echo $evaluation['documentation_score'] ?? 50; ?>" 
                                   oninput="updateScoreDisplay('documentation_score', 'documentation_score_display')">
                            <span id="documentation_score_display" class="badge bg-primary"><?php echo $evaluation['documentation_score'] ?? 50; ?>%</span>
                        </div>
                        <div class="score-bar">
                            <div id="documentation_score_fill" class="score-fill bg-primary" 
                                 style="width: <?php echo $evaluation['documentation_score'] ?? 50; ?>%"></div>
                        </div>
                    </div>

                    <!-- Regulatory Compliance -->
                    <div class="mb-4">
                        <label class="form-label">Regulatory Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="regulatory_score" name="regulatory_score" 
                                   min="0" max="100" value="<?php echo $evaluation['regulatory_score'] ?? 50; ?>" 
                                   oninput="updateScoreDisplay('regulatory_score', 'regulatory_score_display')">
                            <span id="regulatory_score_display" class="badge bg-primary"><?php echo $evaluation['regulatory_score'] ?? 50; ?>%</span>
                        </div>
                        <div class="score-bar">
                            <div id="regulatory_score_fill" class="score-fill bg-primary" 
                                 style="width: <?php echo $evaluation['regulatory_score'] ?? 50; ?>%"></div>
                        </div>
                    </div>

                    <!-- Environmental Compliance -->
                    <div class="mb-4">
                        <label class="form-label">Environmental Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="environmental_score" name="environmental_score" 
                                   min="0" max="100" value="<?php echo $evaluation['environmental_score'] ?? 50; ?>" 
                                   oninput="updateScoreDisplay('environmental_score', 'environmental_score_display')">
                            <span id="environmental_score_display" class="badge bg-primary"><?php echo $evaluation['environmental_score'] ?? 50; ?>%</span>
                        </div>
                        <div class="score-bar">
                            <div id="environmental_score_fill" class="score-fill bg-primary" 
                                 style="width: <?php echo $evaluation['environmental_score'] ?? 50; ?>%"></div>
                        </div>
                    </div>

                    <!-- Safety Compliance -->
                    <div class="mb-4">
                        <label class="form-label">Safety Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="safety_score" name="safety_score" 
                                   min="0" max="100" value="<?php echo $evaluation['safety_score'] ?? 50; ?>" 
                                   oninput="updateScoreDisplay('safety_score', 'safety_score_display')">
                            <span id="safety_score_display" class="badge bg-primary"><?php echo $evaluation['safety_score'] ?? 50; ?>%</span>
                        </div>
                        <div class="score-bar">
                            <div id="safety_score_fill" class="score-fill bg-primary" 
                                 style="width: <?php echo $evaluation['safety_score'] ?? 50; ?>%"></div>
                        </div>
                    </div>

                    <!-- Financial Compliance -->
                    <div class="mb-4">
                        <label class="form-label">Financial Compliance</label>
                        <div class="score-indicator">
                            <input type="range" class="form-range" id="financial_score" name="financial_score" 
                                   min="0" max="100" value="<?php echo $evaluation['financial_score'] ?? 50; ?>" 
                                   oninput="updateScoreDisplay('financial_score', 'financial_score_display')">
                            <span id="financial_score_display" class="badge bg-primary"><?php echo $evaluation['financial_score'] ?? 50; ?>%</span>
                        </div>
                        <div class="score-bar">
                            <div id="financial_score_fill" class="score-fill bg-primary" 
                                 style="width: <?php echo $evaluation['financial_score'] ?? 50; ?>%"></div>
                        </div>
                    </div>

                    <!-- Comments and Recommendations -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="comments" class="form-label">Assessment Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="4" 
                                          placeholder="Provide detailed comments on compliance observations..."><?php echo htmlspecialchars($evaluation['comments'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="recommendations" class="form-label">Recommendations</label>
                                <textarea class="form-control" id="recommendations" name="recommendations" rows="4" 
                                          placeholder="Provide recommendations for improvement..."><?php echo htmlspecialchars($evaluation['recommendations'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="compliance.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" name="update_evaluation" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Update Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Evaluation Information -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Evaluation Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Evaluation ID:</th>
                                <td>#<?php echo $evaluationId; ?></td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td><?php echo date('M j, Y g:i A', strtotime($evaluation['created_at'] ?? 'now')); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo date('M j, Y g:i A', strtotime($evaluation['updated_at'] ?? $evaluation['created_at'] ?? 'now')); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Project:</th>
                                <td><?php echo htmlspecialchars($evaluation['project_title'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <th>Beneficiary:</th>
                                <td><?php echo htmlspecialchars($evaluation['beneficiary_name'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <th>Evaluating Officer:</th>
                                <td><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
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
            updateScoreDisplay('documentation_score', 'documentation_score_display');
            updateScoreDisplay('regulatory_score', 'regulatory_score_display');
            updateScoreDisplay('environmental_score', 'environmental_score_display');
            updateScoreDisplay('safety_score', 'safety_score_display');
            updateScoreDisplay('financial_score', 'financial_score_display');
        });
    </script>
</body>
</html>

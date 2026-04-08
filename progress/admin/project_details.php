<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Get project ID from query parameter
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    redirect('projects.php');
    exit();
}

// Get project data
$project = getProjectById($project_id);
if (!$project) {
    $_SESSION['error'] = "Project not found";
    redirect('projects.php');
    exit();
}

$userData = getUserData();
$pageTitle = "Project Details - " . htmlspecialchars($project['title']);

// Get project expenses and progress updates
$expenses = getProjectExpenses($project_id);
$progress_updates = getProjectProgress($project_id);
$total_expenses = getTotalProjectExpenses($project_id);
$expense_categories = getExpenseCategoriesSummary($project_id);

// Calculate budget utilization
$budget_utilization = $project['budget'] > 0 ? ($total_expenses / $project['budget']) * 100 : 0;

// Get beneficiary and officer details
$beneficiary = getUserById($project['beneficiary_id']);
$officer = $project['officer_id'] ? getUserById($project['officer_id']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --gov-blue: #003366;
            --gov-gold: #FFD700;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .detail-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .project-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .project-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid var(--primary);
        }
        
        .info-label {
            font-size: 0.875rem;
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .progress-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .progress {
            height: 12px;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
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
            width: 2px;
            background: var(--primary);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
        }
        
        .expense-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--success);
        }
        
        .budget-warning {
            border-left-color: var(--warning);
        }
        
        .budget-danger {
            border-left-color: var(--danger);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.3);
            color: white;
        }

        /* Photo Gallery Styles */
        .img-thumbnail {
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            padding: 8px;
            cursor: pointer;
        }

        .img-thumbnail:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            transform: scale(1.05);
        }

        .timeline-item {
            padding: 1.5rem;
            border-left: 4px solid #0d6efd;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>

    <!-- Lightbox CSS for image gallery -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: #1a4e8a;">
        <div class="container">
            <a class="navbar-brand" href="../admin_dashboard.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../admin_dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="assignments.php">
                                <i class="fas fa-user-tie me-2"></i>Officer Assignments
                            </a></li>
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>System Reports
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>System Settings
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                            <?php 
                                            echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); 
                                            ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
                                            <small class="text-muted">System Administrator</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../admin_dashboard.php?logout=true">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-project-diagram me-3"></i>Project Details</h1>
                    <p class="lead mb-0">Comprehensive overview of project information and progress</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <a href="projects.php" class="btn btn-light me-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Projects
                        </a>
                        <a href="edit_project.php?id=<?php echo $project_id; ?>" class="btn btn-outline-light">
                            <i class="fas fa-edit me-2"></i>Edit Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Project Hero Section -->
        <div class="project-hero">
            <div class="project-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <h1><?php echo htmlspecialchars($project['title']); ?></h1>
            <p class="lead mb-4"><?php echo htmlspecialchars($project['description']); ?></p>
            <div class="row justify-content-center">
                <div class="col-auto">
                    <span class="badge bg-<?php 
                        switch($project['status']) {
                            case 'completed': echo 'success'; break;
                            case 'in-progress': echo 'primary'; break;
                            case 'delayed': echo 'danger'; break;
                            case 'planning': echo 'secondary'; break;
                            default: echo 'info';
                        }
                    ?> fs-6 p-2"><?php echo ucfirst($project['status']); ?></span>
                </div>
                <div class="col-auto">
                    <span class="badge bg-light text-dark fs-6 p-2">
                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($project['category'] ?? 'Uncategorized'); ?>
                    </span>
                </div>
                <div class="col-auto">
                    <span class="badge bg-light text-dark fs-6 p-2">
                        Project ID: #<?php echo $project['id']; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $project['progress']; ?>%</div>
                    <div>Completion Progress</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffd54f 100%); color: #000;">
                    <div class="stats-number">ZMW <?php echo number_format($project['budget'], 2); ?></div>
                    <div>Total Budget</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <div class="stats-number">ZMW <?php echo number_format($total_expenses, 2); ?></div>
                    <div>Total Expenses</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, <?php echo $budget_utilization > 100 ? '#f5576c' : ($budget_utilization > 80 ? '#ffc107' : '#56ab2f'); ?> 0%, <?php echo $budget_utilization > 100 ? '#f093fb' : ($budget_utilization > 80 ? '#ffd54f' : '#a8e6cf'); ?> 100%); <?php echo $budget_utilization > 80 ? 'color: #000;' : 'color: white;'; ?>">
                    <div class="stats-number"><?php echo round($budget_utilization, 1); ?>%</div>
                    <div>Budget Utilization</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Project Information -->
            <div class="col-lg-8 mb-4">
                <div class="detail-card">
                    <div class="card-body">
                        <h4 class="section-title">Project Information</h4>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Project Category</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['category'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Location</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['location'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Constituency</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['constituency'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Funding Source</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['funding_source'] ?? 'CDF'); ?></div>
                            </div>
                        </div>

                        <h5 class="mb-3">Timeline</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Start Date</div>
                                <div class="info-value"><?php echo date('M j, Y', strtotime($project['start_date'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">End Date</div>
                                <div class="info-value"><?php echo date('M j, Y', strtotime($project['end_date'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Duration</div>
                                <div class="info-value">
                                    <?php
                                    $start = new DateTime($project['start_date']);
                                    $end = new DateTime($project['end_date']);
                                    $interval = $start->diff($end);
                                    echo $interval->format('%m months %d days');
                                    ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Days Remaining</div>
                                <div class="info-value">
                                    <?php
                                    $today = new DateTime();
                                    $end = new DateTime($project['end_date']);
                                    $remaining = $today->diff($end);
                                    echo $remaining->format('%r%a days');
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Updates -->
                <div class="detail-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title mb-0">Progress Updates</h4>
                            <span class="badge bg-primary"><?php echo count($progress_updates); ?> updates</span>
                        </div>

                        <?php if (count($progress_updates) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($progress_updates as $update): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1">Progress: <?php echo $update['progress_percentage']; ?>%</h6>
                                        <small class="text-muted"><?php echo time_elapsed_string($update['created_at']); ?></small>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($update['description']); ?></p>
                                    <?php if (!empty($update['challenges'])): ?>
                                        <div class="alert alert-warning py-2 mb-2">
                                            <strong>Challenges:</strong> <?php echo htmlspecialchars($update['challenges']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($update['next_steps'])): ?>
                                        <div class="alert alert-info py-2 mb-2">
                                            <strong>Next Steps:</strong> <?php echo htmlspecialchars($update['next_steps']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Progress Photos -->
                                    <?php if (!empty($update['photos'])): 
                                        $photos = json_decode($update['photos'], true);
                                        if (is_array($photos) && count($photos) > 0): ?>
                                    <div class="mt-3">
                                        <strong class="d-block mb-2">Progress Photos:</strong>
                                        <div class="row g-2">
                                            <?php foreach ($photos as $photo): 
                                                if (file_exists('../' . $photo)): ?>
                                                <div class="col-auto">
                                                    <a href="../<?php echo htmlspecialchars($photo); ?>" target="_blank" data-lightbox="progress-<?php echo $update['id']; ?>">
                                                        <img src="../<?php echo htmlspecialchars($photo); ?>" alt="Progress Photo" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2">
                                        Reported by: <?php echo htmlspecialchars($update['created_by_name'] ?? 'System'); ?>
                                    </small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Progress Updates</h5>
                                <p class="text-muted">No progress updates have been reported for this project yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Expense Tracking -->
                <div class="detail-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title mb-0">Expense Tracking</h4>
                            <span class="badge bg-success"><?php echo count($expenses); ?> expenses</span>
                        </div>

                        <?php if (count($expenses) > 0): ?>
                            <div class="mb-4">
                                <h6>Expense Summary by Category</h6>
                                <?php foreach ($expense_categories as $category): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?php echo htmlspecialchars($category['category']); ?></span>
                                    <span class="fw-bold">ZMW <?php echo number_format($category['total_amount'], 2); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <h6>Recent Expenses</h6>
                            <?php foreach (array_slice($expenses, 0, 5) as $expense): ?>
                            <div class="expense-item <?php echo $expense['amount'] > ($project['budget'] * 0.1) ? 'budget-warning' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($expense['description']); ?></h6>
                                        <small class="text-muted">Category: <?php echo htmlspecialchars($expense['category']); ?></small>
                                    </div>
                                    <span class="fw-bold text-success">ZMW <?php echo number_format($expense['amount'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?>
                                        <?php if (!empty($expense['vendor'])): ?>
                                            • <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($expense['vendor']); ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php if (!empty($expense['receipt_number'])): ?>
                                        <small class="text-muted">Receipt: <?php echo htmlspecialchars($expense['receipt_number']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <?php if (count($expenses) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="project_expenses.php?id=<?php echo $project_id; ?>" class="btn btn-outline-primary btn-sm">
                                    View All <?php echo count($expenses); ?> Expenses
                                </a>
                            </div>
                            <?php endif; ?>
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

            <!-- Sidebar -->
            <div class="col-lg-4 mb-4">
                <!-- Progress Overview -->
                <div class="detail-card">
                    <div class="card-body">
                        <h4 class="section-title">Progress Overview</h4>
                        <div class="progress-container">
                            <div class="progress">
                                <div class="progress-bar bg-<?php 
                                    switch($project['status']) {
                                        case 'completed': echo 'success'; break;
                                        case 'in-progress': echo 'primary'; break;
                                        case 'delayed': echo 'danger'; break;
                                        default: echo 'info';
                                    }
                                ?>" style="width: <?php echo $project['progress']; ?>%"></div>
                            </div>
                            <div class="text-center">
                                <span class="fw-bold fs-2"><?php echo $project['progress']; ?>%</span>
                                <small class="text-muted d-block">Complete</small>
                            </div>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="badge bg-<?php 
                                        switch($project['status']) {
                                            case 'completed': echo 'success'; break;
                                            case 'in-progress': echo 'primary'; break;
                                            case 'delayed': echo 'danger'; break;
                                            case 'planning': echo 'secondary'; break;
                                            default: echo 'info';
                                        }
                                    ?>"><?php echo ucfirst($project['status']); ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value"><?php echo date('M j, Y', strtotime($project['updated_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Team -->
                <div class="detail-card">
                    <div class="card-body">
                        <h4 class="section-title">Project Team</h4>
                        
                        <!-- Beneficiary -->
                        <div class="user-card">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($beneficiary['first_name'], 0, 1) . substr($beneficiary['last_name'], 0, 1)); ?>
                            </div>
                            <h5><?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . $beneficiary['last_name']); ?></h5>
                            <p class="text-muted mb-2">Project Beneficiary</p>
                            <div class="d-flex justify-content-center gap-2">
                                <?php if (!empty($beneficiary['phone'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($beneficiary['phone']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if (!empty($beneficiary['email'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($beneficiary['email']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- M&E Officer -->
                        <?php if ($officer): ?>
                        <div class="user-card">
                            <div class="user-avatar" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                <?php echo strtoupper(substr($officer['first_name'], 0, 1) . substr($officer['last_name'], 0, 1)); ?>
                            </div>
                            <h5><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></h5>
                            <p class="text-muted mb-2">M&E Officer</p>
                            <div class="d-flex justify-content-center gap-2">
                                <?php if (!empty($officer['phone'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($officer['phone']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if (!empty($officer['email'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($officer['email']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="user-card">
                            <div class="user-avatar" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <h5>Unassigned</h5>
                            <p class="text-muted mb-3">No M&E Officer Assigned</p>
                            <a href="assign_officer.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-plus me-1"></i>Assign Officer
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="detail-card">
                    <div class="card-body">
                        <h4 class="section-title">Quick Actions</h4>
                        <div class="d-grid gap-2">
                            <a href="edit_project.php?id=<?php echo $project_id; ?>" class="btn btn-custom-primary">
                                <i class="fas fa-edit me-2"></i>Edit Project
                            </a>
                            <?php if (!$officer): ?>
                            <a href="assign_officer.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-user-tie me-2"></i>Assign Officer
                            </a>
                            <?php endif; ?>
                            <a href="project_reports.php?id=<?php echo $project_id; ?>" class="btn btn-outline-success">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </a>
                            <a href="communication/messages.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-info">
                                <i class="fas fa-comments me-2"></i>Send Message
                            </a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lightbox for image galleries
            lightbox.option({
                'resizeDuration': 200,
                'wrapAround': true,
                'alwaysShowNavOnTouchDevices': true
            });
            console.log('Project details page loaded with photo gallery support');
        });
    </script>
</body>
</html>
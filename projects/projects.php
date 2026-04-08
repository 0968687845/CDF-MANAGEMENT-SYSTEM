<?php
require_once 'functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$projects = getBeneficiaryProjects($_SESSION['user_id']);
$pageTitle = "My Projects - CDF Management System";

// Filter projects based on status
$activeProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'in-progress'; });
$completedProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'completed'; });
$delayedProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'delayed'; });

// Search and filter functionality
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$constituencyFilter = $_GET['constituency'] ?? '';

if ($searchTerm || $statusFilter !== 'all' || $constituencyFilter) {
    $filteredProjects = array_filter($projects, function($project) use ($searchTerm, $statusFilter, $constituencyFilter) {
        $matchesSearch = !$searchTerm || 
            stripos($project['title'], $searchTerm) !== false || 
            stripos($project['description'], $searchTerm) !== false;
        
        $matchesStatus = $statusFilter === 'all' || $project['status'] === $statusFilter;
        $matchesConstituency = !$constituencyFilter || ($project['constituency'] ?? '') === $constituencyFilter;
        
        return $matchesSearch && $matchesStatus && $matchesConstituency;
    });
} else {
    $filteredProjects = $projects;
}
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
        
        .project-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .project-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4" style="margin-top: 100px !important;">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">My Projects</h1>
                        <p class="text-muted">Manage and track all your CDF projects</p>
                    </div>
                    <a href="project_setup.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>New Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($projects); ?></div>
                    <div>Total Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                    <div class="stats-number"><?php echo count($activeProjects); ?></div>
                    <div>Active Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffd54f 100%); color: #000;">
                    <div class="stats-number"><?php echo count($completedProjects); ?></div>
                    <div>Completed</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stats-number"><?php echo count($delayedProjects); ?></div>
                    <div>Delayed</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="project_setup.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h6>Create New Project</h6>
                <small class="text-muted">Start a new CDF project</small>
            </a>
            <a href="progress/updates.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6>Update Progress</h6>
                <small class="text-muted">Report project progress</small>
            </a>
            <a href="documents/upload.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-upload"></i>
                </div>
                <h6>Upload Documents</h6>
                <small class="text-muted">Add project files</small>
            </a>
            <a href="reports/progress.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h6>Generate Reports</h6>
                <small class="text-muted">Create project reports</small>
            </a>
        </div>

        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Projects</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by project name or description..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all">All Status</option>
                        <option value="in-progress" <?php echo $statusFilter === 'in-progress' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="delayed" <?php echo $statusFilter === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                        <option value="not-started" <?php echo $statusFilter === 'not-started' ? 'selected' : ''; ?>>Not Started</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Constituency</label>
                    <select class="form-select" name="constituency">
                        <option value="">All Constituencies</option>
                        <option value="Lusaka" <?php echo $constituencyFilter === 'Lusaka' ? 'selected' : ''; ?>>Lusaka</option>
                        <option value="Copperbelt" <?php echo $constituencyFilter === 'Copperbelt' ? 'selected' : ''; ?>>Copperbelt</option>
                        <option value="Southern" <?php echo $constituencyFilter === 'Southern' ? 'selected' : ''; ?>>Southern</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Projects Grid -->
        <div class="row">
            <?php if (count($filteredProjects) > 0): ?>
                <?php foreach ($filteredProjects as $project): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card project-card">
                        <div class="project-status-badge">
                            <span class="badge bg-<?php 
                                switch($project['status']) {
                                    case 'completed': echo 'success'; break;
                                    case 'in-progress': echo 'warning'; break;
                                    case 'delayed': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>"><?php echo ucfirst($project['status']); ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($project['description']); ?></p>
                            
                            <div class="progress-section mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Overall Progress</small>
                                    <small class="fw-bold"><?php echo $project['progress']; ?>%</small>
                                </div>
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
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Budget</small>
                                    <div class="fw-bold">ZMW <?php echo number_format($project['budget'], 2); ?></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Timeline</small>
                                    <div class="fw-bold">
                                        <?php echo date('M j, Y', strtotime($project['start_date'])); ?> - 
                                        <?php echo date('M j, Y', strtotime($project['end_date'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <a href="progress/updates.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-edit me-1"></i>Update Progress
                                </a>
                                <a href="documents/upload.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-file-upload me-1"></i>Upload Files
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Projects Found</h4>
                        <p class="text-muted mb-4">
                            <?php echo ($searchTerm || $statusFilter !== 'all' || $constituencyFilter) ? 
                                'No projects match your search criteria.' : 
                                'You haven\'t created any projects yet.'; ?>
                        </p>
                        <?php if (!($searchTerm || $statusFilter !== 'all' || $constituencyFilter)): ?>
                            <a href="project_setup.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Create Your First Project
                            </a>
                        <?php else: ?>
                            <a href="projects.php" class="btn btn-outline-primary">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
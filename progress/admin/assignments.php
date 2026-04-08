<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$pageTitle = "Officer Assignments - CDF Management System";

// Get all projects and officers
$projects = getAllProjects();
$officers = getUsersByRole('officer');
$unassignedProjects = array_filter($projects, function($p) { return empty($p['officer_id']); });
$assignedProjects = array_filter($projects, function($p) { return !empty($p['officer_id']); });

// Handle assignment actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_officer'])) {
        $project_id = $_POST['project_id'];
        $officer_id = $_POST['officer_id'];
        
        if (assignOfficerToProject($project_id, $officer_id)) {
            $message = "Officer assigned successfully!";
            // Refresh data
            $projects = getAllProjects();
            $unassignedProjects = array_filter($projects, function($p) { return empty($p['officer_id']); });
            $assignedProjects = array_filter($projects, function($p) { return !empty($p['officer_id']); });
        } else {
            $error = "Failed to assign officer. Please try again.";
        }
    } elseif (isset($_POST['reassign_officer'])) {
        $project_id = $_POST['project_id'];
        $officer_id = $_POST['officer_id'];
        
        if (assignOfficerToProject($project_id, $officer_id)) {
            $message = "Officer reassigned successfully!";
            // Refresh data
            $projects = getAllProjects();
            $unassignedProjects = array_filter($projects, function($p) { return empty($p['officer_id']); });
            $assignedProjects = array_filter($projects, function($p) { return !empty($p['officer_id']); });
        } else {
            $error = "Failed to reassign officer. Please try again.";
        }
    } elseif (isset($_POST['remove_assignment'])) {
        $project_id = $_POST['project_id'];
        
        if (removeOfficerFromProject($project_id)) {
            $message = "Assignment removed successfully!";
            // Refresh data
            $projects = getAllProjects();
            $unassignedProjects = array_filter($projects, function($p) { return empty($p['officer_id']); });
            $assignedProjects = array_filter($projects, function($p) { return !empty($p['officer_id']); });
        } else {
            $error = "Failed to remove assignment. Please try again.";
        }
    }
}

// Function to assign officer to project
function assignOfficerToProject($project_id, $officer_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE projects SET officer_id = :officer_id, updated_at = NOW() WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':officer_id', $officer_id);
    $stmt->bindParam(':project_id', $project_id);
    
    if ($stmt->execute()) {
        // Create notification for officer
        createNotification($officer_id, 'New Project Assignment', 'You have been assigned to monitor a new project.');
        
        // Log activity
        logActivity($_SESSION['user_id'], 'officer_assignment', 'Assigned officer to project ID: ' . $project_id);
        return true;
    }
    
    return false;
}

// Function to remove officer from project
function removeOfficerFromProject($project_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE projects SET officer_id = NULL, updated_at = NOW() WHERE id = :project_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'officer_removal', 'Removed officer from project ID: ' . $project_id);
        return true;
    }
    
    return false;
}

// Get officer workload statistics
$officerWorkload = [];
foreach ($officers as $officer) {
    $assignedCount = count(array_filter($projects, function($p) use ($officer) { 
        return $p['officer_id'] == $officer['id']; 
    }));
    $officerWorkload[$officer['id']] = $assignedCount;
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
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .assignment-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .officer-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .workload-badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
        }
        
        .project-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 3px solid var(--primary);
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            margin: 0.5rem 0;
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .assignment-form {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
    </style>
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
                            <li><a class="dropdown-item active" href="assignments.php">
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
                    <h1><i class="fas fa-user-tie me-3"></i>Officer Assignments</h1>
                    <p class="lead mb-0">Manage M&E Officer project assignments and workload distribution</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="projects.php" class="btn btn-light me-2">
                        <i class="fas fa-project-diagram me-2"></i>All Projects
                    </a>
                    <a href="../admin_dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($officers); ?></div>
                    <div>Available Officers</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                    <div class="stats-number"><?php echo count($assignedProjects); ?></div>
                    <div>Assigned Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffd54f 100%); color: #000;">
                    <div class="stats-number"><?php echo count($unassignedProjects); ?></div>
                    <div>Unassigned Projects</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stats-number">
                        <?php echo count($projects) > 0 ? round((count($assignedProjects) / count($projects)) * 100) : 0; ?>%
                    </div>
                    <div>Assignment Rate</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#unassigned-projects" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h6>Unassigned Projects</h6>
                <small class="text-muted"><?php echo count($unassignedProjects); ?> need assignment</small>
            </a>
            <a href="#officers-overview" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h6>Officers Overview</h6>
                <small class="text-muted">View officer workload</small>
            </a>
            <a href="#quick-assignment" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h6>Quick Assignment</h6>
                <small class="text-muted">Assign multiple projects</small>
            </a>
            <a href="reports.php?type=assignments" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h6>Assignment Reports</h6>
                <small class="text-muted">Generate assignment reports</small>
            </a>
        </div>

        <div class="row">
            <!-- Unassigned Projects -->
            <div class="col-lg-6 mb-4">
                <div class="assignment-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Unassigned Projects
                            <span class="badge bg-warning text-dark ms-2"><?php echo count($unassignedProjects); ?></span>
                        </h5>

                        <?php if (count($unassignedProjects) > 0): ?>
                            <?php foreach (array_slice($unassignedProjects, 0, 5) as $project): ?>
                            <div class="project-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($project['title']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        switch($project['status']) {
                                            case 'completed': echo 'success'; break;
                                            case 'in-progress': echo 'primary'; break;
                                            case 'delayed': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>"><?php echo ucfirst($project['status']); ?></span>
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
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo $project['progress']; ?>% complete</small>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <div class="input-group input-group-sm" style="width: 200px;">
                                            <select class="form-select form-select-sm" name="officer_id" required>
                                                <option value="">Select Officer</option>
                                                <?php foreach ($officers as $officer): ?>
                                                <option value="<?php echo $officer['id']; ?>">
                                                    <?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>
                                                    (<?php echo $officerWorkload[$officer['id']] ?? 0; ?> projects)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_officer" class="btn btn-primary btn-sm">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($unassignedProjects) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="#unassigned-projects" class="btn btn-outline-primary btn-sm">
                                    View All <?php echo count($unassignedProjects); ?> Unassigned Projects
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">All Projects Assigned!</h5>
                                <p class="text-muted">Great job! All projects have officers assigned.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Officers Overview -->
            <div class="col-lg-6 mb-4">
                <div class="assignment-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-users me-2"></i>
                            M&E Officers Overview
                        </h5>

                        <?php if (count($officers) > 0): ?>
                            <?php foreach ($officers as $officer): 
                                $assignedCount = $officerWorkload[$officer['id']] ?? 0;
                                $workloadPercentage = count($projects) > 0 ? ($assignedCount / count($projects)) * 100 : 0;
                            ?>
                            <div class="officer-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($officer['first_name'], 0, 1) . substr($officer['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($officer['email'] ?? 'No email'); ?></small>
                                        </div>
                                    </div>
                                    <span class="workload-badge badge bg-<?php 
                                        echo $assignedCount == 0 ? 'secondary' : 
                                             ($assignedCount <= 3 ? 'success' : 
                                             ($assignedCount <= 6 ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo $assignedCount; ?> projects
                                    </span>
                                </div>
                                
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-<?php 
                                        echo $assignedCount == 0 ? 'secondary' : 
                                             ($assignedCount <= 3 ? 'success' : 
                                             ($assignedCount <= 6 ? 'warning' : 'danger')); 
                                    ?>" style="width: <?php echo min($workloadPercentage, 100); ?>%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Workload: 
                                        <span class="fw-bold"><?php echo round($workloadPercentage, 1); ?>%</span>
                                    </small>
                                    <div>
                                        <small class="text-muted me-2">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($officer['phone'] ?? 'N/A'); ?>
                                        </small>
                                        <a href="mailto:<?php echo htmlspecialchars($officer['email'] ?? ''); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No M&E Officers</h5>
                                <p class="text-muted mb-3">No M&E officers are registered in the system.</p>
                                <a href="users.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Add Officers
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Assignment Section -->
        <div class="assignment-card">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Assignment Tool
                </h5>
                
                <div class="assignment-form">
                    <form method="POST" id="quickAssignmentForm">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label">Select Projects</label>
                                <select class="form-select" multiple size="5" id="projectSelection" name="projects[]">
                                    <?php foreach ($unassignedProjects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo htmlspecialchars($project['title']); ?> 
                                        (<?php echo htmlspecialchars($project['constituency'] ?? 'Unknown'); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple projects</div>
                            </div>
                            
                            <div class="col-md-5">
                                <label class="form-label">Assign to Officer</label>
                                <select class="form-select" name="officer_id" required>
                                    <option value="">Select Officer</option>
                                    <?php foreach ($officers as $officer): ?>
                                    <option value="<?php echo $officer['id']; ?>">
                                        <?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>
                                        (<?php echo $officerWorkload[$officer['id']] ?? 0; ?> current projects)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Officers with lower workload are recommended</div>
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="assign_officer" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i>Assign
                                </button>
                            </div>
                        </div>
                    </form>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quick assignment form handling
            const quickAssignmentForm = document.getElementById('quickAssignmentForm');
            if (quickAssignmentForm) {
                quickAssignmentForm.addEventListener('submit', function(e) {
                    const selectedProjects = document.getElementById('projectSelection').selectedOptions;
                    const officerSelect = document.querySelector('select[name="officer_id"]');
                    
                    if (selectedProjects.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one project to assign.');
                        return;
                    }
                    
                    if (!officerSelect.value) {
                        e.preventDefault();
                        alert('Please select an officer to assign the projects to.');
                        return;
                    }
                    
                    if (confirm(`Assign ${selectedProjects.length} project(s) to the selected officer?`)) {
                        // Form will submit normally
                    } else {
                        e.preventDefault();
                    }
                });
            }

            // Smooth scroll for quick action links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
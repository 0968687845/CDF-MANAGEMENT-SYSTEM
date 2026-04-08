<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Auto-update all project statuses using ML analysis
autoUpdateAllProjectStatuses();

// Get all projects
$projects = getAllProjects();
$userData = getUserData();
$pageTitle = "Project Management - CDF Management System";

// Filter projects based on status
$activeProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'in-progress'; });
$completedProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'completed'; });
$delayedProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'delayed'; });
$planningProjects = array_filter($projects, function($p) { return ($p['status'] ?? '') === 'planning'; });

// Search and filter functionality
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$constituencyFilter = $_GET['constituency'] ?? '';

if ($searchTerm || $statusFilter !== 'all' || $constituencyFilter) {
    $filteredProjects = array_filter($projects, function($project) use ($searchTerm, $statusFilter, $constituencyFilter) {
        $matchesSearch = !$searchTerm || 
            stripos($project['title'], $searchTerm) !== false || 
            stripos($project['description'], $searchTerm) !== false ||
            stripos($project['beneficiary_name'] ?? '', $searchTerm) !== false;
        
        $matchesStatus = $statusFilter === 'all' || $project['status'] === $statusFilter;
        $matchesConstituency = !$constituencyFilter || ($project['constituency'] ?? '') === $constituencyFilter;
        
        return $matchesSearch && $matchesStatus && $matchesConstituency;
    });
} else {
    $filteredProjects = $projects;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $selectedProjects = $_POST['selected_projects'] ?? [];
    $bulkAction = $_POST['bulk_action_type'] ?? '';
    
    if (!empty($selectedProjects) && $bulkAction) {
        switch($bulkAction) {
            case 'assign_officer':
                // Redirect to assignment page with selected projects
                $_SESSION['selected_projects'] = $selectedProjects;
                redirect('assign_officer.php');
                break;
            case 'export':
                // Handle export functionality - CSV export
                if (!empty($selectedProjects)) {
                    exportProjectsToCSV($selectedProjects);
                }
                break;
            case 'delete':
                // Handle delete functionality
                if (!empty($selectedProjects)) {
                    deleteProjects($selectedProjects);
                    $_SESSION['success'] = count($selectedProjects) . ' project(s) deleted successfully.';
                    redirect('projects.php');
                }
                break;
        }
    }
}

// Function to export projects to CSV
function exportProjectsToCSV($projectIds) {
    global $pdo;
    
    if (empty($projectIds)) {
        $_SESSION['error'] = 'No projects selected for export';
        return;
    }
    
    try {
        // Get project details
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $query = "SELECT id, title, description, budget, status, progress, beneficiary_name, constituency 
                 FROM projects WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($query);
        $stmt->execute($projectIds);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="projects_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, ['Project ID', 'Title', 'Description', 'Budget', 'Status', 'Progress %', 'Beneficiary', 'Constituency']);
        
        // Write data
        foreach ($projects as $project) {
            fputcsv($output, [
                $project['id'],
                $project['title'],
                substr($project['description'], 0, 100),
                $project['budget'],
                $project['status'],
                $project['progress'],
                $project['beneficiary_name'],
                $project['constituency']
            ]);
        }
        
        fclose($output);
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error exporting projects: ' . $e->getMessage();
    }
}

// Function to delete projects
function deleteProjects($projectIds) {
    global $pdo;
    
    if (empty($projectIds)) {
        $_SESSION['error'] = 'No projects selected for deletion';
        return false;
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        foreach ($projectIds as $project_id) {
            // Delete from project_progress first (child table)
            $delete_progress = "DELETE FROM project_progress WHERE project_id = ?";
            $stmt = $pdo->prepare($delete_progress);
            $stmt->execute([$project_id]);
            
            // Delete from projects table
            $delete_project = "DELETE FROM projects WHERE id = ?";
            $stmt = $pdo->prepare($delete_project);
            $stmt->execute([$project_id]);
        }
        
        // Commit transaction
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        $_SESSION['error'] = 'Error deleting projects: ' . $e->getMessage();
        return false;
    }
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
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 1rem;
        }
        
        .user-avatar-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.8rem;
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
                            <li><a class="dropdown-item active" href="projects.php">
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
                                        <div class="user-avatar-small">
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
                    <h1><i class="fas fa-project-diagram me-3"></i>Project Management</h1>
                    <p class="lead mb-0">Manage and monitor all CDF projects in the system</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="project_reports.php" class="btn btn-light me-2">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                    <a href="../admin_dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
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
            <a href="project_approval.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h6>Approve Projects</h6>
                <small class="text-muted">Review pending project approvals</small>
            </a>
            <a href="assign_officer.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h6>Assign Officers</h6>
                <small class="text-muted">Assign M&E officers to projects</small>
            </a>
            <a href="project_reports.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h6>Generate Reports</h6>
                <small class="text-muted">Create project performance reports</small>
            </a>
            <a href="project_analytics.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6>View Analytics</h6>
                <small class="text-muted">Project performance analytics</small>
            </a>
        </div>

        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Projects</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by project, beneficiary, or description..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all">All Status</option>
                        <option value="planning" <?php echo $statusFilter === 'planning' ? 'selected' : ''; ?>>Planning</option>
                        <option value="in-progress" <?php echo $statusFilter === 'in-progress' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="delayed" <?php echo $statusFilter === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Constituency</label>
                    <select class="form-select" name="constituency">
                        <option value="">All Constituencies</option>
                        <?php 
                        $constituencies = array_unique(array_column($projects, 'constituency'));
                        foreach ($constituencies as $constituency): 
                            if (!empty($constituency)):
                        ?>
                        <option value="<?php echo $constituency; ?>" <?php echo $constituencyFilter === $constituency ? 'selected' : ''; ?>>
                            <?php echo $constituency; ?>
                        </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="table-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>All Projects (<?php echo count($filteredProjects); ?>)
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="bulkAction" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="assign_officer">Assign Officer</option>
                            <option value="export">Export Selected</option>
                            <option value="delete" class="text-danger">Delete Selected</option>
                        </select>
                        <button class="btn btn-primary btn-sm" id="applyBulkAction">
                            <i class="fas fa-play me-1"></i>Apply
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <form method="POST" id="bulkActionForm">
                        <input type="hidden" name="bulk_action" value="1">
                        <input type="hidden" name="bulk_action_type" id="bulkActionType" value="">
                        
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Project Details</th>
                                    <th>Beneficiary</th>
                                    <th>M&E Officer</th>
                                    <th>Progress</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                    <th>Timeline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($filteredProjects) > 0): ?>
                                    <?php foreach ($filteredProjects as $project): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="project-checkbox" name="selected_projects[]" value="<?php echo $project['id']; ?>">
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 60)); ?>...</small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($project['location'] ?? 'N/A'); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar-small me-2">
                                                    <?php 
                                                    $beneficiaryName = $project['beneficiary_name'] ?? 'Unassigned';
                                                    echo strtoupper(substr($beneficiaryName, 0, 1) . substr($beneficiaryName, -1, 1)); 
                                                    ?>
                                                </div>
                                                <div>
                                                    <small class="fw-bold"><?php echo htmlspecialchars($beneficiaryName); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($project['officer_name'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar-small me-2" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                                                        <?php 
                                                        $officerName = $project['officer_name'];
                                                        echo strtoupper(substr($officerName, 0, 1) . substr($officerName, -1, 1)); 
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <small class="fw-bold"><?php echo htmlspecialchars($officerName); ?></small>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress me-2" style="width: 80px; height: 8px;">
                                                    <div class="progress-bar bg-<?php 
                                                        switch($project['status']) {
                                                            case 'completed': echo 'success'; break;
                                                            case 'in-progress': echo 'primary'; break;
                                                            case 'delayed': echo 'danger'; break;
                                                            default: echo 'info';
                                                        }
                                                    ?>" style="width: <?php echo $project['progress']; ?>%"></div>
                                                </div>
                                                <small class="fw-bold"><?php echo $project['progress']; ?>%</small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>ZMW <?php echo number_format($project['budget'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($project['status']) {
                                                    case 'completed': echo 'success'; break;
                                                    case 'in-progress': echo 'primary'; break;
                                                    case 'delayed': echo 'danger'; break;
                                                    case 'planning': echo 'secondary'; break;
                                                    default: echo 'info';
                                                }
                                            ?>"><?php echo ucfirst($project['status']); ?></span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('M j, Y', strtotime($project['start_date'])); ?> 
                                                <br>to<br>
                                                <?php echo date('M j, Y', strtotime($project['end_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-warning" title="Edit Project">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="assign_officer.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-info" title="Assign Officer">
                                                    <i class="fas fa-user-tie"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                                            <h4 class="text-muted">No Projects Found</h4>
                                            <p class="text-muted mb-4">
                                                <?php echo ($searchTerm || $statusFilter !== 'all' || $constituencyFilter) ? 
                                                    'No projects match your search criteria.' : 
                                                    'No projects have been registered yet.'; ?>
                                            </p>
                                            <?php if ($searchTerm || $statusFilter !== 'all' || $constituencyFilter): ?>
                                                <a href="projects.php" class="btn btn-outline-primary">
                                                    <i class="fas fa-times me-2"></i>Clear Filters
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>

                <!-- Pagination -->
                <nav aria-label="Project pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bulk actions
            const selectAll = document.getElementById('selectAll');
            const projectCheckboxes = document.querySelectorAll('.project-checkbox');
            const bulkAction = document.getElementById('bulkAction');
            const applyBulkAction = document.getElementById('applyBulkAction');
            const bulkActionType = document.getElementById('bulkActionType');
            const bulkActionForm = document.getElementById('bulkActionForm');

            selectAll.addEventListener('change', function() {
                projectCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
            });

            applyBulkAction.addEventListener('click', function() {
                const selectedProjects = Array.from(projectCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                
                if (selectedProjects.length === 0) {
                    alert('Please select at least one project.');
                    return;
                }
                
                if (!bulkAction.value) {
                    alert('Please select a bulk action.');
                    return;
                }
                
                // Add confirmation for delete action
                if (bulkAction.value === 'delete') {
                    if (!confirm(`Are you sure you want to delete ${selectedProjects.length} project(s)? This action cannot be undone.`)) {
                        return;
                    }
                }
                
                bulkActionType.value = bulkAction.value;
                bulkActionForm.submit();
            });

            // Auto-submit filter form when select changes
            document.querySelectorAll('select[name="status"], select[name="constituency"]').forEach(select => {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        });
    </script>
</body>
</html>
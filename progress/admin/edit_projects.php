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
$pageTitle = "Edit Project - CDF Management System";

// Get all beneficiaries and officers for dropdowns
$beneficiaries = getUsersByRole('beneficiary');
$officers = getUsersByRole('officer');

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [
        'project_id' => $project_id,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'beneficiary_id' => $_POST['beneficiary_id'],
        'officer_id' => $_POST['officer_id'] ?: null,
        'budget' => $_POST['budget'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'location' => $_POST['location'],
        'constituency' => $_POST['constituency'],
        'category' => $_POST['category'],
        'status' => $_POST['status'],
        'progress' => $_POST['progress']
    ];
    
    $result = updateProject($update_data);
    if ($result === true) {
        $message = "Project updated successfully!";
        // Refresh project data
        $project = getProjectById($project_id);
    } else {
        $error = $result;
    }
}

// Function to update project (add this to functions.php if not exists)
function updateProject($data) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE projects SET 
        title = :title,
        description = :description,
        beneficiary_id = :beneficiary_id,
        officer_id = :officer_id,
        budget = :budget,
        start_date = :start_date,
        end_date = :end_date,
        location = :location,
        constituency = :constituency,
        category = :category,
        status = :status,
        progress = :progress,
        updated_at = NOW()
        WHERE id = :project_id";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':beneficiary_id', $data['beneficiary_id']);
    $stmt->bindParam(':officer_id', $data['officer_id']);
    $stmt->bindParam(':budget', $data['budget']);
    $stmt->bindParam(':start_date', $data['start_date']);
    $stmt->bindParam(':end_date', $data['end_date']);
    $stmt->bindParam(':location', $data['location']);
    $stmt->bindParam(':constituency', $data['constituency']);
    $stmt->bindParam(':category', $data['category']);
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':progress', $data['progress']);
    $stmt->bindParam(':project_id', $data['project_id']);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'project_update', 'Updated project: ' . $data['title']);
        return true;
    }
    
    return "Failed to update project. Please try again.";
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
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .project-header {
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
            margin-bottom: 1.5rem;
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
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.3);
            color: white;
        }
        
        .info-badge {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
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
                                        <div class="user-avatar">
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
                    <h1><i class="fas fa-edit me-3"></i>Edit Project</h1>
                    <p class="lead mb-0">Update project information and assignments</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="projects.php" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Projects
                    </a>
                    <a href="project_details.php?id=<?php echo $project_id; ?>" class="btn btn-outline-light">
                        <i class="fas fa-eye me-2"></i>View Details
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

        <div class="row">
            <!-- Project Overview -->
            <div class="col-lg-4 mb-4">
                <div class="project-header">
                    <div class="project-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($project['title']); ?></h3>
                    <p class="mb-0">Project ID: #<?php echo $project['id']; ?></p>
                </div>

                <!-- Project Stats -->
                <div class="stats-card">
                    <div class="stats-number"><?php echo $project['progress']; ?>%</div>
                    <div>Completion Progress</div>
                </div>

                <!-- Project Information -->
                <div class="form-card">
                    <div class="card-body">
                        <h6 class="section-title">Project Information</h6>
                        
                        <div class="mb-3">
                            <small class="text-muted">Created</small>
                            <div class="fw-bold"><?php echo date('M j, Y g:i A', strtotime($project['created_at'])); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Last Updated</small>
                            <div class="fw-bold"><?php echo date('M j, Y g:i A', strtotime($project['updated_at'])); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Current Status</small>
                            <div>
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
                        
                        <div class="mb-3">
                            <small class="text-muted">Category</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($project['category'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Location</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($project['location'] ?? 'Not specified'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="progress-container">
                    <h6 class="mb-3">Progress Overview</h6>
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
                        <span class="fw-bold fs-4"><?php echo $project['progress']; ?>%</span>
                        <small class="text-muted d-block">Complete</small>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="card-body">
                        <form method="POST" id="editProjectForm">
                            <h4 class="section-title">Basic Information</h4>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Project Title *</label>
                                        <input type="text" class="form-control" name="title" 
                                               value="<?php echo htmlspecialchars($project['title']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Project Description *</label>
                                    <textarea class="form-control" name="description" rows="4" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category *</label>
                                        <select class="form-select" name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="infrastructure" <?php echo ($project['category'] ?? '') === 'infrastructure' ? 'selected' : ''; ?>>Infrastructure</option>
                                            <option value="education" <?php echo ($project['category'] ?? '') === 'education' ? 'selected' : ''; ?>>Education</option>
                                            <option value="health" <?php echo ($project['category'] ?? '') === 'health' ? 'selected' : ''; ?>>Health</option>
                                            <option value="agriculture" <?php echo ($project['category'] ?? '') === 'agriculture' ? 'selected' : ''; ?>>Agriculture</option>
                                            <option value="water-sanitation" <?php echo ($project['category'] ?? '') === 'water-sanitation' ? 'selected' : ''; ?>>Water & Sanitation</option>
                                            <option value="community-development" <?php echo ($project['category'] ?? '') === 'community-development' ? 'selected' : ''; ?>>Community Development</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Location *</label>
                                        <input type="text" class="form-control" name="location" 
                                               value="<?php echo htmlspecialchars($project['location']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title">Budget & Timeline</h4>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Budget (ZMW) *</label>
                                        <input type="number" class="form-control" name="budget" step="0.01" 
                                               value="<?php echo htmlspecialchars($project['budget']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Constituency *</label>
                                        <select class="form-select" name="constituency" required>
                                            <option value="">Select Constituency</option>
                                            <?php foreach (getConstituencies() as $constituency): ?>
                                            <option value="<?php echo $constituency; ?>" 
                                                <?php echo ($project['constituency'] ?? '') === $constituency ? 'selected' : ''; ?>>
                                                <?php echo $constituency; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date *</label>
                                        <input type="date" class="form-control" name="start_date" 
                                               value="<?php echo htmlspecialchars($project['start_date']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Date *</label>
                                        <input type="date" class="form-control" name="end_date" 
                                               value="<?php echo htmlspecialchars($project['end_date']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title">Assignments & Status</h4>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Beneficiary *</label>
                                        <select class="form-select" name="beneficiary_id" required>
                                            <option value="">Select Beneficiary</option>
                                            <?php foreach ($beneficiaries as $beneficiary): ?>
                                            <option value="<?php echo $beneficiary['id']; ?>" 
                                                <?php echo $project['beneficiary_id'] == $beneficiary['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . $beneficiary['last_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">M&E Officer</label>
                                        <select class="form-select" name="officer_id">
                                            <option value="">Unassigned</option>
                                            <?php foreach ($officers as $officer): ?>
                                            <option value="<?php echo $officer['id']; ?>" 
                                                <?php echo $project['officer_id'] == $officer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Project Status *</label>
                                        <select class="form-select" name="status" required>
                                            <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                            <option value="in-progress" <?php echo $project['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="delayed" <?php echo $project['status'] === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Progress (%) *</label>
                                        <input type="range" class="form-range" name="progress" min="0" max="100" 
                                               value="<?php echo $project['progress']; ?>" oninput="updateProgressValue(this.value)">
                                        <div class="text-center">
                                            <span id="progressValue" class="fw-bold fs-5"><?php echo $project['progress']; ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="projects.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-custom-primary">
                                    <i class="fas fa-save me-2"></i>Update Project
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateProgressValue(value) {
            document.getElementById('progressValue').textContent = value + '%';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.getElementById('editProjectForm');
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Check required fields
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                // Check date validity
                const startDate = new Date(form.querySelector('input[name="start_date"]').value);
                const endDate = new Date(form.querySelector('input[name="end_date"]').value);
                
                if (startDate >= endDate) {
                    valid = false;
                    alert('End date must be after start date.');
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields correctly.');
                }
            });

            // Real-time progress update
            const progressRange = document.querySelector('input[name="progress"]');
            if (progressRange) {
                progressRange.addEventListener('input', function() {
                    updateProgressValue(this.value);
                });
            }
        });
    </script>
</body>
</html>
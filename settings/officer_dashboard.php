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

// Get officer's assigned projects for scheduling visits
$projects = getOfficerProjects($_SESSION['user_id']);

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Create site_visits table if it doesn't exist (with correct column names)
createSiteVisitsTable($pdo);

function createSiteVisitsTable($pdo) {
    try {
        $query = "CREATE TABLE IF NOT EXISTS site_visits (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            project_id INT(11) NOT NULL,
            officer_id INT(11) NOT NULL,
            visit_date DATE NOT NULL,
            visit_time TIME NOT NULL,
            location VARCHAR(255) NOT NULL,
            purpose TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'scheduled',
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (officer_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_project_id (project_id),
            INDEX idx_officer_id (officer_id),
            INDEX idx_visit_date (visit_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($query);
        
        // Also create notifications table if it doesn't exist
        $notifications_query = "CREATE TABLE IF NOT EXISTS notifications (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($notifications_query);
        
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
    }
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $project_id = trim($_POST['project_id'] ?? '');
    $visit_date = trim($_POST['visit_date'] ?? '');
    $visit_time = trim($_POST['visit_time'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($project_id)) {
        $errors[] = "Please select a project";
    }
    
    if (empty($visit_date)) {
        $errors[] = "Visit date is required";
    } elseif (strtotime($visit_date) < strtotime('today')) {
        $errors[] = "Visit date cannot be in the past";
    }
    
    if (empty($visit_time)) {
        $errors[] = "Visit time is required";
    }
    
    if (empty($purpose)) {
        $errors[] = "Purpose of visit is required";
    } elseif (strlen($purpose) < 10) {
        $errors[] = "Please provide a more detailed purpose (minimum 10 characters)";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    // Verify project belongs to officer
    $valid_project = false;
    $selected_project = null;
    foreach ($projects as $project) {
        if ($project['id'] == $project_id) {
            $valid_project = true;
            $selected_project = $project;
            break;
        }
    }
    
    if (!$valid_project) {
        $errors[] = "Invalid project selection";
    }
    
    // If no errors, schedule the visit
    if (empty($errors)) {
        try {
            // Begin transaction for multiple database operations
            $pdo->beginTransaction();
            
            // Insert site visit with correct column names
            $query = "INSERT INTO site_visits SET 
                project_id = :project_id,
                officer_id = :officer_id,
                visit_date = :visit_date,
                visit_time = :visit_time,
                location = :location,
                purpose = :purpose,
                status = 'scheduled'";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $stmt->bindParam(':officer_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':visit_date', $visit_date);
            $stmt->bindParam(':visit_time', $visit_time);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':purpose', $purpose);
            
            if ($stmt->execute()) {
                $visit_id = $pdo->lastInsertId();
                
                // Create notification for the beneficiary if project has a beneficiary
                if ($selected_project && isset($selected_project['beneficiary_id']) && $selected_project['beneficiary_id']) {
                    $notification_title = 'Site Visit Scheduled';
                    $notification_message = 'A site visit has been scheduled for your project "' . $selected_project['title'] . '" on ' . date('M j, Y', strtotime($visit_date)) . ' at ' . $visit_time . '. Purpose: ' . $purpose;
                    
                    $notification_query = "INSERT INTO notifications SET 
                        user_id = :user_id,
                        title = :title,
                        message = :message";
                    
                    $notification_stmt = $pdo->prepare($notification_query);
                    $notification_stmt->bindParam(':user_id', $selected_project['beneficiary_id'], PDO::PARAM_INT);
                    $notification_stmt->bindParam(':title', $notification_title);
                    $notification_stmt->bindParam(':message', $notification_message);
                    $notification_stmt->execute();
                }
                
                // Log activity
                $log_query = "INSERT INTO activity_log SET 
                    user_id = :user_id,
                    action = :action,
                    description = :description,
                    ip_address = :ip_address";
                
                $log_stmt = $pdo->prepare($log_query);
                $action = 'site_visit_created';
                $description = 'Scheduled site visit for project: ' . $selected_project['title'];
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                
                $log_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $log_stmt->bindParam(':action', $action);
                $log_stmt->bindParam(':description', $description);
                $log_stmt->bindParam(':ip_address', $ip_address);
                $log_stmt->execute();
                
                // Commit transaction
                $pdo->commit();
                
                $success = 'Site visit scheduled successfully!' . 
                          ($selected_project && isset($selected_project['beneficiary_id']) ? ' The beneficiary has been notified.' : '');
                
                // Clear form fields
                $_POST = [];
                
            } else {
                $pdo->rollBack();
                $errors[] = 'Failed to schedule site visit. Please try again.';
            }
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Error scheduling site visit: " . $e->getMessage());
            
            // More specific error messages based on error code
            if ($e->getCode() == '42S02') {
                $errors[] = 'Database table missing. Please contact system administrator.';
            } elseif ($e->getCode() == '42S22') {
                $errors[] = 'Database column error: ' . $e->getMessage();
            } else {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Schedule Site Visit - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Schedule Site Visit for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            font-size: 1.25rem;
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

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white) !important;
        }

        .dashboard-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: 3rem 0 2.5rem;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.05)"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
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

        .profile-info h1 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
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
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            background: var(--secondary-gradient);
            color: var(--dark);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.7);
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-2px);
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
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .required::after {
            content: " *";
            color: var(--danger);
        }

        .project-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--primary);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
        }

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
        }

        .dropdown-item.active {
            background-color: var(--primary);
            color: white;
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
            
            .action-buttons {
                justify-content: center;
            }
            
            .form-card {
                padding: 1.5rem;
            }
        }

        /* Form validation styles */
        .is-invalid {
            border-color: var(--danger) !important;
        }

        .invalid-feedback {
            display: none;
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .is-invalid ~ .invalid-feedback {
            display: block;
        }

        /* Character count styles */
        .char-count {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .char-count.warning {
            color: var(--warning);
        }

        .char-count.error {
            color: var(--danger);
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
                        <a class="nav-link" href="../officer_dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item active" href="schedule.php">
                                <i class="fas fa-plus-circle me-2"></i>Schedule Visit
                            </a></li>
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
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
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: var(--secondary-gradient); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
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
                    <h1>Schedule Site Visit</h1>
                    <p class="lead">Schedule a new site visit for monitoring and evaluation</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-outline-custom">
                    <i class="fas fa-arrow-left me-2"></i>Back to Visits
                </a>
                <a href="../projects/index.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>View Projects
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="form-card">
                    <h4 class="mb-4"><i class="fas fa-calendar-plus me-2"></i>Schedule New Site Visit</h4>
                    
                    <form method="POST" id="scheduleForm" novalidate>
                        <!-- Project Selection -->
                        <div class="mb-4">
                            <label for="project_id" class="form-label required">Select Project</label>
                            <select class="form-select" id="project_id" name="project_id" required 
                                    onchange="updateProjectInfo(this.value)">
                                <option value="">Choose a project...</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>" 
                                            <?php echo (isset($_POST['project_id']) && $_POST['project_id'] == $project['id']) ? 'selected' : ''; ?>
                                            data-location="<?php echo htmlspecialchars($project['location'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($project['title']); ?> - 
                                        <?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a project</div>
                            <div class="form-text">Select the project you want to schedule a site visit for</div>
                        </div>

                        <!-- Project Information (Dynamic) -->
                        <div id="projectInfo" class="project-info-card" style="display: none;">
                            <h6><i class="fas fa-info-circle me-2"></i>Project Information</h6>
                            <div id="projectDetails" class="mt-2"></div>
                        </div>

                        <!-- Date and Time -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label required">Visit Date</label>
                                <input type="date" class="form-control" id="visit_date" name="visit_date" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       value="<?php echo $_POST['visit_date'] ?? ''; ?>" required>
                                <div class="invalid-feedback">Please select a valid visit date</div>
                                <div class="form-text">Select the date for the site visit</div>
                            </div>
                            <div class="col-md-6">
                                <label for="visit_time" class="form-label required">Visit Time</label>
                                <input type="time" class="form-control" id="visit_time" name="visit_time" 
                                       value="<?php echo $_POST['visit_time'] ?? '09:00'; ?>" required>
                                <div class="invalid-feedback">Please select a visit time</div>
                                <div class="form-text">Select the time for the site visit</div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="mb-4">
                            <label for="location" class="form-label required">Visit Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   placeholder="Enter the exact location or address for the site visit"
                                   value="<?php echo $_POST['location'] ?? ''; ?>" required>
                            <div class="invalid-feedback">Please provide a location for the site visit</div>
                            <div class="form-text">Provide the specific location where the visit will take place</div>
                        </div>

                        <!-- Purpose -->
                        <div class="mb-4">
                            <label for="purpose" class="form-label required">Purpose of Visit</label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="4" 
                                      placeholder="Describe the purpose and objectives of this site visit..."
                                      required minlength="10"><?php echo $_POST['purpose'] ?? ''; ?></textarea>
                            <div class="invalid-feedback">Please provide a detailed purpose (minimum 10 characters)</div>
                            <div class="form-text">Minimum 10 characters. Describe what you plan to inspect or evaluate.</div>
                            <div class="char-count" id="charCountContainer">
                                Character count: <span id="charCount">0</span>/<span id="charMin">10</span>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional information or special requirements..."><?php echo $_POST['notes'] ?? ''; ?></textarea>
                            <div class="form-text">Optional: Include any special requirements or additional information</div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-calendar-check me-2"></i>Schedule Visit
                            </button>
                            <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Reset Form
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Quick Tips -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-lightbulb me-2"></i>Scheduling Tips</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-clock text-warning me-2"></i>Best Times</h6>
                            <p class="small text-muted">Schedule visits during working hours (8:00 AM - 4:00 PM) for better coordination with beneficiaries.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-map-marker-alt text-info me-2"></i>Location Details</h6>
                            <p class="small text-muted">Provide precise location details including landmarks to ensure easy navigation.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-bullseye text-success me-2"></i>Clear Objectives</h6>
                            <p class="small text-muted">Define clear purpose and objectives to make the visit productive and focused.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-bell text-primary me-2"></i>Notifications</h6>
                            <p class="small text-muted">Beneficiaries will be automatically notified when you schedule a visit.</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Projects -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-project-diagram me-2"></i>Your Projects</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($projects) > 0): ?>
                            <?php foreach (array_slice($projects, 0, 3) as $project): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($project['title']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $project['status'] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($projects) > 3): ?>
                                <div class="text-center">
                                    <a href="../projects/index.php" class="btn btn-sm btn-outline-primary">View All Projects</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No projects assigned</p>
                            <div class="text-center">
                                <a href="../projects/request.php" class="btn btn-sm btn-primary-custom">Request Projects</a>
                            </div>
                        <?php endif; ?>
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

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Project data for dynamic updates
        const projects = {
            <?php foreach ($projects as $project): ?>
                <?php echo $project['id']; ?>: {
                    title: "<?php echo addslashes($project['title']); ?>",
                    beneficiary: "<?php echo addslashes($project['beneficiary_name'] ?? 'Unassigned'); ?>",
                    location: "<?php echo addslashes($project['location'] ?? 'Not specified'); ?>",
                    status: "<?php echo $project['status'] ?? 'Unknown'; ?>",
                    progress: "<?php echo $project['progress'] ?? 0; ?>",
                    budget: "<?php echo number_format($project['budget'] ?? 0, 2); ?>",
                    description: "<?php echo addslashes(substr($project['description'] ?? 'No description', 0, 100)); ?>"
                }<?php echo ($project !== end($projects)) ? ',' : ''; ?>
            <?php endforeach; ?>
        

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Highlight active menu item
            highlightActiveMenuItem();

            // Initialize form validation
            initializeFormValidation();

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('visit_date').min = today;

            // Initialize character count
            updateCharCount();

            // Initialize project info if project is already selected
            const projectId = document.getElementById('project_id').value;
            if (projectId) {
                updateProjectInfo(projectId);
            }

            console.log('Schedule Site Visit page loaded successfully');
        });

        // Function to highlight active menu item
        function highlightActiveMenuItem() {
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.nav-link, .dropdown-item');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && (href === currentPage || href.includes('schedule.php'))) {
                    item.classList.add('active');
                    
                    // Also highlight parent dropdown if this is a dropdown item
                    const dropdown = item.closest('.dropdown-menu');
                    if (dropdown) {
                        const dropdownToggle = dropdown.previousElementSibling;
                        if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                            dropdownToggle.classList.add('active');
                        }
                    }
                }
            });
        }

        // Update project information when project is selected
        function updateProjectInfo(projectId) {
            const projectInfo = document.getElementById('projectInfo');
            const projectDetails = document.getElementById('projectDetails');
            const locationField = document.getElementById('location');

            if (projectId && projects[projectId]) {
                const project = projects[projectId];
                
                // Update project details
                projectDetails.innerHTML = `
                    <div class="row small">
                        <div class="col-6">
                            <strong>Beneficiary:</strong><br>
                            ${project.beneficiary}
                        </div>
                        <div class="col-6">
                            <strong>Status:</strong><br>
                            <span class="badge bg-${project.status}">${project.status}</span>
                        </div>
                        <div class="col-6 mt-2">
                            <strong>Progress:</strong><br>
                            ${project.progress}%
                        </div>
                        <div class="col-6 mt-2">
                            <strong>Budget:</strong><br>
                            ZMW ${project.budget}
                        </div>
                        <div class="col-12 mt-2">
                            <strong>Project Location:</strong><br>
                            ${project.location}
                        </div>
                        <div class="col-12 mt-2">
                            <strong>Description:</strong><br>
                            ${project.description}...
                        </div>
                    </div>
                `;
                
                // Pre-fill location field with project location
                if (project.location && project.location !== 'Not specified' && !locationField.value) {
                    locationField.value = project.location;
                }
                
                projectInfo.style.display = 'block';
            } else {
                projectInfo.style.display = 'none';
                projectDetails.innerHTML = '';
            }
        }

        // Character count for purpose field
        function updateCharCount() {
            const purposeField = document.getElementById('purpose');
            const charCount = purposeField.value.length;
            const charCountElement = document.getElementById('charCount');
            const charCountContainer = document.getElementById('charCountContainer');
            
            charCountElement.textContent = charCount;
            
            if (charCount < 10) {
                charCountContainer.className = 'char-count error';
                purposeField.classList.add('is-invalid');
            } else {
                charCountContainer.className = 'char-count';
                purposeField.classList.remove('is-invalid');
            }
        }

        // Set minimum time to current time if today is selected
        function updateTimeMin() {
            const dateField = document.getElementById('visit_date');
            const timeField = document.getElementById('visit_time');
            
            if (dateField.value === new Date().toISOString().split('T')[0]) {
                const now = new Date();
                const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                                  now.getMinutes().toString().padStart(2, '0');
                timeField.min = currentTime;
                
                if (timeField.value < currentTime) {
                    timeField.value = currentTime;
                }
            } else {
                timeField.removeAttribute('min');
            }
        }

        // Form validation
        function initializeFormValidation() {
            const form = document.getElementById('scheduleForm');
            const fields = form.querySelectorAll('input, select, textarea');
            
            fields.forEach(field => {
                field.addEventListener('blur', validateField);
                field.addEventListener('input', function() {
                    if (this.id === 'purpose') {
                        updateCharCount();
                    }
                    if (this.classList.contains('is-invalid')) {
                        validateField.call(this);
                    }
                });
            });

            // Date field specific validation
            document.getElementById('visit_date').addEventListener('change', function() {
                updateTimeMin();
                validateField.call(this);
            });
        }

        function validateField() {
            const field = this;
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';

            // Clear previous validation
            field.classList.remove('is-invalid');
            
            // Required field validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required';
            }
            
            // Specific field validations
            if (isValid) {
                switch (field.id) {
                    case 'visit_date':
                        const selectedDate = new Date(value);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        if (selectedDate < today) {
                            isValid = false;
                            errorMessage = 'Visit date cannot be in the past';
                        }
                        break;
                        
                    case 'purpose':
                        if (value.length < 10) {
                            isValid = false;
                            errorMessage = 'Purpose must be at least 10 characters long';
                        }
                        break;
                        
                    case 'project_id':
                        if (value === '') {
                            isValid = false;
                            errorMessage = 'Please select a project';
                        }
                        break;
                }
            }
            
            // Update field state
            if (!isValid) {
                field.classList.add('is-invalid');
                let feedback = field.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    field.parentNode.insertBefore(feedback, field.nextElementSibling);
                }
                feedback.textContent = errorMessage;
            } else {
                const feedback = field.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            }
            
            return isValid;
        }

        function validateForm() {
            const form = document.getElementById('scheduleForm');
            const fields = form.querySelectorAll('input, select, textarea');
            let isValid = true;
            
            fields.forEach(field => {
                if (!validateField.call(field)) {
                    isValid = false;
                }
            });
            
            return isValid;
        }

        // Form submission
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Scheduling...';
                submitBtn.disabled = true;
                
                // Submit the form
                this.submit();
            } else {
                // Scroll to first error
                const firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                
                showNotification('Please fix the errors in the form before submitting.', 'error');
            }
        });

        // Reset form
        function resetForm() {
            document.getElementById('scheduleForm').reset();
            document.getElementById('projectInfo').style.display = 'none';
            updateCharCount();
            
            // Clear validation states
            const fields = document.querySelectorAll('.is-invalid');
            fields.forEach(field => {
                field.classList.remove('is-invalid');
                const feedback = field.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            });
            
            showNotification('Form has been reset', 'info');
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';
            }, 10);
            
            setTimeout(() => {
                notification.style.transform = 'translateY(-20px)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }

        // Enhanced server time with date
        function updateServerTime() {
            const now = new Date();
            const dateString = now.toLocaleDateString('en-US', { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const serverTimeElement = document.getElementById('serverTime');
            if (serverTimeElement) {
                serverTimeElement.textContent = `${dateString} ${timeString}`;
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('scheduleForm').dispatchEvent(new Event('submit'));
            }
            
            // Escape key to reset form
            if (e.key === 'Escape') {
                e.preventDefault();
                if (confirm('Are you sure you want to reset the form?')) {
                    resetForm();
                }
            }
        });

        // Initialize character count event listener
        document.getElementById('purpose').addEventListener('input', updateCharCount);
        
        // Initialize date change event listener
        document.getElementById('visit_date').addEventListener('change', updateTimeMin);

        // Update server time immediately and then every second
        updateServerTime();
        setInterval(updateServerTime, 1000);
    </script>
</body>
</html>
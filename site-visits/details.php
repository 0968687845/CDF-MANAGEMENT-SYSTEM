<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Get visit ID from URL
$visit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$visit_id) {
    $_SESSION['error'] = 'Invalid site visit ID.';
    redirect('index.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Database connection
$database = new Database();
$pdo = $database->getConnection();

// Get site visit details
try {
    $query = "SELECT sv.*, 
                     p.title as project_title,
                     p.description as project_description,
                     p.budget as project_budget,
                     p.status as project_status,
                     p.progress as project_progress,
                     p.beneficiary_id,
                     CONCAT(b.first_name, ' ', b.last_name) as beneficiary_name,
                     b.phone as beneficiary_phone,
                     b.email as beneficiary_email,
                     b.constituency as beneficiary_constituency,
                     CONCAT(o.first_name, ' ', o.last_name) as officer_name
              FROM site_visits sv
              INNER JOIN projects p ON sv.project_id = p.id
              INNER JOIN users b ON p.beneficiary_id = b.id
              INNER JOIN users o ON sv.officer_id = o.id
              WHERE sv.id = :visit_id AND sv.officer_id = :officer_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt->bindParam(':officer_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$visit) {
        $_SESSION['error'] = 'Site visit not found or you do not have permission to view it.';
        redirect('index.php');
    }
    
} catch (PDOException $e) {
    error_log("Error fetching site visit details: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading site visit details.';
    redirect('index.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $valid_statuses = ['scheduled', 'in-progress', 'completed', 'cancelled'];
        
        if (in_array($new_status, $valid_statuses)) {
            try {
                $update_query = "UPDATE site_visits SET status = :status WHERE id = :visit_id AND officer_id = :officer_id";
                $update_stmt = $pdo->prepare($update_query);
                $update_stmt->bindParam(':status', $new_status);
                $update_stmt->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
                $update_stmt->bindParam(':officer_id', $_SESSION['user_id'], PDO::PARAM_INT);
                
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = 'Visit status updated successfully!';
                    
                    // Create notification for beneficiary if status changed to completed
                    if ($new_status === 'completed' && isset($visit['beneficiary_id'])) {
                        $notification_title = 'Site Visit Completed';
                        $notification_message = 'The site visit for your project "' . $visit['project_title'] . '" has been marked as completed.';
                        
                        $notification_query = "INSERT INTO notifications SET 
                            user_id = :beneficiary_id,
                            title = :title,
                            message = :message";
                        
                        $notification_stmt = $pdo->prepare($notification_query);
                        $notification_stmt->bindParam(':beneficiary_id', $visit['beneficiary_id'], PDO::PARAM_INT);
                        $notification_stmt->bindParam(':title', $notification_title);
                        $notification_stmt->bindParam(':message', $notification_message);
                        $notification_stmt->execute();
                    }
                    
                    // Refresh visit data
                    $stmt->execute();
                    $visit = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $_SESSION['error'] = 'Failed to update visit status.';
                }
            } catch (PDOException $e) {
                error_log("Error updating visit status: " . $e->getMessage());
                $_SESSION['error'] = 'Error updating visit status.';
            }
        }
    }
}

$pageTitle = "Site Visit Details - CDF Management System";

// Function to format date for display
function formatDisplayDate($date) {
    if (empty($date)) return 'Not specified';
    return date('l, F j, Y', strtotime($date));
}

// Function to format time for display
function formatDisplayTime($time) {
    if (empty($time)) return 'Not specified';
    return date('g:i A', strtotime($time));
}

// Safe value getter with fallback
function getSafeValue($array, $key, $default = 'Not specified') {
    return isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Site Visit Details for CDF Management System - Government of Zambia">
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

        .info-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary);
            min-width: 120px;
        }

        .info-value {
            flex: 1;
            color: var(--dark);
        }

        .status-badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .badge-scheduled { background: var(--info); color: white; }
        .badge-in-progress { background: var(--warning); color: var(--dark); }
        .badge-completed { background: var(--success); color: white; }
        .badge-cancelled { background: var(--danger); color: white; }

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
            margin-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid var(--white);
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
            
            .info-item {
                flex-direction: column;
            }
            
            .info-label {
                min-width: auto;
                margin-bottom: 0.5rem;
            }
        }

        /* Print styles */
        @media print {
            .navbar, .action-buttons, .dashboard-footer, .btn {
                display: none !important;
            }
            
            .dashboard-header {
                margin-top: 0;
                color: var(--dark);
                background: none;
            }
            
            .content-card {
                box-shadow: none;
                border: 1px solid var(--gray-light);
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
                            <li><a class="dropdown-item active" href="details.php?id=<?php echo $visit_id; ?>">
                                <i class="fas fa-eye me-2"></i>Visit Details
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
                    <h1>Site Visit Details</h1>
                    <p class="lead">Complete information for scheduled site visit</p>
                    <p class="mb-0">Visit ID: <strong>#<?php echo $visit_id; ?></strong> | Project: <strong><?php echo htmlspecialchars(getSafeValue($visit, 'project_title')); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-outline-custom">
                    <i class="fas fa-arrow-left me-2"></i>Back to Visits
                </a>
                <a href="edit.php?id=<?php echo $visit_id; ?>" class="btn btn-primary-custom">
                    <i class="fas fa-edit me-2"></i>Edit Visit
                </a>
                <a href="report.php?id=<?php echo $visit_id; ?>" class="btn btn-outline-custom">
                    <i class="fas fa-file-alt me-2"></i>Generate Report
                </a>
                <button type="button" class="btn btn-outline-custom" onclick="printVisitDetails()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Visit Information -->
            <div class="col-lg-8">
                <!-- Visit Details Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Visit Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-card">
                            <div class="info-item">
                                <span class="info-label">Project:</span>
                                <span class="info-value">
                                    <strong><?php echo htmlspecialchars(getSafeValue($visit, 'project_title')); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars(getSafeValue($visit, 'project_description')); ?></small>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Visit Date:</span>
                                <span class="info-value">
                                    <strong><?php echo formatDisplayDate(getSafeValue($visit, 'visit_date')); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo formatDisplayTime(getSafeValue($visit, 'visit_time')); ?></small>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars(getSafeValue($visit, 'location')); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Purpose:</span>
                                <span class="info-value"><?php echo htmlspecialchars(getSafeValue($visit, 'purpose')); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <span class="status-badge badge-<?php echo getSafeValue($visit, 'status', 'scheduled'); ?>">
                                        <?php echo ucfirst(getSafeValue($visit, 'status', 'Scheduled')); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Scheduled By:</span>
                                <span class="info-value"><?php echo htmlspecialchars(getSafeValue($visit, 'officer_name')); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created:</span>
                                <span class="info-value">
                                    <?php 
                                    $created_date = getSafeValue($visit, 'created');
                                    if ($created_date && $created_date !== 'Not specified') {
                                        echo date('M j, Y \a\t g:i A', strtotime($created_date));
                                    } else {
                                        echo 'Not available';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <!-- Status Update Form -->
                        <div class="mt-4">
                            <h6><i class="fas fa-sync-alt me-2"></i>Update Visit Status</h6>
                            <form method="POST" class="d-flex gap-2 align-items-center" onsubmit="return confirmStatusUpdate()">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" class="form-select" style="max-width: 200px;">
                                    <option value="scheduled" <?php echo getSafeValue($visit, 'status') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="in-progress" <?php echo getSafeValue($visit, 'status') === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo getSafeValue($visit, 'status') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo getSafeValue($visit, 'status') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-primary-custom btn-sm">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Project Information -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-project-diagram me-2"></i>Project Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-card">
                            <div class="info-item">
                                <span class="info-label">Project Status:</span>
                                <span class="info-value">
                                    <span class="badge bg-<?php echo getSafeValue($visit, 'project_status', 'secondary'); ?>">
                                        <?php echo ucfirst(getSafeValue($visit, 'project_status', 'Unknown')); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Progress:</span>
                                <span class="info-value">
                                    <div class="progress" style="height: 8px; width: 200px;">
                                        <div class="progress-bar bg-<?php echo getSafeValue($visit, 'project_status', 'secondary'); ?>" 
                                             style="width: <?php echo getSafeValue($visit, 'project_progress', 0); ?>%"></div>
                                    </div>
                                    <small><?php echo getSafeValue($visit, 'project_progress', 0); ?>% complete</small>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Budget:</span>
                                <span class="info-value">ZMW <?php echo number_format(getSafeValue($visit, 'project_budget', 0), 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Beneficiary Information & Actions -->
            <div class="col-lg-4">
                <!-- Beneficiary Information -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-user me-2"></i>Beneficiary Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-card">
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars(getSafeValue($visit, 'beneficiary_name')); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value">
                                    <?php if (getSafeValue($visit, 'beneficiary_phone') !== 'Not specified'): ?>
                                        <a href="tel:<?php echo htmlspecialchars(getSafeValue($visit, 'beneficiary_phone')); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars(getSafeValue($visit, 'beneficiary_phone')); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">
                                    <?php if (getSafeValue($visit, 'beneficiary_email') !== 'Not specified'): ?>
                                        <a href="mailto:<?php echo htmlspecialchars(getSafeValue($visit, 'beneficiary_email')); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars(getSafeValue($visit, 'beneficiary_email')); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Constituency:</span>
                                <span class="info-value"><?php echo htmlspecialchars(getSafeValue($visit, 'beneficiary_constituency')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (getSafeValue($visit, 'beneficiary_id') !== 'Not specified'): ?>
                                <button type="button" class="btn btn-outline-primary" onclick="messageBeneficiary(<?php echo $visit['beneficiary_id']; ?>)" data-bs-toggle="tooltip" title="Send message to beneficiary">
                                    <i class="fas fa-comments me-2"></i>Message Beneficiary
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-warning" onclick="editVisit(<?php echo $visit_id; ?>)" data-bs-toggle="tooltip" title="Edit visit details (Ctrl+E)">
                                <i class="fas fa-edit me-2"></i>Edit Visit Details
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="generateReport(<?php echo $visit_id; ?>)" data-bs-toggle="tooltip" title="Generate report (Ctrl+R)">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="viewProject(<?php echo $visit['project_id']; ?>)" data-bs-toggle="tooltip" title="View project details">
                                <i class="fas fa-external-link-alt me-2"></i>View Project
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="printVisitDetails()" data-bs-toggle="tooltip" title="Print this page">
                                <i class="fas fa-print me-2"></i>Print Details
                            </button>
                            <button type="button" class="btn btn-outline-dark" onclick="copyVisitId()" data-bs-toggle="tooltip" title="Copy Visit ID to clipboard">
                                <i class="fas fa-copy me-2"></i>Copy Visit ID
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Visit Timeline -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Visit Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <h6 class="mb-1">Visit Scheduled</h6>
                                <p class="small text-muted mb-1">
                                    <?php 
                                    $created = getSafeValue($visit, 'created');
                                    if ($created && $created !== 'Not specified') {
                                        echo date('M j, Y \a\t g:i A', strtotime($created));
                                    } else {
                                        echo 'Date not available';
                                    }
                                    ?>
                                </p>
                                <p class="small mb-0">Site visit was scheduled by <?php echo htmlspecialchars(getSafeValue($visit, 'officer_name')); ?></p>
                            </div>
                            <div class="timeline-item">
                                <h6 class="mb-1">Current Status</h6>
                                <p class="small text-muted mb-1"><?php echo ucfirst(getSafeValue($visit, 'status', 'scheduled')); ?></p>
                                <p class="small mb-0">
                                    <?php if (getSafeValue($visit, 'status') === 'scheduled'): ?>
                                        Visit is scheduled for <?php echo formatDisplayDate(getSafeValue($visit, 'visit_date')); ?>
                                    <?php elseif (getSafeValue($visit, 'status') === 'in-progress'): ?>
                                        Visit is currently in progress
                                    <?php elseif (getSafeValue($visit, 'status') === 'completed'): ?>
                                        Visit has been completed
                                    <?php else: ?>
                                        Visit has been cancelled
                                    <?php endif; ?>
                                </p>
                            </div>
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

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Store original button text
            document.querySelectorAll('.btn').forEach(button => {
                button.setAttribute('data-original-text', button.innerHTML);
            });

            // Highlight active menu item
            highlightActiveMenuItem();

            console.log('Site Visit Details page loaded successfully');
        });

        // Function to highlight active menu item
        function highlightActiveMenuItem() {
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.nav-link, .dropdown-item');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && (href === currentPage || href.includes(currentPage) || href.includes('details.php'))) {
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

        // Status update confirmation
        function confirmStatusUpdate() {
            const status = document.querySelector('select[name="status"]').value;
            const currentStatus = '<?php echo getSafeValue($visit, 'status', 'scheduled'); ?>';
            
            if (status === currentStatus) {
                alert('Status is already set to ' + currentStatus);
                return false;
            }
            
            return confirm('Are you sure you want to update the visit status to "' + status + '"? This action may notify the beneficiary.');
        }

        // Quick action handlers
        function messageBeneficiary(userId) {
            window.location.href = `../communication/messages.php?user_id=${userId}`;
        }

        function editVisit(visitId) {
            window.location.href = `edit.php?id=${visitId}`;
        }

        function generateReport(visitId) {
            window.location.href = `report.php?id=${visitId}`;
        }

        function viewProject(projectId) {
            window.location.href = `../projects/details.php?id=${projectId}`;
        }

        // Print functionality
        function printVisitDetails() {
            window.print();
        }

        // Copy visit ID to clipboard
        function copyVisitId() {
            const visitId = <?php echo $visit_id; ?>;
            navigator.clipboard.writeText(visitId).then(() => {
                // Show temporary notification
                showNotification('Visit ID copied to clipboard!', 'success');
            }).catch(err => {
                showNotification('Failed to copy Visit ID', 'error');
            });
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
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
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + E for edit
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                editVisit(<?php echo $visit_id; ?>);
            }
            
            // Ctrl + R for report
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                generateReport(<?php echo $visit_id; ?>);
            }
            
            // Ctrl + P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printVisitDetails();
            }
            
            // Escape key to close modals/dropdowns
            if (e.key === 'Escape') {
                const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                openDropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

        // Add loading states to buttons
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                const originalText = this.getAttribute('data-original-text') || this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                this.disabled = true;
                
                // Re-enable after 5 seconds (in case of error)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 5000);
            });
        });

        // Update server time immediately and then every second
        updateServerTime();
        setInterval(updateServerTime, 1000);
    </script>
</body>
</html>
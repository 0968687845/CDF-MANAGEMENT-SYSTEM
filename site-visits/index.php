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

// Get officer's assigned projects for site visits
$projects = getOfficerProjects($_SESSION['user_id']);

// Database connection for site visits
$database = new Database();
$pdo = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_visit':
                $visit_data = [
                    'project_id' => $_POST['project_id'],
                    'visit_date' => $_POST['visit_date'],
                    'purpose' => $_POST['purpose'],
                    'location' => $_POST['location'],
                    'notes' => $_POST['notes'] ?? '',
                    'officer_id' => $_SESSION['user_id']
                ];
                
                if (addSiteVisit($visit_data)) {
                    $_SESSION['success'] = 'Site visit scheduled successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to schedule site visit. Please try again.';
                }
                break;
                
            case 'update_visit':
                $visit_data = [
                    'id' => $_POST['visit_id'],
                    'visit_date' => $_POST['visit_date'],
                    'purpose' => $_POST['purpose'],
                    'location' => $_POST['location'],
                    'notes' => $_POST['notes'] ?? '',
                    'status' => $_POST['status'] ?? 'scheduled'
                ];
                
                if (updateSiteVisit($visit_data)) {
                    $_SESSION['success'] = 'Site visit updated successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to update site visit. Please try again.';
                }
                break;
                
            case 'delete_visit':
                if (deleteSiteVisit($_POST['visit_id'], $_SESSION['user_id'])) {
                    $_SESSION['success'] = 'Site visit deleted successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to delete site visit. Please try again.';
                }
                break;
                
            case 'update_status':
                if (updateVisitStatus($_POST['visit_id'], $_POST['status'], $_SESSION['user_id'])) {
                    $_SESSION['success'] = 'Visit status updated successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to update visit status. Please try again.';
                }
                break;
        }
        redirect('index.php');
    }
}

// Get site visits from database
$site_visits = getOfficerSiteVisits($_SESSION['user_id']);

// Get visit statistics
$visit_stats = getVisitStatistics($_SESSION['user_id']);

$pageTitle = "Site Visits Management - CDF Management System";

// Site Visit Functions
function addSiteVisit($data) {
    global $pdo;
    
    try {
        $query = "INSERT INTO site_visits SET 
            project_id = :project_id,
            visit_date = :visit_date,
            purpose = :purpose,
            location = :location,
            notes = :notes,
            officer_id = :officer_id,
            status = 'scheduled',
            created_at = NOW()";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $data['project_id']);
        $stmt->bindParam(':visit_date', $data['visit_date']);
        $stmt->bindParam(':purpose', $data['purpose']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':officer_id', $data['officer_id']);
        
        if ($stmt->execute()) {
            // Create notification for the beneficiary
            $project = getProjectById($data['project_id']);
            if ($project && $project['beneficiary_id']) {
                createNotification(
                    $project['beneficiary_id'],
                    'Site Visit Scheduled',
                    'A site visit has been scheduled for your project "' . $project['title'] . '" on ' . date('M j, Y', strtotime($data['visit_date']))
                );
            }
            
            logActivity($data['officer_id'], 'site_visit_created', 'Scheduled site visit for project ID: ' . $data['project_id']);
            return true;
        }
    } catch (PDOException $e) {
        error_log("Error adding site visit: " . $e->getMessage());
    }
    
    return false;
}

function getOfficerSiteVisits($officer_id) {
    global $pdo;
    
    try {
        $query = "SELECT sv.*, 
                         p.title as project_title,
                         CONCAT(u.first_name, ' ', u.last_name) as beneficiary_name,
                         u.phone as beneficiary_phone,
                         p.location as project_location
                  FROM site_visits sv
                  INNER JOIN projects p ON sv.project_id = p.id
                  INNER JOIN users u ON p.beneficiary_id = u.id
                  WHERE sv.officer_id = :officer_id
                  ORDER BY sv.visit_date DESC, sv.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':officer_id', $officer_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching site visits: " . $e->getMessage());
        return [];
    }
}

function updateSiteVisit($data) {
    global $pdo;
    
    try {
        $query = "UPDATE site_visits SET 
            visit_date = :visit_date,
            purpose = :purpose,
            location = :location,
            notes = :notes,
            status = :status,
            updated_at = NOW()
            WHERE id = :id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':visit_date', $data['visit_date']);
        $stmt->bindParam(':purpose', $data['purpose']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $data['id']);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating site visit: " . $e->getMessage());
        return false;
    }
}

function deleteSiteVisit($visit_id, $officer_id) {
    global $pdo;
    
    try {
        // Verify ownership
        $check_query = "SELECT id FROM site_visits WHERE id = :visit_id AND officer_id = :officer_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->bindParam(':visit_id', $visit_id);
        $check_stmt->bindParam(':officer_id', $officer_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            return false;
        }
        
        $query = "DELETE FROM site_visits WHERE id = :visit_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':visit_id', $visit_id);
        
        if ($stmt->execute()) {
            logActivity($officer_id, 'site_visit_deleted', 'Deleted site visit ID: ' . $visit_id);
            return true;
        }
    } catch (PDOException $e) {
        error_log("Error deleting site visit: " . $e->getMessage());
    }
    
    return false;
}

function updateVisitStatus($visit_id, $status, $officer_id) {
    global $pdo;
    
    try {
        $query = "UPDATE site_visits SET 
            status = :status,
            updated_at = NOW()
            WHERE id = :visit_id AND officer_id = :officer_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':visit_id', $visit_id);
        $stmt->bindParam(':officer_id', $officer_id);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating visit status: " . $e->getMessage());
        return false;
    }
}

function getVisitStatistics($officer_id) {
    global $pdo;
    
    try {
        $query = "SELECT 
            COUNT(*) as total_visits,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN visit_date = CURDATE() THEN 1 ELSE 0 END) as today_visits
            FROM site_visits 
            WHERE officer_id = :officer_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':officer_id', $officer_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching visit statistics: " . $e->getMessage());
        return [
            'total_visits' => 0,
            'scheduled' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'today_visits' => 0
        ];
    }
}

function getUpcomingVisits($officer_id, $days = 7) {
    global $pdo;
    
    try {
        $query = "SELECT sv.*, 
                         p.title as project_title,
                         CONCAT(u.first_name, ' ', u.last_name) as beneficiary_name
                  FROM site_visits sv
                  INNER JOIN projects p ON sv.project_id = p.id
                  INNER JOIN users u ON p.beneficiary_id = u.id
                  WHERE sv.officer_id = :officer_id 
                  AND sv.status = 'scheduled'
                  AND sv.visit_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  ORDER BY sv.visit_date ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':officer_id', $officer_id);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching upcoming visits: " . $e->getMessage());
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Site Visits Management for CDF Management System - Government of Zambia">
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

        .stats-container {
            margin: 2.5rem 0;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 5px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .stat-title {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .visit-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            overflow: hidden;
        }

        .visit-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .badge-scheduled { background: var(--info); color: white; }
        .badge-in-progress { background: var(--warning); color: var(--dark); }
        .badge-completed { background: var(--success); color: white; }
        .badge-cancelled { background: var(--danger); color: white; }

        .badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table th {
            border-top: none;
            font-weight: 700;
            color: var(--primary);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.25rem;
        }

        .table td {
            padding: 1.25rem;
            vertical-align: middle;
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

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
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
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item active" href="index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
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
                    <h1>Site Visits Management</h1>
                    <p class="lead">Monitor and manage all site visits for assigned projects</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="schedule.php" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i>Schedule New Visit
                </a>
                <a href="../projects/index.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>View Projects
                </a>
                <a href="../evaluation/reports.php" class="btn btn-outline-custom">
                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Flash Messages -->
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

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $visit_stats['total_visits']; ?></div>
                        <div class="stat-title">Total Site Visits</div>
                        <div class="stat-subtitle">All scheduled visits</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $visit_stats['scheduled']; ?></div>
                        <div class="stat-title">Scheduled</div>
                        <div class="stat-subtitle">Upcoming visits</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $visit_stats['completed']; ?></div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-subtitle">Finished visits</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $visit_stats['today_visits']; ?></div>
                        <div class="stat-title">Today's Visits</div>
                        <div class="stat-subtitle">Scheduled for today</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site Visits List -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-map-marker-alt me-2"></i>All Site Visits</h5>
                <div class="btn-group">
                    <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                    <button class="btn btn-outline-custom btn-sm" onclick="exportVisits()">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($site_visits) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Beneficiary</th>
                                    <th>Visit Date & Time</th>
                                    <th>Location</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($site_visits as $visit): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($visit['project_title']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($visit['beneficiary_name']); ?>
                                        <?php if ($visit['beneficiary_phone']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($visit['beneficiary_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($visit['visit_date'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($visit['visit_date'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($visit['location'] ?: $visit['project_location']); ?></td>
                                    <td><?php echo htmlspecialchars($visit['purpose']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $visit['status']; ?>">
                                            <?php echo ucfirst($visit['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewVisit(<?php echo $visit['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="editVisit(<?php echo $visit['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="generateReport(<?php echo $visit['id']; ?>)">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <?php if ($visit['status'] === 'scheduled'): ?>
                                                <button class="btn btn-outline-info" onclick="updateStatus(<?php echo $visit['id']; ?>, 'in-progress')">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php elseif ($visit['status'] === 'in-progress'): ?>
                                                <button class="btn btn-outline-success" onclick="updateStatus(<?php echo $visit['id']; ?>, 'completed')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Site Visits Scheduled</h5>
                        <p class="text-muted mb-4">Get started by scheduling your first site visit.</p>
                        <a href="schedule.php" class="btn btn-primary-custom">
                            <i class="fas fa-plus-circle me-2"></i>Schedule Visit
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Visits -->
        <div class="row">
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt me-2"></i>Upcoming Visits (Next 7 Days)</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $upcoming_visits = getUpcomingVisits($_SESSION['user_id'], 7);
                        ?>
                        
                        <?php if (count($upcoming_visits) > 0): ?>
                            <?php foreach ($upcoming_visits as $visit): ?>
                            <div class="visit-card card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($visit['project_title']); ?></h6>
                                            <p class="card-text text-muted small mb-1">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($visit['beneficiary_name']); ?>
                                            </p>
                                            <p class="card-text text-muted small mb-2">
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($visit['location'] ?: $visit['project_location']); ?>
                                            </p>
                                            <small class="text-primary">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('D, M j, Y - g:i A', strtotime($visit['visit_date'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-scheduled">Scheduled</span>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-primary-custom" onclick="viewVisit(<?php echo $visit['id']; ?>)">View Details</button>
                                        <button class="btn btn-sm btn-outline-custom" onclick="editVisit(<?php echo $visit['id']; ?>)">Reschedule</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No upcoming site visits in the next 7 days</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Visit Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="visitsChart"></canvas>
                        </div>
                        <div class="row text-center mt-4">
                            <div class="col-3">
                                <h4 class="text-primary"><?php echo $visit_stats['total_visits']; ?></h4>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-3">
                                <h4 class="text-success"><?php echo $visit_stats['completed']; ?></h4>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-3">
                                <h4 class="text-info"><?php echo $visit_stats['scheduled']; ?></h4>
                                <small class="text-muted">Scheduled</small>
                            </div>
                            <div class="col-3">
                                <h4 class="text-warning"><?php echo $visit_stats['in_progress']; ?></h4>
                                <small class="text-muted">In Progress</small>
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
        // Initialize Visits Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('visitsChart').getContext('2d');
            const visitsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Scheduled', 'In Progress', 'Completed', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo $visit_stats['scheduled']; ?>,
                            <?php echo $visit_stats['in_progress']; ?>,
                            <?php echo $visit_stats['completed']; ?>,
                            <?php echo $visit_stats['cancelled']; ?>
                        ],
                        backgroundColor: [
                            '#17a2b8',
                            '#ffc107',
                            '#28a745',
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

        // Action Functions
        function viewVisit(visitId) {
            window.location.href = 'details.php?id=' + visitId;
        }

        function editVisit(visitId) {
            window.location.href = 'edit.php?id=' + visitId;
        }

        function generateReport(visitId) {
            window.location.href = 'report.php?id=' + visitId;
        }

        function updateStatus(visitId, status) {
            if (confirm('Are you sure you want to update the visit status?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php';
                
                const visitIdInput = document.createElement('input');
                visitIdInput.type = 'hidden';
                visitIdInput.name = 'visit_id';
                visitIdInput.value = visitId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_status';
                
                form.appendChild(visitIdInput);
                form.appendChild(statusInput);
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportVisits() {
            // In a real implementation, this would generate and download a CSV/PDF report
            alert('Export functionality would generate a report of all site visits.');
        }

        // Update server time
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();
    </script>
</body>
</html>
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

// Create site_visits table if it doesn't exist
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
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
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
        
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
    }
}

// Check if Google Maps API key is configured
$hasValidApiKey = false;
$googleMapsApiKey = '';

// Note: Using Leaflet with OpenStreetMap - no API key required
// This replaces the Google Maps implementation

// Check if IP Geolocation API is configured
$hasIPGeolocation = hasIPGeolocationApiKey();

// Handle form submission
$errors = [];
$success = '';

// Set default values
$default_date = date('Y-m-d');
$default_time = '09:00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect(basename($_SERVER['PHP_SELF']));
        exit;
    }
    // Validate and sanitize input
    $project_id = trim($_POST['project_id'] ?? '');
    $visit_date = trim($_POST['visit_date'] ?? '');
    $visit_time = trim($_POST['visit_time'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    
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
            $pdo->beginTransaction();
            
            $query = "INSERT INTO site_visits SET 
                project_id = :project_id,
                officer_id = :officer_id,
                visit_date = :visit_date,
                visit_time = :visit_time,
                location = :location,
                latitude = :latitude,
                longitude = :longitude,
                purpose = :purpose,
                status = 'scheduled'";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $stmt->bindParam(':officer_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':visit_date', $visit_date);
            $stmt->bindParam(':visit_time', $visit_time);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':latitude', $latitude);
            $stmt->bindParam(':longitude', $longitude);
            $stmt->bindParam(':purpose', $purpose);
            
            if ($stmt->execute()) {
                $visit_id = $pdo->lastInsertId();
                
                // Create notification for beneficiary
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
                
                $pdo->commit();
                
                $success = 'Site visit scheduled successfully!' . 
                          ($selected_project && isset($selected_project['beneficiary_id']) ? ' The beneficiary has been notified.' : '');
                
                // Clear form but keep project selection
                $keep_project_id = $project_id;
                $_POST = [];
                $_POST['project_id'] = $keep_project_id;
                
            } else {
                $pdo->rollBack();
                $errors[] = 'Failed to schedule site visit. Please try again.';
            }
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error scheduling site visit: " . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
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
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <!-- Leaflet Geocoder for Address Search -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-geocoder/1.5.4/control.geocoder.min.css" />
    <!-- Leaflet Routing Machine for Route Display -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <!-- Leaflet JavaScript Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <!-- Leaflet Routing Machine for Route Display -->
    <script src="https://cdn.jsdelivr.net/npm/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
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
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --border-radius: 12px;
            --border-radius-sm: 8px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ed 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: var(--shadow-lg);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
        }

        .navbar-brand img {
            filter: brightness(0) invert(1);
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            transform: scale(1.1);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: var(--border-radius-sm);
            margin: 0 2px;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--secondary);
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .nav-link:hover::before, .nav-link.active::before {
            width: 80%;
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius);
            padding: 0.5rem 0;
            background: var(--white);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: var(--transition);
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--white);
            transform: translateX(5px);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 3rem 0 2rem;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.03)"><polygon points="0,0 1000,50 1000,100 0,100"/></svg>');
            background-size: cover;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 800;
            box-shadow: var(--shadow-lg);
            border: 6px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.1) 100%);
            border-radius: 50%;
        }

        .profile-avatar:hover {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .profile-info h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            color: var(--white);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .profile-info .lead {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.2rem;
            font-weight: 500;
        }

        .profile-info p {
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0;
            font-size: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: var(--dark);
            border: none;
            padding: 0.9rem 2rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: var(--dark);
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            padding: 0.9rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
            position: relative;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 0;
            font-size: 1.3rem;
        }

        /* Form Styles */
        .form-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .form-label {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.7rem;
            font-size: 1rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            padding: 0.85rem 1.2rem;
            transition: var(--transition);
            font-size: 1rem;
            background: var(--white);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.3rem rgba(26, 78, 138, 0.15);
            transform: translateY(-1px);
        }

        .project-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--primary);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .project-info-card:hover {
            transform: translateX(5px);
        }

        /* Map Styles */
        #locationMap {
            width: 100%;
            height: 450px;
            border-radius: var(--border-radius-sm);
            margin-top: 1rem;
            box-shadow: var(--shadow);
            border: 2px solid var(--gray-light);
            transition: var(--transition);
        }

        #locationMap:hover {
            box-shadow: var(--shadow-lg);
        }

        .map-container {
            background: var(--white);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-light);
            box-shadow: var(--shadow);
        }

        .location-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--success);
            border-radius: var(--border-radius-sm);
            padding: 1.2rem;
            margin-top: 1rem;
            box-shadow: var(--shadow);
        }

        .location-controls {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
        }

        .location-controls .btn {
            font-size: 0.9rem;
            padding: 0.7rem 1.2rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .location-controls .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .geolocation-status {
            margin-top: 0.8rem;
            font-size: 0.9rem;
            padding: 0.8rem;
            border-radius: var(--border-radius-sm);
            background: var(--info);
            color: white;
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .accuracy-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .accuracy-high { background-color: var(--success); }
        .accuracy-medium { background-color: var(--warning); }
        .accuracy-low { background-color: var(--danger); }

        .map-placeholder {
            background: linear-gradient(135deg, var(--gray-light) 0%, #dee2e6 100%);
            border-radius: var(--border-radius-sm);
            height: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 3rem;
            color: var(--gray);
            border: 2px dashed #adb5bd;
        }

        .map-placeholder i {
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
            position: relative;
        }

        .dashboard-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }

        /* Real-time Features */
        .real-time-indicator {
            position: fixed;
            top: 90px;
            right: 25px;
            z-index: 1000;
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
            color: white;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.5s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .live-badge {
            background: var(--danger);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 800;
            animation: pulse 1.5s infinite;
        }

        .auto-save-status {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 12px 18px;
            border-radius: var(--border-radius-sm);
            font-size: 0.85rem;
            z-index: 1000;
            display: none;
            animation: slideInUp 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        @keyframes slideInUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Badge Colors */
        .badge-completed { background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%); color: white; }
        .badge-in-progress { background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%); color: var(--dark); }
        .badge-delayed { background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%); color: white; }
        .badge-planning { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; }
        .badge-secondary { background: linear-gradient(135deg, var(--gray) 0%, #5a6268 100%); color: white; }

        /* Character Count */
        .char-count {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid transparent;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%);
            color: #721c24;
            border-left-color: var(--danger);
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left-color: var(--warning);
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #b6e3ec 100%);
            color: #0c5460;
            border-left-color: var(--info);
        }

        /* Form Validation */
        .is-invalid {
            border-color: var(--danger);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6.4.4.4-.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .invalid-feedback {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.4rem;
            font-weight: 500;
        }

        /* Responsive Design */
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
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .profile-info h1 {
                font-size: 1.8rem;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .real-time-indicator {
                top: 80px;
                right: 15px;
                font-size: 0.75rem;
                padding: 8px 15px;
            }

            .location-controls {
                justify-content: center;
            }

            .location-controls .btn {
                flex: 1;
                min-width: 140px;
                text-align: center;
            }

            #locationMap {
                height: 350px;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            .form-card {
                padding: 1.2rem;
            }

            .card-body {
                padding: 1.2rem;
            }

            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                margin-bottom: 0.8rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .d-flex.gap-3 {
                flex-direction: column;
            }

            .d-flex.gap-3 .btn {
                width: 100%;
                margin-bottom: 0.8rem;
            }

            .location-controls .btn {
                min-width: 100%;
            }

            .real-time-indicator {
                display: none;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Custom Scrollbar */
        .visits-list::-webkit-scrollbar {
            width: 6px;
        }

        .visits-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .visits-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .visits-list::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Focus States */
        .btn:focus, .form-control:focus, .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 78, 138, 0.25);
        }

        /* Hover Effects */
        .visit-item {
            transition: var(--transition);
            transform-origin: left;
        }

        .visit-item:hover {
            transform: translateX(8px) scale(1.02);
        }
    </style>
</head>
<body>
    <!-- Real-time Indicator -->
    <div class="real-time-indicator" id="realTimeIndicator">
        <span class="live-badge">LIVE</span>
        <span>Real-time Active</span>
    </div>

    <!-- Auto-save Status -->
    <div class="auto-save-status" id="autoSaveStatus">
        <i class="fas fa-save me-2"></i>
        <span id="autoSaveMessage">Auto-saving...</span>
    </div>

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
                            <li><a class="dropdown-item" href="index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item active" href="schedule.php">
                                <i class="fas fa-plus-circle me-2"></i>Schedule Visit
                            </a></li>
                            <li><a class="dropdown-item" href="map.php">
                                <i class="fas fa-map me-2"></i>View Map
                            </a></li>
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
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
                                        <div class="profile-avatar-small me-3" style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 800; font-size: 1.1rem;">
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
                <a href="map.php" class="btn btn-outline-custom">
                    <i class="fas fa-map me-2"></i>View Map
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

        <!-- IP Geolocation Status -->
        <?php if (!$hasIPGeolocation): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>IP Geolocation Limited:</strong>
                Some location detection features may not work optimally without IP geolocation API configuration.
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
                            <label for="project_id" class="form-label">Select Project</label>
                            <select class="form-select" id="project_id" name="project_id" required 
                                <?= csrfField() ?>
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
                        </div>

                        <!-- Project Information -->
                        <div id="projectInfo" class="project-info-card" style="display: none;">
                            <h6><i class="fas fa-info-circle me-2"></i>Project Information</h6>
                            <div id="projectDetails" class="mt-2"></div>
                            <div id="locationDetectionStatus" class="mt-3" style="display: none;">
                                <div class="alert alert-info p-2 m-0" role="alert">
                                    <small><i class="fas fa-spinner fa-spin me-2"></i><span id="locationStatusText">Detecting beneficiary location...</span></small>
                                </div>
                            </div>
                        </div>

                        <!-- Date and Time -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label">Visit Date</label>
                                <input type="date" class="form-control" id="visit_date" name="visit_date" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       value="<?php echo $_POST['visit_date'] ?? $default_date; ?>" required>
                                <div class="invalid-feedback">Please select a valid visit date</div>
                            </div>
                            <div class="col-md-6">
                                <label for="visit_time" class="form-label">Visit Time</label>
                                <input type="time" class="form-control" id="visit_time" name="visit_time" 
                                       value="<?php echo $_POST['visit_time'] ?? $default_time; ?>" required>
                                <div class="invalid-feedback">Please select a visit time</div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="mb-4">
                            <label for="location" class="form-label">Visit Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   placeholder="Enter the exact location or address for the site visit"
                                   value="<?php echo $_POST['location'] ?? ''; ?>" required
                                   autocomplete="off">
                            <div class="invalid-feedback">Please provide a location for the site visit</div>
                            
                            <!-- Hidden fields for coordinates -->
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo $_POST['latitude'] ?? '0'; ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo $_POST['longitude'] ?? '0'; ?>">
                            
                            <!-- Coordinate Entry Controls -->
                            <div class="mb-3 p-3 bg-light rounded">
                                <h6 class="mb-3"><i class="fas fa-map-pin me-2"></i>Manual Coordinate Entry</h6>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label for="manualLat" class="form-label form-label-sm">Latitude</label>
                                        <input type="number" class="form-control form-control-sm" id="manualLat" 
                                               placeholder="-15.3875" min="-90" max="90" step="0.00001">
                                    </div>
                                    <div class="col-6">
                                        <label for="manualLng" class="form-label form-label-sm">Longitude</label>
                                        <input type="number" class="form-control form-control-sm" id="manualLng" 
                                               placeholder="28.2883" min="-180" max="180" step="0.00001">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-dark" id="setCoordinatesBtn">
                                    <i class="fas fa-check me-1"></i>Set Coordinates
                                </button>
                            </div>

                            <!-- Route Planning -->
                            <div class="mb-3 p-3 bg-light rounded">
                                <h6 class="mb-3"><i class="fas fa-road me-2"></i>Route Planning</h6>
                                <div class="mb-2">
                                    <label for="startingPoint" class="form-label form-label-sm">Starting Point</label>
                                    <input type="text" class="form-control form-control-sm" id="startingPoint" 
                                           placeholder="Enter starting location or search...">
                                </div>
                                <div class="mb-2">
                                    <label for="endingPoint" class="form-label form-label-sm">Ending Point / Site Visit Location</label>
                                    <input type="text" class="form-control form-control-sm" id="endingPoint" 
                                           placeholder="Enter destination location or search...">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="showRouteBtn">
                                    <i class="fas fa-directions me-1"></i>Show Route
                                </button>
                                <small class="text-muted d-block mt-2">
                                    Press Enter in either field to search, or click Show Route to visualize the path.
                                </small>
                            </div>
                            
                            <!-- Google Map Display -->
                            <div class="map-container">
                                <div class="location-controls">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="useCurrentLocationBtn">
                                        <i class="fas fa-location-arrow me-2"></i>Use My Location (GPS)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="detectByIPBtn" <?php echo !$hasIPGeolocation ? 'disabled' : ''; ?>>
                                        <i class="fas fa-globe me-2"></i>Detect by IP
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="detectBeneficiaryLocationBtn">
                                        <i class="fas fa-map-marker-alt me-2"></i>Load Beneficiary Location
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearMapBtn">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                                
                                <div id="locationMap"></div>
                                
                                <div class="geolocation-status" id="geolocationStatus" style="display: none;"></div>
                                <div class="location-info" id="locationInfo" style="display: none;">
                                    <h6><i class="fas fa-info-circle me-2"></i>Location Details</h6>
                                    <div class="row small">
                                        <div class="col-6">
                                            <strong>Latitude:</strong>
                                            <div id="displayLatitude">-</div>
                                        </div>
                                        <div class="col-6">
                                            <strong>Longitude:</strong>
                                            <div id="displayLongitude">-</div>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <strong>Address:</strong>
                                            <div id="displayAddress">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="mb-4">
                            <label for="purpose" class="form-label">Purpose of Visit</label>
                            <textarea class="form-control" id="purpose" name="purpose" rows="4" 
                                      placeholder="Describe the purpose and objectives of this site visit..."
                                      required minlength="10"><?php echo $_POST['purpose'] ?? ''; ?></textarea>
                            <div class="invalid-feedback">Please provide a detailed purpose (minimum 10 characters)</div>
                            <div class="char-count mt-2" id="charCountContainer">
                                <small>Character count: <span id="charCount">0</span>/<span id="charMin">10</span></small>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional information or special requirements..."><?php echo $_POST['notes'] ?? ''; ?></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-calendar-check me-2"></i>Schedule Visit
                            </button>
                            <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Reset
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
                            <p class="small text-muted">Schedule visits during working hours (8:00 AM - 4:00 PM) for better coordination.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-map-marker-alt text-info me-2"></i>Location Details</h6>
                            <p class="small text-muted">Provide precise location details including landmarks for easy navigation.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-bullseye text-success me-2"></i>Clear Objectives</h6>
                            <p class="small text-muted">Define clear purpose and objectives to make the visit productive.</p>
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
                                    <span class="badge badge-<?php echo $project['status'] ?? 'secondary'; ?>">
                                        <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($projects) > 3): ?>
                                <div class="text-center">
                                    <a href="../projects/index.php" class="btn btn-sm btn-primary-custom">View All Projects</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No projects assigned</p>
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
        // Leaflet map functionality with Nominatim geocoding
        let map, marker, autocomplete;
        let currentLocationWatchId = null;
        let routeControl = null;
        let startMarker = null;
        let endMarker = null;
        const hasIPGeolocation = <?php echo $hasIPGeolocation ? 'true' : 'false'; ?>;

        function initMap() {
            const mapElement = document.getElementById('locationMap');
            if (!mapElement) return;

            try {
                // Default center for Zambia
                const defaultCenter = [-15.3875, 28.3228]; // [lat, lng] for Leaflet
                
                // Initialize Leaflet map
                map = L.map('locationMap').setView(defaultCenter, 8);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);

                // Add click listener to map for manual location selection
                map.on('click', function(event) {
                    const lat = event.latlng.lat;
                    const lng = event.latlng.lng;
                    placeMarker(lat, lng);
                    reverseGeocode(lat, lng);
                });

                // Load existing location if available
                const existingLat = parseFloat(document.getElementById('latitude').value);
                const existingLng = parseFloat(document.getElementById('longitude').value);
                
                if (existingLat && existingLng && existingLat !== 0 && existingLng !== 0) {
                    map.setView([existingLat, existingLng], 16);
                    placeMarker(existingLat, existingLng);
                    reverseGeocode(existingLat, existingLng);
                }

                setupLocationSearch();

            } catch(error) {
                console.error('Error initializing map:', error);
                mapElement.innerHTML = '<div class="alert alert-danger m-3">Error loading map: ' + error.message + '</div>';
            }
        }

        function setupLocationSearch() {
            const locationInput = document.getElementById('location');
            if (!locationInput) return;

            let searchTimeout;
            let autocompleteList = null;

            // Real-time location autocomplete as user types
            locationInput.addEventListener('input', function(e) {
                const address = this.value.trim();
                
                // Clear timeout from previous input
                clearTimeout(searchTimeout);
                
                // Remove autocomplete list if input is empty
                if (!address || address.length < 3) {
                    if (autocompleteList) {
                        autocompleteList.remove();
                        autocompleteList = null;
                    }
                    return;
                }
                
                // Debounce search to avoid too many API calls
                searchTimeout = setTimeout(() => {
                    searchLocations(address);
                }, 500);
            });

            // Handle Enter key - confirm selection
            locationInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const address = this.value;
                    if (address) {
                        // Remove autocomplete list when selecting
                        if (autocompleteList) {
                            autocompleteList.remove();
                            autocompleteList = null;
                        }
                        
                        geocodeAddress(address, function(lat, lng) {
                            if (lat !== null && lng !== null) {
                                // Ensure high precision
                                lat = parseFloat(lat).toFixed(8);
                                lng = parseFloat(lng).toFixed(8);
                                
                                map.setView([lat, lng], 16);
                                placeMarker(lat, lng);
                                document.getElementById('latitude').value = lat;
                                document.getElementById('longitude').value = lng;
                                document.getElementById('displayLatitude').textContent = lat;
                                document.getElementById('displayLongitude').textContent = lng;
                                
                                // Show success message with coordinates
                                const statusElement = document.getElementById('geolocationStatus');
                                if (statusElement) {
                                    statusElement.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Location found: ' + address + ' (' + lat + ', ' + lng + ')';
                                    statusElement.className = 'geolocation-status alert alert-success';
                                    statusElement.style.display = 'block';
                                    setTimeout(() => { statusElement.style.display = 'none'; }, 5000);
                                }
                            } else {
                                const statusElement = document.getElementById('geolocationStatus');
                                if (statusElement) {
                                    statusElement.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Could not find address: ' + address;
                                    statusElement.className = 'geolocation-status alert alert-warning';
                                    statusElement.style.display = 'block';
                                    setTimeout(() => { statusElement.style.display = 'none'; }, 5000);
                                }
                            }
                        });
                    }
                }
            });

            // Function to search locations
            function searchLocations(query) {
                const url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query + ', Zambia') + '&limit=8&countrycodes=zm';
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'User-Agent': 'CDF-Management-System/1.0'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        displayAutocomplete(data);
                    } else {
                        if (autocompleteList) {
                            autocompleteList.remove();
                            autocompleteList = null;
                        }
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
            }

            // Function to display autocomplete suggestions
            function displayAutocomplete(results) {
                // Remove old autocomplete list
                if (autocompleteList) {
                    autocompleteList.remove();
                }
                
                // Create new autocomplete list
                autocompleteList = document.createElement('div');
                autocompleteList.id = 'locationAutocomplete';
                autocompleteList.style.cssText = 'position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
                
                results.forEach((result, index) => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding: 10px 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;';
                    
                    // Build display name
                    const displayName = result.display_name.split(',').slice(0, 3).join(',').trim();
                    
                    item.innerHTML = '<div style="font-weight: 500; color: #1a4e8a;">' + displayName + '</div><small style="color: #666; font-size: 0.85em;">Lat: ' + parseFloat(result.lat).toFixed(6) + ', Lng: ' + parseFloat(result.lon).toFixed(6) + '</small>';
                    
                    item.addEventListener('mouseover', function() {
                        this.style.backgroundColor = '#f0f0f0';
                    });
                    
                    item.addEventListener('mouseout', function() {
                        this.style.backgroundColor = 'white';
                    });
                    
                    item.addEventListener('click', function() {
                        const lat = parseFloat(result.lat).toFixed(8);
                        const lng = parseFloat(result.lon).toFixed(8);
                        
                        // Update input field
                        locationInput.value = displayName;
                        
                        // Update map and coordinates
                        map.setView([lat, lng], 16);
                        placeMarker(lat, lng);
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        document.getElementById('displayLatitude').textContent = lat;
                        document.getElementById('displayLongitude').textContent = lng;
                        
                        // Show success message
                        const statusElement = document.getElementById('geolocationStatus');
                        if (statusElement) {
                            statusElement.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Location selected: ' + displayName + ' (' + lat + ', ' + lng + ')';
                            statusElement.className = 'geolocation-status alert alert-success';
                            statusElement.style.display = 'block';
                            setTimeout(() => { statusElement.style.display = 'none'; }, 5000);
                        }
                        
                        // Remove autocomplete list
                        if (autocompleteList) {
                            autocompleteList.remove();
                            autocompleteList = null;
                        }
                    });
                    
                    autocompleteList.appendChild(item);
                });
                
                // Position autocomplete list
                const locationInputWrapper = locationInput.parentElement;
                locationInputWrapper.style.position = 'relative';
                locationInputWrapper.appendChild(autocompleteList);
            }

            // Close autocomplete when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target !== locationInput && autocompleteList) {
                    autocompleteList.remove();
                    autocompleteList = null;
                }
            });
        }

        function geocodeAddress(address, callback, retryCount = 0) {
            const formattedAddress = address + ', Zambia';
            const url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(formattedAddress) + '&limit=5&countrycodes=zm';
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'CDF-Management-System/1.0'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('API error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.length > 0) {
                    // Find the best result (prefer results with address details)
                    let bestResult = data[0];
                    
                    // Try to find a result with a specific address
                    for (let i = 0; i < data.length; i++) {
                        const result = data[i];
                        if (result.address && (result.address.road || result.address.city || result.address.town)) {
                            bestResult = result;
                            break;
                        }
                    }
                    
                    const lat = parseFloat(bestResult.lat);
                    const lng = parseFloat(bestResult.lon);
                    if (callback) callback(lat, lng);
                } else {
                    if (callback) callback(null, null);
                }
            })
            .catch(error => {
                console.error('Geocoding error:', error);
                
                // Retry with delay (max 2 retries)
                if (retryCount < 2) {
                    console.log('Retrying geocoding... Attempt ' + (retryCount + 2) + ' of 3');
                    setTimeout(() => {
                        geocodeAddress(address, callback, retryCount + 1);
                    }, 1000 + (retryCount * 500));
                    return;
                }
                
                // After 3 attempts, fail gracefully
                if (callback) callback(null, null);
            });
        }

        function placeMarker(lat, lng) {
            // Remove existing marker
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Ensure high precision for coordinates
            lat = parseFloat(lat).toFixed(8);
            lng = parseFloat(lng).toFixed(8);
            
            // Create new marker
            marker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);

            // Update coordinates on marker drag
            marker.on('dragend', function() {
                const pos = this.getLatLng();
                const dragLat = parseFloat(pos.lat).toFixed(8);
                const dragLng = parseFloat(pos.lon).toFixed(8);
                document.getElementById('latitude').value = dragLat;
                document.getElementById('longitude').value = dragLng;
                document.getElementById('displayLatitude').textContent = dragLat;
                document.getElementById('displayLongitude').textContent = dragLng;
                reverseGeocode(dragLat, dragLng);
            });

            // Update hidden fields and display with full precision
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('displayLatitude').textContent = lat;
            document.getElementById('displayLongitude').textContent = lng;
            document.getElementById('locationInfo').style.display = 'block';
        }

        function reverseGeocode(lat, lng, retryCount = 0) {
            // Ensure coordinates are valid numbers
            lat = parseFloat(lat);
            lng = parseFloat(lng);
            
            if (isNaN(lat) || isNaN(lng)) {
                console.error('Invalid coordinates:', lat, lng);
                return;
            }
            
            const url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1';
            
            const statusElement = document.getElementById('geolocationStatus');
            if (statusElement && retryCount === 0) {
                statusElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Getting address...';
                statusElement.className = 'geolocation-status alert alert-info';
            }
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'CDF-Management-System/1.0'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('API error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.address) {
                    // Try to build a meaningful address
                    let address = '';
                    
                    // Try different address components in order of preference
                    if (data.address.road) {
                        address = data.address.road;
                        if (data.address.house_number) address = data.address.house_number + ' ' + address;
                    } else if (data.address.neighbourhood) {
                        address = data.address.neighbourhood;
                    } else if (data.address.village) {
                        address = data.address.village;
                    } else if (data.address.town) {
                        address = data.address.town;
                    } else if (data.address.city) {
                        address = data.address.city;
                    } else if (data.address.county) {
                        address = data.address.county;
                    } else if (data.address.state) {
                        address = data.address.state;
                    } else if (data.address.country) {
                        address = data.address.country;
                    } else {
                        address = 'Location at ' + lat.toFixed(4) + ', ' + lng.toFixed(4);
                    }
                    
                    // Add city or town if not already included
                    if (address && (data.address.city || data.address.town)) {
                        const cityOrTown = data.address.city || data.address.town;
                        if (!address.includes(cityOrTown)) {
                            address += ', ' + cityOrTown;
                        }
                    }
                    
                    document.getElementById('location').value = address || 'Unknown Location';
                    
                    if (statusElement) {
                        statusElement.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Location: ' + (address || 'Unknown Location');
                        statusElement.className = 'geolocation-status alert alert-success';
                    }
                } else {
                    if (statusElement) {
                        statusElement.innerHTML = '<i class="fas fa-warning text-warning me-2"></i>Could not find address for coordinates (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + '). Using coordinates instead.';
                        statusElement.className = 'geolocation-status alert alert-warning';
                    }
                    document.getElementById('location').value = 'Location at ' + lat.toFixed(4) + ', ' + lng.toFixed(4);
                }
            })
            .catch(error => {
                console.error('Reverse geocoding error:', error);
                
                // Retry with delay (max 2 retries)
                if (retryCount < 2) {
                    console.log('Retrying reverse geocoding... Attempt ' + (retryCount + 2) + ' of 3');
                    setTimeout(() => {
                        reverseGeocode(lat, lng, retryCount + 1);
                    }, 1000 + (retryCount * 500));
                    return;
                }
                
                // After 3 attempts, fall back to using coordinates with retry message
                const fallbackAddress = 'Location at ' + lat.toFixed(4) + ', ' + lng.toFixed(4);
                document.getElementById('location').value = fallbackAddress;
                
                if (statusElement) {
                    // Show a more helpful message about manual entry
                    statusElement.innerHTML = '<i class="fas fa-info-circle text-info me-2"></i>Address lookup unavailable. You can manually enter the location or continue with coordinates.';
                    statusElement.className = 'geolocation-status alert alert-info';
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        if (statusElement) statusElement.style.display = 'none';
                    }, 5000);
                }
            });
        }

        // Location detection functions
        function useCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by this browser.');
                return;
            }

            const statusElement = document.getElementById('geolocationStatus');
            statusElement.style.display = 'block';
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Detecting your location...';
            statusElement.className = 'geolocation-status alert alert-info';

            // Stop any existing location watch
            if (currentLocationWatchId) {
                navigator.geolocation.clearWatch(currentLocationWatchId);
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            };

            currentLocationWatchId = navigator.geolocation.watchPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;

                    // Update map
                    if (map) {
                        map.setView([lat, lng], 16);
                        placeMarker(lat, lng);
                    }

                    // Reverse geocode to get address
                    reverseGeocode(lat, lng);
                    
                    statusElement.innerHTML = `<i class="fas fa-check-circle text-success me-2"></i>Location detected: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    statusElement.className = 'geolocation-status alert alert-success';
                },
                function(error) {
                    let errorMessage = 'Unknown error occurred';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Permission to access location was denied. Please enable location access in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is currently unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out. Please try again.';
                            break;
                    }
                    statusElement.innerHTML = `<i class="fas fa-exclamation-triangle text-danger me-2"></i>${errorMessage}`;
                    statusElement.className = 'geolocation-status alert alert-danger';
                    
                    if (currentLocationWatchId) {
                        navigator.geolocation.clearWatch(currentLocationWatchId);
                        currentLocationWatchId = null;
                    }
                },
                options
            );
        }

        // Detect location using IP Geolocation API
        function detectLocationByIP() {
            const statusElement = document.getElementById('geolocationStatus');
            statusElement.style.display = 'block';
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Detecting your location via IP...';
            statusElement.className = 'geolocation-status alert alert-info';

            fetch('../api/geolocate_officer.php')
                .then(response => {
                    if (!response.ok) throw new Error('API error: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.latitude && data.longitude) {
                        // Check if location is in Zambia
                        if (data.warning) {
                            statusElement.innerHTML = `<i class="fas fa-exclamation-triangle text-warning me-2"></i>${data.warning}`;
                            statusElement.className = 'geolocation-status alert alert-warning';
                        }

                        const lat = data.latitude;
                        const lng = data.longitude;
                        
                        if (map) {
                            map.setView([lat, lng], 14);
                            placeMarker(lat, lng);
                        }

                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        reverseGeocode(lat, lng);

                        statusElement.innerHTML = `
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Location detected:</strong> ${data.city}, ${data.state}, ${data.country} (${data.isp})
                        `;
                        statusElement.className = 'geolocation-status alert alert-success';

                    } else {
                        statusElement.innerHTML = `<i class="fas fa-times-circle text-danger me-2"></i>${data.message || 'Could not detect location'}`;
                        statusElement.className = 'geolocation-status alert alert-danger';
                    }
                })
                .catch(error => {
                    console.error('IP Geolocation Error:', error);
                    statusElement.innerHTML = `<i class="fas fa-exclamation-triangle text-danger me-2"></i>Could not detect location: ${error.message}`;
                    statusElement.className = 'geolocation-status alert alert-danger';
                });
        }

        function detectBeneficiaryLocation() {
            const projectId = document.getElementById('project_id').value;
            if (!projectId) {
                alert('Please select a project first.');
                return;
            }

            const project = projects[projectId];
            if (!project) {
                alert('Project information not found.');
                return;
            }

            const statusElement = document.getElementById('geolocationStatus');
            statusElement.style.display = 'block';
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Detecting beneficiary location...';
            statusElement.className = 'geolocation-status alert alert-info';

            // Use exact coordinates from project if available
            if (project.latitude && project.longitude && project.latitude !== 0 && project.longitude !== 0) {
                const lat = parseFloat(project.latitude);
                const lng = parseFloat(project.longitude);
                
                if (map) {
                    map.setView([lat, lng], 16);
                    placeMarker(lat, lng);
                    reverseGeocode(lat, lng);
                    statusElement.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Beneficiary location loaded from project coordinates';
                    statusElement.className = 'geolocation-status alert alert-success';
                }
            } 
            // Geocode the project location address
            else if (project.location && project.location !== 'Not specified') {
                geocodeAddress(project.location, function(lat, lng) {
                    if (lat !== null && lng !== null) {
                        if (map) {
                            map.setView([lat, lng], 16);
                            placeMarker(lat, lng);
                        }
                        
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        reverseGeocode(lat, lng);
                        
                        statusElement.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Beneficiary location detected from project address';
                        statusElement.className = 'geolocation-status alert alert-success';
                    } else {
                        statusElement.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Could not find exact beneficiary location. Please set manually.';
                        statusElement.className = 'geolocation-status alert alert-warning';
                    }
                });
            } else {
                statusElement.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>No location data available for this project.';
                statusElement.className = 'geolocation-status alert alert-warning';
            }
        }

        // Manual Coordinate Entry Functions
        function setCoordinatesFromInput() {
            const lat = parseFloat(document.getElementById('manualLat').value);
            const lng = parseFloat(document.getElementById('manualLng').value);
            
            if (isNaN(lat) || isNaN(lng)) {
                alert('Please enter valid coordinates.\nLatitude: -90 to 90\nLongitude: -180 to 180');
                return;
            }
            
            if (lat < -90 || lat > 90) {
                alert('Latitude must be between -90 and 90');
                return;
            }
            
            if (lng < -180 || lng > 180) {
                alert('Longitude must be between -180 and 180');
                return;
            }
            
            // Place marker on map
            if (map) {
                map.setView([lat, lng], 16);
                placeMarker(lat, lng);
                reverseGeocode(lat, lng);
            }
            
            const statusElement = document.getElementById('geolocationStatus');
            if (statusElement) {
                statusElement.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i>Coordinates set: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                statusElement.className = 'geolocation-status alert alert-success';
                statusElement.style.display = 'block';
                setTimeout(() => { statusElement.style.display = 'none'; }, 5000);
            }
        }

        // Route Planning Functions
        function showRoute() {
            const startingPoint = document.getElementById('startingPoint').value;
            const endingPoint = document.getElementById('endingPoint').value;
            
            if (!startingPoint && !endingPoint) {
                alert('Please enter at least a starting point or destination location.');
                return;
            }
            
            // If only one point, use current marker as the other
            if (!startingPoint && endingPoint) {
                const currentLat = parseFloat(document.getElementById('latitude').value);
                const currentLng = parseFloat(document.getElementById('longitude').value);
                
                if (currentLat && currentLng && currentLat !== 0 && currentLng !== 0) {
                    geocodeAddress(endingPoint, function(destLat, destLng) {
                        if (destLat && destLng) {
                            displayRoute([currentLat, currentLng], [destLat, destLng]);
                        } else {
                            alert('Could not find destination location');
                        }
                    });
                } else {
                    alert('Please set starting point first');
                }
            } else if (startingPoint && !endingPoint) {
                const currentLat = parseFloat(document.getElementById('latitude').value);
                const currentLng = parseFloat(document.getElementById('longitude').value);
                
                if (currentLat && currentLng && currentLat !== 0 && currentLng !== 0) {
                    geocodeAddress(startingPoint, function(startLat, startLng) {
                        if (startLat && startLng) {
                            displayRoute([startLat, startLng], [currentLat, currentLng]);
                        } else {
                            alert('Could not find starting location');
                        }
                    });
                } else {
                    alert('Please set destination location first');
                }
            } else {
                // Both points provided
                geocodeAddress(startingPoint, function(startLat, startLng) {
                    if (startLat && startLng) {
                        geocodeAddress(endingPoint, function(destLat, destLng) {
                            if (destLat && destLng) {
                                displayRoute([startLat, startLng], [destLat, destLng]);
                            } else {
                                alert('Could not find destination location');
                            }
                        });
                    } else {
                        alert('Could not find starting location');
                    }
                });
            }
        }

        function displayRoute(startCoords, endCoords) {
            // Remove existing route
            if (routeControl) {
                map.removeControl(routeControl);
            }
            
            // Remove old markers
            if (startMarker) map.removeLayer(startMarker);
            if (endMarker) map.removeLayer(endMarker);
            
            // Add start marker (green)
            startMarker = L.marker(startCoords, {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map).bindPopup('Starting Point').openPopup();
            
            // Add end marker (red)
            endMarker = L.marker(endCoords, {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map).bindPopup('Destination').openPopup();
            
            // Create routing control
            routeControl = L.Routing.control({
                waypoints: [
                    L.latLng(startCoords),
                    L.latLng(endCoords)
                ],
                routeWhileDragging: true,
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                lineOptions: {
                    styles: [
                        { color: '#1a4e8a', opacity: 0.8, weight: 5 }
                    ]
                }
            }).addTo(map);
            
            // Fit map to show both points
            const group = new L.featureGroup([startMarker, endMarker]);
            map.fitBounds(group.getBounds().pad(0.1));
            
            const statusElement = document.getElementById('geolocationStatus');
            if (statusElement) {
                statusElement.innerHTML = '<i class="fas fa-road text-success me-2"></i>Route displayed from starting point to destination.';
                statusElement.className = 'geolocation-status alert alert-success';
                statusElement.style.display = 'block';
                setTimeout(() => { statusElement.style.display = 'none'; }, 5000);
            }
        }
        
        function detectLocationByIP() {
            if (!hasIPGeolocation) {
                alert('IP Geolocation API not configured. Please configure the API key first.');
                return;
            }

            const statusElement = document.getElementById('geolocationStatus');
            statusElement.style.display = 'block';
            statusElement.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Detecting your location via IP...';
            statusElement.className = 'geolocation-status alert alert-info';

            fetch('../api/geolocate_officer.php')
                .then(response => {
                    if (!response.ok) throw new Error('API error: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.latitude && data.longitude) {
                        // Check if location is in Zambia
                        if (data.warning) {
                            statusElement.innerHTML = `<i class="fas fa-exclamation-triangle text-warning me-2"></i>${data.warning}`;
                            statusElement.className = 'geolocation-status alert alert-warning';
                        }

                        const lat = data.latitude;
                        const lng = data.longitude;
                        
                        if (map) {
                            map.setView([lat, lng], 14);
                            placeMarker(lat, lng);
                        }

                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        reverseGeocode(lat, lng);

                        statusElement.innerHTML = `
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Location detected:</strong> ${data.city}, ${data.state}, ${data.country} (${data.isp})
                        `;
                        statusElement.className = 'geolocation-status alert alert-success';

                    } else {
                        statusElement.innerHTML = `<i class="fas fa-times-circle text-danger me-2"></i>${data.message || 'Could not detect location'}`;
                        statusElement.className = 'geolocation-status alert alert-danger';
                    }
                })
                .catch(error => {
                    console.error('IP Geolocation Error:', error);
                    statusElement.innerHTML = `<i class="fas fa-exclamation-triangle text-danger me-2"></i>Could not detect location: ${error.message}`;
                    statusElement.className = 'geolocation-status alert alert-danger';
                });
        }

        // Project data for dynamic updates
        const projects = <?php echo json_encode(array_reduce($projects, function($carry, $project) {
            $carry[intval($project['id'])] = [
                'title' => $project['title'] ?? 'Untitled',
                'beneficiary' => $project['beneficiary_name'] ?? 'Unassigned',
                'beneficiary_id' => intval($project['beneficiary_id'] ?? 0),
                'location' => $project['location'] ?? 'Not specified',
                'latitude' => floatval($project['latitude'] ?? 0),
                'longitude' => floatval($project['longitude'] ?? 0),
                'status' => $project['status'] ?? 'Unknown',
                'progress' => intval($project['progress'] ?? 0)
            ];
            return $carry;
        }, [])); ?>;

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('visit_date').min = today;
            
            // Ensure date field has a value
            if (!document.getElementById('visit_date').value) {
                document.getElementById('visit_date').value = today;
            }
            
            // Ensure time field has a value
            if (!document.getElementById('visit_time').value) {
                document.getElementById('visit_time').value = '09:00';
            }
            
            updateCharCount();

            // Add event listeners
            document.getElementById('useCurrentLocationBtn').addEventListener('click', useCurrentLocation);
            document.getElementById('detectByIPBtn').addEventListener('click', detectLocationByIP);
            document.getElementById('detectBeneficiaryLocationBtn').addEventListener('click', detectBeneficiaryLocation);
            document.getElementById('clearMapBtn').addEventListener('click', clearMap);
            document.getElementById('purpose').addEventListener('input', updateCharCount);
            
            // Add coordinate entry listeners
            document.getElementById('setCoordinatesBtn').addEventListener('click', setCoordinatesFromInput);
            document.getElementById('manualLat').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') setCoordinatesFromInput();
            });
            document.getElementById('manualLng').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') setCoordinatesFromInput();
            });
            
            // Add route planning listeners
            document.getElementById('showRouteBtn').addEventListener('click', showRoute);
            document.getElementById('startingPoint').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    geocodeAddress(this.value, function(lat, lng) {
                        if (lat && lng && map) {
                            map.setView([lat, lng], 14);
                            if (startMarker) map.removeLayer(startMarker);
                            startMarker = L.marker([lat, lng]).addTo(map).bindPopup('Starting Point').openPopup();
                        }
                    });
                }
            });
            document.getElementById('endingPoint').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    geocodeAddress(this.value, function(lat, lng) {
                        if (lat && lng && map) {
                            map.setView([lat, lng], 14);
                            if (endMarker) map.removeLayer(endMarker);
                            endMarker = L.marker([lat, lng]).addTo(map).bindPopup('Destination').openPopup();
                        }
                    });
                }
            });
            
            // Add manual location input listener
            document.getElementById('location').addEventListener('blur', function() {
                if (this.value) {
                    geocodeAddress(this.value, function(lat, lng) {
                        if (lat !== null && lng !== null && map) {
                            map.setView([lat, lng], 16);
                            placeMarker(lat, lng);
                        }
                    });
                }
            });
            
            // Auto-detect beneficiary location when project changes
            document.getElementById('project_id').addEventListener('change', function(e) {
                if (e.target.value) {
                    updateProjectInfo(e.target.value);
                    // Auto-detect location after a short delay
                    setTimeout(() => {
                        detectBeneficiaryLocation();
                    }, 1000);
                }
            });

            // Initialize project info if project is already selected
            const projectId = document.getElementById('project_id').value;
            if (projectId) {
                updateProjectInfo(projectId);
                setTimeout(() => {
                    detectBeneficiaryLocation();
                }, 1500);
            }

            // Form validation
            const form = document.getElementById('scheduleForm');
            form.addEventListener('submit', function(event) {
                // Ensure date and time are set
                if (!document.getElementById('visit_date').value) {
                    document.getElementById('visit_date').value = today;
                }
                if (!document.getElementById('visit_time').value) {
                    document.getElementById('visit_time').value = '09:00';
                }
                
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Initialize Leaflet map
            initMap();
        });

        function updateProjectInfo(projectId) {
            const projectInfo = document.getElementById('projectInfo');
            const projectDetails = document.getElementById('projectDetails');

            if (projectId && projects[projectId]) {
                const project = projects[projectId];
                
                projectDetails.innerHTML = `
                    <div class="row small">
                        <div class="col-6">
                            <strong>Beneficiary:</strong><br>
                            ${project.beneficiary}
                        </div>
                        <div class="col-6">
                            <strong>Status:</strong><br>
                            <span class="badge badge-${project.status}">${project.status}</span>
                        </div>
                        <div class="col-12 mt-2">
                            <strong>Project Location:</strong><br>
                            ${project.location}
                        </div>
                        ${project.latitude && project.longitude ? `
                        <div class="col-12 mt-2">
                            <strong>Coordinates:</strong><br>
                            ${project.latitude.toFixed(6)}, ${project.longitude.toFixed(6)}
                        </div>
                        ` : ''}
                    </div>
                `;
                
                projectInfo.style.display = 'block';
            } else {
                projectInfo.style.display = 'none';
            }
        }

        function updateCharCount() {
            const purposeField = document.getElementById('purpose');
            const charCount = purposeField.value.length;
            document.getElementById('charCount').textContent = charCount;
            
            if (charCount < 10) {
                purposeField.classList.add('is-invalid');
            } else {
                purposeField.classList.remove('is-invalid');
            }
        }

        function clearMap() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            document.getElementById('location').value = '';
            document.getElementById('latitude').value = '0';
            document.getElementById('longitude').value = '0';
            document.getElementById('locationInfo').style.display = 'none';
            
            const statusElement = document.getElementById('geolocationStatus');
            statusElement.style.display = 'none';
            
            // Stop location tracking
            if (currentLocationWatchId) {
                navigator.geolocation.clearWatch(currentLocationWatchId);
                currentLocationWatchId = null;
            }
        }

        function resetForm() {
            document.getElementById('scheduleForm').reset();
            document.getElementById('projectInfo').style.display = 'none';
            updateCharCount();
            clearMap();
            
            // Reset form validation
            const form = document.getElementById('scheduleForm');
            form.classList.remove('was-validated');
            
            // Set default date to today and time to 09:00
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('visit_date').value = today;
            document.getElementById('visit_time').value = '09:00';
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
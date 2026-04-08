<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$projects = getAllProjects();
$notifications = getNotifications($_SESSION['user_id']);
$officers = getUsersByRole('officer');

/**
 * Get beneficiaries filtered by specific types
 */
function getFilteredBeneficiaries() {
    global $pdo;
    try {
        // Only get beneficiaries with women empowerment or youth empowerment types
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, beneficiary_type FROM users 
                             WHERE role = 'beneficiary' 
                             AND (beneficiary_type = 'women empowerment' OR beneficiary_type = 'youth empowerment')
                             ORDER BY first_name, last_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting filtered beneficiaries: " . $e->getMessage());
        // Fallback to all beneficiaries if column doesn't exist
        $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'beneficiary' ORDER BY first_name, last_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$beneficiaries = getFilteredBeneficiaries();

// Handle project actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $projectId = (int)($_GET['id'] ?? 0);
    
    if ($action === 'view' && $projectId) {
        // Get project data for viewing
        $viewProject = getProjectById($projectId);
        if (!$viewProject) {
            $_SESSION['error_message'] = "Project not found";
            redirect('projects.php');
        }
    } elseif ($action === 'edit' && $projectId) {
        // Get project data for editing
        $editProject = getProjectById($projectId);
    } elseif ($action === 'delete' && $projectId) {
        // Delete project
        if (deleteProject($projectId)) {
            $_SESSION['success_message'] = "Project deleted successfully";
        } else {
            $_SESSION['error_message'] = "Failed to delete project";
        }
        redirect('projects.php');
    } elseif ($action === 'assign_officer' && $projectId) {
        // Get project for officer assignment
        $assignProject = getProjectById($projectId);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
        redirect('projects.php');
        exit;
    }
    if (isset($_POST['create_project'])) {
        // Create new project
        $projectData = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'budget' => $_POST['budget'],
            'location' => $_POST['location'],
            'status' => $_POST['status'],
            'category' => $_POST['category'] ?? null,
            'beneficiary_id' => $_POST['beneficiary_id'] ?? null
        ];
        
        if (createProject($projectData)) {
            $_SESSION['success_message'] = "Project created successfully";
        } else {
            $_SESSION['error_message'] = "Failed to create project";
        }
        redirect('projects.php');
    } elseif (isset($_POST['update_project'])) {
        // Update existing project
        $projectId = $_POST['project_id'];
        $projectData = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'budget' => $_POST['budget'],
            'location' => $_POST['location'],
            'status' => $_POST['status'],
            'category' => $_POST['category'] ?? null,
            'beneficiary_id' => $_POST['beneficiary_id'] ?? null
        ];
        
        if (updateProject($projectId, $projectData)) {
            $_SESSION['success_message'] = "Project updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update project";
        }
        redirect('projects.php');
    } elseif (isset($_POST['assign_officer'])) {
        // Assign monitoring officer to project
        $projectId = $_POST['project_id'];
        $officerId = $_POST['officer_id'];
        
        if (assignOfficerToProject($projectId, $officerId)) {
            $_SESSION['success_message'] = "Monitoring officer assigned successfully";
        } else {
            $_SESSION['error_message'] = "Failed to assign monitoring officer";
        }
        redirect('projects.php');
    } elseif (isset($_POST['remove_officer'])) {
        // Remove monitoring officer from project
        $projectId = $_POST['project_id'];
        
        if (removeOfficerFromProject($projectId)) {
            $_SESSION['success_message'] = "Monitoring officer removed successfully";
        } else {
            $_SESSION['error_message'] = "Failed to remove monitoring officer";
        }
        redirect('projects.php');
    }
}

$pageTitle = isset($viewProject) ? "View Project - " . htmlspecialchars($viewProject['title']) : "Project Management - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Project management for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include_once '../includes/global_theme.php'; ?>
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
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
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

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
            transition: var(--transition);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
        }

        .navbar-brand img {
            filter: brightness(1.05) contrast(1.1) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
            transition: var(--transition);
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
            filter: brightness(1.15) contrast(1.2) drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: 8px;
            position: relative;
            font-size: 0.95rem;
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

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius);
            padding: 0.75rem 0;
            border: 1px solid rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
            border-radius: 6px;
            margin: 0 0.5rem;
            width: auto;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            transform: translateX(5px);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
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
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(233, 185, 73, 0.4);
        }

        .profile-info h1 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-info .lead {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: var(--dark);
            border: none;
            padding: 1rem 2rem;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition);
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, #c4952e 100%);
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
            border: 2px solid rgba(255, 255, 255, 0.7);
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            border-color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
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
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Project Cards */
        .project-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.25rem;
            transition: var(--transition);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .project-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
        }

        .progress-section {
            margin: 1.25rem 0;
        }

        .progress {
            height: 10px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .progress-bar {
            border-radius: 10px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .dashboard-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        /* Badge Colors */
        .badge-completed { 
            background: linear-gradient(135deg, var(--success) 0%, #24a140 100%);
            color: white;
        }
        .badge-in-progress { 
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: var(--dark);
        }
        .badge-delayed { 
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
        }
        .badge-planning { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        .badge-assigned { 
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            color: white;
        }
        .badge-unassigned {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: var(--dark);
        }

        .badge-category {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }

        .badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Table Improvements */
        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table th {
            border-top: none;
            font-weight: 700;
            color: var(--primary);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.25rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 1.25rem;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background: rgba(26, 78, 138, 0.03);
            transform: scale(1.01);
            transition: var(--transition);
        }

        /* Button Groups */
        .btn-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .btn-group .btn {
            border: none;
            padding: 0.5rem 0.75rem;
            transition: var(--transition);
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
        }

        /* Form Styling */
        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(26, 78, 138, 0.15);
            border-color: var(--primary);
        }

        /* Modal Styling */
        .modal-content {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
        }

        .modal-title {
            color: var(--primary);
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Officer Assignment Section */
        .officer-assignment {
            background: var(--info-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid var(--info);
        }

        .officer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .officer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Project View Styling */
        .project-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .project-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.05)"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }

        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .meta-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .meta-card .label {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }

        .meta-card .value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .info-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .info-card h6 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            top: 0.25rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid var(--white);
            box-shadow: 0 0 0 3px var(--primary);
        }

        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button:hover {
            background: var(--white);
            color: var(--primary);
        }

        /* Responsive */
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
                font-size: 1.75rem;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .table-responsive {
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-sm);
            }
            
            .officer-info {
                flex-direction: column;
                text-align: center;
            }
            
            .project-meta {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Alert Styling */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-sm);
        }

        /* Status Colors */
        .status-completed { color: var(--success); }
        .status-in-progress { color: var(--warning); }
        .status-delayed { color: var(--danger); }
        .status-planning { color: var(--primary); }
        
        /* Assignment Status */
        .assignment-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .assigned {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .unassigned {
            background: var(--warning-light);
            color: var(--warning);
            border: 1px solid var(--warning);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
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
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">System Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="notifications.php">No new notifications</a></li>
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
                                <i class="fas fa-cog me-2"></i>System Settings
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

    <?php if (isset($viewProject)): ?>
        <!-- Project View Header -->
        <section class="dashboard-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="profile-section">
                        <div class="profile-avatar">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="profile-info">
                            <h1><?php echo htmlspecialchars($viewProject['title']); ?></h1>
                            <p class="lead">Project Details & Information</p>
                            <div class="project-meta">
                                <div class="meta-card">
                                    <div class="label">Status</div>
                                    <div class="value">
                                        <span class="badge badge-<?php echo $viewProject['status']; ?>">
                                            <?php echo ucfirst($viewProject['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="meta-card">
                                    <div class="label">Budget</div>
                                    <div class="value">ZMW <?php echo number_format($viewProject['budget'], 0); ?></div>
                                </div>
                                <div class="meta-card">
                                    <div class="label">Location</div>
                                    <div class="value"><?php echo htmlspecialchars($viewProject['location']); ?></div>
                                </div>
                                <div class="meta-card">
                                    <div class="label">Created</div>
                                    <div class="value"><?php echo date('M j, Y', strtotime($viewProject['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="projects.php" class="back-button">
                            <i class="fas fa-arrow-left me-2"></i>Back to Projects
                        </a>
                        <a href="projects.php?action=edit&id=<?php echo $viewProject['id']; ?>" class="btn btn-primary-custom">
                            <i class="fas fa-edit me-2"></i>Edit Project
                        </a>
                        <?php if (empty($viewProject['officer_id'])): ?>
                            <a href="projects.php?action=assign_officer&id=<?php echo $viewProject['id']; ?>" class="btn btn-outline-custom">
                                <i class="fas fa-user-tie me-2"></i>Assign Officer
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#removeOfficerModal" data-project-id="<?php echo $viewProject['id']; ?>" data-project-title="<?php echo htmlspecialchars($viewProject['title']); ?>" data-officer-name="<?php echo htmlspecialchars($viewProject['officer_name']); ?>">
                                <i class="fas fa-user-minus me-2"></i>Remove Officer
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Project Details -->
        <div class="container mb-5">
            <div class="row">
                <!-- Left Column - Project Information -->
                <div class="col-lg-8">
                    <!-- Project Description -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Project Description</h5>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($viewProject['description'])); ?></p>
                        </div>
                    </div>

                    <!-- Project Timeline -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5><i class="fas fa-history me-2"></i>Project Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <h6>Project Created</h6>
                                    <p class="text-muted mb-1"><?php echo date('F j, Y g:i A', strtotime($viewProject['created_at'])); ?></p>
                                    <small>Project was created in the system</small>
                                </div>
                                <?php if ($viewProject['updated_at'] && $viewProject['updated_at'] != $viewProject['created_at']): ?>
                                <div class="timeline-item">
                                    <h6>Last Updated</h6>
                                    <p class="text-muted mb-1"><?php echo date('F j, Y g:i A', strtotime($viewProject['updated_at'])); ?></p>
                                    <small>Project details were last modified</small>
                                </div>
                                <?php endif; ?>
                                <?php if ($viewProject['officer_id']): ?>
                                <div class="timeline-item">
                                    <h6>Officer Assigned</h6>
                                    <p class="text-muted mb-1">Monitoring officer assigned to project</p>
                                    <small>Currently being monitored by <?php echo htmlspecialchars($viewProject['officer_name']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Project Details -->
                <div class="col-lg-4">
                    <!-- Project Details -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5><i class="fas fa-cog me-2"></i>Project Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-card">
                                <h6><i class="fas fa-map-marker-alt"></i>Location</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($viewProject['location']); ?></p>
                            </div>
                            
                            <div class="info-card">
                                <h6><i class="fas fa-money-bill-wave"></i>Budget</h6>
                                <p class="mb-0">ZMW <?php echo number_format($viewProject['budget'], 0); ?></p>
                            </div>
                            
                            <div class="info-card">
                                <h6><i class="fas fa-tasks"></i>Status</h6>
                                <p class="mb-0">
                                    <span class="badge badge-<?php echo $viewProject['status']; ?>">
                                        <?php echo ucfirst($viewProject['status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <?php if ($viewProject['beneficiary_name']): ?>
                            <div class="info-card">
                                <h6><i class="fas fa-user"></i>Beneficiary</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($viewProject['beneficiary_name']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Monitoring Officer -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-tie me-2"></i>Monitoring Officer</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($viewProject['officer_name']): ?>
                                <div class="officer-info">
                                    <div class="officer-avatar">
                                        <?php echo strtoupper(substr($viewProject['officer_first_name'], 0, 1) . substr($viewProject['officer_last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($viewProject['officer_name']); ?></h6>
                                        <small class="text-muted">Monitoring & Evaluation Officer</small>
                                        <br>
                                        <small class="text-info">
                                            <i class="fas fa-project-diagram"></i>
                                            Monitoring <?php echo getOfficerProjectCount($viewProject['officer_id']); ?> project(s)
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#removeOfficerModal" data-project-id="<?php echo $viewProject['id']; ?>" data-project-title="<?php echo htmlspecialchars($viewProject['title']); ?>" data-officer-name="<?php echo htmlspecialchars($viewProject['officer_name']); ?>">
                                        <i class="fas fa-user-minus me-2"></i>Remove Officer
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-user-times fa-2x text-muted mb-3"></i>
                                    <p class="text-muted mb-3">No monitoring officer assigned</p>
                                    <a href="projects.php?action=assign_officer&id=<?php echo $viewProject['id']; ?>" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-user-tie me-2"></i>Assign Officer
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="content-card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="projects.php?action=edit&id=<?php echo $viewProject['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Project
                                </a>
                                <?php if (empty($viewProject['officer_id'])): ?>
                                    <a href="projects.php?action=assign_officer&id=<?php echo $viewProject['id']; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-user-tie me-2"></i>Assign Officer
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#removeOfficerModal" data-project-id="<?php echo $viewProject['id']; ?>" data-project-title="<?php echo htmlspecialchars($viewProject['title']); ?>" data-officer-name="<?php echo htmlspecialchars($viewProject['officer_name']); ?>">
                                        <i class="fas fa-user-minus me-2"></i>Remove Officer
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProjectModal" data-project-id="<?php echo $viewProject['id']; ?>" data-project-title="<?php echo htmlspecialchars($viewProject['title']); ?>">
                                    <i class="fas fa-trash me-2"></i>Delete Project
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Regular Projects List View -->
        <!-- Dashboard Header -->
        <section class="dashboard-header">
            <div class="container">
                <div class="profile-section">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h1>Project Management</h1>
                        <p class="lead">Manage all CDF projects and their details</p>
                        <p class="mb-0">Total Projects: <strong><?php echo count($projects); ?></strong> | 
                        Unassigned Projects: <strong><?php echo count(array_filter($projects, function($p) { return empty($p['officer_id']); })); ?></strong></p>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="fas fa-plus-circle me-2"></i>Create New Project
                    </button>
                    <a href="projects.php" class="btn btn-outline-custom">
                        <i class="fas fa-sync-alt me-2"></i>Refresh List
                    </a>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="container mb-5">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Projects Table -->
            <div class="content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-project-diagram me-2"></i>All Projects</h5>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control" placeholder="Search projects..." id="searchInput">
                            <button class="btn btn-outline-primary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <select class="form-select" style="width: 180px;" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="planning">Planning</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="delayed">Delayed</option>
                        </select>
                        <select class="form-select" style="width: 180px;" id="assignmentFilter">
                            <option value="">All Assignments</option>
                            <option value="assigned">Assigned</option>
                            <option value="unassigned">Unassigned</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($projects) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="projectsTable">
                                <thead>
                                    <tr>
                                        <th>Project Title</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Category</th>
                                        <th>Budget</th>
                                        <th>Beneficiary</th>
                                        <th>Monitoring Officer</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['location'] ?? 'Not specified'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                                <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-category">
                                                <?php echo ucfirst($project['category'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td>ZMW <?php echo number_format($project['budget'], 0); ?></td>
                                        <td>
                                            <?php if ($project['beneficiary_name'] ?? false): ?>
                                            <span class="badge badge-assigned"><?php echo htmlspecialchars($project['beneficiary_name']); ?></span>
                                            <?php else: ?>
                                            <span class="badge badge-warning">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($project['officer_name'] ?? false): ?>
                                                <div class="assignment-status assigned">
                                                    <i class="fas fa-user-check"></i>
                                                    <?php echo htmlspecialchars($project['officer_name']); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="assignment-status unassigned">
                                                    <i class="fas fa-user-times"></i>
                                                    Not Assigned
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($project['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="projects.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="projects.php?action=edit&id=<?php echo $project['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (empty($project['officer_id'])): ?>
                                                    <a href="projects.php?action=assign_officer&id=<?php echo $project['id']; ?>" class="btn btn-outline-info" title="Assign Officer">
                                                        <i class="fas fa-user-tie"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#removeOfficerModal" data-project-id="<?php echo $project['id']; ?>" data-project-title="<?php echo htmlspecialchars($project['title']); ?>" data-officer-name="<?php echo htmlspecialchars($project['officer_name']); ?>" title="Remove Officer">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProjectModal" data-project-id="<?php echo $project['id']; ?>" data-project-title="<?php echo htmlspecialchars($project['title']); ?>" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Projects Created</h5>
                            <p class="text-muted mb-4">Get started by creating the first project.</p>
                            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                                <i class="fas fa-plus-circle me-2"></i>Create Project
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

    <!-- Modals (Create, Edit, Assign Officer, Remove Officer, Delete) -->
    <!-- These modals remain the same as in the previous code -->
    <!-- ... (Include all the modal code from the previous response) ... -->

    <!-- Remove Officer Modal -->
    <div class="modal fade" id="removeOfficerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-minus me-2"></i>Remove Monitoring Officer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="projects.php">
                    <?= csrfField() ?>
                    <div class="modal-body">
                        <p>Are you sure you want to remove the monitoring officer from this project?</p>
                        <div class="alert alert-info">
                            <strong>Project:</strong> <span id="removeProjectTitle"></span><br>
                            <strong>Officer:</strong> <span id="removeOfficerName"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="project_id" id="removeOfficerProjectId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="remove_officer" class="btn btn-danger">
                            <i class="fas fa-user-minus me-2"></i>Remove Officer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Project Modal -->
    <div class="modal fade" id="deleteProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this project? This action cannot be undone.</p>
                    <div class="alert alert-danger">
                        <strong>Project:</strong> <span id="deleteProjectTitle"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Project
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Project Modal -->
    <div class="modal fade" id="createProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Create New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="projects.php">
                    <?= csrfField() ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="budget" class="form-label">Budget (ZMW)</label>
                                <input type="number" class="form-control" id="budget" name="budget" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="planning">Planning</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="delayed">Delayed</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="women empowerment">Women Empowerment</option>
                                    <option value="youth empowerment">Youth Empowerment</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="beneficiary_id" class="form-label">Beneficiary</label>
                                <select class="form-select" id="beneficiary_id" name="beneficiary_id" required>
                                    <option value="">Select Beneficiary</option>
                                    <?php foreach ($beneficiaries as $beneficiary): ?>
                                    <option value="<?php echo (int)$beneficiary['id']; ?>">
                                        <?php echo htmlspecialchars(($beneficiary['first_name'] ?? '') . ' ' . ($beneficiary['last_name'] ?? '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_project" class="btn btn-primary-custom">
                            <i class="fas fa-plus-circle me-2"></i>Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div class="modal fade" id="editProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="projects.php">
                    <?= csrfField() ?>
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?php echo $editProject['id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required><?php echo htmlspecialchars($editProject['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_budget" class="form-label">Budget (ZMW)</label>
                                <input type="number" class="form-control" id="edit_budget" name="budget" min="0" value="<?php echo $editProject['budget'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="edit_location" name="location" value="<?php echo htmlspecialchars($editProject['location'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="planning" <?php echo ($editProject['status'] ?? '') === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                    <option value="in-progress" <?php echo ($editProject['status'] ?? '') === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo ($editProject['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="delayed" <?php echo ($editProject['status'] ?? '') === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_category" class="form-label">Category</label>
                                <select class="form-select" id="edit_category" name="category" required>
                                    <option value="" <?php echo !isset($editProject['category']) || $editProject['category'] === '' ? 'selected' : ''; ?>>Select Category</option>
                                    <option value="women empowerment" <?php echo ($editProject['category'] ?? '') === 'women empowerment' ? 'selected' : ''; ?>>Women Empowerment</option>
                                    <option value="youth empowerment" <?php echo ($editProject['category'] ?? '') === 'youth empowerment' ? 'selected' : ''; ?>>Youth Empowerment</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_beneficiary_id" class="form-label">Beneficiary</label>
                                <select class="form-select" id="edit_beneficiary_id" name="beneficiary_id" required>
                                    <option value="">Select Beneficiary</option>
                                    <?php foreach ($beneficiaries as $beneficiary): ?>
                                    <option value="<?php echo (int)$beneficiary['id']; ?>" <?php echo (int)($editProject['beneficiary_id'] ?? 0) === (int)$beneficiary['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($beneficiary['first_name'] ?? '') . ' ' . ($beneficiary['last_name'] ?? '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_project" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>Update Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Officer Modal -->
    <div class="modal fade" id="assignOfficerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Assign Monitoring Officer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="projects.php">
                    <?= csrfField() ?>
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?php echo $assignProject['id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="officer_id" class="form-label">Select Officer</label>
                            <select class="form-select" id="officer_id" name="officer_id" required>
                                <option value="">Choose an officer...</option>
                                <?php foreach ($officers as $officer): ?>
                                <option value="<?php echo $officer['id']; ?>"><?php echo htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_officer" class="btn btn-primary-custom">
                            <i class="fas fa-user-tie me-2"></i>Assign Officer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Delete Project Modal Handler
        const deleteProjectModal = document.getElementById('deleteProjectModal');
        if (deleteProjectModal) {
            deleteProjectModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const projectId = button.getAttribute('data-project-id');
                const projectTitle = button.getAttribute('data-project-title');
                
                document.getElementById('deleteProjectTitle').textContent = projectTitle;
                document.getElementById('confirmDeleteBtn').href = 'projects.php?action=delete&id=' + projectId;
            });
        }

        // Remove Officer Modal Handler
        const removeOfficerModal = document.getElementById('removeOfficerModal');
        if (removeOfficerModal) {
            removeOfficerModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const projectId = button.getAttribute('data-project-id');
                const projectTitle = button.getAttribute('data-project-title');
                const officerName = button.getAttribute('data-officer-name');
                
                document.getElementById('removeProjectTitle').textContent = projectTitle;
                document.getElementById('removeOfficerName').textContent = officerName;
                document.getElementById('removeOfficerProjectId').value = projectId;
            });
        }

        // Search and Filter Functionality (only for list view)
        <?php if (!isset($viewProject)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const assignmentFilter = document.getElementById('assignmentFilter');
            const projectsTable = document.getElementById('projectsTable');
            
            if (searchInput && projectsTable) {
                searchInput.addEventListener('keyup', filterProjects);
            }
            
            if (statusFilter && projectsTable) {
                statusFilter.addEventListener('change', filterProjects);
            }
            
            if (assignmentFilter && projectsTable) {
                assignmentFilter.addEventListener('change', filterProjects);
            }
            
            function filterProjects() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const assignmentValue = assignmentFilter.value;
                const rows = projectsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const title = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
                    const status = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                    const officerCell = rows[i].getElementsByTagName('td')[5];
                    const isAssigned = officerCell.textContent.includes('Not Assigned') ? 'unassigned' : 'assigned';
                    
                    const titleMatch = title.includes(searchTerm);
                    const statusMatch = statusValue === '' || status.includes(statusValue);
                    const assignmentMatch = assignmentValue === '' || isAssigned === assignmentValue;
                    
                    if (titleMatch && statusMatch && assignmentMatch) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
        <?php endif; ?>

        // Auto-show edit modal if editing
        <?php if (isset($editProject)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editProjectModal'));
            editModal.show();
        });
        <?php endif; ?>

        // Auto-show assign officer modal if assigning
        <?php if (isset($assignProject)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const assignModal = new bootstrap.Modal(document.getElementById('assignOfficerModal'));
            assignModal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
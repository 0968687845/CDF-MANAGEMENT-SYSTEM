<?php
require_once 'functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

/**
 * Calculate project progress automatically - MATCHING updates.php
 */
function calculateProjectProgress($projectId) {
    global $pdo;
    
    try {
        // Get the automated progress recommendation (same as updates.php)
        $ml_result = getRecommendedProgressPercentage($projectId);
        $automated_progress = isset($ml_result['recommended']) ? intval($ml_result['recommended']) : 0;
        
        // Get project information
        $stmt = $pdo->prepare("SELECT progress, budget FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If automated progress is 0, use stored progress value as fallback
        if ($automated_progress == 0 && isset($project['progress'])) {
            $automated_progress = intval($project['progress']);
        }
        
        // Update project progress in database (optional - uncomment if you want to auto-update)
        // $updateStmt = $pdo->prepare("UPDATE projects SET progress = ?, updated_at = NOW() WHERE id = ?");
        // $updateStmt->execute([$automated_progress, $projectId]);
        
        return $automated_progress;
    } catch (Exception $e) {
        error_log("Progress calculation error: " . $e->getMessage());
        
        // Fallback: Get current progress from database
        try {
            $stmt = $pdo->prepare("SELECT progress FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['progress'] ?? 0;
        } catch (Exception $e2) {
            return 0;
        }
    }
}

$userData = getUserData();
$projects = getBeneficiaryProjects($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id']);
$stats = getDashboardStats($_SESSION['user_id'], 'beneficiary');

// Calculate and update progress for all projects using the SAME method as updates.php
if (!empty($projects)) {
    foreach ($projects as &$project) {
        $project['progress'] = calculateProjectProgress($project['id'] ?? 0);
        
        // Also get additional stats for the dashboard display
        $project['total_expenses'] = getTotalProjectExpenses($project['id'] ?? 0);
        $project['budget_utilization'] = $project['budget'] > 0 ? 
            round(($project['total_expenses'] / $project['budget']) * 100, 1) : 0;
    }
}

// Get real-time activities
$recent_activities = getRecentActivities($_SESSION['user_id'], 5) ?? [];

$pageTitle = "Beneficiary Dashboard - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Beneficiary dashboard for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <style>
    :root {
        /* Color System - Enhanced Contrast */
        --primary: #1a4e8a;
        --primary-dark: #0d3a6c;
        --primary-light: #2c6cb0;
        --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        --secondary: #e9b949;
        --secondary-dark: #d4a337;
        --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
        
        /* Neutral Colors - Improved Readability */
        --light: #f8f9fa;
        --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        --dark: #212529;
        --gray-100: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-300: #dee2e6;
        --gray-400: #ced4da;
        --gray-500: #adb5bd;
        --gray-600: #6c757d;
        --gray-700: #495057;
        --gray-800: #343a40;
        --gray-900: #212529;
        
        /* Semantic Colors - Enhanced Visibility */
        --success: #28a745;
        --success-light: #d4edda;
        --success-dark: #1e7e34;
        --warning: #ffc107;
        --warning-light: #fff3cd;
        --warning-dark: #e0a800;
        --danger: #dc3545;
        --danger-light: #f8d7da;
        --danger-dark: #c82333;
        --info: #17a2b8;
        --info-light: #d1ecf1;
        --info-dark: #138496;
        --white: #ffffff;
        --black: #000000;
        
        /* Design Tokens */
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
        --shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.18);
        --shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.22);
        
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        
        --border-radius-sm: 8px;
        --border-radius: 12px;
        --border-radius-lg: 16px;
        --border-radius-xl: 20px;
        
        /* Typography Scale - Enhanced Readability */
        --text-xs: 0.75rem;
        --text-sm: 0.875rem;
        --text-base: 1rem;
        --text-lg: 1.125rem;
        --text-xl: 1.25rem;
        --text-2xl: 1.5rem;
        --text-3xl: 1.875rem;
        --text-4xl: 2.25rem;
        --text-5xl: 3rem;
        
        /* Spacing Scale */
        --space-1: 0.25rem;
        --space-2: 0.5rem;
        --space-3: 0.75rem;
        --space-4: 1rem;
        --space-5: 1.25rem;
        --space-6: 1.5rem;
        --space-8: 2rem;
        --space-10: 2.5rem;
        --space-12: 3rem;
        --space-16: 4rem;
        --space-20: 5rem;
    }

    /* Reset and Base Styles */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html {
        scroll-behavior: smooth;
        font-size: 16px;
        line-height: 1.6;
    }

    body {
        font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        background-attachment: fixed;
        color: var(--gray-900);
        line-height: 1.7;
        font-weight: 400;
        min-height: 100vh;
        position: relative;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* Enhanced Background Pattern */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
            radial-gradient(circle at 20% 80%, rgba(26, 78, 138, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(233, 185, 73, 0.05) 0%, transparent 50%);
        pointer-events: none;
        z-index: -1;
    }

    /* Enhanced Typography Hierarchy - High Contrast */
    h1, .h1 {
        font-size: var(--text-5xl);
        font-weight: 800;
        line-height: 1.1;
        color: var(--white);
        margin-bottom: var(--space-6);
        letter-spacing: -0.025em;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    h2, .h2 {
        font-size: var(--text-4xl);
        font-weight: 700;
        line-height: 1.2;
        color: var(--primary-dark);
        margin-bottom: var(--space-5);
        letter-spacing: -0.02em;
    }

    h3, .h3 {
        font-size: var(--text-3xl);
        font-weight: 600;
        line-height: 1.3;
        color: var(--primary);
        margin-bottom: var(--space-4);
    }

    h4, .h4 {
        font-size: var(--text-2xl);
        font-weight: 600;
        line-height: 1.4;
        color: var(--gray-800);
        margin-bottom: var(--space-4);
    }

    h5, .h5 {
        font-size: var(--text-xl);
        font-weight: 600;
        line-height: 1.4;
        color: var(--gray-800);
        margin-bottom: var(--space-3);
    }

    h6, .h6 {
        font-size: var(--text-lg);
        font-weight: 600;
        line-height: 1.5;
        color: var(--gray-700);
        margin-bottom: var(--space-2);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    p {
        margin-bottom: var(--space-4);
        color: var(--gray-700);
        line-height: 1.7;
        font-size: var(--text-base);
    }

    .lead {
        font-size: var(--text-xl);
        font-weight: 400;
        color: var(--white);
        line-height: 1.6;
        opacity: 0.95;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .text-muted {
        color: var(--gray-600) !important;
        opacity: 0.9;
    }

    /* Enhanced Navigation */
    .navbar {
        background: var(--primary-gradient);
        box-shadow: var(--shadow-lg);
        padding: var(--space-3) 0;
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .navbar-brand {
        font-weight: 800;
        color: var(--white) !important;
        display: flex;
        align-items: center;
        gap: var(--space-3);
        transition: var(--transition);
        font-size: var(--text-lg);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.95) !important;
        font-weight: 600;
        transition: var(--transition);
        padding: var(--space-3) var(--space-4) !important;
        border-radius: var(--border-radius-sm);
        position: relative;
        overflow: hidden;
        font-size: var(--text-base);
    }

    .nav-link::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 3px;
        background: var(--secondary);
        transition: var(--transition);
        transform: translateX(-50%);
    }

    .nav-link:hover::before,
    .nav-link:focus::before,
    .nav-link.active::before {
        width: 80%;
    }

    .nav-link:hover, 
    .nav-link:focus,
    .nav-link.active {
        color: var(--white) !important;
        background-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    /* Enhanced Dashboard Header */
    .dashboard-header {
        background: var(--primary-gradient);
        color: var(--white);
        padding: var(--space-20) 0 var(--space-16);
        margin-top: 76px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%),
            url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="0,0 1000,100 1000,0"/></svg>');
        background-size: cover;
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-10px) rotate(1deg); }
    }

    .profile-section {
        display: flex;
        align-items: center;
        gap: var(--space-8);
        margin-bottom: var(--space-8);
        position: relative;
        z-index: 2;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: var(--secondary-gradient);
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

    /* FIXED: Profile Info Section - Improved Visibility */
    .profile-info h1 {
        font-size: var(--text-4xl);
        font-weight: 800;
        margin-bottom: var(--space-3);
        color: var(--white) !important;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        line-height: 1.2;
    }

    .profile-info .lead {
        font-size: var(--text-xl);
        font-weight: 500;
        color: rgba(255, 255, 255, 0.95) !important;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        margin-bottom: var(--space-4);
        line-height: 1.4;
    }

    .profile-info p:last-of-type {
        color: rgba(255, 255, 255, 0.9) !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        font-size: var(--text-lg);
        margin-bottom: 0;
    }

    .profile-info strong {
        color: var(--secondary) !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    /* FIXED: Action Buttons - Improved Visibility */
    .action-buttons {
        display: flex;
        gap: var(--space-4);
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    .action-buttons .btn-primary-custom {
        background: var(--secondary-gradient);
        color: var(--dark) !important;
        border: none;
        padding: var(--space-4) var(--space-6);
        font-weight: 700;
        border-radius: var(--border-radius);
        transition: var(--transition);
        box-shadow: var(--shadow);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        font-size: var(--text-base);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .action-buttons .btn-primary-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: var(--transition-slow);
    }

    .action-buttons .btn-primary-custom:hover::before {
        left: 100%;
    }

    .action-buttons .btn-primary-custom:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
        background: var(--secondary-gradient);
    }

    .action-buttons .btn-outline-custom {
        background: transparent;
        color: var(--white) !important;
        border: 2px solid rgba(255, 255, 255, 0.8);
        padding: var(--space-4) var(--space-6);
        font-weight: 600;
        border-radius: var(--border-radius);
        transition: var(--transition);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
        font-size: var(--text-base);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .action-buttons .btn-outline-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 0;
        height: 100%;
        background: rgba(255, 255, 255, 0.15);
        transition: var(--transition);
        z-index: -1;
    }

    .action-buttons .btn-outline-custom:hover::before {
        width: 100%;
    }

    .action-buttons .btn-outline-custom:hover {
        color: var(--white) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        border-color: var(--white);
    }

    /* Ensure proper contrast for all text in dashboard header */
    .dashboard-header {
        color: var(--white) !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    }

    .dashboard-header * {
        color: inherit !important;
    }

    /* Enhanced Stats Cards */
    .stats-container {
        margin: var(--space-8) 0;
    }

    .stat-card {
        background: var(--white);
        border-radius: var(--border-radius-lg);
        padding: var(--space-8) var(--space-6);
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
        height: 100%;
        border-top: 5px solid var(--primary);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--primary-gradient);
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-hover);
    }

    .stat-number {
        font-size: var(--text-4xl);
        font-weight: 800;
        color: var(--primary);
        margin-bottom: var(--space-3);
        line-height: 1;
    }

    .stat-title {
        font-size: var(--text-lg);
        color: var(--gray-700);
        margin-bottom: var(--space-2);
        font-weight: 600;
    }

    .stat-subtitle {
        font-size: var(--text-sm);
        color: var(--gray-600);
        opacity: 0.8;
    }

    /* Enhanced Content Cards */
    .content-card {
        background: var(--white);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow);
        margin-bottom: var(--space-8);
        overflow: hidden;
        transition: var(--transition);
        border: 1px solid rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
    }

    .content-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-4px);
        border-color: var(--primary-light);
    }

    .card-header {
        background: var(--light-gradient);
        border-bottom: 4px solid var(--primary);
        padding: var(--space-6) var(--space-8);
        position: relative;
        overflow: hidden;
    }

    .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 6px;
        height: 100%;
        background: var(--primary-gradient);
    }

    .card-header h5 {
        color: var(--primary-dark);
        font-weight: 800;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: var(--space-4);
        font-size: var(--text-xl);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .card-header h5 i {
        color: var(--secondary);
        font-size: 1.3em;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    }

    .card-body {
        padding: var(--space-8);
    }

    /* Enhanced Admin Tools */
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: var(--space-6);
        margin-bottom: var(--space-6);
    }

    .tool-card {
        background: var(--white);
        border: none;
        border-radius: var(--border-radius);
        padding: var(--space-8) var(--space-6);
        text-align: center;
        transition: var(--transition);
        box-shadow: var(--shadow);
        cursor: pointer;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-left: 5px solid var(--primary);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .tool-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: var(--transition-slow);
    }

    .tool-card:hover::before {
        left: 100%;
    }

    .tool-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-hover);
    }

    .tool-card.success { border-left-color: var(--success); }
    .tool-card.warning { border-left-color: var(--warning); }
    .tool-card.info { border-left-color: var(--info); }
    .tool-card.danger { border-left-color: var(--danger); }

    .tool-icon {
        font-size: 3rem;
        margin-bottom: var(--space-5);
        color: var(--primary);
        transition: var(--transition);
    }

    .tool-card:hover .tool-icon {
        transform: scale(1.1);
    }

    .tool-card.success .tool-icon { color: var(--success); }
    .tool-card.warning .tool-icon { color: var(--warning); }
    .tool-card.info .tool-icon { color: var(--info); }
    .tool-card.danger .tool-icon { color: var(--danger); }

    .tool-card h6 {
        font-weight: 700;
        margin-bottom: var(--space-3);
        font-size: var(--text-lg);
        color: var(--gray-800);
    }

    /* Enhanced Buttons */
    .btn-primary-custom {
        background: var(--secondary-gradient);
        color: var(--dark);
        border: none;
        padding: var(--space-4) var(--space-6);
        font-weight: 700;
        border-radius: var(--border-radius);
        transition: var(--transition);
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
        font-size: var(--text-base);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .btn-primary-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: var(--transition-slow);
    }

    .btn-primary-custom:hover::before {
        left: 100%;
    }

    .btn-primary-custom:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
        background: var(--secondary-gradient);
    }

    .btn-outline-custom {
        background: transparent;
        color: var(--primary);
        border: 3px solid var(--primary);
        padding: var(--space-4) var(--space-6);
        font-weight: 700;
        border-radius: var(--border-radius);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        font-size: var(--text-base);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .btn-outline-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 0;
        height: 100%;
        background: var(--primary);
        transition: var(--transition);
        z-index: -1;
    }

    .btn-outline-custom:hover::before {
        width: 100%;
    }

    .btn-outline-custom:hover {
        color: var(--white);
        transform: translateY(-3px);
        box-shadow: var(--shadow);
        border-color: var(--primary);
    }

    /* Enhanced Footer */
    .dashboard-footer {
        background: var(--white);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        padding: var(--space-8);
        margin-top: var(--space-16);
        border-top: 4px solid var(--primary);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    /* Enhanced Table Styles */
    .table {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .table th {
        border-top: none;
        font-weight: 700;
        color: var(--primary);
        background: var(--light-gradient);
        padding: var(--space-5);
        font-size: var(--text-sm);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        padding: var(--space-5);
        vertical-align: middle;
        border-color: rgba(0, 0, 0, 0.05);
    }

    .table-hover tbody tr:hover {
        background: rgba(26, 78, 138, 0.03);
        transform: scale(1.01);
        transition: var(--transition);
    }

    /* Enhanced Badges */
    .badge-completed { 
        background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
        color: var(--white);
    }
    .badge-in-progress { 
        background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
        color: var(--dark);
    }
    .badge-delayed { 
        background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
        color: var(--white);
    }
    .badge-planning { 
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: var(--white);
    }
    .badge-assigned { 
        background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
        color: var(--white);
    }

    .badge {
        font-weight: 600;
        padding: var(--space-3) var(--space-4);
        border-radius: 20px;
        font-size: var(--text-xs);
        box-shadow: var(--shadow-sm);
    }

    /* Notification Badge */
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        font-size: var(--text-xs);
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

    /* Chart Container */
    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
        background: var(--white);
        border-radius: var(--border-radius);
        padding: var(--space-6);
        box-shadow: var(--shadow-sm);
    }

    /* Activity Items */
    .activity-item {
        padding: var(--space-6);
        border-left: 4px solid transparent;
        transition: var(--transition);
        border-radius: var(--border-radius);
        margin-bottom: var(--space-3);
        background: var(--white);
        box-shadow: var(--shadow-sm);
    }

    .activity-item:hover {
        background: rgba(13, 110, 253, 0.03);
        border-left-color: var(--primary);
        transform: translateX(8px);
        box-shadow: var(--shadow);
    }

    .activity-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-5);
        flex-shrink: 0;
        font-size: var(--text-lg);
        transition: var(--transition);
    }

    .activity-item:hover .activity-icon {
        transform: scale(1.1);
    }

    .activity-icon.primary { background: rgba(26, 78, 138, 0.1); color: var(--primary); }
    .activity-icon.success { background: rgba(40, 167, 69, 0.1); color: var(--success); }
    .activity-icon.warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
    .activity-icon.info { background: rgba(23, 162, 184, 0.1); color: var(--info); }

    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-6);
        margin-bottom: var(--space-6);
    }

    .action-card {
        background: var(--white);
        border: none;
        border-radius: var(--border-radius);
        padding: var(--space-6);
        text-align: center;
        transition: var(--transition);
        box-shadow: var(--shadow);
        cursor: pointer;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-left: 5px solid var(--primary);
        position: relative;
        overflow: hidden;
    }

    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transition: var(--transition-slow);
    }

    .action-card:hover::before {
        left: 100%;
    }

    .action-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: var(--shadow-hover);
        border-left-color: var(--secondary);
    }

    .action-icon {
        font-size: 2.5rem;
        margin-bottom: var(--space-4);
        color: var(--primary);
        transition: var(--transition);
    }

    .action-card:hover .action-icon {
        transform: scale(1.1);
        color: var(--secondary);
    }

    .action-card h6 {
        font-weight: 700;
        margin-bottom: 0;
        font-size: var(--text-base);
        color: var(--gray-800);
    }

    /* Project Card Styles */
    .project-card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: var(--space-6);
        transition: var(--transition);
        overflow: hidden;
        background: var(--white);
        position: relative;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    .project-status-badge {
        position: absolute;
        top: var(--space-4);
        right: var(--space-4);
        z-index: 2;
    }

    .progress-section {
        margin: var(--space-4) 0;
    }

    .progress {
        height: 12px;
        border-radius: var(--border-radius);
        background: var(--gray-200);
        overflow: hidden;
        box-shadow: inset var(--shadow-sm);
    }

    .progress-bar {
        border-radius: var(--border-radius);
        transition: width 0.6s ease;
        position: relative;
        overflow: hidden;
    }

    .progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    /* Budget Utilization Indicator */
    .budget-utilization {
        font-size: var(--text-sm);
        color: var(--gray-600);
        margin-top: var(--space-2);
    }

    .budget-utilization .text-success {
        color: var(--success) !important;
        font-weight: 600;
    }

    .budget-utilization .text-warning {
        color: var(--warning) !important;
        font-weight: 600;
    }

    .budget-utilization .text-danger {
        color: var(--danger) !important;
        font-weight: 600;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: var(--space-16) 0 var(--space-12);
        }
        
        .profile-section {
            flex-direction: column;
            text-align: center;
            gap: var(--space-6);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            font-size: 2rem;
        }
        
        .profile-info h1 {
            font-size: var(--text-3xl);
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .tools-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-4);
        }
        
        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-4);
        }
        
        .stat-number {
            font-size: var(--text-3xl);
        }
        
        .card-body {
            padding: var(--space-6);
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 0 var(--space-4);
        }
        
        .card-body {
            padding: var(--space-4);
        }
        
        .tools-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
        
        .btn-primary-custom,
        .btn-outline-custom {
            width: 100%;
            text-align: center;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }
    }

    /* High Contrast Mode Support */
    @media (prefers-contrast: high) {
        :root {
            --primary: #000080;
            --secondary: #ffa500;
            --gray-600: #000000;
            --gray-900: #000000;
        }
        
        .profile-info h1 {
            color: #000000 !important;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8) !important;
        }
    }

    /* Reduced Motion Support */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
        
        .progress-bar::after,
        .action-card::before,
        .tool-card::before {
            display: none;
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

    .stat-card,
    .content-card,
    .tool-card,
    .action-card,
    .project-card {
        animation: fadeInUp 0.6s ease-out;
    }
</style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Beneficiary Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>My Projects
                            </a></li>
                            <li><a class="dropdown-item" href="communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="support/help.php">
                                <i class="fas fa-question-circle me-2"></i>Help
                            </a></li>
                            <li><a class="dropdown-item" href="financial/expenses.php">
                                <i class="fas fa-receipt me-2"></i>My Expenses
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
                                    <a class="dropdown-item" href="communication/notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="communication/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="communication/notifications.php">No new notifications</a></li>
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
                                            <small class="text-muted">CDF Beneficiary</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="settings/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings/system.php">
                                <i class="fas fa-cog me-2"></i>Account Settings
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
                    <?php 
                    $firstName = $userData['first_name'] ?? '';
                    $lastName = $userData['last_name'] ?? '';
                    echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); 
                    ?>
                </div>
                <div class="profile-info">
                    <h1>Welcome, <?php echo htmlspecialchars($userData['first_name'] ?? 'Beneficiary'); ?>!</h1>
                    <p class="lead">CDF Beneficiary Dashboard - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Constituency: <strong><?php echo htmlspecialchars($userData['constituency'] ?? 'Not specified'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="projects/setup.php" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i>New Project
                </a>
                <a href="progress/updates.php" class="btn btn-outline-custom">
                    <i class="fas fa-sync-alt me-2"></i>Update Progress
                </a>
                <a href="communication/messages.php" class="btn btn-outline-custom">
                    <i class="fas fa-envelope me-2"></i>Messages
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_projects'] ?? count($projects); ?></div>
                        <div class="stat-title">Total Projects</div>
                        <div class="stat-subtitle">Active: <?php echo $stats['active_projects'] ?? 0; ?></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['completed_projects'] ?? 0; ?></div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-subtitle"><?php echo $stats['completion_rate'] ?? 0; ?>% Success Rate</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php 
                            // Calculate average progress using the SAME method as updates.php
                            $total_progress = 0;
                            $project_count = count($projects);
                            if ($project_count > 0) {
                                foreach ($projects as $project) {
                                    $total_progress += $project['progress'];
                                }
                                echo round($total_progress / $project_count, 1);
                            } else {
                                echo 0;
                            }
                        ?>%</div>
                        <div class="stat-title">Avg Progress</div>
                        <div class="stat-subtitle">Automated calculation</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['pending_tasks'] ?? 0; ?></div>
                        <div class="stat-title">Pending Tasks</div>
                        <div class="stat-subtitle">Require attention</div>
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
                <div class="quick-actions">
                    <div class="action-card" onclick="location.href='projects/setup.php'">
                        <i class="fas fa-plus-circle action-icon"></i>
                        <h6>New Project</h6>
                    </div>
                    <div class="action-card" onclick="location.href='progress/updates.php'">
                        <i class="fas fa-sync-alt action-icon"></i>
                        <h6>Update Progress</h6>
                    </div>
                    <div class="action-card" onclick="location.href='financial/expenses.php'">
                        <i class="fas fa-receipt action-icon"></i>
                        <h6>Record Expenses</h6>
                    </div>
                    <div class="action-card" onclick="location.href='communication/messages.php'">
                        <i class="fas fa-envelope action-icon"></i>
                        <h6>Messages</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Projects -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-project-diagram me-2"></i>My Projects</h5>
                        <a href="projects/setup.php" class="btn btn-primary-custom btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>New Project
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($projects) > 0): ?>
                            <?php foreach (array_slice($projects, 0, 3) as $project): ?>
                                <div class="project-card card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($project['title'] ?? 'Untitled Project'); ?></h5>
                                                <p class="card-text text-muted"><?php echo htmlspecialchars(substr($project['description'] ?? 'No description available', 0, 100)); ?>...</p>
                                            </div>
                                            <span class="badge project-status-badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                                <?php echo htmlspecialchars($project['status'] ?? 'Unknown'); ?>
                                            </span>
                                        </div>
                                        <div class="progress-section">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>
                                                    Progress: <?php echo $project['progress'] ?? 0; ?>% 
                                                    <span class="text-muted small">(Automated)</span>
                                                </small>
                                                <small>Budget: ZMW <?php echo number_format($project['budget'] ?? 0, 2); ?></small>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-<?php echo $project['status'] ?? 'planning'; ?>" 
                                                     style="width: <?php echo $project['progress'] ?? 0; ?>%">
                                                </div>
                                            </div>
                                            <div class="budget-utilization">
                                                <small>
                                                    Budget used: 
                                                    <?php 
                                                    $utilization = $project['budget_utilization'] ?? 0;
                                                    if ($utilization >= 90) {
                                                        echo '<span class="text-danger">' . $utilization . '%</span>';
                                                    } elseif ($utilization >= 75) {
                                                        echo '<span class="text-warning">' . $utilization . '%</span>';
                                                    } else {
                                                        echo '<span class="text-success">' . $utilization . '%</span>';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Started: <?php echo date('M j, Y', strtotime($project['start_date'] ?? 'now')); ?>
                                            </small>
                                            <div class="btn-group">
                                                <a href="projects/details.php?id=<?php echo $project['id'] ?? ''; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                                <a href="progress/updates.php?project_id=<?php echo $project['id'] ?? ''; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-sync-alt me-1"></i>Update
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="projects/index.php" class="btn btn-primary-custom">View All Projects</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Projects Yet</h5>
                                <p class="text-muted">Get started by creating your first project.</p>
                                <a href="projects/setup.php" class="btn btn-primary-custom">
                                    <i class="fas fa-plus-circle me-2"></i>Create Project
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity & Notifications -->
            <div class="col-lg-4">
                <!-- Recent Activity -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_activities) > 0): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon <?php echo $activity['type'] ?? 'primary'; ?>">
                                        <i class="fas fa-<?php echo $activity['icon'] ?? 'history'; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['title'] ?? 'Activity'); ?></h6>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($activity['description'] ?? 'No description'); ?></p>
                                        <small class="text-muted"><?php echo time_elapsed_string($activity['created_at'] ?? 'now'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bell me-2"></i>Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon primary">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?></h6>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($notification['message'] ?? 'No message'); ?></p>
                                        <small class="text-muted"><?php echo time_elapsed_string($notification['created_at'] ?? 'now'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-2">
                                <a href="communication/notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No notifications</p>
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
                    <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3">
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
        // Update server time every second
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
<?php
require_once '../functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);
$projects = getBeneficiaryProjects($_SESSION['user_id']);

// Handle progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_progress'])) {
        $project_id = $_POST['project_id'] ?? '';
        $description = $_POST['description'] ?? '';
        $challenges = $_POST['challenges'] ?? '';
        $next_steps = $_POST['next_steps'] ?? '';
        $milestone_id = $_POST['milestone_id'] ?? '';
        $milestone_status = $_POST['milestone_status'] ?? '';
        $achievements = $_POST['achievements'] ?? [];
        
        if (!empty($project_id) && !empty($description)) {
            // Validate required files
            $has_photos = !empty($_FILES['progress_photos']['name'][0]);
            $has_achievements = !empty($achievements) && count(array_filter($achievements)) > 0;
            
            if (!$has_photos) {
                $_SESSION['error'] = "At least one progress photo is required.";
            } elseif (!$has_achievements) {
                $_SESSION['error'] = "At least one achievement/milestone must be recorded.";
            } else {
                // Handle file uploads
                $uploaded_photos = [];
                if ($has_photos) {
                    $uploaded_photos = handleProgressPhotoUpload($_FILES['progress_photos'], $project_id);
                    if (empty($uploaded_photos)) {
                        $_SESSION['error'] = "Failed to upload progress photos. Please try again.";
                    }
                }
                
                // Only proceed if photos uploaded successfully
                if (!empty($uploaded_photos)) {
                    // Handle receipt upload (optional)
                    $receipt_path = null;
                    if (!empty($_FILES['receipt_file']['name'])) {
                        $receipt_path = handleReceiptUpload($_FILES['receipt_file'], $project_id);
                        if (!$receipt_path) {
                            $_SESSION['error'] = "Failed to upload receipt. Please try again.";
                        }
                    }
                    
                    // Only proceed if receipt upload was not attempted or was successful
                    if (empty($_FILES['receipt_file']['name']) || $receipt_path) {
                        // Get project information
                        $project = getProjectById($project_id);
                        $project_budget = $project['budget'] ?? 0;
                        $total_expenses = getTotalProjectExpenses($project_id);
                        $photo_count = count($uploaded_photos);
                        $achievement_count = count(array_filter($achievements));
                        
                        // Factor 1: Budget Utilization (0-100% based on expenses)
                        if ($project_budget > 0) {
                            $expense_percentage = min(100, ($total_expenses / $project_budget) * 100);
                        } else {
                            $expense_percentage = $total_expenses > 0 ? 50 : 0;
                        }
                        
                        // Factor 2: Photo Progress (0-100% based on photo count, 10 photos = 100%)
                        $photo_percentage = min(100, ($photo_count / 10) * 100);
                        
                        // Factor 3: Achievements Progress (0-100% based on achievement count, 5 achievements = 100%)
                        $achievement_percentage = min(100, ($achievement_count / 5) * 100);
                        
                        // Calculate AVERAGE of the three factors
                        $progress_percentage = round(($expense_percentage + $photo_percentage + $achievement_percentage) / 3, 2);
                        
                        // Ensure within bounds
                        $progress_percentage = max(0, min(100, $progress_percentage));
                        
                        if (submitProgressUpdate($project_id, $progress_percentage, $description, $_SESSION['user_id'], $challenges, $next_steps, $uploaded_photos, $receipt_path, $achievements)) {
                        
                        $success_message = "Progress updated successfully! Progress: {$progress_percentage}%";
                        if (count($uploaded_photos) > 0) {
                            $success_message .= " | {$uploaded_photos} photos";
                        }
                        if (count($achievements) > 0) {
                            $success_message .= " | {" . count($achievements) . "} achievements";
                        }
                        if (!empty($milestone_id) && $milestone_status === 'completed') {
                            $success_message .= " | Milestone completed";
                        }
                        
                        $_SESSION['success'] = $success_message;
                        
                        // Create notification for assigned officer
                        $project = getProjectById($project_id);
                        if ($project && $project['officer_id']) {
                            $message = 'Project "' . $project['title'] . '" progress updated to ' . round($progress_percentage) . '%';
                            if (count($achievements) > 0) {
                                $message .= ' | ' . count($achievements) . ' achievement(s) recorded';
                            }
                            createNotification($project['officer_id'], 'Project Progress Updated', $message);
                        }
                        
                        // Refresh projects data
                        $projects = getBeneficiaryProjects($_SESSION['user_id']);
                        
                        // Redirect to avoid form resubmission
                        redirect('updates.php?project_id=' . $project_id);
                        } else {
                            $_SESSION['error'] = "Failed to update progress. Please try again.";
                        }
                    }
                }
            }
        } else {
            $_SESSION['error'] = "Please fill in all required fields.";
        }
    }
    
    // Handle manual completion request
    if (isset($_POST['request_completion'])) {
        $project_id = $_POST['project_id'] ?? '';
        
        // Get the project and its automated progress
        $project = getProjectById($project_id);
        $ml_result = getRecommendedProgressPercentage($project_id);
        $automated_progress = isset($ml_result['recommended']) ? intval($ml_result['recommended']) : 0;
        
        // Check if progress is 100%
        if ($automated_progress >= 100) {
            // Mark project as completed
            $sql = "UPDATE projects SET status = 'completed', actual_end_date = NOW(), progress = 100 WHERE id = ?";
            $stmt = $GLOBALS['conn']->prepare($sql);
            $stmt->bind_param("i", $project_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Project marked as completed successfully!";
            } else {
                $_SESSION['error'] = "Failed to mark project as completed.";
            }
        } else {
            $_SESSION['error'] = "Cannot complete project. Progress must be 100% to mark as completed. Current progress: " . $automated_progress . "%";
        }
        
        redirect('updates.php?project_id=' . $project_id);
    }
}

// Get selected project for editing
$selected_project = null;
$project_progress = [];
$progress_breakdown = [];
$completion_check = [];
$project_milestones = [];
$total_expenses = 0;
$budget_utilization = 0;

if (isset($_GET['project_id'])) {
    $selected_project = getProjectById($_GET['project_id']);
    if ($selected_project) {
        $project_progress = getProjectProgress($_GET['project_id']);
        $progress_breakdown = array();
        $completion_check = array();
        $project_milestones = array();
        
        // Calculate total expenses for this project
        $total_expenses = getTotalProjectExpenses($_GET['project_id']);
        
        // Calculate budget utilization
        if ($selected_project['budget'] > 0) {
            $budget_utilization = ($total_expenses / $selected_project['budget']) * 100;
        }
        
        // Calculate automated progress recommendation
        $ml_result = getRecommendedProgressPercentage($_GET['project_id']);
        $automated_progress = isset($ml_result['recommended']) ? intval($ml_result['recommended']) : 0;
        
        // Get the timestamp of the most recent progress update
        $stmt = $pdo->prepare("SELECT created_at FROM project_progress WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$_GET['project_id']]);
        $latest_update = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_automated_update = $latest_update ? $latest_update['created_at'] : null;
        
        // If still 0, use the stored progress value as fallback
        if ($automated_progress == 0 && isset($selected_project['progress'])) {
            $automated_progress = intval($selected_project['progress']);
        }
        
        // --- AUTO-COMPLETE PROJECT IF PROGRESS HITS 100% ---
        if ($automated_progress >= 100 && $selected_project['status'] !== 'completed') {
            // Mark project as completed in DB
            $sql = "UPDATE projects SET status = 'completed', actual_end_date = NOW(), progress = 100 WHERE id = ?";
            $stmt = $GLOBALS['conn']->prepare($sql);
            $stmt->bind_param("i", $_GET['project_id']);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Project automatically marked as completed! No further updates allowed.";
                // Update local variable so UI disables form
                $selected_project['status'] = 'completed';
                $selected_project['progress'] = 100;
            }
        }
        
        // Add total expenses to the selected_project array for display
        $selected_project['total_expenses'] = $total_expenses;
        $selected_project['budget_utilization'] = round($budget_utilization, 1);
        $selected_project['automated_progress'] = $automated_progress;
        $selected_project['last_automated_update'] = $last_automated_update;
        
        // Populate completion check based on automated progress
        $completion_check['can_complete'] = ($automated_progress >= 100);
        $completion_check['reasons'] = [];
        
        if ($automated_progress < 100) {
            $completion_check['reasons'][] = "Progress must be 100% to mark project as completed (Current: " . $automated_progress . "%)";
        }
    }
}

$pageTitle = "Update Progress - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Update project progress for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        font-size: var(--text-4xl);
        font-weight: 800;
        line-height: 1.1;
        color: var(--white);
        margin-bottom: var(--space-4);
        letter-spacing: -0.025em;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
    }

    h2, .h2 {
        font-size: var(--text-3xl);
        font-weight: 700;
        line-height: 1.2;
        color: var(--primary-dark);
        margin-bottom: var(--space-5);
        letter-spacing: -0.02em;
    }

    h3, .h3 {
        font-size: var(--text-2xl);
        font-weight: 600;
        line-height: 1.3;
        color: var(--primary);
        margin-bottom: var(--space-4);
    }

    h4, .h4 {
        font-size: var(--text-xl);
        font-weight: 600;
        line-height: 1.4;
        color: var(--gray-800);
        margin-bottom: var(--space-4);
    }

    h5, .h5 {
        font-size: var(--text-lg);
        font-weight: 600;
        line-height: 1.4;
        color: var(--gray-800);
        margin-bottom: var(--space-3);
    }

    h6, .h6 {
        font-size: var(--text-base);
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
        font-size: var(--text-lg);
        font-weight: 500;
        color: rgba(255, 255, 255, 0.95) !important;
        line-height: 1.6;
        opacity: 0.95;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
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

    /* Enhanced Page Header */
    .page-header {
        background: var(--primary-gradient);
        color: var(--white);
        padding: var(--space-16) 0 var(--space-12);
        margin-top: 76px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .page-header::before {
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

    /* FIXED: Page Header Text Visibility */
    .page-header h1 {
        font-size: var(--text-4xl);
        font-weight: 800;
        margin-bottom: var(--space-3);
        color: var(--white) !important;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        line-height: 1.2;
    }

    .page-header .lead {
        font-size: var(--text-lg);
        font-weight: 500;
        color: rgba(255, 255, 255, 0.95) !important;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        margin-bottom: 0;
        line-height: 1.4;
    }

    /* FIXED: Action Buttons in Header */
    .action-buttons {
        display: flex;
        gap: var(--space-4);
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
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

    /* Ensure proper contrast for all text in page header */
    .page-header {
        color: var(--white) !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    }

    .page-header * {
        color: inherit !important;
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

    /* Enhanced Project Cards */
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
        transform: translateY(-8px);
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

    /* Enhanced Form Styles */
    .form-control, .form-select, .form-range {
        border: 2px solid var(--gray-300);
        border-radius: var(--border-radius);
        padding: var(--space-4) var(--space-5);
        transition: var(--transition);
        font-size: var(--text-base);
        background: var(--white);
        box-shadow: var(--shadow-sm);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.3rem rgba(26, 78, 138, 0.15);
        transform: translateY(-2px);
    }

    .form-range:focus {
        outline: none;
    }

    .form-range::-webkit-slider-thumb {
        background: var(--primary);
        border: none;
        border-radius: 50%;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .form-range::-webkit-slider-thumb:hover {
        background: var(--primary-dark);
        transform: scale(1.1);
    }

    .form-label {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: var(--space-3);
        font-size: var(--text-base);
    }

    .form-section {
        background: var(--light-gradient);
        border-radius: var(--border-radius);
        padding: var(--space-6);
        margin-bottom: var(--space-6);
        border-left: 4px solid var(--primary);
        box-shadow: var(--shadow-sm);
    }

    .form-section h6 {
        color: var(--primary);
        margin-bottom: var(--space-4);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    /* Enhanced File Upload Styles */
    .file-upload-area {
        border: 3px dashed var(--gray-300);
        border-radius: var(--border-radius-lg);
        padding: var(--space-8);
        text-align: center;
        transition: var(--transition);
        background: var(--light);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .file-upload-area::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(26, 78, 138, 0.05), transparent);
        transition: var(--transition-slow);
    }

    .file-upload-area:hover::before {
        left: 100%;
    }

    .file-upload-area:hover {
        border-color: var(--primary);
        background: rgba(26, 78, 138, 0.03);
        transform: translateY(-2px);
    }

    .file-upload-area.dragover {
        border-color: var(--primary);
        background: rgba(26, 78, 138, 0.08);
        transform: scale(1.02);
    }

    .file-upload-required {
        border-color: var(--danger) !important;
        background: rgba(220, 53, 69, 0.05) !important;
    }

    .file-upload-required .file-upload-icon {
        color: var(--danger) !important;
    }

    .file-upload-icon {
        font-size: 3.5rem;
        color: var(--gray-400);
        margin-bottom: var(--space-4);
        transition: var(--transition);
    }

    .file-upload-area:hover .file-upload-icon {
        color: var(--primary);
        transform: scale(1.1);
    }

    .file-preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: var(--space-4);
        margin-top: var(--space-4);
    }

    .file-preview-item {
        position: relative;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        background: var(--white);
    }

    .file-preview-item:hover {
        transform: scale(1.05);
        box-shadow: var(--shadow-hover);
    }

    .file-preview-image {
        width: 100%;
        height: 120px;
        object-fit: cover;
        display: block;
    }

    .file-preview-video {
        width: 100%;
        height: 120px;
        object-fit: cover;
        background: var(--gray-900);
    }

    .file-preview-remove {
        position: absolute;
        top: var(--space-2);
        right: var(--space-2);
        background: var(--danger);
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--text-sm);
        cursor: pointer;
        opacity: 0;
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .file-preview-item:hover .file-preview-remove {
        opacity: 1;
    }

    .file-preview-name {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: var(--space-2) var(--space-3);
        font-size: var(--text-xs);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 500;
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

    .badge {
        font-weight: 700;
        padding: var(--space-3) var(--space-4);
        border-radius: 20px;
        font-size: var(--text-xs);
        box-shadow: var(--shadow-sm);
        text-transform: uppercase;
        letter-spacing: 0.05em;
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

    /* Enhanced Progress History */
    .progress-history-item {
        padding: var(--space-6);
        border-left: 4px solid var(--primary);
        background: var(--light-gradient);
        border-radius: var(--border-radius);
        margin-bottom: var(--space-4);
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: hidden;
    }

    .progress-history-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: var(--transition-slow);
    }

    .progress-history-item:hover::before {
        left: 100%;
    }

    .progress-history-item:hover {
        background: rgba(13, 110, 253, 0.05);
        transform: translateX(var(--space-2));
        box-shadow: var(--shadow);
    }

    .progress-history-item .progress-badge {
        background: var(--primary-gradient);
        color: white;
        padding: var(--space-2) var(--space-3);
        border-radius: var(--border-radius-sm);
        font-size: var(--text-sm);
        font-weight: 700;
        box-shadow: var(--shadow-sm);
    }

    .progress-photos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: var(--space-3);
        margin-top: var(--space-4);
    }

    .progress-photo-thumb {
        width: 100%;
        height: 80px;
        object-fit: cover;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        transition: var (--transition);
        box-shadow: var(--shadow-sm);
    }

    .progress-photo-thumb:hover {
        transform: scale(1.1);
        box-shadow: var(--shadow-hover);
    }

    /* Enhanced Alert Styles */
    .alert {
        border-radius: var(--border-radius);
        border: none;
        box-shadow: var(--shadow);
        padding: var(--space-5) var(--space-6);
        border-left: 4px solid;
        backdrop-filter: blur(10px);
        font-weight: 500;
    }

    .alert-success {
        background: var(--success-light);
        border-left-color: var (--success);
        color: var(--success-dark);
    }

    .alert-danger {
        background: var(--danger-light);
        border-left-color: var(--danger);
        color: var(--danger-dark);
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

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: var(--space-16);
        color: var(--gray-600);
    }

    .empty-state-icon {
        font-size: 4rem;
        color: var(--gray-400);
        margin-bottom: var(--space-4);
        opacity: 0.5;
    }

    /* Enhanced Modal Styles */
    .modal-content {
        border-radius: var(--border-radius-lg);
        border: none;
        box-shadow: var(--shadow-hover);
        overflow: hidden;
    }

    .modal-header {
        background: var(--light-gradient);
        border-bottom: 3px solid var(--primary);
        padding: var(--space-6) var(--space-8);
    }

    .modal-header h5 {
        color: var(--primary-dark);
        font-weight: 800;
        margin-bottom: 0;
    }

    /* Progress Bar Colors */
    .progress-bar-planning { background: var(--primary-gradient); }
    .progress-bar-in-progress { background: var(--warning); }
    .progress-bar-completed { background: var(--success-gradient); }
    .progress-bar-delayed { background: var(--danger-gradient); }

    .success-gradient { background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%); }
    .danger-gradient { background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%); }

    .required-field::after {
        content: " *";
        color: var(--danger);
        font-weight: bold;
    }

    /* New Styles for Automated Progress */
    .progress-breakdown {
        background: var(--light-gradient);
        border-radius: var(--border-radius);
        padding: var(--space-5);
        margin-bottom: var(--space-5);
        border: 1px solid var(--gray-200);
    }

    .breakdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-3);
        padding: var(--space-3);
        background: white;
        border-radius: var(--border-radius-sm);
        box-shadow: var(--shadow-sm);
    }

    .breakdown-label {
        flex: 1;
        font-weight: 600;
        color: var(--gray-700);
    }

    .breakdown-value {
        font-weight: 700;
        color: var(--primary);
        min-width: 60px;
        text-align: right;
    }

    .breakdown-weight {
        font-size: var(--text-sm);
        color: var(--gray-600);
        margin-left: var(--space-3);
        min-width: 80px;
        text-align: right;
    }

    .completion-check {
        border-left: 4px solid var(--success);
    }

    .completion-check.warning {
        border-left-color: var(--warning);
    }

    .completion-check.danger {
        border-left-color: var(--danger);
    }

    .milestone-item {
        border-left: 4px solid var(--primary);
        transition: var(--transition);
    }

    .milestone-item.completed {
        border-left-color: var(--success);
        background: var(--success-light);
    }

    .milestone-item.in-progress {
        border-left-color: var(--warning);
        background: var(--warning-light);
    }

    /* Achievement Styles */
    .achievement-item {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(233, 185, 73, 0.05) 100%);
        border: 2px solid rgba(40, 167, 69, 0.2) !important;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .achievement-item:hover {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(233, 185, 73, 0.1) 100%);
        border-color: rgba(40, 167, 69, 0.4) !important;
        box-shadow: var(--shadow-sm);
    }

    .achievement-item input[type="text"] {
        border-left: 4px solid var(--success);
    }

    .achievement-item input[type="text"]:focus {
        border-left-color: var(--success);
    }

    /* Progress Increment Input */
    .input-group {
        box-shadow: var(--shadow-sm);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .input-group .form-control {
        border-right: none;
    }

    .input-group .input-group-text {
        background: var(--light-gradient);
        border-left: none;
        font-weight: 600;
        color: var(--primary);
        border-radius: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header {
            padding: var(--space-12) 0 var(--space-8);
            text-align: center;
        }
        
        .project-card {
            margin-bottom: var(--space-4);
        }
        
        .form-section {
            padding: var(--space-4);
        }
        
        .file-preview-container {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
        
        .card-body {
            padding: var(--space-6);
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .breakdown-item {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--space-2);
        }
        
        .breakdown-value, .breakdown-weight {
            text-align: left;
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 0 var(--space-4);
        }
        
        .card-body {
            padding: var(--space-4);
        }
        
        .btn-primary-custom,
        .btn-outline-custom {
            width: 100%;
            text-align: center;
        }
        
        .progress-history-item {
            padding: var(--space-4);
        }
        
        .file-upload-area {
            padding: var(--space-6) var(--space-4);
        }
        
        .file-preview-container {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
        
        h1 {
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
        .file-upload-area::before,
        .progress-history-item::before,
        .btn-primary-custom::before {
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

    .content-card,
    .project-card,
    .progress-history-item {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Small Button Styles */
    .btn-sm {
        padding: var(--space-2) var(--space-3);
        font-size: var(--text-sm);
        border-radius: var(--border-radius-sm);
    }

    /* Grid Layout Improvements */
    .action-buttons {
        display: flex;
        gap: var(--space-4);
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }

    /* Range Input Enhancement */
    .form-range {
        padding: var(--space-4) 0;
    }

    .form-range::-webkit-slider-track {
        background: var(--gray-300);
        border-radius: var(--border-radius);
        height: 8px;
    }

    .form-range::-webkit-slider-thumb {
        width: 20px;
        height: 20px;
        margin-top: -6px;
    }
</style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Beneficiary Portal
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
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>My Projects
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/notifications.php">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a></li>
                            <li><a class="dropdown-item active" href="updates.php">
                                <i class="fas fa-sync-alt me-2"></i>Update Progress
                            </a></li>
                            <li><a class="dropdown-item" href="../financial/expenses.php">
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
                                <li><a class="dropdown-item text-center" href="../communication/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="../communication/notifications.php">No new notifications</a></li>
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
                            <li><a class="dropdown-item" href="../settings/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="../settings/system.php">
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-sync-alt me-2"></i>Update Project Progress</h1>
                    <p class="lead mb-0">Track and update your CDF projects with automated progress calculation</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <a href="../projects/setup.php" class="btn btn-outline-custom">
                            <i class="fas fa-plus-circle me-2"></i>New Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Alert Messages -->
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
            <!-- Progress Update Form -->
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-edit me-2"></i>Update Progress</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($selected_project && $selected_project['status'] === 'completed'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                This project has been <strong>automatically marked as completed</strong> because progress reached 100%.<br>
                                <span class="text-muted">No further updates are allowed.</span>
                            </div>
                        <?php elseif ($selected_project): ?>
                            <form method="POST" action="" enctype="multipart/form-data" id="progressForm">
                                <div class="mb-3">
                                    <label for="project_id" class="form-label required-field">Select Project</label>
                                    <select class="form-select" id="project_id" name="project_id" required onchange="loadProjectProgress(this.value)">
                                        <option value="">Choose a project...</option>
                                        <?php foreach ($projects as $project): 
                                            // Calculate automated progress for each project in dropdown
                                            $auto_progress = getRecommendedProgressPercentage($project['id']);
                                            $display_progress = isset($auto_progress['recommended']) ? intval($auto_progress['recommended']) : intval($project['progress']);
                                        ?>
                                            <option value="<?php echo $project['id']; ?>" 
                                                <?php echo (isset($_GET['project_id']) && $_GET['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($project['title']); ?> 
                                                (Automated: <?php echo $display_progress; ?>%)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <?php if ($selected_project): ?>
                                <!-- Automated Progress Display -->
                                <div class="mb-4">
                                    <label class="form-label">Automated Progress Calculation</label>
                                    <div class="alert alert-info">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-robot me-2"></i>
                                                <strong>System Calculated Progress:</strong> 
                                                <span class="fw-bold text-primary fs-5">
                                                    <?php echo isset($selected_project['automated_progress']) ? intval($selected_project['automated_progress']) : (isset($selected_project['progress']) ? intval($selected_project['progress']) : 0); ?>%
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                Last updated: <?php echo $selected_project['last_automated_update'] ? date('M j, Y g:i A', strtotime($selected_project['last_automated_update'])) : 'Never'; ?>
                                            </small>
                                        </div>
                                        <small class="d-block mt-2">
                                            Progress is automatically calculated based on:
                                            <ul class="mb-0 mt-1">
                                                <li><strong>Budget Utilization (40%):</strong> Expenses recorded vs project budget</li>
                                                <li><strong>Photo Uploads (30%):</strong> Number of progress photos uploaded</li>
                                                <li><strong>Achievements/Milestones (30%):</strong> Milestones and achievements recorded</li>
                                            </ul>
                                        </small>
                                    </div>
                                </div>

                                <!-- Progress Breakdown -->
                                <?php if ($progress_breakdown): ?>
                                <div class="progress-breakdown mb-4">
                                    <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Progress Breakdown</h6>
                                    <?php foreach ($progress_breakdown['components'] as $component => $score): ?>
                                    <div class="breakdown-item">
                                        <span class="breakdown-label">
                                            <?php echo ucfirst($component); ?> Progress:
                                        </span>
                                        <span class="breakdown-value">
                                            <?php echo round($score, 1); ?>%
                                        </span>
                                        <span class="breakdown-weight">
                                            (Weight: <?php echo ($progress_breakdown['weights'][$component] * 100); ?>%)
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="breakdown-item" style="background: var(--primary-light); color: white;">
                                        <span class="breakdown-label fw-bold">
                                            Final Progress:
                                        </span>
                                        <span class="breakdown-value fw-bold">
                                            <?php echo $progress_breakdown['final_progress']; ?>%
                                        </span>
                                        <span class="breakdown-weight">
                                            Total
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Project Information -->
                                <div class="form-section">
                                    <h6><i class="fas fa-info-circle me-2"></i>Project Information</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Project:</strong> <?php echo htmlspecialchars($selected_project['title']); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge badge-<?php echo $selected_project['status'] ?? 'planning'; ?>">
                                                    <?php echo ucfirst($selected_project['status'] ?? 'Planning'); ?>
                                                </span>
                                            </p>
                                            <p><strong>Start Date:</strong> <?php echo date('M j, Y', strtotime($selected_project['actual_start_date'] ?? $selected_project['created_at'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Budget:</strong> ZMW <?php echo number_format($selected_project['budget'], 2); ?></p>
                                            <p><strong>Expenses:</strong> ZMW <?php echo number_format($selected_project['total_expenses'] ?? 0, 2); ?></p>
                                            <p><strong>Budget Used:</strong> <?php echo $selected_project['budget_utilization'] ?? 0; ?>%</p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($selected_project['estimated_completion_date']): ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <p><strong>Estimated Completion:</strong> 
                                                <?php echo date('M j, Y', strtotime($selected_project['estimated_completion_date'])); ?>
                                                <?php 
                                                    $today = new DateTime();
                                                    $completion_date = new DateTime($selected_project['estimated_completion_date']);
                                                    $days_remaining = $today->diff($completion_date)->days;
                                                    $is_overdue = $today > $completion_date;
                                                ?>
                                                <span class="badge <?php echo $is_overdue ? 'badge-delayed' : 'badge-in-progress'; ?> ms-2">
                                                    <?php echo $is_overdue ? 'Overdue by ' . $days_remaining . ' days' : $days_remaining . ' days remaining'; ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="progress-section mt-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Automated Progress</small>
                                            <small><?php echo isset($selected_project['automated_progress']) ? intval($selected_project['automated_progress']) : (isset($selected_project['progress']) ? intval($selected_project['progress']) : 0); ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-<?php echo $selected_project['status'] ?? 'planning'; ?>" 
                                                 style="width: <?php echo isset($selected_project['automated_progress']) ? intval($selected_project['automated_progress']) : (isset($selected_project['progress']) ? intval($selected_project['progress']) : 0); ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Milestone Selection -->
                                <?php if (!empty($project_milestones)): ?>
                                <div class="mb-3">
                                    <label for="milestone_id" class="form-label">Update Milestone (Optional)</label>
                                    <select class="form-select" id="milestone_id" name="milestone_id">
                                        <option value="">Select a milestone to update...</option>
                                        <?php foreach ($project_milestones as $milestone): ?>
                                        <option value="<?php echo $milestone['id']; ?>">
                                            <?php echo htmlspecialchars($milestone['title']); ?> 
                                            (<?php echo ucfirst($milestone['status']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="milestone_status" class="form-label">Milestone Status</label>
                                    <select class="form-select" id="milestone_status" name="milestone_status">
                                        <option value="">Select status...</option>
                                        <option value="in-progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <!-- Achievements/Milestones Section -->
                                <div class="form-section mb-4">
                                    <h6><i class="fas fa-trophy me-2"></i>Record Achievements/Milestones <span class="text-danger">*</span></h6>
                                    <p class="text-muted small mb-3">Add at least one achievement or milestone accomplished in this update. This is required to submit your progress.</p>
                                    
                                    <div id="achievementsContainer">
                                        <!-- Achievements will be added here dynamically -->
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addAchievementBtn">
                                        <i class="fas fa-plus me-1"></i>Add Achievement
                                    </button>
                                </div>

                                <!-- Progress Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label required-field">Progress Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Describe what has been accomplished since the last update. Include details about work completed, materials used, and any significant developments..." required></textarea>
                                    <small class="text-muted">Detailed descriptions help improve automated progress accuracy.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="challenges" class="form-label">Challenges Faced (Optional)</label>
                                    <textarea class="form-control" id="challenges" name="challenges" rows="3" 
                                              placeholder="Describe any challenges or obstacles encountered..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="next_steps" class="form-label">Next Steps (Optional)</label>
                                    <textarea class="form-control" id="next_steps" name="next_steps" rows="3" 
                                              placeholder="Outline the planned next steps..."></textarea>
                                </div>

                                <!-- Photo Upload Section -->
                                <div class="mb-4">
                                    <label class="form-label required-field">Progress Photos</label>
                                    <div class="file-upload-area file-upload-required" id="fileUploadArea">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h6>Drag & Drop Photos Here</h6>
                                        <p class="text-muted small mb-3">or click to browse</p>
                                        <p class="small text-muted">Supported formats: JPG, PNG, GIF, MP4, MOV<br>Max file size: 10MB</p>
                                        <p class="small text-danger fw-bold mt-2">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            At least one photo is required for progress validation
                                        </p>
                                        <input type="file" id="progress_photos" name="progress_photos[]" multiple 
                                               accept="image/*,video/*" class="d-none" onchange="handleFileSelect(this.files)" required>
                                    </div>
                                    <div class="file-preview-container" id="filePreviewContainer"></div>
                                    <div id="photoError" class="text-danger small mt-2 d-none">
                                        <i class="fas fa-exclamation-circle me-1"></i>At least one progress photo is required
                                    </div>
                                </div>

                                <!-- Receipt Upload Section (Optional) -->
                                <div class="mb-4">
                                    <label class="form-label">Upload Receipt (Optional)</label>
                                    <p class="text-muted small mb-2">Upload a receipt or supporting document (invoice, receipt, proof of purchase, etc.)</p>
                                    <div class="file-upload-area" id="receiptUploadArea">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <h6>Drag & Drop Receipt Here</h6>
                                        <p class="text-muted small mb-3">or click to browse</p>
                                        <p class="small text-muted">Supported formats: PDF, JPG, PNG<br>Max file size: 5MB</p>
                                        <input type="file" id="receipt_file" name="receipt_file" 
                                               accept=".pdf,.jpg,.jpeg,.png,image/pdf,image/jpeg,image/png" class="d-none" onchange="handleReceiptSelect(this)">
                                    </div>
                                    <div class="file-preview-container" id="receiptPreviewContainer"></div>
                                    <div id="receiptError" class="text-danger small mt-2 d-none">
                                        <i class="fas fa-exclamation-circle me-1"></i><span id="receiptErrorMsg"></span>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="update_progress" class="btn btn-primary-custom" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>Submit Progress Update
                                    </button>
                                </div>

                                <!-- Project Completion Request -->
                                <?php if ($selected_project['status'] !== 'completed'): ?>
                                <div class="mt-4 pt-4 border-top">
                                    <h6><i class="fas fa-flag-checkered me-2"></i>Project Completion</h6>
                                    <div class="alert <?php echo $completion_check['can_complete'] ? 'alert-success completion-check' : 'alert-warning completion-check warning'; ?>">
                                        <?php if ($completion_check['can_complete']): ?>
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Ready for Completion!</strong>
                                            <p class="mb-2">This project meets all completion criteria.</p>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="project_id" value="<?php echo $selected_project['id']; ?>">
                                                <button type="submit" name="request_completion" class="btn btn-success btn-sm">
                                                    <i class="fas fa-flag me-1"></i>Mark as Completed
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Not Ready for Completion</strong>
                                            <ul class="mb-2 mt-2">
                                                <?php foreach ($completion_check['reasons'] as $reason): ?>
                                                    <li><?php echo $reason; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <small>Continue with progress updates to meet completion criteria.</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Select a project to update its progress</p>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please select a project to update.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Project Progress History & Milestones -->
            <div class="col-lg-6">
                <?php if ($selected_project): ?>
                    <!-- Progress History -->
                    <div class="content-card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-history me-2"></i>Progress History</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $progress_history = getProjectProgress($selected_project['id']);
                            // Sort by most recent first to display latest updates first
                            usort($progress_history, function($a, $b) {
                                return strtotime($b['created_at']) - strtotime($a['created_at']);
                            });
                            if (count($progress_history) > 0): 
                            ?>
                                <?php foreach (array_slice($progress_history, 0, 5) as $index => $history): ?>
                                <div class="progress-history-item <?php echo ($index === 0) ? 'border-2 border-success' : ''; ?>" id="history-record-<?php echo $history['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="progress-badge"><?php echo $history['progress_percentage']; ?>%</span>
                                        <small class="text-muted"><?php echo time_elapsed_string($history['created_at']); ?></small>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($history['description']); ?></p>
                                    
                                    <?php if (!empty($history['challenges'])): ?>
                                        <p class="small text-muted mb-1">
                                            <strong>Challenges:</strong> <?php echo htmlspecialchars($history['challenges']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($history['next_steps'])): ?>
                                        <p class="small text-muted mb-2">
                                            <strong>Next Steps:</strong> <?php echo htmlspecialchars($history['next_steps']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Display Achievements/Milestones -->
                                    <?php if (!empty($history['achievements'])): 
                                        $achievements_list = json_decode($history['achievements'], true);
                                        if (is_array($achievements_list) && count($achievements_list) > 0): ?>
                                            <div class="mb-2 p-2 bg-light rounded">
                                                <strong class="small d-block mb-2">
                                                    <i class="fas fa-trophy me-1 text-warning"></i>Achievements/Milestones:
                                                </strong>
                                                <ul class="small mb-0 ms-3">
                                                    <?php foreach ($achievements_list as $achievement): ?>
                                                        <li><?php echo htmlspecialchars($achievement); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Display Progress Photos -->
                                    <?php if (!empty($history['photos'])): 
                                        $photos = json_decode($history['photos'], true);
                                        if (is_array($photos) && count($photos) > 0): ?>
                                            <div class="progress-photos-grid">
                                                <?php foreach (array_slice($photos, 0, 3) as $photo): ?>
                                                    <?php if (file_exists('../' . $photo)): ?>
                                                        <img src="../<?php echo htmlspecialchars($photo); ?>" 
                                                             alt="Progress Photo" 
                                                             class="progress-photo-thumb"
                                                             onclick="openPhotoModal('<?php echo htmlspecialchars($photo); ?>')">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (count($photos) > 3): ?>
                                                    <div class="progress-photo-thumb bg-primary text-white d-flex align-items-center justify-content-center">
                                                        <small>+<?php echo count($photos) - 3; ?> more</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($progress_history) > 5): ?>
                                    <div class="text-center mt-3">
                                        <a href="../projects/details.php?id=<?php echo $selected_project['id']; ?>" class="btn btn-outline-custom btn-sm">
                                            View Full History
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-history empty-state-icon"></i>
                                    <p>No progress updates yet</p>
                                    <p class="small text-muted">Start tracking your project progress by making your first update.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Milestones -->
                    <?php if (!empty($project_milestones)): ?>
                    <div class="content-card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-flag me-2"></i>Project Milestones</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($project_milestones as $milestone): ?>
                            <div class="progress-history-item milestone-item <?php echo $milestone['status']; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($milestone['title']); ?></h6>
                                    <span class="badge badge-<?php echo $milestone['status'] === 'completed' ? 'completed' : ($milestone['status'] === 'in-progress' ? 'in-progress' : 'planning'); ?>">
                                        <?php echo ucfirst($milestone['status']); ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-2"><?php echo htmlspecialchars($milestone['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Target: <?php echo date('M j, Y', strtotime($milestone['target_date'])); ?>
                                    </small>
                                    <small class="text-muted">
                                        Weight: <?php echo $milestone['weightage']; ?>%
                                    </small>
                                </div>
                                <?php if ($milestone['status'] === 'completed' && $milestone['completed_date']): ?>
                                    <small class="text-success mt-2">
                                        <i class="fas fa-check me-1"></i>
                                        Completed: <?php echo date('M j, Y', strtotime($milestone['completed_date'])); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Quick Project Overview -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-project-diagram me-2"></i>My Projects</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($projects) > 0): ?>
                            <?php foreach (array_slice($projects, 0, 3) as $project): 
                                // Calculate automated progress for each project
                                $auto_progress = getRecommendedProgressPercentage($project['id']);
                                $display_progress = isset($auto_progress['recommended']) ? intval($auto_progress['recommended']) : intval($project['progress']);
                            ?>
                            <div class="project-card card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h6>
                                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($project['description'] ?? 'No description', 0, 80)); ?>...</p>
                                        </div>
                                        <span class="badge project-status-badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                            <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </div>
                                    <div class="progress-section">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Progress: <?php echo $display_progress; ?>% <span class="text-muted small">(Automated)</span></small>
                                            <small>Budget: ZMW <?php echo number_format($project['budget'], 2); ?></small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-<?php echo $project['status'] ?? 'planning'; ?>" 
                                                 style="width: <?php echo $display_progress; ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Started: <?php echo date('M j, Y', strtotime($project['start_date'] ?? 'now')); ?>
                                        </small>
                                        <a href="?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-custom">
                                            <i class="fas fa-edit me-1"></i>Update
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($projects) > 3): ?>
                                <div class="text-center">
                                    <a href="../projects/index.php" class="btn btn-outline-custom btn-sm">View All Projects</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-project-diagram empty-state-icon"></i>
                                <p>No Projects Yet</p>
                                <p class="small text-muted">Get started by creating your first project.</p>
                                <a href="../projects/setup.php" class="btn btn-primary-custom btn-sm">
                                    <i class="fas fa-plus-circle me-2"></i>Create Project
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Progress Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" alt="Progress Photo" class="img-fluid" style="max-height: 70vh;">
                    <video id="modalVideo" src="" controls class="img-fluid d-none" style="max-height: 70vh;"></video>
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
        // File upload functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('progress_photos');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        const photoError = document.getElementById('photoError');
        const selectedFiles = new Map();

        // Click to upload
        fileUploadArea.addEventListener('click', () => fileInput.click());

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('dragover'), false);
        });

        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFileSelect(files);
        }

        function handleFileSelect(files) {
            for (let file of files) {
                if (validateFile(file)) {
                    addFilePreview(file);
                }
            }
            updateFileInput();
            validateForm();
        }

        function validateFile(file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
            const maxSize = 10 * 1024 * 1024; // 10MB

            if (!validTypes.includes(file.type)) {
                alert(`File type not supported: ${file.name}. Please use JPG, PNG, GIF, MP4, or MOV.`);
                return false;
            }

            if (file.size > maxSize) {
                alert(`File too large: ${file.name}. Maximum size is 10MB.`);
                return false;
            }

            return true;
        }

        function addFilePreview(file) {
            const fileId = Date.now() + Math.random();
            selectedFiles.set(fileId, file);

            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';
            previewItem.dataset.fileId = fileId;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="file-preview-image">
                        <button type="button" class="file-preview-remove" onclick="removeFile('${fileId}')">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="file-preview-name">${file.name}</div>
                    `;
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                previewItem.innerHTML = `
                    <video class="file-preview-video">
                        <source src="${URL.createObjectURL(file)}" type="${file.type}">
                    </video>
                    <button type="button" class="file-preview-remove" onclick="removeFile('${fileId}')">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="file-preview-name">${file.name}</div>
                `;
            }

            filePreviewContainer.appendChild(previewItem);
        }

        function removeFile(fileId) {
            selectedFiles.delete(fileId);
            document.querySelector(`[data-file-id="${fileId}"]`).remove();
            updateFileInput();
            validateForm();
        }

        function updateFileInput() {
            // Create a new DataTransfer object
            const dt = new DataTransfer();
            
            // Add all selected files to the DataTransfer object
            selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            
            // Update the file input files
            fileInput.files = dt.files;
        }

        // Form validation
        function validateForm() {
            const hasPhotos = selectedFiles.size > 0;
            const submitBtn = document.getElementById('submitBtn');
            
            // Show/hide error messages
            if (!hasPhotos) {
                photoError.classList.remove('d-none');
                fileUploadArea.classList.add('file-upload-required');
            } else {
                photoError.classList.add('d-none');
                fileUploadArea.classList.remove('file-upload-required');
            }
            
            // Enable/disable submit button
            submitBtn.disabled = !hasPhotos;
        }

        // Receipt upload functionality
        const receiptUploadArea = document.getElementById('receiptUploadArea');
        const receiptInput = document.getElementById('receipt_file');
        const receiptPreviewContainer = document.getElementById('receiptPreviewContainer');
        const receiptError = document.getElementById('receiptError');
        const receiptErrorMsg = document.getElementById('receiptErrorMsg');
        let selectedReceipt = null;

        // Click to upload receipt
        receiptUploadArea.addEventListener('click', () => receiptInput.click());

        // Drag and drop for receipt
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            receiptUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            receiptUploadArea.addEventListener(eventName, () => receiptUploadArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            receiptUploadArea.addEventListener(eventName, () => receiptUploadArea.classList.remove('dragover'), false);
        });

        receiptUploadArea.addEventListener('drop', handleReceiptDrop, false);

        function handleReceiptDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                handleReceiptSelect(files[0]);
            }
        }

        function handleReceiptSelect(input) {
            const file = input.files ? input.files[0] : input;
            receiptError.classList.add('d-none');
            receiptPreviewContainer.innerHTML = '';

            if (!file) return;

            // Validate receipt file
            const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!validTypes.includes(file.type)) {
                receiptErrorMsg.textContent = `File type not supported: ${file.name}. Please use PDF, JPG, or PNG.`;
                receiptError.classList.remove('d-none');
                receiptInput.value = '';
                selectedReceipt = null;
                return;
            }

            if (file.size > maxSize) {
                receiptErrorMsg.textContent = `File too large: ${file.name}. Maximum size is 5MB.`;
                receiptError.classList.remove('d-none');
                receiptInput.value = '';
                selectedReceipt = null;
                return;
            }

            // Store selected receipt
            selectedReceipt = file;

            // Create preview
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';

            if (file.type === 'application/pdf') {
                previewItem.innerHTML = `
                    <div class="file-preview-document">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <button type="button" class="file-preview-remove" onclick="removeReceipt()">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="file-preview-name">${file.name}</div>
                `;
            } else if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Receipt Preview" class="file-preview-image">
                        <button type="button" class="file-preview-remove" onclick="removeReceipt()">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="file-preview-name">${file.name}</div>
                    `;
                };
                reader.readAsDataURL(file);
            }

            receiptPreviewContainer.appendChild(previewItem);
        }

        function removeReceipt() {
            selectedReceipt = null;
            receiptInput.value = '';
            receiptPreviewContainer.innerHTML = '';
            receiptError.classList.add('d-none');
        }

        // Photo modal functionality
        function openPhotoModal(fileUrl) {
            const modalPhoto = document.getElementById('modalPhoto');
            const modalVideo = document.getElementById('modalVideo');
            
            // Hide all media types first
            modalPhoto.classList.add('d-none');
            modalVideo.classList.add('d-none');
            
            const fileExtension = fileUrl.toLowerCase().split('.').pop();
            
            if (['mp4', 'mov', 'avi'].includes(fileExtension)) {
                // Video file
                modalVideo.classList.remove('d-none');
                modalVideo.src = '../' + fileUrl;
            } else {
                // Image file
                modalPhoto.classList.remove('d-none');
                modalPhoto.src = '../' + fileUrl;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            modal.show();
        }

        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Auto-refresh page when success message appears to display new progress record
        window.addEventListener('load', function() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                // Check if this page load has a success message (meaning form was just submitted)
                const successText = successAlert.textContent;
                if (successText.includes('Progress updated successfully')) {
                    // Add visual highlight to latest record
                    setTimeout(function() {
                        const latestRecord = document.querySelector('.progress-history-item.border-2');
                        if (latestRecord) {
                            latestRecord.style.backgroundColor = '#d4edda';
                            latestRecord.style.animation = 'pulse 2s infinite';
                        }
                    }, 500);
                    
                    // Auto-scroll to progress history section
                    const historySection = document.querySelector('.progress-history-item');
                    if (historySection) {
                        setTimeout(function() {
                            historySection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 1000);
                    }
                }
            }
        });

        // Add pulse animation for latest record
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }
        `;
        document.head.appendChild(style);

        // Load project progress when selected from dropdown
        function loadProjectProgress(projectId) {
            if (projectId) {
                // Redirect to the project page to load data
                window.location.href = '?project_id=' + projectId;
            }
        }

        // Update milestone status based on selection
        document.addEventListener('DOMContentLoaded', function() {
            const milestoneId = document.getElementById('milestone_id');
            const milestoneStatus = document.getElementById('milestone_status');
            
            if (milestoneId && milestoneStatus) {
                // Disable milestone status initially if no milestone selected
                if (!milestoneId.value) {
                    milestoneStatus.disabled = true;
                }
                
                milestoneId.addEventListener('change', function() {
                    if (this.value) {
                        milestoneStatus.disabled = false;
                    } else {
                        milestoneStatus.disabled = true;
                        milestoneStatus.value = '';
                    }
                });
            }
            
            // Initialize achievements functionality
            initializeAchievements();
            validateForm();
        });

        // Achievements Management
        let achievementCount = 0;

        function initializeAchievements() {
            const addBtn = document.getElementById('addAchievementBtn');
            if (addBtn) {
                addBtn.addEventListener('click', addAchievement);
            }
        }

        function addAchievement() {
            achievementCount++;
            const container = document.getElementById('achievementsContainer');
            
            const achievementDiv = document.createElement('div');
            achievementDiv.className = 'achievement-item mb-3 p-3 border rounded';
            achievementDiv.id = 'achievement_' + achievementCount;
            achievementDiv.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="achievements[]" 
                               placeholder="E.g., Foundation completed, Materials delivered, 50% construction done..." 
                               required>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeAchievement(${achievementCount})">
                            <i class="fas fa-trash me-1"></i>Remove
                        </button>
                    </div>
                </div>
                <small class="text-success mt-2 d-block">
                    <i class="fas fa-plus-circle me-1"></i>This achievement will add +3% to progress
                </small>
            `;
            
            container.appendChild(achievementDiv);
        }

        function removeAchievement(id) {
            const element = document.getElementById('achievement_' + id);
            if (element) {
                element.remove();
            }
        }

        // Form submission validation
        document.getElementById('progressForm').addEventListener('submit', function(e) {
            const hasPhotos = selectedFiles.size > 0;
            
            if (!hasPhotos) {
                e.preventDefault();
                alert('Please upload at least one progress photo before submitting.');
                return false;
            }
        });
    </script>
</body>
</html>
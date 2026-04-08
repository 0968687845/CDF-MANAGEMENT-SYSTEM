<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $profileData = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'department' => $_POST['department'],
            'position' => $_POST['position']
        ];
        
        if (updateUserProfile($_SESSION['user_id'], $profileData)) {
            $_SESSION['success_message'] = "Profile updated successfully";
            // Refresh user data
            $userData = getUserData();
        } else {
            $_SESSION['error_message'] = "Failed to update profile";
        }
        redirect('profile.php');
        
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error_message'] = "New passwords do not match";
        } elseif (!verifyCurrentPassword($_SESSION['user_id'], $currentPassword)) {
            $_SESSION['error_message'] = "Current password is incorrect";
        } else {
            if (changeUserPassword($_SESSION['user_id'], $currentPassword, $newPassword)) {
                $_SESSION['success_message'] = "Password changed successfully";
            } else {
                $_SESSION['error_message'] = "Failed to change password";
            }
        }
        redirect('profile.php');
        
    } elseif (isset($_POST['update_preferences'])) {
        // Update user preferences
        $preferences = [
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'desktop_notifications' => isset($_POST['desktop_notifications']) ? 1 : 0,
            'theme' => $_POST['theme'],
            'language' => $_POST['language'],
            'timezone' => $_POST['timezone']
        ];
        
        if (updateUserPreferences($_SESSION['user_id'], $preferences)) {
            $_SESSION['success_message'] = "Preferences updated successfully";
        } else {
            $_SESSION['error_message'] = "Failed to update preferences";
        }
        redirect('profile.php');
    }
}

// Get user activity logs
$userActivity = getUserActivity($_SESSION['user_id'], 10);

// Get user preferences
$userPreferences = getUserPreferences($_SESSION['user_id']);

$pageTitle = "My Profile - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="User profile management for CDF Management System - Government of Zambia">
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
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 3rem;
            font-weight: 800;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 30px rgba(233, 185, 73, 0.4);
        }

        .profile-avatar-upload {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.875rem;
            opacity: 0;
            transition: var(--transition);
        }

        .profile-avatar:hover .profile-avatar-upload {
            opacity: 1;
        }

        .profile-info h1 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            font-weight: 900;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            letter-spacing: -0.3px;
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

        /* Stats Cards */
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
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2.75rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
            line-height: 1;
        }

        .stat-title {
            font-size: 1.1rem;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .stat-subtitle {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
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
            background: linear-gradient(135deg, #e8eef8 0%, #dae5f0 100%);
            border-bottom: 4px solid var(--primary);
            padding: 1.75rem;
            position: relative;
        }

        .card-header h5 {
            color: var(--primary-dark);
            font-weight: 900;
            margin-bottom: 0;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: 0.2px;
        }

        /* Profile Specific Styles */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 249, 250, 0.98) 100%);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            transition: var(--transition);
        }

        .form-section:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .form-section:hover::before {
            left: 100%;
        }

        .form-section h6 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }

        .form-section h6 i {
            font-size: 1.3rem;
        }

        .setting-item {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--gray-light);
            transition: var(--transition);
        }

        .setting-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .setting-item:hover {
            padding-left: 0.75rem;
        }

        .setting-label {
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 0.6rem;
            font-size: 1.02rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: 0.2px;
        }

        .setting-description {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .setting-value {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 0.95rem;
        }

        /* Activity Items */
        .activity-item {
            padding: 1.5rem;
            border-left: 5px solid transparent;
            transition: var(--transition);
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            background: rgba(26, 78, 138, 0.02);
            box-shadow: var(--shadow-sm);
        }

        .activity-item:hover {
            background: rgba(26, 78, 138, 0.05);
            border-left-color: var(--primary);
            transform: translateX(8px);
            box-shadow: var(--shadow);
        }

        .activity-item h6 {
            color: #1a1a1a;
            font-weight: 800;
            margin-bottom: 0.6rem;
            font-size: 0.95rem;
        }

        .activity-item p {
            color: #444;
            margin-bottom: 0.6rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            flex-shrink: 0;
            font-size: 1.25rem;
            transition: var(--transition);
        }

        .activity-item:hover .activity-icon {
            transform: scale(1.1);
        }

        .activity-icon.primary { background: rgba(26, 78, 138, 0.1); color: var(--primary); }
        .activity-icon.success { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .activity-icon.warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .activity-icon.info { background: rgba(23, 162, 184, 0.1); color: var(--info); }

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
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.25);
        }
        .badge-in-progress { 
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #1a1a1a;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.25);
        }
        .badge-delayed { 
            background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.25);
        }
        .badge-planning { 
            background: linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%);
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 8px rgba(26, 78, 138, 0.25);
        }
        .badge-assigned { 
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.25);
        }
        .badge-warning { 
            background: linear-gradient(135deg, #fd7e14 0%, #e56c00 100%);
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            box-shadow: 0 2px 8px rgba(253, 126, 20, 0.25);
        }

        .badge {
            font-weight: 700;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 0.85rem;
            box-shadow: var(--shadow-sm);
            display: inline-block;
            letter-spacing: 0.3px;
            transition: var(--transition);
            border: none;
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

        /* Form Styling */
        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.875rem 1.125rem;
            border: 2.5px solid rgba(26, 78, 138, 0.2);
            transition: var(--transition);
            font-weight: 600;
            background: var(--white);
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-control:hover, .form-select:hover {
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 2px 8px rgba(26, 78, 138, 0.1);
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 5px rgba(26, 78, 138, 0.15);
            border-color: var(--primary-dark);
            background: var(--white);
            font-weight: 600;
            color: var(--dark);
        }

        .form-label {
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        /* Alert Styling */
        .alert {
            border-radius: var(--border-radius-lg);
            border: none;
            box-shadow: var(--shadow);
            animation: slideDown 0.4s ease-out;
            border-left: 6px solid;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .alert-success {
            border-left-color: #1e7e34;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(40, 167, 69, 0.08) 100%);
            color: #1a5a2a;
        }

        .alert-danger {
            border-left-color: #bd2130;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15) 0%, rgba(220, 53, 69, 0.08) 100%);
            color: #7a1820;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Password Strength */
        .password-strength {
            height: 10px;
            border-radius: 10px;
            margin-top: 0.75rem;
            background: #d0d0d0;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
            border: 1px solid #bbb;
        }

        .password-strength-bar {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .strength-weak { 
            background: linear-gradient(90deg, #d32f2f 0%, #b71c1c 100%);
            width: 25%;
        }
        .strength-fair { 
            background: linear-gradient(90deg, #f57c00 0%, #e65100 100%);
            width: 50%;
        }
        .strength-good { 
            background: linear-gradient(90deg, #1976d2 0%, #1565c0 100%);
            width: 75%;
        }
        .strength-strong { 
            background: linear-gradient(90deg, #388e3c 0%, #2e7d32 100%);
            width: 100%;
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
            
            .profile-stats {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 2.25rem;
            }
        }

        @media (max-width: 576px) {
            .btn-primary-custom,
            .btn-outline-custom {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .table-responsive {
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-sm);
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
        .stat-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Hover Effects */
        .hover-lift {
            transition: var(--transition);
        }

        .hover-lift:hover {
            transform: translateY(-5px);
        }

        /* Text Utilities */
        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Profile Completion */
        .profile-completion {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .profile-completion::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .completion-text {
            font-weight: 800;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            position: relative;
            z-index: 2;
        }

        .completion-percentage {
            font-weight: 900;
            font-size: 1.25rem;
            position: relative;
            z-index: 2;
        }

        .progress {
            height: 12px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--secondary) 0%, #f0ad4e 100%);
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(233, 185, 73, 0.4);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
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
                            <li><a class="dropdown-item" href="projects.php">
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
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
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
                            <li><a class="dropdown-item active" href="profile.php">
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

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                    <div class="profile-avatar-upload">
                        <i class="fas fa-camera me-1"></i>Change Photo
                    </div>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h1>
                    <p class="lead">System Administrator</p>
                    <p class="mb-0">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($userData['email']); ?> |
                        <i class="fas fa-calendar me-2"></i>Member since <?php echo date('M Y', strtotime($userData['created_at'])); ?> |
                        <i class="fas fa-shield-alt me-2"></i>Last login: <?php echo date('M j, Y g:i A', strtotime($userData['updated_at'] ?? date('Y-m-d H:i:s'))); ?>
                    </p>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit me-2"></i>Edit Profile
                </button>
                <button class="btn btn-outline-custom" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key me-2"></i>Change Password
                </button>
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

        <!-- Profile Completion -->
        <div class="profile-completion">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="completion-text">Profile Completion</div>
                <div class="completion-percentage">85%</div>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: 85%"></div>
            </div>
            <small class="mt-2 d-block">Complete your profile by adding missing information</small>
        </div>

        <!-- Profile Statistics -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count(getAllProjects()); ?></div>
                <div class="stat-title">Total Projects</div>
                <div class="stat-subtitle">All projects in system</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(getUsersByRole('officer')); ?></div>
                <div class="stat-title">M&E Officers</div>
                <div class="stat-subtitle">Active officers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(getUsersByRole('beneficiary')); ?></div>
                <div class="stat-title">Beneficiaries</div>
                <div class="stat-subtitle">Registered beneficiaries</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($notifications); ?></div>
                <div class="stat-title">Notifications</div>
                <div class="stat-subtitle">Unread notifications</div>
            </div>
        </div>

        <div class="row">
            <!-- Personal Information -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="setting-item">
                                    <div class="setting-label">First Name</div>
                                    <div class="setting-value"><?php echo htmlspecialchars($userData['first_name']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="setting-item">
                                    <div class="setting-label">Last Name</div>
                                    <div class="setting-value"><?php echo htmlspecialchars($userData['last_name']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="setting-item">
                                    <div class="setting-label">Email Address</div>
                                    <div class="setting-value"><?php echo htmlspecialchars($userData['email']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="setting-item">
                                    <div class="setting-label">Phone Number</div>
                                    <div class="setting-value"><?php echo htmlspecialchars($userData['phone'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="setting-item">
                                    <div class="setting-label">Department</div>
                                    <div class="setting-value"><?php echo htmlspecialchars($userData['department'] ?? 'Administration'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="setting-item">
                                    <div class="setting-label">Position</div>
                                    <div class="setting-value"><?php echo htmlspecialchars($userData['position'] ?? 'System Administrator'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-label">User ID</div>
                            <div class="setting-value">
                                <code><?php echo htmlspecialchars($userData['id']); ?></code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($userActivity) > 0): ?>
                            <?php foreach ($userActivity as $activity): ?>
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
            </div>

            <!-- Preferences & Settings -->
            <div class="col-lg-4">
                <!-- Account Settings -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-cog me-2"></i>Account Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile.php">
                            <div class="setting-item">
                                <div class="setting-label">Email Notifications</div>
                                <div class="setting-description">Receive email notifications for important updates</div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" <?php echo ($userPreferences['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">Enabled</label>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-label">Desktop Notifications</div>
                                <div class="setting-description">Show desktop notifications for new alerts</div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="desktop_notifications" id="desktop_notifications" <?php echo ($userPreferences['desktop_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="desktop_notifications">Enabled</label>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-label">Theme</div>
                                <div class="setting-description">Choose your preferred interface theme</div>
                                <select class="form-select" name="theme" id="themeSelect" onchange="applyTheme(this.value)">
                                    <option value="light" <?php echo ($userPreferences['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light Theme</option>
                                    <option value="dark" <?php echo ($userPreferences['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark Theme</option>
                                    <option value="auto" <?php echo ($userPreferences['theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                </select>
                            </div>

                            <div class="setting-item">
                                <div class="setting-label">Language</div>
                                <div class="setting-description">Select your preferred language</div>
                                <select class="form-select" name="language">
                                    <option value="en" <?php echo ($userPreferences['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="fr" <?php echo ($userPreferences['language'] ?? 'en') === 'fr' ? 'selected' : ''; ?>>French</option>
                                    <option value="es" <?php echo ($userPreferences['language'] ?? 'en') === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                </select>
                            </div>

                            <div class="setting-item">
                                <div class="setting-label">Timezone</div>
                                <div class="setting-description">Set your local timezone</div>
                                <select class="form-select" name="timezone">
                                    <option value="Africa/Lusaka" <?php echo ($userPreferences['timezone'] ?? 'Africa/Lusaka') === 'Africa/Lusaka' ? 'selected' : ''; ?>>Africa/Lusaka (CAT)</option>
                                    <option value="UTC" <?php echo ($userPreferences['timezone'] ?? 'Africa/Lusaka') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                </select>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="update_preferences" class="btn btn-primary-custom btn-sm">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Information -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt me-2"></i>Security</h5>
                    </div>
                    <div class="card-body">
                        <div class="setting-item">
                            <div class="setting-label">Last Password Change</div>
                            <div class="setting-value"><?php echo date('M j, Y', strtotime($userData['updated_at'])); ?></div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-label">Two-Factor Authentication</div>
                            <div class="setting-value">
                                <span class="badge badge-warning">Not Enabled</span>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="setting-label">Login Sessions</div>
                            <div class="setting-value">
                                <span class="badge badge-success">1 Active</span>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-shield-alt me-2"></i>Enable 2FA
                            </button>
                            <button class="btn btn-outline-info btn-sm">
                                <i class="fas fa-list me-2"></i>View Sessions
                            </button>
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

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="profile.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($userData['department'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($userData['position'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary-custom">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="profile.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required onkeyup="checkPasswordStrength(this.value)">
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="form-text" id="passwordStrengthText">Password strength</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="form-text" id="passwordMatchText"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="change_password" class="btn btn-primary-custom">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme management
        function applyTheme(theme) {
            localStorage.setItem('theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
            
            if (theme === 'light') {
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
            } else if (theme === 'dark') {
                document.body.classList.add('dark-theme');
                document.body.classList.remove('light-theme');
            } else if (theme === 'auto') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                applyTheme(prefersDark ? 'dark' : 'light');
                return;
            }
        }

        // Apply saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || '<?php echo $userPreferences['theme'] ?? 'light'; ?>';
            applyTheme(savedTheme);
            
            // Update select value
            const themeSelect = document.getElementById('themeSelect');
            if (themeSelect) {
                themeSelect.value = savedTheme;
            }
        });

        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            let text = '';
            let barClass = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    text = 'Weak';
                    barClass = 'strength-weak';
                    break;
                case 2:
                    text = 'Fair';
                    barClass = 'strength-fair';
                    break;
                case 3:
                    text = 'Good';
                    barClass = 'strength-good';
                    break;
                case 4:
                    text = 'Strong';
                    barClass = 'strength-strong';
                    break;
            }
            
            strengthBar.className = 'password-strength-bar ' + barClass;
            strengthText.textContent = text;
            strengthText.className = 'form-text ' + (strength >= 3 ? 'text-success' : strength >= 2 ? 'text-warning' : 'text-danger');
        }

        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatchText');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
                matchText.className = 'form-text';
            } else if (newPassword === confirmPassword) {
                matchText.textContent = 'Passwords match';
                matchText.className = 'form-text text-success';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'form-text text-danger';
            }
        });

        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    const firstInvalid = this.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                }
            });
        });

        // Auto-close alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>
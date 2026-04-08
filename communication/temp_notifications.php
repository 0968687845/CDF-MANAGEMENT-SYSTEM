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

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_as_read'])) {
        $notification_id = $_POST['notification_id'] ?? '';
        if (!empty($notification_id)) {
            if (markNotificationAsRead($notification_id)) {
                $_SESSION['success'] = "Notification marked as read.";
                // Refresh notifications
                $notifications = getNotifications($_SESSION['user_id']);
            } else {
                $_SESSION['error'] = "Failed to mark notification as read.";
            }
        }
    }
    
    if (isset($_POST['mark_all_read'])) {
        if (markAllNotificationsAsRead($_SESSION['user_id'])) {
            $_SESSION['success'] = "All notifications marked as read.";
            // Refresh notifications
            $notifications = getNotifications($_SESSION['user_id']);
        } else {
            $_SESSION['error'] = "Failed to mark all notifications as read.";
        }
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = $_POST['notification_id'] ?? '';
        if (!empty($notification_id)) {
            if (deleteNotification($notification_id)) {
                $_SESSION['success'] = "Notification deleted successfully.";
                // Refresh notifications
                $notifications = getNotifications($_SESSION['user_id']);
            } else {
                $_SESSION['error'] = "Failed to delete notification.";
            }
        }
    }
    
    if (isset($_POST['clear_all'])) {
        if (clearAllNotifications($_SESSION['user_id'])) {
            $_SESSION['success'] = "All notifications cleared.";
            // Refresh notifications
            $notifications = getNotifications($_SESSION['user_id']);
        } else {
            $_SESSION['error'] = "Failed to clear all notifications.";
        }
    }
}

// Get notification statistics
$unread_count = count(array_filter($notifications, function($notification) {
    return !$notification['is_read'];
}));
$total_count = count($notifications);

$pageTitle = "Notifications - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Notifications management for CDF Management System - Government of Zambia">
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
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
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

    .navbar-brand img {
        height: 40px;
        width: auto;
        object-fit: contain;
        filter: brightness(1.05) contrast(1.1) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
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

    /* Enhanced Notification Item */
    .notification-item {
        padding: var(--space-6);
        border-left: 4px solid transparent;
        transition: var(--transition);
        border-radius: var(--border-radius);
        margin-bottom: var(--space-4);
        background: var(--white);
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: hidden;
    }

    .notification-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: var(--transition-slow);
    }

    .notification-item:hover::before {
        left: 100%;
    }

    .notification-item:hover {
        background: rgba(13, 110, 253, 0.03);
        border-left-color: var(--primary);
        transform: translateX(var(--space-2));
        box-shadow: var(--shadow);
    }

    .notification-item.unread {
        background: rgba(26, 78, 138, 0.08);
        border-left-color: var(--primary);
    }

    .notification-item.read {
        background: var(--white);
        border-left-color: var(--gray-300);
        opacity: 0.9;
    }

    .notification-icon {
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
        box-shadow: var(--shadow-sm);
    }

    .notification-item:hover .notification-icon {
        transform: scale(1.1);
    }

    .notification-icon.info { 
        background: rgba(26, 78, 138, 0.1); 
        color: var(--primary); 
    }
    .notification-icon.success { 
        background: rgba(40, 167, 69, 0.1); 
        color: var(--success); 
    }
    .notification-icon.warning { 
        background: rgba(255, 193, 7, 0.1); 
        color: var(--warning); 
    }
    .notification-icon.danger { 
        background: rgba(220, 53, 69, 0.1); 
        color: var(--danger); 
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

    /* Enhanced Action Buttons */
    .action-buttons {
        display: flex;
        gap: var(--space-4);
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
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

    /* Enhanced Badges */
    .badge-unread {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: var(--white);
        font-weight: 700;
        padding: var(--space-2) var(--space-3);
        border-radius: 20px;
        font-size: var(--text-xs);
        box-shadow: var(--shadow-sm);
    }

    .badge-read {
        background: linear-gradient(135deg, var(--gray-300) 0%, var(--gray-400) 100%);
        color: var(--gray-900);
        font-weight: 700;
        padding: var(--space-2) var(--space-3);
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

    /* Enhanced Form Styles */
    .form-control, .form-select {
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

    .form-label {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: var(--space-3);
        font-size: var(--text-base);
    }

    /* Alert Enhancements */
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
        border-left-color: var(--success);
        background: var(--success-light);
        color: var(--success-dark);
    }

    .alert-danger {
        border-left-color: var(--danger);
        background: var(--danger-light);
        color: var(--danger-dark);
    }

    .alert-info {
        border-left-color: var(--info);
        background: var(--info-light);
        color: var(--info-dark);
    }

    .alert-warning {
        border-left-color: var(--warning);
        background: var(--warning-light);
        color: var(--warning-dark);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: var(--space-16);
        color: var(--gray-600);
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--gray-300);
        margin-bottom: var(--space-6);
        opacity: 0.8;
    }

    .empty-state h4 {
        color: var(--gray-800);
        margin-bottom: var(--space-4);
    }

    .empty-state p {
        color: var(--gray-600);
        margin-bottom: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header {
            padding: var(--space-12) 0 var(--space-8);
            text-align: center;
        }
        
        .card-body {
            padding: var(--space-6);
        }
        
        .notification-item {
            padding: var(--space-4);
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .btn-primary-custom,
        .btn-outline-custom {
            width: 100%;
            text-align: center;
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 0 var(--space-4);
        }
        
        .card-body {
            padding: var(--space-4);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            margin-right: var(--space-4);
        }
        
        .notification-item {
            padding: var(--space-3);
            margin-bottom: var(--space-3);
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
    .notification-item {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Custom Scrollbar for Notification List */
    .notification-list::-webkit-scrollbar {
        width: 8px;
    }

    .notification-list::-webkit-scrollbar-track {
        background: var(--gray-200);
        border-radius: 4px;
    }

    .notification-list::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
        transition: var(--transition);
    }

    .notification-list::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }

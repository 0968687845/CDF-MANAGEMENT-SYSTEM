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

// Support functionality removed

$pageTitle = "Help & Support - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Help and support center for CDF Management System - Government of Zambia">
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

        /* Enhanced Help Cards */
        .help-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: var(--space-6);
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .help-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition-slow);
        }

        .help-card:hover::before {
            left: 100%;
        }

        .help-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .help-icon {
            font-size: 3rem;
            margin-bottom: var(--space-4);
            color: var(--primary);
            transition: var(--transition);
        }

        .help-card:hover .help-icon {
            transform: scale(1.1);
            color: var(--primary-dark);
        }

        /* Enhanced Live Chat */
        .chat-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: var(--space-5) var(--space-6);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chat-header h6 {
            color: var(--white);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .chat-messages {
            flex: 1;
            padding: var(--space-5);
            overflow-y: auto;
            background: var(--gray-100);
            display: flex;
            flex-direction: column;
            gap: var(--space-4);
        }

        .message {
            max-width: 80%;
            padding: var(--space-4) var(--space-5);
            border-radius: var(--border-radius);
            position: relative;
            animation: fadeInUp 0.3s ease-out;
        }

        .message.user {
            background: var(--primary-gradient);
            color: var(--white);
            align-self: flex-end;
            border-bottom-right-radius: var(--space-2);
        }

        .message.support {
            background: var(--white);
            color: var(--gray-800);
            align-self: flex-start;
            border: 1px solid var(--gray-300);
            border-bottom-left-radius: var(--space-2);
            box-shadow: var(--shadow-sm);
        }

        .message-time {
            font-size: var(--text-xs);
            opacity: 0.7;
            margin-top: var(--space-2);
            display: block;
        }

        .chat-input-container {
            padding: var(--space-5);
            border-top: 1px solid var(--gray-300);
            background: var(--white);
        }

        .chat-input-group {
            display: flex;
            gap: var(--space-3);
        }

        .chat-input {
            flex: 1;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            padding: var(--space-4) var(--space-5);
            font-size: var(--text-base);
            transition: var(--transition);
            resize: none;
        }

        .chat-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.15);
            outline: none;
        }

        .send-button {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            border-radius: var(--border-radius);
            padding: var(--space-4) var(--space-5);
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }

        .send-button:hover {
            background: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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

        /* Support Ticket Styles */
        .ticket-item {
            padding: var(--space-5);
            border-left: 4px solid var(--primary);
            background: var(--light-gradient);
            border-radius: var(--border-radius);
            margin-bottom: var(--space-4);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .ticket-item:hover {
            background: rgba(13, 110, 253, 0.05);
            transform: translateX(var(--space-2));
            box-shadow: var(--shadow);
        }

        .ticket-status {
            display: inline-block;
            padding: var(--space-2) var(--space-3);
            border-radius: 20px;
            font-size: var(--text-xs);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-open { background: var(--success-light); color: var(--success-dark); }
        .status-pending { background: var(--warning-light); color: var(--warning-dark); }
        .status-resolved { background: var(--info-light); color: var(--info-dark); }
        .status-closed { background: var(--gray-300); color: var(--gray-700); }

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
            border-left-color: var(--success);
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

        /* FAQ Styles */
        .faq-item {
            margin-bottom: var(--space-4);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .faq-item:hover {
            box-shadow: var(--shadow);
        }

        .faq-question {
            background: var(--light-gradient);
            padding: var(--space-5) var(--space-6);
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question:hover {
            background: rgba(26, 78, 138, 0.05);
        }

        .faq-answer {
            padding: var(--space-5) var(--space-6);
            background: var(--white);
            border-top: 1px solid var(--gray-300);
            display: none;
        }

        .faq-answer.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                padding: var(--space-12) 0 var(--space-8);
                text-align: center;
            }
            
            .chat-container {
                height: 400px;
            }
            
            .message {
                max-width: 90%;
            }
            
            .card-body {
                padding: var(--space-6);
            }
            
            .help-card {
                margin-bottom: var(--space-4);
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
            
            .chat-input-group {
                flex-direction: column;
            }
            
            .send-button {
                width: 100%;
                justify-content: center;
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
            
            .help-card::before,
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
        .help-card,
        .ticket-item {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Online Status Indicator */
        .online-status {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            font-size: var(--text-sm);
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 2s infinite;
        }

        /* Chat Typing Indicator */
        .typing-indicator {
            display: none;
            padding: var(--space-3) var(--space-4);
            background: var(--white);
            border-radius: var(--border-radius);
            align-self: flex-start;
            font-style: italic;
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
        }

        .typing-indicator.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
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
                            <li><a class="dropdown-item active" href="help.php">
                                <i class="fas fa-question-circle me-2"></i>Help & Support
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
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: var(--secondary-gradient); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
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
                    <h1><i class="fas fa-question-circle me-2"></i>Help & Support</h1>
                    <p class="lead mb-0">Get assistance with your CDF projects and system usage</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="online-status">
                        <span class="status-dot"></span>
                        <span>Support Online</span>
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
            <!-- Quick Help Section -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-life-ring me-2"></i>Quick Help</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6 col-lg-12">
                                <div class="help-card">
                                    <i class="fas fa-file-alt help-icon"></i>
                                    <h6>Documentation</h6>
                                    <p class="small text-muted">User guides and manuals</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-12">
                                <div class="help-card">
                                    <i class="fas fa-video help-icon"></i>
                                    <h6>Video Tutorials</h6>
                                    <p class="small text-muted">Step-by-step guides</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-12">
                                <div class="help-card">
                                    <i class="fas fa-download help-icon"></i>
                                    <h6>Resources</h6>
                                    <p class="small text-muted">Forms and templates</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-12">
                                <div class="help-card">
                                    <i class="fas fa-phone help-icon"></i>
                                    <h6>Contact</h6>
                                    <p class="small text-muted">+260 211 123 456</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Chat Section -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-comments me-2"></i>Live Chat Support</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="chat-container">
                            <div class="chat-header">
                                <h6>
                                    <i class="fas fa-headset me-2"></i>
                                    CDF Support Team
                                    <span class="online-status ms-3">
                                        <span class="status-dot"></span>
                                        <span>Online</span>
                                    </span>
                                </h6>
                            </div>
                            
                            <div class="chat-messages" id="chatMessages">
                                <!-- Welcome Message -->
                                <div class="message support">
                                    <div class="message-content">
                                        <p>Hello! Welcome to CDF Support. How can we help you today?</p>
                                        <small class="message-time"><?php echo date('g:i A'); ?></small>
                                    </div>
                                </div>
                                
                                <!-- Sample FAQ Suggestions -->
                                <div class="message support">
                                    <div class="message-content">
                                        <p>Quick help topics:</p>
                                        <ul class="small mb-2">
                                            <li>How to update project progress</li>
                                            <li>Submitting expense reports</li>
                                            <li>Project approval process</li>
                                            <li>Technical issues</li>
                                        </ul>
                                        <small class="message-time"><?php echo date('g:i A'); ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="typing-indicator" id="typingIndicator">
                                <i class="fas fa-ellipsis-h me-2"></i>
                                Support agent is typing...
                            </div>
                            
                            <form method="POST" class="chat-input-container">
                                <div class="chat-input-group">
                                    <select class="form-select" name="category" style="max-width: 180px;">
                                        <option value="general">General Help</option>
                                        <option value="technical">Technical Issue</option>
                                        <option value="project">Project Related</option>
                                        <option value="financial">Financial Query</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <textarea 
                                        class="chat-input" 
                                        name="message" 
                                        placeholder="Type your message here..." 
                                        rows="1"
                                        required
                                    ></textarea>
                                    <button type="submit" name="send_message" class="send-button">
                                        <i class="fas fa-paper-plane"></i>
                                        Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h5>
                    </div>
                    <div class="card-body">
                        <div class="faq-item">
                            <button class="faq-question">
                                How do I update my project progress?
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <p>To update your project progress:</p>
                                <ol>
                                    <li>Go to the "Update Progress" section</li>
                                    <li>Select your project from the dropdown</li>
                                    <li>Set the progress percentage using the slider</li>
                                    <li>Add a description of work completed</li>
                                    <li>Upload progress photos if available</li>
                                    <li>Click "Update Progress" to submit</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question">
                                How do I submit expense reports?
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Expense submission process:</p>
                                <ul>
                                    <li>Navigate to "My Expenses" section</li>
                                    <li>Select the relevant project</li>
                                    <li>Click "Record Expense"</li>
                                    <li>Fill in expense details and amount</li>
                                    <li>Attach receipts if available</li>
                                    <li>Submit for officer review</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="faq-item">
                            <button class="faq-question">
                                What is the project approval process?
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <p>The project approval involves:</p>
                                <ol>
                                    <li>Project submission and initial review</li>
                                    <li>Technical feasibility assessment</li>
                                    <li>Budget approval</li>
                                    <li>Community consultation verification</li>
                                    <li>Final approval by CDF committee</li>
                                    <li>Project implementation begins</li>
                                </ol>
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
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const answer = button.nextElementSibling;
                const icon = button.querySelector('i');
                
                // Toggle current answer
                answer.classList.toggle('show');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
                
                // Close other answers
                document.querySelectorAll('.faq-answer').forEach(otherAnswer => {
                    if (otherAnswer !== answer && otherAnswer.classList.contains('show')) {
                        otherAnswer.classList.remove('show');
                        const otherIcon = otherAnswer.previousElementSibling.querySelector('i');
                        otherIcon.classList.remove('fa-chevron-up');
                        otherIcon.classList.add('fa-chevron-down');
                    }
                });
            });
        });

        // Auto-resize textarea
        const textarea = document.querySelector('.chat-input');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Simulate support responses
        function simulateSupportResponse() {
            const typingIndicator = document.getElementById('typingIndicator');
            const chatMessages = document.getElementById('chatMessages');
            
            // Show typing indicator
            typingIndicator.classList.add('show');
            
            setTimeout(() => {
                typingIndicator.classList.remove('show');
                
                // Add support response
                const responses = [
                    "Thank you for your message. Our support team will review your query and get back to you shortly.",
                    "I understand your concern. Let me check that for you.",
                    "That's a great question! Here's what you need to know...",
                    "I can help you with that. Could you provide more details?",
                    "We've received similar queries. Here's the solution that worked for others."
                ];
                
                const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message support';
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <p>${randomResponse}</p>
                        <small class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                    </div>
                `;
                
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
            }, 2000);
        }

        // Handle chat form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = this.querySelector('textarea');
            const message = messageInput.value.trim();
            const category = this.querySelector('select').value;
            
            if (message) {
                const chatMessages = document.getElementById('chatMessages');
                
                // Add user message
                const userMessageDiv = document.createElement('div');
                userMessageDiv.className = 'message user';
                userMessageDiv.innerHTML = `
                    <div class="message-content">
                        <p><strong>Category:</strong> ${category.charAt(0).toUpperCase() + category.slice(1)}</p>
                        <p>${message}</p>
                        <small class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                    </div>
                `;
                
                chatMessages.appendChild(userMessageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Clear input
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                // Simulate support response
                simulateSupportResponse();
                
                // Submit form via AJAX (you can implement this)
                // const formData = new FormData(this);
                // fetch('help.php', {
                //     method: 'POST',
                //     body: formData
                // }).then(response => response.json())
                //   .then(data => {
                //       // Handle response
                //   });
            }
        });

        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

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
    </script>
</body>
</html>
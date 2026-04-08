<?php
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Allow both beneficiaries and officers to access messages
$user_role = getUserRole();
if (!in_array($user_role, ['beneficiary', 'officer'])) {
    redirect('../index.php');
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../index.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get all messages (both sent and received)
$all_messages = getAllUserMessages($_SESSION['user_id']);
$unread_count = getUnreadMessageCount($_SESSION['user_id']);

// Get conversations (grouped by other user)
$conversations = getConversations($_SESSION['user_id']);

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $recipient_id = $_POST['recipient_id'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
        
        if (!empty($recipient_id) && !empty($subject) && !empty($message)) {
            if (sendMessage($_SESSION['user_id'], $recipient_id, $subject, $message, $is_urgent)) {
                $_SESSION['success'] = "Message sent successfully!";
                createNotification($recipient_id, 'New Message', 'You have received a new message from ' . $userData['first_name'] . ' ' . $userData['last_name']);
                // Refresh messages after sending
                $all_messages = getAllUserMessages($_SESSION['user_id']);
                $conversations = getConversations($_SESSION['user_id']);
            } else {
                $_SESSION['error'] = "Failed to send message. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Please fill in all required fields.";
        }
    }
    
    // Mark message as read
    if (isset($_POST['mark_as_read'])) {
        $message_id = $_POST['message_id'] ?? '';
        if (!empty($message_id)) {
            markMessageAsRead($message_id);
            $_SESSION['success'] = "Message marked as read.";
            // Refresh messages
            $all_messages = getAllUserMessages($_SESSION['user_id']);
            $conversations = getConversations($_SESSION['user_id']);
            $unread_count = getUnreadMessageCount($_SESSION['user_id']);
        }
    }
}

// Get available recipients based on user role
$user_role = $_SESSION['user_role'] ?? 'beneficiary';
$recipients = [];
$project_beneficiaries = []; // Map project ID to beneficiary info

if ($user_role === 'beneficiary') {
    // Beneficiaries can only message officers (not admins)
    $recipients = getUsersByRole('officer');
} elseif ($user_role === 'officer') {
    // Officers can message their assigned projects (via beneficiaries)
    $officer_projects = getOfficerProjects($_SESSION['user_id']);
    
    // Transform projects into recipient format with beneficiary data
    $beneficiary_map = []; // Track which beneficiaries we've added
    
    foreach ($officer_projects as $project) {
        // Use beneficiary as recipient (grouped by beneficiary)
        if (!empty($project['beneficiary_id']) && !isset($beneficiary_map[$project['beneficiary_id']])) {
            // Create recipient entry for each beneficiary with their projects
            $recipient = [
                'id' => $project['beneficiary_id'],
                'first_name' => explode(' ', $project['beneficiary_name'])[0] ?? 'Unknown',
                'last_name' => implode(' ', array_slice(explode(' ', $project['beneficiary_name']), 1)) ?? '',
                'email' => $project['beneficiary_id'], // Store beneficiary_id for reference
                'role' => 'beneficiary'
            ];
            
            $recipients[] = $recipient;
            $beneficiary_map[$project['beneficiary_id']] = true;
            $project_beneficiaries[$project['beneficiary_id']] = [];
        }
        
        // Add project to beneficiary's project list
        if (!empty($project['beneficiary_id'])) {
            $project_beneficiaries[$project['beneficiary_id']][] = $project;
        }
    }
}

// Get specific conversation if selected
$selected_conversation = null;
if (isset($_GET['conversation_with'])) {
    $selected_conversation = $_GET['conversation_with'];
    $conversation_messages = getMessagesBetweenUsers($_SESSION['user_id'], $selected_conversation);
}

$pageTitle = "Messages - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Communication messages for CDF Management System - Government of Zambia">
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

    /* Enhanced Conversation List */
    .conversation-item {
        padding: var(--space-6);
        border-bottom: 1px solid var(--gray-300);
        transition: var(--transition);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .conversation-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(26, 78, 138, 0.05), transparent);
        transition: var(--transition-slow);
    }

    .conversation-item:hover::before {
        left: 100%;
    }

    .conversation-item:hover {
        background: rgba(26, 78, 138, 0.03);
        transform: translateX(var(--space-2));
    }

    .conversation-item.active {
        background: rgba(26, 78, 138, 0.08);
        border-left: 4px solid var(--primary);
    }

    .conversation-item.unread {
        background: rgba(26, 78, 138, 0.05);
        font-weight: 600;
    }

    /* Enhanced Message Items */
    .message-item {
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

    .message-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: var(--transition-slow);
    }

    .message-item:hover::before {
        left: 100%;
    }

    .message-item:hover {
        background: rgba(13, 110, 253, 0.03);
        border-left-color: var(--primary);
        transform: translateX(var(--space-2));
        box-shadow: var(--shadow);
    }

    .message-item.unread {
        background: rgba(26, 78, 138, 0.08);
        border-left-color: var(--primary);
    }

    .message-item.sent {
        background: rgba(40, 167, 69, 0.05);
        border-left-color: var(--success);
    }

    .message-icon {
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

    .message-item:hover .message-icon {
        transform: scale(1.1);
    }

    .message-icon.incoming { 
        background: rgba(26, 78, 138, 0.1); 
        color: var(--primary); 
    }
    .message-icon.outgoing { 
        background: rgba(40, 167, 69, 0.1); 
        color: var(--success); 
    }
    .message-icon.urgent { 
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

    /* Enhanced Message Thread */
    .message-thread {
        max-height: 500px;
        overflow-y: auto;
        padding: var(--space-6);
        background: var(--gray-100);
        border-radius: var(--border-radius);
        box-shadow: inset var(--shadow-sm);
    }

    .message-bubble {
        padding: var(--space-4) var(--space-5);
        border-radius: var(--border-radius-lg);
        margin-bottom: var(--space-4);
        max-width: 70%;
        position: relative;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .message-bubble:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .message-bubble.sent {
        background: var(--primary-gradient);
        color: var(--white);
        margin-left: auto;
        border-bottom-right-radius: var(--space-2);
        text-shadow: none;
    }

    .message-bubble.sent p,
    .message-bubble.sent .message-content {
        color: var(--white) !important;
    }

    .message-bubble.received {
        background: var(--gray-100);
        color: var(--gray-900);
        border: 2px solid var(--primary-light);
        border-bottom-left-radius: var(--space-2);
        box-shadow: var(--shadow);
    }

    .message-bubble.received p {
        color: var(--gray-900) !important;
        margin-bottom: 0.5rem;
    }

    .message-bubble.received .message-content {
        color: var(--gray-900) !important;
    }

    .message-time {
        font-size: var(--text-xs);
        opacity: 0.8;
        margin-top: var(--space-2);
        font-weight: 500;
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

    /* Enhanced Badges */
    .badge-urgent {
        background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
        color: var(--white);
        font-weight: 700;
        padding: var(--space-2) var(--space-3);
        border-radius: 20px;
        font-size: var(--text-xs);
        box-shadow: var(--shadow-sm);
    }

    .badge-unread {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: var(--white);
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

    /* Modal Enhancements */
    .modal-content {
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        border: none;
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
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    .modal-body {
        padding: var(--space-8);
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

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header {
            padding: var(--space-12) 0 var(--space-8);
            text-align: center;
        }
        
        .message-bubble {
            max-width: 85%;
        }
        
        .card-body {
            padding: var(--space-6);
        }
        
        .conversation-item,
        .message-item {
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
        
        .modal-body {
            padding: var(--space-6);
        }
        
        .message-icon {
            width: 40px;
            height: 40px;
            margin-right: var(--space-4);
        }
        
        .message-bubble {
            max-width: 90%;
            padding: var(--space-3) var(--space-4);
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
    .conversation-item,
    .message-item {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Custom Scrollbar for Message Thread */
    .message-thread::-webkit-scrollbar {
        width: 8px;
    }

    .message-thread::-webkit-scrollbar-track {
        background: var(--gray-200);
        border-radius: 4px;
    }

    .message-thread::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
        transition: var(--transition);
    }

    .message-thread::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
</style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                <?php echo $user_role === 'officer' ? 'CDF M&E Officer Portal' : 'CDF Beneficiary Portal'; ?>
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
                            <li><a class="dropdown-item active" href="messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="../support/help.php">
                                <i class="fas fa-question-circle me-2"></i>Help
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
                                            <small class="text-muted"><?php echo $user_role === 'officer' ? 'M&E Officer' : 'CDF Beneficiary'; ?></small>
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
                    <h1><i class="fas fa-comments me-2"></i>Messages</h1>
                    <p class="lead mb-0">
                        <?php if ($user_role === 'officer'): ?>
                            Communicate with your assigned beneficiaries
                        <?php else: ?>
                            Communicate with CDF Monitoring & Evaluation Officers
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="fas fa-plus-circle me-2"></i>New Message
                        </button>
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
            <!-- Conversations List -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-users me-2"></i>Conversations</h5>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge badge-unread"><?php echo $unread_count; ?> unread</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($conversations) > 0): ?>
                            <?php foreach ($conversations as $conversation): ?>
                            <a href="?conversation_with=<?php echo $conversation['other_user_id']; ?>" 
                               class="text-decoration-none text-dark">
                                <div class="conversation-item <?php echo ($selected_conversation == $conversation['other_user_id']) ? 'active' : ''; ?> <?php echo $conversation['unread_count'] > 0 ? 'unread' : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="message-icon <?php echo $conversation['unread_count'] > 0 ? 'incoming' : 'outgoing'; ?>">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($conversation['other_user_name']); ?></h6>
                                            <p class="mb-1 small text-muted"><?php echo htmlspecialchars($conversation['last_message']); ?></p>
                                            <small class="text-muted"><?php echo time_elapsed_string($conversation['last_message_time']); ?></small>
                                        </div>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="badge badge-unread"><?php echo $conversation['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No conversations yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Message Thread -->
            <div class="col-lg-8">
                <?php if ($selected_conversation && isset($conversation_messages)): ?>
                    <?php 
                    $other_user = null;
                    foreach ($conversations as $conv) {
                        if ($conv['other_user_id'] == $selected_conversation) {
                            $other_user = $conv;
                            break;
                        }
                    }
                    ?>
                    <div class="content-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>
                                <i class="fas fa-user me-2"></i>
                                Conversation with <?php echo htmlspecialchars($other_user['other_user_name']); ?>
                            </h5>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="mark_all_read" value="<?php echo $selected_conversation; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-custom">
                                    <i class="fas fa-check-double me-1"></i>Mark All Read
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="message-thread">
                                <?php if (count($conversation_messages) > 0): ?>
                                    <?php foreach ($conversation_messages as $message): ?>
                                    <div class="message-bubble <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                                        <div class="message-content">
                                            <p class="mb-1"><?php echo htmlspecialchars($message['message']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="message-time">
                                                    <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                                </small>
                                                <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                                    <i class="fas fa-check<?php echo $message['is_read'] ? '-double text-info' : ''; ?>"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <p class="text-muted">No messages in this conversation yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quick Reply Form -->
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="recipient_id" value="<?php echo $selected_conversation; ?>">
                                <input type="hidden" name="subject" value="Re: Conversation">
                                <div class="input-group">
                                    <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                    <button type="submit" name="send_message" class="btn btn-primary-custom">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- All Messages View -->
                    <div class="content-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-inbox me-2"></i>All Messages</h5>
                            <div>
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge badge-unread"><?php echo $unread_count; ?> unread</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (count($all_messages) > 0): ?>
                                <?php foreach ($all_messages as $message): ?>
                                <div class="message-item <?php echo !$message['is_read'] && $message['recipient_id'] == $_SESSION['user_id'] ? 'unread' : ''; ?> <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : ''; ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="message-icon <?php echo $message['is_urgent'] ? 'urgent' : ($message['sender_id'] == $_SESSION['user_id'] ? 'outgoing' : 'incoming'); ?>">
                                            <i class="fas fa-<?php echo $message['is_urgent'] ? 'exclamation-triangle' : ($message['sender_id'] == $_SESSION['user_id'] ? 'paper-plane' : 'envelope'); ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="mb-1">
                                                    <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                                        To: <?php echo htmlspecialchars($message['recipient_name'] ?? 'Unknown'); ?>
                                                    <?php else: ?>
                                                        From: <?php echo htmlspecialchars($message['sender_name'] ?? 'Unknown'); ?>
                                                    <?php endif; ?>
                                                    <?php if ($message['is_urgent']): ?>
                                                        <span class="badge badge-urgent ms-2">Urgent</span>
                                                    <?php endif; ?>
                                                    <?php if (!$message['is_read'] && $message['recipient_id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge badge-unread ms-2">New</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <small class="text-muted"><?php echo time_elapsed_string($message['created_at']); ?></small>
                                            </div>
                                            <h6 class="text-primary mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars(substr($message['message'], 0, 150)); ?><?php echo strlen($message['message']) > 150 ? '...' : ''; ?></p>
                                            <?php if (!$message['is_read'] && $message['recipient_id'] == $_SESSION['user_id']): ?>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                    <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline-custom">
                                                        <i class="fas fa-check me-1"></i>Mark as Read
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Messages Yet</h5>
                                    <p class="text-muted mb-4">Start a conversation by sending your first message.</p>
                                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                        <i class="fas fa-plus-circle me-2"></i>Compose Message
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (empty($recipients)): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php if ($user_role === 'officer'): ?>
                                    You don't have any beneficiaries assigned yet. Please contact administration or wait for project assignments.
                                <?php else: ?>
                                    No officers are currently available. Please try again later or contact support.
                                <?php endif; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="recipient_id" class="form-label">
                                <?php echo $user_role === 'officer' ? 'Send Message To Beneficiary' : 'Send Message To Officer'; ?>
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="recipient_id" name="recipient_id" required>
                                <option value="">
                                    <?php echo $user_role === 'officer' ? 'Select a Beneficiary...' : 'Select an Officer...'; ?>
                                </option>
                                <?php if (empty($recipients)): ?>
                                    <option value="" disabled>
                                        <?php echo $user_role === 'officer' ? 'No beneficiaries assigned' : 'No officers available'; ?>
                                    </option>
                                <?php else: ?>
                                    <?php foreach ($recipients as $recipient): ?>
                                        <option value="<?php echo $recipient['id']; ?>">
                                            <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']); ?>
                                            <?php if ($user_role === 'beneficiary'): ?>
                                                (M&E Officer)
                                            <?php else: ?>
                                                <?php 
                                                    if (!empty($project_beneficiaries[$recipient['id']])) {
                                                        $project_titles = array_map(function($p) { return $p['title']; }, $project_beneficiaries[$recipient['id']]);
                                                        echo '- ' . htmlspecialchars(implode(', ', array_slice($project_titles, 0, 2)));
                                                    }
                                                ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="6" required placeholder="Type your message here..."></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_urgent" name="is_urgent">
                            <label class="form-check-label" for="is_urgent">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>Mark as urgent
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_message" class="btn btn-primary-custom" <?php echo empty($recipients) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </form>
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
        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Auto-focus on subject field when modal opens
        document.getElementById('newMessageModal').addEventListener('shown.bs.modal', function () {
            document.getElementById('subject').focus();
        });

        // Auto-scroll to bottom of message thread
        const messageThread = document.querySelector('.message-thread');
        if (messageThread) {
            messageThread.scrollTop = messageThread.scrollHeight;
        }
    </script>
</body>
</html>
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

// Get assigned beneficiaries for this officer (from projects)
$assigned_beneficiaries = getAssignedBeneficiaries($_SESSION['user_id']);
$admins = getUsersByRole('admin');
$recipients = array_merge($assigned_beneficiaries, $admins);

// Get real communication data
$all_messages = getAllUserMessages($_SESSION['user_id']);
$unread_count = getUnreadMessageCount($_SESSION['user_id']);
$conversations = getConversations($_SESSION['user_id']);

// Get specific conversation if selected
$selected_conversation = null;
$conversation_messages = [];
$selected_user_name = "";
if (isset($_GET['conversation_with'])) {
    $selected_conversation = $_GET['conversation_with'];
    $conversation_messages = getMessagesBetweenUsers($_SESSION['user_id'], $selected_conversation);
    
    // Get selected user's name
    foreach ($recipients as $user) {
        if ($user['id'] == $selected_conversation) {
            $selected_user_name = $user['first_name'] . ' ' . $user['last_name'];
            break;
        }
    }
}

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $recipient_id = $_POST['recipient_id'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
        $message_type = $_POST['message_type'] ?? 'general';
        
        if (!empty($recipient_id) && !empty($subject) && !empty($message)) {
            if (sendMessage($_SESSION['user_id'], $recipient_id, $subject, $message, $is_urgent, $message_type)) {
                $_SESSION['success'] = "Message sent successfully!";
                createNotification($recipient_id, 'New Message', 'You have received a new message from ' . $userData['first_name'] . ' ' . $userData['last_name']);
                
                // If in conversation view, stay there, otherwise reload to show new message
                if ($selected_conversation && $selected_conversation == $recipient_id) {
                    // Stay in the same conversation
                    $conversation_messages = getMessagesBetweenUsers($_SESSION['user_id'], $selected_conversation);
                } else {
                    // Refresh the page to show updated lists
                    redirect("login.php" . ($selected_conversation ? "?conversation_with=" . $selected_conversation : ""));
                }
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
        }
    }
    
    // Mark all as read for conversation
    if (isset($_POST['mark_all_read'])) {
        $conversation_with = $_POST['mark_all_read'] ?? '';
        if (!empty($conversation_with)) {
            markAllMessagesAsRead($_SESSION['user_id'], $conversation_with);
            $_SESSION['success'] = "All messages marked as read.";
        }
    }
    
    // Start new conversation
    if (isset($_POST['start_conversation'])) {
        $recipient_id = $_POST['new_recipient_id'] ?? '';
        if (!empty($recipient_id)) {
            redirect("login.php?conversation_with=" . $recipient_id);
        }
    }
    
    // Refresh data after POST actions
    $all_messages = getAllUserMessages($_SESSION['user_id']);
    $conversations = getConversations($_SESSION['user_id']);
    $unread_count = getUnreadMessageCount($_SESSION['user_id']);
    if ($selected_conversation) {
        $conversation_messages = getMessagesBetweenUsers($_SESSION['user_id'], $selected_conversation);
    }
}

$pageTitle = "Communication Center - M&E Officer Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Professional communication center for M&E Officer - CDF Management System - Government of Zambia">
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
            --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            line-height: 1.7;
        }

        /* Navigation */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem !important;
            border-radius: 4px;
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: var(--primary-gradient);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 76px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            font-weight: 700;
            box-shadow: var(--shadow);
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .profile-info h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-primary-custom:hover {
            background: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid #e0e0e0;
        }

        .content-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.25rem;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Stats Cards */
        .stats-container {
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        /* User List */
        .user-list-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .user-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-item:hover {
            background-color: rgba(26, 78, 138, 0.05);
        }

        .user-item.active {
            background-color: rgba(26, 78, 138, 0.1);
            border-left: 4px solid var(--primary);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-role {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .user-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--success);
            flex-shrink: 0;
        }

        /* Conversation List */
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .conversation-item:hover {
            background-color: rgba(26, 78, 138, 0.05);
        }

        .conversation-item.active {
            background-color: rgba(26, 78, 138, 0.1);
            border-left: 4px solid var(--primary);
        }

        .conversation-item.unread {
            background: rgba(26, 78, 138, 0.05);
            font-weight: 600;
        }

        /* Message Thread */
        .message-thread {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            max-width: 75%;
            position: relative;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .message-bubble:hover {
            transform: translateY(-1px);
        }

        .message-bubble.sent {
            background: var(--primary-gradient);
            color: var(--white);
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .message-bubble.received {
            background: var(--white);
            color: var(--dark);
            border: 1px solid var(--gray-light);
            border-bottom-left-radius: 5px;
        }

        .message-bubble.urgent {
            border-left: 4px solid var(--danger);
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .message-sender {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Badges */
        .badge-unread {
            background: var(--primary);
            color: white;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-urgent {
            background: var(--danger);
            color: white;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-type {
            background: var(--info);
            color: white;
            font-weight: 500;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.7rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .action-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            border-left: 4px solid var(--primary);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            color: inherit;
            text-decoration: none;
        }

        .action-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            color: var(--gray);
            font-style: italic;
            font-size: 0.9rem;
        }

        .typing-dots {
            display: inline-flex;
            margin-left: 0.5rem;
        }

        .typing-dots span {
            height: 6px;
            width: 6px;
            background: var(--gray);
            border-radius: 50%;
            display: block;
            margin: 0 1px;
            animation: typing 1s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }

        /* Message Status Icons */
        .message-status {
            margin-left: 0.5rem;
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        /* Dashboard Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--primary);
        }

        /* Professional Header */
        .professional-header {
            background: white;
            border-bottom: 2px solid var(--primary);
            padding: 1rem 0;
            margin-bottom: 1.5rem;
        }

        .conversation-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
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
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item active" href="login.php">
                                <i class="fas fa-comments me-2"></i>Communication Center
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
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
                    <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1>Professional Communication Center</h1>
                    <p class="lead">Secure messaging with assigned beneficiaries and administrators</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                    <i class="fas fa-plus-circle me-2"></i>Compose Message
                </button>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#selectUserModal">
                    <i class="fas fa-user-plus me-2"></i>Start Conversation
                </button>
                <a href="../projects/index.php" class="btn btn-primary-custom">
                    <i class="fas fa-project-diagram me-2"></i>My Projects
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Communication Statistics -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $unread_count; ?></div>
                        <div class="stat-title">Unread Messages</div>
                        <div class="stat-subtitle">Require your attention</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($conversations); ?></div>
                        <div class="stat-title">Active Conversations</div>
                        <div class="stat-subtitle">Ongoing discussions</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($assigned_beneficiaries); ?></div>
                        <div class="stat-title">Assigned Beneficiaries</div>
                        <div class="stat-subtitle">Under your supervision</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-number">24h</div>
                        <div class="stat-title">Response Time</div>
                        <div class="stat-subtitle">Service level agreement</div>
                    </div>
                </div>
            </div>
        </div>

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
            <!-- Users & Conversations Sidebar -->
            <div class="col-lg-4">
                <!-- Available Users -->
                <div class="content-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-users me-2"></i>Assigned Beneficiaries</h5>
                        <span class="badge bg-primary"><?php echo count($assigned_beneficiaries); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="search-box p-3 border-bottom">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="userSearch" placeholder="Search beneficiaries...">
                        </div>
                        <div class="user-list-container">
                            <?php if (count($assigned_beneficiaries) > 0): ?>
                                <?php foreach ($assigned_beneficiaries as $beneficiary): ?>
                                <a href="?conversation_with=<?php echo $beneficiary['id']; ?>" class="text-decoration-none text-dark">
                                    <div class="user-item <?php echo ($selected_conversation == $beneficiary['id']) ? 'active' : ''; ?>">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($beneficiary['first_name'], 0, 1) . substr($beneficiary['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($beneficiary['first_name'] . ' ' . $beneficiary['last_name']); ?></div>
                                            <div class="user-role">Beneficiary</div>
                                        </div>
                                        <div class="user-status"></div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No assigned beneficiaries</p>
                                    <small class="text-muted">You need to be assigned to projects with beneficiaries</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Conversations -->
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-comments me-2"></i>Recent Conversations</h5>
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
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($conversation['other_user_name'], 0, 1)); ?>
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
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No conversations yet</p>
                                <small class="text-muted">Start a conversation with a beneficiary</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Message Thread -->
            <div class="col-lg-8">
                <?php if ($selected_conversation): ?>
                    <div class="content-card">
                        <div class="conversation-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3" style="background: white; color: var(--primary);">
                                    <?php echo strtoupper(substr($selected_user_name, 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($selected_user_name); ?></h5>
                                    <small class="opacity-75">
                                        <span id="typingIndicator" class="typing-indicator" style="display: none;">
                                            is typing <div class="typing-dots"><span></span><span></span><span></span></div>
                                        </span>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="mark_all_read" value="<?php echo $selected_conversation; ?>">
                                    <button type="submit" class="btn btn-sm btn-light">
                                        <i class="fas fa-check-double me-1"></i>Mark All Read
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                    <i class="fas fa-reply me-1"></i>Reply
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="message-thread" id="messageThread">
                                <?php if (count($conversation_messages) > 0): ?>
                                    <?php foreach ($conversation_messages as $message): ?>
                                    <div class="message-bubble <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?> <?php echo $message['is_urgent'] ? 'urgent' : ''; ?>">
                                        <div class="message-header">
                                            <span class="message-sender">
                                                <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'You' : htmlspecialchars($selected_user_name); ?>
                                            </span>
                                            <?php if ($message['is_urgent']): ?>
                                                <span class="badge-urgent">URGENT</span>
                                            <?php endif; ?>
                                            <?php if (isset($message['message_type']) && $message['message_type'] != 'general'): ?>
                                                <span class="badge-type"><?php echo ucfirst($message['message_type'] ?? 'general'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-content">
                                            <?php if (!empty($message['subject'])): ?>
                                                <strong><?php echo htmlspecialchars($message['subject']); ?></strong><br>
                                            <?php endif; ?>
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                            <?php if ($message['sender_id'] == $_SESSION['user_id']): ?>
                                                <span class="message-status">
                                                    <?php if ($message['is_read']): ?>
                                                        <i class="fas fa-check-double text-info" title="Read"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-check" title="Sent"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No messages in this conversation yet</p>
                                        <small class="text-muted">Send a message to start the conversation</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Reply Form -->
                            <div class="mt-3">
                                <form method="POST" id="replyForm">
                                    <input type="hidden" name="recipient_id" value="<?php echo $selected_conversation; ?>">
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" 
                                               placeholder="Message subject..." required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="3" 
                                                  placeholder="Type your message here..." required></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="message_type" class="form-label">Message Type</label>
                                                <select class="form-select" id="message_type" name="message_type">
                                                    <option value="general">General</option>
                                                    <option value="project">Project Related</option>
                                                    <option value="financial">Financial</option>
                                                    <option value="technical">Technical</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mt-4 pt-2">
                                                <input class="form-check-input" type="checkbox" id="is_urgent" name="is_urgent">
                                                <label class="form-check-label" for="is_urgent">
                                                    Mark as Urgent
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-shield-alt me-1"></i>All messages are encrypted and secure
                                        </small>
                                        <button type="submit" name="send_message" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Welcome/Empty State -->
                    <div class="content-card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-comments fa-4x text-primary mb-4"></i>
                            <h3>Welcome to Communication Center</h3>
                            <p class="text-muted mb-4">Select a beneficiary from the list to start a conversation or compose a new message.</p>
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="quick-actions">
                                        <a href="#" class="action-card" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                            <div class="action-icon">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <h6>Compose Message</h6>
                                            <p class="small text-muted mb-0">Send a new message to beneficiaries</p>
                                        </a>
                                        <a href="#" class="action-card" data-bs-toggle="modal" data-bs-target="#selectUserModal">
                                            <div class="action-icon">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                            <h6>Start Conversation</h6>
                                            <p class="small text-muted mb-0">Begin a new conversation</p>
                                        </a>
                                        <a href="../projects/index.php" class="action-card">
                                            <div class="action-icon">
                                                <i class="fas fa-project-diagram"></i>
                                            </div>
                                            <h6>View Projects</h6>
                                            <p class="small text-muted mb-0">Check assigned projects</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
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
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="recipient_id" class="form-label">Recipient</label>
                            <select class="form-select" id="recipient_id" name="recipient_id" required>
                                <option value="">Select a recipient...</option>
                                <?php foreach ($recipients as $recipient): ?>
                                <option value="<?php echo $recipient['id']; ?>" 
                                        <?php echo ($selected_conversation == $recipient['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']); ?>
                                    (<?php echo $recipient['role'] == 'beneficiary' ? 'Beneficiary' : 'Administrator'; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="modal_subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="modal_message" class="form-label">Message</label>
                            <textarea class="form-control" id="modal_message" name="message" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modal_message_type" class="form-label">Message Type</label>
                                    <select class="form-select" id="modal_message_type" name="message_type">
                                        <option value="general">General</option>
                                        <option value="project">Project Related</option>
                                        <option value="financial">Financial</option>
                                        <option value="technical">Technical</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4 pt-2">
                                    <input class="form-check-input" type="checkbox" id="modal_is_urgent" name="is_urgent">
                                    <label class="form-check-label" for="modal_is_urgent">
                                        Mark as Urgent
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Select User Modal -->
    <div class="modal fade" id="selectUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Start New Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_recipient_id" class="form-label">Select User</label>
                            <select class="form-select" id="new_recipient_id" name="new_recipient_id" required>
                                <option value="">Choose a user to start conversation...</option>
                                <?php foreach ($recipients as $recipient): ?>
                                <option value="<?php echo $recipient['id']; ?>">
                                    <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']); ?>
                                    (<?php echo $recipient['role'] == 'beneficiary' ? 'Beneficiary' : 'Administrator'; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This will open a conversation with the selected user where you can exchange messages.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="start_conversation" class="btn btn-primary">
                            <i class="fas fa-comments me-2"></i>Start Conversation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>CDF Monitoring & Evaluation System</h6>
                    <p class="mb-2 text-muted">Government of the Republic of Zambia</p>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure Government Communication Platform
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small text-muted mb-1">
                        <i class="fas fa-clock me-1"></i>
                        System Time: <?php echo date('F j, Y, g:i A'); ?>
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-user me-1"></i>
                        Logged in as: <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User search functionality
        document.getElementById('userSearch')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                const userName = item.querySelector('.user-name').textContent.toLowerCase();
                if (userName.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Auto-scroll to bottom of message thread
        function scrollToBottom() {
            const messageThread = document.getElementById('messageThread');
            if (messageThread) {
                messageThread.scrollTop = messageThread.scrollHeight;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });

        // Simulate typing indicator (for demo purposes)
        function showTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.style.display = 'inline-flex';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 3000);
            }
        }

        // Show typing indicator when user starts typing in reply form
        document.getElementById('message')?.addEventListener('focus', showTypingIndicator);

        // Auto-fill recipient in new message modal if conversation is selected
        document.addEventListener('DOMContentLoaded', function() {
            const recipientSelect = document.getElementById('recipient_id');
            const selectedConversation = '<?php echo $selected_conversation; ?>';
            
            if (selectedConversation && recipientSelect) {
                recipientSelect.value = selectedConversation;
            }
        });

        // Prevent unnecessary reloading - only reload when needed
        let shouldReload = false;
        
        // Check if we need to reload (only for new messages in background)
        function checkForNewMessages() {
            if (!document.hidden && !shouldReload) {
                fetch('../ajax/get_unread_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const currentUnread = <?php echo $unread_count; ?>;
                        if (data.unread_count > currentUnread) {
                            shouldReload = true;
                            // Show notification instead of auto-reload
                            showNewMessageNotification(data.unread_count - currentUnread);
                        }
                    })
                    .catch(error => console.error('Error checking messages:', error));
            }
        }

        // Show notification for new messages
        function showNewMessageNotification(count) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-bell me-2"></i>
                <strong>New Message!</strong> You have ${count} new message${count > 1 ? 's' : ''}.
                <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="location.reload()"></button>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 10000);
        }

        // Check for new messages every 30 seconds (less frequent)
        setInterval(checkForNewMessages, 30000);
    </script>
</body>
</html>
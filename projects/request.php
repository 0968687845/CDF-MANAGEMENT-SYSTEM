<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_data = [
        'project_type' => $_POST['project_type'] ?? '',
        'constituency' => $_POST['constituency'] ?? '',
        'priority_level' => $_POST['priority_level'] ?? '',
        'justification' => $_POST['justification'] ?? '',
        'expected_timeline' => $_POST['expected_timeline'] ?? '',
        'estimated_budget' => $_POST['estimated_budget'] ?? '',
        'community_impact' => $_POST['community_impact'] ?? '',
        'special_requirements' => $_POST['special_requirements'] ?? ''
    ];
    
    // Basic validation
    $required_fields = ['project_type', 'constituency', 'priority_level', 'justification'];
    $is_valid = true;
    
    foreach ($required_fields as $field) {
        if (empty($request_data[$field])) {
            $is_valid = false;
            $error_message = "Please fill in all required fields.";
            break;
        }
    }
    
    if ($is_valid) {
        // In a real application, you would save this to the database
        // For now, we'll simulate a successful submission
        $success_message = "Project assignment request submitted successfully! Your request has been sent to the administrator for review.";
        
        // Log the activity
        logActivity($_SESSION['user_id'], 'project_request', 'Submitted project assignment request for ' . $request_data['project_type']);
        
        // Clear form data
        $request_data = array_fill_keys(array_keys($request_data), '');
    }
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

$pageTitle = "Request Project Assignment - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Request project assignment - CDF Management System - Government of Zambia">
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
        }

        body {
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
            color: var(--gray-900);
            line-height: 1.7;
            font-weight: 400;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Enhanced Navigation */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow-lg);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: var(--border-radius-sm);
            position: relative;
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
            background: linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
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
            font-weight: 700;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .profile-info h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .profile-info .lead {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Action Buttons */
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
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            background: var(--secondary-gradient);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Enhanced Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .content-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-4px);
        }

        .card-header {
            background: var(--light-gradient);
            border-bottom: 4px solid var(--primary);
            padding: 1.5rem;
            position: relative;
        }

        .card-header h5 {
            color: var(--primary-dark);
            font-weight: 800;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Form Styles */
        .request-form {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary);
        }

        .form-section h6 {
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius-sm);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .required-field::after {
            content: " *";
            color: var(--danger);
        }

        /* Priority Badges */
        .priority-high {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: var(--white);
        }

        .priority-medium {
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
            color: var(--dark);
        }

        .priority-low {
            background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
            color: var(--white);
        }

        /* Alert Styles */
        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: var(--success-light);
            color: var(--success-dark);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger-dark);
            border-left: 4px solid var(--danger);
        }

        /* Info Cards */
        .info-card {
            background: var(--info-light);
            border: 1px solid var(--info);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card h6 {
            color: var(--info-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
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
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .form-section {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                text-align: center;
            }
            
            .card-body {
                padding: 1.5rem;
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
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-project-diagram me-1"></i>Assigned Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="request.php">
                            <i class="fas fa-envelope me-1"></i>Request Assignment
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
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
                    <h1>Request Project Assignment</h1>
                    <p class="lead">Submit a request for new project monitoring assignments</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong> | Department: <strong><?php echo htmlspecialchars($userData['department'] ?? 'M&E Department'); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary-custom">
                    <i class="fas fa-arrow-left me-2"></i>Back to Projects
                </a>
                <a href="../officer_dashboard.php" class="btn btn-outline-custom">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Information Card -->
        <div class="info-card">
            <h6><i class="fas fa-info-circle me-2"></i>Request Guidelines</h6>
            <p class="mb-2">Please provide detailed information about the type of project assignment you are requesting. Your request will be reviewed by the system administrator and assigned based on availability and your expertise.</p>
            <p class="mb-0"><strong>Note:</strong> Fields marked with <span class="text-danger">*</span> are required.</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-custom mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fa-lg"></i>
                    <div>
                        <h6 class="mb-1">Request Submitted Successfully!</h6>
                        <p class="mb-0"><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-custom mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <div>
                        <h6 class="mb-1">Submission Error</h6>
                        <p class="mb-0"><?php echo $error_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Request Form -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i>Project Assignment Request Form</h5>
            </div>
            <div class="card-body">
                <form class="request-form" method="POST" action="">
                    <!-- Project Details Section -->
                    <div class="form-section">
                        <h6><i class="fas fa-project-diagram me-2"></i>Project Details</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="project_type" class="form-label required-field">Project Type</label>
                                <select class="form-select" id="project_type" name="project_type" required>
                                    <option value="">Select project type</option>
                                    <option value="Youth Empowerment" <?php echo ($_POST['project_type'] ?? '') === 'Youth Empowerment' ? 'selected' : ''; ?>>Youth Empowerment</option>
                                    <option value="Women Empowerment" <?php echo ($_POST['project_type'] ?? '') === 'Women Empowerment' ? 'selected' : ''; ?>>Women Empowerment</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="constituency" class="form-label required-field">Preferred Constituency</label>
                                <select class="form-select" id="constituency" name="constituency" required>
                                    <option value="">Select constituency</option>
                                    <?php
                                    $constituencies = getConstituencies();
                                    foreach ($constituencies as $constituency):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($constituency); ?>" 
                                            <?php echo ($_POST['constituency'] ?? '') === $constituency ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($constituency); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority_level" class="form-label required-field">Priority Level</label>
                                <select class="form-select" id="priority_level" name="priority_level" required>
                                    <option value="">Select priority level</option>
                                    <option value="high" <?php echo ($_POST['priority_level'] ?? '') === 'high' ? 'selected' : ''; ?>>High Priority</option>
                                    <option value="medium" <?php echo ($_POST['priority_level'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                                    <option value="low" <?php echo ($_POST['priority_level'] ?? '') === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expected_timeline" class="form-label">Expected Timeline (Months)</label>
                                <select class="form-select" id="expected_timeline" name="expected_timeline">
                                    <option value="">Select timeline</option>
                                    <option value="1-3" <?php echo ($_POST['expected_timeline'] ?? '') === '1-3' ? 'selected' : ''; ?>>1-3 Months</option>
                                    <option value="3-6" <?php echo ($_POST['expected_timeline'] ?? '') === '3-6' ? 'selected' : ''; ?>>3-6 Months</option>
                                    <option value="6-12" <?php echo ($_POST['expected_timeline'] ?? '') === '6-12' ? 'selected' : ''; ?>>6-12 Months</option>
                                    <option value="12+" <?php echo ($_POST['expected_timeline'] ?? '') === '12+' ? 'selected' : ''; ?>>12+ Months</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="estimated_budget" class="form-label">Estimated Budget Range (ZMW)</label>
                            <select class="form-select" id="estimated_budget" name="estimated_budget">
                                <option value="">Select budget range</option>
                                <option value="0-50000" <?php echo ($_POST['estimated_budget'] ?? '') === '0-50000' ? 'selected' : ''; ?>>Up to ZMW 50,000</option>
                                <option value="50000-200000" <?php echo ($_POST['estimated_budget'] ?? '') === '50000-200000' ? 'selected' : ''; ?>>ZMW 50,000 - 200,000</option>
                                <option value="200000-500000" <?php echo ($_POST['estimated_budget'] ?? '') === '200000-500000' ? 'selected' : ''; ?>>ZMW 200,000 - 500,000</option>
                                <option value="500000+" <?php echo ($_POST['estimated_budget'] ?? '') === '500000+' ? 'selected' : ''; ?>>ZMW 500,000+</option>
                            </select>
                        </div>
                    </div>

                    <!-- Justification Section -->
                    <div class="form-section">
                        <h6><i class="fas fa-file-alt me-2"></i>Justification & Requirements</h6>
                        
                        <div class="mb-3">
                            <label for="justification" class="form-label required-field">Justification for Request</label>
                            <textarea class="form-control" id="justification" name="justification" rows="4" 
                                      placeholder="Please explain why you are requesting this specific project assignment, including your relevant experience and expertise..." 
                                      required><?php echo htmlspecialchars($_POST['justification'] ?? ''); ?></textarea>
                            <div class="form-text">Provide detailed justification for your project assignment request.</div>
                        </div>

                        <div class="mb-3">
                            <label for="community_impact" class="form-label">Expected Community Impact</label>
                            <textarea class="form-control" id="community_impact" name="community_impact" rows="3"
                                      placeholder="Describe the expected impact on the community and how you plan to monitor this..."><?php echo htmlspecialchars($_POST['community_impact'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="special_requirements" class="form-label">Special Requirements or Considerations</label>
                            <textarea class="form-control" id="special_requirements" name="special_requirements" rows="3"
                                      placeholder="Any special equipment, training, or support required for this assignment..."><?php echo htmlspecialchars($_POST['special_requirements'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-2"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-question-circle me-2"></i>Request Process Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-clock me-2"></i>Processing Timeline</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Initial review: 1-2 business days</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Assignment decision: 3-5 business days</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Notification: Within 24 hours of decision</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-envelope me-2"></i>Contact Information</h6>
                        <p class="mb-2">For urgent requests or questions about your submission:</p>
                        <ul class="list-unstyled">
                            <li class="mb-1"><i class="fas fa-phone me-2"></i>+260 211 123 456</li>
                            <li class="mb-1"><i class="fas fa-envelope me-2"></i>projects@cdf.gov.zm</li>
                            <li><i class="fas fa-building me-2"></i>CDF Project Management Office</li>
                        </ul>
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
        // Update server time every second
        function updateServerTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('serverTime').textContent = timeString;
        }
        
        setInterval(updateServerTime, 1000);
        updateServerTime();

        // Form validation enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.request-form');
            const requiredFields = form.querySelectorAll('[required]');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields marked with *.');
                }
            });
        });
    </script>
</body>
</html>
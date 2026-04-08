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

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process settings
    $default_view = $_POST['default_view'] ?? 'grid';
    $items_per_page = intval($_POST['items_per_page'] ?? 10);
    $notifications_email = isset($_POST['notifications_email']) ? 1 : 0;
    $notifications_inapp = isset($_POST['notifications_inapp']) ? 1 : 0;
    $timezone = $_POST['timezone'] ?? 'Africa/Lusaka';
    $language = $_POST['language'] ?? 'en';
    $theme = $_POST['theme'] ?? 'light';
    
    // Validation
    if ($items_per_page < 5 || $items_per_page > 100) {
        $errors[] = "Items per page must be between 5 and 100";
    }
    
    if (!in_array($timezone, timezone_identifiers_list())) {
        $errors[] = "Invalid timezone selected";
    }
    
    $allowed_languages = ['en' => 'English'];
    if (!array_key_exists($language, $allowed_languages)) {
        $errors[] = "Invalid language selected";
    }
    
    $allowed_themes = ['light', 'dark', 'auto'];
    if (!in_array($theme, $allowed_themes)) {
        $errors[] = "Invalid theme selected";
    }
    
    // If no errors, save settings
    if (empty($errors)) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Check if settings exist for this user
            $check_query = "SELECT id FROM user_settings WHERE user_id = :user_id";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing settings
                $query = "UPDATE user_settings SET 
                    default_view = :default_view,
                    items_per_page = :items_per_page,
                    notifications_email = :notifications_email,
                    notifications_inapp = :notifications_inapp,
                    timezone = :timezone,
                    language = :language,
                    theme = :theme,
                    updated_at = NOW()
                    WHERE user_id = :user_id";
            } else {
                // Insert new settings
                $query = "INSERT INTO user_settings SET 
                    user_id = :user_id,
                    default_view = :default_view,
                    items_per_page = :items_per_page,
                    notifications_email = :notifications_email,
                    notifications_inapp = :notifications_inapp,
                    timezone = :timezone,
                    language = :language,
                    theme = :theme,
                    created_at = NOW()";
            }
            
            $stmt = $pdo->prepare($query);
            if ($check_stmt->rowCount() > 0) {
                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            }
            $stmt->bindParam(':default_view', $default_view);
            $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
            $stmt->bindParam(':notifications_email', $notifications_email, PDO::PARAM_INT);
            $stmt->bindParam(':notifications_inapp', $notifications_inapp, PDO::PARAM_INT);
            $stmt->bindParam(':timezone', $timezone);
            $stmt->bindParam(':language', $language);
            $stmt->bindParam(':theme', $theme);
            
            if ($stmt->execute()) {
                $success = 'Dashboard settings updated successfully!';
                
                // Update session with new settings
                $_SESSION['user_settings'] = [
                    'default_view' => $default_view,
                    'items_per_page' => $items_per_page,
                    'notifications_email' => $notifications_email,
                    'notifications_inapp' => $notifications_inapp,
                    'timezone' => $timezone,
                    'language' => $language,
                    'theme' => $theme
                ];
            } else {
                $errors[] = 'Failed to update settings. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log("Error saving dashboard settings: " . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get current settings
$current_settings = getUserSettings($_SESSION['user_id']);

$pageTitle = "Dashboard Settings - CDF Management System";

// Function to get user settings
function getUserSettings($user_id) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $query = "SELECT * FROM user_settings WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($settings) {
            return $settings;
        }
    } catch (PDOException $e) {
        error_log("Error fetching user settings: " . $e->getMessage());
    }
    
    // Return default settings if none found
    return [
        'default_view' => 'grid',
        'items_per_page' => 10,
        'notifications_email' => 1,
        'notifications_inapp' => 1,
        'timezone' => 'Africa/Lusaka',
        'language' => 'en',
        'theme' => 'light'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Dashboard Settings for CDF Management System - Government of Zambia">
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
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
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

        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: 8px;
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

        .dashboard-header {
            background: var(--primary-gradient);
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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--secondary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2rem;
            font-weight: 800;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .profile-info h1 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

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
            padding: 1rem 2rem;
            font-weight: 700;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            background: var(--secondary-gradient);
            color: var(--dark);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.7);
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .content-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .content-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
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

        .form-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .dropdown-item.active {
            background-color: var(--primary);
            color: white;
        }

        .settings-section {
            margin-bottom: 2.5rem;
        }

        .settings-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .preview-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 2px dashed var(--gray-light);
            text-align: center;
            margin-top: 1rem;
        }

        .theme-option {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 1rem;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 1rem;
        }

        .theme-option:hover {
            border-color: var(--primary);
        }

        .theme-option.selected {
            border-color: var(--primary);
            background-color: rgba(26, 78, 138, 0.05);
        }

        .theme-preview {
            width: 100%;
            height: 80px;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .theme-light {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }

        .theme-dark {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            border: 1px solid #718096;
        }

        .theme-auto {
            background: linear-gradient(135deg, #f8f9fa 50%, #2d3748 50%);
            border: 1px solid #dee2e6;
        }

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
            
            .action-buttons {
                justify-content: center;
            }
            
            .form-card {
                padding: 1.5rem;
            }
        }

        /* Form validation styles */
        .is-invalid {
            border-color: var(--danger) !important;
        }

        .invalid-feedback {
            display: none;
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .is-invalid ~ .invalid-feedback {
            display: block;
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
                            </a></li>
                            <li><a class="dropdown-item" href="../analytics/dashboard.php">
                                <i class="fas fa-chart-bar me-2"></i>Analytics
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="officer_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Settings
                            </a></li>
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile Settings
                            </a></li>
                            <li><a class="dropdown-item" href="system.php">
                                <i class="fas fa-sliders-h me-2"></i>System Settings
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item active" href="officer_dashboard.php">
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
                    <h1>Dashboard Settings</h1>
                    <p class="lead">Customize your dashboard experience and preferences</p>
                    <p class="mb-0">Officer: <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="../officer_dashboard.php" class="btn btn-outline-custom">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <a href="profile.php" class="btn btn-outline-custom">
                    <i class="fas fa-user me-2"></i>Profile Settings
                </a>
                <button type="button" class="btn btn-primary-custom" onclick="resetToDefaults()">
                    <i class="fas fa-undo me-2"></i>Reset to Defaults
                </button>
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
                    <h4 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Preferences</h4>
                    
                    <form method="POST" id="settingsForm" novalidate>
                        <!-- Display Settings -->
                        <div class="settings-section">
                            <h5 class="section-title"><i class="fas fa-desktop me-2"></i>Display Settings</h5>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="default_view" class="form-label">Default View</label>
                                    <select class="form-select" id="default_view" name="default_view">
                                        <option value="grid" <?php echo ($current_settings['default_view'] ?? 'grid') === 'grid' ? 'selected' : ''; ?>>Grid View</option>
                                        <option value="list" <?php echo ($current_settings['default_view'] ?? 'grid') === 'list' ? 'selected' : ''; ?>>List View</option>
                                        <option value="table" <?php echo ($current_settings['default_view'] ?? 'grid') === 'table' ? 'selected' : ''; ?>>Table View</option>
                                    </select>
                                    <div class="form-text">Choose how projects and visits are displayed by default</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="items_per_page" class="form-label">Items Per Page</label>
                                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                                           min="5" max="100" value="<?php echo $current_settings['items_per_page'] ?? 10; ?>">
                                    <div class="invalid-feedback">Please enter a number between 5 and 100</div>
                                    <div class="form-text">Number of items to show per page in lists</div>
                                </div>
                            </div>

                            <!-- Theme Selection -->
                            <div class="mb-4">
                                <label class="form-label">Theme</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="theme-option <?php echo ($current_settings['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>" onclick="selectTheme('light')">
                                            <div class="theme-preview theme-light"></div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" value="light" id="themeLight" <?php echo ($current_settings['theme'] ?? 'light') === 'light' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="themeLight">
                                                    <i class="fas fa-sun me-1"></i>Light
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="theme-option <?php echo ($current_settings['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>" onclick="selectTheme('dark')">
                                            <div class="theme-preview theme-dark"></div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" value="dark" id="themeDark" <?php echo ($current_settings['theme'] ?? 'light') === 'dark' ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-white" for="themeDark">
                                                    <i class="fas fa-moon me-1"></i>Dark
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="theme-option <?php echo ($current_settings['theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>" onclick="selectTheme('auto')">
                                            <div class="theme-preview theme-auto"></div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" value="auto" id="themeAuto" <?php echo ($current_settings['theme'] ?? 'light') === 'auto' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="themeAuto">
                                                    <i class="fas fa-adjust me-1"></i>Auto
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-text">Choose your preferred color theme</div>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="settings-section">
                            <h5 class="section-title"><i class="fas fa-bell me-2"></i>Notification Settings</h5>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notifications_email" name="notifications_email" <?php echo ($current_settings['notifications_email'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications_email">
                                        Email Notifications
                                    </label>
                                </div>
                                <div class="form-text">Receive notifications via email</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notifications_inapp" name="notifications_inapp" <?php echo ($current_settings['notifications_inapp'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications_inapp">
                                        In-App Notifications
                                    </label>
                                </div>
                                <div class="form-text">Show notifications within the application</div>
                            </div>
                        </div>

                        <!-- Regional Settings -->
                        <div class="settings-section">
                            <h5 class="section-title"><i class="fas fa-globe me-2"></i>Regional Settings</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php
                                        $timezones = timezone_identifiers_list();
                                        $current_timezone = $current_settings['timezone'] ?? 'Africa/Lusaka';
                                        foreach ($timezones as $tz) {
                                            $selected = $tz === $current_timezone ? 'selected' : '';
                                            echo "<option value=\"$tz\" $selected>$tz</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">Set your local timezone for accurate time displays</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="language" class="form-label">Language</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="en" <?php echo ($current_settings['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                        <!-- Add more languages as needed -->
                                    </select>
                                    <div class="form-text">Choose your preferred language</div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                            <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Reset Changes
                            </button>
                            <a href="../officer_dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Settings Preview -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-eye me-2"></i>Settings Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="preview-card">
                            <i class="fas fa-tachometer-alt fa-3x text-primary mb-3"></i>
                            <h6>Dashboard Preview</h6>
                            <p class="small text-muted">Your changes will be reflected in real-time</p>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-2">
                                    <span>View:</span>
                                    <span class="fw-bold" id="previewView"><?php echo ucfirst($current_settings['default_view'] ?? 'grid'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span>Items per page:</span>
                                    <span class="fw-bold" id="previewItems"><?php echo $current_settings['items_per_page'] ?? 10; ?></span>
                                </div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span>Theme:</span>
                                    <span class="fw-bold" id="previewTheme"><?php echo ucfirst($current_settings['theme'] ?? 'light'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span>Timezone:</span>
                                    <span class="fw-bold" id="previewTimezone"><?php echo $current_settings['timezone'] ?? 'Africa/Lusaka'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-lightbulb me-2"></i>Settings Tips</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-desktop text-primary me-2"></i>Display Settings</h6>
                            <p class="small text-muted">Choose a view that works best for your workflow. Grid view is great for visual overview, while list view shows more details.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-bell text-warning me-2"></i>Notifications</h6>
                            <p class="small text-muted">Enable email notifications for important updates even when you're not active in the system.</p>
                        </div>
                        <div class="mb-3">
                            <h6><i class="fas fa-globe text-success me-2"></i>Regional Settings</h6>
                            <p class="small text-muted">Set your correct timezone to ensure all dates and times are displayed accurately for your location.</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Changes</h5>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Last login:</span>
                                <span class="text-muted"><?php echo date('M j, g:i A'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Settings updated:</span>
                                <span class="text-muted"><?php echo isset($current_settings['updated_at']) ? date('M j, g:i A', strtotime($current_settings['updated_at'])) : 'Never'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Current version:</span>
                                <span class="text-muted">2.5.1</span>
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
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Highlight active menu item
            highlightActiveMenuItem();

            // Initialize form validation
            initializeFormValidation();

            // Initialize real-time preview updates
            initializePreviewUpdates();

            console.log('Dashboard Settings page loaded successfully');
        });

        // Function to highlight active menu item
        function highlightActiveMenuItem() {
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.nav-link, .dropdown-item');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && (href === currentPage || href.includes('officer_dashboard.php'))) {
                    item.classList.add('active');
                    
                    // Also highlight parent dropdown if this is a dropdown item
                    const dropdown = item.closest('.dropdown-menu');
                    if (dropdown) {
                        const dropdownToggle = dropdown.previousElementSibling;
                        if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                            dropdownToggle.classList.add('active');
                        }
                    }
                }
            });
        }

        // Theme selection
        function selectTheme(theme) {
            // Update radio button
            document.querySelector(`input[name="theme"][value="${theme}"]`).checked = true;
            
            // Update theme option visuals
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector(`.theme-option input[value="${theme}"]`).closest('.theme-option').classList.add('selected');
            
            // Update preview
            updatePreview();
        }

        // Initialize real-time preview updates
        function initializePreviewUpdates() {
            const form = document.getElementById('settingsForm');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('change', updatePreview);
                input.addEventListener('input', updatePreview);
            });
            
            // Initial preview update
            updatePreview();
        }

        // Update preview based on current form values
        function updatePreview() {
            const form = document.getElementById('settingsForm');
            const formData = new FormData(form);
            
            // Update preview elements
            document.getElementById('previewView').textContent = formData.get('default_view') ? formData.get('default_view').charAt(0).toUpperCase() + formData.get('default_view').slice(1) : 'Grid';
            document.getElementById('previewItems').textContent = formData.get('items_per_page') || '10';
            document.getElementById('previewTheme').textContent = formData.get('theme') ? formData.get('theme').charAt(0).toUpperCase() + formData.get('theme').slice(1) : 'Light';
            document.getElementById('previewTimezone').textContent = formData.get('timezone') || 'Africa/Lusaka';
        }

        // Form validation
        function initializeFormValidation() {
            const form = document.getElementById('settingsForm');
            const fields = form.querySelectorAll('input, select, textarea');
            
            fields.forEach(field => {
                field.addEventListener('blur', validateField);
                field.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        validateField.call(this);
                    }
                });
            });

            // Items per page specific validation
            document.getElementById('items_per_page').addEventListener('change', function() {
                validateField.call(this);
            });
        }

        function validateField() {
            const field = this;
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';

            // Clear previous validation
            field.classList.remove('is-invalid');
            
            // Specific field validations
            switch (field.id) {
                case 'items_per_page':
                    const items = parseInt(value);
                    if (isNaN(items) || items < 5 || items > 100) {
                        isValid = false;
                        errorMessage = 'Items per page must be between 5 and 100';
                    }
                    break;
            }
            
            // Update field state
            if (!isValid) {
                field.classList.add('is-invalid');
                let feedback = field.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    field.parentNode.insertBefore(feedback, field.nextElementSibling);
                }
                feedback.textContent = errorMessage;
            } else {
                const feedback = field.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            }
            
            return isValid;
        }

        function validateForm() {
            const form = document.getElementById('settingsForm');
            const fields = form.querySelectorAll('input, select, textarea');
            let isValid = true;
            
            fields.forEach(field => {
                if (!validateField.call(field)) {
                    isValid = false;
                }
            });
            
            return isValid;
        }

        // Form submission
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                submitBtn.disabled = true;
                
                // Submit the form
                this.submit();
            } else {
                // Scroll to first error
                const firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                
                showNotification('Please fix the errors in the form before saving.', 'error');
            }
        });

        // Reset form to current values
        function resetForm() {
            // This would normally reset to the originally loaded values
            // For now, we'll just reload the page
            if (confirm('Are you sure you want to reset all changes?')) {
                window.location.reload();
            }
        }

        // Reset to default settings
        function resetToDefaults() {
            if (confirm('Are you sure you want to reset all settings to default values? This cannot be undone.')) {
                // Set default values
                document.getElementById('default_view').value = 'grid';
                document.getElementById('items_per_page').value = '10';
                document.getElementById('notifications_email').checked = true;
                document.getElementById('notifications_inapp').checked = true;
                document.getElementById('timezone').value = 'Africa/Lusaka';
                document.getElementById('language').value = 'en';
                selectTheme('light');
                
                // Update preview
                updatePreview();
                
                showNotification('Settings reset to default values. Click "Save Settings" to apply.', 'info');
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';
            }, 10);
            
            setTimeout(() => {
                notification.style.transform = 'translateY(-20px)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }

        // Enhanced server time with date
        function updateServerTime() {
            const now = new Date();
            const dateString = now.toLocaleDateString('en-US', { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const serverTimeElement = document.getElementById('serverTime');
            if (serverTimeElement) {
                serverTimeElement.textContent = `${dateString} ${timeString}`;
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('settingsForm').dispatchEvent(new Event('submit'));
            }
            
            // Escape key to cancel
            if (e.key === 'Escape') {
                e.preventDefault();
                window.location.href = '../officer_dashboard.php';
            }
        });

        // Update server time immediately and then every second
        updateServerTime();
        setInterval(updateServerTime, 1000);
    </script>
</body>
</html>
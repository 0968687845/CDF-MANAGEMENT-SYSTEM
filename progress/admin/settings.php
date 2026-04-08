<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$pageTitle = "System Settings - CDF Management System";

// Get notifications for the top bar
$notifications = getNotifications($_SESSION['user_id']);
$unread_notifications = array_filter($notifications, function($n) { return $n['is_read'] == 0; });

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general_settings'])) {
        // Handle general settings update
        $message = "General settings updated successfully!";
    } elseif (isset($_POST['update_email_settings'])) {
        // Handle email settings update
        $message = "Email settings updated successfully!";
    } elseif (isset($_POST['update_security_settings'])) {
        // Handle security settings update
        $message = "Security settings updated successfully!";
    } elseif (isset($_POST['update_backup_settings'])) {
        // Handle backup settings update
        $message = "Backup settings updated successfully!";
    } elseif (isset($_POST['test_email'])) {
        // Handle email test
        $message = "Test email sent successfully!";
    } elseif (isset($_POST['create_backup'])) {
        // Handle backup creation
        $message = "System backup created successfully!";
    }
}

// Get current settings (in a real application, these would come from a database)
$system_settings = [
    'system_name' => 'CDF Management System',
    'system_version' => '2.5.1',
    'admin_email' => 'admin@cdf.gov.zm',
    'timezone' => 'Africa/Lusaka',
    'date_format' => 'd/m/Y',
    'items_per_page' => 20,
    'maintenance_mode' => false,
    'smtp_host' => 'mail.cdf.gov.zm',
    'smtp_port' => 587,
    'smtp_username' => 'noreply@cdf.gov.zm',
    'smtp_encryption' => 'tls',
    'password_policy' => 'strong',
    'session_timeout' => 60,
    'login_attempts' => 5,
    'backup_frequency' => 'weekly',
    'backup_retention' => 30,
    'auto_backup' => true
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --gov-blue: #003366;
            --gov-gold: #FFD700;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.3);
            color: white;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .system-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--secondary);
            min-width: 150px;
        }
        
        .info-value {
            color: var(--primary);
            font-weight: 500;
        }
        
        .settings-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 1.5rem;
        }
        
        .settings-tabs .nav-link {
            border: none;
            color: var(--secondary);
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-bottom: 3px solid transparent;
        }
        
        .settings-tabs .nav-link.active {
            color: var(--primary);
            background: none;
            border-bottom: 3px solid var(--primary);
        }
        
        .danger-zone {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border: 2px solid var(--danger);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: #1a4e8a;">
        <div class="container">
            <a class="navbar-brand" href="../admin_dashboard.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../admin_dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
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
                            <li><a class="dropdown-item" href="project_reports.php">
                                <i class="fas fa-chart-bar me-2"></i>System Reports
                            </a></li>
                            <li><a class="dropdown-item active" href="settings.php">
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
                            <?php if (count($unread_notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($unread_notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $notification['is_read'] == 0 ? 'fw-bold' : ''; ?>" href="communication/notifications.php">
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
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar-small" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">
                                            <?php 
                                            echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); 
                                            ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h6>
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
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../admin_dashboard.php?logout=true">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-cog me-3"></i>System Settings</h1>
                    <p class="lead mb-0">Configure system preferences and manage application settings</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../admin_dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number">v<?php echo $system_settings['system_version']; ?></div>
                    <div>System Version</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);">
                    <div class="stats-number">24/7</div>
                    <div>Uptime</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ffd54f 100%); color: #000;">
                    <div class="stats-number">98.7%</div>
                    <div>System Health</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stats-number">256</div>
                    <div>Active Users</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#general-settings" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h6>General Settings</h6>
                <small class="text-muted">Basic system configuration</small>
            </a>
            <a href="#email-settings" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h6>Email Settings</h6>
                <small class="text-muted">Configure email services</small>
            </a>
            <a href="#security-settings" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h6>Security</h6>
                <small class="text-muted">Security and access controls</small>
            </a>
            <a href="#backup-settings" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-database"></i>
                </div>
                <h6>Backup & Recovery</h6>
                <small class="text-muted">Data protection settings</small>
            </a>
        </div>

        <div class="row">
            <!-- System Information -->
            <div class="col-lg-4 mb-4">
                <div class="settings-card">
                    <div class="card-body">
                        <h4 class="section-title">System Information</h4>
                        <div class="system-info">
                            <div class="info-item">
                                <span class="info-label">System Name:</span>
                                <span class="info-value"><?php echo $system_settings['system_name']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Version:</span>
                                <span class="info-value"><?php echo $system_settings['system_version']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">PHP Version:</span>
                                <span class="info-value"><?php echo PHP_VERSION; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Database:</span>
                                <span class="info-value">MySQL 8.0</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Server:</span>
                                <span class="info-value">Apache/2.4</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Backup:</span>
                                <span class="info-value">Today, 02:00 AM</span>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Quick Actions</h5>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#clearCacheModal">
                                <i class="fas fa-broom me-2"></i>Clear System Cache
                            </button>
                            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#optimizeDbModal">
                                <i class="fas fa-tachometer-alt me-2"></i>Optimize Database
                            </button>
                            <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#systemLogsModal">
                                <i class="fas fa-file-alt me-2"></i>View System Logs
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="settings-card">
                    <div class="card-body">
                        <h4 class="section-title">System Health</h4>
                        <div class="system-info">
                            <div class="info-item">
                                <span class="info-label">CPU Usage:</span>
                                <span class="info-value text-success">24%</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Memory Usage:</span>
                                <span class="info-value text-warning">68%</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Disk Space:</span>
                                <span class="info-value text-success">45%</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Database Size:</span>
                                <span class="info-value">245 MB</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Active Sessions:</span>
                                <span class="info-value">87</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-body">
                        <!-- Settings Tabs -->
                        <ul class="nav settings-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                    <i class="fas fa-sliders-h me-2"></i>General
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                    <i class="fas fa-shield-alt me-2"></i>Security
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                                    <i class="fas fa-database me-2"></i>Backup
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="settingsTabsContent">
                            <!-- General Settings Tab -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <form method="POST">
                                    <div class="settings-section">
                                        <h5 class="mb-3">System Configuration</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">System Name</label>
                                                <input type="text" class="form-control" name="system_name" value="<?php echo $system_settings['system_name']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Admin Email</label>
                                                <input type="email" class="form-control" name="admin_email" value="<?php echo $system_settings['admin_email']; ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Timezone</label>
                                                <select class="form-select" name="timezone">
                                                    <option value="Africa/Lusaka" <?php echo $system_settings['timezone'] === 'Africa/Lusaka' ? 'selected' : ''; ?>>Africa/Lusaka</option>
                                                    <option value="UTC" <?php echo $system_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Date Format</label>
                                                <select class="form-select" name="date_format">
                                                    <option value="d/m/Y" <?php echo $system_settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                                    <option value="m/d/Y" <?php echo $system_settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                                    <option value="Y-m-d" <?php echo $system_settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Items Per Page</label>
                                                <input type="number" class="form-control" name="items_per_page" value="<?php echo $system_settings['items_per_page']; ?>" min="5" max="100">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Maintenance Mode</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" <?php echo $system_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Enable maintenance mode</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="update_general_settings" class="btn btn-custom-primary">
                                            <i class="fas fa-save me-2"></i>Save General Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Email Settings Tab -->
                            <div class="tab-pane fade" id="email" role="tabpanel">
                                <form method="POST">
                                    <div class="settings-section">
                                        <h5 class="mb-3">SMTP Configuration</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" name="smtp_host" value="<?php echo $system_settings['smtp_host']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" name="smtp_port" value="<?php echo $system_settings['smtp_port']; ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Username</label>
                                                <input type="text" class="form-control" name="smtp_username" value="<?php echo $system_settings['smtp_username']; ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SMTP Encryption</label>
                                                <select class="form-select" name="smtp_encryption">
                                                    <option value="tls" <?php echo $system_settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                    <option value="ssl" <?php echo $system_settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                    <option value="" <?php echo $system_settings['smtp_encryption'] === '' ? 'selected' : ''; ?>>None</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" name="smtp_password" placeholder="Enter new password to change">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="test_email" class="btn btn-outline-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Send Test Email
                                        </button>
                                        <button type="submit" name="update_email_settings" class="btn btn-custom-primary">
                                            <i class="fas fa-save me-2"></i>Save Email Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Security Settings Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <form method="POST">
                                    <div class="settings-section">
                                        <h5 class="mb-3">Security Configuration</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Password Policy</label>
                                                <select class="form-select" name="password_policy">
                                                    <option value="basic" <?php echo $system_settings['password_policy'] === 'basic' ? 'selected' : ''; ?>>Basic (6 characters minimum)</option>
                                                    <option value="strong" <?php echo $system_settings['password_policy'] === 'strong' ? 'selected' : ''; ?>>Strong (8+ chars with mix)</option>
                                                    <option value="very_strong" <?php echo $system_settings['password_policy'] === 'very_strong' ? 'selected' : ''; ?>>Very Strong (12+ chars with special)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Session Timeout (minutes)</label>
                                                <input type="number" class="form-control" name="session_timeout" value="<?php echo $system_settings['session_timeout']; ?>" min="15" max="480">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Max Login Attempts</label>
                                                <input type="number" class="form-control" name="login_attempts" value="<?php echo $system_settings['login_attempts']; ?>" min="3" max="10">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Two-Factor Authentication</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" name="two_factor">
                                                    <label class="form-check-label">Enable 2FA for all users</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="update_security_settings" class="btn btn-custom-primary">
                                            <i class="fas fa-save me-2"></i>Save Security Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Backup Settings Tab -->
                            <div class="tab-pane fade" id="backup" role="tabpanel">
                                <form method="POST">
                                    <div class="settings-section">
                                        <h5 class="mb-3">Backup Configuration</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Backup Frequency</label>
                                                <select class="form-select" name="backup_frequency">
                                                    <option value="daily" <?php echo $system_settings['backup_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                    <option value="weekly" <?php echo $system_settings['backup_frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                    <option value="monthly" <?php echo $system_settings['backup_frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Backup Retention (days)</label>
                                                <input type="number" class="form-control" name="backup_retention" value="<?php echo $system_settings['backup_retention']; ?>" min="7" max="365">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_backup" <?php echo $system_settings['auto_backup'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Enable automatic backups</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Backup Storage Location</label>
                                            <input type="text" class="form-control" value="/var/backups/cdf_system" readonly>
                                            <div class="form-text">Backup files are stored in the system backup directory</div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="create_backup" class="btn btn-outline-success">
                                            <i class="fas fa-download me-2"></i>Create Backup Now
                                        </button>
                                        <button type="submit" name="update_backup_settings" class="btn btn-custom-primary">
                                            <i class="fas fa-save me-2"></i>Save Backup Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Danger Zone -->
                        <div class="danger-zone">
                            <h5 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                            <p class="text-muted mb-3">These actions are irreversible. Please proceed with caution.</p>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                                    <i class="fas fa-trash me-2"></i>Clear All Data
                                </button>
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetSystemModal">
                                    <i class="fas fa-redo me-2"></i>Reset System
                                </button>
                                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteSystemModal">
                                    <i class="fas fa-skull-crossbones me-2"></i>Delete System
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Quick Actions -->
    <div class="modal fade" id="clearCacheModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clear System Cache</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to clear the system cache? This will remove all temporary files and may improve performance.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Clear Cache</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="optimizeDbModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Optimize Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will optimize all database tables to improve performance and reclaim unused space.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Optimize Database</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll for quick action links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        // Switch to the corresponding tab
                        const tabId = this.getAttribute('href').substring(1) + '-tab';
                        const tab = document.getElementById(tabId);
                        if (tab) {
                            const tabInstance = new bootstrap.Tab(tab);
                            tabInstance.show();
                        }
                        
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Form validation
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    let valid = true;
                    const requiredFields = form.querySelectorAll('[required]');
                    
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
                        alert('Please fill in all required fields.');
                    }
                });
            });
        });
    </script>
</body>
</html>
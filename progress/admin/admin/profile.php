<?php
// Start session
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Mock session data for development - remove in production
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
}

// Simple authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Mock user data - replace with actual database calls
$userData = [
    'id' => 1,
    'first_name' => 'Samuel',
    'last_name' => 'Sitemba',
    'email' => 'samuel.sitemba@cdf.gov.zm',
    'phone' => '+260-966-123456',
    'department' => 'System Administration',
    'position' => 'Senior System Administrator',
    'constituency' => 'Lusaka Central',
    'employee_id' => 'CDF-ADM-2200340',
    'created_at' => '2023-01-15 08:00:00',
    'last_login' => date('Y-m-d H:i:s'),
    'status' => 'active',
    'profile_picture' => null
];

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update basic profile information
        $userData['first_name'] = $_POST['first_name'] ?? $userData['first_name'];
        $userData['last_name'] = $_POST['last_name'] ?? $userData['last_name'];
        $userData['email'] = $_POST['email'] ?? $userData['email'];
        $userData['phone'] = $_POST['phone'] ?? $userData['phone'];
        $userData['department'] = $_POST['department'] ?? $userData['department'];
        $userData['position'] = $_POST['position'] ?? $userData['position'];
        
        $success_message = "Profile updated successfully!";
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            $success_message = "Password changed successfully!";
        }
    }
    
    if ($action === 'update_preferences') {
        $userData['email_notifications'] = isset($_POST['email_notifications']) ? 1 : 0;
        $userData['sms_notifications'] = isset($_POST['sms_notifications']) ? 1 : 0;
        $userData['theme'] = $_POST['theme'] ?? 'light';
        
        $success_message = "Preferences updated successfully!";
    }
}

$pageTitle = "Admin Profile - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Admin profile management - CDF Management System">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --warning: #ffc107;
            --danger: #dc3545;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        /* Navigation */
        .navbar {
            background-color: var(--primary);
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

        .navbar-brand img {
            filter: brightness(0) invert(1);
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
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
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 700;
            box-shadow: var(--shadow);
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .profile-info h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .content-card .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .content-card .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Form Styles */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .btn-primary-custom {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: var(--transition);
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-outline-custom {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        /* Profile Stats */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .stat-item:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Activity Timeline */
        .activity-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-light);
        }

        .activity-item {
            position: relative;
            padding: 1rem 0;
            border-left: 3px solid var(--primary);
            padding-left: 1.5rem;
            margin-left: -2px;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid var(--white);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 3px solid var(--primary);
        }

        /* Alert Styles */
        .alert-custom {
            border-radius: 10px;
            border: none;
            box-shadow: var(--shadow);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
                padding: 1.5rem 0;
            }
            
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
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
                            <li><a class="dropdown-item" href="projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>System Settings
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?logout=true">
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
                    <h1>Admin Profile</h1>
                    <p class="lead">Manage your account settings and preferences</p>
                    <p class="mb-0">Employee ID: <strong><?php echo htmlspecialchars($userData['employee_id']); ?></strong> | 
                    Status: <span class="badge bg-success"><?php echo ucfirst($userData['status']); ?></span></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Profile Information -->
            <div class="col-lg-8">
                <!-- Profile Statistics -->
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number">156</div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">42</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">89%</div>
                        <div class="stat-label">System Uptime</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">2.5K</div>
                        <div class="stat-label">Reports Generated</div>
                    </div>
                </div>

                <!-- Profile Information Form -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($userData['phone']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="<?php echo htmlspecialchars($userData['department']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position" 
                                           value="<?php echo htmlspecialchars($userData['position']); ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                                <button type="button" class="btn btn-outline-custom" onclick="resetForm()">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Form -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Password must be at least 8 characters long and include uppercase, lowercase, and numbers.
                            </div>

                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Additional Information -->
            <div class="col-lg-4">
                <!-- Account Information -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Employee ID:</strong><br>
                            <?php echo htmlspecialchars($userData['employee_id']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Account Created:</strong><br>
                            <?php echo date('F j, Y', strtotime($userData['created_at'])); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Last Login:</strong><br>
                            <?php echo date('F j, Y g:i A', strtotime($userData['last_login'])); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Account Status:</strong><br>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="mb-0">
                            <strong>User Role:</strong><br>
                            <span class="badge bg-primary">System Administrator</span>
                        </div>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cog me-2"></i>Preferences</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_preferences">
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                                <label class="form-check-label" for="email_notifications">
                                    Email Notifications
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications">
                                <label class="form-check-label" for="sms_notifications">
                                    SMS Notifications
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label for="theme" class="form-label">Theme Preference</label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="light">Light Theme</option>
                                    <option value="dark">Dark Theme</option>
                                    <option value="auto">Auto (System)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-custom btn-sm">
                                <i class="fas fa-save me-1"></i>Save Preferences
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <div class="activity-item">
                                <h6 class="mb-1">Profile Updated</h6>
                                <p class="mb-1 small">You updated your contact information</p>
                                <small class="text-muted">2 hours ago</small>
                            </div>
                            <div class="activity-item">
                                <h6 class="mb-1">Password Changed</h6>
                                <p class="mb-1 small">You changed your account password</p>
                                <small class="text-muted">3 days ago</small>
                            </div>
                            <div class="activity-item">
                                <h6 class="mb-1">System Login</h6>
                                <p class="mb-1 small">You logged in from a new device</p>
                                <small class="text-muted">1 week ago</small>
                            </div>
                            <div class="activity-item">
                                <h6 class="mb-1">Report Generated</h6>
                                <p class="mb-1 small">You generated a system audit report</p>
                                <small class="text-muted">2 weeks ago</small>
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
        // Form reset function
        function resetForm() {
            document.querySelector('form').reset();
        }

        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength');
            
            if (!strengthIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'password-strength';
                indicator.className = 'mt-2 small';
                this.parentNode.appendChild(indicator);
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            const strengthText = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
            const strengthColors = ['danger', 'danger', 'warning', 'success', 'success'];
            
            document.getElementById('password-strength').innerHTML = 
                `Password Strength: <span class="text-${strengthColors[strength]}">${strengthText[strength]}</span>`;
        });

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
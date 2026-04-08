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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect(basename($_SERVER['PHP_SELF']));
        exit;
    }
    if (isset($_POST['update_profile'])) {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $nrc = $_POST['nrc'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $constituency = $_POST['constituency'] ?? '';
        $ward = $_POST['ward'] ?? '';
        $village = $_POST['village'] ?? '';
        $marital_status = $_POST['marital_status'] ?? '';
        
        // Basic validation
        if (!empty($first_name) && !empty($last_name) && !empty($email)) {
            $update_data = [
                'user_id' => $_SESSION['user_id'],
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'nrc' => $nrc,
                'dob' => $dob,
                'gender' => $gender,
                'constituency' => $constituency,
                'ward' => $ward,
                'village' => $village,
                'marital_status' => $marital_status
            ];
            
            if (updateUser($_SESSION['user_id'], $update_data)) {
                $_SESSION['success'] = "Profile updated successfully!";
                // Refresh user data
                $userData = getUserData();
            } else {
                $_SESSION['error'] = "Failed to update profile. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Please fill in all required fields.";
        }
    }
    
    // Handle profile picture upload
    if (isset($_POST['upload_photo']) && isset($_FILES['profile_picture'])) {
        $upload_result = handleProfilePictureUpload($_SESSION['user_id'], $_FILES['profile_picture']);
        if ($upload_result === true) {
            $_SESSION['success'] = "Profile picture updated successfully!";
            // Refresh user data
            $userData = getUserData();
        } else {
            $_SESSION['error'] = $upload_result;
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            if ($new_password === $confirm_password) {
                $password_result = changeUserPassword($_SESSION['user_id'], $current_password, $new_password);
                if ($password_result === true) {
                    $_SESSION['success'] = "Password changed successfully!";
                } else {
                    $_SESSION['error'] = $password_result;
                }
            } else {
                $_SESSION['error'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['error'] = "Please fill in all password fields.";
        }
    }
}

$pageTitle = "My Profile - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Profile management for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            /* Primary Colors */
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            
            /* Neutral Colors */
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            
            /* Status Colors */
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            
            /* Effects */
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
            --border-radius: 10px;
        }

        /* ===== BASE STYLES ===== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        /* ===== NAVIGATION ===== */
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
            visibility: visible !important;
        }

        .navbar-brand img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.75rem 1rem !important;
            border-radius: 4px;
            visibility: visible !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover,
        .nav-link:focus,
        .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .nav-link i {
            visibility: visible !important;
            color: var(--secondary) !important;
        }

        .dropdown-toggle::after {
            border-top: 0.3em solid rgba(255, 255, 255, 0.95) !important;
            border-right: 0.3em solid transparent !important;
            border-left: 0.3em solid transparent !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            padding: 0.5rem 0;
            background-color: var(--white) !important;
            min-width: 250px;
        }

        .dropdown-menu .dropdown-item {
            color: var(--gray-900) !important;
            padding: 0.75rem 1.25rem !important;
            font-weight: 500;
            transition: var(--transition);
            visibility: visible !important;
        }

        .dropdown-menu .dropdown-item:hover,
        .dropdown-menu .dropdown-item.active {
            background-color: var(--light) !important;
            color: var(--primary) !important;
        }

        .dropdown-menu .dropdown-header {
            color: var(--gray-900) !important;
            padding: 0.75rem 1.25rem !important;
            font-weight: 600;
            background-color: var(--white) !important;
        }

        .dropdown-menu .dropdown-divider {
            border-top: 1px solid var(--gray-200) !important;
            margin: 0.5rem 0 !important;
        }

        .dropdown-menu .text-muted {
            color: var(--gray-600) !important;
        }

        .dropdown-menu .text-danger {
            color: var(--danger) !important;
        }

        .dropdown-menu .text-danger:hover {
            color: var(--white) !important;
            background-color: var(--danger) !important;
        }

        /* ===== HEADER ===== */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 76px;
        }

        /* ===== CONTENT CARDS ===== */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
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
        }

        /* ===== PROFILE PICTURE STYLES ===== */
        .profile-picture-section {
            text-align: center;
            padding: 2rem;
            background: var(--light);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--white);
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }

        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 3rem;
            font-weight: 700;
            border: 5px solid var(--white);
            box-shadow: var(--shadow);
            margin: 0 auto 1rem auto;
        }

        .profile-upload-btn {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .profile-upload-btn input[type="file"] {
            position: absolute;
            left: -9999px;
            top: -9999px;
            opacity: 0;
            width: 1px;
            height: 1px;
            cursor: pointer;
        }

        /* ===== FORM STYLES ===== */
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .form-section {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .form-section h6 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        /* ===== BUTTONS ===== */
        .btn-primary-custom {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
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
            padding: 0.5rem 1rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        /* ===== BADGES ===== */
        .badge-verified {
            background-color: var(--success);
            color: white;
        }

        .badge-pending {
            background-color: var(--warning);
            color: var(--dark);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--white);
            box-shadow: 0 2px 6px rgba(220, 53, 69, 0.4);
            z-index: 10;
            visibility: visible !important;
        }

        /* ===== ALERTS ===== */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 4px solid transparent;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border-left-color: var(--success);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-left-color: var(--danger);
        }

        /* ===== FOOTER ===== */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--primary);
        }

        /* ===== PROFILE INFO STYLES ===== */
        .profile-info-item {
            display: flex;
            justify-content: between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .profile-info-item:last-child {
            border-bottom: none;
        }

        .profile-info-label {
            font-weight: 600;
            color: var(--primary);
            min-width: 150px;
        }

        .profile-info-value {
            color: var(--dark);
        }

        /* ===== PASSWORD STRENGTH ===== */
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 0.5rem;
            transition: var(--transition);
        }

        .password-strength.weak {
            background-color: var(--danger);
            width: 25%;
        }

        .password-strength.fair {
            background-color: var(--warning);
            width: 50%;
        }

        .password-strength.good {
            background-color: var(--info);
            width: 75%;
        }

        .password-strength.strong {
            background-color: var(--success);
            width: 100%;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 1.5rem 0;
            }
            
            .profile-picture-section {
                padding: 1.5rem;
            }
            
            .profile-picture,
            .profile-picture-placeholder {
                width: 120px;
                height: 120px;
            }
            
            .form-section {
                padding: 1rem;
            }
            
            .navbar-brand img {
                height: 35px;
            }
        }

        @media (max-width: 576px) {
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .profile-info-item {
                flex-direction: column;
            }
            
            .profile-info-label {
                min-width: auto;
                margin-bottom: 0.25rem;
            }
            
            .page-header {
                margin-top: 70px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .text-primary-custom {
            color: var(--primary) !important;
        }

        .bg-light-custom {
            background-color: var(--light) !important;
        }

        .border-radius-custom {
            border-radius: var(--border-radius) !important;
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
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
                            <li><a class="dropdown-item" href="../progress/updates.php">
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
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($userData['profile_picture'])): ?>
                                            <img src="../uploads/profiles/<?php echo htmlspecialchars($userData['profile_picture']); ?>?t=<?php echo time(); ?>" 
                                                 alt="Profile" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
                                                <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                                            <small class="text-muted">CDF Beneficiary</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item active" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="system.php">
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
                    <h1><i class="fas fa-user me-2"></i>My Profile</h1>
                    <p class="lead mb-0">Manage your personal information and profile settings</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <a href="system.php" class="btn btn-outline-custom">
                            <i class="fas fa-cog me-2"></i>Account Settings
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
            <!-- Profile Picture & Basic Info -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="profile-picture-section">
                        <?php if (!empty($userData['profile_picture'])): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($userData['profile_picture']); ?>?t=<?php echo time(); ?>" 
                                 alt="Profile Picture" class="profile-picture">
                        <?php else: ?>
                            <div class="profile-picture-placeholder">
                                <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h5><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h5>
                        <p class="text-muted mb-3">CDF Beneficiary</p>
                        
                        <form method="POST" action="" enctype="multipart/form-data" id="profilePhotoForm">
                            <div class="profile-upload-btn">
                                <button type="button" class="btn btn-outline-custom" onclick="document.getElementById('profilePhotoInput').click()">
                                    <i class="fas fa-camera me-2"></i>Change Photo
                                </button>
                                    <?= csrfField() ?>
                                <input type="file" id="profilePhotoInput" name="profile_picture" accept="image/*" onchange="document.getElementById('profilePhotoForm').submit()">
                                <input type="hidden" name="upload_photo" value="1">
                            </div>
                        </form>
                        <small class="text-muted d-block mt-2">JPG, PNG or GIF, Max 2MB</small>
                    </div>

                    <div class="card-body">
                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Account Information</h6>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Member Since:</span>
                            <span class="profile-info-value"><?php echo date('M j, Y', strtotime($userData['created_at'])); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Last Login:</span>
                            <span class="profile-info-value"><?php echo date('M j, Y g:i A', strtotime($userData['updated_at'])); ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">Status:</span>
                            <span class="profile-info-value">
                                <span class="badge badge-verified">Active</span>
                            </span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-label">User ID:</span>
                            <span class="profile-info-value">#<?php echo str_pad($userData['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Update Form -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-edit me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                            <?= csrfField() ?>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($userData['phone']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nrc" class="form-label">NRC Number</label>
                                        <input type="text" class="form-control" id="nrc" name="nrc" 
                                               value="<?php echo htmlspecialchars($userData['nrc']); ?>" 
                                               placeholder="e.g., 123456/78/9">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dob" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="dob" name="dob" 
                                               value="<?php echo htmlspecialchars($userData['dob']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo ($userData['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($userData['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo ($userData['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="marital_status" class="form-label">Marital Status</label>
                                        <select class="form-select" id="marital_status" name="marital_status">
                                            <option value="">Select Status</option>
                                            <option value="single" <?php echo ($userData['marital_status'] == 'single') ? 'selected' : ''; ?>>Single</option>
                                            <option value="married" <?php echo ($userData['marital_status'] == 'married') ? 'selected' : ''; ?>>Married</option>
                                            <option value="divorced" <?php echo ($userData['marital_status'] == 'divorced') ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="widowed" <?php echo ($userData['marital_status'] == 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="constituency" class="form-label">Constituency</label>
                                            <select class="form-select" id="constituency" name="constituency">
                                                <option value="">Select Constituency</option>
                                                <?php 
                                                $constituencies = getConstituencies();
                                                foreach ($constituencies as $constituency): 
                                                ?>
                                                    <option value="<?php echo $constituency; ?>" 
                                                        <?php echo ($userData['constituency'] == $constituency) ? 'selected' : ''; ?>>
                                                        <?php echo $constituency; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ward" class="form-label">Ward</label>
                                            <input type="text" class="form-control" id="ward" name="ward" 
                                                   value="<?php echo htmlspecialchars($userData['ward']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="village" class="form-label">Village/Area</label>
                                    <input type="text" class="form-control" id="village" name="village" 
                                           value="<?php echo htmlspecialchars($userData['village']); ?>">
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                            <?= csrfField() ?>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" onkeyup="checkPasswordStrength(this.value)">
                                        <div id="passwordStrength" class="password-strength"></div>
                                        <small class="form-text text-muted">Password must be at least 8 characters with uppercase, lowercase, number, and special character.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="change_password" class="btn btn-outline-custom">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
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

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            // Remove all classes
            strengthBar.className = 'password-strength';
            
            // Add appropriate class
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength === 3) {
                strengthBar.classList.add('fair');
            } else if (strength === 4) {
                strengthBar.classList.add('good');
            } else if (strength >= 5) {
                strengthBar.classList.add('strong');
            }
        }

        // Confirm password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword && confirmPassword !== '') {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
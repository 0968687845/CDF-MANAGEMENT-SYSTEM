<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Get user ID from query parameter
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    redirect('users.php');
    exit();
}

// Get user data
$user = getUserById($user_id);
if (!$user) {
    $_SESSION['error'] = "User not found";
    redirect('users.php');
    exit();
}

$userData = getUserData();
$pageTitle = "Edit User - CDF Management System";

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'role' => $_POST['role'],
        'status' => $_POST['status'],
        'constituency' => $_POST['constituency'],
        'ward' => $_POST['ward'],
        'village' => $_POST['village']
    ];
    
    $result = updateUser($user_id, $update_data);
    if ($result === true) {
        $message = "User updated successfully!";
        // Refresh user data
        $user = getUserById($user_id);
    } else {
        $error = $result;
    }
}
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
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .user-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
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
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #0a58ca 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.3);
            color: white;
        }
        
        .info-badge {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
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
                                        <div class="user-avatar-large" style="width: 40px; height: 40px; font-size: 1rem;">
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
                    <h1><i class="fas fa-user-edit me-3"></i>Edit User</h1>
                    <p class="lead mb-0">Update user information and permissions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="users.php" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users
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

        <div class="row">
            <!-- User Information -->
            <div class="col-lg-4 mb-4">
                <div class="form-card">
                    <div class="card-body text-center">
                        <div class="user-avatar-large">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                        
                        <div class="info-badge mb-3">
                            <i class="fas fa-user-tag me-2"></i>
                            <span class="text-uppercase fw-bold"><?php echo htmlspecialchars($user['role']); ?></span>
                        </div>
                        
                        <div class="row text-start">
                            <div class="col-12 mb-2">
                                <small class="text-muted">User ID</small>
                                <div class="fw-bold">#<?php echo htmlspecialchars($user['id']); ?></div>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted">Status</small>
                                <div>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted">Member Since</small>
                                <div class="fw-bold"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Last Login</small>
                                <div class="fw-bold">
                                    <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="stats-card">
                    <div class="stats-number">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>Last Updated</div>
                    <small><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></small>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="card-body">
                        <form method="POST" id="editUserForm">
                            <h4 class="section-title">Basic Information</h4>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                        <div class="form-text">We'll never share the email with anyone else.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title">Account Settings</h4>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">User Role *</label>
                                        <select class="form-select" name="role" required>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                            <option value="officer" <?php echo $user['role'] === 'officer' ? 'selected' : ''; ?>>M&E Officer</option>
                                            <option value="beneficiary" <?php echo $user['role'] === 'beneficiary' ? 'selected' : ''; ?>>Beneficiary</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Account Status *</label>
                                        <select class="form-select" name="status" required>
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title">Location Information</h4>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Constituency</label>
                                        <select class="form-select" name="constituency">
                                            <option value="">Select Constituency</option>
                                            <?php foreach (getConstituencies() as $constituency): ?>
                                            <option value="<?php echo $constituency; ?>" 
                                                <?php echo ($user['constituency'] ?? '') === $constituency ? 'selected' : ''; ?>>
                                                <?php echo $constituency; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Ward</label>
                                        <input type="text" class="form-control" name="ward" 
                                               value="<?php echo htmlspecialchars($user['ward'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Village</label>
                                        <input type="text" class="form-control" name="village" 
                                               value="<?php echo htmlspecialchars($user['village'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-custom-primary">
                                    <i class="fas fa-save me-2"></i>Update User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.getElementById('editUserForm');
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Check required fields
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
    </script>
</body>
</html>
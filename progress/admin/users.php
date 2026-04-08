<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Handle form actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle user actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create':
            $result = createUser($_POST);
            if ($result === true) {
                $message = "User created successfully!";
            } else {
                $error = $result;
            }
            break;
            
        case 'update':
            if (isset($_POST['user_id'])) {
                $result = updateUser($_POST['user_id'], $_POST);
                if ($result === true) {
                    $message = "User updated successfully!";
                } else {
                    $error = $result;
                }
            }
            break;
            
        case 'delete':
            if (isset($_POST['user_id'])) {
                $result = deleteUser($_POST['user_id']);
                if ($result === true) {
                    $message = "User deleted successfully!";
                } else {
                    $error = $result;
                }
            }
            break;
            
        case 'bulk_action':
            if (isset($_POST['selected_users']) && isset($_POST['bulk_action_type'])) {
                $result = handleBulkAction($_POST['selected_users'], $_POST['bulk_action_type']);
                if ($result === true) {
                    $message = "Bulk action completed successfully!";
                } else {
                    $error = $result;
                }
            }
            break;
    }
}

// Get all users
$users = getAllUsers();
$userData = getUserData();

$pageTitle = "User Management - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --secondary: #e9b949;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--primary);
            border-radius: 10px 10px 0 0 !important;
            padding: 1.25rem;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--primary);
            background-color: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, #d4a337 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1rem;
        }
        
        .badge-role {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .badge-admin { background-color: var(--primary); }
        .badge-officer { background-color: var(--info); }
        .badge-beneficiary { background-color: var(--success); }
        
        .badge-status {
            font-size: 0.7rem;
            padding: 0.3em 0.6em;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            margin: 0 0.1rem;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 2.5rem;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stats-card.primary { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); }
        .stats-card.success { background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%); }
        .stats-card.warning { background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%); }
        .stats-card.info { background: linear-gradient(135deg, var(--info) 0%, #138496 100%); }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .user-form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
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
                            <li><a class="dropdown-item active" href="users.php">
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
                    <!-- Notifications -->
                    <?php
                    $notifications = getNotifications($_SESSION['user_id']);
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo count($notifications); ?>
                                </span>
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
                                <li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="notifications.php">No new notifications</a></li>
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
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #e9b949 0%, #d4a337 100%); display: flex; align-items: center; justify-content: center; color: #1a4e8a; font-weight: 700;">
                                            <?php 
                                            $userData = getUserData();
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
                    <h1><i class="fas fa-users me-3"></i>User Management</h1>
                    <p class="lead mb-0">Manage system users, permissions, and access levels</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </button>
                    <a href="../admin_dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card primary">
                    <div class="stats-number"><?php echo count($users); ?></div>
                    <div class="stats-title">Total Users</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card success">
                    <div class="stats-number"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'beneficiary')); ?></div>
                    <div class="stats-title">Beneficiaries</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card info">
                    <div class="stats-number"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'officer')); ?></div>
                    <div class="stats-title">M&E Officers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card warning">
                    <div class="stats-number"><?php echo count(array_filter($users, fn($u) => $u['status'] === 'inactive')); ?></div>
                    <div class="stats-title">Inactive Users</div>
                </div>
            </div>
        </div>

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

        <!-- Filter and Search Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters & Search</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="roleFilter">
                            <option value="">All Roles</option>
                            <option value="admin">Administrator</option>
                            <option value="officer">M&E Officer</option>
                            <option value="beneficiary">Beneficiary</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" id="resetFilters">
                            <i class="fas fa-redo me-2"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>System Users</h5>
                <div class="d-flex">
                    <select class="form-select form-select-sm me-2" id="bulkAction" style="width: auto;">
                        <option value="">Bulk Actions</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button class="btn btn-sm btn-primary" id="applyBulkAction">
                        <i class="fas fa-play me-1"></i>Apply
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>">
                                <td>
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <small><i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></small>
                                        <br>
                                        <small><i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-role badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($user['constituency'] ?? 'N/A'); ?>
                                        <?php if (!empty($user['ward'])): ?>
                                        <br><span class="text-muted">Ward: <?php echo htmlspecialchars($user['ward']); ?></span>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-status <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-primary view-user" data-user-id="<?php echo $user['id']; ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning edit-user" data-user-id="<?php echo $user['id']; ?>" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['status'] === 'active'): ?>
                                        <button class="btn btn-sm btn-outline-secondary toggle-status" data-user-id="<?php echo $user['id']; ?>" data-action="deactivate" title="Deactivate">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-success toggle-status" data-user-id="<?php echo $user['id']; ?>" data-action="activate" title="Activate">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger delete-user" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Users Found</h5>
                                    <p class="text-muted">Get started by creating the first user account.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                        <i class="fas fa-user-plus me-2"></i>Create User
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="User pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="createUserForm">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role *</label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="admin">Administrator</option>
                                        <option value="officer">M&E Officer</option>
                                        <option value="beneficiary">Beneficiary</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">NRC Number</label>
                                    <input type="text" class="form-control" name="nrc" placeholder="123456/78/9">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dob">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Marital Status</label>
                                    <select class="form-select" name="marital_status">
                                        <option value="">Select Status</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="divorced">Divorced</option>
                                        <option value="widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="form-section-title">Location Information</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Constituency</label>
                                    <select class="form-select" name="constituency">
                                        <option value="">Select Constituency</option>
                                        <?php foreach (getConstituencies() as $constituency): ?>
                                        <option value="<?php echo $constituency; ?>"><?php echo $constituency; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Ward</label>
                                    <input type="text" class="form-control" name="ward">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Village</label>
                                    <input type="text" class="form-control" name="village">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const statusFilter = document.getElementById('statusFilter');
            const resetFilters = document.getElementById('resetFilters');
            
            function filterUsers() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleValue = roleFilter.value;
                const statusValue = statusFilter.value;
                
                document.querySelectorAll('#usersTable tbody tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const role = row.getAttribute('data-role');
                    const status = row.getAttribute('data-status');
                    
                    const matchesSearch = text.includes(searchTerm);
                    const matchesRole = !roleValue || role === roleValue;
                    const matchesStatus = !statusValue || status === statusValue;
                    
                    row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
                });
            }
            
            searchInput.addEventListener('input', filterUsers);
            roleFilter.addEventListener('change', filterUsers);
            statusFilter.addEventListener('change', filterUsers);
            
            resetFilters.addEventListener('click', function() {
                searchInput.value = '';
                roleFilter.value = '';
                statusFilter.value = '';
                filterUsers();
            });
            
            // Bulk actions
            const selectAll = document.getElementById('selectAll');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');
            const bulkAction = document.getElementById('bulkAction');
            const applyBulkAction = document.getElementById('applyBulkAction');
            
            selectAll.addEventListener('change', function() {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
            });
            
            applyBulkAction.addEventListener('click', function() {
                const selectedUsers = Array.from(userCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                
                if (selectedUsers.length === 0) {
                    alert('Please select at least one user.');
                    return;
                }
                
                if (!bulkAction.value) {
                    alert('Please select a bulk action.');
                    return;
                }
                
                if (confirm(`Are you sure you want to ${bulkAction.value} ${selectedUsers.length} user(s)?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const actionInput = document.createElement('input');
                    actionInput.name = 'action';
                    actionInput.value = 'bulk_action';
                    form.appendChild(actionInput);
                    
                    const bulkActionInput = document.createElement('input');
                    bulkActionInput.name = 'bulk_action_type';
                    bulkActionInput.value = bulkAction.value;
                    form.appendChild(bulkActionInput);
                    
                    selectedUsers.forEach(userId => {
                        const input = document.createElement('input');
                        input.name = 'selected_users[]';
                        input.value = userId;
                        form.appendChild(input);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
            
            // User actions
            document.querySelectorAll('.view-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    // Implement view user functionality
                    alert('View user: ' + userId);
                });
            });
            
            document.querySelectorAll('.edit-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    // Implement edit user functionality
                    window.location.href = `edit_user.php?id=${userId}`;
                });
            });
            
            document.querySelectorAll('.toggle-status').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const action = this.getAttribute('data-action');
                    if (confirm(`Are you sure you want to ${action} this user?`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';
                        
                        const actionInput = document.createElement('input');
                        actionInput.name = 'action';
                        actionInput.value = 'update';
                        form.appendChild(actionInput);
                        
                        const userIdInput = document.createElement('input');
                        userIdInput.name = 'user_id';
                        userIdInput.value = userId;
                        form.appendChild(userIdInput);
                        
                        const statusInput = document.createElement('input');
                        statusInput.name = 'status';
                        statusInput.value = action === 'activate' ? 'active' : 'inactive';
                        form.appendChild(statusInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    
                    if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';
                        
                        const actionInput = document.createElement('input');
                        actionInput.name = 'action';
                        actionInput.value = 'delete';
                        form.appendChild(actionInput);
                        
                        const userIdInput = document.createElement('input');
                        userIdInput.name = 'user_id';
                        userIdInput.value = userId;
                        form.appendChild(userIdInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            // Password confirmation validation
            const createUserForm = document.getElementById('createUserForm');
            if (createUserForm) {
                createUserForm.addEventListener('submit', function(e) {
                    const password = this.querySelector('input[name="password"]').value;
                    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                    }
                });
            }
        });
    </script>
</body>
</html>

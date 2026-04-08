<?php
require_once '../functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$projects = getBeneficiaryProjects($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id']);

// Get selected project
$selected_project_id = $_GET['project_id'] ?? ($_POST['project_id'] ?? null);
$selected_project = null;
if ($selected_project_id) {
    $selected_project = getProjectById($selected_project_id);
}

// Handle expense submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $expense_data = [
        'project_id' => $selected_project_id,
        'amount' => floatval($_POST['amount']),
        'category' => trim($_POST['category']),
        'description' => trim($_POST['description']),
        'expense_date' => $_POST['expense_date'],
        'receipt_number' => trim($_POST['receipt_number']),
        'vendor' => trim($_POST['vendor']),
        'vendor_phone' => trim($_POST['vendor_phone']), // NEW FIELD
        'payment_method' => $_POST['payment_method'],
        'notes' => trim($_POST['notes'] ?? '')
    ];

    // Validate expense
    $errors = [];
    if ($expense_data['amount'] <= 0) {
        $errors['amount'] = 'Valid amount is required';
    }
    if (empty($expense_data['category'])) {
        $errors['category'] = 'Category is required';
    }
    if (empty($expense_data['description'])) {
        $errors['description'] = 'Description is required';
    }
    if (empty($expense_data['expense_date'])) {
        $errors['expense_date'] = 'Expense date is required';
    }

    // Check if receipt is required
    $receipt_required_categories = ['Materials', 'Equipment', 'Administration', 'Utilities', 'Other'];
    $receipt_optional_categories = ['Labor', 'Transport'];
    
    $is_receipt_required = in_array($expense_data['category'], $receipt_required_categories);
    
    // Handle receipt upload
    $receipt_path = null;
    if (!empty($_FILES['receipt_file']['name'])) {
        $receipt_path = handleReceiptUpload($_FILES['receipt_file'], $selected_project_id);
        if (!$receipt_path) {
            $errors['receipt_file'] = 'Failed to upload receipt. Please try again.';
        }
    } elseif ($is_receipt_required) {
        $errors['receipt_file'] = 'Receipt is required for ' . $expense_data['category'] . ' expenses';
    }

    // Handle resource photos upload
    $resource_photos = [];
    if (!empty($_FILES['resource_photos']['name'][0])) {
        $resource_photos = handleProgressPhotoUpload($_FILES['resource_photos'], $selected_project_id);
    }

    if (empty($errors)) {
        if (addProjectExpense($expense_data, $_SESSION['user_id'], $receipt_path, $resource_photos)) {
            $_SESSION['success_message'] = 'Expense recorded successfully!' . 
                ($receipt_path ? ' Receipt uploaded.' : '') . 
                (count($resource_photos) > 0 ? ' ' . count($resource_photos) . ' resource photos uploaded.' : '');
            redirect("expenses.php?project_id=$selected_project_id");
        } else {
            $errors['general'] = 'Failed to record expense. Please try again.';
        }
    }
}

// Handle expense deletion
if (isset($_GET['delete_expense'])) {
    $expense_id = $_GET['delete_expense'];
    if (deleteProjectExpense($expense_id, $_SESSION['user_id'])) {
        $_SESSION['success_message'] = 'Expense deleted successfully!';
        redirect("expenses.php?project_id=$selected_project_id");
    } else {
        $errors['general'] = 'Failed to delete expense.';
    }
}

// Get expenses for selected project
$expenses = [];
$total_expenses = 0;
$remaining_budget = 0;
$budget_utilization = 0;
$expense_categories = [];

if ($selected_project) {
    $expenses = getProjectExpenses($selected_project_id);
    $total_expenses = array_sum(array_column($expenses, 'amount'));
    $remaining_budget = $selected_project['budget'] - $total_expenses;
    $budget_utilization = $selected_project['budget'] > 0 ? ($total_expenses / $selected_project['budget']) * 100 : 0;
    
    // Get expense categories for chart
    $expense_categories = getExpenseCategoriesSummary($selected_project_id);
}

$pageTitle = "Project Expenses - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Project expenses management - CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --gray-lighter: #f8f9fa;
            --success: #28a745;
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --border-radius: 12px;
            --border-radius-sm: 8px;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
            color: var(--dark);
            line-height: 1.7;
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            transform: translateY(-1px);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.6rem 1rem !important;
            border-radius: var(--border-radius-sm);
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .content-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }

        .card-header {
            background: var(--light-gradient);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h5 i {
            color: var(--secondary);
            font-size: 1.1em;
        }

        /* Stats Cards */
        .stats-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition);
        }

        .stats-card:hover::before {
            left: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-title {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Progress Bar */
        .budget-progress {
            height: 16px;
            border-radius: 8px;
            background: var(--gray-light);
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            border-radius: 8px;
            transition: width 0.6s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Expense Table */
        .expense-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .expense-table th {
            background: var(--primary-gradient);
            color: var(--white);
            font-weight: 600;
            padding: 1.25rem;
            text-align: left;
            border: none;
            position: sticky;
            top: 0;
        }

        .expense-table td {
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-light);
            background: var(--white);
            transition: var(--transition);
        }

        .expense-table tr:hover td {
            background: var(--gray-lighter);
            transform: scale(1.01);
        }

        .expense-category {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: var(--primary-light);
            color: var(--white);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Buttons */
        .btn-primary-custom {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            background: var(--secondary-gradient);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-outline-custom:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        /* Form Styles */
        .form-control, .form-select {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            padding: 0.875rem 1rem;
            transition: var(--transition);
            font-size: 0.95rem;
            background: var(--white);
            box-shadow: var(--shadow-light);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.3rem rgba(26, 78, 138, 0.15);
            transform: translateY(-1px);
        }

        /* File Upload Styles */
        .file-upload-area {
            border: 2px dashed var(--gray-light);
            border-radius: var(--border-radius-sm);
            padding: 0.3rem 0.5rem; /* Minimized padding */
            text-align: center;
            transition: var(--transition);
            background: var(--light);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            min-height: 40px; /* Minimized height */
            max-width: 200px; /* Minimized width */
            margin: 0 auto;
        }
        .file-upload-area .file-upload-icon {
            font-size: 1rem; /* Smaller icon */
            margin-bottom: 0.1rem;
        }
        .file-upload-area h6 {
            font-size: 0.85rem;
            margin-bottom: 0.1rem;
        }
        .file-upload-area p {
            font-size: 0.7rem;
            margin-bottom: 0.1rem;
        }
        .receipt-upload-area {
            border-color: var(--success);
            background: rgba(40, 167, 69, 0.03);
            min-height: 40px; /* Minimized height */
            max-width: 200px; /* Minimized width */
            margin: 0 auto;
        }
        .receipt-upload-area .file-upload-icon {
            font-size: 1rem; /* Smaller icon */
        }
        .file-preview-container {
            margin-top: 0.3rem;
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(26, 78, 138, 0.03);
            transform: translateY(-2px);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(26, 78, 138, 0.08);
            transform: scale(1.02);
        }

        .receipt-upload-area {
            border-color: var(--success);
            background: rgba(40, 167, 69, 0.03);
        }

        .receipt-upload-area:hover {
            border-color: var(--success-dark);
            background: rgba(40, 167, 69, 0.08);
        }

        .file-upload-icon {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .file-upload-area:hover .file-upload-icon {
            color: var(--primary);
            transform: scale(1.1);
        }

        .receipt-upload-area .file-upload-icon {
            color: var(--success);
        }

        .receipt-upload-area:hover .file-upload-icon {
            color: var(--success-dark);
        }

        .file-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .file-preview-item {
            position: relative;
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            background: var(--white);
        }

        .file-preview-item:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-hover);
        }

        .file-preview-image {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block;
        }

        .file-preview-remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            opacity: 0;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .file-preview-item:hover .file-preview-remove {
            opacity: 1;
        }

        .file-preview-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 0.75rem;
            font-size: 0.7rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
        }

        /* Alert Styles */
        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow);
            padding: 1.25rem 1.5rem;
            border-left: 4px solid;
            backdrop-filter: blur(10px);
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
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 3px solid var(--primary);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .expense-table {
                font-size: 0.875rem;
            }
            
            .expense-table th,
            .expense-table td {
                padding: 1rem 0.75rem;
            }
            
            .chart-container {
                height: 300px;
            }
            
            .file-upload-area {
                padding: 1.5rem 1rem;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Budget Warning */
        .budget-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid var(--warning);
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            margin: 1rem 0;
        }

        .budget-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 4px solid var(--danger);
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            margin: 1rem 0;
        }

        /* Receipt Requirement Notice */
        .receipt-notice {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border-left: 4px solid var(--info);
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        /* Vendor Phone Styling */
        .vendor-phone {
            font-family: 'Courier New', monospace;
            background: var(--gray-lighter);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../beneficiary_dashboard.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="45" height="45" style="border-radius: 4px;">
                CDF Beneficiary Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../beneficiary_dashboard.php">
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
                            <li><a class="dropdown-item active" href="expenses.php">
                                <i class="fas fa-receipt me-2"></i>My Expenses
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="../support/help.php">
                                <i class="fas fa-question-circle me-2"></i>Help
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

    <!-- Main Content -->
    <div class="container mt-5 mb-5" style="margin-top: 120px !important;">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-2" style="color: var(--primary); font-weight: 800;">Project Expenses</h1>
                <p class="text-muted mb-0">Track and manage your project expenses and budget utilization</p>
            </div>
            <a href="../projects/index.php" class="btn btn-outline-custom">
                <i class="fas fa-arrow-left me-2"></i>Back to Projects
            </a>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-custom" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-custom" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <!-- Project Selection -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-project-diagram me-2"></i>Select Project</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <select class="form-select" name="project_id" onchange="this.form.submit()">
                            <option value="">Select a project to view expenses...</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" 
                                    <?php echo $selected_project_id == $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['title']); ?> 
                                    (Budget: ZMW <?php echo number_format($project['budget'], 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search me-2"></i>View Expenses
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_project): ?>
            <!-- Budget Overview -->
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie me-2"></i>Budget Overview - <?php echo htmlspecialchars($selected_project['title']); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Budget Warnings -->
                    <?php if ($budget_utilization >= 90): ?>
                        <div class="budget-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Budget Alert:</strong> You have used <?php echo number_format($budget_utilization, 1); ?>% of your budget. Only ZMW <?php echo number_format($remaining_budget, 2); ?> remaining.
                        </div>
                    <?php elseif ($budget_utilization >= 75): ?>
                        <div class="budget-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Budget Warning:</strong> You have used <?php echo number_format($budget_utilization, 1); ?>% of your budget. Please monitor your spending.
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number">ZMW <?php echo number_format($selected_project['budget'], 0); ?></div>
                                <div class="stats-title">Total Budget</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-success">ZMW <?php echo number_format($total_expenses, 0); ?></div>
                                <div class="stats-title">Used Funds</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-info">ZMW <?php echo number_format($remaining_budget, 0); ?></div>
                                <div class="stats-title">Remaining Funds</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?php echo number_format($budget_utilization, 1); ?>%</div>
                                <div class="stats-title">Budget Utilization</div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Progress -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Budget Utilization Progress</span>
                            <span class="fw-bold"><?php echo number_format($budget_utilization, 1); ?>%</span>
                        </div>
                        <div class="budget-progress">
                            <div class="progress-bar bg-<?php echo $budget_utilization > 90 ? 'danger' : ($budget_utilization > 75 ? 'warning' : 'success'); ?>" 
                                 style="width: <?php echo min($budget_utilization, 100); ?>%">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">ZMW 0</small>
                            <small class="text-muted">ZMW <?php echo number_format($selected_project['budget'], 0); ?></small>
                        </div>
                    </div>

                    <!-- Expense Chart -->
                    <?php if (count($expense_categories) > 0): ?>
                    <div class="chart-container">
                        <canvas id="expenseChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No expense data available for chart visualization.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Expense Form -->
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="fas fa-plus-circle me-2"></i>Record New Expense</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?php echo $selected_project_id; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount (ZMW) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                       name="amount" step="0.01" min="0.01" required 
                                       placeholder="Enter expense amount"
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['amount']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['category']) ? 'is-invalid' : ''; ?>" name="category" required onchange="toggleReceiptRequirement(this.value)">
                                    <option value="">Select Category</option>
                                    <option value="Materials" <?php echo ($_POST['category'] ?? '') === 'Materials' ? 'selected' : ''; ?>>Construction Materials</option>
                                    <option value="Labor" <?php echo ($_POST['category'] ?? '') === 'Labor' ? 'selected' : ''; ?>>Labor Costs</option>
                                    <option value="Equipment" <?php echo ($_POST['category'] ?? '') === 'Equipment' ? 'selected' : ''; ?>>Equipment Rental</option>
                                    <option value="Transport" <?php echo ($_POST['category'] ?? '') === 'Transport' ? 'selected' : ''; ?>>Transport & Logistics</option>
                                    <option value="Administration" <?php echo ($_POST['category'] ?? '') === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                                    <option value="Utilities" <?php echo ($_POST['category'] ?? '') === 'Utilities' ? 'selected' : ''; ?>>Utilities</option>
                                    <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <?php if (isset($errors['category'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['category']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                       name="description" required placeholder="Brief description of expense"
                                       value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
                                <?php if (isset($errors['description'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['expense_date']) ? 'is-invalid' : ''; ?>" 
                                       name="expense_date" required value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>">
                                <?php if (isset($errors['expense_date'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['expense_date']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Receipt Number</label>
                                <input type="text" class="form-control" name="receipt_number" 
                                       placeholder="Optional receipt number"
                                       value="<?php echo htmlspecialchars($_POST['receipt_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vendor/Supplier</label>
                                <input type="text" class="form-control" name="vendor" 
                                       placeholder="Vendor name"
                                       value="<?php echo htmlspecialchars($_POST['vendor'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vendor/Supplier Phone</label>
                                <input type="text" class="form-control" name="vendor_phone" 
                                       placeholder="Vendor phone number"
                                       value="<?php echo htmlspecialchars($_POST['vendor_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method">
                                    <option value="Cash" <?php echo ($_POST['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Bank Transfer" <?php echo ($_POST['payment_method'] ?? '') === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Mobile Money" <?php echo ($_POST['payment_method'] ?? '') === 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="Cheque" <?php echo ($_POST['payment_method'] ?? '') === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                                </select>
                            </div>
                        </div>

                        <!-- Receipt Upload Section -->
                        <div class="mb-4">
                            <label class="form-label">Receipt Proof <span id="receiptRequired" class="text-danger d-none">*</span></label>
                            <div class="receipt-notice d-none" id="receiptNotice">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Receipt Required:</strong> A receipt is mandatory for this expense category as proof of purchase.
                            </div>
                            <div class="file-upload-area receipt-upload-area" id="receiptUploadArea">
                                <div class="file-upload-icon">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <h6>Upload Receipt Proof</h6>
                                <p class="text-muted small mb-3">or click to browse</p>
                                <p class="small text-muted">Supported formats: JPG, PNG, PDF<br>Max file size: 5MB</p>
                                <input type="file" id="receipt_file" name="receipt_file" 
                                       accept="image/*,.pdf" class="d-none" onchange="handleReceiptSelect(this.files)">
                            </div>
                            <div class="file-preview-container" id="receiptPreviewContainer"></div>
                            <?php if (isset($errors['receipt_file'])): ?>
                                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['receipt_file']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Resource Photos Upload Section -->
                        <div class="mb-4">
                            <label class="form-label">Resource Photos (Optional)</label>
                            <div class="file-upload-area" id="resourceUploadArea">
                                <div class="file-upload-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <h6>Upload Resource Photos</h6>
                                <p class="text-muted small mb-3">or click to browse</p>
                                <p class="small text-muted">Show purchased materials or resources<br>Supported formats: JPG, PNG<br>Max file size: 10MB per photo</p>
                                <input type="file" id="resource_photos" name="resource_photos[]" multiple 
                                       accept="image/*" class="d-none" onchange="handleResourceSelect(this.files)">
                            </div>
                            <div class="file-preview-container" id="resourcePreviewContainer"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" 
                                      placeholder="Additional notes about this expense"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="add_expense" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Record Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Expenses List -->
            <div class="content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list me-2"></i>Expense History</h5>
                    <span class="badge bg-primary"><?php echo count($expenses); ?> expenses</span>
                </div>
                <div class="card-body">
                    <?php if (count($expenses) > 0): ?>
                        <div class="table-responsive">
                            <table class="expense-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount (ZMW)</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Vendor</th>
                                        <th>Vendor Phone</th>
                                        <th>Receipt</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                                        <td><?php echo number_format($expense['amount'], 2); ?></td>
                                        <td><span class="expense-category"><?php echo htmlspecialchars($expense['category']); ?></span></td>
                                        <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                        <td><?php echo isset($expense['vendor']) ? htmlspecialchars($expense['vendor']) : ''; ?></td>
                                        <td>
                                            <?php if (!empty($expense['vendor_phone'])): ?>
                                                <span class="vendor-phone"><?php echo htmlspecialchars($expense['vendor_phone']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($expense['receipt_path'])): ?>
                                                <?php 
                                                $ext = strtolower(pathinfo($expense['receipt_path'], PATHINFO_EXTENSION));
                                                if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                                    <img src="<?php echo htmlspecialchars($expense['receipt_path']); ?>" alt="Receipt" style="max-width:60px; max-height:60px; border-radius:4px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                                                <?php elseif ($ext === 'pdf'): ?>
                                                    <a href="<?php echo htmlspecialchars($expense['receipt_path']); ?>" target="_blank" style="text-decoration:none;">
                                                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Unsupported</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?project_id=<?php echo $selected_project_id; ?>&delete_expense=<?php echo $expense['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this expense?');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h5 class="text-muted">No Expenses Recorded</h5>
                            <p class="text-muted">Start by recording your first expense using the form above.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- No Project Selected -->
            <div class="content-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">Select a Project</h3>
                    <p class="text-muted mb-4">Choose a project from the dropdown above to view and manage expenses.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receipt / Resource Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhoto" src="" alt="Receipt" class="img-fluid" style="max-height: 70vh;">
                    <div id="modalPDF" class="d-none">
                        <iframe id="pdfViewer" src="" width="100%" height="600px" style="border: none;"></iframe>
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
                    <img src="../coat-of-arms-of-zambia.jpg" alt="Republic of Zambia" height="50" class="me-3" style="border-radius: 4px;">
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
        // Update server time
        function updateServerTime() {
            const now = new Date();
            document.getElementById('serverTime').textContent = now.toLocaleTimeString('en-US', { hour12: false });
        }
        setInterval(updateServerTime, 1000);

        // Toggle receipt requirement based on category
        function toggleReceiptRequirement(category) {
            const receiptRequired = ['Materials', 'Equipment', 'Administration', 'Utilities', 'Other'];
            const receiptOptional = ['Labor', 'Transport'];
            
            const receiptRequiredElement = document.getElementById('receiptRequired');
            const receiptNotice = document.getElementById('receiptNotice');
            
            if (receiptRequired.includes(category)) {
                receiptRequiredElement.classList.remove('d-none');
                receiptNotice.classList.remove('d-none');
            } else {
                receiptRequiredElement.classList.add('d-none');
                receiptNotice.classList.add('d-none');
            }
        }

        // Initialize receipt requirement on page load
        document.addEventListener('DOMContentLoaded', function() {
            const initialCategory = document.querySelector('select[name="category"]').value;
            if (initialCategory) {
                toggleReceiptRequirement(initialCategory);
            }
        });

        // Receipt upload functionality
        const receiptUploadArea = document.getElementById('receiptUploadArea');
        const receiptInput = document.getElementById('receipt_file');
        const receiptPreviewContainer = document.getElementById('receiptPreviewContainer');
        let selectedReceipt = null;

        // Click to upload receipt
        receiptUploadArea.addEventListener('click', () => receiptInput.click());

        // Drag and drop for receipt
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            receiptUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            receiptUploadArea.addEventListener(eventName, () => receiptUploadArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            receiptUploadArea.addEventListener(eventName, () => receiptUploadArea.classList.remove('dragover'), false);
        });

        receiptUploadArea.addEventListener('drop', handleReceiptDrop, false);

        function handleReceiptDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleReceiptSelect(files);
        }

        function handleReceiptSelect(files) {
            if (files.length > 0) {
                const file = files[0];
                if (validateReceiptFile(file)) {
                    addReceiptPreview(file);
                }
            }
        }

        function validateReceiptFile(file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!validTypes.includes(file.type)) {
                alert(`File type not supported for receipt: ${file.name}. Please use JPG, PNG, or PDF.`);
                return false;
            }

            if (file.size > maxSize) {
                alert(`File too large: ${file.name}. Maximum size for receipts is 5MB.`);
                return false;
            }

            return true;
        }

        function addReceiptPreview(file) {
            selectedReceipt = file;
            
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Receipt Preview" class="file-preview-image">
                        <button type="button" class="file-preview-remove" onclick="removeReceipt()">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="file-preview-name">${file.name}</div>
                    `;
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                previewItem.innerHTML = `
                    <div class="file-preview-image bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                    </div>
                    <button type="button" class="file-preview-remove" onclick="removeReceipt()">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="file-preview-name">${file.name}</div>
                `;
            }

            receiptPreviewContainer.innerHTML = '';
            receiptPreviewContainer.appendChild(previewItem);
        }

        function removeReceipt() {
            selectedReceipt = null;
            receiptPreviewContainer.innerHTML = '';
            receiptInput.value = '';
        }

        // Resource photos upload functionality
        const resourceUploadArea = document.getElementById('resourceUploadArea');
        const resourceInput = document.getElementById('resource_photos');
        const resourcePreviewContainer = document.getElementById('resourcePreviewContainer');
        const selectedResourceFiles = new Map();

        // Click to upload resources
        resourceUploadArea.addEventListener('click', () => resourceInput.click());

        // Drag and drop for resources
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            resourceUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            resourceUploadArea.addEventListener(eventName, () => resourceUploadArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            resourceUploadArea.addEventListener(eventName, () => resourceUploadArea.classList.remove('dragover'), false);
        });

        resourceUploadArea.addEventListener('drop', handleResourceDrop, false);

        function handleResourceDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleResourceSelect(files);
        }

        function handleResourceSelect(files) {
            for (let file of files) {
                if (validateResourceFile(file)) {
                    addResourcePreview(file);
                }
            }
            updateResourceInput();
        }

        function validateResourceFile(file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            const maxSize = 10 * 1024 * 1024; // 10MB

            if (!validTypes.includes(file.type)) {
                alert(`File type not supported for resource photos: ${file.name}. Please use JPG or PNG.`);
                return false;
            }

            if (file.size > maxSize) {
                alert(`File too large: ${file.name}. Maximum size for resource photos is 10MB.`);
                return false;
            }

            return true;
        }

        function addResourcePreview(file) {
            const fileId = Date.now() + Math.random();
            selectedResourceFiles.set(fileId, file);
        
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';
            previewItem.dataset.fileId = fileId;
        
            const reader = new FileReader();
            reader.onload = (e) => {
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Resource Preview" class="file-preview-image">
                    <button type="button" class="file-preview-remove" onclick="removeResourceFile('${fileId}')">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="file-preview-name">${file.name}</div>
                `;
            };
            reader.readAsDataURL(file);
        
            resourcePreviewContainer.appendChild(previewItem);
        }

        function removeResourceFile(fileId) {
            selectedResourceFiles.delete(fileId);
            document.querySelector(`[data-file-id="${fileId}"]`).remove();
            updateResourceInput();
        }

        function updateResourceInput() {
            // Create a new DataTransfer object
            const dt = new DataTransfer();
            
            // Add all selected files to the DataTransfer object
            selectedResourceFiles.forEach(file => {
                dt.items.add(file);
            });
            
            // Update the file input files
            resourceInput.files = dt.files;
        }

        // Photo modal functionality
        function openPhotoModal(fileUrl) {
            const modalPhoto = document.getElementById('modalPhoto');
            const modalPDF = document.getElementById('modalPDF');
            const pdfViewer = document.getElementById('pdfViewer');
            
            // Hide all media types first
            modalPhoto.classList.add('d-none');
            modalPDF.classList.add('d-none');
            
            const fileExtension = fileUrl.toLowerCase().split('.').pop();
            
            if (fileExtension === 'pdf') {
                // PDF file
                modalPDF.classList.remove('d-none');
                pdfViewer.src = '../' + fileUrl;
            } else {
                // Image file
                modalPhoto.classList.remove('d-none');
                modalPhoto.src = '../' + fileUrl;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            modal.show();
        }

        // Expense Chart
        <?php if ($selected_project && count($expense_categories) > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('expenseChart').getContext('2d');
            
            // Prepare chart data
            const labels = <?php echo json_encode(array_column($expense_categories, 'category')); ?>;
            const data = <?php echo json_encode(array_column($expense_categories, 'total_amount')); ?>;
            
            // Generate colors
            const backgroundColors = [
                '#1a4e8a', '#e9b949', '#28a745', '#dc3545', 
                '#6f42c1', '#fd7e14', '#20c997', '#0dcaf0',
                '#6610f2', '#6f42c1', '#d63384', '#fd7e14'
            ];
            
            const expenseChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ZMW ' + value.toLocaleString() + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '55%',
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}
// Get project ID from query parameter
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    redirect('projects.php');
    exit();
}

// Get project data
$project = getProjectById($project_id);
if (!$project) {
    $_SESSION['error'] = "Project not found";
    redirect('projects.php');
    exit();
}

// Get expense data
$expenses = getProjectExpenses($project_id);
$total_expenses = getTotalProjectExpenses($project_id);
$expense_categories = getExpenseCategoriesSummary($project_id);

// Calculate statistics
$budget_utilization = $project['budget'] > 0 ? ($total_expenses / $project['budget']) * 100 : 0;
$remaining_budget = $project['budget'] - $total_expenses;
$avg_expense = count($expenses) > 0 ? $total_expenses / count($expenses) : 0;

// Get beneficiary info
$beneficiary = getUserById($project['beneficiary_id']);

$userData = getUserData();
$pageTitle = "Project Expenses - " . htmlspecialchars($project['title']);
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
            --info: #0dcaf0;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            background-color: #f5f7fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .navbar {
            background-color: #1a4e8a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #0d6efd;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }

        .stat-label {
            font-size: 0.875rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a4e8a;
        }

        .expense-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table thead th {
            color: #1a4e8a;
            font-weight: 600;
            padding: 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-category {
            background-color: #e7f1ff;
            color: #0d6efd;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }

        .category-summary {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-name {
            font-weight: 500;
            color: #1a4e8a;
        }

        .category-amount {
            font-weight: 700;
            color: var(--success);
            font-size: 1.1rem;
        }

        .expense-high {
            background-color: #fff5f5;
            border-left: 4px solid var(--danger);
        }

        .budget-progress {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .budget-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--success), var(--info));
            transition: width 0.3s ease;
        }

        .budget-progress-bar.warning {
            background: linear-gradient(90deg, var(--warning), var(--danger));
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }

        .btn-custom {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .dashboard-footer {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-top: 3rem;
            border-top: 3px solid #1a4e8a;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-state-text {
            color: #666;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-cube me-2"></i>CDF System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php?logout=true">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header" style="margin-top: 70px;">
        <div class="container">
            <nav class="breadcrumb">
                <a class="breadcrumb-item text-white" href="projects.php">Projects</a>
                <a class="breadcrumb-item text-white" href="project_details.php?id=<?php echo $project_id; ?>">
                    <?php echo htmlspecialchars($project['title']); ?>
                </a>
                <span class="breadcrumb-item active text-white-50">Expenses</span>
            </nav>
            <h1 class="page-title">Project Expenses</h1>
            <p class="text-white-50 mb-0">
                <i class="fas fa-chart-pie me-2"></i>
                Detailed expense tracking and budget analysis
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="row mb-4">
            <!-- Budget Statistics -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card success">
                    <div class="stat-label">Total Budget</div>
                    <div class="stat-value">ZMW <?php echo number_format($project['budget'], 2); ?></div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="stat-card danger">
                    <div class="stat-label">Total Expenses</div>
                    <div class="stat-value">ZMW <?php echo number_format($total_expenses, 2); ?></div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="stat-card info">
                    <div class="stat-label">Remaining Budget</div>
                    <div class="stat-value" style="color: <?php echo $remaining_budget < 0 ? '#dc3545' : '#0d6efd'; ?>;">
                        ZMW <?php echo number_format($remaining_budget, 2); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="stat-card warning">
                    <div class="stat-label">Budget Utilization</div>
                    <div class="stat-value"><?php echo round($budget_utilization, 1); ?>%</div>
                </div>
            </div>
        </div>

        <!-- Budget Progress Bar -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="stat-label">Budget Allocation Progress</span>
                        <small class="text-muted">
                            ZMW <?php echo number_format($total_expenses, 2); ?> / 
                            ZMW <?php echo number_format($project['budget'], 2); ?>
                        </small>
                    </div>
                    <div class="budget-progress">
                        <div class="budget-progress-bar <?php echo $budget_utilization > 80 ? 'warning' : ''; ?>" 
                             style="width: <?php echo min($budget_utilization, 100); ?>%"></div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <?php echo $remaining_budget < 0 ? '⚠️ Budget exceeded by ZMW ' . number_format(abs($remaining_budget), 2) : 'Budget available: ZMW ' . number_format($remaining_budget, 2); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Expense Summary by Category -->
        <?php if (count($expense_categories) > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="category-summary">
                    <h5 class="mb-3">
                        <i class="fas fa-bars me-2"></i>Expense Summary by Category
                    </h5>
                    <?php foreach ($expense_categories as $category): ?>
                    <div class="category-item">
                        <span class="category-name"><?php echo htmlspecialchars($category['category']); ?></span>
                        <div class="text-end">
                            <span class="badge-category"><?php echo $category['count']; ?> expense(s)</span>
                            <span class="category-amount ms-2">ZMW <?php echo number_format($category['total_amount'], 2); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Expenses Table -->
        <div class="row">
            <div class="col-12">
                <div class="expense-table">
                    <div style="padding: 1.5rem;">
                        <h5 class="mb-3">
                            <i class="fas fa-receipt me-2"></i>All Expenses (<?php echo count($expenses); ?>)
                        </h5>
                    </div>

                    <?php if (count($expenses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Vendor</th>
                                    <th>Receipt #</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): 
                                    $is_high = $expense['amount'] > ($project['budget'] * 0.1);
                                ?>
                                <tr class="<?php echo $is_high ? 'expense-high' : ''; ?>">
                                    <td>
                                        <i class="fas fa-calendar me-2 text-muted"></i>
                                        <strong><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>
                                        <span class="badge-category"><?php echo htmlspecialchars($expense['category']); ?></span>
                                    </td>
                                    <td><?php echo !empty($expense['vendor']) ? htmlspecialchars($expense['vendor']) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo !empty($expense['receipt_number']) ? htmlspecialchars($expense['receipt_number']) : '<span class="text-muted">-</span>'; ?></td>
                                    <td class="text-end">
                                        <strong class="text-success">ZMW <?php echo number_format($expense['amount'], 2); ?></strong>
                                        <?php if ($is_high): ?>
                                            <br><small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>High expense</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals Row -->
                    <div style="padding: 1.5rem; border-top: 2px solid #dee2e6; background-color: #f8f9fa;">
                        <div class="row text-end">
                            <div class="col-12">
                                <h6 class="mb-0">
                                    Total Expenses: 
                                    <span class="text-success" style="font-size: 1.5rem;">
                                        ZMW <?php echo number_format($total_expenses, 2); ?>
                                    </span>
                                </h6>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h5 class="empty-state-text">No Expenses Recorded</h5>
                        <p class="text-muted">No expenses have been recorded for this project yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="project_details.php?id=<?php echo $project_id; ?>" class="btn btn-outline-primary btn-custom">
                    <i class="fas fa-arrow-left me-2"></i>Back to Project Details
                </a>
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
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

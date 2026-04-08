<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'all';
$date_range = $_GET['date_range'] ?? 'month';
$status_filter = $_GET['status'] ?? 'all';
$project_filter = $_GET['project_id'] ?? 'all';

// Get projects for filter dropdown
$projects = getOfficerProjects($_SESSION['user_id']) ?? [];

// Initialize default values for undefined functions (will be populated below)
$stats = ['total_evaluations' => 0, 'compliance_rate' => 0, 'pending_reviews' => 0];
$monthly_trends = [];
$category_performance = [];
$status_distribution = [];
$score_distribution = [];

// GET DATA FROM ALL EVALUATION TOOLS
// 1. Compliance Data
$compliance_checks = function_exists('getComplianceChecks') ? getComplianceChecks($_SESSION['user_id']) : [];

// 2. Progress Review Data
$progress_reviews = function_exists('getProgressReviews') ? getProgressReviews($_SESSION['user_id']) : [];

// 3. Quality Evaluation Data
$quality_evaluations = function_exists('getQualityEvaluations') ? getQualityEvaluations($_SESSION['user_id']) : [];

// 4. Impact Assessment Data
$impact_assessments = function_exists('getRecentImpactAssessments') ? getRecentImpactAssessments($_SESSION['user_id'], 100) : [];

// Consolidate all evaluation data
$all_evaluations = [];

// Add compliance checks
foreach ($compliance_checks as $compliance) {
    $all_evaluations[] = [
        'type' => 'compliance',
        'project_id' => $compliance['project_id'],
        'project_title' => $compliance['project_title'] ?? 'Unknown',
        'overall_score' => $compliance['overall_compliance'] ?? 0,
        'created_at' => $compliance['created_at'] ?? date('Y-m-d'),
        'data' => $compliance
    ];
}

// Add progress reviews
foreach ($progress_reviews as $progress) {
    $all_evaluations[] = [
        'type' => 'progress',
        'project_id' => $progress['project_id'],
        'project_title' => $progress['project_title'] ?? 'Unknown',
        'overall_score' => $progress['progress_score'] ?? 0,
        'created_at' => $progress['created_at'] ?? date('Y-m-d'),
        'data' => $progress
    ];
}

// Add quality evaluations
foreach ($quality_evaluations as $quality) {
    $all_evaluations[] = [
        'type' => 'quality',
        'project_id' => $quality['project_id'],
        'project_title' => $quality['project_title'] ?? 'Unknown',
        'overall_score' => $quality['quality_score'] ?? 0,
        'created_at' => $quality['created_at'] ?? date('Y-m-d'),
        'data' => $quality
    ];
}

// Add impact assessments
foreach ($impact_assessments as $impact) {
    $all_evaluations[] = [
        'type' => 'impact',
        'project_id' => $impact['project_id'],
        'project_title' => $impact['project_title'] ?? 'Unknown',
        'overall_score' => $impact['overall_impact'] ?? 0,
        'created_at' => $impact['created_at'] ?? date('Y-m-d'),
        'data' => $impact
    ];
}

// Sort by date descending
usort($all_evaluations, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Apply report_type filter to consolidated evaluations
if ($report_type !== 'all') {
    $all_evaluations = array_filter($all_evaluations, function($eval) use ($report_type) {
        return $eval['type'] === $report_type;
    });
    $all_evaluations = array_values($all_evaluations);
}

// Apply date range filter
if ($date_range !== 'all') {
    $cutoff = strtotime(date('Y-m-d'));
    switch ($date_range) {
        case 'today':
            $start = $cutoff;
            break;
        case 'week':
            $start = $cutoff - (7 * 86400);
            break;
        case 'month':
            $start = $cutoff - (30 * 86400);
            break;
        case 'quarter':
            $start = $cutoff - (90 * 86400);
            break;
        case 'year':
            $start = $cutoff - (365 * 86400);
            break;
        default:
            $start = 0;
    }
    
    $all_evaluations = array_filter($all_evaluations, function($eval) use ($start) {
        return strtotime($eval['created_at']) >= $start;
    });
    $all_evaluations = array_values($all_evaluations);
}

// Apply project filter
if ($project_filter !== 'all') {
    $all_evaluations = array_filter($all_evaluations, function($eval) use ($project_filter) {
        return $eval['project_id'] == $project_filter;
    });
    $all_evaluations = array_values($all_evaluations);
}

// Apply status filter
if ($status_filter !== 'all') {
    $all_evaluations = array_filter($all_evaluations, function($eval) use ($status_filter) {
        return isset($eval['data']['status']) && $eval['data']['status'] === $status_filter;
    });
    $all_evaluations = array_values($all_evaluations);
}

// Generate report summary from filtered consolidated data
$report_summary = [
    'total_evaluations' => count($all_evaluations),
    'avg_score' => count($all_evaluations) > 0 ? round(array_reduce($all_evaluations, function($carry, $item) {
        return $carry + ($item['overall_score'] ?? 0);
    }, 0) / count($all_evaluations), 1) : 0,
    'completion_rate' => count($all_evaluations) > 0 ? round((count(array_filter($all_evaluations, fn($e) => ($e['overall_score'] ?? 0) >= 70)) / count($all_evaluations)) * 100, 1) : 0,
    'high_priority' => count(array_filter($all_evaluations, fn($e) => ($e['overall_score'] ?? 0) < 60))
];

// ==================== GENERATE CHART DATA FROM FILTERED EVALUATIONS ====================

// 1. Status Distribution (for Doughnut Chart)
$status_distribution = [
    'Completed' => count(array_filter($all_evaluations, fn($e) => isset($e['data']['status']) && $e['data']['status'] === 'completed')),
    'In Progress' => count(array_filter($all_evaluations, fn($e) => isset($e['data']['status']) && $e['data']['status'] === 'in-progress')),
    'Pending' => count(array_filter($all_evaluations, fn($e) => isset($e['data']['status']) && $e['data']['status'] === 'pending')),
    'Delayed' => count(array_filter($all_evaluations, fn($e) => isset($e['data']['status']) && $e['data']['status'] === 'delayed'))
];

// 2. Category Performance (for Bar Chart)
$category_performance = [];
foreach ($all_evaluations as $eval) {
    $category = $eval['type'];
    if (!isset($category_performance[$category])) {
        $category_performance[$category] = 0;
        $category_performance[$category . '_count'] = 0;
    }
    $category_performance[$category] += $eval['overall_score'];
    $category_performance[$category . '_count']++;
}

// Convert to averages
$category_perf_avg = [];
foreach ($category_performance as $key => $value) {
    if (strpos($key, '_count') === false) {
        $count = $category_performance[$key . '_count'] ?? 1;
        $category_perf_avg[ucfirst($key)] = $count > 0 ? round($value / $count, 1) : 0;
    }
}
$category_performance = $category_perf_avg;

// 3. Monthly Trends (for Line Chart)
$monthly_trends = [];
$months_back = 6;
for ($i = $months_back - 1; $i >= 0; $i--) {
    $month_date = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    
    $month_evals = array_filter($all_evaluations, fn($e) => substr($e['created_at'], 0, 7) === $month_date);
    $month_avg = count($month_evals) > 0 ? round(array_reduce($month_evals, fn($c, $e) => $c + ($e['overall_score'] ?? 0), 0) / count($month_evals), 1) : 0;
    
    $monthly_trends[] = [
        'month' => $month_label,
        'score' => $month_avg,
        'count' => count($month_evals)
    ];
}

// 4. Score Distribution (for Area Chart)
$score_ranges = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
foreach ($all_evaluations as $eval) {
    $score = intval($eval['overall_score']);
    if ($score <= 20) $score_ranges['0-20']++;
    elseif ($score <= 40) $score_ranges['21-40']++;
    elseif ($score <= 60) $score_ranges['41-60']++;
    elseif ($score <= 80) $score_ranges['61-80']++;
    else $score_ranges['81-100']++;
}
$score_distribution = $score_ranges;

// ==================== GENERATE DETAILED PER-TYPE STATISTICS ====================

// Initialize stats arrays for each evaluation type
$compliance_stats = [
    'count' => 0,
    'avg_score' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0,
    'details' => []
];

$progress_stats = [
    'count' => 0,
    'avg_score' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0,
    'details' => []
];

$quality_stats = [
    'count' => 0,
    'avg_score' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0,
    'details' => []
];

$impact_stats = [
    'count' => 0,
    'avg_score' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0,
    'details' => []
];

// Calculate stats for each evaluation type
foreach ($all_evaluations as $eval) {
    $score = $eval['overall_score'] ?? 0;
    $type = $eval['type'];
    
    if ($type === 'compliance') {
        $compliance_stats['count']++;
        $compliance_stats['details'][] = $eval;
        if ($score >= 80) $compliance_stats['high']++;
        elseif ($score >= 60) $compliance_stats['medium']++;
        else $compliance_stats['low']++;
    } elseif ($type === 'progress') {
        $progress_stats['count']++;
        $progress_stats['details'][] = $eval;
        if ($score >= 80) $progress_stats['high']++;
        elseif ($score >= 60) $progress_stats['medium']++;
        else $progress_stats['low']++;
    } elseif ($type === 'quality') {
        $quality_stats['count']++;
        $quality_stats['details'][] = $eval;
        if ($score >= 80) $quality_stats['high']++;
        elseif ($score >= 60) $quality_stats['medium']++;
        else $quality_stats['low']++;
    } elseif ($type === 'impact') {
        $impact_stats['count']++;
        $impact_stats['details'][] = $eval;
        if ($score >= 80) $impact_stats['high']++;
        elseif ($score >= 60) $impact_stats['medium']++;
        else $impact_stats['low']++;
    }
}

// Calculate averages
if ($compliance_stats['count'] > 0) {
    $compliance_stats['avg_score'] = round(array_reduce($compliance_stats['details'], fn($c, $e) => $c + ($e['overall_score'] ?? 0), 0) / $compliance_stats['count'], 1);
}
if ($progress_stats['count'] > 0) {
    $progress_stats['avg_score'] = round(array_reduce($progress_stats['details'], fn($c, $e) => $c + ($e['overall_score'] ?? 0), 0) / $progress_stats['count'], 1);
}
if ($quality_stats['count'] > 0) {
    $quality_stats['avg_score'] = round(array_reduce($quality_stats['details'], fn($c, $e) => $c + ($e['overall_score'] ?? 0), 0) / $quality_stats['count'], 1);
}
if ($impact_stats['count'] > 0) {
    $impact_stats['avg_score'] = round(array_reduce($impact_stats['details'], fn($c, $e) => $c + ($e['overall_score'] ?? 0), 0) / $impact_stats['count'], 1);
}

// (Placeholder for future export functionality)

// Handle new evaluation submission
if (isset($_POST['create_evaluation'])) {
    $evaluation_data = [
        'project_id' => $_POST['project_id'],
        'officer_id' => $_SESSION['user_id'],
        'evaluation_type' => $_POST['evaluation_type'],
        'evaluation_date' => $_POST['evaluation_date'],
        'status' => $_POST['status'],
        'compliance_score' => $_POST['compliance_score'],
        'budget_compliance' => $_POST['budget_compliance'],
        'timeline_compliance' => $_POST['timeline_compliance'],
        'quality_score' => $_POST['quality_score'],
        'documentation_score' => $_POST['documentation_score'],
        'community_impact_score' => $_POST['community_impact_score'],
        'overall_score' => $_POST['overall_score'],
        'findings' => $_POST['findings'],
        'recommendations' => $_POST['recommendations']
    ];
    
    $result = createEvaluation($evaluation_data);
    if ($result) {
        $_SESSION['success'] = "Evaluation created successfully!";
        header("Location: reports.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to create evaluation. Please try again.";
    }
}

$pageTitle = "Dynamic Evaluation Reports - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Dynamic Evaluation Reports dashboard for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.1/dist/chartjs-plugin-zoom.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
    :root {
        --primary: #1a4e8a;
        --primary-dark: #0d3a6c;
        --primary-light: #2c6cb0;
        --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        --secondary: #e9b949;
        --secondary-dark: #d4a337;
        --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
        
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
        --shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.18);
        --shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.22);
        
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --border-radius: 12px;
        --border-radius-lg: 16px;
    }

    body {
        font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        background-attachment: fixed;
        color: #212529;
        line-height: 1.7;
    }

    .navbar {
        background: var(--primary-gradient);
        box-shadow: var(--shadow-lg);
        padding: 0.8rem 0;
    }

    .navbar-brand {
        font-weight: 800;
        color: white !important;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.95) !important;
        font-weight: 600;
        transition: var(--transition);
        padding: 0.6rem 1rem !important;
        border-radius: 8px;
    }

    .nav-link:hover, 
    .nav-link:focus,
    .nav-link.active {
        color: white !important;
        background-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .dashboard-header {
        background: var(--primary-gradient);
        color: white;
        padding: 3rem 0 2.5rem;
        margin-top: 76px;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius-lg);
        padding: 2rem 1.5rem;
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
        height: 100%;
        border-top: 5px solid var(--primary);
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }

    .stat-number {
        font-size: 2.75rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 0.75rem;
        line-height: 1;
    }

    .stat-title {
        font-size: 1.1rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .content-card {
        background: white;
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
        position: relative;
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

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }

    .chart-container:hover {
        box-shadow: var(--shadow);
        transform: translateY(-2px);
    }

    .chart-controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }

    .chart-controls .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .loading-spinner {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 100;
    }

    .chart-updating {
        opacity: 0.7;
        pointer-events: none;
    }

    .real-time-badge {
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }

    .filter-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        border: 1px solid #dee2e6;
    }

    .export-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .btn-export {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-pdf { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; }
    .btn-excel { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; }
    .btn-word { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; }
    .btn-html { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); color: white; }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .metric-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        text-align: center;
        box-shadow: var(--shadow-sm);
        border-left: 4px solid var(--primary);
        transition: var(--transition);
        height: 100%;
        margin-bottom: 1rem;
    }

    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow);
    }

    .trend-indicator {
        font-size: 0.875rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .trend-up { color: #28a745; }
    .trend-down { color: #dc3545; }
    .trend-neutral { color: #6c757d; }

    .data-freshness {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .report-summary-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .table th {
        border-top: none;
        font-weight: 700;
        color: var(--primary);
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.25rem;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-completed { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; }
    .badge-in-progress { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529; }
    .badge-pending { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; }
    .badge-delayed { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; }

    .badge {
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        box-shadow: var(--shadow-sm);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge i {
        font-size: 0.9rem;
    }

    .evaluation-row {
        transition: all 0.3s ease;
        border-bottom: 1px solid #e9ecef;
    }

    .evaluation-row:hover {
        background-color: rgba(26, 78, 138, 0.05);
        box-shadow: inset 0 2px 8px rgba(26, 78, 138, 0.08);
    }

    .table .progress {
        margin: 0;
        border-radius: 4px;
        background-color: #e9ecef;
    }

    .table .progress-bar {
        border-radius: 4px;
        transition: width 0.6s ease;
        background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
    }

    .table td {
        vertical-align: middle;
        padding: 1rem;
    }

    .table tbody tr td:first-child {
        font-weight: 600;
        color: var(--primary);
    }

    .table thead th {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        font-weight: 700;
        border: none;
        padding: 1.25rem 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .progress-thin {
        height: 6px;
        margin: 0.25rem 0;
    }

    @media (max-width: 768px) {
        .dashboard-header {
            text-align: center;
            padding: 2rem 0 1.5rem;
        }
        
        .export-buttons {
            justify-content: center;
        }
        
        .filter-section .row > div {
            margin-bottom: 1rem;
        }
        
        .chart-container {
            height: 250px;
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../projects/index.php">
                                <i class="fas fa-project-diagram me-2"></i>Assigned Projects
                            </a></li>
                            <li><a class="dropdown-item active" href="reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
                            </a></li>
                            <li><a class="dropdown-item" href="../analytics/dashboard.php">
                                <i class="fas fa-chart-bar me-2"></i>Analytics
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
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: var(--secondary-gradient); display: flex; align-items: center; justify-content: center; color: #212529; font-weight: 700;">
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
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Dynamic Evaluation Reports</h1>
                    <p class="lead">Real-time analytics with automatic data updates</p>
                    <p class="mb-0">
                        <span class="real-time-badge">
                            <i class="fas fa-sync-alt me-1"></i>LIVE DATA
                        </span>
                        <span class="ms-2">Last updated: <span id="lastUpdateTime"><?php echo date('H:i:s'); ?></span></span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light" onclick="refreshAllCharts()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Charts
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Report Summary -->
        <div class="report-summary-card">
            <div class="row">
                <div class="col-md-3">
                    <h4><?php echo $report_summary['total_evaluations']; ?></h4>
                    <small>Total Evaluations</small>
                </div>
                <div class="col-md-3">
                    <h4><?php echo $report_summary['avg_score']; ?>%</h4>
                    <small>Average Score</small>
                </div>
                <div class="col-md-3">
                    <h4><?php echo $report_summary['completion_rate']; ?>%</h4>
                    <small>Completion Rate</small>
                </div>
                <div class="col-md-3">
                    <h4><?php echo $report_summary['high_priority']; ?></h4>
                    <small>High Priority</small>
                </div>
            </div>
        </div>

        <!-- Evaluation Tools Quick Buttons -->
        <div class="content-card">
            <div class="card-header">
                <h5><i class="fas fa-tools me-2"></i>Evaluation Tools</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="progress.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4" style="border: 2px solid; transition: all 0.3s;" onmouseover="this.style.background='#1a4e8a'; this.style.color='white';" onmouseout="this.style.background=''; this.style.color='#1a4e8a';">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <strong>Progress Review</strong>
                            <small class="text-muted mt-2" style="font-size: 0.85rem;">Review beneficiary progress</small>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="compliance.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4" style="border: 2px solid; transition: all 0.3s;" onmouseover="this.style.background='#ffc107'; this.style.color='#212529';" onmouseout="this.style.background=''; this.style.color='#ffc107';">
                            <i class="fas fa-check-double fa-2x mb-2"></i>
                            <strong>Compliance Check</strong>
                            <small class="text-muted mt-2" style="font-size: 0.85rem;">Verify CDF guidelines</small>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="quality.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4" style="border: 2px solid; transition: all 0.3s;" onmouseover="this.style.background='#28a745'; this.style.color='white';" onmouseout="this.style.background=''; this.style.color='#28a745';">
                            <i class="fas fa-award fa-2x mb-2"></i>
                            <strong>Quality Assessment</strong>
                            <small class="text-muted mt-2" style="font-size: 0.85rem;">Evaluate quality standards</small>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="impact.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4" style="border: 2px solid; transition: all 0.3s;" onmouseover="this.style.background='#17a2b8'; this.style.color='white';" onmouseout="this.style.background=''; this.style.color='#17a2b8';">
                            <i class="fas fa-bullseye fa-2x mb-2"></i>
                            <strong>Impact Evaluation</strong>
                            <small class="text-muted mt-2" style="font-size: 0.85rem;">Assess impact metrics</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Filter Section -->
        <div class="filter-section">
            <form method="GET" id="reportFilters">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Report Type</label>
                        <select name="report_type" class="form-select" onchange="updateCharts()">
                            <option value="all" <?php echo $report_type === 'all' ? 'selected' : ''; ?>>All Evaluations</option>
                            <option value="progress" <?php echo $report_type === 'progress' ? 'selected' : ''; ?>>Progress Reports</option>
                            <option value="compliance" <?php echo $report_type === 'compliance' ? 'selected' : ''; ?>>Compliance Reports</option>
                            <option value="quality" <?php echo $report_type === 'quality' ? 'selected' : ''; ?>>Quality Assessments</option>
                            <option value="impact" <?php echo $report_type === 'impact' ? 'selected' : ''; ?>>Impact Evaluations</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Date Range</label>
                        <select name="date_range" class="form-select" onchange="updateCharts()">
                            <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="quarter" <?php echo $date_range === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                            <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>This Year</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select" onchange="updateCharts()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="in-progress" <?php echo $status_filter === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="delayed" <?php echo $status_filter === 'delayed' ? 'selected' : ''; ?>>Delayed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Project</label>
                        <select name="project_id" class="form-select" onchange="updateCharts()">
                            <option value="all" <?php echo $project_filter === 'all' ? 'selected' : ''; ?>>All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                            <i class="fas fa-filter me-1"></i> Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Real-time Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0" id="totalEvaluations"><?php echo $report_summary['total_evaluations']; ?></h3>
                            <small class="text-muted">Total Evaluations</small>
                        </div>
                        <i class="fas fa-clipboard-check fa-2x text-primary"></i>
                    </div>
                    <div class="trend-indicator trend-neutral mt-2" id="evaluationsTrend">
                        <i class="fas fa-info-circle me-1"></i>
                        <span><?php echo $report_type !== 'all' ? ucfirst($report_type) . ' type' : 'All types'; ?></span>
                    </div>
                    <div class="data-freshness">Updated just now</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0" id="avgScore"><?php echo $report_summary['avg_score']; ?>%</h3>
                            <small class="text-muted">Average Score</small>
                        </div>
                        <i class="fas fa-chart-line fa-2x text-success"></i>
                    </div>
                    <div class="trend-indicator <?php echo $report_summary['avg_score'] >= 70 ? 'trend-up' : 'trend-down'; ?> mt-2" id="scoreTrend">
                        <i class="fas fa-<?php echo $report_summary['avg_score'] >= 70 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                        <span><?php echo $report_summary['avg_score'] >= 70 ? 'Above target' : 'Below target'; ?></span>
                    </div>
                    <div class="data-freshness">Updated just now</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0" id="completionRate"><?php echo $report_summary['completion_rate']; ?>%</h3>
                            <small class="text-muted">Completion Rate</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-info"></i>
                    </div>
                    <div class="trend-indicator <?php echo $report_summary['completion_rate'] >= 80 ? 'trend-up' : 'trend-down'; ?> mt-2" id="completionTrend">
                        <i class="fas fa-<?php echo $report_summary['completion_rate'] >= 80 ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                        <span><?php echo $report_summary['completion_rate'] >= 80 ? 'On track' : 'Needs attention'; ?></span>
                    </div>
                    <div class="data-freshness">Updated just now</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0" id="highPriority"><?php echo $report_summary['high_priority']; ?></h3>
                            <small class="text-muted">High Priority</small>
                        </div>
                        <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                    </div>
                    <div class="trend-indicator <?php echo $report_summary['high_priority'] === 0 ? 'trend-up' : 'trend-down'; ?> mt-2" id="priorityTrend">
                        <i class="fas fa-<?php echo $report_summary['high_priority'] === 0 ? 'check' : 'times'; ?> me-1"></i>
                        <span><?php echo $report_summary['high_priority'] === 0 ? 'No issues' : $report_summary['high_priority'] . ' items'; ?></span>
                    </div>
                    <div class="data-freshness">Updated just now</div>
                </div>
            </div>
        </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0" id="completionRate"><?php echo $report_summary['completion_rate'] ?? 0; ?>%</h3>
                            <small class="text-muted">Completion Rate</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-info"></i>
                    </div>
                    <div class="trend-indicator trend-up mt-2" id="completionTrend">
                        <i class="fas fa-arrow-up me-1"></i>
                        <span>+8% improvement</span>
                    </div>
                    <div class="data-freshness">Updated just now</div>
                </div>
            </div>
        </div>

        <!-- Detailed Evaluation Statistics by Type -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-th me-2"></i>Evaluation Breakdown by Type</h4>
                    <small class="ms-auto text-muted">Real-time assessment statistics</small>
                </div>
            </div>
            
            <!-- Compliance Statistics Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="content-card h-100" style="border-left: 5px solid #ffc107;">
                    <div class="card-header" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); border-bottom: none; color: #212529;">
                        <h5 style="color: #212529; margin-bottom: 0;"><i class="fas fa-check-double me-2"></i>Compliance Checks</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <small class="text-muted">Total Assessments</small>
                                <h3 class="text-warning mb-0"><?php echo $compliance_stats['count']; ?></h3>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <strong>Average Score</strong>
                                <span class="text-<?php echo $compliance_stats['avg_score'] >= 70 ? 'success' : 'danger'; ?> ms-2 fw-bold">
                                    <?php echo $compliance_stats['avg_score']; ?>%
                                </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo min($compliance_stats['avg_score'], 100); ?>%; background: linear-gradient(90deg, #ffc107, #e0a800);"></div>
                            </div>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3);">
                                    <small class="text-muted d-block mb-1">High (≥80)</small>
                                    <h5 class="text-success mb-0"><?php echo $compliance_stats['high']; ?></h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3);">
                                    <small class="text-muted d-block mb-1">Medium (60-79)</small>
                                    <h5 class="text-warning mb-0"><?php echo $compliance_stats['medium']; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="p-2 rounded" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);">
                                <small class="text-muted d-block mb-1">Low (<60)</small>
                                <h5 class="text-danger mb-0"><?php echo $compliance_stats['low']; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Review Statistics Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="content-card h-100" style="border-left: 5px solid #28a745;">
                    <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); border-bottom: none; color: white;">
                        <h5 style="color: white; margin-bottom: 0;"><i class="fas fa-chart-line me-2"></i>Progress Reviews</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <small class="text-muted">Total Assessments</small>
                                <h3 class="text-success mb-0"><?php echo $progress_stats['count']; ?></h3>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <strong>Average Score</strong>
                                <span class="text-<?php echo $progress_stats['avg_score'] >= 70 ? 'success' : 'danger'; ?> ms-2 fw-bold">
                                    <?php echo $progress_stats['avg_score']; ?>%
                                </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo min($progress_stats['avg_score'], 100); ?>%; background: linear-gradient(90deg, #28a745, #1e7e34);"></div>
                            </div>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3);">
                                    <small class="text-muted d-block mb-1">High (≥80)</small>
                                    <h5 class="text-success mb-0"><?php echo $progress_stats['high']; ?></h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3);">
                                    <small class="text-muted d-block mb-1">Medium (60-79)</small>
                                    <h5 class="text-warning mb-0"><?php echo $progress_stats['medium']; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="p-2 rounded" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);">
                                <small class="text-muted d-block mb-1">Low (<60)</small>
                                <h5 class="text-danger mb-0"><?php echo $progress_stats['low']; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quality Assessment Statistics Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="content-card h-100" style="border-left: 5px solid #17a2b8;">
                    <div class="card-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border-bottom: none; color: white;">
                        <h5 style="color: white; margin-bottom: 0;"><i class="fas fa-award me-2"></i>Quality Assessments</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <small class="text-muted">Total Assessments</small>
                                <h3 class="text-info mb-0"><?php echo $quality_stats['count']; ?></h3>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <strong>Average Score</strong>
                                <span class="text-<?php echo $quality_stats['avg_score'] >= 70 ? 'success' : 'danger'; ?> ms-2 fw-bold">
                                    <?php echo $quality_stats['avg_score']; ?>%
                                </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo min($quality_stats['avg_score'], 100); ?>%; background: linear-gradient(90deg, #17a2b8, #138496);"></div>
                            </div>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3);">
                                    <small class="text-muted d-block mb-1">High (≥80)</small>
                                    <h5 class="text-success mb-0"><?php echo $quality_stats['high']; ?></h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3);">
                                    <small class="text-muted d-block mb-1">Medium (60-79)</small>
                                    <h5 class="text-warning mb-0"><?php echo $quality_stats['medium']; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="p-2 rounded" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);">
                                <small class="text-muted d-block mb-1">Low (<60)</small>
                                <h5 class="text-danger mb-0"><?php echo $quality_stats['low']; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Impact Evaluation Statistics Card -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="content-card h-100" style="border-left: 5px solid #dc3545;">
                    <div class="card-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-bottom: none; color: white;">
                        <h5 style="color: white; margin-bottom: 0;"><i class="fas fa-bullseye me-2"></i>Impact Evaluations</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <small class="text-muted">Total Assessments</small>
                                <h3 class="text-danger mb-0"><?php echo $impact_stats['count']; ?></h3>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <strong>Average Score</strong>
                                <span class="text-<?php echo $impact_stats['avg_score'] >= 70 ? 'success' : 'danger'; ?> ms-2 fw-bold">
                                    <?php echo $impact_stats['avg_score']; ?>%
                                </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo min($impact_stats['avg_score'], 100); ?>%; background: linear-gradient(90deg, #dc3545, #c82333);"></div>
                            </div>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3);">
                                    <small class="text-muted d-block mb-1">High (≥80)</small>
                                    <h5 class="text-success mb-0"><?php echo $impact_stats['high']; ?></h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3);">
                                    <small class="text-muted d-block mb-1">Medium (60-79)</small>
                                    <h5 class="text-warning mb-0"><?php echo $impact_stats['medium']; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="p-2 rounded" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);">
                                <small class="text-muted d-block mb-1">Low (<60)</small>
                                <h5 class="text-danger mb-0"><?php echo $impact_stats['low']; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Charts Section -->
        <div class="row">
            <!-- Evaluation Status Distribution -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-chart-pie me-2"></i>Evaluation Status Distribution</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-secondary" onclick="refreshChart('statusChart')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body position-relative">
                        <div class="loading-spinner" id="statusChartLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Scores by Category -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-chart-bar me-2"></i>Compliance Scores by Category</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-secondary" onclick="refreshChart('complianceChart')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body position-relative">
                        <div class="loading-spinner" id="complianceChartLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="complianceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Performance Trends -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-chart-line me-2"></i>Monthly Performance Trends</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-secondary" onclick="refreshChart('trendChart')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleTrendChartType()">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body position-relative">
                        <div class="loading-spinner" id="trendChartLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Score Distribution -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-chart-area me-2"></i>Score Distribution Analysis</h5>
                        <div class="chart-controls">
                            <button class="btn btn-sm btn-outline-secondary" onclick="refreshChart('distributionChart')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body position-relative">
                        <div class="loading-spinner" id="distributionChartLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluation Data Table - Consolidated from All Evaluation Tools -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="fas fa-table me-2"></i>Consolidated Evaluation Data</h5>
                    <small class="text-muted">Data from Compliance, Progress, Quality, and Impact evaluations</small>
                </div>
                <div class="export-buttons">
                    <button class="btn btn-sm btn-pdf" onclick="exportTableToPDF()">
                        <i class="fas fa-file-pdf me-1"></i>PDF
                    </button>
                    <button class="btn btn-sm btn-excel" onclick="exportTableToExcel()">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="evaluationsTable">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Evaluation Type</th>
                                <th>Date</th>
                                <th>Overall Score</th>
                                <th>Compliance</th>
                                <th>Progress</th>
                                <th>Quality</th>
                                <th>Impact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($all_evaluations) > 0): ?>
                                <?php foreach (array_slice($all_evaluations, 0, 50) as $evaluation): ?>
                                <tr class="evaluation-row" data-type="<?php echo $evaluation['type']; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($evaluation['project_title']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?php 
                                            $type_color = ['compliance' => '#1a4e8a', 'progress' => '#28a745', 'quality' => '#e9b949', 'impact' => '#17a2b8'];
                                            echo $type_color[$evaluation['type']] ?? '#6c757d';
                                        ?>">
                                            <i class="fas fa-<?php 
                                                $type_icon = ['compliance' => 'check-circle', 'progress' => 'chart-line', 'quality' => 'star', 'impact' => 'handshake'];
                                                echo $type_icon[$evaluation['type']] ?? 'file';
                                            ?> me-1"></i><?php echo ucfirst($evaluation['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($evaluation['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-<?php 
                                            $score = intval($evaluation['overall_score']);
                                            echo $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo intval($evaluation['overall_score']); ?>%
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($evaluation['type'] === 'compliance'): ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" style="width: <?php echo intval($evaluation['data']['overall_compliance'] ?? 0); ?>%"></div>
                                            </div>
                                            <small><?php echo intval($evaluation['data']['overall_compliance'] ?? 0); ?>%</small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($evaluation['type'] === 'progress'): ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo intval($evaluation['data']['progress_score'] ?? 0); ?>%"></div>
                                            </div>
                                            <small><?php echo intval($evaluation['data']['progress_score'] ?? 0); ?>%</small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($evaluation['type'] === 'quality'): ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-warning" style="width: <?php echo intval($evaluation['data']['quality_score'] ?? 0); ?>%"></div>
                                            </div>
                                            <small><?php echo intval($evaluation['data']['quality_score'] ?? 0); ?>%</small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($evaluation['type'] === 'impact'): ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" style="width: <?php echo intval($evaluation['data']['overall_impact'] ?? 0); ?>%"></div>
                                            </div>
                                            <small><?php echo intval($evaluation['data']['overall_impact'] ?? 0); ?>%</small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Completed</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View Details" onclick="viewEvaluationDetails('<?php echo $evaluation['type']; ?>', <?php echo $evaluation['data']['id'] ?? 0; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="Download" onclick="downloadEvaluation('<?php echo $evaluation['type']; ?>', <?php echo $evaluation['data']['id'] ?? 0; ?>)">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Evaluations Found</h5>
                                        <p class="text-muted">No evaluation records found. Create evaluations in Compliance, Progress, Quality, or Impact sections.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Assessment Data by Type -->
        <div class="row mt-4">
            <!-- Compliance Assessment Details -->
            <?php if ($compliance_stats['count'] > 0): ?>
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-check-double me-2"></i>Compliance Assessment Details</h5>
                        <small class="text-muted">All compliance checks submitted</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($compliance_stats['details'], 0, 5) as $detail): ?>
                                    <tr>
                                        <td><small><?php echo htmlspecialchars(substr($detail['project_title'], 0, 20)); ?></small></td>
                                        <td><strong class="text-<?php echo $detail['overall_score'] >= 70 ? 'success' : 'warning'; ?>"><?php echo $detail['overall_score']; ?>%</strong></td>
                                        <td><small><?php echo date('M j', strtotime($detail['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Progress Assessment Details -->
            <?php if ($progress_stats['count'] > 0): ?>
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Progress Review Details</h5>
                        <small class="text-muted">All progress reviews submitted</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($progress_stats['details'], 0, 5) as $detail): ?>
                                    <tr>
                                        <td><small><?php echo htmlspecialchars(substr($detail['project_title'], 0, 20)); ?></small></td>
                                        <td><strong class="text-<?php echo $detail['overall_score'] >= 70 ? 'success' : 'warning'; ?>"><?php echo $detail['overall_score']; ?>%</strong></td>
                                        <td><small><?php echo date('M j', strtotime($detail['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quality Assessment Details -->
            <?php if ($quality_stats['count'] > 0): ?>
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-award me-2"></i>Quality Assessment Details</h5>
                        <small class="text-muted">All quality assessments submitted</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($quality_stats['details'], 0, 5) as $detail): ?>
                                    <tr>
                                        <td><small><?php echo htmlspecialchars(substr($detail['project_title'], 0, 20)); ?></small></td>
                                        <td><strong class="text-<?php echo $detail['overall_score'] >= 70 ? 'success' : 'warning'; ?>"><?php echo $detail['overall_score']; ?>%</strong></td>
                                        <td><small><?php echo date('M j', strtotime($detail['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Impact Assessment Details -->
            <?php if ($impact_stats['count'] > 0): ?>
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bullseye me-2"></i>Impact Evaluation Details</h5>
                        <small class="text-muted">All impact evaluations submitted</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($impact_stats['details'], 0, 5) as $detail): ?>
                                    <tr>
                                        <td><small><?php echo htmlspecialchars(substr($detail['project_title'], 0, 20)); ?></small></td>
                                        <td><strong class="text-<?php echo $detail['overall_score'] >= 70 ? 'success' : 'warning'; ?>"><?php echo $detail['overall_score']; ?>%</strong></td>
                                        <td><small><?php echo date('M j', strtotime($detail['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Evaluation Summary Statistics -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Evaluations by Type</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="evaluationTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bar-chart me-2"></i>Score Distribution by Type</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="scoreByTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Evaluation Modal -->
    <div class="modal fade" id="createEvaluationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Evaluation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Project</label>
                                    <select name="project_id" class="form-select" required>
                                        <option value="">Select Project</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Evaluation Type</label>
                                    <select name="evaluation_type" class="form-select" required>
                                        <option value="progress">Progress Review</option>
                                        <option value="compliance">Compliance Check</option>
                                        <option value="quality">Quality Assessment</option>
                                        <option value="impact">Impact Evaluation</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Evaluation Date</label>
                                    <input type="date" name="evaluation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="completed">Completed</option>
                                        <option value="in-progress">In Progress</option>
                                        <option value="pending">Pending</option>
                                        <option value="delayed">Delayed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Compliance Score (%)</label>
                                    <input type="number" name="compliance_score" class="form-control" min="0" max="100" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Budget Compliance (%)</label>
                                    <input type="number" name="budget_compliance" class="form-control" min="0" max="100" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Timeline Compliance (%)</label>
                                    <input type="number" name="timeline_compliance" class="form-control" min="0" max="100" value="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Quality Score (%)</label>
                                    <input type="number" name="quality_score" class="form-control" min="0" max="100" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Documentation Score (%)</label>
                                    <input type="number" name="documentation_score" class="form-control" min="0" max="100" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Community Impact Score (%)</label>
                                    <input type="number" name="community_impact_score" class="form-control" min="0" max="100" value="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Overall Score (%)</label>
                            <input type="number" name="overall_score" class="form-control" min="0" max="100" value="0" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Findings</label>
                            <textarea name="findings" class="form-control" rows="3" placeholder="Enter evaluation findings..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Recommendations</label>
                            <textarea name="recommendations" class="form-control" rows="3" placeholder="Enter recommendations..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_evaluation" class="btn btn-primary-custom">Create Evaluation</button>
                    </div>
                </form>
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
        // Global chart instances
        let charts = {};
        let chartConfigs = {};
        let autoRefreshInterval;

        // Initialize all charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeAllCharts();
            startAutoRefresh();
        });

        // Initialize all charts
        function initializeAllCharts() {
            initializeStatusChart();
            initializeComplianceChart();
            initializeTrendChart();
            initializeDistributionChart();
            initializeEvaluationTypeChart();
            initializeScoreByTypeChart();
            updateLastUpdateTime();
        }

        // Status Distribution Chart
        function initializeStatusChart() {
            const ctx = document.getElementById('statusChart').getContext('2d');
            
            const data = {
                labels: <?php echo json_encode(array_keys($status_distribution)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($status_distribution)); ?>,
                    backgroundColor: [
                        '#28a745', // Completed - Green
                        '#ffc107', // In Progress - Yellow
                        '#17a2b8', // Pending - Blue
                        '#dc3545', // Delayed - Red
                        '#6c757d'  // Other - Gray
                    ],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            };

            charts.statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });

            chartConfigs.statusChart = { type: 'doughnut', data: data };
        }

        // Compliance Scores Chart
        function initializeComplianceChart() {
            const ctx = document.getElementById('complianceChart').getContext('2d');
            
            const data = {
                labels: <?php echo json_encode(array_keys($category_performance)); ?>,
                datasets: [{
                    label: 'Current Scores',
                    data: <?php echo json_encode(array_values($category_performance)); ?>,
                    backgroundColor: '#1a4e8a',
                    borderColor: '#1a4e8a',
                    borderWidth: 2,
                    borderRadius: 4,
                    borderSkipped: false,
                }, {
                    label: 'Target (80%)',
                    data: Array(<?php echo count($category_performance); ?>).fill(80),
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderColor: 'rgba(220, 53, 69, 0.3)',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    type: 'line',
                    fill: false,
                    pointRadius: 0
                }]
            };

            charts.complianceChart = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Score (%)'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            chartConfigs.complianceChart = { type: 'bar', data: data };
        }

        // Trend Chart
        function initializeTrendChart() {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            const data = {
                labels: <?php echo json_encode(array_column($monthly_trends, 'month')); ?>,
                datasets: [{
                    label: 'Overall Score',
                    data: <?php echo json_encode(array_column($monthly_trends, 'score')); ?>,
                    borderColor: '#1a4e8a',
                    backgroundColor: 'rgba(26, 78, 138, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1a4e8a',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            };

            charts.trendChart = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Score (%)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'nearest'
                    }
                }
            });

            chartConfigs.trendChart = { type: 'line', data: data, currentType: 'line' };
        }

        // Distribution Chart
        function initializeDistributionChart() {
            const ctx = document.getElementById('distributionChart').getContext('2d');
            
            const rawData = <?php echo json_encode(array_values($score_distribution)); ?>;
            const total = rawData.reduce((a, b) => a + b, 0);
            const percentages = rawData.map(value => total > 0 ? Math.round((value / total) * 100) : 0);
            
            const data = {
                labels: ['Critical (0-20%)', 'Poor (21-40%)', 'Fair (41-60%)', 'Good (61-80%)', 'Excellent (81-100%)'],
                datasets: [{
                    label: 'Number of Evaluations',
                    data: rawData,
                    backgroundColor: [
                        '#dc3545',  // Red - Critical
                        '#fd7e14',  // Orange - Poor
                        '#ffc107',  // Yellow - Fair
                        '#28a745',  // Green - Good
                        '#1a4e8a'   // Dark Blue - Excellent
                    ],
                    borderColor: [
                        '#bd2130',
                        '#e56a00',
                        '#e0a800',
                        '#1e7e34',
                        '#0d3a6c'
                    ],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            };

            const chartInstance = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Evaluations',
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = percentages[context.dataIndex];
                                    return `${value} evaluations (${percentage}%)`;
                                },
                                afterLabel: function(context) {
                                    const labels = ['Critical', 'Poor', 'Fair', 'Good', 'Excellent'];
                                    return `Performance: ${labels[context.dataIndex]}`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            font: {
                                size: 13,
                                weight: 'bold'
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            color: function(context) {
                                return context.dataset.borderColor[context.dataIndex];
                            },
                            formatter: function(value, context) {
                                const percentage = percentages[context.dataIndex];
                                return `${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            charts.distributionChart = chartInstance;
            chartConfigs.distributionChart = { type: 'bar', data: data };
        }

        // Evaluation Type Chart - Pie Chart
        function initializeEvaluationTypeChart() {
            const ctx = document.getElementById('evaluationTypeChart');
            if (!ctx) return;
            
            const evaluationData = <?php echo json_encode([
                'Compliance' => count($compliance_checks),
                'Progress' => count($progress_reviews),
                'Quality' => count($quality_evaluations),
                'Impact' => count($impact_assessments)
            ]); ?>;
            
            const data = {
                labels: Object.keys(evaluationData),
                datasets: [{
                    data: Object.values(evaluationData),
                    backgroundColor: [
                        '#1a4e8a',  // Compliance - Blue
                        '#28a745',  // Progress - Green
                        '#e9b949',  // Quality - Yellow
                        '#17a2b8'   // Impact - Cyan
                    ],
                    borderColor: [
                        '#0d3a6c',
                        '#1e7e34',
                        '#d4a337',
                        '#138496'
                    ],
                    borderWidth: 2
                }]
            };

            charts.evaluationTypeChart = new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 13,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Score by Type Chart - Bar Chart
        function initializeScoreByTypeChart() {
            const ctx = document.getElementById('scoreByTypeChart');
            if (!ctx) return;
            
            const complianceAvg = <?php echo count($compliance_checks) > 0 ? 'Math.round(' . implode('+', array_map(function($c) { return '(' . ($c['overall_compliance'] ?? 0) . ')'; }, $compliance_checks)) . '/' . count($compliance_checks) . ')' : '0'; ?>;
            const progressAvg = <?php echo count($progress_reviews) > 0 ? 'Math.round(' . implode('+', array_map(function($p) { return '(' . ($p['progress_score'] ?? 0) . ')'; }, $progress_reviews)) . '/' . count($progress_reviews) . ')' : '0'; ?>;
            const qualityAvg = <?php echo count($quality_evaluations) > 0 ? 'Math.round(' . implode('+', array_map(function($q) { return '(' . ($q['quality_score'] ?? 0) . ')'; }, $quality_evaluations)) . '/' . count($quality_evaluations) . ')' : '0'; ?>;
            const impactAvg = <?php echo count($impact_assessments) > 0 ? 'Math.round(' . implode('+', array_map(function($i) { return '(' . ($i['overall_impact'] ?? 0) . ')'; }, $impact_assessments)) . '/' . count($impact_assessments) . ')' : '0'; ?>;
            
            const data = {
                labels: ['Compliance', 'Progress', 'Quality', 'Impact'],
                datasets: [{
                    label: 'Average Score (%)',
                    data: [complianceAvg, progressAvg, qualityAvg, impactAvg],
                    backgroundColor: [
                        '#1a4e8a',
                        '#28a745',
                        '#e9b949',
                        '#17a2b8'
                    ],
                    borderColor: [
                        '#0d3a6c',
                        '#1e7e34',
                        '#d4a337',
                        '#138496'
                    ],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            };

            charts.scoreByTypeChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: data,
                options: {
                    indexAxis: 'x',
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Average Score (%)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Average: ${Math.round(context.raw)}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Chart control functions
        function refreshChart(chartId) {
            const chart = charts[chartId];
            const loadingElement = document.getElementById(chartId + 'Loading');
            
            if (chart && loadingElement) {
                loadingElement.style.display = 'block';
                chart.canvas.parentElement.classList.add('chart-updating');
                
                // Simulate data refresh
                setTimeout(() => {
                    updateChartData(chartId);
                    loadingElement.style.display = 'none';
                    chart.canvas.parentElement.classList.remove('chart-updating');
                    chart.update();
                    updateLastUpdateTime();
                }, 1000);
            }
        }

        function refreshAllCharts() {
            Object.keys(charts).forEach(chartId => {
                refreshChart(chartId);
            });
            updateMetrics();
        }

        function toggleTrendChartType() {
            const chart = charts.trendChart;
            const config = chartConfigs.trendChart;
            
            if (config.currentType === 'line') {
                chart.config.type = 'bar';
                config.currentType = 'bar';
            } else {
                chart.config.type = 'line';
                config.currentType = 'line';
            }
            
            chart.update();
        }

        // Update chart data based on current filters
        function updateChartData(chartId) {
            const chart = charts[chartId];
            const filters = getCurrentFilters();
            
            // Simulate data updates based on filters
            switch(chartId) {
                case 'statusChart':
                    const newStatusData = simulateStatusData(filters);
                    chart.data.datasets[0].data = newStatusData;
                    break;
                    
                case 'complianceChart':
                    const newComplianceData = simulateComplianceData(filters);
                    chart.data.datasets[0].data = newComplianceData;
                    break;
                    
                case 'trendChart':
                    const newTrendData = simulateTrendData(filters);
                    chart.data.datasets[0].data = newTrendData;
                    break;
                    
                case 'distributionChart':
                    const newDistributionData = simulateDistributionData(filters);
                    chart.data.datasets[0].data = newDistributionData;
                    break;
            }
        }

        // Get current filter values
        function getCurrentFilters() {
            return {
                report_type: document.querySelector('select[name="report_type"]').value,
                date_range: document.querySelector('select[name="date_range"]').value,
                status: document.querySelector('select[name="status"]').value,
                project_id: document.querySelector('select[name="project_id"]').value
            };
        }

        // Update charts when filters change
        function updateCharts() {
            // Submit form to apply filters and refresh data on server
            document.getElementById('reportFilters').submit();
        }

        // Apply filters and refresh
        function applyFilters() {
            document.getElementById('reportFilters').submit();
        }

        // Update metrics display
        function updateMetrics() {
            const metrics = simulateMetricUpdates();
            
            document.getElementById('totalEvaluations').textContent = metrics.totalEvaluations;
            document.getElementById('avgScore').textContent = metrics.avgScore + '%';
            document.getElementById('pendingReviews').textContent = metrics.pendingReviews;
            document.getElementById('completionRate').textContent = metrics.completionRate + '%';
        }

        // Use real data from database (no simulation)
        function simulateStatusData(filters) {
            // Return exact data from database without randomization
            return <?php echo json_encode(array_values($status_distribution ?? [])); ?>;
        }

        function simulateComplianceData(filters) {
            // Return exact compliance scores from database
            return <?php echo json_encode(array_values($category_performance ?? [])); ?>;
        }

        function simulateTrendData(filters) {
            // Return exact trend data from database
            return <?php echo json_encode(array_column($monthly_trends ?? [], 'score')); ?>;
        }

        function simulateDistributionData(filters) {
            // Return exact distribution data from database
            return <?php echo json_encode(array_values($score_distribution ?? [])); ?>;
        }

        function simulateMetricUpdates() {
            return {
                totalEvaluations: <?php echo $stats['total_evaluations'] ?? 0; ?>,
                avgScore: <?php echo $stats['compliance_rate'] ?? 0; ?>,
                pendingReviews: <?php echo $stats['pending_reviews'] ?? 0; ?>,
                completionRate: <?php echo $report_summary['completion_rate'] ?? 0; ?>
            };
        }

        function updateLastUpdateTime() {
            document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
        }

        // Auto-refresh functionality
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                // Only refresh data that changes, not entire page
                updateLastUpdateTime();
            }, 60000); // Every 1 minute for time update
        }

        function stopAutoRefresh() {
            clearInterval(autoRefreshInterval);
        }

        // View Evaluation Details
        function viewEvaluationDetails(type, id) {
            const typeRoutes = {
                'compliance': '../evaluation/compliance.php',
                'progress': '../evaluation/progress.php',
                'quality': '../evaluation/quality.php',
                'impact': '../evaluation/impact.php'
            };
            
            const route = typeRoutes[type];
            if (route) {
                window.location.href = `${route}?view=${id}`;
            } else {
                alert('Invalid evaluation type');
            }
        }

        // Download Evaluation
        function downloadEvaluation(type, id) {
            const typeRoutes = {
                'compliance': '../evaluation/compliance.php',
                'progress': '../evaluation/progress.php',
                'quality': '../evaluation/quality.php',
                'impact': '../evaluation/impact.php'
            };
            
            const route = typeRoutes[type];
            if (route) {
                window.location.href = `${route}?export=${id}&format=pdf`;
            } else {
                alert('Invalid evaluation type');
            }
        }

        // Export functionality
        function exportTableToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFontSize(20);
            doc.text('CDF Evaluation Report', 105, 15, { align: 'center' });
            doc.setFontSize(12);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 105, 25, { align: 'center' });
            
            let yPosition = 40;
            const table = document.getElementById('evaluationsTable');
            const rows = table.querySelectorAll('tbody tr');
            
            doc.setFontSize(10);
            rows.forEach((row, index) => {
                if (yPosition > 270) {
                    doc.addPage();
                    yPosition = 20;
                }
                
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const project = cells[0].textContent.trim();
                    const type = cells[1].textContent.trim();
                    const date = cells[2].textContent.trim();
                    
                    doc.text(`${project} - ${type} - ${date}`, 20, yPosition);
                    yPosition += 7;
                }
            });
            
            doc.save('evaluations_report.pdf');
        }

        function exportTableToExcel() {
            const data = [['Project', 'Type', 'Date', 'Status', 'Compliance', 'Budget', 'Timeline', 'Quality', 'Overall']];
            
            const table = document.getElementById('evaluationsTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const rowData = [
                        cells[0].textContent.trim(),
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        cells[4].textContent.trim(),
                        cells[5].textContent.trim(),
                        cells[6].textContent.trim(),
                        cells[7].textContent.trim(),
                        cells[8].textContent.trim()
                    ];
                    data.push(rowData);
                }
            });
            
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Evaluations');
            XLSX.writeFile(wb, 'evaluations_data.xlsx');
        }

        function downloadEvaluationReport(evaluationId) {
            alert(`Downloading detailed report for evaluation ID: ${evaluationId}`);
        }

        // Auto-calculate overall score
        document.addEventListener('DOMContentLoaded', function() {
            const scoreInputs = [
                'compliance_score', 'budget_compliance', 'timeline_compliance',
                'quality_score', 'documentation_score', 'community_impact_score'
            ];
            
            const overallScoreInput = document.querySelector('input[name="overall_score"]');
            
            scoreInputs.forEach(inputName => {
                const input = document.querySelector(`input[name="${inputName}"]`);
                if (input) {
                    input.addEventListener('input', calculateOverallScore);
                }
            });
            
            function calculateOverallScore() {
                let total = 0;
                let count = 0;
                
                scoreInputs.forEach(inputName => {
                    const input = document.querySelector(`input[name="${inputName}"]`);
                    if (input && input.value) {
                        total += parseInt(input.value);
                        count++;
                    }
                });
                
                if (count > 0) {
                    const average = Math.round(total / count);
                    overallScoreInput.value = average;
                }
            }
        });

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            stopAutoRefresh();
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
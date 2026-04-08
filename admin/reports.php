<?php
require_once '../functions.php';
requireRole('admin');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);

// Get report data
$reportType = $_GET['type'] ?? 'overview';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$constituency = $_GET['constituency'] ?? 'all';

// Generate report data based on parameters
$reportData = generateReportData($reportType, $startDate, $endDate, $constituency);

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    exportReport($reportData, $exportType, $reportType);
}

/**
 * Generate report data based on type and filters
 */
function generateReportData($type, $startDate, $endDate, $constituency) {
    $data = [];
    
    switch ($type) {
        case 'overview':
            $data = generateOverviewReport($startDate, $endDate, $constituency);
            break;
        case 'projects':
            $data = generateProjectsReport($startDate, $endDate, $constituency);
            break;
        case 'financial':
            $data = generateFinancialReport($startDate, $endDate, $constituency);
            break;
        case 'users':
            $data = generateUsersReport($startDate, $endDate, $constituency);
            break;
        case 'activities':
            $data = generateActivitiesReport($startDate, $endDate, $constituency);
            break;
        case 'performance':
            $data = generatePerformanceReport($startDate, $endDate, $constituency);
            break;
        default:
            $data = generateOverviewReport($startDate, $endDate, $constituency);
    }
    
    return $data;
}

/**
 * Fallback data for errors
 */
function getFallbackReportData($type) {
    return [
        'summary' => ['total_projects' => 0, 'total_budget' => 0, 'total_users' => 0, 'completion_rate' => 0],
        'table_headers' => ['No Data Available'],
        'table_data' => [['Please check your database connection']]
    ];
}

/**
 * Generate system overview report
 */
function generateOverviewReport($startDate, $endDate, $constituency) {
    global $pdo;
    try {
        $query = "SELECT COUNT(*) as total_projects, 
                         SUM(budget) as total_budget,
                         SUM(CASE WHEN status IN ('in_progress', 'planning') THEN 1 ELSE 0 END) as active_projects,
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                  FROM projects 
                  WHERE created_at >= ? AND created_at <= ?";
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($constituency !== 'all') {
            $query .= " AND constituency = ?";
            $params[] = $constituency;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $projectStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $usersStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role IN ('beneficiary', 'officer')");
        $userStats = $usersStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalProjects = $projectStats['total_projects'] ?? 0;
        $completedProjects = $projectStats['completed_projects'] ?? 0;
        $completionRate = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0;
        
        // Get category breakdown
        $categoryQuery = "SELECT category, COUNT(*) as count, 
                                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                                SUM(budget) as total_budget
                         FROM projects 
                         WHERE created_at >= ? AND created_at <= ?";
        $categoryParams = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($constituency !== 'all') {
            $categoryQuery .= " AND constituency = ?";
            $categoryParams[] = $constituency;
        }
        
        $categoryQuery .= " GROUP BY category ORDER BY count DESC";
        $categoryStmt = $pdo->prepare($categoryQuery);
        $categoryStmt->execute($categoryParams);
        $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tableData = [];
        $activeCount = 0;
        foreach ($categories as $cat) {
            $active = $cat['count'] - $cat['completed'];
            $activeCount += $active;
            $tableData[] = [
                ucfirst($cat['category'] ?? 'Other') . ' Projects',
                $cat['count'] ?? 0,
                $active,
                $cat['completed'] ?? 0,
                number_format($cat['total_budget'] ?? 0)
            ];
        }
        
        return [
            'summary' => [
                'total_projects' => $projectStats['total_projects'] ?? 0,
                'total_budget' => $projectStats['total_budget'] ?? 0,
                'total_users' => $userStats['total'] ?? 0,
                'completion_rate' => $completionRate
            ],
            'table_headers' => ['Category', 'Total', 'Active', 'Completed', 'Budget (ZMW)'],
            'table_data' => $tableData
        ];
    } catch (PDOException $e) {
        error_log("Report generation error: " . $e->getMessage());
        return getFallbackReportData('overview');
    }
}

/**
 * Generate projects report
 */
function generateProjectsReport($startDate, $endDate, $constituency) {
    global $pdo;
    try {
         $query = "SELECT p.id, p.title as project_name, p.beneficiary_id, u.first_name, u.last_name, 
                    p.constituency, p.status, p.progress as completion_percentage, p.budget
                FROM projects p
                LEFT JOIN users u ON p.beneficiary_id = u.id
                WHERE p.created_at >= ? AND p.created_at <= ?";
         $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($constituency !== 'all') {
            $query .= " AND p.constituency = ?";
            $params[] = $constituency;
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT 100";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total_projects' => count($projects),
            'total_budget' => 0,
            'total_users' => count(array_unique(array_column($projects, 'beneficiary_id'))),
            'completion_rate' => 0
        ];
        
        $totalCompletion = 0;
        $tableData = [];
        
        foreach ($projects as $project) {
            $stats['total_budget'] += $project['budget'] ?? 0;
            $totalCompletion += $project['completion_percentage'] ?? 0;

            $tableData[] = [
                'PRJ-' . str_pad($project['id'], 4, '0', STR_PAD_LEFT),
                htmlspecialchars($project['project_name'] ?? ($project['title'] ?? 'N/A')),
                htmlspecialchars(($project['first_name'] ?? '') . ' ' . ($project['last_name'] ?? '')),
                htmlspecialchars($project['constituency'] ?? 'N/A'),
                ucfirst($project['status'] ?? 'unknown'),
                ($project['completion_percentage'] ?? 0) . '%',
                number_format($project['budget'] ?? 0)
            ];
        }
        
        $stats['completion_rate'] = count($projects) > 0 ? round($totalCompletion / count($projects)) : 0;
        
        return [
            'summary' => $stats,
            'table_headers' => ['Project ID', 'Project Name', 'Beneficiary', 'Constituency', 'Status', 'Progress', 'Budget'],
            'table_data' => $tableData
        ];
    } catch (PDOException $e) {
        error_log("Report generation error: " . $e->getMessage());
        return getFallbackReportData('projects');
    }
}

/**
 * Generate financial report
 */
function generateFinancialReport($startDate, $endDate, $constituency) {
    global $pdo;
    try {
        $query = "SELECT constituency, 
                         SUM(budget) as total_budget,
                         SUM(CASE WHEN status = 'completed' THEN budget ELSE 0 END) as utilized,
                         SUM(CASE WHEN status != 'completed' THEN budget ELSE 0 END) as balance
                  FROM projects
                  WHERE created_at >= ? AND created_at <= ?";
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($constituency !== 'all') {
            $query .= " AND constituency = ?";
            $params[] = $constituency;
        }
        
        $query .= " GROUP BY constituency ORDER BY total_budget DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $financialData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalBudget = 0;
        $totalUtilized = 0;
        $tableData = [];
        
        foreach ($financialData as $row) {
            $total = $row['total_budget'] ?? 0;
            $utilized = $row['utilized'] ?? 0;
            $balance = $row['balance'] ?? 0;
            $utilization = $total > 0 ? round(($utilized / $total) * 100) : 0;
            
            $totalBudget += $total;
            $totalUtilized += $utilized;
            
            $tableData[] = [
                htmlspecialchars($row['constituency'] ?? 'N/A'),
                number_format($total),
                number_format($utilized),
                number_format($balance),
                $utilization . '%'
            ];
        }
        
        return [
            'summary' => [
                'total_projects' => count($financialData),
                'total_budget' => $totalBudget,
                'total_users' => 0,
                'completion_rate' => $totalBudget > 0 ? round(($totalUtilized / $totalBudget) * 100) : 0
            ],
            'table_headers' => ['Constituency', 'Total Budget', 'Utilized', 'Balance', 'Utilization Rate'],
            'table_data' => $tableData
        ];
    } catch (PDOException $e) {
        error_log("Report generation error: " . $e->getMessage());
        return getFallbackReportData('financial');
    }
}

/**
 * Generate users activity report
 */
function generateUsersReport($startDate, $endDate, $constituency) {
    global $pdo;
    try {
        $beneficiaryStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'beneficiary' AND status = 'active'");
        $beneficiaryActive = $beneficiaryStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $beneficiaryInactiveStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'beneficiary' AND status = 'inactive'");
        $beneficiaryInactive = $beneficiaryInactiveStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $beneficiaryProjectsStmt = $pdo->query("SELECT COUNT(*) as total FROM projects WHERE beneficiary_id IS NOT NULL");
        $beneficiaryProjects = $beneficiaryProjectsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $officerStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'officer' AND status = 'active'");
        $officerActive = $officerStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $officerInactiveStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'officer' AND status = 'inactive'");
        $officerInactive = $officerInactiveStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $adminStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
        $adminCount = $adminStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $totalBeneficiaries = $beneficiaryActive + $beneficiaryInactive;
        $totalOfficers = $officerActive + $officerInactive;
        
        return [
            'summary' => [
                'total_projects' => $beneficiaryProjects,
                'total_budget' => 0,
                'total_users' => $totalBeneficiaries + $totalOfficers + $adminCount,
                'completion_rate' => 0
            ],
            'table_headers' => ['User Type', 'Total Users', 'Active', 'Inactive', 'Projects Assigned'],
            'table_data' => [
                ['Beneficiaries', $totalBeneficiaries, $beneficiaryActive, $beneficiaryInactive, $beneficiaryProjects],
                ['M&E Officers', $totalOfficers, $officerActive, $officerInactive, 'N/A'],
                ['Administrators', $adminCount, $adminCount, 0, 'N/A']
            ]
        ];
    } catch (PDOException $e) {
        error_log("Report generation error: " . $e->getMessage());
        return getFallbackReportData('users');
    }
}

/**
 * Generate officer performance report
 */
function generatePerformanceReport($startDate, $endDate, $constituency) {
    global $pdo;
    try {
        $query = "SELECT u.id, u.first_name, u.last_name,
                         COUNT(DISTINCT pa.project_id) as projects_assigned,
                         SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN p.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                         AVG(p.quality_score) as avg_rating
                  FROM users u
                  LEFT JOIN project_assignments pa ON u.id = pa.officer_id
                  LEFT JOIN projects p ON pa.project_id = p.id
                  WHERE u.role = 'officer' AND u.status = 'active'";
        
        if ($constituency !== 'all') {
            $query .= " AND u.constituency = ?";
            $params = [$constituency];
        } else {
            $params = [];
        }
        
        $query .= " GROUP BY u.id, u.first_name, u.last_name ORDER BY projects_assigned DESC LIMIT 50";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $tableData = [];
        $totalAssigned = 0;
        
        foreach ($officers as $officer) {
            $assigned = $officer['projects_assigned'] ?? 0;
            $completed = $officer['completed'] ?? 0;
            $inProgress = $officer['in_progress'] ?? 0;
            $rating = $officer['avg_rating'] ?? 0;
            
            $totalAssigned += $assigned;
            
            $tableData[] = [
                htmlspecialchars(($officer['first_name'] ?? '') . ' ' . ($officer['last_name'] ?? '')),
                $assigned,
                $completed,
                $inProgress,
                number_format($rating, 1) . '/5'
            ];
        }
        
        return [
            'summary' => [
                'total_projects' => $totalAssigned,
                'total_budget' => 0,
                'total_users' => count($officers),
                'completion_rate' => 0
            ],
            'table_headers' => ['Officer Name', 'Projects Assigned', 'Completed', 'In Progress', 'Avg Rating'],
            'table_data' => $tableData
        ];
    } catch (PDOException $e) {
        error_log("Report generation error: " . $e->getMessage());
        return getFallbackReportData('performance');
    }
}

/**
 * Generate recent system activities report
 */
function generateActivitiesReport($startDate, $endDate, $constituency) {
    global $pdo;
    try {
        $query = "SELECT al.id, al.user_id, al.action, al.details, al.project_id, al.created_at, u.first_name, u.last_name, p.title as project_title
                  FROM activity_log al
                  LEFT JOIN users u ON al.user_id = u.id
                  LEFT JOIN projects p ON al.project_id = p.id
                  WHERE al.created_at >= ? AND al.created_at <= ?";
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

        if ($constituency !== 'all') {
            $query .= " AND p.constituency = ?";
            $params[] = $constituency;
        }

        $query .= " ORDER BY al.created_at DESC LIMIT 500";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tableData = [];
        foreach ($activities as $act) {
            $tableData[] = [
                date('M j, Y H:i', strtotime($act['created_at'] ?? 'now')),
                htmlspecialchars(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? 'System')),
                htmlspecialchars($act['action'] ?? 'N/A'),
                htmlspecialchars($act['project_title'] ?? 'N/A'),
                htmlspecialchars(substr($act['details'] ?? '', 0, 120))
            ];
        }

        return [
            'summary' => [
                'total_projects' => count($activities),
                'total_budget' => 0,
                'total_users' => 0,
                'completion_rate' => 0
            ],
            'table_headers' => ['Date', 'User', 'Action', 'Project', 'Details'],
            'table_data' => $tableData
        ];
    } catch (PDOException $e) {
        error_log("Activities report error: " . $e->getMessage());
        return getFallbackReportData('activities');
    }
}

/**
 * Export report in various formats
 */
function exportReport($reportData, $format, $reportType) {
    // Set headers based on format
    switch ($format) {
        case 'pdf':
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
            // In a real implementation, you would generate PDF here
            // For now, we'll just redirect back
            echo "PDF export functionality would be implemented here with a library like TCPDF or Dompdf";
            exit;
            
        case 'excel':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.xls"');
            exportExcel($reportData, $reportType);
            exit;
            
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
            exportCSV($reportData, $reportType);
            exit;
    }
}

/**
 * Export data as Excel (simplified version)
 */
function exportExcel($reportData, $reportType) {
    $output = fopen('php://output', 'w');
    
    // Add headers
    fwrite($output, "CDF Management System - " . ucfirst($reportType) . " Report\n");
    fwrite($output, "Generated on: " . date('Y-m-d H:i:s') . "\n\n");
    
    // Add table headers
    if (isset($reportData['table_headers'])) {
        fwrite($output, implode("\t", $reportData['table_headers']) . "\n");
    }
    
    // Add table data
    if (isset($reportData['table_data'])) {
        foreach ($reportData['table_data'] as $row) {
            fwrite($output, implode("\t", $row) . "\n");
        }
    }
    
    fclose($output);
}

/**
 * Export data as CSV
 */
function exportCSV($reportData, $reportType) {
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, ["CDF Management System - " . ucfirst($reportType) . " Report"]);
    fputcsv($output, ["Generated on: " . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty line
    
    // Add table headers
    if (isset($reportData['table_headers'])) {
        fputcsv($output, $reportData['table_headers']);
    }
    
    // Add table data
    if (isset($reportData['table_data'])) {
        foreach ($reportData['table_data'] as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
}

$pageTitle = "System Reports - CDF Management System";

/**
 * Get chart data from database
 */
function getChartData($startDate, $endDate, $constituency) {
    global $pdo;
    try {
        // Project status data
        $statusQuery = "SELECT status, COUNT(*) as count FROM projects 
                       WHERE created_at >= ? AND created_at <= ?";
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($constituency !== 'all') {
            $statusQuery .= " AND constituency = ?";
            $params[] = $constituency;
        }
        
        $statusQuery .= " GROUP BY status";
        $statusStmt = $pdo->prepare($statusQuery);
        $statusStmt->execute($params);
        $statusData = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $statusLabels = [];
        $statusCounts = [];
        foreach ($statusData as $row) {
            $statusLabels[] = ucfirst($row['status'] ?? 'unknown');
            $statusCounts[] = $row['count'] ?? 0;
        }
        
        // Budget allocation by category
        $budgetQuery = "SELECT category, SUM(budget) as total FROM projects 
                       WHERE created_at >= ? AND created_at <= ?";
        $budgetParams = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($constituency !== 'all') {
            $budgetQuery .= " AND constituency = ?";
            $budgetParams[] = $constituency;
        }
        
        $budgetQuery .= " GROUP BY category ORDER BY total DESC";
        $budgetStmt = $pdo->prepare($budgetQuery);
        $budgetStmt->execute($budgetParams);
        $budgetData = $budgetStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $budgetLabels = [];
        $budgetAmounts = [];
        foreach ($budgetData as $row) {
            $budgetLabels[] = ucfirst($row['category'] ?? 'Other');
            $budgetAmounts[] = $row['total'] ?? 0;
        }
        
        return [
            'status' => [
                'labels' => json_encode($statusLabels),
                'data' => json_encode($statusCounts)
            ],
            'budget' => [
                'labels' => json_encode($budgetLabels),
                'data' => json_encode($budgetAmounts)
            ]
        ];
    } catch (PDOException $e) {
        error_log("Chart data error: " . $e->getMessage());
        return [
            'status' => ['labels' => json_encode([]), 'data' => json_encode([])],
            'budget' => ['labels' => json_encode([]), 'data' => json_encode([])]
        ];
    }
}

$chartData = getChartData($startDate, $endDate, $constituency);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Administrative reports dashboard for CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include_once '../includes/global_theme.php'; ?>
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
            --info: #17a2b8;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            padding: 0.75rem 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            font-size: 1.125rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .navbar-brand img {
            width: 45px;
            height: 45px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.5)) brightness(1.05) contrast(1.1);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            padding: 3px;
            background: rgba(255, 255, 255, 0.1);
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.1);
            filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.7)) brightness(1.1) contrast(1.2);
            border-color: var(--secondary);
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.75rem 1rem !important;
            border-radius: 6px;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--secondary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::before,
        .nav-link:focus::before,
        .nav-link.active::before {
            width: 80%;
        }

        .nav-link:hover, 
        .nav-link:focus,
        .nav-link.active {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow);
            border-radius: 8px;
            padding: 0.5rem 0;
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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2rem;
            font-weight: 700;
            box-shadow: var(--shadow);
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .profile-info h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

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
            color: var(--white);
            border: 2px solid var(--white);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--white);
            color: var(--primary);
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .content-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
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

        /* Report Filters */
        .report-filters {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        .filter-group {
            margin-bottom: 1rem;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        /* Report Cards */
        .report-card {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .report-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .report-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .report-subtitle {
            font-size: 0.85rem;
            color: var(--gray);
        }

        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .btn-export {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.5rem 1rem;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-export:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            color: var(--white);
        }

        .btn-export.pdf {
            background-color: var(--danger);
        }

        .btn-export.pdf:hover {
            background-color: #c82333;
        }

        .btn-export.excel {
            background-color: var(--success);
        }

        .btn-export.excel:hover {
            background-color: #218838;
        }

        .btn-export.csv {
            background-color: var(--info);
        }

        .btn-export.csv:hover {
            background-color: #138496;
        }

        /* Table Styles */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(26, 78, 138, 0.05);
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            background: var(--white);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        /* Footer */
        .dashboard-footer {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            padding: 2rem 1.5rem;
            margin-top: 3rem;
            border-top: 3px solid var(--primary);
        }

        /* Notification Badge */
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
            
            .action-buttons {
                justify-content: center;
            }
            
            .export-buttons {
                justify-content: center;
                flex-wrap: wrap;
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
                CDF Admin Portal
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
                            <li><a class="dropdown-item" href="users.php">
                                <i class="fas fa-users me-2"></i>User Management
                            </a></li>
                            <li><a class="dropdown-item" href="projects.php">
                                <i class="fas fa-project-diagram me-2"></i>Project Management
                            </a></li>
                            <li><a class="dropdown-item" href="assignments.php">
                                <i class="fas fa-user-tie me-2"></i>Officer Assignments
                            </a></li>
                            <li><a class="dropdown-item active" href="reports.php">
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
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">System Notifications</h6></li>
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
                                <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="notifications.php">No new notifications</a></li>
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
                                <i class="fas fa-cog me-2"></i>System Settings
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
                    <h1>System Reports</h1>
                    <p class="lead">Comprehensive analytics and reporting dashboard - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Generate detailed reports for analysis and decision making</p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="dashboard.php" class="btn btn-primary-custom">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </a>
                <a href="projects.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>Manage Projects
                </a>
                <a href="users.php" class="btn btn-outline-custom">
                    <i class="fas fa-users me-2"></i>User Management
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Report Filters -->
        <div class="report-filters">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label for="reportType">Report Type</label>
                            <select class="form-select" id="reportType" name="type">
                                <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>System Overview</option>
                                <option value="projects" <?php echo $reportType === 'projects' ? 'selected' : ''; ?>>Projects Report</option>
                                <option value="financial" <?php echo $reportType === 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                                <option value="users" <?php echo $reportType === 'users' ? 'selected' : ''; ?>>User Activity</option>
                                <option value="performance" <?php echo $reportType === 'performance' ? 'selected' : ''; ?>>Officer Performance</option>
                                <option value="activities" <?php echo $reportType === 'activities' ? 'selected' : ''; ?>>System Activities</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo $startDate; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label for="endDate">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo $endDate; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="filter-group">
                            <label for="constituency">Constituency</label>
                            <select class="form-select" id="constituency" name="constituency">
                                <option value="all" <?php echo $constituency === 'all' ? 'selected' : ''; ?>>All Constituencies</option>
                                <option value="lusaka" <?php echo $constituency === 'lusaka' ? 'selected' : ''; ?>>Lusaka</option>
                                <option value="copperbelt" <?php echo $constituency === 'copperbelt' ? 'selected' : ''; ?>>Copperbelt</option>
                                <option value="southern" <?php echo $constituency === 'southern' ? 'selected' : ''; ?>>Southern</option>
                                <option value="eastern" <?php echo $constituency === 'eastern' ? 'selected' : ''; ?>>Eastern</option>
                                <option value="western" <?php echo $constituency === 'western' ? 'selected' : ''; ?>>Western</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="reports.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Reset Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn btn-export pdf">
                <i class="fas fa-file-pdf me-1"></i>Export PDF
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-export excel">
                <i class="fas fa-file-excel me-1"></i>Export Excel
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-export csv">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </a>
        </div>

        <!-- Report Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="report-card">
                    <div class="report-number"><?php echo number_format($reportData['summary']['total_projects'] ?? 0); ?></div>
                    <div class="report-title">Total Projects</div>
                    <div class="report-subtitle">Across all constituencies</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="report-card">
                    <div class="report-number">ZMW <?php echo number_format($reportData['summary']['total_budget'] ?? 0); ?></div>
                    <div class="report-title">Total Budget</div>
                    <div class="report-subtitle">Allocated funds</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="report-card">
                    <div class="report-number"><?php echo number_format($reportData['summary']['total_users'] ?? 0); ?></div>
                    <div class="report-title">Active Users</div>
                    <div class="report-subtitle">Beneficiaries & Officers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="report-card">
                    <div class="report-number"><?php echo number_format($reportData['summary']['completion_rate'] ?? 0); ?>%</div>
                    <div class="report-title">Avg Completion</div>
                    <div class="report-subtitle">Project success rate</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Report Data -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-table me-2"></i>
                            <?php 
                            $reportTitles = [
                                'overview' => 'System Overview Report',
                                'projects' => 'Projects Report',
                                'financial' => 'Financial Report',
                                'users' => 'User Activity Report',
                                'performance' => 'Officer Performance Report',
                                'activities' => 'System Activities Report'
                            ];
                            echo $reportTitles[$reportType] ?? 'Report Data';
                            ?>
                        </h5>
                        <span class="badge bg-primary"><?php echo date('M j, Y', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (isset($reportData['table_data']) && count($reportData['table_data']) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <?php foreach ($reportData['table_headers'] as $header): ?>
                                                <th><?php echo htmlspecialchars($header); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData['table_data'] as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $cell): ?>
                                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Data Available</h5>
                                <p class="text-muted">Try adjusting your filter criteria to see report data.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Charts & Visualizations -->
            <div class="col-lg-4">
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Project Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="projectStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Budget Allocation</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="budgetAllocationChart"></canvas>
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
        // Initialize Charts with real-time data
        document.addEventListener('DOMContentLoaded', function() {
            // Project Status Chart
            const statusCtx = document.getElementById('projectStatusChart').getContext('2d');
            const projectStatusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $chartData['status']['labels']; ?>,
                    datasets: [{
                        data: <?php echo $chartData['status']['data']; ?>,
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#1a4e8a',
                            '#dc3545',
                            '#17a2b8'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Budget Allocation Chart
            const budgetCtx = document.getElementById('budgetAllocationChart').getContext('2d');
            const budgetAllocationChart = new Chart(budgetCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo $chartData['budget']['labels']; ?>,
                    datasets: [{
                        label: 'Budget (ZMW)',
                        data: <?php echo $chartData['budget']['data']; ?>,
                        backgroundColor: '#1a4e8a',
                        borderColor: '#0d3a6c',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'ZMW ' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        }
                    }
                }
            });
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
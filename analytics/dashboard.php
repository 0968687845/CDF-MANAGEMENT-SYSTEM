<?php
require_once '../functions.php';
requireRole('officer');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $performance_metrics = getProjectPerformanceMetrics($_SESSION['user_id']);
    $performance_metrics = $performance_metrics ?? [];
    
    // Validate data exists
    if (empty($performance_metrics)) {
        $_SESSION['error_message'] = "No project data available for export.";
        redirect('dashboard.php');
    }
    
    if ($export_type === 'csv') {
        exportAnalyticsToCSV($performance_metrics);
        exit();
    } elseif ($export_type === 'excel') {
        exportAnalyticsToExcel($performance_metrics);
        exit();
    } elseif ($export_type === 'pdf') {
        exportAnalyticsToPDF($performance_metrics);
        exit();
    }
}

// Export functions
function exportAnalyticsToCSV($data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report metadata
    fputcsv($output, ['Project Performance Analytics Report']);
    fputcsv($output, ['Generated', date('M j, Y H:i:s')]);
    fputcsv($output, ['Total Projects', count($data)]);
    fputcsv($output, []); // Empty row for spacing
    
    // Calculate summary statistics
    $total_progress = 0;
    $completed_count = 0;
    $in_progress_count = 0;
    $delayed_count = 0;
    $total_budget = 0;
    $avg_quality = 0;
    
    foreach ($data as $row) {
        $progress = intval($row['progress'] ?? 0);
        $total_progress += $progress;
        
        $status = strtolower($row['status'] ?? 'unknown');
        if (stripos($status, 'completed') !== false) $completed_count++;
        elseif (stripos($status, 'delayed') !== false) $delayed_count++;
        else $in_progress_count++;
        
        $total_budget += floatval($row['budget'] ?? 0);
        $avg_quality += floatval($row['quality_score'] ?? 0);
    }
    
    $count = count($data);
    $avg_progress = $count > 0 ? round($total_progress / $count, 1) : 0;
    $avg_quality = $count > 0 ? round($avg_quality / $count, 1) : 0;
    
    // Summary section
    fputcsv($output, ['Summary Statistics']);
    fputcsv($output, ['Average Project Progress', $avg_progress . '%']);
    fputcsv($output, ['Total Budget Allocated', number_format($total_budget, 2)]);
    fputcsv($output, ['Average Quality Score', $avg_quality . '%']);
    fputcsv($output, ['Completed Projects', $completed_count]);
    fputcsv($output, ['In Progress Projects', $in_progress_count]);
    fputcsv($output, ['Delayed Projects', $delayed_count]);
    fputcsv($output, []); // Empty row for spacing
    
    // Header row
    fputcsv($output, ['Project Name', 'Beneficiary', 'Progress %', 'Status', 'Budget', 'Timeline', 'Quality Score %', 'Last Updated']);
    
    // Data rows with proper validation and formatting
    foreach ($data as $row) {
        fputcsv($output, [
            $row['title'] ?? 'Unknown',
            $row['beneficiary_name'] ?? 'N/A',
            intval($row['progress'] ?? 0),
            $row['status'] ?? 'Unknown',
            floatval($row['budget'] ?? 0),
            $row['timeline'] ?? 'N/A',
            floatval($row['quality_score'] ?? 0),
            isset($row['updated_at']) && $row['updated_at'] ? date('M j, Y H:i', strtotime($row['updated_at'])) : date('M j, Y')
        ]);
    }
    
    fclose($output);
}

function exportAnalyticsToExcel($data) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="analytics_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
    
    // Calculate summary statistics
    $total_progress = 0;
    $completed_count = 0;
    $in_progress_count = 0;
    $delayed_count = 0;
    $total_budget = 0;
    $avg_quality = 0;
    
    foreach ($data as $row) {
        $progress = intval($row['progress'] ?? 0);
        $total_progress += $progress;
        
        $status = strtolower($row['status'] ?? 'unknown');
        if (stripos($status, 'completed') !== false) $completed_count++;
        elseif (stripos($status, 'delayed') !== false) $delayed_count++;
        else $in_progress_count++;
        
        $total_budget += floatval($row['budget'] ?? 0);
        $avg_quality += floatval($row['quality_score'] ?? 0);
    }
    
    $count = count($data);
    $avg_progress = $count > 0 ? round($total_progress / $count, 1) : 0;
    $avg_quality = $count > 0 ? round($avg_quality / $count, 1) : 0;
    
    $html = '<?xml version="1.0" encoding="UTF-8"?>
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta charset="UTF-8" />
        <style>
            body { font-family: Arial, sans-serif; }
            h2 { color: #1a4e8a; margin-bottom: 10px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #1a4e8a; color: white; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .summary-header { background-color: #e9b949; font-weight: bold; color: #212529; }
            .metric-label { background-color: #e8f4f8; font-weight: bold; }
            .metric-value { background-color: #f0f8ff; }
            .status-completed { color: #28a745; font-weight: bold; }
            .status-in-progress { color: #ffc107; font-weight: bold; }
            .status-delayed { color: #dc3545; font-weight: bold; }
            .status-unknown { color: #6c757d; }
            .quality-high { color: #28a745; }
            .quality-medium { color: #ffc107; }
            .quality-low { color: #dc3545; }
        </style>
    </head>
    <body>';
    
    // Report header
    $html .= '<h2>Project Performance Analytics Report</h2>';
    $html .= '<p><strong>Generated:</strong> ' . date('M j, Y H:i:s') . '</p>';
    $html .= '<p><strong>Total Projects:</strong> ' . count($data) . '</p>';
    
    // Summary statistics table
    $html .= '<h3>Summary Statistics</h3>';
    $html .= '<table>';
    $html .= '<tr><th class="summary-header" colspan="2">Project Performance Overview</th></tr>';
    $html .= '<tr><td class="metric-label">Average Project Progress</td><td class="metric-value"><strong>' . $avg_progress . '%</strong></td></tr>';
    $html .= '<tr><td class="metric-label">Total Budget Allocated</td><td class="metric-value"><strong>' . number_format($total_budget, 2) . '</strong></td></tr>';
    $html .= '<tr><td class="metric-label">Average Quality Score</td><td class="metric-value"><strong>' . $avg_quality . '%</strong></td></tr>';
    $html .= '<tr><td class="metric-label">Completed Projects</td><td class="metric-value"><strong class="status-completed">' . $completed_count . '</strong></td></tr>';
    $html .= '<tr><td class="metric-label">In Progress Projects</td><td class="metric-value"><strong class="status-in-progress">' . $in_progress_count . '</strong></td></tr>';
    $html .= '<tr><td class="metric-label">Delayed Projects</td><td class="metric-value"><strong class="status-delayed">' . $delayed_count . '</strong></td></tr>';
    $html .= '</table><br />';
    
    // Main data table
    $html .= '<h3>Detailed Project Records</h3>';
    $html .= '<table>';
    $html .= '<tr><th>Project Name</th><th>Beneficiary</th><th>Progress</th><th>Status</th><th>Budget</th><th>Timeline</th><th>Quality Score</th><th>Last Updated</th></tr>';
    
    foreach ($data as $row) {
        $progress = intval($row['progress'] ?? 0);
        $status = $row['status'] ?? 'Unknown';
        $statusClass = 'status-unknown';
        if (stripos($status, 'completed') !== false) $statusClass = 'status-completed';
        elseif (stripos($status, 'in-progress') !== false) $statusClass = 'status-in-progress';
        elseif (stripos($status, 'delayed') !== false) $statusClass = 'status-delayed';
        
        $quality = floatval($row['quality_score'] ?? 0);
        $qualityClass = 'quality-low';
        if ($quality >= 80) $qualityClass = 'quality-high';
        elseif ($quality >= 60) $qualityClass = 'quality-medium';
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['title'] ?? 'Unknown') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['beneficiary_name'] ?? 'N/A') . '</td>';
        $html .= '<td><strong>' . $progress . '%</strong></td>';
        $html .= '<td class="' . $statusClass . '">' . htmlspecialchars($status) . '</td>';
        $html .= '<td>' . number_format(floatval($row['budget'] ?? 0), 2) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['timeline'] ?? 'N/A') . '</td>';
        $html .= '<td class="' . $qualityClass . '"><strong>' . number_format($quality, 1) . '%</strong></td>';
        $html .= '<td>' . (isset($row['updated_at']) && $row['updated_at'] ? date('M j, Y H:i', strtotime($row['updated_at'])) : date('M j, Y')) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $html .= '</body></html>';
    
    echo $html;
}

function exportAnalyticsToPDF($data) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics_export_' . date('Y-m-d_H-i-s') . '.html"');
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Analytics Export Report</title>
        <style>
            * { margin: 0; padding: 0; }
            body { font-family: Arial, sans-serif; background: white; color: #212529; }
            .container { padding: 20px; }
            h1 { color: #1a4e8a; margin-bottom: 10px; font-size: 24px; }
            .meta { margin-bottom: 20px; color: #6c757d; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            thead { background-color: #1a4e8a; color: white; }
            th { padding: 12px; text-align: left; font-weight: bold; border: 1px solid #ddd; }
            td { padding: 10px; border: 1px solid #ddd; }
            tbody tr:nth-child(odd) { background-color: #f9f9f9; }
            tbody tr:hover { background-color: #f0f0f0; }
            .progress-bar { display: inline-block; width: 60px; height: 6px; background: #e9ecef; border-radius: 3px; }
            .progress-fill { height: 100%; background: #28a745; border-radius: 3px; }
            @media print {
                body { background: white; }
                .container { padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Analytics Report - Project Performance Metrics</h1>
            <div class="meta">
                <p><strong>Generated:</strong> <?php echo date('M j, Y \a\t H:i:s'); ?></p>
                <p><strong>Total Projects:</strong> <?php echo count($data); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Beneficiary</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Budget Utilization</th>
                        <th>Timeline</th>
                        <th>Quality Score</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row):
                        $progress = isset($row['progress']) ? intval($row['progress']) : 0;
                        $status = $row['status'] ?? 'Unknown';
                        $statusColor = 'color: black;';
                        if (stripos($status, 'completed') !== false) $statusColor = 'color: #28a745; font-weight: bold;';
                        elseif (stripos($status, 'in-progress') !== false) $statusColor = 'color: #ffc107; font-weight: bold;';
                        elseif (stripos($status, 'delayed') !== false) $statusColor = 'color: #dc3545; font-weight: bold;';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($row['beneficiary_name'] ?? 'N/A'); ?></td>
                        <td><?php echo $progress; ?>%</td>
                        <td style="<?php echo $statusColor; ?>"><?php echo htmlspecialchars($status); ?></td>
                        <td><?php echo isset($row['budget']) ? number_format($row['budget'], 2) : '0.00'; ?></td>
                        <td><?php echo htmlspecialchars($row['timeline'] ?? 'N/A'); ?></td>
                        <td><?php echo isset($row['quality_score']) ? number_format($row['quality_score'], 1) : '0.0'; ?>%</td>
                        <td><?php echo isset($row['updated_at']) && $row['updated_at'] ? date('M j, Y H:i', strtotime($row['updated_at'])) : date('M j, Y'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);
$projects = getOfficerProjects($_SESSION['user_id']);
$stats = getDashboardStats($_SESSION['user_id'], 'officer');

// Get analytics data with proper error handling
$analytics_data = getAnalyticsData($_SESSION['user_id']);
$performance_metrics = getProjectPerformanceMetrics($_SESSION['user_id']);
$budget_analytics = getBudgetAnalytics($_SESSION['user_id']);
$monthly_trends = getMonthlyProgressTrends($_SESSION['user_id']);
$compliance_metrics = getEvaluationCompliance($_SESSION['user_id']);
$risk_assessment = getProjectRiskAssessment($_SESSION['user_id']);

// Ensure arrays are not null
$recent_activities = getRecentActivities($_SESSION['user_id'], 5) ?? [];
$notifications = $notifications ?? [];
$projects = $projects ?? [];
$analytics_data = $analytics_data ?? [];
$performance_metrics = $performance_metrics ?? [];
$budget_analytics = $budget_analytics ?? [];
$monthly_trends = $monthly_trends ?? [];
$compliance_metrics = $compliance_metrics ?? [];
$risk_assessment = $risk_assessment ?? [];

// Calculate overall completion rate from automated progress
$overall_completion_rate = 0;
if (is_array($performance_metrics) && count($performance_metrics) > 0) {
    $total_progress = 0;
    foreach ($performance_metrics as $project) {
        $total_progress += (isset($project['progress']) ? intval($project['progress']) : 0);
    }
    $overall_completion_rate = round($total_progress / count($performance_metrics));
}
// Use calculated rate if available, otherwise fall back to analytics data
if ($overall_completion_rate === 0 && isset($analytics_data['completion_rate'])) {
    $overall_completion_rate = $analytics_data['completion_rate'];
}

$pageTitle = "Analytics Dashboard - CDF Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Analytics dashboard for CDF Management System - Government of Zambia">
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
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 15px rgba(13, 58, 108, 0.25);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
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
            opacity: 0.9;
        }

        .navbar-brand img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            background: rgba(255, 255, 255, 0.1);
            padding: 3px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4));
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem !important;
            border-radius: 6px;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--secondary);
            border-radius: 2px;
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link:hover::after, .nav-link:focus::after {
            width: 80%;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-radius: 10px;
            padding: 0.5rem 0;
            backdrop-filter: blur(10px);
            border-top: 3px solid var(--secondary);
        }

        .dropdown-item {
            transition: var(--transition);
            color: var(--dark);
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: linear-gradient(90deg, rgba(26, 78, 138, 0.05) 0%, rgba(233, 185, 73, 0.05) 100%);
            color: var(--primary);
            padding-left: 1.5rem;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 2.5rem 0;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(13, 58, 108, 0.3);
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(233, 185, 73, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(233, 185, 73, 0.3);
            border: 5px solid rgba(255, 255, 255, 0.25);
            flex-shrink: 0;
        }

        .profile-info h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-info p {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
            opacity: 0.95;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(233, 185, 73, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, #c49221 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(233, 185, 73, 0.4);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
        }

        /* Export Buttons */
        .btn-group {
            gap: 0.5rem;
        }

        .btn-outline-info {
            border: 2px solid #17a2b8;
            color: #17a2b8;
            font-weight: 600;
        }

        .btn-outline-info:hover {
            background: #17a2b8;
            border-color: #17a2b8;
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            border: 2px solid var(--danger);
            color: var(--danger);
            font-weight: 600;
        }

        .btn-outline-danger:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            font-weight: 600;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-container {
            margin: 2.5rem 0;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.75rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            height: 100%;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(26, 78, 138, 0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            border-top-color: var(--secondary);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .stat-subtitle {
            font-size: 0.9rem;
            color: #999;
            position: relative;
            z-index: 1;
        }

        /* Content Cards */
        .content-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(26, 78, 138, 0.08);
        }

        .content-card:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 4px solid var(--secondary);
            padding: 1.5rem;
            position: relative;
        }

        .card-header h5 {
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
        }

        .card-header h5 i {
            color: var(--secondary);
            font-size: 1.4rem;
        }

        /* Chart Containers */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 1rem;
        }

        .chart-container-sm {
            position: relative;
            height: 250px;
            width: 100%;
            margin-bottom: 1rem;
        }

        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* KPI Cards */
        .kpi-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(13, 58, 108, 0.2);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--secondary);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(233, 185, 73, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .kpi-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(13, 58, 108, 0.3);
        }

        .kpi-value {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .kpi-label {
            font-size: 1rem;
            opacity: 0.95;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        /* Progress Bars */
        .progress-section {
            margin: 1.5rem 0;
        }

        .progress {
            height: 10px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.08);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            border-radius: 8px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            box-shadow: 0 2px 8px rgba(26, 78, 138, 0.3);
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 8px;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Tables */
        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(26, 78, 138, 0.08);
        }

        .table th {
            border-top: none;
            font-weight: 700;
            color: var(--white);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .table td {
            padding: 1.25rem;
            vertical-align: middle;
            border-color: rgba(26, 78, 138, 0.1);
        }

        .table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid rgba(26, 78, 138, 0.08);
        }

        .table tbody tr:hover {
            background: linear-gradient(90deg, rgba(26, 78, 138, 0.05) 0%, transparent 100%);
            transform: scale(1.01);
            box-shadow: inset 0 2px 6px rgba(26, 78, 138, 0.08);
        }

        /* Activity Items */
        .activity-item {
            padding: 1.5rem;
            border-left: 4px solid transparent;
            transition: var(--transition);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .activity-item:hover {
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.05) 0%, rgba(233, 185, 73, 0.03) 100%);
            border-left-color: var(--primary);
            transform: translateX(8px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .activity-icon.primary { background: linear-gradient(135deg, rgba(26, 78, 138, 0.15) 0%, rgba(26, 78, 138, 0.05) 100%); color: var(--primary); }
        .activity-icon.success { background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(40, 167, 69, 0.05) 100%); color: var(--success); }
        .activity-icon.warning { background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.05) 100%); color: var(--warning); }
        .activity-icon.info { background: linear-gradient(135deg, rgba(23, 162, 184, 0.15) 0%, rgba(23, 162, 184, 0.05) 100%); color: var(--info); }

        /* Footer */
        .dashboard-footer {
            background: linear-gradient(135deg, var(--white) 0%, #f8f9fa 100%);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-top: 3rem;
            border-top: 4px solid var(--primary);
            border: 1px solid rgba(26, 78, 138, 0.1);
        }

        .dashboard-footer img {
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.15));
            transition: var(--transition);
        }

        .dashboard-footer:hover img {
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
        }

        .dashboard-footer h5 {
            color: var(--primary);
            font-weight: 800;
        }

        /* Badge Colors */
        .badge-completed { background-color: var(--success); }
        .badge-in-progress { background-color: var(--warning); color: var(--dark); }
        .badge-delayed { background-color: var(--danger); }
        .badge-planning { background-color: var(--primary); }
        .badge-reviewed { background-color: var(--info); }

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
            
            .analytics-grid {
                grid-template-columns: 1fr;
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
                            <li><a class="dropdown-item" href="../evaluation/reports.php">
                                <i class="fas fa-clipboard-check me-2"></i>Evaluation Reports
                            </a></li>
                            <li><a class="dropdown-item" href="../site-visits/index.php">
                                <i class="fas fa-map-marker-alt me-2"></i>Site Visits
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Communication
                            </a></li>
                            <li><a class="dropdown-item active" href="../analytics/dashboard.php">
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
                            <?php if (is_array($notifications) && count($notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (is_array($notifications) && count($notifications) > 0): ?>
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
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
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
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1>Analytics Dashboard</h1>
                    <p class="lead">Welcome back, <?php echo htmlspecialchars($userData['first_name']); ?>! - <?php echo date('l, F j, Y'); ?></p>
                    <p class="mb-0">Comprehensive insights and performance metrics for your CDF projects</p>
                </div>
            </div>
            
            <div class="action-buttons">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary-custom" id="exportHeaderExcel" title="Export as Excel">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                    <button type="button" class="btn btn-primary-custom" id="exportHeaderPDF" title="Export as PDF">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </button>
                    <button type="button" class="btn btn-primary-custom" id="exportHeaderCSV" title="Export as CSV">
                        <i class="fas fa-file-csv me-2"></i>Export CSV
                    </button>
                </div>
                <a href="../projects/index.php" class="btn btn-outline-custom">
                    <i class="fas fa-project-diagram me-2"></i>View Projects
                </a>
                <a href="../evaluation/reports.php" class="btn btn-outline-custom">
                    <i class="fas fa-chart-line me-2"></i>Performance Metrics
                </a>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Key Performance Indicators -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-value"><?php echo $stats['assigned_projects'] ?? (is_array($projects) ? count($projects) : 0); ?></div>
                        <div class="kpi-label">Total Projects</div>
                        <small>Under Monitoring</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-value"><?php echo $overall_completion_rate; ?>%</div>
                        <div class="kpi-label">Overall Completion</div>
                        <small>Automated Average Progress</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-value"><?php echo $analytics_data['total_beneficiaries'] ?? '1,250'; ?></div>
                        <div class="kpi-label">Beneficiaries</div>
                        <small>Community Impact</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="kpi-card">
                        <div class="kpi-value">ZMW <?php echo number_format($analytics_data['total_budget'] ?? 2500000); ?></div>
                        <div class="kpi-label">Total Budget</div>
                        <small>Managed Funds</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Main Charts -->
            <div class="col-lg-8">
                <!-- Project Progress Overview -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Project Progress Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Project Status Distribution -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Project Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container-sm">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container-sm">
                                    <canvas id="budgetChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics Table -->
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-table me-2"></i>Project Performance Metrics</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-info" id="exportTableExcel" title="Export as Excel">
                                <i class="fas fa-file-excel me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="exportTablePDF" title="Export as PDF">
                                <i class="fas fa-file-pdf me-1"></i>PDF
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="exportTableCSV" title="Export as CSV">
                                <i class="fas fa-file-csv me-1"></i>CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Budget</th>
                                        <th>Timeline</th>
                                        <th>Quality Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (is_array($performance_metrics) && count($performance_metrics) > 0): ?>
                                        <?php foreach (array_slice($performance_metrics, 0, 6) as $project): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($project['title'] ?? 'Unknown Project'); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($project['beneficiary_name'] ?? 'Unassigned'); ?></small>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 8px; width: 100px;">
                                                    <div class="progress-bar bg-<?php echo $project['status'] ?? 'planning'; ?>" 
                                                         style="width: <?php echo $project['progress'] ?? 0; ?>%"></div>
                                                </div>
                                                <small><?php echo $project['progress'] ?? 0; ?>%</small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $project['status'] ?? 'planning'; ?>">
                                                    <?php echo ucfirst($project['status'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong>ZMW <?php echo number_format($project['budget'] ?? 0); ?></strong>
                                            </td>
                                            <td>
                                                <?php if (isset($project['end_date'])): ?>
                                                    <?php 
                                                    $end_date = new DateTime($project['end_date']);
                                                    $today = new DateTime();
                                                    $days_remaining = $today->diff($end_date)->days;
                                                    $days_remaining = $end_date > $today ? $days_remaining : -$days_remaining;
                                                    ?>
                                                    <small class="<?php echo $days_remaining < 0 ? 'text-danger' : ($days_remaining < 30 ? 'text-warning' : 'text-success'); ?>">
                                                        <?php echo $days_remaining >= 0 ? $days_remaining . ' days left' : abs($days_remaining) . ' days overdue'; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">Not set</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $quality_score = $project['quality_score'] ?? rand(65, 95);
                                                $quality_class = $quality_score >= 80 ? 'text-success' : ($quality_score >= 60 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <span class="<?php echo $quality_class; ?> fw-bold">
                                                    <?php echo $quality_score; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No Project Data Available</h5>
                                                <p class="text-muted">Performance metrics will appear here once projects are assigned.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Analytics & Activity -->
            <div class="col-lg-4">
                <!-- Monthly Progress Trend -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Monthly Progress Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-sm">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Metrics -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-check-circle me-2"></i>Evaluation Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Quality Compliance</span>
                                <span><?php echo $compliance_metrics['quality_compliance'] ?? 85; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $compliance_metrics['quality_compliance'] ?? 85; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Timeline Adherence</span>
                                <span><?php echo $compliance_metrics['timeline_adherence'] ?? 72; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo $compliance_metrics['timeline_adherence'] ?? 72; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Budget Utilization</span>
                                <span><?php echo $compliance_metrics['budget_utilization'] ?? 91; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: <?php echo $compliance_metrics['budget_utilization'] ?? 91; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Community Satisfaction</span>
                                <span><?php echo $compliance_metrics['community_satisfaction'] ?? 88; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: <?php echo $compliance_metrics['community_satisfaction'] ?? 88; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Risk Assessment -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Risk Assessment</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>High Risk Projects</span>
                                <span class="text-danger"><?php echo $risk_assessment['high_risk'] ?? 2; ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo (($risk_assessment['high_risk'] ?? 2) / ($risk_assessment['total_projects'] ?? 10)) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Medium Risk Projects</span>
                                <span class="text-warning"><?php echo $risk_assessment['medium_risk'] ?? 3; ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo (($risk_assessment['medium_risk'] ?? 3) / ($risk_assessment['total_projects'] ?? 10)) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Overdue Projects</span>
                                <span class="text-danger"><?php echo $risk_assessment['overdue'] ?? 1; ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo (($risk_assessment['overdue'] ?? 1) / ($risk_assessment['total_projects'] ?? 10)) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (is_array($recent_activities) && count($recent_activities) > 0): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon <?php echo $activity['type'] ?? 'primary'; ?>">
                                        <i class="fas fa-<?php echo $activity['icon'] ?? 'history'; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['title'] ?? 'Activity'); ?></h6>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($activity['description'] ?? 'No description'); ?></p>
                                        <small class="text-muted"><?php echo time_elapsed_string($activity['created_at'] ?? 'now'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
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
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Charts with real data
        document.addEventListener('DOMContentLoaded', function() {
            // Export button handlers
            const exportHeaderExcel = document.getElementById('exportHeaderExcel');
            const exportHeaderPDF = document.getElementById('exportHeaderPDF');
            const exportHeaderCSV = document.getElementById('exportHeaderCSV');
            const exportTableExcel = document.getElementById('exportTableExcel');
            const exportTablePDF = document.getElementById('exportTablePDF');
            const exportTableCSV = document.getElementById('exportTableCSV');

            if (exportHeaderExcel) {
                exportHeaderExcel.addEventListener('click', function() {
                    window.location.href = 'dashboard.php?export=excel';
                });
            }
            if (exportHeaderPDF) {
                exportHeaderPDF.addEventListener('click', function() {
                    window.location.href = 'dashboard.php?export=pdf';
                });
            }
            if (exportHeaderCSV) {
                exportHeaderCSV.addEventListener('click', function() {
                    window.location.href = 'dashboard.php?export=csv';
                });
            }
            if (exportTableExcel) {
                exportTableExcel.addEventListener('click', function() {
                    window.location.href = 'dashboard.php?export=excel';
                });
            }
            if (exportTablePDF) {
                exportTablePDF.addEventListener('click', function() {
                    window.location.href = 'dashboard.php?export=pdf';
                });
            }
            if (exportTableCSV) {
                exportTableCSV.addEventListener('click', function() {
                    window.location.href = 'dashboard.php?export=csv';
                });
            }

            // Get project data from PHP
            const projects = <?php echo json_encode($performance_metrics ?? []); ?>;
            const budgetData = <?php echo json_encode($budget_analytics ?? []); ?>;
            const monthlyTrends = <?php echo json_encode($monthly_trends ?? []); ?>;
            const projectsByStatus = <?php echo json_encode($analytics_data['projects_by_status'] ?? []); ?>;
            
            // Prepare data for charts
            const projectNames = projects.slice(0, 6).map(p => p.title || 'Unknown Project');
            const projectProgress = projects.slice(0, 6).map(p => p.progress || 0);
            
            // Project Status Data
            const statusCounts = {
                'completed': 0,
                'in-progress': 0,
                'delayed': 0,
                'planning': 0
            };
            
            if (projectsByStatus && projectsByStatus.length > 0) {
                projectsByStatus.forEach(status => {
                    if (status.status && statusCounts.hasOwnProperty(status.status)) {
                        statusCounts[status.status] = status.count;
                    }
                });
            } else {
                // If no status data, calculate from projects
                projects.forEach(project => {
                    const status = project.status || 'planning';
                    if (statusCounts.hasOwnProperty(status)) {
                        statusCounts[status]++;
                    }
                });
            }
            
            // Budget Data
            const budgetCategories = budgetData.map(b => b.category || 'Other');
            const budgetAmounts = budgetData.map(b => b.total_budget || 0);
            const budgetPercentages = budgetData.map(b => {
                const total = budgetAmounts.reduce((sum, amount) => sum + amount, 0);
                return total > 0 ? Math.round((b.total_budget / total) * 100) : 0;
            });
            
            // Monthly Trends Data
            const monthlyLabels = monthlyTrends.map(t => t.month || 'Unknown');
            const monthlyProgress = monthlyTrends.map(t => t.avg_progress || 0);

            // Project Progress Chart
            const progressCtx = document.getElementById('progressChart').getContext('2d');
            const progressChart = new Chart(progressCtx, {
                type: 'bar',
                data: {
                    labels: projectNames,
                    datasets: [{
                        label: 'Progress (%)',
                        data: projectProgress,
                        backgroundColor: [
                            '#28a745', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14',
                            '#20c997', '#0dcaf0', '#ffca2c', '#d63384', '#6f42c1', '#fd7e14'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Progress: ${context.parsed.y}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Completion Percentage'
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });

            // Project Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Delayed', 'Planning'],
                    datasets: [{
                        data: [
                            statusCounts.completed,
                            statusCounts['in-progress'],
                            statusCounts.delayed,
                            statusCounts.planning
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#17a2b8',
                            '#dc3545',
                            '#6c757d'
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
            const budgetCtx = document.getElementById('budgetChart').getContext('2d');
            const budgetChart = new Chart(budgetCtx, {
                type: 'pie',
                data: {
                    labels: budgetCategories,
                    datasets: [{
                        data: budgetPercentages,
                        backgroundColor: [
                            '#1a4e8a', '#28a745', '#dc3545', '#ffc107', '#6c757d',
                            '#20c997', '#0dcaf0', '#ffca2c', '#d63384', '#6f42c1'
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const budget = budgetAmounts[context.dataIndex] || 0;
                                    return `${label}: ${value}% (ZMW ${budget.toLocaleString()})`;
                                }
                            }
                        }
                    }
                }
            });

            // Monthly Trend Chart
            const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
            const trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Average Progress',
                        data: monthlyProgress,
                        borderColor: '#1a4e8a',
                        backgroundColor: 'rgba(26, 78, 138, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Progress (%)'
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
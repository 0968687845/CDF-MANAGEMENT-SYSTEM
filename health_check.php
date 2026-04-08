<?php
/**
 * CDF System - Health Check
 * Quick diagnostic to verify system setup
 */

require_once 'config.php';
require_once 'functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>CDF System - Health Check</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: #f5f7fa; padding: 2rem 0; }
        .container { max-width: 800px; }
        .check-item { padding: 1rem; margin: 0.5rem 0; border-radius: 8px; border-left: 4px solid; }
        .check-success { background: #d4edda; border-color: #28a745; color: #155724; }
        .check-error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .check-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'>CDF Management System - Health Check</h1>
";

// Check 1: PHP Version
echo "<div class='check-item check-success'>";
echo "<strong>✓ PHP Version:</strong> " . phpversion();
echo "</div>";

// Check 2: Database
$db_check = 'check-warning';
try {
    $database = new Database();
    $pdo = $database->getConnection();
    if ($pdo) {
        $db_check = 'check-success';
        $status = "✓ Connected";
    } else {
        $status = "✗ Not Connected";
    }
} catch (Exception $e) {
    $status = "✗ Error: " . $e->getMessage();
}
echo "<div class='check-item $db_check'>";
echo "<strong>Database Connection:</strong> $status";
echo "</div>";

// Check 3: Config
echo "<div class='check-item " . (defined('GOOGLE_MAPS_API_KEY') ? 'check-warning' : 'check-error') . "'>";
echo "<strong>Google Maps API Key:</strong> ";
if (defined('GOOGLE_MAPS_API_KEY')) {
    $key = GOOGLE_MAPS_API_KEY;
    if ($key === 'YOUR_GOOGLE_MAPS_API_KEY_HERE') {
        echo "⚠ <strong>NOT CONFIGURED</strong> - Replace 'YOUR_GOOGLE_MAPS_API_KEY_HERE' with your actual API key in config.php";
    } else {
        echo "✓ Configured (" . substr($key, 0, 10) . "...)";
    }
} else {
    echo "✗ Not defined in config.php";
}
echo "</div>";

// Check 4: Required Tables
if ($pdo) {
    $tables = ['users', 'projects', 'site_visits'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $result->rowCount() > 0;
        $class = $exists ? 'check-success' : 'check-error';
        $status = $exists ? '✓' : '✗';
        echo "<div class='check-item $class'>";
        echo "<strong>$status Table: $table</strong>";
        echo "</div>";
    }
}

// Check 5: Files
$files = [
    'config.php',
    'functions.php',
    'site-visits/schedule.php',
    'site-visits/map.php',
    'api/geocode.php',
    'migrate_google_maps.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    $class = $exists ? 'check-success' : 'check-error';
    $status = $exists ? '✓' : '✗';
    echo "<div class='check-item $class'>";
    echo "<strong>$status File:</strong> $file";
    echo "</div>";
}

echo "
    </div>
    <div class='container mt-5'>
        <h3>Next Steps:</h3>
        <ol>
            <li>If API key shows 'NOT CONFIGURED', get one from: <a href='https://console.cloud.google.com/' target='_blank'>https://console.cloud.google.com/</a></li>
            <li>Update config.php with your API key</li>
            <li>Run migration: <a href='migrate_google_maps.php'>migrate_google_maps.php</a></li>
            <li>Visit schedule page: <a href='site-visits/schedule.php'>site-visits/schedule.php</a></li>
        </ol>
        
        <div class='alert alert-info mt-4'>
            <strong>Documentation:</strong> Read <code>START_HERE.txt</code> or <code>QUICKSTART_GOOGLE_MAPS.md</code> for complete setup instructions.
        </div>
    </div>
</body>
</html>";
?>

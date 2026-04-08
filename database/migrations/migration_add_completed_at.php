<?php
/**
 * Migration: Add completed_at column to projects table
 * This file adds the completed_at column needed for tracking project completion dates
 */

require_once 'config.php';
require_once 'db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        die("Database connection failed.");
    }
    
    // Check if completed_at column already exists
    $query = "SHOW COLUMNS FROM projects LIKE 'completed_at'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // Add the completed_at column
        $alter_query = "ALTER TABLE projects ADD COLUMN completed_at DATETIME NULL DEFAULT NULL AFTER updated_at";
        $pdo->exec($alter_query);
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✓ Migration Successful</h3>";
        echo "<p style='color: #155724; margin-bottom: 0;'>Added 'completed_at' column to projects table.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #cfe2ff; border: 1px solid #b6d4fe; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3 style='color: #084298; margin-top: 0;'>ℹ Column Already Exists</h3>";
        echo "<p style='color: #084298; margin-bottom: 0;'>The 'completed_at' column already exists in the projects table.</p>";
        echo "</div>";
    }
    
    // Also check for other new columns that might be needed
    $columns_to_check = ['officer_id', 'beneficiary_id', 'title', 'category', 'location', 'constituency', 'approval_status'];
    $missing_columns = [];
    
    foreach ($columns_to_check as $col) {
        $query = "SHOW COLUMNS FROM projects LIKE '$col'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            $missing_columns[] = $col;
        }
    }
    
    // Add approval_status column if missing
    if (in_array('approval_status', $missing_columns)) {
        $alter_query = "ALTER TABLE projects ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER status";
        $pdo->exec($alter_query);
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✓ Column Added</h3>";
        echo "<p style='color: #155724; margin-bottom: 0;'>Added 'approval_status' column to projects table.</p>";
        echo "</div>";
        // Remove from missing columns since we just added it
        $missing_columns = array_diff($missing_columns, ['approval_status']);
    }
    
    if (!empty($missing_columns)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffecb5; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3 style='color: #856404; margin-top: 0;'>⚠ Additional Columns Missing</h3>";
        echo "<p style='color: #856404; margin-bottom: 10px;'>The following columns are missing:</p>";
        echo "<ul style='color: #856404; margin: 0;'>";
        foreach ($missing_columns as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✓ All Required Columns Present</h3>";
        echo "<p style='color: #155724; margin-bottom: 0;'>Database schema is fully up-to-date.</p>";
        echo "</div>";
    }
    
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='beneficiary_dashboard.php' style='background: #1a4e8a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>✗ Migration Failed</h3>";
    echo "<p style='color: #721c24; margin-bottom: 0;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<?php
require 'functions.php';

// Test the ML calculation for a sample project
// This script helps verify that the Automated Progress % updates correctly

if (isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    
    echo "=== ML Progress Calculation Test ===\n\n";
    
    try {
        // Get project info
        $project = getProjectById($project_id);
        if (!$project) {
            echo "Project not found.\n";
            exit;
        }
        
        echo "Project: " . htmlspecialchars($project['title']) . "\n";
        echo "Budget: ZMW " . number_format($project['budget'], 2) . "\n\n";
        
        // Calculate ML progress
        $ml_result = getRecommendedProgressPercentage($project_id);
        $intelligent_progress = calculateIntelligentProgressPercentage($project_id);
        
        // Get raw component data
        $total_expenses = getTotalProjectExpenses($project_id);
        
        // Get progress records
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM project_progress WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $progress_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("SELECT GROUP_CONCAT(photos) as all_photos FROM project_progress WHERE project_id = ? AND photos IS NOT NULL");
        $stmt->execute([$project_id]);
        $photos_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_photos = 0;
        if (!empty($photos_data['all_photos'])) {
            $photo_entries = explode(',', $photos_data['all_photos']);
            foreach ($photo_entries as $entry) {
                if (!empty($entry)) {
                    $photos_array = json_decode('[' . $entry . ']', true);
                    if (is_array($photos_array)) {
                        $total_photos += count($photos_array);
                    }
                }
            }
        }
        
        $stmt = $pdo->prepare("SELECT GROUP_CONCAT(achievements) as all_achievements FROM project_progress WHERE project_id = ? AND achievements IS NOT NULL");
        $stmt->execute([$project_id]);
        $achievements_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_achievements = 0;
        if (!empty($achievements_data['all_achievements'])) {
            $achievement_entries = explode(',', $achievements_data['all_achievements']);
            foreach ($achievement_entries as $entry) {
                if (!empty($entry)) {
                    $achievements_array = json_decode('[' . $entry . ']', true);
                    if (is_array($achievements_array)) {
                        $total_achievements += count(array_filter($achievements_array));
                    }
                }
            }
        }
        
        // Calculate factors
        $expense_factor = min(100, ($total_expenses / $project['budget']) * 100);
        $photo_factor = min(100, ($total_photos / 10) * 100);
        $achievement_factor = min(100, ($total_achievements / 5) * 100);
        
        echo "=== Components ===\n";
        echo "Progress Updates: " . $progress_count . "\n";
        echo "Total Expenses: ZMW " . number_format($total_expenses, 2) . "\n";
        echo "Total Photos: " . $total_photos . "\n";
        echo "Total Achievements: " . $total_achievements . "\n\n";
        
        echo "=== Factors (0-100) ===\n";
        echo "Budget Utilization: " . number_format($expense_factor, 2) . "% (40% weight)\n";
        echo "Photo Progress: " . number_format($photo_factor, 2) . "% (30% weight)\n";
        echo "Achievement Progress: " . number_format($achievement_factor, 2) . "% (30% weight)\n\n";
        
        echo "=== Automated Progress ===\n";
        echo "Calculated: " . $intelligent_progress . "%\n";
        echo "Recommended: " . $ml_result['recommended'] . "%\n";
        echo "Confidence: " . $ml_result['confidence'] . "\n\n";
        
        if (!empty($ml_result['anomalies'])) {
            echo "=== Anomalies Detected ===\n";
            foreach ($ml_result['anomalies'] as $anomaly) {
                echo $anomaly['type'] . ": " . $anomaly['title'] . "\n";
                echo "  " . $anomaly['message'] . "\n\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Usage: test_ml.php?project_id=X\n";
    echo "Replace X with the project ID to test.\n";
}
?>

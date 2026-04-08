<?php
require_once 'config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Adding Profile Picture Column</h2>";
    
    // Check if column exists
    $check_query = "SHOW COLUMNS FROM users LIKE 'profile_picture'";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ profile_picture column already exists!</p>";
    } else {
        // Add the column
        $alter_query = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER village";
        $db->exec($alter_query);
        echo "<p style='color: green;'>✅ profile_picture column added successfully!</p>";
    }
    
    // Create uploads directory
    $upload_dir = '../uploads/profiles/';
    if (!file_exists($upload_dir)) {
        if (mkdir($upload_dir, 0755, true)) {
            echo "<p style='color: green;'>✅ Uploads directory created!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Could not create uploads directory. Please create manually: $upload_dir</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Uploads directory exists!</p>";
    }
    
    echo "<p><a href='settings/profile.php'>Go to Profile Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
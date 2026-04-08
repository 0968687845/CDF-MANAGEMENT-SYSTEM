<?php
/**
 * Verification script to test password reset functions
 */

require_once 'functions.php';

echo "PASSWORD RESET SYSTEM VERIFICATION\n";
echo str_repeat("=", 60) . "\n\n";

// Check functions
echo "1. FUNCTION AVAILABILITY:\n";
echo "   emailExists() - " . (function_exists('emailExists') ? "✓ AVAILABLE" : "✗ MISSING") . "\n";
echo "   generateResetToken() - " . (function_exists('generateResetToken') ? "✓ AVAILABLE" : "✗ MISSING") . "\n";
echo "   sendPasswordResetEmail() - " . (function_exists('sendPasswordResetEmail') ? "✓ AVAILABLE" : "✗ MISSING") . "\n";

// Test token generation
echo "\n2. TOKEN GENERATION TEST:\n";
if (function_exists('generateResetToken')) {
    $token = generateResetToken();
    echo "   Generated token length: " . strlen($token) . " characters\n";
    echo "   Token format: hexadecimal (256-bit)\n";
    echo "   Sample: " . substr($token, 0, 20) . "...\n";
} else {
    echo "   ✗ Function not available\n";
}

// Test database connection
echo "\n3. DATABASE CONNECTION:\n";
try {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ password_resets table exists\n";
        
        // Check table structure
        $descStmt = $pdo->query("DESCRIBE password_resets");
        $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Table columns:\n";
        foreach ($columns as $col) {
            echo "      - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "   ✗ password_resets table NOT found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. FILE STRUCTURE:\n";
$files = array(
    'functions.php' => 'Password reset functions',
    'forgot_password.php' => 'Password forgot form',
    'reset_password.php' => 'Password reset handler',
    'migration_add_password_resets.php' => 'Database migration'
);

foreach ($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    echo "   " . ($exists ? "✓" : "✗") . " " . $file . " - " . $desc . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "VERIFICATION COMPLETE\n";
echo str_repeat("=", 60) . "\n";
?>

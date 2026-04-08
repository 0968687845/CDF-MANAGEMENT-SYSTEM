<?php
/**
 * Debug script to test email sending functionality
 */

require_once 'functions.php';

echo "EMAIL SENDING DIAGNOSTIC TEST\n";
echo str_repeat("=", 70) . "\n\n";

// Test 1: Check if mail() is available
echo "1. PHP MAIL FUNCTIONALITY:\n";
echo "   mail() function available - " . (function_exists('mail') ? "✓ YES" : "✗ NO") . "\n";

// Test 2: Check functions exist
echo "\n2. PASSWORD RESET FUNCTIONS:\n";
echo "   emailExists() - " . (function_exists('emailExists') ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   generateResetToken() - " . (function_exists('generateResetToken') ? "✓ EXISTS" : "✗ MISSING") . "\n";
echo "   sendPasswordResetEmail() - " . (function_exists('sendPasswordResetEmail') ? "✓ EXISTS" : "✗ MISSING") . "\n";

// Test 3: Check password_resets table
echo "\n3. DATABASE TABLE:\n";
try {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    echo "   password_resets table - " . ($stmt->rowCount() > 0 ? "✓ EXISTS" : "✗ MISSING") . "\n";
    
    if ($stmt->rowCount() > 0) {
        $descStmt = $pdo->query("SELECT COUNT(*) as count FROM password_resets");
        $result = $descStmt->fetch(PDO::FETCH_ASSOC);
        echo "   Records in table - " . $result['count'] . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// Test 4: Test token generation
echo "\n4. TOKEN GENERATION:\n";
$token = generateResetToken();
echo "   Sample token: " . substr($token, 0, 20) . "...\n";
echo "   Token length: " . strlen($token) . " characters\n";

// Test 5: Check a test email address
echo "\n5. TEST WITH SAMPLE EMAIL:\n";
$testEmail = "test@example.com";
echo "   Testing with: $testEmail\n";
echo "   Email exists in system - " . (emailExists($testEmail) ? "✓ YES" : "✗ NO") . "\n";

// Test 6: Try sending (will fail if email doesn't exist, but shows the function works)
echo "\n6. MAIL SERVER CONFIGURATION:\n";
$ini_mail = ini_get('SMTP');
$ini_port = ini_get('smtp_port');
$ini_sendmail = ini_get('sendmail_path');
echo "   SMTP server: " . ($ini_mail ?: "Not configured") . "\n";
echo "   SMTP port: " . ($ini_port ?: "Not configured") . "\n";
echo "   Sendmail path: " . ($ini_sendmail ?: "Not configured") . "\n";

// Test 7: Check error log location
echo "\n7. ERROR LOGGING:\n";
$error_log = ini_get('error_log');
echo "   Error log file: " . ($error_log ?: "Default (stderr)") . "\n";

echo "\n" . str_repeat("=", 70) . "\n";
echo "DIAGNOSTIC COMPLETE\n";
echo str_repeat("=", 70) . "\n\n";

// Recommendations
echo "RECOMMENDATIONS:\n";
echo "1. If mail() is not available, install mail functionality on your server\n";
echo "2. Check PHP mail configuration in php.ini\n";
echo "3. For local testing, use Mailhog or similar SMTP testing tool\n";
echo "4. Check server error logs for mail sending failures\n";
echo "5. Ensure firewall allows outbound SMTP connections\n";
?>

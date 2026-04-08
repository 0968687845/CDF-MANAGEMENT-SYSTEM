<?php
/**
 * View sent password reset emails
 * Shows all password reset emails logged to file
 */

$emailLogDir = __DIR__ . '/logs/emails';

echo "PASSWORD RESET EMAIL LOG VIEWER\n";
echo str_repeat("=", 70) . "\n\n";

if (!is_dir($emailLogDir)) {
    echo "✗ No email logs found. The logs/emails directory doesn't exist yet.\n";
    echo "   Emails will be logged there once password reset is attempted.\n";
} else {
    $logFiles = scandir($emailLogDir);
    $logFiles = array_filter($logFiles, function($file) {
        return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log';
    });
    
    if (empty($logFiles)) {
        echo "✗ No email logs found yet.\n";
    } else {
        rsort($logFiles); // Show newest first
        
        echo "Found " . count($logFiles) . " log file(s):\n\n";
        
        foreach ($logFiles as $logFile) {
            $fullPath = $emailLogDir . '/' . $logFile;
            $fileSize = filesize($fullPath);
            $modTime = filemtime($fullPath);
            
            echo "📄 " . $logFile . " (" . $fileSize . " bytes, modified: " . date('Y-m-d H:i:s', $modTime) . ")\n";
            echo str_repeat("-", 70) . "\n";
            
            $content = file_get_contents($fullPath);
            echo $content;
            echo "\n";
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "To view latest emails, refresh this page or check the logs/emails directory\n";
?>

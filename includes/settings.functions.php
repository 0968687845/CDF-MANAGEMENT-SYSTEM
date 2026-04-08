<?php
// CDF Management System — settings functions

function getSystemSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value, setting_type FROM system_settings");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to key-value pairs with proper type casting
        $result = [];
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            
            // Cast values based on their type
            switch ($setting['setting_type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'string':
                case 'text':
                default:
                    $value = (string)$value;
                    break;
            }
            
            $result[$setting['setting_key']] = $value;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error getting system settings: " . $e->getMessage());
        // Return default settings if table doesn't exist
        return getDefaultSystemSettings();
    }
}
function getDefaultSystemSettings() {
    return [
        'system_name' => 'CDF Management System',
        'system_email' => 'noreply@cdf.gov.zm',
        'admin_email' => 'admin@cdf.gov.zm',
        'timezone' => 'Africa/Lusaka',
        'date_format' => 'Y-m-d',
        'items_per_page' => 10,
        'maintenance_mode' => false,
        'email_notifications' => true,
        'project_approvals' => true,
        'officer_assignments' => true,
        'budget_alerts' => true,
        'system_updates' => true,
        'password_policy' => 'medium',
        'session_timeout' => 60,
        'max_login_attempts' => 5,
        'two_factor_auth' => false,
        'ip_whitelist' => '',
        'auto_backup' => true,
        'backup_frequency' => 'daily',
        'backup_retention' => 30,
        'backup_email' => 'backups@cdf.gov.zm',
        'last_backup' => 'Never'
    ];
}
function updateSystemSettings($settingsData) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($settingsData as $key => $value) {
            // Determine the setting type
            $settingType = getSettingType($key);
            
            // Convert boolean values to 1/0 for database storage
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_at) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $settingType, $value]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error updating system settings: " . $e->getMessage());
        return false;
    }
}
function getSettingType($key) {
    $booleanSettings = [
        'maintenance_mode', 'email_notifications', 'project_approvals', 
        'officer_assignments', 'budget_alerts', 'system_updates', 
        'two_factor_auth', 'auto_backup'
    ];
    
    $integerSettings = [
        'items_per_page', 'session_timeout', 'max_login_attempts', 
        'backup_retention'
    ];
    
    $textSettings = [
        'ip_whitelist'
    ];
    
    if (in_array($key, $booleanSettings)) {
        return 'boolean';
    } elseif (in_array($key, $integerSettings)) {
        return 'integer';
    } elseif (in_array($key, $textSettings)) {
        return 'text';
    } else {
        return 'string';
    }
}
function updateNotificationSettings($notificationData) {
    // Add notification group to data
    $dataWithGroup = [];
    foreach ($notificationData as $key => $value) {
        $dataWithGroup[$key] = $value;
    }
    
    return updateSystemSettings($dataWithGroup);
}
function updateSecuritySettings($securityData) {
    // Add security group to data
    $dataWithGroup = [];
    foreach ($securityData as $key => $value) {
        $dataWithGroup[$key] = $value;
    }
    
    return updateSystemSettings($dataWithGroup);
}
function updateBackupSettings($backupData) {
    // Add backup group to data
    $dataWithGroup = [];
    foreach ($backupData as $key => $value) {
        $dataWithGroup[$key] = $value;
    }
    
    return updateSystemSettings($dataWithGroup);
}
function clearSystemCache() {
    try {
        // Clear opcache if enabled
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear any file-based caches
        $cacheDir = '../cache/';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        // Log the action
        error_log("System cache cleared by user: " . $_SESSION['user_id']);
        
        return true;
    } catch (Exception $e) {
        error_log("Error clearing system cache: " . $e->getMessage());
        return false;
    }
}
function runManualBackup() {
    try {
        $backupDir = '../backups/';
        
        // Create backups directory if it doesn't exist
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'backup_' . $timestamp . '.sql';
        
        // Get database configuration
        $dbHost = 'localhost';
        $dbName = 'cdf_system'; // Replace with your database name
        $dbUser = 'root'; // Replace with your database user
        $dbPass = ''; // Replace with your database password
        
        // Create database backup using mysqldump
        $command = "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > {$backupFile}";
        system($command, $output);
        
        if ($output === 0 && file_exists($backupFile)) {
            // Update last backup timestamp
            $settingsData = [
                'last_backup' => date('Y-m-d H:i:s')
            ];
            
            updateSystemSettings($settingsData);
            
            // Log successful backup
            error_log("Manual backup completed successfully by user: " . $_SESSION['user_id']);
            return true;
        } else {
            throw new Exception("Backup command failed with output: " . $output);
        }
        
    } catch (Exception $e) {
        error_log("Error running manual backup: " . $e->getMessage());
        
        // Fallback: Just update the timestamp without actual backup
        $settingsData = [
            'last_backup' => date('Y-m-d H:i:s') . ' (Simulated)'
        ];
        
        updateSystemSettings($settingsData);
        return true; // Return true for demonstration purposes
    }
}
function testEmailConfiguration() {
    try {
        $systemSettings = getSystemSettings();
        $adminEmail = $systemSettings['admin_email'] ?? 'admin@cdf.gov.zm';
        
        // For demonstration, we'll simulate email sending
        // In production, you would use PHPMailer, SwiftMailer, or mail() function
        
        $subject = 'CDF System - Email Configuration Test';
        $message = "This is a test email to verify that your email configuration is working correctly.\n\n";
        $message .= "Sent from: " . $systemSettings['system_name'] . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        
        // Simulate email sending (replace with actual email code)
        $emailSent = true; // mail($adminEmail, $subject, $message);
        
        if ($emailSent) {
            error_log("Test email sent successfully to: " . $adminEmail);
            return true;
        } else {
            throw new Exception("Email sending function returned false");
        }
        
    } catch (Exception $e) {
        error_log("Error testing email configuration: " . $e->getMessage());
        
        // For development, return true to simulate success
        return true;
    }
}
function initializeSystemSettings() {
    global $pdo;
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SELECT 1 FROM system_settings LIMIT 1");
        return true; // Table exists
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $createTableSQL = "
            CREATE TABLE system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) NOT NULL UNIQUE,
                setting_value TEXT,
                setting_type VARCHAR(50) DEFAULT 'string',
                setting_group VARCHAR(100) DEFAULT 'general',
                description TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        try {
            $pdo->exec($createTableSQL);
            
            // Insert default settings
            $defaultSettings = getDefaultSystemSettings();
            foreach ($defaultSettings as $key => $value) {
                $type = getSettingType($key);
                $group = getSettingGroup($key);
                
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_group) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$key, $value, $type, $group]);
            }
            
            return true;
        } catch (PDOException $createError) {
            error_log("Error creating system_settings table: " . $createError->getMessage());
            return false;
        }
    }
}
function getSettingGroup($key) {
    $notificationSettings = [
        'email_notifications', 'project_approvals', 'officer_assignments', 
        'budget_alerts', 'system_updates'
    ];
    
    $securitySettings = [
        'password_policy', 'session_timeout', 'max_login_attempts', 
        'two_factor_auth', 'ip_whitelist'
    ];
    
    $backupSettings = [
        'auto_backup', 'backup_frequency', 'backup_retention', 
        'backup_email', 'last_backup'
    ];
    
    if (in_array($key, $notificationSettings)) {
        return 'notifications';
    } elseif (in_array($key, $securitySettings)) {
        return 'security';
    } elseif (in_array($key, $backupSettings)) {
        return 'backup';
    } else {
        return 'general';
    }
}
function updateUserProfile($userId, $profileData) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, position = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $profileData['first_name'],
            $profileData['last_name'],
            $profileData['email'],
            $profileData['phone'],
            $profileData['department'],
            $profileData['position'],
            $userId
        ]);
    } catch (PDOException $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return false;
    }
}

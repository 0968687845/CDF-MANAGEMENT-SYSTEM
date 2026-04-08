<?php
// CDF Management System — auth functions

function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
function requireRole($role) {
        // Check maintenance mode for non-admin users
        if (isMaintenanceModeEnabled() && getUserRole() !== 'admin') {
            showMaintenancePage();
            exit();
        }
        
        if (!isLoggedIn() || getUserRole() !== $role) {
            redirect('login.php');
            exit();
        }
    }
function isMaintenanceModeEnabled() {
        static $maintenanceMode = null;
        
        if ($maintenanceMode === null) {
            try {
                $settings = getSystemSettings();
                $maintenanceMode = (bool)($settings['maintenance_mode'] ?? false);
            } catch (Exception $e) {
                $maintenanceMode = false;
            }
        }
        
        return $maintenanceMode;
    }
function showMaintenancePage() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>System Maintenance - CDF Management System</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body {
                    background: linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                
                .maintenance-container {
                    text-align: center;
                    background: white;
                    padding: 3rem;
                    border-radius: 16px;
                    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
                    max-width: 500px;
                    width: 100%;
                    margin: 2rem;
                }
                
                .maintenance-icon {
                    font-size: 4rem;
                    color: #ffc107;
                    margin-bottom: 2rem;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.7; transform: scale(1.05); }
                }
                
                .maintenance-title {
                    font-size: 1.75rem;
                    color: #1a4e8a;
                    font-weight: 800;
                    margin-bottom: 1rem;
                }
                
                .maintenance-message {
                    color: #6c757d;
                    font-size: 1.1rem;
                    margin-bottom: 1.5rem;
                    line-height: 1.6;
                }
                
                .system-status {
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin: 2rem 0;
                    color: #856404;
                }
                
                .status-icon {
                    font-size: 2rem;
                    margin-bottom: 0.5rem;
                }
                
                .contact-info {
                    background: #f8f9fa;
                    border-left: 4px solid #1a4e8a;
                    padding: 1rem;
                    border-radius: 8px;
                    margin: 1.5rem 0;
                    text-align: left;
                }
                
                .contact-info strong {
                    color: #1a4e8a;
                }
                
                .footer-text {
                    font-size: 0.9rem;
                    color: #999;
                    margin-top: 2rem;
                }
                
                .coat-of-arms {
                    max-width: 60px;
                    margin-bottom: 1rem;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <img src="<?php echo (file_exists('coat-of-arms-of-zambia.jpg') ? '' : '../'); ?>coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" class="coat-of-arms">
                
                <div class="maintenance-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                
                <h1 class="maintenance-title">System Maintenance</h1>
                
                <p class="maintenance-message">
                    We're currently performing scheduled system maintenance. The CDF Management System will be back online shortly.
                </p>
                
                <div class="system-status">
                    <div class="status-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <strong>Expected Downtime:</strong>
                        <p class="mb-0">Please check back shortly</p>
                    </div>
                </div>
                
                <div class="contact-info">
                    <strong><i class="fas fa-envelope me-2"></i>For Assistance:</strong>
                    <p class="mb-0">Contact: admin@cdf.gov.zm</p>
                </div>
                
                <div class="footer-text">
                    <p>Government of the Republic of Zambia</p>
                    <p>CDF Management System</p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
function redirect($url) {
        header("Location: $url");
        exit();
    }
function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
function verifyCsrfToken(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }
function login($username, $password) {
        global $pdo;

        // Check if account is locked out
        $stmt = $pdo->prepare("SELECT id, password, role, first_name, last_name, login_attempts, account_locked_until FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
            $remaining = ceil((strtotime($user['account_locked_until']) - time()) / 60);
            return "Account locked due to too many failed attempts. Try again in {$remaining} minute(s).";
        }

        if (password_verify($password, $user['password'])) {
            // Check maintenance mode
            if (isMaintenanceModeEnabled() && $user['role'] !== 'admin') {
                return false;
            }

            // Reset login attempts on success
            $pdo->prepare("UPDATE users SET login_attempts = 0, account_locked_until = NULL, last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username']  = $username;
            return true;
        }

        // Failed attempt — increment counter, lock after 5 failures
        $pdo->prepare("
            UPDATE users
            SET login_attempts = login_attempts + 1,
                account_locked_until = IF(login_attempts + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
            WHERE id = ?
        ")->execute([$user['id']]);

        return false;
    }
function emailExists($email) {
        global $pdo;
        
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
function generateResetToken() {
        return bin2hex(random_bytes(32));
    }
function sendPasswordResetEmail($email, $resetToken) {
        global $pdo;
        
        try {
            // Get user data
            $query = "SELECT id, first_name, last_name FROM users WHERE email = :email";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $user['id'];
            
            // Hash token before storing — raw token goes in the email link only
            $hashedToken = hash('sha256', $resetToken);
            $expiryTime = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $query = "INSERT INTO password_resets (user_id, token, email, created_at, expires_at)
                      VALUES (:user_id, :token, :email, NOW(), :expires_at)
                      ON DUPLICATE KEY UPDATE token = :token, created_at = NOW(), expires_at = :expires_at, is_used = FALSE";

            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':token', $hashedToken);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':expires_at', $expiryTime);
            $stmt->execute();

            // Build reset link with the raw (unhashed) token
            $resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') .
                        $_SERVER['HTTP_HOST'] .
                        dirname($_SERVER['PHP_SELF']) .
                        '/reset_password.php?token=' . $resetToken;
            
            // Email content
            $subject = "Password Reset Instructions - CDF Management System";
            
            $emailContent = "
                <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; }
                            .header { background: linear-gradient(135deg, #1a4e8a 0%, #0d3a6c 100%); color: white; padding: 30px; text-align: center; }
                            .content { padding: 30px; background: #f8f9fa; border: 1px solid #e0e0e0; }
                            .button { display: inline-block; background: #1a4e8a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
                            .warning { color: #d32f2f; font-weight: bold; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Password Reset Request</h2>
                                <p>CDF Management System - Government of Zambia</p>
                            </div>
                            <div class='content'>
                                <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
                                
                                <p>You have requested to reset your password for your CDF Management System account.</p>
                                
                                <p>Click the button below to reset your password:</p>
                                
                                <center>
                                    <a href='" . $resetLink . "' class='button'>Reset Password</a>
                                </center>
                                
                                <p>Or copy and paste this link in your browser:</p>
                                <p><small>" . htmlspecialchars($resetLink) . "</small></p>
                                
                                <p class='warning'>⚠️ This link will expire in 24 hours.</p>
                                
                                <p>If you did not request a password reset, please ignore this email or contact support immediately.</p>
                                
                                <p><strong>Important Security Notice:</strong></p>
                                <ul>
                                    <li>Never share your reset link with anyone</li>
                                    <li>Government staff will never ask for your password</li>
                                    <li>Always use HTTPS (secure) connections</li>
                                </ul>
                                
                                <p>Best regards,<br/>CDF Management System Support Team</p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " Government of the Republic of Zambia. All rights reserved.</p>
                                <p>This is an official government system. Unauthorized access is prohibited.</p>
                            </div>
                        </div>
                    </body>
                </html>
            ";
            
            // Email headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: CDF System <support@cdf.gov.zm>\r\n";
            $headers .= "Reply-To: support@cdf.gov.zm\r\n";
            
            // Send email - with fallback file logging for development
            $mailSent = false;
            $errorMsg = '';
            
            try {
                // Try to send email
                $mailSent = @mail($email, $subject, $emailContent, $headers);
            } catch (Exception $e) {
                $errorMsg = "Mail exception: " . $e->getMessage();
            }
            
            // Create email log directory if it doesn't exist
            $emailLogDir = __DIR__ . '/logs/emails';
            if (!is_dir($emailLogDir)) {
                @mkdir($emailLogDir, 0755, true);
            }
            
            // Log email to file (for testing/verification)
            $emailLogFile = $emailLogDir . '/' . date('Y-m-d') . '.log';
            $logEntry = "=== Password Reset Email ===\n";
            $logEntry .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
            $logEntry .= "To: " . $email . "\n";
            $logEntry .= "User: " . $user['first_name'] . " " . $user['last_name'] . "\n";
            $logEntry .= "Token: " . substr($resetToken, 0, 20) . "...\n";
            $logEntry .= "Reset Link: " . $resetLink . "\n";
            $logEntry .= "Mail Result: " . ($mailSent ? "SUCCESS" : "FAILED") . "\n";
            if ($errorMsg) {
                $logEntry .= "Error: " . $errorMsg . "\n";
            }
            $logEntry .= "\n";
            
            @file_put_contents($emailLogFile, $logEntry, FILE_APPEND);
            
            // If mail failed, check if it's a configuration issue
            if (!$mailSent) {
                // Log to PHP error log as well
                error_log("Password reset email failed for: " . $email . " | " . $errorMsg);
                
                // For development environments, we'll still consider it successful
                // if the database entry was created and email was logged to file
                // This allows testing without a working mail server
                
                // Check if we're in development mode by looking for configuration
                $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || 
                                 strpos($_SERVER['HTTP_HOST'] ?? 'localhost', '127.0.0.1') !== false ||
                                 strpos($_SERVER['HTTP_HOST'] ?? 'localhost', '::1') !== false);
                
                if ($isDevelopment && is_file($emailLogFile)) {
                    // In development, if we logged the email to file, consider it a success
                    logActivity($user_id, 'password_reset_requested', 'Password reset email prepared (dev mode): ' . $email);
                    return true;
                }
                
                return false;
            } else {
                // Email sent successfully
                logActivity($user_id, 'password_reset_requested', 'Password reset email sent to: ' . $email);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Error in sendPasswordResetEmail: " . $e->getMessage());
            return false;
        }
    }

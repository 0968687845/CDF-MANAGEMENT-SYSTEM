<?php
// CDF Management System — misc functions

function handleProfilePictureUpload($user_id, $file) {
        global $pdo;
        
        // Use absolute path based on __DIR__
        $upload_dir = __DIR__ . '/uploads/profiles/';
        
        // Check if uploads directory exists, create if not
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return "Unable to create upload directory.";
            }
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            return "Upload directory is not writable.";
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                UPLOAD_ERR_EXTENSION => 'Extension blocked file upload'
            ];
            $error_msg = $error_messages[$file['error']] ?? 'Unknown upload error';
            return "Error uploading file: " . $error_msg;
        }
        
        // Check file size (max 2MB)
        if ($file['size'] > 2097152) {
            return "File too large. Maximum size is 2MB.";
        }
        
        // Check file size is not empty
        if ($file['size'] <= 0) {
            return "File is empty.";
        }
        
        // Check file type using mime_content_type or finfo
        $file_type = '';
        if (function_exists('mime_content_type')) {
            $file_type = mime_content_type($file['tmp_name']);
        } else if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $file_type = finfo_file($finfo, $file['tmp_name']);
            }
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (empty($file_type) || !in_array($file_type, $allowed_types)) {
            return "Invalid file type. Only JPG, PNG, and GIF are allowed. Detected: " . $file_type;
        }
        
        // Get old profile picture BEFORE updating
        $old_picture = '';
        $query_old = "SELECT profile_picture FROM users WHERE id = :user_id";
        $stmt_old = $pdo->prepare($query_old);
        $stmt_old->bindParam(':user_id', $user_id);
        $stmt_old->execute();
        if ($stmt_old->rowCount() === 1) {
            $result = $stmt_old->fetch(PDO::FETCH_ASSOC);
            $old_picture = $result['profile_picture'] ?? '';
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Set proper file permissions
            chmod($file_path, 0644);
            
            // Update database
            $query = "UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':profile_picture', $filename);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                // Delete old profile picture if exists
                if (!empty($old_picture) && file_exists($upload_dir . $old_picture)) {
                    unlink($upload_dir . $old_picture);
                }
                
                logActivity($user_id, 'profile_picture_update', 'Updated profile picture');
                return true;
            } else {
                // Delete uploaded file if database update failed
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                return "Failed to update profile picture in database.";
            }
        } else {
            return "Failed to save uploaded file. Check upload directory permissions.";
        }
    }
function changeUserPassword($user_id, $current_password, $new_password) {
        global $pdo;
        
        // Get current password hash
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Validate new password strength
                if (!validatePassword($new_password)) {
                    return "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
                }
                
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $query = "UPDATE users SET password = :password WHERE id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    logActivity($user_id, 'password_change', 'Changed password');
                    return true;
                } else {
                    return "Failed to update password. Please try again.";
                }
            } else {
                return "Current password is incorrect.";
            }
        } else {
            return "User not found.";
        }
    }

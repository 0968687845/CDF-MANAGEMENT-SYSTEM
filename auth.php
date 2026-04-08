<?php
require_once 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
    exit();
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect('register.php');
        exit;
    }

    // Sanitize input data
    $data = sanitize($_POST);
    
    // Basic validation
    $errors = [];
    
    if (empty($data['first_name'])) {
        $errors[] = "First name is required";
    }
    
    if (empty($data['last_name'])) {
        $errors[] = "Last name is required";
    }
    
    if (empty($data['username'])) {
        $errors[] = "Username is required";
    } elseif (strlen($data['username']) < 4) {
        $errors[] = "Username must be at least 4 characters";
    }
    
    if (empty($data['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($data['password']) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($data['role'])) {
        $errors[] = "Role is required";
    }

    // Additional validation for beneficiary group registrations
    if (($data['role'] ?? '') === 'beneficiary') {
        // Validate primary NRC if provided
        if (!empty($data['nrc']) && !validateNRC($data['nrc'])) {
            $errors[] = "Please enter a valid NRC for the primary beneficiary (format: 123456/78/9).";
        }

        // If group registration selected, validate group fields and members
        if (!empty($data['is_group']) && intval($data['is_group']) === 1) {
            if (empty(trim($data['group_name'] ?? ''))) {
                $errors[] = "Group name is required for group registrations.";
            }

            if (empty($data['members']) || !is_array($data['members'])) {
                $errors[] = "Please add at least one group member.";
            } else {
                foreach ($data['members'] as $idx => $member) {
                    $mName = trim($member['name'] ?? '');
                    $mNrc = trim($member['nrc'] ?? '');
                    $mPhone = trim($member['phone'] ?? '');

                    if ($mName === '') {
                        $errors[] = "Member name is required for group member #" . ($idx + 1) . ".";
                    }

                    if ($mNrc === '' || !validateNRC($mNrc)) {
                        $errors[] = "Invalid or missing NRC for group member #" . ($idx + 1) . ". Use format 123456/78/9.";
                    }

                    if ($mPhone !== '' && !validatePhone($mPhone)) {
                        $errors[] = "Invalid phone number for group member #" . ($idx + 1) . ".";
                    }
                }
            }
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $result = registerUser($data);
        
        if ($result === true) {
            $role = $data['role'] ?? 'beneficiary';
            if ($role === 'beneficiary') {
                $_SESSION['success'] = "Registration submitted! Your account is pending admin approval. You will be notified once approved.";
            } else {
                $_SESSION['success'] = "Registration successful! You can now login.";
            }
            redirect('login.php');
        } else {
            $_SESSION['error'] = $result;
            // Redirect back to appropriate registration page
            $role = $data['role'];
            if ($role === 'admin') {
                redirect('admin_register.php');
            } elseif ($role === 'officer') {
                redirect('officer_register.php');
            } else {
                redirect('beneficiary_register.php');
            }
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        // Redirect back to appropriate registration page
        $role = $data['role'] ?? '';
        if ($role === 'admin') {
            redirect('admin_register.php');
        } elseif ($role === 'officer') {
            redirect('officer_register.php');
        } else {
            redirect('beneficiary_register.php');
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        redirect('login.php');
        exit;
    }

    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Do not sanitize password before verify

    $result = login($username, $password);
    if ($result === true) {
        switch ($_SESSION['user_role']) {
            case 'admin':
                redirect('admin_dashboard.php');
                break;
            case 'officer':
                redirect('officer_dashboard.php');
                break;
            case 'beneficiary':
                redirect('beneficiary_dashboard.php');
                break;
            default:
                redirect('index.php');
        }
    } else {
        $_SESSION['error'] = is_string($result) ? $result : "Invalid username or password";
        redirect('login.php');
    }
}
?>
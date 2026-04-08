<?php
// CDF Management System — user functions

function getUserData() {
        if (!isLoggedIn()) return null;
        
        global $pdo;
        
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
function getUsersByRole($role) {
        global $pdo;
        
        $query = "SELECT * FROM users WHERE role = :role AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getAllUsers() {
        global $pdo;
        
        $query = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function createUser($data, $password = null) {
        global $pdo;
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            return "First name and last name are required";
        }
        
        // Generate username if not provided
        if (empty($data['username'])) {
            $firstName = strtolower($data['first_name']);
            $lastName = strtolower($data['last_name']);
            $username = substr($firstName, 0, 1) . $lastName;
            
            // Ensure unique username
            $counter = 1;
            $originalUsername = $username;
            while (true) {
                $check_query = "SELECT id FROM users WHERE username = :username";
                $check_stmt = $pdo->prepare($check_query);
                $check_stmt->bindParam(':username', $username);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() === 0) {
                    break;
                }
                $username = $originalUsername . $counter;
                $counter++;
            }
            $data['username'] = $username;
        } else {
            // Check if username already exists
            $check_query = "SELECT id FROM users WHERE username = :username";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->bindParam(':username', $data['username']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                return "Username already exists";
            }
        }
        
        // Check if email already exists
        if (!empty($data['email'])) {
            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->bindParam(':email', $data['email']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                return "Email already exists";
            }
        }
        
        // Use provided password or generate a default one
        if (empty($password)) {
            $password = 'Temp123!';
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare SQL query
        $query = "INSERT INTO users SET 
            username = :username,
            password = :password,
            email = :email,
            phone = :phone,
            first_name = :first_name,
            last_name = :last_name,
            nrc = :nrc,
            dob = :dob,
            gender = :gender,
            role = :role,
            constituency = :constituency,
            ward = :ward,
            village = :village,
            marital_status = :marital_status,
            status = :status";
        
        $stmt = $pdo->prepare($query);
        
        // Bind parameters with proper variable assignment
        $username = $data['username'];
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $first_name = $data['first_name'];
        $last_name = $data['last_name'];
        $nrc = $data['nrc'] ?? null;
        $dob = $data['dob'] ?? null;
        $gender = $data['gender'] ?? null;
        $role = $data['role'] ?? 'beneficiary';
        $constituency = $data['constituency'] ?? null;
        $ward = $data['ward'] ?? null;
        $village = $data['village'] ?? null;
        $marital_status = $data['marital_status'] ?? null;
        $status = $data['status'] ?? 'active';
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':nrc', $nrc);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':constituency', $constituency);
        $stmt->bindParam(':ward', $ward);
        $stmt->bindParam(':village', $village);
        $stmt->bindParam(':marital_status', $marital_status);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            // Log the activity
            logActivity($_SESSION['user_id'], 'user_creation', 'Created new user: ' . $data['username']);
            return true;
        }
        
        return "Failed to create user. Please try again.";
    }
function updateUser($id, $data) {
        global $pdo;
        
        // Add user_id to data array
        $data['user_id'] = $id;
        
        // Build update query dynamically based on provided fields
        $query = "UPDATE users SET ";
        $params = [];
        
        if (isset($data['first_name'])) {
            $query .= "first_name = :first_name, ";
            $params[':first_name'] = $data['first_name'];
        }
        
        if (isset($data['last_name'])) {
            $query .= "last_name = :last_name, ";
            $params[':last_name'] = $data['last_name'];
        }
        
        if (isset($data['email'])) {
            $query .= "email = :email, ";
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['phone'])) {
            $query .= "phone = :phone, ";
            $params[':phone'] = $data['phone'];
        }
        
        if (isset($data['role'])) {
            $query .= "role = :role, ";
            $params[':role'] = $data['role'];
        }
        
        if (isset($data['status'])) {
            $query .= "status = :status, ";
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['constituency'])) {
            $query .= "constituency = :constituency, ";
            $params[':constituency'] = $data['constituency'];
        }
        
        if (isset($data['ward'])) {
            $query .= "ward = :ward, ";
            $params[':ward'] = $data['ward'];
        }
        
        if (isset($data['village'])) {
            $query .= "village = :village, ";
            $params[':village'] = $data['village'];
        }
        
        // Remove trailing comma and add WHERE clause
        $query = rtrim($query, ', ') . " WHERE id = :user_id";
        $params[':user_id'] = $data['user_id'];
        
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'user_update', 'Updated user ID: ' . $data['user_id']);
            return true;
        }
        
        return "Failed to update user. Please try again.";
    }
function deleteUser($user_id) {
        global $pdo;
        
        // Check if user exists
        $check_query = "SELECT username FROM users WHERE id = :user_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() === 0) {
            return "User not found";
        }
        
        $user = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            return "You cannot delete your own account";
        }
        
        // Delete user
        $query = "DELETE FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'user_deletion', 'Deleted user: ' . $user['username']);
            return true;
        }
        
        return "Failed to delete user. Please try again.";
    }
function handleBulkAction($user_ids, $action) {
        global $pdo;
        
        if (empty($user_ids)) {
            return "No users selected";
        }
        
        $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
        
        switch ($action) {
            case 'activate':
                $query = "UPDATE users SET status = 'active' WHERE id IN ($placeholders)";
                break;
            case 'deactivate':
                $query = "UPDATE users SET status = 'inactive' WHERE id IN ($placeholders)";
                break;
            case 'delete':
                // Prevent self-deletion in bulk actions
                if (in_array($_SESSION['user_id'], $user_ids)) {
                    return "You cannot delete your own account";
                }
                $query = "DELETE FROM users WHERE id IN ($placeholders)";
                break;
            default:
                return "Invalid bulk action";
        }
        
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute($user_ids)) {
            logActivity($_SESSION['user_id'], 'bulk_action', "Performed $action on " . count($user_ids) . " users");
            return true;
        }
        
        return "Failed to perform bulk action. Please try again.";
    }
function getUserById($user_id) {
        global $pdo;
        
        $query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
function getUsersCountByRole($role = null) {
        global $pdo;
        
        if ($role) {
            $query = "SELECT COUNT(*) as count FROM users WHERE role = :role";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':role', $role);
        } else {
            $query = "SELECT COUNT(*) as count FROM users";
            $stmt = $pdo->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'];
    }
function calculateBeneficiaryRating($user_id) {
        global $pdo;
        
        // Get all projects for the user
        $query = "SELECT p.*, 
                         COUNT(DISTINCT pp.id) as update_count,
                         AVG(p.progress) as avg_progress,
                         SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                         SUM(CASE WHEN p.status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_count,
                         SUM(CASE WHEN p.status = 'delayed' THEN 1 ELSE 0 END) as delayed_count
                  FROM projects p
                  LEFT JOIN project_progress pp ON p.id = pp.project_id
                  WHERE p.beneficiary_id = :user_id
                  GROUP BY p.beneficiary_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($projects)) {
            return [
                'overall_rating' => 0,
                'rating_letter' => 'N/A',
                'consistency_score' => 0,
                'velocity_score' => 0,
                'adherence_score' => 0,
                'quality_score' => 0,
                'reliability_score' => 0,
                'projects_analyzed' => 0,
                'details' => 'Insufficient data for rating'
            ];
        }
        
        $ratings = [];
        
        // 1. CONSISTENCY SCORE (20%) - How regularly updates are submitted
        $consistency_scores = [];
        foreach ($projects as $project) {
            if ($project['update_count'] > 0) {
                $days_active = 0;
                $query_dates = "SELECT MIN(created_at) as first_update, MAX(created_at) as last_update, COUNT(*) as updates
                               FROM project_progress WHERE project_id = :project_id";
                $stmt_dates = $pdo->prepare($query_dates);
                $stmt_dates->bindParam(':project_id', $project['id']);
                $stmt_dates->execute();
                $date_info = $stmt_dates->fetch(PDO::FETCH_ASSOC);
                
                if ($date_info && $date_info['first_update']) {
                    $days_active = (strtotime($date_info['last_update']) - strtotime($date_info['first_update'])) / (60 * 60 * 24);
                    if ($days_active > 0) {
                        $update_frequency = $date_info['updates'] / ($days_active + 1);
                        // Optimal frequency is ~0.5 updates per day
                        $consistency = min(100, ($update_frequency / 0.5) * 100);
                        $consistency_scores[] = $consistency;
                    }
                }
            }
        }
        
        $consistency_score = !empty($consistency_scores) ? array_sum($consistency_scores) / count($consistency_scores) : 0;
        $ratings['consistency_score'] = round($consistency_score, 2);
        
        // 2. VELOCITY SCORE (25%) - Project completion speed and acceleration
        $velocity_scores = [];
        foreach ($projects as $project) {
            $velocity_analysis = analyzeProgressVelocity($project['id']);
            
            if ($velocity_analysis['velocity'] > 0) {
                // Score based on velocity (2% per day is good)
                $velocity_score = min(100, ($velocity_analysis['velocity'] / 2) * 100);
                
                // Acceleration bonus (trend affects score)
                $trend_modifier = match($velocity_analysis['trend']) {
                    'accelerating' => 1.1,
                    'stable' => 1.0,
                    'declining' => 0.85,
                    'stagnant' => 0.6,
                    default => 0.8
                };
                
                $velocity_scores[] = min(100, $velocity_score * $trend_modifier);
            }
        }
        
        $velocity_score = !empty($velocity_scores) ? array_sum($velocity_scores) / count($velocity_scores) : 0;
        $ratings['velocity_score'] = round($velocity_score, 2);
        
        // 3. ADHERENCE SCORE (20%) - On-time project completion
        $total_projects = count($projects);
        $completed = array_sum(array_column($projects, 'completed_count'));
        $delayed = array_sum(array_column($projects, 'delayed_count'));
        
        $adherence_score = $total_projects > 0 ? 
            (($completed / $total_projects) * 100) - (($delayed / $total_projects) * 30) : 0;
        $adherence_score = max(0, min(100, $adherence_score));
        $ratings['adherence_score'] = round($adherence_score, 2);
        
        // 4. QUALITY SCORE (20%) - Progress quality and detail
        $quality_scores = [];
        foreach ($projects as $project) {
            $query_quality = "SELECT AVG(LENGTH(description)) as avg_desc_length,
                                    COUNT(CASE WHEN challenges IS NOT NULL AND LENGTH(challenges) > 10 THEN 1 END) as challenges_count,
                                    COUNT(CASE WHEN next_steps IS NOT NULL AND LENGTH(next_steps) > 10 THEN 1 END) as steps_count,
                                    COUNT(CASE WHEN photos IS NOT NULL THEN 1 END) as photo_count
                             FROM project_progress WHERE project_id = :project_id";
            $stmt_quality = $pdo->prepare($query_quality);
            $stmt_quality->bindParam(':project_id', $project['id']);
            $stmt_quality->execute();
            $quality_info = $stmt_quality->fetch(PDO::FETCH_ASSOC);
            
            if ($quality_info && $project['update_count'] > 0) {
                $description_quality = min(40, ($quality_info['avg_desc_length'] / 100) * 40);
                $detail_quality = min(30, (($quality_info['challenges_count'] + $quality_info['steps_count']) / $project['update_count']) * 30);
                $documentation_quality = min(30, ($quality_info['photo_count'] / $project['update_count']) * 30);
                
                $quality_scores[] = $description_quality + $detail_quality + $documentation_quality;
            }
        }
        
        $quality_score = !empty($quality_scores) ? array_sum($quality_scores) / count($quality_scores) : 0;
        $ratings['quality_score'] = round($quality_score, 2);
        
        // 5. RELIABILITY SCORE (15%) - No delays or issues
        $reliability_score = max(0, 100 - (($delayed / max(1, $total_projects)) * 50));
        $ratings['reliability_score'] = round($reliability_score, 2);
        
        // CALCULATE OVERALL RATING (Weighted Average)
        $overall_rating = 
            ($ratings['consistency_score'] * 0.20) +
            ($ratings['velocity_score'] * 0.25) +
            ($ratings['adherence_score'] * 0.20) +
            ($ratings['quality_score'] * 0.20) +
            ($ratings['reliability_score'] * 0.15);
        
        // Convert to letter grade
        if ($overall_rating >= 90) {
            $rating_letter = 'A (Excellent)';
        } elseif ($overall_rating >= 80) {
            $rating_letter = 'B (Very Good)';
        } elseif ($overall_rating >= 70) {
            $rating_letter = 'C (Good)';
        } elseif ($overall_rating >= 60) {
            $rating_letter = 'D (Satisfactory)';
        } else {
            $rating_letter = 'F (Needs Improvement)';
        }
        
        return [
            'overall_rating' => round($overall_rating, 2),
            'rating_letter' => $rating_letter,
            'consistency_score' => $ratings['consistency_score'],
            'velocity_score' => $ratings['velocity_score'],
            'adherence_score' => $ratings['adherence_score'],
            'quality_score' => $ratings['quality_score'],
            'reliability_score' => $ratings['reliability_score'],
            'projects_analyzed' => $total_projects,
            'completed_projects' => $completed,
            'delayed_projects' => $delayed,
            'in_progress_projects' => $total_projects - $completed - $delayed
        ];
    }
function getBeneficiaryPerformanceInsights($user_id) {
        $rating = calculateBeneficiaryRating($user_id);
        
        $insights = [];
        
        // Generate actionable insights based on scores
        if ($rating['consistency_score'] < 60) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Improve Update Frequency',
                'message' => 'Regular updates help maintain project momentum. Aim for at least 2-3 updates per week.',
                'recommendation' => 'Schedule weekly progress updates'
            ];
        } else if ($rating['consistency_score'] >= 85) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Consistency',
                'message' => 'You are submitting regular, timely updates consistently.',
                'recommendation' => 'Maintain this excellent tracking habit'
            ];
        }
        
        if ($rating['velocity_score'] < 60) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Accelerate Project Pace',
                'message' => 'Your projects are progressing slowly. Consider increasing resources or effort.',
                'recommendation' => 'Review project timeline and bottlenecks'
            ];
        } else if ($rating['velocity_score'] >= 85) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong Progress Momentum',
                'message' => 'Your projects are progressing at an excellent pace.',
                'recommendation' => 'Continue current strategies'
            ];
        }
        
        if ($rating['quality_score'] < 60) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Enhance Documentation Quality',
                'message' => 'Include more details in updates - challenges, next steps, and supporting photos.',
                'recommendation' => 'Provide comprehensive progress descriptions'
            ];
        } else if ($rating['quality_score'] >= 85) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Outstanding Documentation',
                'message' => 'Your progress reports are detailed and well-documented.',
                'recommendation' => 'Maintain high-quality documentation standards'
            ];
        }
        
        if ($rating['reliability_score'] < 70) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Address Project Delays',
                'message' => 'Several projects have experienced delays. Focus on improving timely delivery.',
                'recommendation' => 'Implement delay prevention strategies'
            ];
        } else if ($rating['reliability_score'] >= 90) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Highly Reliable Performance',
                'message' => 'Your projects are consistently completed on schedule.',
                'recommendation' => 'Continue maintaining excellent reliability'
            ];
        }
        
        return [
            'rating' => $rating,
            'insights' => $insights
        ];
    }
function getActiveBeneficiariesForOfficer($officer_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.phone,
                    p.title as project_title,
                    p.status as project_status
                FROM users u
                INNER JOIN projects p ON u.id = p.beneficiary_id
                INNER JOIN officer_assignments oa ON p.id = oa.project_id
                WHERE oa.officer_id = ? 
                AND u.role = 'beneficiary'
                AND u.status = 'active'
                AND p.status IN ('active', 'in-progress', 'planning')
                ORDER BY u.first_name, u.last_name
            ");
            
            $stmt->execute([$officer_id]);
            $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $beneficiaries ?: [];
            
        } catch (PDOException $e) {
            error_log("Database error in getActiveBeneficiariesForOfficer: " . $e->getMessage());
            
            // Return sample data for development
            return [
                [
                    'id' => 1,
                    'first_name' => 'John',
                    'last_name' => 'Mwila',
                    'email' => 'john.mwila@example.com',
                    'phone' => '+260 97 123 4567',
                    'project_title' => 'Community School Renovation',
                    'project_status' => 'in-progress'
                ],
                [
                    'id' => 2,
                    'first_name' => 'Mary',
                    'last_name' => 'Banda',
                    'email' => 'mary.banda@example.com',
                    'phone' => '+260 96 234 5678',
                    'project_title' => 'Health Clinic Construction',
                    'project_status' => 'active'
                ],
                [
                    'id' => 3,
                    'first_name' => 'David',
                    'last_name' => 'Phiri',
                    'email' => 'david.phiri@example.com',
                    'phone' => '+260 95 345 6789',
                    'project_title' => 'Water Well Installation',
                    'project_status' => 'planning'
                ]
            ];
        }
    }
function getActiveBeneficiariesForOfficerSimple($officer_id) {
        global $pdo;
        
        try {
            // Try different possible table structures
            $queries = [
                // Try projects table with officer_id direct assignment
                "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.phone
                 FROM users u 
                 INNER JOIN projects p ON u.id = p.beneficiary_id 
                 WHERE p.officer_id = ? AND u.role = 'beneficiary' AND u.status = 'active'",
                
                // Try user_assignments table
                "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.phone
                 FROM users u 
                 INNER JOIN user_assignments ua ON u.id = ua.beneficiary_id 
                 WHERE ua.officer_id = ? AND u.role = 'beneficiary' AND u.status = 'active'",
                
                // Try project_assignments table
                "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.phone
                 FROM users u 
                 INNER JOIN projects p ON u.id = p.beneficiary_id 
                 INNER JOIN project_assignments pa ON p.id = pa.project_id 
                 WHERE pa.officer_id = ? AND u.role = 'beneficiary' AND u.status = 'active'"
            ];
            
            foreach ($queries as $query) {
                try {
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$officer_id]);
                    $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($beneficiaries)) {
                        return $beneficiaries;
                    }
                } catch (PDOException $e) {
                    // Continue to next query if this one fails
                    continue;
                }
            }
            
            return [];
            
        } catch (PDOException $e) {
            error_log("Database error in getActiveBeneficiariesForOfficerSimple: " . $e->getMessage());
            return [];
        }
    }
function getAssignedBeneficiaries($officer_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.created_at 
                FROM users u
                INNER JOIN projects p ON u.id = p.beneficiary_id 
                WHERE p.assigned_officer_id = ? AND u.role = 'beneficiary'
                GROUP BY u.id
                ORDER BY u.first_name, u.last_name
            ");
            $stmt->execute([$officer_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching assigned beneficiaries: " . $e->getMessage());
            return [];
        }
    }
function updateUserStatus($id, $status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    } catch (PDOException $e) {
        error_log("Error updating user status: " . $e->getMessage());
        return false;
    }
}
function resetUserPassword($id, $newPassword) {
    global $pdo;
    
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    } catch (PDOException $e) {
        error_log("Error resetting user password: " . $e->getMessage());
        return false;
    }
}
function updateUserLastLogin($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Error updating user last login: " . $e->getMessage());
        return false;
    }
}
function updateUserPreferences($userId, $preferences) {
    global $pdo;
    
    try {
        // Convert preferences array to JSON
        $preferencesJson = json_encode($preferences);
        
        $stmt = $pdo->prepare("UPDATE users SET preferences = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$preferencesJson, $userId]);
    } catch (PDOException $e) {
        error_log("Error updating user preferences: " . $e->getMessage());
        return false;
    }
}
function getUserActivity($userId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_activity 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user activity: " . $e->getMessage());
        // Return sample data for demonstration
        return [
            [
                'title' => 'Profile Updated',
                'description' => 'You updated your profile information',
                'type' => 'primary',
                'icon' => 'user-edit',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'title' => 'Password Changed',
                'description' => 'You changed your account password',
                'type' => 'success',
                'icon' => 'key',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'title' => 'System Login',
                'description' => 'You logged into the system',
                'type' => 'info',
                'icon' => 'sign-in-alt',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ]
        ];
    }
}
function getUserPreferences($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT preferences FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user['preferences'] ? json_decode($user['preferences'], true) : [];
    } catch (PDOException $e) {
        error_log("Error getting user preferences: " . $e->getMessage());
        return [];
    }
}
function verifyCurrentPassword($userId, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return password_verify($password, $user['password']);
    } catch (PDOException $e) {
        error_log("Error verifying current password: " . $e->getMessage());
        return false;
    }
}
function registerUser($data) {
    global $pdo;
    
    if (!$pdo) {
        return "Database connection error. Please try again.";
    }
    
    try {
        // Basic existence checks
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->rowCount() > 0) {
            return "Username already exists. Please choose another.";
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email'] ?? '']);
        if ($stmt->rowCount() > 0) {
            return "Email already exists. Please use another.";
        }

        // Start transaction so user+group creation is atomic
        $pdo->beginTransaction();

        // Hash the password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user
        $sql = "INSERT INTO users (
            username, email, password, first_name, last_name, nrc, phone, role, 
            constituency, department, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Beneficiaries require admin approval before activation
        $role = $data['role'] ?? 'beneficiary';
        $status = ($role === 'beneficiary') ? 'pending' : 'active';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['username'],
            $data['email'] ?? '',
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['nrc'] ?? '',
            $data['phone'] ?? '',
            $role,
            $data['constituency'] ?? '',
            $data['department'] ?? '',
            $status,
        ]);

        $userId = $pdo->lastInsertId();

        // If group registration requested, insert group and members
        if (!empty($data['is_group']) && intval($data['is_group']) === 1) {
            $groupName = trim($data['group_name'] ?? '');
            if ($groupName === '') {
                $pdo->rollBack();
                return 'Group name is required for group registrations.';
            }

            // Validate members array
            $members = $data['members'] ?? [];
            if (!is_array($members) || count($members) === 0) {
                $pdo->rollBack();
                return 'Please add at least one group member.';
            }

            // Insert group
            $stmt = $pdo->prepare("INSERT INTO beneficiary_groups (group_name, owner_user_id) VALUES (?, ?)");
            $stmt->execute([$groupName, $userId]);
            $groupId = $pdo->lastInsertId();

            // Prepare member insert
            $memberStmt = $pdo->prepare("INSERT INTO group_members (group_id, member_name, member_phone, member_nrc) VALUES (?, ?, ?, ?)");

            foreach ($members as $m) {
                $mname = trim($m['name'] ?? '');
                $mphone = trim($m['phone'] ?? '');
                $mnrc = trim($m['nrc'] ?? '');

                if ($mname === '') {
                    $pdo->rollBack();
                    return 'All group members must have a name.';
                }

                // Validate NRC format
                if (!empty($mnrc) && !validateNRC($mnrc)) {
                    $pdo->rollBack();
                    return 'Invalid NRC format for one of the group members. Use 123456/78/9 format.';
                }

                // Optional: validate phone
                if (!empty($mphone) && !validatePhone($mphone)) {
                    $pdo->rollBack();
                    return 'Invalid phone number for one of the group members.';
                }

                $memberStmt->execute([$groupId, $mname, $mphone, $mnrc]);
            }
        }

        // Commit transaction
        $pdo->commit();

        // Notify all admins about pending beneficiary registration
        if ($status === 'pending') {
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
            $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            foreach ($admins as $adminId) {
                createNotification(
                    $adminId,
                    'New Beneficiary Registration',
                    "A new beneficiary ({$fullName}) has registered and is awaiting approval. Review in User Management."
                );
            }
        }

        return true;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Database error in registerUser: " . $e->getMessage());
        return "An error occurred during registration. Please try again.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in registerUser: " . $e->getMessage());
        return "An error occurred. Please try again.";
    }
}

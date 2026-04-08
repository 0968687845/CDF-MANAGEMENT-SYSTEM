<?php
// CDF Management System — project functions

function getAllProjects() {
        global $pdo;
        
        $query = "SELECT p.*, 
                         CONCAT(b.first_name, ' ', b.last_name) as beneficiary_name,
                         CONCAT(o.first_name, ' ', o.last_name) as officer_name
                  FROM projects p
                  LEFT JOIN users b ON p.beneficiary_id = b.id
                  LEFT JOIN users o ON p.officer_id = o.id
                  ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getBeneficiaryProjects($beneficiary_id) {
        global $pdo;
        
        $query = "SELECT p.*, 
                         CONCAT(o.first_name, ' ', o.last_name) as officer_name
                  FROM projects p
                  LEFT JOIN users o ON p.officer_id = o.id
                  WHERE p.beneficiary_id = :beneficiary_id 
                  ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':beneficiary_id', $beneficiary_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getOfficerProjects($officer_id) {
        global $pdo;
        
        $query = "SELECT p.*, 
                         CONCAT(b.first_name, ' ', b.last_name) as beneficiary_name
                  FROM projects p
                  LEFT JOIN users b ON p.beneficiary_id = b.id
                  WHERE p.officer_id = :officer_id 
                  ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':officer_id', $officer_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function getProjectById($project_id) {
        global $pdo;
        
        $query = "SELECT p.*, 
                         CONCAT(b.first_name, ' ', b.last_name) as beneficiary_name,
                         CONCAT(o.first_name, ' ', o.last_name) as officer_name
                  FROM projects p
                  LEFT JOIN users b ON p.beneficiary_id = b.id
                  LEFT JOIN users o ON p.officer_id = o.id
                  WHERE p.id = :project_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
function createNewProject($data, $beneficiary_id) {
        global $pdo;
        
        $query = "INSERT INTO projects SET 
            title = :title,
            description = :description,
            beneficiary_id = :beneficiary_id,
            budget = :budget,
            start_date = :start_date,
            end_date = :end_date,
            location = :location,
            constituency = :constituency,
            category = :category,
            funding_source = :funding_source,
            budget_breakdown = :budget_breakdown,
            required_materials = :required_materials,
            human_resources = :human_resources,
            stakeholders = :stakeholders,
            community_approval = :community_approval,
            environmental_compliance = :environmental_compliance,
            land_ownership = :land_ownership,
            technical_feasibility = :technical_feasibility,
            budget_approval = :budget_approval,
            additional_notes = :additional_notes,
            status = 'planning',
            progress = 0";
        
        $stmt = $pdo->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':beneficiary_id', $beneficiary_id);
        $stmt->bindParam(':budget', $data['budget']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':constituency', $data['constituency']);
        $stmt->bindParam(':category', $data['category']);
        
        // Optional fields
        $funding_source = $data['funding_source'] ?? null;
        $budget_breakdown = $data['budget_breakdown'] ?? null;
        $required_materials = $data['required_materials'] ?? null;
        $human_resources = $data['human_resources'] ?? null;
        $stakeholders = $data['stakeholders'] ?? null;
        $community_approval = isset($data['community_approval']) ? 1 : 0;
        $environmental_compliance = isset($data['environmental_compliance']) ? 1 : 0;
        $land_ownership = isset($data['land_ownership']) ? 1 : 0;
        $technical_feasibility = isset($data['technical_feasibility']) ? 1 : 0;
        $budget_approval = isset($data['budget_approval']) ? 1 : 0;
        $additional_notes = $data['additional_notes'] ?? null;
        
        $stmt->bindParam(':funding_source', $funding_source);
        $stmt->bindParam(':budget_breakdown', $budget_breakdown);
        $stmt->bindParam(':required_materials', $required_materials);
        $stmt->bindParam(':human_resources', $human_resources);
        $stmt->bindParam(':stakeholders', $stakeholders);
        $stmt->bindParam(':community_approval', $community_approval);
        $stmt->bindParam(':environmental_compliance', $environmental_compliance);
        $stmt->bindParam(':land_ownership', $land_ownership);
        $stmt->bindParam(':technical_feasibility', $technical_feasibility);
        $stmt->bindParam(':budget_approval', $budget_approval);
        $stmt->bindParam(':additional_notes', $additional_notes);
        
        if ($stmt->execute()) {
            createNotification($beneficiary_id, 'Project Created', 'Your project "' . $data['title'] . '" has been successfully created and is pending review.');
            logActivity($beneficiary_id, 'project_creation', 'Created new project: ' . $data['title']);
            return true;
        }
        
        return false;
    }
function getProjectProgress($project_id) {
        global $pdo;
        
        $query = "SELECT pp.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                  FROM project_progress pp
                  LEFT JOIN users u ON pp.created_by = u.id
                  WHERE pp.project_id = :project_id 
                  ORDER BY pp.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function submitProgressUpdate($project_id, $progress_percentage, $description, $created_by, $challenges = '', $next_steps = '', $photos = [], $receipt_path = null, $achievements = []) {
        global $pdo;
        
        // Handle achievements JSON
        $achievements_json = !empty($achievements) ? json_encode($achievements) : null;
        
        // Handle receipt path - ensure it's stored properly
        $receipt_to_store = !empty($receipt_path) ? $receipt_path : null;
        
        $query = "INSERT INTO project_progress SET 
                  project_id = :project_id,
                  progress_percentage = :progress_percentage,
                  description = :description,
                  challenges = :challenges,
                  next_steps = :next_steps,
                  photos = :photos,
                  receipt_path = :receipt_path,
                  achievements = :achievements,
                  created_by = :created_by";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':progress_percentage', $progress_percentage);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':challenges', $challenges);
        $stmt->bindParam(':next_steps', $next_steps);
        
        // Handle photos JSON
        $photos_json = !empty($photos) ? json_encode($photos) : null;
        $stmt->bindParam(':photos', $photos_json);
        
        // Handle receipt path - use PDO bindParam properly
        $stmt->bindParam(':receipt_path', $receipt_to_store);
        
        // Handle achievements JSON
        $stmt->bindParam(':achievements', $achievements_json);
        
        $stmt->bindParam(':created_by', $created_by);
        
        if ($stmt->execute()) {
            logActivity($created_by, 'progress_update', 'Updated progress for project ID: ' . $project_id . ' to ' . $progress_percentage . '%');
            return true;
        }
        
        return false;
    }
function handleProgressPhotoUpload($files, $project_id) {
        $uploaded_files = [];
        $upload_dir = '../uploads/progress/' . $project_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Handle multiple files
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                // Validate file
                if (!validateUploadedFile($files, $i)) {
                    continue;
                }
                
                // Generate unique filename
                $file_extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = 'progress_' . time() . '_' . $i . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                    $uploaded_files[] = 'uploads/progress/' . $project_id . '/' . $filename;
                }
            }
        }
        
        return $uploaded_files;
    }
function handleReceiptUpload($file, $project_id) {
        $upload_dir = '../uploads/receipts/' . $project_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file
            if (!validateUploadedFile($file, 0, true)) {
                return false;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                return 'uploads/receipts/' . $project_id . '/' . $filename;
            }
        }
        
        return false;
    }
function validateUploadedFile($file, $index = 0, $isReceipt = false) {
        if ($isReceipt) {
            $max_size = 5 * 1024 * 1024; // 5MB for receipts
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        } else {
            $max_size = 10 * 1024 * 1024; // 10MB for progress photos
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
        }
        
        // Get file info
        if (is_array($file['name'])) {
            $file_type = mime_content_type($file['tmp_name'][$index]);
            $file_size = $file['size'][$index];
        } else {
            $file_type = mime_content_type($file['tmp_name']);
            $file_size = $file['size'];
        }
        
        // Check file type
        if (!in_array($file_type, $allowed_types)) {
            return false;
        }
        
        // Check file size
        if ($file_size > $max_size) {
            return false;
        }
        
        return true;
    }
function updateProjectProgress($project_id, $progress) {
        global $pdo;
        
        // Auto-complete project when progress reaches 100%
        $completion_status = (float)$progress >= 100 ? 'completed' : null;
        
        if ($completion_status === 'completed') {
            $query = "UPDATE projects SET progress = :progress, status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = :project_id";
        } else {
            $query = "UPDATE projects SET progress = :progress, updated_at = NOW() WHERE id = :project_id";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':progress', $progress);
        $stmt->bindParam(':project_id', $project_id);
        
        return $stmt->execute();
    }
function detectProjectStatus($project_id) {
        global $pdo;
        
        // Get project details
        $query = "SELECT p.*, 
                         (SELECT progress FROM project_progress WHERE project_id = p.id ORDER BY created_at DESC LIMIT 1) as latest_progress,
                         (SELECT created_at FROM project_progress WHERE project_id = p.id ORDER BY created_at DESC LIMIT 1) as last_update,
                         COUNT(pp.id) as update_count
                  FROM projects p
                  LEFT JOIN project_progress pp ON p.id = pp.project_id
                  WHERE p.id = :project_id
                  GROUP BY p.id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            return ['status' => 'unknown', 'confidence' => 0, 'reason' => 'Project not found'];
        }
        
        $status = 'pending';
        $confidence = 0;
        $reason = '';
        
        // Status Detection Logic
        // 1. Check if project is 100% complete
        if ((float)$project['latest_progress'] >= 100 || (float)$project['progress'] >= 100) {
            $status = 'completed';
            $confidence = 0.98;
            $reason = 'Project has reached 100% completion';
        }
        // 2. Check if project is approved and has updates
        else if ($project['status'] === 'approved' && $project['update_count'] > 0) {
            $status = 'in-progress';
            $confidence = 0.95;
            $reason = 'Project is approved with active updates';
        }
        // 3. Check for delayed status (no updates in 14+ days)
        else if (!empty($project['last_update'])) {
            $last_update_time = strtotime($project['last_update']);
            $current_time = time();
            $days_since_update = floor(($current_time - $last_update_time) / (60 * 60 * 24));
            
            if ($days_since_update >= 14) {
                $status = 'delayed';
                $confidence = 0.9 + (min($days_since_update, 30) / 100); // Confidence increases with delay
                $reason = "No updates for $days_since_update days";
            } else if ($days_since_update >= 7) {
                $status = 'at-risk';
                $confidence = 0.85;
                $reason = "At risk of delay - no updates for $days_since_update days";
            } else if ($project['status'] === 'approved') {
                $status = 'in-progress';
                $confidence = 0.85;
                $reason = 'Project approved and recently updated';
            }
        }
        // 4. Check if project is pending approval
        else if ($project['status'] === 'pending') {
            $status = 'pending';
            $confidence = 0.9;
            $reason = 'Awaiting project approval';
        }
        
        // ML-based Progress Velocity Analysis
        $velocity_analysis = analyzeProgressVelocity($project_id);
        
        // Adjust confidence based on velocity
        if ($velocity_analysis['trend'] === 'stagnant' && $status !== 'completed') {
            $confidence = max(0.7, $confidence - 0.1);
            $reason .= ' (Velocity: Stagnant)';
        } else if ($velocity_analysis['trend'] === 'declining' && $status !== 'completed') {
            $confidence = max(0.65, $confidence - 0.15);
            $reason .= ' (Velocity: Declining)';
            $status = 'at-risk';
        }
        
        return [
            'status' => $status,
            'confidence' => round($confidence, 2),
            'reason' => $reason,
            'latest_progress' => (float)$project['latest_progress'] ?? (float)$project['progress'],
            'days_since_update' => isset($last_update_time) ? $days_since_update : null,
            'velocity' => $velocity_analysis
        ];
    }
function analyzeProgressVelocity($project_id) {
        global $pdo;
        
        $query = "SELECT progress_percentage, created_at 
                  FROM project_progress 
                  WHERE project_id = :project_id 
                  ORDER BY created_at ASC 
                  LIMIT 10";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($updates) < 2) {
            return [
                'trend' => 'insufficient_data',
                'velocity' => 0,
                'acceleration' => 0,
                'prediction_days' => null
            ];
        }
        
        // Calculate velocity (progress per day)
        $velocities = [];
        $dates = [];
        
        for ($i = 1; $i < count($updates); $i++) {
            $prev_progress = (float)$updates[$i - 1]['progress_percentage'];
            $curr_progress = (float)$updates[$i]['progress_percentage'];
            $prev_date = strtotime($updates[$i - 1]['created_at']);
            $curr_date = strtotime($updates[$i]['created_at']);
            
            $days_diff = max(1, ($curr_date - $prev_date) / (60 * 60 * 24));
            $velocity = ($curr_progress - $prev_progress) / $days_diff;
            
            $velocities[] = $velocity;
            $dates[] = $days_diff;
        }
        
        $avg_velocity = array_sum($velocities) / count($velocities);
        
        // Calculate acceleration (change in velocity)
        $acceleration = 0;
        if (count($velocities) > 1) {
            $acceleration = ($velocities[count($velocities) - 1] - $velocities[0]) / count($velocities);
        }
        
        // Determine trend
        $trend = 'stable';
        if ($avg_velocity <= 0) {
            $trend = 'stagnant';
        } else if ($acceleration < -0.5) {
            $trend = 'declining';
        } else if ($acceleration > 0.5) {
            $trend = 'accelerating';
        }
        
        // Predict completion days
        $latest_progress = (float)end($updates)['progress_percentage'];
        $remaining_progress = 100 - $latest_progress;
        $prediction_days = null;
        
        if ($avg_velocity > 0 && $remaining_progress > 0) {
            $prediction_days = ceil($remaining_progress / $avg_velocity);
        }
        
        return [
            'trend' => $trend,
            'velocity' => round($avg_velocity, 2),
            'acceleration' => round($acceleration, 2),
            'prediction_days' => $prediction_days,
            'latest_progress' => $latest_progress
        ];
    }
function generateProjectAlerts($project_id) {
        global $pdo;
        
        $alerts = [];
        $status_info = detectProjectStatus($project_id);
        
        // Critical alerts
        if ($status_info['status'] === 'delayed') {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-exclamation-circle',
                'title' => 'Project Delayed',
                'message' => $status_info['reason'],
                'action' => 'Submit an update immediately to resume progress'
            ];
        }
        
        // Warning alerts
        if ($status_info['status'] === 'at-risk') {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-clock',
                'title' => 'Project at Risk',
                'message' => $status_info['reason'],
                'action' => 'Regular updates help maintain project momentum'
            ];
        }
        
        // Velocity warnings
        if ($status_info['velocity']['trend'] === 'declining') {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-arrow-trend-down',
                'title' => 'Declining Progress Rate',
                'message' => 'Project progress velocity is declining',
                'action' => 'Consider identifying and addressing bottlenecks'
            ];
        }
        
        // Positive alerts
        if ($status_info['status'] === 'completed') {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'fa-check-circle',
                'title' => 'Project Completed',
                'message' => 'Congratulations! Your project has reached 100% completion',
                'action' => 'Submit final reports and documentation'
            ];
        } else if ($status_info['velocity']['trend'] === 'accelerating') {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fa-arrow-trend-up',
                'title' => 'Accelerating Progress',
                'message' => 'Great! Your project progress is accelerating',
                'action' => 'Keep up the momentum'
            ];
        }
        
        // Prediction alerts
        if ($status_info['velocity']['prediction_days'] !== null && $status_info['status'] !== 'completed') {
            if ($status_info['velocity']['prediction_days'] <= 7) {
                $alerts[] = [
                    'type' => 'success',
                    'icon' => 'fa-flag-checkered',
                    'title' => 'Nearing Completion',
                    'message' => "Project expected to complete in approximately {$status_info['velocity']['prediction_days']} days",
                    'action' => 'Finalize remaining tasks'
                ];
            } else if ($status_info['velocity']['prediction_days'] > 60) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fa-hourglass',
                    'title' => 'Long Timeline Ahead',
                    'message' => "Current pace suggests completion in approximately {$status_info['velocity']['prediction_days']} days",
                    'action' => 'Maintain consistent updates to track progress'
                ];
            }
        }
        
        return $alerts;
    }
function autoUpdateProjectStatus($project_id) {
        global $pdo;
        
        $status_info = detectProjectStatus($project_id);
        
        // Map detected status to database status
        $db_status = match($status_info['status']) {
            'completed' => 'completed',
            'in-progress' => 'in-progress',
            'delayed' => 'delayed',
            'at-risk' => 'at-risk',
            'pending' => 'pending',
            default => 'pending'
        };
        
        $query = "UPDATE projects SET status = :status, updated_at = NOW() WHERE id = :project_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':status', $db_status);
        $stmt->bindParam(':project_id', $project_id);
        
        if ($stmt->execute()) {
            logActivity(0, 'auto_status_update', "Project $project_id auto-updated to status: $db_status");
            return true;
        }
        
        return false;
    }
function autoUpdateAllProjectStatuses() {
        global $pdo;
        
        try {
            // Get all active projects
            $query = "SELECT id, progress, status, approval_status, approved_at, created_at 
                     FROM projects 
                     WHERE status NOT IN ('completed', 'cancelled')
                     LIMIT 500";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updated_count = 0;
            
            foreach ($projects as $project) {
                $project_id = $project['id'];
                $progress = (float)$project['progress'];
                $current_status = $project['status'];
                
                // Rule 1: Auto-complete at 100% progress
                if ($progress >= 100 && $current_status !== 'completed') {
                    $update_query = "UPDATE projects SET status = 'completed', completed_at = NOW(), updated_at = NOW() WHERE id = :project_id";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->bindParam(':project_id', $project_id);
                    if ($update_stmt->execute()) {
                        $updated_count++;
                        // Skip logging for batch updates to avoid foreign key issues
                    }
                    continue;
                }
                
                // Rule 2: Mark as delayed if no updates for 14+ days since approval
                if ($project['approval_status'] === 'approved' && $progress < 100) {
                    $approved_time = strtotime($project['approved_at'] ?? $project['created_at']);
                    $days_since_approval = (time() - $approved_time) / (60 * 60 * 24);
                    
                    if ($days_since_approval >= 14 && $progress === 0) {
                        // No updates and 14+ days passed = Delayed with 0% progress
                        if ($current_status !== 'delayed') {
                            $update_query = "UPDATE projects SET status = 'delayed', updated_at = NOW() WHERE id = :project_id";
                            $update_stmt = $pdo->prepare($update_query);
                            $update_stmt->bindParam(':project_id', $project_id);
                            if ($update_stmt->execute()) {
                                $updated_count++;
                                // Skip logging for batch updates to avoid foreign key issues
                            }
                        }
                        continue;
                    }
                }
                
                // Rule 3: Use ML-based status detection for other cases
                $status_info = detectProjectStatus($project_id);
                $new_status = $status_info['status'];
                
                if ($new_status !== $current_status && $new_status !== 'pending') {
                    $update_query = "UPDATE projects SET status = :status, updated_at = NOW() WHERE id = :project_id";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->bindParam(':status', $new_status);
                    $update_stmt->bindParam(':project_id', $project_id);
                    if ($update_stmt->execute()) {
                        $updated_count++;
                        // Skip logging for batch updates to avoid foreign key issues
                    }
                }
            }
            
            return [
                'success' => true,
                'updated' => $updated_count,
                'total_processed' => count($projects)
            ];
            
        } catch (Exception $e) {
            // Silent failure on batch update - don't block page load
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
function addProjectExpense($expense_data, $user_id, $receipt_path = null, $resource_photos = []) {
        global $pdo;
        
        // Verify user owns the project
        $verify_query = "SELECT id FROM projects WHERE id = :project_id AND beneficiary_id = :user_id";
        $verify_stmt = $pdo->prepare($verify_query);
        $verify_stmt->bindParam(':project_id', $expense_data['project_id']);
        $verify_stmt->bindParam(':user_id', $user_id);
        $verify_stmt->execute();
        
        if ($verify_stmt->rowCount() === 0) {
            return false;
        }
        
        $query = "INSERT INTO project_expenses SET 
            project_id = :project_id,
            amount = :amount,
            category = :category,
            description = :description,
            expense_date = :expense_date,
            receipt_number = :receipt_number,
            vendor = :vendor,
            payment_method = :payment_method,
            notes = :notes,
            receipt_path = :receipt_path,
            resource_photos = :resource_photos,
            created_by = :created_by";
        
        $stmt = $pdo->prepare($query);
        
        // Bind parameters with null handling
        $stmt->bindParam(':project_id', $expense_data['project_id']);
        $stmt->bindParam(':amount', $expense_data['amount']);
        $stmt->bindParam(':category', $expense_data['category']);
        $stmt->bindParam(':description', $expense_data['description']);
        $stmt->bindParam(':expense_date', $expense_data['expense_date']);
        
        // Handle optional fields
        $receipt_number = !empty($expense_data['receipt_number']) ? $expense_data['receipt_number'] : null;
        $vendor = !empty($expense_data['vendor']) ? $expense_data['vendor'] : null;
        $payment_method = !empty($expense_data['payment_method']) ? $expense_data['payment_method'] : 'Cash';
        $notes = !empty($expense_data['notes']) ? $expense_data['notes'] : null;
        
        // Handle receipt path and resource photos
        $receipt_path_value = $receipt_path;
        $resource_photos_json = !empty($resource_photos) ? json_encode($resource_photos) : null;
        
        $stmt->bindParam(':receipt_number', $receipt_number);
        $stmt->bindParam(':vendor', $vendor);
        $stmt->bindParam(':payment_method', $payment_method);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':receipt_path', $receipt_path_value);
        $stmt->bindParam(':resource_photos', $resource_photos_json);
        $stmt->bindParam(':created_by', $user_id);
        
        if ($stmt->execute()) {
            logActivity($user_id, 'expense_added', 'Added expense: ' . $expense_data['description'] . ' - ZMW ' . $expense_data['amount']);
            return true;
        }
        
        return false;
    }
function getProjectExpenses($project_id) {
        global $pdo;
        
        $query = "SELECT * FROM project_expenses 
                  WHERE project_id = :project_id 
                  ORDER BY expense_date DESC, created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function deleteProjectExpense($expense_id, $user_id) {
        global $pdo;
        
        // Verify user owns the expense through project ownership
        $verify_query = "SELECT pe.id, pe.description, pe.amount, pe.receipt_path, pe.resource_photos
                         FROM project_expenses pe 
                         JOIN projects p ON pe.project_id = p.id 
                         WHERE pe.id = :expense_id AND p.beneficiary_id = :user_id";
        $verify_stmt = $pdo->prepare($verify_query);
        $verify_stmt->bindParam(':expense_id', $expense_id);
        $verify_stmt->bindParam(':user_id', $user_id);
        $verify_stmt->execute();
        
        if ($verify_stmt->rowCount() === 0) {
            return false;
        }
        
        $expense = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete associated files
        if (!empty($expense['receipt_path']) && file_exists('../' . $expense['receipt_path'])) {
            unlink('../' . $expense['receipt_path']);
        }
        
        // Delete resource photos
        if (!empty($expense['resource_photos'])) {
            $resource_photos = json_decode($expense['resource_photos'], true);
            if (is_array($resource_photos)) {
                foreach ($resource_photos as $photo_path) {
                    if (file_exists('../' . $photo_path)) {
                        unlink('../' . $photo_path);
                    }
                }
            }
        }
        
        $query = "DELETE FROM project_expenses WHERE id = :expense_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':expense_id', $expense_id);
        
        if ($stmt->execute()) {
            logActivity($user_id, 'expense_deleted', 'Deleted expense: ' . $expense['description'] . ' - ZMW ' . $expense['amount']);
            return true;
        }
        
        return false;
    }
function getTotalProjectExpenses($project_id) {
        global $pdo;
        
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM project_expenses WHERE project_id = :project_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
function getExpenseCategoriesSummary($project_id) {
        global $pdo;
        
        $query = "SELECT category, COUNT(*) as count, SUM(amount) as total_amount 
                  FROM project_expenses 
                  WHERE project_id = :project_id 
                  GROUP BY category 
                  ORDER BY total_amount DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
function calculateAutomaticProgress($project_id) {
        global $pdo;
        
        try {
            $query = "SELECT 
                        COALESCE(SUM(CASE WHEN status = 'completed' THEN weightage ELSE 0 END), 0) as completed_weight,
                        COALESCE(SUM(weightage), 0) as total_weight
                      FROM project_milestones 
                      WHERE project_id = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total_weight'] > 0) {
                $progress = round(($result['completed_weight'] / $result['total_weight']) * 100);
                return min($progress, 100); // Ensure progress doesn't exceed 100%
            }
            
            return 0;
            
        } catch (PDOException $e) {
            error_log("Error calculating automatic progress: " . $e->getMessage());
            return false;
        }
    }
function createProject($projectData) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO projects (title, description, budget, location, status, category, beneficiary_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $projectData['title'],
            $projectData['description'],
            $projectData['budget'],
            $projectData['location'],
            $projectData['status'],
            $projectData['category'],
            $projectData['beneficiary_id'],
            $_SESSION['user_id']
        ]);
    } catch (PDOException $e) {
        error_log("Error creating project: " . $e->getMessage());
        return false;
    }
}
function updateProject($id, $projectData) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE projects 
            SET title = ?, description = ?, budget = ?, location = ?, status = ?, category = ?, beneficiary_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $projectData['title'],
            $projectData['description'],
            $projectData['budget'],
            $projectData['location'],
            $projectData['status'],
            $projectData['category'],
            $projectData['beneficiary_id'],
            $id
        ]);
    } catch (PDOException $e) {
        error_log("Error updating project: " . $e->getMessage());
        return false;
    }
}
function deleteProject($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting project: " . $e->getMessage());
        return false;
    }
}
function getBeneficiaries() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'beneficiary' ORDER BY first_name, last_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting beneficiaries: " . $e->getMessage());
        return [];
    }
}
function assignOfficerToProject($projectId, $officerId) {
    global $pdo;
    
    try {
        // Update project with officer assignment and change status from planning to in-progress
        $stmt = $pdo->prepare("UPDATE projects SET officer_id = ?, status = CASE WHEN status = 'planning' THEN 'in-progress' ELSE status END, actual_start_date = CASE WHEN actual_start_date IS NULL THEN NOW() ELSE actual_start_date END, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$officerId, $projectId]);
    } catch (PDOException $e) {
        error_log("Error assigning officer to project: " . $e->getMessage());
        return false;
    }
}
function removeOfficerFromProject($projectId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE projects SET officer_id = NULL, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$projectId]);
    } catch (PDOException $e) {
        error_log("Error removing officer from project: " . $e->getMessage());
        return false;
    }
}
function getOfficerProjectCount($officerId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as project_count FROM projects WHERE officer_id = ?");
        $stmt->execute([$officerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['project_count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error getting officer project count: " . $e->getMessage());
        return 0;
    }
}
function getAllProjectsWithOfficers() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   u.first_name, u.last_name, 
                   CONCAT(u.first_name, ' ', u.last_name) as beneficiary_name,
                   o.first_name as officer_first_name, 
                   o.last_name as officer_last_name,
                   CONCAT(o.first_name, ' ', o.last_name) as officer_name,
                   o.id as officer_id
            FROM projects p 
            LEFT JOIN users u ON p.beneficiary_id = u.id 
            LEFT JOIN users o ON p.officer_id = o.id 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting projects with officers: " . $e->getMessage());
        return [];
    }
}
function calculateIntelligentProgressPercentage($project_id) {
    global $pdo;
    
    if (!$pdo) {
        return 0;
    }
    
    try {
        // Load project data
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            return 0;
        }
        
        $budget = $project['budget'] ?? 1;
        
        // Factor 1: Budget Utilization / Expenses (40% weight)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_spent FROM project_expenses WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $expense_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_spent = $expense_result['total_spent'] ?? 0;
        $expense_factor = min(100, ($total_spent / $budget) * 100);
        
        // Factor 2: Progress Photo Uploads (30% weight)
        // Count individual photos from all progress updates
        $stmt = $pdo->prepare("SELECT photos FROM project_progress WHERE project_id = ? AND photos IS NOT NULL");
        $stmt->execute([$project_id]);
        $all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_photo_count = 0;
        foreach ($all_results as $row) {
            if (!empty($row['photos'])) {
                $photos_array = json_decode($row['photos'], true);
                if (is_array($photos_array)) {
                    $total_photo_count += count($photos_array);
                }
            }
        }
        
        // Assume 10 photos = 100% for photos factor
        $photo_factor = min(100, ($total_photo_count / 10) * 100);
        
        // Factor 3: Achievements/Milestones (30% weight)
        $stmt = $pdo->prepare("SELECT achievements FROM project_progress WHERE project_id = ? AND achievements IS NOT NULL");
        $stmt->execute([$project_id]);
        $achievements_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_achievement_count = 0;
        foreach ($achievements_results as $row) {
            if (!empty($row['achievements'])) {
                $achievements_array = json_decode($row['achievements'], true);
                if (is_array($achievements_array)) {
                    $total_achievement_count += count(array_filter($achievements_array));
                }
            }
        }
        
        // Assume 5 achievements = 100% for achievements factor
        $achievement_factor = min(100, ($total_achievement_count / 5) * 100);
        
        // Calculate AVERAGE of the three factors
        $progress_percentage = round(($expense_factor + $photo_factor + $achievement_factor) / 3, 2);
        
        return max(0, min(100, $progress_percentage));
        
    } catch (Exception $e) {
        error_log("Error calculating intelligent progress: " . $e->getMessage());
        return 0;
    }
}
function calculateBudgetUtilization($project_id) {
    global $pdo;
    
    try {
        // Get project budget
        $stmt = $pdo->prepare("SELECT budget FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project || $project['budget'] <= 0) {
            return 0;
        }
        
        // Get total expenses
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM project_expenses WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return min(100, ($expenses / $project['budget']) * 100);
    } catch (Exception $e) {
        error_log("Error calculating budget utilization: " . $e->getMessage());
        return 0;
    }
}
function calculatePhotoProgress($project_id) {
    global $pdo;
    
    try {
        // Count all photos from project_progress
        $stmt = $pdo->prepare("SELECT photos FROM project_progress WHERE project_id = ? AND photos IS NOT NULL");
        $stmt->execute([$project_id]);
        $all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_photo_count = 0;
        foreach ($all_results as $row) {
            if (!empty($row['photos'])) {
                $photos_array = json_decode($row['photos'], true);
                if (is_array($photos_array)) {
                    $total_photo_count += count($photos_array);
                }
            }
        }
        
        // 10 photos = 100%
        return min(100, ($total_photo_count / 10) * 100);
    } catch (Exception $e) {
        error_log("Error calculating photo progress: " . $e->getMessage());
        return 0;
    }
}
function calculateAchievementProgress($project_id) {
    global $pdo;
    
    try {
        // Count all achievements from project_progress
        $stmt = $pdo->prepare("SELECT achievements FROM project_progress WHERE project_id = ? AND achievements IS NOT NULL");
        $stmt->execute([$project_id]);
        $achievements_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_achievement_count = 0;
        foreach ($achievements_results as $row) {
            if (!empty($row['achievements'])) {
                $achievements_array = json_decode($row['achievements'], true);
                if (is_array($achievements_array)) {
                    $total_achievement_count += count(array_filter($achievements_array));
                }
            }
        }
        
        // 5 achievements = 100%
        return min(100, ($total_achievement_count / 5) * 100);
    } catch (Exception $e) {
        error_log("Error calculating achievement progress: " . $e->getMessage());
        return 0;
    }
}
function detectProgressAnomalies($project_id) {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    $anomalies = [];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            return [];
        }
        
        // Get latest progress and total expenses
        $stmt = $pdo->prepare("SELECT progress_percentage, created_at FROM project_progress_updates WHERE project_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$project_id]);
        $latest_progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_spent FROM project_expenses WHERE project_id = ? AND approved = 1");
        $stmt->execute([$project_id]);
        $expense_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_spent = $expense_result['total_spent'] ?? 0;
        $budget_utilization = ($total_spent / ($project['budget'] ?? 1)) * 100;
        
        // Anomaly 1: High budget with low progress
        if ($budget_utilization > 70 && $latest_progress && $latest_progress['progress_percentage'] < 40) {
            $anomalies[] = [
                'type' => 'warning',
                'title' => 'Budget-Progress Mismatch',
                'message' => "High budget utilization ({$budget_utilization}%) but progress is only {$latest_progress['progress_percentage']}%"
            ];
        }
        
        // Anomaly 2: Stagnant progress
        if ($latest_progress) {
            $stmt = $pdo->prepare("SELECT progress_percentage FROM project_progress_updates WHERE project_id = ? ORDER BY created_at DESC LIMIT 2 OFFSET 1");
            $stmt->execute([$project_id]);
            $previous = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($previous && $previous['progress_percentage'] == $latest_progress['progress_percentage']) {
                $days_old = (time() - strtotime($latest_progress['created_at'])) / (24 * 3600);
                if ($days_old > 14) {
                    $anomalies[] = [
                        'type' => 'danger',
                        'title' => 'Stagnant Progress',
                        'message' => "Progress has not changed for {$days_old} days"
                    ];
                }
            }
        }
        
        // Anomaly 3: Recent high spending without progress update
        $stmt = $pdo->prepare("SELECT MAX(expense_date) as last_expense FROM project_expenses WHERE project_id = ? AND approved = 1");
        $stmt->execute([$project_id]);
        $last_expense = $stmt->fetch(PDO::FETCH_ASSOC)['last_expense'] ?? null;
        
        if ($last_expense && $latest_progress) {
            $days_since_expense = (time() - strtotime($last_expense)) / (24 * 3600);
            $days_since_update = (time() - strtotime($latest_progress['created_at'])) / (24 * 3600);
            
            if ($days_since_expense < 7 && $days_since_update > 7) {
                $anomalies[] = [
                    'type' => 'info',
                    'title' => 'Update Progress',
                    'message' => "Recent expenses detected but progress update is {$days_since_update} days old"
                ];
            }
        }
        
    } catch (Exception $e) {
        error_log("Error detecting anomalies: " . $e->getMessage());
    }
    
    return $anomalies;
}
function getRecommendedProgressPercentage($project_id) {
    $intelligent_progress = calculateIntelligentProgressPercentage($project_id);
    $anomalies = detectProgressAnomalies($project_id);
    
    return [
        'recommended' => round($intelligent_progress),
        'confidence' => $intelligent_progress > 50 ? 'high' : 'medium',
        'anomalies' => $anomalies
    ];
}
function storeProgressMetadata($project_id, $progress, $source, $use_ml) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO progress_metadata (project_id, progress_percentage, calculation_source, used_ml, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$project_id, $progress, $source, $use_ml ? 1 : 0]);
        return true;
    } catch (Exception $e) {
        error_log("Error storing progress metadata: " . $e->getMessage());
        return false;
    }
}
function autoUpdateProjectProgressFromExpenses($project_id) {
    global $pdo;
    
    try {
        $intelligent_progress = calculateIntelligentProgressPercentage($project_id);
        
        // Only update if change is significant (> 5%)
        $stmt = $pdo->prepare("SELECT progress FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current && abs($intelligent_progress - ($current['progress'] ?? 0)) > 5) {
            $stmt = $pdo->prepare("UPDATE projects SET progress = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$intelligent_progress, $project_id]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error auto-updating project progress: " . $e->getMessage());
        return false;
    }
}
function calculateProjectTimelineProgress($start_date, $end_date) {
    try {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $now = time();
        
        // If project hasn't started yet
        if ($now < $start) {
            return 0;
        }
        
        // If project has ended
        if ($now >= $end) {
            return 100;
        }
        
        // Calculate progress
        $total_duration = $end - $start;
        $elapsed = $now - $start;
        
        if ($total_duration <= 0) {
            return 0;
        }
        
        $progress = ($elapsed / $total_duration) * 100;
        return min(100, max(0, $progress)); // Ensure between 0-100
        
    } catch (Exception $e) {
        error_log("Error calculating timeline progress: " . $e->getMessage());
        return 0;
    }
}

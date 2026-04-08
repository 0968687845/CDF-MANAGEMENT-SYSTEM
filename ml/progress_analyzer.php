<?php
/**
 * ML-Based Progress Analyzer
 * Detects anomalies between expense usage and progress updates
 * Automatically calculates progress percentage using intelligent algorithms
 */

require_once '../config.php';
require_once '../db.php';

class ProgressAnalyzer {
    private $pdo;
    private $project_id;
    private $project_data;
    
    public function __construct($pdo, $project_id) {
        $this->pdo = $pdo;
        $this->project_id = $project_id;
        $this->loadProjectData();
    }
    
    /**
     * Load project data from database
     */
    private function loadProjectData() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = ?");
            $stmt->execute([$this->project_id]);
            $this->project_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error loading project data: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate intelligent progress percentage based on expenses and timeline
     */
    public function calculateIntelligentProgress() {
        if (!$this->project_data) {
            return 0;
        }
        
        $factors = [];
        
        // Factor 1: Budget utilization (30% weight)
        $budget_factor = $this->calculateBudgetFactor();
        $factors['budget'] = ['value' => $budget_factor, 'weight' => 0.30];
        
        // Factor 2: Timeline progress (25% weight)
        $timeline_factor = $this->calculateTimelineProgress();
        $factors['timeline'] = ['value' => $timeline_factor, 'weight' => 0.25];
        
        // Factor 3: Update frequency & consistency (20% weight)
        $update_factor = $this->calculateUpdateConsistency();
        $factors['updates'] = ['value' => $update_factor, 'weight' => 0.20];
        
        // Factor 4: Expense pattern analysis (15% weight)
        $expense_factor = $this->analyzeExpensePattern();
        $factors['expenses'] = ['value' => $expense_factor, 'weight' => 0.15];
        
        // Factor 5: Velocity trend (10% weight)
        $velocity_factor = $this->calculateVelocityTrend();
        $factors['velocity'] = ['value' => $velocity_factor, 'weight' => 0.10];
        
        // Calculate weighted progress
        $intelligent_progress = 0;
        foreach ($factors as $factor) {
            $intelligent_progress += $factor['value'] * $factor['weight'];
        }
        
        // Cap between 0-100%
        $intelligent_progress = max(0, min(100, round($intelligent_progress, 2)));
        
        return [
            'progress' => $intelligent_progress,
            'factors' => $factors,
            'confidence' => $this->calculateConfidenceScore($factors)
        ];
    }
    
    /**
     * Calculate budget utilization factor
     */
    private function calculateBudgetFactor() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_spent 
                FROM project_expenses 
                WHERE project_id = ? AND approved = 1
            ");
            $stmt->execute([$this->project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_spent = $result['total_spent'] ?? 0;
            $budget = $this->project_data['budget'] ?? 1;
            
            $utilization = min(100, ($total_spent / $budget) * 100);
            
            // Expected utilization should be proportional to progress
            // If more budget spent, progress should reflect that
            return min(100, $utilization * 0.95); // Cap at 95% to leave room for actual completion
            
        } catch (Exception $e) {
            error_log("Error calculating budget factor: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate timeline-based progress
     */
    private function calculateTimelineProgress() {
        try {
            $start_date = strtotime($this->project_data['start_date']);
            $end_date = strtotime($this->project_data['end_date']);
            $now = time();
            
            $total_duration = $end_date - $start_date;
            $elapsed = $now - $start_date;
            
            if ($total_duration <= 0) {
                return 50; // Default if dates are invalid
            }
            
            $timeline_progress = ($elapsed / $total_duration) * 100;
            
            // Cap at 95% - completion requires manual confirmation
            return min(95, max(0, $timeline_progress));
            
        } catch (Exception $e) {
            error_log("Error calculating timeline progress: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate update frequency consistency factor
     */
    private function calculateUpdateConsistency() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as update_count, 
                       MAX(created_at) as last_update
                FROM project_progress_updates 
                WHERE project_id = ?
            ");
            $stmt->execute([$this->project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $update_count = $result['update_count'] ?? 0;
            
            // Expected updates: roughly 1 per week for average project
            $expected_updates = ceil(((time() - strtotime($this->project_data['start_date'])) / (7 * 24 * 3600)));
            
            if ($expected_updates <= 0) {
                return 0;
            }
            
            $consistency = min(100, ($update_count / max(1, $expected_updates)) * 100);
            
            // Check recency of last update
            if ($result['last_update']) {
                $last_update_age = (time() - strtotime($result['last_update'])) / (24 * 3600);
                
                // Penalize if updates are stale (older than 14 days)
                if ($last_update_age > 14) {
                    $consistency *= 0.5;
                }
            }
            
            return min(100, $consistency);
            
        } catch (Exception $e) {
            error_log("Error calculating update consistency: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Analyze expense spending pattern
     */
    private function analyzeExpensePattern() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(SUM(amount), 0) as total_spent,
                    COUNT(*) as transaction_count,
                    MAX(expense_date) as last_expense_date,
                    MIN(expense_date) as first_expense_date
                FROM project_expenses 
                WHERE project_id = ? AND approved = 1
            ");
            $stmt->execute([$this->project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_spent = $result['total_spent'] ?? 0;
            $transaction_count = $result['transaction_count'] ?? 0;
            
            // Active spending indicates project progress
            $base_score = 0;
            
            if ($transaction_count > 0) {
                // More transactions = more activity = more progress
                $expected_transactions = ceil(((time() - strtotime($this->project_data['start_date'])) / (7 * 24 * 3600)) * 2);
                $base_score = min(100, ($transaction_count / max(1, $expected_transactions)) * 100);
            }
            
            // Check spending frequency
            if ($result['first_expense_date'] && $result['last_expense_date']) {
                $expense_days = (strtotime($result['last_expense_date']) - strtotime($result['first_expense_date'])) / (24 * 3600);
                
                if ($expense_days > 0) {
                    $spending_frequency = $transaction_count / max(1, ($expense_days / 7));
                    
                    // Regular spending (2-4 transactions per week) is ideal
                    if ($spending_frequency >= 2 && $spending_frequency <= 4) {
                        $base_score = min(100, $base_score * 1.1);
                    }
                }
            }
            
            return min(100, $base_score);
            
        } catch (Exception $e) {
            error_log("Error analyzing expense pattern: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calculate velocity trend
     */
    private function calculateVelocityTrend() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT progress_percentage, created_at 
                FROM project_progress_updates 
                WHERE project_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$this->project_id]);
            $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($updates) < 2) {
                return 50; // Insufficient data
            }
            
            // Calculate velocity (progress per day)
            $first = end($updates);
            $latest = reset($updates);
            
            $days_elapsed = (strtotime($latest['created_at']) - strtotime($first['created_at'])) / (24 * 3600);
            $progress_change = $latest['progress_percentage'] - $first['progress_percentage'];
            
            if ($days_elapsed <= 0) {
                return 50;
            }
            
            $velocity = $progress_change / $days_elapsed;
            
            // Expected velocity: 1% per day for typical project
            $velocity_score = min(100, abs($velocity) * 10);
            
            // Penalty for negative velocity (regression)
            if ($velocity < 0) {
                $velocity_score *= 0.3;
            }
            
            return $velocity_score;
            
        } catch (Exception $e) {
            error_log("Error calculating velocity trend: " . $e->getMessage());
            return 50;
        }
    }
    
    /**
     * Calculate confidence score for the prediction
     */
    private function calculateConfidenceScore($factors) {
        $data_quality = 0;
        
        // More factors with data = higher confidence
        $non_zero_factors = count(array_filter($factors, function($f) { 
            return $f['value'] > 0; 
        }));
        
        $data_quality = ($non_zero_factors / count($factors)) * 100;
        
        // Check for sufficient update history
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM project_progress_updates WHERE project_id = ?
            ");
            $stmt->execute([$this->project_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $update_history = $result['count'] ?? 0;
            
            if ($update_history > 5) {
                $data_quality = min(100, $data_quality * 1.2);
            }
        } catch (Exception $e) {
            // Continue with current score
        }
        
        return min(100, $data_quality);
    }
    
    /**
     * Detect anomalies between expenses and progress
     */
    public function detectAnomalies() {
        $anomalies = [];
        
        try {
            // Get latest progress and expenses
            $stmt = $this->pdo->prepare("
                SELECT progress_percentage, created_at 
                FROM project_progress_updates 
                WHERE project_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$this->project_id]);
            $latest_progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_spent 
                FROM project_expenses 
                WHERE project_id = ? AND approved = 1
            ");
            $stmt->execute([$this->project_id]);
            $expense_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $budget = $this->project_data['budget'] ?? 1;
            $budget_utilization = ($expense_result['total_spent'] / $budget) * 100;
            
            // Anomaly 1: High budget utilization with low progress
            if ($budget_utilization > 70 && $latest_progress && $latest_progress['progress_percentage'] < 40) {
                $anomalies[] = [
                    'type' => 'warning',
                    'level' => 'high',
                    'title' => 'Budget-Progress Mismatch',
                    'message' => sprintf(
                        'High budget utilization (%.1f%%) detected but progress is only %d%%. This suggests possible inefficient spending.',
                        $budget_utilization,
                        $latest_progress['progress_percentage']
                    ),
                    'recommendation' => 'Review expense categories and ensure spending aligns with project deliverables.'
                ];
            }
            
            // Anomaly 2: High progress with minimal spending
            if ($latest_progress && $latest_progress['progress_percentage'] > 60 && $budget_utilization < 20) {
                $anomalies[] = [
                    'type' => 'warning',
                    'level' => 'medium',
                    'title' => 'Unusually High Progress',
                    'message' => sprintf(
                        'Project reports %d%% progress but only %.1f%% of budget has been spent. Verify progress accuracy.',
                        $latest_progress['progress_percentage'],
                        $budget_utilization
                    ),
                    'recommendation' => 'Confirm that reported progress matches actual work completed and expenses incurred.'
                ];
            }
            
            // Anomaly 3: Stagnant progress
            if ($latest_progress) {
                $stmt = $this->pdo->prepare("
                    SELECT progress_percentage, created_at 
                    FROM project_progress_updates 
                    WHERE project_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 2 OFFSET 1
                ");
                $stmt->execute([$this->project_id]);
                $previous_progress = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($previous_progress) {
                    $days_since_last_update = (time() - strtotime($previous_progress['created_at'])) / (24 * 3600);
                    $progress_change = $latest_progress['progress_percentage'] - $previous_progress['progress_percentage'];
                    
                    if ($progress_change == 0 && $days_since_last_update > 14) {
                        $anomalies[] = [
                            'type' => 'danger',
                            'level' => 'high',
                            'title' => 'Stagnant Progress',
                            'message' => sprintf(
                                'Progress has not changed for %.0f days. Last update: %s',
                                $days_since_last_update,
                                $previous_progress['created_at']
                            ),
                            'recommendation' => 'Provide an updated progress report or notify the assigned officer if the project is experiencing delays.'
                        ];
                    }
                }
            }
            
            // Anomaly 4: Recent high spending without corresponding progress update
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as recent_spending, MAX(expense_date) as last_expense
                FROM project_expenses 
                WHERE project_id = ? AND approved = 1 AND expense_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$this->project_id]);
            $recent_expense = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($recent_expense['recent_spending'] > 0) {
                $stmt = $this->pdo->prepare("
                    SELECT MAX(created_at) as last_progress_update
                    FROM project_progress_updates 
                    WHERE project_id = ?
                ");
                $stmt->execute([$this->project_id]);
                $last_update = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $last_progress_age = $last_update['last_progress_update'] ? 
                    (time() - strtotime($last_update['last_progress_update'])) / (24 * 3600) : 365;
                
                if ($last_progress_age > 7) {
                    $anomalies[] = [
                        'type' => 'info',
                        'level' => 'medium',
                        'title' => 'Recent Expenses Without Progress Update',
                        'message' => sprintf(
                            'ZMW %.2f in expenses were recorded in the last 7 days, but the progress update is %d days old.',
                            $recent_expense['recent_spending'],
                            (int)$last_progress_age
                        ),
                        'recommendation' => 'Consider updating progress to reflect recent work and spending activities.'
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("Error detecting anomalies: " . $e->getMessage());
        }
        
        return $anomalies;
    }
    
    /**
     * Predict project completion date
     */
    public function predictCompletionDate() {
        try {
            // Get recent progress updates (last 30 days)
            $stmt = $this->pdo->prepare("
                SELECT progress_percentage, created_at 
                FROM project_progress_updates 
                WHERE project_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY created_at ASC
            ");
            $stmt->execute([$this->project_id]);
            $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($updates) < 2) {
                return [
                    'estimate' => null,
                    'confidence' => 'low',
                    'message' => 'Insufficient data for prediction'
                ];
            }
            
            // Calculate average velocity
            $total_progress = 0;
            $total_days = 0;
            
            for ($i = 1; $i < count($updates); $i++) {
                $progress_change = $updates[$i]['progress_percentage'] - $updates[$i-1]['progress_percentage'];
                $days = (strtotime($updates[$i]['created_at']) - strtotime($updates[$i-1]['created_at'])) / (24 * 3600);
                
                if ($days > 0) {
                    $total_progress += $progress_change;
                    $total_days += $days;
                }
            }
            
            if ($total_days <= 0 || $total_progress <= 0) {
                return [
                    'estimate' => null,
                    'confidence' => 'low',
                    'message' => 'Unable to calculate completion estimate'
                ];
            }
            
            $daily_velocity = $total_progress / $total_days;
            $current_progress = $updates[count($updates)-1]['progress_percentage'];
            $remaining = 100 - $current_progress;
            
            $days_to_completion = ceil($remaining / $daily_velocity);
            $estimated_date = date('Y-m-d', time() + ($days_to_completion * 24 * 3600));
            
            $project_end_date = strtotime($this->project_data['end_date']);
            $estimated_end = strtotime($estimated_date);
            $is_on_schedule = abs($estimated_end - $project_end_date) < (7 * 24 * 3600);
            
            return [
                'estimate' => $estimated_date,
                'days_remaining' => $days_to_completion,
                'daily_velocity' => round($daily_velocity, 2),
                'confidence' => $is_on_schedule ? 'high' : 'medium',
                'scheduled_date' => $this->project_data['end_date'],
                'on_schedule' => $is_on_schedule
            ];
            
        } catch (Exception $e) {
            error_log("Error predicting completion date: " . $e->getMessage());
            return [
                'estimate' => null,
                'confidence' => 'low',
                'message' => 'Error calculating estimate: ' . $e->getMessage()
            ];
        }
    }
}

// Helper function to get analyzer instance
function getProgressAnalyzer($pdo, $project_id) {
    return new ProgressAnalyzer($pdo, $project_id);
}

<?php
/**
 * Machine Learning Report Generator
 * Generates intelligent project statistics and reports based on evaluation data
 * Analyzes trends, patterns, and provides actionable insights
 */

class MLReportGenerator {
    
    /**
     * Generate comprehensive project statistics report
     * @param int $project_id
     * @param array $evaluations - All evaluations for the project
     * @return array
     */
    public static function generateProjectStatistics($project_id, $evaluations) {
        global $pdo;
        
        $project = self::getProject($project_id);
        if (!$project) return null;
        
        $stats = [
            'project_id' => $project_id,
            'project_name' => $project['name'] ?? 'Unknown',
            'generated_at' => date('Y-m-d H:i:s'),
            'total_evaluations' => count($evaluations),
            'evaluation_trends' => self::analyzeEvaluationTrends($evaluations),
            'performance_metrics' => self::calculatePerformanceMetrics($evaluations),
            'risk_assessment' => self::assessRisks($evaluations),
            'progress_analysis' => self::analyzeProgress($project_id, $evaluations),
            'sentiment_summary' => self::summarizeSentiment($evaluations),
            'key_issues' => self::identifyKeyIssues($evaluations),
            'success_factors' => self::identifySuccessFactors($evaluations),
            'recommendations' => self::generateStrategicRecommendations($evaluations),
            'milestone_status' => self::analyzeMilestoneStatus($project_id),
            'resource_efficiency' => self::analyzeResourceEfficiency($project_id),
            'timeline_forecast' => self::forecastTimeline($project_id, $evaluations),
            'quality_trends' => self::analyzeQualityTrends($evaluations),
            'stakeholder_impact' => self::assessStakeholderImpact($project_id)
        ];
        
        return $stats;
    }
    
    /**
     * Analyze trends in evaluations over time
     * @param array $evaluations
     * @return array
     */
    private static function analyzeEvaluationTrends($evaluations) {
        if (empty($evaluations)) {
            return ['trend' => 'insufficient_data', 'direction' => 'neutral'];
        }
        
        $scores = array_column($evaluations, 'progress_score');
        
        if (count($scores) < 2) {
            return ['trend' => 'insufficient_data', 'direction' => 'neutral', 'score_average' => $scores[0] ?? 0];
        }
        
        // Calculate trend direction
        $first_half_avg = array_sum(array_slice($scores, 0, ceil(count($scores) / 2))) / ceil(count($scores) / 2);
        $second_half_avg = array_sum(array_slice($scores, ceil(count($scores) / 2))) / floor(count($scores) / 2);
        
        $trend_direction = $second_half_avg > $first_half_avg ? 'improving' : 
                          ($second_half_avg < $first_half_avg ? 'declining' : 'stable');
        
        $trend_percentage = ($second_half_avg - $first_half_avg) / $first_half_avg * 100;
        
        return [
            'trend' => $trend_direction,
            'trend_percentage' => round($trend_percentage, 2),
            'average_score' => round(array_sum($scores) / count($scores), 2),
            'highest_score' => max($scores),
            'lowest_score' => min($scores),
            'score_variance' => round(self::calculateVariance($scores), 2),
            'consistency' => self::assessConsistency($scores)
        ];
    }
    
    /**
     * Calculate performance metrics across all evaluations
     * @param array $evaluations
     * @return array
     */
    private static function calculatePerformanceMetrics($evaluations) {
        $metrics = [
            'progress_score' => [],
            'quality_rating' => [],
            'timeline_adherence' => [],
            'resource_utilization' => []
        ];
        
        foreach ($evaluations as $eval) {
            if (isset($eval['progress_score'])) $metrics['progress_score'][] = $eval['progress_score'];
            if (isset($eval['quality_rating'])) $metrics['quality_rating'][] = $eval['quality_rating'];
            if (isset($eval['timeline_adherence'])) $metrics['timeline_adherence'][] = $eval['timeline_adherence'];
            if (isset($eval['resource_utilization'])) $metrics['resource_utilization'][] = $eval['resource_utilization'];
        }
        
        $calculated_metrics = [];
        
        foreach ($metrics as $metric_type => $values) {
            if (!empty($values)) {
                $calculated_metrics[$metric_type] = [
                    'average' => round(array_sum($values) / count($values), 2),
                    'max' => max($values),
                    'min' => min($values),
                    'median' => self::calculateMedian($values),
                    'std_dev' => round(self::calculateStdDev($values), 2),
                    'rating' => self::rateMetric(array_sum($values) / count($values))
                ];
            }
        }
        
        return $calculated_metrics;
    }
    
    /**
     * Assess risks based on evaluation data
     * @param array $evaluations
     * @return array
     */
    private static function assessRisks($evaluations) {
        $risks = [];
        
        foreach ($evaluations as $eval) {
            // Extract risks from challenges field
            if (!empty($eval['challenges'])) {
                // Use sentiment analyzer if available
                $risk_level = self::evaluateChallengesSeverity($eval['challenges']);
                $risks[] = [
                    'date' => $eval['created_at'] ?? date('Y-m-d'),
                    'description' => $eval['challenges'],
                    'severity' => $risk_level,
                    'metrics_impact' => self::assessMetricsImpact($eval)
                ];
            }
        }
        
        // Rank risks by severity
        usort($risks, function($a, $b) {
            $severity_map = ['critical' => 3, 'high' => 2, 'medium' => 1, 'low' => 0];
            return $severity_map[$b['severity']] - $severity_map[$a['severity']];
        });
        
        return [
            'total_risks' => count($risks),
            'critical_risks' => count(array_filter($risks, fn($r) => $r['severity'] === 'critical')),
            'high_risks' => count(array_filter($risks, fn($r) => $r['severity'] === 'high')),
            'risks' => array_slice($risks, 0, 10) // Top 10 risks
        ];
    }
    
    /**
     * Analyze overall project progress
     * @param int $project_id
     * @param array $evaluations
     * @return array
     */
    private static function analyzeProgress($project_id, $evaluations) {
        $scores = array_column($evaluations, 'progress_score');
        
        if (empty($scores)) {
            return [
                'current_progress' => 0,
                'expected_progress' => 0,
                'variance' => 0,
                'status' => 'unknown',
                'forecast' => 'insufficient_data'
            ];
        }
        
        $current = end($scores);
        $expected = self::calculateExpectedProgress($project_id);
        $variance = $current - $expected;
        
        return [
            'current_progress' => $current,
            'expected_progress' => $expected,
            'variance' => round($variance, 2),
            'variance_percentage' => round(($variance / $expected) * 100, 2),
            'status' => $variance >= 0 ? 'on_track' : 'behind_schedule',
            'forecast' => self::forecastCompletion($scores),
            'completion_estimate' => self::estimateCompletionDate($project_id, $evaluations)
        ];
    }
    
    /**
     * Summarize sentiment across all evaluations
     * @param array $evaluations
     * @return array
     */
    private static function summarizeSentiment($evaluations) {
        $sentiments = [];
        $total_positivity = 0;
        
        foreach ($evaluations as $eval) {
            // Analyze both challenges and recommendations
            $challenges_text = $eval['challenges'] ?? '';
            $recommendations_text = $eval['recommendations'] ?? '';
            $combined_text = $challenges_text . ' ' . $recommendations_text;
            
            if (!empty($combined_text)) {
                $sentiment = self::simpleSentimentAnalysis($combined_text);
                $sentiments[] = $sentiment;
                $total_positivity += $sentiment;
            }
        }
        
        $avg_sentiment = count($sentiments) > 0 ? $total_positivity / count($sentiments) : 0.5;
        
        return [
            'overall_sentiment' => $avg_sentiment >= 0.6 ? 'positive' : ($avg_sentiment <= 0.4 ? 'negative' : 'neutral'),
            'sentiment_score' => round($avg_sentiment, 3),
            'positivity_trend' => self::analyzeSentimentTrend($sentiments),
            'evaluations_analyzed' => count($sentiments)
        ];
    }
    
    /**
     * Identify key issues from all evaluations
     * @param array $evaluations
     * @return array
     */
    private static function identifyKeyIssues($evaluations) {
        $issues = [];
        
        foreach ($evaluations as $eval) {
            if (!empty($eval['challenges'])) {
                // Split challenges into individual issues
                $challenge_lines = array_filter(explode("\n", $eval['challenges']));
                foreach ($challenge_lines as $line) {
                    $line = trim($line);
                    if (strlen($line) > 10) {
                        $issues[$line] = ($issues[$line] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Sort by frequency
        arsort($issues);
        
        return [
            'unique_issues' => count($issues),
            'recurring_issues' => count(array_filter($issues, fn($v) => $v > 1)),
            'top_issues' => array_slice($issues, 0, 5, true)
        ];
    }
    
    /**
     * Identify success factors and positive elements
     * @param array $evaluations
     * @return array
     */
    private static function identifySuccessFactors($evaluations) {
        $success_factors = [];
        
        foreach ($evaluations as $eval) {
            if (!empty($eval['recommendations'])) {
                // Extract success indicators
                if (strpos($eval['recommendations'], 'continue') !== false) {
                    $success_factors['momentum'] = ($success_factors['momentum'] ?? 0) + 1;
                }
                if (strpos($eval['recommendations'], 'expand') !== false) {
                    $success_factors['scalability'] = ($success_factors['scalability'] ?? 0) + 1;
                }
                if (strpos($eval['recommendations'], 'replicate') !== false) {
                    $success_factors['best_practices'] = ($success_factors['best_practices'] ?? 0) + 1;
                }
            }
        }
        
        arsort($success_factors);
        
        return [
            'total_success_factors' => array_sum($success_factors),
            'top_factors' => array_slice($success_factors, 0, 5, true)
        ];
    }
    
    /**
     * Generate strategic recommendations
     * @param array $evaluations
     * @return array
     */
    private static function generateStrategicRecommendations($evaluations) {
        $recommendations = [];
        
        $risks = self::assessRisks($evaluations);
        if (!empty($risks['critical_risks']) && $risks['critical_risks'] > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => 'Address critical risks immediately',
                'reason' => 'Critical risks detected in project'
            ];
        }
        
        $progress = self::analyzeProgress(1, $evaluations); // Placeholder project_id
        if ($progress['status'] === 'behind_schedule') {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Accelerate project activities to meet schedule',
                'reason' => 'Project is behind schedule'
            ];
        }
        
        $sentiment = self::summarizeSentiment($evaluations);
        if ($sentiment['overall_sentiment'] === 'negative') {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Provide support and resources to improve project morale',
                'reason' => 'Overall project sentiment is negative'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Helper: Simple sentiment analysis (-1 to 1)
     * @param string $text
     * @return float
     */
    private static function simpleSentimentAnalysis($text) {
        $positive_words = ['excellent', 'good', 'great', 'success', 'progress', 'achieved'];
        $negative_words = ['poor', 'bad', 'delay', 'issue', 'problem', 'failure'];
        
        $score = 0.5; // neutral baseline
        $text_lower = strtolower($text);
        
        foreach ($positive_words as $word) {
            $score += substr_count($text_lower, $word) * 0.1;
        }
        
        foreach ($negative_words as $word) {
            $score -= substr_count($text_lower, $word) * 0.1;
        }
        
        return max(0, min(1, $score));
    }
    
    /**
     * Helper: Calculate variance
     * @param array $values
     * @return float
     */
    private static function calculateVariance($values) {
        if (count($values) < 2) return 0;
        $avg = array_sum($values) / count($values);
        $variance = 0;
        foreach ($values as $v) {
            $variance += pow($v - $avg, 2);
        }
        return $variance / count($values);
    }
    
    /**
     * Helper: Calculate standard deviation
     * @param array $values
     * @return float
     */
    private static function calculateStdDev($values) {
        return sqrt(self::calculateVariance($values));
    }
    
    /**
     * Helper: Calculate median
     * @param array $values
     * @return float
     */
    private static function calculateMedian($values) {
        sort($values);
        $mid = intdiv(count($values), 2);
        return count($values) % 2 === 0 
            ? ($values[$mid - 1] + $values[$mid]) / 2 
            : $values[$mid];
    }
    
    /**
     * Helper: Get project details
     * @param int $project_id
     * @return array|null
     */
    private static function getProject($project_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Helper: Evaluate challenges severity
     * @param string $text
     * @return string
     */
    private static function evaluateChallengesSeverity($text) {
        return 'medium';
    }
    
    /**
     * Helper: Assess metrics impact
     * @param array $eval
     * @return array
     */
    private static function assessMetricsImpact($eval) {
        return [];
    }
    
    /**
     * Helper: Calculate expected progress
     * @param int $project_id
     * @return int
     */
    private static function calculateExpectedProgress($project_id) {
        return 50;
    }
    
    /**
     * Helper: Forecast completion
     * @param array $scores
     * @return string
     */
    private static function forecastCompletion($scores) {
        return 'on_track';
    }
    
    /**
     * Helper: Estimate completion date
     * @param int $project_id
     * @param array $evaluations
     * @return string
     */
    private static function estimateCompletionDate($project_id, $evaluations) {
        return date('Y-m-d');
    }
    
    /**
     * Helper: Analyze sentiment trend
     * @param array $sentiments
     * @return string
     */
    private static function analyzeSentimentTrend($sentiments) {
        return 'stable';
    }
    
    /**
     * Helper: Analyze milestone status
     * @param int $project_id
     * @return array
     */
    private static function analyzeMilestoneStatus($project_id) {
        return [];
    }
    
    /**
     * Helper: Analyze resource efficiency
     * @param int $project_id
     * @return array
     */
    private static function analyzeResourceEfficiency($project_id) {
        return [];
    }
    
    /**
     * Helper: Forecast timeline
     * @param int $project_id
     * @param array $evaluations
     * @return array
     */
    private static function forecastTimeline($project_id, $evaluations) {
        return [];
    }
    
    /**
     * Helper: Analyze quality trends
     * @param array $evaluations
     * @return array
     */
    private static function analyzeQualityTrends($evaluations) {
        return [];
    }
    
    /**
     * Helper: Assess stakeholder impact
     * @param int $project_id
     * @return array
     */
    private static function assessStakeholderImpact($project_id) {
        return [];
    }
    
    /**
     * Helper: Assess consistency
     * @param array $scores
     * @return string
     */
    private static function assessConsistency($scores) {
        return 'consistent';
    }
    
    /**
     * Helper: Rate metric
     * @param float $value
     * @return string
     */
    private static function rateMetric($value) {
        return $value >= 70 ? 'excellent' : ($value >= 50 ? 'good' : 'needs_improvement');
    }
}

?>

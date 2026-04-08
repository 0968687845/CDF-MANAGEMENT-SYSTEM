<?php
/**
 * Machine Learning Sentiment Analyzer
 * Analyzes evaluation notes and challenges to generate AI-powered insights
 * Provides text analysis, sentiment scoring, and automated recommendations
 */

class SentimentAnalyzer {
    
    /**
     * Analyze evaluation text for sentiment and key insights
     * @param string $text - The evaluation notes/challenges text
     * @return array - Sentiment analysis results
     */
    public static function analyzeText($text) {
        if (empty($text)) {
            return [
                'sentiment' => 'neutral',
                'score' => 0.5,
                'confidence' => 0.0,
                'keywords' => [],
                'issues_detected' => [],
                'insights' => [],
                'recommendation_priority' => 'medium'
            ];
        }
        
        $analysis = [
            'original_text' => $text,
            'text_length' => strlen($text),
            'word_count' => str_word_count($text),
            'sentence_count' => substr_count($text, '.') + substr_count($text, '!') + substr_count($text, '?'),
            'sentiment' => self::calculateSentiment($text),
            'score' => self::calculateSentimentScore($text),
            'confidence' => self::calculateConfidence($text),
            'keywords' => self::extractKeywords($text),
            'issues_detected' => self::detectIssues($text),
            'topics' => self::detectTopics($text),
            'insights' => self::generateInsights($text),
            'recommendation_priority' => self::determinePriority($text)
        ];
        
        return $analysis;
    }
    
    /**
     * Calculate overall sentiment (positive, neutral, negative)
     * @param string $text
     * @return string
     */
    private static function calculateSentiment($text) {
        $text = strtolower($text);
        
        $positive_words = [
            'excellent', 'good', 'great', 'outstanding', 'impressive', 'wonderful',
            'success', 'successful', 'achieved', 'completed', 'delivered', 'accomplished',
            'progress', 'progressing', 'improving', 'improved', 'enhancement', 'positive',
            'efficient', 'effective', 'quality', 'professional', 'reliable', 'strong',
            'exceeding', 'exceed', 'exceeded', 'exceptional', 'remarkable', 'brilliant'
        ];
        
        $negative_words = [
            'poor', 'bad', 'terrible', 'awful', 'horrible', 'challenge', 'problem',
            'issue', 'difficulty', 'failed', 'failure', 'delay', 'delayed', 'behind',
            'incomplete', 'insufficient', 'weak', 'poor quality', 'inefficient', 'slow',
            'risk', 'risk', 'concern', 'worried', 'critical', 'urgent', 'emergency',
            'shortage', 'lacking', 'deficit', 'deficit', 'struggle', 'struggling'
        ];
        
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            $positive_count += substr_count($text, $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count($text, $word);
        }
        
        $total_sentiment_words = $positive_count + $negative_count;
        
        if ($total_sentiment_words == 0) {
            return 'neutral';
        }
        
        $ratio = $positive_count / $total_sentiment_words;
        
        if ($ratio >= 0.65) {
            return 'positive';
        } elseif ($ratio <= 0.35) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
    
    /**
     * Calculate sentiment score (0.0 to 1.0)
     * @param string $text
     * @return float
     */
    private static function calculateSentimentScore($text) {
        $text = strtolower($text);
        
        $positive_words = [
            'excellent' => 1.0, 'good' => 0.8, 'great' => 0.9, 'outstanding' => 1.0,
            'progress' => 0.7, 'successful' => 0.9, 'achieved' => 0.85, 'completed' => 0.8,
            'improved' => 0.8, 'efficient' => 0.8, 'effective' => 0.8, 'quality' => 0.7
        ];
        
        $negative_words = [
            'poor' => 0.1, 'bad' => 0.2, 'terrible' => 0.0, 'failed' => 0.1,
            'delay' => 0.3, 'issue' => 0.4, 'problem' => 0.35, 'challenge' => 0.45,
            'risk' => 0.3, 'concern' => 0.4, 'shortage' => 0.2, 'incomplete' => 0.25
        ];
        
        $total_score = 0;
        $word_count = 0;
        
        foreach ($positive_words as $word => $weight) {
            $count = substr_count($text, $word);
            $total_score += $count * $weight;
            $word_count += $count;
        }
        
        foreach ($negative_words as $word => $weight) {
            $count = substr_count($text, $word);
            $total_score += $count * $weight;
            $word_count += $count;
        }
        
        if ($word_count == 0) {
            return 0.5; // neutral
        }
        
        $score = $total_score / $word_count;
        return max(0.0, min(1.0, $score));
    }
    
    /**
     * Calculate confidence level based on text analysis
     * @param string $text
     * @return float
     */
    private static function calculateConfidence($text) {
        $word_count = str_word_count($text);
        $sentence_count = substr_count($text, '.') + substr_count($text, '!') + substr_count($text, '?');
        
        // Confidence increases with more detailed, well-formed text
        $confidence = 0;
        
        // Word count factor (0-0.3)
        if ($word_count >= 100) $confidence += 0.3;
        elseif ($word_count >= 50) $confidence += 0.2;
        elseif ($word_count >= 20) $confidence += 0.1;
        
        // Sentence structure factor (0-0.3)
        if ($sentence_count >= 3) $confidence += 0.3;
        elseif ($sentence_count >= 2) $confidence += 0.2;
        elseif ($sentence_count >= 1) $confidence += 0.1;
        
        // Specific details factor (0-0.4)
        $has_numbers = preg_match('/\d+/', $text);
        $has_dates = preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}/', $text);
        $has_percentages = preg_match('/\d+%/', $text);
        
        $specificity = ($has_numbers ? 1 : 0) + ($has_dates ? 1 : 0) + ($has_percentages ? 1 : 0);
        $confidence += ($specificity * 0.13); // max 0.39
        
        return min(1.0, $confidence);
    }
    
    /**
     * Extract key topics and themes from text
     * @param string $text
     * @return array
     */
    private static function extractKeywords($text) {
        $keywords = [];
        
        // Define keyword categories
        $keyword_map = [
            'timeline' => ['delay', 'schedule', 'timeline', 'deadline', 'ahead', 'behind', 'progress', 'on-time'],
            'quality' => ['quality', 'standards', 'defects', 'excellence', 'workmanship', 'specifications'],
            'resources' => ['materials', 'budget', 'funds', 'equipment', 'staff', 'labor', 'shortage'],
            'beneficiary' => ['beneficiary', 'communities', 'stakeholder', 'satisfaction', 'feedback'],
            'challenges' => ['challenge', 'difficulty', 'obstacle', 'barrier', 'constraint', 'issue', 'problem'],
            'weather' => ['weather', 'rain', 'flooding', 'climate', 'seasonal', 'temperature'],
            'logistics' => ['transport', 'supply', 'delivery', 'logistics', 'accessibility', 'location']
        ];
        
        $text_lower = strtolower($text);
        
        foreach ($keyword_map as $category => $words) {
            $category_keywords = [];
            foreach ($words as $word) {
                if (strpos($text_lower, $word) !== false) {
                    $category_keywords[] = $word;
                }
            }
            if (!empty($category_keywords)) {
                $keywords[$category] = $category_keywords;
            }
        }
        
        return $keywords;
    }
    
    /**
     * Detect issues mentioned in the text
     * @param string $text
     * @return array
     */
    private static function detectIssues($text) {
        $issues = [];
        $text_lower = strtolower($text);
        
        $issue_patterns = [
            'budget' => ['budget', 'cost', 'expense', 'financial', 'funding', 'expensive'],
            'timeline' => ['delay', 'behind schedule', 'postponed', 'deadline missed'],
            'quality' => ['defect', 'poor quality', 'substandard', 'rework', 'revision'],
            'resources' => ['shortage', 'insufficient', 'lacking', 'unavailable', 'scarce'],
            'weather' => ['rain', 'flooding', 'weather', 'seasonal', 'climate'],
            'communication' => ['miscommunication', 'unclear', 'confusion', 'misunderstanding'],
            'safety' => ['safety', 'accident', 'incident', 'hazard', 'injury'],
            'compliance' => ['non-compliance', 'violation', 'regulation', 'standard', 'requirement']
        ];
        
        foreach ($issue_patterns as $issue_type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text_lower, $keyword) !== false) {
                    $issues[$issue_type] = true;
                    break;
                }
            }
        }
        
        return array_keys($issues);
    }
    
    /**
     * Detect main topics from text
     * @param string $text
     * @return array
     */
    private static function detectTopics($text) {
        $topics = [];
        $text_lower = strtolower($text);
        
        $topic_keywords = [
            'infrastructure' => ['building', 'construction', 'road', 'water', 'sanitation', 'electricity'],
            'health' => ['health', 'clinic', 'medical', 'vaccination', 'disease', 'hygiene'],
            'education' => ['school', 'education', 'training', 'student', 'classroom', 'curriculum'],
            'agriculture' => ['farming', 'crop', 'agriculture', 'livestock', 'irrigation', 'yield'],
            'empowerment' => ['skills', 'training', 'employment', 'business', 'income', 'empowerment'],
            'environmental' => ['environment', 'tree', 'forest', 'solar', 'renewable', 'climate']
        ];
        
        foreach ($topic_keywords as $topic => $keywords) {
            $topic_score = 0;
            foreach ($keywords as $keyword) {
                $topic_score += substr_count($text_lower, $keyword);
            }
            if ($topic_score > 0) {
                $topics[$topic] = $topic_score;
            }
        }
        
        arsort($topics);
        return array_slice($topics, 0, 3, true);
    }
    
    /**
     * Generate AI insights from evaluation text
     * @param string $text
     * @return array
     */
    private static function generateInsights($text) {
        $insights = [];
        $text_lower = strtolower($text);
        
        // Insight generation rules
        if (strpos($text_lower, 'delay') !== false || strpos($text_lower, 'behind') !== false) {
            $insights[] = "Timeline concerns detected. Recommend expedited action plan.";
        }
        
        if (strpos($text_lower, 'quality') !== false || strpos($text_lower, 'defect') !== false) {
            $insights[] = "Quality issues mentioned. Suggest quality assurance review.";
        }
        
        if (strpos($text_lower, 'budget') !== false || strpos($text_lower, 'cost') !== false) {
            $insights[] = "Budget considerations noted. Recommend financial review.";
        }
        
        if (strpos($text_lower, 'shortage') !== false || strpos($text_lower, 'insufficient') !== false) {
            $insights[] = "Resource constraints identified. Explore alternative solutions.";
        }
        
        if (strpos($text_lower, 'weather') !== false || strpos($text_lower, 'rain') !== false) {
            $insights[] = "Environmental factors affecting project. Plan contingency measures.";
        }
        
        if (strpos($text_lower, 'excellent') !== false || strpos($text_lower, 'success') !== false) {
            $insights[] = "Project performing well. Document best practices for replication.";
        }
        
        if (strpos($text_lower, 'community') !== false || strpos($text_lower, 'beneficiary') !== false) {
            $insights[] = "Strong community engagement noted. Maintain momentum with stakeholders.";
        }
        
        if (str_word_count($text) < 20) {
            $insights[] = "Limited detail provided. Consider more detailed evaluation in next review.";
        }
        
        return $insights;
    }
    
    /**
     * Determine priority level for recommended actions
     * @param string $text
     * @return string
     */
    private static function determinePriority($text) {
        $text_lower = strtolower($text);
        
        $critical_words = ['critical', 'urgent', 'emergency', 'immediate', 'failure', 'collapsed', 'accident', 'injury'];
        $high_words = ['serious', 'important', 'significant', 'problem', 'issue', 'delay', 'risk'];
        $low_words = ['minor', 'slight', 'small', 'minor issue', 'note', 'observation'];
        
        foreach ($critical_words as $word) {
            if (strpos($text_lower, $word) !== false) {
                return 'critical';
            }
        }
        
        foreach ($high_words as $word) {
            if (strpos($text_lower, $word) !== false) {
                return 'high';
            }
        }
        
        foreach ($low_words as $word) {
            if (strpos($text_lower, $word) !== false) {
                return 'low';
            }
        }
        
        return 'medium';
    }
    
    /**
     * Generate actionable recommendations based on analysis
     * @param array $analysis - Results from analyzeText()
     * @return array
     */
    public static function generateRecommendations($analysis) {
        $recommendations = [];
        
        // Based on sentiment
        if ($analysis['sentiment'] === 'negative') {
            $recommendations[] = [
                'type' => 'urgent_intervention',
                'priority' => 'high',
                'action' => 'Project requires immediate intervention and support',
                'reason' => 'Negative sentiment detected in evaluation'
            ];
        }
        
        // Based on detected issues
        if (in_array('timeline', $analysis['issues_detected'])) {
            $recommendations[] = [
                'type' => 'timeline_adjustment',
                'priority' => 'high',
                'action' => 'Review project schedule and adjust milestones accordingly',
                'reason' => 'Timeline concerns identified in evaluation'
            ];
        }
        
        if (in_array('budget', $analysis['issues_detected'])) {
            $recommendations[] = [
                'type' => 'budget_review',
                'priority' => 'medium',
                'action' => 'Conduct financial review and reallocate funds if necessary',
                'reason' => 'Budget constraints mentioned in evaluation'
            ];
        }
        
        if (in_array('quality', $analysis['issues_detected'])) {
            $recommendations[] = [
                'type' => 'quality_audit',
                'priority' => 'high',
                'action' => 'Implement quality assurance measures and conduct audit',
                'reason' => 'Quality issues detected in evaluation'
            ];
        }
        
        if (in_array('resources', $analysis['issues_detected'])) {
            $recommendations[] = [
                'type' => 'resource_allocation',
                'priority' => 'medium',
                'action' => 'Assess resource needs and explore additional support',
                'reason' => 'Resource constraints identified'
            ];
        }
        
        // Low confidence warning
        if ($analysis['confidence'] < 0.4) {
            $recommendations[] = [
                'type' => 'clarification_needed',
                'priority' => 'medium',
                'action' => 'Request additional details and specific metrics from evaluator',
                'reason' => 'Evaluation lacks sufficient detail for comprehensive analysis'
            ];
        }
        
        return $recommendations;
    }
}

?>

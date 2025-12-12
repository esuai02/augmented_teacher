<?php
/**
 * Agent 01: Real-time Onboarding
 * 실시간 온보딩 에이전트
 * 
 * PHP 5.6+ compatible
 */

require_once(__DIR__ . '/base_agent.php');

class Agent01Onboarding extends BaseAgent {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            1,
            'Real-time Onboarding',
            '학생 프로필 및 학습 이력을 로드하고 초기 분석을 수행합니다'
        );
    }
    
    /**
     * Execute onboarding logic
     * 
     * @param array $inputs Input parameters
     * @param array $context Execution context
     * @return array Execution results
     */
    public function execute(array $inputs, array $context) {
        $this->log('Starting onboarding process');
        
        // Validate inputs
        if (!$this->validateInputs($inputs)) {
            $this->log('Invalid inputs provided', 'error');
            return $this->formatResponse(false, array(), 'Invalid inputs');
        }
        
        // Extract student ID
        $studentId = isset($inputs['student_id']) ? $inputs['student_id'] : null;
        
        if (empty($studentId)) {
            $this->log('Student ID is required', 'error');
            return $this->formatResponse(false, array(), 'Student ID is required');
        }
        
        // Simulate loading student profile
        $profile = $this->loadStudentProfile($studentId);
        
        // Simulate loading learning history
        $history = $this->loadLearningHistory($studentId);
        
        // Analyze MBTI type
        $mbtiType = isset($inputs['mbti_type']) ? $inputs['mbti_type'] : $this->analyzeMBTI($profile);
        
        // Calculate initial metrics
        $metrics = $this->calculateInitialMetrics($profile, $history);
        
        // Prepare outputs
        $outputs = array(
            'profile' => $profile,
            'history_summary' => $history,
            'mbti_type' => $mbtiType,
            'initial_metrics' => $metrics,
            'recommendations' => $this->generateInitialRecommendations($profile, $mbtiType)
        );
        
        // Calculate KPI
        $kpi = array(
            'profile_completeness' => $this->calculateProfileCompleteness($profile),
            'data_quality_score' => $this->calculateDataQuality($profile, $history)
        );
        
        $this->log('Onboarding completed successfully');
        
        // Create response
        $response = new AgentResponse('ok', $this->getId(), $outputs, $kpi, array(
            'explain' => '학생 프로필과 학습 이력을 성공적으로 로드했습니다'
        ));
        
        return $response->toArray();
    }
    
    /**
     * Validate inputs specific to onboarding
     * 
     * @param array $inputs
     * @return bool
     */
    public function validateInputs(array $inputs) {
        // Check for required fields
        if (!isset($inputs['student_id'])) {
            return false;
        }
        
        // Additional validation can be added here
        return true;
    }
    
    /**
     * Load student profile (simulation)
     * 
     * @param string $studentId
     * @return array
     */
    private function loadStudentProfile($studentId) {
        // In real implementation, this would query the database
        return array(
            'student_id' => $studentId,
            'name' => '홍길동',
            'grade' => '중2',
            'class' => '3반',
            'age' => 14,
            'learning_style' => 'visual',
            'preferences' => array('개념이해', '문제풀이'),
            'strengths' => array('논리적 사고', '패턴 인식'),
            'weaknesses' => array('계산 실수', '시간 관리')
        );
    }
    
    /**
     * Load learning history (simulation)
     * 
     * @param string $studentId
     * @return array
     */
    private function loadLearningHistory($studentId) {
        // In real implementation, this would query the database
        return array(
            'total_sessions' => 45,
            'total_hours' => 67.5,
            'average_score' => 78.5,
            'recent_topics' => array('이차방정식', '도형의 성질', '확률'),
            'error_patterns' => array('부호 실수', '공식 혼동'),
            'improvement_trend' => 'positive'
        );
    }
    
    /**
     * Analyze MBTI type
     * 
     * @param array $profile
     * @return string
     */
    private function analyzeMBTI($profile) {
        // Simple logic to determine MBTI based on profile
        // In real implementation, this would use more sophisticated analysis
        $learningStyle = isset($profile['learning_style']) ? $profile['learning_style'] : 'visual';
        
        switch ($learningStyle) {
            case 'visual':
                return 'INTJ';
            case 'auditory':
                return 'ENFP';
            case 'kinesthetic':
                return 'ESTP';
            default:
                return 'ISTJ';
        }
    }
    
    /**
     * Calculate initial metrics
     * 
     * @param array $profile
     * @param array $history
     * @return array
     */
    private function calculateInitialMetrics($profile, $history) {
        return array(
            'engagement_score' => min(100, ($history['total_sessions'] / 50) * 100),
            'performance_score' => $history['average_score'],
            'consistency_score' => 75, // Placeholder
            'potential_score' => 85 // Placeholder
        );
    }
    
    /**
     * Generate initial recommendations
     * 
     * @param array $profile
     * @param string $mbtiType
     * @return array
     */
    private function generateInitialRecommendations($profile, $mbtiType) {
        $recommendations = array();
        
        // Based on MBTI type
        if (strpos($mbtiType, 'INT') === 0) {
            $recommendations[] = '개념 이해 중심의 학습 추천';
            $recommendations[] = '독립적인 문제 해결 시간 제공';
        } elseif (strpos($mbtiType, 'ENF') === 0) {
            $recommendations[] = '그룹 토론 활동 추천';
            $recommendations[] = '창의적 문제 해결 과제 제공';
        }
        
        // Based on weaknesses
        if (in_array('계산 실수', $profile['weaknesses'])) {
            $recommendations[] = '계산 정확도 향상 훈련 필요';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate profile completeness
     * 
     * @param array $profile
     * @return float
     */
    private function calculateProfileCompleteness($profile) {
        $requiredFields = array('student_id', 'name', 'grade', 'class', 'learning_style');
        $presentFields = 0;
        
        foreach ($requiredFields as $field) {
            if (!empty($profile[$field])) {
                $presentFields++;
            }
        }
        
        return ($presentFields / count($requiredFields)) * 100;
    }
    
    /**
     * Calculate data quality score
     * 
     * @param array $profile
     * @param array $history
     * @return float
     */
    private function calculateDataQuality($profile, $history) {
        $score = 0;
        
        // Check profile completeness
        if (!empty($profile) && count($profile) >= 5) {
            $score += 40;
        }
        
        // Check history availability
        if (!empty($history) && isset($history['total_sessions']) && $history['total_sessions'] > 10) {
            $score += 40;
        }
        
        // Check recent activity
        if (isset($history['total_sessions']) && $history['total_sessions'] > 30) {
            $score += 20;
        }
        
        return min(100, $score);
    }
}

// Register the agent with the factory
AgentFactory::register(1, 'Agent01Onboarding');

?>
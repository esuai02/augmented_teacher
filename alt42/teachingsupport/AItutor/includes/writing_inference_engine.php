<?php
/**
 * 필기 유추 엔진
 * 필기 패턴을 분석하여 인지 상태, 오류 유형, 진행 상태를 유추
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

require_once(__DIR__ . '/intervention_manager.php');

class WritingInferenceEngine {
    private $interventionManager;
    
    public function __construct() {
        $this->interventionManager = new InterventionManager();
    }
    
    /**
     * 인지 상태 유추
     */
    public function inferCognitiveState($pattern, $confidence) {
        $patternId = $pattern['pattern_id'] ?? '';
        $inference = $pattern['inference'] ?? '';
        
        $stateMapping = [
            'PATTERN_PAUSE_3S' => [
                'state' => '막힘 또는 사고 중',
                'confidence' => 0.6,
                'alternatives' => [
                    ['state' => '막힘', 'probability' => 0.4],
                    ['state' => '사고 중', 'probability' => 0.6]
                ]
            ],
            'PATTERN_PAUSE_5S' => [
                'state' => '백지 막힘 가능성',
                'confidence' => 0.7,
                'alternatives' => [
                    ['state' => '백지 막힘', 'probability' => 0.7],
                    ['state' => '깊은 사고', 'probability' => 0.3]
                ]
            ],
            'PATTERN_PAUSE_10S' => [
                'state' => '백지 막힘',
                'confidence' => 0.9,
                'alternatives' => []
            ],
            'PATTERN_ERASE_REPEAT' => [
                'state' => '혼란, 불확실',
                'confidence' => 0.85,
                'alternatives' => []
            ],
            'PATTERN_OVERWRITE' => [
                'state' => '자기 수정 시도',
                'confidence' => 0.7,
                'alternatives' => []
            ],
            'PATTERN_FAST_PROGRESS' => [
                'state' => '이해하고 진행 중',
                'confidence' => 0.9,
                'alternatives' => []
            ]
        ];
        
        return $stateMapping[$patternId] ?? [
            'state' => $inference,
            'confidence' => $confidence,
            'alternatives' => []
        ];
    }
    
    /**
     * 오류 유형 유추
     */
    public function inferErrorType($pattern) {
        $patternId = $pattern['pattern_id'] ?? '';
        
        $errorMapping = [
            'PATTERN_SIGN_CORRECTION' => [
                'error_type' => '부호 실수',
                'confidence' => 0.8,
                'suggested_intervention' => 'INT_6_1' // 즉시 교정
            ],
            'PATTERN_FRACTION_LINE_UNCLEAR' => [
                'error_type' => '분수 개념 혼란',
                'confidence' => 0.75,
                'suggested_intervention' => 'INT_3_1' // 일상 비유
            ],
            'PATTERN_MULTIPLE_EQUALS' => [
                'error_type' => '등식 변형 과정 혼란',
                'confidence' => 0.7,
                'suggested_intervention' => 'INT_2_4' // 역순 재구성
            ]
        ];
        
        return $errorMapping[$patternId] ?? null;
    }
    
    /**
     * 진행 상태 유추
     */
    public function inferProgressState($pattern) {
        $patternId = $pattern['pattern_id'] ?? '';
        
        $progressMapping = [
            'PATTERN_FAST_PROGRESS' => [
                'state' => '정상 진행',
                'confidence' => 0.9,
                'intervention_needed' => false
            ],
            'PATTERN_PAUSE_10S' => [
                'state' => '막힘',
                'confidence' => 0.9,
                'intervention_needed' => true
            ]
        ];
        
        return $progressMapping[$patternId] ?? null;
    }
    
    /**
     * 확신도 기반 개입 결정
     */
    public function decideIntervention($inference, $confidence) {
        $patternId = $inference['pattern_id'] ?? '';
        
        // 확신도가 높으면 즉시 개입, 낮으면 질문
        if ($confidence >= 0.8) {
            return $this->getHighConfidenceIntervention($patternId);
        } elseif ($confidence >= 0.6) {
            return $this->getMediumConfidenceIntervention($patternId);
        } else {
            return $this->getLowConfidenceIntervention($patternId);
        }
    }
    
    /**
     * 높은 확신도 개입
     */
    private function getHighConfidenceIntervention($patternId) {
        $mapping = [
            'PATTERN_PAUSE_10S' => [
                'intervention_id' => 'INT_5_5', // 힌트 질문
                'method' => 'breathing_bar',
                'message' => '막혔으면 ? 그려줘'
            ],
            'PATTERN_ERASE_REPEAT' => [
                'intervention_id' => 'INT_2_3', // 단계 분해
                'method' => 'inline_question',
                'message' => '어디가 헷갈려?'
            ],
            'PATTERN_SIGN_CORRECTION' => [
                'intervention_id' => 'INT_6_1', // 즉시 교정
                'method' => 'inline_question',
                'message' => '부호 확인해볼까?'
            ]
        ];
        
        return $mapping[$patternId] ?? null;
    }
    
    /**
     * 중간 확신도 개입
     */
    private function getMediumConfidenceIntervention($patternId) {
        $mapping = [
            'PATTERN_PAUSE_3S' => [
                'intervention_id' => 'INT_1_3', // 사고 여백 제공
                'method' => 'margin_whisper',
                'message' => '생각 중이야?'
            ],
            'PATTERN_PAUSE_5S' => [
                'intervention_id' => 'INT_1_3', // 사고 여백 제공
                'method' => 'margin_whisper',
                'message' => '생각 중이야?'
            ]
        ];
        
        return $mapping[$patternId] ?? null;
    }
    
    /**
     * 낮은 확신도 개입
     */
    private function getLowConfidenceIntervention($patternId) {
        return [
            'intervention_id' => 'INT_5_1', // 확인 질문
            'method' => 'corner_emoji',
            'message' => '괜찮아?'
        ];
    }
    
    /**
     * 패턴 → 개입 활동 매핑
     */
    public function mapPatternToIntervention($pattern) {
        $patternId = $pattern['pattern_id'] ?? '';
        $confidence = $pattern['confidence'] ?? 0.5;
        
        $mapping = [
            'PATTERN_PAUSE_3S' => ['INT_1_3', 'INT_5_1'],
            'PATTERN_PAUSE_5S' => ['INT_5_5', 'INT_1_3'],
            'PATTERN_PAUSE_10S' => ['INT_5_5', 'INT_6_3'],
            'PATTERN_ERASE_REPEAT' => ['INT_2_3', 'INT_5_7'],
            'PATTERN_OVERWRITE' => ['INT_1_5', 'INT_6_2'],
            'PATTERN_SIGN_CORRECTION' => ['INT_6_1', 'INT_6_2'],
            'PATTERN_FAST_PROGRESS' => [] // 개입 없음
        ];
        
        $interventionIds = $mapping[$patternId] ?? [];
        
        // 확신도에 따라 선택
        if ($confidence >= 0.8 && !empty($interventionIds)) {
            return $interventionIds[0];
        } elseif (!empty($interventionIds)) {
            return $interventionIds[count($interventionIds) - 1];
        }
        
        return null;
    }
}


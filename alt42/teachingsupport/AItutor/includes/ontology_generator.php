<?php
/**
 * 온톨로지 생성기
 * 실제 문맥을 담을 수 있는 온톨로지 생성
 * Agent01의 OIW Model 적용
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class OntologyGenerator {
    
    /**
     * 온톨로지 생성
     * 
     * @param array $analysis 대화 분석 결과
     * @param array $rules 생성된 룰 목록
     * @return array 생성된 온톨로지
     */
    public function generateOntology($analysis, $rules) {
        $ontology = [
            'will' => $this->generateWillLayer($analysis),
            'intent' => $this->generateIntentLayer($analysis),
            'reasoning' => $this->generateReasoningLayer($analysis),
            'ontology' => $this->generateOntologyNodes($analysis, $rules)
        ];

        return $ontology;
    }

    /**
     * Will Layer 생성
     */
    private function generateWillLayer($analysis) {
        return [
            'core' => [
                [
                    'value' => '학생이 단원 학습에서 좌절하지 않도록 한다',
                    'priority' => 10,
                    'constraints' => [
                        'difficulty_progression' => ['allowed' => ['점진적 상승'], 'forbidden' => ['급격한 상승']],
                        'concept_sequence' => ['required' => ['선행 개념 확인', '단계별 이해']]
                    ]
                ],
                [
                    'value' => '단원의 핵심 개념을 확실히 이해하도록 한다',
                    'priority' => 9,
                    'constraints' => [
                        'concept_mastery' => ['threshold' => 0.8, 'measurement' => '핵심 개념 이해도'],
                        'prerequisite_check' => ['required' => true]
                    ]
                ],
                [
                    'value' => '단원 간 연결성을 이해하도록 한다',
                    'priority' => 8,
                    'constraints' => [
                        'unit_relations' => ['required' => true],
                        'prerequisite_units' => ['check' => true]
                    ]
                ]
            ],
            'constraints' => [
                '학부모 불신을 유발하지 않는다',
                '학원 진도와 완전히 어긋나지 않는다',
                '시험 대비를 완전히 무시하지 않는다'
            ]
        ];
    }

    /**
     * Intent Layer 생성
     */
    private function generateIntentLayer($analysis) {
        $intent = [
            'session_goal' => '',
            'short_term' => '',
            'long_term' => '',
            'priority' => []
        ];

        if ($analysis['unit']) {
            $intent['session_goal'] = "{$analysis['unit']['korean']} 단원의 핵심 개념 이해";
            $intent['short_term'] = "{$analysis['unit']['korean']} 단원 학습 완료";
            $intent['long_term'] = "수학 단원 간 연결성 이해 및 종합적 실력 향상";
        }

        if (!empty($analysis['concepts'])) {
            $intent['priority'][] = '핵심 개념 이해 (최우선)';
        }
        if (!empty($analysis['problems'])) {
            $intent['priority'][] = '문제 풀이 능력 향상';
        }
        if (!empty($analysis['prerequisites'])) {
            $intent['priority'][] = '선행 개념 확인 및 보완';
        }

        return $intent;
    }

    /**
     * Reasoning Layer 생성
     */
    private function generateReasoningLayer($analysis) {
        return [
            'cosmology' => [
                'possibility' => $this->generatePossibility($analysis),
                'duality' => $this->generateDuality($analysis),
                'tension' => $this->generateTension($analysis),
                'impulse' => $this->generateImpulse($analysis),
                'awareness' => $this->generateAwareness($analysis),
                'meaning' => $this->generateMeaning($analysis),
                'origin_rule' => 'Will과 Intent를 모든 전략의 출발점으로 사용'
            ]
        ];
    }

    /**
     * Ontology Nodes 생성
     */
    private function generateOntologyNodes($analysis, $rules) {
        $nodes = [];

        // Context Layer: UnitLearningContext
        if ($analysis['unit']) {
            $nodes[] = [
                'id' => 'AIT_UnitLearningContext',
                'class' => 'mk:UnitLearningContext',
                'stage' => 'Context',
                'parent' => 'root',
                'metadata' => [
                    'intent' => '단원 학습 맥락을 구조화',
                    'identity' => "{$analysis['unit']['korean']} 단원 학습 컨텍스트",
                    'purpose' => '단원 학습 전략 수립을 위한 기반 데이터 제공',
                    'context' => "단원: {$analysis['unit']['korean']}, 난이도: {$analysis['difficulty_level']}"
                ],
                'properties' => [
                    'hasCurrentUnit' => $analysis['unit']['code'],
                    'hasUnitDifficulty' => $analysis['difficulty_level'],
                    'hasPrerequisites' => $analysis['prerequisites'],
                    'hasConcepts' => array_column($analysis['concepts'], 'name')
                ]
            ];
        }

        // Context Layer: ConceptLearningContext
        foreach ($analysis['concepts'] as $concept) {
            $nodes[] = [
                'id' => 'AIT_Concept_' . str_replace(' ', '_', $concept['name']),
                'class' => 'mk:ConceptLearningContext',
                'stage' => 'Context',
                'parent' => 'AIT_UnitLearningContext',
                'metadata' => [
                    'intent' => "{$concept['name']} 개념 학습 맥락",
                    'identity' => "{$concept['name']} 개념",
                    'purpose' => '개념 학습 전략 수립',
                    'context' => "개념 유형: {$concept['type']}"
                ],
                'properties' => [
                    'hasConceptName' => $concept['name'],
                    'hasConceptType' => $concept['type'],
                    'hasDescription' => $concept['description']
                ]
            ];
        }

        // Decision Layer: UnitLearningStrategy
        if ($analysis['unit']) {
            $nodes[] = [
                'id' => 'AIT_UnitLearningStrategy',
                'class' => 'mk:UnitLearningStrategy',
                'stage' => 'Decision',
                'parent' => 'AIT_UnitLearningContext',
                'usesContext' => ['AIT_UnitLearningContext'],
                'metadata' => [
                    'intent' => '단원 학습 전략 결정',
                    'identity' => "{$analysis['unit']['korean']} 단원 학습 전략",
                    'purpose' => '학습 순서, 방법, 자료 선택',
                    'context' => '분석 결과 기반 전략 수립'
                ],
                'properties' => [
                    'recommendsLearningSequence' => $analysis['learning_sequence'],
                    'recommendsTeachingMethods' => array_column($analysis['teaching_methods'], 'method'),
                    'recommendsDifficulty' => $analysis['difficulty_level']
                ]
            ];
        }

        // Execution Layer: UnitLearningExecutionPlan
        if ($analysis['unit']) {
            $nodes[] = [
                'id' => 'AIT_UnitLearningExecutionPlan',
                'class' => 'mk:UnitLearningExecutionPlan',
                'stage' => 'Execution',
                'parent' => 'AIT_UnitLearningStrategy',
                'metadata' => [
                    'intent' => '단원 학습 실행 계획',
                    'identity' => "{$analysis['unit']['korean']} 단원 실행 계획",
                    'purpose' => '구체적 학습 활동 및 측정',
                    'context' => '전략을 실행 가능한 단계로 변환'
                ],
                'properties' => [
                    'hasActionSteps' => $this->generateActionSteps($analysis),
                    'hasMeasurementCriteria' => $this->generateMeasurementCriteria($analysis),
                    'hasFeedbackPoints' => $this->generateFeedbackPoints($analysis),
                    'hasAdjustmentRules' => $this->generateAdjustmentRules($analysis)
                ]
            ];
        }

        return $nodes;
    }

    /**
     * Reasoning: Possibility 생성
     */
    private function generatePossibility($analysis) {
        $possibility = [];
        
        if ($analysis['unit']) {
            $possibility[] = "{$analysis['unit']['korean']} 단원 학습";
        }
        if (!empty($analysis['concepts'])) {
            $possibility[] = count($analysis['concepts']) . "개 개념 학습";
        }
        if (!empty($analysis['problems'])) {
            $possibility[] = count($analysis['problems']) . "개 문제 풀이";
        }

        return implode(', ', $possibility);
    }

    /**
     * Reasoning: Duality 생성
     */
    private function generateDuality($analysis) {
        $dualities = [];
        
        if (!empty($analysis['concepts']) && !empty($analysis['problems'])) {
            $dualities[] = '개념 이해 vs 문제 풀이';
        }
        if ($analysis['difficulty_level'] >= 4) {
            $dualities[] = '기초 보완 vs 진도 유지';
        }

        return implode(', ', $dualities);
    }

    /**
     * Reasoning: Tension 생성
     */
    private function generateTension($analysis) {
        $tensions = [];
        
        if ($analysis['difficulty_level'] >= 4) {
            $tensions[] = '높은 난이도';
        }
        if (!empty($analysis['prerequisites'])) {
            $tensions[] = '선행 개념 필요';
        }
        if (!empty($analysis['student_responses'])) {
            $lowUnderstanding = array_filter($analysis['student_responses'], function($r) {
                return $r['understanding_level'] === 'low';
            });
            if (count($lowUnderstanding) > 0) {
                $tensions[] = '이해도 낮음';
            }
        }

        return implode(' + ', $tensions);
    }

    /**
     * Reasoning: Impulse 생성
     */
    private function generateImpulse($analysis) {
        if ($analysis['unit']) {
            return "{$analysis['unit']['korean']} 단원 마스터리";
        }
        return '수학 실력 향상';
    }

    /**
     * Reasoning: Awareness 생성
     */
    private function generateAwareness($analysis) {
        $awareness = [];
        
        if ($analysis['unit']) {
            $awareness[] = "현재 단원: {$analysis['unit']['korean']}";
        }
        if (!empty($analysis['concepts'])) {
            $awareness[] = "학습 개념: " . implode(', ', array_column($analysis['concepts'], 'name'));
        }
        if ($analysis['difficulty_level']) {
            $awareness[] = "난이도: {$analysis['difficulty_level']}";
        }

        return implode(', ', $awareness);
    }

    /**
     * Reasoning: Meaning 생성
     */
    private function generateMeaning($analysis) {
        $meanings = [];
        
        if (!empty($analysis['concepts'])) {
            $meanings[] = '핵심 개념 이해 최우선';
        }
        if (!empty($analysis['prerequisites'])) {
            $meanings[] = '선행 개념 확인 필수';
        }
        if ($analysis['difficulty_level'] >= 4) {
            $meanings[] = '단계별 접근 필요';
        }

        return implode(', ', $meanings);
    }

    /**
     * Action Steps 생성
     */
    private function generateActionSteps($analysis) {
        $steps = [];
        
        if (!empty($analysis['prerequisites'])) {
            $steps[] = '선행 단원 확인';
        }
        if (!empty($analysis['concepts'])) {
            $steps[] = '개념 단계별 설명';
        }
        if (!empty($analysis['problems'])) {
            $steps[] = '문제 풀이 연습';
        }
        if (!empty($analysis['teaching_methods'])) {
            foreach ($analysis['teaching_methods'] as $method) {
                $steps[] = "{$method['method']} 방법 적용";
            }
        }

        return $steps;
    }

    /**
     * Measurement Criteria 생성
     */
    private function generateMeasurementCriteria($analysis) {
        $criteria = [];
        
        $criteria[] = '개념 이해도 (목표: 80% 이상)';
        if (!empty($analysis['problems'])) {
            $criteria[] = '문제 풀이 정확도';
        }
        if (!empty($analysis['student_responses'])) {
            $criteria[] = '학생 응답 품질';
        }

        return $criteria;
    }

    /**
     * Feedback Points 생성
     */
    private function generateFeedbackPoints($analysis) {
        $points = [];
        
        foreach ($analysis['concepts'] as $concept) {
            $points[] = "{$concept['name']} 개념 이해도 확인";
        }
        if (!empty($analysis['problems'])) {
            $points[] = '문제 풀이 과정 피드백';
        }

        return $points;
    }

    /**
     * Adjustment Rules 생성
     */
    private function generateAdjustmentRules($analysis) {
        $rules = [];
        
        if ($analysis['difficulty_level'] >= 4) {
            $rules[] = '이해도 낮으면 난이도 하향 조정';
        }
        if (!empty($analysis['student_responses'])) {
            $lowUnderstanding = array_filter($analysis['student_responses'], function($r) {
                return $r['understanding_level'] === 'low';
            });
            if (count($lowUnderstanding) > 0) {
                $rules[] = '기본 개념부터 다시 설명';
            }
        }

        return $rules;
    }
}


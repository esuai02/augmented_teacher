<?php
/**
 * 룰 생성기
 * 교수법 레벨의 의사결정을 위한 룰 생성
 * Agent01의 rules.yaml 구조 기반
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class RuleGenerator {
    
    /**
     * 교수법 룰 생성
     * 
     * @param array $analysis 대화 분석 결과
     * @param array $comprehensiveQuestions 포괄적 질문 목록
     * @return array 생성된 룰 목록
     */
    public function generateTeachingRules($analysis, $comprehensiveQuestions) {
        $rules = [];

        // U0: 단원 학습 시작 전 정보 수집 룰
        $rules = array_merge($rules, $this->generateU0Rules($analysis));

        // U1: 단원 학습 시작 전략 룰
        $rules = array_merge($rules, $this->generateU1Rules($analysis, $comprehensiveQuestions));

        // U2: 개념 학습 중 전략 룰
        $rules = array_merge($rules, $this->generateU2Rules($analysis));

        // U3: 문제 풀이 전략 룰
        $rules = array_merge($rules, $this->generateU3Rules($analysis));

        return $rules;
    }

    /**
     * U0 룰 생성: 단원 학습 시작 전 정보 수집
     */
    private function generateU0Rules($analysis) {
        $rules = [];

        // 선행 단원 확인 룰
        if (!empty($analysis['prerequisites'])) {
            $rules[] = [
                'rule_id' => 'U0_R1_prerequisite_check',
                'priority' => 99,
                'description' => '선행 단원 완료 여부 확인',
                'conditions' => [
                    [
                        'field' => 'current_unit',
                        'operator' => '==',
                        'value' => $analysis['unit']['code']
                    ],
                    [
                        'field' => 'prerequisite_units_completed',
                        'operator' => '==',
                        'value' => null
                    ]
                ],
                'action' => [
                    "load_db: 'math_unit_relations.yaml'",
                    "check_prerequisites: '{$analysis['unit']['code']}'",
                    "analyze: 'prerequisite_mastery_status'",
                    "display_message: '선행 단원(" . implode(', ', $analysis['prerequisites']) . ") 완료 여부를 확인하여 학습 준비 상태를 평가합니다.'"
                ],
                'confidence' => 0.95,
                'rationale' => '선행 단원 미완료 시 학습 어려움 예방'
            ];
        }

        // 단원 난이도 평가 룰
        if ($analysis['unit']) {
            $rules[] = [
                'rule_id' => 'U0_R2_unit_difficulty_assessment',
                'priority' => 98,
                'description' => '단원 난이도와 학생 수준 매칭',
                'conditions' => [
                    [
                        'field' => 'current_unit',
                        'operator' => '==',
                        'value' => $analysis['unit']['code']
                    ],
                    [
                        'field' => 'unit_difficulty',
                        'operator' => '==',
                        'value' => $analysis['difficulty_level']
                    ]
                ],
                'action' => [
                    "load_db: 'math_unit_relations.yaml'",
                    "get_unit_difficulty: '{$analysis['unit']['code']}'",
                    "match_difficulty_level: '{$analysis['difficulty_level']}'",
                    "recommend: 'adjusted_learning_approach'",
                    "display_message: '단원 난이도({$analysis['difficulty_level']})에 맞춘 학습 접근을 제안합니다.'"
                ],
                'confidence' => 0.93,
                'rationale' => '적절한 난이도 매칭이 학습 효과 향상'
            ];
        }

        return $rules;
    }

    /**
     * U1 룰 생성: 단원 학습 시작 전략
     */
    private function generateU1Rules($analysis, $comprehensiveQuestions) {
        $rules = [];

        // 단원 학습 시작 종합 전략 룰
        if ($analysis['unit']) {
            $rules[] = [
                'rule_id' => 'U1_R1_unit_start_strategy',
                'priority' => 100,
                'description' => '단원 학습 시작 종합 전략',
                'conditions' => [
                    [
                        'field' => 'user_message',
                        'operator' => 'contains',
                        'value' => '단원 시작'
                    ],
                    [
                        'field' => 'current_unit',
                        'operator' => '==',
                        'value' => $analysis['unit']['code']
                    ]
                ],
                'action' => [
                    "create_instance: 'mk:UnitLearningContext'",
                    "set_property: ('mk:hasCurrentUnit', '{$analysis['unit']['code']}')",
                    "set_property: ('mk:hasPrerequisiteStatus', 'checked')",
                    "set_property: ('mk:hasUnitDifficulty', '{$analysis['difficulty_level']}')",
                    "generate_strategy: 'mk:UnitStartStrategy'",
                    "recommend_path: '단원 학습 시작 전략: 선행 확인 + 난이도 매칭 + 학습 계획'",
                    "display_message: '{$analysis['unit']['korean']} 단원 학습을 시작하기 위해 종합 전략을 수립합니다.'"
                ],
                'confidence' => 0.96,
                'rationale' => '단원 학습 시작 시 종합적 전략 필요'
            ];
        }

        return $rules;
    }

    /**
     * U2 룰 생성: 개념 학습 중 전략
     */
    private function generateU2Rules($analysis) {
        $rules = [];

        // 개념별 학습 전략 룰
        foreach ($analysis['concepts'] as $index => $concept) {
            $rules[] = [
                'rule_id' => 'U2_R' . ($index + 1) . '_concept_' . str_replace(' ', '_', $concept['name']),
                'priority' => 95 - $index,
                'description' => "{$concept['name']} 개념 학습 전략",
                'conditions' => [
                    [
                        'field' => 'current_concept',
                        'operator' => '==',
                        'value' => $concept['name']
                    ],
                    [
                        'field' => 'concept_understanding_score',
                        'operator' => '<',
                        'value' => 0.8
                    ]
                ],
                'action' => [
                    "analyze: 'concept_confusion_points'",
                    "identify: 'prerequisite_concept_gaps'",
                    "recommend: 'concept_clarification_strategy'",
                    "display_message: '{$concept['name']} 개념을 단계별로 설명하고 이해도를 확인합니다.'"
                ],
                'confidence' => 0.92,
                'rationale' => "{$concept['name']} 개념의 체계적 학습 필요"
            ];
        }

        // 교수법 적용 룰
        foreach ($analysis['teaching_methods'] as $index => $method) {
            $rules[] = [
                'rule_id' => 'U2_R_METHOD_' . ($index + 1) . '_' . str_replace(' ', '_', $method['method']),
                'priority' => 90 - $index,
                'description' => "{$method['method']} 교수법 적용",
                'conditions' => [
                    [
                        'field' => 'teaching_method',
                        'operator' => '==',
                        'value' => $method['method']
                    ],
                    [
                        'field' => 'student_understanding',
                        'operator' => 'in',
                        'value' => ['medium', 'low']
                    ]
                ],
                'action' => [
                    "apply_method: '{$method['method']}'",
                    "generate_explanation: 'method_based_explanation'",
                    "display_message: '{$method['description']} 방법을 활용하여 설명합니다.'"
                ],
                'confidence' => 0.88,
                'rationale' => "{$method['method']} 방법이 학습 효과 향상에 도움"
            ];
        }

        return $rules;
    }

    /**
     * U3 룰 생성: 문제 풀이 전략
     */
    private function generateU3Rules($analysis) {
        $rules = [];

        // 문제별 풀이 전략 룰
        foreach ($analysis['problems'] as $index => $problem) {
            $rules[] = [
                'rule_id' => 'U3_R' . ($index + 1) . '_problem_' . $problem['type'],
                'priority' => 94 - $index,
                'description' => "{$problem['type']} 유형 문제 풀이 전략",
                'conditions' => [
                    [
                        'field' => 'problem_type',
                        'operator' => '==',
                        'value' => $problem['type']
                    ],
                    [
                        'field' => 'problem_difficulty',
                        'operator' => '==',
                        'value' => $problem['difficulty']
                    ]
                ],
                'action' => [
                    "analyze: 'problem_structure'",
                    "recommend: 'step_by_step_solution'",
                    "provide_hints: 'progressive_hints'",
                    "display_message: '{$problem['type']} 유형 문제를 단계별로 풀이합니다.'"
                ],
                'confidence' => 0.91,
                'rationale' => "{$problem['type']} 유형의 체계적 풀이 필요"
            ];
        }

        // 학생 응답 기반 보완 룰
        foreach ($analysis['student_responses'] as $index => $response) {
            if ($response['understanding_level'] === 'low') {
                $rules[] = [
                    'rule_id' => 'U3_R_REMEDIATION_' . ($index + 1),
                    'priority' => 93,
                    'description' => '학생 이해도 낮음 시 보완 전략',
                    'conditions' => [
                        [
                            'field' => 'student_understanding_level',
                            'operator' => '==',
                            'value' => 'low'
                        ],
                        [
                            'field' => 'student_confidence',
                            'operator' => '==',
                            'value' => $response['confidence']
                        ]
                    ],
                    'action' => [
                        "remediate: 'basic_concept_review'",
                        "provide_easier_examples: true",
                        "step_by_step_guidance: true",
                        "display_message: '기본 개념부터 다시 설명하고 쉬운 예시를 제공합니다.'"
                    ],
                    'confidence' => 0.90,
                    'rationale' => '이해도가 낮을 때는 기초부터 다시 설명 필요'
                ];
            }
        }

        return $rules;
    }
}


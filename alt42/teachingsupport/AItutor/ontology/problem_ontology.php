<?php
/**
 * 문항별 온톨로지 (Problem Ontology)
 * 
 * Phase 2: 문항별 온톨로지 구축
 * - 각 문항의 요구 개념 온톨로지
 * - 선행-후행 관계 그래프
 * - 흔한 오류 패턴 매핑
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

/**
 * 수학 개념 온톨로지 - 유리수의 나눗셈 예시
 */
return [
    // ========================================
    // 메타 정보
    // ========================================
    '@context' => [
        '@vocab' => 'https://mathking.kr/ontology/',
        'mk' => 'https://mathking.kr/ontology/',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl' => 'http://www.w3.org/2002/07/owl#'
    ],
    
    // ========================================
    // 단원 온톨로지 (Unit Ontology)
    // ========================================
    'units' => [
        'rational_number_division' => [
            '@id' => 'mk:Unit_RationalNumberDivision',
            '@type' => 'owl:Class',
            'rdfs:label' => '유리수의 나눗셈',
            'rdfs:label_en' => 'Division of Rational Numbers',
            'unit_code' => 'M1-2-3',
            'grade' => '중1',
            'difficulty' => 3,
            'prerequisites' => [
                'mk:Unit_IntegerDivision',
                'mk:Unit_FractionBasics',
                'mk:Unit_SignRules'
            ],
            'concepts' => [
                'mk:Concept_SignRuleForDivision',
                'mk:Concept_Reciprocal',
                'mk:Concept_FractionDivision',
                'mk:Concept_ContinuousDivision'
            ],
            'learning_objectives' => [
                '유리수의 나눗셈에서 부호 규칙을 이해한다',
                '역수의 개념을 이해하고 활용한다',
                '분수의 나눗셈을 역수 곱셈으로 변환한다',
                '연속 나눗셈을 순서대로 계산한다'
            ]
        ]
    ],
    
    // ========================================
    // 개념 온톨로지 (Concept Ontology)
    // ========================================
    'concepts' => [
        // 부호 규칙
        'sign_rule' => [
            '@id' => 'mk:Concept_SignRuleForDivision',
            '@type' => 'owl:Class',
            'rdfs:label' => '나눗셈의 부호 규칙',
            'rdfs:label_en' => 'Sign Rule for Division',
            'difficulty' => 2,
            'prerequisites' => ['mk:Concept_SignRuleForMultiplication'],
            'key_points' => [
                '(+) ÷ (+) = (+)',
                '(+) ÷ (-) = (-)',
                '(-) ÷ (+) = (-)',
                '(-) ÷ (-) = (+)'
            ],
            'common_mistakes' => [
                [
                    'type' => 'sign_error',
                    'description' => '부호 규칙 혼동',
                    'example' => '(-6) ÷ (-2) = -3 (잘못)',
                    'correct' => '(-6) ÷ (-2) = +3',
                    'persona_prone' => ['P004', 'P005']
                ]
            ],
            'teaching_methods' => [
                '같은 부호면 양수, 다른 부호면 음수',
                '곱셈 부호 규칙과 동일함을 강조'
            ]
        ],
        
        // 역수
        'reciprocal' => [
            '@id' => 'mk:Concept_Reciprocal',
            '@type' => 'owl:Class',
            'rdfs:label' => '역수',
            'rdfs:label_en' => 'Reciprocal',
            'difficulty' => 2,
            'prerequisites' => ['mk:Concept_FractionBasics'],
            'key_points' => [
                'a의 역수는 1/a',
                'a/b의 역수는 b/a',
                '두 수의 곱이 1이면 서로 역수'
            ],
            'common_mistakes' => [
                [
                    'type' => 'reciprocal_forget',
                    'description' => '역수로 바꾸지 않고 그냥 곱함',
                    'example' => '3 ÷ 2/5 = 3 × 2/5 (잘못)',
                    'correct' => '3 ÷ 2/5 = 3 × 5/2',
                    'persona_prone' => ['P004', 'P005', 'P009']
                ],
                [
                    'type' => 'reciprocal_partial',
                    'description' => '분자만 바꾸거나 분모만 바꿈',
                    'example' => '8/3의 역수 = 8/3 또는 3/3 (잘못)',
                    'correct' => '8/3의 역수 = 3/8',
                    'persona_prone' => ['P005', 'P009']
                ]
            ],
            'teaching_methods' => [
                '분자와 분모를 뒤집기',
                '곱해서 1이 되는 수 찾기'
            ]
        ],
        
        // 분수의 나눗셈
        'fraction_division' => [
            '@id' => 'mk:Concept_FractionDivision',
            '@type' => 'owl:Class',
            'rdfs:label' => '분수의 나눗셈',
            'rdfs:label_en' => 'Fraction Division',
            'difficulty' => 3,
            'prerequisites' => [
                'mk:Concept_Reciprocal',
                'mk:Concept_FractionMultiplication'
            ],
            'key_points' => [
                'a/b ÷ c/d = a/b × d/c',
                '나누는 수의 역수를 곱한다',
                '분수 ÷ 분수 = 분수 × 역수'
            ],
            'common_mistakes' => [
                [
                    'type' => 'order_error',
                    'description' => '나누어지는 수를 역수로 바꿈',
                    'example' => '2/3 ÷ 4/5 = 3/2 × 4/5 (잘못)',
                    'correct' => '2/3 ÷ 4/5 = 2/3 × 5/4',
                    'persona_prone' => ['P005', 'P008']
                ]
            ],
            'teaching_methods' => [
                '뒤에 오는 수만 뒤집기',
                '÷를 ×로 바꾸고 뒤집기'
            ]
        ],
        
        // 연속 나눗셈
        'continuous_division' => [
            '@id' => 'mk:Concept_ContinuousDivision',
            '@type' => 'owl:Class',
            'rdfs:label' => '연속 나눗셈',
            'rdfs:label_en' => 'Continuous Division',
            'difficulty' => 4,
            'prerequisites' => [
                'mk:Concept_FractionDivision',
                'mk:Concept_SignRuleForDivision'
            ],
            'key_points' => [
                '왼쪽에서 오른쪽으로 순서대로',
                '각 나눗셈마다 역수로 변환',
                '부호는 개수로 판단 (음수 짝수개 = 양수)'
            ],
            'common_mistakes' => [
                [
                    'type' => 'order_error',
                    'description' => '계산 순서 무시하고 임의로 계산',
                    'example' => '12 ÷ 2 ÷ 3에서 2 ÷ 3 먼저 (잘못)',
                    'correct' => '(12 ÷ 2) ÷ 3 = 6 ÷ 3 = 2',
                    'persona_prone' => ['P004', 'P005']
                ],
                [
                    'type' => 'sign_accumulation_error',
                    'description' => '여러 부호 누적 실수',
                    'example' => '(-12) ÷ (+8/3) ÷ (+9/4) 부호 실수',
                    'persona_prone' => ['P004', 'P008']
                ]
            ],
            'teaching_methods' => [
                '단계별로 하나씩 계산',
                '중간 결과 확인하며 진행'
            ]
        ]
    ],
    
    // ========================================
    // 문항별 온톨로지 (Problem Item Ontology)
    // ========================================
    'problem_items' => [
        // 문항 1
        'item_1' => [
            '@id' => 'mk:ProblemItem_1',
            '@type' => 'mk:ProblemItem',
            'rdfs:label' => '(+21) ÷ (-3)',
            'item_number' => 1,
            'difficulty' => 'easy',
            'difficulty_score' => 1,
            'topic' => '유리수의 나눗셈',
            'expression' => '(+21) ÷ (-3)',
            'answer' => -7,
            'requires_concepts' => [
                'mk:Concept_SignRuleForDivision'
            ],
            'prerequisite_concepts' => [
                'mk:Concept_IntegerDivision'
            ],
            'solving_steps' => [
                ['step' => 1, 'action' => '부호 결정', 'detail' => '(+) ÷ (-) = (-)', 'concept' => 'sign_rule'],
                ['step' => 2, 'action' => '절댓값 나눗셈', 'detail' => '21 ÷ 3 = 7', 'concept' => 'integer_division'],
                ['step' => 3, 'action' => '결과 조합', 'detail' => '-7', 'concept' => 'result']
            ],
            'common_mistakes' => [
                ['type' => 'sign_error', 'wrong_answer' => 7, 'explanation' => '부호 규칙 착오']
            ],
            'recommended_personas' => ['P004', 'P005'],
            'estimated_time_seconds' => 30
        ],
        
        // 문항 2
        'item_2' => [
            '@id' => 'mk:ProblemItem_2',
            '@type' => 'mk:ProblemItem',
            'rdfs:label' => '(-6/15) ÷ (+4/5)',
            'item_number' => 2,
            'difficulty' => 'medium',
            'difficulty_score' => 3,
            'topic' => '유리수의 나눗셈',
            'expression' => '(-6/15) ÷ (+4/5)',
            'answer' => '-1/2',
            'requires_concepts' => [
                'mk:Concept_SignRuleForDivision',
                'mk:Concept_Reciprocal',
                'mk:Concept_FractionDivision'
            ],
            'prerequisite_concepts' => [
                'mk:Concept_FractionBasics',
                'mk:Concept_FractionSimplification'
            ],
            'solving_steps' => [
                ['step' => 1, 'action' => '부호 결정', 'detail' => '(-) ÷ (+) = (-)', 'concept' => 'sign_rule'],
                ['step' => 2, 'action' => '역수 변환', 'detail' => '÷ 4/5 → × 5/4', 'concept' => 'reciprocal'],
                ['step' => 3, 'action' => '분수 곱셈', 'detail' => '6/15 × 5/4', 'concept' => 'fraction_multiplication'],
                ['step' => 4, 'action' => '약분', 'detail' => '= 30/60 = 1/2', 'concept' => 'simplification'],
                ['step' => 5, 'action' => '부호 적용', 'detail' => '-1/2', 'concept' => 'result']
            ],
            'common_mistakes' => [
                ['type' => 'reciprocal_forget', 'explanation' => '역수로 바꾸지 않음'],
                ['type' => 'simplification_error', 'explanation' => '약분 실수']
            ],
            'recommended_personas' => ['P005', 'P008', 'P009'],
            'estimated_time_seconds' => 90
        ],
        
        // 문항 3
        'item_3' => [
            '@id' => 'mk:ProblemItem_3',
            '@type' => 'mk:ProblemItem',
            'rdfs:label' => '(+16) ÷ (-2) ÷ (-4)',
            'item_number' => 3,
            'difficulty' => 'medium',
            'difficulty_score' => 3,
            'topic' => '유리수의 나눗셈',
            'expression' => '(+16) ÷ (-2) ÷ (-4)',
            'answer' => 2,
            'requires_concepts' => [
                'mk:Concept_SignRuleForDivision',
                'mk:Concept_ContinuousDivision'
            ],
            'prerequisite_concepts' => [
                'mk:Concept_IntegerDivision'
            ],
            'solving_steps' => [
                ['step' => 1, 'action' => '첫 번째 나눗셈', 'detail' => '16 ÷ (-2) = -8', 'concept' => 'sign_rule'],
                ['step' => 2, 'action' => '두 번째 나눗셈', 'detail' => '(-8) ÷ (-4) = +2', 'concept' => 'sign_rule'],
                ['step' => 3, 'action' => '결과 확인', 'detail' => '2', 'concept' => 'result']
            ],
            'common_mistakes' => [
                ['type' => 'order_error', 'explanation' => '계산 순서 잘못'],
                ['type' => 'sign_accumulation_error', 'explanation' => '부호 누적 실수']
            ],
            'recommended_personas' => ['P004', 'P005', 'P008'],
            'estimated_time_seconds' => 60
        ],
        
        // 문항 4
        'item_4' => [
            '@id' => 'mk:ProblemItem_4',
            '@type' => 'mk:ProblemItem',
            'rdfs:label' => '(-12) ÷ (+8/3) ÷ (+9/4)',
            'item_number' => 4,
            'difficulty' => 'hard',
            'difficulty_score' => 5,
            'topic' => '유리수의 나눗셈',
            'expression' => '(-12) ÷ (+8/3) ÷ (+9/4)',
            'answer' => -2,
            'requires_concepts' => [
                'mk:Concept_SignRuleForDivision',
                'mk:Concept_Reciprocal',
                'mk:Concept_FractionDivision',
                'mk:Concept_ContinuousDivision'
            ],
            'prerequisite_concepts' => [
                'mk:Concept_IntegerDivision',
                'mk:Concept_FractionBasics',
                'mk:Concept_FractionMultiplication'
            ],
            'solving_steps' => [
                ['step' => 1, 'action' => '부호 결정', 'detail' => '(-) ÷ (+) ÷ (+) = (-)', 'concept' => 'sign_rule'],
                ['step' => 2, 'action' => '첫 번째 역수 변환', 'detail' => '(-12) × 3/8', 'concept' => 'reciprocal'],
                ['step' => 3, 'action' => '첫 번째 계산', 'detail' => '= -36/8 = -9/2', 'concept' => 'fraction_division'],
                ['step' => 4, 'action' => '두 번째 역수 변환', 'detail' => '(-9/2) × 4/9', 'concept' => 'reciprocal'],
                ['step' => 5, 'action' => '두 번째 계산', 'detail' => '= -36/18 = -2', 'concept' => 'simplification']
            ],
            'common_mistakes' => [
                ['type' => 'reciprocal_forget', 'explanation' => '역수 변환 누락'],
                ['type' => 'order_error', 'explanation' => '계산 순서 잘못'],
                ['type' => 'sign_error', 'explanation' => '부호 실수'],
                ['type' => 'simplification_error', 'explanation' => '약분 실수']
            ],
            'recommended_personas' => ['P004', 'P005', 'P008', 'P009'],
            'estimated_time_seconds' => 180,
            'hints' => [
                ['level' => 1, 'content' => '÷ 분수 = × 역수 를 기억해!'],
                ['level' => 2, 'content' => '8/3의 역수는 3/8이야'],
                ['level' => 3, 'content' => '(-12) × 3/8 = -36/8 부터 해보자']
            ]
        ]
    ],
    
    // ========================================
    // 개념 관계 그래프 (Concept Relation Graph)
    // ========================================
    'concept_relations' => [
        // is_prerequisite_of: A → B (A는 B의 선행개념)
        [
            'source' => 'mk:Concept_IntegerDivision',
            'relation' => 'is_prerequisite_of',
            'target' => 'mk:Concept_SignRuleForDivision',
            'weight' => 0.9
        ],
        [
            'source' => 'mk:Concept_FractionBasics',
            'relation' => 'is_prerequisite_of',
            'target' => 'mk:Concept_Reciprocal',
            'weight' => 0.95
        ],
        [
            'source' => 'mk:Concept_Reciprocal',
            'relation' => 'is_prerequisite_of',
            'target' => 'mk:Concept_FractionDivision',
            'weight' => 0.9
        ],
        [
            'source' => 'mk:Concept_FractionDivision',
            'relation' => 'is_prerequisite_of',
            'target' => 'mk:Concept_ContinuousDivision',
            'weight' => 0.85
        ],
        [
            'source' => 'mk:Concept_SignRuleForDivision',
            'relation' => 'is_prerequisite_of',
            'target' => 'mk:Concept_ContinuousDivision',
            'weight' => 0.8
        ],
        
        // related_to: 관련 개념
        [
            'source' => 'mk:Concept_SignRuleForMultiplication',
            'relation' => 'related_to',
            'target' => 'mk:Concept_SignRuleForDivision',
            'weight' => 0.95
        ],
        [
            'source' => 'mk:Concept_FractionMultiplication',
            'relation' => 'related_to',
            'target' => 'mk:Concept_FractionDivision',
            'weight' => 0.9
        ]
    ],
    
    // ========================================
    // 오류 패턴 온톨로지 (Error Pattern Ontology)
    // ========================================
    'error_patterns' => [
        'sign_error' => [
            '@id' => 'mk:ErrorPattern_SignError',
            '@type' => 'mk:ErrorPattern',
            'rdfs:label' => '부호 오류',
            'description' => '나눗셈 부호 규칙 적용 오류',
            'frequency' => 'high',
            'severity' => 'medium',
            'affected_concepts' => ['mk:Concept_SignRuleForDivision'],
            'detection_signals' => [
                '절댓값은 맞으나 부호가 틀림',
                '음수 개수 세기 실수'
            ],
            'intervention_recommendations' => ['INT_4_2', 'INT_6_1', 'INT_6_5'],
            'persona_prone' => ['P004', 'P005']
        ],
        
        'reciprocal_forget' => [
            '@id' => 'mk:ErrorPattern_ReciprocalForget',
            '@type' => 'mk:ErrorPattern',
            'rdfs:label' => '역수 변환 누락',
            'description' => '분수 나눗셈에서 역수로 바꾸지 않음',
            'frequency' => 'very_high',
            'severity' => 'high',
            'affected_concepts' => ['mk:Concept_Reciprocal', 'mk:Concept_FractionDivision'],
            'detection_signals' => [
                '÷를 ×로 바꾸지 않음',
                '뒤의 분수를 그대로 곱함'
            ],
            'intervention_recommendations' => ['INT_3_3', 'INT_4_1', 'INT_6_1'],
            'persona_prone' => ['P004', 'P005', 'P009']
        ],
        
        'order_error' => [
            '@id' => 'mk:ErrorPattern_OrderError',
            '@type' => 'mk:ErrorPattern',
            'rdfs:label' => '계산 순서 오류',
            'description' => '연속 나눗셈 순서 잘못',
            'frequency' => 'medium',
            'severity' => 'high',
            'affected_concepts' => ['mk:Concept_ContinuousDivision'],
            'detection_signals' => [
                '뒤에서부터 계산',
                '임의 순서로 계산'
            ],
            'intervention_recommendations' => ['INT_2_3', 'INT_4_1', 'INT_6_1'],
            'persona_prone' => ['P004', 'P005', 'P008']
        ],
        
        'reciprocal_partial' => [
            '@id' => 'mk:ErrorPattern_ReciprocalPartial',
            '@type' => 'mk:ErrorPattern',
            'rdfs:label' => '역수 부분 오류',
            'description' => '분자만 또는 분모만 바꿈',
            'frequency' => 'medium',
            'severity' => 'high',
            'affected_concepts' => ['mk:Concept_Reciprocal'],
            'detection_signals' => [
                '분자만 역수 취함',
                '분모만 역수 취함'
            ],
            'intervention_recommendations' => ['INT_3_5', 'INT_6_5'],
            'persona_prone' => ['P005', 'P009']
        ]
    ],
    
    // ========================================
    // 난이도-페르소나 매핑
    // ========================================
    'difficulty_persona_mapping' => [
        'easy' => [
            'recommended_approach' => 'fast_verification',
            'primary_personas' => ['P004'], // 빠른데허술형 조심
            'intervention_focus' => ['INT_1_5', 'INT_6_1'] // 자기 수정, 즉시 교정
        ],
        'medium' => [
            'recommended_approach' => 'step_by_step',
            'primary_personas' => ['P005', 'P008', 'P009'], // 집중튐, 불안과몰입, 추상약함
            'intervention_focus' => ['INT_2_3', 'INT_3_3', 'INT_5_4'] // 단계분해, 구체적수대입, 선택지
        ],
        'hard' => [
            'recommended_approach' => 'guided_discovery',
            'primary_personas' => ['P001', 'P003', 'P011'], // 회피, 감정출렁, 무기력
            'intervention_focus' => ['INT_5_5', 'INT_7_3', 'INT_7_4'] // 힌트, 난이도예고, 작은성공
        ]
    ]
];


<?php
/**
 * 페르소나별 룰셋 (Persona-based Rules)
 * 
 * Phase 1: 페르소나별 기본 룰셋 정의
 * 12가지 페르소나별 맞춤 개입 룰
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

/**
 * 페르소나별 룰 정의
 * 
 * 페르소나 ID:
 * - P001: 막힘-회피형 (Avoider)
 * - P002: 확인요구형 (Checker)
 * - P003: 감정출렁형 (Emotion-driven)
 * - P004: 빠른데허술형 (Speed-but-Miss)
 * - P005: 집중튐형 (Attention Hopper)
 * - P006: 패턴추론형 (Pattern Seeker)
 * - P007: 최대한쉬운길형 (Efficiency Maximizer)
 * - P008: 불안과몰입형 (Over-focusing Worrier)
 * - P009: 추상약함형 (Concrete Learner)
 * - P010: 상호작용의존형 (Interactive Dependent)
 * - P011: 무기력형 (Low Drive)
 * - P012: 메타인지고수형 (Meta-high)
 */

return [
    // ========================================
    // P001: 막힘-회피형 (Avoider)
    // ========================================
    'P001' => [
        'persona_id' => 'P001',
        'name' => '막힘-회피형',
        'positive_name' => '도전형',
        'icon' => '🚫',
        'positive_icon' => '💪',
        'primary_interventions' => ['INT_1_1', 'INT_1_3', 'INT_5_5', 'INT_6_3'],
        'avoid_interventions' => ['INT_4_5'], // 예고 신호는 부담 가중
        'rules' => [
            [
                'rule_id' => 'PERS_P001_R1_early_quit',
                'priority' => 95,
                'description' => '조기 포기 감지 시 작은 단계 제안',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P001'],
                    ['field' => 'quit_attempt', 'operator' => '==', 'value' => true],
                    ['field' => 'time_on_problem', 'operator' => '<', 'value' => 30]
                ],
                'action' => 'INT_2_3', // 단계 분해
                'confidence' => 0.88,
                'message' => '작은 단계부터 시작해보자! 한 걸음씩 가면 돼 👣'
            ],
            [
                'rule_id' => 'PERS_P001_R2_pencil_down',
                'priority' => 94,
                'description' => '펜 내려놓음 감지 시 대기 후 격려',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P001'],
                    ['field' => 'pen_down_duration', 'operator' => '>=', 'value' => 5]
                ],
                'action' => 'INT_1_3', // 사고 여백 제공
                'confidence' => 0.85,
                'message' => '한번 생각해봐. 천천히 해도 돼 💭'
            ],
            [
                'rule_id' => 'PERS_P001_R3_mini_success',
                'priority' => 93,
                'description' => '작은 진전 시 즉시 격려',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P001'],
                    ['field' => 'step_progress', 'operator' => '>', 'value' => 0]
                ],
                'action' => 'INT_7_1', // 노력 인정
                'confidence' => 0.90,
                'message' => '잘했어! 첫 발을 내딛었네 ✨'
            ]
        ]
    ],
    
    // ========================================
    // P002: 확인요구형 (Checker)
    // ========================================
    'P002' => [
        'persona_id' => 'P002',
        'name' => '확인요구형',
        'positive_name' => '자기확신형',
        'icon' => '❓',
        'positive_icon' => '✨',
        'primary_interventions' => ['INT_2_1', 'INT_5_1', 'INT_6_2', 'INT_6_4'],
        'avoid_interventions' => ['INT_1_5'], // 자기 수정 대기는 불안 가중
        'rules' => [
            [
                'rule_id' => 'PERS_P002_R1_confirm_request',
                'priority' => 92,
                'description' => '확인 요청 빈도 높을 때 자기 검증 유도',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P002'],
                    ['field' => 'confirm_request_count', 'operator' => '>=', 'value' => 3],
                    ['field' => 'time_window', 'operator' => '<=', 'value' => 60]
                ],
                'action' => 'INT_5_3', // 역질문
                'confidence' => 0.85,
                'message' => '네 판단을 믿어봐! 스스로 검증하는 힘을 키우자 🔍'
            ],
            [
                'rule_id' => 'PERS_P002_R2_correct_no_confirm',
                'priority' => 90,
                'description' => '정답인데 확인 요청 시 자신감 부여',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P002'],
                    ['field' => 'answer_correct', 'operator' => '==', 'value' => true],
                    ['field' => 'requested_confirmation', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_6_2', // 부분 인정 확장
                'confidence' => 0.88,
                'message' => '맞았어! 네가 생각한 게 맞아 👍'
            ]
        ]
    ],
    
    // ========================================
    // P003: 감정출렁형 (Emotion-driven)
    // ========================================
    'P003' => [
        'persona_id' => 'P003',
        'name' => '감정출렁형',
        'positive_name' => '감정안정형',
        'icon' => '🎢',
        'positive_icon' => '😌',
        'primary_interventions' => ['INT_1_4', 'INT_7_1', 'INT_7_2', 'INT_7_3', 'INT_7_4', 'INT_7_5'],
        'avoid_interventions' => ['INT_6_1'], // 즉시 교정은 감정 악화
        'rules' => [
            [
                'rule_id' => 'PERS_P003_R1_mood_drop',
                'priority' => 96,
                'description' => '감정 하락 감지 시 즉시 정서 개입',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P003'],
                    ['field' => 'emotion_change', 'operator' => '==', 'value' => 'negative'],
                    ['field' => 'consecutive_wrong', 'operator' => '>=', 'value' => 1]
                ],
                'action' => 'INT_7_2', // 정상화
                'confidence' => 0.92,
                'message' => '이거 다 어려워해. 너만 그런 게 아니야 🌊'
            ],
            [
                'rule_id' => 'PERS_P003_R2_frustration',
                'priority' => 97,
                'description' => '좌절 신호 시 진정 대기',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P003'],
                    ['field' => 'emotion_type', 'operator' => 'in', 'value' => ['anxious', 'stuck']],
                    ['field' => 'sigh_detected', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_1_4', // 감정 진정 대기
                'confidence' => 0.90,
                'message' => '한 문제는 한 문제일 뿐! 차분하게 다음으로 가자'
            ],
            [
                'rule_id' => 'PERS_P003_R3_small_win',
                'priority' => 94,
                'description' => '연속 오답 시 쉬운 문제로 성취감 제공',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P003'],
                    ['field' => 'consecutive_wrong', 'operator' => '>=', 'value' => 2]
                ],
                'action' => 'INT_7_4', // 작은 성공 만들기
                'confidence' => 0.88,
                'message' => '이건 할 수 있을 거야! 해보자 💪'
            ]
        ]
    ],
    
    // ========================================
    // P004: 빠른데허술형 (Speed-but-Miss)
    // ========================================
    'P004' => [
        'persona_id' => 'P004',
        'name' => '빠른데허술형',
        'positive_name' => '정확추구형',
        'icon' => '⚡',
        'positive_icon' => '🎯',
        'primary_interventions' => ['INT_1_5', 'INT_4_1', 'INT_4_2', 'INT_6_1', 'INT_6_5'],
        'avoid_interventions' => ['INT_2_6'], // 요약 압축은 빠른 처리 강화
        'rules' => [
            [
                'rule_id' => 'PERS_P004_R1_fast_finish',
                'priority' => 93,
                'description' => '빠른 풀이 시 검증 유도',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P004'],
                    ['field' => 'solve_speed', 'operator' => '==', 'value' => 'fast'],
                    ['field' => 'item_difficulty', 'operator' => '!=', 'value' => 'easy']
                ],
                'action' => 'INT_1_5', // 자기 수정 대기
                'confidence' => 0.85,
                'message' => '마지막 10초 검증! 속도보다 정확도가 진짜 실력 ✅'
            ],
            [
                'rule_id' => 'PERS_P004_R2_careless_error',
                'priority' => 95,
                'description' => '계산 실수 패턴 감지 시 즉시 지적',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P004'],
                    ['field' => 'error_type', 'operator' => 'in', 'value' => ['sign_error', 'calculation_error', 'unit_missing']]
                ],
                'action' => 'INT_6_1', // 즉시 교정
                'confidence' => 0.90,
                'message' => '잠깐! 여기 부호 확인해봐 🔍'
            ],
            [
                'rule_id' => 'PERS_P004_R3_repeat_mistake',
                'priority' => 94,
                'description' => '같은 실수 반복 시 대비 강조',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P004'],
                    ['field' => 'same_error_count', 'operator' => '>=', 'value' => 2]
                ],
                'action' => 'INT_4_2', // 대비 강조
                'confidence' => 0.88,
                'message' => 'A가 아니라 B야! 헷갈리는 부분 확실히 하자'
            ]
        ]
    ],
    
    // ========================================
    // P005: 집중튐형 (Attention Hopper)
    // ========================================
    'P005' => [
        'persona_id' => 'P005',
        'name' => '집중튐형',
        'positive_name' => '집중유지형',
        'icon' => '🦘',
        'positive_icon' => '🔬',
        'primary_interventions' => ['INT_1_1', 'INT_2_2', 'INT_3_2', 'INT_4_3', 'INT_4_4'],
        'avoid_interventions' => ['INT_3_4'], // 극단적 예시는 주의 분산
        'rules' => [
            [
                'rule_id' => 'PERS_P005_R1_attention_drift',
                'priority' => 93,
                'description' => '시선/주의 이동 감지 시 집중 유도',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P005'],
                    ['field' => 'attention_drift', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_4_3', // 톤/속도 변화
                'confidence' => 0.85,
                'message' => '지금 이 문장에만 집중! 한 곳에 시선 고정해보자 👀'
            ],
            [
                'rule_id' => 'PERS_P005_R2_visual_anchor',
                'priority' => 91,
                'description' => '언어적 설명에 반응 없을 때 시각화',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P005'],
                    ['field' => 'verbal_response', 'operator' => '==', 'value' => 'none'],
                    ['field' => 'explanation_count', 'operator' => '>=', 'value' => 2]
                ],
                'action' => 'INT_3_2', // 시각화 전환
                'confidence' => 0.82,
                'message' => '그림으로 한번 볼까? 📊'
            ],
            [
                'rule_id' => 'PERS_P005_R3_mark_important',
                'priority' => 90,
                'description' => '핵심 못 찾을 때 시각적 마킹',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P005'],
                    ['field' => 'missed_key_point', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_4_4', // 시각적 마킹
                'confidence' => 0.87,
                'message' => '여기가 핵심이야! ⭐'
            ]
        ]
    ],
    
    // ========================================
    // P006: 패턴추론형 (Pattern Seeker)
    // ========================================
    'P006' => [
        'persona_id' => 'P006',
        'name' => '패턴추론형',
        'positive_name' => '구조마스터형',
        'icon' => '🧩',
        'positive_icon' => '🏗️',
        'primary_interventions' => ['INT_1_3', 'INT_2_4', 'INT_2_5', 'INT_3_4', 'INT_5_6'],
        'avoid_interventions' => ['INT_2_3'], // 단계 분해는 전체상 파괴
        'rules' => [
            [
                'rule_id' => 'PERS_P006_R1_structure_search',
                'priority' => 88,
                'description' => '구조 탐색 중일 때 여백 제공',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P006'],
                    ['field' => 'search_pattern', 'operator' => '==', 'value' => 'structure_seeking']
                ],
                'action' => 'INT_1_3', // 사고 여백 제공
                'confidence' => 0.85,
                'message' => '원리를 찾는 건 좋아! 구조부터 파악하고 가자 🗺️'
            ],
            [
                'rule_id' => 'PERS_P006_R2_why_question',
                'priority' => 87,
                'description' => '왜 이렇게 되는지 질문 시 역순 설명',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P006'],
                    ['field' => 'question_type', 'operator' => '==', 'value' => 'why']
                ],
                'action' => 'INT_2_4', // 역순 재구성
                'confidence' => 0.83,
                'message' => '결론부터 거꾸로 보면 이해될 거야'
            ],
            [
                'rule_id' => 'PERS_P006_R3_connection_need',
                'priority' => 86,
                'description' => '연결성 필요 시 연결고리 명시',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P006'],
                    ['field' => 'missed_connection', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_2_5', // 연결고리 명시
                'confidence' => 0.85,
                'message' => 'A이기 때문에 B, B이기 때문에 C야'
            ]
        ]
    ],
    
    // ========================================
    // P007: 최대한쉬운길형 (Efficiency Maximizer)
    // ========================================
    'P007' => [
        'persona_id' => 'P007',
        'name' => '최대한쉬운길형',
        'positive_name' => '효율전문가형',
        'icon' => '🛤️',
        'positive_icon' => '🚀',
        'primary_interventions' => ['INT_2_5', 'INT_2_6', 'INT_4_5'],
        'avoid_interventions' => ['INT_2_1'], // 동일 반복은 지루함 유발
        'rules' => [
            [
                'rule_id' => 'PERS_P007_R1_shortcut_request',
                'priority' => 85,
                'description' => '지름길 질문 시 핵심 규칙 제시',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P007'],
                    ['field' => 'question_type', 'operator' => 'in', 'value' => ['shortcut', 'tip', 'quick_way']]
                ],
                'action' => 'INT_2_6', // 요약 압축
                'confidence' => 0.88,
                'message' => '핵심 규칙 20%로 80% 해결! 스마트하게 가자 💡'
            ],
            [
                'rule_id' => 'PERS_P007_R2_bored_signal',
                'priority' => 84,
                'description' => '지루함 신호 시 중요도 강조',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P007'],
                    ['field' => 'engagement_level', 'operator' => '==', 'value' => 'low']
                ],
                'action' => 'INT_4_5', // 예고 신호
                'confidence' => 0.80,
                'message' => '이건 시험에 꼭 나와! 집중해봐'
            ]
        ]
    ],
    
    // ========================================
    // P008: 불안과몰입형 (Over-focusing Worrier)
    // ========================================
    'P008' => [
        'persona_id' => 'P008',
        'name' => '불안과몰입형',
        'positive_name' => '적정몰입형',
        'icon' => '😰',
        'positive_icon' => '⚖️',
        'primary_interventions' => ['INT_1_2', 'INT_3_5', 'INT_6_5', 'INT_7_3', 'INT_7_5'],
        'avoid_interventions' => ['INT_4_2'], // 대비 강조는 불안 가중
        'rules' => [
            [
                'rule_id' => 'PERS_P008_R1_over_check',
                'priority' => 91,
                'description' => '과도한 확인 반복 시 완벽주의 완화',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P008'],
                    ['field' => 'recheck_count', 'operator' => '>=', 'value' => 3],
                    ['field' => 'answer_changed', 'operator' => '==', 'value' => false]
                ],
                'action' => 'INT_7_3', // 난이도 조정 예고
                'confidence' => 0.88,
                'message' => '여기까지만 확인! 완벽주의 내려놓기 연습 🧘'
            ],
            [
                'rule_id' => 'PERS_P008_R2_easy_stuck',
                'priority' => 90,
                'description' => '쉬운 문제에 오래 붙잡힐 때 진행 유도',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P008'],
                    ['field' => 'item_difficulty', 'operator' => '==', 'value' => 'easy'],
                    ['field' => 'time_on_problem', 'operator' => '>', 'value' => 120]
                ],
                'action' => 'INT_7_5', // 유머/가벼운 전환
                'confidence' => 0.85,
                'message' => '이미 충분해! 다음으로 가도 괜찮아 😊'
            ],
            [
                'rule_id' => 'PERS_P008_R3_tension_high',
                'priority' => 92,
                'description' => '긴장 과다 시 이완 유도',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P008'],
                    ['field' => 'tension_level', 'operator' => '==', 'value' => 'high']
                ],
                'action' => 'INT_7_5', // 유머/가벼운 전환
                'confidence' => 0.87,
                'message' => '심호흡 한번! 긴장 풀어도 돼'
            ]
        ]
    ],
    
    // ========================================
    // P009: 추상약함형 (Concrete Learner)
    // ========================================
    'P009' => [
        'persona_id' => 'P009',
        'name' => '추상약함형',
        'positive_name' => '예시활용형',
        'icon' => '📦',
        'positive_icon' => '🎨',
        'primary_interventions' => ['INT_1_1', 'INT_2_3', 'INT_3_1', 'INT_3_2', 'INT_3_3', 'INT_3_6'],
        'avoid_interventions' => ['INT_2_4'], // 역순 재구성은 혼란 가중
        'rules' => [
            [
                'rule_id' => 'PERS_P009_R1_abstract_stuck',
                'priority' => 93,
                'description' => '추상 개념에서 막힐 때 구체적 수 대입',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P009'],
                    ['field' => 'content_type', 'operator' => '==', 'value' => 'abstract'],
                    ['field' => 'understanding_level', 'operator' => '<=', 'value' => 'low']
                ],
                'action' => 'INT_3_3', // 구체적 수 대입
                'confidence' => 0.90,
                'message' => '예시 하나로 시작! 구체적인 것부터 추상으로 🪜'
            ],
            [
                'rule_id' => 'PERS_P009_R2_example_request',
                'priority' => 92,
                'description' => '예시 요청 시 일상 비유 제공',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P009'],
                    ['field' => 'request_type', 'operator' => '==', 'value' => 'example']
                ],
                'action' => 'INT_3_1', // 일상 비유
                'confidence' => 0.92,
                'message' => '예를 들면...'
            ],
            [
                'rule_id' => 'PERS_P009_R3_variable_fear',
                'priority' => 91,
                'description' => '변수/문자 두려움 시 숫자로 변환',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P009'],
                    ['field' => 'content_has_variables', 'operator' => '==', 'value' => true],
                    ['field' => 'confusion_detected', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_3_3', // 구체적 수 대입
                'confidence' => 0.88,
                'message' => 'x 대신 숫자 넣어서 해보자! x=2라면?'
            ]
        ]
    ],
    
    // ========================================
    // P010: 상호작용의존형 (Interactive Dependent)
    // ========================================
    'P010' => [
        'persona_id' => 'P010',
        'name' => '상호작용의존형',
        'positive_name' => '자기주도형',
        'icon' => '🤝',
        'positive_icon' => '🌟',
        'primary_interventions' => ['INT_2_1', 'INT_3_7', 'INT_5_2', 'INT_6_3', 'INT_6_6', 'INT_7_6'],
        'avoid_interventions' => ['INT_1_3'], // 긴 사고 여백은 정지 유발
        'rules' => [
            [
                'rule_id' => 'PERS_P010_R1_solo_freeze',
                'priority' => 94,
                'description' => '혼자 풀다 정지 시 함께 완성',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P010'],
                    ['field' => 'solo_mode', 'operator' => '==', 'value' => true],
                    ['field' => 'freeze_duration', 'operator' => '>=', 'value' => 10]
                ],
                'action' => 'INT_6_3', // 함께 완성
                'confidence' => 0.90,
                'message' => '내 안의 선생님 깨우기! 스스로에게 질문해봐 💭'
            ],
            [
                'rule_id' => 'PERS_P010_R2_need_prompt',
                'priority' => 93,
                'description' => '외부 자극 필요 시 예측 질문',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P010'],
                    ['field' => 'passive_listening', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_5_2', // 예측 질문
                'confidence' => 0.88,
                'message' => '다음엔 뭘 해야 할 것 같아?'
            ],
            [
                'rule_id' => 'PERS_P010_R3_choice_empower',
                'priority' => 91,
                'description' => '무기력 시 선택권 부여',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P010'],
                    ['field' => 'engagement_level', 'operator' => '==', 'value' => 'very_low']
                ],
                'action' => 'INT_7_6', // 선택권 부여
                'confidence' => 0.85,
                'message' => '이거 먼저 할까, 저거 먼저 할까?'
            ]
        ]
    ],
    
    // ========================================
    // P011: 무기력형 (Low Drive)
    // ========================================
    'P011' => [
        'persona_id' => 'P011',
        'name' => '무기력형',
        'positive_name' => '동기활성형',
        'icon' => '😔',
        'positive_icon' => '🔥',
        'primary_interventions' => ['INT_1_4', 'INT_3_1', 'INT_4_3', 'INT_5_4', 'INT_7_1', 'INT_7_2', 'INT_7_4'],
        'avoid_interventions' => ['INT_1_3'], // 긴 대기는 더 무기력
        'rules' => [
            [
                'rule_id' => 'PERS_P011_R1_no_energy',
                'priority' => 95,
                'description' => '시작부터 에너지 없을 때 작은 목표 설정',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P011'],
                    ['field' => 'session_start', 'operator' => '==', 'value' => true],
                    ['field' => 'energy_level', 'operator' => '==', 'value' => 'low']
                ],
                'action' => 'INT_7_4', // 작은 성공 만들기
                'confidence' => 0.92,
                'message' => '초단위 목표 달성! 지금 이 한 문제만 집중 🎮'
            ],
            [
                'rule_id' => 'PERS_P011_R2_no_progress',
                'priority' => 93,
                'description' => '진도 안 나갈 때 선택지 제공',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P011'],
                    ['field' => 'progress_rate', 'operator' => '==', 'value' => 0],
                    ['field' => 'time_elapsed', 'operator' => '>', 'value' => 60]
                ],
                'action' => 'INT_5_4', // 선택지 질문
                'confidence' => 0.88,
                'message' => 'A일까 B일까? 하나만 골라봐'
            ],
            [
                'rule_id' => 'PERS_P011_R3_any_effort',
                'priority' => 96,
                'description' => '어떤 시도든 즉시 인정',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P011'],
                    ['field' => 'any_attempt', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_7_1', // 노력 인정
                'confidence' => 0.95,
                'message' => '좋아, 시작했네! 그게 제일 중요해 ✨'
            ]
        ]
    ],
    
    // ========================================
    // P012: 메타인지고수형 (Meta-high)
    // ========================================
    'P012' => [
        'persona_id' => 'P012',
        'name' => '메타인지고수형',
        'positive_name' => '전략마스터형',
        'icon' => '🧠',
        'positive_icon' => '👑',
        'primary_interventions' => ['INT_1_3', 'INT_1_5', 'INT_2_4', 'INT_3_4', 'INT_5_3', 'INT_5_7'],
        'avoid_interventions' => ['INT_2_1', 'INT_5_4'], // 단순 반복이나 이지선다는 지루함
        'rules' => [
            [
                'rule_id' => 'PERS_P012_R1_high_mastery',
                'priority' => 85,
                'description' => '높은 마스터리 시 고난도 도전',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P012'],
                    ['field' => 'mastery_level', 'operator' => '>=', 'value' => 0.8],
                    ['field' => 'engagement_level', 'operator' => '==', 'value' => 'medium']
                ],
                'action' => 'SUGGEST_CHALLENGE',
                'confidence' => 0.88,
                'message' => '고난도 도전! 네 전략을 더 날카롭게 만들자 ⚔️'
            ],
            [
                'rule_id' => 'PERS_P012_R2_strategy_share',
                'priority' => 83,
                'description' => '전략 공유 기회 제공',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P012'],
                    ['field' => 'problem_solved', 'operator' => '==', 'value' => true],
                    ['field' => 'method_novel', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_5_3', // 역질문
                'confidence' => 0.85,
                'message' => '어떻게 그렇게 풀었어? 네 방법 설명해봐!'
            ],
            [
                'rule_id' => 'PERS_P012_R3_self_correct',
                'priority' => 84,
                'description' => '자기 수정 시간 제공',
                'conditions' => [
                    ['field' => 'persona_id', 'operator' => '==', 'value' => 'P012'],
                    ['field' => 'self_questioning', 'operator' => '==', 'value' => true]
                ],
                'action' => 'INT_1_5', // 자기 수정 대기
                'confidence' => 0.90,
                'message' => '스스로 찾아봐. 충분히 할 수 있어'
            ]
        ]
    ]
];


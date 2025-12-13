<?php
/**
 * 상황별 페르소나 매핑 온톨로지
 * 
 * 학습 상황 → 페르소나 추천
 * AI 분석 결과 → 페르소나 자동 감지
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

return [
    // ================================================================================
    // 메타 정보
    // ================================================================================
    '@context' => [
        '@vocab' => 'https://mathking.kr/ontology/persona/',
        'mk' => 'https://mathking.kr/ontology/',
        'situation' => 'https://mathking.kr/ontology/situation/'
    ],
    
    // ================================================================================
    // 상황 정의 (Situation Definitions)
    // ================================================================================
    'situations' => [
        // ------------------------------------------------
        // 필기 패턴 상황
        // ------------------------------------------------
        'writing_pause_short' => [
            'id' => 'situation:writing_pause_short',
            'label' => '짧은 필기 정지 (3-5초)',
            'signals' => [
                ['field' => 'pause_duration', 'range' => [3, 5]]
            ],
            'persona_scores' => [
                'P001' => 0.3, // 회피형
                'P005' => 0.5, // 집중튐형
                'P009' => 0.4  // 추상약함형
            ],
            'primary_intervention' => 'INT_1_1'
        ],
        
        'writing_pause_long' => [
            'id' => 'situation:writing_pause_long',
            'label' => '긴 필기 정지 (10초 이상)',
            'signals' => [
                ['field' => 'pause_duration', 'range' => [10, null]]
            ],
            'persona_scores' => [
                'P001' => 0.8, // 회피형
                'P003' => 0.6, // 감정출렁형
                'P011' => 0.7  // 무기력형
            ],
            'primary_intervention' => 'INT_5_5'
        ],
        
        'repeated_erase' => [
            'id' => 'situation:repeated_erase',
            'label' => '반복 지우기',
            'signals' => [
                ['field' => 'erase_count', 'range' => [3, null]],
                ['field' => 'erase_time_window', 'range' => [null, 30]]
            ],
            'persona_scores' => [
                'P002' => 0.7, // 확인요구형
                'P004' => 0.6, // 빠른허술형
                'P008' => 0.8  // 불안과몰입형
            ],
            'primary_intervention' => 'INT_5_7'
        ],
        
        'fast_solve' => [
            'id' => 'situation:fast_solve',
            'label' => '빠른 풀이',
            'signals' => [
                ['field' => 'solve_speed', 'value' => 'fast']
            ],
            'persona_scores' => [
                'P004' => 0.9, // 빠른허술형
                'P007' => 0.5, // 쉬운길형
                'P012' => 0.4  // 메타인지고수형
            ],
            'primary_intervention' => 'INT_1_5'
        ],
        
        'slow_progress' => [
            'id' => 'situation:slow_progress',
            'label' => '느린 진행',
            'signals' => [
                ['field' => 'progress_rate', 'range' => [null, 0.3]]
            ],
            'persona_scores' => [
                'P001' => 0.6, // 회피형
                'P008' => 0.7, // 불안과몰입형
                'P009' => 0.6  // 추상약함형
            ],
            'primary_intervention' => 'INT_2_3'
        ],
        
        // ------------------------------------------------
        // 감정 상태 상황
        // ------------------------------------------------
        'emotion_confident' => [
            'id' => 'situation:emotion_confident',
            'label' => '자신감 있는 상태',
            'signals' => [
                ['field' => 'emotion_type', 'value' => 'confident']
            ],
            'persona_scores' => [
                'P004' => 0.5, // 빠른허술형 - 과신 위험
                'P012' => 0.7  // 메타인지고수형
            ],
            'primary_intervention' => 'SUGGEST_CHALLENGE'
        ],
        
        'emotion_stuck' => [
            'id' => 'situation:emotion_stuck',
            'label' => '막힌 상태',
            'signals' => [
                ['field' => 'emotion_type', 'value' => 'stuck']
            ],
            'persona_scores' => [
                'P001' => 0.8, // 회피형
                'P003' => 0.7, // 감정출렁형
                'P011' => 0.8  // 무기력형
            ],
            'primary_intervention' => 'INT_5_5'
        ],
        
        'emotion_anxious' => [
            'id' => 'situation:emotion_anxious',
            'label' => '불안한 상태',
            'signals' => [
                ['field' => 'emotion_type', 'value' => 'anxious']
            ],
            'persona_scores' => [
                'P003' => 0.9, // 감정출렁형
                'P008' => 0.9, // 불안과몰입형
                'P002' => 0.6  // 확인요구형
            ],
            'primary_intervention' => 'INT_7_3'
        ],
        
        'emotion_confused' => [
            'id' => 'situation:emotion_confused',
            'label' => '헷갈리는 상태',
            'signals' => [
                ['field' => 'emotion_type', 'value' => 'confused']
            ],
            'persona_scores' => [
                'P005' => 0.7, // 집중튐형
                'P009' => 0.8, // 추상약함형
                'P006' => 0.5  // 패턴추론형
            ],
            'primary_intervention' => 'INT_5_4'
        ],
        
        // ------------------------------------------------
        // 오류 패턴 상황
        // ------------------------------------------------
        'error_sign' => [
            'id' => 'situation:error_sign',
            'label' => '부호 오류',
            'signals' => [
                ['field' => 'error_type', 'value' => 'sign_error']
            ],
            'persona_scores' => [
                'P004' => 0.9, // 빠른허술형
                'P005' => 0.6  // 집중튐형
            ],
            'primary_intervention' => 'INT_6_1',
            'concept_focus' => 'mk:Concept_SignRuleForDivision'
        ],
        
        'error_reciprocal' => [
            'id' => 'situation:error_reciprocal',
            'label' => '역수 오류',
            'signals' => [
                ['field' => 'error_type', 'value' => 'reciprocal_forget']
            ],
            'persona_scores' => [
                'P005' => 0.7, // 집중튐형
                'P009' => 0.8  // 추상약함형
            ],
            'primary_intervention' => 'INT_6_5',
            'concept_focus' => 'mk:Concept_Reciprocal'
        ],
        
        'error_order' => [
            'id' => 'situation:error_order',
            'label' => '계산 순서 오류',
            'signals' => [
                ['field' => 'error_type', 'value' => 'order_error']
            ],
            'persona_scores' => [
                'P004' => 0.8, // 빠른허술형
                'P005' => 0.7  // 집중튐형
            ],
            'primary_intervention' => 'INT_4_2',
            'concept_focus' => 'mk:Concept_ContinuousDivision'
        ],
        
        'error_calculation' => [
            'id' => 'situation:error_calculation',
            'label' => '계산 실수',
            'signals' => [
                ['field' => 'error_type', 'value' => 'calculation_error']
            ],
            'persona_scores' => [
                'P004' => 0.9, // 빠른허술형
                'P005' => 0.5  // 집중튐형
            ],
            'primary_intervention' => 'INT_6_1'
        ],
        
        // ------------------------------------------------
        // 상호작용 패턴 상황
        // ------------------------------------------------
        'repeated_confirm_request' => [
            'id' => 'situation:repeated_confirm_request',
            'label' => '반복 확인 요청',
            'signals' => [
                ['field' => 'confirm_request_count', 'range' => [3, null]],
                ['field' => 'time_window', 'range' => [null, 60]]
            ],
            'persona_scores' => [
                'P002' => 0.95, // 확인요구형
                'P008' => 0.6   // 불안과몰입형
            ],
            'primary_intervention' => 'INT_5_3'
        ],
        
        'hint_request_frequent' => [
            'id' => 'situation:hint_request_frequent',
            'label' => '빈번한 힌트 요청',
            'signals' => [
                ['field' => 'hint_request_count', 'range' => [3, null]]
            ],
            'persona_scores' => [
                'P010' => 0.9, // 상호작용의존형
                'P001' => 0.6  // 회피형
            ],
            'primary_intervention' => 'INT_5_2'
        ],
        
        'passive_listening' => [
            'id' => 'situation:passive_listening',
            'label' => '수동적 청취',
            'signals' => [
                ['field' => 'interaction_rate', 'range' => [null, 0.2]],
                ['field' => 'response_delay', 'range' => [5, null]]
            ],
            'persona_scores' => [
                'P010' => 0.7, // 상호작용의존형
                'P011' => 0.8  // 무기력형
            ],
            'primary_intervention' => 'INT_5_2'
        ],
        
        'early_quit_attempt' => [
            'id' => 'situation:early_quit_attempt',
            'label' => '조기 포기 시도',
            'signals' => [
                ['field' => 'quit_signal', 'value' => true],
                ['field' => 'progress_percent', 'range' => [null, 30]]
            ],
            'persona_scores' => [
                'P001' => 0.95, // 회피형
                'P011' => 0.8   // 무기력형
            ],
            'primary_intervention' => 'INT_2_3'
        ],
        
        // ------------------------------------------------
        // 학습 패턴 상황
        // ------------------------------------------------
        'consecutive_correct' => [
            'id' => 'situation:consecutive_correct',
            'label' => '연속 정답',
            'signals' => [
                ['field' => 'consecutive_correct', 'range' => [3, null]]
            ],
            'persona_scores' => [
                'P012' => 0.7, // 메타인지고수형
                'P007' => 0.5  // 쉬운길형
            ],
            'primary_intervention' => 'SUGGEST_CHALLENGE'
        ],
        
        'consecutive_wrong' => [
            'id' => 'situation:consecutive_wrong',
            'label' => '연속 오답',
            'signals' => [
                ['field' => 'consecutive_wrong', 'range' => [2, null]]
            ],
            'persona_scores' => [
                'P003' => 0.8, // 감정출렁형
                'P011' => 0.7, // 무기력형
                'P001' => 0.6  // 회피형
            ],
            'primary_intervention' => 'INT_7_4'
        ],
        
        'mastery_high' => [
            'id' => 'situation:mastery_high',
            'label' => '높은 숙달도',
            'signals' => [
                ['field' => 'mastery_level', 'range' => [0.8, null]]
            ],
            'persona_scores' => [
                'P012' => 0.8, // 메타인지고수형
                'P007' => 0.6  // 쉬운길형
            ],
            'primary_intervention' => 'SUGGEST_CHALLENGE'
        ],
        
        'difficulty_mismatch' => [
            'id' => 'situation:difficulty_mismatch',
            'label' => '난이도 불일치',
            'signals' => [
                ['field' => 'item_difficulty', 'value' => 'hard'],
                ['field' => 'mastery_level', 'range' => [null, 0.4]]
            ],
            'persona_scores' => [
                'P003' => 0.7, // 감정출렁형
                'P008' => 0.8, // 불안과몰입형
                'P009' => 0.7  // 추상약함형
            ],
            'primary_intervention' => 'INT_7_3'
        ]
    ],
    
    // ================================================================================
    // 페르소나 상세 정의 (Persona Details)
    // ================================================================================
    'personas' => [
        'P001' => [
            'id' => 'P001',
            'name' => '막힘-회피형',
            'positive_name' => '도전형',
            'icon' => '🚫',
            'positive_icon' => '💪',
            'detection_signals' => [
                'writing_pause_long',
                'early_quit_attempt',
                'slow_progress'
            ],
            'trigger_weight' => [
                'pause_duration' => 0.4,
                'quit_signal' => 0.5,
                'progress_rate' => 0.3
            ],
            'preferred_interventions' => ['INT_1_1', 'INT_1_3', 'INT_5_5', 'INT_6_3', 'INT_2_3'],
            'avoid_interventions' => ['INT_4_5'],
            'chat_style' => [
                'tone' => 'encouraging',
                'message_length' => 'short',
                'use_emoji' => true,
                'emphasis' => 'small_steps'
            ],
            'response_templates' => [
                'greeting' => '작은 단계부터 시작해보자! 👣',
                'stuck' => '한번 생각해봐. 천천히 해도 돼 💭',
                'progress' => '잘했어! 첫 발을 내딛었네 ✨',
                'hint' => '시작점만 알려줄게. 거기서부터 해봐!'
            ]
        ],
        
        'P002' => [
            'id' => 'P002',
            'name' => '확인요구형',
            'positive_name' => '자기확신형',
            'icon' => '❓',
            'positive_icon' => '✨',
            'detection_signals' => [
                'repeated_confirm_request',
                'repeated_erase'
            ],
            'trigger_weight' => [
                'confirm_request_count' => 0.6,
                'recheck_count' => 0.4
            ],
            'preferred_interventions' => ['INT_2_1', 'INT_5_1', 'INT_6_2', 'INT_6_4'],
            'avoid_interventions' => ['INT_1_5'],
            'chat_style' => [
                'tone' => 'affirming',
                'message_length' => 'medium',
                'use_emoji' => true,
                'emphasis' => 'self_trust'
            ],
            'response_templates' => [
                'greeting' => '네 판단을 믿어봐! 🔍',
                'correct_confirm' => '맞았어! 네가 생각한 게 맞아 👍',
                'self_check' => '스스로 검증하는 힘을 키우자!',
                'progress' => '혼자 확인할 수 있었지? 잘했어!'
            ]
        ],
        
        'P003' => [
            'id' => 'P003',
            'name' => '감정출렁형',
            'positive_name' => '감정안정형',
            'icon' => '🎢',
            'positive_icon' => '😌',
            'detection_signals' => [
                'emotion_anxious',
                'emotion_stuck',
                'consecutive_wrong'
            ],
            'trigger_weight' => [
                'emotion_change' => 0.5,
                'consecutive_wrong' => 0.4,
                'frustration_signal' => 0.5
            ],
            'preferred_interventions' => ['INT_1_4', 'INT_7_1', 'INT_7_2', 'INT_7_3', 'INT_7_4', 'INT_7_5'],
            'avoid_interventions' => ['INT_6_1'],
            'chat_style' => [
                'tone' => 'soothing',
                'message_length' => 'short',
                'use_emoji' => true,
                'emphasis' => 'emotional_support'
            ],
            'response_templates' => [
                'greeting' => '한 문제는 한 문제일 뿐! 차분하게 가자 🌊',
                'frustration' => '괜찮아, 이거 다 어려워해. 너만 그런 게 아니야',
                'wrong' => '이건 할 수 있을 거야! 해보자 💪',
                'progress' => '잘했어! 감정 조절하면서 풀었네 😊'
            ]
        ],
        
        'P004' => [
            'id' => 'P004',
            'name' => '빠른데허술형',
            'positive_name' => '정확추구형',
            'icon' => '⚡',
            'positive_icon' => '🎯',
            'detection_signals' => [
                'fast_solve',
                'error_sign',
                'error_calculation',
                'error_order'
            ],
            'trigger_weight' => [
                'solve_speed' => 0.5,
                'careless_error_count' => 0.5
            ],
            'preferred_interventions' => ['INT_1_5', 'INT_4_1', 'INT_4_2', 'INT_6_1', 'INT_6_5'],
            'avoid_interventions' => ['INT_2_6'],
            'chat_style' => [
                'tone' => 'alert',
                'message_length' => 'short',
                'use_emoji' => true,
                'emphasis' => 'precision'
            ],
            'response_templates' => [
                'greeting' => '마지막 10초 검증! 속도보다 정확도가 진짜 실력 ✅',
                'fast_finish' => '빨리 풀었네! 한번 검토해볼까?',
                'error_found' => '잠깐! 여기 부호 확인해봐 🔍',
                'progress' => '정확하게 풀었네! 이게 진짜 실력이야 🎯'
            ]
        ],
        
        'P005' => [
            'id' => 'P005',
            'name' => '집중튐형',
            'positive_name' => '집중유지형',
            'icon' => '🦘',
            'positive_icon' => '🔬',
            'detection_signals' => [
                'writing_pause_short',
                'emotion_confused',
                'error_reciprocal'
            ],
            'trigger_weight' => [
                'attention_drift' => 0.6,
                'task_switch_count' => 0.4
            ],
            'preferred_interventions' => ['INT_1_1', 'INT_2_2', 'INT_3_2', 'INT_4_3', 'INT_4_4'],
            'avoid_interventions' => ['INT_3_4'],
            'chat_style' => [
                'tone' => 'focused',
                'message_length' => 'very_short',
                'use_emoji' => true,
                'emphasis' => 'attention'
            ],
            'response_templates' => [
                'greeting' => '지금 이 문장에만 집중! 👀',
                'drift' => '여기를 봐봐! 👉',
                'visual' => '그림으로 한번 볼까? 📊',
                'progress' => '집중했네! 그게 핵심이야 🔬'
            ]
        ],
        
        'P006' => [
            'id' => 'P006',
            'name' => '패턴추론형',
            'positive_name' => '구조마스터형',
            'icon' => '🧩',
            'positive_icon' => '🏗️',
            'detection_signals' => [
                'emotion_confused'
            ],
            'trigger_weight' => [
                'pattern_seeking' => 0.6,
                'structure_question' => 0.5
            ],
            'preferred_interventions' => ['INT_1_3', 'INT_2_4', 'INT_2_5', 'INT_3_4', 'INT_5_6'],
            'avoid_interventions' => ['INT_2_3'],
            'chat_style' => [
                'tone' => 'analytical',
                'message_length' => 'medium',
                'use_emoji' => false,
                'emphasis' => 'structure'
            ],
            'response_templates' => [
                'greeting' => '원리를 찾는 건 좋아! 구조부터 파악하고 가자 🗺️',
                'why' => '거꾸로 보면 이해될 거야. 결론부터...',
                'connection' => 'A이기 때문에 B, B이기 때문에 C야',
                'progress' => '구조를 꿰뚫었네! 🏗️'
            ]
        ],
        
        'P007' => [
            'id' => 'P007',
            'name' => '최대한쉬운길형',
            'positive_name' => '효율전문가형',
            'icon' => '🛤️',
            'positive_icon' => '🚀',
            'detection_signals' => [
                'mastery_high',
                'fast_solve'
            ],
            'trigger_weight' => [
                'shortcut_request' => 0.6,
                'efficiency_preference' => 0.5
            ],
            'preferred_interventions' => ['INT_2_5', 'INT_2_6', 'INT_4_5'],
            'avoid_interventions' => ['INT_2_1'],
            'chat_style' => [
                'tone' => 'efficient',
                'message_length' => 'short',
                'use_emoji' => true,
                'emphasis' => 'key_points'
            ],
            'response_templates' => [
                'greeting' => '핵심 규칙 20%로 80% 해결! 스마트하게 가자 💡',
                'shortcut' => '지름길 알려줄게!',
                'important' => '이거 시험에 나와! 🎯',
                'progress' => '효율적으로 풀었네! 🚀'
            ]
        ],
        
        'P008' => [
            'id' => 'P008',
            'name' => '불안과몰입형',
            'positive_name' => '적정몰입형',
            'icon' => '😰',
            'positive_icon' => '⚖️',
            'detection_signals' => [
                'emotion_anxious',
                'repeated_erase',
                'difficulty_mismatch'
            ],
            'trigger_weight' => [
                'recheck_count' => 0.5,
                'tension_level' => 0.5
            ],
            'preferred_interventions' => ['INT_1_2', 'INT_3_5', 'INT_6_5', 'INT_7_3', 'INT_7_5'],
            'avoid_interventions' => ['INT_4_2'],
            'chat_style' => [
                'tone' => 'calming',
                'message_length' => 'short',
                'use_emoji' => true,
                'emphasis' => 'balance'
            ],
            'response_templates' => [
                'greeting' => '여기까지만 확인! 완벽주의 내려놓기 연습 🧘',
                'over_check' => '이미 충분해! 다음으로 가도 괜찮아 😊',
                'tension' => '심호흡 한번! 긴장 풀어도 돼',
                'progress' => '적정한 집중! 잘했어 ⚖️'
            ]
        ],
        
        'P009' => [
            'id' => 'P009',
            'name' => '추상약함형',
            'positive_name' => '예시활용형',
            'icon' => '📦',
            'positive_icon' => '🎨',
            'detection_signals' => [
                'emotion_confused',
                'error_reciprocal',
                'slow_progress'
            ],
            'trigger_weight' => [
                'abstract_concept_struggle' => 0.6,
                'variable_fear' => 0.5
            ],
            'preferred_interventions' => ['INT_1_1', 'INT_2_3', 'INT_3_1', 'INT_3_2', 'INT_3_3', 'INT_3_6'],
            'avoid_interventions' => ['INT_2_4'],
            'chat_style' => [
                'tone' => 'concrete',
                'message_length' => 'medium',
                'use_emoji' => true,
                'emphasis' => 'examples'
            ],
            'response_templates' => [
                'greeting' => '예시 하나로 시작! 구체적인 것부터 추상으로 🪜',
                'abstract' => 'x 대신 숫자 넣어서 해보자! x=2라면?',
                'example' => '예를 들면...',
                'progress' => '예시로 이해했네! 🎨'
            ]
        ],
        
        'P010' => [
            'id' => 'P010',
            'name' => '상호작용의존형',
            'positive_name' => '자기주도형',
            'icon' => '🤝',
            'positive_icon' => '🌟',
            'detection_signals' => [
                'hint_request_frequent',
                'passive_listening'
            ],
            'trigger_weight' => [
                'hint_dependency' => 0.6,
                'solo_freeze' => 0.5
            ],
            'preferred_interventions' => ['INT_2_1', 'INT_3_7', 'INT_5_2', 'INT_6_3', 'INT_6_6', 'INT_7_6'],
            'avoid_interventions' => ['INT_1_3'],
            'chat_style' => [
                'tone' => 'guiding',
                'message_length' => 'medium',
                'use_emoji' => true,
                'emphasis' => 'self_initiative'
            ],
            'response_templates' => [
                'greeting' => '내 안의 선생님 깨우기! 스스로에게 질문해봐 💭',
                'solo' => '같이 하자! 여기서부터...',
                'prompt' => '다음엔 뭘 해야 할 것 같아?',
                'progress' => '혼자서도 할 수 있었지! 🌟'
            ]
        ],
        
        'P011' => [
            'id' => 'P011',
            'name' => '무기력형',
            'positive_name' => '동기활성형',
            'icon' => '😔',
            'positive_icon' => '🔥',
            'detection_signals' => [
                'emotion_stuck',
                'passive_listening',
                'early_quit_attempt',
                'consecutive_wrong'
            ],
            'trigger_weight' => [
                'energy_level' => 0.5,
                'engagement_level' => 0.5
            ],
            'preferred_interventions' => ['INT_1_4', 'INT_3_1', 'INT_4_3', 'INT_5_4', 'INT_7_1', 'INT_7_2', 'INT_7_4'],
            'avoid_interventions' => ['INT_1_3'],
            'chat_style' => [
                'tone' => 'energizing',
                'message_length' => 'very_short',
                'use_emoji' => true,
                'emphasis' => 'small_goals'
            ],
            'response_templates' => [
                'greeting' => '초단위 목표 달성! 지금 이 한 문제만 집중 🎮',
                'start' => '딱 1번만! 지금 이것만 🔥',
                'choice' => 'A일까 B일까? 하나만 골라봐',
                'progress' => '좋아, 시작했네! 그게 제일 중요해 ✨'
            ]
        ],
        
        'P012' => [
            'id' => 'P012',
            'name' => '메타인지고수형',
            'positive_name' => '전략마스터형',
            'icon' => '🧠',
            'positive_icon' => '👑',
            'detection_signals' => [
                'emotion_confident',
                'mastery_high',
                'consecutive_correct'
            ],
            'trigger_weight' => [
                'self_reflection' => 0.6,
                'strategy_awareness' => 0.5
            ],
            'preferred_interventions' => ['INT_1_3', 'INT_1_5', 'INT_2_4', 'INT_3_4', 'INT_5_3', 'INT_5_7'],
            'avoid_interventions' => ['INT_2_1', 'INT_5_4'],
            'chat_style' => [
                'tone' => 'challenging',
                'message_length' => 'medium',
                'use_emoji' => false,
                'emphasis' => 'strategy'
            ],
            'response_templates' => [
                'greeting' => '고난도 도전! 네 전략을 더 날카롭게 만들자 ⚔️',
                'strategy' => '어떻게 그렇게 풀었어? 네 방법 설명해봐!',
                'self_correct' => '스스로 찾아봐. 충분히 할 수 있어',
                'progress' => '전략 마스터! 👑'
            ]
        ]
    ],
    
    // ================================================================================
    // 페르소나 감지 알고리즘 설정
    // ================================================================================
    'detection_config' => [
        'min_signals_for_detection' => 2,
        'confidence_threshold' => 0.6,
        'decay_factor' => 0.9, // 시간에 따른 신호 가중치 감소
        'window_size' => 60, // 분석 윈도우 (초)
        'update_interval' => 10, // 페르소나 재평가 간격 (초)
        
        // 복합 상황 처리
        'compound_situations' => [
            'frustrated_beginner' => [
                'requires' => ['emotion_stuck', 'slow_progress'],
                'persona_boost' => ['P001' => 0.3, 'P011' => 0.3]
            ],
            'confident_careless' => [
                'requires' => ['emotion_confident', 'fast_solve', 'error_calculation'],
                'persona_boost' => ['P004' => 0.4]
            ],
            'anxious_perfectionist' => [
                'requires' => ['emotion_anxious', 'repeated_confirm_request', 'repeated_erase'],
                'persona_boost' => ['P008' => 0.4, 'P002' => 0.2]
            ]
        ]
    ],
    
    // ================================================================================
    // 페르소나 전환 규칙
    // ================================================================================
    'persona_transition' => [
        'positive_signals' => [
            'consecutive_correct' => 0.3,
            'self_correction' => 0.4,
            'reduced_hint_request' => 0.3,
            'stable_emotion' => 0.2
        ],
        'positive_threshold' => 0.7,
        'min_duration_for_transition' => 180, // 3분 이상 유지 시 긍정 전환
        'celebration_on_transition' => true
    ]
];


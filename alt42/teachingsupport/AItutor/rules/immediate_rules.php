<?php
/**
 * 즉각 반응 룰셋 (Immediate Response Rules)
 * 
 * Phase 1: 즉각 반응 시스템 - 룰 기반
 * - 필기 패턴 → 개입 트리거 연결
 * - 제스처 입력 → 즉각 응답
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

/**
 * 즉각 반응 룰 정의
 * 
 * 룰 구조:
 * - rule_id: 룰 고유 ID
 * - priority: 우선순위 (높을수록 먼저 평가)
 * - category: 룰 카테고리 (U0-U4)
 * - conditions: 조건 배열
 * - action: 실행할 개입 활동 ID
 * - confidence: 신뢰도 (0.0-1.0)
 * - rationale: 룰의 근거
 */

return [
    // ========================================
    // U1: 상태 인식 룰 (패턴 감지)
    // ========================================
    
    // 필기 정지 감지 - 인지 부하
    [
        'rule_id' => 'IMM_U1_R1_pause_cognitive_load',
        'priority' => 99,
        'category' => 'U1',
        'description' => '3초 이상 필기 정지 시 인지 부하 의심',
        'conditions' => [
            ['field' => 'pause_duration', 'operator' => '>=', 'value' => 3],
            ['field' => 'current_step', 'operator' => '!=', 'value' => 'completed'],
            ['field' => 'session_status', 'operator' => '==', 'value' => 'active']
        ],
        'action' => 'INT_1_1', // 인지 부하 대기
        'confidence' => 0.85,
        'rationale' => '3초 이상 정지는 인지 과부하 가능성이 높음'
    ],
    
    // 긴 정지 - 심각한 막힘
    [
        'rule_id' => 'IMM_U1_R2_long_pause_stuck',
        'priority' => 98,
        'category' => 'U1',
        'description' => '10초 이상 필기 정지 시 심각한 막힘 감지',
        'conditions' => [
            ['field' => 'pause_duration', 'operator' => '>=', 'value' => 10],
            ['field' => 'emotion_type', 'operator' => 'in', 'value' => ['neutral', 'confused', 'stuck']]
        ],
        'action' => 'INT_5_5', // 힌트 질문
        'confidence' => 0.90,
        'rationale' => '10초 이상 정지는 완전히 막힌 상태'
    ],
    
    // 반복 지우기 - 혼란
    [
        'rule_id' => 'IMM_U1_R3_erase_repeat_confusion',
        'priority' => 95,
        'category' => 'U1',
        'description' => '3회 이상 반복 지우기 시 혼란 감지',
        'conditions' => [
            ['field' => 'erase_count', 'operator' => '>=', 'value' => 3],
            ['field' => 'time_window', 'operator' => '<=', 'value' => 30] // 30초 이내
        ],
        'action' => 'INT_5_7', // 메타인지 질문
        'confidence' => 0.82,
        'rationale' => '반복적인 지우기는 개념 혼란 신호'
    ],
    
    // 빠른 풀이 - 검증 필요
    [
        'rule_id' => 'IMM_U1_R4_fast_solve_verify',
        'priority' => 90,
        'category' => 'U1',
        'description' => '예상보다 빠른 풀이 시 검증 필요',
        'conditions' => [
            ['field' => 'solve_duration', 'operator' => '<', 'value' => 'expected_duration * 0.5'],
            ['field' => 'item_difficulty', 'operator' => 'in', 'value' => ['medium', 'hard']]
        ],
        'action' => 'INT_6_1', // 즉시 교정 대기
        'confidence' => 0.75,
        'rationale' => '빠른 풀이는 실수 가능성 높음'
    ],
    
    // ========================================
    // U2: 분석 판단 룰 (제스처 입력)
    // ========================================
    
    // 체크 제스처 - 이해 확인
    [
        'rule_id' => 'IMM_U2_R1_gesture_check',
        'priority' => 100,
        'category' => 'U2',
        'description' => '✓ 제스처 입력 시 다음 단계 진행',
        'conditions' => [
            ['field' => 'gesture_type', 'operator' => '==', 'value' => 'check'],
            ['field' => 'current_step', 'operator' => '<', 'value' => 'max_step']
        ],
        'action' => 'STEP_ADVANCE',
        'confidence' => 0.95,
        'rationale' => '체크 제스처는 이해 완료 신호'
    ],
    
    // X 제스처 - 재설명 필요
    [
        'rule_id' => 'IMM_U2_R2_gesture_cross',
        'priority' => 100,
        'category' => 'U2',
        'description' => '✗ 제스처 입력 시 재설명 트리거',
        'conditions' => [
            ['field' => 'gesture_type', 'operator' => '==', 'value' => 'cross']
        ],
        'action' => 'INT_2_1', // 동일 반복 재설명
        'confidence' => 0.95,
        'rationale' => 'X 제스처는 이해 못함 신호'
    ],
    
    // 물음표 제스처 - 질문 필요
    [
        'rule_id' => 'IMM_U2_R3_gesture_question',
        'priority' => 100,
        'category' => 'U2',
        'description' => '? 제스처 입력 시 비침습적 질문 표시',
        'conditions' => [
            ['field' => 'gesture_type', 'operator' => '==', 'value' => 'question']
        ],
        'action' => 'NON_INTRUSIVE_QUESTION',
        'confidence' => 0.95,
        'rationale' => '물음표 제스처는 도움 요청 신호'
    ],
    
    // 원 제스처 - 확인 요청
    [
        'rule_id' => 'IMM_U2_R4_gesture_circle',
        'priority' => 100,
        'category' => 'U2',
        'description' => '○ 제스처 입력 시 확인 피드백',
        'conditions' => [
            ['field' => 'gesture_type', 'operator' => '==', 'value' => 'circle']
        ],
        'action' => 'INT_6_4', // 되물어 확인
        'confidence' => 0.90,
        'rationale' => '원 제스처는 확인 요청 신호'
    ],
    
    // 화살표 제스처 - 다음으로
    [
        'rule_id' => 'IMM_U2_R5_gesture_arrow',
        'priority' => 100,
        'category' => 'U2',
        'description' => '→ 제스처 입력 시 다음 문항으로',
        'conditions' => [
            ['field' => 'gesture_type', 'operator' => '==', 'value' => 'arrow']
        ],
        'action' => 'ITEM_ADVANCE',
        'confidence' => 0.90,
        'rationale' => '화살표 제스처는 건너뛰기 신호'
    ],
    
    // ========================================
    // U3: 개입 결정 룰 (감정 기반)
    // ========================================
    
    // 자신있음 감정 - 도전 제안
    [
        'rule_id' => 'IMM_U3_R1_emotion_confident',
        'priority' => 85,
        'category' => 'U3',
        'description' => '자신있어 감정 시 난이도 상향 제안',
        'conditions' => [
            ['field' => 'emotion_type', 'operator' => '==', 'value' => 'confident'],
            ['field' => 'consecutive_correct', 'operator' => '>=', 'value' => 2]
        ],
        'action' => 'SUGGEST_CHALLENGE',
        'confidence' => 0.80,
        'rationale' => '자신감 + 연속 정답은 도전 준비 완료'
    ],
    
    // 막힘 감정 - 힌트 제공
    [
        'rule_id' => 'IMM_U3_R2_emotion_stuck',
        'priority' => 92,
        'category' => 'U3',
        'description' => '막혔어 감정 선택 시 힌트 제공',
        'conditions' => [
            ['field' => 'emotion_type', 'operator' => '==', 'value' => 'stuck']
        ],
        'action' => 'INT_5_5', // 힌트 질문
        'confidence' => 0.88,
        'rationale' => '막힘 감정은 도움 요청'
    ],
    
    // 불안 감정 - 정서 조절
    [
        'rule_id' => 'IMM_U3_R3_emotion_anxious',
        'priority' => 93,
        'category' => 'U3',
        'description' => '불안해 감정 선택 시 정서 조절',
        'conditions' => [
            ['field' => 'emotion_type', 'operator' => '==', 'value' => 'anxious']
        ],
        'action' => 'INT_7_3', // 난이도 조정 예고
        'confidence' => 0.90,
        'rationale' => '불안 감정은 정서 개입 필요'
    ],
    
    // 헷갈림 감정 - 명확화
    [
        'rule_id' => 'IMM_U3_R4_emotion_confused',
        'priority' => 91,
        'category' => 'U3',
        'description' => '헷갈려 감정 선택 시 명확화 질문',
        'conditions' => [
            ['field' => 'emotion_type', 'operator' => '==', 'value' => 'confused']
        ],
        'action' => 'INT_5_4', // 선택지 질문
        'confidence' => 0.85,
        'rationale' => '헷갈림은 명확화 필요'
    ],
    
    // ========================================
    // U0: 시스템 제어 룰
    // ========================================
    
    // 세션 시작
    [
        'rule_id' => 'IMM_U0_R1_session_start',
        'priority' => 100,
        'category' => 'U0',
        'description' => '세션 시작 시 초기화',
        'conditions' => [
            ['field' => 'event_type', 'operator' => '==', 'value' => 'session_start']
        ],
        'action' => 'SESSION_INIT',
        'confidence' => 1.00,
        'rationale' => '세션 시작 시 컨텍스트 초기화'
    ],
    
    // 단계 완료
    [
        'rule_id' => 'IMM_U0_R2_step_complete',
        'priority' => 96,
        'category' => 'U0',
        'description' => '단계 완료 시 진행률 업데이트',
        'conditions' => [
            ['field' => 'step_status', 'operator' => '==', 'value' => 'completed']
        ],
        'action' => 'UPDATE_PROGRESS',
        'confidence' => 1.00,
        'rationale' => '단계 완료 기록'
    ],
    
    // ========================================
    // U4: 반영/학습 룰
    // ========================================
    
    // 개입 효과성 기록
    [
        'rule_id' => 'IMM_U4_R1_intervention_effect',
        'priority' => 80,
        'category' => 'U4',
        'description' => '개입 후 학생 반응 기록',
        'conditions' => [
            ['field' => 'intervention_executed', 'operator' => '==', 'value' => true],
            ['field' => 'student_response', 'operator' => '!=', 'value' => null]
        ],
        'action' => 'LOG_EFFECTIVENESS',
        'confidence' => 0.90,
        'rationale' => '개입 효과 학습을 위한 데이터 수집'
    ]
];


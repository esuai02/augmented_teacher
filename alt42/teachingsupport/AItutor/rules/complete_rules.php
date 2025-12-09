<?php
/**
 * 완결성 99% 룰셋 (Complete Rules)
 * 
 * 실제 선생님과 같은 상호작용을 위한 완전한 룰 정의
 * - 모든 상황 커버
 * - 조건 조합 최적화
 * - 연쇄 룰 지원
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    2.0
 */

return [
    // ================================================================================
    // LAYER 1: 세션 생명주기 룰 (Session Lifecycle)
    // ================================================================================
    
    'SL_001' => [
        'rule_id' => 'SL_001',
        'layer' => 'session',
        'name' => '세션 시작 - 초기 진단',
        'priority' => 100,
        'conditions' => [
            ['field' => 'event_type', 'op' => '==', 'value' => 'session_start']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'SESSION_INIT'],
            ['type' => 'chat', 'message' => '안녕! 오늘 {unit_name} 같이 공부해보자 📚', 'delay' => 500],
            ['type' => 'question', 'style' => 'button', 'text' => '준비됐어?', 'options' => [
                ['label' => '응, 시작하자!', 'value' => 'ready', 'next_rule' => 'SL_002'],
                ['label' => '잠깐만...', 'value' => 'wait', 'next_rule' => 'SL_003']
            ]]
        ],
        'confidence' => 1.0
    ],
    
    'SL_002' => [
        'rule_id' => 'SL_002',
        'layer' => 'session',
        'name' => '세션 시작 - 준비 완료',
        'priority' => 99,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'ready']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아! 먼저 문제를 한번 읽어볼까?'],
            ['type' => 'system', 'action' => 'SHOW_PROBLEM'],
            ['type' => 'system', 'action' => 'START_TIMER']
        ],
        'confidence' => 1.0
    ],
    
    'SL_003' => [
        'rule_id' => 'SL_003',
        'layer' => 'session',
        'name' => '세션 시작 - 대기 필요',
        'priority' => 99,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'wait']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '괜찮아, 천천히 준비해 😊'],
            ['type' => 'question', 'style' => 'button', 'text' => '어떤 게 필요해?', 'options' => [
                ['label' => '문제 미리보기', 'value' => 'preview', 'next_rule' => 'SL_004'],
                ['label' => '개념 복습', 'value' => 'review', 'next_rule' => 'SL_005'],
                ['label' => '이제 시작할게', 'value' => 'ready', 'next_rule' => 'SL_002']
            ]]
        ],
        'confidence' => 1.0
    ],
    
    'SL_004' => [
        'rule_id' => 'SL_004',
        'layer' => 'session',
        'name' => '문제 미리보기',
        'priority' => 98,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'preview']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'SHOW_PROBLEM_PREVIEW'],
            ['type' => 'chat', 'message' => '이런 유형의 문제야. 어떻게 느껴져?'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '할 만해 보여', 'value' => 'confident', 'emotion' => 'confident'],
                ['label' => '좀 어려워 보여', 'value' => 'worried', 'emotion' => 'anxious'],
                ['label' => '잘 모르겠어', 'value' => 'unsure', 'emotion' => 'confused']
            ]]
        ],
        'confidence' => 1.0
    ],
    
    'SL_005' => [
        'rule_id' => 'SL_005',
        'layer' => 'session',
        'name' => '개념 복습 제공',
        'priority' => 98,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'review']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아, 먼저 핵심 개념부터 확인하자!'],
            ['type' => 'system', 'action' => 'SHOW_CONCEPT_REVIEW'],
            ['type' => 'intervention', 'id' => 'INT_4_1'] // 핵심 반복 강조
        ],
        'confidence' => 1.0
    ],
    
    // ================================================================================
    // LAYER 2: 필기 패턴 감지 룰 (Writing Pattern Detection)
    // ================================================================================
    
    'WP_001' => [
        'rule_id' => 'WP_001',
        'layer' => 'writing',
        'name' => '필기 시작 감지',
        'priority' => 95,
        'conditions' => [
            ['field' => 'writing_event', 'op' => '==', 'value' => 'stroke_start'],
            ['field' => 'is_first_stroke', 'op' => '==', 'value' => true]
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'RESET_PAUSE_TIMER'],
            ['type' => 'log', 'event' => 'first_stroke']
        ],
        'confidence' => 1.0
    ],
    
    'WP_002' => [
        'rule_id' => 'WP_002',
        'layer' => 'writing',
        'name' => '짧은 멈춤 (3-5초) - 사고 중',
        'priority' => 90,
        'conditions' => [
            ['field' => 'pause_duration', 'op' => '>=', 'value' => 3],
            ['field' => 'pause_duration', 'op' => '<', 'value' => 5],
            ['field' => 'stroke_count', 'op' => '>', 'value' => 0]
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_1_1'], // 인지 부하 대기
            ['type' => 'log', 'event' => 'short_pause']
        ],
        'confidence' => 0.8
    ],
    
    'WP_003' => [
        'rule_id' => 'WP_003',
        'layer' => 'writing',
        'name' => '중간 멈춤 (5-10초) - 막힘 의심',
        'priority' => 92,
        'conditions' => [
            ['field' => 'pause_duration', 'op' => '>=', 'value' => 5],
            ['field' => 'pause_duration', 'op' => '<', 'value' => 10]
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '생각하는 중이구나 💭', 'style' => 'whisper'],
            ['type' => 'question', 'style' => 'button', 'text' => '어때?', 'options' => [
                ['label' => '생각 중이야', 'value' => 'thinking', 'next_rule' => 'WP_010'],
                ['label' => '조금 막혔어', 'value' => 'stuck', 'next_rule' => 'WP_011'],
                ['label' => '힌트 좀...', 'value' => 'hint', 'next_rule' => 'WP_012']
            ], 'timeout' => 5000, 'timeout_rule' => 'WP_010']
        ],
        'confidence' => 0.85
    ],
    
    'WP_004' => [
        'rule_id' => 'WP_004',
        'layer' => 'writing',
        'name' => '긴 멈춤 (10초 이상) - 심각한 막힘',
        'priority' => 95,
        'conditions' => [
            ['field' => 'pause_duration', 'op' => '>=', 'value' => 10]
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'CAPTURE_WHITEBOARD'],
            ['type' => 'system', 'action' => 'ANALYZE_WRITING'],
            ['type' => 'chat', 'message' => '막힌 것 같아. 같이 봐볼까? 🤔'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '힌트 줘', 'value' => 'hint', 'next_rule' => 'WP_012'],
                ['label' => '처음부터 다시', 'value' => 'restart', 'next_rule' => 'WP_013'],
                ['label' => '조금만 더 해볼게', 'value' => 'continue', 'next_rule' => 'WP_010']
            ]]
        ],
        'confidence' => 0.92
    ],
    
    'WP_005' => [
        'rule_id' => 'WP_005',
        'layer' => 'writing',
        'name' => '반복 지우기 (3회 이상) - 혼란',
        'priority' => 93,
        'conditions' => [
            ['field' => 'erase_count', 'op' => '>=', 'value' => 3],
            ['field' => 'erase_time_window', 'op' => '<=', 'value' => 30]
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '많이 지우고 있네. 헷갈리는 부분이 있어?'],
            ['type' => 'intervention', 'id' => 'INT_5_7'], // 메타인지 질문
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '어디서 시작해야 할지 모르겠어', 'value' => 'start_confused', 'next_rule' => 'WP_014'],
                ['label' => '중간 과정이 헷갈려', 'value' => 'process_confused', 'next_rule' => 'WP_015'],
                ['label' => '답이 안 맞는 것 같아', 'value' => 'answer_wrong', 'next_rule' => 'WP_016']
            ]]
        ],
        'confidence' => 0.88
    ],
    
    'WP_006' => [
        'rule_id' => 'WP_006',
        'layer' => 'writing',
        'name' => '빠른 풀이 완료',
        'priority' => 88,
        'conditions' => [
            ['field' => 'solve_duration', 'op' => '<', 'value' => 'expected_time * 0.5'],
            ['field' => 'item_difficulty', 'op' => 'in', 'value' => ['medium', 'hard']]
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '빨리 풀었네! 👏 한번 검토해볼까?'],
            ['type' => 'intervention', 'id' => 'INT_1_5'], // 자기 수정 대기
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '응, 다시 볼게', 'value' => 'review', 'next_rule' => 'WP_017'],
                ['label' => '확실해!', 'value' => 'confident', 'next_rule' => 'WP_018']
            ]]
        ],
        'confidence' => 0.75
    ],
    
    'WP_007' => [
        'rule_id' => 'WP_007',
        'layer' => 'writing',
        'name' => '느린 풀이 (시간 초과 위험)',
        'priority' => 85,
        'conditions' => [
            ['field' => 'solve_duration', 'op' => '>', 'value' => 'expected_time * 1.5'],
            ['field' => 'progress_percent', 'op' => '<', 'value' => 50]
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '시간이 좀 걸리네. 어디가 어려워?'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '개념이 헷갈려', 'value' => 'concept', 'next_rule' => 'WP_019'],
                ['label' => '계산이 복잡해', 'value' => 'calculation', 'next_rule' => 'WP_020'],
                ['label' => '괜찮아, 계속할게', 'value' => 'continue']
            ]]
        ],
        'confidence' => 0.82
    ],
    
    'WP_010' => [
        'rule_id' => 'WP_010',
        'layer' => 'writing',
        'name' => '사고 중 - 계속 대기',
        'priority' => 80,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'thinking']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아, 천천히 생각해봐 👍', 'style' => 'whisper'],
            ['type' => 'intervention', 'id' => 'INT_1_3'] // 사고 여백 제공
        ],
        'confidence' => 0.9
    ],
    
    'WP_011' => [
        'rule_id' => 'WP_011',
        'layer' => 'writing',
        'name' => '막힘 확인 - 힌트 제안',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'stuck']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '괜찮아! 어디서 막혔는지 같이 봐보자'],
            ['type' => 'question', 'style' => 'button', 'text' => '어떤 도움이 필요해?', 'options' => [
                ['label' => '첫 단계 힌트', 'value' => 'hint_first', 'next_rule' => 'HT_001'],
                ['label' => '공식 확인', 'value' => 'formula', 'next_rule' => 'HT_002'],
                ['label' => '비슷한 예제', 'value' => 'example', 'next_rule' => 'HT_003']
            ]]
        ],
        'confidence' => 0.92
    ],
    
    'WP_012' => [
        'rule_id' => 'WP_012',
        'layer' => 'writing',
        'name' => '힌트 요청 처리',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'hint']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'GET_CONTEXTUAL_HINT'],
            ['type' => 'intervention', 'id' => 'INT_5_5'], // 힌트 질문
            ['type' => 'log', 'event' => 'hint_requested']
        ],
        'confidence' => 0.95
    ],
    
    'WP_013' => [
        'rule_id' => 'WP_013',
        'layer' => 'writing',
        'name' => '처음부터 다시 시작',
        'priority' => 85,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'restart']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아! 새로 시작하자. 먼저 문제를 다시 읽어볼까?'],
            ['type' => 'system', 'action' => 'CLEAR_WHITEBOARD'],
            ['type' => 'intervention', 'id' => 'INT_2_3'] // 단계 분해
        ],
        'confidence' => 0.9
    ],
    
    'WP_014' => [
        'rule_id' => 'WP_014',
        'layer' => 'writing',
        'name' => '시작점 혼란 해결',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'start_confused']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '시작이 어렵지? 문제에서 뭘 구해야 하는지부터 확인하자'],
            ['type' => 'intervention', 'id' => 'INT_5_5'], // 힌트 질문
            ['type' => 'question', 'style' => 'button', 'text' => '이 문제에서 구해야 하는 건?', 'options' => 'DYNAMIC_FROM_ONTOLOGY']
        ],
        'confidence' => 0.9
    ],
    
    'WP_015' => [
        'rule_id' => 'WP_015',
        'layer' => 'writing',
        'name' => '중간 과정 혼란 해결',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'process_confused']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '중간 과정이 헷갈리구나. 단계별로 쪼개서 보자!'],
            ['type' => 'intervention', 'id' => 'INT_2_3'], // 단계 분해
            ['type' => 'system', 'action' => 'SHOW_STEP_BY_STEP']
        ],
        'confidence' => 0.9
    ],
    
    'WP_016' => [
        'rule_id' => 'WP_016',
        'layer' => 'writing',
        'name' => '답 검증 요청',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'answer_wrong']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'CAPTURE_AND_ANALYZE'],
            ['type' => 'chat', 'message' => '어디가 안 맞는 것 같아? 같이 확인해볼까?'],
            ['type' => 'intervention', 'id' => 'INT_6_4'] // 되물어 확인
        ],
        'confidence' => 0.9
    ],
    
    'WP_017' => [
        'rule_id' => 'WP_017',
        'layer' => 'writing',
        'name' => '자기 검토 시작',
        'priority' => 82,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'review']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아! 특히 부호랑 계산 순서 확인해봐 ✅'],
            ['type' => 'intervention', 'id' => 'INT_4_1'], // 핵심 반복 강조
            ['type' => 'system', 'action' => 'START_REVIEW_TIMER', 'duration' => 30]
        ],
        'confidence' => 0.88
    ],
    
    'WP_018' => [
        'rule_id' => 'WP_018',
        'layer' => 'writing',
        'name' => '확신 있는 답 제출',
        'priority' => 82,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'confident']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'SUBMIT_ANSWER'],
            ['type' => 'system', 'action' => 'CHECK_ANSWER']
        ],
        'confidence' => 0.85
    ],
    
    'WP_019' => [
        'rule_id' => 'WP_019',
        'layer' => 'writing',
        'name' => '개념 헷갈림 - 복습 제공',
        'priority' => 86,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'concept']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '개념부터 다시 정리하자!'],
            ['type' => 'intervention', 'id' => 'INT_3_3'], // 구체적 수 대입
            ['type' => 'system', 'action' => 'SHOW_CONCEPT_FROM_ONTOLOGY']
        ],
        'confidence' => 0.9
    ],
    
    'WP_020' => [
        'rule_id' => 'WP_020',
        'layer' => 'writing',
        'name' => '계산 복잡 - 분해 제공',
        'priority' => 86,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'calculation']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '계산을 작은 단계로 나눠서 해보자!'],
            ['type' => 'intervention', 'id' => 'INT_2_3'], // 단계 분해
            ['type' => 'system', 'action' => 'DECOMPOSE_CALCULATION']
        ],
        'confidence' => 0.9
    ],
    
    // ================================================================================
    // LAYER 3: 힌트 제공 룰 (Hint Delivery)
    // ================================================================================
    
    'HT_001' => [
        'rule_id' => 'HT_001',
        'layer' => 'hint',
        'name' => '첫 단계 힌트',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'hint_first']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'GET_FIRST_STEP_HINT'],
            ['type' => 'chat', 'message' => '💡 {first_step_hint}'],
            ['type' => 'question', 'style' => 'button', 'text' => '이해됐어?', 'options' => [
                ['label' => '응, 해볼게!', 'value' => 'understood'],
                ['label' => '더 설명해줘', 'value' => 'more', 'next_rule' => 'HT_004'],
                ['label' => '예시로 보여줘', 'value' => 'example', 'next_rule' => 'HT_003']
            ]]
        ],
        'confidence' => 0.92
    ],
    
    'HT_002' => [
        'rule_id' => 'HT_002',
        'layer' => 'hint',
        'name' => '공식 확인',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'formula']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'GET_RELATED_FORMULAS'],
            ['type' => 'chat', 'message' => '📐 이 공식을 사용해봐:\n{formula}'],
            ['type' => 'intervention', 'id' => 'INT_4_4'], // 시각적 마킹
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '어떻게 적용해?', 'value' => 'how_apply', 'next_rule' => 'HT_005'],
                ['label' => '알겠어!', 'value' => 'understood']
            ]]
        ],
        'confidence' => 0.92
    ],
    
    'HT_003' => [
        'rule_id' => 'HT_003',
        'layer' => 'hint',
        'name' => '예제 제공',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'example']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'GET_SIMILAR_EXAMPLE'],
            ['type' => 'chat', 'message' => '비슷한 예제 보여줄게!'],
            ['type' => 'intervention', 'id' => 'INT_3_3'], // 구체적 수 대입
            ['type' => 'system', 'action' => 'SHOW_WORKED_EXAMPLE']
        ],
        'confidence' => 0.95
    ],
    
    'HT_004' => [
        'rule_id' => 'HT_004',
        'layer' => 'hint',
        'name' => '추가 설명',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'more']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_2_3'], // 단계 분해
            ['type' => 'chat', 'message' => '더 쪼개서 설명할게!'],
            ['type' => 'system', 'action' => 'GET_DETAILED_EXPLANATION']
        ],
        'confidence' => 0.9
    ],
    
    'HT_005' => [
        'rule_id' => 'HT_005',
        'layer' => 'hint',
        'name' => '공식 적용 방법',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'how_apply']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '이 문제에 적용하면...'],
            ['type' => 'intervention', 'id' => 'INT_3_3'], // 구체적 수 대입
            ['type' => 'system', 'action' => 'SHOW_FORMULA_APPLICATION']
        ],
        'confidence' => 0.92
    ],
    
    // ================================================================================
    // LAYER 4: 제스처 반응 룰 (Gesture Response)
    // ================================================================================
    
    'GS_001' => [
        'rule_id' => 'GS_001',
        'layer' => 'gesture',
        'name' => '체크 제스처 - 이해 완료',
        'priority' => 100,
        'conditions' => [
            ['field' => 'gesture_type', 'op' => '==', 'value' => 'check']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아! 다음으로 넘어갈까? ✓'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '응, 다음!', 'value' => 'next', 'next_rule' => 'GS_010'],
                ['label' => '잠깐, 한번 더 볼게', 'value' => 'review']
            ]]
        ],
        'confidence' => 0.98
    ],
    
    'GS_002' => [
        'rule_id' => 'GS_002',
        'layer' => 'gesture',
        'name' => 'X 제스처 - 이해 안됨',
        'priority' => 100,
        'conditions' => [
            ['field' => 'gesture_type', 'op' => '==', 'value' => 'cross']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '이해가 안 됐구나. 다른 방식으로 설명해줄게!'],
            ['type' => 'intervention', 'id' => 'INT_2_1'], // 동일 반복
            ['type' => 'question', 'style' => 'button', 'text' => '어떤 설명이 좋을까?', 'options' => [
                ['label' => '그림으로', 'value' => 'visual', 'next_rule' => 'GS_011'],
                ['label' => '예시로', 'value' => 'example', 'next_rule' => 'HT_003'],
                ['label' => '더 쉽게', 'value' => 'simpler', 'next_rule' => 'GS_012']
            ]]
        ],
        'confidence' => 0.98
    ],
    
    'GS_003' => [
        'rule_id' => 'GS_003',
        'layer' => 'gesture',
        'name' => '물음표 제스처 - 질문',
        'priority' => 100,
        'conditions' => [
            ['field' => 'gesture_type', 'op' => '==', 'value' => 'question']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '궁금한 게 있구나! 뭐가 헷갈려? 🤔'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '이 단계가 왜 이렇게 되는지', 'value' => 'why_step', 'next_rule' => 'GS_013'],
                ['label' => '다음에 뭘 해야 하는지', 'value' => 'what_next', 'next_rule' => 'GS_014'],
                ['label' => '내 풀이가 맞는지', 'value' => 'check_mine', 'next_rule' => 'WP_016']
            ]]
        ],
        'confidence' => 0.98
    ],
    
    'GS_004' => [
        'rule_id' => 'GS_004',
        'layer' => 'gesture',
        'name' => '원 제스처 - 확인 요청',
        'priority' => 100,
        'conditions' => [
            ['field' => 'gesture_type', 'op' => '==', 'value' => 'circle']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'CAPTURE_CIRCLED_AREA'],
            ['type' => 'chat', 'message' => '이 부분 확인해줄까? 잠깐만...'],
            ['type' => 'intervention', 'id' => 'INT_6_4'], // 되물어 확인
            ['type' => 'system', 'action' => 'ANALYZE_CIRCLED_CONTENT']
        ],
        'confidence' => 0.95
    ],
    
    'GS_005' => [
        'rule_id' => 'GS_005',
        'layer' => 'gesture',
        'name' => '화살표 제스처 - 다음으로',
        'priority' => 100,
        'conditions' => [
            ['field' => 'gesture_type', 'op' => '==', 'value' => 'arrow']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'STEP_ADVANCE'],
            ['type' => 'chat', 'message' => '다음 단계로! →']
        ],
        'confidence' => 0.95
    ],
    
    'GS_010' => [
        'rule_id' => 'GS_010',
        'layer' => 'gesture',
        'name' => '다음 단계 진행',
        'priority' => 95,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'next']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'STEP_ADVANCE'],
            ['type' => 'system', 'action' => 'UPDATE_PROGRESS']
        ],
        'confidence' => 1.0
    ],
    
    'GS_011' => [
        'rule_id' => 'GS_011',
        'layer' => 'gesture',
        'name' => '시각적 설명',
        'priority' => 92,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'visual']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_3_2'], // 시각화 전환
            ['type' => 'system', 'action' => 'SHOW_VISUAL_EXPLANATION']
        ],
        'confidence' => 0.9
    ],
    
    'GS_012' => [
        'rule_id' => 'GS_012',
        'layer' => 'gesture',
        'name' => '더 쉬운 설명',
        'priority' => 92,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'simpler']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_3_6'], // 학생 언어 번역
            ['type' => 'system', 'action' => 'SIMPLIFY_EXPLANATION']
        ],
        'confidence' => 0.9
    ],
    
    'GS_013' => [
        'rule_id' => 'GS_013',
        'layer' => 'gesture',
        'name' => '왜 이렇게 되는지 설명',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'why_step']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_2_5'], // 연결고리 명시
            ['type' => 'system', 'action' => 'EXPLAIN_STEP_REASONING']
        ],
        'confidence' => 0.92
    ],
    
    'GS_014' => [
        'rule_id' => 'GS_014',
        'layer' => 'gesture',
        'name' => '다음 할 것 안내',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'what_next']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_5_2'], // 예측 질문
            ['type' => 'system', 'action' => 'GUIDE_NEXT_STEP']
        ],
        'confidence' => 0.92
    ],
    
    // ================================================================================
    // LAYER 5: 감정 반응 룰 (Emotion Response)
    // ================================================================================
    
    'EM_001' => [
        'rule_id' => 'EM_001',
        'layer' => 'emotion',
        'name' => '자신감 - 도전 제안',
        'priority' => 85,
        'conditions' => [
            ['field' => 'emotion_type', 'op' => '==', 'value' => 'confident'],
            ['field' => 'consecutive_correct', 'op' => '>=', 'value' => 2]
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '잘하고 있어! 💪 좀 더 어려운 거 해볼까?'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '좋아, 도전!', 'value' => 'challenge', 'next_rule' => 'EM_010'],
                ['label' => '아니, 이 정도로', 'value' => 'stay']
            ]]
        ],
        'confidence' => 0.85
    ],
    
    'EM_002' => [
        'rule_id' => 'EM_002',
        'layer' => 'emotion',
        'name' => '막힘 - 적극 지원',
        'priority' => 92,
        'conditions' => [
            ['field' => 'emotion_type', 'op' => '==', 'value' => 'stuck']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_7_2'], // 정상화
            ['type' => 'chat', 'message' => '이거 어려운 문제야. 다들 힘들어해 😊'],
            ['type' => 'question', 'style' => 'button', 'text' => '어떻게 도와줄까?', 'options' => [
                ['label' => '힌트 줘', 'value' => 'hint', 'next_rule' => 'WP_012'],
                ['label' => '처음부터 같이', 'value' => 'together', 'next_rule' => 'EM_011'],
                ['label' => '잠깐 쉴래', 'value' => 'break', 'next_rule' => 'EM_012']
            ]]
        ],
        'confidence' => 0.92
    ],
    
    'EM_003' => [
        'rule_id' => 'EM_003',
        'layer' => 'emotion',
        'name' => '불안 - 안정화',
        'priority' => 93,
        'conditions' => [
            ['field' => 'emotion_type', 'op' => '==', 'value' => 'anxious']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_7_3'], // 난이도 조정 예고
            ['type' => 'chat', 'message' => '괜찮아, 천천히 해도 돼. 시간 충분해 🌱'],
            ['type' => 'intervention', 'id' => 'INT_1_4'], // 감정 진정 대기
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '심호흡하고 다시', 'value' => 'breathe', 'next_rule' => 'EM_013'],
                ['label' => '쉬운 것부터', 'value' => 'easier', 'next_rule' => 'EM_014'],
                ['label' => '괜찮아, 계속할게', 'value' => 'continue']
            ]]
        ],
        'confidence' => 0.93
    ],
    
    'EM_004' => [
        'rule_id' => 'EM_004',
        'layer' => 'emotion',
        'name' => '헷갈림 - 명확화',
        'priority' => 90,
        'conditions' => [
            ['field' => 'emotion_type', 'op' => '==', 'value' => 'confused']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '어디가 헷갈려? 같이 정리해보자!'],
            ['type' => 'intervention', 'id' => 'INT_5_7'], // 메타인지 질문
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '개념이 헷갈려', 'value' => 'concept', 'next_rule' => 'WP_019'],
                ['label' => '순서가 헷갈려', 'value' => 'order', 'next_rule' => 'EM_015'],
                ['label' => '전부 다', 'value' => 'all', 'next_rule' => 'EM_016']
            ]]
        ],
        'confidence' => 0.88
    ],
    
    'EM_010' => [
        'rule_id' => 'EM_010',
        'layer' => 'emotion',
        'name' => '도전 수락',
        'priority' => 82,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'challenge']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'INCREASE_DIFFICULTY'],
            ['type' => 'chat', 'message' => '좋아! 더 어려운 문제 가보자! 🚀'],
            ['type' => 'intervention', 'id' => 'SUGGEST_CHALLENGE']
        ],
        'confidence' => 0.9
    ],
    
    'EM_011' => [
        'rule_id' => 'EM_011',
        'layer' => 'emotion',
        'name' => '함께 풀기',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'together']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_6_3'], // 함께 완성
            ['type' => 'chat', 'message' => '좋아, 같이 하자! 먼저 첫 번째 단계부터...'],
            ['type' => 'system', 'action' => 'START_GUIDED_MODE']
        ],
        'confidence' => 0.92
    ],
    
    'EM_012' => [
        'rule_id' => 'EM_012',
        'layer' => 'emotion',
        'name' => '휴식',
        'priority' => 85,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'break']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_7_5'], // 유머/가벼운 전환
            ['type' => 'chat', 'message' => '좋아! 잠깐 쉬자 ☕ 준비되면 말해줘'],
            ['type' => 'system', 'action' => 'PAUSE_SESSION']
        ],
        'confidence' => 0.95
    ],
    
    'EM_013' => [
        'rule_id' => 'EM_013',
        'layer' => 'emotion',
        'name' => '심호흡 유도',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'breathe']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'SHOW_BREATHING_EXERCISE'],
            ['type' => 'chat', 'message' => '🌬️ 숨을 깊게 들이쉬고... 내쉬고...'],
            ['type' => 'intervention', 'id' => 'INT_1_4'] // 감정 진정 대기
        ],
        'confidence' => 0.9
    ],
    
    'EM_014' => [
        'rule_id' => 'EM_014',
        'layer' => 'emotion',
        'name' => '난이도 낮추기',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'easier']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_7_4'], // 작은 성공 만들기
            ['type' => 'system', 'action' => 'DECREASE_DIFFICULTY'],
            ['type' => 'chat', 'message' => '좋아! 먼저 쉬운 것부터 자신감 쌓자 💪']
        ],
        'confidence' => 0.9
    ],
    
    'EM_015' => [
        'rule_id' => 'EM_015',
        'layer' => 'emotion',
        'name' => '순서 정리',
        'priority' => 86,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'order']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_2_3'], // 단계 분해
            ['type' => 'chat', 'message' => '순서를 정리해줄게!\n1️⃣ → 2️⃣ → 3️⃣'],
            ['type' => 'system', 'action' => 'SHOW_ORDERED_STEPS']
        ],
        'confidence' => 0.9
    ],
    
    'EM_016' => [
        'rule_id' => 'EM_016',
        'layer' => 'emotion',
        'name' => '전체 재설명',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'all']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_7_1'], // 노력 인정
            ['type' => 'chat', 'message' => '괜찮아! 처음부터 천천히 다시 설명해줄게'],
            ['type' => 'system', 'action' => 'RESTART_EXPLANATION']
        ],
        'confidence' => 0.9
    ],
    
    // ================================================================================
    // LAYER 6: 답 검증 룰 (Answer Verification)
    // ================================================================================
    
    'AV_001' => [
        'rule_id' => 'AV_001',
        'layer' => 'answer',
        'name' => '정답',
        'priority' => 100,
        'conditions' => [
            ['field' => 'answer_result', 'op' => '==', 'value' => 'correct']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '🎉 정답이야! 잘했어!!'],
            ['type' => 'intervention', 'id' => 'INT_7_1'], // 노력 인정
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '다음 문제!', 'value' => 'next', 'next_rule' => 'AV_010'],
                ['label' => '왜 맞았는지 확인', 'value' => 'review', 'next_rule' => 'AV_011'],
                ['label' => '비슷한 문제 더', 'value' => 'similar', 'next_rule' => 'AV_012']
            ]]
        ],
        'confidence' => 1.0
    ],
    
    'AV_002' => [
        'rule_id' => 'AV_002',
        'layer' => 'answer',
        'name' => '오답 - 가까운 실수',
        'priority' => 98,
        'conditions' => [
            ['field' => 'answer_result', 'op' => '==', 'value' => 'incorrect'],
            ['field' => 'error_type', 'op' => 'in', 'value' => ['sign_error', 'calculation_error']]
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_6_2'], // 부분 인정 확장
            ['type' => 'chat', 'message' => '거의 다 맞았어! 👏 작은 실수가 있네'],
            ['type' => 'system', 'action' => 'HIGHLIGHT_ERROR_LOCATION'],
            ['type' => 'question', 'style' => 'button', 'text' => '어디가 틀렸는지 볼까?', 'options' => [
                ['label' => '응, 보여줘', 'value' => 'show', 'next_rule' => 'AV_020'],
                ['label' => '내가 찾아볼게', 'value' => 'self', 'next_rule' => 'AV_021']
            ]]
        ],
        'confidence' => 0.95
    ],
    
    'AV_003' => [
        'rule_id' => 'AV_003',
        'layer' => 'answer',
        'name' => '오답 - 개념 오류',
        'priority' => 97,
        'conditions' => [
            ['field' => 'answer_result', 'op' => '==', 'value' => 'incorrect'],
            ['field' => 'error_type', 'op' => 'in', 'value' => ['reciprocal_forget', 'concept_error']]
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_6_5'], // 오개념 즉시 분리
            ['type' => 'chat', 'message' => '여기 중요한 개념이 빠졌어! 같이 봐보자'],
            ['type' => 'system', 'action' => 'EXPLAIN_CONCEPT_ERROR'],
            ['type' => 'question', 'style' => 'button', 'options' => [
                ['label' => '개념 다시 보기', 'value' => 'concept', 'next_rule' => 'AV_022'],
                ['label' => '예시로 보여줘', 'value' => 'example', 'next_rule' => 'HT_003']
            ]]
        ],
        'confidence' => 0.93
    ],
    
    'AV_004' => [
        'rule_id' => 'AV_004',
        'layer' => 'answer',
        'name' => '오답 - 순서 오류',
        'priority' => 96,
        'conditions' => [
            ['field' => 'answer_result', 'op' => '==', 'value' => 'incorrect'],
            ['field' => 'error_type', 'op' => '==', 'value' => 'order_error']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_4_2'], // 대비 강조
            ['type' => 'chat', 'message' => '계산 순서가 바뀌었어! 순서가 중요해'],
            ['type' => 'system', 'action' => 'SHOW_CORRECT_ORDER']
        ],
        'confidence' => 0.92
    ],
    
    'AV_010' => [
        'rule_id' => 'AV_010',
        'layer' => 'answer',
        'name' => '다음 문제로',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'next']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'ITEM_ADVANCE'],
            ['type' => 'system', 'action' => 'UPDATE_PROGRESS'],
            ['type' => 'chat', 'message' => '다음 문제 가보자! 📝']
        ],
        'confidence' => 1.0
    ],
    
    'AV_011' => [
        'rule_id' => 'AV_011',
        'layer' => 'answer',
        'name' => '정답 리뷰',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'review']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋은 습관이야! 왜 맞았는지 확인해보자'],
            ['type' => 'system', 'action' => 'SHOW_SOLUTION_REVIEW']
        ],
        'confidence' => 0.95
    ],
    
    'AV_012' => [
        'rule_id' => 'AV_012',
        'layer' => 'answer',
        'name' => '비슷한 문제 제공',
        'priority' => 85,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'similar']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아! 비슷한 문제로 연습하자'],
            ['type' => 'system', 'action' => 'GET_SIMILAR_PROBLEM']
        ],
        'confidence' => 0.9
    ],
    
    'AV_020' => [
        'rule_id' => 'AV_020',
        'layer' => 'answer',
        'name' => '오류 위치 표시',
        'priority' => 92,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'show']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_4_4'], // 시각적 마킹
            ['type' => 'system', 'action' => 'HIGHLIGHT_ERROR_DETAIL'],
            ['type' => 'chat', 'message' => '👉 여기를 봐봐. {error_explanation}']
        ],
        'confidence' => 0.95
    ],
    
    'AV_021' => [
        'rule_id' => 'AV_021',
        'layer' => 'answer',
        'name' => '자기 오류 찾기',
        'priority' => 88,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'self']
        ],
        'actions' => [
            ['type' => 'intervention', 'id' => 'INT_1_5'], // 자기 수정 대기
            ['type' => 'chat', 'message' => '좋아! 스스로 찾아보는 거 좋은 습관이야 👍'],
            ['type' => 'system', 'action' => 'START_SELF_CHECK_TIMER', 'duration' => 30]
        ],
        'confidence' => 0.9
    ],
    
    'AV_022' => [
        'rule_id' => 'AV_022',
        'layer' => 'answer',
        'name' => '개념 재학습',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'concept']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'SHOW_CONCEPT_FROM_ONTOLOGY'],
            ['type' => 'intervention', 'id' => 'INT_3_3'], // 구체적 수 대입
            ['type' => 'chat', 'message' => '이 개념을 다시 정리해보자!']
        ],
        'confidence' => 0.92
    ],
    
    // ================================================================================
    // LAYER 7: 장기기억 룰 (Long-term Memory)
    // ================================================================================
    
    'LM_001' => [
        'rule_id' => 'LM_001',
        'layer' => 'memory',
        'name' => '장기기억 단계 도달',
        'priority' => 95,
        'conditions' => [
            ['field' => 'current_step', 'op' => '==', 'value' => 5],
            ['field' => 'step_label', 'op' => '==', 'value' => '장기기억화']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '🏆 마지막 단계야! 오래 기억하도록 정리하자'],
            ['type' => 'question', 'style' => 'button', 'text' => '어떤 방법으로 할까?', 'options' => [
                ['label' => '핵심 정리', 'value' => 'summary', 'next_rule' => 'LM_010'],
                ['label' => '반복 연습', 'value' => 'practice', 'next_rule' => 'LM_011'],
                ['label' => '내가 설명해볼게', 'value' => 'teach', 'next_rule' => 'LM_012']
            ]]
        ],
        'confidence' => 1.0
    ],
    
    'LM_010' => [
        'rule_id' => 'LM_010',
        'layer' => 'memory',
        'name' => '핵심 정리',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'summary']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'GENERATE_KEY_POINTS'],
            ['type' => 'chat', 'message' => '오늘 배운 핵심을 정리해볼게!\n\n{key_points}'],
            ['type' => 'intervention', 'id' => 'INT_2_6'] // 요약 압축
        ],
        'confidence' => 0.95
    ],
    
    'LM_011' => [
        'rule_id' => 'LM_011',
        'layer' => 'memory',
        'name' => '반복 연습',
        'priority' => 90,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'practice']
        ],
        'actions' => [
            ['type' => 'system', 'action' => 'START_MEMORY_ACTIVITY'],
            ['type' => 'chat', 'message' => '3번 반복하면 기억에 오래 남아! 시작하자 🧠']
        ],
        'confidence' => 0.95
    ],
    
    'LM_012' => [
        'rule_id' => 'LM_012',
        'layer' => 'memory',
        'name' => '가르치기 학습',
        'priority' => 92,
        'conditions' => [
            ['field' => 'user_response', 'op' => '==', 'value' => 'teach']
        ],
        'actions' => [
            ['type' => 'chat', 'message' => '좋아! 가르치는 게 최고의 학습이지. 설명해봐!'],
            ['type' => 'intervention', 'id' => 'INT_5_3'], // 역질문
            ['type' => 'system', 'action' => 'ENABLE_TEACHING_MODE']
        ],
        'confidence' => 0.95
    ]
];


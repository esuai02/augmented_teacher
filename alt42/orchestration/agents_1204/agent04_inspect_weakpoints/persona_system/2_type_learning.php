<?php
/**
 * Agent04 유형학습 페르소나 시스템 설정
 *
 * 유형별 문제 학습 시 발생하는 인지관성(Cognitive Inertia) 패턴 정의
 * 각 패턴에 대한 해결 전략, 확인 포인트, 선생님 대화 템플릿 포함
 *
 * @package AugmentedTeacher\Agent04\PersonaSystem
 * @version 2.0
 * @since 2025-12-03
 */

return [
    // ==========================================
    // 상황 기본 정보
    // ==========================================
    'situation' => [
        'id' => 2,
        'code' => 'type_learning',
        'name' => '유형학습',
        'description' => '유형별 문제 학습 시 발생하는 인지관성 패턴을 탐지하고 개선 전략을 제시',
        'icon' => '📝',
        'color' => '#8b5cf6',
        'version' => '2.0.0',
    ],

    // ==========================================
    // 시스템 설정
    // ==========================================
    'system' => [
        'debug_mode' => false,
        'log_enabled' => true,
        'log_level' => 'info',
    ],

    // ==========================================
    // 엔진 설정
    // ==========================================
    'engine' => [
        'default_persona' => 'type_learning_analyzer',
        'default_tone' => 'Supportive',
        'confidence_threshold' => 0.6,
        'enable_audio_feedback' => true,
        'audio_base_url' => 'https://mathking.kr/Contents/personas/type_learning/',
        'audio_format' => 'wav',
    ],

    // ==========================================
    // 세부 항목 정의
    // ==========================================
    'sub_items' => [
        'type_efficacy' => [
            'name' => '대표유형 효능감 인식',
            'description' => '대표유형 학습 방법에 대한 자기효능감 인식',
            'icon' => '💪',
        ],
        'weekly_goal' => [
            'name' => '주간목표',
            'description' => '주간 학습 목표 설정 및 관리',
            'icon' => '📅',
        ],
        'daily_goal' => [
            'name' => '오늘목표',
            'description' => '일일 학습 목표 설정 및 달성',
            'icon' => '🎯',
        ],
        'pomodoro_selection' => [
            'name' => '포모도로',
            'description' => '포모도로 기법 선택 및 적용',
            'icon' => '🍅',
        ],
        'method_selection' => [
            'name' => '방법선택',
            'description' => '학습 방법 선택 과정',
            'icon' => '🔧',
        ],
        'order_selection' => [
            'name' => '순서선택',
            'description' => '문제 풀이 순서 결정',
            'icon' => '📊',
        ],
        'interval_selection' => [
            'name' => '시간간격 선택',
            'description' => '학습 시간 간격 설정',
            'icon' => '⏰',
        ],
        'supplementary_learning' => [
            'name' => '보충학습',
            'description' => '부족한 부분 보충 학습',
            'icon' => '📚',
        ],
        'hint_setting' => [
            'name' => '힌트설정',
            'description' => '힌트 활용 방법 설정',
            'icon' => '💡',
        ],
        'qa_interaction' => [
            'name' => '질의응답',
            'description' => '유형 학습 중 질의응답',
            'icon' => '💬',
        ],
    ],

    // ==========================================
    // 카테고리 정의 (유형학습 상황 특화)
    // ==========================================
    'categories' => [
        'goal_setting' => [
            'name' => '목표 설정 문제',
            'description' => '적절한 목표 설정에 어려움을 겪는 패턴',
            'icon' => '🎯',
            'color' => '#ef4444',
            'priority_weight' => 1.0,
        ],
        'planning_failure' => [
            'name' => '계획 실패',
            'description' => '학습 계획 수립 및 실행 실패',
            'icon' => '📋',
            'color' => '#f59e0b',
            'priority_weight' => 0.95,
        ],
        'method_confusion' => [
            'name' => '방법 혼란',
            'description' => '효과적인 학습 방법을 찾지 못하는 패턴',
            'icon' => '🤔',
            'color' => '#8b5cf6',
            'priority_weight' => 0.9,
        ],
        'time_management' => [
            'name' => '시간 관리 실패',
            'description' => '학습 시간을 효율적으로 관리하지 못하는 패턴',
            'icon' => '⏰',
            'color' => '#06b6d4',
            'priority_weight' => 0.85,
        ],
        'dependency' => [
            'name' => '의존성',
            'description' => '힌트나 외부 도움에 지나치게 의존하는 패턴',
            'icon' => '🆘',
            'color' => '#10b981',
            'priority_weight' => 0.8,
        ],
        'avoidance' => [
            'name' => '회피 성향',
            'description' => '어려운 유형을 회피하려는 패턴',
            'icon' => '🏃',
            'color' => '#ec4899',
            'priority_weight' => 0.85,
        ],
        'inefficiency' => [
            'name' => '비효율적 학습',
            'description' => '비효율적인 방법으로 시간을 낭비하는 패턴',
            'icon' => '🔄',
            'color' => '#6366f1',
            'priority_weight' => 0.9,
        ],
    ],

    // ==========================================
    // 우선순위 레벨 정의
    // ==========================================
    'priority_levels' => [
        'high' => [
            'name' => '높음',
            'color' => '#ef4444',
            'weight' => 1.0,
            'intervention_urgency' => 'immediate',
        ],
        'medium' => [
            'name' => '보통',
            'color' => '#f59e0b',
            'weight' => 0.7,
            'intervention_urgency' => 'scheduled',
        ],
        'low' => [
            'name' => '낮음',
            'color' => '#10b981',
            'weight' => 0.4,
            'intervention_urgency' => 'optional',
        ],
    ],

    // ==========================================
    // 인지관성 페르소나 정의
    // ==========================================
    'personas' => [
        // ------------------------------------------
        // 대표유형 효능감 인식 관련 페르소나
        // ------------------------------------------
        1 => [
            'id' => 1,
            'name' => '유형 두려움형',
            'desc' => '새로운 문제 유형을 마주하면 시도 전에 포기하는 패턴.',
            'sub_item' => 'type_efficacy',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '😨',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'유형 친해지기\' → 새 유형은 먼저 해설 읽기 → 비슷한 쉬운 문제 3개 먼저 풀기 → 자신감 쌓고 어려운 문제 도전',
                'check' => '쉬운 문제 3개를 풀었는지, 자신감이 생겼는지 확인',
                'teacher_dialog' => '선생님, 새 유형이 무서웠는데 쉬운 문제 3개를 먼저 풀었어요. 이제 좀 자신감이 생겼는지 확인해주세요!',
            ],
        ],
        2 => [
            'id' => 2,
            'name' => '과거 실패 고착형',
            'desc' => '예전에 틀렸던 유형을 계속 못 할 것이라 믿는 패턴.',
            'sub_item' => 'type_efficacy',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '🔙',
            'priority' => 'high',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'재도전 기록\' → 예전에 틀렸던 유형 1개 선택 → 다시 공부하고 풀기 → 성공하면 \'극복 일지\'에 기록',
                'check' => '극복 일지에 기록했는지, 성공 경험을 했는지 확인',
                'teacher_dialog' => '선생님, 예전에 못 풀던 유형을 다시 도전해서 성공했어요! 극복 일지에 기록했는데 봐주세요!',
            ],
        ],
        3 => [
            'id' => 3,
            'name' => '자기 과소평가형',
            'desc' => '실제로는 잘하면서도 유형 학습 능력이 부족하다고 믿는 패턴.',
            'sub_item' => 'type_efficacy',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '😔',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'증거 수집\' → 지난주 맞힌 유형 문제들 다시 보기 → \'내가 잘한 것\' 리스트 작성',
                'check' => '리스트에 3개 이상 적었는지, 객관적으로 평가했는지 확인',
                'teacher_dialog' => '선생님, \'내가 잘한 것\' 리스트를 만들었어요. 제가 생각보다 잘하고 있는 건지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 주간목표 관련 페르소나
        // ------------------------------------------
        4 => [
            'id' => 4,
            'name' => '비현실적 목표형',
            'desc' => '달성 불가능한 높은 주간 목표를 세우고 좌절하는 패턴.',
            'sub_item' => 'weekly_goal',
            'category' => 'goal_setting',
            'category_name' => '목표 설정 문제',
            'icon' => '🚀',
            'priority' => 'high',
            'audio_time' => '2:15',
            'solution' => [
                'action' => '\'70% 목표\' 규칙 → 할 수 있다고 생각하는 양의 70%만 목표로 설정 → 달성 후 추가 목표',
                'check' => '목표의 70%를 달성했는지, 추가 목표를 세웠는지 확인',
                'teacher_dialog' => '선생님, 이번 주는 70% 목표로 세웠어요. 무리하지 않고 달성했는데, 다음 주 목표도 같이 봐주세요!',
            ],
        ],
        5 => [
            'id' => 5,
            'name' => '목표 부재형',
            'desc' => '구체적인 주간 목표 없이 막연히 공부하는 패턴.',
            'sub_item' => 'weekly_goal',
            'category' => 'planning_failure',
            'category_name' => '계획 실패',
            'icon' => '🌫️',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'SMART 목표\' 설정 → 구체적, 측정 가능, 달성 가능, 관련성, 시간 제한 → 5가지 충족하는 목표 작성',
                'check' => '목표가 SMART 기준을 충족하는지 확인',
                'teacher_dialog' => '선생님, SMART 기준으로 주간 목표를 세웠어요. 잘 세웠는지 확인해주세요!',
            ],
        ],
        6 => [
            'id' => 6,
            'name' => '목표 수정 거부형',
            'desc' => '상황이 바뀌어도 처음 세운 목표를 고집하는 패턴.',
            'sub_item' => 'weekly_goal',
            'category' => 'goal_setting',
            'category_name' => '목표 설정 문제',
            'icon' => '🪨',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'수요일 점검\' → 주 중간에 목표 달성도 확인 → 필요시 목표 조정',
                'check' => '수요일에 점검했는지, 합리적으로 조정했는지 확인',
                'teacher_dialog' => '선생님, 수요일에 점검해보니 목표를 조정할 필요가 있었어요. 제 조정이 적절한지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 오늘목표 관련 페르소나
        // ------------------------------------------
        7 => [
            'id' => 7,
            'name' => '과부하 목표형',
            'desc' => '하루에 소화할 수 없는 양의 유형을 학습하려는 패턴.',
            'sub_item' => 'daily_goal',
            'category' => 'goal_setting',
            'category_name' => '목표 설정 문제',
            'icon' => '💥',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'3유형 규칙\' → 하루 새 유형은 최대 3개까지 → 나머지는 복습에 할당',
                'check' => '3유형 규칙을 지켰는지, 복습 시간을 확보했는지 확인',
                'teacher_dialog' => '선생님, 오늘 새 유형 3개만 공부하고 복습 시간을 확보했어요. 적당한 양인지 확인해주세요!',
            ],
        ],
        8 => [
            'id' => 8,
            'name' => '무계획 시작형',
            'desc' => '오늘 뭘 할지 정하지 않고 일단 책을 펴는 패턴.',
            'sub_item' => 'daily_goal',
            'category' => 'planning_failure',
            'category_name' => '계획 실패',
            'icon' => '🎲',
            'priority' => 'high',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'3분 계획\' → 공부 시작 전 3분간 오늘 목표 3가지 적기 → 완료 시 체크',
                'check' => '3분 계획을 세웠는지, 목표를 달성했는지 확인',
                'teacher_dialog' => '선생님, 오늘 시작 전에 3분 계획을 세웠어요. 목표 3가지를 다 달성했는지 확인해주세요!',
            ],
        ],
        9 => [
            'id' => 9,
            'name' => '완료 확인 부재형',
            'desc' => '오늘 목표를 세웠지만 달성 여부를 확인하지 않는 패턴.',
            'sub_item' => 'daily_goal',
            'category' => 'planning_failure',
            'category_name' => '계획 실패',
            'icon' => '❓',
            'priority' => 'medium',
            'audio_time' => '1:45',
            'solution' => [
                'action' => '\'종료 점검\' → 학습 종료 5분 전 목표 달성 체크 → 미달성 시 내일 목표에 반영',
                'check' => '종료 점검을 했는지, 미달성 항목을 반영했는지 확인',
                'teacher_dialog' => '선생님, 종료 점검을 했더니 1개는 내일로 미뤄야 할 것 같아요. 괜찮을까요?',
            ],
        ],

        // ------------------------------------------
        // 포모도로 관련 페르소나
        // ------------------------------------------
        10 => [
            'id' => 10,
            'name' => '휴식 스킵형',
            'desc' => '포모도로 휴식 시간을 건너뛰고 계속 공부하다 번아웃되는 패턴.',
            'sub_item' => 'pomodoro_selection',
            'category' => 'time_management',
            'category_name' => '시간 관리 실패',
            'icon' => '😮‍💨',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'필수 휴식 알람\' → 25분 후 반드시 5분 휴식 알람 설정 → 휴식 중 일어나 스트레칭',
                'check' => '알람을 설정했는지, 휴식을 지켰는지 확인',
                'teacher_dialog' => '선생님, 오늘 포모도로 4세트 하면서 휴식을 모두 지켰어요. 집중력이 더 좋아진 것 같아요!',
            ],
        ],
        11 => [
            'id' => 11,
            'name' => '포모도로 포기형',
            'desc' => '25분을 채우지 못하고 중간에 포기하는 패턴.',
            'sub_item' => 'pomodoro_selection',
            'category' => 'time_management',
            'category_name' => '시간 관리 실패',
            'icon' => '🏳️',
            'priority' => 'high',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'미니 포모도로\' → 처음엔 15분으로 시작 → 성공하면 5분씩 늘려 25분까지',
                'check' => '15분을 완료했는지, 점진적으로 늘리고 있는지 확인',
                'teacher_dialog' => '선생님, 15분 포모도로로 시작했더니 성공했어요! 이제 20분으로 늘려볼까요?',
            ],
        ],
        12 => [
            'id' => 12,
            'name' => '방해 취약형',
            'desc' => '포모도로 중 외부 방해에 쉽게 집중이 끊기는 패턴.',
            'sub_item' => 'pomodoro_selection',
            'category' => 'time_management',
            'category_name' => '시간 관리 실패',
            'icon' => '📱',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'방해 차단\' → 포모도로 시작 시 핸드폰 무음 + 다른 방 → 방해 요소 메모해두고 휴식 시간에 처리',
                'check' => '방해 차단을 실천했는지, 집중 시간이 늘었는지 확인',
                'teacher_dialog' => '선생님, 핸드폰을 다른 방에 두고 공부했더니 집중이 잘 됐어요!',
            ],
        ],

        // ------------------------------------------
        // 방법선택 관련 페르소나
        // ------------------------------------------
        13 => [
            'id' => 13,
            'name' => '무작정 반복형',
            'desc' => '효과적인 방법 없이 같은 유형을 무작정 반복만 하는 패턴.',
            'sub_item' => 'method_selection',
            'category' => 'inefficiency',
            'category_name' => '비효율적 학습',
            'icon' => '🔄',
            'priority' => 'high',
            'audio_time' => '2:15',
            'solution' => [
                'action' => '\'방법 실험\' → 같은 유형 3문제를 다른 방법으로 풀기 → 가장 효율적인 방법 선택',
                'check' => '3가지 방법을 시도했는지, 최적 방법을 찾았는지 확인',
                'teacher_dialog' => '선생님, 같은 유형을 3가지 방법으로 풀어봤어요. 어떤 방법이 가장 좋은지 확인해주세요!',
            ],
        ],
        14 => [
            'id' => 14,
            'name' => '익숙한 방법 고집형',
            'desc' => '더 효율적인 방법이 있어도 익숙한 방법만 고집하는 패턴.',
            'sub_item' => 'method_selection',
            'category' => 'method_confusion',
            'category_name' => '방법 혼란',
            'icon' => '🔒',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'새 방법 도전\' → 일주일에 1개 새로운 풀이법 시도 → 효과 비교 기록',
                'check' => '새 방법을 시도했는지, 비교 기록을 했는지 확인',
                'teacher_dialog' => '선생님, 이번 주 새 풀이법을 시도해봤어요. 기존 방법과 비교해서 어떤지 봐주세요!',
            ],
        ],
        15 => [
            'id' => 15,
            'name' => '방법 혼란형',
            'desc' => '여러 방법 중 어떤 것을 선택해야 할지 결정하지 못하는 패턴.',
            'sub_item' => 'method_selection',
            'category' => 'method_confusion',
            'category_name' => '방법 혼란',
            'icon' => '🤷',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'방법 결정 트리\' → 유형별 최적 풀이법 정리표 만들기 → 문제 보면 트리 따라 방법 선택',
                'check' => '결정 트리를 만들었는지, 활용하고 있는지 확인',
                'teacher_dialog' => '선생님, 방법 결정 트리를 만들었어요. 제가 정리한 게 맞는지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 순서선택 관련 페르소나
        // ------------------------------------------
        16 => [
            'id' => 16,
            'name' => '쉬운 것만 먼저형',
            'desc' => '항상 쉬운 유형만 먼저 풀고 어려운 유형을 미루는 패턴.',
            'sub_item' => 'order_selection',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '🐣',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'샌드위치 순서\' → 쉬움 → 어려움 → 쉬움 순서로 배치 → 어려운 것을 중간에 끼워넣기',
                'check' => '샌드위치 순서를 지켰는지, 어려운 유형을 풀었는지 확인',
                'teacher_dialog' => '선생님, 샌드위치 순서로 공부했더니 어려운 유형도 풀 수 있었어요!',
            ],
        ],
        17 => [
            'id' => 17,
            'name' => '순서 무관심형',
            'desc' => '문제 풀이 순서를 전혀 고려하지 않고 무작위로 푸는 패턴.',
            'sub_item' => 'order_selection',
            'category' => 'planning_failure',
            'category_name' => '계획 실패',
            'icon' => '🎰',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'전략적 순서\' → 난이도 파악 후 순서 결정 → 집중력 높을 때 어려운 것 배치',
                'check' => '전략적으로 순서를 정했는지, 효율이 높아졌는지 확인',
                'teacher_dialog' => '선생님, 오늘 순서를 전략적으로 정하고 공부했어요. 효율이 좋아졌는지 확인해주세요!',
            ],
        ],
        18 => [
            'id' => 18,
            'name' => '번호 순서 고집형',
            'desc' => '무조건 문제 번호 순서대로만 풀려고 하는 패턴.',
            'sub_item' => 'order_selection',
            'category' => 'method_confusion',
            'category_name' => '방법 혼란',
            'icon' => '1️⃣',
            'priority' => 'low',
            'audio_time' => '1:45',
            'solution' => [
                'action' => '\'유연한 순서\' → 어려우면 표시하고 다음으로 → 나중에 돌아오기',
                'check' => '유연하게 순서를 바꿨는지, 막힘 없이 진행했는지 확인',
                'teacher_dialog' => '선생님, 어려운 문제는 표시하고 넘어갔다가 나중에 풀었어요. 이 방법이 괜찮은가요?',
            ],
        ],

        // ------------------------------------------
        // 시간간격 선택 관련 페르소나
        // ------------------------------------------
        19 => [
            'id' => 19,
            'name' => '몰아치기 학습형',
            'desc' => '간격 없이 한꺼번에 몰아서 공부하고 금방 잊어버리는 패턴.',
            'sub_item' => 'interval_selection',
            'category' => 'inefficiency',
            'category_name' => '비효율적 학습',
            'icon' => '⚡',
            'priority' => 'high',
            'audio_time' => '2:20',
            'solution' => [
                'action' => '\'간격 반복\' → 같은 유형을 1일, 3일, 7일 간격으로 복습 → 복습 일정표 작성',
                'check' => '간격 반복을 지켰는지, 기억이 더 오래가는지 확인',
                'teacher_dialog' => '선생님, 간격 반복 일정표를 만들어서 복습했어요. 기억이 더 잘 되는 것 같아요!',
            ],
        ],
        20 => [
            'id' => 20,
            'name' => '장시간 집중 실패형',
            'desc' => '오래 앉아있지만 실제 집중 시간은 짧은 패턴.',
            'sub_item' => 'interval_selection',
            'category' => 'time_management',
            'category_name' => '시간 관리 실패',
            'icon' => '😪',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'집중 시간 측정\' → 타이머로 실제 집중 시간 기록 → 집중력 떨어지면 휴식 후 재시작',
                'check' => '집중 시간을 측정했는지, 적절히 휴식을 취했는지 확인',
                'teacher_dialog' => '선생님, 집중 시간을 측정해봤더니 실제로 45분밖에 안 됐어요. 어떻게 늘릴 수 있을까요?',
            ],
        ],
        21 => [
            'id' => 21,
            'name' => '복습 간격 무시형',
            'desc' => '복습 타이밍을 놓쳐 배운 내용을 금방 잊어버리는 패턴.',
            'sub_item' => 'interval_selection',
            'category' => 'planning_failure',
            'category_name' => '계획 실패',
            'icon' => '📅',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'복습 알림\' → 학습 후 1일, 7일 복습 알람 설정 → 알람 울리면 바로 복습',
                'check' => '알람을 설정했는지, 알람에 맞춰 복습했는지 확인',
                'teacher_dialog' => '선생님, 복습 알람을 설정하고 지켜봤어요. 이전보다 기억이 더 잘 나요!',
            ],
        ],

        // ------------------------------------------
        // 보충학습 관련 페르소나
        // ------------------------------------------
        22 => [
            'id' => 22,
            'name' => '보충 회피형',
            'desc' => '부족한 부분이 있어도 보충 학습을 미루거나 피하는 패턴.',
            'sub_item' => 'supplementary_learning',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '🙈',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'보충 우선\' 규칙 → 새 유형 학습 전 부족한 유형 1개 먼저 보충 → 보충 완료 체크',
                'check' => '보충을 먼저 했는지, 기본기가 탄탄해졌는지 확인',
                'teacher_dialog' => '선생님, 새 유형 전에 부족한 유형을 먼저 보충했어요. 기본기가 좋아졌는지 확인해주세요!',
            ],
        ],
        23 => [
            'id' => 23,
            'name' => '무분별 보충형',
            'desc' => '필요 없는 것까지 모두 보충하려다 시간을 낭비하는 패턴.',
            'sub_item' => 'supplementary_learning',
            'category' => 'inefficiency',
            'category_name' => '비효율적 학습',
            'icon' => '🌊',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'우선순위 보충\' → 틀린 문제 분석 후 가장 약한 유형 3개만 선택 → 집중 보충',
                'check' => '3개를 선택했는지, 효율적으로 보충했는지 확인',
                'teacher_dialog' => '선생님, 가장 약한 유형 3개만 골라서 집중 보충했어요. 선택이 맞는지 확인해주세요!',
            ],
        ],
        24 => [
            'id' => 24,
            'name' => '보충 방법 미숙형',
            'desc' => '보충 학습을 하지만 효과적인 방법을 모르는 패턴.',
            'sub_item' => 'supplementary_learning',
            'category' => 'method_confusion',
            'category_name' => '방법 혼란',
            'icon' => '🤔',
            'priority' => 'medium',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'보충 3단계\' → 1)개념 다시 읽기 2)예제 따라 풀기 3)비슷한 문제 3개 풀기',
                'check' => '3단계를 따랐는지, 이해가 향상됐는지 확인',
                'teacher_dialog' => '선생님, 보충 3단계대로 공부했어요. 이해가 좋아졌는지 확인해주세요!',
            ],
        ],

        // ------------------------------------------
        // 힌트설정 관련 페르소나
        // ------------------------------------------
        25 => [
            'id' => 25,
            'name' => '힌트 의존형',
            'desc' => '힌트 없이는 문제를 시도조차 하지 않으려는 패턴.',
            'sub_item' => 'hint_setting',
            'category' => 'dependency',
            'category_name' => '의존성',
            'icon' => '🆘',
            'priority' => 'high',
            'audio_time' => '2:15',
            'solution' => [
                'action' => '\'5분 먼저\' 규칙 → 힌트 보기 전 최소 5분 혼자 시도 → 5분 후에도 모르면 힌트 1개만',
                'check' => '5분을 시도했는지, 힌트 사용을 줄였는지 확인',
                'teacher_dialog' => '선생님, 힌트 보기 전에 5분 먼저 시도했어요. 혼자 푼 문제가 늘었는지 확인해주세요!',
            ],
        ],
        26 => [
            'id' => 26,
            'name' => '힌트 거부형',
            'desc' => '필요한 상황에서도 힌트를 보지 않고 시간을 낭비하는 패턴.',
            'sub_item' => 'hint_setting',
            'category' => 'inefficiency',
            'category_name' => '비효율적 학습',
            'icon' => '🙅',
            'priority' => 'medium',
            'audio_time' => '1:55',
            'solution' => [
                'action' => '\'10분 규칙\' → 10분 막히면 힌트 보기 → 힌트 보고 풀면 나중에 혼자 다시 풀기',
                'check' => '10분 규칙을 지켰는지, 효율이 높아졌는지 확인',
                'teacher_dialog' => '선생님, 10분 막혀서 힌트를 봤어요. 나중에 혼자 다시 풀어볼게요!',
            ],
        ],
        27 => [
            'id' => 27,
            'name' => '힌트 남용형',
            'desc' => '힌트를 너무 쉽게 보고 학습 효과가 떨어지는 패턴.',
            'sub_item' => 'hint_setting',
            'category' => 'dependency',
            'category_name' => '의존성',
            'icon' => '📖',
            'priority' => 'high',
            'audio_time' => '2:00',
            'solution' => [
                'action' => '\'힌트 쿠폰제\' → 하루 힌트 사용 3회로 제한 → 정말 필요할 때만 사용',
                'check' => '힌트 사용을 3회 이내로 제한했는지 확인',
                'teacher_dialog' => '선생님, 오늘 힌트를 2번만 사용했어요. 스스로 푸는 능력이 늘었나요?',
            ],
        ],

        // ------------------------------------------
        // 질의응답 관련 페르소나
        // ------------------------------------------
        28 => [
            'id' => 28,
            'name' => '질문 두려움형',
            'desc' => '유형 학습 중 모르는 것이 있어도 질문하기를 두려워하는 패턴.',
            'sub_item' => 'qa_interaction',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '🙊',
            'priority' => 'high',
            'audio_time' => '2:10',
            'solution' => [
                'action' => '\'질문 노트\' → 모르는 것 바로 적기 → 질문 시간에 노트 보며 질문',
                'check' => '질문 노트를 작성했는지, 실제로 질문했는지 확인',
                'teacher_dialog' => '선생님, 질문 노트에 적어뒀던 것들을 질문할게요!',
            ],
        ],
        29 => [
            'id' => 29,
            'name' => '질문 미루기형',
            'desc' => '질문을 나중에 하려다 결국 안 하게 되는 패턴.',
            'sub_item' => 'qa_interaction',
            'category' => 'avoidance',
            'category_name' => '회피 성향',
            'icon' => '⏰',
            'priority' => 'medium',
            'audio_time' => '1:50',
            'solution' => [
                'action' => '\'즉시 질문\' 규칙 → 의문이 생기면 24시간 내 질문 → 질문 예약 시스템 활용',
                'check' => '24시간 내에 질문했는지 확인',
                'teacher_dialog' => '선생님, 어제 모르는 게 생겨서 바로 질문하러 왔어요!',
            ],
        ],
        30 => [
            'id' => 30,
            'name' => '불명확 질문형',
            'desc' => '질문을 하지만 무엇이 모르는지 명확하게 표현하지 못하는 패턴.',
            'sub_item' => 'qa_interaction',
            'category' => 'method_confusion',
            'category_name' => '방법 혼란',
            'icon' => '🌫️',
            'priority' => 'medium',
            'audio_time' => '2:05',
            'solution' => [
                'action' => '\'질문 공식\' → \'[유형] 중에서 [구체적 부분]이 [왜/어떻게] 모르겠어요\' 형식으로 질문',
                'check' => '질문 공식을 사용했는지, 명확하게 질문했는지 확인',
                'teacher_dialog' => '선생님, 이차방정식 유형 중에서 판별식 사용 시점이 왜 이렇게 정해지는지 모르겠어요!',
            ],
        ],
    ],
];

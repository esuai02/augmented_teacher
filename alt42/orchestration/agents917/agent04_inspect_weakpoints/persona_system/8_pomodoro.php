<?php
/**
 * Agent04 Persona System - Pomodoro (포모도로)
 *
 * 포모도로 학습법 적용 시 발생하는 인지관성 패턴 정의
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04_InspectWeakpoints
 * @version     2.0.0
 * @author      Augmented Teacher Team
 * @created     2025-12-03
 *
 * 파일 위치: /alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/8_pomodoro.php
 */

return [
    // =========================================================================
    // 상황 메타데이터
    // =========================================================================
    'situation' => [
        'id' => 8,
        'key' => 'pomodoro',
        'name' => '포모도로',
        'description' => '포모도로 학습법 적용 시 발생하는 인지관성 패턴',
        'icon' => '🍅',
        'color' => '#ec4899',
        'version' => '2.0.0',
        'last_updated' => '2025-12-03'
    ],

    // =========================================================================
    // 시스템 설정
    // =========================================================================
    'system' => [
        'agent_id' => 4,
        'agent_name' => 'Agent04_InspectWeakpoints',
        'agent_description' => '인지관성 분석 에이전트',
        'persona_type' => 'pomodoro',
        'total_personas' => 27
    ],

    // =========================================================================
    // 엔진 설정
    // =========================================================================
    'engine' => [
        'default_tone' => 'motivational',
        'confidence_threshold' => 0.6,
        'max_active_patterns' => 5,
        'enable_audio_feedback' => true,
        'audio_base_url' => 'https://mathking.kr/Contents/personas/pomodoro/',
        'audio_format' => 'wav'
    ],

    // =========================================================================
    // 세부 항목 정의
    // =========================================================================
    'sub_items' => [
        'pomodoro_efficacy' => [
            'id' => 1,
            'name' => '포모도로 효능감 인식',
            'description' => '포모도로 기법의 가치와 효과에 대한 인식 패턴',
            'icon' => '💪'
        ],
        'scope_setting' => [
            'id' => 2,
            'name' => '작성내용의 범위 정하기',
            'description' => '포모도로 세션 범위 설정 과정의 패턴',
            'icon' => '📏'
        ],
        'input_method' => [
            'id' => 3,
            'name' => '내용입력 방법 확인',
            'description' => '학습 내용 입력 방법 관련 패턴',
            'icon' => '⌨️'
        ],
        'return_check_prep' => [
            'id' => 4,
            'name' => '귀가검사 준비',
            'description' => '학습 후 점검 준비 과정의 패턴',
            'icon' => '🏠'
        ],
        'session_reflection' => [
            'id' => 5,
            'name' => '세션별 성찰활동',
            'description' => '각 포모도로 세션 후 성찰 과정의 패턴',
            'icon' => '🪞'
        ],
        'input_reflection' => [
            'id' => 6,
            'name' => '입력과정에 대한 성찰',
            'description' => '학습 기록 과정에 대한 성찰 패턴',
            'icon' => '✍️'
        ],
        'emotion_expression' => [
            'id' => 7,
            'name' => '감정표현 활용',
            'description' => '학습 중 감정 표현 관련 패턴',
            'icon' => '😊'
        ],
        'best_practice_subscription' => [
            'id' => 8,
            'name' => '모범사례 구독',
            'description' => '모범 학습 사례 참고 및 활용 패턴',
            'icon' => '⭐'
        ],
        'memo_usage' => [
            'id' => 9,
            'name' => '메모장 활용',
            'description' => '메모 기능 활용 관련 패턴',
            'icon' => '📝'
        ]
    ],

    // =========================================================================
    // 카테고리 정의
    // =========================================================================
    'categories' => [
        'cognitive_overload' => [
            'name' => '인지 과부하',
            'description' => '포모도로 활동 중 작업기억 초과',
            'icon' => '🧠',
            'color' => '#ef4444'
        ],
        'confidence_distortion' => [
            'name' => '자신감 왜곡',
            'description' => '포모도로 효과에 대한 과신 또는 불신',
            'icon' => '😰',
            'color' => '#f59e0b'
        ],
        'habit_pattern' => [
            'name' => '습관 패턴',
            'description' => '비효율적인 포모도로 습관',
            'icon' => '🔁',
            'color' => '#8b5cf6'
        ],
        'approach_error' => [
            'name' => '접근 전략 오류',
            'description' => '포모도로 전략의 오류',
            'icon' => '🎯',
            'color' => '#06b6d4'
        ],
        'attention_deficit' => [
            'name' => '주의력 결핍',
            'description' => '포모도로 세션 중 집중력 저하',
            'icon' => '👁️',
            'color' => '#10b981'
        ],
        'time_pressure' => [
            'name' => '시간/압박 관리',
            'description' => '포모도로 시간 관리 실패',
            'icon' => '⏰',
            'color' => '#ec4899'
        ],
        'emotional_block' => [
            'name' => '감정적 장벽',
            'description' => '포모도로에 대한 심리적 저항',
            'icon' => '💭',
            'color' => '#78716c'
        ]
    ],

    // =========================================================================
    // 우선순위 레벨
    // =========================================================================
    'priority_levels' => [
        'critical' => ['name' => '긴급', 'color' => '#dc2626', 'weight' => 1.0],
        'high' => ['name' => '높음', 'color' => '#ef4444', 'weight' => 0.85],
        'medium' => ['name' => '보통', 'color' => '#f59e0b', 'weight' => 0.6],
        'low' => ['name' => '낮음', 'color' => '#10b981', 'weight' => 0.35]
    ],

    // =========================================================================
    // 페르소나 목록 (27개)
    // =========================================================================
    'personas' => [
        // ---------------------------------------------------------------------
        // 1. 포모도로 효능감 인식 (pomodoro_efficacy) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 1,
            'name' => '포모도로 회의론',
            'desc' => '25분 집중 + 5분 휴식 패턴이 자신에게 맞지 않는다고 생각하는 패턴',
            'sub_item' => 'pomodoro_efficacy',
            'category' => 'confidence_distortion',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '포모도로의 과학적 근거 설명 및 개인화된 시간 설정 안내',
                'check' => '포모도로 기법에 대한 이해가 생겼는지 확인',
                'teacher_dialog' => '포모도로 시간은 조정할 수 있어. 15분이나 20분으로 시작해도 괜찮아. 중요한 건 집중과 휴식의 리듬이야.'
            ]
        ],
        [
            'id' => 2,
            'name' => '타이머 무시',
            'desc' => '타이머가 울려도 계속하거나, 타이머를 설정하지 않는 패턴',
            'sub_item' => 'pomodoro_efficacy',
            'category' => 'habit_pattern',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '타이머 준수의 중요성 인식 및 자동 타이머 설정',
                'check' => '타이머에 맞춰 학습을 조절하는지 확인',
                'teacher_dialog' => '타이머가 울리면 반드시 멈춰야 해. 그게 포모도로의 핵심이야. 계속하고 싶어도 일단 휴식!'
            ]
        ],
        [
            'id' => 3,
            'name' => '휴식 죄책감',
            'desc' => '휴식 시간에 쉬는 것에 대해 죄책감을 느끼는 패턴',
            'sub_item' => 'pomodoro_efficacy',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '휴식이 학습 효율에 미치는 긍정적 영향 설명',
                'check' => '휴식을 편안하게 받아들이는지 확인',
                'teacher_dialog' => '휴식은 게으름이 아니야. 뇌가 정보를 정리하는 중요한 시간이야. 쉬어야 더 잘 집중할 수 있어.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 2. 작성내용의 범위 정하기 (scope_setting) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 4,
            'name' => '범위 과대 설정',
            'desc' => '한 포모도로에 너무 많은 내용을 하려고 계획하는 패턴',
            'sub_item' => 'scope_setting',
            'category' => 'approach_error',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '실현 가능한 범위로 축소하는 방법 안내',
                'check' => '한 세션에 적절한 양을 계획했는지 확인',
                'teacher_dialog' => '25분 동안 할 수 있는 양을 현실적으로 정해야 해. 조금 적다 싶을 정도가 딱 좋아.'
            ]
        ],
        [
            'id' => 5,
            'name' => '범위 미설정',
            'desc' => '구체적으로 무엇을 할지 정하지 않고 시작하는 패턴',
            'sub_item' => 'scope_setting',
            'category' => 'approach_error',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '세션 시작 전 구체적 목표 설정 루틴 형성',
                'check' => '명확한 목표를 설정했는지 확인',
                'teacher_dialog' => '"공부하기"가 아니라 "수학 25번~30번 풀기"처럼 구체적으로 정해야 집중이 잘 돼.'
            ]
        ],
        [
            'id' => 6,
            'name' => '우선순위 무시 범위 설정',
            'desc' => '중요도와 관계없이 쉬운 것만 포모도로에 포함하는 패턴',
            'sub_item' => 'scope_setting',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '중요도 기반 범위 설정 방법 안내',
                'check' => '중요한 내용이 범위에 포함되었는지 확인',
                'teacher_dialog' => '어려운 것도 포모도로에 포함해야 해. 집중 시간이니까 어려운 것을 하기 좋은 때야.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 3. 내용입력 방법 확인 (input_method) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 7,
            'name' => '입력 방법 혼란',
            'desc' => '학습 내용을 어떤 형식으로 기록해야 할지 모르는 패턴',
            'sub_item' => 'input_method',
            'category' => 'cognitive_overload',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '간단한 입력 템플릿 제공',
                'check' => '입력 방법을 이해하고 있는지 확인',
                'teacher_dialog' => '어렵게 생각하지 마. "오늘 뭘 했다", "이게 어려웠다" 정도만 적어도 충분해.'
            ]
        ],
        [
            'id' => 8,
            'name' => '과도한 기록',
            'desc' => '모든 것을 상세히 기록하려다 시간을 낭비하는 패턴',
            'sub_item' => 'input_method',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '핵심만 기록하는 방법 안내',
                'check' => '간결하게 기록하고 있는지 확인',
                'teacher_dialog' => '기록은 간단히! 핵심 키워드나 한 문장이면 충분해. 기록하느라 시간 다 쓰면 안 돼.'
            ]
        ],
        [
            'id' => 9,
            'name' => '기록 회피',
            'desc' => '학습 기록 자체를 귀찮아하여 생략하는 패턴',
            'sub_item' => 'input_method',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '기록의 가치와 최소한의 기록 방법 안내',
                'check' => '간단하게라도 기록하는지 확인',
                'teacher_dialog' => '기록하면 나중에 복습할 때 큰 도움이 돼. 이모지 하나라도 남기는 습관을 들여볼까?'
            ]
        ],

        // ---------------------------------------------------------------------
        // 4. 귀가검사 준비 (return_check_prep) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 10,
            'name' => '귀가검사 미인식',
            'desc' => '학습 후 점검의 필요성을 인식하지 못하는 패턴',
            'sub_item' => 'return_check_prep',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '귀가검사의 목적과 효과 설명',
                'check' => '귀가검사의 필요성을 이해했는지 확인',
                'teacher_dialog' => '집에 가기 전에 오늘 뭘 배웠는지 확인하면, 기억에 훨씬 오래 남아.'
            ]
        ],
        [
            'id' => 11,
            'name' => '형식적 준비',
            'desc' => '귀가검사를 대충 형식적으로만 준비하는 패턴',
            'sub_item' => 'return_check_prep',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '진정한 자기 점검의 방법 안내',
                'check' => '실질적으로 점검 준비를 하는지 확인',
                'teacher_dialog' => '대충 하면 의미가 없어. 오늘 핵심 3가지를 정말 설명할 수 있는지 스스로 확인해봐.'
            ]
        ],
        [
            'id' => 12,
            'name' => '준비 시간 부족',
            'desc' => '귀가검사 준비 시간을 충분히 확보하지 않는 패턴',
            'sub_item' => 'return_check_prep',
            'category' => 'time_pressure',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '학습 일정에 귀가검사 시간 포함하기',
                'check' => '귀가검사 시간이 계획에 포함되었는지 확인',
                'teacher_dialog' => '마지막 포모도로는 귀가검사 준비 시간으로 남겨두면 좋아.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 5. 세션별 성찰활동 (session_reflection) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 13,
            'name' => '성찰 생략',
            'desc' => '포모도로 세션이 끝나도 성찰 없이 바로 다음으로 넘어가는 패턴',
            'sub_item' => 'session_reflection',
            'category' => 'habit_pattern',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '세션 후 1분 성찰 루틴 형성',
                'check' => '각 세션 후 성찰을 하는지 확인',
                'teacher_dialog' => '세션이 끝나면 1분만 생각해봐. "집중 잘 됐나?", "뭐가 어려웠나?", "다음엔 뭘 할까?"'
            ]
        ],
        [
            'id' => 14,
            'name' => '피상적 성찰',
            'desc' => '"잘했다/못했다" 수준의 얕은 성찰만 하는 패턴',
            'sub_item' => 'session_reflection',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '구체적인 성찰 질문 가이드 제공',
                'check' => '구체적인 성찰을 하고 있는지 확인',
                'teacher_dialog' => '"잘했다"가 아니라 "어떤 부분이 왜 잘 됐는지" 생각해봐. 그래야 다음에도 잘할 수 있어.'
            ]
        ],
        [
            'id' => 15,
            'name' => '성찰 결과 미활용',
            'desc' => '성찰은 하지만 다음 세션에 반영하지 않는 패턴',
            'sub_item' => 'session_reflection',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '성찰 내용을 다음 세션 계획에 반영하는 방법 안내',
                'check' => '성찰 결과가 다음 계획에 반영되는지 확인',
                'teacher_dialog' => '성찰해서 발견한 점을 다음 포모도로에 적용해봐. 그게 진짜 성장이야.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 6. 입력과정에 대한 성찰 (input_reflection) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 16,
            'name' => '입력 과정 무성찰',
            'desc' => '학습 기록 방식에 대해 돌아보지 않는 패턴',
            'sub_item' => 'input_reflection',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '기록 방식 개선 질문 가이드 제공',
                'check' => '기록 방식을 개선하려는 노력이 있는지 확인',
                'teacher_dialog' => '내가 기록하는 방식이 효율적인지 생각해본 적 있어? 더 좋은 방법이 있을 수도 있어.'
            ]
        ],
        [
            'id' => 17,
            'name' => '입력 습관 고착',
            'desc' => '비효율적인 기록 방식을 개선하지 않고 유지하는 패턴',
            'sub_item' => 'input_reflection',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '다양한 기록 방법 시도 권장',
                'check' => '새로운 기록 방법을 시도할 의향이 있는지 확인',
                'teacher_dialog' => '항상 같은 방식으로 기록하고 있네. 마인드맵이나 그림으로도 한번 해볼까?'
            ]
        ],
        [
            'id' => 18,
            'name' => '기록 품질 무관심',
            'desc' => '기록의 정확성이나 유용성을 확인하지 않는 패턴',
            'sub_item' => 'input_reflection',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '기록 품질 점검 체크리스트 제공',
                'check' => '기록이 나중에 도움이 될 수준인지 확인',
                'teacher_dialog' => '기록한 것을 나중에 봤을 때 이해할 수 있어? 미래의 나를 위한 기록이 되도록 해보자.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 7. 감정표현 활용 (emotion_expression) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 19,
            'name' => '감정 표현 기피',
            'desc' => '학습 중 느낀 감정을 표현하거나 기록하지 않는 패턴',
            'sub_item' => 'emotion_expression',
            'category' => 'emotional_block',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '감정 표현의 학습적 효과 설명 및 간단한 표현 방법 안내',
                'check' => '감정을 표현할 의향이 있는지 확인',
                'teacher_dialog' => '오늘 공부하면서 어땠어? 이모지로 표현해봐. 😊😐😣 감정을 인식하는 것도 학습의 일부야.'
            ]
        ],
        [
            'id' => 20,
            'name' => '부정적 감정 억압',
            'desc' => '학습 중 느끼는 부정적 감정을 무시하거나 억누르는 패턴',
            'sub_item' => 'emotion_expression',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '부정적 감정 인식 및 건강한 처리 방법 안내',
                'check' => '부정적 감정을 인정하고 표현할 수 있는지 확인',
                'teacher_dialog' => '어려워서 짜증나거나 지루한 건 당연해. 그 감정을 인정하고, 왜 그런지 생각해보는 게 도움이 돼.'
            ]
        ],
        [
            'id' => 21,
            'name' => '감정-학습 연결 실패',
            'desc' => '감정 상태가 학습에 미치는 영향을 인식하지 못하는 패턴',
            'sub_item' => 'emotion_expression',
            'category' => 'approach_error',
            'priority' => 'low',
            'audio_time' => 25,
            'solution' => [
                'action' => '감정과 학습 효율의 관계 설명',
                'check' => '감정 상태를 고려하여 학습을 조절하는지 확인',
                'teacher_dialog' => '기분이 안 좋을 때와 좋을 때 공부 효율이 다르잖아. 감정을 관리하면 학습도 잘 돼.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 8. 모범사례 구독 (best_practice_subscription) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 22,
            'name' => '모범사례 무관심',
            'desc' => '다른 학습자의 좋은 방법을 참고하지 않는 패턴',
            'sub_item' => 'best_practice_subscription',
            'category' => 'approach_error',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '모범사례 참고의 효과 설명 및 구독 방법 안내',
                'check' => '모범사례를 참고할 의향이 있는지 확인',
                'teacher_dialog' => '공부 잘하는 친구들은 어떻게 하는지 궁금하지 않아? 좋은 방법을 배워서 적용해볼 수 있어.'
            ]
        ],
        [
            'id' => 23,
            'name' => '맹목적 모방',
            'desc' => '모범사례를 자신에게 맞게 조정하지 않고 그대로 따라하는 패턴',
            'sub_item' => 'best_practice_subscription',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '모범사례를 자신에게 맞게 적용하는 방법 안내',
                'check' => '모범사례를 자신의 상황에 맞게 조정하는지 확인',
                'teacher_dialog' => '좋은 방법이라도 그대로 따라하면 안 맞을 수 있어. 네 상황에 맞게 조금씩 바꿔서 해봐.'
            ]
        ],
        [
            'id' => 24,
            'name' => '비교 스트레스',
            'desc' => '모범사례와 자신을 비교하며 위축되는 패턴',
            'sub_item' => 'best_practice_subscription',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '모범사례는 참고용이며 자신만의 페이스가 있음을 인식시킴',
                'check' => '건강하게 모범사례를 활용하고 있는지 확인',
                'teacher_dialog' => '모범사례는 영감을 얻는 거지, 비교해서 좌절하라는 게 아니야. 네 속도로 성장하면 돼.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 9. 메모장 활용 (memo_usage) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 25,
            'name' => '메모 미활용',
            'desc' => '메모 기능이 있어도 사용하지 않는 패턴',
            'sub_item' => 'memo_usage',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '메모의 효과와 간단한 활용법 안내',
                'check' => '메모를 활용하기 시작했는지 확인',
                'teacher_dialog' => '공부하다가 떠오르는 생각이나 아이디어를 메모해두면 나중에 큰 도움이 돼.'
            ]
        ],
        [
            'id' => 26,
            'name' => '메모 과잉',
            'desc' => '모든 것을 메모하려다 학습에 집중하지 못하는 패턴',
            'sub_item' => 'memo_usage',
            'category' => 'attention_deficit',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '핵심만 메모하는 방법 안내',
                'check' => '메모량이 적절한지 확인',
                'teacher_dialog' => '메모는 키워드만! 나중에 기억을 되살릴 수 있는 힌트만 적으면 충분해.'
            ]
        ],
        [
            'id' => 27,
            'name' => '메모 미정리',
            'desc' => '메모를 한 후 정리하거나 활용하지 않는 패턴',
            'sub_item' => 'memo_usage',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '메모 정리 및 활용 루틴 형성',
                'check' => '메모를 정리하고 활용하는지 확인',
                'teacher_dialog' => '메모는 정리해야 가치가 있어. 일주일에 한 번은 메모를 보고 정리하는 시간을 가져봐.'
            ]
        ]
    ]
];

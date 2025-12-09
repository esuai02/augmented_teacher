<?php
/**
 * Agent04 Persona System - Review Activity (복습활동)
 *
 * 복습 활동 시 발생하는 인지관성 패턴 정의
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04_InspectWeakpoints
 * @version     2.0.0
 * @author      Augmented Teacher Team
 * @created     2025-12-03
 *
 * 파일 위치: /alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/7_review_activity.php
 */

return [
    // =========================================================================
    // 상황 메타데이터
    // =========================================================================
    'situation' => [
        'id' => 7,
        'key' => 'review_activity',
        'name' => '복습활동',
        'description' => '복습 활동 시 발생하는 인지관성 패턴',
        'icon' => '🔄',
        'color' => '#06b6d4',
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
        'persona_type' => 'review_activity',
        'total_personas' => 28
    ],

    // =========================================================================
    // 엔진 설정
    // =========================================================================
    'engine' => [
        'default_tone' => 'supportive',
        'confidence_threshold' => 0.6,
        'max_active_patterns' => 5,
        'enable_audio_feedback' => true,
        'audio_base_url' => 'https://mathking.kr/Contents/personas/review_activity/',
        'audio_format' => 'wav'
    ],

    // =========================================================================
    // 세부 항목 정의
    // =========================================================================
    'sub_items' => [
        'review_efficacy' => [
            'id' => 1,
            'name' => '복습루틴 효능감 인식',
            'description' => '복습의 가치와 효과에 대한 인식 패턴',
            'icon' => '💪'
        ],
        'review_time_setting' => [
            'id' => 2,
            'name' => '복습시간 정하기',
            'description' => '복습 시간 설정 과정의 패턴',
            'icon' => '⏰'
        ],
        'need_analysis' => [
            'id' => 3,
            'name' => '필요영역 분석',
            'description' => '복습이 필요한 영역 분석 과정의 패턴',
            'icon' => '🔍'
        ],
        'review_curriculum' => [
            'id' => 4,
            'name' => '복습 커리큘럼 정하기',
            'description' => '복습 순서와 계획을 정하는 과정의 패턴',
            'icon' => '📋'
        ],
        'review_execution' => [
            'id' => 5,
            'name' => '복습실행',
            'description' => '실제 복습을 실행하는 과정의 패턴',
            'icon' => '📖'
        ],
        'review_closing' => [
            'id' => 6,
            'name' => '복습 마무리',
            'description' => '복습 마무리 과정의 패턴',
            'icon' => '🏁'
        ],
        'closing_feedback' => [
            'id' => 7,
            'name' => '마무리 피드백',
            'description' => '복습 후 피드백 과정의 패턴',
            'icon' => '💬'
        ]
    ],

    // =========================================================================
    // 카테고리 정의
    // =========================================================================
    'categories' => [
        'cognitive_overload' => [
            'name' => '인지 과부하',
            'description' => '복습 과정에서 작업기억 초과',
            'icon' => '🧠',
            'color' => '#ef4444'
        ],
        'confidence_distortion' => [
            'name' => '자신감 왜곡',
            'description' => '복습에 대한 과신 또는 위축',
            'icon' => '😰',
            'color' => '#f59e0b'
        ],
        'habit_pattern' => [
            'name' => '습관 패턴',
            'description' => '비효율적인 복습 습관',
            'icon' => '🔁',
            'color' => '#8b5cf6'
        ],
        'approach_error' => [
            'name' => '접근 전략 오류',
            'description' => '복습 전략의 오류',
            'icon' => '🎯',
            'color' => '#06b6d4'
        ],
        'attention_deficit' => [
            'name' => '주의력 결핍',
            'description' => '복습 중 집중력 저하',
            'icon' => '👁️',
            'color' => '#10b981'
        ],
        'time_pressure' => [
            'name' => '시간/압박 관리',
            'description' => '복습 시간 관리 실패',
            'icon' => '⏰',
            'color' => '#ec4899'
        ],
        'emotional_block' => [
            'name' => '감정적 장벽',
            'description' => '복습에 대한 심리적 저항',
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
    // 페르소나 목록 (28개)
    // =========================================================================
    'personas' => [
        // ---------------------------------------------------------------------
        // 1. 복습루틴 효능감 인식 (review_efficacy) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 1,
            'name' => '복습 무용론',
            'desc' => '복습해도 의미없다고 생각하여 복습을 회피하는 패턴',
            'sub_item' => 'review_efficacy',
            'category' => 'confidence_distortion',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '복습의 실제 효과를 데이터로 보여주고 동기 부여',
                'check' => '복습의 가치를 인식하고 시도 의향이 있는지 확인',
                'teacher_dialog' => '복습하면 정말 도움이 안 될까? 연구에 따르면 적절한 간격으로 복습하면 기억률이 2배 이상 높아져.'
            ]
        ],
        [
            'id' => 2,
            'name' => '복습 기피',
            'desc' => '새로운 내용 학습만 선호하고 복습을 지루하게 여기는 패턴',
            'sub_item' => 'review_efficacy',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '복습의 재미와 효과를 경험할 수 있는 방법 제시',
                'check' => '복습에 대한 부정적 감정이 줄었는지 확인',
                'teacher_dialog' => '복습이 지루하게 느껴질 수 있어. 하지만 복습은 새로운 연결고리를 찾는 탐험이 될 수 있어.'
            ]
        ],
        [
            'id' => 3,
            'name' => '과신 복습 생략',
            'desc' => '다 안다고 생각하여 복습을 생략하는 패턴',
            'sub_item' => 'review_efficacy',
            'category' => 'confidence_distortion',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '자가 테스트를 통해 실제 이해도 확인',
                'check' => '복습 없이 정확히 기억하는지 테스트',
                'teacher_dialog' => '다 안다고 생각하니? 그럼 한번 테스트해볼까? 복습하지 않으면 시간이 지나면서 많이 잊어버려.'
            ]
        ],
        [
            'id' => 4,
            'name' => '복습 루틴 부재',
            'desc' => '정기적인 복습 습관이 형성되지 않은 패턴',
            'sub_item' => 'review_efficacy',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '복습 루틴 수립 및 알림 설정 안내',
                'check' => '정기 복습 계획이 있는지 확인',
                'teacher_dialog' => '복습은 습관이 되어야 효과가 있어. 매일 10분씩 복습 시간을 정해볼까?'
            ]
        ],

        // ---------------------------------------------------------------------
        // 2. 복습시간 정하기 (review_time_setting) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 5,
            'name' => '복습 시간 미설정',
            'desc' => '언제 복습할지 구체적인 시간을 정하지 않는 패턴',
            'sub_item' => 'review_time_setting',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '일일 스케줄에서 복습 시간 확보 방법 안내',
                'check' => '구체적인 복습 시간이 설정되었는지 확인',
                'teacher_dialog' => '언제 복습할지 정해두면 실천 확률이 높아져. 오늘 몇 시에 복습하면 좋을까?'
            ]
        ],
        [
            'id' => 6,
            'name' => '비현실적 복습 계획',
            'desc' => '실행 불가능할 정도로 많은 복습량을 계획하는 패턴',
            'sub_item' => 'review_time_setting',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '실현 가능한 복습량으로 조정',
                'check' => '계획한 복습량이 현실적인지 확인',
                'teacher_dialog' => '복습 계획이 너무 많으면 오히려 실천하기 어려워. 핵심 내용 위주로 줄여볼까?'
            ]
        ],
        [
            'id' => 7,
            'name' => '피곤한 시간 배정',
            'desc' => '집중력이 낮은 시간대에 복습을 배정하는 패턴',
            'sub_item' => 'review_time_setting',
            'category' => 'time_pressure',
            'priority' => 'low',
            'audio_time' => 25,
            'solution' => [
                'action' => '개인의 집중력 높은 시간대 파악 및 복습 시간 조정',
                'check' => '복습 시간이 집중하기 좋은 시간대인지 확인',
                'teacher_dialog' => '너무 피곤한 시간에 복습하면 효과가 떨어져. 가장 맑은 정신일 때가 언제야?'
            ]
        ],
        [
            'id' => 8,
            'name' => '복습 간격 무시',
            'desc' => '에빙하우스 망각곡선을 고려하지 않는 복습 일정 패턴',
            'sub_item' => 'review_time_setting',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 30,
            'solution' => [
                'action' => '효율적인 복습 간격(1일-3일-7일-30일) 안내',
                'check' => '적절한 복습 간격이 설정되었는지 확인',
                'teacher_dialog' => '배운 후 1일, 3일, 7일, 30일에 복습하면 장기 기억에 효과적이야.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 3. 필요영역 분석 (need_analysis) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 9,
            'name' => '전체 복습 고집',
            'desc' => '모든 내용을 똑같이 복습하려 하여 효율이 떨어지는 패턴',
            'sub_item' => 'need_analysis',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '약점 중심 복습 전략 안내',
                'check' => '취약한 부분을 우선 복습하는지 확인',
                'teacher_dialog' => '모든 걸 똑같이 복습하면 시간이 부족해. 잘 모르는 부분 위주로 복습하는 게 효율적이야.'
            ]
        ],
        [
            'id' => 10,
            'name' => '약점 분석 실패',
            'desc' => '어떤 부분이 약한지 파악하지 못하는 패턴',
            'sub_item' => 'need_analysis',
            'category' => 'cognitive_overload',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '자가 진단 테스트를 통한 약점 파악 방법 안내',
                'check' => '자신의 약점을 인식하고 있는지 확인',
                'teacher_dialog' => '어디가 약한지 모르겠다면, 간단한 테스트를 통해 확인해볼까?'
            ]
        ],
        [
            'id' => 11,
            'name' => '약점 회피',
            'desc' => '약한 부분은 피하고 자신있는 부분만 복습하는 패턴',
            'sub_item' => 'need_analysis',
            'category' => 'emotional_block',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '약점 극복이 성장의 핵심임을 인식시키고 단계적 접근',
                'check' => '약한 부분을 복습할 의향이 있는지 확인',
                'teacher_dialog' => '어려운 부분을 피하면 계속 약점으로 남아. 조금씩 도전해볼까?'
            ]
        ],
        [
            'id' => 12,
            'name' => '우선순위 혼란',
            'desc' => '어떤 내용부터 복습해야 할지 결정하지 못하는 패턴',
            'sub_item' => 'need_analysis',
            'category' => 'cognitive_overload',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '시험/활용 빈도/연결성 기준 우선순위 결정 방법 안내',
                'check' => '복습 우선순위가 설정되었는지 확인',
                'teacher_dialog' => '뭐부터 할지 모르겠으면, 시험에 나올 가능성이 높은 것, 기초가 되는 것부터 정해볼까?'
            ]
        ],

        // ---------------------------------------------------------------------
        // 4. 복습 커리큘럼 정하기 (review_curriculum) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 13,
            'name' => '무계획 복습',
            'desc' => '복습 순서와 계획 없이 무작위로 복습하는 패턴',
            'sub_item' => 'review_curriculum',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '체계적인 복습 계획 수립 방법 안내',
                'check' => '복습 계획이 체계적으로 수립되었는지 확인',
                'teacher_dialog' => '이것저것 뒤죽박죽 복습하면 효과가 떨어져. 순서를 정해서 복습해볼까?'
            ]
        ],
        [
            'id' => 14,
            'name' => '과목 편중',
            'desc' => '특정 과목/단원만 반복 복습하고 다른 것은 무시하는 패턴',
            'sub_item' => 'review_curriculum',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '균형 잡힌 복습 계획 수립',
                'check' => '모든 필요 영역이 포함되었는지 확인',
                'teacher_dialog' => '좋아하는 것만 복습하면 다른 부분이 약해져. 골고루 복습 계획을 세워볼까?'
            ]
        ],
        [
            'id' => 15,
            'name' => '복습 방법 단조',
            'desc' => '항상 같은 방법으로만 복습하여 효과가 떨어지는 패턴',
            'sub_item' => 'review_curriculum',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 25,
            'solution' => [
                'action' => '다양한 복습 방법(문제풀이, 요약, 설명하기 등) 안내',
                'check' => '다양한 복습 방법을 계획에 포함했는지 확인',
                'teacher_dialog' => '항상 같은 방법으로 복습하면 지루해져. 문제 풀기, 마인드맵 그리기, 누군가에게 설명하기 등 다양하게 해봐.'
            ]
        ],
        [
            'id' => 16,
            'name' => '연결성 무시',
            'desc' => '관련된 내용을 연결하지 않고 개별적으로 복습하는 패턴',
            'sub_item' => 'review_curriculum',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '관련 개념 연결 복습 방법 안내',
                'check' => '연결된 개념들을 함께 복습하는지 확인',
                'teacher_dialog' => '비슷한 개념들을 연결해서 복습하면 이해가 더 깊어져. 관련된 것끼리 묶어볼까?'
            ]
        ],

        // ---------------------------------------------------------------------
        // 5. 복습실행 (review_execution) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 17,
            'name' => '수동적 복습',
            'desc' => '단순히 읽기만 하는 수동적 복습 패턴',
            'sub_item' => 'review_execution',
            'category' => 'approach_error',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '능동적 복습 방법(인출 연습, 자기 설명) 안내',
                'check' => '능동적으로 복습하고 있는지 확인',
                'teacher_dialog' => '그냥 읽기만 하면 금방 잊어버려. 책을 덮고 떠올려보거나, 문제를 풀어보는 게 훨씬 효과적이야.'
            ]
        ],
        [
            'id' => 18,
            'name' => '복습 중 산만',
            'desc' => '복습 중에 다른 것에 신경 쓰여 집중하지 못하는 패턴',
            'sub_item' => 'review_execution',
            'category' => 'attention_deficit',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '집중 환경 조성 및 방해 요소 제거 방법 안내',
                'check' => '복습에 집중하고 있는지 확인',
                'teacher_dialog' => '복습할 때 핸드폰이나 다른 것들은 잠시 멀리 두고, 집중해서 해보자.'
            ]
        ],
        [
            'id' => 19,
            'name' => '복습 깊이 부족',
            'desc' => '피상적으로만 훑고 지나가는 복습 패턴',
            'sub_item' => 'review_execution',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '깊이 있는 복습을 위한 질문 던지기 방법 안내',
                'check' => '복습 내용을 깊이 이해했는지 확인',
                'teacher_dialog' => '"왜 이렇게 되지?", "다른 경우에는 어떨까?" 같은 질문을 스스로 던지면서 복습해봐.'
            ]
        ],
        [
            'id' => 20,
            'name' => '복습 중단',
            'desc' => '복습을 시작했다가 중간에 포기하는 패턴',
            'sub_item' => 'review_execution',
            'category' => 'emotional_block',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '작은 단위로 나누어 달성감 느끼게 하기',
                'check' => '복습을 끝까지 완료했는지 확인',
                'teacher_dialog' => '한 번에 너무 많이 하려면 힘들어. 10분만 먼저 해보고, 할 수 있으면 더 해보자.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 6. 복습 마무리 (review_closing) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 21,
            'name' => '마무리 점검 생략',
            'desc' => '복습 후 이해도를 확인하지 않고 끝내는 패턴',
            'sub_item' => 'review_closing',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '복습 후 자가 테스트 루틴 형성',
                'check' => '복습 내용을 확실히 이해했는지 테스트',
                'teacher_dialog' => '복습이 끝나면 스스로 테스트해봐. 진짜 알았는지 확인하는 게 중요해.'
            ]
        ],
        [
            'id' => 22,
            'name' => '복습 기록 미작성',
            'desc' => '복습한 내용과 결과를 기록하지 않는 패턴',
            'sub_item' => 'review_closing',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '복습 일지 작성 습관 형성',
                'check' => '복습 내용을 기록했는지 확인',
                'teacher_dialog' => '오늘 뭘 복습했고 어떤 부분이 좋았는지 간단히 적어두면, 다음에 더 효율적으로 복습할 수 있어.'
            ]
        ],
        [
            'id' => 23,
            'name' => '다음 복습 미계획',
            'desc' => '다음 복습 일정을 정하지 않고 끝내는 패턴',
            'sub_item' => 'review_closing',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 20,
            'solution' => [
                'action' => '복습 종료 시 다음 복습 예약 루틴 형성',
                'check' => '다음 복습 일정이 정해졌는지 확인',
                'teacher_dialog' => '복습을 끝내기 전에 다음에 언제 다시 볼지 정해두자. 그래야 잊어버리지 않아.'
            ]
        ],
        [
            'id' => 24,
            'name' => '성과 인정 실패',
            'desc' => '복습을 통한 성장을 인식하지 못하는 패턴',
            'sub_item' => 'review_closing',
            'category' => 'emotional_block',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '복습 전후 비교를 통한 성장 인식',
                'check' => '복습으로 얼마나 성장했는지 인식하는지 확인',
                'teacher_dialog' => '복습 전에는 몰랐던 걸 이제는 알게 됐잖아. 조금씩 성장하고 있어!'
            ]
        ],

        // ---------------------------------------------------------------------
        // 7. 마무리 피드백 (closing_feedback) - 4개
        // ---------------------------------------------------------------------
        [
            'id' => 25,
            'name' => '피드백 수용 거부',
            'desc' => '복습 결과에 대한 피드백을 받아들이지 않는 패턴',
            'sub_item' => 'closing_feedback',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '피드백의 가치와 활용 방법 안내',
                'check' => '피드백을 긍정적으로 받아들이는지 확인',
                'teacher_dialog' => '피드백은 더 잘하기 위한 거야. 부족한 점을 알면 다음에 보완할 수 있잖아.'
            ]
        ],
        [
            'id' => 26,
            'name' => '피드백 미반영',
            'desc' => '피드백을 받았지만 다음 복습에 반영하지 않는 패턴',
            'sub_item' => 'closing_feedback',
            'category' => 'habit_pattern',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '피드백 기록 및 다음 복습 계획에 반영하는 방법 안내',
                'check' => '피드백이 다음 계획에 반영되었는지 확인',
                'teacher_dialog' => '받은 피드백을 적어두고, 다음 복습할 때 그 부분을 보완해보자.'
            ]
        ],
        [
            'id' => 27,
            'name' => '자기 평가 왜곡',
            'desc' => '실제보다 높거나 낮게 복습 효과를 평가하는 패턴',
            'sub_item' => 'closing_feedback',
            'category' => 'confidence_distortion',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '객관적 평가 기준 제공 (테스트, 문제풀이 정답률)',
                'check' => '자기 평가가 객관적인지 확인',
                'teacher_dialog' => '느낌보다는 실제 테스트 결과로 복습 효과를 확인하는 게 정확해.'
            ]
        ],
        [
            'id' => 28,
            'name' => '개선 방향 미설정',
            'desc' => '복습 후 무엇을 개선할지 방향을 정하지 않는 패턴',
            'sub_item' => 'closing_feedback',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '복습 피드백을 바탕으로 구체적 개선점 도출',
                'check' => '다음에 개선할 점이 명확한지 확인',
                'teacher_dialog' => '오늘 복습에서 뭘 더 잘할 수 있었을까? 다음에는 그 부분을 신경 써보자.'
            ]
        ]
    ]
];

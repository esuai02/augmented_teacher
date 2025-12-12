<?php
/**
 * Agent04 Persona System - QA Session (질의응답)
 *
 * 질의응답 과정에서 발생하는 인지관성 패턴 정의
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04_InspectWeakpoints
 * @version     2.0.0
 * @author      Augmented Teacher Team
 * @created     2025-12-03
 *
 * 파일 위치: /alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/6_qa_session.php
 */

return [
    // =========================================================================
    // 상황 메타데이터
    // =========================================================================
    'situation' => [
        'id' => 6,
        'key' => 'qa_session',
        'name' => '질의응답',
        'description' => '질문 및 답변 과정에서 발생하는 인지관성 패턴',
        'icon' => '💬',
        'color' => '#10b981',
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
        'persona_type' => 'qa_session',
        'total_personas' => 30
    ],

    // =========================================================================
    // 엔진 설정
    // =========================================================================
    'engine' => [
        'default_tone' => 'supportive',
        'confidence_threshold' => 0.6,
        'max_active_patterns' => 5,
        'enable_audio_feedback' => true,
        'audio_base_url' => 'https://mathking.kr/Contents/personas/qa_session/',
        'audio_format' => 'wav'
    ],

    // =========================================================================
    // 세부 항목 정의
    // =========================================================================
    'sub_items' => [
        'qa_efficacy' => [
            'id' => 1,
            'name' => '질의응답 효능감 인식',
            'description' => '질의응답의 가치와 효과에 대한 인식 패턴',
            'icon' => '💪'
        ],
        'doubt_occurrence' => [
            'id' => 2,
            'name' => '의문발생',
            'description' => '학습 중 의문이 발생하는 과정의 패턴',
            'icon' => '❓'
        ],
        'question_generation' => [
            'id' => 3,
            'name' => '질문생성',
            'description' => '의문을 구체적인 질문으로 만드는 과정의 패턴',
            'icon' => '✍️'
        ],
        'focused_resolution' => [
            'id' => 4,
            'name' => '집중해결',
            'description' => '질문에 집중하여 해결하는 과정의 패턴',
            'icon' => '🎯'
        ],
        'question_decision' => [
            'id' => 5,
            'name' => '질문결정',
            'description' => '어떤 질문을 할지 결정하는 과정의 패턴',
            'icon' => '🤔'
        ],
        'self_directed_qa' => [
            'id' => 6,
            'name' => '질의응답 스스로 주도하기',
            'description' => '자기주도적 질의응답 과정의 패턴',
            'icon' => '🙋'
        ],
        'intervention_method' => [
            'id' => 7,
            'name' => '질문 듣는 중 개입방법',
            'description' => '설명을 듣는 중 개입하는 방법의 패턴',
            'icon' => '✋'
        ],
        'closing' => [
            'id' => 8,
            'name' => '마무리',
            'description' => '질의응답 마무리 과정의 패턴',
            'icon' => '🏁'
        ],
        'tracking_followup' => [
            'id' => 9,
            'name' => '추적 및 후속 학습',
            'description' => '질의응답 후 추적 및 후속 학습 패턴',
            'icon' => '📊'
        ],
        'close_session' => [
            'id' => 10,
            'name' => '닫기',
            'description' => '질의응답 세션 종료 과정의 패턴',
            'icon' => '👋'
        ]
    ],

    // =========================================================================
    // 카테고리 정의
    // =========================================================================
    'categories' => [
        'cognitive_overload' => [
            'name' => '인지 과부하',
            'description' => '질문 과정에서 작업기억 초과',
            'icon' => '🧠',
            'color' => '#ef4444'
        ],
        'confidence_distortion' => [
            'name' => '자신감 왜곡',
            'description' => '질문에 대한 과신 또는 위축',
            'icon' => '😰',
            'color' => '#f59e0b'
        ],
        'habit_pattern' => [
            'name' => '습관 패턴',
            'description' => '비효율적인 질문 습관',
            'icon' => '🔁',
            'color' => '#8b5cf6'
        ],
        'approach_error' => [
            'name' => '접근 전략 오류',
            'description' => '질문 전략의 오류',
            'icon' => '🎯',
            'color' => '#06b6d4'
        ],
        'attention_deficit' => [
            'name' => '주의력 결핍',
            'description' => '질의응답 중 집중력 저하',
            'icon' => '👁️',
            'color' => '#10b981'
        ],
        'emotional_block' => [
            'name' => '감정적 장벽',
            'description' => '질문에 대한 심리적 장벽',
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
    // 페르소나 목록 (30개)
    // =========================================================================
    'personas' => [
        // ---------------------------------------------------------------------
        // 1. 질의응답 효능감 인식 (qa_efficacy) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 1,
            'name' => '질문 무용론',
            'desc' => '질문해도 도움이 안 된다고 생각하여 질문을 포기하는 패턴',
            'sub_item' => 'qa_efficacy',
            'category' => 'confidence_distortion',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '질문으로 해결된 과거 경험을 상기시키고, 작은 질문부터 시도',
                'check' => '질문의 가치를 인식하고 시도 의향이 있는지 확인',
                'teacher_dialog' => '질문하면 정말 이해가 안 될까? 지난번에 질문했을 때 어떤 점이 도움이 됐는지 기억해볼까?'
            ]
        ],
        [
            'id' => 2,
            'name' => '질문 회피',
            'desc' => '질문하면 바보처럼 보일까봐 질문을 회피하는 패턴',
            'sub_item' => 'qa_efficacy',
            'category' => 'emotional_block',
            'priority' => 'high',
            'audio_time' => 30,
            'solution' => [
                'action' => '질문은 학습의 중요한 도구임을 인식시키고 안전한 환경 조성',
                'check' => '질문에 대한 심리적 부담이 줄었는지 확인',
                'teacher_dialog' => '질문하는 건 모르는 게 아니라 더 알고 싶다는 거야. 선생님은 질문하는 친구가 정말 멋지다고 생각해.'
            ]
        ],
        [
            'id' => 3,
            'name' => '자기 해결 강박',
            'desc' => '모든 것을 혼자 해결해야 한다고 생각하여 질문을 기피하는 패턴',
            'sub_item' => 'qa_efficacy',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '적절한 질문이 효율적인 학습 방법임을 설명',
                'check' => '필요시 질문할 의향이 있는지 확인',
                'teacher_dialog' => '혼자 해결하려는 노력은 좋지만, 때로는 질문하는 게 더 빠르고 효과적일 수 있어.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 2. 의문발생 (doubt_occurrence) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 4,
            'name' => '의문 인식 실패',
            'desc' => '이해가 안 되는 부분이 있어도 그것을 의문으로 인식하지 못하는 패턴',
            'sub_item' => 'doubt_occurrence',
            'category' => 'attention_deficit',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '학습 중 모르는 부분을 체크하는 습관 형성 훈련',
                'check' => '이해 안 되는 부분을 스스로 인식할 수 있는지 확인',
                'teacher_dialog' => '방금 배운 내용 중에서 조금이라도 애매한 부분이 있었니? 100% 확실하지 않은 부분을 찾아볼까?'
            ]
        ],
        [
            'id' => 5,
            'name' => '의문 무시',
            'desc' => '의문이 생겼지만 중요하지 않다고 판단하여 무시하는 패턴',
            'sub_item' => 'doubt_occurrence',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '작은 의문도 중요할 수 있음을 인식시키고 기록 습관 형성',
                'check' => '의문을 기록하고 해결하려는 의지가 있는지 확인',
                'teacher_dialog' => '작은 의문 하나가 큰 이해로 이어질 수 있어. 그 의문을 한번 메모해볼까?'
            ]
        ],
        [
            'id' => 6,
            'name' => '의문 과잉',
            'desc' => '모든 것에 의문을 품어 학습 진행이 어려운 패턴',
            'sub_item' => 'doubt_occurrence',
            'category' => 'cognitive_overload',
            'priority' => 'medium',
            'audio_time' => 30,
            'solution' => [
                'action' => '핵심 의문과 부차적 의문을 구분하는 방법 안내',
                'check' => '우선순위를 정해서 의문을 해결할 수 있는지 확인',
                'teacher_dialog' => '의문이 많다는 건 생각을 많이 한다는 거야. 그 중에서 가장 중요한 것부터 하나씩 해결해볼까?'
            ]
        ],

        // ---------------------------------------------------------------------
        // 3. 질문생성 (question_generation) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 7,
            'name' => '질문 언어화 실패',
            'desc' => '의문은 있지만 질문으로 표현하지 못하는 패턴',
            'sub_item' => 'question_generation',
            'category' => 'cognitive_overload',
            'priority' => 'high',
            'audio_time' => 30,
            'solution' => [
                'action' => '질문 템플릿 제공 (예: "~가 왜 ~인지 모르겠어요")',
                'check' => '질문 형태로 의문을 표현할 수 있는지 확인',
                'teacher_dialog' => '어떤 부분이 궁금한지 정확히 말로 표현하기 어려울 수 있어. "이 부분이 왜 이렇게 되는지 모르겠어요"처럼 말해볼까?'
            ]
        ],
        [
            'id' => 8,
            'name' => '모호한 질문',
            'desc' => '질문이 너무 광범위하거나 모호하여 답변받기 어려운 패턴',
            'sub_item' => 'question_generation',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '구체적인 질문 만드는 방법 안내 (5W1H 활용)',
                'check' => '질문이 구체적이고 답변 가능한 형태인지 확인',
                'teacher_dialog' => '"이거 모르겠어요"보다 "이 공식이 왜 이렇게 적용되는지 모르겠어요"가 더 도움을 받기 쉬워.'
            ]
        ],
        [
            'id' => 9,
            'name' => '핵심 질문 회피',
            'desc' => '진짜 모르는 것 대신 덜 중요한 질문만 하는 패턴',
            'sub_item' => 'question_generation',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '안전한 환경에서 핵심 질문을 하도록 격려',
                'check' => '진짜 궁금한 것을 질문할 용기가 있는지 확인',
                'teacher_dialog' => '혹시 정말 궁금한 건 따로 있는데 물어보기 어려웠니? 어떤 질문이든 괜찮아.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 4. 집중해결 (focused_resolution) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 10,
            'name' => '답변 미집중',
            'desc' => '답변을 들으면서 다른 생각을 하거나 집중하지 못하는 패턴',
            'sub_item' => 'focused_resolution',
            'category' => 'attention_deficit',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '답변 내용을 메모하거나 요약하면서 듣는 습관 형성',
                'check' => '답변 내용을 자신의 말로 요약할 수 있는지 확인',
                'teacher_dialog' => '선생님 설명을 들으면서 핵심 내용을 메모해볼까? 그러면 더 집중하게 될 거야.'
            ]
        ],
        [
            'id' => 11,
            'name' => '이해 확인 생략',
            'desc' => '답변을 받고 이해했는지 확인하지 않고 넘어가는 패턴',
            'sub_item' => 'focused_resolution',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '답변 후 자신의 말로 다시 설명해보는 습관 형성',
                'check' => '답변 내용을 제대로 이해했는지 확인',
                'teacher_dialog' => '방금 들은 설명을 네 말로 다시 한번 정리해볼 수 있어?'
            ]
        ],
        [
            'id' => 12,
            'name' => '부분 이해 만족',
            'desc' => '일부만 이해하고도 완전히 이해했다고 생각하는 패턴',
            'sub_item' => 'focused_resolution',
            'category' => 'confidence_distortion',
            'priority' => 'high',
            'audio_time' => 30,
            'solution' => [
                'action' => '이해도 점검 질문을 통해 빈 부분 확인',
                'check' => '모든 부분을 이해했는지 꼼꼼히 확인',
                'teacher_dialog' => '지금 설명한 것 중에서 100% 확실하게 이해한 부분과 좀 애매한 부분을 나눠볼까?'
            ]
        ],

        // ---------------------------------------------------------------------
        // 5. 질문결정 (question_decision) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 13,
            'name' => '질문 선택 마비',
            'desc' => '여러 의문 중 어떤 것을 질문할지 결정하지 못하는 패턴',
            'sub_item' => 'question_decision',
            'category' => 'cognitive_overload',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '의문을 나열하고 우선순위를 정하는 방법 안내',
                'check' => '질문 우선순위를 정할 수 있는지 확인',
                'teacher_dialog' => '궁금한 게 여러 개 있구나. 그 중에서 지금 가장 급한 것, 또는 가장 기본이 되는 것부터 정해볼까?'
            ]
        ],
        [
            'id' => 14,
            'name' => '타이밍 결정 실패',
            'desc' => '언제 질문해야 할지 적절한 타이밍을 잡지 못하는 패턴',
            'sub_item' => 'question_decision',
            'category' => 'approach_error',
            'priority' => 'low',
            'audio_time' => 25,
            'solution' => [
                'action' => '질문하기 좋은 타이밍 가이드 제공',
                'check' => '적절한 질문 타이밍을 인식하는지 확인',
                'teacher_dialog' => '질문은 설명이 끝난 직후나, 이해가 안 될 때 바로 하는 게 좋아. 메모해뒀다가 나중에 해도 괜찮고.'
            ]
        ],
        [
            'id' => 15,
            'name' => '완벽한 질문 강박',
            'desc' => '완벽한 질문을 만들려다가 결국 질문하지 못하는 패턴',
            'sub_item' => 'question_decision',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '불완전한 질문도 괜찮다는 것을 인식시킴',
                'check' => '부담 없이 질문할 수 있는지 확인',
                'teacher_dialog' => '완벽한 질문이 아니어도 괜찮아. 대충 어떤 부분이 궁금한지만 말해줘도 선생님이 이해할 수 있어.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 6. 질의응답 스스로 주도하기 (self_directed_qa) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 16,
            'name' => '수동적 질의응답',
            'desc' => '선생님이 물어봐야만 질문하고, 스스로 질문을 시작하지 않는 패턴',
            'sub_item' => 'self_directed_qa',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '자발적 질문의 중요성 인식 및 질문 루틴 형성',
                'check' => '스스로 질문을 시작하려는 의지가 있는지 확인',
                'teacher_dialog' => '공부하다가 궁금한 게 생기면 먼저 질문해봐. 네가 먼저 물어보면 더 많이 배울 수 있어.'
            ]
        ],
        [
            'id' => 17,
            'name' => '깊이 있는 질문 회피',
            'desc' => '표면적인 질문만 하고 깊이 있는 질문을 피하는 패턴',
            'sub_item' => 'self_directed_qa',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 30,
            'solution' => [
                'action' => '"왜"와 "어떻게" 질문을 하도록 유도',
                'check' => '깊이 있는 질문을 시도하는지 확인',
                'teacher_dialog' => '"왜 이렇게 되는 걸까?" "다른 방법은 없을까?" 같은 질문도 해보면 더 깊이 이해할 수 있어.'
            ]
        ],
        [
            'id' => 18,
            'name' => '질문 연결 실패',
            'desc' => '답변을 받은 후 후속 질문으로 이어가지 못하는 패턴',
            'sub_item' => 'self_directed_qa',
            'category' => 'approach_error',
            'priority' => 'low',
            'audio_time' => 25,
            'solution' => [
                'action' => '꼬리질문 하는 방법 안내',
                'check' => '후속 질문을 생각해낼 수 있는지 확인',
                'teacher_dialog' => '방금 들은 설명에서 더 궁금한 점은 없어? "그러면 이건 어떻게 되나요?"처럼 이어서 물어봐도 좋아.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 7. 질문 듣는 중 개입방법 (intervention_method) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 19,
            'name' => '개입 타이밍 실수',
            'desc' => '설명 중간에 너무 자주 끊거나, 아예 끊지 않는 패턴',
            'sub_item' => 'intervention_method',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 25,
            'solution' => [
                'action' => '적절한 개입 타이밍 가이드 제공',
                'check' => '적절한 타이밍에 개입할 수 있는지 확인',
                'teacher_dialog' => '설명 중에 이해가 안 되면 바로 말해도 괜찮아. 단, 문장이 끝나는 시점에 끊어서 물어보면 더 좋아.'
            ]
        ],
        [
            'id' => 20,
            'name' => '이해 확인 요청 주저',
            'desc' => '이해가 안 되는데 다시 설명해달라고 요청하지 못하는 패턴',
            'sub_item' => 'intervention_method',
            'category' => 'emotional_block',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '재설명 요청이 자연스러운 것임을 인식시킴',
                'check' => '필요시 재설명을 요청할 수 있는지 확인',
                'teacher_dialog' => '"잠깐만요, 그 부분 다시 설명해주세요"라고 말하는 건 전혀 부끄러운 게 아니야.'
            ]
        ],
        [
            'id' => 21,
            'name' => '예시 요청 실패',
            'desc' => '추상적 설명을 듣고도 구체적 예시를 요청하지 못하는 패턴',
            'sub_item' => 'intervention_method',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '예시 요청의 효과를 알리고 요청 방법 안내',
                'check' => '필요시 예시를 요청할 수 있는지 확인',
                'teacher_dialog' => '설명이 이해하기 어려우면 "예를 들어주세요"라고 말해봐. 예시를 들으면 훨씬 이해가 쉬워.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 8. 마무리 (closing) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 22,
            'name' => '마무리 정리 생략',
            'desc' => '질의응답 후 배운 내용을 정리하지 않고 넘어가는 패턴',
            'sub_item' => 'closing',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '질의응답 후 요약 정리 습관 형성',
                'check' => '배운 내용을 정리했는지 확인',
                'teacher_dialog' => '방금 질문하고 들은 내용을 한 줄로 정리해볼까? 그래야 오래 기억할 수 있어.'
            ]
        ],
        [
            'id' => 23,
            'name' => '추가 질문 기회 놓침',
            'desc' => '마무리 단계에서 추가로 궁금한 점을 확인하지 않는 패턴',
            'sub_item' => 'closing',
            'category' => 'approach_error',
            'priority' => 'low',
            'audio_time' => 20,
            'solution' => [
                'action' => '마무리 전 추가 질문 확인 루틴 형성',
                'check' => '추가 질문이 없는지 확인했는지 점검',
                'teacher_dialog' => '질문이 다 해결됐니? 혹시 설명을 듣다가 새로 생긴 궁금증은 없어?'
            ]
        ],
        [
            'id' => 24,
            'name' => '감사 표현 누락',
            'desc' => '도움을 받고도 적절한 감사를 표현하지 않는 패턴',
            'sub_item' => 'closing',
            'category' => 'habit_pattern',
            'priority' => 'low',
            'audio_time' => 15,
            'solution' => [
                'action' => '감사 표현의 중요성과 방법 안내',
                'check' => '적절한 감사 표현을 하는지 확인',
                'teacher_dialog' => '도움을 받았을 때 고맙다고 표현하면, 다음에도 더 기꺼이 도움을 줄 수 있어.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 9. 추적 및 후속 학습 (tracking_followup) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 25,
            'name' => '후속 점검 미실시',
            'desc' => '질문으로 해결한 내용을 나중에 다시 확인하지 않는 패턴',
            'sub_item' => 'tracking_followup',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '질의응답 내용 복습 스케줄 수립',
                'check' => '해결한 내용을 나중에 복습할 계획이 있는지 확인',
                'teacher_dialog' => '오늘 질문한 내용을 내일 한번 더 확인해보면 더 오래 기억할 수 있어.'
            ]
        ],
        [
            'id' => 26,
            'name' => '유사 문제 연습 회피',
            'desc' => '질문으로 이해한 후 비슷한 문제로 연습하지 않는 패턴',
            'sub_item' => 'tracking_followup',
            'category' => 'approach_error',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '이해한 내용을 유사 문제로 확인하는 습관 형성',
                'check' => '유사 문제 연습 의향이 있는지 확인',
                'teacher_dialog' => '이해됐다면 비슷한 문제를 몇 개 풀어보면 확실해져. 연습문제 해볼까?'
            ]
        ],
        [
            'id' => 27,
            'name' => '질문 이력 미관리',
            'desc' => '과거 질문과 답변을 기록하고 관리하지 않는 패턴',
            'sub_item' => 'tracking_followup',
            'category' => 'habit_pattern',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '질문 노트 작성 습관 형성',
                'check' => '질문과 답변을 기록하는 습관이 있는지 확인',
                'teacher_dialog' => '질문했던 내용을 노트에 정리해두면, 나중에 같은 의문이 생겼을 때 바로 찾아볼 수 있어.'
            ]
        ],

        // ---------------------------------------------------------------------
        // 10. 닫기 (close_session) - 3개
        // ---------------------------------------------------------------------
        [
            'id' => 28,
            'name' => '급한 종료',
            'desc' => '질의응답을 제대로 마무리하지 않고 급하게 끝내는 패턴',
            'sub_item' => 'close_session',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 20,
            'solution' => [
                'action' => '질의응답 마무리 체크리스트 제공',
                'check' => '모든 마무리 단계를 거쳤는지 확인',
                'teacher_dialog' => '끝내기 전에 오늘 질문한 내용 정리했니? 다음에 더 궁금한 게 있으면 또 물어봐.'
            ]
        ],
        [
            'id' => 29,
            'name' => '미해결 방치',
            'desc' => '해결되지 않은 의문을 남겨둔 채 세션을 종료하는 패턴',
            'sub_item' => 'close_session',
            'category' => 'approach_error',
            'priority' => 'high',
            'audio_time' => 25,
            'solution' => [
                'action' => '미해결 질문 목록 작성 및 후속 계획 수립',
                'check' => '미해결 질문이 있는지 확인하고 해결 계획이 있는지 점검',
                'teacher_dialog' => '아직 해결 안 된 궁금한 게 있니? 있다면 메모해두고 다음에 다시 물어보자.'
            ]
        ],
        [
            'id' => 30,
            'name' => '학습 연계 실패',
            'desc' => '질의응답에서 배운 것을 다음 학습과 연결하지 못하는 패턴',
            'sub_item' => 'close_session',
            'category' => 'approach_error',
            'priority' => 'medium',
            'audio_time' => 25,
            'solution' => [
                'action' => '질의응답 내용과 학습 계획 연결 방법 안내',
                'check' => '배운 내용을 다음 학습에 적용할 계획이 있는지 확인',
                'teacher_dialog' => '오늘 배운 내용이 다음에 공부할 때 어디서 쓰일 수 있을지 생각해봤어?'
            ]
        ]
    ]
];

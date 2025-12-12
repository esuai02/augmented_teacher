<?php
/**
 * S5 정서 감지 상황 응답 템플릿
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    // 기본 템플릿
    'default' => [
        'message' => '학습하시면서 어떤 기분이 드시나요?',
        'cta' => '기분 표현하기',
        'tone' => 'empathetic'
    ],

    // 감정 상태별 주요 응답
    'emotional_states' => [
        // E1: 불안/긴장 감지
        'anxiety' => [
            'triggers' => ['빠른 스크롤', '반복 클릭', '짧은 체류', '건너뛰기'],
            'primary' => [
                'message' => '조금 긴장되시나요? 천천히 해도 괜찮아요. 자기 페이스가 가장 중요해요. 🌿',
                'cta' => '편안하게 시작하기',
                'intervention' => 'calming'
            ],
            'variations' => [
                'before_test' => '시험 전이라 긴장되시죠? 심호흡하고 시작해봐요.',
                'time_pressure' => '시간은 충분해요. 서두르지 않아도 돼요.',
                'difficulty' => '어려워 보여도 차근차근 하면 할 수 있어요.'
            ],
            'support_actions' => [
                'reduce_visual_complexity',
                'extend_time_limits',
                'show_progress_encouragement',
                'offer_practice_mode'
            ]
        ],

        // E2: 좌절/낙담 감지
        'frustration' => [
            'triggers' => ['긴 멈춤', '반복 오답', '빈번한 삭제', '포기 패턴'],
            'primary' => [
                'message' => '힘드셨죠? 어려운 부분이었어요. 잠시 쉬었다 해도 괜찮아요. 💪',
                'cta' => '휴식 후 재시작',
                'intervention' => 'encouragement'
            ],
            'variations' => [
                'after_failure' => '실패는 성공의 어머니라고 하잖아요. 다시 할 수 있어요!',
                'repeated_mistake' => '같은 실수를 반복해도 괜찮아요. 그게 배움이에요.',
                'comparison' => '다른 사람과 비교하지 마세요. 당신만의 속도가 있어요.'
            ],
            'support_actions' => [
                'show_past_achievements',
                'lower_difficulty_temporarily',
                'offer_hint_support',
                'celebrate_small_wins'
            ]
        ],

        // E3: 무관심/권태 감지
        'boredom' => [
            'triggers' => ['높은 스킵율', '빠른 정답', '낮은 참여도', '단조로운 패턴'],
            'primary' => [
                'message' => '좀 지루하셨나요? 더 재미있고 도전적인 걸 해볼까요? ✨',
                'cta' => '새로운 도전',
                'intervention' => 'engagement_boost'
            ],
            'variations' => [
                'too_easy' => '이미 잘 알고 계시네요! 더 어려운 걸로 가볼까요?',
                'repetitive' => '같은 유형이 반복됐네요. 다른 형식으로 바꿔볼까요?',
                'passive_learning' => '직접 해보는 활동으로 바꿔볼까요?'
            ],
            'support_actions' => [
                'increase_difficulty',
                'introduce_gamification',
                'change_activity_type',
                'offer_choice_and_autonomy'
            ]
        ],

        // E4: 자신감/열정 감지
        'confidence' => [
            'triggers' => ['연속 정답', '빠른 진행', '추가 도전', '높은 참여도'],
            'primary' => [
                'message' => '대단해요! 실력이 느는 게 보여요! 더 도전해볼까요? 🔥',
                'cta' => '고급 레벨 도전',
                'intervention' => 'challenge_elevation'
            ],
            'variations' => [
                'streak' => '{streak_count}연속 정답! 정말 잘하고 있어요!',
                'mastery' => '이 부분 완벽하게 마스터하셨네요!',
                'improvement' => '지난번보다 {improvement_percent}% 향상됐어요!'
            ],
            'support_actions' => [
                'offer_advanced_content',
                'unlock_bonus_challenges',
                'show_mastery_badge',
                'invite_to_help_others'
            ]
        ],

        // E5: 호기심/흥미 감지
        'curiosity' => [
            'triggers' => ['탐색 행동', '추가 질문', '관련 검색', '깊이 있는 학습'],
            'primary' => [
                'message' => '호기심이 가득하시네요! 더 깊이 알아볼까요? 🔍',
                'cta' => '심화 학습하기',
                'intervention' => 'depth_exploration'
            ],
            'variations' => [
                'why_question' => '좋은 질문이에요! 그 이유를 함께 알아봐요.',
                'related_topic' => '관련된 흥미로운 주제가 있어요. 볼까요?',
                'real_world' => '실제로 어떻게 사용되는지 궁금하시죠?'
            ],
            'support_actions' => [
                'provide_additional_resources',
                'show_related_topics',
                'offer_deep_dive_content',
                'connect_to_real_world_examples'
            ]
        ],

        // E6: 평온/안정 감지
        'calm' => [
            'triggers' => ['일정한 페이스', '안정적 성취', '꾸준한 진행'],
            'primary' => [
                'message' => '꾸준히 잘 하고 계시네요! 이 페이스 유지해요. 😊',
                'cta' => '계속 진행하기',
                'intervention' => 'maintain_pace'
            ],
            'variations' => [
                'steady': '안정적으로 진행하고 계세요. 좋아요!',
                'consistent' => '꾸준함이 최고의 무기예요.',
                'balanced' => '균형 잡힌 학습을 하고 계시네요.'
            ],
            'support_actions' => [
                'maintain_current_difficulty',
                'gentle_progress_reminders',
                'periodic_achievements',
                'optional_challenges'
            ]
        ]
    ],

    // 인지적 페르소나와 정서 조합
    'cognitive_emotional_matrix' => [
        // 탐색적 학습자(C1)의 정서별 대응
        'C1' => [
            'anxiety' => '새로운 걸 탐색하는 게 부담될 수 있어요. 익숙한 것부터 시작해볼까요?',
            'frustration' => '모든 길이 막힌 것 같아도, 아직 탐색하지 않은 방법이 있어요.',
            'boredom' => '새로운 영역이 당신을 기다리고 있어요! 탐험을 계속해요.',
            'confidence' => '탐험가 정신이 빛나고 있어요! 더 미지의 영역으로!',
            'curiosity' => '그 호기심 그대로 따라가 보세요. 놀라운 발견이 있을 거예요.'
        ],
        // 체계적 학습자(C2)의 정서별 대응
        'C2' => [
            'anxiety' => '계획대로 차근차근 하면 돼요. 한 단계씩 밟아가요.',
            'frustration' => '계획에서 벗어났더라도 다시 궤도에 오를 수 있어요.',
            'boredom' => '체계적인 학습에 새로운 목표를 추가해볼까요?',
            'confidence' => '계획대로 완벽하게 진행되고 있어요! 다음 단계로!',
            'curiosity' => '궁금한 점을 학습 계획에 추가해서 체계적으로 알아봐요.'
        ],
        // 추가 조합...
    ],

    // 감정 전환 지원 템플릿
    'emotional_transitions' => [
        'anxiety_to_calm' => [
            'steps' => [
                '심호흡 3번 해볼까요? 🌬️',
                '지금까지 해온 것들을 떠올려봐요.',
                '작은 것 하나만 해결해봐요. 할 수 있어요!'
            ],
            'activity' => 'breathing_exercise',
            'duration' => 30
        ],
        'frustration_to_confidence' => [
            'steps' => [
                '이전에 해결했던 문제를 볼까요?',
                '당신은 이미 이만큼 성장했어요.',
                '이번에도 해낼 수 있어요!'
            ],
            'activity' => 'past_achievements',
            'show_progress' => true
        ],
        'boredom_to_curiosity' => [
            'steps' => [
                '이 개념이 실제로 어디에 쓰이는지 알아요?',
                '상상해보세요, 이걸 알면 무엇을 할 수 있을까요?',
                '숨겨진 재미있는 사실이 있어요!'
            ],
            'activity' => 'real_world_connection',
            'gamification' => true
        ]
    ],

    // 시간대별 정서 고려
    'temporal_emotional_context' => [
        'morning' => [
            'anxiety' => '아침부터 긴장되시죠? 천천히 시작해도 괜찮아요.',
            'default' => '좋은 아침이에요! 오늘도 화이팅!'
        ],
        'after_lunch' => [
            'boredom' => '점심 후 졸리시죠? 가벼운 활동부터 해볼까요?',
            'default' => '오후 학습 시작! 에너지 충전되셨나요?'
        ],
        'evening' => [
            'frustration' => '하루 종일 열심히 했으니 오늘은 여기까지만 해도 돼요.',
            'default' => '저녁 시간이네요. 마무리 학습 시작!'
        ],
        'late_night' => [
            'anxiety' => '늦은 시간엔 무리하지 마세요. 내일 이어서 해요.',
            'default' => '늦은 시간까지 대단해요! 무리하진 마세요.'
        ]
    ],

    // 개입 후 모니터링
    'post_intervention_monitoring' => [
        'check_after' => 60, // seconds
        'success_indicators' => [
            'engagement_increase',
            'error_rate_decrease',
            'pace_normalization',
            'positive_action'
        ],
        'follow_up_templates' => [
            'improved' => '기분이 나아지신 것 같아요! 잘하고 계세요. 👏',
            'unchanged' => '아직 힘드시면 잠시 쉬어도 괜찮아요.',
            'declined' => '무리하지 마세요. 오늘은 여기까지만 해도 정말 잘 한 거예요.'
        ]
    ]
];

/*
 * 관련 데이터베이스 테이블:
 * - mdl_agent19_emotional_state (id, user_id, state_code, confidence, detected_at, context_data)
 * - mdl_agent19_intervention_history (id, user_id, emotion_from, emotion_to, intervention_type, success, created_at)
 */

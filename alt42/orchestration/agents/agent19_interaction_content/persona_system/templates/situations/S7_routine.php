<?php
/**
 * S7 시그니처 루틴 상황 응답 템플릿
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    'default' => [
        'message' => '나만의 학습 패턴이 생겼네요! 좋은 습관이에요.',
        'cta' => '루틴 확인하기',
        'tone' => 'acknowledging'
    ],

    'routine_types' => [
        'positive_routine' => [
            'message' => '멋진 학습 루틴이에요! 이 습관을 유지해봐요. ⭐',
            'celebration' => true,
            'examples' => [
                'consistent_timing' => '매일 같은 시간에 학습하시네요!',
                'warm_up' => '항상 복습으로 시작하시는 좋은 습관!',
                'completion' => '시작한 건 꼭 끝내시네요!'
            ]
        ],
        'improvement_opportunity' => [
            'message' => '학습 패턴을 조금 바꿔보면 더 효과적일 수 있어요.',
            'suggestion' => true,
            'patterns' => [
                'late_night_only' => '아침에도 해보면 기억이 더 잘 돼요.',
                'skip_review' => '복습을 추가하면 효과가 2배!',
                'rush_through' => '천천히 해도 괜찮아요.'
            ]
        ],
        'signature_detected' => [
            'message' => '당신만의 특별한 학습 스타일을 발견했어요!',
            'personalization' => true,
            'adapt_content' => true
        ]
    ],

    'cognitive' => [
        'C1' => ['message' => '탐험가는 새로운 루틴도 발견할 수 있어요!'],
        'C2' => ['message' => '체계적인 루틴이 정착되고 있어요. 완벽해요!'],
        'C3' => ['message' => '실용적인 학습 패턴이네요!'],
        'C4' => ['message' => '개념을 다지는 좋은 루틴이에요.'],
        'C5' => ['message' => '꾸준한 복습 루틴이 효과를 보이고 있어요!'],
        'C6' => ['message' => '도전적인 루틴이 실력 향상에 도움이 되고 있어요!']
    ],

    'emotional' => [
        'E1' => ['message' => '익숙한 루틴이 안정감을 주죠? 좋아요! 🌱'],
        'E2' => ['message' => '루틴을 유지하는 것만으로도 대단해요! 💪'],
        'E3' => ['message' => '루틴에 새로운 요소를 추가해볼까요? ✨'],
        'E4' => ['message' => '꾸준한 열정이 루틴을 만들었어요! 🔥'],
        'E5' => ['message' => '호기심으로 새로운 루틴도 시도해봐요! 🔍'],
        'E6' => ['message' => '안정적인 루틴이 학습을 탄탄하게 해요. 😊']
    ],

    'routine_insights' => [
        'best_time' => [
            'template' => '당신의 최적 학습 시간: {time}',
            'recommendation' => '이 시간에 어려운 내용을 학습하면 좋아요.'
        ],
        'best_duration' => [
            'template' => '집중력이 최고인 시간: {duration}분',
            'recommendation' => '이 시간 동안 핵심 내용에 집중해봐요.'
        ],
        'best_activity' => [
            'template' => '당신에게 가장 효과적인 활동: {activity}',
            'recommendation' => '이 방식으로 더 학습해볼까요?'
        ],
        'learning_rhythm' => [
            'template' => '당신의 학습 리듬이 파악됐어요!',
            'pattern_display' => true
        ]
    ],

    'milestone_celebrations' => [
        'first_routine' => '첫 번째 학습 루틴이 생겼어요! 🎉',
        'week_consistency' => '일주일 동안 꾸준히 하셨어요! 📅',
        'month_consistency' => '한 달 동안 루틴을 유지했어요! 🏆',
        'improvement' => '루틴 덕분에 실력이 {percent}% 향상됐어요!'
    ]
];

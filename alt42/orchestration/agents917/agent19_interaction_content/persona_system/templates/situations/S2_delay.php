<?php
/**
 * S2 지연 감지 상황 응답 템플릿
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    'default' => [
        'message' => '천천히 생각하고 계시네요. 도움이 필요하시면 말씀해주세요.',
        'cta' => '힌트 보기',
        'tone' => 'patient'
    ],

    'delay_types' => [
        'response_time' => [
            'message' => '시간을 가지고 생각하시는 것 좋아요. 준비되시면 답해주세요.',
            'threshold_multiplier' => 2.0
        ],
        'hesitation' => [
            'message' => '시작하기 전에 망설여지시나요? 어디서부터 할지 알려드릴까요?',
            'threshold_seconds' => 30
        ],
        'thinking' => [
            'message' => '깊이 생각하고 계시는 것 같아요. 좋은 접근이에요!',
            'positive_framing' => true
        ]
    ],

    'cognitive' => [
        'C1' => ['message' => '어디서 시작할지 고민이시죠? 몇 가지 옵션을 보여드릴게요.'],
        'C2' => ['message' => '순서대로 정리해서 시작해볼까요?'],
        'C3' => ['message' => '먼저 실제 예시를 보고 시작하시겠어요?'],
        'C4' => ['message' => '기본 개념부터 정리하고 시작할까요?'],
        'C5' => ['message' => '이전에 했던 비슷한 문제를 떠올려볼까요?'],
        'C6' => ['message' => '어려운 문제군요! 단서를 찾아볼까요?']
    ],

    'emotional' => [
        'E1' => ['message' => '서두르지 않아도 돼요. 천천히 생각해보세요. 🌱'],
        'E2' => ['message' => '막막하시죠? 함께 첫 걸음을 떼볼까요? 💪'],
        'E3' => ['message' => '다른 방식으로 접근해볼까요? ✨'],
        'E4' => ['message' => '신중하게 생각하시네요. 좋은 자세예요! 🎯'],
        'E5' => ['message' => '궁금한 점이 있으신 것 같아요. 물어보세요! 🔍'],
        'E6' => ['message' => '편안하게 생각해보세요. 시간은 충분해요. 😊']
    ],

    'assistance_levels' => [
        1 => ['type' => 'encouragement', 'message' => '잘 생각하고 계세요!'],
        2 => ['type' => 'direction', 'message' => '이 부분부터 시작해보세요.'],
        3 => ['type' => 'hint', 'message' => '힌트: {hint_content}'],
        4 => ['type' => 'partial', 'message' => '첫 번째 단계는: {first_step}'],
        5 => ['type' => 'full_guide', 'message' => '함께 단계별로 풀어봐요.']
    ]
];

<?php
/**
 * S6 활동 불균형 상황 응답 템플릿
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    'default' => [
        'message' => '다양한 활동을 해보면 학습 효과가 더 좋아요!',
        'cta' => '다른 활동 해보기',
        'tone' => 'suggestive'
    ],

    'imbalance_types' => [
        'activity_concentration' => [
            'message' => '{activity_name}만 하셨네요. 다른 유형도 도전해볼까요?',
            'threshold' => 0.7,
            'suggestions' => ['video', 'quiz', 'practice', 'reading', 'interactive']
        ],
        'difficulty_bias' => [
            'high' => ['message' => '어려운 문제만 푸시네요! 가끔 쉬운 것도 자신감에 좋아요.'],
            'low' => ['message' => '실력이 많이 늘었어요! 더 어려운 것에 도전해볼까요?'],
            'threshold' => 0.8
        ],
        'content_type_bias' => [
            'message' => '다양한 형식으로 학습하면 기억에 더 오래 남아요.',
            'balanced_ratio' => [
                'video' => 0.3,
                'reading' => 0.2,
                'practice' => 0.35,
                'interactive' => 0.15
            ]
        ]
    ],

    'cognitive' => [
        'C1' => ['message' => '탐험가답게 다른 영역도 돌아볼 때가 됐어요!'],
        'C2' => ['message' => '균형 잡힌 학습 계획에 새 활동을 추가해볼까요?'],
        'C3' => ['message' => '이론도 가끔 보면 실습이 더 효과적이에요.'],
        'C4' => ['message' => '개념 학습도 좋지만 적용해보는 것도 필요해요.'],
        'C5' => ['message' => '반복도 좋지만 새로운 것도 도전해봐요.'],
        'C6' => ['message' => '도전도 좋지만 기본기도 다져야 해요.']
    ],

    'behavioral' => [
        'B1' => ['message' => '집중하시는 것 좋지만 다양한 시각도 필요해요.'],
        'B2' => ['message' => '더 탐험할 곳이 많아요!'],
        'B3' => ['message' => '완료한 것도 좋지만 새로운 시작도 해볼까요?'],
        'B4' => ['message' => '반복 연습 효과가 나타나고 있어요. 이제 새로운 것도!'],
        'B5' => ['message' => '다양하게 접근하시네요! 깊이도 더해볼까요?'],
        'B6' => ['message' => '다른 사람들은 어떻게 학습하는지 볼까요?']
    ],

    'balance_suggestions' => [
        'add_video' => ['activity' => 'video', 'message' => '영상으로 개념을 정리해봐요.'],
        'add_practice' => ['activity' => 'practice', 'message' => '직접 풀어보면서 익혀봐요.'],
        'add_reading' => ['activity' => 'reading', 'message' => '글로 된 설명을 읽어봐요.'],
        'add_interactive' => ['activity' => 'interactive', 'message' => '상호작용 콘텐츠로 재미있게!'],
        'adjust_difficulty' => ['message' => '난이도를 조절해서 다양하게 연습해봐요.']
    ]
];

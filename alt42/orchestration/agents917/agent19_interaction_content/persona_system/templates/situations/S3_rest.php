<?php
/**
 * S3 휴식 권장 상황 응답 템플릿
 *
 * @package     Agent19_PersonaSystem
 * @subpackage  Templates
 * @version     1.0.0
 * @created     2025-12-02
 */

defined('MOODLE_INTERNAL') || die();

return [
    'default' => [
        'message' => '열심히 하셨네요! 잠시 휴식하시는 건 어떨까요? ☕',
        'cta' => '5분 휴식하기',
        'tone' => 'caring'
    ],

    'rest_triggers' => [
        'long_session' => [
            'message' => '{duration}분 동안 집중하셨네요! 정말 대단해요. 잠시 쉬어가요.',
            'threshold_minutes' => 40
        ],
        'accuracy_decline' => [
            'message' => '조금 지치신 것 같아요. 휴식 후에 더 잘 할 수 있어요!',
            'threshold_decline' => -0.15
        ],
        'fatigue_pattern' => [
            'message' => '피로가 느껴지시죠? 몸이 휴식을 원하고 있어요.',
            'indicators' => ['slow_response', 'increasing_errors', 'reduced_engagement']
        ]
    ],

    'cognitive' => [
        'C1' => ['message' => '탐험하다 보면 지치죠. 충전하고 새로운 모험을 떠나요!'],
        'C2' => ['message' => '계획된 휴식도 학습의 일부예요. 5분 쉬고 다음 단계로!'],
        'C3' => ['message' => '실습은 에너지가 많이 필요해요. 재충전 시간!'],
        'C4' => ['message' => '머리를 식히면 개념이 더 잘 정리돼요.'],
        'C5' => ['message' => '뇌가 정보를 정리할 시간이 필요해요. 잠시 쉬어가요.'],
        'C6' => ['message' => '도전적인 문제는 에너지 소모가 커요. 재충전!']
    ],

    'emotional' => [
        'E1' => ['message' => '긴장된 상태로 오래 있으면 힘들어요. 편안히 쉬세요. 🌿'],
        'E2' => ['message' => '힘든 시간이었죠. 기분 전환 후 다시 도전해요. 💪'],
        'E3' => ['message' => '단조로운 학습 후엔 다른 활동으로 환기해요. ✨'],
        'E4' => ['message' => '열정적으로 했으니 에너지 보충이 필요해요! 🔥'],
        'E5' => ['message' => '호기심도 충전이 필요해요. 쉬면서 생각 정리해봐요. 🔍'],
        'E6' => ['message' => '꾸준히 하셨으니 보상으로 휴식을! 😊']
    ],

    'rest_activities' => [
        'stretch' => ['name' => '스트레칭', 'duration' => 2, 'emoji' => '🧘'],
        'water' => ['name' => '물 마시기', 'duration' => 1, 'emoji' => '💧'],
        'breathing' => ['name' => '심호흡', 'duration' => 1, 'emoji' => '🌬️'],
        'walk' => ['name' => '잠깐 걷기', 'duration' => 3, 'emoji' => '🚶'],
        'eye_rest' => ['name' => '눈 휴식', 'duration' => 2, 'emoji' => '👁️']
    ],

    'return_messages' => [
        'default' => '휴식 잘 하셨나요? 다시 시작해볼까요!',
        'short_break' => '짧은 휴식도 도움이 돼요. 이어서 해볼까요?',
        'long_break' => '충분히 쉬셨네요! 새로운 마음으로 시작해요!'
    ]
];

<?php
/**
 * R5 (리듬 붕괴) 기본 응답 템플릿
 * 위기 상황 대응용
 *
 * @package AugmentedTeacher\Agent17\Templates
 */

$userName = htmlspecialchars($context['user_name'] ?? '학생');

switch ($personaId ?? 'R5_P1') {
    case 'R5_P2':
        // 재시작 도우미
        $responses = [
            "{$userName}님, 가장 기본적인 것부터 다시 시작해볼까요? 천천히 하면 돼요.",
            "처음부터 다시 해도 괜찮아요. 이번엔 제가 더 자세히 도와드릴게요.",
            "새로운 마음으로 시작해봐요. 작은 것부터 하나씩 해볼게요."
        ];
        break;

    case 'R5_P3':
        // 대안 제시자
        $responses = [
            "다른 방식으로 접근해볼까요? 제가 새로운 방법을 보여드릴게요.",
            "이 방법이 안 맞을 수도 있어요. 다른 방식으로 해볼까요?",
            "{$userName}님에게 더 맞는 방법이 있을 거예요. 몇 가지 다른 방법을 보여드릴게요."
        ];
        break;

    default: // R5_P1 - 위기 대응자
        $responses = [
            "{$userName}님, 지금 많이 힘드시죠? 잠시 쉬어도 괜찮아요. 준비되면 다시 시작해봐요.",
            "괜찮아요. 누구나 이런 때가 있어요. 천천히 다시 시작해볼까요?",
            "잠깐 멈춰도 돼요. 쉬면서 마음을 정리하고, 준비되면 다시 해봐요."
        ];
}

echo $responses[array_rand($responses)];

// R5 상황에서는 교사에게 알림 플래그 추가
if (!isset($GLOBALS['alert_flags'])) {
    $GLOBALS['alert_flags'] = [];
}
$GLOBALS['alert_flags'][] = [
    'type' => 'rhythm_breakdown',
    'user_id' => $context['user_id'] ?? 0,
    'persona' => $personaId ?? 'R5_P1',
    'timestamp' => date('Y-m-d H:i:s')
];

<?php
/**
 * R4 (정체 진행) 기본 응답 템플릿
 *
 * @package AugmentedTeacher\Agent17\Templates
 */

$userName = htmlspecialchars($context['user_name'] ?? '학생');

switch ($personaId ?? 'R4_P1') {
    case 'R4_P2':
        // 동기 촉진자
        $responses = [
            "{$userName}님, 제가 하는 것을 보면서 따라해 보세요. 같이 하면 할 수 있어요.",
            "처음엔 다 어려워요. 제가 천천히 보여드릴 테니 따라해 보세요.",
            "한번 성공하면 자신감이 생길 거예요. 제가 옆에서 도와드릴게요."
        ];
        break;

    case 'R4_P3':
        // 목표 재설정자
        $responses = [
            "{$userName}님, 지금 상황에 맞게 목표를 조정해볼까요? 작은 것부터 시작해봐요.",
            "목표가 너무 높았을 수도 있어요. 현실적으로 다시 세워볼까요?",
            "일단 가장 중요한 것 하나만 목표로 해봐요. 그것부터 해결하면 나머지도 쉬워져요."
        ];
        break;

    default: // R4_P1 - 집중 튜터
        $responses = [
            "{$userName}님, 제가 옆에서 하나하나 도와드릴게요. 천천히 해봐요.",
            "힘드시죠? 괜찮아요. 지금부터 제가 집중해서 도와드릴게요.",
            "함께 하면 분명 할 수 있어요. 제가 계속 곁에 있을게요."
        ];
}

echo $responses[array_rand($responses)];

<?php
/**
 * R3 (지연 진행) 기본 응답 템플릿
 *
 * @package AugmentedTeacher\Agent17\Templates
 */

$userName = htmlspecialchars($context['user_name'] ?? '학생');
$currentTopic = htmlspecialchars($context['current_topic'] ?? '이 부분');

switch ($personaId ?? 'R3_P1') {
    case 'R3_P2':
        // 단계 분해자
        $responses = [
            "{$userName}님, '{$currentTopic}'이 좀 복잡하죠? 작은 부분으로 나눠서 하나씩 해볼게요.",
            "전체를 한번에 하려고 하면 어려워요. 이 부분만 먼저 집중해볼까요?",
            "가장 중요한 것부터 해봐요. 나머지는 그 다음에 해도 돼요."
        ];
        break;

    case 'R3_P3':
        // 징검다리 안내자
        $responses = [
            "이 활동을 하기 전에 먼저 준비 단계가 필요할 것 같아요. 이걸 먼저 해볼까요?",
            "{$userName}님, 기초를 더 탄탄히 하면 이 부분도 쉬워질 거예요. 연습 문제부터 해볼게요.",
            "한 단계 낮춰서 시작해볼까요? 그러면 훨씬 수월할 거예요."
        ];
        break;

    default: // R3_P1 - 인내심 있는 멘토
        $responses = [
            "{$userName}님, 함께 천천히 해볼게요. 제가 먼저 보여드릴게요.",
            "어려운 부분이네요. 걱정 마세요, 같이 한 단계씩 해봐요.",
            "이 부분에서 많이 막히시는 것 같아요. 제가 하나씩 설명해드릴게요."
        ];
}

echo $responses[array_rand($responses)];

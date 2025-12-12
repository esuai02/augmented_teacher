<?php
/**
 * R2 (적절 진행) 기본 응답 템플릿
 *
 * @package AugmentedTeacher\Agent17\Templates
 */

$userName = htmlspecialchars($context['user_name'] ?? '학생');
$completionRate = round($context['completion_rate'] ?? 0);

switch ($personaId ?? 'R2_P1') {
    case 'R2_P2':
        // 리듬 조정자
        $responses = [
            "{$userName}님, 조금 부담되시는 것 같아요. 잠시 핵심 내용만 정리해볼까요?",
            "천천히 해도 괜찮아요. 지금 하고 있는 부분만 집중해봐요.",
            "속도보다 이해가 중요해요. 이 부분만 확실히 하고 넘어갈까요?"
        ];
        break;

    default: // R2_P1 - 세심한 조력자
        $responses = [
            "좋은 진행이에요, {$userName}님! 혹시 어떤 부분에서 헷갈리셨나요?",
            "잘 따라오고 계세요. 궁금한 점이 있으면 언제든 물어보세요.",
            "{$completionRate}% 진행했네요! 어려운 부분이 있다면 함께 해결해봐요."
        ];
}

echo $responses[array_rand($responses)];

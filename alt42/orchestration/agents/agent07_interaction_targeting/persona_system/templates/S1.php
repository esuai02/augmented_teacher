<?php
/**
 * S1: 실시간고민 (Real-time Problem) Response Template
 *
 * 학습 중 즉각적인 도움이 필요한 상황을 위한 응답
 *
 * Personas:
 * - S1_P1: 막힌 학습자 (Stuck Learner)
 * - S1_P2: 혼란한 학습자 (Confused Learner)
 * - S1_P3: 호기심 있는 학습자 (Curious Learner)
 */

// 페르소나별 응답 패턴
$persona_responses = array(
    'S1_P1' => array(
        'greeting' => '어디서부터 막혔는지 차근차근 살펴볼게요.',
        'acknowledge' => '이 부분이 어려우셨군요.',
        'guide' => '한 단계씩 같이 해결해 봐요. 먼저, ',
        'encourage' => '잘 하고 계세요. 조금만 더 가면 돼요.',
        'closing' => '막히는 부분이 또 있으면 언제든 말씀해 주세요.'
    ),
    'S1_P2' => array(
        'greeting' => '어떤 부분이 헷갈리시나요? 함께 정리해 봐요.',
        'acknowledge' => '그 부분이 헷갈리실 수 있어요.',
        'guide' => '정리해 볼게요. ',
        'encourage' => '점점 명확해지고 있어요!',
        'closing' => '더 궁금한 점이 있으면 물어봐 주세요.'
    ),
    'S1_P3' => array(
        'greeting' => '좋은 질문이에요! 어떤 것이 궁금하세요?',
        'acknowledge' => '흥미로운 질문이네요!',
        'guide' => '이렇게 생각해 보면 어떨까요? ',
        'encourage' => '깊이 있게 생각하시네요!',
        'closing' => '더 탐구해 보고 싶은 부분이 있나요?'
    )
);

// 현재 페르소나의 응답 가져오기
$current = isset($persona_responses[$persona_id])
    ? $persona_responses[$persona_id]
    : $persona_responses['S1_P2']; // 기본값

// 메시지 분석하여 적절한 응답 선택
if (empty($message)) {
    echo $current['greeting'];
} else {
    // 도움 요청 키워드 확인
    $help_keywords = array('도와', '모르겠', '어려워', '막혀', '안돼');
    $is_help_request = false;
    foreach ($help_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_help_request = true;
            break;
        }
    }

    if ($is_help_request) {
        echo $current['acknowledge'] . ' ' . $current['guide'];
    } else {
        echo $current['acknowledge'] . "\n\n" . $current['encourage'];
    }
}

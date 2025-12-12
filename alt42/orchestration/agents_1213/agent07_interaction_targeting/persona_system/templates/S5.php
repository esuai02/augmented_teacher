<?php
/**
 * S5: 귀가검사 (End-of-Day Review) Response Template
 *
 * 하루 학습 마무리 및 회고 상황을 위한 응답
 *
 * Personas:
 * - S5_P1: 불안한 마무리 (Anxious Closer)
 * - S5_P2: 성취 마무리 (Achieved Closer)
 * - S5_P3: 급한 마무리 (Rushed Closer)
 */

$persona_responses = array(
    'S5_P1' => array(
        'greeting' => '오늘 하루 어떠셨어요?',
        'acknowledge' => '걱정이 되시는군요.',
        'guide' => '오늘 한 것들을 차분하게 정리해 볼까요?',
        'reflection' => "오늘의 학습을 돌아봐요:\n- 오늘 무엇을 시도했나요?\n- 조금이라도 나아진 점은?\n- 내일은 어떻게 할 수 있을까요?",
        'encourage' => '완벽하지 않아도 괜찮아요. 시도한 것 자체가 가치 있어요.',
        'closing' => '오늘 수고하셨어요. 푹 쉬세요!'
    ),
    'S5_P2' => array(
        'greeting' => '오늘 배운 것들을 정리해 볼까요?',
        'acknowledge' => '오늘 많은 것을 하셨네요!',
        'guide' => '오늘의 성취를 기록해 볼까요?',
        'reflection' => "오늘의 성과를 축하해요!\n- 오늘 완료한 것은?\n- 특히 잘한 점은?\n- 새롭게 배운 것은?",
        'encourage' => '오늘 정말 잘 하셨어요! 이 페이스를 유지해 봐요.',
        'closing' => '내일도 화이팅!'
    ),
    'S5_P3' => array(
        'greeting' => '서둘러 끝내야 하시는군요.',
        'acknowledge' => '시간이 없으시죠.',
        'guide' => '핵심만 빠르게 확인할게요.',
        'reflection' => "빠른 체크:\n- 오늘 목표한 건 했나요? (Y/N)\n- 내일 기억할 한 가지는?",
        'encourage' => '짧게라도 정리하는 습관 좋아요.',
        'closing' => '빠르게 마무리하고 쉬세요!'
    )
);

$current = isset($persona_responses[$persona_id])
    ? $persona_responses[$persona_id]
    : $persona_responses['S5_P2'];

// 컨텍스트 확인
$anxiety_signals = isset($context['anxiety_signals']) ? $context['anxiety_signals'] : false;
$termination_urgency = isset($context['termination_urgency']) ? $context['termination_urgency'] : false;

if (empty($message)) {
    if ($termination_urgency) {
        echo $current['greeting'] . "\n\n" . $current['reflection'];
    } else {
        echo $current['greeting'];
    }
} else {
    // 불안/걱정 키워드 확인
    $anxiety_keywords = array('걱정', '불안', '못했', '안했', '부족');
    $is_anxious = false;
    foreach ($anxiety_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_anxious = true;
            break;
        }
    }

    // 급함 키워드 확인
    $rush_keywords = array('빨리', '급해', '가야', '시간없');
    $is_rushed = false;
    foreach ($rush_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_rushed = true;
            break;
        }
    }

    if ($is_anxious) {
        echo $current['acknowledge'] . "\n\n" . $current['encourage'];
    } elseif ($is_rushed) {
        echo $current['guide'] . "\n\n" . $current['reflection'];
    } else {
        echo $current['acknowledge'] . "\n\n" . $current['reflection'];
    }
}

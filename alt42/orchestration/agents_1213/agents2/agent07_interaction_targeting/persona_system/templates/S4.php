<?php
/**
 * S4: 목표설정 (Goal Setting) Response Template
 *
 * 학습 목표 수립 단계를 위한 응답
 *
 * Personas:
 * - S4_P1: 모호한 학습자 (Vague Learner)
 * - S4_P2: 외압 학습자 (Externally Motivated Learner)
 * - S4_P3: 비전 학습자 (Vision-driven Learner)
 */

$persona_responses = array(
    'S4_P1' => array(
        'greeting' => '어떤 학습을 하고 싶으신가요?',
        'acknowledge' => '무엇을 해야 할지 막막하시군요.',
        'guide' => '함께 구체적인 목표를 세워 볼까요?',
        'technique' => "목표를 명확하게 만들어 봐요:\n- 무엇을 배우고 싶으세요?\n- 언제까지 하고 싶으세요?\n- 어떻게 성공을 확인할 수 있을까요?",
        'encourage' => '처음엔 모호해도 괜찮아요. 하나씩 정리해 보면 됩니다.',
        'closing' => '목표가 명확해지면 학습이 훨씬 쉬워질 거예요.'
    ),
    'S4_P2' => array(
        'greeting' => '학습 목표를 함께 정리해 볼까요?',
        'acknowledge' => '해야 한다는 압박감이 있으시네요.',
        'guide' => '잠깐, 왜 이것을 배우고 싶으신지 생각해 볼까요?',
        'technique' => "내 것으로 만들어 봐요:\n- 이걸 배우면 나에게 어떤 점이 좋을까?\n- 이것과 관련해서 진짜 궁금한 것은?\n- 내가 원하는 방식으로 배울 수 있을까?",
        'encourage' => '남이 시킨 것도 내 것으로 만들 수 있어요.',
        'closing' => '조금씩이라도 자신만의 이유를 찾아보세요.'
    ),
    'S4_P3' => array(
        'greeting' => '장기적인 계획을 세우고 싶으시군요!',
        'acknowledge' => '큰 그림을 보고 계시네요.',
        'guide' => '비전을 구체적인 단계로 나눠 볼까요?',
        'technique' => "큰 목표를 작은 단계로:\n1. 최종 목표는 무엇인가요?\n2. 그 전에 필요한 중간 단계는?\n3. 이번 주에 할 수 있는 첫 단계는?",
        'encourage' => '원대한 비전을 가지셨네요! 멋져요.',
        'closing' => '한 걸음씩 나아가다 보면 그 비전에 도달할 거예요.'
    )
);

$current = isset($persona_responses[$persona_id])
    ? $persona_responses[$persona_id]
    : $persona_responses['S4_P2'];

// 목표 명확도 컨텍스트 확인
$goal_clarity = isset($context['goal_clarity']) ? $context['goal_clarity'] : 0.5;
$intrinsic_motivation = isset($context['intrinsic_motivation']) ? $context['intrinsic_motivation'] : 0.5;

if (empty($message)) {
    echo $current['greeting'];
} else {
    // 모호한 표현 확인
    $vague_keywords = array('잘하고싶', '열심히', '좀더', '그냥', '뭔가', '몰라');
    $is_vague = false;
    foreach ($vague_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_vague = true;
            break;
        }
    }

    // 외부 압력 표현 확인
    $external_keywords = array('해야', '시켜서', '엄마', '아빠', '선생님');
    $is_external = false;
    foreach ($external_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_external = true;
            break;
        }
    }

    if ($is_vague) {
        echo $current['acknowledge'] . "\n\n" . $current['technique'];
    } elseif ($is_external) {
        echo $current['acknowledge'] . "\n\n" . $current['guide'];
    } else {
        echo $current['acknowledge'] . "\n\n" . $current['encourage'];
    }
}

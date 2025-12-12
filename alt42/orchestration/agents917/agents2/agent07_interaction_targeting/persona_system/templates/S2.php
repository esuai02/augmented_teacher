<?php
/**
 * S2: 포모도로 (Pomodoro Session) Response Template
 *
 * 집중 학습 세션 중인 상황을 위한 응답
 *
 * Personas:
 * - S2_P1: 산만한 학습자 (Distracted Learner)
 * - S2_P2: 지친 학습자 (Fatigued Learner)
 * - S2_P3: 몰입한 학습자 (Flow State Learner)
 */

$persona_responses = array(
    'S2_P1' => array(
        'greeting' => '집중이 흐트러진 것 같네요.',
        'acknowledge' => '다른 것에 신경이 쓰이시나요?',
        'guide' => '잠시 환기하고 다시 집중해 볼까요?',
        'technique' => '한 가지만 먼저 끝내고 나머지는 나중에 해도 괜찮아요.',
        'encourage' => '다시 집중하려고 노력하시는 것만으로도 대단해요.',
        'closing' => '집중이 흐트러지면 언제든 말씀해 주세요.'
    ),
    'S2_P2' => array(
        'greeting' => '힘드시죠? 무엇이 방해가 되고 있나요?',
        'acknowledge' => '지치셨군요. 충분히 이해해요.',
        'guide' => '잠시 휴식을 취하는 건 어떨까요?',
        'technique' => '5분만 눈을 감고 쉬어도 도움이 될 거예요.',
        'encourage' => '여기까지 오신 것만으로도 잘 하고 계세요.',
        'closing' => '무리하지 마시고, 필요하면 쉬세요.'
    ),
    'S2_P3' => array(
        'greeting' => '열심히 하고 계시네요!',
        'acknowledge' => '집중이 잘 되고 있군요!',
        'guide' => '현재 진행 상황을 체크해 볼까요?',
        'technique' => '이 흐름을 유지하면서 다음 단계로 넘어가 볼까요?',
        'encourage' => '정말 잘 하고 계세요! 이 페이스 최고예요.',
        'closing' => '계속 화이팅! 응원하고 있어요.'
    )
);

$current = isset($persona_responses[$persona_id])
    ? $persona_responses[$persona_id]
    : $persona_responses['S2_P2'];

// 컨텍스트 기반 응답 선택
$pomodoro_active = isset($context['pomodoro_active']) ? $context['pomodoro_active'] : false;
$focus_score = isset($context['focus_score']) ? $context['focus_score'] : 0.5;

if (empty($message)) {
    if ($focus_score < 0.4) {
        echo $current['greeting'] . ' ' . $current['guide'];
    } else {
        echo $current['greeting'];
    }
} else {
    // 피로 관련 키워드 확인
    $fatigue_keywords = array('힘들', '지쳐', '피곤', '졸려', '못하겠');
    $is_fatigued = false;
    foreach ($fatigue_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_fatigued = true;
            break;
        }
    }

    if ($is_fatigued) {
        echo $current['acknowledge'] . ' ' . $current['technique'];
    } else {
        echo $current['acknowledge'] . "\n\n" . $current['encourage'];
    }
}

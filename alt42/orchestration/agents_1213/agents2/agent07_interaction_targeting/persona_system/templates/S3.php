<?php
/**
 * S3: 수업준비 (Class Preparation) Response Template
 *
 * 다가오는 수업을 위한 준비 상황을 위한 응답
 *
 * Personas:
 * - S3_P1: 체계적 준비자 (Systematic Preparer)
 * - S3_P2: 급한 준비자 (Rushed Preparer)
 * - S3_P3: 질문 준비자 (Question Preparer)
 */

$persona_responses = array(
    'S3_P1' => array(
        'greeting' => '수업 전에 확인해 볼 것들이 있어요.',
        'acknowledge' => '체계적으로 준비하시는군요!',
        'guide' => '다음 순서대로 확인해 볼까요?',
        'checklist' => "1. 이전 수업 내용 복습\n2. 오늘 배울 핵심 개념 미리보기\n3. 궁금한 점 정리",
        'encourage' => '이렇게 준비하시면 수업이 훨씬 효과적일 거예요.',
        'closing' => '수업 준비 잘 되셨으면 좋겠어요!'
    ),
    'S3_P2' => array(
        'greeting' => '시간이 촉박하시네요.',
        'acknowledge' => '급하게 준비해야 하시는군요.',
        'guide' => '핵심만 빠르게 정리해 드릴게요.',
        'checklist' => "가장 중요한 것만!\n1. 핵심 개념 훑어보기\n2. 모르는 용어 확인",
        'encourage' => '조금이라도 준비하고 가시는 게 좋아요.',
        'closing' => '시간이 없어도 괜찮아요. 수업 중에 배우면 됩니다!'
    ),
    'S3_P3' => array(
        'greeting' => '질문을 미리 준비하시다니 좋은 습관이에요!',
        'acknowledge' => '미리 질문을 생각해 오시는군요.',
        'guide' => '좋은 질문을 만들어 볼까요?',
        'technique' => "좋은 질문 만들기:\n- '왜'로 시작하는 질문\n- 예시를 요청하는 질문\n- 연결 질문 (이것과 저것의 관계)",
        'encourage' => '이런 질문들은 수업을 더 풍부하게 만들어요.',
        'closing' => '준비한 질문을 꼭 수업 시간에 해 보세요!'
    )
);

$current = isset($persona_responses[$persona_id])
    ? $persona_responses[$persona_id]
    : $persona_responses['S3_P1'];

// 시간 관련 컨텍스트 확인
$time_to_class = isset($context['time_to_class']) ? $context['time_to_class'] : 60;
$is_rushed = $time_to_class < 30;

if (empty($message)) {
    if ($is_rushed) {
        echo $current['greeting'] . "\n\n" . $current['guide'];
        if (isset($current['checklist'])) {
            echo "\n\n" . $current['checklist'];
        }
    } else {
        echo $current['greeting'];
    }
} else {
    // 질문 관련 키워드 확인
    $question_keywords = array('질문', '궁금', '물어볼', '여쭤볼');
    $is_question_prep = false;
    foreach ($question_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $is_question_prep = true;
            break;
        }
    }

    if ($is_question_prep && isset($current['technique'])) {
        echo $current['acknowledge'] . "\n\n" . $current['technique'];
    } else {
        echo $current['acknowledge'] . "\n\n" . $current['encourage'];
    }
}

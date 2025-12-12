<?php
/**
 * S6: 커리큘럼 (Curriculum Planning) Response Template
 *
 * 장기 학습 계획 수립 상황을 위한 응답
 *
 * Personas:
 * - S6_P1: 방향 탐색자 (Direction Seeker)
 * - S6_P2: 로드맵 탐색자 (Roadmap Seeker)
 * - S6_P3: 탐험적 학습자 (Exploratory Learner)
 */

$persona_responses = array(
    'S6_P1' => array(
        'greeting' => '학습 방향을 고민하고 계시군요.',
        'acknowledge' => '어디로 가야 할지 막막하시죠.',
        'guide' => '함께 방향을 찾아봐요.',
        'exploration' => "방향 찾기 질문:\n- 지금 가장 관심 있는 분야는?\n- 어떤 것을 할 줄 알게 되고 싶으세요?\n- 이전에 재미있었던 학습 경험은?",
        'encourage' => '방향을 찾으려고 노력하시는 것 자체가 좋은 시작이에요.',
        'closing' => '천천히 탐색해 봐요. 답은 찾을 거예요.'
    ),
    'S6_P2' => array(
        'greeting' => '전체적인 로드맵을 함께 살펴볼까요?',
        'acknowledge' => '큰 그림을 그리고 계시네요.',
        'guide' => '체계적인 학습 경로를 설계해 봐요.',
        'roadmap' => "로드맵 설계:\n1. 현재 수준 파악\n2. 목표 지점 설정\n3. 중간 이정표 배치\n4. 각 단계별 학습 자료 선정",
        'encourage' => '이렇게 계획적으로 접근하시면 효율적으로 성장할 수 있어요.',
        'closing' => '로드맵을 따라가다 보면 목표에 도달할 거예요.'
    ),
    'S6_P3' => array(
        'greeting' => '새로운 분야를 탐색하시는군요!',
        'acknowledge' => '호기심이 많으시네요!',
        'guide' => '다양한 분야를 맛보기로 경험해 볼까요?',
        'exploration' => "탐험 전략:\n- 관심 분야 3개 선택\n- 각각 맛보기 학습 (1-2시간)\n- 가장 끌리는 분야 깊게 파기",
        'encourage' => '새로운 것을 탐색하는 자세가 정말 좋아요!',
        'closing' => '어떤 분야가 가장 재미있으셨나요?'
    )
);

$current = isset($persona_responses[$persona_id])
    ? $persona_responses[$persona_id]
    : $persona_responses['S6_P2'];

// 컨텍스트 확인
$direction_certainty = isset($context['direction_certainty']) ? $context['direction_certainty'] : 0.5;
$discovery_oriented = isset($context['discovery_oriented']) ? $context['discovery_oriented'] : false;

if (empty($message)) {
    echo $current['greeting'];
} else {
    // 방향 관련 키워드 확인
    $direction_keywords = array('어디', '뭘', '무엇을', '어떤', '시작');
    $needs_direction = false;
    foreach ($direction_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $needs_direction = true;
            break;
        }
    }

    // 로드맵 관련 키워드 확인
    $roadmap_keywords = array('계획', '로드맵', '순서', '단계', '경로');
    $wants_roadmap = false;
    foreach ($roadmap_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $wants_roadmap = true;
            break;
        }
    }

    // 탐험 관련 키워드 확인
    $explore_keywords = array('재미', '흥미', '궁금', '새로운', '다양한');
    $wants_explore = false;
    foreach ($explore_keywords as $keyword) {
        if (mb_strpos($message, $keyword) !== false) {
            $wants_explore = true;
            break;
        }
    }

    if ($needs_direction && isset($current['exploration'])) {
        echo $current['acknowledge'] . "\n\n" . $current['exploration'];
    } elseif ($wants_roadmap && isset($current['roadmap'])) {
        echo $current['acknowledge'] . "\n\n" . $current['roadmap'];
    } elseif ($wants_explore && isset($current['exploration'])) {
        echo $current['acknowledge'] . "\n\n" . $current['exploration'];
    } else {
        echo $current['acknowledge'] . "\n\n" . $current['encourage'];
    }
}

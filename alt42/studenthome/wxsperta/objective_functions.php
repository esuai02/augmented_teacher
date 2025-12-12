<?php
/**
 * Objective Functions (Internal)
 * - 절대 학생에게 "목적함수/최적화/KPI" 같은 단어로 노출하지 않는다.
 * - 서버 내부에서 추천 선택지/프롬프트 스위칭에만 사용한다.
 */

function orbit_strpos_any($haystack, $needle) {
    $haystack = (string)$haystack;
    $needle = (string)$needle;
    if ($needle === '') return false;
    if (function_exists('mb_strpos')) return mb_strpos($haystack, $needle);
    return strpos($haystack, $needle);
}

/**
 * 🌌 마이 궤도(전체) 목적함수: 다목적(정서/자율/성장/장벽제거/지속성)
 * - 값은 0~1 가중치로 해석
 */
function orbit_global_objective_weights() {
    return [
        // 정서 안전(불안/좌절 시 우선순위 상승)
        'emotion_safety' => 0.30,
        // 자율성(선택/통제감)
        'autonomy' => 0.20,
        // 성장(자기명확성/방향/역량/실행)
        'growth' => 0.25,
        // 장벽 제거(막힘/회피 비용 최소화)
        'barrier_removal' => 0.15,
        // 지속성(주간 리텐션/재방문)
        'retention' => 0.10,
    ];
}

/**
 * WXSPERTA 8층(홀론)별 목적함수(내부)
 * - 여기서의 "홀론"은 8층 레이어(W/X/S/P/E/R/T/A)의 대화 기여 방향(질문유형/선택지 형태)을 뜻한다.
 */
function orbit_holon_objective_weights($anchor_layer) {
    $k = strtoupper(trim((string)$anchor_layer));

    // 기본값: 문맥(X) 중심
    $defaults = [
        'clarity' => 0.30,
        'actionability' => 0.25,
        'emotional_relief' => 0.25,
        'exploration' => 0.20,
    ];

    switch ($k) {
        case 'W': // WorldView
            return [
                'clarity' => 0.45,
                'meaning' => 0.35,
                'exploration' => 0.15,
                'actionability' => 0.05,
            ];
        case 'X': // Context
            return $defaults;
        case 'S': // Structure
            return [
                'clarity' => 0.45,
                'structure' => 0.35,
                'actionability' => 0.15,
                'emotional_relief' => 0.05,
            ];
        case 'P': // Process
            return [
                'actionability' => 0.45,
                'clarity' => 0.30,
                'barrier_removal' => 0.15,
                'retention' => 0.10,
            ];
        case 'E': // Execution
            return [
                'actionability' => 0.55,
                'barrier_removal' => 0.25,
                'retention' => 0.10,
                'emotional_relief' => 0.10,
            ];
        case 'R': // Reflection
            return [
                'emotional_relief' => 0.40,
                'clarity' => 0.35,
                'growth' => 0.20,
                'retention' => 0.05,
            ];
        case 'T': // Transfer/Traffic
            return [
                'barrier_removal' => 0.40,
                'exploration' => 0.30,
                'actionability' => 0.20,
                'clarity' => 0.10,
            ];
        case 'A': // Abstraction
            return [
                'clarity' => 0.40,
                'meaning' => 0.35,
                'transfer' => 0.20,
                'retention' => 0.05,
            ];
        default:
            return $defaults;
    }
}

/**
 * 학생 언어로만 추천 선택지(3-choice) 만들기
 * - 목적함수는 내부에서만: 결과는 "추천 선택지" 텍스트로만 반환
 */
function orbit_recommend_3choices($agent, $user_message, $ai_message) {
    // 간단한 상태 폴백(대화가 얇아도 동작)
    $u = (string)$user_message;
    $emotion = 'neutral';
    $emotion_words = [
        'anxious' => ['불안', '걱정', '무서', '망할', '큰일'],
        'frustrated' => ['짜증', '포기', '못하겠', '너무 어려', '안 풀려'],
        'bored' => ['재미없', '지루', '의미없'],
        'sad' => ['우울', '슬퍼'],
        'energized' => ['좋아', '할래', '가보자', '재밌', '신나'],
    ];
    foreach ($emotion_words as $k => $words) {
        foreach ($words as $w) {
            if (orbit_strpos_any($u, $w) !== false) { $emotion = $k; break 2; }
        }
    }

    // agent category로 앵커 대략 매핑(세부 앵커는 conversation_id/상태추론이 붙으면 더 정교해짐)
    $cat = (string)($agent['category'] ?? '');
    $anchor = 'X';
    if ($cat === 'future_design') $anchor = 'W';
    else if ($cat === 'execution') $anchor = 'E';
    else if ($cat === 'branding') $anchor = 'R';
    else if ($cat === 'knowledge_management') $anchor = 'S';

    // 정서가 흔들리면 R로 스위칭(전체 목적함수에서 emotion_safety 가중치)
    if ($emotion !== 'neutral') $anchor = 'R';

    // 3-choice 템플릿(학생 언어)
    $choices = [];

    // (1) 지금(상태/막힘)
    if ($emotion !== 'neutral') {
        $choices[] = "지금 마음부터 10초만 체크하자(괜찮아)";
    } else {
        $choices[] = "지금 제일 막히는 한 줄만 말해줘";
    }

    // (2) 다음 한 칸(실행/절차)
    if ($anchor === 'E' || $anchor === 'P') {
        $choices[] = "딱 5분짜리로 지금 바로 할 수 있는 한 칸 정하자";
    } else {
        $choices[] = "오늘은 뭐부터 하면 ‘한 칸 전진’ 느낌일까?";
    }

    // (3) 탐험(확장/연결/의미)
    if ($anchor === 'W') {
        $choices[] = "10년 후의 너는 지금 너한테 뭐라고 말할까?";
    } else if ($anchor === 'S') {
        $choices[] = "관심사 두 개를 ‘+’로 연결해볼까?";
    } else if ($anchor === 'R') {
        $choices[] = "방금 상황을 ‘한 줄 데이터’로 적어볼래?";
    } else {
        $choices[] = "다른 길(다른 방법)도 한 번 열어볼까?";
    }

    return array_slice($choices, 0, 3);
}



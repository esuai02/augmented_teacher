<?php
/**
 * Agent 04: 활동유형
 * 현재 학습 활동
 */

function get_agent_04_config() {
    return [
        'id' => 4,
        'title' => '활동유형',
        'description' => '현재 학습 활동',
        'icon' => '📚',
        'color' => '#10b981',
        'inputs' => ['활동 목록'],
        'outputs' => ['선택된 활동', '활동 특성', '필요 자원']
    ];
}

function process_agent_04($data) {
    return [
        'inputs' => ['활동 목록' => '개념이해, 유형학습, 문제풀이'],
        'processing' => '활동 분석 완료',
        'outputs' => ['선택된 활동' => $data['activity'] ?? '개념이해', '활동 특성' => '분석됨', '필요 자원' => '확인됨'],
        'insights' => '최적 활동 선택',
        'nextStepRecommendation' => '지도모드 설정'
    ];
}

function render_agent_04($step, $data) { return ''; }

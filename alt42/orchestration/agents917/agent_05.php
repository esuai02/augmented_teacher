<?php
/**
 * Agent 05: 지도모드
 * 학습 지도 방식
 */

function get_agent_05_config() {
    return [
        'id' => 5,
        'title' => '지도모드',
        'description' => '학습 지도 방식',
        'icon' => '🧭',
        'color' => '#6366f1',
        'inputs' => ['학습자 특성', '활동 유형'],
        'outputs' => ['선택된 모드', '모드 전략', '예상 효과']
    ];
}

function process_agent_05($data) {
    return [
        'inputs' => ['학습자 특성' => 'INTJ', '활동 유형' => $data['activity'] ?? '개념이해'],
        'processing' => '지도모드 설정 완료',
        'outputs' => ['선택된 모드' => '맞춤학습', '모드 전략' => '개인화', '예상 효과' => '높음'],
        'insights' => '최적 지도 방식 선택',
        'nextStepRecommendation' => '목표 분석'
    ];
}

function render_agent_05($step, $data) { return ''; }

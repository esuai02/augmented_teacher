<?php
/**
 * Agent 02: 문제 발견
 * 학습 문제점 식별
 */

// Agent configuration
function get_agent_02_config() {
    return [
        'id' => 2,
        'title' => '문제 발견',
        'description' => '학습 문제점 식별',
        'icon' => '🔍',
        'color' => '#ef4444',
        'inputs' => ['학습 상황', '어려움 설명', '구체적 문제'],
        'outputs' => ['문제 정의', '우선순위', '긴급도']
    ];
}

// Process agent logic
function process_agent_02($data) {
    global $DB, $USER;
    
    $result = [
        'inputs' => [
            '학습 상황' => $data['situation'] ?? '현재 학습 상황',
            '어려움 설명' => $data['difficulty'] ?? '학습 어려움',
            '구체적 문제' => $data['problem'] ?? '구체적 문제'
        ],
        'processing' => '문제 분석 및 식별 완료',
        'outputs' => [
            '문제 정의' => '학습 문제 정의됨',
            '우선순위' => '높음',
            '긴급도' => '중간'
        ],
        'insights' => '주요 학습 장애 요인 파악',
        'nextStepRecommendation' => '상황유형 분석 필요'
    ];
    
    return $result;
}

// Render agent UI component
function render_agent_02($step, $data) {
    $html = '';
    // Problem input UI is handled in main index.php
    return $html;
}
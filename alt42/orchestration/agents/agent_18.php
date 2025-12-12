<?php
/**
 * Agent 18: 상호작용 컨텐츠 생성
 * 맞춤형 컨텐츠
 */

function get_agent_18_config() {
    return [
        'id' => 18,
        'title' => '상호작용 컨텐츠 생성',
        'description' => '지금까지 분석내용을 종합하여 맞춤형 상호작용 컨텐츠를 생성',
        'icon' => '✨',
        'color' => '#8b5cf6',
        'inputs' => ['전체 분석 데이터', '학습자 특성', '문제 상황'],
        'outputs' => ['상호작용 유형', '맞춤형 컨텐츠', '실행 시나리오']
    ];
}

function process_agent_18($data) {
    return [
        'inputs' => ['전체 분석 데이터' => '종합됨', '학습자 특성' => 'INTJ', '문제 상황' => '응용력 부족'],
        'processing' => '맞춤형 컨텐츠 생성 완료',
        'outputs' => ['상호작용 유형' => '대화형', '맞춤형 컨텐츠' => '생성됨', '실행 시나리오' => '준비됨'],
        'insights' => '개인화된 학습 컨텐츠 준비',
        'nextStepRecommendation' => '개입 준비'
    ];
}

function render_agent_18($step, $data) { return ''; }

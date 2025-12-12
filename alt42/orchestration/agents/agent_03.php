<?php
/**
 * Agent 03: 상황유형
 * 시험 일정 맥락
 */

function get_agent_03_config() {
    return [
        'id' => 3,
        'title' => '상황유형',
        'description' => '시험 일정 맥락',
        'icon' => '📅',
        'color' => '#ec4899',
        'inputs' => ['시험 일정', '현재 날짜'],
        'outputs' => ['학습 맥락', '긴급도', '집중 영역']
    ];
}

function process_agent_03($data) {
    return [
        'inputs' => ['시험 일정' => $data['exam'] ?? '', '현재 날짜' => date('Y-m-d')],
        'processing' => '상황 분석 완료',
        'outputs' => ['학습 맥락' => '분석됨', '긴급도' => '중간', '집중 영역' => '확인됨'],
        'insights' => '시험 준비 상황 파악',
        'nextStepRecommendation' => '활동유형 선택'
    ];
}

function render_agent_03($step, $data) { return ''; }

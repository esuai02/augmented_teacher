<?php
/**
 * Agent 01: 온보딩 정보 (Onboarding Information)
 * 기본정보 로드 완료
 */

// Agent configuration
function get_agent_01_config() {
    return [
        'id' => 1,
        'title' => '온보딩 정보',
        'description' => '기본정보 로드 완료',
        'icon' => '👤',
        'color' => '#3b82f6',
        'inputs' => ['학생 ID', '학년/반', '기존 데이터', 'MBTI'],
        'outputs' => ['프로필 정보', '학습 이력', '선호도 설정', '성격 유형']
    ];
}

// Process agent logic
function process_agent_01($data) {
    global $DB, $USER;
    
    $result = [
        'inputs' => [
            '학생 ID' => $data['studentId'] ?? 'S2024001',
            '학년/반' => $data['class'] ?? '중2-3반',
            '기존 데이터' => '로드됨',
            'MBTI' => $data['mbti'] ?? 'INTJ'
        ],
        'processing' => '학생 프로필 정보가 로드되었습니다',
        'outputs' => [
            '프로필 정보' => '완료',
            '학습 이력' => '분석됨',
            '선호도 설정' => '확인됨',
            '성격 유형' => $data['mbti'] ?? 'INTJ'
        ],
        'insights' => '기존 학습 패턴 파악 완료',
        'nextStepRecommendation' => '문제 발견 단계로 진행'
    ];
    
    return $result;
}

// Render agent UI component
function render_agent_01($step, $data) {
    $html = '';
    
    if (isset($step['requiresUserInput']) && $step['requiresUserInput']) {
        $html .= '<div class="agent-01-input">';
        $html .= '<input type="text" placeholder="추가 정보 입력" class="additional-info" />';
        $html .= '</div>';
    }
    
    return $html;
}
<?php
/**
 * dataindex_common.php - 공통 에이전트 드롭다운 네비게이션 컴포넌트
 *
 * 이 파일은 각 에이전트 폴더의 dataindex.php에서 include하여 사용
 *
 * 사용법:
 *   $currentAgentId = 'agent01_onboarding';  // 현재 에이전트 ID 설정
 *   include_once(__DIR__ . '/../agent_orchestration/dataindex_common.php');
 */

// 22개 에이전트 목록
$allAgents = [
    'agent01_onboarding' => 'Agent 01 - Onboarding',
    'agent02_exam_schedule' => 'Agent 02 - Exam Schedule',
    'agent03_goals_analysis' => 'Agent 03 - Goals Analysis',
    'agent04_inspect_weakpoints' => 'Agent 04 - Inspect Weakpoints',
    'agent05_learning_emotion' => 'Agent 05 - Learning Emotion',
    'agent06_teacher_feedback' => 'Agent 06 - Teacher Feedback',
    'agent07_interaction_targeting' => 'Agent 07 - Interaction Targeting',
    'agent08_calmness' => 'Agent 08 - Calmness',
    'agent09_learning_management' => 'Agent 09 - Learning Management',
    'agent10_concept_notes' => 'Agent 10 - Concept Notes',
    'agent11_problem_notes' => 'Agent 11 - Problem Notes',
    'agent12_rest_routine' => 'Agent 12 - Rest Routine',
    'agent13_learning_dropout' => 'Agent 13 - Learning Dropout',
    'agent14_current_position' => 'Agent 14 - Current Position',
    'agent15_problem_redefinition' => 'Agent 15 - Problem Redefinition',
    'agent16_interaction_preparation' => 'Agent 16 - Interaction Preparation',
    'agent17_remaining_activities' => 'Agent 17 - Remaining Activities',
    'agent18_signature_routine' => 'Agent 18 - Signature Routine',
    'agent19_interaction_content' => 'Agent 19 - Interaction Content',
    'agent20_intervention_preparation' => 'Agent 20 - Intervention Preparation',
    'agent21_intervention_execution' => 'Agent 21 - Intervention Execution',
    'agent22_module_improvement' => 'Agent 22 - Module Improvement'
];

/**
 * 에이전트 드롭다운 메뉴 HTML 생성
 *
 * @param string $currentAgentId 현재 선택된 에이전트 ID
 * @return string HTML 문자열
 */
function renderAgentDropdown($currentAgentId) {
    global $allAgents;

    $html = '<select id="agentSelector" onchange="changeAgent()" style="padding: 4px 8px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.875rem; cursor: pointer; min-width: 200px;">';

    foreach ($allAgents as $agentId => $agentName) {
        $selected = ($currentAgentId === $agentId) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($agentId) . '" ' . $selected . '>' . htmlspecialchars($agentName) . '</option>';
    }

    $html .= '</select>';

    return $html;
}

/**
 * 에이전트 변경 JavaScript 함수 (분리된 파일용)
 *
 * @return string JavaScript 코드
 */
function getAgentChangeScript() {
    return <<<'JS'
function changeAgent() {
    const agentSelector = document.getElementById('agentSelector');
    const selectedAgent = agentSelector.value;
    // 해당 에이전트 폴더의 dataindex.php로 이동
    window.location.href = '../' + selectedAgent + '/dataindex.php';
}
JS;
}

/**
 * 서버 기본 URL 반환
 */
function getBaseUrl() {
    return 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration13/agents/';
}

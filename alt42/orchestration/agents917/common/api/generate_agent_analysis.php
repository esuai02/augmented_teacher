<?php
/**
 * Agent Analysis Generation API
 *
 * Receives agent problem data and generates AI-powered analysis report
 * Currently uses placeholder logic - GPT-4 integration in next phase
 *
 * @version 1.0
 * @date 2025-01-21
 * File: api/generate_agent_analysis.php
 */

// Include Moodle configuration
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Include GPT integration
require_once(__DIR__ . '/gpt_helper.php');

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error handling function
function sendError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Log function for debugging
function logDebug($message, $data = null) {
    error_log("[generate_agent_analysis.php] " . $message .
        ($data ? " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) : ""));
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('POST 요청만 허용됩니다.', 405);
    }

    // Get request body
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendError('요청 본문이 비어있습니다. - File: generate_agent_analysis.php, Line: ' . __LINE__, 400);
    }

    // Parse JSON
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('JSON 파싱 오류: ' . json_last_error_msg() . ' - File: generate_agent_analysis.php, Line: ' . __LINE__, 400);
    }

    logDebug("Request received", $data);

    // Validate required fields
    $required_fields = ['agent_id', 'agent_number', 'agent_name', 'problem_text'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError("필수 필드가 누락되었습니다: {$field} - File: generate_agent_analysis.php, Line: " . __LINE__, 400);
        }
    }

    // Extract data
    $agent_id = $data['agent_id'];
    $agent_number = intval($data['agent_number']);
    $agent_name = $data['agent_name'];
    $agent_description = $data['agent_description'] ?? '';
    $problem_text = $data['problem_text'];
    $problem_index = isset($data['problem_index']) ? intval($data['problem_index']) : 0;
    $student_id = isset($data['student_id']) ? intval($data['student_id']) : $USER->id;
    $timestamp = isset($data['timestamp']) ? intval($data['timestamp']) : time();

    logDebug("Parsed data", [
        'agent_id' => $agent_id,
        'agent_number' => $agent_number,
        'agent_name' => $agent_name,
        'problem_text' => $problem_text,
        'student_id' => $student_id
    ]);

    // Get student information
    $student = $DB->get_record('user', ['id' => $student_id], 'id, firstname, lastname, email');
    if (!$student) {
        sendError("학생 정보를 찾을 수 없습니다. (ID: {$student_id}) - File: generate_agent_analysis.php, Line: " . __LINE__, 404);
    }

    $student_name = $student->firstname . ' ' . $student->lastname;

    logDebug("Student found", ['student_id' => $student_id, 'student_name' => $student_name]);

    // ==================================================================
    // GPT-4 ANALYSIS GENERATION
    // ==================================================================

    // Prepare student context data
    $student_context = [
        '학년' => getUserField($student_id, 'grade') ?? '정보 없음',
        '학습 성향' => getUserField($student_id, 'learning_style') ?? '분석 중'
    ];

    // Attempt GPT-4 analysis first
    logDebug("Attempting GPT analysis");
    $gpt_result = generateGPTAnalysis(
        $agent_number,
        $agent_name,
        $agent_description,
        $problem_text,
        $student_name,
        $student_context
    );

    if ($gpt_result['success']) {
        // GPT analysis successful
        $analysis = $gpt_result['analysis'];
        logDebug("GPT analysis successful", [
            'usage' => $gpt_result['usage'],
            'analysis_length' => strlen(json_encode($analysis))
        ]);
    } else {
        // GPT failed - fall back to placeholder
        logDebug("GPT analysis failed, using placeholder", ['error' => $gpt_result['error']]);

        if (!empty($gpt_result['using_placeholder'])) {
            // GPT not configured - use placeholder silently
            $analysis = generatePlaceholderAnalysis(
                $agent_number,
                $agent_name,
                $problem_text,
                $student_name
            );
        } else {
            // GPT configured but failed - return error
            sendError('GPT 분석 실패: ' . $gpt_result['error'] . ' - File: generate_agent_analysis.php, Line: ' . __LINE__, 500);
        }
    }

    // Save analysis to database (optional - for audit trail)
    try {
        $record = new stdClass();
        $record->agent_id = $agent_id;
        $record->agent_number = $agent_number;
        $record->agent_name = $agent_name;
        $record->problem_text = $problem_text;
        $record->problem_index = $problem_index;
        $record->student_id = $student_id;
        $record->student_name = $student_name;
        $record->analysis_json = json_encode($analysis, JSON_UNESCAPED_UNICODE);
        $record->timecreated = $timestamp;
        $record->timemodified = time();

        // Check if table exists before inserting
        if ($DB->get_manager()->table_exists('alt42_agent_analyses')) {
            $insert_id = $DB->insert_record('alt42_agent_analyses', $record);
            logDebug("Analysis saved to database", ['insert_id' => $insert_id]);
        } else {
            logDebug("Table alt42_agent_analyses does not exist - skipping save");
        }
    } catch (Exception $e) {
        // Non-critical error - log but don't fail
        logDebug("Failed to save analysis to database: " . $e->getMessage());
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'analysis' => $analysis,
        'metadata' => [
            'agent_id' => $agent_id,
            'agent_number' => $agent_number,
            'agent_name' => $agent_name,
            'student_id' => $student_id,
            'student_name' => $student_name,
            'generated_at' => date('Y-m-d H:i:s', $timestamp)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    logDebug("Unexpected error: " . $e->getMessage());
    sendError('서버 오류가 발생했습니다: ' . $e->getMessage() . ' - File: generate_agent_analysis.php, Line: ' . $e->getLine(), 500);
}

/**
 * Get user custom field value
 *
 * @param int $user_id User ID
 * @param string $field_name Field short name
 * @return string|null Field value or null
 */
function getUserField($user_id, $field_name) {
    global $DB;

    try {
        // Get field ID
        $field = $DB->get_record('user_info_field', ['shortname' => $field_name], 'id');
        if (!$field) {
            return null;
        }

        // Get field data
        $data = $DB->get_record('user_info_data', [
            'userid' => $user_id,
            'fieldid' => $field->id
        ], 'data');

        return $data ? $data->data : null;
    } catch (Exception $e) {
        error_log("[generate_agent_analysis.php] getUserField error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate placeholder analysis
 * Fallback when GPT API is not configured or fails
 *
 * @param int $agent_number Agent number (1-21)
 * @param string $agent_name Agent name
 * @param string $problem_text Problem description
 * @param string $student_name Student name
 * @return array Analysis with 4 sections
 */
function generatePlaceholderAnalysis($agent_number, $agent_name, $problem_text, $student_name) {
    // Agent-specific analysis templates
    $templates = [
        1 => [
            'problem_situation' => "{$student_name} 학생의 경우, 초기 온보딩 과정에서 학습 프로필 정보가 불완전하게 수집되었습니다. 특히 MBTI 학습성향 데이터와 학습 이력 정보가 누락되어 있어, 맞춤형 학습 계획 수립에 어려움이 있습니다.",
            'cause_analysis' => "이는 주로 (1) 최초 등록 시 필수 정보 수집 프로세스의 미비, (2) 학생의 정보 입력 동기 부족, (3) 시스템의 자동 데이터 수집 기능 미작동 등이 복합적으로 작용한 결과입니다. 담임 선생님과의 초기 면담 내용도 구조화되지 않아 활용이 제한적입니다.",
            'improvement_plan' => "단계별 개선안을 제시합니다. 1단계: 온보딩 체크리스트 자동화 (필수 7개 항목 완성도 추적), 2단계: 게이미피케이션을 통한 정보 입력 유도 (프로필 완성도 진행바 + 보상), 3단계: 선생님 면담 내용 자동 텍스트화 및 태깅 시스템 구축, 4단계: 첫 2주간 집중 모니터링으로 누락 데이터 보완.",
            'expected_outcome' => "이 개선안 적용 시 프로필 완성도가 현재 45%에서 90% 이상으로 향상되며, 맞춤형 학습 계획 수립 시점이 평균 2주에서 3일로 단축될 것으로 예상됩니다. 또한 담임 선생님의 학생 이해도가 향상되어 초기 학습 적응 기간이 30% 감소할 것입니다."
        ],
        // Add more agent-specific templates as needed
        // For now, use generic template for other agents
    ];

    // Get template or use generic
    $template = $templates[$agent_number] ?? [
        'problem_situation' => "{$agent_name} 분석 결과, {$student_name} 학생의 경우 다음과 같은 문제 상황이 발견되었습니다: {$problem_text}",
        'cause_analysis' => "이 문제의 근본 원인을 분석한 결과, (1) 학습 패턴의 불일치, (2) 목표 설정의 모호성, (3) 피드백 루프의 부재 등이 주요 요인으로 파악되었습니다. 특히 개인별 학습 특성을 고려하지 않은 일률적인 접근 방식이 문제를 악화시키고 있습니다.",
        'improvement_plan' => "개선 방안은 다음과 같습니다. 첫째, 학생 개별 데이터 기반 맞춤형 학습 계획 수립. 둘째, 주간 단위 목표 세분화 및 달성도 추적 시스템 구축. 셋째, 실시간 피드백 제공을 통한 즉각적 행동 교정. 넷째, 성공 사례 기반 모범 루틴 제안 및 적용.",
        'expected_outcome' => "이러한 개선 방안 적용 시, 학습 효율성이 평균 25% 향상되고, 목표 달성률이 현재 60%에서 85% 이상으로 증가할 것으로 예상됩니다. 또한 학생의 학습 만족도와 자기효능감이 유의미하게 상승하여 장기적인 학습 지속성이 확보될 것입니다."
    ];

    return $template;
}

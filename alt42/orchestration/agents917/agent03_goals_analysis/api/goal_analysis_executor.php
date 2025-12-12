<?php
/**
 * Goal Analysis Executor API
 * File: api/goal_analysis_executor.php:1
 * Handles execution of goal analysis for 5 types
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=UTF-8');

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';
$type = $input['type'] ?? $_POST['type'] ?? '';
$userid = $input['userid'] ?? $_POST['userid'] ?? $USER->id;

// Response template
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'error' => null
];

try {
    switch ($action) {
        case 'execute':
            $response = executeGoalAnalysis($type, $userid, $DB);
            break;

        case 'get_latest':
            $response = getLatestAnalysis($type, $userid, $DB);
            break;

        case 'get_history':
            $response = getAnalysisHistory($type, $userid, $DB);
            break;

        default:
            throw new Exception("Invalid action: {$action}. File: api/goal_analysis_executor.php:42");
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Goal Analysis Error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Execute goal analysis based on type
 * @param string $type - quarter|weekly|today|pomodoro|curriculum
 * @param int $userid - User ID
 * @param object $DB - Moodle DB object
 * @return array Response array
 */
function executeGoalAnalysis($type, $userid, $DB) {
    // Validate type
    $validTypes = ['quarter', 'weekly', 'today', 'pomodoro', 'curriculum'];
    if (!in_array($type, $validTypes)) {
        throw new Exception("Invalid type: {$type}. File: api/goal_analysis_executor.php:71");
    }

    // 1. Collect raw data based on type
    $rawData = collectDataByType($type, $userid, $DB);

    // 2. Generate GPT prompt
    $prompt = generatePromptByType($type, $rawData);

    // 3. Analyze with GPT (mock for now - replace with actual GPT call)
    $analysisResult = analyzeWithGPT($prompt, $type);

    // 4. Calculate statistics
    $statistics = calculateStatistics($rawData, $type);

    // 5. Calculate effectiveness score
    $effectivenessScore = calculateEffectiveness($rawData, $analysisResult, $type);

    // 6. Save to database (using analysis_type field for goal type)
    $record = new stdClass();
    $record->userid = $userid;
    $record->analysis_type = $type; // 기존 analysis_type 필드 사용
    $record->raw_data = json_encode($rawData, JSON_UNESCAPED_UNICODE);
    $record->gpt_prompt = $prompt;
    $record->analysis_result = $analysisResult;
    $record->statistics = json_encode($statistics, JSON_UNESCAPED_UNICODE);
    $record->effectiveness_score = $effectivenessScore;
    $record->timecreated = time();

    $id = $DB->insert_record('alt42g_goal_analysis', $record);

    return [
        'success' => true,
        'message' => "목표 분석 완료: {$type}",
        'data' => [
            'id' => $id,
            'type' => $type,
            'analysis' => $analysisResult,
            'statistics' => $statistics,
            'score' => $effectivenessScore
        ]
    ];
}

/**
 * Collect data based on analysis type
 */
function collectDataByType($type, $userid, $DB) {
    $data = [];

    switch ($type) {
        case 'quarter':
            // 분기목표: 3개월 학습 데이터
            $data['period'] = '3 months';
            $data['goals'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_student_goals}
                 WHERE userid = ? AND goal_type = 'quarter'
                 ORDER BY timecreated DESC LIMIT 10",
                [$userid]
            );
            $data['sessions'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_learning_sessions}
                 WHERE userid = ? AND timecreated > ?
                 ORDER BY timecreated DESC",
                [$userid, time() - (90 * 86400)]
            );
            break;

        case 'weekly':
            // 주간목표: 1주일 학습 데이터
            $data['period'] = '1 week';
            $data['goals'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_student_goals}
                 WHERE userid = ? AND goal_type = 'weekly'
                 ORDER BY timecreated DESC LIMIT 4",
                [$userid]
            );
            $data['sessions'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_learning_sessions}
                 WHERE userid = ? AND timecreated > ?
                 ORDER BY timecreated DESC",
                [$userid, time() - (7 * 86400)]
            );
            break;

        case 'today':
            // 오늘목표: 당일 학습 데이터
            $data['period'] = '1 day';
            $data['goals'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_student_goals}
                 WHERE userid = ? AND goal_type = 'daily'
                 ORDER BY timecreated DESC LIMIT 1",
                [$userid]
            );
            $data['sessions'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_learning_sessions}
                 WHERE userid = ? AND DATE(FROM_UNIXTIME(timecreated)) = CURDATE()
                 ORDER BY timecreated DESC",
                [$userid]
            );
            break;

        case 'pomodoro':
            // 포모도르: 최근 포모도르 세션
            $data['period'] = 'recent pomodoro';
            $data['sessions'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_pomodoro_sessions}
                 WHERE userid = ?
                 ORDER BY timecreated DESC LIMIT 20",
                [$userid]
            );
            break;

        case 'curriculum':
            // 커리큘럼: 전체 커리큘럼 진행도
            $data['period'] = 'curriculum progress';
            $data['progress'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_curriculum_progress}
                 WHERE userid = ?
                 ORDER BY timecreated DESC",
                [$userid]
            );
            $data['completed'] = $DB->get_records_sql(
                "SELECT * FROM {alt42g_completed_units}
                 WHERE userid = ?
                 ORDER BY timecreated DESC LIMIT 50",
                [$userid]
            );
            break;
    }

    return $data;
}

/**
 * Generate GPT prompt based on type
 */
function generatePromptByType($type, $rawData) {
    $prompts = [
        'quarter' => "분기 학습 목표 분석:\n\n목표 달성률, 학습 패턴, 개선 방안을 분석해주세요.",
        'weekly' => "주간 학습 목표 분석:\n\n이번 주 목표 달성도와 다음 주 계획을 제시해주세요.",
        'today' => "오늘의 학습 목표 분석:\n\n오늘의 학습 진행 상황과 남은 시간 활용 방안을 알려주세요.",
        'pomodoro' => "포모도르 세션 분석:\n\n집중도, 생산성, 휴식 패턴을 분석해주세요.",
        'curriculum' => "커리큘럼 진행도 분석:\n\n전체 진도율, 취약 영역, 우선순위를 파악해주세요."
    ];

    $prompt = $prompts[$type] ?? '';
    $prompt .= "\n\n데이터:\n" . json_encode($rawData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    return $prompt;
}

/**
 * Analyze with GPT (mock implementation - replace with actual API call)
 */
function analyzeWithGPT($prompt, $type) {
    // TODO: Replace with actual GPT API call
    // For now, return mock analysis

    $mockResults = [
        'quarter' => "분기 목표 분석 결과:\n- 목표 달성률: 75%\n- 강점: 꾸준한 학습 습관\n- 개선점: 오답 복습 강화 필요",
        'weekly' => "주간 목표 분석 결과:\n- 이번 주 달성률: 80%\n- 다음 주 권장 학습량: 주 15시간",
        'today' => "오늘 학습 분석 결과:\n- 현재 진행률: 60%\n- 남은 목표: 2개 유형 학습\n- 예상 소요 시간: 1.5시간",
        'pomodoro' => "포모도르 분석 결과:\n- 평균 집중 시간: 23분\n- 생산성 점수: 85/100\n- 권장 휴식 패턴: 5분 휴식 권장",
        'curriculum' => "커리큘럼 분석 결과:\n- 전체 진도율: 65%\n- 취약 영역: 2단원 함수\n- 우선 학습 권장: 3단원 시작 전 복습"
    ];

    return $mockResults[$type] ?? "분석 결과 없음";
}

/**
 * Calculate statistics
 */
function calculateStatistics($rawData, $type) {
    return [
        'type' => $type,
        'data_count' => count($rawData),
        'analysis_time' => date('Y-m-d H:i:s'),
        'period' => $rawData['period'] ?? 'unknown'
    ];
}

/**
 * Calculate effectiveness score (0-100)
 */
function calculateEffectiveness($rawData, $analysisResult, $type) {
    // Simple heuristic - replace with actual calculation
    $baseScore = 70;
    $dataBonus = min(count($rawData) * 2, 20);
    $typeBonus = ['quarter' => 5, 'weekly' => 3, 'today' => 2, 'pomodoro' => 4, 'curriculum' => 5][$type] ?? 0;

    return min($baseScore + $dataBonus + $typeBonus, 100);
}

/**
 * Get latest analysis by type
 */
function getLatestAnalysis($type, $userid, $DB) {
    $record = $DB->get_record_sql(
        "SELECT * FROM {alt42g_goal_analysis}
         WHERE userid = ? AND analysis_type = ?
         ORDER BY timecreated DESC LIMIT 1",
        [$userid, $type]
    );

    if (!$record) {
        return [
            'success' => false,
            'message' => '저장된 분석 결과가 없습니다.'
        ];
    }

    return [
        'success' => true,
        'data' => [
            'id' => $record->id,
            'type' => $record->analysis_type,
            'analysis' => $record->analysis_result,
            'statistics' => json_decode($record->statistics, true),
            'score' => $record->effectiveness_score,
            'created' => date('Y-m-d H:i:s', $record->timecreated)
        ]
    ];
}

/**
 * Get analysis history by type
 */
function getAnalysisHistory($type, $userid, $DB) {
    $records = $DB->get_records_sql(
        "SELECT * FROM {alt42g_goal_analysis}
         WHERE userid = ? AND analysis_type = ?
         ORDER BY timecreated DESC LIMIT 10",
        [$userid, $type]
    );

    $history = [];
    foreach ($records as $record) {
        $history[] = [
            'id' => $record->id,
            'score' => $record->effectiveness_score,
            'created' => date('Y-m-d H:i:s', $record->timecreated)
        ];
    }

    return [
        'success' => true,
        'data' => $history
    ];
}
?>

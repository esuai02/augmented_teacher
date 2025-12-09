<?php
/**
 * Agent05 데이터 매핑 분석 도구
 * rules.yaml 데이터 | DB 존재 여부 | 데이터 타입 식별 | data_access.php 적용 여부 | 매핑 불일치 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent05_learning_emotion/rules/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 학생 ID 파라미터
$studentid = optional_param('studentid', 1603, PARAM_INT);

// 권한 체크
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    $studentid = $USER->id;
}

// rules.yaml 파일 읽기
$rulesYamlPath = __DIR__ . '/rules.yaml';
$rulesYamlContent = file_exists($rulesYamlPath) ? file_get_contents($rulesYamlPath) : '';

// rules.yaml에서 사용하는 필드 추출
$rulesFields = [];
if (!empty($rulesYamlContent)) {
    // field: 패턴으로 필드 추출
    preg_match_all('/field:\s*"([^"]+)"/', $rulesYamlContent, $matches);
    if (!empty($matches[1])) {
        $rulesFields = array_unique($matches[1]);
        sort($rulesFields);
    }
    
    // activity_type, emotion_type 등도 추출
    preg_match_all('/activity_type|emotion_type|persona_type|concentration_level|stress_level|anxiety_level|fatigue_level|engagement_state|emotion_intensity|emotion_duration|emotion_trend|emotion_state|emotion_pattern|emotion_history|emotion_trigger|pause_duration|pause_count|question_hesitation_time|hesitation_duration|learning_pattern_duration|pattern_stability|emotional_volatility|cognitive_fatigue_level|motivation_level|exam_days_remaining|readiness_level|previous_unit_performance|current_unit|problem_difficulty|time_pressure|remaining_time|solving_stage|academy_context|academy_class_understanding|academy_friend_comparison|academy_homework_burden|persona_history_count|activity_persona_mapping|emotion_pattern_repetition|achievement_rate|flow_state|physiological_signs|question_avoidance_behavior|avoidance_behavior|entry_resistance|tension_level|confidence_level|success_rate|achievement_satisfaction|daily_learning_summary|emotion_trigger_event|trigger_frequency|emotion_history_count|pattern_consistency|pause_pattern|hesitation_pattern|unit_relationship|error_type|problem_type|facial_expression|eye_contact_pattern|hesitation_gesture|movement_speed|emotional_weight|focus_stability|interest_level|concept_survey_response|type_survey_response|problem_survey_response|error_survey_response|qa_survey_response|review_survey_response|pomodoro_survey_response|home_check_survey_response|activity_status/i', $rulesYamlContent, $additionalMatches);
    if (!empty($additionalMatches[0])) {
        $rulesFields = array_merge($rulesFields, $additionalMatches[0]);
        $rulesFields = array_unique($rulesFields);
        sort($rulesFields);
    }
}

// data_access.php에서 사용하는 필드 추출
$dataAccessPath = __DIR__ . '/data_access.php';
$dataAccessContent = file_exists($dataAccessPath) ? file_get_contents($dataAccessPath) : '';

$dataAccessFields = [];
if (!empty($dataAccessContent)) {
    // $context['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$context\[\'([^\']+)\'\]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_unique($matches[1]);
        sort($dataAccessFields);
    }
    
    // $emotion->필드명 패턴으로 필드 추출
    preg_match_all('/\$emotion->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    // DB 테이블 필드명 추출
    preg_match_all('/->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

// view_reports.php 또는 관련 파일에서 사용하는 데이터 필드 추출
$viewReportsPath = __DIR__ . '/../../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
if (!file_exists($viewReportsPath)) {
    $viewReportsPath = __DIR__ . '/../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
}

$viewReportsFields = [];
$viewReportsTables = [];

if (file_exists($viewReportsPath)) {
    $viewReportsContent = file_get_contents($viewReportsPath);
    
    // 테이블명 추출
    preg_match_all('/\{([a-z_]+)\}/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_unique($matches[1]);
    }
    
    // $data['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$data\[\'([^\']+)\'\]/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // 설문 응답 필드
    preg_match_all('/\'([a-z_]+)\'\s*=>/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    $viewReportsFields = array_unique($viewReportsFields);
    sort($viewReportsFields);
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];

// mdl_learning_emotions 테이블 구조 확인 (TODO로 언급됨)
if ($DB->get_manager()->table_exists(new xmldb_table('mdl_learning_emotions'))) {
    $dbTables[] = 'mdl_learning_emotions';
    try {
        $columns = $DB->get_columns('mdl_learning_emotions');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'mdl_learning_emotions.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from mdl_learning_emotions: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_user_info_data 테이블 (fieldid='22' 역할 정보)
if ($DB->get_manager()->table_exists(new xmldb_table('mdl_user_info_data'))) {
    $dbTables[] = 'mdl_user_info_data';
    try {
        $columns = $DB->get_columns('mdl_user_info_data');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'mdl_user_info_data.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from mdl_user_info_data: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_alt42_calmness 테이블 (침착도 데이터)
if ($DB->get_manager()->table_exists(new xmldb_table('alt42_calmness'))) {
    $dbTables[] = 'alt42_calmness';
    try {
        $columns = $DB->get_columns('alt42_calmness');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'alt42_calmness.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from alt42_calmness: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// data_access.php에서 실제 사용 여부 확인 함수
function checkDataAccessUsage($fieldName, $dataAccessContent) {
    if (empty($dataAccessContent)) {
        return false;
    }
    
    // 필드명 직접 사용
    if (strpos($dataAccessContent, "'" . $fieldName . "'") !== false) {
        return true;
    }
    if (strpos($dataAccessContent, '"' . $fieldName . '"') !== false) {
        return true;
    }
    if (strpos($dataAccessContent, '$' . $fieldName) !== false) {
        return true;
    }
    
    // 배열 접근 패턴
    if (strpos($dataAccessContent, "['" . $fieldName . "']") !== false) {
        return true;
    }
    if (strpos($dataAccessContent, '["' . $fieldName . '"]') !== false) {
        return true;
    }
    
    // $context['필드명'] 패턴
    if (strpos($dataAccessContent, "\$context['" . $fieldName . "']") !== false) {
        return true;
    }
    
    // $emotion->필드명 패턴 (agent05 특화)
    if (strpos($dataAccessContent, "\$emotion->" . $fieldName) !== false) {
        return true;
    }
    
    return false;
}

// 실제 DB 데이터 존재 여부 확인 (rules.yaml 필드 기준)
$dbDataExists = [];
$dbDataSample = [];

foreach ($rulesFields as $field) {
    $exists = false;
    $tableName = '';
    $sampleValue = null;
    
    // mdl_learning_emotions 테이블 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('mdl_learning_emotions'))) {
        try {
            $columns = $DB->get_columns('mdl_learning_emotions');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('mdl_learning_emotions', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'mdl_learning_emotions';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking mdl_learning_emotions: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // alt42_calmness 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('alt42_calmness'))) {
        try {
            $columns = $DB->get_columns('alt42_calmness');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('alt42_calmness', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'alt42_calmness';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking alt42_calmness: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    if ($exists) {
        $dbDataExists[] = [
            'field' => $field,
            'table' => $tableName,
            'type' => classifyDataType($field, $tableName),
            'sample' => $sampleValue
        ];
    }
}

// 데이터 타입 분류 함수
function classifyDataType($fieldName, $tableName = '') {
    // 설문 데이터 (survdata) - 사용자 입력
    $survFields = [
        'concept_survey_response', 'type_survey_response', 'problem_survey_response',
        'error_survey_response', 'qa_survey_response', 'review_survey_response',
        'pomodoro_survey_response', 'home_check_survey_response',
        'emotion_survey_response', 'persona_survey_response'
    ];
    
    // 시스템 데이터 (sysdata) - DB에서 자동 조회
    $sysFields = [
        'timecreated', 'timemodified', 'userid', 'user_id',
        'level', 'duration', 'timefinished', 'hide',
        'activity_type', 'activity_status', 'emotion_type',
        'concentration_level', 'focus_stability', 'engagement_state',
        'pause_duration', 'pause_count', 'question_hesitation_time',
        'hesitation_duration', 'learning_pattern_duration',
        'pattern_stability', 'emotional_volatility',
        'cognitive_fatigue_level', 'motivation_level',
        'exam_days_remaining', 'readiness_level',
        'previous_unit_performance', 'current_unit',
        'problem_difficulty', 'time_pressure', 'remaining_time',
        'solving_stage', 'academy_context',
        'academy_class_understanding', 'academy_friend_comparison',
        'academy_homework_burden', 'persona_history_count',
        'activity_persona_mapping', 'emotion_pattern_repetition',
        'achievement_rate', 'flow_state', 'physiological_signs',
        'question_avoidance_behavior', 'avoidance_behavior',
        'entry_resistance', 'tension_level', 'confidence_level',
        'success_rate', 'achievement_satisfaction',
        'daily_learning_summary', 'emotion_trigger_event',
        'trigger_frequency', 'emotion_history_count',
        'pattern_consistency', 'pause_pattern', 'hesitation_pattern',
        'unit_relationship', 'error_type', 'problem_type',
        'facial_expression', 'eye_contact_pattern', 'hesitation_gesture',
        'movement_speed', 'emotional_weight', 'interest_level',
        'stress_level', 'anxiety_level', 'fatigue_level',
        'emotion_intensity', 'emotion_duration', 'emotion_trend',
        'emotion_state', 'emotion_pattern', 'emotion_history',
        'emotion_trigger', 'persona_type'
    ];
    
    // 생성 데이터 (gendata) - AI/계산으로 생성
    $genFields = [
        'emotion_persona', 'persona_classification', 'emotion_analysis',
        'emotion_summary', 'emotion_prediction', 'emotion_mapping',
        'emotion_pattern_analysis', 'composite_persona_profile',
        'emotion_regulation_strategy', 'emotion_recovery_plan',
        'emotion_impact_prediction', 'signature_routine_pattern',
        'emotion_awareness', 'emotion_recovery_routine',
        'self_control_routine', 'emotion_flow_pattern',
        'emotion_transition_zone', 'emotion_persona_pattern',
        'feedback_acceptance_rate', 'recovery_time_avg',
        'preferred_interaction_channel', 'intervention_type_effectiveness',
        'emotion_self_regulation', 'emotion_impact_prediction',
        'anxiety_readiness_mapping', 'emotion_trigger_pattern',
        'emotion_pattern_prediction', 'unit_emotion_pattern',
        'difficulty_emotion_pattern', 'academy_emotion_pattern',
        'solving_process_emotion_timeline', 'time_pressure_emotion_pattern',
        'unit_transition_emotion_pattern', 'composite_persona_pattern',
        'problem_type_emotion_pattern', 'error_type_emotion_pattern',
        'achievement_fatigue_type', 'fatigue_type_classification',
        'daily_emotion_summary', 'growth_vs_exhaustion_type',
        'concentration_tension_balance', 'anxiety_avoidance_pattern',
        'meta_cognitive_feedback', 'emotional_inertia_type',
        'restart_scenario', 'concept_emotion_summary',
        'problem_emotion_summary', 'fatigue_emotion_summary',
        'qa_hesitation_summary', 'problem_type_emotion_mapping',
        'error_type_emotion_classification', 'unit_emotion_pattern_analysis',
        'difficulty_emotion_response_pattern', 'academy_emotion_factor_analysis',
        'solving_stage_emotion_analysis', 'time_pressure_emotion_classification',
        'unit_transition_emotion_pattern', 'composite_persona_analysis'
    ];
    
    if (in_array($fieldName, $survFields) || strpos($fieldName, 'survey') !== false || strpos($fieldName, 'response') !== false) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields) || strpos($tableName, 'calmness') !== false || 
              strpos($tableName, 'tracking') !== false || strpos($tableName, 'messages') !== false ||
              strpos($tableName, 'learning_emotions') !== false) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields) || strpos($fieldName, 'analysis') !== false || 
              strpos($fieldName, 'summary') !== false || strpos($fieldName, 'pattern') !== false ||
              strpos($fieldName, 'mapping') !== false || strpos($fieldName, 'classification') !== false) {
        return 'gendata';
    } else {
        return 'unknown';
    }
}

// 분석 결과 생성
$analysisResults = [];

// 1. rules.yaml에 있는데 data_access.php에 없는 필드 (실제 사용 여부 확인)
$inRulesNotInDataAccess = [];
foreach ($rulesFields as $field) {
    if (!checkDataAccessUsage($field, $dataAccessContent)) {
        $inRulesNotInDataAccess[] = $field;
    }
}

// 2. data_access.php에 있는데 rules.yaml에 없는 필드
$inDataAccessNotInRules = array_diff($dataAccessFields, $rulesFields);

// 3. DB에 있는데 rules.yaml에 사용하지 않는 데이터
$inDbNotInRules = [];
foreach ($dbFields as $dbField) {
    $fieldName = explode('.', $dbField)[1] ?? $dbField;
    if (!in_array($fieldName, $rulesFields)) {
        $inDbNotInRules[] = $dbField;
    }
}

// 4. view_reports.php에서 사용하는데 rules.yaml에 없는 필드
$inViewReportsNotInRules = array_diff($viewReportsFields, $rulesFields);

// 5. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
$similarFields = [
    ['calmness', 'concentration_level'],
    ['emotion', 'emotion_type'],
    ['stress', 'stress_level'],
    ['anxiety', 'anxiety_level'],
    ['fatigue', 'fatigue_level'],
    ['persona', 'persona_type'],
    ['engagement', 'engagement_state'],
    ['pause', 'pause_duration'],
    ['hesitation', 'hesitation_duration'],
    ['pattern', 'emotion_pattern']
];

foreach ($similarFields as $pair) {
    $field1 = $pair[0];
    $field2 = $pair[1];
    $inViewReports = in_array($field1, $viewReportsFields);
    $inRules = in_array($field2, $rulesFields);
    
    if ($inViewReports && $inRules) {
        $mappingMismatches[] = [
            'view_reports_field' => $field1,
            'rules_field' => $field2,
            'type' => 'similar_concept'
        ];
    }
}

// 6. 데이터 타입별 분류
$rulesFieldsByType = [
    'survdata' => [],
    'sysdata' => [],
    'gendata' => [],
    'unknown' => []
];

foreach ($rulesFields as $field) {
    $type = classifyDataType($field);
    $rulesFieldsByType[$type][] = $field;
}

$dataAccessFieldsByType = [
    'survdata' => [],
    'sysdata' => [],
    'gendata' => [],
    'unknown' => []
];

foreach ($dataAccessFields as $field) {
    $type = classifyDataType($field);
    $dataAccessFieldsByType[$type][] = $field;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent05 데이터 매핑 분석</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f3f4f6;
            padding: 2rem;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1f2937;
        }
        
        .section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .badge-surv {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-sys {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-gen {
            background: #cfe2ff;
            color: #084298;
        }
        
        .badge-rule {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .field-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 0.5rem;
        }
        
        .field-item {
            padding: 6px 12px;
            background: #f3f4f6;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #374151;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .type-section {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .type-section h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>📊 Agent05 데이터 매핑 분석 리포트</h1>
            <p>rules.yaml 데이터 | DB 존재 여부 | 데이터 타입 식별 | data_access.php 적용 여부 | 매핑 불일치 분석</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">학생 ID: <?php echo $studentid; ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Rules.yaml 필드</h3>
                <div class="number"><?php echo count($rulesFields); ?></div>
            </div>
            <div class="stat-card">
                <h3>Data Access 필드</h3>
                <div class="number"><?php echo count($dataAccessFields); ?></div>
            </div>
            <div class="stat-card">
                <h3>View Reports 필드</h3>
                <div class="number"><?php echo count($viewReportsFields); ?></div>
            </div>
            <div class="stat-card">
                <h3>DB 테이블</h3>
                <div class="number"><?php echo count($dbTables); ?></div>
            </div>
            <div class="stat-card">
                <h3>매핑 불일치</h3>
                <div class="number"><?php echo count($mappingMismatches); ?></div>
            </div>
        </div>
        
        <!-- 데이터 타입별 분류 -->
        <div class="section">
            <h2>📊 데이터 타입별 분류</h2>
            <div class="type-grid">
                <div class="type-section">
                    <h4>📝 Rules.yaml 필드 타입 분류</h4>
                    <p><strong>SurvData:</strong> <?php echo count($rulesFieldsByType['survdata']); ?></p>
                    <p><strong>SysData:</strong> <?php echo count($rulesFieldsByType['sysdata']); ?></p>
                    <p><strong>GenData:</strong> <?php echo count($rulesFieldsByType['gendata']); ?></p>
                    <p><strong>Unknown:</strong> <?php echo count($rulesFieldsByType['unknown']); ?></p>
                </div>
                <div class="type-section">
                    <h4>💾 Data Access 필드 타입 분류</h4>
                    <p><strong>SurvData:</strong> <?php echo count($dataAccessFieldsByType['survdata']); ?></p>
                    <p><strong>SysData:</strong> <?php echo count($dataAccessFieldsByType['sysdata']); ?></p>
                    <p><strong>GenData:</strong> <?php echo count($dataAccessFieldsByType['gendata']); ?></p>
                    <p><strong>Unknown:</strong> <?php echo count($dataAccessFieldsByType['unknown']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- 1. Rules.yaml에 있는데 data_access.php에 없는 필드 -->
        <div class="section">
            <h2>⚠️ Rules.yaml에 있는데 data_access.php에 없는 필드</h2>
            <?php if (empty($inRulesNotInDataAccess)): ?>
                <div class="empty-state">
                    <p>모든 필드가 data_access.php에 구현되어 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #dc2626; margin-bottom: 1rem;">총 <?php echo count($inRulesNotInDataAccess); ?>개 필드가 누락되었습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>데이터 타입</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inRulesNotInDataAccess as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'warning')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($dataType === 'survdata') {
                                        echo '설문 응답 데이터 (사용자 입력)';
                                    } elseif ($dataType === 'sysdata') {
                                        echo '시스템 데이터 (DB에서 자동 조회)';
                                    } elseif ($dataType === 'gendata') {
                                        echo '생성 데이터 (AI/계산으로 생성)';
                                    } else {
                                        echo '알 수 없음';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 2. Data_access.php에 있는데 rules.yaml에 없는 필드 -->
        <div class="section">
            <h2>ℹ️ Data_access.php에 있는데 rules.yaml에 없는 필드</h2>
            <?php if (empty($inDataAccessNotInRules)): ?>
                <div class="empty-state">
                    <p>모든 필드가 rules.yaml에 정의되어 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($inDataAccessNotInRules); ?>개 필드가 rules.yaml에 정의되지 않았습니다.</p>
                <div class="field-list">
                    <?php foreach ($inDataAccessNotInRules as $field): ?>
                        <span class="field-item badge-warning"><?php echo htmlspecialchars($field); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 3. DB에 있는데 rules.yaml에 사용하지 않는 데이터 -->
        <div class="section">
            <h2>🗄️ DB에 있는데 rules.yaml에 사용하지 않는 데이터</h2>
            <?php if (empty($inDbNotInRules)): ?>
                <div class="empty-state">
                    <p>모든 DB 필드가 rules.yaml에서 사용되고 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($inDbNotInRules); ?>개 DB 필드가 rules.yaml에서 사용되지 않습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>DB 필드</th>
                            <th>데이터 타입</th>
                            <th>테이블</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDbNotInRules as $dbField): ?>
                            <?php 
                            $parts = explode('.', $dbField);
                            $table = $parts[0] ?? '';
                            $field = $parts[1] ?? $dbField;
                            $dataType = classifyDataType($field, $table);
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'warning')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($table); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 3-1. DB에 실제 데이터가 존재하는 rules.yaml 필드 -->
        <div class="section">
            <h2>✅ DB에 실제 데이터가 존재하는 rules.yaml 필드</h2>
            <?php if (empty($dbDataExists)): ?>
                <div class="empty-state">
                    <p>DB에 실제 데이터가 존재하는 필드가 없습니다.</p>
                </div>
            <?php else: ?>
                <p style="color: #10b981; margin-bottom: 1rem;">총 <?php echo count($dbDataExists); ?>개 필드가 DB에 실제 데이터를 가지고 있습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>데이터 타입</th>
                            <th>테이블</th>
                            <th>샘플 데이터</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbDataExists as $item): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : ($item['type'] === 'gendata' ? 'gen' : 'warning')); ?>">
                                        <?php echo $item['type']; ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($item['table']); ?></code></td>
                                <td style="font-size: 0.85rem; color: #6b7280;">
                                    <?php echo htmlspecialchars($item['sample'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 4. View_reports.php에서 사용하는데 rules.yaml에 없는 필드 -->
        <div class="section">
            <h2>📄 View_reports.php에서 사용하는데 rules.yaml에 없는 필드</h2>
            <?php if (empty($inViewReportsNotInRules)): ?>
                <div class="empty-state">
                    <p>모든 필드가 rules.yaml에 정의되어 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($inViewReportsNotInRules); ?>개 필드가 rules.yaml에 정의되지 않았습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>데이터 타입</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inViewReportsNotInRules as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'warning')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($dataType === 'survdata') {
                                        echo '설문 응답 데이터 (사용자 입력)';
                                    } elseif ($dataType === 'sysdata') {
                                        echo '시스템 데이터 (DB에서 자동 조회)';
                                    } elseif ($dataType === 'gendata') {
                                        echo '생성 데이터 (계산/추론)';
                                    } else {
                                        echo '알 수 없음';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 5. 매핑 불일치 -->
        <div class="section">
            <h2>🔄 매핑 불일치 (유사한 데이터인데 다른 이름으로 사용)</h2>
            <?php if (empty($mappingMismatches)): ?>
                <div class="empty-state">
                    <p>매핑 불일치가 발견되지 않았습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #dc2626; margin-bottom: 1rem;">총 <?php echo count($mappingMismatches); ?>개 매핑 불일치가 발견되었습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>View Reports 필드</th>
                            <th>Rules.yaml 필드</th>
                            <th>타입</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappingMismatches as $mismatch): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($mismatch['view_reports_field']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($mismatch['rules_field']); ?></code></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($mismatch['type']); ?></span></td>
                                <td>유사한 개념인데 다른 필드명으로 사용됨</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 전체 필드 목록 -->
        <div class="section">
            <h2>📋 전체 필드 목록</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Rules.yaml 필드 (<?php echo count($rulesFields); ?>)</h3>
                    <div class="field-list" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($rulesFields as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'warning')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Data Access 필드 (<?php echo count($dataAccessFields); ?>)</h3>
                    <div class="field-list" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($dataAccessFields as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'success')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">View Reports 필드 (<?php echo count($viewReportsFields); ?>)</h3>
                    <div class="field-list" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($viewReportsFields as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'warning')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


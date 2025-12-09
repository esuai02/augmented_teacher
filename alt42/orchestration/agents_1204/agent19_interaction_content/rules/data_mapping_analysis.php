<?php
/**
 * Agent 19 데이터 매핑 분석 도구
 * view_reports.php에서 사용하는 데이터와 rules.yaml, data_access.php를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent19_interaction_content/rules/
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
$rulesFieldsWithTypes = []; // 필드와 데이터 타입 매핑
if (!empty($rulesYamlContent)) {
    // field: 패턴으로 필드 추출
    preg_match_all('/field:\s*"([^"]+)"/', $rulesYamlContent, $matches);
    if (!empty($matches[1])) {
        $rulesFields = array_unique($matches[1]);
        sort($rulesFields);
    }
    
    // 각 필드의 사용 컨텍스트 분석 (survdata/sysdata/gendata 판단)
    foreach ($rulesFields as $field) {
        $rulesFieldsWithTypes[$field] = [
            'type' => 'unknown',
            'used_in_rules' => [],
            'source' => 'rules.yaml'
        ];
    }
}

// data_access.php에서 사용하는 필드 추출
$dataAccessPath = __DIR__ . '/data_access.php';
$dataAccessContent = file_exists($dataAccessPath) ? file_get_contents($dataAccessPath) : '';

$dataAccessFields = [];
$dataAccessFieldsWithInfo = [];
if (!empty($dataAccessContent)) {
    // $context['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$context\[\'([^\']+)\'\]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_unique($matches[1]);
        sort($dataAccessFields);
    }
    
    // $context['필드명'] = 패턴으로 필드 추출
    preg_match_all('/\$context\[\'([^\']+)\'\]\s*=/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    foreach ($dataAccessFields as $field) {
        $dataAccessFieldsWithInfo[$field] = [
            'type' => 'unknown',
            'source' => 'data_access.php',
            'applied' => true
        ];
    }
}

// view_reports.php에서 사용하는 데이터 필드 추출
$viewReportsPath = __DIR__ . '/../../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
if (!file_exists($viewReportsPath)) {
    // 다른 경로 시도
    $viewReportsPath = __DIR__ . '/../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
}

$viewReportsFields = [];
$viewReportsTables = [];
$viewReportsFieldsWithInfo = [];

if (file_exists($viewReportsPath)) {
    $viewReportsContent = file_get_contents($viewReportsPath);
    
    // 테이블명 추출 (Moodle 테이블 포맷)
    preg_match_all('/\{([a-z_]+)\}/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_unique($matches[1]);
    }
    
    // mdl_ 테이블명 추출
    preg_match_all('/mdl_([a-z_]+)/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_merge($viewReportsTables, $matches[1]);
        $viewReportsTables = array_unique($viewReportsTables);
    }
    
    // $data['필드명'] 패턴으로 필드 추출 (JSON 응답 데이터)
    preg_match_all('/\$data\[\'([^\']+)\'\]/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field) {
            $viewReportsFields[] = $field;
            $viewReportsFieldsWithInfo[$field] = [
                'type' => 'unknown',
                'source' => 'view_reports.php',
                'table' => null
            ];
        }
    }
    
    // 설문 응답 필드 (responses 배열)
    preg_match_all('/\'([a-z_]+)\'\s*=>/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field) {
            if (!in_array($field, $viewReportsFields)) {
                $viewReportsFields[] = $field;
                $viewReportsFieldsWithInfo[$field] = [
                    'type' => 'survdata',
                    'source' => 'view_reports.php',
                    'table' => 'alt42_goinghome'
                ];
            }
        }
    }
    
    // 실제 DB 쿼리에서 사용하는 필드 추출
    preg_match_all('/SELECT\s+([^FROM]+)/i', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $selectPart) {
            preg_match_all('/(\w+)/', $selectPart, $fieldMatches);
            foreach ($fieldMatches[1] as $field) {
                if (!in_array($field, ['SELECT', 'DISTINCT', 'COUNT', 'MAX', 'MIN', 'AVG', 'SUM', 'AS']) && 
                    !in_array($field, $viewReportsFields)) {
                    $viewReportsFields[] = $field;
                    $viewReportsFieldsWithInfo[$field] = [
                        'type' => 'sysdata',
                        'source' => 'view_reports.php',
                        'table' => null
                    ];
                }
            }
        }
    }
    
    $viewReportsFields = array_unique($viewReportsFields);
    sort($viewReportsFields);
}

// 실제 DB에서 데이터 조회 및 존재 여부 확인
$dbFields = [];
$dbTables = [];
$dbFieldsWithInfo = [];

// 관련 테이블 목록
$relatedTables = [
    'alt42_goinghome',
    'alt42_calmness',
    'abessi_tracking',
    'abessi_messages',
    'abessi_mbtilog',
    'user',
    'alt42o_onboarding'
];

foreach ($relatedTables as $tableName) {
    $fullTableName = (strpos($tableName, 'mdl_') === 0) ? $tableName : 'mdl_' . $tableName;
    
    if ($DB->get_manager()->table_exists(new xmldb_table($tableName)) || 
        $DB->get_manager()->table_exists(new xmldb_table($fullTableName))) {
        
        $actualTableName = $DB->get_manager()->table_exists(new xmldb_table($tableName)) ? $tableName : $fullTableName;
        $dbTables[] = $actualTableName;
        
        try {
            $columns = $DB->get_columns($actualTableName);
            foreach ($columns as $colName => $colInfo) {
                $dbFieldKey = $actualTableName . '.' . $colName;
                $dbFields[] = $dbFieldKey;
                $dbFieldsWithInfo[$dbFieldKey] = [
                    'field' => $colName,
                    'table' => $actualTableName,
                    'type' => classifyDataType($colName, $actualTableName),
                    'exists_in_db' => true,
                    'used_in_rules' => false,
                    'used_in_data_access' => false,
                    'used_in_view_reports' => false
                ];
            }
        } catch (Exception $e) {
            error_log("Error getting columns from {$actualTableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
}

// 데이터 타입 분류 함수 (agent19 특화)
function classifyDataType($fieldName, $tableName = '') {
    // 설문 데이터 (survdata) - 사용자 입력
    $survFields = [
        'calmness', 'pomodoro', 'inefficiency', 'weekly_goal', 'daily_plan', 'pace_anxiety',
        'satisfaction', 'boredom', 'stress_level', 'positive_moment', 'problem_count',
        'error_note', 'concept_study', 'difficulty_level', 'easy_problems', 'self_improvement',
        'missed_opportunity', 'intuition_solving', 'forced_solving', 'questions_asked',
        'unsaid_words', 'rest_pattern', 'long_problem', 'study_amount',
        'emotion_state', 'mbti_type', 'progress_rate', 'pressure_level',
        'rest_pattern_status', 'fatigue_accumulation', 'error_repeat_count',
        'calmness_score_change', 'activity_distribution_balance', 'signature_routine_detected'
    ];
    
    // 시스템 데이터 (sysdata) - DB에서 자동 조회
    $sysFields = [
        'level', 'timecreated', 'timefinished', 'duration', 'timemodified', 
        'student_check', 'turn', 'hide', 'userid', 'id',
        'engagement_score', 'input_event_count', 'time_window_minutes',
        'current_activity_difficulty', 'detection_source', 'immersion_level',
        'current_position_status', 'rest_interval_minutes', 'study_session_duration',
        'consecutive_study_minutes', 'rest_missing_count', 'concept_review_time_seconds',
        'selection_error_frequency', 'calmness_score', 'mistake_repeat_count',
        'concept_study_ratio', 'problem_solving_ratio', 'routine_consistency_days',
        'routine_success_rate', 'template_library_has_match', 'template_match_score'
    ];
    
    // 생성 데이터 (gendata) - 계산/추론/생성
    $genFields = [
        'calmnessGrade', 'pomodoroUsage', 'errorNoteCount', 'needsAttention',
        'calmness_score_change', 'confidence_change', 'error_type', 'error_category',
        'study_style', 'concept_mastery_level', 'goal_type', 'user_resistance_to_change',
        'previous_intervention_count', 'interaction_type', 'interaction_delivered',
        'problem_solved_correctly', 'unit_completed', 'current_unit', 'learning_stage',
        'academy_textbook', 'textbook_level', 'problem_type', 'weak_units',
        'academy_class_time', 'academy_unit', 'math_learning_style'
    ];
    
    // 테이블 기반 분류
    if (strpos($tableName, 'goinghome') !== false || strpos($tableName, 'onboarding') !== false) {
        return 'survdata';
    }
    if (strpos($tableName, 'calmness') !== false || strpos($tableName, 'tracking') !== false || 
        strpos($tableName, 'messages') !== false || strpos($tableName, 'user') !== false) {
        return 'sysdata';
    }
    
    // 필드명 기반 분류
    if (in_array($fieldName, $survFields)) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields)) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields)) {
        return 'gendata';
    }
    
    return 'unknown';
}

// 각 필드의 사용 여부 확인
foreach ($dbFieldsWithInfo as $dbFieldKey => &$info) {
    $fieldName = $info['field'];
    
    // rules.yaml에서 사용 여부
    if (in_array($fieldName, $rulesFields)) {
        $info['used_in_rules'] = true;
    }
    
    // data_access.php에서 사용 여부
    if (in_array($fieldName, $dataAccessFields)) {
        $info['used_in_data_access'] = true;
    }
    
    // view_reports.php에서 사용 여부
    if (in_array($fieldName, $viewReportsFields)) {
        $info['used_in_view_reports'] = true;
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
    
    return false;
}

// 실제 DB 데이터 존재 여부 확인 (rules.yaml 필드 기준)
$dbDataExists = [];
foreach ($rulesFields as $field) {
    $exists = false;
    $tableName = '';
    $sampleValue = null;
    
    // alt42_goinghome 테이블 확인 (JSON 데이터)
    if ($DB->get_manager()->table_exists(new xmldb_table('alt42_goinghome'))) {
        try {
            $sampleData = $DB->get_record_sql(
                "SELECT * FROM {alt42_goinghome} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$studentid],
                IGNORE_MISSING
            );
            if ($sampleData && isset($sampleData->text)) {
                $jsonData = json_decode($sampleData->text, true);
                if (is_array($jsonData) && isset($jsonData[$field])) {
                    $exists = true;
                    $tableName = 'alt42_goinghome';
                    $sampleValue = is_string($jsonData[$field]) ? substr($jsonData[$field], 0, 50) : $jsonData[$field];
                }
            }
        } catch (Exception $e) {
            error_log("Error checking alt42_goinghome: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
    
    // alt42o_onboarding 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('alt42o_onboarding'))) {
        try {
            $columns = $DB->get_columns('alt42o_onboarding');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('alt42o_onboarding', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'alt42o_onboarding';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking alt42o_onboarding: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
foreach ($dbFieldsWithInfo as $dbFieldKey => $info) {
    if (!$info['used_in_rules']) {
        $inDbNotInRules[] = [
            'field' => $info['field'],
            'table' => $info['table'],
            'type' => $info['type'],
            'used_in_data_access' => $info['used_in_data_access'],
            'used_in_view_reports' => $info['used_in_view_reports']
        ];
    }
}

// 4. view_reports.php에서 사용하는데 rules.yaml에 없는 필드
$inViewReportsNotInRules = [];
foreach ($viewReportsFields as $field) {
    if (!in_array($field, $rulesFields)) {
        $info = $viewReportsFieldsWithInfo[$field] ?? ['type' => 'unknown', 'source' => 'view_reports.php', 'table' => null];
        $inViewReportsNotInRules[] = [
            'field' => $field,
            'type' => classifyDataType($field, $info['table'] ?? ''),
            'table' => $info['table'],
            'source' => $info['source']
        ];
    }
}

// 5. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
$similarFieldMappings = [
    ['calmness', 'calmness_score', 'calmness_score_change'],
    ['pomodoro', 'study_hours_per_week', 'input_event_count'],
    ['error_note', 'error_repeat_count', 'mistake_repeat_count'],
    ['engagement_score', 'immersion_level'],
    ['progress_rate', 'current_position_status'],
    ['rest_pattern', 'rest_pattern_status', 'rest_interval_minutes'],
    ['emotion_state', 'emotion_log'],
    ['mbti_type', 'mbti']
];

foreach ($similarFieldMappings as $similarGroup) {
    $foundInViewReports = [];
    $foundInRules = [];
    
    foreach ($similarGroup as $field) {
        if (in_array($field, $viewReportsFields)) {
            $foundInViewReports[] = $field;
        }
        if (in_array($field, $rulesFields)) {
            $foundInRules[] = $field;
        }
    }
    
    if (!empty($foundInViewReports) && !empty($foundInRules) && 
        count(array_intersect($foundInViewReports, $foundInRules)) === 0) {
        $mappingMismatches[] = [
            'view_reports_fields' => $foundInViewReports,
            'rules_fields' => $foundInRules,
            'type' => 'similar_concept',
            'description' => '유사한 개념인데 다른 필드명으로 사용됨'
        ];
    }
}

// 데이터 타입별 통계
$typeStats = [
    'survdata' => ['rules' => 0, 'data_access' => 0, 'view_reports' => 0, 'db' => 0],
    'sysdata' => ['rules' => 0, 'data_access' => 0, 'view_reports' => 0, 'db' => 0],
    'gendata' => ['rules' => 0, 'data_access' => 0, 'view_reports' => 0, 'db' => 0],
    'unknown' => ['rules' => 0, 'data_access' => 0, 'view_reports' => 0, 'db' => 0]
];

foreach ($rulesFields as $field) {
    $type = classifyDataType($field);
    $typeStats[$type]['rules']++;
}

foreach ($dataAccessFields as $field) {
    $type = classifyDataType($field);
    $typeStats[$type]['data_access']++;
}

foreach ($viewReportsFields as $field) {
    $info = $viewReportsFieldsWithInfo[$field] ?? [];
    $type = $info['type'] !== 'unknown' ? $info['type'] : classifyDataType($field);
    $typeStats[$type]['view_reports']++;
}

foreach ($dbFieldsWithInfo as $info) {
    $type = $info['type'];
    $typeStats[$type]['db']++;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 19</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
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
        
        .type-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .type-stat-card {
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .type-stat-card.survdata {
            background: #d4edda;
            border-color: #155724;
        }
        
        .type-stat-card.sysdata {
            background: #fff3cd;
            border-color: #856404;
        }
        
        .type-stat-card.gendata {
            background: #cfe2ff;
            border-color: #084298;
        }
        
        .type-stat-card.unknown {
            background: #e2e3e5;
            border-color: #6c757d;
        }
        
        .type-stat-card h4 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .type-stat-card .stat-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .flow-diagram {
            background: #f9fafb;
            padding: 2rem;
            border-radius: 10px;
            margin: 2rem 0;
            border: 2px dashed #e5e7eb;
        }
        
        .flow-diagram h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .flow-box {
            display: inline-block;
            padding: 1rem 1.5rem;
            margin: 0.5rem;
            border-radius: 8px;
            font-weight: 600;
            color: white;
        }
        
        .flow-box.sysdata { background: #f59e0b; }
        .flow-box.survdata { background: #10b981; }
        .flow-box.hybriddata { background: #8b5cf6; }
        .flow-box.gendata { background: #3b82f6; }
        .flow-box.merge { background: #667eea; }
        
        .arrow {
            display: inline-block;
            margin: 0 0.5rem;
            color: #6b7280;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/contextual_agents/beforegoinghome/view_reports.php?studentid=<?php echo $studentid; ?>" class="back-button">← View Reports로 돌아가기</a>
        
        <div class="header">
            <h1>📊 Agent 19 데이터 매핑 분석 리포트</h1>
            <p>view_reports.php vs rules.yaml vs data_access.php 비교 분석</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">학생 ID: <?php echo $studentid; ?></p>
        </div>
        
        <!-- 전체 데이터 플로우 다이어그램 -->
        <div class="section">
            <h2>1️⃣ 전체 데이터 플로우 (Main Data Flow)</h2>
            <p style="margin-bottom: 1rem; color: #6b7280;">
                메타데이터가 전체 시스템을 구동하며, 데이터는 sysdata → survdata → hybriddata → gendata → merge 순서로 흐릅니다.
            </p>
            <div class="flow-diagram">
                <div style="text-align: center;">
                    <span class="flow-box sysdata">SysData</span>
                    <span class="arrow">→</span>
                    <span class="flow-box survdata">SurvData</span>
                    <span class="arrow">→</span>
                    <span class="flow-box hybriddata">HybridData</span>
                    <span class="arrow">→</span>
                    <span class="flow-box gendata">GenData</span>
                    <span class="arrow">→</span>
                    <span class="flow-box merge">Merge</span>
                </div>
            </div>
        </div>
        
        <!-- 데이터 타입별 우선순위 -->
        <div class="section">
            <h2>2️⃣ 데이터 타입별 우선순위 (Data Priority)</h2>
            <p style="margin-bottom: 1rem; color: #6b7280;">
                데이터 병합 시 우선순위: <strong>Override > GenData > HybridData > SurvData > SysData</strong>
            </p>
            <div class="flow-diagram">
                <div style="text-align: center;">
                    <div style="display: inline-block; margin: 0.5rem;">
                        <div class="flow-box" style="background: #dc2626;">Override</div>
                        <div style="font-size: 0.8rem; margin-top: 0.25rem;">최우선</div>
                    </div>
                    <span class="arrow">></span>
                    <div style="display: inline-block; margin: 0.5rem;">
                        <div class="flow-box gendata">GenData</div>
                        <div style="font-size: 0.8rem; margin-top: 0.25rem;">AI 생성</div>
                    </div>
                    <span class="arrow">></span>
                    <div style="display: inline-block; margin: 0.5rem;">
                        <div class="flow-box hybriddata">HybridData</div>
                        <div style="font-size: 0.8rem; margin-top: 0.25rem;">계산</div>
                    </div>
                    <span class="arrow">></span>
                    <div style="display: inline-block; margin: 0.5rem;">
                        <div class="flow-box survdata">SurvData</div>
                        <div style="font-size: 0.8rem; margin-top: 0.25rem;">사용자 입력</div>
                    </div>
                    <span class="arrow">></span>
                    <div style="display: inline-block; margin: 0.5rem;">
                        <div class="flow-box sysdata">SysData</div>
                        <div style="font-size: 0.8rem; margin-top: 0.25rem;">시스템 기본</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 통계 카드 -->
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
        </div>
        
        <!-- 데이터 타입별 통계 -->
        <div class="section">
            <h2>3️⃣ 데이터 타입별 통계</h2>
            <div class="type-stats-grid">
                <div class="type-stat-card survdata">
                    <h4>SurvData (설문 데이터)</h4>
                    <div class="stat-row">
                        <span>Rules.yaml:</span>
                        <strong><?php echo $typeStats['survdata']['rules']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>Data Access:</span>
                        <strong><?php echo $typeStats['survdata']['data_access']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>View Reports:</span>
                        <strong><?php echo $typeStats['survdata']['view_reports']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>DB:</span>
                        <strong><?php echo $typeStats['survdata']['db']; ?></strong>
                    </div>
                </div>
                <div class="type-stat-card sysdata">
                    <h4>SysData (시스템 데이터)</h4>
                    <div class="stat-row">
                        <span>Rules.yaml:</span>
                        <strong><?php echo $typeStats['sysdata']['rules']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>Data Access:</span>
                        <strong><?php echo $typeStats['sysdata']['data_access']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>View Reports:</span>
                        <strong><?php echo $typeStats['sysdata']['view_reports']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>DB:</span>
                        <strong><?php echo $typeStats['sysdata']['db']; ?></strong>
                    </div>
                </div>
                <div class="type-stat-card gendata">
                    <h4>GenData (생성 데이터)</h4>
                    <div class="stat-row">
                        <span>Rules.yaml:</span>
                        <strong><?php echo $typeStats['gendata']['rules']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>Data Access:</span>
                        <strong><?php echo $typeStats['gendata']['data_access']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>View Reports:</span>
                        <strong><?php echo $typeStats['gendata']['view_reports']; ?></strong>
                    </div>
                    <div class="stat-row">
                        <span>DB:</span>
                        <strong><?php echo $typeStats['gendata']['db']; ?></strong>
                    </div>
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
                            <th>테이블</th>
                            <th>데이터 타입</th>
                            <th>Data Access 사용</th>
                            <th>View Reports 사용</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDbNotInRules as $item): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($item['table']); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : ($item['type'] === 'gendata' ? 'gen' : 'warning')); ?>">
                                        <?php echo $item['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['used_in_data_access']): ?>
                                        <span class="badge badge-success">사용</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">미사용</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['used_in_view_reports']): ?>
                                        <span class="badge badge-success">사용</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">미사용</span>
                                    <?php endif; ?>
                                </td>
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
                            <th>테이블</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inViewReportsNotInRules as $item): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : ($item['type'] === 'gendata' ? 'gen' : 'warning')); ?>">
                                        <?php echo $item['type']; ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($item['table'] ?? 'N/A'); ?></code></td>
                                <td>
                                    <?php 
                                    if ($item['type'] === 'survdata') {
                                        echo '설문 응답 데이터 (사용자 입력)';
                                    } elseif ($item['type'] === 'sysdata') {
                                        echo '시스템 데이터 (DB에서 자동 조회)';
                                    } elseif ($item['type'] === 'gendata') {
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
                                <td>
                                    <?php foreach ($mismatch['view_reports_fields'] as $field): ?>
                                        <span class="badge badge-surv"><?php echo htmlspecialchars($field); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php foreach ($mismatch['rules_fields'] as $field): ?>
                                        <span class="badge badge-rule"><?php echo htmlspecialchars($field); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($mismatch['type']); ?></span></td>
                                <td><?php echo htmlspecialchars($mismatch['description']); ?></td>
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
                    <div class="field-list">
                        <?php foreach ($rulesFields as $field): ?>
                            <?php $type = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $type === 'survdata' ? 'surv' : ($type === 'sysdata' ? 'sys' : ($type === 'gendata' ? 'gen' : 'rule')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Data Access 필드 (<?php echo count($dataAccessFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($dataAccessFields as $field): ?>
                            <?php $type = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $type === 'survdata' ? 'surv' : ($type === 'sysdata' ? 'sys' : ($type === 'gendata' ? 'gen' : 'success')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">View Reports 필드 (<?php echo count($viewReportsFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($viewReportsFields as $field): ?>
                            <?php 
                            $info = $viewReportsFieldsWithInfo[$field] ?? [];
                            $type = $info['type'] !== 'unknown' ? $info['type'] : classifyDataType($field);
                            ?>
                            <span class="field-item badge-<?php echo $type === 'survdata' ? 'surv' : ($type === 'sysdata' ? 'sys' : ($type === 'gendata' ? 'gen' : 'info')); ?>">
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


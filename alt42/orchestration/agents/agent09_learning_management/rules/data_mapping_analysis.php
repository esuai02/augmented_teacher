<?php
/**
 * Agent 09 - Learning Management 데이터 매핑 분석 도구
 * rules.yaml, data_access.php, DB 스키마 간의 데이터 매핑 상태를 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent09_learning_management/rules/
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
    
    // 함수 반환값에서 필드 추출
    preg_match_all('/\'([a-z_]+)\'\s*=>/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];

// mdl_user 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('user'))) {
    $dbTables[] = 'user';
    try {
        $columns = $DB->get_columns('user');
        foreach ($columns as $colName => $colInfo) {
            if (in_array($colName, ['id', 'firstname', 'lastname', 'email'])) {
                $dbFields[] = 'user.' . $colName;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting columns from user: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_alt42g_goal_analysis 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('alt42g_goal_analysis'))) {
    $dbTables[] = 'alt42g_goal_analysis';
    try {
        $columns = $DB->get_columns('alt42g_goal_analysis');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'alt42g_goal_analysis.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from alt42g_goal_analysis: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_alt42g_pomodoro_sessions 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('alt42g_pomodoro_sessions'))) {
    $dbTables[] = 'alt42g_pomodoro_sessions';
    try {
        $columns = $DB->get_columns('alt42g_pomodoro_sessions');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'alt42g_pomodoro_sessions.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from alt42g_pomodoro_sessions: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_abessi_messages 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('abessi_messages'))) {
    $dbTables[] = 'abessi_messages';
    try {
        $columns = $DB->get_columns('abessi_messages');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'abessi_messages.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from abessi_messages: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_logstore_standard_log 테이블 구조 확인 (출결 데이터)
if ($DB->get_manager()->table_exists(new xmldb_table('logstore_standard_log'))) {
    $dbTables[] = 'logstore_standard_log';
    try {
        $columns = $DB->get_columns('logstore_standard_log');
        foreach ($columns as $colName => $colInfo) {
            if (in_array($colName, ['id', 'userid', 'timecreated', 'eventname', 'component', 'action'])) {
                $dbFields[] = 'logstore_standard_log.' . $colName;
            }
        }
    } catch (Exception $e) {
        error_log("Error getting columns from logstore_standard_log: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
$dbDataSample = [];

foreach ($rulesFields as $field) {
    $exists = false;
    $tableName = '';
    $sampleValue = null;
    
    // alt42g_goal_analysis 테이블 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('alt42g_goal_analysis'))) {
        try {
            $columns = $DB->get_columns('alt42g_goal_analysis');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('alt42g_goal_analysis', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'alt42g_goal_analysis';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking alt42g_goal_analysis: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // alt42g_pomodoro_sessions 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('alt42g_pomodoro_sessions'))) {
        try {
            $columns = $DB->get_columns('alt42g_pomodoro_sessions');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('alt42g_pomodoro_sessions', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'alt42g_pomodoro_sessions';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking alt42g_pomodoro_sessions: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // abessi_messages 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('abessi_messages'))) {
        try {
            $columns = $DB->get_columns('abessi_messages');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record_sql(
                    "SELECT * FROM {abessi_messages} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                    [$studentid],
                    IGNORE_MISSING
                );
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'abessi_messages';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking abessi_messages: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
function classifyDataType($fieldName, $tableName = '', $rulesYamlContent = '') {
    // survdata: 설문/사용자 입력 데이터
    $survFields = ['student_survey_data', 'teacher_checklist_data', 'text_input_data', 
                   'text_feedback_provided', 'teacher_checklist_provided', 'text_input_provided',
                   'student_survey_enabled', 'feedback_collection_due'];
    
    // sysdata: 시스템/DB에서 자동 조회되는 데이터
    $sysFields = ['attendance_data', 'attendance_pattern', 'pomodoro_data', 'goal_data',
                  'wrong_note_data', 'test_data', 'student_id', 'timecreated', 'timemodified',
                  'userid', 'status', 'level', 'duration'];
    
    // gendata: AI/계산으로 생성되는 데이터
    $genFields = ['pattern_type', 'risk_level', 'dropout_risk_score', 'data_reliability_grade',
                  'data_density', 'data_consistency', 'data_effectiveness', 'pattern_stability_score',
                  'learning_pattern_type', 'overall_consistency_score', 'effectiveness_score',
                  'routine_effectiveness_score', 'math_learning_efficiency_score', 'math_learning_habit_score'];
    
    // hybriddata: 여러 소스를 조합하여 계산된 데이터
    $hybridFields = ['hybrid_data', 'goal_achievement', 'pomodoro_completion', 'error_patterns',
                     'data_density_score', 'data_balance_score', 'attendance_pomodoro_correlation',
                     'goal_test_score_correlation', 'wrong_note_study_time_correlation',
                     'pomodoro_test_performance_correlation', 'concept_mastery_speed',
                     'problem_solving_speed', 'error_reduction_rate', 'root_cause', 'cause_priority'];
    
    // 테이블 기반 분류
    if (strpos($tableName, 'goal_analysis') !== false || strpos($tableName, 'pomodoro') !== false ||
        strpos($tableName, 'messages') !== false || strpos($tableName, 'logstore') !== false) {
        return 'sysdata';
    }
    
    // 필드명 기반 분류
    if (in_array($fieldName, $survFields)) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields)) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields)) {
        return 'gendata';
    } elseif (in_array($fieldName, $hybridFields)) {
        return 'hybriddata';
    }
    
    // rules.yaml 내용 기반 추론
    if (!empty($rulesYamlContent)) {
        if (preg_match('/generate.*' . preg_quote($fieldName, '/') . '/i', $rulesYamlContent)) {
            return 'gendata';
        }
        if (preg_match('/calculate.*' . preg_quote($fieldName, '/') . '/i', $rulesYamlContent)) {
            return 'hybriddata';
        }
        if (preg_match('/collect.*' . preg_quote($fieldName, '/') . '/i', $rulesYamlContent)) {
            return 'survdata';
        }
    }
    
    return 'unknown';
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
    // 필드명 정규화 (snake_case로 변환)
    $normalizedField = strtolower($fieldName);
    
    // rules.yaml 필드와 매칭 시도
    $matched = false;
    foreach ($rulesFields as $ruleField) {
        if (stripos($ruleField, $normalizedField) !== false || 
            stripos($normalizedField, $ruleField) !== false) {
            $matched = true;
            break;
        }
    }
    
    if (!$matched) {
        $inDbNotInRules[] = $dbField;
    }
}

// 4. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
$similarFields = [
    ['attendance_data', 'attendance_pattern'],
    ['goal_data', 'goal_achievement'],
    ['pomodoro_data', 'pomodoro_completion'],
    ['wrong_note_data', 'error_patterns'],
    ['test_data', 'test_patterns'],
    ['student_level', 'current_level'],
    ['pattern_type', 'learning_pattern_type']
];

foreach ($similarFields as $pair) {
    $field1 = $pair[0];
    $field2 = $pair[1];
    $inRules1 = in_array($field1, $rulesFields);
    $inRules2 = in_array($field2, $rulesFields);
    $inDataAccess1 = in_array($field1, $dataAccessFields);
    $inDataAccess2 = in_array($field2, $dataAccessFields);
    
    if (($inRules1 && $inDataAccess2) || ($inRules2 && $inDataAccess1)) {
        $mappingMismatches[] = [
            'field1' => $field1,
            'field2' => $field2,
            'field1_in_rules' => $inRules1,
            'field2_in_rules' => $inRules2,
            'field1_in_data_access' => $inDataAccess1,
            'field2_in_data_access' => $inDataAccess2,
            'type' => 'similar_concept'
        ];
    }
}

// 5. data_access.php에서 사용 여부 확인
$dataAccessUsage = [];
foreach ($rulesFields as $field) {
    $dataAccessUsage[$field] = [
        'field' => $field,
        'in_rules_yaml' => true,
        'in_data_access' => in_array($field, $dataAccessFields),
        'in_db' => false,
        'data_type' => classifyDataType($field, '', $rulesYamlContent),
        'db_table' => null
    ];
    
    // DB에서 해당 필드 찾기
    foreach ($dbFields as $dbField) {
        $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
        if (stripos($field, $dbFieldName) !== false || stripos($dbFieldName, $field) !== false) {
            $dataAccessUsage[$field]['in_db'] = true;
            $dataAccessUsage[$field]['db_table'] = explode('.', $dbField)[0] ?? '';
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 09 (Learning Management)</title>
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
            max-width: 1400px;
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
        
        .badge-hybrid {
            background: #d1ecf1;
            color: #0c5460;
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
        
        .badge-yes {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-no {
            background: #f8d7da;
            color: #721c24;
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
        
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>📊 데이터 매핑 분석 리포트 - Agent 09</h1>
            <p>Learning Management: rules.yaml vs data_access.php vs DB 스키마 비교 분석</p>
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
                <h3>DB 테이블</h3>
                <div class="number"><?php echo count($dbTables); ?></div>
            </div>
            <div class="stat-card">
                <h3>DB 필드</h3>
                <div class="number"><?php echo count($dbFields); ?></div>
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
                            <?php $dataType = classifyDataType($field, '', $rulesYamlContent); ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'hybrid')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($dataType === 'survdata') {
                                        echo '설문/사용자 입력 데이터 - data_access.php에 수집 로직 필요';
                                    } elseif ($dataType === 'sysdata') {
                                        echo '시스템 데이터 - DB 조회 로직 필요';
                                    } elseif ($dataType === 'gendata') {
                                        echo 'AI 생성 데이터 - 생성 로직 필요';
                                    } elseif ($dataType === 'hybriddata') {
                                        echo '복합 계산 데이터 - 계산 로직 필요';
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
                            $dataType = classifyDataType($field, $table, $rulesYamlContent);
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'hybrid')); ?>">
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
                                    <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : ($item['type'] === 'hybriddata' ? 'hybrid' : 'gen')); ?>">
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
        
        <!-- 4. 매핑 불일치 -->
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
                            <th>필드 1</th>
                            <th>필드 2</th>
                            <th>Rules.yaml</th>
                            <th>Data Access</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappingMismatches as $mismatch): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($mismatch['field1']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($mismatch['field2']); ?></code></td>
                                <td>
                                    <?php if ($mismatch['field1_in_rules']): ?>
                                        <span class="badge badge-yes">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-no">No</span>
                                    <?php endif; ?>
                                    <?php if ($mismatch['field2_in_rules']): ?>
                                        <span class="badge badge-yes">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-no">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($mismatch['field1_in_data_access']): ?>
                                        <span class="badge badge-yes">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-no">No</span>
                                    <?php endif; ?>
                                    <?php if ($mismatch['field2_in_data_access']): ?>
                                        <span class="badge badge-yes">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-no">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>유사한 개념인데 다른 필드명으로 사용됨 - 통일 필요</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 5. 전체 필드 매핑 상세 분석 -->
        <div class="section">
            <h2>📋 전체 필드 매핑 상세 분석</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Rules.yaml</th>
                        <th>Data Access</th>
                        <th>DB 존재</th>
                        <th>데이터 타입</th>
                        <th>DB 테이블</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataAccessUsage as $usage): ?>
                        <tr>
                            <td><strong><code><?php echo htmlspecialchars($usage['field']); ?></code></strong></td>
                            <td>
                                <span class="badge badge-yes">Yes</span>
                            </td>
                            <td>
                                <?php if ($usage['in_data_access']): ?>
                                    <span class="badge badge-yes">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-error">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($usage['in_db']): ?>
                                    <span class="badge badge-yes">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-no">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $usage['data_type'] === 'survdata' ? 'surv' : ($usage['data_type'] === 'sysdata' ? 'sys' : ($usage['data_type'] === 'gendata' ? 'gen' : 'hybrid')); ?>">
                                    <?php echo $usage['data_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usage['db_table']): ?>
                                    <code><?php echo htmlspecialchars($usage['db_table']); ?></code>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($usage['in_data_access'] && $usage['in_db']) {
                                    echo '<span class="badge badge-success">완료</span>';
                                } elseif ($usage['in_data_access']) {
                                    echo '<span class="badge badge-warning">부분</span>';
                                } else {
                                    echo '<span class="badge badge-error">누락</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 전체 필드 목록 -->
        <div class="section">
            <h2>📋 전체 필드 목록</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Rules.yaml 필드 (<?php echo count($rulesFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($rulesFields as $field): ?>
                            <span class="field-item badge-rule"><?php echo htmlspecialchars($field); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Data Access 필드 (<?php echo count($dataAccessFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($dataAccessFields as $field): ?>
                            <span class="field-item badge-success"><?php echo htmlspecialchars($field); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


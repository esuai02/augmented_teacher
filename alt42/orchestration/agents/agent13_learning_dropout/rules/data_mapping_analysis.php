<?php
/**
 * Agent 13 데이터 매핑 분석 도구
 * rules.yaml 데이터 | DB 존재 여부 | 설문 데이터(survdata)인지 sysdata인지 gendata인지 식별
 * data_access.php에서 적용여부 | DB에 있는데 rules.yaml에 사용하지 않는 데이터
 * 같은 데이터 혹은 유사 데이터인데 매핑 불일치하는 경우 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent13_learning_dropout/rules/
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
    
    // $context['필드명'] = 패턴으로 필드 추출
    preg_match_all('/\$context\[\'([^\']+)\'\]\s*=/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

// agent.php에서 사용하는 필드 추출
$agentPhpPath = __DIR__ . '/../agent.php';
$agentPhpContent = file_exists($agentPhpPath) ? file_get_contents($agentPhpPath) : '';

$agentPhpFields = [];
if (!empty($agentPhpContent)) {
    // $data['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$data\[\'([^\']+)\'\]/', $agentPhpContent, $matches);
    if (!empty($matches[1])) {
        $agentPhpFields = array_unique($matches[1]);
        sort($agentPhpFields);
    }
    
    // $context['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$context\[\'([^\']+)\'\]/', $agentPhpContent, $matches);
    if (!empty($matches[1])) {
        $agentPhpFields = array_merge($agentPhpFields, $matches[1]);
        $agentPhpFields = array_unique($agentPhpFields);
        sort($agentPhpFields);
    }
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];

// abessi_today 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('abessi_today'))) {
    $dbTables[] = 'abessi_today';
    try {
        $columns = $DB->get_columns('abessi_today');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'abessi_today.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from abessi_today: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// abessi_messages 테이블 구조 확인
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

// abessi_tracking 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('abessi_tracking'))) {
    $dbTables[] = 'abessi_tracking';
    try {
        $columns = $DB->get_columns('abessi_tracking');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'abessi_tracking.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from abessi_tracking: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// abessi_indicators 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('abessi_indicators'))) {
    $dbTables[] = 'abessi_indicators';
    try {
        $columns = $DB->get_columns('abessi_indicators');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'abessi_indicators.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from abessi_indicators: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

/**
 * 데이터 타입 식별 함수 (survdata/sysdata/gendata/hybriddata)
 */
function identifyDataType($fieldName, $rulesContent, $dataAccessContent) {
    $type = 'unknown';
    $evidence = [];
    
    // survdata: 설문/입력에서 수집되는 데이터
    if (preg_match('/collect_info:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
        $type = 'survdata';
        $evidence[] = 'rules.yaml에서 collect_info로 수집';
    }
    
    if (preg_match('/question:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'survdata';
            $evidence[] = 'rules.yaml에서 질문으로 수집';
        }
    }
    
    // sysdata: DB에서 직접 가져오는 데이터
    if (preg_match('/\$DB->(get_record|get_records|get_record_sql).*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent) ||
        preg_match('/SELECT\s+.*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent) ||
        preg_match('/\$context\[["\']' . preg_quote($fieldName, '/') . '["\']\]\s*=/i', $dataAccessContent)) {
        if ($type === 'unknown') {
            $type = 'sysdata';
            $evidence[] = 'data_access.php에서 DB 직접 조회';
        } else if ($type === 'survdata') {
            $type = 'hybriddata';
            $evidence[] = 'survdata + sysdata 조합';
        }
    }
    
    // gendata: 생성 규칙이 있는 데이터
    if (preg_match('/generation_rule.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/generate.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/calculate.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/LLM|AI|프롬프트.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'gendata';
            $evidence[] = 'rules.yaml에서 생성 규칙 존재';
        } else if ($type === 'sysdata') {
            $type = 'hybriddata';
            $evidence[] = 'sysdata 기반 생성';
        }
    }
    
    // depends_on이 있으면 계산된 데이터 (hybriddata)
    if (preg_match('/depends_on.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown' || $type === 'sysdata') {
            $type = 'hybriddata';
            $evidence[] = '다른 필드에 의존하는 계산 데이터';
        }
    }
    
    // Agent 13 특화 필드 분류
    $survFields = ['current_math_unit', 'problem_difficulty', 'learning_stage', 
                   'academy_class_understanding', 'academy_homework_burden', 
                   'math_level', 'math_learning_style'];
    
    $sysFields = ['ninactive', 'nlazy', 'nlazy_blocks', 'eye_count', 'eye_flag', 
                  'eye_timespent_min', 'tlaststroke_min', 'npomodoro', 'kpomodoro', 
                  'pmresult', 'activetime', 'checktime', 'status', 'type', 
                  'timecreated', 'timemodified', 'duration', 'text', 'hide'];
    
    $genFields = ['risk_tier', 'dropout_prediction', 'intervention_timing', 
                  'intervention_message', 'insights', 'recommendations', 
                  'window', 'metrics'];
    
    if (in_array($fieldName, $survFields)) {
        if ($type === 'unknown') {
            $type = 'survdata';
            $evidence[] = 'Agent 13 설문 수집 필드';
        }
    } elseif (in_array($fieldName, $sysFields)) {
        if ($type === 'unknown') {
            $type = 'sysdata';
            $evidence[] = 'Agent 13 시스템 데이터 필드';
        }
    } elseif (in_array($fieldName, $genFields)) {
        if ($type === 'unknown') {
            $type = 'gendata';
            $evidence[] = 'Agent 13 생성 데이터 필드';
        }
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence
    ];
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
    
    // abessi_today 테이블 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('abessi_today'))) {
        try {
            $columns = $DB->get_columns('abessi_today');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('abessi_today', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'abessi_today';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking abessi_today: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // abessi_tracking 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('abessi_tracking'))) {
        try {
            $columns = $DB->get_columns('abessi_tracking');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('abessi_tracking', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'abessi_tracking';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking abessi_tracking: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    if ($exists) {
        $dataTypeInfo = identifyDataType($field, $rulesYamlContent, $dataAccessContent);
        $dbDataExists[] = [
            'field' => $field,
            'table' => $tableName,
            'type' => $dataTypeInfo['type'] ?? 'unknown',
            'sample' => $sampleValue
        ];
    }
}

// 각 필드의 데이터 타입 식별
$fieldDataTypes = [];
$allFields = array_unique(array_merge($rulesFields, $dataAccessFields, $agentPhpFields));

foreach ($allFields as $field) {
    $fieldDataTypes[$field] = identifyDataType($field, $rulesYamlContent, $dataAccessContent);
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

// 4. agent.php에서 사용하는데 rules.yaml에 없는 필드
$inAgentPhpNotInRules = array_diff($agentPhpFields, $rulesFields);

// 5. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
$similarFields = [
    ['ninactive', 'nlazy'],
    ['eye_count', 'eye_flag'],
    ['tlaststroke_min', 'tlaststroke'],
    ['npomodoro', 'kpomodoro'],
    ['current_math_unit', 'math_unit'],
    ['problem_difficulty', 'difficulty_level'],
    ['academy_class_understanding', 'class_understanding'],
    ['academy_homework_burden', 'homework_burden']
];

foreach ($similarFields as $pair) {
    $field1 = $pair[0];
    $field2 = $pair[1];
    $inRules1 = in_array($field1, $rulesFields);
    $inRules2 = in_array($field2, $rulesFields);
    $inDataAccess1 = in_array($field1, $dataAccessFields);
    $inDataAccess2 = in_array($field2, $dataAccessFields);
    
    if (($inRules1 && $inRules2) || ($inDataAccess1 && $inDataAccess2)) {
        $mappingMismatches[] = [
            'field1' => $field1,
            'field2' => $field2,
            'type' => 'duplicate_field',
            'location' => ($inRules1 && $inRules2) ? 'rules.yaml' : 'data_access.php'
        ];
    }
}

// 6. data_access.php에서 적용 여부 확인
$dataAccessApplied = [];
foreach ($rulesFields as $field) {
    $applied = in_array($field, $dataAccessFields);
    $dataAccessApplied[$field] = $applied;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 13</title>
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            color: #ef4444;
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
            color: #ef4444;
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
            background: #e7d4f8;
            color: #6b21a8;
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
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .evidence-list {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .evidence-list li {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>📊 Agent 13 데이터 매핑 분석 리포트</h1>
            <p>rules.yaml 데이터 | DB 존재 여부 | survdata/sysdata/gendata 식별 | data_access.php 적용 여부</p>
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
                <h3>Agent.php 필드</h3>
                <div class="number"><?php echo count($agentPhpFields); ?></div>
            </div>
            <div class="stat-card">
                <h3>DB 테이블</h3>
                <div class="number"><?php echo count($dbTables); ?></div>
            </div>
        </div>
        
        <!-- 전체 필드 데이터 타입 분석 -->
        <div class="section">
            <h2>📋 전체 필드 데이터 타입 분석</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>데이터 타입</th>
                        <th>Rules.yaml</th>
                        <th>Data Access</th>
                        <th>Agent.php</th>
                        <th>증거</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allFields as $field): ?>
                        <?php 
                        $typeInfo = $fieldDataTypes[$field] ?? ['type' => 'unknown', 'evidence' => []];
                        $inRules = in_array($field, $rulesFields);
                        $inDataAccess = in_array($field, $dataAccessFields);
                        $inAgentPhp = in_array($field, $agentPhpFields);
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($field); ?></code></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $typeInfo['type'] === 'survdata' ? 'surv' : 
                                        ($typeInfo['type'] === 'sysdata' ? 'sys' : 
                                        ($typeInfo['type'] === 'gendata' ? 'gen' : 
                                        ($typeInfo['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); 
                                ?>">
                                    <?php echo htmlspecialchars($typeInfo['type']); ?>
                                </span>
                            </td>
                            <td><?php echo $inRules ? '✅' : '❌'; ?></td>
                            <td><?php echo $inDataAccess ? '✅' : '❌'; ?></td>
                            <td><?php echo $inAgentPhp ? '✅' : '❌'; ?></td>
                            <td>
                                <ul class="evidence-list">
                                    <?php foreach ($typeInfo['evidence'] as $ev): ?>
                                        <li><?php echo htmlspecialchars($ev); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                            <?php $typeInfo = $fieldDataTypes[$field] ?? ['type' => 'unknown', 'evidence' => []]; ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $typeInfo['type'] === 'survdata' ? 'surv' : 
                                            ($typeInfo['type'] === 'sysdata' ? 'sys' : 
                                            ($typeInfo['type'] === 'gendata' ? 'gen' : 
                                            ($typeInfo['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); 
                                    ?>">
                                        <?php echo htmlspecialchars($typeInfo['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($typeInfo['type'] === 'survdata') {
                                        echo '설문 응답 데이터 (사용자 입력) - data_access.php에 수집 로직 필요';
                                    } elseif ($typeInfo['type'] === 'sysdata') {
                                        echo '시스템 데이터 (DB에서 자동 조회) - data_access.php에 DB 조회 로직 필요';
                                    } elseif ($typeInfo['type'] === 'gendata') {
                                        echo '생성 데이터 (계산/추론) - data_access.php에 생성 로직 필요';
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
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>데이터 타입</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDataAccessNotInRules as $field): ?>
                            <?php $typeInfo = $fieldDataTypes[$field] ?? ['type' => 'unknown', 'evidence' => []]; ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $typeInfo['type'] === 'survdata' ? 'surv' : 
                                            ($typeInfo['type'] === 'sysdata' ? 'sys' : 
                                            ($typeInfo['type'] === 'gendata' ? 'gen' : 
                                            ($typeInfo['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); 
                                    ?>">
                                        <?php echo htmlspecialchars($typeInfo['type']); ?>
                                    </span>
                                </td>
                                <td>rules.yaml에 필드 정의 추가 필요</td>
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
                                    <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : ($item['type'] === 'gendata' ? 'gen' : ($item['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); ?>">
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
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDbNotInRules as $dbField): ?>
                            <?php 
                            $parts = explode('.', $dbField);
                            $table = $parts[0] ?? '';
                            $field = $parts[1] ?? $dbField;
                            $typeInfo = $fieldDataTypes[$field] ?? ['type' => 'unknown', 'evidence' => []];
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $typeInfo['type'] === 'survdata' ? 'surv' : 
                                            ($typeInfo['type'] === 'sysdata' ? 'sys' : 
                                            ($typeInfo['type'] === 'gendata' ? 'gen' : 
                                            ($typeInfo['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); 
                                    ?>">
                                        <?php echo htmlspecialchars($typeInfo['type']); ?>
                                    </span>
                                </td>
                                <td>rules.yaml에 필드 추가하여 활용 가능</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 4. 매핑 불일치 -->
        <div class="section">
            <h2>🔄 매핑 불일치 (같은 데이터인데 다른 이름으로 사용)</h2>
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
                            <th>타입</th>
                            <th>위치</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappingMismatches as $mismatch): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($mismatch['field1']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($mismatch['field2']); ?></code></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($mismatch['type']); ?></span></td>
                                <td><?php echo htmlspecialchars($mismatch['location']); ?></td>
                                <td>유사한 개념인데 다른 필드명으로 사용됨 - 통일 필요</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 5. Data Access 적용 여부 -->
        <div class="section">
            <h2>✅ Data Access 적용 여부</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>데이터 타입</th>
                        <th>적용 여부</th>
                        <th>설명</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rulesFields as $field): ?>
                        <?php 
                        $applied = $dataAccessApplied[$field] ?? false;
                        $typeInfo = $fieldDataTypes[$field] ?? ['type' => 'unknown', 'evidence' => []];
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($field); ?></code></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $typeInfo['type'] === 'survdata' ? 'surv' : 
                                        ($typeInfo['type'] === 'sysdata' ? 'sys' : 
                                        ($typeInfo['type'] === 'gendata' ? 'gen' : 
                                        ($typeInfo['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); 
                                ?>">
                                    <?php echo htmlspecialchars($typeInfo['type']); ?>
                                </span>
                            </td>
                            <td><?php echo $applied ? '✅ 적용됨' : '❌ 미적용'; ?></td>
                            <td>
                                <?php if (!$applied): ?>
                                    data_access.php에 구현 필요
                                <?php else: ?>
                                    정상적으로 구현됨
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


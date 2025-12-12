<?php
/**
 * Agent 11 - Problem Notes 데이터 매핑 분석 도구
 * view_reports.php에서 사용하는 데이터와 rules.yaml, data_access.php를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent11_problem_notes/rules/
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
    
    // field_path: 패턴으로 필드 추출
    preg_match_all('/field_path:\s*"([^"]+)"/', $rulesYamlContent, $matches);
    if (!empty($matches[1])) {
        $rulesFields = array_merge($rulesFields, $matches[1]);
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
    
    // 배열 필드 추출 (attempt_notes, preparation_notes 등)
    preg_match_all('/\'(attempt_notes|preparation_notes|essay_assessments|error_patterns|note_statistics)\'/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    // SQL SELECT 필드 추출
    preg_match_all('/SELECT\s+([^F]+)\s+FROM/i', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $selectFields = explode(',', $matches[1][0]);
        foreach ($selectFields as $field) {
            $field = trim($field);
            if (!empty($field) && $field !== '*') {
                $dataAccessFields[] = $field;
            }
        }
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

// view_reports.php에서 사용하는 데이터 필드 추출 (agent11 관련 부분만)
$viewReportsPath = __DIR__ . '/../../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
if (!file_exists($viewReportsPath)) {
    $viewReportsPath = __DIR__ . '/../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
}

$viewReportsFields = [];
$viewReportsTables = [];
$viewReportsDataTypes = [];

if (file_exists($viewReportsPath)) {
    $viewReportsContent = file_get_contents($viewReportsPath);
    
    // 테이블명 추출 (abessi_messages 관련)
    preg_match_all('/\{([a-z_]+)\}/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_unique($matches[1]);
    }
    
    // error_note 관련 필드 추출
    if (strpos($viewReportsContent, 'error_note') !== false) {
        $viewReportsFields[] = 'error_note';
    }
    
    // abessi_messages 관련 필드 추출
    preg_match_all('/mdl_abessi_messages[^;]*SELECT\s+([^F]+)\s+FROM/i', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $selectFields = explode(',', $matches[1][0]);
        foreach ($selectFields as $field) {
            $field = trim($field);
            if (!empty($field) && $field !== '*') {
                $viewReportsFields[] = $field;
            }
        }
    }
    
    // student_check, turn, hide 등 필드 추출
    preg_match_all('/(student_check|turn|hide|timemodified)/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    $viewReportsFields = array_unique($viewReportsFields);
    sort($viewReportsFields);
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];

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

// 실제 DB 데이터 존재 여부 확인 (rules.yaml 필드 기준)
$dbDataExists = [];
$dbDataSample = [];

foreach ($rulesFields as $field) {
    $exists = false;
    $tableName = '';
    $sampleValue = null;
    
    // abessi_messages 테이블 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('abessi_messages'))) {
        try {
            $columns = $DB->get_columns('abessi_messages');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record_sql(
                    "SELECT * FROM {abessi_messages} 
                     WHERE contentstype = ? AND userid = ? 
                     ORDER BY timecreated DESC LIMIT 1",
                    [2, $studentid],
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
function classifyDataType($fieldName, $tableName = '') {
    // survdata: 사용자 입력/설문 데이터
    $survFields = ['contentstitle', 'status', 'wboardid'];
    
    // sysdata: 시스템/DB 자동 생성 데이터
    $sysFields = ['id', 'userid', 'nstroke', 'tlaststroke', 'timecreated', 'usedtime', 
                  'contentstype', 'student_check', 'turn', 'hide', 'timemodified'];
    
    // gendata: 계산/추론된 데이터
    $genFields = ['reflection_score', 'completeness_score', 'engagement_score', 
                  'stroke_density', 'error_patterns', 'note_statistics',
                  'unit_review_priority', 'error_cause_type', 'routine_stage'];
    
    // hybriddata: 복합 계산 데이터
    $hybridFields = ['note_quality_assessment', 'concentration_level', 
                     'reflection_quality_level', 'improvement_level'];
    
    if (in_array($fieldName, $survFields) || strpos($fieldName, 'title') !== false) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields) || strpos($tableName, 'messages') !== false) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields) || strpos($fieldName, '_score') !== false) {
        return 'gendata';
    } elseif (in_array($fieldName, $hybridFields)) {
        return 'hybriddata';
    } else {
        return 'unknown';
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
    $found = false;
    foreach ($rulesFields as $ruleField) {
        if (strpos($ruleField, $fieldName) !== false || strpos($fieldName, $ruleField) !== false) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $inDbNotInRules[] = $dbField;
    }
}

// 4. view_reports.php에서 사용하는데 rules.yaml에 없는 필드
$inViewReportsNotInRules = [];
foreach ($viewReportsFields as $field) {
    $found = false;
    foreach ($rulesFields as $ruleField) {
        if (strpos($ruleField, $field) !== false || strpos($field, $ruleField) !== false) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $inViewReportsNotInRules[] = $field;
    }
}

// 5. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
$similarFields = [
    ['error_note', 'preparation_notes'],
    ['nstroke', 'stroke_count'],
    ['usedtime', 'time_used'],
    ['contentstitle', 'note_title'],
    ['tlaststroke', 'last_stroke_time']
];

foreach ($similarFields as $pair) {
    $field1 = $pair[0];
    $field2 = $pair[1];
    $inViewReports = in_array($field1, $viewReportsFields);
    $inRules = false;
    foreach ($rulesFields as $ruleField) {
        if (strpos($ruleField, $field2) !== false) {
            $inRules = true;
            break;
        }
    }
    
    if ($inViewReports && $inRules) {
        $mappingMismatches[] = [
            'view_reports_field' => $field1,
            'rules_field' => $field2,
            'type' => 'similar_concept'
        ];
    }
}

// 6. DB에 실제 데이터가 있는데 rules.yaml에 없는 필드 (기존 로직 유지)
$dbFieldsWithDataNotInRules = [];
try {
    $sampleData = $DB->get_record_sql(
        "SELECT * FROM {abessi_messages} 
         WHERE contentstype = ? AND userid = ? 
         ORDER BY timecreated DESC LIMIT 1",
        [2, $studentid],
        IGNORE_MISSING
    );
    
    if ($sampleData) {
        foreach ($sampleData as $key => $value) {
            if ($value !== null && $value !== '') {
                $fieldName = $key;
                $found = false;
                foreach ($rulesFields as $ruleField) {
                    if (strpos($ruleField, $fieldName) !== false || strpos($fieldName, $ruleField) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $dbFieldsWithDataNotInRules[] = [
                        'field' => 'abessi_messages.' . $key,
                        'sample' => is_string($value) ? substr($value, 0, 50) : $value
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error checking DB fields not in rules: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 11 (Problem Notes)</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            color: #10b981;
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
            color: #10b981;
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
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-form input {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .filter-form button {
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .filter-form button:hover {
            background: #059669;
        }
        
        .sample-data {
            font-size: 0.8rem;
            color: #6b7280;
            font-style: italic;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>📊 데이터 매핑 분석 리포트 - Agent 11 (Problem Notes)</h1>
            <p>view_reports.php vs rules.yaml vs data_access.php 비교 분석</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">학생 ID: <?php echo $studentid; ?></p>
        </div>
        
        <div class="filter-section">
            <form method="get" class="filter-form">
                <label>학생 ID:</label>
                <input type="number" name="studentid" value="<?php echo $studentid; ?>" placeholder="학생 ID">
                <button type="submit">분석 실행</button>
            </form>
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
                                    <span class="badge badge-<?php 
                                        echo $dataType === 'survdata' ? 'surv' : 
                                            ($dataType === 'sysdata' ? 'sys' : 
                                            ($dataType === 'gendata' ? 'gen' : 
                                            ($dataType === 'hybriddata' ? 'hybrid' : 'info'))); 
                                    ?>">
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
                                        echo '생성 데이터 (LLM/계산으로 생성)';
                                    } elseif ($dataType === 'hybriddata') {
                                        echo '복합 데이터 (계산/조합)';
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
                            <th>DB 존재 여부</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDbNotInRules as $dbField): ?>
                            <?php 
                            $parts = explode('.', $dbField);
                            $table = $parts[0] ?? '';
                            $field = $parts[1] ?? $dbField;
                            $dataType = classifyDataType($field, $table);
                            $exists = $dbDataExists[$dbField] ?? false;
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $dataType === 'survdata' ? 'surv' : 
                                            ($dataType === 'sysdata' ? 'sys' : 
                                            ($dataType === 'gendata' ? 'gen' : 
                                            ($dataType === 'hybriddata' ? 'hybrid' : 'info'))); 
                                    ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                <td>
                                    <?php if ($exists): ?>
                                        <span class="badge badge-success">존재함</span>
                                        <?php if (isset($dbDataSample[$dbField])): ?>
                                            <div class="sample-data">샘플: <?php echo htmlspecialchars($dbDataSample[$dbField]); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-warning">없음</span>
                                    <?php endif; ?>
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
                                    <span class="badge badge-<?php 
                                        echo $dataType === 'survdata' ? 'surv' : 
                                            ($dataType === 'sysdata' ? 'sys' : 
                                            ($dataType === 'gendata' ? 'gen' : 
                                            ($dataType === 'hybriddata' ? 'hybrid' : 'info'))); 
                                    ?>">
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
                                    } elseif ($dataType === 'hybriddata') {
                                        echo '복합 데이터 (계산/조합)';
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
        
        <!-- 6. DB에 실제 데이터가 있는데 rules.yaml에 없는 필드 -->
        <div class="section">
            <h2>💾 DB에 실제 데이터가 있는데 rules.yaml에 없는 필드</h2>
            <?php if (empty($dbFieldsWithDataNotInRules)): ?>
                <div class="empty-state">
                    <p>DB에 있는 모든 데이터가 rules.yaml에서 사용되고 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($dbFieldsWithDataNotInRules); ?>개 필드가 DB에 데이터가 있지만 rules.yaml에서 사용되지 않습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>DB 필드</th>
                            <th>데이터 타입</th>
                            <th>샘플 데이터</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dbFieldsWithDataNotInRules as $item): ?>
                            <?php 
                            $parts = explode('.', $item['field']);
                            $table = $parts[0] ?? '';
                            $field = $parts[1] ?? $item['field'];
                            $dataType = classifyDataType($field, $table);
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $dataType === 'survdata' ? 'surv' : 
                                            ($dataType === 'sysdata' ? 'sys' : 
                                            ($dataType === 'gendata' ? 'gen' : 
                                            ($dataType === 'hybriddata' ? 'hybrid' : 'info'))); 
                                    ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['sample'] !== null): ?>
                                        <code style="font-size: 0.85rem;"><?php echo htmlspecialchars($item['sample']); ?></code>
                                    <?php else: ?>
                                        <span class="badge badge-info">데이터 있음</span>
                                    <?php endif; ?>
                                </td>
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
                    <h3 style="color: #10b981; margin-bottom: 1rem;">Rules.yaml 필드 (<?php echo count($rulesFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($rulesFields as $field): ?>
                            <span class="field-item badge-rule"><?php echo htmlspecialchars($field); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #10b981; margin-bottom: 1rem;">Data Access 필드 (<?php echo count($dataAccessFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($dataAccessFields as $field): ?>
                            <span class="field-item badge-success"><?php echo htmlspecialchars($field); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 style="color: #10b981; margin-bottom: 1rem;">View Reports 필드 (<?php echo count($viewReportsFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($viewReportsFields as $field): ?>
                            <span class="field-item badge-surv"><?php echo htmlspecialchars($field); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


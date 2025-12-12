<?php
/**
 * Agent 16 데이터 매핑 분석 도구
 * rules.yaml, data_access.php, DB 데이터를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent16_interaction_preparation/rules/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// xmldb_table 클래스 로드를 위해 ddllib.php require
if (isset($CFG) && isset($CFG->libdir)) {
    require_once($CFG->libdir.'/ddllib.php');
}

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
    
    // $scenario->필드명 패턴으로 필드 추출
    preg_match_all('/\$scenario->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];

// mdl_agent16_interaction_scenarios 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('agent16_interaction_scenarios'))) {
    $dbTables[] = 'agent16_interaction_scenarios';
    try {
        $columns = $DB->get_columns('agent16_interaction_scenarios');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'agent16_interaction_scenarios.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from agent16_interaction_scenarios: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// mdl_alt42o_onboarding 테이블 구조 확인 (Agent 01 연계)
if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_onboarding'))) {
    $dbTables[] = 'alt42o_onboarding';
    try {
        $columns = $DB->get_columns('alt42o_onboarding');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'alt42o_onboarding.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from alt42o_onboarding: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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

// 데이터 타입 분류 함수
function classifyDataType($fieldName, $tableName = '') {
    // survdata: 설문/사용자 입력 데이터
    $survFields = ['math_learning_stage', 'math_recent_accuracy', 'unit_accuracy', 'weak_units', 
                   'pre_class_needed', 'post_class_needed', 'class_content', 'understanding_level',
                   'student_question_frequency', 'student_motivation', 'student_fatigue',
                   'preparation_type', 'problem_type'];
    
    // sysdata: 시스템/DB 자동 조회 데이터
    $sysFields = ['userid', 'id', 'created_at', 'updated_at', 'guide_mode', 'scenario',
                  'vibe_coding_prompt', 'db_tracking_prompt', 'timecreated', 'timemodified',
                  'math_learning_style', 'student_level', 'academy_class_time', 'current_time',
                  'current_unit', 'current_unit_accuracy', 'exam_d_day', 'time_limit_approaching',
                  'problem_solving_time', 'stuck_threshold_minutes', 'expected_speed'];
    
    // gendata: 계산/추론/생성 데이터
    $genFields = ['student_level', 'weak_units', 'calculation_error_pattern', 'worldview',
                  'narrative_theme', 'tone', 'character', 'effectiveness_score',
                  'learning_continuity', 'accuracy_improvement', 'preferred_worldview'];
    
    if (in_array($fieldName, $survFields) || strpos($tableName, 'survey') !== false) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields) || strpos($tableName, 'agent16') !== false || 
              strpos($tableName, 'onboarding') !== false) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields)) {
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

// 4. data_access.php에서 사용하는데 DB에 없는 필드
$inDataAccessNotInDb = [];
foreach ($dataAccessFields as $field) {
    $found = false;
    foreach ($dbFields as $dbField) {
        $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
        if ($field === $dbFieldName) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $inDataAccessNotInDb[] = $field;
    }
}

// 5. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
// 유사 필드 매핑
$similarFields = [
    ['math_learning_stage', 'learning_stage'],
    ['math_recent_accuracy', 'recent_accuracy'],
    ['math_learning_style', 'learning_style'],
    ['student_level', 'level'],
    ['weak_units', 'math_weak_units'],
    ['guide_mode', 'worldview']
];

foreach ($similarFields as $pair) {
    $field1 = $pair[0];
    $field2 = $pair[1];
    $inRules = in_array($field1, $rulesFields);
    $inDataAccess = in_array($field2, $dataAccessFields);
    
    if ($inRules && $inDataAccess && $field1 !== $field2) {
        $mappingMismatches[] = [
            'rules_field' => $field1,
            'data_access_field' => $field2,
            'type' => 'similar_concept'
        ];
    }
}

// 중복 선언 제거: 위에서 이미 $dbDataExists가 선언되고 샘플 데이터와 함께 초기화됨
// 이 부분은 제거됨 (라인 129-182에서 이미 처리됨)

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 16</title>
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
    </style>
</head>
<body>
    <div class="container">
        <a href="../../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>🎭 Agent 16 - 데이터 매핑 분석 리포트</h1>
            <p>rules.yaml vs data_access.php vs DB 데이터 비교 분석</p>
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
                <div class="field-list">
                    <?php foreach ($inRulesNotInDataAccess as $field): ?>
                        <span class="field-item badge-error"><?php echo htmlspecialchars($field); ?></span>
                    <?php endforeach; ?>
                </div>
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
        
        <!-- 4. Data_access.php에서 사용하는데 DB에 없는 필드 -->
        <div class="section">
            <h2>📄 Data_access.php에서 사용하는데 DB에 없는 필드</h2>
            <?php if (empty($inDataAccessNotInDb)): ?>
                <div class="empty-state">
                    <p>모든 필드가 DB에 존재합니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($inDataAccessNotInDb); ?>개 필드가 DB에 없습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>데이터 타입</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDataAccessNotInDb as $field): ?>
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
                            <th>Rules.yaml 필드</th>
                            <th>Data Access 필드</th>
                            <th>타입</th>
                            <th>설명</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappingMismatches as $mismatch): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($mismatch['rules_field']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($mismatch['data_access_field']); ?></code></td>
                                <td><span class="badge badge-warning"><?php echo htmlspecialchars($mismatch['type']); ?></span></td>
                                <td>유사한 개념인데 다른 필드명으로 사용됨</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 6. DB 데이터 존재 여부 -->
        <div class="section">
            <h2>✅ DB 데이터 존재 여부 (rules.yaml 필드 기준)</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <h3 style="color: #10b981; margin-bottom: 1rem;">✅ DB에 존재하는 필드 (<?php echo count($dbDataExists); ?>)</h3>
                    <?php if (empty($dbDataExists)): ?>
                        <div class="empty-state">
                            <p>DB에 존재하는 필드가 없습니다.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>필드명</th>
                                    <th>테이블</th>
                                    <th>타입</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbDataExists as $item): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                                        <td><code><?php echo htmlspecialchars($item['table']); ?></code></td>
                                        <td>
                                            <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : 'gen'); ?>">
                                                <?php echo $item['type']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 style="color: #dc2626; margin-bottom: 1rem;">❌ DB에 없는 필드 (<?php echo count($dbDataMissing); ?>)</h3>
                    <?php if (empty($dbDataMissing)): ?>
                        <div class="empty-state">
                            <p>모든 필드가 DB에 존재합니다. ✅</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>필드명</th>
                                    <th>타입</th>
                                    <th>설명</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbDataMissing as $item): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                                        <td>
                                            <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : 'gen'); ?>">
                                                <?php echo $item['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($item['type'] === 'survdata') {
                                                echo '설문으로 수집 필요';
                                            } elseif ($item['type'] === 'sysdata') {
                                                echo '시스템 데이터 (다른 테이블 또는 계산 필요)';
                                            } elseif ($item['type'] === 'gendata') {
                                                echo '생성/계산 데이터';
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
            </div>
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


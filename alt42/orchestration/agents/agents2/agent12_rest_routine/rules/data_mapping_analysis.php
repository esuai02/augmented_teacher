<?php
/**
 * 데이터 매핑 분석 도구 - Agent 12 (Rest Routine)
 * view_reports.php에서 사용하는 데이터와 rules.yaml, data_access.php를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent12_rest_routine/rules/
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
    
    // field: 패턴 (따옴표 없음)
    preg_match_all('/field:\s*([a-zA-Z_][a-zA-Z0-9_]*)/', $rulesYamlContent, $matches);
    if (!empty($matches[1])) {
        $rulesFields = array_merge($rulesFields, $matches[1]);
        $rulesFields = array_unique($rulesFields);
        sort($rulesFields);
    }
    
    // source_type에서 필드 추출
    preg_match_all('/source_type:\s*["\']([^"\']+)["\']/', $rulesYamlContent, $matches);
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
    
    // $context["필드명"] 패턴
    preg_match_all('/\$context\["([^"]+)"\]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    // $rest_routine->필드명 패턴으로 필드 추출
    preg_match_all('/\$rest_routine->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    // 변수 할당 패턴 ($field = ...)
    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        // PHP 내장 변수 제외
        $excludeVars = ['DB', 'USER', 'PAGE', 'OUTPUT', 'context', 'rest_routine', 'data', 'result', 'sql', 'params', 'record', 'records'];
        foreach ($matches[1] as $var) {
            if (!in_array($var, $excludeVars) && !preg_match('/^(i|j|k|n|count|key|value|item)$/', $var)) {
                $dataAccessFields[] = $var;
            }
        }
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
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
$viewReportsDataTypes = [];

if (file_exists($viewReportsPath)) {
    $viewReportsContent = file_get_contents($viewReportsPath);
    
    // 테이블명 추출 (Moodle 테이블 포맷)
    preg_match_all('/\{([a-z_]+)\}/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_unique($matches[1]);
    }
    
    // mdl_ 접두사 테이블명 추출
    preg_match_all('/mdl_([a-z_]+)/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_merge($viewReportsTables, $matches[1]);
        $viewReportsTables = array_unique($viewReportsTables);
    }
    
    // $data['필드명'] 패턴으로 필드 추출 (JSON 응답 데이터)
    preg_match_all('/\$data\[\'([^\']+)\'\]/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // $data["필드명"] 패턴
    preg_match_all('/\$data\["([^"]+)"\]/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // 설문 응답 필드 (responses 배열)
    preg_match_all('/\'([a-z_]+)\'\s*=>/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // SELECT 문에서 필드 추출
    preg_match_all('/SELECT\s+([^F]+)\s+FROM/i', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $selectPart) {
            preg_match_all('/([a-z_]+)/', $selectPart, $fieldMatches);
            if (!empty($fieldMatches[1])) {
                $viewReportsFields = array_merge($viewReportsFields, $fieldMatches[1]);
            }
        }
    }
    
    $viewReportsFields = array_unique($viewReportsFields);
    sort($viewReportsFields);
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];
$dbFieldDetails = []; // 테이블별 필드 상세 정보

// agent12 관련 테이블들 확인
$potentialTables = [
    'alt42_goinghome',
    'alt42_calmness', 
    'abessi_tracking',
    'abessi_messages',
    'alt42o_onboarding',
    'alt42_rest_routine', // agent12 전용 테이블 (있다면)
    'alt42r_rest', // agent12 전용 테이블 (있다면)
];

foreach ($potentialTables as $tableName) {
    if ($DB->get_manager()->table_exists(new xmldb_table($tableName))) {
        $dbTables[] = $tableName;
        try {
            $columns = $DB->get_columns($tableName);
            foreach ($columns as $colName => $colInfo) {
                $fullFieldName = $tableName . '.' . $colName;
                $dbFields[] = $fullFieldName;
                $dbFieldDetails[$fullFieldName] = [
                    'table' => $tableName,
                    'field' => $colName,
                    'type' => $colInfo->meta_type ?? 'unknown',
                    'exists' => true
                ];
            }
        } catch (Exception $e) {
            error_log("Error getting columns from {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
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

// 데이터 타입 분류 함수 (agent12에 맞게 수정)
function classifyDataType($fieldName, $tableName = '', $rulesYamlContent = '') {
    // survdata (설문 데이터) - 사용자 입력
    $survFields = [
        'calmness', 'pomodoro', 'inefficiency', 'weekly_goal', 'daily_plan', 
        'pace_anxiety', 'satisfaction', 'boredom', 'stress_level', 'positive_moment', 
        'problem_count', 'error_note', 'concept_study', 'difficulty_level', 
        'easy_problems', 'self_improvement', 'missed_opportunity', 'intuition_solving', 
        'forced_solving', 'questions_asked', 'unsaid_words', 'rest_pattern', 
        'long_problem', 'study_amount',
        // agent12 관련 설문 필드
        'rest_quality', 'rest_duration', 'rest_satisfaction', 'rest_type',
        'rest_effectiveness', 'rest_frequency', 'rest_preference'
    ];
    
    // sysdata (시스템 데이터) - DB에서 자동 조회
    $sysFields = [
        'level', 'timecreated', 'timefinished', 'duration', 'timemodified', 
        'student_check', 'turn', 'hide', 'userid', 'id', 'timestart', 'timeend'
    ];
    
    // gendata (생성 데이터) - AI/계산으로 생성
    $genFields = [
        'calmnessGrade', 'pomodoroUsage', 'errorNoteCount', 'needsAttention',
        'rest_score', 'rest_recommendation', 'rest_analysis'
    ];
    
    // 테이블 기반 분류
    if (strpos($tableName, 'goinghome') !== false || strpos($tableName, 'survey') !== false) {
        return 'survdata';
    }
    
    if (strpos($tableName, 'calmness') !== false || 
        strpos($tableName, 'tracking') !== false || 
        strpos($tableName, 'messages') !== false ||
        strpos($tableName, 'user') !== false) {
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
    
    // rules.yaml에서 source_type 확인
    if (!empty($rulesYamlContent)) {
        $fieldPattern = preg_quote($fieldName, '/');
        if (preg_match('/field:\s*["\']?' . $fieldPattern . '["\']?.*?source_type:\s*["\']([^"\']+)["\']/s', $rulesYamlContent, $matches)) {
            $sourceType = strtolower($matches[1]);
            if (strpos($sourceType, 'survey') !== false || strpos($sourceType, 'surv') !== false) {
                return 'survdata';
            } elseif (strpos($sourceType, 'system') !== false || strpos($sourceType, 'sys') !== false) {
                return 'sysdata';
            } elseif (strpos($sourceType, 'generated') !== false || strpos($sourceType, 'gen') !== false) {
                return 'gendata';
            }
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
    if (!in_array($fieldName, $rulesFields)) {
        $inDbNotInRules[] = $dbField;
    }
}

// 4. view_reports.php에서 사용하는데 rules.yaml에 없는 필드
$inViewReportsNotInRules = array_diff($viewReportsFields, $rulesFields);

// 5. DB에 있는데 data_access.php에서 사용하지 않는 필드
$inDbNotInDataAccess = [];
foreach ($dbFields as $dbField) {
    $fieldName = explode('.', $dbField)[1] ?? $dbField;
    if (!in_array($fieldName, $dataAccessFields)) {
        $inDbNotInDataAccess[] = $dbField;
    }
}

// 6. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
// 유사한 필드명 매칭
$similarFieldPatterns = [
    ['calmness', 'math_confidence', 'confidence_level'],
    ['pomodoro', 'study_hours_per_week', 'study_time'],
    ['error_note', 'math_weak_units', 'weak_points'],
    ['rest_pattern', 'rest_quality', 'rest_satisfaction'],
    ['rest_duration', 'rest_time', 'rest_length']
];

foreach ($similarFieldPatterns as $patternGroup) {
    $foundInViewReports = [];
    $foundInRules = [];
    
    foreach ($patternGroup as $field) {
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
            'type' => 'similar_concept'
        ];
    }
}

// 7. 데이터 타입별 분류 및 통계
$rulesFieldsByType = [
    'survdata' => [],
    'sysdata' => [],
    'gendata' => [],
    'unknown' => []
];

foreach ($rulesFields as $field) {
    $dataType = classifyDataType($field, '', $rulesYamlContent);
    $rulesFieldsByType[$dataType][] = $field;
}

$dbFieldsByType = [
    'survdata' => [],
    'sysdata' => [],
    'gendata' => [],
    'unknown' => []
];

foreach ($dbFields as $dbField) {
    $parts = explode('.', $dbField);
    $table = $parts[0] ?? '';
    $field = $parts[1] ?? $dbField;
    $dataType = classifyDataType($field, $table, $rulesYamlContent);
    $dbFieldsByType[$dataType][] = $dbField;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 12 (Rest Routine)</title>
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
        
        .badge-unknown {
            background: #e5e7eb;
            color: #374151;
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
        
        .type-stats {
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
            background: #e5e7eb;
            border-color: #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>📊 데이터 매핑 분석 리포트 - Agent 12 (Rest Routine)</h1>
            <p>view_reports.php vs rules.yaml vs data_access.php 비교 분석</p>
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
        </div>
        
        <!-- 데이터 타입별 통계 -->
        <div class="section">
            <h2>📈 데이터 타입별 분류 통계</h2>
            <div class="type-stats">
                <div class="type-stat-card survdata">
                    <h3 style="margin-bottom: 0.5rem;">SurvData (설문 데이터)</h3>
                    <p style="font-size: 1.5rem; font-weight: bold;">Rules: <?php echo count($rulesFieldsByType['survdata']); ?></p>
                    <p style="font-size: 0.9rem; color: #666;">DB: <?php echo count($dbFieldsByType['survdata']); ?></p>
                </div>
                <div class="type-stat-card sysdata">
                    <h3 style="margin-bottom: 0.5rem;">SysData (시스템 데이터)</h3>
                    <p style="font-size: 1.5rem; font-weight: bold;">Rules: <?php echo count($rulesFieldsByType['sysdata']); ?></p>
                    <p style="font-size: 0.9rem; color: #666;">DB: <?php echo count($dbFieldsByType['sysdata']); ?></p>
                </div>
                <div class="type-stat-card gendata">
                    <h3 style="margin-bottom: 0.5rem;">GenData (생성 데이터)</h3>
                    <p style="font-size: 1.5rem; font-weight: bold;">Rules: <?php echo count($rulesFieldsByType['gendata']); ?></p>
                    <p style="font-size: 0.9rem; color: #666;">DB: <?php echo count($dbFieldsByType['gendata']); ?></p>
                </div>
                <div class="type-stat-card unknown">
                    <h3 style="margin-bottom: 0.5rem;">Unknown (미분류)</h3>
                    <p style="font-size: 1.5rem; font-weight: bold;">Rules: <?php echo count($rulesFieldsByType['unknown']); ?></p>
                    <p style="font-size: 0.9rem; color: #666;">DB: <?php echo count($dbFieldsByType['unknown']); ?></p>
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
                            <?php $dataType = classifyDataType($field, '', $rulesYamlContent); ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'unknown')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td>data_access.php에서 구현 필요</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 1-1. DB에 실제 데이터가 존재하는 rules.yaml 필드 -->
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
                            <th>DB 타입</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDbNotInRules as $dbField): ?>
                            <?php 
                            $parts = explode('.', $dbField);
                            $table = $parts[0] ?? '';
                            $field = $parts[1] ?? $dbField;
                            $dataType = classifyDataType($field, $table, $rulesYamlContent);
                            $fieldDetail = $dbFieldDetails[$dbField] ?? null;
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'unknown')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td><?php echo $fieldDetail ? htmlspecialchars($fieldDetail['type']) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 4. DB에 있는데 data_access.php에서 사용하지 않는 필드 -->
        <div class="section">
            <h2>🔍 DB에 있는데 data_access.php에서 사용하지 않는 필드</h2>
            <?php if (empty($inDbNotInDataAccess)): ?>
                <div class="empty-state">
                    <p>모든 DB 필드가 data_access.php에서 사용되고 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($inDbNotInDataAccess); ?>개 DB 필드가 data_access.php에서 사용되지 않습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>DB 필드</th>
                            <th>테이블</th>
                            <th>데이터 타입</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inDbNotInDataAccess as $dbField): ?>
                            <?php 
                            $parts = explode('.', $dbField);
                            $table = $parts[0] ?? '';
                            $field = $parts[1] ?? $dbField;
                            $dataType = classifyDataType($field, $table, $rulesYamlContent);
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'unknown')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 5. View_reports.php에서 사용하는데 rules.yaml에 없는 필드 -->
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
                            <?php $dataType = classifyDataType($field, '', $rulesYamlContent); ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($field); ?></code></td>
                                <td>
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'unknown')); ?>">
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
        
        <!-- 6. 매핑 불일치 -->
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
                                        <code><?php echo htmlspecialchars($field); ?></code><br>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php foreach ($mismatch['rules_fields'] as $field): ?>
                                        <code><?php echo htmlspecialchars($field); ?></code><br>
                                    <?php endforeach; ?>
                                </td>
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
                    <h3 style="color: #10b981; margin-bottom: 1rem;">Rules.yaml 필드 (<?php echo count($rulesFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($rulesFields as $field): ?>
                            <?php $dataType = classifyDataType($field, '', $rulesYamlContent); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'unknown')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
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
                            <?php $dataType = classifyDataType($field, '', $rulesYamlContent); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'gendata' ? 'gen' : 'unknown')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- DB 테이블 목록 -->
        <div class="section">
            <h2>🗄️ DB 테이블 목록</h2>
            <?php if (empty($dbTables)): ?>
                <div class="empty-state">
                    <p>확인된 DB 테이블이 없습니다.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>테이블명</th>
                            <th>필드 수</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $tableFieldCounts = [];
                        foreach ($dbFields as $dbField) {
                            $parts = explode('.', $dbField);
                            $table = $parts[0] ?? '';
                            if (!isset($tableFieldCounts[$table])) {
                                $tableFieldCounts[$table] = 0;
                            }
                            $tableFieldCounts[$table]++;
                        }
                        foreach ($dbTables as $table): 
                        ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                <td><?php echo $tableFieldCounts[$table] ?? 0; ?></td>
                                <td><span class="badge badge-success">존재함</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


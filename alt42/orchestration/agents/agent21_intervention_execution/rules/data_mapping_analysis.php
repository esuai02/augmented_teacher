<?php
/**
 * Agent 21 - Intervention Execution 데이터 매핑 분석 도구
 * rules.yaml 데이터 | DB 존재 여부 | 설문 데이터(survdata)인지 sysdata인지, gendata인지 식별
 * data_access.php에서 적용여부 | DB에 있는데 rules.yaml에 사용하지 않는 데이터
 * 같은 데이터 혹은 유사 데이터인데 매핑 불일치하는 경우
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent21_intervention_execution/rules/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 학생 ID 파라미터
$studentid = optional_param('studentid', 0, PARAM_INT);

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
    preg_match_all('/field:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $rulesYamlContent, $matches);
    if (!empty($matches[1])) {
        $rulesFields = array_merge($rulesFields, $matches[1]);
    }
    
    // depends_on 필드 추출
    preg_match_all('/depends_on:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $rulesYamlContent, $dependsMatches);
    if (!empty($dependsMatches[1])) {
        $rulesFields = array_merge($rulesFields, $dependsMatches[1]);
    }
    
    // data_sources에서 필드 추출
    preg_match_all('/-\s*["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']/i', $rulesYamlContent, $dataSourceMatches);
    if (!empty($dataSourceMatches[1])) {
        $rulesFields = array_merge($rulesFields, $dataSourceMatches[1]);
    }
    
    // YAML 구조에서 직접 필드명 추출
    preg_match_all('/^\s*([a-zA-Z_][a-zA-Z0-9_]*):\s*$/m', $rulesYamlContent, $yamlMatches);
    if (!empty($yamlMatches[1])) {
        $rulesFields = array_merge($rulesFields, $yamlMatches[1]);
    }
    
    // conditions에서 사용하는 필드명 추출
    preg_match_all('/-\s*field:\s*["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']/i', $rulesYamlContent, $conditionMatches);
    if (!empty($conditionMatches[1])) {
        $rulesFields = array_merge($rulesFields, $conditionMatches[1]);
    }
    
    $rulesFields = array_unique($rulesFields);
    sort($rulesFields);
}

// data_access.php에서 사용하는 필드 추출
$dataAccessPath = __DIR__ . '/data_access.php';
$dataAccessContent = file_exists($dataAccessPath) ? file_get_contents($dataAccessPath) : '';

$dataAccessFields = [];
if (!empty($dataAccessContent)) {
    // $context['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$context\[["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']\]/i', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
    }
    
    // SELECT 문에서 필드 추출
    preg_match_all('/SELECT\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $dataAccessContent, $selectMatches);
    if (!empty($selectMatches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $selectMatches[1]);
    }
    
    // 테이블 필드 접근 패턴
    preg_match_all('/->([a-zA-Z_][a-zA-Z0-9_]*)/i', $dataAccessContent, $objectMatches);
    if (!empty($objectMatches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $objectMatches[1]);
    }
    
    $dataAccessFields = array_unique($dataAccessFields);
    sort($dataAccessFields);
}

// DB 스키마 분석
$dbFields = [];
$dbTables = [];

// agent21이 사용하는 주요 테이블들
$mainTables = [
    'abessi_today',
    'abessi_todayplans',
    'abessi_messages',
    'abessi_tracking',
    'abessi_indicators',
    'alt42_calmness',
    'user',
    'alt42_student_profiles'
];

foreach ($mainTables as $tableName) {
    try {
        $tableManager = $DB->get_manager();
        $xmldbTable = new xmldb_table($tableName);
        
        if ($tableManager->table_exists($xmldbTable)) {
            $dbTables[] = $tableName;
            $columns = $DB->get_columns($tableName);
            foreach ($columns as $colName => $colInfo) {
                $dbFields[] = $tableName . '.' . $colName;
            }
        }
    } catch (Exception $e) {
        error_log("Table check failed for {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
    
    // alt42_student_profiles 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
        try {
            $columns = $DB->get_columns('alt42_student_profiles');
            if (isset($columns[$field])) {
                $sampleData = $DB->get_record('alt42_student_profiles', ['userid' => $studentid], $field, IGNORE_MISSING);
                if ($sampleData && isset($sampleData->$field)) {
                    $exists = true;
                    $tableName = 'alt42_student_profiles';
                    $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                }
            }
        } catch (Exception $e) {
            error_log("Error checking alt42_student_profiles: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    if ($exists) {
        $dataTypeInfo = identifyDataType($field, $rulesYamlContent ?? '', $dataAccessContent ?? '');
        $dbDataExists[] = [
            'field' => $field,
            'table' => $tableName,
            'type' => $dataTypeInfo['type'] ?? 'unknown',
            'sample' => $sampleValue
        ];
    }
}

// 데이터 타입 식별 함수
function identifyDataType($fieldName, $rulesContent, $dataAccessContent) {
    $type = 'unknown';
    $evidence = [];
    
    // survdata: 설문/입력에서 수집되는 데이터
    if (preg_match('/collect_info:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/question:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/request.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        $type = 'survdata';
        $evidence[] = 'rules.yaml에서 collect_info/question으로 수집';
    }
    
    // sysdata: DB에서 직접 가져오는 데이터
    $sysdataPatterns = [
        '/\$DB->(get_record|get_records|get_record_sql).*' . preg_quote($fieldName, '/') . '/i',
        '/SELECT\s+.*' . preg_quote($fieldName, '/') . '/i',
        '/\$context\[["\']' . preg_quote($fieldName, '/') . '["\']\]\s*=/i'
    ];
    
    $hasSysdata = false;
    foreach ($sysdataPatterns as $pattern) {
        if (preg_match($pattern, $dataAccessContent)) {
            $hasSysdata = true;
            break;
        }
    }
    
    if ($hasSysdata) {
        if ($type === 'unknown') {
            $type = 'sysdata';
            $evidence[] = 'data_access.php에서 DB 직접 조회';
        } else if ($type === 'survdata') {
            $type = 'hybriddata';
            $evidence[] = 'survdata + sysdata 조합';
        }
    }
    
    // gendata: 생성 규칙이 있는 데이터
    $gendataPatterns = [
        '/generation_rule.*' . preg_quote($fieldName, '/') . '/i',
        '/generate.*' . preg_quote($fieldName, '/') . '/i',
        '/LLM|AI|프롬프트.*' . preg_quote($fieldName, '/') . '/i',
        '/execute_intervention.*' . preg_quote($fieldName, '/') . '/i'
    ];
    
    $hasGendata = false;
    foreach ($gendataPatterns as $pattern) {
        if (preg_match($pattern, $rulesContent)) {
            $hasGendata = true;
            break;
        }
    }
    
    if ($hasGendata) {
        if ($type === 'unknown') {
            $type = 'gendata';
            $evidence[] = 'rules.yaml에서 생성 규칙 존재';
        } else if ($type === 'sysdata' || $type === 'survdata') {
            $type = 'hybriddata';
            $evidence[] = ($type === 'sysdata' ? 'sysdata' : 'survdata') . ' 기반 생성';
        }
    }
    
    // depends_on이 있으면 계산된 데이터일 가능성
    if (preg_match('/depends_on.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'hybriddata';
            $evidence[] = '다른 필드에 의존하는 계산 데이터';
        }
    }
    
    // action에서 execute, calculate 등이 있으면 계산/생성 데이터
    if (preg_match('/execute.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/calculate.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'hybriddata';
            $evidence[] = 'rules.yaml에서 execute/calculate 액션으로 생성';
        }
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence
    ];
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

// 4. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
foreach ($rulesFields as $ruleField) {
    foreach ($dbFields as $dbField) {
        $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
        
        // 유사도 계산
        $similarity = 0;
        if (strtolower($ruleField) === strtolower($dbFieldName)) {
            $similarity = 100;
        } else if (strpos(strtolower($ruleField), strtolower($dbFieldName)) !== false || 
                  strpos(strtolower($dbFieldName), strtolower($ruleField)) !== false) {
            $similarity = 70;
        } else {
            similar_text(strtolower($ruleField), strtolower($dbFieldName), $similarity);
        }
        
        if ($similarity > 60 && $similarity < 100 && $ruleField !== $dbFieldName) {
            $mappingMismatches[] = [
                'rules_field' => $ruleField,
                'db_field' => $dbFieldName,
                'db_table' => explode('.', $dbField)[0] ?? '',
                'similarity' => round($similarity, 2),
                'suggestion' => '유사한 필드명이지만 매핑되지 않음'
            ];
        }
    }
}

// 전체 매핑 분석
$mapping = [];
foreach ($rulesFields as $field) {
    $inDB = false;
    $dbTable = '';
    foreach ($dbFields as $dbField) {
        $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
        if ($field === $dbFieldName) {
            $inDB = true;
            $dbTable = explode('.', $dbField)[0] ?? '';
            break;
        }
    }
    
    $inDataAccess = in_array($field, $dataAccessFields);
    
    $dataType = identifyDataType(
        $field,
        $rulesYamlContent,
        $dataAccessContent
    );
    
    $mapping[] = [
        'field' => $field,
        'in_rules_yaml' => true,
        'in_db' => $inDB,
        'in_data_access' => $inDataAccess,
        'db_table' => $dbTable,
        'data_type' => $dataType['type'],
        'evidence' => $dataType['evidence'],
        'status' => $inDB && $inDataAccess ? 'complete' : ($inDB ? 'partial' : 'missing')
    ];
}

// DB에만 있는 필드 추가
foreach ($inDbNotInRules as $dbField) {
    $fieldName = explode('.', $dbField)[1] ?? $dbField;
    $tableName = explode('.', $dbField)[0] ?? '';
    $inDataAccess = in_array($fieldName, $dataAccessFields);
    
    $mapping[] = [
        'field' => $fieldName,
        'in_rules_yaml' => false,
        'in_db' => true,
        'in_data_access' => $inDataAccess,
        'db_table' => $tableName,
        'data_type' => 'sysdata',
        'evidence' => ['DB에 존재하지만 rules.yaml에 정의되지 않음'],
        'status' => 'unmapped'
    ];
}

// 통계 계산
$totalFields = count($mapping);
$completeFields = count(array_filter($mapping, fn($m) => $m['status'] === 'complete'));
$partialFields = count(array_filter($mapping, fn($m) => $m['status'] === 'partial'));
$missingFields = count(array_filter($mapping, fn($m) => $m['status'] === 'missing'));
$unmappedFields = count(array_filter($mapping, fn($m) => $m['status'] === 'unmapped'));

$survdataCount = count(array_filter($mapping, fn($m) => $m['data_type'] === 'survdata'));
$sysdataCount = count(array_filter($mapping, fn($m) => $m['data_type'] === 'sysdata'));
$gendataCount = count(array_filter($mapping, fn($m) => $m['data_type'] === 'gendata'));
$hybriddataCount = count(array_filter($mapping, fn($m) => $m['data_type'] === 'hybriddata'));
$unknownCount = count(array_filter($mapping, fn($m) => $m['data_type'] === 'unknown'));

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 21 - 데이터 매핑 분석</title>
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
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
        }
        
        /* 데이터 플로우 다이어그램 */
        .flow-diagram {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .flow-diagram h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .flow-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }
        
        .flow-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .flow-box {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-align: center;
            min-width: 150px;
            position: relative;
        }
        
        .flow-box.sysdata {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .flow-box.survdata {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        
        .flow-box.hybriddata {
            background: #ddd6fe;
            color: #5b21b6;
            border: 2px solid #8b5cf6;
        }
        
        .flow-box.gendata {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #f59e0b;
        }
        
        .flow-box.merge {
            background: #fce7f3;
            color: #9f1239;
            border: 2px solid #ec4899;
        }
        
        .flow-arrow {
            font-size: 1.5rem;
            color: #6b7280;
        }
        
        /* 우선순위 다이어그램 */
        .priority-diagram {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .priority-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .priority-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border-left: 4px solid #e5e7eb;
        }
        
        .priority-item.override {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .priority-item.gendata {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .priority-item.hybriddata {
            border-left-color: #8b5cf6;
            background: #f5f3ff;
        }
        
        .priority-item.survdata {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }
        
        .priority-item.sysdata {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .priority-number {
            font-weight: bold;
            color: #6b7280;
            min-width: 30px;
        }
        
        .data-table {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #667eea;
            color: white;
            padding: 1rem;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-yes {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-no {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-complete {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-partial {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-missing {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-unmapped {
            background: #e9d5ff;
            color: #6b21a8;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-survdata {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .type-sysdata {
            background: #d1fae5;
            color: #065f46;
        }
        
        .type-gendata {
            background: #fef3c7;
            color: #92400e;
        }
        
        .type-hybriddata {
            background: #ddd6fe;
            color: #5b21b6;
        }
        
        .type-unknown {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .evidence-list {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .evidence-list li {
            margin: 0.25rem 0;
        }
        
        .mismatch-section {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .mismatch-section h3 {
            color: #991b1b;
            margin-bottom: 1rem;
        }
        
        .mismatch-item {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #f59e0b;
        }
        
        .mismatch-item strong {
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Agent 21 - Intervention Execution 데이터 매핑 분석</h1>
            <p>rules.yaml 데이터 | DB 존재 여부 | 설문 데이터(survdata)인지 sysdata인지, gendata인지 식별 | data_access.php에서 적용여부</p>
            <?php if ($studentid > 0): ?>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">학생 ID: <?php echo $studentid; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>전체 필드</h3>
                <div class="value"><?php echo $totalFields; ?></div>
            </div>
            <div class="stat-card">
                <h3>완전 매핑</h3>
                <div class="value"><?php echo $completeFields; ?></div>
            </div>
            <div class="stat-card">
                <h3>부분 매핑</h3>
                <div class="value"><?php echo $partialFields; ?></div>
            </div>
            <div class="stat-card">
                <h3>매핑 누락</h3>
                <div class="value"><?php echo $missingFields; ?></div>
            </div>
            <div class="stat-card">
                <h3>DB만 존재</h3>
                <div class="value"><?php echo $unmappedFields; ?></div>
            </div>
            <div class="stat-card">
                <h3>SurvData</h3>
                <div class="value"><?php echo $survdataCount; ?></div>
            </div>
            <div class="stat-card">
                <h3>SysData</h3>
                <div class="value"><?php echo $sysdataCount; ?></div>
            </div>
            <div class="stat-card">
                <h3>GenData</h3>
                <div class="value"><?php echo $gendataCount; ?></div>
            </div>
            <div class="stat-card">
                <h3>HybridData</h3>
                <div class="value"><?php echo $hybriddataCount; ?></div>
            </div>
        </div>
        
        <!-- 데이터 플로우 다이어그램 -->
        <div class="flow-diagram">
            <h3>1️⃣ 전체 데이터 플로우 (Main Data Flow)</h3>
            <p style="text-align: center; color: #6b7280; margin-bottom: 1.5rem;">
                메타데이터가 전체 시스템을 구동하며, 데이터는 sysdata → survdata → hybriddata → gendata → merge 순서로 흐릅니다.
            </p>
            <div class="flow-container">
                <div class="flow-row">
                    <div class="flow-box sysdata">SysData<br><small>시스템/DB 데이터</small></div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box survdata">SurvData<br><small>설문/입력 데이터</small></div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box hybriddata">HybridData<br><small>복합 계산 데이터</small></div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box gendata">GenData<br><small>AI 생성 데이터</small></div>
                    <div class="flow-arrow">→</div>
                    <div class="flow-box merge">Merge<br><small>최종 컨텍스트</small></div>
                </div>
            </div>
        </div>
        
        <!-- 데이터 우선순위 다이어그램 -->
        <div class="priority-diagram">
            <h3>2️⃣ 데이터 타입별 우선순위 (Data Priority)</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">
                데이터 병합 시 우선순위: <strong>Override > GenData > HybridData > SurvData > SysData</strong>
            </p>
            <div class="priority-list">
                <div class="priority-item override">
                    <span class="priority-number">1</span>
                    <span><strong>Teacher Override</strong> - 선생님 직접 수정 (최우선)</span>
                </div>
                <div class="priority-item gendata">
                    <span class="priority-number">2</span>
                    <span><strong>Generated Data</strong> - AI/툴 생성 데이터 (<?php echo $gendataCount; ?>개)</span>
                </div>
                <div class="priority-item hybriddata">
                    <span class="priority-number">3</span>
                    <span><strong>Hybrid Data</strong> - 계산된 복합 데이터 (<?php echo $hybriddataCount; ?>개)</span>
                </div>
                <div class="priority-item survdata">
                    <span class="priority-number">4</span>
                    <span><strong>Survey Data</strong> - 사용자 입력 데이터 (<?php echo $survdataCount; ?>개)</span>
                </div>
                <div class="priority-item sysdata">
                    <span class="priority-number">5</span>
                    <span><strong>System Data</strong> - 시스템 기본값 (<?php echo $sysdataCount; ?>개)</span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($mappingMismatches)): ?>
        <!-- 매핑 불일치 섹션 -->
        <div class="mismatch-section">
            <h3>⚠️ 매핑 불일치 감지 (Mapping Mismatches)</h3>
            <p style="margin-bottom: 1rem; color: #991b1b;">
                같은 데이터 또는 유사한 데이터인데 매핑이 불일치하는 경우를 발견했습니다.
            </p>
            <?php foreach ($mappingMismatches as $mismatch): ?>
            <div class="mismatch-item">
                <strong>Rules.yaml:</strong> <?php echo htmlspecialchars($mismatch['rules_field']); ?> 
                <span style="color: #6b7280;">↔</span> 
                <strong>DB:</strong> <?php echo htmlspecialchars($mismatch['db_field']); ?> 
                (<?php echo htmlspecialchars($mismatch['db_table']); ?>)
                <span style="color: #f59e0b; margin-left: 1rem;">(유사도: <?php echo $mismatch['similarity']; ?>%)</span>
                <br>
                <small style="color: #6b7280;"><?php echo htmlspecialchars($mismatch['suggestion']); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- DB에 실제 데이터가 존재하는 rules.yaml 필드 -->
        <?php if (!empty($dbDataExists)): ?>
        <div class="data-table" style="margin-bottom: 2rem;">
            <div class="table-header" style="background: #10b981;">
                ✅ DB에 실제 데이터가 존재하는 rules.yaml 필드 (<?php echo count($dbDataExists); ?>개)
            </div>
            <table>
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
                                <span class="type-<?php echo $item['type']; ?>">
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
        </div>
        <?php endif; ?>
        
        <div class="data-table">
            <div class="table-header">
                데이터 필드 매핑 상세 분석 - Agent 21 Intervention Execution
            </div>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Rules.yaml</th>
                        <th>DB 존재</th>
                        <th>Data Access</th>
                        <th>DB 테이블</th>
                        <th>데이터 타입</th>
                        <th>상태</th>
                        <th>증거</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mapping as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $item['in_rules_yaml'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_rules_yaml'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $item['in_db'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_db'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $item['in_data_access'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_data_access'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($item['db_table']): ?>
                                <code><?php echo htmlspecialchars($item['db_table']); ?></code>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="type-badge type-<?php echo $item['data_type']; ?>">
                                <?php echo strtoupper($item['data_type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $item['status']; ?>">
                                <?php 
                                $statusLabels = [
                                    'complete' => '완전',
                                    'partial' => '부분',
                                    'missing' => '누락',
                                    'unmapped' => '미매핑'
                                ];
                                echo $statusLabels[$item['status']] ?? $item['status'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <ul class="evidence-list">
                                <?php foreach ($item['evidence'] as $evidence): ?>
                                <li><?php echo htmlspecialchars($evidence); ?></li>
                                <?php endforeach; ?>
                                <?php if (empty($item['evidence'])): ?>
                                <li style="color: #9ca3af;">증거 없음</li>
                                <?php endif; ?>
                            </ul>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


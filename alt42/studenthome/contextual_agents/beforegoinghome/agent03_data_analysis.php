<?php
// Moodle 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 페이지 설정
$PAGE->set_url('/studenthome/contextual_agents/beforegoinghome/agent03_data_analysis.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Agent 03 데이터 매핑 분석');

// 권한 체크 (선생님만 볼 수 있도록)
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    die('접근 권한이 없습니다.');
}

// 학생 ID 파라미터
$studentid = optional_param('studentid', 1603, PARAM_INT);
$agentid = 'agent03_goals_analysis';

/**
 * view_reports.php에서 사용하는 데이터 필드 추출
 */
function extractViewReportsData($studentid) {
    global $DB;
    
    $fields = [];
    $dataSources = [];
    
    // 1. alt42_goinghome 테이블의 JSON 데이터 분석
    if ($studentid) {
        $records = $DB->get_records('alt42_goinghome', ['userid' => $studentid], 'timecreated DESC', '*', 0, 5);
        foreach ($records as $record) {
            $data = json_decode($record->text, true);
            if ($data) {
                // student_info 필드들
                if (isset($data['student_info'])) {
                    foreach (array_keys($data['student_info']) as $key) {
                        $fieldName = 'student_info.' . $key;
                        if (!in_array($fieldName, $fields)) {
                            $fields[] = $fieldName;
                            $dataSources[$fieldName] = [
                                'source' => 'alt42_goinghome.text (JSON)',
                                'type' => 'sysdata',
                                'table' => 'alt42_goinghome',
                                'description' => '학생 기본 정보'
                            ];
                        }
                    }
                }
                // responses 필드들 (설문 응답)
                if (isset($data['responses'])) {
                    foreach (array_keys($data['responses']) as $key) {
                        $fieldName = 'responses.' . $key;
                        if (!in_array($fieldName, $fields)) {
                            $fields[] = $fieldName;
                            $dataSources[$fieldName] = [
                                'source' => 'alt42_goinghome.text (JSON)',
                                'type' => 'survdata',
                                'table' => 'alt42_goinghome',
                                'description' => '귀가검사 설문 응답'
                            ];
                        }
                    }
                }
                // 기타 필드들
                foreach (['date', 'report_id', 'student_name'] as $key) {
                    if (isset($data[$key])) {
                        $fieldName = $key;
                        if (!in_array($fieldName, $fields)) {
                            $fields[] = $fieldName;
                            $dataSources[$fieldName] = [
                                'source' => 'alt42_goinghome.text (JSON)',
                                'type' => 'sysdata',
                                'table' => 'alt42_goinghome',
                                'description' => '리포트 메타데이터'
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // 2. mdl_alt42_calmness 테이블
    $fields[] = 'calmness_level';
    $dataSources['calmness_level'] = [
        'source' => 'mdl_alt42_calmness.level',
        'type' => 'sysdata',
        'table' => 'mdl_alt42_calmness',
        'description' => '침착도 레벨 (0-100)'
    ];
    
    // 3. mdl_abessi_tracking 테이블 (포모도르)
    $fields[] = 'pomodoro_data';
    $dataSources['pomodoro_data'] = [
        'source' => 'mdl_abessi_tracking',
        'type' => 'sysdata',
        'table' => 'mdl_abessi_tracking',
        'description' => '포모도르 수학일기 사용 데이터'
    ];
    
    // 4. mdl_abessi_messages 테이블 (오답노트)
    $fields[] = 'error_note_data';
    $dataSources['error_note_data'] = [
        'source' => 'mdl_abessi_messages',
        'type' => 'sysdata',
        'table' => 'mdl_abessi_messages',
        'description' => '오답노트 작성 데이터'
    ];
    
    return [
        'fields' => array_unique($fields),
        'data_sources' => $dataSources
    ];
}

/**
 * YAML 파일 파싱
 */
function parseYamlRules($filePath) {
    if (!file_exists($filePath)) {
        return ['error' => 'File not found: ' . $filePath];
    }
    
    $content = file_get_contents($filePath);
    $fields = [];
    
    // field: "field_name" 패턴 찾기
    preg_match_all('/field:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $content, $matches);
    if (!empty($matches[1])) {
        $fields = array_merge($fields, $matches[1]);
    }
    
    // collect_info 패턴
    preg_match_all('/collect_info:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $content, $collectMatches);
    if (!empty($collectMatches[1])) {
        $fields = array_merge($fields, $collectMatches[1]);
    }
    
    // depends_on 패턴
    preg_match_all('/depends_on:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $content, $dependsMatches);
    if (!empty($dependsMatches[1])) {
        $fields = array_merge($fields, $dependsMatches[1]);
    }
    
    // conditions에서 사용하는 필드명
    preg_match_all('/-\s*field:\s*["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']/i', $content, $conditionMatches);
    if (!empty($conditionMatches[1])) {
        $fields = array_merge($fields, $conditionMatches[1]);
    }
    
    return [
        'fields' => array_unique($fields),
        'raw_content' => $content
    ];
}

/**
 * data_access.php에서 사용하는 필드 추출
 */
function parseDataAccess($filePath) {
    if (!file_exists($filePath)) {
        return ['error' => 'File not found: ' . $filePath];
    }
    
    $content = file_get_contents($filePath);
    $fields = [];
    
    // $context['field_name'] 패턴 찾기
    preg_match_all('/\$context\[["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']\]/i', $content, $matches);
    if (!empty($matches[1])) {
        $fields = array_unique($matches[1]);
    }
    
    // DB 쿼리에서 필드명 추출
    preg_match_all('/SELECT\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $selectMatches);
    if (!empty($selectMatches[1])) {
        $fields = array_merge($fields, array_unique($selectMatches[1]));
    }
    
    return [
        'fields' => array_unique($fields),
        'raw_content' => $content
    ];
}

/**
 * DB 스키마 분석
 */
function analyzeDBSchema($studentid = 0) {
    global $DB;
    
    $tables = [];
    $allFields = [];
    
    // 주요 테이블 목록
    $mainTables = [
        'mdl_user',
        'mdl_alt42_student_profiles',
        'mdl_abessi_mbtilog',
        'mdl_abessi_tracking',
        'mdl_abessi_messages',
        'mdl_alt42_calmness',
        'mdl_alt42_goinghome',
        'mdl_alt42_goals',
        'mdl_alt42_goal_analysis'
    ];
    
    foreach ($mainTables as $tableName) {
        try {
            $tableNameWithoutPrefix = str_replace('mdl_', '', $tableName);
            $tableManager = $DB->get_manager();
            
            $xmldbTable = new xmldb_table($tableNameWithoutPrefix);
            
            if ($tableManager->table_exists($xmldbTable)) {
                $table = $tableNameWithoutPrefix;
                $columns = $DB->get_columns($table);
                $fieldNames = array_keys($columns);
                $tables[$tableName] = $fieldNames;
                $allFields = array_merge($allFields, $fieldNames);
            }
        } catch (Exception $e) {
            error_log("Table check failed for {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    return [
        'tables' => $tables,
        'all_fields' => array_unique($allFields)
    ];
}

/**
 * 데이터 타입 식별
 */
function identifyDataType($fieldName, $rulesContent, $dataAccessContent, $viewReportsData) {
    $type = 'unknown';
    $evidence = [];
    
    // view_reports.php에서 사용하는 데이터인지 확인
    if (isset($viewReportsData[$fieldName])) {
        $type = $viewReportsData[$fieldName]['type'];
        $evidence[] = 'view_reports.php에서 사용: ' . $viewReportsData[$fieldName]['source'];
    }
    
    // survdata: 설문/입력에서 수집되는 데이터
    if (preg_match('/collect_info:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'survdata';
        } else if ($type === 'sysdata') {
            $type = 'hybriddata';
        }
        $evidence[] = 'rules.yaml에서 collect_info로 수집';
    }
    
    // sysdata: DB에서 직접 가져오는 데이터
    if (preg_match('/\$DB->(get_record|get_records|get_record_sql).*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent) ||
        preg_match('/SELECT\s+.*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent) ||
        preg_match('/\$context\[["\']' . preg_quote($fieldName, '/') . '["\']\]\s*=/i', $dataAccessContent)) {
        if ($type === 'unknown') {
            $type = 'sysdata';
        } else if ($type === 'survdata') {
            $type = 'hybriddata';
        }
        $evidence[] = 'data_access.php에서 DB 직접 조회';
    }
    
    // gendata: 생성 규칙이 있는 데이터
    if (preg_match('/generation_rule.*' . preg_quote($fieldName, '/') . '/i', $rulesContent) ||
        preg_match('/generate.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'gendata';
        } else if ($type === 'sysdata') {
            $type = 'hybriddata';
        }
        $evidence[] = 'rules.yaml에서 생성 규칙 존재';
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence
    ];
}

/**
 * 유사 필드명 매칭 (매핑 불일치 감지)
 */
function findSimilarFields($fieldName, $allFields) {
    $similar = [];
    $fieldLower = strtolower($fieldName);
    
    foreach ($allFields as $otherField) {
        $otherLower = strtolower($otherField);
        
        // 완전 일치 제외
        if ($fieldLower === $otherLower) {
            continue;
        }
        
        // 유사도 체크
        $similarity = 0;
        
        // 포함 관계
        if (strpos($fieldLower, $otherLower) !== false || strpos($otherLower, $fieldLower) !== false) {
            $similarity += 50;
        }
        
        // 공통 단어
        $fieldWords = explode('_', $fieldLower);
        $otherWords = explode('_', $otherLower);
        $commonWords = array_intersect($fieldWords, $otherWords);
        if (count($commonWords) > 0) {
            $similarity += count($commonWords) * 20;
        }
        
        // 레벤슈타인 거리
        $distance = levenshtein($fieldLower, $otherLower);
        $maxLen = max(strlen($fieldLower), strlen($otherLower));
        if ($maxLen > 0) {
            $similarity += (1 - ($distance / $maxLen)) * 30;
        }
        
        if ($similarity > 40) {
            $similar[] = [
                'field' => $otherField,
                'similarity' => $similarity
            ];
        }
    }
    
    // 유사도 순으로 정렬
    usort($similar, function($a, $b) {
        return $b['similarity'] - $a['similarity'];
    });
    
    return array_slice($similar, 0, 5); // 상위 5개만
}

/**
 * 종합 분석
 */
function analyzeAgent03Data($agentid, $studentid) {
    $basePath = __DIR__ . '/../../orchestration/agents/' . $agentid . '/rules/';
    
    $rulesPath = $basePath . 'rules.yaml';
    $dataAccessPath = $basePath . 'data_access.php';
    
    $analysis = [
        'agent_id' => $agentid,
        'student_id' => $studentid,
        'rules_yaml' => null,
        'data_access' => null,
        'db_schema' => null,
        'view_reports_data' => null,
        'mapping' => [],
        'unmapped_db' => [],
        'unmapped_rules' => [],
        'similar_fields' => []
    ];
    
    // rules.yaml 분석
    if (file_exists($rulesPath)) {
        $analysis['rules_yaml'] = parseYamlRules($rulesPath);
    }
    
    // data_access.php 분석
    if (file_exists($dataAccessPath)) {
        $analysis['data_access'] = parseDataAccess($dataAccessPath);
    }
    
    // DB 스키마 분석
    $analysis['db_schema'] = analyzeDBSchema($studentid);
    
    // view_reports.php 데이터 분석
    $analysis['view_reports_data'] = extractViewReportsData($studentid);
    
    // 매핑 분석
    $rulesFields = $analysis['rules_yaml']['fields'] ?? [];
    $dataAccessFields = $analysis['data_access']['fields'] ?? [];
    $dbFields = $analysis['db_schema']['all_fields'] ?? [];
    $viewReportsFields = array_keys($analysis['view_reports_data']['data_sources'] ?? []);
    $allFields = array_unique(array_merge($rulesFields, $dataAccessFields, $dbFields, $viewReportsFields));
    
    // 1. rules.yaml에 있는 필드들
    foreach ($rulesFields as $field) {
        $inDB = in_array($field, $dbFields);
        $inDataAccess = in_array($field, $dataAccessFields);
        $inViewReports = in_array($field, $viewReportsFields);
        
        $dataType = identifyDataType(
            $field,
            $analysis['rules_yaml']['raw_content'] ?? '',
            $analysis['data_access']['raw_content'] ?? '',
            $analysis['view_reports_data']['data_sources'] ?? []
        );
        
        $analysis['mapping'][] = [
            'field' => $field,
            'in_rules_yaml' => true,
            'in_db' => $inDB,
            'in_data_access' => $inDataAccess,
            'in_view_reports' => $inViewReports,
            'data_type' => $dataType['type'],
            'evidence' => $dataType['evidence'],
            'status' => $inDB && $inDataAccess ? 'complete' : ($inDB ? 'partial' : 'missing')
        ];
        
        // 유사 필드 찾기
        $similar = findSimilarFields($field, $allFields);
        if (!empty($similar)) {
            $analysis['similar_fields'][$field] = $similar;
        }
    }
    
    // 2. view_reports.php에 있는 필드들
    foreach ($viewReportsFields as $field) {
        if (!in_array($field, $rulesFields)) {
            $inDB = in_array($field, $dbFields);
            $inDataAccess = in_array($field, $dataAccessFields);
            
            $dataType = identifyDataType(
                $field,
                $analysis['rules_yaml']['raw_content'] ?? '',
                $analysis['data_access']['raw_content'] ?? '',
                $analysis['view_reports_data']['data_sources'] ?? []
            );
            
            $analysis['mapping'][] = [
                'field' => $field,
                'in_rules_yaml' => false,
                'in_db' => $inDB,
                'in_data_access' => $inDataAccess,
                'in_view_reports' => true,
                'data_type' => $dataType['type'],
                'evidence' => $dataType['evidence'],
                'status' => 'unmapped_view_reports'
            ];
        }
    }
    
    // 3. DB에 있지만 rules.yaml에 없는 필드들
    $unmappedDBFields = array_diff($dbFields, $rulesFields);
    foreach ($unmappedDBFields as $field) {
        if (!in_array($field, $viewReportsFields)) {
            $inDataAccess = in_array($field, $dataAccessFields);
            $analysis['unmapped_db'][] = [
                'field' => $field,
                'in_data_access' => $inDataAccess,
                'status' => $inDataAccess ? 'partial' : 'orphan'
            ];
        }
    }
    
    // 4. rules.yaml에 있지만 DB에 없는 필드들
    $unmappedRulesFields = array_diff($rulesFields, $dbFields);
    foreach ($unmappedRulesFields as $field) {
        if (!in_array($field, $dataAccessFields)) {
            $analysis['unmapped_rules'][] = [
                'field' => $field,
                'status' => 'missing_db'
            ];
        }
    }
    
    return $analysis;
}

// 분석 실행
$analysis = null;
$error = null;

try {
    $analysis = analyzeAgent03Data($agentid, $studentid);
} catch (Exception $e) {
    $error = $e->getMessage() . ' [File: ' . __FILE__ . ', Line: ' . $e->getLine() . ']';
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 03 데이터 매핑 분석</title>
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
            max-width: 1800px;
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
            margin-bottom: 0.5rem;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-form input {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }
        
        .filter-form button {
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .filter-form button:hover {
            background: #2563eb;
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
        
        .data-table {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
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
        
        .error-box {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .evidence-list {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .evidence-list li {
            margin: 0.25rem 0;
        }
        
        .similar-field {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #fef3c7;
            color: #92400e;
            border-radius: 4px;
            font-size: 0.75rem;
            margin: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Agent 03 데이터 매핑 분석</h1>
            <p>view_reports.php, rules.yaml, data_access.php, DB 스키마 간의 데이터 매핑 상태를 분석합니다.</p>
        </div>
        
        <div class="filter-section">
            <form method="get" class="filter-form">
                <label>학생 ID:</label>
                <input type="number" name="studentid" value="<?php echo $studentid; ?>" placeholder="1603">
                <button type="submit">분석 실행</button>
                <a href="view_reports.php?studentid=<?php echo $studentid; ?>" style="padding: 0.75rem 1.5rem; background: #10b981; color: white; text-decoration: none; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500;">
                    리포트 보기 →
                </a>
            </form>
        </div>
        
        <?php if ($error): ?>
        <div class="error-box">
            <strong>오류:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($analysis): ?>
        <?php
        $totalFields = count($analysis['mapping']);
        $completeFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'complete'));
        $partialFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'partial'));
        $missingFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'missing'));
        $unmappedFields = count($analysis['unmapped_db']);
        $viewReportsFields = count(array_filter($analysis['mapping'], fn($m) => $m['in_view_reports'] ?? false));
        
        $survdataCount = count(array_filter($analysis['mapping'], fn($m) => $m['data_type'] === 'survdata'));
        $sysdataCount = count(array_filter($analysis['mapping'], fn($m) => $m['data_type'] === 'sysdata'));
        $gendataCount = count(array_filter($analysis['mapping'], fn($m) => $m['data_type'] === 'gendata'));
        $hybriddataCount = count(array_filter($analysis['mapping'], fn($m) => $m['data_type'] === 'hybriddata'));
        ?>
        
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
                <h3>View Reports 사용</h3>
                <div class="value"><?php echo $viewReportsFields; ?></div>
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
        
        <div class="data-table">
            <div class="table-header">
                데이터 필드 매핑 상세 분석 - Agent 03 Goals Analysis
            </div>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Rules.yaml</th>
                        <th>DB 존재</th>
                        <th>Data Access</th>
                        <th>View Reports</th>
                        <th>데이터 타입</th>
                        <th>상태</th>
                        <th>증거</th>
                        <th>유사 필드</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['mapping'] as $item): ?>
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
                            <span class="badge <?php echo ($item['in_view_reports'] ?? false) ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo ($item['in_view_reports'] ?? false) ? 'Yes' : 'No'; ?>
                            </span>
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
                                    'unmapped_view_reports' => 'View Reports만'
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
                            </ul>
                        </td>
                        <td>
                            <?php if (isset($analysis['similar_fields'][$item['field']])): ?>
                                <?php foreach ($analysis['similar_fields'][$item['field']] as $similar): ?>
                                <span class="similar-field" title="유사도: <?php echo $similar['similarity']; ?>%">
                                    <?php echo htmlspecialchars($similar['field']); ?>
                                </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($analysis['unmapped_db'])): ?>
        <div class="data-table">
            <div class="table-header">
                DB에 있지만 rules.yaml에 없는 필드들
            </div>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Data Access</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['unmapped_db'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $item['in_data_access'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_data_access'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $item['status']; ?>">
                                <?php echo $item['status'] === 'partial' ? '부분' : '고아'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($analysis['unmapped_rules'])): ?>
        <div class="data-table">
            <div class="table-header">
                rules.yaml에 있지만 DB에 없는 필드들
            </div>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['unmapped_rules'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td>
                            <span class="badge badge-missing">
                                DB 누락
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</body>
</html>


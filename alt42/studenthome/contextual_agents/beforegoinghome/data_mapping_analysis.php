<?php
// Moodle 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 페이지 설정
$PAGE->set_url('/studenthome/contextual_agents/beforegoinghome/data_mapping_analysis.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('데이터 매핑 분석');

// 권한 체크 (선생님만 볼 수 있도록)
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    die('접근 권한이 없습니다.');
}

// 학생 ID 파라미터
$studentid = optional_param('studentid', 0, PARAM_INT);
$agentid = optional_param('agentid', 'agent01_onboarding', PARAM_TEXT);

// 에이전트 목록
$agents = [
    'agent01_onboarding' => 'Agent 01 - Onboarding',
    'agent02_exam_schedule' => 'Agent 02 - Exam Schedule',
    'agent03_goals_analysis' => 'Agent 03 - Goals Analysis',
    'agent04_inspect_weakpoints' => 'Agent 04 - Inspect Weakpoints',
    'agent05_learning_emotion' => 'Agent 05 - Learning Emotion',
    'agent06_teacher_feedback' => 'Agent 06 - Teacher Feedback',
    'agent07_interaction_targeting' => 'Agent 07 - Interaction Targeting',
    'agent08_calmness' => 'Agent 08 - Calmness',
    'agent09_learning_management' => 'Agent 09 - Learning Management',
    'agent10_concept_notes' => 'Agent 10 - Concept Notes',
    'agent11_problem_notes' => 'Agent 11 - Problem Notes',
    'agent12_rest_routine' => 'Agent 12 - Rest Routine',
    'agent13_learning_dropout' => 'Agent 13 - Learning Dropout',
    'agent14_current_position' => 'Agent 14 - Current Position',
    'agent15_problem_redefinition' => 'Agent 15 - Problem Redefinition',
    'agent16_interaction_preparation' => 'Agent 16 - Interaction Preparation',
    'agent17_remaining_activities' => 'Agent 17 - Remaining Activities',
    'agent18_signature_routine' => 'Agent 18 - Signature Routine',
    'agent19_interaction_content' => 'Agent 19 - Interaction Content',
    'agent20_intervention_preparation' => 'Agent 20 - Intervention Preparation',
    'agent21_intervention_execution' => 'Agent 21 - Intervention Execution',
    'agent22_module_improvement' => 'Agent 22 - Module Improvement'
];

/**
 * YAML 파일 파싱 (간단한 버전)
 */
function parseYamlRules($filePath) {
    if (!file_exists($filePath)) {
        return ['error' => 'File not found: ' . $filePath];
    }
    
    $content = file_get_contents($filePath);
    $rules = [];
    $fields = [];
    
    // rules.yaml에서 필드 추출
    // 1. field: "field_name" 패턴 찾기
    preg_match_all('/field:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $content, $matches);
    if (!empty($matches[1])) {
        $fields = array_merge($fields, $matches[1]);
    }
    
    // 2. action에서 collect_info, generate 등 추출
    preg_match_all('/collect_info:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $content, $collectMatches);
    if (!empty($collectMatches[1])) {
        $fields = array_merge($fields, $collectMatches[1]);
    }
    
    // 3. depends_on 필드 추출
    preg_match_all('/depends_on:\s*["\']?([a-zA-Z_][a-zA-Z0-9_]*)["\']?/i', $content, $dependsMatches);
    if (!empty($dependsMatches[1])) {
        $fields = array_merge($fields, $dependsMatches[1]);
    }
    
    // 4. YAML 구조에서 직접 필드명 추출 (더 정교한 패턴)
    // - field_name: 형태
    preg_match_all('/^\s*([a-zA-Z_][a-zA-Z0-9_]*):\s*$/m', $content, $yamlMatches);
    if (!empty($yamlMatches[1])) {
        $fields = array_merge($fields, $yamlMatches[1]);
    }
    
    // 5. agent04 특화: conditions에서 사용하는 필드명 추출 (더 정확하게)
    // - field: "activity_type" 같은 패턴
    preg_match_all('/-\s*field:\s*["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']/i', $content, $conditionMatches);
    if (!empty($conditionMatches[1])) {
        $fields = array_merge($fields, $conditionMatches[1]);
    }
    
    // 6. {{field_name}} 템플릿 변수 추출
    preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/i', $content, $templateMatches);
    if (!empty($templateMatches[1])) {
        $fields = array_merge($fields, $templateMatches[1]);
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
 * DB 테이블 스키마 분석
 */
function analyzeDBSchema($studentid = 0, $agentid = '') {
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
        'mdl_alt42_goinghome'
    ];
    
    // agent04 특화 테이블 추가
    if ($agentid === 'agent04_inspect_weakpoints') {
        $mainTables[] = 'mdl_alt42_student_activity';
    }
    
    // agent06 특화 테이블 추가 (교사 피드백 관련)
    if ($agentid === 'agent06_teacher_feedback') {
        $mainTables[] = 'mdl_abessi_todayplans'; // 교사 피드백 데이터
        // mdl_teacher_feedback 테이블이 생성되면 추가
        // $mainTables[] = 'mdl_teacher_feedback';
    }
    
    foreach ($mainTables as $tableName) {
        try {
            $tableNameWithoutPrefix = str_replace('mdl_', '', $tableName);
            $tableManager = $DB->get_manager();
            
            // xmldb_table 객체 생성
            $xmldbTable = new xmldb_table($tableNameWithoutPrefix);
            
            if ($tableManager->table_exists($xmldbTable)) {
                $table = $tableNameWithoutPrefix;
                $columns = $DB->get_columns($table);
                $fieldNames = array_keys($columns);
                $tables[$tableName] = $fieldNames;
                $allFields = array_merge($allFields, $fieldNames);
                
                // JSON 필드 처리 (agent04의 survey_responses 같은 경우)
                foreach ($columns as $colName => $colDef) {
                    if (isset($colDef->meta_type) && $colDef->meta_type === 'text') {
                        // JSON 필드로 추정되는 경우 내부 필드도 추출 시도
                        if (strpos($colName, 'response') !== false || strpos($colName, 'data') !== false) {
                            // 실제 데이터가 있으면 JSON 파싱 시도
                            if ($studentid > 0) {
                                try {
                                    $sample = $DB->get_record($table, ['userid' => $studentid], $colName);
                                    if ($sample && isset($sample->$colName)) {
                                        $jsonData = json_decode($sample->$colName, true);
                                        if (is_array($jsonData)) {
                                            $allFields = array_merge($allFields, array_keys($jsonData));
                                        }
                                    }
                                } catch (Exception $e) {
                                    // 무시
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // 테이블이 없으면 무시
            error_log("Table check failed for {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    return [
        'tables' => $tables,
        'all_fields' => array_unique($allFields)
    ];
}

/**
 * 데이터 타입 식별 (survdata/sysdata/gendata/hybriddata)
 */
function identifyDataType($fieldName, $rulesContent, $dataAccessContent) {
    $type = 'unknown';
    $evidence = [];
    
    // survdata: 설문/입력에서 수집되는 데이터
    // request_teacher_input으로 수집하는 경우 (agent06 특화)
    if (preg_match('/request_teacher_input:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        $type = 'survdata';
        $evidence[] = 'rules.yaml에서 request_teacher_input으로 수집';
    }
    
    // collect_info로 명시적으로 수집하는 경우
    if (preg_match('/collect_info:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'survdata';
            $evidence[] = 'rules.yaml에서 collect_info로 수집';
        }
    }
    
    // question 액션이 있는 경우도 survdata로 간주
    if (preg_match('/question:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'survdata';
            $evidence[] = 'rules.yaml에서 질문으로 수집';
        }
    }
    
    // sysdata: DB에서 직접 가져오는 데이터
    // data_access.php에서 직접 조회하는 경우
    $sysdataPatterns = [
        '/\$DB->(get_record|get_records|get_record_sql).*' . preg_quote($fieldName, '/') . '/i',
        '/SELECT\s+.*' . preg_quote($fieldName, '/') . '/i',
        '/\$context\[["\']' . preg_quote($fieldName, '/') . '["\']\]\s*=/i',
        '/\$context\[["\']' . preg_quote($fieldName, '/') . '["\']\]/i'
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
            // survdata와 sysdata 둘 다 있으면 hybrid로 처리
            $type = 'hybriddata';
            $evidence[] = 'survdata + sysdata 조합';
        }
    }
    
    // gendata: 생성 규칙이 있는 데이터
    $gendataPatterns = [
        '/generation_rule.*' . preg_quote($fieldName, '/') . '/i',
        '/generate.*' . preg_quote($fieldName, '/') . '/i',
        '/generate_description:.*' . preg_quote($fieldName, '/') . '/i',
        '/LLM|AI|프롬프트.*' . preg_quote($fieldName, '/') . '/i'
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
            // sysdata/survdata를 기반으로 생성하는 경우
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
    
    // analyze 액션이 있으면 분석/계산 데이터일 가능성
    if (preg_match('/analyze:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'hybriddata';
            $evidence[] = 'rules.yaml에서 analyze 액션으로 분석';
        }
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence
    ];
}

/**
 * 에이전트 데이터 분석
 */
function analyzeAgentData($agentid, $studentid = 0) {
    $basePath = __DIR__ . '/../../orchestration/agents/' . $agentid . '/rules/';
    
    $rulesPath = $basePath . 'rules.yaml';
    $dataAccessPath = $basePath . 'data_access.php';
    
    $analysis = [
        'agent_id' => $agentid,
        'rules_yaml' => null,
        'data_access' => null,
        'db_schema' => null,
        'mapping' => []
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
    $analysis['db_schema'] = analyzeDBSchema($studentid, $agentid);
    
    // 매핑 분석
    $rulesFields = $analysis['rules_yaml']['fields'] ?? [];
    $dataAccessFields = $analysis['data_access']['fields'] ?? [];
    $dbFields = $analysis['db_schema']['all_fields'] ?? [];
    
    // 1. rules.yaml에 있는 필드들
    foreach ($rulesFields as $field) {
        $inDB = in_array($field, $dbFields);
        $inDataAccess = in_array($field, $dataAccessFields);
        
        $dataType = identifyDataType(
            $field,
            $analysis['rules_yaml']['raw_content'] ?? '',
            $analysis['data_access']['raw_content'] ?? ''
        );
        
        $analysis['mapping'][] = [
            'field' => $field,
            'in_rules_yaml' => true,
            'in_db' => $inDB,
            'in_data_access' => $inDataAccess,
            'data_type' => $dataType['type'],
            'evidence' => $dataType['evidence'],
            'status' => $inDB && $inDataAccess ? 'complete' : ($inDB ? 'partial' : 'missing')
        ];
    }
    
    // 2. DB에 있지만 rules.yaml에 없는 필드들
    $unmappedDBFields = array_diff($dbFields, $rulesFields);
    foreach ($unmappedDBFields as $field) {
        $inDataAccess = in_array($field, $dataAccessFields);
        $analysis['mapping'][] = [
            'field' => $field,
            'in_rules_yaml' => false,
            'in_db' => true,
            'in_data_access' => $inDataAccess,
            'data_type' => 'sysdata',
            'evidence' => ['DB에 존재하지만 rules.yaml에 정의되지 않음'],
            'status' => 'unmapped'
        ];
    }
    
    // 3. data_access.php에 있지만 rules.yaml에 없는 필드들
    $unmappedAccessFields = array_diff($dataAccessFields, $rulesFields);
    foreach ($unmappedAccessFields as $field) {
        if (!in_array($field, $unmappedDBFields)) { // 이미 위에서 처리한 것은 제외
            $analysis['mapping'][] = [
                'field' => $field,
                'in_rules_yaml' => false,
                'in_db' => false,
                'in_data_access' => true,
                'data_type' => 'unknown',
                'evidence' => ['data_access.php에서 사용하지만 DB와 rules.yaml에 없음'],
                'status' => 'orphan'
            ];
        }
    }
    
    // 4. 매핑 불일치 감지 (유사한 필드명 찾기)
    $analysis['mapping_mismatches'] = [];
    foreach ($rulesFields as $ruleField) {
        foreach ($dbFields as $dbField) {
            // 유사도 계산 (간단한 문자열 유사도)
            $similarity = 0;
            if (strtolower($ruleField) === strtolower($dbField)) {
                $similarity = 100;
            } else if (strpos(strtolower($ruleField), strtolower($dbField)) !== false || 
                      strpos(strtolower($dbField), strtolower($ruleField)) !== false) {
                $similarity = 70;
            } else {
                // 레벤슈타인 거리 기반 유사도
                similar_text(strtolower($ruleField), strtolower($dbField), $similarity);
            }
            
            if ($similarity > 60 && $similarity < 100 && $ruleField !== $dbField) {
                $analysis['mapping_mismatches'][] = [
                    'rules_field' => $ruleField,
                    'db_field' => $dbField,
                    'similarity' => round($similarity, 2),
                    'suggestion' => '유사한 필드명이지만 매핑되지 않음'
                ];
            }
        }
    }
    
    return $analysis;
}

// 분석 실행
$analysis = null;
$error = null;

try {
    if ($agentid && isset($agents[$agentid])) {
        $analysis = analyzeAgentData($agentid, $studentid);
    }
} catch (Exception $e) {
    $error = $e->getMessage() . ' [File: ' . __FILE__ . ', Line: ' . $e->getLine() . ']';
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석</title>
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
        
        .filter-form select,
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
        
        .badge-orphan {
            background: #fce7f3;
            color: #9f1239;
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
        
        /* 데이터 플로우 다이어그램 스타일 */
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
        
        /* 매핑 불일치 섹션 */
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
            <h1>📊 데이터 매핑 분석</h1>
            <p>rules.yaml, data_access.php, DB 스키마 간의 데이터 매핑 상태를 분석합니다.</p>
        </div>
        
        <div class="filter-section">
            <form method="get" class="filter-form">
                <label>에이전트 선택:</label>
                <select name="agentid">
                    <?php foreach ($agents as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $agentid === $id ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <label>학생 ID (선택):</label>
                <input type="number" name="studentid" value="<?php echo $studentid; ?>" placeholder="0 = 전체">
                
                <button type="submit">분석 실행</button>
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
        $unmappedFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'unmapped'));
        $orphanFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'orphan'));
        
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
                <h3>고아 필드</h3>
                <div class="value"><?php echo $orphanFields; ?></div>
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
        
        <?php if (!empty($analysis['mapping_mismatches'])): ?>
        <!-- 매핑 불일치 섹션 -->
        <div class="mismatch-section">
            <h3>⚠️ 매핑 불일치 감지 (Mapping Mismatches)</h3>
            <p style="margin-bottom: 1rem; color: #991b1b;">
                같은 데이터 또는 유사한 데이터인데 매핑이 불일치하는 경우를 발견했습니다.
            </p>
            <?php foreach ($analysis['mapping_mismatches'] as $mismatch): ?>
            <div class="mismatch-item">
                <strong>Rules.yaml:</strong> <?php echo htmlspecialchars($mismatch['rules_field']); ?> 
                <span style="color: #6b7280;">↔</span> 
                <strong>DB:</strong> <?php echo htmlspecialchars($mismatch['db_field']); ?>
                <span style="color: #f59e0b; margin-left: 1rem;">(유사도: <?php echo $mismatch['similarity']; ?>%)</span>
                <br>
                <small style="color: #6b7280;"><?php echo htmlspecialchars($mismatch['suggestion']); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($analysis['mapping_mismatches'])): ?>
        <div class="data-table" style="margin-bottom: 2rem;">
            <div class="table-header" style="background: #ef4444;">
                ⚠️ 매핑 불일치 감지 (유사 필드명)
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Rules.yaml 필드</th>
                        <th>DB 필드</th>
                        <th>유사도</th>
                        <th>제안</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['mapping_mismatches'] as $mismatch): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($mismatch['rules_field']); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($mismatch['db_field']); ?></strong></td>
                        <td><?php echo $mismatch['similarity']; ?>%</td>
                        <td><?php echo htmlspecialchars($mismatch['suggestion']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="data-table">
            <div class="table-header">
                데이터 필드 매핑 상세 분석 - <?php echo $agents[$agentid]; ?>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Rules.yaml</th>
                        <th>DB 존재</th>
                        <th>Data Access</th>
                        <th>데이터 타입</th>
                        <th>상태</th>
                        <th>증거</th>
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
                                    'unmapped' => '미매핑',
                                    'orphan' => '고아'
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        // 사용되지 않는 DB 필드 통계
        $unusedDBFields = array_filter($analysis['mapping'], function($item) {
            return $item['status'] === 'unmapped' && !$item['in_data_access'];
        });
        if (!empty($unusedDBFields)): 
        ?>
        <div class="data-table" style="margin-top: 2rem;">
            <div class="table-header" style="background: #f59e0b;">
                📊 사용되지 않는 DB 필드 (<?php echo count($unusedDBFields); ?>개)
            </div>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>DB 존재</th>
                        <th>Rules.yaml</th>
                        <th>Data Access</th>
                        <th>비고</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unusedDBFields as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td><span class="badge badge-yes">Yes</span></td>
                        <td><span class="badge badge-no">No</span></td>
                        <td><span class="badge badge-no">No</span></td>
                        <td>DB에 존재하지만 rules.yaml과 data_access.php 모두에서 사용되지 않음</td>
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


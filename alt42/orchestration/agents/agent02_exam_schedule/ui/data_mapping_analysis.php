<?php
/**
 * Agent 02 데이터 매핑 분석 도구
 * File: alt42/orchestration/agents/agent02_exam_schedule/ui/data_mapping_analysis.php
 * 
 * view_reports.php의 데이터와 rules.yaml, data_access.php, DB 스키마를 비교 분석
 * - rules.yaml 데이터 존재 여부
 * - DB 존재 여부
 * - 데이터 타입 식별 (survdata, sysdata, gendata, hybriddata)
 * - data_access.php에서 적용 여부
 * - DB에 있는데 rules.yaml에 사용하지 않는 데이터
 * - 같은/유사 데이터인데 매핑 불일치하는 경우
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 페이지 설정
$PAGE->set_url('/orchestration/agents/agent02_exam_schedule/ui/data_mapping_analysis.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Agent 02 데이터 매핑 분석');

// 권한 체크
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());
if (!$isTeacher) {
    die('접근 권한이 없습니다.');
}

// 학생 ID 파라미터
$studentid = optional_param('studentid', 1603, PARAM_INT);

/**
 * view_reports.php에서 사용하는 데이터 필드 추출
 */
function extractViewReportsFields($studentid) {
    global $DB;
    
    $fields = [];
    $fieldSources = [];
    
    // view_reports.php에서 사용하는 데이터
    // 1. alt42_goinghome 테이블의 text 필드 (JSON)
    try {
        $record = $DB->get_record('alt42_goinghome', ['userid' => $studentid], '*', IGNORE_MISSING);
        if ($record && !empty($record->text)) {
            $data = json_decode($record->text, true);
            if (is_array($data)) {
                // student_info 필드들
                if (isset($data['student_info'])) {
                    foreach ($data['student_info'] as $key => $value) {
                        $fields[] = 'student_info.' . $key;
                        $fieldSources['student_info.' . $key] = 'view_reports.php (alt42_goinghome.text)';
                    }
                }
                
                // responses 필드들
                if (isset($data['responses'])) {
                    foreach ($data['responses'] as $key => $value) {
                        $fields[] = 'responses.' . $key;
                        $fieldSources['responses.' . $key] = 'view_reports.php (alt42_goinghome.text)';
                    }
                }
                
                // 기타 필드들
                foreach ($data as $key => $value) {
                    if (!in_array($key, ['student_info', 'responses'])) {
                        $fields[] = $key;
                        $fieldSources[$key] = 'view_reports.php (alt42_goinghome.text)';
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error extracting view_reports fields: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    // 2. 실제 DB에서 조회하는 필드들
    // 침착도 데이터
    try {
        $calmnessData = $DB->get_record_sql("
            SELECT level 
            FROM mdl_alt42_calmness 
            WHERE userid = ? 
            ORDER BY timecreated DESC 
            LIMIT 1", [$studentid], IGNORE_MISSING);
        if ($calmnessData) {
            $fields[] = 'calmness_level';
            $fieldSources['calmness_level'] = 'view_reports.php (mdl_alt42_calmness.level)';
        }
    } catch (Exception $e) {
        error_log("Error checking calmness: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    // 포모도르 데이터
    try {
        $pomodoroData = $DB->get_records_sql("
            SELECT * FROM mdl_abessi_tracking 
            WHERE userid = ? AND duration > ? AND hide = 0 
            ORDER BY id DESC LIMIT 10", [$studentid, time() - 7 * 24 * 60 * 60], IGNORE_MISSING);
        if (!empty($pomodoroData)) {
            $fields[] = 'pomodoro_usage';
            $fieldSources['pomodoro_usage'] = 'view_reports.php (mdl_abessi_tracking)';
        }
    } catch (Exception $e) {
        error_log("Error checking pomodoro: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    // 오답노트 데이터
    try {
        $errorNoteData = $DB->get_records_sql("
            SELECT * FROM mdl_abessi_messages 
            WHERE userid = ? AND (student_check = 1 OR turn = 1) AND hide = 0 AND timemodified > ? 
            ORDER BY timemodified DESC LIMIT 10", [$studentid, time() - 24 * 60 * 60], IGNORE_MISSING);
        if (!empty($errorNoteData)) {
            $fields[] = 'error_note_count';
            $fieldSources['error_note_count'] = 'view_reports.php (mdl_abessi_messages)';
        }
    } catch (Exception $e) {
        error_log("Error checking error notes: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return [
        'fields' => array_unique($fields),
        'sources' => $fieldSources
    ];
}

/**
 * YAML 파일에서 필드 추출
 */
function parseYamlRules($filePath) {
    if (!file_exists($filePath)) {
        return ['error' => 'File not found: ' . $filePath];
    }
    
    $content = file_get_contents($filePath);
    $fields = [];
    $fieldDetails = [];
    
    // field: "field_name" 패턴 찾기
    preg_match_all('/field:\s*["\']?([a-zA-Z_][a-zA-Z0-9_.]*)["\']?/i', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field) {
            $fields[] = $field;
            if (!isset($fieldDetails[$field])) {
                $fieldDetails[$field] = [];
            }
            $fieldDetails[$field][] = 'rules.yaml field 정의';
        }
    }
    
    // collect_info로 수집하는 필드
    preg_match_all('/collect_info:\s*["\']?([a-zA-Z_][a-zA-Z0-9_.]*)["\']?/i', $content, $collectMatches);
    if (!empty($collectMatches[1])) {
        foreach ($collectMatches[1] as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
            if (!isset($fieldDetails[$field])) {
                $fieldDetails[$field] = [];
            }
            $fieldDetails[$field][] = 'rules.yaml collect_info';
        }
    }
    
    // depends_on 필드
    preg_match_all('/depends_on:\s*["\']?([a-zA-Z_][a-zA-Z0-9_.]*)["\']?/i', $content, $dependsMatches);
    if (!empty($dependsMatches[1])) {
        foreach ($dependsMatches[1] as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
            if (!isset($fieldDetails[$field])) {
                $fieldDetails[$field] = [];
            }
            $fieldDetails[$field][] = 'rules.yaml depends_on';
        }
    }
    
    return [
        'fields' => array_unique($fields),
        'field_details' => $fieldDetails,
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
    $fieldDetails = [];
    
    // $context['field_name'] 패턴
    preg_match_all('/\$context\[["\']([a-zA-Z_][a-zA-Z0-9_.]*)["\']\]/i', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field) {
            $fields[] = $field;
            if (!isset($fieldDetails[$field])) {
                $fieldDetails[$field] = [];
            }
            $fieldDetails[$field][] = 'data_access.php context';
        }
    }
    
    // DB 쿼리에서 필드명 추출
    preg_match_all('/SELECT\s+([a-zA-Z_][a-zA-Z0-9_.]*)/i', $content, $selectMatches);
    if (!empty($selectMatches[1])) {
        foreach ($selectMatches[1] as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
            if (!isset($fieldDetails[$field])) {
                $fieldDetails[$field] = [];
            }
            $fieldDetails[$field][] = 'data_access.php SELECT';
        }
    }
    
    // 함수 반환값에서 필드 추출
    preg_match_all('/function\s+\w+.*?return\s+\[.*?["\']([a-zA-Z_][a-zA-Z0-9_.]*)["\']/is', $content, $returnMatches);
    if (!empty($returnMatches[1])) {
        foreach ($returnMatches[1] as $field) {
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
            if (!isset($fieldDetails[$field])) {
                $fieldDetails[$field] = [];
            }
            $fieldDetails[$field][] = 'data_access.php return';
        }
    }
    
    return [
        'fields' => array_unique($fields),
        'field_details' => $fieldDetails,
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
    $fieldTables = [];
    
    // agent02 관련 테이블 목록
    $mainTables = [
        'mdl_alt42_exam_schedule',
        'mdl_user',
        'mdl_alt42_student_profiles',
        'mdl_alt42_calmness',
        'mdl_abessi_tracking',
        'mdl_abessi_messages',
        'mdl_alt42_goinghome'
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
                foreach ($fieldNames as $field) {
                    $allFields[] = $field;
                    $fieldTables[$field] = $tableName;
                }
            }
        } catch (Exception $e) {
            error_log("Table check failed for {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    return [
        'tables' => $tables,
        'all_fields' => array_unique($allFields),
        'field_tables' => $fieldTables
    ];
}

/**
 * data_access.php에서 실제 사용 여부 확인 함수
 */
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

/**
 * 데이터 타입 식별
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
        preg_match('/LLM|AI|프롬프트.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'gendata';
            $evidence[] = 'rules.yaml에서 생성 규칙 존재';
        } else if ($type === 'sysdata') {
            $type = 'hybriddata';
            $evidence[] = 'sysdata 기반 생성';
        }
    }
    
    // depends_on이 있으면 계산된 데이터
    if (preg_match('/depends_on.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
        if ($type === 'unknown') {
            $type = 'hybriddata';
            $evidence[] = '다른 필드에 의존하는 계산 데이터';
        }
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence
    ];
}

/**
 * 유사 필드명 매칭 (같은 데이터인데 이름이 다른 경우)
 */
function findSimilarFields($field1, $field2) {
    $similarity = 0;
    $reasons = [];
    
    // 완전 일치
    if ($field1 === $field2) {
        return ['similarity' => 100, 'reasons' => ['완전 일치']];
    }
    
    // 소문자/대문자만 다른 경우
    if (strtolower($field1) === strtolower($field2)) {
        return ['similarity' => 95, 'reasons' => ['대소문자만 다름']];
    }
    
    // 언더스코어 vs 점 (student_grade vs student.grade)
    $normalized1 = str_replace('.', '_', $field1);
    $normalized2 = str_replace('.', '_', $field2);
    if (strtolower($normalized1) === strtolower($normalized2)) {
        return ['similarity' => 90, 'reasons' => ['구분자만 다름 (. vs _)']];
    }
    
    // 부분 일치 (한쪽이 다른 쪽을 포함)
    if (stripos($field1, $field2) !== false || stripos($field2, $field1) !== false) {
        $similarity = 70;
        $reasons[] = '부분 일치';
    }
    
    // 유사도 계산 (Levenshtein distance 기반)
    $maxLen = max(strlen($field1), strlen($field2));
    if ($maxLen > 0) {
        $distance = levenshtein(strtolower($field1), strtolower($field2));
        $similarity = max($similarity, (1 - $distance / $maxLen) * 100);
    }
    
    return [
        'similarity' => $similarity,
        'reasons' => $reasons
    ];
}

/**
 * 메인 분석 함수
 */
function analyzeAgent02Data($studentid) {
    $basePath = __DIR__ . '/../rules/';
    $rulesPath = $basePath . 'rules.yaml';
    $dataAccessPath = $basePath . 'data_access.php';
    
    $analysis = [
        'student_id' => $studentid,
        'view_reports' => extractViewReportsFields($studentid),
        'rules_yaml' => parseYamlRules($rulesPath),
        'data_access' => parseDataAccess($dataAccessPath),
        'db_schema' => analyzeDBSchema($studentid),
        'mapping' => [],
        'orphan_fields' => [],
        'mismatched_fields' => []
    ];
    
    // 모든 필드 수집
    $viewReportsFields = $analysis['view_reports']['fields'] ?? [];
    $rulesFields = $analysis['rules_yaml']['fields'] ?? [];
    $dataAccessFields = $analysis['data_access']['fields'] ?? [];
    $dbFields = $analysis['db_schema']['all_fields'] ?? [];
    
    $allFields = array_unique(array_merge($viewReportsFields, $rulesFields, $dataAccessFields, $dbFields));
    
    // 실제 DB 데이터 존재 여부 확인 (rules.yaml 필드 기준)
    global $DB;
    $dbDataExists = [];
    $dataAccessContent = $analysis['data_access']['raw_content'] ?? '';
    
    foreach ($rulesFields as $field) {
        $exists = false;
        $tableName = '';
        $sampleValue = null;
        
        // alt42_exam_schedule 테이블 확인
        if ($DB->get_manager()->table_exists(new xmldb_table('alt42_exam_schedule'))) {
            try {
                $columns = $DB->get_columns('alt42_exam_schedule');
                if (isset($columns[$field])) {
                    $sampleData = $DB->get_record('alt42_exam_schedule', ['userid' => $studentid], $field, IGNORE_MISSING);
                    if ($sampleData && isset($sampleData->$field)) {
                        $exists = true;
                        $tableName = 'alt42_exam_schedule';
                        $sampleValue = is_string($sampleData->$field) ? substr($sampleData->$field, 0, 50) : $sampleData->$field;
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking alt42_exam_schedule: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
        }
        
        // alt42_goinghome 테이블 확인 (JSON 데이터)
        if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('alt42_goinghome'))) {
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
            $dataTypeInfo = identifyDataType($field, $analysis['rules_yaml']['raw_content'] ?? '', $dataAccessContent);
            $dbDataExists[] = [
                'field' => $field,
                'table' => $tableName,
                'type' => $dataTypeInfo['type'] ?? 'unknown',
                'sample' => $sampleValue
            ];
        }
    }
    $analysis['db_data_exists'] = $dbDataExists;
    
    // 각 필드에 대한 매핑 분석
    foreach ($allFields as $field) {
        $inViewReports = in_array($field, $viewReportsFields);
        $inRulesYaml = in_array($field, $rulesFields);
        $inDataAccess = checkDataAccessUsage($field, $dataAccessContent);
        $inDB = in_array($field, $dbFields);
        
        // 데이터 타입 식별
        $dataType = identifyDataType(
            $field,
            $analysis['rules_yaml']['raw_content'] ?? '',
            $analysis['data_access']['raw_content'] ?? ''
        );
        
        // 상태 결정
        $status = 'unknown';
        if ($inRulesYaml && $inDataAccess && $inDB) {
            $status = 'complete';
        } else if ($inRulesYaml && ($inDataAccess || $inDB)) {
            $status = 'partial';
        } else if ($inRulesYaml && !$inDataAccess && !$inDB) {
            $status = 'missing';
        } else if (!$inRulesYaml && $inDB) {
            $status = 'orphan';
        } else if ($inViewReports && !$inRulesYaml) {
            $status = 'unmapped';
        }
        
        $mapping = [
            'field' => $field,
            'in_view_reports' => $inViewReports,
            'in_rules_yaml' => $inRulesYaml,
            'in_data_access' => $inDataAccess,
            'in_db' => $inDB,
            'data_type' => $dataType['type'],
            'evidence' => $dataType['evidence'],
            'status' => $status,
            'view_reports_source' => $analysis['view_reports']['sources'][$field] ?? null
        ];
        
        $analysis['mapping'][] = $mapping;
        
        // 고아 필드 (DB에만 있음)
        if ($status === 'orphan') {
            $analysis['orphan_fields'][] = $field;
        }
        
        // 매핑 불일치 필드 (view_reports에 있지만 rules.yaml에 없음)
        if ($inViewReports && !$inRulesYaml) {
            $analysis['mismatched_fields'][] = $field;
        }
    }
    
    // 유사 필드 찾기
    $similarFields = [];
    foreach ($viewReportsFields as $vrField) {
        foreach ($rulesFields as $ruleField) {
            $similarity = findSimilarFields($vrField, $ruleField);
            if ($similarity['similarity'] >= 70 && $vrField !== $ruleField) {
                $similarFields[] = [
                    'view_reports_field' => $vrField,
                    'rules_yaml_field' => $ruleField,
                    'similarity' => $similarity['similarity'],
                    'reasons' => $similarity['reasons']
                ];
            }
        }
    }
    $analysis['similar_fields'] = $similarFields;
    
    return $analysis;
}

// 분석 실행
$error = null;
$analysis = null;

try {
    $analysis = analyzeAgent02Data($studentid);
} catch (Exception $e) {
    $error = $e->getMessage() . " [File: " . __FILE__ . ", Line: " . $e->getLine() . "]";
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent 02 데이터 매핑 분석</title>
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-form input,
        .filter-form button {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .filter-form button {
            background: #3b82f6;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
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
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
        }
        
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            position: sticky;
            top: 0;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-complete {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-partial {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-missing {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-orphan {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .badge-unmapped {
            background: #fce7f3;
            color: #9f1239;
        }
        
        .badge-survdata {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-sysdata {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-gendata {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-hybriddata {
            background: #e9d5ff;
            color: #6b21a8;
        }
        
        .badge-unknown {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .check-icon {
            color: #10b981;
        }
        
        .x-icon {
            color: #ef4444;
        }
        
        .evidence-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .evidence-list li {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .similarity-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        
        .similarity-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Agent 02 데이터 매핑 분석</h1>
            <p>view_reports.php, rules.yaml, data_access.php, DB 스키마 간의 데이터 매핑 상태를 분석합니다.</p>
        </div>
        
        <div class="filter-section">
            <form method="get" class="filter-form">
                <label>학생 ID:</label>
                <input type="number" name="studentid" value="<?php echo $studentid; ?>" placeholder="학생 ID">
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
        $orphanFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'orphan'));
        $unmappedFields = count(array_filter($analysis['mapping'], fn($m) => $m['status'] === 'unmapped'));
        
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
                <div class="value"><?php echo $orphanFields; ?></div>
            </div>
            <div class="stat-card">
                <h3>View만 존재</h3>
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
        
        <!-- 매핑 테이블 -->
        <div class="section">
            <h2>📋 전체 필드 매핑 현황</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>View Reports</th>
                        <th>Rules.yaml</th>
                        <th>Data Access</th>
                        <th>DB</th>
                        <th>데이터 타입</th>
                        <th>상태</th>
                        <th>증거</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['mapping'] as $mapping): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($mapping['field']); ?></strong></td>
                        <td><?php echo $mapping['in_view_reports'] ? '<span class="check-icon">✓</span>' : '<span class="x-icon">✗</span>'; ?></td>
                        <td><?php echo $mapping['in_rules_yaml'] ? '<span class="check-icon">✓</span>' : '<span class="x-icon">✗</span>'; ?></td>
                        <td><?php echo $mapping['in_data_access'] ? '<span class="check-icon">✓</span>' : '<span class="x-icon">✗</span>'; ?></td>
                        <td><?php echo $mapping['in_db'] ? '<span class="check-icon">✓</span>' : '<span class="x-icon">✗</span>'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $mapping['data_type']; ?>">
                                <?php echo strtoupper($mapping['data_type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $mapping['status']; ?>">
                                <?php echo ucfirst($mapping['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($mapping['evidence'])): ?>
                            <ul class="evidence-list">
                                <?php foreach ($mapping['evidence'] as $evidence): ?>
                                <li><?php echo htmlspecialchars($evidence); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <?php if ($mapping['view_reports_source']): ?>
                            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                📍 <?php echo htmlspecialchars($mapping['view_reports_source']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- DB에 실제 데이터가 존재하는 rules.yaml 필드 -->
        <?php if (!empty($analysis['db_data_exists'])): ?>
        <div class="section">
            <h2>✅ DB에 실제 데이터가 존재하는 rules.yaml 필드</h2>
            <p style="color: #10b981; margin-bottom: 1rem;">총 <?php echo count($analysis['db_data_exists']); ?>개 필드가 DB에 실제 데이터를 가지고 있습니다.</p>
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
                    <?php foreach ($analysis['db_data_exists'] as $item): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                            <td>
                                <span class="badge badge-<?php echo $item['type'] === 'survdata' ? 'surv' : ($item['type'] === 'sysdata' ? 'sys' : ($item['type'] === 'gendata' ? 'gen' : ($item['type'] === 'hybriddata' ? 'hybrid' : 'warning'))); ?>">
                                    <?php echo strtoupper($item['type']); ?>
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
        
        <!-- 고아 필드 (DB에만 있음) -->
        <?php if (!empty($analysis['orphan_fields'])): ?>
        <div class="section">
            <h2>🔍 DB에만 존재하는 필드 (rules.yaml 미사용)</h2>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($analysis['orphan_fields'] as $field): ?>
                <li style="padding: 0.5rem; background: #f9fafb; margin: 0.25rem 0; border-radius: 4px;">
                    <strong><?php echo htmlspecialchars($field); ?></strong>
                    <?php if (isset($analysis['db_schema']['field_tables'][$field])): ?>
                    <span style="color: #6b7280; font-size: 0.875rem;">(<?php echo htmlspecialchars($analysis['db_schema']['field_tables'][$field]); ?>)</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- 매핑 불일치 필드 -->
        <?php if (!empty($analysis['mismatched_fields'])): ?>
        <div class="section">
            <h2>⚠️ View Reports에만 존재하는 필드 (rules.yaml 미매핑)</h2>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($analysis['mismatched_fields'] as $field): ?>
                <li style="padding: 0.5rem; background: #fef2f2; margin: 0.25rem 0; border-radius: 4px;">
                    <strong><?php echo htmlspecialchars($field); ?></strong>
                    <?php if (isset($analysis['view_reports']['sources'][$field])): ?>
                    <span style="color: #6b7280; font-size: 0.875rem;">(<?php echo htmlspecialchars($analysis['view_reports']['sources'][$field]); ?>)</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- 유사 필드 (매핑 불일치 가능성) -->
        <?php if (!empty($analysis['similar_fields'])): ?>
        <div class="section">
            <h2>🔗 유사 필드 (매핑 불일치 가능성)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>View Reports 필드</th>
                        <th>Rules.yaml 필드</th>
                        <th>유사도</th>
                        <th>이유</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['similar_fields'] as $similar): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($similar['view_reports_field']); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($similar['rules_yaml_field']); ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span><?php echo round($similar['similarity']); ?>%</span>
                                <div class="similarity-bar" style="flex: 1;">
                                    <div class="similarity-fill" style="width: <?php echo $similar['similarity']; ?>%;"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($similar['reasons'])): ?>
                            <ul class="evidence-list">
                                <?php foreach ($similar['reasons'] as $reason): ?>
                                <li><?php echo htmlspecialchars($reason); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
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


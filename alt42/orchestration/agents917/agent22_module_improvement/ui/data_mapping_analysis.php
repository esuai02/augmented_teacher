<?php
/**
 * 데이터 매핑 분석 도구 - Agent22 (범용)
 * view_reports.php에서 사용하는 데이터와 rules.yaml, data_access.php를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent22_module_improvement/ui/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// xmldb_table 클래스 로드
if (isset($CFG) && isset($CFG->libdir)) {
    require_once($CFG->libdir.'/ddllib.php');
}

// 파라미터
$agentid = optional_param('agentid', 'agent01_onboarding', PARAM_TEXT);
$studentid = optional_param('studentid', 1603, PARAM_INT);

// 권한 체크
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    $studentid = $USER->id;
}

// 에이전트 경로 확인
$agentBasePath = __DIR__ . '/../' . $agentid . '/rules/';
if (!file_exists($agentBasePath)) {
    // 다른 경로 시도
    $agentBasePath = __DIR__ . '/../../' . $agentid . '/rules/';
}

if (!file_exists($agentBasePath)) {
    die('에이전트 경로를 찾을 수 없습니다: ' . htmlspecialchars($agentid));
}

// rules.yaml 파일 읽기
$rulesYamlPath = $agentBasePath . 'rules.yaml';
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
$dataAccessPath = $agentBasePath . 'data_access.php';
$dataAccessContent = file_exists($dataAccessPath) ? file_get_contents($dataAccessPath) : '';

$dataAccessFields = [];
if (!empty($dataAccessContent)) {
    // $context['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$context\[\'([^\']+)\'\]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_unique($matches[1]);
        sort($dataAccessFields);
    }
    
    // $onboarding->필드명 패턴으로 필드 추출
    preg_match_all('/\$onboarding->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    // $profile->필드명 패턴으로 필드 추출
    preg_match_all('/\$profile->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

// view_reports.php에서 사용하는 데이터 필드 추출 (agent01의 경우)
$viewReportsPath = __DIR__ . '/../../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
if (!file_exists($viewReportsPath)) {
    $viewReportsPath = __DIR__ . '/../../../studenthome/contextual_agents/beforegoinghome/view_reports.php';
}

$viewReportsFields = [];
$viewReportsTables = [];
$viewReportsContent = ''; // 초기화

if (file_exists($viewReportsPath)) {
    $viewReportsContent = file_get_contents($viewReportsPath);
    
    // 테이블명 추출
    preg_match_all('/\{([a-z_]+)\}/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_unique($matches[1]);
    }
    
    // $data['필드명'] 패턴으로 필드 추출
    preg_match_all('/\$data\[\'([^\']+)\'\]/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // 설문 응답 필드
    preg_match_all('/\'([a-z_]+)\'\s*=>/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    $viewReportsFields = array_unique($viewReportsFields);
    sort($viewReportsFields);
}

// 실제 DB에서 데이터 조회
$dbFields = [];
$dbTables = [];

// rules.yaml에서 언급된 테이블명 추출
if (!empty($rulesYamlContent)) {
    preg_match_all('/\{([a-z_]+)\}/', $rulesYamlContent, $matches);
    if (!empty($matches[1])) {
        $potentialTables = array_unique($matches[1]);
        foreach ($potentialTables as $tableName) {
            if ($DB->get_manager()->table_exists(new xmldb_table($tableName))) {
                $dbTables[] = $tableName;
                try {
                    $columns = $DB->get_columns($tableName);
                    foreach ($columns as $colName => $colInfo) {
                        $dbFields[] = $tableName . '.' . $colName;
                    }
                } catch (Exception $e) {
                    error_log("Error getting columns from {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
        }
    }
}

// data_access.php에서 사용하는 테이블명 추출
if (!empty($dataAccessContent)) {
    preg_match_all('/get_record\([\'"]([a-z_]+)[\'"]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $tableName) {
            if (!in_array($tableName, $dbTables) && $DB->get_manager()->table_exists(new xmldb_table($tableName))) {
                $dbTables[] = $tableName;
                try {
                    $columns = $DB->get_columns($tableName);
                    foreach ($columns as $colName => $colInfo) {
                        $dbFields[] = $tableName . '.' . $colName;
                    }
                } catch (Exception $e) {
                    error_log("Error getting columns from {$tableName}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
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
    
    // $profile->필드명 패턴 (agent22 특화)
    if (strpos($dataAccessContent, "\$profile->" . $fieldName) !== false) {
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
        $viewReportsContentForField = isset($viewReportsContent) ? $viewReportsContent : '';
        $dataTypeInfo = identifyDataType($field, $rulesYamlContent ?? '', $dataAccessContent ?? '', $tableName, $viewReportsContentForField);
        $dbDataExists[] = [
            'field' => $field,
            'table' => $tableName,
            'type' => $dataTypeInfo['type'] ?? 'unknown',
            'db_applied' => $dataTypeInfo['db_applied'] ?? false,
            'sample' => $sampleValue
        ];
    }
}

/**
 * 데이터 타입 식별 함수 (metadata 기준)
 * DB 적용 여부 구분: uidata, gendata, sysdata, survdata
 */
function identifyDataType($fieldName, $rulesContent = '', $dataAccessContent = '', $tableName = '', $viewReportsContent = '') {
    $type = 'unknown';
    $evidence = [];
    $dbApplied = false; // DB 적용 여부
    
    // 1. rules.yaml에서 source_type 확인
    if (!empty($rulesContent)) {
        // source_type: survey 패턴
        if (preg_match('/source_type:\s*["\']?survey["\']?/i', $rulesContent) && 
            preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'survdata';
            $evidence[] = 'rules.yaml에서 survey로 정의됨';
            $dbApplied = true;
        }
        // source_type: system 패턴
        elseif (preg_match('/source_type:\s*["\']?system["\']?/i', $rulesContent) && 
                preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'sysdata';
            $evidence[] = 'rules.yaml에서 system으로 정의됨';
            $dbApplied = true;
        }
        // source_type: generated 패턴 (LLM modeling)
        elseif (preg_match('/source_type:\s*["\']?generated["\']?/i', $rulesContent) && 
                preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'gendata';
            $evidence[] = 'rules.yaml에서 generated(LLM modeling)로 정의됨';
            $dbApplied = true;
        }
        // source_type: interface 패턴 (사용자 인터페이스 입력)
        elseif (preg_match('/source_type:\s*["\']?interface["\']?/i', $rulesContent) && 
                preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'uidata';
            $evidence[] = 'rules.yaml에서 interface로 정의됨';
            $dbApplied = true;
        }
    }
    
    // 2. view_reports.php에서 사용자 인터페이스 입력 확인 (uidata)
    if (!empty($viewReportsContent)) {
        // 사용자가 직접 입력하는 필드 패턴 확인
        $uiInputPatterns = [
            '/input.*' . preg_quote($fieldName, '/') . '/i',
            '/textarea.*' . preg_quote($fieldName, '/') . '/i',
            '/select.*' . preg_quote($fieldName, '/') . '/i',
            '/\$data\[\'[\'"]?' . preg_quote($fieldName, '/') . '[\'"]?\]/i',
            '/responses\[[\'"]?' . preg_quote($fieldName, '/') . '[\'"]?\]/i'
        ];
        
        foreach ($uiInputPatterns as $pattern) {
            if (preg_match($pattern, $viewReportsContent)) {
                if ($type === 'unknown') {
                    $type = 'uidata';
                    $evidence[] = 'view_reports.php에서 사용자 인터페이스 입력으로 확인됨';
                    $dbApplied = true;
                }
                break;
            }
        }
    }
    
    // 3. data_access.php에서 데이터 소스 확인
    if (!empty($dataAccessContent)) {
        // 설문 테이블에서 가져오는 경우 (survdata)
        if (preg_match('/get_record.*onboarding.*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent) ||
            preg_match('/' . preg_quote($fieldName, '/') . '.*onboarding/i', $dataAccessContent)) {
            if ($type === 'unknown') {
                $type = 'survdata';
                $evidence[] = 'data_access.php에서 onboarding 테이블 조회 (설문 데이터)';
                $dbApplied = true;
            }
        }
        // 시스템 테이블에서 가져오는 경우 (sysdata)
        elseif (preg_match('/get_record.*user.*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent) ||
                preg_match('/get_record.*calmness.*' . preg_quote($fieldName, '/') . '/i', $dataAccessContent)) {
            if ($type === 'unknown') {
                $type = 'sysdata';
                $evidence[] = 'data_access.php에서 시스템 테이블 조회';
                $dbApplied = true;
            }
        }
    }
    
    // 4. 테이블명 기반 추론
    if ($type === 'unknown' && !empty($tableName)) {
        if (strpos($tableName, 'onboarding') !== false || strpos($tableName, 'survey') !== false || 
            strpos($tableName, 'goinghome') !== false) {
            $type = 'survdata';
            $evidence[] = '테이블명 기반 추론: 설문 데이터';
            $dbApplied = true;
        } elseif (strpos($tableName, 'user') !== false || strpos($tableName, 'calmness') !== false || 
                  strpos($tableName, 'tracking') !== false || strpos($tableName, 'messages') !== false) {
            $type = 'sysdata';
            $evidence[] = '테이블명 기반 추론: 시스템 데이터';
            $dbApplied = true;
        }
    }
    
    // 5. 필드명 패턴 기반 추론
    if ($type === 'unknown') {
        // uidata 패턴: 사용자가 직접 입력하는 필드
        $uiPatterns = ['goal', 'plan', 'question', 'response', 'answer', 'feedback', 'note', 'memo'];
        // survdata 패턴: 설문 응답 필드
        $survPatterns = ['calmness', 'pomodoro', 'satisfaction', 'stress', 'anxiety', 'boredom', 'weekly_goal', 'daily_plan'];
        // sysdata 패턴: 시스템 자동 생성 필드
        $sysPatterns = ['timecreated', 'timemodified', 'userid', 'id', 'level', 'duration', 'timestart', 'timeend'];
        // gendata 패턴: AI/계산 생성 필드
        $genPatterns = ['grade', 'usage', 'count', 'analysis', 'score', 'recommendation', 'diagnosis'];
        
        foreach ($uiPatterns as $pattern) {
            if (stripos($fieldName, $pattern) !== false) {
                $type = 'uidata';
                $evidence[] = '필드명 패턴 기반 추론: 사용자 인터페이스 입력';
                $dbApplied = true;
                break;
            }
        }
        
        if ($type === 'unknown') {
            foreach ($survPatterns as $pattern) {
                if (stripos($fieldName, $pattern) !== false) {
                    $type = 'survdata';
                    $evidence[] = '필드명 패턴 기반 추론: 설문 데이터';
                    $dbApplied = true;
                    break;
                }
            }
        }
        
        if ($type === 'unknown') {
            foreach ($sysPatterns as $pattern) {
                if (stripos($fieldName, $pattern) !== false) {
                    $type = 'sysdata';
                    $evidence[] = '필드명 패턴 기반 추론: 시스템 데이터';
                    $dbApplied = true;
                    break;
                }
            }
        }
        
        if ($type === 'unknown') {
            foreach ($genPatterns as $pattern) {
                if (stripos($fieldName, $pattern) !== false) {
                    $type = 'gendata';
                    $evidence[] = '필드명 패턴 기반 추론: 생성 데이터 (LLM modeling)';
                    $dbApplied = true;
                    break;
                }
            }
        }
    }
    
    // 6. generation_rule이 있으면 gendata (LLM modeling)
    if (!empty($rulesContent)) {
        $gendataPatterns = [
            '/generate.*' . preg_quote($fieldName, '/') . '/i',
            '/generation_rule.*' . preg_quote($fieldName, '/') . '/i',
            '/LLM.*' . preg_quote($fieldName, '/') . '/i',
            '/modeling.*' . preg_quote($fieldName, '/') . '/i'
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
                $evidence[] = 'rules.yaml에서 생성 규칙 존재 (LLM modeling)';
                $dbApplied = true;
            } elseif ($type === 'sysdata' || $type === 'survdata' || $type === 'uidata') {
                // 기존 데이터 기반 생성인 경우 gendata로 변경
                $type = 'gendata';
                $evidence[] = '기존 데이터 기반 LLM 생성';
                $dbApplied = true;
            }
        }
    }
    
    // 7. depends_on이 있으면 gendata (계산 데이터)
    if (!empty($rulesContent)) {
        if (preg_match('/depends_on.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
            if ($type === 'unknown') {
                $type = 'gendata';
                $evidence[] = '다른 필드에 의존하는 계산 데이터';
                $dbApplied = true;
            }
        }
    }
    
    // 8. analyze 액션이 있으면 gendata (분석 데이터)
    if (!empty($rulesContent)) {
        if (preg_match('/analyze:.*' . preg_quote($fieldName, '/') . '/i', $rulesContent)) {
            if ($type === 'unknown') {
                $type = 'gendata';
                $evidence[] = 'rules.yaml에서 analyze 액션으로 분석';
                $dbApplied = true;
            }
        }
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence,
        'db_applied' => $dbApplied
    ];
}

// 분석 결과 생성
$analysis = [
    'agent_id' => $agentid,
    'rules_yaml' => [
        'fields' => $rulesFields,
        'count' => count($rulesFields),
        'raw_content' => $rulesYamlContent
    ],
    'data_access' => [
        'fields' => $dataAccessFields,
        'count' => count($dataAccessFields),
        'raw_content' => $dataAccessContent
    ],
    'view_reports' => [
        'fields' => $viewReportsFields,
        'tables' => $viewReportsTables,
        'count' => count($viewReportsFields)
    ],
    'db' => [
        'fields' => $dbFields,
        'tables' => $dbTables,
        'count' => count($dbFields)
    ],
    'mapping' => [],
    'orphan_fields' => [],
    'mismatched_fields' => [],
    'unmapped_fields' => []
];

// 모든 필드 수집
$allFields = array_unique(array_merge($rulesFields, $dataAccessFields, $viewReportsFields));
sort($allFields);

// 각 필드에 대한 매핑 분석
foreach ($allFields as $field) {
    $inViewReports = in_array($field, $viewReportsFields);
    $inRulesYaml = in_array($field, $rulesFields);
    $inDataAccess = in_array($field, $dataAccessFields);
    $inDB = false;
    $dbTableName = '';
    
    // DB 필드 확인
    foreach ($dbFields as $dbField) {
        $dbFieldName = explode('.', $dbField)[1] ?? $dbField;
        if ($dbFieldName === $field) {
            $inDB = true;
            $dbTableName = explode('.', $dbField)[0] ?? '';
            break;
        }
    }
    
    // 데이터 타입 식별 (metadata 기준)
    $viewReportsContentForField = isset($viewReportsContent) ? $viewReportsContent : '';
    $dataType = identifyDataType(
        $field,
        $rulesYamlContent,
        $dataAccessContent,
        $dbTableName,
        $viewReportsContentForField
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
        'metadata' => $dataType['type'], // metadata 필드명
        'db_applied' => $dataType['db_applied'] ?? false, // DB 적용 여부
        'in_view_reports' => $inViewReports,
        'in_rules_yaml' => $inRulesYaml,
        'in_data_access' => $inDataAccess,
        'in_db' => $inDB,
        'db_table' => $dbTableName,
        'data_type' => $dataType['type'], // 하위 호환성 유지
        'evidence' => $dataType['evidence'],
        'status' => $status
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

// 유사 필드 찾기 (매핑 불일치)
$similarFields = [];
foreach ($viewReportsFields as $vrField) {
    foreach ($rulesFields as $ruleField) {
        // 유사도 체크 (간단한 문자열 유사도)
        $similarity = 0;
        similar_text(strtolower($vrField), strtolower($ruleField), $similarity);
        
        if ($similarity > 60 && $vrField !== $ruleField) {
            $similarFields[] = [
                'view_reports_field' => $vrField,
                'rules_field' => $ruleField,
                'similarity' => round($similarity, 2),
                'type' => 'similar_concept'
            ];
        }
    }
}

$analysis['similar_fields'] = $similarFields;

// 통계 계산
$stats = [
    'total_fields' => count($allFields),
    'rules_fields' => count($rulesFields),
    'data_access_fields' => count($dataAccessFields),
    'view_reports_fields' => count($viewReportsFields),
    'db_fields' => count($dbFields),
    'complete_mappings' => count(array_filter($analysis['mapping'], function($m) { return $m['status'] === 'complete'; })),
    'orphan_fields_count' => count($analysis['orphan_fields']),
    'mismatched_fields_count' => count($analysis['mismatched_fields']),
    'similar_fields_count' => count($similarFields)
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - <?php echo htmlspecialchars($agentid); ?></title>
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
            background: #e7d4f8;
            color: #6f42c1;
        }
        
        .badge-uidata {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-unknown {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .action-button {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            position: relative;
        }
        
        .action-button:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .action-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
        }
        
        /* Tooltip 스타일 */
        .action-button[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            padding: 8px 12px;
            background: #1f2937;
            color: white;
            font-size: 0.75rem;
            font-weight: normal;
            white-space: normal;
            max-width: 1500px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            pointer-events: none;
            line-height: 1.4;
        }
        
        .action-button[title]:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 2px;
            border: 6px solid transparent;
            border-top-color: #1f2937;
            z-index: 1001;
            pointer-events: none;
        }
        
        .improvement-content {
            min-height: 20px;
            padding: 4px 0;
        }
        
        .action-button-list {
            background: #10b981;
        }
        
        .action-button-list:hover {
            background: #059669;
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }
        
        .action-button-list:active {
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }
        
        .inputtype-select {
            transition: all 0.2s;
        }
        
        /* inputtype별 배경색 - 항상 적용 */
        .inputtype-select[data-current="uidata"],
        .inputtype-select[data-current="uidata"]:focus,
        .inputtype-select[data-current="uidata"]:hover {
            background: #dbeafe !important;
            color: #1e40af !important;
            border-color: #93c5fd !important;
        }
        
        .inputtype-select[data-current="gendata"],
        .inputtype-select[data-current="gendata"]:focus,
        .inputtype-select[data-current="gendata"]:hover {
            background: #cfe2ff !important;
            color: #084298 !important;
            border-color: #9ec5fe !important;
        }
        
        .inputtype-select[data-current="sysdata"],
        .inputtype-select[data-current="sysdata"]:focus,
        .inputtype-select[data-current="sysdata"]:hover {
            background: #fff3cd !important;
            color: #856404 !important;
            border-color: #ffd966 !important;
        }
        
        .inputtype-select[data-current="survdata"],
        .inputtype-select[data-current="survdata"]:focus,
        .inputtype-select[data-current="survdata"]:hover {
            background: #d4edda !important;
            color: #155724 !important;
            border-color: #86cfac !important;
        }
        
        .inputtype-select[data-current="unknown"],
        .inputtype-select[data-current="unknown"]:focus,
        .inputtype-select[data-current="unknown"]:hover {
            background: #f3f4f6 !important;
            color: #6b7280 !important;
            border-color: #d1d5db !important;
        }
        
        .inputtype-select:hover {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: scale(1.02);
        }
        
        .inputtype-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .status-select {
            transition: all 0.2s;
        }
        
        /* 상태별 배경색 - 항상 적용 */
        .status-select[data-current="준비"],
        .status-select[data-current="준비"]:focus,
        .status-select[data-current="준비"]:hover {
            background: #fef3c7 !important;
            color: #92400e !important;
            border-color: #fcd34d !important;
        }
        
        .status-select[data-current="동작"],
        .status-select[data-current="동작"]:focus,
        .status-select[data-current="동작"]:hover {
            background: #d1fae5 !important;
            color: #065f46 !important;
            border-color: #6ee7b7 !important;
        }
        
        .status-select:hover {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            transform: scale(1.02);
        }
        
        .status-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        
        .rules-yaml-toggle {
            transition: all 0.2s;
        }
        
        .rules-yaml-toggle:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }
        
        .rules-yaml-toggle:active {
            transform: scale(0.95);
        }
        
        .badge-complete {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-partial {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-missing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-orphan {
            background: #ffeaa7;
            color: #856404;
        }
        
        .badge-unmapped {
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
        
        .evidence-list {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .evidence-list li {
            margin-left: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php?userid=<?php echo $studentid; ?>" class="back-button">← Agent Garden으로 돌아가기</a>
        
        <div class="header">
            <h1>📊 데이터 매핑 분석 리포트</h1>
            <p><?php echo htmlspecialchars($agentid); ?> - rules.yaml vs data_access.php vs DB 비교 분석</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">학생 ID: <?php echo $studentid; ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Rules.yaml 필드</h3>
                <div class="number"><?php echo $stats['rules_fields']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Data Access 필드</h3>
                <div class="number"><?php echo $stats['data_access_fields']; ?></div>
            </div>
            <div class="stat-card">
                <h3>View Reports 필드</h3>
                <div class="number"><?php echo $stats['view_reports_fields']; ?></div>
            </div>
            <div class="stat-card">
                <h3>DB 필드</h3>
                <div class="number"><?php echo $stats['db_fields']; ?></div>
            </div>
            <div class="stat-card">
                <h3>완전 매핑</h3>
                <div class="number"><?php echo $stats['complete_mappings']; ?></div>
            </div>
            <div class="stat-card">
                <h3>고아 필드</h3>
                <div class="number" style="color: #dc2626;"><?php echo $stats['orphan_fields_count']; ?></div>
            </div>
        </div>
        
        <!-- 전체 필드 매핑 테이블 -->
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0;">📋 전체 필드 매핑 현황</h2>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button class="action-button" 
                                data-action="context-select"
                                title="현재 에이전트의 포괄질문 표시">적용할 문맥 선택하기</button>
                        <button class="action-button" 
                                data-action="rule-update"
                                title="현재 에이전트의 포괄 및 데이터 기반 질문속의 내용들을 답변하기 위한 완결성 있는 룰들을 생성하고 점검. 새로운 룰을 적용할 때는 포괄질문 및 데이터기반 질문의 내용을 수정, 추가">Rule 업데이트</button>
                        <button class="action-button" 
                                data-action="metadata-update"
                                title="새롭게 추가된 룰 속의 데이터, 시스템 DB 추가 정보를 가지고 와서 metadata를 업데이트">Metadata 업데이트</button>
                        <button class="action-button" 
                                data-action="input-method-search"
                                title="인터페이스 목록 내용과 사용자 UX 목록을 검토하여 가장 효과적인 입력방법을 탐색한 다음 개선탭에 내용을 추가">입력방법 탐색</button>
                        <button class="action-button" 
                                data-action="ontology-update"
                                title="온톨로지 업데이트">온톨로지 업데이트</button>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-left: 1rem; padding-left: 1rem; border-left: 2px solid #e5e7eb;">
                        <button class="action-button action-button-list" 
                                data-action="interface-list"
                                title="인터페이스 목록 보기">인터페이스 목록 보기</button>
                        <button class="action-button action-button-list" 
                                data-action="context-list"
                                title="Context 목록보기">Context 목록보기</button>
                    </div>
                </div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>metadata</th>
                        <th>inputtype</th>
                        <th>상태</th>
                        <th>DB 적용</th>
                        <th>Rules.yaml</th>
                        <th>증거</th>
                        <th>개선</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['mapping'] as $index => $mapping): 
                        $currentInputtype = $mapping['metadata'] ?? $mapping['data_type'] ?? 'unknown';
                        $fieldName = htmlspecialchars($mapping['field']);
                        // 상태를 준비/동작으로 매핑 (complete, partial -> 동작, missing, orphan, unmapped -> 준비)
                        $currentStatus = $mapping['status'] ?? 'unknown';
                        $isReady = in_array($currentStatus, ['missing', 'orphan', 'unmapped', 'unknown']);
                        $statusValue = $isReady ? '준비' : '동작';
                    ?>
                    <tr>
                        <td><code><?php echo $fieldName; ?></code></td>
                        <td>
                            <select class="inputtype-select" 
                                    data-field="<?php echo $fieldName; ?>" 
                                    data-index="<?php echo $index; ?>"
                                    data-current="<?php echo $currentInputtype; ?>"
                                    style="padding: 4px 8px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.875rem; cursor: pointer; min-width: 120px; font-weight: 500;">
                                <option value="uidata" style="background: #dbeafe; color: #1e40af;" <?php echo $currentInputtype === 'uidata' ? 'selected' : ''; ?>>uidata</option>
                                <option value="gendata" style="background: #cfe2ff; color: #084298;" <?php echo $currentInputtype === 'gendata' ? 'selected' : ''; ?>>gendata</option>
                                <option value="sysdata" style="background: #fff3cd; color: #856404;" <?php echo $currentInputtype === 'sysdata' ? 'selected' : ''; ?>>sysdata</option>
                                <option value="survdata" style="background: #d4edda; color: #155724;" <?php echo $currentInputtype === 'survdata' ? 'selected' : ''; ?>>survdata</option>
                                <option value="unknown" style="background: #f3f4f6; color: #6b7280;" <?php echo $currentInputtype === 'unknown' ? 'selected' : ''; ?>>unknown</option>
                            </select>
                        </td>
                        <td>
                            <select class="status-select" 
                                    data-field="<?php echo $fieldName; ?>" 
                                    data-index="<?php echo $index; ?>"
                                    data-current="<?php echo $statusValue; ?>"
                                    style="padding: 4px 8px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.875rem; cursor: pointer; min-width: 100px; font-weight: 500;">
                                <option value="준비" style="background: #fef3c7; color: #92400e;" <?php echo $statusValue === '준비' ? 'selected' : ''; ?>>준비</option>
                                <option value="동작" style="background: #d1fae5; color: #065f46;" <?php echo $statusValue === '동작' ? 'selected' : ''; ?>>동작</option>
                            </select>
                        </td>
                        <td>
                            <?php if ($mapping['db_applied'] ?? false): ?>
                                <span style="color: #10b981; font-weight: bold;">✅ 적용</span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">❌ 미적용</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="rules-yaml-toggle" 
                                    data-field="<?php echo $fieldName; ?>" 
                                    data-index="<?php echo $index; ?>"
                                    data-current="<?php echo $mapping['in_rules_yaml'] ? '1' : '0'; ?>"
                                    style="background: none; border: none; cursor: pointer; padding: 0; transition: all 0.2s; display: flex; align-items: center; gap: 4px;"
                                    title="클릭하여 Rules.yaml 적용 여부 변경">
                                <?php if ($mapping['in_rules_yaml']): ?>
                                    <span style="color: #10b981; font-weight: bold;">✅</span>
                                    <span style="color: #10b981; font-weight: bold;">적용</span>
                                <?php else: ?>
                                    <span style="color: #ef4444;">❌</span>
                                    <span style="color: #9ca3af;">미적용</span>
                                <?php endif; ?>
                            </button>
                        </td>
                        <td>
                            <?php if (!empty($mapping['evidence'])): ?>
                            <ul class="evidence-list">
                                <?php foreach ($mapping['evidence'] as $ev): ?>
                                <li><?php echo htmlspecialchars($ev); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="improvement-content" data-field="<?php echo $fieldName; ?>" data-index="<?php echo $index; ?>">
                                <span style="color: #9ca3af; font-size: 0.875rem;">-</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- DB에 실제 데이터가 존재하는 rules.yaml 필드 -->
        <?php if (!empty($dbDataExists)): ?>
        <div class="section">
            <h2>✅ DB에 실제 데이터가 존재하는 rules.yaml 필드</h2>
            <p style="color: #10b981; margin-bottom: 1rem;">총 <?php echo count($dbDataExists); ?>개 필드가 DB에 실제 데이터를 가지고 있습니다.</p>
            <table class="data-table">
                <thead>
                        <tr>
                            <th>metadata</th>
                            <th>inputtype</th>
                            <th>DB 적용</th>
                            <th>테이블</th>
                            <th>샘플 데이터</th>
                        </tr>
                </thead>
                <tbody>
                    <?php foreach ($dbDataExists as $item): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($item['field']); ?></code></td>
                            <td>
                                <span class="badge badge-<?php 
                                    $type = $item['type'] ?? 'unknown';
                                    echo $type === 'survdata' ? 'surv' : 
                                        ($type === 'sysdata' ? 'sys' : 
                                        ($type === 'gendata' ? 'gen' : 
                                        ($type === 'uidata' ? 'uidata' : 'unknown'))); 
                                ?>">
                                    <?php echo htmlspecialchars($type); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['db_applied'] ?? false): ?>
                                    <span style="color: #10b981; font-weight: bold;">✅ 적용</span>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">❌ 미적용</span>
                                <?php endif; ?>
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
        
        <!-- DB에 있는데 rules.yaml에 사용하지 않는 데이터 -->
        <div class="section">
            <h2>🗄️ DB에 있는데 rules.yaml에 사용하지 않는 데이터</h2>
            <?php if (empty($analysis['orphan_fields'])): ?>
                <div class="empty-state">
                    <p>모든 DB 필드가 rules.yaml에서 사용되고 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($analysis['orphan_fields']); ?>개 DB 필드가 rules.yaml에서 사용되지 않습니다.</p>
                <div class="field-list">
                    <?php foreach ($analysis['orphan_fields'] as $field): ?>
                        <?php 
                        $fieldMapping = null;
                        foreach ($analysis['mapping'] as $m) {
                            if ($m['field'] === $field) {
                                $fieldMapping = $m;
                                break;
                            }
                        }
                        ?>
                        <span class="field-item">
                            <?php echo htmlspecialchars($field); ?>
                            <?php if ($fieldMapping): ?>
                                <span class="badge badge-<?php echo $fieldMapping['data_type']; ?>">
                                    <?php echo $fieldMapping['data_type']; ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 매핑 불일치 필드 -->
        <div class="section">
            <h2>⚠️ View Reports에 있는데 rules.yaml에 없는 필드</h2>
            <?php if (empty($analysis['mismatched_fields'])): ?>
                <div class="empty-state">
                    <p>모든 View Reports 필드가 rules.yaml에 정의되어 있습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #dc2626; margin-bottom: 1rem;">총 <?php echo count($analysis['mismatched_fields']); ?>개 필드가 매핑되지 않았습니다.</p>
                <div class="field-list">
                    <?php foreach ($analysis['mismatched_fields'] as $field): ?>
                        <span class="field-item badge-unmapped"><?php echo htmlspecialchars($field); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 유사 필드 (매핑 불일치) -->
        <div class="section">
            <h2>🔍 유사 필드 (매핑 불일치 가능성)</h2>
            <?php if (empty($analysis['similar_fields'])): ?>
                <div class="empty-state">
                    <p>유사한 필드명이 발견되지 않았습니다. ✅</p>
                </div>
            <?php else: ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">총 <?php echo count($analysis['similar_fields']); ?>개 유사 필드가 발견되었습니다.</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>View Reports 필드</th>
                            <th>Rules.yaml 필드</th>
                            <th>유사도</th>
                            <th>타입</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis['similar_fields'] as $similar): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($similar['view_reports_field']); ?></code></td>
                            <td><code><?php echo htmlspecialchars($similar['rules_field']); ?></code></td>
                            <td><?php echo $similar['similarity']; ?>%</td>
                            <td><?php echo htmlspecialchars($similar['type']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 데이터 타입별 통계 (Inputtype 기준) -->
        <div class="section">
            <h2>📊 Inputtype별 통계 (DB 적용 여부)</h2>
            <?php
            $typeStats = [];
            $dbAppliedStats = [];
            foreach ($analysis['mapping'] as $mapping) {
                $type = $mapping['metadata'] ?? $mapping['data_type'] ?? 'unknown';
                if (!isset($typeStats[$type])) {
                    $typeStats[$type] = 0;
                    $dbAppliedStats[$type] = 0;
                }
                $typeStats[$type]++;
                if ($mapping['db_applied'] ?? false) {
                    $dbAppliedStats[$type]++;
                }
            }
            ?>
            <div class="stats-grid">
                <?php foreach ($typeStats as $type => $count): 
                    $dbApplied = $dbAppliedStats[$type] ?? 0;
                    $dbNotApplied = $count - $dbApplied;
                ?>
                <div class="stat-card">
                    <h3><?php echo strtoupper($type); ?></h3>
                    <div class="number"><?php echo $count; ?></div>
                    <div style="margin-top: 0.5rem; font-size: 0.85rem;">
                        <span style="color: #10b981;">✅ DB 적용: <?php echo $dbApplied; ?></span><br>
                        <span style="color: #9ca3af;">❌ 미적용: <?php echo $dbNotApplied; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <h3 style="font-size: 1rem; margin-bottom: 0.5rem; color: #374151;">Inputtype 설명</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 0.5rem;"><strong>uidata</strong>: 인터페이스를 통한 입력여부 (사용자 직접 입력)</li>
                    <li style="margin-bottom: 0.5rem;"><strong>gendata</strong>: 생성입력 여부 (LLM modeling - AI 생성)</li>
                    <li style="margin-bottom: 0.5rem;"><strong>sysdata</strong>: 시스템에 의한 입력여부 (시스템 자동 입력)</li>
                    <li style="margin-bottom: 0.5rem;"><strong>survdata</strong>: 설문에 의한 입력 (설문 응답)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Inputtype 변경 이벤트 처리
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('.inputtype-select');
            
            // 초기 로드 시 선택된 값으로 data-current 설정
            selects.forEach(function(select) {
                const selectedValue = select.value;
                select.setAttribute('data-current', selectedValue);
            });
            
            selects.forEach(function(select) {
                select.addEventListener('change', function() {
                    const field = this.getAttribute('data-field');
                    const newInputtype = this.value;
                    const index = this.getAttribute('data-index');
                    
                    console.log('Inputtype 변경:', {
                        field: field,
                        oldValue: this.options[this.selectedIndex].text,
                        newValue: newInputtype,
                        index: index
                    });
                    
                    // data-current 속성 업데이트 (색상 변경을 위해)
                    this.setAttribute('data-current', newInputtype);
                    
                    // 포커스 해제해도 색상 유지
                    this.blur();
                    
                    // TODO: 서버에 변경사항 전송
                    // fetch('update_inputtype.php', {
                    //     method: 'POST',
                    //     headers: {
                    //         'Content-Type': 'application/json',
                    //     },
                    //     body: JSON.stringify({
                    //         field: field,
                    //         inputtype: newInputtype,
                    //         agentid: '<?php echo $agentid; ?>',
                    //         studentid: <?php echo $studentid; ?>
                    //     })
                    // });
                    
                    // 시각적 피드백
                    this.style.background = '#dbeafe';
                    setTimeout(() => {
                        this.style.background = 'white';
                    }, 500);
                });
            });
            
            // 상태 변경 이벤트 처리
            const statusSelects = document.querySelectorAll('.status-select');
            
            // 초기 로드 시 선택된 값으로 data-current 설정
            statusSelects.forEach(function(select) {
                const selectedValue = select.value;
                select.setAttribute('data-current', selectedValue);
            });
            
            statusSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    const field = this.getAttribute('data-field');
                    const newStatus = this.value;
                    const index = this.getAttribute('data-index');
                    
                    console.log('상태 변경:', {
                        field: field,
                        newStatus: newStatus,
                        index: index
                    });
                    
                    // data-current 속성 업데이트 (색상 변경을 위해)
                    this.setAttribute('data-current', newStatus);
                    
                    // 포커스 해제해도 색상 유지
                    this.blur();
                    
                    // TODO: 서버에 변경사항 전송
                    // fetch('update_status.php', {
                    //     method: 'POST',
                    //     headers: {
                    //         'Content-Type': 'application/json',
                    //     },
                    //     body: JSON.stringify({
                    //         field: field,
                    //         status: newStatus,
                    //         agentid: '<?php echo $agentid; ?>',
                    //         studentid: <?php echo $studentid; ?>
                    //     })
                    // });
                    
                    // 시각적 피드백
                    this.style.background = '#d1fae5';
                    setTimeout(() => {
                        this.style.background = 'white';
                    }, 500);
                });
            });
            
            // Rules.yaml 토글 이벤트 처리
            const rulesYamlToggles = document.querySelectorAll('.rules-yaml-toggle');
            
            rulesYamlToggles.forEach(function(button) {
                button.addEventListener('click', function() {
                    const field = this.getAttribute('data-field');
                    const index = this.getAttribute('data-index');
                    const current = this.getAttribute('data-current');
                    const newValue = current === '1' ? '0' : '1';
                    
                    console.log('Rules.yaml 적용 여부 변경:', {
                        field: field,
                        oldValue: current === '1' ? '적용' : '미적용',
                        newValue: newValue === '1' ? '적용' : '미적용',
                        index: index
                    });
                    
                    // 상태 업데이트
                    this.setAttribute('data-current', newValue);
                    
                    // 아이콘과 텍스트 변경
                    const spans = this.querySelectorAll('span');
                    if (newValue === '1') {
                        if (spans.length >= 2) {
                            spans[0].textContent = '✅';
                            spans[0].style.color = '#10b981';
                            spans[0].style.fontWeight = 'bold';
                            spans[1].textContent = '적용';
                            spans[1].style.color = '#10b981';
                            spans[1].style.fontWeight = 'bold';
                        }
                    } else {
                        if (spans.length >= 2) {
                            spans[0].textContent = '❌';
                            spans[0].style.color = '#ef4444';
                            spans[0].style.fontWeight = 'normal';
                            spans[1].textContent = '미적용';
                            spans[1].style.color = '#9ca3af';
                            spans[1].style.fontWeight = 'normal';
                        }
                    }
                    
                    // TODO: 서버에 변경사항 전송
                    // fetch('update_rules_yaml.php', {
                    //     method: 'POST',
                    //     headers: {
                    //         'Content-Type': 'application/json',
                    //     },
                    //     body: JSON.stringify({
                    //         field: field,
                    //         in_rules_yaml: newValue === '1',
                    //         agentid: '<?php echo $agentid; ?>',
                    //         studentid: <?php echo $studentid; ?>
                    //     })
                    // });
                    
                    // 시각적 피드백
                    this.style.background = '#dbeafe';
                    setTimeout(() => {
                        this.style.background = 'none';
                    }, 300);
                });
            });
        });
    </script>
</body>
</html>


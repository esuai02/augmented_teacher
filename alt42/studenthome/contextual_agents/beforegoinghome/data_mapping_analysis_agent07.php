<?php
/**
 * Agent07 데이터 매핑 분석 도구
 * rules.yaml 데이터와 DB 데이터를 비교 분석하여 시각화
 * 
 * @file data_mapping_analysis_agent07.php
 * @location alt42/studenthome/contextual_agents/beforegoinghome/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// 페이지 설정
$PAGE->set_url('/studenthome/contextual_agents/beforegoinghome/data_mapping_analysis_agent07.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Agent07 데이터 매핑 분석');

// 학생 ID 파라미터
$studentid = optional_param('studentid', 0, PARAM_INT);
$agentid = optional_param('agentid', 'agent07', PARAM_TEXT);

// 권한 체크
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    $studentid = $USER->id;
}

if (!$studentid) {
    die('학생 ID가 필요합니다.');
}

/**
 * rules.yaml 파일에서 필드 추출
 */
function extractFieldsFromRulesYaml($agentPath) {
    $rulesFile = $agentPath . '/rules/rules.yaml';
    if (!file_exists($rulesFile)) {
        error_log("Rules.yaml 파일을 찾을 수 없습니다: {$rulesFile} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return [];
    }
    
    $content = file_get_contents($rulesFile);
    $fields = [];
    
    // YAML 파싱 (정규식 기반 - 더 정교하게)
    // field: 패턴으로 필드 추출 (따옴표 처리)
    preg_match_all('/field:\s*["\']?([a-zA-Z_][a-zA-Z0-9_.]+)["\']?/i', $content, $matches);
    if (!empty($matches[1])) {
        $fields = array_merge($fields, $matches[1]);
    }
    
    // depends_on 필드도 추출
    preg_match_all('/depends_on:\s*["\']?([a-zA-Z_][a-zA-Z0-9_.]+)["\']?/i', $content, $depMatches);
    if (!empty($depMatches[1])) {
        $fields = array_merge($fields, $depMatches[1]);
    }
    
    // value 필드에서 변수 참조 추출 (예: ${field_name})
    preg_match_all('/\$\{([a-zA-Z_][a-zA-Z0-9_.]+)\}/i', $content, $varMatches);
    if (!empty($varMatches[1])) {
        $fields = array_merge($fields, $varMatches[1]);
    }
    
    // 중첩 필드 추출 (예: goals.long_term)
    preg_match_all('/["\']([a-zA-Z_][a-zA-Z0-9_]*\.[a-zA-Z_][a-zA-Z0-9_]+)["\']/i', $content, $nestedMatches);
    if (!empty($nestedMatches[1])) {
        $fields = array_merge($fields, $nestedMatches[1]);
    }
    
    return array_unique($fields);
}

/**
 * data_access.php에서 사용하는 필드 추출
 */
function extractFieldsFromDataAccess($agentPath) {
    $dataAccessFile = $agentPath . '/rules/data_access.php';
    if (!file_exists($dataAccessFile)) {
        error_log("Data_access.php 파일을 찾을 수 없습니다: {$dataAccessFile} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return [];
    }
    
    $content = file_get_contents($dataAccessFile);
    $fields = [];
    
    // 컨텍스트 배열에서 필드 추출 (더 정교하게)
    // 'field' => 또는 "field" => 패턴
    preg_match_all('/[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]\s*=>/i', $content, $matches);
    if (!empty($matches[1])) {
        $fields = array_merge($fields, $matches[1]);
    }
    
    // $context['field'] 패턴 추출
    preg_match_all('/\$context\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]\]/i', $content, $contextMatches);
    if (!empty($contextMatches[1])) {
        $fields = array_merge($fields, $contextMatches[1]);
    }
    
    // DB 쿼리에서 필드 추출 (SELECT 절)
    preg_match_all('/SELECT\s+([^FROM]+)/is', $content, $selectMatches);
    if (!empty($selectMatches[1])) {
        foreach ($selectMatches[1] as $selectClause) {
            // 테이블 별칭 제외하고 필드명만 추출
            preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:,|FROM|$)/i', $selectClause, $fieldMatches);
            if (!empty($fieldMatches[1])) {
                $fields = array_merge($fields, $fieldMatches[1]);
            }
        }
    }
    
    // get_record에서 필드 추출
    preg_match_all('/get_record\([^,]+,\s*\[[\'"]([a-zA-Z_][a-zA-Z0-9_]*)[\'"]/i', $content, $recordMatches);
    if (!empty($recordMatches[1])) {
        $fields = array_merge($fields, $recordMatches[1]);
    }
    
    return array_unique($fields);
}

/**
 * DB에서 실제 데이터 확인
 */
function checkDBFields($studentid, $agentid) {
    global $DB;
    
    $dbFields = [];
    
    // agent07 관련 테이블 확인 (mdl_ 접두사 포함)
    $tables = [
        'alt42_interaction_targeting',
        'mdl_alt42_interaction_targeting',
        'alt42_student_profiles',
        'mdl_alt42_student_profiles',
        'alt42_onboarding',
        'mdl_alt42o_onboarding',
        'alt42_learning_history',
        'mdl_alt42o_learning_history',
        'alt42_calmness',
        'mdl_alt42_calmness',
        'alt42_goinghome',
        'mdl_alt42_goinghome',
        'user',
        'mdl_user'
    ];
    
    foreach ($tables as $table) {
        if ($DB->get_manager()->table_exists(new xmldb_table($table))) {
            try {
                $record = $DB->get_record($table, ['userid' => $studentid], '*', IGNORE_MISSING);
                if ($record) {
                    foreach (get_object_vars($record) as $field => $value) {
                        if ($field !== 'id' && $value !== null) {
                            $dbFields[$field] = [
                                'table' => $table,
                                'value' => $value,
                                'type' => gettype($value)
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking table {$table}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
        }
    }
    
    return $dbFields;
}

/**
 * 데이터 타입 식별 (survdata/sysdata/gendata)
 */
function identifyDataType($field, $value, $table) {
    // survdata: 사용자 입력 데이터 (설문, 폼 등)
    $surveyTables = ['alt42_onboarding', 'alt42_interaction_targeting', 'mdl_alt42o_onboarding'];
    $surveyFields = ['concept_progress', 'advanced_progress', 'math_confidence', 'parent_style', 
                     'exam_style', 'problem_preference', 'long_term_goal', 'short_term_goal',
                     'mid_term_goal', 'vacation_hours', 'grade_detail', 'course_level'];
    
    // sysdata: 시스템/DB 자동 데이터
    $systemTables = ['user', 'mdl_user', 'alt42_student_profiles', 'mdl_alt42_student_profiles'];
    $systemFields = ['firstname', 'lastname', 'email', 'timecreated', 'lastaccess', 'id', 
                     'userid', 'username', 'confirmed', 'deleted'];
    
    // gendata: AI 생성 데이터
    $genTables = ['alt42_goinghome', 'mdl_alt42_goinghome'];
    $genFields = ['generated_content', 'ai_analysis', 'llm_response', 'text', 'response'];
    
    // hybriddata: 계산/조합 데이터 (특정 패턴)
    $hybridFields = ['risk_score', 'learning_style', 'math_level', 'academy_grade'];
    
    if (in_array($table, $surveyTables) || in_array($field, $surveyFields)) {
        return 'survdata';
    } elseif (in_array($field, $hybridFields)) {
        return 'hybriddata';
    } elseif (in_array($table, $genTables) || in_array($field, $genFields)) {
        return 'gendata';
    } elseif (in_array($table, $systemTables) || in_array($field, $systemFields)) {
        return 'sysdata';
    } else {
        // 기본값은 sysdata
        return 'sysdata';
    }
}

// Agent07 경로 (서버 경로 기준)
$agentPath = '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting';
if (!is_dir($agentPath)) {
    // 상대 경로로 재시도
    $agentPath = __DIR__ . '/../../../../orchestration/agents/agent07_interaction_targeting';
    if (!is_dir($agentPath)) {
        die('Agent07 경로를 찾을 수 없습니다. [File: ' . __FILE__ . ', Line: ' . __LINE__ . ']');
    }
}

// 데이터 수집
$rulesFields = extractFieldsFromRulesYaml($agentPath);
$dataAccessFields = extractFieldsFromDataAccess($agentPath);
$dbFields = checkDBFields($studentid, $agentid);

// 분석 결과 생성
$analysis = [
    'rules_only' => [],      // rules.yaml에만 있는 필드
    'db_only' => [],          // DB에만 있는 필드
    'both' => [],             // 둘 다 있는 필드
    'mapping_mismatch' => [], // 매핑 불일치
    'data_access_applied' => [] // data_access.php에서 사용하는 필드
];

// rules.yaml 필드 분석
foreach ($rulesFields as $field) {
    $inDb = isset($dbFields[$field]);
    $inDataAccess = in_array($field, $dataAccessFields);
    
    if ($inDb) {
        $dbInfo = $dbFields[$field];
        $dataType = identifyDataType($field, $dbInfo['value'], $dbInfo['table']);
        
        $analysis['both'][] = [
            'field' => $field,
            'db_table' => $dbInfo['table'],
            'db_value' => $dbInfo['value'],
            'data_type' => $dataType,
            'in_data_access' => $inDataAccess
        ];
    } else {
        $analysis['rules_only'][] = [
            'field' => $field,
            'in_data_access' => $inDataAccess
        ];
    }
}

// DB에만 있는 필드
foreach ($dbFields as $field => $info) {
    if (!in_array($field, $rulesFields)) {
        $dataType = identifyDataType($field, $info['value'], $info['table']);
        $analysis['db_only'][] = [
            'field' => $field,
            'table' => $info['table'],
            'value' => $info['value'],
            'data_type' => $dataType
        ];
    }
}

// data_access.php에서 사용하는 필드
foreach ($dataAccessFields as $field) {
    $inRules = in_array($field, $rulesFields);
    $inDb = isset($dbFields[$field]);
    
    $analysis['data_access_applied'][] = [
        'field' => $field,
        'in_rules' => $inRules,
        'in_db' => $inDb,
        'db_info' => $inDb ? $dbFields[$field] : null
    ];
}

// 매핑 불일치 확인 (유사 필드명)
$similarFields = [
    'student_grade' => 'grade_detail',
    'study_style' => 'problem_preference',
    'study_hours_per_week' => 'vacation_hours',
    'goals.long_term' => 'long_term_goal',
    'academy_name' => 'academy_name'
];

foreach ($similarFields as $rulesField => $dbField) {
    $rulesExists = in_array($rulesField, $rulesFields);
    $dbExists = isset($dbFields[$dbField]);
    
    if ($rulesExists && $dbExists && $rulesField !== $dbField) {
        $analysis['mapping_mismatch'][] = [
            'rules_field' => $rulesField,
            'db_field' => $dbField,
            'db_table' => $dbFields[$dbField]['table'],
            'db_value' => $dbFields[$dbField]['value']
        ];
    }
}

// HTML 출력
echo $OUTPUT->header();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent07 데이터 매핑 분석</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .data-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }

        .data-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .data-card.rules-only {
            border-left: 4px solid #ffc107;
        }

        .data-card.db-only {
            border-left: 4px solid #dc3545;
        }

        .data-card.both {
            border-left: 4px solid #28a745;
        }

        .data-card.mismatch {
            border-left: 4px solid #fd7e14;
        }

        .field-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .field-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 5px;
        }

        .badge-survdata {
            background: #17a2b8;
            color: white;
        }

        .badge-sysdata {
            background: #6c757d;
            color: white;
        }

        .badge-gendata {
            background: #6f42c1;
            color: white;
        }

        .badge-hybriddata {
            background: #e83e8c;
            color: white;
        }

        .badge-yes {
            background: #28a745;
            color: white;
        }

        .badge-no {
            background: #dc3545;
            color: white;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .summary-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .summary-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="view_reports.php?studentid=<?php echo $studentid; ?>" class="back-btn">← 돌아가기</a>
        
        <h1>Agent07 데이터 매핑 분석</h1>
        <p class="subtitle">학생 ID: <?php echo $studentid; ?> | 분석 일시: <?php echo date('Y-m-d H:i:s'); ?></p>

        <!-- 요약 통계 -->
        <div class="summary">
            <div class="summary-card">
                <h3><?php echo count($analysis['rules_only']); ?></h3>
                <p>Rules.yaml에만 있는 필드</p>
            </div>
            <div class="summary-card">
                <h3><?php echo count($analysis['db_only']); ?></h3>
                <p>DB에만 있는 필드</p>
            </div>
            <div class="summary-card">
                <h3><?php echo count($analysis['both']); ?></h3>
                <p>둘 다 있는 필드</p>
            </div>
            <div class="summary-card">
                <h3><?php echo count($analysis['mapping_mismatch']); ?></h3>
                <p>매핑 불일치</p>
            </div>
        </div>

        <!-- 1. Rules.yaml에만 있는 필드 -->
        <?php if (!empty($analysis['rules_only'])): ?>
        <div class="section">
            <h2 class="section-title">1. Rules.yaml에만 있는 필드 (DB에 없음)</h2>
            <div class="data-grid">
                <?php foreach ($analysis['rules_only'] as $item): ?>
                <div class="data-card rules-only">
                    <div class="field-name"><?php echo htmlspecialchars($item['field']); ?></div>
                    <div class="field-info">
                        <span class="badge <?php echo $item['in_data_access'] ? 'badge-yes' : 'badge-no'; ?>">
                            Data Access: <?php echo $item['in_data_access'] ? '사용' : '미사용'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. DB에만 있는 필드 -->
        <?php if (!empty($analysis['db_only'])): ?>
        <div class="section">
            <h2 class="section-title">2. DB에만 있는 필드 (Rules.yaml에 없음)</h2>
            <div class="data-grid">
                <?php foreach ($analysis['db_only'] as $item): ?>
                <div class="data-card db-only">
                    <div class="field-name"><?php echo htmlspecialchars($item['field']); ?></div>
                    <div class="field-info">테이블: <?php echo htmlspecialchars($item['table']); ?></div>
                    <div class="field-info">
                        <span class="badge badge-<?php echo $item['data_type']; ?>">
                            <?php echo strtoupper($item['data_type']); ?>
                        </span>
                    </div>
                    <div class="field-info">값: <?php echo htmlspecialchars(substr(strval($item['value']), 0, 50)); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. 둘 다 있는 필드 -->
        <?php if (!empty($analysis['both'])): ?>
        <div class="section">
            <h2 class="section-title">3. Rules.yaml과 DB 둘 다 있는 필드</h2>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>DB 테이블</th>
                        <th>데이터 타입</th>
                        <th>Data Access 적용</th>
                        <th>DB 값 (일부)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['both'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['db_table']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $item['data_type']; ?>">
                                <?php echo strtoupper($item['data_type']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $item['in_data_access'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_data_access'] ? '사용' : '미사용'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(substr(strval($item['db_value']), 0, 50)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- 4. 매핑 불일치 -->
        <?php if (!empty($analysis['mapping_mismatch'])): ?>
        <div class="section">
            <h2 class="section-title">4. 매핑 불일치 (유사 필드명)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rules.yaml 필드</th>
                        <th>DB 필드</th>
                        <th>DB 테이블</th>
                        <th>DB 값 (일부)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['mapping_mismatch'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['rules_field']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['db_field']); ?></td>
                        <td><?php echo htmlspecialchars($item['db_table']); ?></td>
                        <td><?php echo htmlspecialchars(substr(strval($item['db_value']), 0, 50)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- 5. Data Access 적용 현황 -->
        <div class="section">
            <h2 class="section-title">5. Data Access.php 적용 현황</h2>
            <table>
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Rules.yaml 사용</th>
                        <th>DB 존재</th>
                        <th>DB 테이블</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['data_access_applied'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['field']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $item['in_rules'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_rules'] ? '사용' : '미사용'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $item['in_db'] ? 'badge-yes' : 'badge-no'; ?>">
                                <?php echo $item['in_db'] ? '존재' : '없음'; ?>
                            </span>
                        </td>
                        <td><?php echo $item['in_db'] ? htmlspecialchars($item['db_info']['table']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
echo $OUTPUT->footer();
?>


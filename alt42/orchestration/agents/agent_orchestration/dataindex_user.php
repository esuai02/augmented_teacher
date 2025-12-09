<?php
/**
 * 사용자별 실제 데이터 표시 도구 - dataindex.php의 사용자 데이터 버전
 * ?userid=... 파라미터로 특정 사용자의 실제 데이터를 표시
 * 
 * @file dataindex_user.php
 * @location alt42/orchestration/agents/agent_orchestration/
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $OUTPUT;
require_login();

// xmldb_table 클래스 로드
if (isset($CFG) && isset($CFG->libdir)) {
    require_once($CFG->libdir.'/ddllib.php');
}

// 파라미터 - userid 사용
$userid = optional_param('userid', null, PARAM_INT);
$agentid = optional_param('agentid', 'agent01_onboarding', PARAM_TEXT);

// userid가 없으면 현재 사용자 ID 사용
if (empty($userid)) {
    $userid = $USER->id;
}

// 권한 체크
$isTeacher = has_capability('moodle/course:manageactivities', context_system::instance());

if (!$isTeacher) {
    // 학생은 자신의 데이터만 볼 수 있음
    $userid = $USER->id;
}

// 사용자 정보 조회
$userInfo = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
if (!$userInfo) {
    die('사용자를 찾을 수 없습니다: ' . htmlspecialchars($userid) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
}

// 22개 에이전트 목록
$allAgents = [
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

// 에이전트 경로 확인
$agentBasePath = __DIR__ . '/../' . $agentid . '/rules/';
if (!file_exists($agentBasePath)) {
    $agentBasePath = __DIR__ . '/../../' . $agentid . '/rules/';
}

if (!file_exists($agentBasePath)) {
    die('에이전트 경로를 찾을 수 없습니다: ' . htmlspecialchars($agentid) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
}

// rules.yaml 파일 읽기
$rulesYamlPath = $agentBasePath . 'rules.yaml';
$rulesYamlContent = file_exists($rulesYamlPath) ? file_get_contents($rulesYamlPath) : '';

// rules.yaml에서 사용하는 필드 추출
$rulesFields = [];
if (!empty($rulesYamlContent)) {
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
    preg_match_all('/\$context\[\'([^\']+)\'\]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_unique($matches[1]);
        sort($dataAccessFields);
    }
    
    preg_match_all('/\$onboarding->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
    
    preg_match_all('/\$profile->([a-zA-Z_]+)/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        $dataAccessFields = array_merge($dataAccessFields, $matches[1]);
        $dataAccessFields = array_unique($dataAccessFields);
        sort($dataAccessFields);
    }
}

/**
 * 사용자의 실제 데이터 값 조회 함수
 */
function getUserFieldValue($userid, $fieldName, $tableName = '') {
    global $DB;
    
    $value = null;
    $source = '';
    
    // 1. alt42o_onboarding 테이블 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('alt42o_onboarding'))) {
        try {
            $columns = $DB->get_columns('alt42o_onboarding');
            if (isset($columns[$fieldName])) {
                $record = $DB->get_record('alt42o_onboarding', ['userid' => $userid], $fieldName, IGNORE_MISSING);
                if ($record && isset($record->$fieldName)) {
                    $value = $record->$fieldName;
                    $source = 'alt42o_onboarding';
                }
            }
        } catch (Exception $e) {
            error_log("Error getting field {$fieldName} from alt42o_onboarding: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // 2. alt42_goinghome 테이블 확인 (JSON 데이터)
    if ($value === null && $DB->get_manager()->table_exists(new xmldb_table('alt42_goinghome'))) {
        try {
            $record = $DB->get_record_sql(
                "SELECT * FROM {alt42_goinghome} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$userid],
                IGNORE_MISSING
            );
            if ($record && isset($record->text)) {
                $jsonData = json_decode($record->text, true);
                if (is_array($jsonData) && isset($jsonData[$fieldName])) {
                    $value = $jsonData[$fieldName];
                    $source = 'alt42_goinghome';
                }
            }
        } catch (Exception $e) {
            error_log("Error getting field {$fieldName} from alt42_goinghome: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // 3. mdl_alt42g_* 테이블 확인 (MATHKING DB)
    $mathkingFields = [
        // additional_info
        'favorite_food', 'favorite_fruit', 'favorite_snack', 'hobbies_interests', 'fandom_yn', 'data_consent',
        // learning_progress
        'notes', 'weekly_hours', 'academy_experience',
        // learning_goals
        'short_term_goal', 'mid_term_goal', 'long_term_goal', 'goal_note'
    ];
    
    if ($value === null && in_array($fieldName, $mathkingFields)) {
        try {
            // omniui/config.php에서 MATHKING DB 설정 로드
            $omniCandidates = [
                $_SERVER['DOCUMENT_ROOT'] . '/moodle/local/augmented_teacher/alt42/omniui/config.php',
                dirname(__DIR__, 2) . '/omniui/config.php',
                dirname(__DIR__, 3) . '/omniui/config.php'
            ];
            foreach ($omniCandidates as $cfgPath) {
                if (is_string($cfgPath) && file_exists($cfgPath) && is_readable($cfgPath)) {
                    include_once($cfgPath);
                    break;
                }
            }
            
            if (defined('MATHKING_DB_HOST') && defined('MATHKING_DB_NAME') && defined('MATHKING_DB_USER')) {
                $dsn = 'mysql:host=' . MATHKING_DB_HOST . ';dbname=' . MATHKING_DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // 테이블별로 분기 처리
                $tableName = '';
                if (in_array($fieldName, ['favorite_food', 'favorite_fruit', 'favorite_snack', 'hobbies_interests', 'fandom_yn', 'data_consent'])) {
                    $tableName = 'mdl_alt42g_additional_info';
                } elseif (in_array($fieldName, ['notes', 'weekly_hours', 'academy_experience'])) {
                    $tableName = 'mdl_alt42g_learning_progress';
                } elseif (in_array($fieldName, ['short_term_goal', 'mid_term_goal', 'long_term_goal', 'goal_note'])) {
                    $tableName = 'mdl_alt42g_learning_goals';
                }
                
                if (!empty($tableName)) {
                    $stmt = $pdo->prepare("SELECT {$fieldName} FROM {$tableName} WHERE userid = ?");
                    $stmt->execute([$userid]);
                    if ($row = $stmt->fetch()) {
                        if (isset($row[$fieldName]) && $row[$fieldName] !== null && $row[$fieldName] !== '') {
                            $value = $row[$fieldName];
                            $source = $tableName;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error getting field {$fieldName} from mdl_alt42g_additional_info: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // 4. user 테이블 확인
    if ($value === null && $DB->get_manager()->table_exists(new xmldb_table('user'))) {
        try {
            $columns = $DB->get_columns('user');
            if (isset($columns[$fieldName])) {
                $record = $DB->get_record('user', ['id' => $userid], $fieldName, IGNORE_MISSING);
                if ($record && isset($record->$fieldName)) {
                    $value = $record->$fieldName;
                    $source = 'user';
                }
            }
        } catch (Exception $e) {
            error_log("Error getting field {$fieldName} from user: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // 4. alt42_student_profiles 테이블 확인
    if ($value === null && $DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
        try {
            $record = $DB->get_record('alt42_student_profiles', ['userid' => $userid], '*', IGNORE_MISSING);
            if ($record && isset($record->profile_data)) {
                $jsonData = json_decode($record->profile_data, true);
                if (is_array($jsonData) && isset($jsonData[$fieldName])) {
                    $value = $jsonData[$fieldName];
                    $source = 'alt42_student_profiles';
                }
            }
        } catch (Exception $e) {
            error_log("Error getting field {$fieldName} from alt42_student_profiles: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
    
    // 값 포맷팅
    if ($value !== null) {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        // 긴 문자열은 잘라서 표시
        if (is_string($value) && strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
    }
    
    return [
        'value' => $value,
        'source' => $source,
        'exists' => $value !== null
    ];
}

/**
 * 데이터 타입 식별 함수 (dataindex.php와 동일)
 */
function identifyDataType($fieldName, $rulesContent = '', $dataAccessContent = '', $tableName = '', $viewReportsContent = '') {
    $type = 'unknown';
    $evidence = [];
    $dbApplied = false;
    
    // 간단한 타입 식별 로직 (dataindex.php와 동일한 로직 사용)
    if (!empty($rulesContent)) {
        if (preg_match('/source_type:\s*["\']?survey["\']?/i', $rulesContent) && 
            preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'survdata';
            $evidence[] = 'rules.yaml에서 survey로 정의됨';
            $dbApplied = true;
        } elseif (preg_match('/source_type:\s*["\']?system["\']?/i', $rulesContent) && 
                preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'sysdata';
            $evidence[] = 'rules.yaml에서 system으로 정의됨';
            $dbApplied = true;
        } elseif (preg_match('/source_type:\s*["\']?generated["\']?/i', $rulesContent) && 
                preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'gendata';
            $evidence[] = 'rules.yaml에서 generated(LLM modeling)로 정의됨';
            $dbApplied = true;
        } elseif (preg_match('/source_type:\s*["\']?interface["\']?/i', $rulesContent) && 
                preg_match('/field:\s*["\']?' . preg_quote($fieldName, '/') . '["\']?/i', $rulesContent)) {
            $type = 'uidata';
            $evidence[] = 'rules.yaml에서 interface로 정의됨';
            $dbApplied = true;
        }
    }
    
    return [
        'type' => $type,
        'evidence' => $evidence,
        'db_applied' => $dbApplied
    ];
}

// 모든 필드 수집
$allFields = array_unique(array_merge($rulesFields, $dataAccessFields));
sort($allFields);

// 각 필드에 대한 실제 데이터 조회
$userData = [];
foreach ($allFields as $field) {
    $fieldData = getUserFieldValue($userid, $field);
    $dataType = identifyDataType($field, $rulesYamlContent ?? '', $dataAccessContent ?? '', '', '');
    
    $userData[] = [
        'field' => $field,
        'value' => $fieldData['value'],
        'source' => $fieldData['source'],
        'exists' => $fieldData['exists'],
        'type' => $dataType['type'] ?? 'unknown',
        'db_applied' => $dataType['db_applied'] ?? false,
        'in_rules_yaml' => in_array($field, $rulesFields),
        'in_data_access' => in_array($field, $dataAccessFields)
    ];
}

// 통계 계산
$stats = [
    'total_fields' => count($allFields),
    'rules_fields' => count($rulesFields),
    'data_access_fields' => count($dataAccessFields),
    'fields_with_data' => count(array_filter($userData, function($d) { return $d['exists']; })),
    'fields_without_data' => count(array_filter($userData, function($d) { return !$d['exists']; }))
];

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사용자 데이터 조회 - <?php echo htmlspecialchars($userInfo->firstname . ' ' . $userInfo->lastname); ?></title>
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
        
        .user-info {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .user-info h2 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .user-info-item {
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .user-info-item strong {
            color: #374151;
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }
        
        .user-info-item span {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .agent-selector-container {
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .agent-selector-container select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .agent-selector-container select:hover {
            border-color: #667eea;
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
        
        .badge-uidata {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-unknown {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .value-cell {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            max-width: 400px;
            word-break: break-word;
        }
        
        .value-exists {
            color: #10b981;
            font-weight: 500;
        }
        
        .value-empty {
            color: #9ca3af;
            font-style: italic;
        }
        
        .source-cell {
            font-size: 0.85rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 사용자 정보 섹션 -->
        <div class="user-info">
            <h2>👤 사용자 정보</h2>
            <div class="user-info-grid">
                <div class="user-info-item">
                    <strong>사용자 ID</strong>
                    <span><?php echo htmlspecialchars($userid); ?></span>
                </div>
                <div class="user-info-item">
                    <strong>이름</strong>
                    <span><?php echo htmlspecialchars($userInfo->firstname . ' ' . $userInfo->lastname); ?></span>
                </div>
                <div class="user-info-item">
                    <strong>이메일</strong>
                    <span><?php echo htmlspecialchars($userInfo->email); ?></span>
                </div>
                <div class="user-info-item">
                    <strong>사용자명</strong>
                    <span><?php echo htmlspecialchars($userInfo->username); ?></span>
                </div>
            </div>
        </div>
        
        <!-- 에이전트 선택 -->
        <div class="agent-selector-container">
            <label for="agentSelector" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">에이전트 선택:</label>
            <select id="agentSelector" onchange="changeAgent()">
                <?php foreach ($allAgents as $agentId => $agentName): ?>
                    <option value="<?php echo $agentId; ?>" <?php echo $agentid === $agentId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($agentName); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="header">
            <h1>📊 사용자 데이터 조회</h1>
            <p><?php echo htmlspecialchars($agentid); ?> - 실제 데이터 값 표시</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">사용자: <?php echo htmlspecialchars($userInfo->firstname . ' ' . $userInfo->lastname); ?> (ID: <?php echo $userid; ?>)</p>
        </div>
        
        <!-- 통계 -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>전체 필드</h3>
                <div class="number"><?php echo $stats['total_fields']; ?></div>
            </div>
            <div class="stat-card">
                <h3>데이터 있음</h3>
                <div class="number" style="color: #10b981;"><?php echo $stats['fields_with_data']; ?></div>
            </div>
            <div class="stat-card">
                <h3>데이터 없음</h3>
                <div class="number" style="color: #dc2626;"><?php echo $stats['fields_without_data']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Rules.yaml 필드</h3>
                <div class="number"><?php echo $stats['rules_fields']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Data Access 필드</h3>
                <div class="number"><?php echo $stats['data_access_fields']; ?></div>
            </div>
        </div>
        
        <!-- 실제 데이터 테이블 -->
        <div class="section">
            <h2>📋 필드별 실제 데이터 값</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>Inputtype</th>
                        <th>실제 값</th>
                        <th>데이터 소스</th>
                        <th>Rules.yaml</th>
                        <th>Data Access</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userData as $data): 
                        $fieldName = htmlspecialchars($data['field']);
                        $type = $data['type'];
                        $value = $data['value'];
                        $source = $data['source'];
                        $exists = $data['exists'];
                    ?>
                    <tr>
                        <td><code><?php echo $fieldName; ?></code></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $type === 'survdata' ? 'surv' : 
                                    ($type === 'sysdata' ? 'sys' : 
                                    ($type === 'gendata' ? 'gen' : 
                                    ($type === 'uidata' ? 'uidata' : 'unknown'))); 
                            ?>">
                                <?php echo htmlspecialchars($type); ?>
                            </span>
                        </td>
                        <td class="value-cell <?php echo $exists ? 'value-exists' : 'value-empty'; ?>">
                            <?php if ($exists): ?>
                                <?php echo htmlspecialchars($value ?? 'NULL'); ?>
                            <?php else: ?>
                                <span style="color: #9ca3af;">(데이터 없음)</span>
                            <?php endif; ?>
                        </td>
                        <td class="source-cell">
                            <?php if ($source): ?>
                                <code><?php echo htmlspecialchars($source); ?></code>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($data['in_rules_yaml']): ?>
                                <span style="color: #10b981; font-weight: bold;">✅</span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">❌</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($data['in_data_access']): ?>
                                <span style="color: #10b981; font-weight: bold;">✅</span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">❌</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 데이터가 있는 필드만 표시 -->
        <div class="section">
            <h2>✅ 데이터가 있는 필드</h2>
            <?php 
            $fieldsWithData = array_filter($userData, function($d) { return $d['exists']; });
            if (empty($fieldsWithData)): 
            ?>
                <p style="color: #9ca3af; padding: 2rem; text-align: center;">데이터가 있는 필드가 없습니다.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>필드명</th>
                            <th>Inputtype</th>
                            <th>실제 값</th>
                            <th>데이터 소스</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fieldsWithData as $data): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($data['field']); ?></code></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $data['type'] === 'survdata' ? 'surv' : 
                                        ($data['type'] === 'sysdata' ? 'sys' : 
                                        ($data['type'] === 'gendata' ? 'gen' : 
                                        ($data['type'] === 'uidata' ? 'uidata' : 'unknown'))); 
                                ?>">
                                    <?php echo htmlspecialchars($data['type']); ?>
                                </span>
                            </td>
                            <td class="value-cell value-exists">
                                <?php echo htmlspecialchars($data['value'] ?? 'NULL'); ?>
                            </td>
                            <td class="source-cell">
                                <code><?php echo htmlspecialchars($data['source']); ?></code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // 에이전트 변경 함수
        function changeAgent() {
            const agentSelector = document.getElementById('agentSelector');
            const selectedAgent = agentSelector.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('agentid', selectedAgent);
            window.location.href = currentUrl.toString();
        }
    </script>
</body>
</html>


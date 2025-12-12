<?php
/**
 * 데이터 매핑 분석 도구 - Agent 08 (Calmness)
 * view_reports.php에서 사용하는 데이터와 rules.yaml, data_access.php를 비교 분석
 * 
 * @file data_mapping_analysis.php
 * @location alt42/orchestration/agents/agent08_calmness/rules/
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
    
    // factors.필드명 패턴 추출
    preg_match_all('/factors\[\'([^\']+)\'\]/', $dataAccessContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $field) {
            $dataAccessFields[] = 'factors.' . $field;
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
    
    // 테이블명 추출
    preg_match_all('/\{([a-z_]+)\}/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsTables = array_unique($matches[1]);
    }
    
    // $data['필드명'] 패턴으로 필드 추출 (JSON 응답 데이터)
    preg_match_all('/\$data\[\'([^\']+)\'\]/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // 설문 응답 필드 (responses 배열)
    preg_match_all('/\'([a-z_]+)\'\s*=>/', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        $viewReportsFields = array_merge($viewReportsFields, $matches[1]);
    }
    
    // 실제 DB 쿼리에서 사용하는 필드 추출
    preg_match_all('/SELECT\s+([^F]+)\s+FROM/i', $viewReportsContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $selectClause) {
            preg_match_all('/(\w+)/', $selectClause, $fieldMatches);
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

// alt42_calmness 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('alt42_calmness'))) {
    $dbTables[] = 'alt42_calmness';
    try {
        $columns = $DB->get_columns('alt42_calmness');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'alt42_calmness.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from alt42_calmness: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// abessi_today 테이블 구조 확인 (침착도 데이터)
if ($DB->get_manager()->table_exists(new xmldb_table('abessi_today'))) {
    $dbTables[] = 'abessi_today';
    try {
        $columns = $DB->get_columns('abessi_today');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'abessi_today.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from abessi_today: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// abessi_tracking 테이블 구조 확인 (포모도르/학습 추적)
if ($DB->get_manager()->table_exists(new xmldb_table('abessi_tracking'))) {
    $dbTables[] = 'abessi_tracking';
    try {
        $columns = $DB->get_columns('abessi_tracking');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'abessi_tracking.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from abessi_tracking: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// abessi_messages 테이블 구조 확인 (오답노트)
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

// alt42_goinghome 테이블 구조 확인
if ($DB->get_manager()->table_exists(new xmldb_table('alt42_goinghome'))) {
    $dbTables[] = 'alt42_goinghome';
    try {
        $columns = $DB->get_columns('alt42_goinghome');
        foreach ($columns as $colName => $colInfo) {
            $dbFields[] = 'alt42_goinghome.' . $colName;
        }
    } catch (Exception $e) {
        error_log("Error getting columns from alt42_goinghome: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
}

// 데이터 타입 분류 함수 (Agent 08 특화)
function classifyDataType($fieldName, $tableName = '') {
    // 설문 데이터 (survdata) - 사용자 입력
    $survFields = ['calmness', 'pomodoro', 'inefficiency', 'weekly_goal', 'daily_plan', 'pace_anxiety', 
                   'satisfaction', 'boredom', 'stress_level', 'positive_moment', 'problem_count', 
                   'error_note', 'concept_study', 'difficulty_level', 'easy_problems', 'self_improvement',
                   'missed_opportunity', 'intuition_solving', 'forced_solving', 'questions_asked',
                   'unsaid_words', 'rest_pattern', 'long_problem', 'study_amount', 'error_analysis_requested',
                   'learning_psychology_change_requested', 'self_reflection_triggered'];
    
    // 시스템 데이터 (sysdata) - DB에서 자동 조회
    $sysFields = ['level', 'timecreated', 'timefinished', 'duration', 'timemodified', 'student_check', 
                  'turn', 'hide', 'userid', 'id', 'score', 'type', 'timefinished'];
    
    // 생성 데이터 (gendata) - 계산/추론
    $genFields = ['calmnessGrade', 'pomodoroUsage', 'errorNoteCount', 'needsAttention', 'calm_score',
                  'baseline_calm', 'calm_trend', 'calm_decline_percentage', 'accuracy_rate', 
                  'solving_speed', 'consecutive_errors', 'hesitation_count', 'decline_type',
                  'decline_percentage', 'focus_decline_segments', 'session_duration', 'exam_mode',
                  'time_pressure_indicators', 'unstable_patterns_count', 'error_rate', 
                  'previous_day_calm', 'emotional_data_available', 'decline_cause', 'emotional_indicators',
                  'whiteboard_logs_available', 'thought_omission_count', 'error_causes_generated',
                  'intuition_problems_count', 'certainty_problems_count', 'average_solving_time',
                  'verification_skipped', 'problem_type', 'current_unit', 'student_level',
                  'problem_difficulty', 'learning_stage', 'error_type', 'stress_level',
                  'conflicting_signals', 'prerequisite_mastery', 'personal_baseline', 'calm_data_count',
                  'calm_variance', 'whiteboard_feedback_count'];
    
    // 하이브리드 데이터 (hybriddata) - 복합 계산
    $hybridFields = ['factors.recent_average', 'factors.recent_min', 'factors.recent_max', 
                     'factors.data_count'];
    
    // 필드명 기반 분류
    if (in_array($fieldName, $survFields) || strpos($tableName, 'goinghome') !== false) {
        return 'survdata';
    } elseif (in_array($fieldName, $sysFields) || strpos($tableName, 'calmness') !== false || 
              strpos($tableName, 'tracking') !== false || strpos($tableName, 'messages') !== false ||
              strpos($tableName, 'abessi_today') !== false) {
        return 'sysdata';
    } elseif (in_array($fieldName, $genFields) || in_array($fieldName, $hybridFields)) {
        // 하이브리드 필드는 별도 표시
        if (in_array($fieldName, $hybridFields)) {
            return 'hybriddata';
        }
        return 'gendata';
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
    
    // factors['필드명'] 패턴 (agent08 특화)
    if (strpos($dataAccessContent, "factors['" . $fieldName . "']") !== false) {
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
    
    // alt42_calmness 테이블 확인
    if ($DB->get_manager()->table_exists(new xmldb_table('alt42_calmness'))) {
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
    
    // abessi_today 테이블 확인
    if (!$exists && $DB->get_manager()->table_exists(new xmldb_table('abessi_today'))) {
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
    
    if ($exists) {
        $dbDataExists[] = [
            'field' => $field,
            'table' => $tableName,
            'type' => classifyDataType($field, $tableName),
            'sample' => $sampleValue
        ];
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
    // 중첩 필드 처리 (factors.recent_average 등)
    $baseFieldName = explode('.', $fieldName)[0];
    if (!in_array($fieldName, $rulesFields) && !in_array($baseFieldName, $rulesFields)) {
        $inDbNotInRules[] = $dbField;
    }
}

// 4. view_reports.php에서 사용하는데 rules.yaml에 없는 필드
$inViewReportsNotInRules = array_diff($viewReportsFields, $rulesFields);

// 5. 매핑 불일치 확인 (같은 데이터인데 다른 이름으로 사용)
$mappingMismatches = [];
// Agent 08 특화 유사 필드 매핑
$similarFields = [
    ['calmness', 'calm_score'],
    ['level', 'calm_score'],
    ['score', 'calm_score'],
    ['pomodoro', 'solving_speed'],
    ['error_note', 'consecutive_errors'],
    ['calmnessGrade', 'calm_score'],
    ['actualCalmness', 'calm_score'],
    ['errorNoteCount', 'consecutive_errors'],
    ['pomodoroUsage', 'solving_speed']
];

foreach ($similarFields as $pair) {
    $field1 = $pair[0];
    $field2 = $pair[1];
    $inViewReports = in_array($field1, $viewReportsFields);
    $inRules = in_array($field2, $rulesFields);
    
    if ($inViewReports && $inRules) {
        $mappingMismatches[] = [
            'view_reports_field' => $field1,
            'rules_field' => $field2,
            'type' => 'similar_concept'
        ];
    }
}

// 6. data_access.php에서 실제로 사용하는지 확인
$dataAccessUsage = [];
foreach ($rulesFields as $field) {
    $found = false;
    // 직접 사용
    if (strpos($dataAccessContent, "'" . $field . "'") !== false || 
        strpos($dataAccessContent, '"' . $field . '"') !== false) {
        $found = true;
    }
    // 중첩 필드 처리
    $baseField = explode('.', $field)[0];
    if ($baseField !== $field && 
        (strpos($dataAccessContent, "'" . $baseField . "'") !== false ||
         strpos($dataAccessContent, '"' . $baseField . '"') !== false)) {
        $found = true;
    }
    
    $dataAccessUsage[$field] = $found;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데이터 매핑 분석 - Agent 08 (Calmness)</title>
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
        
        .badge-hybrid {
            background: #e7d4ff;
            color: #5a1a7a;
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
        
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../agent_orchestration/dataindex.php" class="back-button">← 데이터 인덱스로 돌아가기</a>
        
        <div class="header">
            <h1>📊 데이터 매핑 분석 리포트 - Agent 08 (Calmness)</h1>
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
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'hybriddata' ? 'hybrid' : 'gen')); ?>">
                                        <?php echo $dataType; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($dataType === 'survdata') {
                                        echo '설문 응답 데이터 (사용자 입력) - data_access.php에 추가 필요';
                                    } elseif ($dataType === 'sysdata') {
                                        echo '시스템 데이터 (DB에서 자동 조회) - data_access.php에 추가 필요';
                                    } elseif ($dataType === 'gendata') {
                                        echo '생성 데이터 (계산/추론) - data_access.php에 계산 로직 추가 필요';
                                    } elseif ($dataType === 'hybriddata') {
                                        echo '하이브리드 데이터 (복합 계산) - data_access.php에 계산 로직 추가 필요';
                                    } else {
                                        echo '알 수 없음 - 분류 필요';
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
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'hybriddata' ? 'hybrid' : 'gen')); ?>">
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
                                    <span class="badge badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'hybriddata' ? 'hybrid' : 'gen')); ?>">
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
                                        echo '하이브리드 데이터 (복합 계산)';
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
                                <td>유사한 개념인데 다른 필드명으로 사용됨 - 매핑 통일 필요</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- 6. data_access.php 적용 여부 확인 -->
        <div class="section">
            <h2>✅ Data_access.php 적용 여부 확인</h2>
            <?php 
            $notUsed = array_filter($dataAccessUsage, function($used) { return !$used; });
            $used = array_filter($dataAccessUsage, function($used) { return $used; });
            ?>
            <p style="margin-bottom: 1rem;">
                <strong>사용 중:</strong> <?php echo count($used); ?>개 필드 | 
                <strong>미사용:</strong> <?php echo count($notUsed); ?>개 필드
            </p>
            <?php if (!empty($notUsed)): ?>
                <p style="color: #f59e0b; margin-bottom: 1rem;">다음 필드들이 rules.yaml에 정의되어 있지만 data_access.php에서 실제로 사용되지 않습니다:</p>
                <div class="field-list">
                    <?php foreach ($notUsed as $field => $used): ?>
                        <span class="field-item badge-warning"><?php echo htmlspecialchars($field); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>모든 rules.yaml 필드가 data_access.php에서 사용되고 있습니다. ✅</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 전체 필드 목록 -->
        <div class="section">
            <h2>📋 전체 필드 목록</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Rules.yaml 필드 (<?php echo count($rulesFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($rulesFields as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'hybriddata' ? 'hybrid' : 'gen')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
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
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">View Reports 필드 (<?php echo count($viewReportsFields); ?>)</h3>
                    <div class="field-list">
                        <?php foreach ($viewReportsFields as $field): ?>
                            <?php $dataType = classifyDataType($field); ?>
                            <span class="field-item badge-<?php echo $dataType === 'survdata' ? 'surv' : ($dataType === 'sysdata' ? 'sys' : ($dataType === 'hybriddata' ? 'hybrid' : 'gen')); ?>">
                                <?php echo htmlspecialchars($field); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

